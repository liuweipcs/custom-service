<?php

namespace app\modules\orders\controllers;

use app\components\Controller;
use app\modules\mails\models\MailOutbox;
use app\modules\orders\models\EbayOnlineListing;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\OrderTransactionKefu;
use app\common\MHelper;
use app\modules\orders\models\OrderList;
use app\modules\orders\models\OrderPackageKefu;
use app\modules\orders\models\OrderWalmartItem;
use app\modules\services\modules\amazon\components\Mail;
use app\modules\services\modules\walmart\models\GetOrder;
use app\modules\systems\models\ErpOrderApi;
use app\modules\aftersales\models\Domesticreturngoods;
use app\modules\users\models\UserRole;
use yii\helpers\Json;
use Yii;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Country;
use app\modules\orders\models\Warehouse;
use app\modules\orders\models\Logistic;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\mails\models\EbayInquiry;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\orders\models\CancelTransaction;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\services\modules\aliexpress\models\ExtendsBuyerAcceptGoodsTime;
use app\modules\systems\models\RefundAccount;
use app\modules\systems\models\Updatestatustask;
use app\modules\orders\models\OrderAmazonItem;
use app\modules\orders\models\Transactionrecord;
use app\modules\systems\models\TransactionAddress;
use app\modules\orders\models\OrderOtherSearch;
use app\modules\orders\models\OrderWishSearch;
use app\modules\orders\models\OrderAliexpressSearch;
use app\modules\orders\models\OrderAmazonSearch;
use app\modules\orders\models\OrderEbay;
use yii\data\Pagination;
use app\modules\systems\models\CurrencyRateKefu;
use app\modules\orders\models\OrderAliexpressKefu;
use yii\helpers\Url;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressEvaluateList;
use app\modules\services\modules\aliexpress\models\AliexpressOrder;
use app\modules\services\modules\paypal\models\PaypalInvoice;
use app\modules\orders\models\PaypalInvoiceRecord;
use app\modules\aftersales\models\AfterSalesRedirect;
use app\modules\orders\models\OrderInvoives;
use app\modules\orders\models\OrderUpdateLogKefu;
use app\modules\systems\models\PaypalAccount;
use app\modules\mails\models\EbayCancellations;
use app\modules\orders\models\OrderRemarkKefu;

class OrderController extends Controller {

    /**
     * 原订单详情，从ERP接口获取数据
     */
    public function actionOrderdetailscopy() {
        $this->isPopup = true;
        $order_id = $this->request->getQueryParam('order_id'); //平台订单号
        $platform = $this->request->getQueryParam('platform'); //平台code
        $system_order_id = $this->request->getQueryParam('system_order_id'); //系统订单号
        $transaction_id = $this->request->getQueryParam('transaction_id'); // ebay订单详情交易id
        //$system_order_id = 'CO170509003126';
        $orderinfo = [];

        if ($platform && ($order_id || $system_order_id || $transaction_id)) {
            if (!empty($transaction_id))
                $orderinfo = Order::getOrderStackByTransactionId($platform, $transaction_id);
            else
                $orderinfo = Order::getOrderStack($platform, $order_id, $system_order_id);
            if (!empty($orderinfo))
                $orderinfo = Json::decode(Json::encode($orderinfo), true);
            else
                $orderinfo = [];
        }
        //如果在erp没获取到交易信息  则在客服系统重新获取一遍
        if (!empty($orderinfo['trade'])) {
            foreach ($orderinfo['trade'] as $key => $value) {
                if ($value['receiver_email'] == "" || $value['payer_email'] == "") {
                    $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->asArray()->one();
                    if (!empty($transactionRecord)) {
                        $orderinfo['trade'][$key]['receiver_email'] = $transactionRecord['receiver_email'];
                        $orderinfo['trade'][$key]['payer_email'] = $transactionRecord['payer_email'];
                        $orderinfo['trade'][$key]['payment_status'] = $transactionRecord['payment_status'];
                    }
                }
            }
        }

        //组装库存和在途数
        if (!empty($orderinfo)) {
            // Amazon订单信息加上FBA/FBM信息
            if (!empty($orderinfo['info'])) {
                if ($orderinfo['info']['amazon_fulfill_channel'] == 'AFN')
                    $orderinfo['info']['amazon_fulfill_channel'] = 'FBA';
                if ($orderinfo['info']['amazon_fulfill_channel'] == 'MFN')
                    $orderinfo['info']['amazon_fulfill_channel'] = 'FBM';
                $orderinfo['info']['product_weight'] = 0;
            }
            if (!empty($orderinfo['product'])) {
                $result = isset($orderinfo['wareh_logistics']) && isset($orderinfo['wareh_logistics']['warehouse']);
                foreach ($orderinfo['product'] as $key => $value) {

                    $orderinfo['info']['product_weight'] += $value['product_weight'] * $value['quantity'];

                    list($stock, $on_way_count) = [null, null];
                    if ($result) {
                        $data = VHelper::getProductStockAndOnCount($value['sku'], $orderinfo['wareh_logistics']['warehouse']['warehouse_code']);
                        $stock = $data['available_stock'];
                        $on_way_count = $data['on_way_stock'];
                    }
                    $orderinfo['product'][$key]['stock'] = $stock;
                    $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;

                    //如果有产品的asinval则组装产品详情的链接地址
                    if (isset($value['asinval'])) {
                        $orderinfo['product'][$key]['detail_link_href'] = '#';
                        $orderinfo['product'][$key]['detail_link_title'] = null;
                        $site_code = Account::getSiteCode($orderinfo['info']['account_id'], Platform::PLATFORM_CODE_AMAZON);

                        if (!empty($value['asinval']) && !empty($site_code)) {
                            $link_info = VHelper::getProductDetailLinkHref($site_code, $value['asinval']);
                            $orderinfo['product'][$key]['detail_link_href'] = $link_info['href'];
                            $orderinfo['product'][$key]['detail_link_title'] = $link_info['title'];
                        }
                    }
                }
            }

            //付款帐号与收款帐号
            /* if(!empty($orderinfo['trade'])){

              foreach ($orderinfo['trade'] as $key => $value) {

              $transactionId =$value['transaction_id'];

              $PayMessage =VHelper::getTransactionAccount($transactionId);
              //var_dump($PayMessage);exit;

              if(!empty($PayMessage) && isset($PayMessage[0])){
              // var_dump($PayMessage);exit;

              $orderinfo['trade'][$key]['receiver_business'] = $PayMessage[0]['receiver_business'];

              $orderinfo['trade'][$key]['payer_email'] =$PayMessage[0]['payer_email'];

              }else{

              $orderinfo['trade'][$key]['receiver_business'] = "暂无信息";

              $orderinfo['trade'][$key]['payer_email'] = "暂无信息";
              }
              }
              } */
        } else {

            $orderinfo['info'] = null;
        }

        //获取订单的退货退款数据
        //$orderinfo['refund_data'] = OrderReturnRefund::getOrderReturnRefundList($platform,$system_order_id);
        //$afterSalesOrders = AfterSalesOrder::getByOrderId(Platform::PLATFORM_CODE_ALI, $orderinfo['info']['order_id']); orderdetails

        $afterSalesOrders = [];
        if (!empty($orderinfo['info']['order_id'])) {
            $afterSalesOrders = AfterSalesOrder::getByOrderId($platform, $orderinfo['info']['order_id']);
        }
        $countires = Country::getCodeNamePairs();
        $warehouseList = Warehouse::getWarehouseList();

        //paypal账号信息
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }

        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }
//        if(in_array(Yii::$app->user->identity->login_name,['何贞'])){
        return $this->render('detail/index', [
                    'order_id' => $order_id,
                    'info' => $orderinfo,
                    'paypallist' => $palPalList,
                    'countries' => $countires,
                    'warehouseList' => $warehouseList,
                    'afterSalesOrders' => $afterSalesOrders,
                    'platform' => $platform,
                    'isAuthority' => $isAuthority
        ]);
    }

    /**
     * 通过客服系统从库获取订单详情，优化访问速度
     */
    public function actionOrderdetails() {
        $this->isPopup = true;
        //退款跟进表id
        $returnid = $this->request->getQueryParam('id');
        //平台订单号
        $order_id = $this->request->getQueryParam('order_id');
        //平台code
        $platform = $this->request->getQueryParam('platform');
        //系统订单号
        $system_order_id = $this->request->getQueryParam('system_order_id');
        //交易id
        $transaction_id = $this->request->getQueryParam('transaction_id');
        $account = $this->request->getQueryParam('account_id');
        $type = $this->request->getQueryParam('type') ? 1 : 0;
        /*         * ********** 国内退件参数***************** */
        //是否退件
        $is_return = $this->request->getQueryParam('is_return') ? 1 : 0;
        //追踪号
        $track_number = $this->request->getQueryParam('track_number');
        //判断该订单是否为纠纷订单
        $dispute = "";

        if ($account) {
            //获取erp account_id
            $account_id = Account::find()
                        ->select('old_account_id')
                        ->where(['id' => $account])
                        ->asArray()
                        ->scalar();
        }
        if ($platform == "EB") {
            //ebay退货请求表
            $ebayrequests = EbayReturnsRequests::find()->where(['platform_order_id' => $order_id])->asArray()->one();
            //ebay质询表
            $ebayinquiry = EbayInquiry::find()->where(['platform_order_id' => $order_id])->asArray()->one();
            //ebay取消订单
            $ebaycancellations = EbayCancellations::find()->where(['legacy_order_id' => $order_id])->asArray()->one();
            if (!empty($ebayrequests) || !empty($ebayinquiry) || !empty($ebaycancellations)) {
                $dispute = 1;
            }
        }
        //订单信息
        $orderinfo = [];

        if ($platform && ($order_id || $system_order_id || $transaction_id)) {
            if (!empty($transaction_id)) {
                $orderinfo = OrderKefu::getOrderStackByTransactionId($platform, $transaction_id);
            } else {
                $orderinfo = OrderKefu::getOrderStack($platform, $order_id, $system_order_id, 1, $account_id);
            }
            if (!empty($orderinfo)) {
                $orderinfo = Json::decode(Json::encode($orderinfo), true);
            } else {
                $orderinfo = [];
            }
        }

        if (!empty($orderinfo['info'])) {
            //获取站点site_code
            if ($orderinfo['info']['platform_code'] == "AMAZON") {
                $orderinfo['info']['site_code'] = Account::findSiteCode($orderinfo['info']['account_id'], $orderinfo['info']['platform_code']);
            }
            //如果是速卖通平台的订单，获取买家剩余收货时间
            if ($orderinfo['info']['platform_code'] == Platform::PLATFORM_CODE_ALI) {
                $platformOrderId = $orderinfo['info']['platform_order_id'];
                $platformOrderIdArr = explode('-', $platformOrderId);
                if (!empty($platformOrderIdArr) && count($platformOrderIdArr) == 2) {
                    $platformOrderId = $platformOrderIdArr[0];
                }

                $newOrderInfo = AliexpressOrder::getNewOrderInfo($platformOrderId, $orderinfo['info']['account_id']);
                if (!empty($newOrderInfo) && !empty($newOrderInfo['target']['over_time_left'])) {
                    //买家确认收货结束时间
                    //注意接口返回的是美国时间
                    $orderinfo['info']['buyer_accept_goods_end_time'] = $newOrderInfo['target']['over_time_left'];
                }
            }
        }

        //如果在erp没获取到交易信息  则在客服系统重新获取一遍
        if (!empty($orderinfo['trade'])) {
            foreach ($orderinfo['trade'] as $key => $value) {
                if ($value['receiver_email'] == "" || $value['payer_email'] == "") {
                    $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->asArray()->one();
                    if (!empty($transactionRecord)) {
                        $orderinfo['trade'][$key]['receiver_email'] = $transactionRecord['receiver_email'];
                        $orderinfo['trade'][$key]['payer_email'] = $transactionRecord['payer_email'];
                        $orderinfo['trade'][$key]['payment_status'] = $transactionRecord['payment_status'];
                    }
                }
            }
        }
        //组装库存和在途数
        if (!empty($orderinfo)) {
            // Amazon订单信息加上FBA/FBM信息
            if (!empty($orderinfo['info'])) {
                if ($orderinfo['info']['amazon_fulfill_channel'] == 'AFN') {
                    $orderinfo['info']['amazon_fulfill_channel'] = 'FBA';
                }
                if ($orderinfo['info']['amazon_fulfill_channel'] == 'MFN') {
                    $orderinfo['info']['amazon_fulfill_channel'] = 'FBM';
                }
                $orderinfo['info']['product_weight'] = 0;
            }
            if (!empty($orderinfo['product'])) {
                $result = isset($orderinfo['wareh_logistics']) && isset($orderinfo['wareh_logistics']['warehouse']);
                foreach ($orderinfo['product'] as $key => $value) {
                    //获取
                    $url = "http://dc.yibainetwork.com/index.php/products/getProductPersonInfo";
                    //$skus = 'GS00001';测试数据
                    $skus = trim($value['sku']);
                    $json_str = [
                        'skus' => json_encode([$skus])
                    ];
                    //信息
                    $product_info = VHelper::http_post_json($url, $json_str);
                    if ($product_info->error == 0 && !empty($product_info->success_list)) {
                        $orderinfo['product'][$key]['create_user'] = $product_info->success_list[0][0]->create_user;
                        $orderinfo['product'][$key]['buyer'] = $product_info->success_list[0][0]->buyer;
                        $orderinfo['product'][$key]['editor'] = $product_info->success_list[0][0]->editor;
                    }


                    if ($value['platform_code'] == "EB") {
                        //获取item location
                        $orderinfo['product'][$key]['location'] = EbayOnlineListing::getItemLocation($value['item_id']);
                    }
                    $orderinfo['info']['product_weight'] += $value['product_weight'] * $value['quantity'];

                    list($stock, $on_way_count) = [null, null];
                    if ($result) {
                        $data = [];
                        $stock = isset($data['available_stock']) ? $data['available_stock'] : 0;
                        $on_way_count = isset($data['on_way_stock']) ? $data['on_way_stock'] : 0;
                    }
                    $orderinfo['product'][$key]['stock'] = $stock;
                    $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;

                    //如果有产品的asinval则组装产品详情的链接地址
                    if (isset($value['asinval'])) {
                        $orderinfo['product'][$key]['detail_link_href'] = '#';
                        $orderinfo['product'][$key]['detail_link_title'] = null;
                        $site_code = Account::getSiteCode($orderinfo['info']['account_id'], Platform::PLATFORM_CODE_AMAZON);

                        if (!empty($value['asinval']) && !empty($site_code)) {
                            $link_info = VHelper::getProductDetailLinkHref($site_code, $value['asinval']);
                            $orderinfo['product'][$key]['detail_link_href'] = $link_info['href'];
                            $orderinfo['product'][$key]['detail_link_title'] = $link_info['title'];
                        }
                    }
                }
            }
        } else {
            $orderinfo['info'] = null;
        }
        $afterSalesOrders = [];
        if (!empty($orderinfo['info']['order_id'])) {
            $afterSalesOrders = AfterSalesOrder::getByOrderId($platform, $orderinfo['info']['order_id']);
        }
        $countires = Country::getCodeNamePairsList();
        $warehouseList = Warehouse::getWarehouseListAll();
        //paypal账号信息
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        //加黑名单解决方案  部门主管及以上
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }

        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }
        //获取订单的交易号
        //$transaction_id = OrderEbay::getTransactionid($orderinfo['info']['order_id']);
        $orderinfo['info']['dispute'] = $dispute;
        if ($type) {
            return $this->render('detail/product_info', ['info' => $orderinfo, 'platform' => $platform, 'returnid' => $returnid]); //订单信息
        } else {
            return $this->render('detail/index', [
                        'is_return' => $is_return,
                        'returnid' => $returnid,
                        'track_number' => $track_number,
                        'order_id' => $order_id,
                        'info' => $orderinfo,
                        'paypallist' => $palPalList,
                        'countries' => $countires,
                        'warehouseList' => $warehouseList,
                        'afterSalesOrders' => $afterSalesOrders,
                        'platform' => $platform,
                        'isAuthority' => $isAuthority,
                        'transaction_id' => $transaction_id
            ]);
        }
    }

    /**
     * @desc 取消订单 永久作废
     */
    public function actionCancelorder() {
        $this->isPopup = true;
        $info['orderId'] = $this->request->getQueryParam('order_id');
        $info['platformCode'] = $this->request->getQueryParam('platform');

        if (Yii::$app->request->isPost) {
            $orderid = $this->request->post('order_id');
            $platformCode = $this->request->post('platformCode');
            $remark = $this->request->post('remark');
            if (empty($remark))
                $this->_showMessage('备注不能为空', false);
            //永久作废
            $return = Order::cancelOrder($platformCode, $orderid, null, $remark);
            if ($return === true)
                $this->_showMessage('取消订单成功', true, null, false, null, 'top.refreshTable(parent.location.reload());', false, 'msg');
            list($flag, $message) = $return;
            $this->_showMessage('取消订单失败，' . $message, false);
        }
        return $this->render('cancelremark', ['info' => $info]);
    }

    /**
     * 订单备注
     */
    public function actionMarkprocessed() {

        if (Yii::$app->request->isGet) {
            $orderId = $_GET['order_id'];
            if (empty($orderId)) {
                $this->_showMessage('订单号不存在', false);
            }
            $platform_code = $_GET['platform_code'];
            if (empty($platform_code)) {
                $this->_showMessage('无效的平台code', false);
            }
            $arr = array(
                'order_id' => $orderId,
                'platform_code' => $platform_code,
            );
            $data = json_encode($arr);
            $ErpOrderApi = new ErpOrderApi();
            $flag = $ErpOrderApi->setMarkprocessed($data);
            if ($flag->statusCode == 200) {
                die(json_encode([
                    'status' => 200,
                    'message' => $flag->message,
                ]));
            } else {
                die(json_encode([
                    'status' => 0,
                    'message' => $flag->message,
                ]));
            }
        }
    }

    /**
     * 发票
     * @return string
     */
    public function actionInvoice() {
        $orderID = Yii::$app->request->getQueryParam('order_id');
        $orderID = trim($orderID);
        $invoiceInfo = OrderInvoives::checkDetailByOrderId($orderID);
        $invoicedetail = $invoiceInfo['order_invoice_detail'];
        $invoicedetail = json_decode($invoicedetail);
        $platform_code = Yii::$app->request->getQueryParam('platform');
        $platform = OrderKefu::getOrderModel($platform_code);
        //订单主表信息
        $model = OrderKefu::model($platform->ordermain)->where(['order_id' => $orderID])->one();
        if (empty($model)) {
            $model = OrderKefu::model($platform->ordermaincopy)->where(['order_id' => $orderID])->one();
        }
        //订单详情数据
        $detail = OrderKefu::model($platform->orderdetail)->where(['order_id' => $orderID])->all();
        if (empty($detail)) {
            $detail = OrderKefu::model($platform->orderdetailcopy)->where(['order_id' => $orderID])->all();
        }
        $warehouse = Warehouse::findOne($model->warehouse_id);
        //VHelper::dump($_POST);
        if (Yii::$app->request->isPost) {
            $invoiceLogistics = $_POST['invoice_logistics'];
            $invoiceDetail = $_POST['invoices'];
            $orderID = $_POST['id'];
            $arr = array(
                'order_id' => $orderID,
                'warehouse_id' => $warehouse->warehouse_type,
                'invoice_data' => $invoiceDetail,
                'invoiceLogistics' => $invoiceLogistics,
            );
            $data = json_encode($arr);
            $ErpOrderApi = new ErpOrderApi();
            $flag = $ErpOrderApi->setOrderInvoice($data);
            if ($flag->status == 1) {
                $this->_showMessage($flag->mess, true, null, true, null, null, false);
            } else if ($flag->status == 5) {
                $this->_showMessage($flag->mess, true, null, true, null, null, false);
            } else {
                $this->_showMessage($flag->mess, false);
            }
        }

        $this->isPopup = true;
        return $this->render('invoice', [
                    'model' => $model,
                    'platform' => $platform_code,
                    'detail' => $detail,
                    'invoiceInfo' => $invoiceInfo,
                    'invoicedetail' => $invoicedetail
        ]);
    }

    /**
     * 导出订单结果
     * @return string
     */
    public function actionExportinvocie() {
        $post = explode(",", $_POST['invoice']);
        $orderID = $post[0];
        $platform_form = $post[1];
        $invoiceInfo = OrderInvoives::checkDetailByOrderId($orderID);
        $invoicedetail = $invoiceInfo['order_invoice_detail'];
        $invoicedetail = json_decode($invoicedetail);
        $platform = OrderKefu::getOrderModel($platform_form);
        //订单主表信息
        $model = OrderKefu::model($platform->ordermain)->where(['order_id' => $orderID])->one();
        if (empty($model)) {
            $model = OrderKefu::model($platform->ordermaincopy)->where(['order_id' => $orderID])->one();
        }
        $filename = 'INVOICE-' . date('YmdHis') . '-' . $platform_form;
        //订单详情数据
        $detail = OrderKefu::model($platform->orderdetail)->where(['order_id' => $model->order_id])->all();
        if (empty($detail)) {
            $detail = OrderKefu::model($platform->orderdetailcopy)->where(['order_id' => $model->order_id])->all();
        }
        $this->isPopup = true;
        return $this->render('downinvoice', [
                    'model' => $model,
                    'platform' => $platform_form,
                    'detail' => $detail,
                    'invoiceInfo' => $invoiceInfo,
                    'invoicedetail' => $invoicedetail,
                    'filename' => $filename
        ]);
    }

    /**
     * 永久作废恢复
     * @throws \yii\db\Exception
     */
    public function actionOrdertoinit() {
        if (empty($_REQUEST['selectIds'])) {
            $get_date = isset($_REQUEST['get_date']) ? $_REQUEST['get_date'] : null; //下单时间 发货时间 付款时间
            $begin_date = isset($_REQUEST['begin_date']) ? $_REQUEST['begin_date'] : null; //开始时间
            $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : null; //结束时间
            $condition_option = isset($_REQUEST['condition_option']) ? $_REQUEST['condition_option'] : null;
            $condition_value = isset($_REQUEST['condition_value']) ? $_REQUEST['condition_value'] : null;
            $platformCode = isset($_REQUEST['platform_code']) ? trim($_REQUEST['platform_code']) : null;
            $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //账号
            $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
            $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
            $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
            $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
            $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态
            $order_status = isset($_REQUEST['order_status']) ? trim($_REQUEST['order_status']) : null;
            $order_type = isset($_REQUEST['order_type']) ? trim($_REQUEST['order_type']) : null;
            $item_location = isset($_REQUEST['item_location']) ? $_REQUEST['item_location'] : null;

            if ($platformCode == Platform::PLATFORM_CODE_EB) {
                $user_id = Yii::$app->user->identity->id;
                $erp_account_id = Account::find()
                        ->select('old_account_id')
                        ->from(Account::tableName() . ' as t')
                        ->innerJoin('{{%user_account}} as t1', 't1.account_id = t.id')
                        ->where(['t1.user_id' => $user_id, 't1.platform_code' => $platformCode])
                        ->column();
                if (!empty($erp_account_id)) {
                    $account_id = $erp_account_id;
                } else {
                    $account_id = null;
                }
            }
            $params = 'batch_operate';
            $orders_arr = '';
            $pageCur = null;
            $pageSize = null;
            $orders_data = OrderList::getOrder_list($condition_option, $condition_value, $platformCode, $account_id, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $params, $order_status, $orders_arr, $order_type, $item_location);
            $order_ids = ltrim($orders_data['order_ids'], ',');
        } else {
            $order_ids = $_REQUEST['selectIds'];
        }
        $platform_code = $_REQUEST['platform_code'];

        $data = [
            'order_ids' => $order_ids,
            'platform_code' => $platform_code
        ];
       
        //永久作废恢复
        $erp_result = Order::Ordertoinit($data);
        if ($erp_result) {
            if ($erp_result['bool'] == true) {
                $this->_showMessage($erp_result['info'], true, null, false, null, 'top.window.location.reload();');
            }
        }
        $this->_showMessage($erp_result['info'], false, null, false, null, 'top.window.location.reload();');
    }

    /*     * *
     * 处理单个订单取消作废功能
     * * */

    public function actionOrdertoinitlist() {
        $order_id = $this->request->getQueryParam('orderid'); //平台订单号
        $platform = $this->request->getQueryParam('platform'); //平台code
        $data = [
            'order_ids' => $order_id,
            'platform_code' => $platform
        ];
        //永久作废恢复
        $erp_result = Order::Ordertoinit($data);
          if ($erp_result) {
            if ($erp_result['bool'] == true) {
                $this->_showMessage($erp_result['info'], true, null, false, null,'top.window.location.reload();');
            }
        }
        $this->_showMessage($erp_result['info'], false, null, false, null);
        
        
    }

    /**
     * 推送订单到仓库
     * @throws \yii\db\Exception
     */
    public function actionBatchsendorde() {
        if (empty($_REQUEST['selectIds'])) {
            $get_date = isset($_REQUEST['get_date']) ? $_REQUEST['get_date'] : null; //下单时间 发货时间 付款时间
            $begin_date = isset($_REQUEST['begin_date']) ? $_REQUEST['begin_date'] : null; //开始时间
            $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : null; //结束时间
            $condition_option = isset($_REQUEST['condition_option']) ? $_REQUEST['condition_option'] : null;
            $condition_value = isset($_REQUEST['condition_value']) ? $_REQUEST['condition_value'] : null;
            $platformCode = isset($_REQUEST['platform_code']) ? trim($_REQUEST['platform_code']) : null;
            $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //账号
            $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
            $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
            $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
            $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
            $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态
            $order_status = isset($_REQUEST['order_status']) ? trim($_REQUEST['order_status']) : null;
            $order_type = isset($_REQUEST['order_type']) ? trim($_REQUEST['order_type']) : null;
            $item_location = isset($_REQUEST['item_location']) ? $_REQUEST['item_location'] : null;
            $account_id = null;
            if ($platformCode == Platform::PLATFORM_CODE_EB) {
                $user_id = Yii::$app->user->identity->id;
                $erp_account_id = Account::find()
                        ->select('old_account_id')
                        ->from(Account::tableName() . ' as t')
                        ->innerJoin('{{%user_account}} as t1', 't1.account_id = t.id')
                        ->where(['t1.user_id' => $user_id, 't1.platform_code' => $platformCode])
                        ->column();
                if (!empty($erp_account_id)) {
                    $account_id = $erp_account_id;
                } else {
                    $account_id = null;
                }
            }
            $params = 'batch_operate';
            $orders_arr = '';
            $pageCur = null;
            $pageSize = null;
            $orders_data = OrderList::getOrder_list($condition_option, $condition_value, $platformCode, $account_id, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $params, $order_status, $orders_arr, $order_type, $item_location);
            $order_ids = ltrim($orders_data['order_ids'], ',');
        } else {
            $order_ids = $_REQUEST['selectIds'];
        }
        $platform_code = $_REQUEST['platform_code'];

        $data = [
            'order_ids' => $order_ids,
            'platform_code' => $platform_code
        ];
        //推送订单到仓库
        $erp_result = Order::batchsendorde($data);
        if ($erp_result) {
            if ($erp_result['bool'] == true) {
                $this->_showMessage($erp_result['info'], true, null, false, null, 'top.window.location.reload();');
            }
        }
        $this->_showMessage($erp_result['info'], false, null, false, null, 'top.window.location.reload();');
    }

    /**
     * 优先配库
     * @throws \yii\db\Exception
     */
    public function actionSetprioritystatus() {
        if (empty($_REQUEST['selectIds'])) {
            $get_date = isset($_REQUEST['get_date']) ? $_REQUEST['get_date'] : null; //下单时间 发货时间 付款时间
            $begin_date = isset($_REQUEST['begin_date']) ? $_REQUEST['begin_date'] : null; //开始时间
            $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : null; //结束时间
            $condition_option = isset($_REQUEST['condition_option']) ? $_REQUEST['condition_option'] : null;
            $condition_value = isset($_REQUEST['condition_value']) ? $_REQUEST['condition_value'] : null;
            $platformCode = isset($_REQUEST['platform_code']) ? trim($_REQUEST['platform_code']) : null;
            $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //账号
            $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
            $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
            $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
            $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
            $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态
            $order_status = isset($_REQUEST['order_status']) ? trim($_REQUEST['order_status']) : null;
            $order_type = isset($_REQUEST['order_type']) ? trim($_REQUEST['order_type']) : null;
            $item_location = isset($_REQUEST['item_location']) ? $_REQUEST['item_location'] : null;
            $account_id = null;
            if ($platformCode == Platform::PLATFORM_CODE_EB) {
                $user_id = Yii::$app->user->identity->id;
                $erp_account_id = Account::find()
                        ->select('old_account_id')
                        ->from(Account::tableName() . ' as t')
                        ->innerJoin('{{%user_account}} as t1', 't1.account_id = t.id')
                        ->where(['t1.user_id' => $user_id, 't1.platform_code' => $platformCode])
                        ->column();
                if (!empty($erp_account_id)) {
                    $account_id = $erp_account_id;
                } else {
                    $account_id = null;
                }
            }
            $params = 'batch_operate';
            $orders_arr = '';
            $pageCur = null;
            $pageSize = null;
            $orders_data = OrderList::getOrder_list($condition_option, $condition_value, $platformCode, $account_id, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $params, $order_status, $orders_arr, $order_type, $item_location);
            $order_ids = ltrim($orders_data['order_ids'], ',');
        } else {
            $order_ids = $_REQUEST['selectIds'];
        }
        $platform_code = $_REQUEST['platform_code'];

        $data = [
            'order_ids' => $order_ids,
            'platform_code' => $platform_code
        ];
        //优先配库
        $erp_result = Order::setprioritystatus($data);
        if ($erp_result) {
            if ($erp_result['bool'] == true) {
                $this->_showMessage($erp_result['info'], true, null, false, null, 'top.window.location.reload();');
            }
        }
        $this->_showMessage($erp_result['info'], false, null, false, null, 'top.window.location.reload();');
    }

    /**
     * 手动给订单配库
     * @throws \yii\db\Exception
     */
    public function actionBatchallotstock() {
        if (empty($_REQUEST['selectIds'])) {
            $get_date = isset($_REQUEST['get_date']) ? $_REQUEST['get_date'] : null; //下单时间 发货时间 付款时间
            $begin_date = isset($_REQUEST['begin_date']) ? $_REQUEST['begin_date'] : null; //开始时间
            $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : null; //结束时间
            $condition_option = isset($_REQUEST['condition_option']) ? $_REQUEST['condition_option'] : null;
            $condition_value = isset($_REQUEST['condition_value']) ? $_REQUEST['condition_value'] : null;
            $platformCode = isset($_REQUEST['platform_code']) ? trim($_REQUEST['platform_code']) : null;
            $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //账号
            $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
            $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
            $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
            $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
            $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态
            $order_status = isset($_REQUEST['order_status']) ? trim($_REQUEST['order_status']) : null;
            $order_type = isset($_REQUEST['order_type']) ? trim($_REQUEST['order_type']) : null;
            $item_location = isset($_REQUEST['item_location']) ? $_REQUEST['item_location'] : null;
            $account_id = null;
            if ($platformCode == Platform::PLATFORM_CODE_EB) {
                $user_id = Yii::$app->user->identity->id;
                $erp_account_id = Account::find()
                        ->select('old_account_id')
                        ->from(Account::tableName() . ' as t')
                        ->innerJoin('{{%user_account}} as t1', 't1.account_id = t.id')
                        ->where(['t1.user_id' => $user_id, 't1.platform_code' => $platformCode])
                        ->column();
                if (!empty($erp_account_id)) {
                    $account_id = $erp_account_id;
                } else {
                    $account_id = null;
                }
            }
            $params = 'batch_operate';
            $orders_arr = '';
            $pageCur = null;
            $pageSize = null;
            $orders_data = OrderList::getOrder_list($condition_option, $condition_value, $platformCode, $account_id, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $params, $order_status, $orders_arr, $order_type, $item_location);
            $order_ids = ltrim($orders_data['order_ids'], ',');
        } else {
            $order_ids = $_REQUEST['selectIds'];
        }
        $platform_code = $_REQUEST['platform_code'];

        $data = [
            'order_ids' => $order_ids,
            'platform_code' => $platform_code
        ];
        //手动给订单配库
        $erp_result = Order::batchallotstock($data);
        if ($erp_result) {
            if ($erp_result['bool'] == true) {
                $this->_showMessage($erp_result['info'], true, null, false, null, 'top.window.location.reload();');
            }
        }
        $this->_showMessage($erp_result['info'], false, null, false, null, 'top.window.location.reload();');
    }

    /**
     * @desc 暂扣订单
     */
    public function actionHoldorder() {
        $this->isPopup = true;
        $info['orderId'] = $this->request->getQueryParam('order_id');
        $info['platformCode'] = $this->request->getQueryParam('platform');
        if (Yii::$app->request->isPost) {
            $orderid = $this->request->post('order_id');
            $platformCode = $this->request->post('platformCode');
            $remark = $this->request->post('remark');
            if (empty($remark))
                $this->_showMessage('备注不能为空', false);
            $return = Order::holdOrder($platformCode, $orderid, $remark);
            if ($return === true)
                $this->_showMessage('操作成功', true, null, true, array(), null, true, 'alert', true);
            list($flag, $message) = $return;
            $this->_showMessage('操作失败，', false);
        }
        return $this->render('hold', ['info' => $info]);
    }

    /**
     * @desc 取消暂扣订单
     */
    public function actionCancelholdorder() {
        $orderId = $this->request->getQueryParam('order_id');
        $platformCode = $this->request->getQueryParam('platform');
        $return = Order::cancelHoldOrder($platformCode, $orderId);
        if ($return === true)
            $this->_showMessage('操作成功', true, null, true);
        list($flag, $message) = $return;
        $this->_showMessage('操作失败', false);
    }

    /*
     * @desc 取消买方订单
     * */

    public function actionHandlecancelorder() {
        $orderId = $this->request->getQueryParam('order_id');
        $platform_order_id = $this->request->getQueryParam('platform');
        $buyerPaid = $this->request->getQueryParam('buyerPaid');
        $payTime = $this->request->getQueryParam('payTime');
        $cancelType = $this->request->post('cancel_reason');
        $accountID = $this->request->getQueryParam('account_id');
        $buyerPaid == 1 ? $buyerPaid = true : $buyerPaid = false;
        switch ($cancelType) {
            case 0:
                $cancelReason = 'OUT_OF_STOCK_OR_CANNOT_FULFILL';
                break;

            case 1:
                $cancelReason = 'BUYER_ASKED_CANCEL';
                break;

            case 2:
                $cancelReason = 'ADDRESS_ISSUES';
                break;
        }

        if ($buyerPaid == true && !empty($payTime)) {

            $time = date("Y-m-d\TH:i:s", strtotime($payTime)) . '.000Z';
            $data = ['buyerPaid' => $buyerPaid, 'buyerPaidDate' => array('value' => $time), 'cancelReason' => $cancelReason, 'legacyOrderId' => $platform_order_id];
        } else
            $data = ['buyerPaid' => $buyerPaid, 'cancelReason' => $cancelReason, 'legacyOrderId' => $platform_order_id];

        //获取帐号
        $accountName = Account::getHistoryAccount($accountID, Platform::PLATFORM_CODE_EB);
        if ($accountName != 'NoAccount')
            $ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
        else
            $this->_showMessage(\Yii::t('system', 'Operate Failed') . '。无法获取erp帐号', false);
        //资格检查
        $api = new PostOrderAPI($ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/cancellation/check_eligibility', 'post');
        $api->setData($data);
        $response = $api->sendHttpRequest('json');
        $responseJson = Json::decode($response);

        $warnInfo = ''; //提示的警告信息
        if ($responseJson['eligible'] == false) {
            $this->_showMessage(\Yii::t('system', 'Operate Failed') . '。失败原因：' . $responseJson['failureReason'][0], false);
        } else {
            $responseModel = new CancelTransaction();
            $responseModel->account_id = Yii::$app->user->id;
            $responseModel->create_by = Yii::$app->user->identity->user_name;
            $responseModel->create_time = date('Y-m-d H:i:s', time());

            $cancellationApi = new PostOrderAPI($ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/cancellation', 'post');
            $cancellationApi->setData($data);
            $cancellationResponse = $cancellationApi->sendHttpRequest('json');
            $cancellationResponseJson = Json::decode($cancellationResponse);

            if (isset($cancellationResponseJson['cancelId'])) {
                $responseModel->cancel_id = $cancellationResponseJson['cancelId'];
                $responseModel->status = 1;
                $responseModel->reason = 'Successful';
                $responseModel->save();
                //更改erp数据订单状态
                $result_cancel_order = Order::cancelOrder(Platform::PLATFORM_CODE_EB, $orderId, $platform_order_id, '');
                if ($result_cancel_order !== true) {

                    $flag = false;
                    //insert一条失败的数据
                    Updatestatustask::insertOne(Platform::PLATFORM_CODE_EB, $platform_order_id);
                    $warnInfo = 'eaby接口处理成功，但是erp永久作废订单失败';
                }
                $flag = true;
            } else {
                $flag = false;
                $warnInfo = $cancellationResponseJson['error'][0]['message'];
                $this->_showMessage(\Yii::t('system', 'Operate Failed') . '。失败原因:' . $warnInfo, false);
            }
            if ($flag)
                $this->_showMessage(\Yii::t('system', 'Operate Successful') . '。CANCEL_ID:' . $cancellationResponseJson['cancelId'], true);
            else
                $this->_showMessage(\Yii::t('system', 'Operate Successful') . '。CANCEL_ID:' . $cancellationResponseJson['cancelId'] . $warnInfo, true);
        }
    }

    /**
     * 取消订单(Ebay)
     * @return string
     */
    public function actionCanceltransaction() {
        $this->isPopup = true;
        $info = array();
        $info['order_id'] = $this->request->getQueryParam('orderid');
        $info['buyerPaid'] = $this->request->getQueryParam('payment_status') == 0 ? false : true;
        $info['payTime'] = $this->request->getQueryParam('paytime');
        $info['platform_order_id'] = $this->request->getQueryParam('platform_order_id');
        $info['transactionId'] = $this->request->getQueryParam('transaction_id');
        $info['account_id'] = $this->request->getQueryParam('account_id');
        $info['payment_status'] = $this->request->getQueryParam('payment_status');

        //是否有纠纷
        if (!empty($info['transactionId'])) {
            foreach ($info['transactionId'] as $value) {
                if (EbayInquiry::whetherExist($value))
                    $this->_showMessage("纠纷状态不可取消交易", false);
            }
        }
        if (EbayReturnsRequests::whetherExist($info['platform_order_id'])) {
            $this->_showMessage("纠纷状态不可取消交易", false);
        } elseif ($info['payment_status'] != 0 && ceil((time() - strtotime($info['payTime'])) / 86400) > 30) {
            $this->_showMessage("单子不能超过30天限制", false);
        }
        return $this->render('canceltransaction', [
                    'info' => $info
        ]);
    }

    /**
     * @desc 根据仓库获取物流
     */
    public function actionGetlogistics() {
        $warehouseId = (int) $this->request->getQueryParam('warehouse_id');
        if (empty($warehouseId))
            $this->_showMessage('无效的仓库ID', true, null, false, ['' => '---请选择---']);
        $logistics = Logistic::getWarehouseLogistics($warehouseId);
        $logisticList = [];
        if (!empty($logistics)) {
            foreach ($logistics as $row)
                $logisticList[$row->ship_code] = $row->ship_name;
        }
        $this->_showMessage('', true, null, false, $logisticList);
    }

    /**
     * @author alpha
     * @desc 订单列表公共方法
     * @param $condition_option
     * @param $condition_value
     * @param $platform_code
     * @param $account_ids
     * @param $warehouse_id
     * @param $ship_code
     * @param $ship_country
     * @param $currency
     * @param $get_date
     * @param $begin_date
     * @param $end_date
     * @param int $pageCur
     * @param int $pageSize
     * @param null $complete_status
     * @param null $order_status
     * @param null $item_location
     * @param null $remark
     * @return array|null
     * @throws \yii\db\Exception
     */
    protected function orderlists($condition_option, $condition_value, $platform_code, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur = 0, $pageSize = 0, $complete_status, $order_status, $item_location, $remark, $start_money = null, $end_money = null, $warehouse_res = [], $created_state = null, $paytime_state = null, $shipped_state = null) {

        if (empty($platform_code) || empty($condition_option)) {
            return null;
        }
        list($buyer_id, $item_id, $package_id, $paypal_id, $sku, $account_id, $order_number) = array(null, null, null, null, null, null, null);
        switch ($condition_option) {
            case 'buyer_id':
                if (strstr($condition_value, '--')) {
                    $arr = explode('--', $condition_value);
                    if (!empty($arr)) {
                        $condition_value = $arr[1];
                    }
                    $buyer_id = $condition_value;
                } else {
                    $buyer_id = $condition_value;
                }
                break;
            case 'order_number':
                $order_number = $condition_value;
                break;
            case 'item_id':
                $item_id = $condition_value;
                break;
            case 'package_id':
                $package_id = $condition_value;
                break;
            case 'paypal_id':
                $paypal_id = $condition_value;
                break;
            case 'sku':
                $sku = $condition_value;
                break;
        }
        if ($platform_code == Platform::PLATFORM_CODE_EB) {
            $user_id = Yii::$app->user->identity->id;
            $erp_account_id = Account::find()
                    ->select('old_account_id')
                    ->from(Account::tableName() . ' as t')
                    ->innerJoin('{{%user_account}} as t1', 't1.account_id = t.id')
                    ->where(['t1.user_id' => $user_id, 't1.platform_code' => $platform_code])
                    ->column();
            if (!empty($erp_account_id))
                $account_id = $erp_account_id;
        }

        switch ($platform_code) {
            case Platform::PLATFORM_CODE_EB:

                $orders_data = OrderEbay::getOrder_list($platform_code, $buyer_id, $item_id, $package_id, $paypal_id, $account_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $item_location, $remark, $warehouse_res, $created_state, $paytime_state, $shipped_state);
                break;
            case Platform::PLATFORM_CODE_WISH:
                $orders_data = OrderWishSearch::getOrder_list($platform_code, $buyer_id, $item_id, $package_id, $paypal_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $warehouse_res);
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $orders_data = OrderAmazonSearch::getOrder_list($platform_code, $buyer_id, $item_id, $package_id, $paypal_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $warehouse_res);
                break;
            case Platform::PLATFORM_CODE_ALI:
                $orders_data = OrderAliexpressSearch::getOrder_list($platform_code, $buyer_id, $item_id, $package_id, $paypal_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $order_status, $start_money, $end_money, $warehouse_res);
                break;
            default :
                $orders_data = OrderOtherSearch::getOrder_list($platform_code, $buyer_id, $order_number, $item_id, $package_id, $paypal_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $warehouse_res);
                break;
        }
        return $orders_data;
    }

    /**
     * 订单查询列表
     */
    public function actionList() {
        //下单时间  发货时间 付款时间
        $get_date = isset($_REQUEST['get_date']) ? $_REQUEST['get_date'] : null; //下单时间 发货时间 付款时间
        $begin_date = isset($_REQUEST['begin_date']) ? $_REQUEST['begin_date'] : null; //开始时间
        $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : null; //结束时间
        $condition_option = isset($_REQUEST['condition_option']) ? $_REQUEST['condition_option'] : null;
        $condition_value = isset($_REQUEST['condition_value']) ? trim($_REQUEST['condition_value']) : null;
        $platformCode = isset($_REQUEST['platform_codes']) ? trim($_REQUEST['platform_codes']) : null;
        $platformList = Platform::getPlatformAsArray();
        $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //账号
        $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
        $warehouse_type = isset($_REQUEST['warehouse_type']) ? trim($_REQUEST['warehouse_type']) : null; //仓库类型
        $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
        $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
        $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态
        $order_status = isset($_REQUEST['order_status']) ? $_REQUEST['order_status'] : null; //店铺订单状态
        $order_type = isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : null; //订单类型
        $item_location = isset($_REQUEST['item_location']) ? $_REQUEST['item_location'] : null;
        $remark = isset($_REQUEST['remark']) ? $_REQUEST['remark'] : null;
        $created_state = isset($_REQUEST['created_state']) ? $_REQUEST['created_state'] : null; //下单时间排序
        $paytime_state = isset($_REQUEST['paytime_state']) ? $_REQUEST['paytime_state'] : null; //付款时间排序
        $shipped_state = isset($_REQUEST['shipped_state']) ? $_REQUEST['shipped_state'] : null; //发货时间排序
        if ($platformCode == null) {
            $ImportPeople_list = Account::getIdNameKefuList('EB');
        } else {
            $ImportPeople_list = Account::getIdNameKefuList($platformCode);
        }
        //获取对应的仓库
        $warehouse_res = $this->getWarehouseType($warehouse_type);
        $ImportPeople_list[0] = '全部';
        $warehouse_name_list = Warehouse::getWarehouseListAll(); //发货仓库     
        $warehouse_name_list[0] = "全部";
        ksort($warehouse_name_list);
        $ship_code_list = Logistic::getLogisArrCodeName(); //发货方式
        $ship_code_list[0] = "全部";
        ksort($ship_code_list);
        $ship_country_list = Country::getCodeNamePairsList('cn_name'); //目的国
        array_unshift($ship_country_list, '全部');
        $currency_list = CurrencyRateKefu::getCurrencyList(); //货币类型
        array_unshift($currency_list, '全部');
        $complete_status_list = order::getOrderCompleteStatus(); //订单状态
        $account_id = null;
        //ebay 多一个账号id数组
        if ($platformCode == Platform::PLATFORM_CODE_EB) {
            $user_id = Yii::$app->user->identity->id;
            $erp_account_id = Account::find()
                    ->select('old_account_id')
                    ->from(Account::tableName() . ' as t')
                    ->innerJoin('{{%user_account}} as t1', 't1.account_id = t.id')
                    ->where(['t1.user_id' => $user_id, 't1.platform_code' => $platformCode])
                    ->column();
            if (!empty($erp_account_id))
                $account_id = $erp_account_id;
        }
        $countryList = Country::getCodeNamePairs('cn_name');
        $params = 'order_list';
        $orders_arr = '';
        $result = OrderList::getOrder_list($condition_option, $condition_value, $platformCode, $account_id, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $params, $order_status, $orders_arr, $order_type, $item_location, $remark, $warehouse_res, $created_state, $paytime_state, $shipped_state);
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => $result['count'],
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        //定义店铺订单状态数组

        $order_status_list = OrderKefu::getOrderStatus(null);
        $order_type_lists = [
            0 => '全部',
            1 => '普通订单',
            2 => '合并后的订单',
            3 => '被合并的订单',
            4 => '拆分的主订单',
            5 => '拆分后的子订单',
            6 => '普通订单[已创建过重寄单]',
            7 => '重寄后的订单',
            8 => '客户补款的订单'
        ];
        return $this->render('orderlist', [
                    'orders' => $result['data_list'],
                    'page' => $page,
                    'count' => $result['count'],
                    'condition_option' => $condition_option,
                    'condition_value' => $condition_value,
                    'platformList' => $platformList,
                    'platformCode' => $platformCode,
                    'account_ids' => $account_ids,
                    'ImportPeople_list' => $ImportPeople_list,
                    'warehouse_id' => $warehouse_id,
                    'warehouse_name_list' => $warehouse_name_list,
                    'ship_code' => $ship_code,
                    'ship_code_list' => $ship_code_list,
                    'ship_country' => $ship_country,
                    'ship_country_list' => $ship_country_list,
                    'currency' => $currency,
                    'currency_list' => $currency_list,
                    'complete_status' => $complete_status,
                    'complete_status_list' => $complete_status_list,
                    'get_date' => $get_date,
                    'begin_date' => $begin_date,
                    'end_date' => $end_date,
                    'order_status_list' => $order_status_list,
                    'order_status' => $order_status,
                    'order_type' => $order_type,
                    'countryList' => $countryList,
                    'order_type_lists' => $order_type_lists,
                    'item_location' => $item_location,
                    'remark' => $remark,
                    'warehouse_type' => $warehouse_type,
                    'created_state' => $created_state,
                    'paytime_state' => $paytime_state,
                    'shipped_state' => $shipped_state
        ]);
    }

    /**
     * @author alpha
     * @desc
     */
    public function actionGetaccountbyplatformcode() {
        $request = Yii::$app->request->post();
        $platformCode = isset($request['platform_code']) ? trim($request['platform_code']) : null;
        $ImportPeople_list = Account::getIdNameKefuList($platformCode);
        if (!empty($ImportPeople_list)) {
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $ImportPeople_list;
            die(Json::encode($response));
        } else {
            $response['status'] = 'error';
            $response['message'] = '暂无账号信息';
            die(Json::encode($response));
        }
    }

    /**
     * 平台订单导出
     * @throws \PHPExcel_Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function actionDownload() {
        set_time_limit(0);
        error_reporting(E_ERROR);
        $request = Yii::$app->request->get();
        $condition_option = isset($request['condition_option']) ? trim($request['condition_option']) : null;
        $condition_value = isset($request['condition_value']) ? trim($request['condition_value']) : null;
        $platform_code = isset($request['platform_code']) ? trim($request['platform_code']) : null;
        ;
        $account_ids = isset($request['account_ids']) ? trim($request['account_ids']) : null; //MALL账号
        $warehouse_id = isset($request['warehouse_id']) ? trim($request['warehouse_id']) : null; //发货仓库
        $get_date = isset($request['get_date']) ? trim($request['get_date']) : null;
        $begin_date = isset($request['begin_date']) ? trim($request['begin_date']) : null; //
        $end_date = isset($request['end_date']) ? trim($request['end_date']) : null;
        $ship_code = isset($request['ship_code']) ? trim($request['ship_code']) : null; //出货方式
        $ship_country = isset($request['ship_country']) ? trim($request['ship_country']) : null; //目的国
        $currency = isset($request['currency']) ? trim($request['currency']) : null; // 货币类型
        $complete_status = isset($request['complete_status']) ? trim($request['complete_status']) : null;
        $order_status = isset($request['order_status']) ? trim($request['order_status']) : null;
        $order_type = isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : null; //订单类型
        $item_location = isset($_REQUEST['item_location']) ? $_REQUEST['item_location'] : null;
        $remark = isset($_REQUEST['remark']) ? $_REQUEST['remark'] : null;
        $json = isset($request['json']) ? $request['json'] : []; //选中的行数据
        $orders_arr = [];

        if (!empty($json)) {
            //当前选择的订单id
            $orders_arr = explode(',', $json);
        } else {
            //判断
            //查询
            if (empty($platform_code)) {
                return null;
            }
            if (empty($account_id) && empty($condition_value) && empty($warehouse_id) && empty($ship_code) && empty($ship_country) && empty($shipped_date) && empty($end_date) && empty($currency) && empty($paytime) && empty($paytime_end)) {
                return null;
            }
            if (empty($buyer_id)) {
                $buyer_id = null;
            }
            if (empty($item_id)) {
                $item_id = null;
            }
            if (empty($package_id)) {
                $package_id = null;
            }
            if (empty($paypal_id)) {
                $paypal_id = null;
            }
            if (empty($sku)) {
                $sku = null;
            }
            if (empty($account_id)) {
                $account_id = null;
            }
            if (empty($order_number)) {
                $order_number = null;
            }
        }
        $account_id = null;
        if ($platform_code == Platform::PLATFORM_CODE_EB) {
            $user_id = Yii::$app->user->identity->id;
            $erp_account_id = Account::find()
                    ->select('old_account_id')
                    ->from(Account::tableName() . ' as t')
                    ->innerJoin('{{%user_account}} as t1', 't1.account_id = t.id')
                    ->where(['t1.user_id' => $user_id, 't1.platform_code' => $platform_code])
                    ->column();
            if (!empty($erp_account_id))
                $account_id = $erp_account_id;
        }
        $pageCur = null;
        $pageSize = null;
        $params = 'download_order';
        $orders_data = OrderList::getOrder_list($condition_option, $condition_value, $platform_code, $account_id, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $params, $order_status, $orders_arr, $order_type, $item_location, $remark);
        $data = $orders_data['data_list'];
        //导出数据数组
        $curRow = 2;
        $data_new = [];
        $mergeArr = [];
        $remarks = '';
        $mergeCol = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE'];
        foreach ($data as $k => &$v) {
            if (!empty($v['remark'])) {
                foreach ($v['remark'] as $val) {
                    $remarks .= $val . '-';
                }
                $data[$k]['remarks'] = $remarks;
            }

            $v['sku'] = explode(',', $v['sku']);
            if (count($v['sku']) > 1) {
                $preRow = $curRow;
                $nxtRow = $curRow + count($v['sku']) - 1;
                if ($curRow == $preRow) {
                    foreach ($mergeCol as $item) {
                        $mergeArr[] = "{$item}{$preRow}:{$item}{$nxtRow}";
                    }
                }
                $curRow += count($v['sku']) - 1;
                foreach ($v['sku'] as $kk => $vv) {
                    $tmp = $v;
                    $value = explode('*', $vv);
                    if (count($value) > 1) {
                        $tmp['sku'] = $value[0];
                        $tmp['quantity'] = $value[1];
                        $tmp['titleCn'] = $value[2];
                        $tmp['item_id'] = $value[3];
                    } else {
                        $tmp['sku'] = $vv;
                    }
                    $data_new[] = $tmp;
                }
            } else {
                foreach ($v['sku'] as $kk => $vv) {
                    $value = explode('*', $vv);
                    if (count($value) > 1) {
                        $v['sku'] = $value[0];
                        $v['quantity'] = $value[1];
                        $v['titleCn'] = $value[2];
                        $v['item_id'] = $value[3];
                    }
                    $data_new[] = $v;
                }
            }
        }
        $model = $data_new;
        //
        if ($platform_code == Platform::PLATFORM_CODE_AMAZON) {
            $comment_type = 'feedback';
            $comment_type_column = 'feedback';
            $dispute = 'review';
            $dispute_column = 'review';
        } else {
            $comment_type = '评价状态';
            $comment_type_column = 'comment_type';
            $dispute = '纠纷状态';
            $dispute_column = 'dispute';
        }
        //ebay 添加item location字段导出
        if ($platform_code == Platform::PLATFORM_CODE_EB) {
            $item_location = 'Item Location';
            $item_location_column = 'location';
        } else {
            $item_location_column = '';
            $item_location = '';
        }
        $headers = ['platform_code' => '平台', 'account_name' => '账号', 'account_short_name' => '帐号简称', 'order_id' => '系统订单号',
            'buyer_id' => '买家ID', 'email' => '买家邮箱', 'ship_country_name' => '目的国', 'site_code' => '站点',
            'total_price' => '订单金额', 'complete_status_text' => '订单状态', 'order_type' => '订单类型', 'created_time' => '下单时间',
            'paytime' => '付款时间', 'shipped_date' => '发货时间', 'track_number' => '物流单号', 'warehouse' => '发货仓库', 'logistics' => '发货方式',
            $comment_type_column => $comment_type, $dispute_column => $dispute, 'after_sale_ids' => '售后问题', 'inbox' => '站内信', 'sku' => 'SKU',
            'titleCn' => '产品中文名称', 'quantity' => '产品数量', 'item_id' => 'item_id', 'currency' => '订单货币',
            'refund_amount' => '退款金额（元）', 'profit' => '利润（元）', 'profit_rate' => '利润率（%）', 'remarks' => '订单备注', $item_location_column => $item_location];
        $columns = ['platform_code', 'account_name', 'account_short_name', 'order_id', 'buyer_id', 'email',
            'ship_country_name', 'site_code', 'total_price', 'complete_status_text',
            'order_type', 'created_time', 'paytime', 'shipped_date', 'track_number', 'warehouse',
            'logistics', $comment_type_column, $dispute_column, 'after_sale_ids', 'inbox', 'sku', 'titleCn', 'quantity',
            'item_id', 'currency', 'refund_amount', 'profit', 'profit_rate', 'remarks', $item_location_column];
        $this->exportExcel([
            'fileName' => 'orderAll_' . $platform_code . date('Y-m-d'),
            'models' => $model,
            'mode' => 'export',
            'columns' => $columns,
            'headers' => $headers,
            'format' => 'Excel5',
                ], $mergeArr);
    }

    /**
     * 通用方法
     * @param $config
     * @param array $mergeArr
     * @throws \PHPExcel_Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function exportExcel($config, $mergeArr = array()) {
        $config['class'] = 'moonland\phpexcel\Excel';
        $excel = \Yii::createObject($config);

        if ($excel->mode == 'export') {
            $sheet = new \PHPExcel();

            if (!isset($excel->models)) {
                throw new InvalidConfigException('Config models must be set');
            }
            $worksheet = $sheet->getActiveSheet();
            if (!empty($mergeArr)) {
                foreach ($mergeArr as $item) {
                    $worksheet->mergeCells($item);
                }
            }
            $excel->executeColumns($worksheet, $excel->models, isset($excel->columns) ? $excel->populateColumns($excel->columns) : [], isset($excel->headers) ? $excel->headers : []);
            if ($excel->asAttachment) {
                $excel->setHeaders();
            }
            $excel->writeFile($sheet);
        }
    }

    /**
     * ebay订单查询列表
     */
    public function actionEbaylist() {
        $condition_option = isset($_REQUEST['condition_option']) ? trim($_REQUEST['condition_option']) : null;
        $condition_value = isset($_REQUEST['condition_value']) ? trim($_REQUEST['condition_value']) : null;
        $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null;
        $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //ebay账号
        $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
        $get_date = isset($_REQUEST['get_date']) ? trim($_REQUEST['get_date']) : null;
        $begin_date = isset($_REQUEST['begin_date']) ? trim($_REQUEST['begin_date']) : null;
        $end_date = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null;
        $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
        $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
        $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
        $warehouse_type = isset($_REQUEST['warehouse_type']) ? trim($_REQUEST['warehouse_type']) : null; //仓库类型
        $item_location = isset($_REQUEST['item_location']) ? $_REQUEST['item_location'] : null;
        $remark = isset($_REQUEST['remark']) ? $_REQUEST['remark'] : null;
        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        $order_status = isset($_REQUEST['order_status']) ? trim($_REQUEST['order_status']) : null; //当前页
        $created_state = isset($_REQUEST['created_state']) ? trim($_REQUEST['created_state']) : null; //下单时间排序
        $paytime_state = isset($_REQUEST['paytime_state']) ? trim($_REQUEST['paytime_state']) : null; //付款时间排序   
        $shipped_state = isset($_REQUEST['shipped_state']) ? trim($_REQUEST['shipped_state']) : null; //发货时间排序      
        $platform_code = Platform::PLATFORM_CODE_EB;
        $complete_status_list = Order::getOrderCompleteStatus();
        $ImportPeople_list = Account::getIdNameKefuList(Platform::PLATFORM_CODE_EB); //ebay账号
        $ImportPeople_list[0] = '全部';
        ksort($ImportPeople_list);
        /*  $ImportPeople_list = asort($complete_status_list); */
        $warehouse_res = $this->getWarehouseType($warehouse_type);
        $warehouse_name_list = Warehouse::getWarehouseListAll(); //发货仓库
        $warehouse_name_list[0] = "全部";
        ksort($warehouse_name_list);
        $ship_code_list = Logistic::getLogisArrCodeName(); //发货方式
        $ship_code_list[0] = "全部";
        ksort($ship_code_list);
        $ship_country_list = Country::getCodeNamePairsList('cn_name'); //目的国
        array_unshift($ship_country_list, '全部');
        $currency_list = CurrencyRateKefu::getCurrencyList(); //货币类型
        array_unshift($currency_list, '全部');


        $result = $this->orderlists($condition_option, $condition_value, $platform_code, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $order_status, $item_location, $remark, null, null, $warehouse_res, $created_state, $paytime_state, $shipped_state);

        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => $result['count'],
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        return $this->render('ebaylist', [
                    'orders' => $result['data_list'],
                    'page' => $page,
                    'count' => $result['count'],
                    'condition_option' => $condition_option,
                    'condition_value' => $condition_value,
                    'platform' => $platform_code,
                    'complete_status_list' => $complete_status_list,
                    'complete_status' => $complete_status,
                    'account_ids' => $account_ids,
                    'ImportPeople_list' => $ImportPeople_list,
                    'warehouse_id' => $warehouse_id,
                    'warehouse_name_list' => $warehouse_name_list,
                    'get_date' => $get_date,
                    'begin_date' => $begin_date,
                    'end_date' => $end_date,
                    'ship_code' => $ship_code,
                    'ship_code_list' => $ship_code_list,
                    'ship_country' => $ship_country,
                    'ship_country_list' => $ship_country_list,
                    'currency' => $currency,
                    'currency_list' => $currency_list,
                    'item_location' => $item_location,
                    'remark' => $remark,
                    'warehouse_type' => $warehouse_type,
                    'created_state' => $created_state,
                    'paytime_state' => $paytime_state,
                    'shipped_state' => $shipped_state
        ]);
    }

    /**
     * wish 订单查询列表
     */
    public function actionWishlist() {
        $condition_option = isset($_REQUEST['condition_option']) ? trim($_REQUEST['condition_option']) : null;
        $condition_value = isset($_REQUEST['condition_value']) ? trim($_REQUEST['condition_value']) : null;
        $platform_code = Platform::PLATFORM_CODE_WISH;
        $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //wish账号
        $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
        $warehouse_type = isset($_REQUEST['warehouse_type']) ? trim($_REQUEST['warehouse_type']) : null; //仓库类型
        $get_date = isset($_REQUEST['get_date']) ? trim($_REQUEST['get_date']) : null;
        $begin_date = isset($_REQUEST['begin_date']) ? trim($_REQUEST['begin_date']) : null;
        $end_date = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null;
        $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
        $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
        $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态
        $ImportPeople_list = Account::getIdNameKefuList(Platform::PLATFORM_CODE_WISH); //wish账号
        $warehouse_res = $this->getWarehouseType($warehouse_type);
        $warehouse_name_list = Warehouse::getWarehouseListAll(); //发货仓库
        $warehouse_name_list[0] = "全部";
        ksort($warehouse_name_list);
        $ship_code_list = Logistic::getLogisArrCodeName(); //发货方式
        $ship_code_list[0] = "全部";
        ksort($ship_code_list);
        $ship_country_list = Country::getCodeNamePairsList('cn_name'); //目的国
        array_unshift($ship_country_list, '全部');
        $currency_list = CurrencyRateKefu::getCurrencyList(); //货币类型
        array_unshift($currency_list, '全部');
        $complete_status_list = order::getOrderCompleteStatus(); //订单状态
        $order_status = null;
        $item_location = null;
        $remark = null;
        $result = $this->orderlists($condition_option, $condition_value, $platform_code, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $order_status, $item_location, $remark, null, null, $warehouse_res);
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => $result['count'],
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        return $this->render('wishlist', [
                    'orders' => $result['data_list'],
                    'page' => $page,
                    'count' => $result['count'],
                    'condition_option' => $condition_option,
                    'condition_value' => $condition_value,
                    'platform' => $platform_code,
                    'account_ids' => $account_ids,
                    'ImportPeople_list' => $ImportPeople_list,
                    'warehouse_id' => $warehouse_id,
                    'warehouse_name_list' => $warehouse_name_list,
                    'get_date' => $get_date,
                    'begin_date' => $begin_date,
                    'end_date' => $end_date,
                    'ship_code' => $ship_code,
                    'ship_code_list' => $ship_code_list,
                    'ship_country' => $ship_country,
                    'ship_country_list' => $ship_country_list,
                    'currency' => $currency,
                    'currency_list' => $currency_list,
                    'complete_status' => $complete_status,
                    'complete_status_list' => $complete_status_list,
                    'warehouse_type' => $warehouse_type,
        ]);
    }

    /**
     * 沃尔玛erp订单列表
     */
    public function actionWalmartlisterp() {

        $cookie = \Yii::$app->request->cookies;
        return $this->renderList('walmartlisterp', []);
    }

    /**
     * Amazonerp订单列表
     */
    public function actionAmazonlisterp() {

        $cookie = \Yii::$app->request->cookies;
        return $this->renderList('amazonlisterp', []);
    }

    /**
     * 速卖通订单查询列表
     */
    public function actionAliexpresslist() {
        $get_date = isset($_REQUEST['get_date']) ? $_REQUEST['get_date'] : null; //下单时间 发货时间 付款时间
        $begin_date = isset($_REQUEST['begin_date']) ? $_REQUEST['begin_date'] : null; //开始时间
        $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : null; //结束时间
        $order_status = isset($_REQUEST['order_status']) ? $_REQUEST['order_status'] : null; //店铺订单状态
        $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态
        $condition_option = isset($_REQUEST['condition_option']) ? trim($_REQUEST['condition_option']) : null;
        $condition_value = isset($_REQUEST['condition_value']) ? trim($_REQUEST['condition_value']) : null;
        $platform_code = Platform::PLATFORM_CODE_ALI;
        $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //Aliexpresslist账号
        $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
        $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        $start_money = isset($_REQUEST['start_money']) ? trim($_REQUEST['start_money']) : null; //最低金额
        $end_money = isset($_REQUEST['end_money']) ? trim($_REQUEST['end_money']) : null; //最高金额
        $warehouse_type = isset($_REQUEST['warehouse_type']) ? trim($_REQUEST['warehouse_type']) : null; //仓库类型
        $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
        $ImportPeople_list = Account::getIdNameKefuList(Platform::PLATFORM_CODE_ALI); //Aliexpresslist账号
        $warehouse_res = $this->getWarehouseType($warehouse_type);
        $ImportPeople_list[0] = "全部";
        ksort($ImportPeople_list);
        $warehouse_name_list = Warehouse::getWarehouseListAll(); //发货仓库
        $warehouse_name_list[0] = "全部";
        ksort($warehouse_name_list);
        $ship_code_list = Logistic::getLogisArrCodeName(); //发货方式
        $ship_code_list[0] = "全部";
        ksort($ship_code_list);
        $ship_country_list = Country::getCodeNamePairsList('cn_name'); //目的国
        array_unshift($ship_country_list, '全部');
        $complete_status_list = order::getOrderCompleteStatus(); //订单状态
        //  $warehouse_id = '';
        $currency = '';
        $remark = null;
        //速卖通查询
        $result = $this->orderlists($condition_option, $condition_value, $platform_code, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $order_status, $item_location = null, $remark, $start_money, $end_money, $warehouse_res);
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => $result['count'],
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        //定义店铺订单状态数组
        $order_status_list = array(
            'PLACE_ORDER_SUCCESS' => '等待买家付款',
            'IN_CANCEL' => '买家申请取消',
            'WAIT_SELLER_SEND_GOODS' => '等待卖家发货',
            'SELLER_PART_SEND_GOODS' => '部分发货',
            'WAIT_BUYER_ACCEPT_GOODS' => '等待买家收货',
            'FUND_PROCESSING' => '买家确认收货后，等待退放款处理',
            'FINISH' => '已结束的订单',
            'IN_ISSUE' => '含纠纷的订单',
            'IN_FROZEN' => '冻结中的订单',
            'WAIT_SELLER_EXAMINE_MONEY' => '等待卖家确认金额',
            'RISK_CONTROL' => '订单处于风控24小时中',
        );
        return $this->render('aliexpresslist', [
                    'orders' => $result['data_list'],
                    'page' => $page,
                    'count' => $result['count'],
                    'condition_option' => $condition_option,
                    'condition_value' => $condition_value,
                    'platform' => $platform_code,
                    'account_ids' => $account_ids,
                    'ImportPeople_list' => $ImportPeople_list,
                    'get_date' => $get_date,
                    'begin_date' => $begin_date,
                    'order_status' => $order_status,
                    'end_date' => $end_date,
                    'ship_code' => $ship_code,
                    'ship_code_list' => $ship_code_list,
                    'ship_country' => $ship_country,
                    'ship_country_list' => $ship_country_list,
                    'order_status_list' => $order_status_list,
                    'complete_status' => $complete_status,
                    'complete_status_list' => $complete_status_list,
                    'start_money' => $start_money,
                    'end_money' => $end_money,
                    'warehouse_type' => $warehouse_type,
                    'warehouse_name_list' => $warehouse_name_list,
                    'warehouse_id' => $warehouse_id,
        ]);
    }

    /**
     * @desc amazon 订单查询列表
     */
    public function actionAmazonlist() {
        $condition_option = isset($_REQUEST['condition_option']) ? trim($_REQUEST['condition_option']) : null;
        $condition_value = isset($_REQUEST['condition_value']) ? trim($_REQUEST['condition_value']) : null;
        $platform_code = Platform::PLATFORM_CODE_AMAZON;
        $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //amazon账号
        $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
        $warehouse_type = isset($_REQUEST['warehouse_type']) ? trim($_REQUEST['warehouse_type']) : null; //仓库类型
        $get_date = isset($_REQUEST['get_date']) ? trim($_REQUEST['get_date']) : null;
        $end_date = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null;
        $begin_date = isset($_REQUEST['begin_date']) ? trim($_REQUEST['begin_date']) : null; //
        $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
        $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
        $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态
        $ImportPeople_list = Account::getIdNameKefuList(Platform::PLATFORM_CODE_AMAZON); //amazon账号
        $warehouse_res = $this->getWarehouseType($warehouse_type);
        $ImportPeople_list[0] = '全部';
        ksort($ImportPeople_list);
        $warehouse_name_list = Warehouse::getWarehouseListAll(); //发货仓库
        $warehouse_name_list[0] = "全部";
        ksort($warehouse_name_list);
        $ship_code_list = Logistic::getLogisArrCodeName(); //发货方式
        $ship_code_list[0] = "全部";
        ksort($ship_code_list);
        $ship_country_list = Country::getCodeNamePairsList('cn_name'); //目的国
        array_unshift($ship_country_list, '全部');
        $currency_list = CurrencyRateKefu::getCurrencyList(); //货币类型
        array_unshift($currency_list, '全部');
        $complete_status_list = order::getOrderCompleteStatus(); //订单状态
        $order_status = null;
        $item_location = null;
        $remark = null;
        $result = $this->orderlists($condition_option, $condition_value, $platform_code, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $order_status, $item_location, $remark, null, null, $warehouse_res);
        //创建分页组件
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => $result['count'],
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        return $this->render('amazonlist', [
                    'orders' => $result['data_list'],
                    'page' => $page,
                    'count' => $result['count'],
                    'condition_option' => $condition_option,
                    'condition_value' => $condition_value,
                    'platform' => $platform_code,
                    'account_ids' => $account_ids,
                    'ImportPeople_list' => $ImportPeople_list,
                    'warehouse_id' => $warehouse_id,
                    'warehouse_name_list' => $warehouse_name_list,
                    'get_date' => $get_date,
                    'begin_date' => $begin_date,
                    'end_date' => $end_date,
                    'ship_code' => $ship_code,
                    'ship_code_list' => $ship_code_list,
                    'ship_country' => $ship_country,
                    'ship_country_list' => $ship_country_list,
                    'currency' => $currency,
                    'currency_list' => $currency_list,
                    'complete_status' => $complete_status,
                    'complete_status_list' => $complete_status_list,
                    'warehouse_type' => $warehouse_type
        ]);
    }

    /**
     * 账号ID 站点获取站点邮箱
     * Amazon
     */
    public function actionGetsendemail() {
        $request = Yii::$app->request->post();
        $accouontId = $request['account_id'];
        $site = $request['site'];
        if ($site == 'es') {
            $site = 'sp';
        }
        $model = Account::find()->select('email')->where(['old_account_id' => $accouontId, 'site_code' => $site, 'status' => 1])->asArray()->one();
        $email = $model['email'];
        die(json_encode($email));
    }

    /**
     * 获取多个发件人邮箱
     */
    public function actionGetsendemails() {
        $request = Yii::$app->request->post();
        $four_ids = $request['four_ids'];
        $four_ids = ltrim($four_ids, ',');
        //拆分数组
        $four_ids_arr = explode(',', $four_ids);
        $emails = "";
        foreach ($four_ids_arr as &$v) {

            $site = explode('&', $v)[1];
            $accouontId = explode('&', $v)[0];
            if ($site == 'es') {
                $site = 'sp';
            }
            $model = Account::find()->select('email')->where(['old_account_id' => $accouontId, 'site_code' => $site, 'status' => 1])->asArray()->one();
            $email = $model['email'];
            if (!empty($email)) {
                $emails .= ',' . $email;
            }
            $v .= "&" . $email;
        }
        $data_arr['emails'] = ltrim($emails, ',');
        $data_arr['four_arr'] = $four_ids_arr;
        echo json_encode($data_arr);
        die;
    }

    /**
     * 单个发邮件
     * Amazon
     */
    public function actionSendemail() {
        $returnArr = ['bool' => 1, 'msg' => '发送成功!'];
        $request = Yii::$app->request->post();
        $sendEmal = trim($request['send_email']);
        $recipientEmail = $request['recipient_email'];
        $title = $request['title'];
        $content = $request['content'];
        $res = Mail::instance($sendEmal)
                ->setTo($recipientEmail)
                ->setSubject($title)
                ->seHtmlBody($content)
                ->setFrom($sendEmal)
                ->sendmail();
        if (!$res) {
            $returnArr = ['bool' => 0, 'msg' => '错误原因: ' . Mail::$errorMsg[0]];
        }
        echo json_encode($returnArr);
        die;
    }

    /**
     * 多个发送 插入mail_outbox表
     */
    public function actionSendemails() {
        $returnArr = ['bool' => 1, 'msg' => '发送成功!'];
        $request = Yii::$app->request->post();
        $all_value = $request['all_value'];
        $title = $request['title'];
        $content = $request['content'];
        //批量插入{{%mail_outbox}}
        $all_value_arr = explode(',', $all_value);
        foreach ($all_value_arr as $k => &$v) {
            $yb = new MailOutbox(); //
            $account_id = explode('&', $v)[0];
            $site = explode('&', $v)[1];
            $recipientenmail = explode('&', $v)[2];
            $order_id = explode('&', $v)[3];
            $send_email = explode('&', $v)[4];
            $yb->platform_code = Platform::PLATFORM_CODE_AMAZON;
            $yb->account_id = $account_id;
            $yb->subject = $title;
            $yb->content = $content;
            $yb->send_time = date('Y-m-d H:i:s', strtotime(" +4 minute")); //
            $yb->send_status = 0; //默认等待发送
            $yb->send_params = json_encode(
                    [
                        'sender_email' => $send_email,
                        'receive_email' => $recipientenmail,
                        'order_id' => $order_id,
                        'attachments' => []
                    ]
            );
            $yb->create_by = Yii::$app->user->identity->login_name;
            $yb->create_time = date('Y-m-d H:i:s');
            $res = $yb->save();
        }
        if (!$res) {
            $returnArr = ['bool' => 0, 'msg' => '批量发送失败 '];
        }
        echo json_encode($returnArr);
        exit;
    }

    /**
     * @desc 沃尔玛 订单查询列表
     */
    public function actionWalmartlist() {
        $condition_option = isset($_REQUEST['condition_option']) ? trim($_REQUEST['condition_option']) : null;
        $condition_value = isset($_REQUEST['condition_value']) ? trim($_REQUEST['condition_value']) : null;
        $platform_code = Platform::PLATFORM_CODE_WALMART;
        $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //WALMART账号
        $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
        $warehouse_type = isset($_REQUEST['warehouse_type']) ? trim($_REQUEST['warehouse_type']) : null; //仓库类型
        $get_date = isset($_REQUEST['get_date']) ? trim($_REQUEST['get_date']) : null;
        $begin_date = isset($_REQUEST['begin_date']) ? trim($_REQUEST['begin_date']) : null;
        $end_date = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null;
        $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
        $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
        $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态

        $warehouse_res = $this->getWarehouseType($warehouse_type);
        $ImportPeople_list = Account::getIdNameKefuList(Platform::PLATFORM_CODE_WALMART); //WALMART账号
        $ImportPeople_list[0] = '全部';
        ksort($ImportPeople_list);
        $warehouse_name_list = Warehouse::getWarehouseListAll(); //发货仓库
        $warehouse_name_list[0] = "全部";
        ksort($warehouse_name_list);
        $ship_code_list = Logistic::getLogisArrCodeName(); //发货方式
        $ship_code_list[0] = "全部";
        ksort($ship_code_list);
        $ship_country_list = Country::getCodeNamePairsList('cn_name'); //目的国
        array_unshift($ship_country_list, '全部');
        $currency_list = CurrencyRateKefu::getCurrencyList(); //货币类型
        array_unshift($currency_list, '全部');
        $complete_status_list = order::getOrderCompleteStatus(); //订单状态
        $order_status = null;
        $item_location = null;
        $remark = null;
        $result = $this->orderlists($condition_option, $condition_value, $platform_code, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $order_status, $item_location, $remark, null, null, $warehouse_res);
        //创建分页组件
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => $result['count'],
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        return $this->render('walmartlist', [
                    'orders' => $result['data_list'],
                    'page' => $page,
                    'count' => $result['count'],
                    'condition_option' => $condition_option,
                    'condition_value' => $condition_value,
                    'platform' => $platform_code,
                    'account_ids' => $account_ids,
                    'ImportPeople_list' => $ImportPeople_list,
                    'warehouse_id' => $warehouse_id,
                    'warehouse_name_list' => $warehouse_name_list,
                    'get_date' => $get_date,
                    'begin_date' => $begin_date,
                    'end_date' => $end_date,
                    'ship_code' => $ship_code,
                    'ship_code_list' => $ship_code_list,
                    'ship_country' => $ship_country,
                    'ship_country_list' => $ship_country_list,
                    'currency' => $currency,
                    'currency_list' => $currency_list,
                    'complete_status' => $complete_status,
                    'complete_status_list' => $complete_status_list,
                    'warehouse_type' => $warehouse_type
        ]);
    }

    /**
     * @desc mymall 订单查询列表
     */
    public function actionMymalllist() {
        $condition_option = isset($_REQUEST['condition_option']) ? trim($_REQUEST['condition_option']) : null;
        $condition_value = isset($_REQUEST['condition_value']) ? trim($_REQUEST['condition_value']) : null;
        $platform_code = Platform::PLATFORM_CODE_MALL;
        $account_ids = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null; //MALL账号
        $warehouse_id = isset($_REQUEST['warehouse_id']) ? trim($_REQUEST['warehouse_id']) : null; //发货仓库
        $warehouse_type = isset($_REQUEST['warehouse_type']) ? trim($_REQUEST['warehouse_type']) : null; //仓库类型
        $get_date = isset($_REQUEST['get_date']) ? trim($_REQUEST['get_date']) : null; //
        $begin_date = isset($_REQUEST['begin_date']) ? trim($_REQUEST['begin_date']) : null; //
        $end_date = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null;
        $ship_code = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null; //出货方式
        $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null; //目的国
        $currency = isset($_REQUEST['currency']) ? trim($_REQUEST['currency']) : null; // 货币类型
        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        $complete_status = isset($_REQUEST['complete_status']) ? trim($_REQUEST['complete_status']) : null; //订单状态
        $ImportPeople_list = Account::getIdNameKefuList(Platform::PLATFORM_CODE_MALL); //MALL账号
        $warehouse_res = $this->getWarehouseType($warehouse_type);
        $ImportPeople_list[0] = '全部';
        ksort($ImportPeople_list);
        $warehouse_name_list = Warehouse::getWarehouseListAll(); //发货仓库
        $warehouse_name_list[0] = "全部";
        ksort($warehouse_name_list);
        $ship_code_list = Logistic::getLogisArrCodeName(); //发货方式
        $ship_code_list[0] = "全部";
        ksort($ship_code_list);
        $ship_country_list = Country::getCodeNamePairsList('cn_name'); //目的国
        array_unshift($ship_country_list, '全部');
        $currency_list = CurrencyRateKefu::getCurrencyList(); //货币类型
        array_unshift($currency_list, '全部');
        $complete_status_list = order::getOrderCompleteStatus(); //订单状态
        $order_status = null;
        $item_location = null;
        $remark = null;
        $result = $this->orderlists($condition_option, $condition_value, $platform_code, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $order_status, $item_location, $remark, null, null, $warehouse_res);
        //创建分页组件
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => $result['count'],
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        return $this->render('mymalllist', [
                    'orders' => $result['data_list'],
                    'page' => $page,
                    'count' => $result['count'],
                    'condition_option' => $condition_option,
                    'condition_value' => $condition_value,
                    'platform' => $platform_code,
                    'account_ids' => $account_ids,
                    'ImportPeople_list' => $ImportPeople_list,
                    'warehouse_id' => $warehouse_id,
                    'warehouse_name_list' => $warehouse_name_list,
                    'get_date' => $get_date,
                    'begin_date' => $begin_date,
                    'end_date' => $end_date,
                    'ship_code' => $ship_code,
                    'ship_code_list' => $ship_code_list,
                    'ship_country' => $ship_country,
                    'ship_country_list' => $ship_country_list,
                    'currency' => $currency,
                    'currency_list' => $currency_list,
                    'complete_status' => $complete_status,
                    'complete_status_list' => $complete_status_list,
                    'warehouse_type' => $warehouse_type
        ]);
    }

    /**
     *
     * @return string
     */
    public function actionRedirectorder() {
        $this->isPopup = true;
        $orderId = $this->request->getQueryParam('order_id');
        $platform = $this->request->getQueryParam('platform');
        $orderinfo = [];
        if (empty($platform))
            $this->_showMessage('平台CODE无效', false, null, false, null, 'top.layer.closeAll()');
        if (empty($orderId))
            $this->_showMessage('订单号无效', false, null, false, null, 'top.layer.closeAll()');
        $orderinfo = Order::getOrderStackByOrderId($platform, '', $orderId);
        if (empty($orderinfo))
            $this->_showMessage('找不到对应订单', false, null, false, null, 'top.layer.closeAll()');
        $datas = ['orderId' => $orderId, 'platformCode' => $platform];
        if ($this->request->getIsAjax()) {
            $skuArr = $this->request->getBodyParam('sku');
            $titleArr = $this->request->getBodyParam('product_title');
            $quantity = $this->request->getBodyParam('quantity');
            $items = [];
            $skuList = [];
            foreach ($skuArr as $key => $sku) {
                $sku = trim($sku);
                if (in_array($sku, $skuList))
                    $this->_showMessage('SKU{' . $sku . '}重复', false);
                if (empty($sku))
                    $this->_showMessage('SKU为空', false);
                $title = isset($titleArr[$key]) ? trim($titleArr[$key]) : '';
                if (empty($title))
                    $this->_showMessage('产品标题为空', false);
                $quantity = isset($quantity[$key]) ? (int) $quantity[$key] : 0;
                if ($quantity <= 0)
                    $this->_showMessage('产品数量必须大于0', false);
                $items[] = [
                    'sku' => $sku,
                    'productTitle' => $title,
                    'quantity' => $quantity,
                ];
            }

            //发货地址信息
            $datas['city'] = $shipCityName = trim($this->request->getBodyParam('ship_city_name'));
            $datas['countryCode'] = $shipCountry = trim($this->request->getBodyParam('ship_country'));
            $datas['shipName'] = $shipName = trim($this->request->getBodyParam('ship_name'));
            $datas['phone'] = $shipPhone = trim($this->request->getBodyParam('ship_phone'));
            $datas['province'] = $state = trim($this->request->getBodyParam('ship_stateorprovince'));
            $datas['address1'] = $address1 = trim($this->request->getBodyParam('ship_street1'));
            $datas['address2'] = $address2 = trim($this->request->getBodyParam('ship_street2'));
            $datas['postCode'] = $postCode = trim($this->request->getBodyParam('ship_zip'));
            $datas['items'] = $items;
            if (empty($shipName))
                $this->_showMessage('收件人不能为空', false);
            if (empty($shipCountry))
                $this->_showMessage('国家不能为空', false);
            if (empty($address1))
                $this->_showMessage('地址不能为空', false);
            if (empty($shipCityName))
                $this->_showMessage('城市不能为空', false);
            if (empty($postCode))
                $this->_showMessage('邮编不能为空', false);

            //发货仓库和运输方式
            $warehouseId = (int) $this->request->getBodyParam('warehouse_id');
            $shipCode = trim($this->request->getBodyParam('ship_code'));
            if (empty($warehouseId))
                $this->_showMessage('发货仓库不能为空', false);
            if ($shipCode === '' || $shipCode === null)
                $this->_showMessage('邮寄方式不能为空', false);
            $datas['warehouseId'] = $warehouseId;
            $datas['shipCode'] = $shipCode;
            $orderModel = new Order();
            $flag = $orderModel->redirectOrder($datas);
            if (!$flag)
                $this->_showMessage('重寄订单失败，' . $orderModel->getExceptionMessage(), false);
            $this->_showMessage('操作成功', true, true);
        }
        $orderinfo = Json::decode(Json::encode($orderinfo), true);
        $countires = Country::getCodeNamePairs();
        $warehouseList = Warehouse::getWarehouseList();
        $logistics = Logistic::getWarehouseLogistics($orderinfo['info']['warehouse_id']);
        return $this->render('redirectorder', [
                    'info' => $orderinfo,
                    'countries' => $countires,
                    'warehouseList' => $warehouseList,
                    'logistics' => $logistics,
        ]);
    }

    /*
     * @desc 添加订单备注
     */

    public function actionAddremark() {
        $orderId = $_REQUEST['order_id'];
        $remark = $_REQUEST['remark'];
        $user = Yii::$app->user->id;
        $name = Yii::$app->user->identity->user_name;
        $orderRemarkInfos = Order::getAddremark($orderId, $remark, $user, $name);

        $jsonData = array(
            'ack' => $orderRemarkInfos->ack,
            'info' => $orderRemarkInfos->OrderRemark
        );
        echo Json::encode($jsonData);
    }

    /*
     * @desc 删除标签
     * */

    public function actionRemoveremark() {
        $id = $_REQUEST['id'];
        $user = Yii::$app->user->id;
        $name = Yii::$app->user->identity->user_name;
        $removeMark = Order::removeMark($id, $user, $name);

        $jsonData = array(
            'ack' => $removeMark->ack,
            'info' => $removeMark->OrderRemark
        );
        echo Json::encode($jsonData);
    }

    /*
     * @desc 添加出货备注
     * */

    public function actionAddprintremark() {
        $orderId = $_POST['order_id'];
        $platform = $_POST['platform'];
        $print_remark = trim($_POST['print_remark']);
        $orderinfo = Order::Addprintremark($orderId, $platform, $print_remark);
        $jsonData = array(
            'ack' => $orderinfo->ack,
            'info' => $orderinfo->Printremark
        );
        echo Json::encode($jsonData);
    }

    /**
     * 延长买家确认收货时间
     */
    public function actionExtendacceptgoodstime() {
        $platformCode = Yii::$app->request->post('platform_code', '');
        $platformOrderId = Yii::$app->request->post('platform_order_id', '');
        $day = Yii::$app->request->post('day', 0);

        if (empty($platformCode)) {
            die(json_encode([
                'code' => 0,
                'message' => '平台code不能为空',
            ]));
        }
        if (empty($platformOrderId)) {
            die(json_encode([
                'code' => 0,
                'message' => '平台订单号不能为空',
            ]));
        }
        if (empty($day)) {
            die(json_encode([
                'code' => 0,
                'message' => '延长天数不能为空',
            ]));
        }

        if ($platformCode == Platform::PLATFORM_CODE_ALI) {
            $model = new ExtendsBuyerAcceptGoodsTime();
            $result = $model->extendAcceptGoodsTime($platformOrderId, $day);
            if ($result === true) {
                die(json_encode([
                    'code' => 1,
                    'message' => '延长收货时间成功',
                ]));
            } else {
                die(json_encode([
                    'code' => 0,
                    'message' => $result,
                ]));
            }
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '延长收货时间功能待开发',
            ]));
        }
    }

    /**
     * 获取亚马逊item
     */
    public function actionGetamazonitems() {
        error_reporting(E_ERROR);
        $platformCode = trim(\yii::$app->request->getQueryParam('platform_code'));
        if (empty($platformCode))
            $this->_showMessage('无效的平台CODE', false);
        $accountName = trim(\yii::$app->request->getQueryParam('account_name'));
        if (empty($accountName))
            $this->_showMessage('无效订单账号ID', false);
        $platformOrderId = trim(\yii::$app->request->getQueryParam('platform_order_id'));
        if (empty($platformOrderId))
            $this->_showMessage('无效的订单号', false);
        $accountModel = new Account();
        try {
            $accountInfo = $accountModel->getAccountFromErp($platformCode, $accountName);
            if (empty($accountInfo))
                $this->_showMessage('查询账号信息失败', false);
            if ($platformCode == Platform::PLATFORM_CODE_AMAZON) {
                $awsAccessKeyId = $accountInfo->aws_access_key_id;
                $awsSecretAccessKey = $accountInfo->secret_key;
                $applicationName = 'YB-APP';
                $applicationVersion = '1.0';
                $config = [
                    'ServiceURL' => $accountInfo->service_url . '/Orders/2013-09-01',
                ];
                $server = new \MarketplaceWebServiceOrders_Client($awsAccessKeyId, $awsSecretAccessKey, $applicationName, $applicationVersion, $config);
                $request = new \MarketplaceWebServiceOrders_Model_ListOrderItemsRequest();
                $request->setAmazonOrderId($platformOrderId);
                $request->setSellerId($accountInfo->merchant_id);
                $response = $server->listOrderItems($request);
                $details = array();
                if ($response->isSetListOrderItemsResult()) {
                    $listOrderItemsResult = $response->getListOrderItemsResult();
                    if ($listOrderItemsResult->isSetOrderItems()) {
                        $orderItemList = $listOrderItemsResult->getOrderItems();
                        $details = $this->getOrderItemArr($orderItemList);
                        if (!$details) {
                            throw new \Exception('have not any items!');
                        }
                    }
                    while ($listOrderItemsResult->isSetNextToken()) {
                        sleep(6); //每调用一次停留6S
                        $nextToken = $listOrderItemsResult->getNextToken();
                        $response = $this->getItemsResponseByNextToken($accountInfo, $nextToken);
                        if ($response->isSetListOrderItemsByNextTokenResult()) {
                            $listOrderItemsByNextTokenResult = $response->getListOrderItemsByNextTokenResult();
                            if ($listOrderItemsByNextTokenResult->isSetOrderItems()) {
                                $orderItemList = $listOrderItemsResult->getOrderItems();
                                if (count($orderItemList) < 1) {
                                    throw new \Exception('have not any items!');
                                }
                                $details = $this->getOrderItemArr($orderItemList, $details);
                            }
                        }
                    }
                }
                if (empty($details))
                    $this->_showMessage('获取数据为空', false);
                foreach ($details as $_d) {
                    $itemId = isset($_d['item_id']) ? trim($_d['item_id']) : '';
                    //将原item的原始数据保存在order amazon item表
                    $OrderAmazonItem = OrderAmazonItem::findOne(['order_id' => $platformOrderId, 'item_id' => $itemId]);
                    if (empty($OrderAmazonItem))
                        $OrderAmazonItem = new OrderAmazonItem();
                    $OrderAmazonItem->order_id = $platformOrderId;
                    $OrderAmazonItem->asin = isset($_d['asin']) ? trim($_d['asin']) : '';
                    $OrderAmazonItem->seller_sku = isset($_d['seller_sku']) ? trim($_d['seller_sku']) : '';
                    $OrderAmazonItem->item_id = $itemId;
                    $OrderAmazonItem->title = isset($_d['title']) ? trim($_d['title']) : '';
                    $OrderAmazonItem->qty_ordered = isset($_d['qty_ordered']) ? (int) $_d['qty_ordered'] : '';
                    $OrderAmazonItem->qty_shipped = isset($_d['qty_shipped']) ? (int) $_d['qty_shipped'] : '';
                    $OrderAmazonItem->item_currency_code = isset($_d['item_currency_code']) ?
                            trim($_d['item_currency_code']) : '';
                    $OrderAmazonItem->item_price_amount = isset($_d['item_price_amount']) ?
                            floatval($_d['item_price_amount']) : '';
                    $OrderAmazonItem->item_shipping_currency_code = isset($_d['item_shipping_currency_code']) ?
                            trim($_d['item_shipping_currency_code']) : '';
                    $OrderAmazonItem->item_shipping_amount = isset($_d['item_shipping_amount']) ?
                            floatval($_d['item_shipping_amount']) : '';
                    $OrderAmazonItem->item_tax_currency_code = isset($_d['item_tax_currency_code']) ?
                            trim($_d['item_tax_currency_code']) : '';
                    $OrderAmazonItem->item_tax_amount = isset($_d['item_tax_amount']) ?
                            floatval($_d['item_tax_amount']) : '';
                    $OrderAmazonItem->shipping_tax_currency_code = isset($_d['shipping_tax_currency_code']) ?
                            trim($_d['shipping_tax_currency_code']) : '';
                    $OrderAmazonItem->shipping_tax_amount = isset($_d['shipping_tax_amount']) ?
                            floatval($_d['shipping_tax_amount']) : '';
                    $OrderAmazonItem->shipping_discount_currency_code = isset($_d['shipping_discount_currency_code']) ?
                            trim($_d['shipping_discount_currency_code']) : '';
                    $OrderAmazonItem->shipping_discount_amount = isset($_d['shipping_discount_amount']) ?
                            floatval($_d['shipping_discount_amount']) : '';
                    $OrderAmazonItem->promotion_discount_currency_code = isset($_d['promotion_discount_currency_code']) ?
                            trim($_d['promotion_discount_currency_code']) : '';
                    $OrderAmazonItem->promotion_discount_amount = isset($_d['promotion_discount_amount']) ?
                            floatval($_d['promotion_discount_amount']) : '';
                    $promotionIdStr = '';
                    $promotionIds = isset($_d['promotionId']) ? $_d['promotionId'] : [];
                    if (!empty($promotionIds)) {
                        foreach ($promotionIds as $promotionId)
                            $promotionIdStr .= $promotionId . ',';
                    }
                    $promotionIdStr = trim($promotionIdStr, ',');
                    $OrderAmazonItem->promotionId = $promotionIdStr;
                    $flag = $OrderAmazonItem->save(false);
                    if (!$flag)
                        $this->_showMessage('保存数据失败', false);
                }
            } elseif ($platformCode == Platform::PLATFORM_CODE_WALMART) {
                $getOrderModel = new GetOrder($accountName);
                $getOrderModel->setRequest();
                $url = "https://marketplace.walmartapis.com/v3/orders?purchaseOrderId={$platformOrderId}";
                $result = $getOrderModel->handleResponse($url);
                if ($result) {
                    list($flag, $message) = OrderWalmartItem::saveData($result);
                } else
                    $this->_showMessage('未获取到数据', false);
                if ($flag != false)
                    $this->_showMessage('保存数据失败' . $message, false);
            }

            $this->_showMessage('获取数据成功', true, null, true);
        } catch (\Exception $e) {//var_dump($e->getMessage());
            $this->_showMessage('获取数据失败' . $e->getMessage(), false);
        }
    }

    /**
     * 获取订单item数组
     * @param $orderItemList
     * @param array $orderItemArr
     * @return array
     */
    public function getOrderItemArr($orderItemList, $orderItemArr = array()) {
        if (is_array($orderItemArr) && count($orderItemArr)) {
            $i = count($orderItemArr);
        } else {
            $i = 0;
        }
        foreach ($orderItemList as $orderItem) {
            if ($orderItem->isSetASIN()) {
                $orderItemArr[$i]['asin'] = $orderItem->getASIN();
            }
            if ($orderItem->isSetSellerSKU()) {
                $orderItemArr[$i]['seller_sku'] = $orderItem->getSellerSKU();
            }
            if ($orderItem->isSetOrderItemId()) {
                $orderItemArr[$i]['item_id'] = $orderItem->getOrderItemId();
            }
            if ($orderItem->isSetTitle()) {
                $orderItemArr[$i]['title'] = $orderItem->getTitle();
            }
            if ($orderItem->isSetQuantityOrdered()) {
                $orderItemArr[$i]['qty_ordered'] = $orderItem->getQuantityOrdered();
            }
            if ($orderItem->isSetQuantityShipped()) {
                $orderItemArr[$i]['qty_shipped'] = $orderItem->getQuantityShipped();
            }
            if ($orderItem->isSetItemPrice()) {
                $itemPrice = $orderItem->getItemPrice();
                if ($itemPrice->isSetCurrencyCode()) {
                    $orderItemArr[$i]['item_currency_code'] = $itemPrice->getCurrencyCode();
                }
                if ($itemPrice->isSetAmount()) {
                    $orderItemArr[$i]['item_price_amount'] = $itemPrice->getAmount();
                }
            }
            if ($orderItem->isSetShippingPrice()) {
                $shippingPrice = $orderItem->getShippingPrice();
                if ($shippingPrice->isSetCurrencyCode()) {
                    $orderItemArr[$i]['item_shipping_currency_code'] = $shippingPrice->getCurrencyCode();
                }
                if ($shippingPrice->isSetAmount()) {
                    $orderItemArr[$i]['item_shipping_amount'] = $shippingPrice->getAmount();
                }
            }
            if ($orderItem->isSetGiftWrapPrice()) {
                $giftWrapPrice = $orderItem->getGiftWrapPrice();
                if ($giftWrapPrice->isSetCurrencyCode()) {
                    $orderItemArr[$i]['giftwrap_currency_code'] = $giftWrapPrice->getCurrencyCode();
                }
                if ($giftWrapPrice->isSetAmount()) {
                    $orderItemArr[$i]['giftwrap_amount'] = $giftWrapPrice->getAmount();
                }
            }
            if ($orderItem->isSetItemTax()) {
                $itemTax = $orderItem->getItemTax();
                if ($itemTax->isSetCurrencyCode()) {
                    $orderItemArr[$i]['item_tax_currency_code'] = $itemTax->getCurrencyCode();
                }
                if ($itemTax->isSetAmount()) {
                    $orderItemArr[$i]['item_tax_amount'] = $itemTax->getAmount();
                }
            }
            if ($orderItem->isSetShippingTax()) {
                $shippingTax = $orderItem->getShippingTax();
                if ($shippingTax->isSetCurrencyCode()) {
                    $orderItemArr[$i]['shipping_tax_currency_code'] = $shippingTax->getCurrencyCode();
                }
                if ($shippingTax->isSetAmount()) {
                    $orderItemArr[$i]['shipping_tax_amount'] = $shippingTax->getAmount();
                }
            }
            if ($orderItem->isSetGiftWrapTax()) {
                $giftWrapTax = $orderItem->getGiftWrapTax();
                if ($giftWrapTax->isSetCurrencyCode()) {
                    $orderItemArr[$i]['giftwrap_tax_currency_code'] = $giftWrapTax->getCurrencyCode();
                }
                if ($giftWrapTax->isSetAmount()) {
                    $orderItemArr[$i]['giftwrap_tax_amount'] = $giftWrapTax->getAmount();
                }
            }
            if ($orderItem->isSetShippingDiscount()) {
                $shippingDiscount = $orderItem->getShippingDiscount();
                if ($shippingDiscount->isSetCurrencyCode()) {
                    $orderItemArr[$i]['shipping_discount_currency_code'] = $shippingDiscount->getCurrencyCode();
                }
                if ($shippingDiscount->isSetAmount()) {
                    $orderItemArr[$i]['shipping_discount_amount'] = $shippingDiscount->getAmount();
                }
            }

            if ($orderItem->isSetPromotionDiscount()) {
                $promotionDiscount = $orderItem->getPromotionDiscount();
                if ($promotionDiscount->isSetCurrencyCode()) {
                    $orderItemArr[$i]['promotion_discount_currency_code'] = $promotionDiscount->getCurrencyCode();
                }
                if ($promotionDiscount->isSetAmount()) {
                    $orderItemArr[$i]['promotion_discount_amount'] = $promotionDiscount->getAmount();
                }
            }
            if ($orderItem->isSetPromotionIds()) {
                //$promotionIds = $orderItem->getPromotionIds();
                //$promotionIdList  =  $promotionIds->getPromotionId();
                $promotionIdList = $orderItem->getPromotionIds();
                foreach ($promotionIdList as $promotionId) {
                    $orderItemArr[$i]['promotionId'][] = $promotionId;
                }
            }
            if ($orderItem->isSetCODFee()) {
                $CODFee = $orderItem->getCODFee();
                if ($CODFee->isSetCurrencyCode()) {
                    $orderItemArr[$i]['codfee_currency_code'] = $CODFee->getCurrencyCode();
                }
                if ($CODFee->isSetAmount()) {
                    $orderItemArr[$i]['codfee_amount'] = $CODFee->getAmount();
                }
            }
            if ($orderItem->isSetCODFeeDiscount()) {
                $CODFeeDiscount = $orderItem->getCODFeeDiscount();
                if ($CODFeeDiscount->isSetCurrencyCode()) {
                    $orderItemArr[$i]['codfee_discount_currency_code'] = $CODFeeDiscount->getCurrencyCode();
                }
                if ($CODFeeDiscount->isSetAmount()) {
                    $orderItemArr[$i]['codfee_discount_amount'] = $CODFeeDiscount->getAmount();
                }
            }
            if ($orderItem->isSetGiftMessageText()) {
                $orderItemArr[$i]['gift_message_text'] = $orderItem->getGiftMessageText();
            }
            if ($orderItem->isSetGiftWrapLevel()) {
                $orderItemArr[$i]['gift_wrap_level'] = $orderItem->getGiftWrapLevel();
            }
            if ($orderItem->isSetInvoiceData()) {
                $invoiceData = $orderItem->getInvoiceData();
                if ($invoiceData->isSetInvoiceRequirement()) {
                    $orderItemArr[$i]['invoice']['requirement'] = $invoiceData->getInvoiceRequirement();
                }
                if ($invoiceData->isSetBuyerSelectedInvoiceCategory()) {
                    $orderItemArr[$i]['invoice']['buyer_sel_category'] = $invoiceData->getBuyerSelectedInvoiceCategory();
                }
                if ($invoiceData->isSetInvoiceTitle()) {
                    $orderItemArr[$i]['invoice']['title'] = $invoiceData->getInvoiceTitle();
                }
                if ($invoiceData->isSetInvoiceInformation()) {
                    $orderItemArr[$i]['invoice']['information'] = $invoiceData->getInvoiceInformation();
                }
            }
            $i++;
        }
        return $orderItemArr;
    }

    /**
     * 订单绑定payPal收款信息
     * 1.本地paypal交易记录获取数据 如果能获取到则直接获取
     * 2.获取不到就调用paypal Api获取
     * 3.判断金额与货币是否与paypal信息是否一致
     * @author allen <2018-03-10>
     */
    public function actionOrderbindtransaction() {
        $bool = FALSE;
        $message = "操作成功!";
        $request = Yii::$app->request->post();
        $orderId = isset($request['order_id']) ? trim($request['order_id']) : ""; //当前订单号
        $account = isset($request['account']) ? trim($request['account']) : ""; //收款payPal账号
        $transactionId = isset($request['transactionId']) ? trim($request['transactionId']) : ""; //payPal交易号
        $currency = isset($request['currency']) ? trim($request['currency']) : ""; //货币类型
        $amount = isset($request['amount']) ? trim($request['amount']) : ""; //收款金额
        //先从本地paypal交易记录获取数据 如果能获取到则直接获取  获取不到就调用paypal Api获取
        $info = Transactionrecord::getTransactionInfo($transactionId);
        if (empty($info)) {
            //通过payPal Api获取交易详情
            $account_info = RefundAccount::findOne($account);
            if (empty($account_info)) {
                $bool = TRUE;
                $message = "payPal账号获取失败!";
            }


            if (!$bool) {
                //组请求数据
                $params['detail_config'] = [
                    'acct1.UserName' => $account_info['api_username'],
                    'acct1.Password' => $account_info['api_password'],
                    'acct1.Signature' => $account_info['api_signature'],
                ];
                $params['transID'] = $transactionId;

                $response = VHelper::ebTransactionDeail($params);
                $payPalInfo = $response[0];
                if ($payPalInfo) {
                    //开启事物
                    $transaction = Yii::$app->db->beginTransaction();
                    //添加交易记录信息
                    $transactionInserResult = Transactionrecord::addTranctionRecord($payPalInfo);
                    if ($transactionInserResult['bool']) {
                        $bool = TRUE;
                        $message = $transactionInserResult['info'];
                    }

                    //添加交易地址信息
                    if (!$bool) {
                        $addressResult = TransactionAddress::insertAddressData($payPalInfo->PaymentTransactionDetails->PayerInfo->Address, $transactionId);
                        if (!$addressResult[0]) {
                            $bool = TRUE;
                            $message = $addressResult[1];
                        }
                    }

                    //提交事物保存数据
                    if (!$bool) {
                        $transaction->commit();
                        $info = Transactionrecord::getTransactionInfo($transactionId);
                    } else {
                        $transaction->rollBack();
                    }
                } else {
                    $bool = TRUE;
                    $message = $response[1];
                }
            }
        }


        //账号 金额信息如果核查无误可同步ERP将当前订单与payPal交易号进行绑定
        if (!$bool) {
            if ($info->currency != $currency) {
                $bool = TRUE;
                $message = '所选货币类型: ' . $currency . ' 与交易信息不符,请重新确认';
            }

            if (!$bool && $info->amt != $amount) {
                $bool = TRUE;
                $message = "金额与交易信息不符,请重新确认!";
            }

            //同步ERP
            if (!$bool) {
                $info = json_decode(Json::encode($info), TRUE);
                $apiDatas = [
                    'orderId' => $orderId,
                    'transactionId' => $transactionId,
                    'payPalInfo' => $info,
                ];
                $apiRes = Order::orderbindtransaction($apiDatas);
                if ($apiRes['bool']) {
                    $bool = TRUE;
                    $message = $apiRes['info'];
                } else {
                    $message .= $apiRes['info'];
                }
            }
        }
        echo json_encode(['bool' => $bool, 'msg' => $message, 'info' => $info]);
        die;
    }

    /**
     * 根据跟踪号获取物流跟踪信息
     * @return type
     * @author allen <2018-05-29>
     */
    public function actionGettracknumber() {

        $trackNumber = $this->request->getQueryParam('track_number');
//        $trackNumber = 'JK928540359GB';
        $data = [];
        if ($trackNumber) {
            $url = 'http://47.107.20.63:8000/serverapi/logisticstrack/' . $trackNumber . '?access_token=OulgrsTuNuRkceM6K1gpoB8zvEdygyK5';

            $ch = curl_init(); //初始化
            curl_setopt($ch, CURLOPT_URL, $url); //设置访问的URL
            curl_setopt($ch, CURLOPT_HEADER, false); // false 设置不需要头信息 如果 true 连头部信息也输出

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //只获取页面内容，但不输出


            $return = curl_exec($ch); //执行访问，返回结果

            curl_close($ch); //关闭curl，释放资源

            $result = json_decode($return, true);

            if ($result['Ack']) {
                if (is_array($result['ResultData']) && !empty($result['ResultData'])) {
                    $data = $result['ResultData'];
                }
            }
        }
        return $this->renderPartial('track_history', ['data' => $data, 'trackNumber' => $trackNumber]);
    }

    /**
     * 速卖通消息详情订单信息
     * @return \yii\base\string
     */
    public function actionAliexpressmessageorderlist() {
        $params = $_REQUEST;
        $orderModel = new OrderAliexpressKefu();
        $dataProvider = $orderModel->searchMessageOrderList($params);
        $accountList = Account::getOldIdShortNamePairs(Platform::PLATFORM_CODE_ALI);
        $countryList = Country::getCodeNamePairs('cn_name');
        $warehouseList = Warehouse::getWarehouseListAll();
        $warehouseTypeList = Warehouse::getWarehousetype();
        $logisticsList = Logistic::getLogisArrCodeName();
        $currentOrderId = isset($params['current_order_id']) ? trim($params['current_order_id']) : '';

        $models = $dataProvider->getModels();
        $childOrderIds = [];
        $childCount = 0;
        //把当前订单加到订单列表
        if (!empty($currentOrderId)) {
            $orderModel = OrderAliexpressKefu::findOne(['platform_order_id' => $currentOrderId]);
            if (!empty($orderModel)) {
                //如果是拆分的主单，找出拆分的子单，排在主单后面
                if ($orderModel->order_type == OrderKefu::ORDER_TYPE_SPLIT_MAIN) {
                    $childrenOrderModels = OrderAliexpressKefu::findAll(['parent_order_id' =>
                                $orderModel->order_id]);
                    if (!empty($childrenOrderModels)) {
                        foreach ($childrenOrderModels as $childrenOrderModel) {
                            array_unshift($models, $childrenOrderModel);
                            $childOrderIds[] = $childrenOrderModel->order_id;
                            //array_pop($models);
                        }
                    }
                }
                array_unshift($models, $orderModel);
                $childCount = sizeof($childOrderIds);
                //array_pop($models);
                //$dataProvider->setModels($models);
            }
        }

        $modelDatas = [];
        $key = 0;
        $i = 0;
        foreach ($models as $model) {
            $i++;
            //过滤当前订单
            if ($key != 0 && $model->platform_order_id == $currentOrderId) {
                unset($model);
                continue;
            }
            if ($key > $childCount && in_array($model->order_id, $childOrderIds)) {
                unset($model);
                continue;
            }
            
            //订单备注
            $remark = "";    
            $remarks = OrderRemarkKefu::getOrderRemarks($model->order_id);
            if (!empty($remarks)) {
                foreach ($remarks as $k => $orderRemarkInfo) {
                    $userNmae = MHelper::getUsername($orderRemarkInfo['create_user_id']);
                    $remark .= ($k+1).'. '. $orderRemarkInfo['remark'].'  ['.$userNmae.'/'.$orderRemarkInfo['create_time'].']'.'<br/><br/>';
                }
            }
            
            $model->order_link = '<a _width="70%" _height="70%" class="edit-button platform_order_id" data-orderid="' . $model->platform_order_id . '"
                    href="' . Url::toRoute(['/orders/order/orderdetails',
                        'order_id' => $model->platform_order_id,
                        'platform' => Platform::PLATFORM_CODE_ALI,
                        'system_order_id' => $model->order_id]) . '" title="订单信息">定位单号：<br/>' .
                    (array_key_exists($model->account_id, $accountList) ?
                    $accountList[$model->account_id] . '-' . $model->order_id : $model->order_id) . '</a>';
            $model->order_link .= '<br />平台订单号：<br />' . $model->platform_order_id;
            $model->ship_country = $model->ship_country . (array_key_exists($model->ship_country, $countryList) ? '(' . $countryList[$model->ship_country] . ')' : '');
            $model->total_price = $model->total_price . '(' . $model->currency . ')';
            $model->complete_status_text = Order::getOrderCompleteDiffStatus($model->complete_status);

            //查看订单纠纷情况
            $orderIssueInfo = AliexpressDisputeList::findOne(['platform_order_id' => $model->platform_order_id]);
            if (empty($orderIssueInfo)) {
                //通过父级订单ID来查纠纷
                $orderIssueInfo = AliexpressDisputeList::find()->where(['platform_parent_order_id' => $model->platform_order_id])->all();

                if (empty($orderIssueInfo)) {
                    $model->issue_status = '<span class="label label-success">无</span>';
                } else {
                    foreach ($orderIssueInfo as $issueInfo) {
                        if (empty($model->issue_status)) {
                            if ($issueInfo->issue_status == 'processing') {
                                $model->issue_status = '<span class="label label-danger">纠纷订单</span><br>';
                            } else if ($issueInfo->issue_status == 'canceled_issue') {
                                $model->issue_status = '<span class="label label-default">纠纷取消</span><br>';
                            } else if ($issueInfo->issue_status == 'finish') {
                                $model->issue_status = '<span class="label label-success">纠纷结束</span><br>';
                            }
                        }

                        $model->issue_status .= '<a class="edit-button" _width="90%" _height="90%" href="' .
                                Url::toRoute(['/mails/aliexpressdispute/showorder',
                                    'issue_id' => $issueInfo->platform_dispute_id
                                ]) . '"><span class="label label-danger">纠纷ID:' . $issueInfo->platform_dispute_id .
                                '</span></a><br>';
                    }
                }
            } else {
                if ($orderIssueInfo->issue_status == 'processing') {
                    $model->issue_status = '<span class="label label-danger">纠纷订单</span><br>';
                } else if ($orderIssueInfo->issue_status == 'canceled_issue') {
                    $model->issue_status = '<span class="label label-default">纠纷取消</span><br>';
                } else if ($orderIssueInfo->issue_status == 'finish') {
                    $model->issue_status = '<span class="label label-success">纠纷结束</span><br>';
                }

                $model->issue_status .= '<a class="edit-button" _width="90%" _height="90%" href="' .
                        Url::toRoute(['/mails/aliexpressdispute/showorder',
                            'issue_id' => $orderIssueInfo->platform_dispute_id
                        ]) . '"><span class="label label-danger">纠纷ID:' . $orderIssueInfo->platform_dispute_id .
                        '</span></a><br>';
            }

            //订单平台状态
            $model->order_status = OrderKefu::getOrderStatus($model->order_status);
            //退款状态
            if ($model->refund_status == 0)
                $model->refund_status_text = '<span class="label label-success">无</span>';
            else if ($model->refund_status == 1)
                $model->refund_status_text = '<span class="label label-danger">部分退款</span>';
            else
                $model->refund_status_text = '<span class="label label-danger">全部退款</span>';
            $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder(Platform::PLATFORM_CODE_ALI, $model->order_id);
            //是否有售后订单
            if ($aftersaleinfo) {
                $res = AfterSalesOrder::getAfterSalesOrderByOrderId($model->order_id, Platform::PLATFORM_CODE_ALI);
                //获取售后单信息
                if (!empty($res['refund_res'])) {
                    $refund_res = '退款';
                    foreach ($res['refund_res'] as $refund_re) {
                        $refund_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                                $refund_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_ALI . '&status=' . $aftersaleinfo->status . '" >' .
                                $refund_re['after_sale_id'] . '</a>';
                    }
                } else {
                    $refund_res = '';
                }

                if (!empty($res['return_res'])) {
                    $return_res = '退货';
                    foreach ($res['return_res'] as $return_re) {
                        $return_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id=' .
                                $return_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_ALI . '&status=' . $aftersaleinfo->status . '" >' .
                                $return_re['after_sale_id'] . '</a>';
                    }
                } else {
                    $return_res = '';
                }

                if (!empty($res['redirect_res'])) {
                    $redirect_res = '重寄';
                    foreach ($res['redirect_res'] as $redirect_re) {
                        $redirect_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id=' .
                                $redirect_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_ALI . '&status=' . $aftersaleinfo->status . '" >' .
                                $redirect_re['after_sale_id'] . '</a>';
                    }
                } else {
                    $redirect_res = '';
                }
                if (!empty($res['domestic_return'])) {
                    $domestic_return = '退货跟进';
                    if ($res['domestic_return']['state'] == 1) {
                        $state = '未处理';
                    } elseif ($res['domestic_return']['state'] == 2) {
                        $state = '无需处理';
                    } elseif ($res['domestic_return']['state'] == 3) {
                        $state = '已处理';
                    } elseif ($res['domestic_return']['state'] == 4){
                        $state = '驳回EPR';
                    } else {
                        $state = '暂不处理';
                    }
                    //状态：1、未处理，2、无需处理，3、已处理，4、驳回EPR
                    $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                            $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_ALI . '" >' .
                            $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a>';
                } else {
                    $domestic_return = '';
                }
                $after_sale_text = '';
                if (!empty($refund_res)) {
                    $after_sale_text .= $refund_res . '<br>';
                }
                if (!empty($return_res)) {
                    $after_sale_text .= $return_res . '<br>';
                }
                if (!empty($redirect_res)) {
                    $after_sale_text .= $redirect_res . '<br>';
                }
                if (!empty($domestic_return)) {
                    $after_sale_text .= $domestic_return;
                }

                $model->after_sale_text = $after_sale_text;
            } else {
                $res = AfterSalesOrder::getAfterSalesOrderByOrderId($model->order_id, Platform::PLATFORM_CODE_ALI);
                if (!empty($res['domestic_return'])) {
                    $domestic_return = '退货跟进';
                    if ($res['domestic_return']['state'] == 1) {
                        $state = '未处理';
                    } elseif ($res['domestic_return']['state'] == 2) {
                        $state = '无需处理';
                    } elseif ($res['domestic_return']['state'] == 3) {
                        $state = '已处理';
                    } elseif ($res['domestic_return']['state'] == 4){
                        $state = '驳回EPR';
                    } else {
                        $state = '暂不处理';
                    }

                    //状态：1、未处理，2、无需处理，3、已处理，4、驳回EPR
                    $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                            $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_ALI . '" >' .
                            $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a>';
                    $model->after_sale_text = $domestic_return;
                } else {
                    $model->after_sale_text = '<span class="label label-success">无</span>';
                }
            }
          /*  $complaint = \app\modules\aftersales\models\ComplaintModel::find()->select('complaint_order,status')->where(['order_id' => $model->order_id])->one();
            if (empty($complaint)) {
              $model->after_sale_text = '<span class="label label-success">无</span>';
            } else {
                if ($complaint->status == 6) {
                    $model->after_sale_text= '<a _width="100%" _height="100%" class="edit-button" href=' . Url::toRoute(['/aftersales/complaint/getcompain', 'complaint_order' => $complaint->complaint_order]) . '>' . $complaint->complaint_order . '(已处理)</a>';
                } else {
                    $model->after_sale_text= '<a _width="100%" _height="100%" class="edit-button" href=' . Url::toRoute(['/aftersales/complaint/getcompain', 'complaint_order' => $complaint->complaint_order]) . '>' . $complaint->complaint_order . '(未处理)</a>';
                }
            }*/
            //用于保存售后单信息勿删
            $ajax_after_info = "<span id='after_{$model->order_id}'></span>";
            $model->after_sale_text = $model->after_sale_text . $ajax_after_info;

            //是否有评价
            $orderEvaluate = AliexpressEvaluateList::findOne(['platform_order_id' => $model->platform_order_id]);
            if (!empty($orderEvaluate))
                $model->evaluate = $orderEvaluate->buyer_evaluation;
            else
                $model->evaluate = '无';
            //付款时间
            if ($model->payment_status == 0)
                $model->paytime = "未付款";

            $shipCode = !empty($model->real_ship_code) ? $model->real_ship_code : $model->ship_code;
            $model->ship_name = array_key_exists($shipCode, $logisticsList) ? $logisticsList[$shipCode] : '';
            $model->track_number = '<a target="_blank" href="http://www.17track.net/zh-cn/track?nums=' .
                    $model->track_number . '" title="物流追踪号">' .
                    $model->track_number . '</a>';
            if ($model->shipped_date == '0000-00-00 00:00:00')
                $model->shipped_date = '';
            //拆分合并订单，重寄订单标记
            $current = '';
            $redirectLabel = '';
            $current_order_warehouse_id = OrderPackageKefu::getOrderPackageWareHouseId($model->order_id);

            if ($model->platform_order_id == $currentOrderId) {
                //添加当前订单产品信息
                $product_detail = json_encode(OrderKefu::getProductDetail($model->platform_code, $model->order_id));
                $order_id = $model->order_id;
                $model->warehouse_type = array_key_exists(isset($current_order_warehouse_id) ? $current_order_warehouse_id : 0, $warehouseTypeList) ?
                        $warehouseTypeList[isset($current_order_warehouse_id) ? $current_order_warehouse_id : 0] : '';
                $current_order_warehouse_name = array_key_exists(isset($current_order_warehouse_id) ? $current_order_warehouse_id : 0, $warehouseList) ?
                        $warehouseList[isset($current_order_warehouse_id) ? $current_order_warehouse_id : 0] : '';
                $current = '<span class="label label-danger">当前订单</span>' .
                        "<input type='hidden' name='current_order_id' value='$order_id'>" .
                        "<input type='hidden' name='current_order_warehouse_id' value='$current_order_warehouse_id'>" .
                        "<input type='hidden' name='current_order_warehouse_name' value='$current_order_warehouse_name'>" .
                        "<input type='hidden' id='current_product_detail' value='$product_detail'>";
            }
            //修改仓库名称 获取包裹信息的仓库
            $model->warehouse_name = array_key_exists($current_order_warehouse_id, $warehouseList) ?
                    $warehouseList[$current_order_warehouse_id] : '';
            if ($model->order_type == Order::ORDER_TYPE_REDIRECT_ORDER)
                $redirectLabel = '<span class="label label-warning">重寄订单</span>';

            switch ($model->order_type) {
                case Order::ORDER_TYPE_MERGE_MAIN:
                    $rela_order_name = '合并前子订单';
                    $rela_is_arr = true;
                    break;
                case Order::ORDER_TYPE_SPLIT_MAIN:
                    $rela_order_name = '拆分后子订单';
                    $rela_is_arr = true;
                    break;
                case Order::ORDER_TYPE_MERGE_RES:
                    $rela_order_name = '合并后父订单';
                    $rela_is_arr = false;
                    break;
                case Order::ORDER_TYPE_SPLIT_CHILD:
                    $rela_order_name = '拆分前父订单';
                    $rela_is_arr = false;
                    break;
                default:
                    $rela_order_name = '';
            }

            $order_result = '';
            if (!empty($rela_order_name)) {
                if ($rela_is_arr) {
                    //查找订单对应的父订单
                    $parentOrders = AliexpressOrder::findAll(['parent_order_id' => $model->order_id]);
                    if (!empty($parentOrders)) {
                        foreach ($parentOrders as $parentOrder) {
                            $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=ALI&system_order_id=' .
                                    $parentOrder->order_id . '" title="订单信息">' . $parentOrder->order_id . '</a></p>';
                        }
                    }
                    if (!empty($order_result))
                        $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                } else {
                    $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=ALI&system_order_id=' .
                            $model->parent_order_id . '" title="订单信息">' . $model->parent_order_id . '</a></p>';
                    $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                }
            }
            //包裹信息
            if (!empty($model->warehouse_name)) {
                $warehouse_name = $model->warehouse_name;
            } else {
                $warehouse_name = '<span class="label label-success">无</span>';
            }
            if (!empty($model->ship_name)) {
                $ship_name = $model->ship_name;
            } else {
                $ship_name = '<span class="label label-success">无</span>';
            }
            if (!empty($model->track_number)) {
                $track_number = $model->track_number;
            } else {
                $track_number = '<span class="label label-success">无</span>';
            }
            if (!empty($model->shipped_date)) {
                $shipped_date = $model->shipped_date;
            } else {
                $shipped_date = '<span class="label label-success">无</span>';
            }

            $model->package_info = $warehouse_name . '<br>' . $ship_name . '<br>' . $track_number . '<br>' . $shipped_date;

            //账号,国家,买家ID
            $resultt = Account::getHistoryAccountInfo($model->account_id, Platform::PLATFORM_CODE_ALI);
            if (!empty($resultt->account_name)) {
                $account_name = $resultt->account_name;
            } else {
                $account_name = '<span class="label label-success">无</span>';
            }
            if (!empty($model->ship_country)) {
                $ship_country = $model->ship_country;
            } else {
                $ship_country = '<span class="label label-success">无</span>';
            }
            if (!empty($model->buyer_id)) {
                $buyer_id = $model->buyer_id;
            } else {
                $buyer_id = '<span class="label label-success">无</span>';
            }
            $model->account_country_buyer = $account_name . '<br>' . $ship_country . '<br>' . $buyer_id;
            //付款时间,订单状态 ,评价
            if (!empty($model->paytime)) {
                $paytime = $model->paytime;
            } else {
                $paytime = '<span class="label label-success">无</span>';
            }
            if (!empty($model->complete_status_text)) {
                $complete_status_text = $model->complete_status_text;
            } else {
                $complete_status_text = '<span class="label label-success">无</span>';
            }
            $model->pay_time_status = $paytime . '<br>' . $complete_status_text."<br>".$model->evaluate;

            //获取订单交易信息
            $trade = OrderKefu::getTrade($model->order_id, Platform::PLATFORM_CODE_ALI);
            if (isset($trade) && !empty($trade)) {
                $after_refund_amount = 0;
                foreach ($trade as $after_sale_refund) {
                    if ($after_sale_refund['amt'] < 0)
                        $after_refund_amount += $after_sale_refund['amt'];
                }
                if (!empty($after_refund_amount)) {
                    $refund_amount_amt = $after_refund_amount;
                } else {
                    $refund_amount_amt = '-';
                }
            } else {
                $refund_amount_amt = '-';
            }
            //获取利润信息
            $profit = OrderKefu::getProfit($model->order_id);
            $refundlost = 0;
            if ($refundlost) {
                $refundlost = $refundlost >= 0 ? '<font color="green">' . $refundlost . '(CNY)</font>' : '<font color="red">' . $refundlost . '(CNY)</font>';
            } else {
                if ($profit) {
                    $refundlost = $profit['profit'] >= 0 ? '<font color="green">' . (($profit['profit_new1']) + ($profit['stock_price'])) . '(CNY)</font>' : '<font color="red">' . (($profit['profit_new1']) + ($profit['stock_price'])) . '(CNY)</font>';
                } else {
                    $refundlost = '<font color="green">0(CNY)</font>';
                }
            }
            //订单金额,退款金额,利润
            if (!empty($model->total_price)) {
                $total_price = '<span style="color:green;">' . $model->total_price . '</span>';
            } else {
                $total_price = '<span class="label label-success">无</span>';
            }
            $model->order_refund_monery = $total_price . '<br>' . $refund_amount_amt . '<br>' . $refundlost;
            //纠纷状态,退货编码,售后,仓库客诉单
            $refundcode=\app\modules\aftersales\models\AfterRefundCode::find()->where(['order_id'=>$model->order_id])->asArray()->one();
            if(empty($refundcode)){
               $refundcode['refund_code']= '<span class="label label-success">无</span>';
            }
             //仓库客诉单
            $complaint = \app\modules\aftersales\models\ComplaintModel::find()->select('complaint_order,status')->where(['order_id' => $model->order_id])->one();
            if(!empty($complaint)){
                $complaint_complaint_order='<a _width="100%" _height="100%" class="edit-button" href=' . Url::toRoute(['/aftersales/complaint/getcompain', 'complaint_order' => $complaint->complaint_order]) . '>' . $complaint->complaint_order . '(已处理)</a>';
            }else{
                $complaint_complaint_order='<span class="label label-success">无</span>';
            }
            $model->issue_feedback_sale = $model->issue_status . '<br>' . $refundcode['refund_code'] . '<br>' . $model->after_sale_text."<br>".$complaint_complaint_order;
            //店铺订单状态
            $model->order_status_time = $model->order_status . '<br>';
            $model->order_link .= $current;
            if(!empty($remark)){
                $model->order_link .= '<br/><span style="color:red;cursor: pointer" onclick="have_remark(this);" class="have_remark_'.$i.'" data-id="'.$i.'" data-remark="'. $remark.'">有备注</span>';
            }
            if (!empty($order_result))
                $model->order_link .= $redirectLabel . $order_result;
            $modelDatas[] = $model;
            $key++;
        }

        $dataProvider->setModels($modelDatas);
        return $this->renderList('index', [
                    'model' => $orderModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 未关联订单的收款请求
     */
    public function actionGathering() {
        $this->isPopup = true;
        //收款账号
        $paypal_email = PaypalAccount::getPaypleEmails();
        if (Yii::$app->request->isPost) {
            $bool = FALSE;
            $message = "操作成功!";
            $paypalAccount = $this->request->post('paypalAccount');
            $payerEmail = $this->request->post('payerEmail');
            $currency = $this->request->post('currency');
            $amount = $this->request->post('amount');
            $productName = $this->request->post('productName');
            $note = $this->request->post('note') ? $this->request->post('note') : '';


            if (!isset($paypalAccount) && !isset($payerEmail) && !isset($currency) && !isset($amount) && !isset($productName)) {
                $bool = true;
                $message = '收款信息不完整，请填写完整！';
            }

            //获取paypal的client_id /secret
            $account_info = RefundAccount::getAccountOne($paypalAccount);

            if (!$account_info['client_id']) {
                $bool = true;
                $message = '未获取到client_id,请检查收款账号！';
            }
            $clientId = $account_info['client_id'];
            $clientSecret = $account_info['secret'];

            //通过paypal invoice API发送数据 并保存到客服数据库中
            if (!$bool) {
                $apiContext = PaypalInvoice::getApiContext($clientId, $clientSecret);
                if (!is_object($apiContext)) {
                    $bool = true;
                    $message = '未获取到Token,请检查收款账号！';
                }
                //生成开票草稿
                $invoice = PaypalInvoice::createInvoice($paypalAccount, $payerEmail, $productName, $amount, $currency, $note, $apiContext);

                if (!empty($invoice->id)) {
                    //开启事物
                    $transaction = Yii::$app->db->beginTransaction();

                    //添加开票草稿记录
                    $invoiceInserResult = PaypalInvoiceRecord::addInvoiceRecord($invoice, '');
                    if ($invoiceInserResult['bool']) {
                        $bool = TRUE;
                        $message = $invoiceInserResult['info'];
                    }
                    //发送开票草稿
                    $sendInvoiceResult = PaypalInvoice::sendInvoice($invoice, $apiContext);
                    if ($sendInvoiceResult['status']) {
                        $updateRes = PaypalInvoiceRecord::updateInvoiceRecord($sendInvoiceResult['invoiceData'], 'SENT');
                        if ($updateRes['bool']) {
                            $bool = TRUE;
                            $message = $updateRes['info'];
                        }
                    } else {
                        $bool = TRUE;
                        $message = $sendInvoiceResult;
                    }

                    if (!$bool) {
                        $transaction->commit();
                    } else {
                        $transaction->rollBack();
                    }
                } else {
                    $bool = true;
                    $message = '收款失败，请检查所填写内容！';
                    $info = $invoice;
                }
            }
            echo json_encode(['bool' => $bool, 'msg' => $message, 'info' => ($sendInvoiceResult['invoiceData'] ? $sendInvoiceResult['invoiceData'] : $info)]);
            exit;
        }

        return $this->render('paypalgathering', [
                    'paypal_email' => $paypal_email,
        ]);
    }

    //收款功能

    public function actionEbaypaypalinvoice() {
        $this->isPopup = true;
        // 平台订单号
        $order_id = trim($this->request->get('order_id'));
        //交易号
        $transaction_id = $this->request->getQueryParam('transaction_id');
        //平台code
        //$platform = Platform::PLATFORM_CODE_EB;
        $platform = $this->request->getQueryParam('platform');
        //系统订单号
        $platform_order_id = $this->request->getQueryParam('platform_order_id');

        //获取订单信息
        $orderinfo = [];
        if ($platform && $platform_order_id) {
            $orderinfo = OrderKefu::getOrderStack($platform, $platform_order_id);
        }

        if (!$orderinfo['info'])
            $this->_showMessage('未获取到订单信息', false);

        $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $transaction_id])->asArray()->one();

        if (!empty($transactionRecord)) {
            $receiver_email = $transactionRecord['receiver_email'];
            $payer_email = $transactionRecord['payer_email'];
        }

        $receiver_email = $receiver_email ? $receiver_email : '';
        $payer_email = $payer_email ? $payer_email : '';
        $tradeData = array(
            'receiver_email' => $receiver_email,
            'payer_email' => $payer_email,
        );
        $productName = $orderinfo['product'][0]['title'] . '(item_id:' . $orderinfo['product'][0]['item_id'] . ')';
        //paypal账号信息
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }
        return $this->render('ebaypaypalinvoice', [
                    'order_id' => $order_id,
                    'platform' => $platform,
                    'platform_order_id' => $platform_order_id,
                    'trade' => $tradeData,
                    'productName' => $productName,
                    'paypallist' => $palPalList
        ]);
    }

    //创建并发送收款请求
    public function actionSendpaypalinvoice() {
        if (Yii::$app->request->isPost) {
            $bool = FALSE;
            $message = "操作成功!";
            $paypalAccount = $this->request->post('paypalAccount');
            $payerEmail = $this->request->post('payerEmail');
            $currency = $this->request->post('currency');
            $amount = $this->request->post('amount');
            $platform_code = $this->request->post('platform_code');
            $productName = $this->request->post('productName');
            $note = $this->request->post('note') ? $this->request->post('note') : '';
            $orderId = $this->request->post('orderId') ? $this->request->post('orderId') : '';
            if (in_array(Yii::$app->user->identity->login_name, ['胡文俊'])) {
                var_dump($this->request->post());
            }
            if (!isset($paypalAccount) && !isset($payerEmail) && !isset($currency) && !isset($amount) && !isset($productName)) {
                $bool = true;
                $message = '收款信息不完整，请填写完整！';
            }
            //获取paypal的client_id /secret
            $account_info = RefundAccount::getAccountOne($paypalAccount);
            if (!$account_info['client_id']) {
                $bool = true;
                $message = '未获取到client_id,请检查收款账号！';
            }
            $clientId = $account_info['client_id'];
            $clientSecret = $account_info['secret'];

            //通过paypal invoice API发送数据 并保存到客服数据库中
            if (!$bool) {
                $apiContext = PaypalInvoice::getApiContext($clientId, $clientSecret);
                if (!is_object($apiContext)) {
                    $bool = true;
                    $message = '未获取到Token,请检查收款账号！';
                }
                //生成开票草稿
                $invoice = PaypalInvoice::createInvoice($paypalAccount, $payerEmail, $productName, $amount, $currency, $note, $apiContext);
                if (!empty($invoice->id)) {
                    //开启事物
                    $transaction = Yii::$app->db->beginTransaction();

                    //添加开票草稿记录
                    $invoiceInserResult = PaypalInvoiceRecord::addInvoiceRecord($invoice, $orderId, $platform_code);
                    if ($invoiceInserResult['bool']) {
                        $bool = TRUE;
                        $message = $invoiceInserResult['info'];
                    }
                    //发送开票草稿
                    $sendInvoiceResult = PaypalInvoice::sendInvoice($invoice, $apiContext);
                    if ($sendInvoiceResult['status']) {
                        $updateRes = PaypalInvoiceRecord::updateInvoiceRecord($sendInvoiceResult['invoiceData'], 'SENT');
                        if ($updateRes['bool']) {
                            $bool = TRUE;
                            $message = $updateRes['info'];
                        }

                        //添加到订单备注
                        $remark = "向客户发起收款，客户paypal账号：" . $payerEmail . "；我司payapl账号：" . $paypalAccount . "；币种：" . $currency . "；金额：" . $amount . "；产品名称：" . $productName . "；留言：" . $note;
                        $user = Yii::$app->user->id;
                        $name = Yii::$app->user->identity->user_name;
                        $orderRemarkInfos = Order::getAddremark($orderId, $remark, $user, $name);
                        if ($orderRemarkInfos->ack != 'true') {
                            $bool = TRUE;
                            $message = $orderRemarkInfos->OrderRemark;
                        }
                    } else {
                        $bool = TRUE;
                        $message = $sendInvoiceResult;
                    }

                    if (!$bool) {
                        $transaction->commit();
                    } else {
                        $transaction->rollBack();
                    }
                } else {
                    $bool = true;
                    $message = '收款失败，请检查所填写内容！';
                    $info = $invoice;
                }
            }
        }

        echo json_encode(['bool' => $bool, 'msg' => $message, 'info' => ($sendInvoiceResult['invoiceData'] ? $sendInvoiceResult['invoiceData'] : $info), 'orderId' => $orderId, 'platform_code' => $platform_code]);
        exit;
    }

    /**
     * 取消收款请求
     * @throws \yii\db\Exception
     */
    public function actionCancelebaypaypalinvoice() {
        $this->isPopup = true;
        $bool = FALSE;
        $message = "操作成功!";
        $request = Yii::$app->request->get();
        $orderId = $request['order_id'];
        $invoiceId = $request['invoice_id'];
        $mail = $request['invoice_email'];
        //获取paypal的client_id /secret
        $account_info = RefundAccount::getAccountOne($mail);
        if (!$account_info['client_id']) {
            $bool = true;
            $message = '未获取到client_id,请检查收款账号！';
        }
        $clientId = $account_info['client_id'];
        $clientSecret = $account_info['secret'];
        $apiContext = PaypalInvoice::getApiContext($clientId, $clientSecret);
        if (!is_object($apiContext)) {
            $bool = true;
            $message = '未获取到Token,请检查收款账号！';
        }
        $invoice = PaypalInvoice::getInvoiceInfo($invoiceId, $apiContext);

        if (isset($invoice->id)) {
            //开启事物
            $transaction = Yii::$app->db->beginTransaction();

            $cancelInvoiceResult = PaypalInvoice::cancelInvoice($invoice, $apiContext);
            if ($cancelInvoiceResult) {
                $updateRes = PaypalInvoiceRecord::updateInvoiceRecord($invoiceId, 'CANCELLED');
                if ($updateRes['bool']) {
                    $bool = TRUE;
                    $message = $updateRes['info'];
                }

                //添加到订单备注
                $remark = "取消向客户发起收款，客户paypal账号：" . $invoice->billing_info[0]->email . "；我司payapl账号：" . $invoice->merchant_info->email . "；币种：" . $invoice->total_amount->currency . "；金额：" . $invoice->total_amount->value . "；产品名称：" . $invoice->items[0]->name . "；留言：" . $invoice->note;
                $user = Yii::$app->user->id;
                $name = Yii::$app->user->identity->user_name;
                $orderRemarkInfos = Order::getAddremark($orderId, $remark, $user, $name);
                if ($orderRemarkInfos->ack != 'true') {
                    $bool = TRUE;
                    $message = $orderRemarkInfos->OrderRemark;
                }
            } else {
                $bool = true;
                $message = '';
            }
            if (!$bool) {
                $transaction->commit();
            } else {
                $transaction->rollBack();
            }
        } else {
            $bool = true;
            $message = '取消收款失败，请检查所用账号！';
        }
        echo json_encode(['bool' => $bool, 'msg' => $message]);
        exit;
    }

    /**
     * 更新速卖通店铺订单状态
     */
    public function actionUpdatealishoporderstatus() {
        $orderId = Yii::$app->request->post('order_id', '');

        if (empty($orderId)) {
            die(json_encode([
                'code' => 0,
                'message' => '订单ID为空',
                'data' => ['order_id' => $orderId],
            ]));
        }

        $orderInfo = AliexpressOrder::findOne(['platform_order_id' => $orderId]);
        if (empty($orderId)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到订单信息',
                'data' => ['order_id' => $orderId],
            ]));
        }

        //处理以前中间有-的订单ID
        $orderIdArr = explode('-', $orderInfo['platform_order_id']);
        if (count($orderIdArr) == 2) {
            $orderId = $orderIdArr[0];
        }

        $apiOrderInfo = AliexpressOrder::getOrderInfo($orderId, $orderInfo['account_id']);
        if (empty($apiOrderInfo)) {
            die(json_encode([
                'code' => 0,
                'message' => '通过接口获取订单信息失败',
                'data' => ['order_id' => $orderId],
            ]));
        }

        if (!empty($apiOrderInfo['target']['order_status'])) {
            $result = Order::updateOrderStatus(Platform::PLATFORM_CODE_ALI, $orderInfo['platform_order_id'], $apiOrderInfo['target']['order_status']);
            if ($result) {
                die(json_encode([
                    'code' => 1,
                    'message' => '成功',
                    'data' => ['order_id' => $orderId],
                ]));
            } else {
                die(json_encode([
                    'code' => 0,
                    'message' => '失败',
                    'data' => ['order_id' => $orderId],
                ]));
            }
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '订单状态为空',
                'data' => ['order_id' => $orderId],
            ]));
        }
    }

    /**
     * @author alpha
     * @desc 忽略发货
     */
    public function actionIgnoreitem() {
        $data = Yii::$app->request->post();
        $id = isset($data['id']) ? intval($data['id']) : 0;
        $paltform_code = isset($data['platform_code']) ? trim($data['platform_code']) : null;
        $plat_order_id = isset($data['order_id']) ? trim($data['order_id']) : null;
        $erp_api = new ErpOrderApi();
        $dataArr['id'] = $id;
        $dataArr['order_id'] = $plat_order_id;
        $dataArr['platform_code'] = $paltform_code;
        $data['create_user'] = Yii::$app->user->identity->login_name;
        $dataArr['create_time'] = date('Y-m-d H:i:s');
        $ignore_item_res = $erp_api->Ignoreitem($dataArr);
        if ($ignore_item_res->statusCode != 200)
            $this->_showMessage('操作失败，' . $ignore_item_res->message, false);
        $this->_showMessage($ignore_item_res->message, true, null, true, null, null, false);
    }

    /**
     * @author alpha
     * @desc 恢复发货
     */
    public function actionRecoveritem() {
        $data = Yii::$app->request->post();
        $id = isset($data['id']) ? intval($data['id']) : 0;
        $paltform_code = isset($data['platform_code']) ? trim($data['platform_code']) : null;
        $plat_order_id = isset($data['order_id']) ? trim($data['order_id']) : null;
        $dataArr['id'] = $id;
        $dataArr['order_id'] = $plat_order_id;
        $dataArr['platform_code'] = $paltform_code;
        $data['create_user'] = Yii::$app->user->identity->login_name;
        $dataArr['create_time'] = date('Y-m-d H:i:s');
        $erp_api = new ErpOrderApi();
        $recover_item_res = $erp_api->Recoveritem($dataArr);
        if ($recover_item_res->statusCode != 200)
            $this->_showMessage('操作失败，' . $recover_item_res->message, false);
        $this->_showMessage($recover_item_res->message, true, null, true, null, null, false);
    }

    /**
     * @desc 修改订单地址
     */
    public function actionEditaddress() {
        $orderId = $this->request->getQueryParam('order_id');
        $returnid = $this->request->getQueryParam('returnid');
        $platformCode = $this->request->getQueryParam('platform');
        $is_return = $this->request->getQueryParam('is_return');
        $track_number = $this->request->getQueryParam('track_number');
        if (empty($orderId))
            $this->_showMessage('无效的订单', false);
        if (empty($platformCode))
            $this->_showMessage('无效的平台', false);
        $data = [];
        $data['order_id'] = $orderId;
        $data['platform_code'] = $platformCode;
        $data['ship_city_name'] = $shipCityName = trim($this->request->getBodyParam('ship_city_name'));
        $ship_country = $shipCountry = trim($this->request->getBodyParam('ship_country'));
        $ship_code = explode('&', $ship_country)[0]; //简称
        $ship_name = explode('&', $ship_country)[1]; //全称
        $data['ship_country'] = $ship_code;
        $data['ship_country_name'] = $ship_name;
        $data['ship_name'] = $shipName = trim($this->request->getBodyParam('ship_name'));
        $data['ship_phone'] = $shipPhone = trim($this->request->getBodyParam('ship_phone'));
        $data['ship_stateorprovince'] = $state = trim($this->request->getBodyParam('ship_stateorprovince'));
        $data['ship_street1'] = $address1 = trim($this->request->getBodyParam('ship_street1'));
        $data['ship_street2'] = $address2 = trim($this->request->getBodyParam('ship_street2'));
        $data['ship_zip'] = $postCode = trim($this->request->getBodyParam('ship_zip'));
        $data['email'] = $email = trim($this->request->getBodyParam('email'));
        $data['buyer_id'] = $buyer_id = trim($this->request->getBodyParam('buyer_id'));
        $data['create_user'] = Yii::$app->user->identity->login_name;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['is_return'] = $is_return;

        if (empty($buyer_id))
            $this->_showMessage('买家id不能为空', false);
        if (empty($postCode))
            $this->_showMessage('邮编不能为空', false);
        if ($platformCode != 'ALI' && empty($email))
            $this->_showMessage('邮箱不能为空', false);
        if (empty($shipName))
            $this->_showMessage('收件人不能为空', false);
        if (empty($shipCountry))
            $this->_showMessage('国家不能为空', false);
        if (empty($address1))
            $this->_showMessage('地址不能为空', false);
        if (empty($shipCityName))
            $this->_showMessage('城市不能为空', false);

        $orderModel = new ErpOrderApi();
        if ($is_return == 1) {
            $ship_data = array(
                "order_id" => $orderId,
                "platform_code" => $platformCode,
                "track_number" => $track_number,
                "type" => 2, //2发货,3不发货
                'create_user' => Yii::$app->user->identity->login_name, //修改人
                'create_time' => date('Y-m-d H:i:s'), //修改时间
            );

            $resdiu = $orderModel->Whethership($ship_data);
            if ($resdiu->statusCode == 200) {
                $orderModels = new ErpOrderApi();
                $res = $orderModels->editShippingAddress($data);
                if ($res->statusCode == 200) {
                    $record = '修改地址';
                    $record .= '买家id:' . $buyer_id;
                    $record .= '邮编:' . $postCode;
                    $record .= '邮箱:' . $email;
                    $record .= '收件人:' . $shipName;
                    $record .= '国家:' . $shipCountry;
                    $record .= '地址:' . $address1;
                    $record .= '城市:' . $shipCityName;

                    $type = Domesticreturngoods::saveRecord($returnid, $record, Yii::$app->user->identity->login_name, 1);
                    if ($type) {
                        $this->_showMessage('修改地址成功', true, null, true, null, null, false);
                    } else {
                        $this->_showMessage('修改地址失败，' . '更新退件单失败', false);
                    }
                } else {
                    $this->_showMessage($resdiu->message, false);
                }
            } else {
                $this->_showMessage($resdiu->message, false);
            }
        } else {
            $res = $orderModel->editShippingAddress($data);
            if ($res->statusCode != 200)
                $this->_showMessage('修改地址失败，' . $res->message, false);
            $this->_showMessage('修改地址成功', true, null, true, null, null, false);
        }
    }

    /**
     * @desc 编辑订单仓库物流
     */
    public function actionEditorderwarehouse() {
        $orderId = $this->request->getQueryParam('order_id');
        $returnid = $this->request->getQueryParam('returnid');
        $platformCode = $this->request->getQueryParam('platform');
        $is_return = $this->request->getQueryParam('is_return');
        $track_number = $this->request->getQueryParam('track_number');

        if (empty($orderId))
            $this->_showMessage('无效的订单', false);
        if (empty($platformCode))
            $this->_showMessage('无效的平台', false);
        $warehouseId = (int) $this->request->getBodyParam('warehouse_id');
        $shipCode = trim($this->request->getBodyParam('ship_code'));
        if (empty($warehouseId))
            $this->_showMessage('发货仓库不能为空', false);
        if ($shipCode === '' || $shipCode === null)
            $this->_showMessage('邮寄方式不能为空', false);

        $data = [];
        $data['order_id'] = $orderId;
        $data['platform_code'] = $platformCode;
        $data['warehouse_id'] = $warehouseId;
        $data['ship_code'] = $shipCode;
        $data['create_user'] = Yii::$app->user->identity->login_name;
        $data['create_time'] = date('Y-m-d H:i:s');
        $warehouseList = Warehouse::getWarehouseList();
        $logistics = Logistic::getWarehouseLogistics($warehouseId);
        $logisticsname = '';
        foreach ($logistics as $logistic) {
            if ($logistic->ship_code == $shipCode) {
                $logisticsname = $logistic->ship_name;
                break;
            }
        }

        $orderModel = new ErpOrderApi();
        if ($is_return == 1) {
            $ship_data = array(
                "order_id" => $orderId,
                "platform_code" => $platformCode,
                "track_number" => $track_number,
                "type" => 2, //2发货,3不发货
                'create_user' => Yii::$app->user->identity->login_name, //修改人
                'create_time' => date('Y-m-d H:i:s'), //修改时间
            );
            $resdiu = $orderModel->Whethership($ship_data);
            if ($resdiu->statusCode == 200) {
                $orderModel = new ErpOrderApi();
                $res = $orderModel->editOrderWarehouse($data);

                if ($res->statusCode == 200) {
                    $record = '修改信息';
                    $record .= '<br>发货仓库：' . $warehouseList[$warehouseId];
                    $record .= '<br>邮寄方式：' . $logisticsname;
                    $type = Domesticreturngoods::saveRecord($returnid, $record, Yii::$app->user->identity->login_name, 1);
                    if ($type) {
                        $this->_showMessage('操作成功', true, null, true, null, null, false);
                    } else {
                        $this->_showMessage('操作失败', true, null, true, null, null, false);
                    }
                } else {
                    $this->_showMessage($res->message, false);
                }
            } else {
                $this->_showMessage($resdiu->message, false);
            }
        } else {
            $res = $orderModel->editOrderWarehouse($data);
            if ($res->statusCode != 200) {
                $this->_showMessage('操作失败，' . $res->message, false);
            }
            $this->_showMessage('操作成功', true, null, true, null, null, false);
        }
    }

    /**
     * @desc 编辑订单产品
     *
     */
    public function actionEditorderproduct() {
        $sku_data = [];
        $orderId = $this->request->getQueryParam('order_id');
        $platformCode = $this->request->getQueryParam('platform');
        $is_return = $this->request->getQueryParam('is_return');
        $track_number = $this->request->getQueryParam('track_number');

        if (empty($orderId))
            $this->_showMessage('无效的订单', false);
        if (empty($platformCode))
            $this->_showMessage('无效的平台', false);
        $skuArr = $this->request->getBodyParam('sku');
        $titleArr = $this->request->getBodyParam('product_title');
        $quantityArr = $this->request->getBodyParam('quantity');
        $salePriceArr = $this->request->getBodyParam('sale_price');
        $shipPriceArr = $this->request->getBodyParam('ship_price');
        $is_erpArr = $this->request->getBodyParam('is_erp');
        $total_priceArr = $this->request->getBodyParam('total_price');
        $item_idArr = $this->request->getBodyParam('item_id');
        $editskuArr = $this->request->getBodyParam('editsku');
        $product_idArr = $this->request->getBodyParam('product_id');
        $product_titleArr = $this->request->getBodyParam('product_title');
        $is_deleteArr = $this->request->getBodyParam('is_delete');
        foreach ($skuArr as $key => $sku) {
            $sku = trim($sku);
            if (empty($sku))
                $this->_showMessage('SKU不能为空', false);
            $title = isset($titleArr[$key]) ? trim($titleArr[$key]) : '';
            if (empty($title))
                $this->_showMessage('产品标题不能为空', false);
            $quantity = isset($quantityArr[$key]) ? (int) $quantityArr[$key] : 0;
            if ($quantity <= 0)
                $this->_showMessage('产品数量不能为小于等于0', false);
            $salePrice = isset($salePriceArr[$key]) ? floatval($salePriceArr[$key]) : 0.00;
            $shipPrice = isset($shipPriceArr[$key]) ? floatval($shipPriceArr[$key]) : 0.00;
            $total_price = isset($total_priceArr[$key]) ? floatval($total_priceArr[$key]) : 0.00;
            $is_erp = isset($is_erpArr[$key]) ? $is_erpArr[$key] : null;
            $item_id = isset($item_idArr[$key]) ? $item_idArr[$key] : '';
            $editsku = isset($editskuArr[$key]) ? $editskuArr[$key] : null;
            $product_id = isset($product_idArr[$key]) ? $product_idArr[$key] : 0;
            $product_title = isset($product_titleArr[$key]) ? $product_titleArr[$key] : '';
            $is_delete = isset($is_deleteArr[$key]) ? $is_deleteArr[$key] : '';
            $sku_data[] = [
                'item_id' => $item_id,
                'is_delete' => $is_delete,
                'is_erp' => $is_erp,
                'editsku' => $editsku,
                'number' => $quantity,
                'qs' => $quantity,
                'sku' => $sku,
                'sale_price' => $salePrice,
                'ship_price' => $shipPrice,
                'total_price' => $total_price,
                'id' => $product_id,
                'title' => $product_title
            ];
        }
        $data['order_id'] = $orderId;
        $data['platform_code'] = $platformCode;
        $data['goods'] = $sku_data;
        $orderModel = new ErpOrderApi();
        if ($is_return == 1) {
            $ship_data = array(
                "order_id" => $orderId,
                "platform_code" => $platformCode,
                "track_number" => $track_number,
                "type" => 2, //2发货,3不发货
                'create_user' => Yii::$app->user->identity->login_name, //修改人
                'create_time' => date('Y-m-d H:i:s'), //修改时间
            );
            $resdiu = $orderModel->Whethership($ship_data);
            if ($resdiu->statusCode == 200) {
                $orderModel = new ErpOrderApi();
                $res = $orderModel->editOrderProduct($data);
                if ($res->statusCode == 200) {
                    $record = '修改信息';
                    $record .= '<br>：标题产品sku	数量	平台卖价	总运费' . $product_title;
                    $record .= '<br>：' . $logisticsname;
                    $type = Domesticreturngoods::saveRecord($returnid, $record, Yii::$app->user->identity->login_name, 1);
                    if ($type) {
                        $this->_showMessage('操作成功', true, null, true, null, null, false);
                    } else {
                        $this->_showMessage('操作失败' . '<br>' . $res->message, false);
                    }
                } else {
                    $this->_showMessage('操作失败' . '<br>' . $res->message, false);
                }
            } else {
                $this->_showMessage($resdiu->message, false);
            }
        } else {
            $res = $orderModel->editOrderProduct($data);
            if ($res->statusCode != 200)
                $this->_showMessage('操作失败' . '<br>' . $res->message, false);
            $this->_showMessage('操作成功', true, null, true, null, null, false);
        }
    }

    /*     * **
     * 
     * 获取对应仓库的id
     * @param $warehouse_type [string]
     * @return $warehouse_res [array]
     * @author harvin <2018-11-12>
     * * */

    protected function getWarehouseType($warehouse_type) {
        $warehouse_res = [];
        if (!empty($warehouse_type)) {
            if ($warehouse_type == 12) { //海外仓
                $warehouse_type1 = "易佰东莞仓库";
                $warehouse_type2 = "海外虚拟仓";
                $warehouse_type3 = "代销";
                $warehouse_type4 = "中转";
                $res = Warehouse::find()->select('id')->where(['not like', 'warehouse_name', $warehouse_type1])
                        ->andWhere(['not like', 'warehouse_name', $warehouse_type2])
                        ->andWhere(['not like', 'warehouse_name', $warehouse_type3])
                        ->andWhere(['not like', 'warehouse_name', $warehouse_type4])
                        ->all();
                foreach ($res as $v) {
                    $warehouse_res[] = $v->id;
                }
            } else {
                $res = Warehouse::find()->select('id')->where(['like', 'warehouse_name', $warehouse_type])->all();
                foreach ($res as $v) {
                    $warehouse_res[] = $v->id;
                }
            }
        }
        return $warehouse_res;
    }

}
