<?php

namespace app\modules\aftersales\controllers;

use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\aftersales\models\AfterSalesProduct;
use app\modules\aftersales\models\AfterSalesReceipt;
use app\modules\orders\models\Logistic;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderAmazonItem;
use app\modules\orders\models\OrderDetail;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\OrderWalmartItem;
use app\modules\orders\models\Transactionrecord;
use app\modules\orders\models\Warehouse;
use app\modules\products\models\Product;
use app\modules\systems\models\AutoCode;
use app\modules\systems\models\Country;
use app\modules\systems\models\ErpOrderApi;
use app\modules\systems\models\ErpProductApi;
use app\modules\systems\models\RefundAccount;
use app\modules\systems\models\TransactionAddress;
use app\modules\systems\models\Transactions;
use app\modules\aftersales\models\Domesticreturngoods;
use Yii;
use app\modules\aftersales\models\AfterSalesOrder;
use yii\data\Pagination;
use yii\db\Exception;
use yii\helpers\Json;
use app\components\Controller;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\aftersales\models\AfterSalesReturn;
use app\modules\aftersales\models\OrderReturnDetail;
use app\modules\aftersales\models\AfterSalesRedirect;
use app\modules\aftersales\models\OrderRedirectDetail;
use app\modules\systems\models\BasicConfig;
use app\modules\users\models\UserRole;
use app\modules\orders\models\OrderEbayKefu;
use app\modules\orders\models\OrderAmazonKefu;
use app\modules\orders\models\OrderWishKefu;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\orders\models\OrderAliexpressKefu;
/**
 * RefundreturnreasonController implements the CRUD actions for RefundReturnReason model.
 */
class SalesController extends Controller {

    protected $order_status_map = array(0 => '初始化', 1 => '正常', 5 => '异常', 10 => '缺货', 13 => '已备货', 15 => '待发货', 17 => '超期', 19 => '部分发货', 20 => '已发货', 25 => '暂扣', 40 => '已取消', 45 => '已完成');
    protected $refund_status_map = array(1 => '待退款', 2 => '退款中', 3 => '退款完成', 4 => '退款失败');
    protected $return_status_map = array(1 => '待收货', 2 => '已收货', 3 => '取消退货');
    protected $audit_status_map = array(1 => '未审核', 2 => '审核通过', 3 => '退回修改', 4 => '完结');

    /**
     * 售后单列表
     * @return \yii\base\string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList() {
        $url = $_SERVER['REQUEST_URI'];
        $params = Yii::$app->request->get();
        if (empty($params)) {
            $params = Yii::$app->request->getBodyParams();
        }
        $_REQUEST['platform_code'] = AfterSalesOrder::ORDER_SEARCH_CONDITION_FROM_ALL;
        list($model, $dataProvider) = $this->dataList($params, $url);
        return $this->renderList('index', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
                    'platform_code' => $_REQUEST['platform_code'],
                    'url' => $url,
        ]);
    }

    /**
     * 速卖通退货退款列表.
     * @return mixed
     */
    public function actionAliexpresslist() {
        $url = $_SERVER['REQUEST_URI'];
        $params = \Yii::$app->request->getBodyParams();
        $_REQUEST['platform_code'] = Platform::PLATFORM_CODE_ALI;
        $params['platform_code'] = Platform::PLATFORM_CODE_ALI;

        list($model, $dataProvider) = $this->dataList($params, $url);

        return $this->renderList('index', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
                    'platform_code' => Platform::PLATFORM_CODE_ALI,
                    'url' => $url,
        ]);
    }

    /**
     * 公共查询列表的方法
     * @param array $params 查询条件组成的数组
     */
    protected function dataList($params, $url = null) {
        $model = new AfterSalesOrder();
        $dataProvider = $model->searchList($params, $url);
        return [$model, $dataProvider];
    }
  /***
   * 获取对应account_id
   * 
   * **/ 
    
    public function  actionGetaccountid(){
     $platform_code=Yii::$app->request->post('platform_code');
   
     if(empty($platform_code)){
       return json_encode(['state'=>0,'msg'=>'参数错误']);
     }
    $res= Account::find()->select('id,account_name')->where(['platform_code'=>$platform_code])->asArray()->all();
   
    $re=[];
       foreach($res as $v){
           $re[$v['id']]=$v['account_name'];
       }
  
     return json_encode(['data'=>$re]);        
     
     
   }






 /**
     * 亚马逊退货退款列表
     * @return mixed
     */
    public function actionAmazonlist() {
        $url = $_SERVER['REQUEST_URI'];
        $params = \Yii::$app->request->getBodyParams();
        $_REQUEST['platform_code'] = Platform::PLATFORM_CODE_AMAZON;
        $params['platform_code'] = Platform::PLATFORM_CODE_AMAZON;

        list($model, $dataProvider) = $this->dataList($params, $url);

        return $this->renderList('index', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
                    'platform_code' => Platform::PLATFORM_CODE_AMAZON,
                    'url' => $url,
        ]);
    }

    /**
     * 沃尔玛退货退款列表
     * @return mixed
     */
    public function actionWalmartlist() {
        $url = $_SERVER['REQUEST_URI'];
        $params = \Yii::$app->request->getBodyParams();
        $_REQUEST['platform_code'] = Platform::PLATFORM_CODE_WALMART;
        $params['platform_code'] = Platform::PLATFORM_CODE_WALMART;


        list($model, $dataProvider) = $this->dataList($params, $url);

        return $this->renderList('index', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
                    'platform_code' => Platform::PLATFORM_CODE_WALMART,
                    'url' => $url,
        ]);
    }

    /**
     * Ebay退货退款列表
     * @return \yii\base\string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionEbaylist() {
        $url = $_SERVER['REQUEST_URI'];
        $params = \Yii::$app->request->getBodyParams();
        $params['platform_code'] = Platform::PLATFORM_CODE_EB;
        $_REQUEST['platform_code'] = Platform::PLATFORM_CODE_EB;
        list($model, $dataProvider) = $this->dataList($params, $url);
        return $this->renderList('index', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
                    'platform_code' => Platform::PLATFORM_CODE_EB,
                    'url' => $url,
        ]);
    }

    /**
     * wish退货退款列表
     * @return mixed
     */
    public function actionWishlist() {
        $url = $_SERVER['REQUEST_URI'];
        $params = \Yii::$app->request->getBodyParams();
        $params['platform_code'] = Platform::PLATFORM_CODE_WISH;
        $_REQUEST['platform_code'] = Platform::PLATFORM_CODE_WISH;

        list($model, $dataProvider) = $this->dataList($params, $url);

        return $this->renderList('index', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
                    'platform_code' => Platform::PLATFORM_CODE_WISH,
                    'url' => $url,
        ]);
    }

    /**
     * @desc 售后单详情公共方法
     */
    protected function detail($after_sale_id, $platform_code, $type) {

        list($data, $detail_data) = array(null, null);

        if ($type == "return") {
            $data = AfterSalesReturn::getList($after_sale_id, $platform_code);
            $detail_data = OrderReturnDetail::getList($after_sale_id);
        }

        if ($type == "refund") {
            $data = AfterSalesRefund::getList($after_sale_id, $platform_code);
        }

        if ($type == "redirect") {
            $data = AfterSalesRedirect::getList($after_sale_id, $platform_code);
            $detail_data = OrderRedirectDetail::getList($after_sale_id);
        }

        return [$data, $detail_data];
    }

    /**
     * @desc 退款售后单详情
     */
    public function actionDetailrefund() {
        $this->isPopup = true;
        $after_sale_id = $this->request->getQueryParam('after_sale_id');
        $platform_code = $this->request->getQueryParam('platform_code');
        $model = AfterSalesOrder::find()->where(['after_sale_id' => $after_sale_id])->asArray()->one();
        list($data, $detail_data) = $this->detail($after_sale_id, $platform_code, 'refund');
        $order_info = Order::getOrderStackByOrderId(Platform::PLATFORM_CODE_EB, '', $data->order_id);

        return $this->renderList('detailrefund', [
                    'model' => $model,
                    'data' => $data,
                    'detail_data' => $detail_data,
                    'order_info' => $order_info,
                    'status' => $this->request->getQueryParam('status'),
        ]);
    }

    /**
     * @desc 退货售后单详情
     */
    public function actionDetailreturn() {
        $this->isPopup = true;
        $after_sale_id = $this->request->getQueryParam('after_sale_id');
        $platform_code = $this->request->getQueryParam('platform_code');
        $model = AfterSalesOrder::find()->where(['after_sale_id' => $after_sale_id])->asArray()->one();
        list($data, $detail_data) = $this->detail($after_sale_id, $platform_code, 'return');

        return $this->renderList('detailreturn', [
                    'model' => $model,
                    'data' => $data,
                    'detail_data' => $detail_data,
                    'status' => $this->request->getQueryParam('status'),
        ]);
    }

    /**
     * @desc 重寄售后单详情
     */
    public function actionDetailredirect() {
        $this->isPopup = true;
        $after_sale_id = $this->request->getQueryParam('after_sale_id');
        $platform_code = $this->request->getQueryParam('platform_code');

        $model = AfterSalesOrder::find()->where(['after_sale_id' => $after_sale_id])->asArray()->one();

        list($data, $detail_data) = $this->detail($after_sale_id, $platform_code, 'redirect');

        return $this->renderList('detailredirect', [
                    'model' => $model,
                    'data' => $data,
                    'detail_data' => $detail_data,
                    'status' => $this->request->getQueryParam('status'),
        ]);
    }

    /*
     * @desc 批量审核 退款状态显示
     * batchList 批量更新条件
     * */

    public function actionBatchaudit() {

        $params = \Yii::$app->request->post("ids");
        $url = \Yii::$app->request->getQueryParam("url");
        if (empty($params))
            $this->_showMessage('无效的售后单号', false);
        $afterSalesOrderInfo = AfterSalesOrder::findByAfterSalesOrderId($params);
        if (empty($afterSalesOrderInfo)) {
            $this->_showMessage('找不到该售后单', false);
        }
        $result_id = '';
        foreach ($afterSalesOrderInfo as $key => $Orderid) {
            if ($Orderid->status == AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED) {
                continue;
            }
            $flag = $Orderid->audit(AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED);
            if (!$flag) {
                $result_id .= $Orderid->after_sale_id . ',';
            }
        }
        if ($result_id)
            $this->_showMessage($result_id . '售后单审核失败', false);
        else {
            if ($url) {
                $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute($url) . '");';
                $this->_showMessage('审核成功', true, null, false, null, $refreshUrl);
            } else
                $this->_showMessage('审核成功', true, null, true);
        }
    }

    /**
     * @desc
     * @return string
     * @throws Exception
     */
    public function actionEdit() {
        $this->isPopup = true;
        $after_sales_id = $this->request->getQueryParam('after_sales_id');
        $type = $this->request->getQueryParam('type');
        $afterSaleOrderModel = AfterSalesOrder::findOne($after_sales_id);
        if (empty($afterSaleOrderModel)) {
            $this->_showMessage('找不到对应售后单', false, null, false, null, 'layer.closeAll()');
        }
        $afterSaleDetail = '';
        $refundDetails = array();
        switch ($type) {
            case AfterSalesOrder::ORDER_TYPE_REFUND:
                $afterSaleDetail = AfterSalesRefund::findByAfterSaleId($after_sales_id);
                $refundDetails = \yii\helpers\Json::decode($afterSaleDetail->refund_detail);
                break;
            case AfterSalesOrder::ORDER_TYPE_RETURN:
                $afterSaleDetail = AfterSalesReturn::findByAfterSaleId($after_sales_id);
                break;
            case AfterSalesOrder::ORDER_TYPE_REDIRECT:
                $afterSaleDetail = AfterSalesRedirect::findOne($after_sales_id);
                break;
        }
        if (empty($afterSaleDetail)) {
            $this->_showMessage('找不到对应售后单详情', false, null, false, null, 'layer.closeAll()');
        }
        $orderId = $afterSaleOrderModel->order_id;
        $platform = $afterSaleOrderModel->platform_code;
        $orderinfo = [];
        if (empty($platform))
            $this->_showMessage('平台CODE无效', false, null, false, null, 'layer.closeAll()');
        if (empty($orderId))
            $this->_showMessage('订单号无效', false, null, false, null, 'layer.closeAll()');
        $orderinfo = Order::getOrderStackByOrderId($platform, '', $orderId);
        if (empty($orderinfo))
            $this->_showMessage('找不到对应订单', false, null, false, null, 'layer.closeAll()');
        $order_amount = isset($orderinfo->info) && isset($orderinfo->info->total_price) ? $orderinfo->info->total_price : 0.00;
        $allow_refund_amount = AfterSalesRefund::getAllowRefundAmount($orderId, $order_amount, $platform);

        if ($this->request->getIsPost()) {
            /* if($afterSaleOrderModel->status == 2)
              {
              $this->_showMessage('审核已通过，不能再次修改！');
              } */
            $orderProducts = [];
            if (isset($orderinfo->product) && !empty($orderinfo->product)) {
                foreach ($orderinfo->product as $row)
                    $orderProducts[$row->sku] = $row;
            }
            $dbTransaction = AfterSalesOrder::getDb()->beginTransaction();
            try {
                $issueProductArr = $this->request->getBodyParam('issue_product');
                $issueProductIdArr = $this->request->getBodyParam('issue_product_id');
                $issueProductIdValue = [];
                $departmentId = $this->request->getBodyParam('department_id');
                $reasonId = $this->request->getBodyParam('reason_id');
                $remark = $this->request->getBodyParam('remark');
                $after_sale_order_id = $this->request->getBodyParam('after_sale_order_id');
                $type = $this->request->getBodyParam('after_sales_type');

                if (empty($type))
                    $this->_showMessage('必须选择一个售后类型', false);

                if (!in_array($type, [
                            AfterSalesOrder::ORDER_TYPE_REFUND,
                            AfterSalesOrder::ORDER_TYPE_RETURN,
                            AfterSalesOrder::ORDER_TYPE_REDIRECT,
                        ]))
                    throw new \Exception('无效的售后类型');
                if (empty($departmentId)) {
                    $this->_showMessage('请选择责任归属部门', false);
                }

                if (empty($reasonId))
                    $this->_showMessage('请选择原因类型', false);


                $issueProducts = [];
                if (!empty($issueProductArr)) {
                    $isSetNum = FALSE;
                    foreach ($issueProductArr as $sku => $quantity) {
                        $sku = trim($sku);
                        $quantity = (int) $quantity;
                        if ($quantity) {
                            $isSetNum = TRUE;
                        }
                        if (empty($sku))
                            throw new \Exception('无效的SKU', false);
                        if (!isset($orderProducts[$sku]))
                            throw new \Exception('SKU不存在于订单中', false);
                        if ($quantity <= 0)
                            continue;
                        $issueProducts[] = [
                            'id' => $issueProductIdArr[$sku],
                            'platform_code' => $platform,
                            'order_id' => $orderId,
                            'sku' => $sku,
                            'product_title' => isset($orderProducts[$sku]->picking_name) ? $orderProducts[$sku]->picking_name : $orderProducts[$sku]->title,
                            'quantity' => $orderProducts[$sku]->quantity,
                            'linelist_cn_name' => $orderProducts[$sku]->linelist_cn_name,
                            'issue_quantity' => $quantity,
                            'reason_id' => $reasonId,
                        ];
                    }
                    if (!$isSetNum) {
                        $this->_showMessage('问题产品数量必填', false);
                    }
                }


                switch ($type) {
                    //处理退款
                    case $type == AfterSalesOrder::ORDER_TYPE_REFUND:
                        $refundAmount = floatval($this->request->getBodyParam('refund_amount'));
                        $currencyCode = $orderinfo->info->currency;
                        $message = trim($this->request->getBodyParam('message'));
                        $reasonCode = '';
                        $platform_order_id = '';

                        if ($platform == Platform::PLATFORM_CODE_AMAZON) {
                            $itemPriceAmounts = $this->request->getBodyParam('item_price_amount');
                            $itemShippingAmounts = $this->request->getBodyParam('item_shipping_amount');
                            $itemTaxAmounts = $this->request->getBodyParam('item_tax_amount');
                            $shippingTaxAmounts = $this->request->getBodyParam('shipping_tax_amount');
                            $shippingDiscountAmounts = $this->request->getBodyParam('shipping_discount_amount');
                            $promotionDiscountAmounts = $this->request->getBodyParam('promotion_discount_amount');
                            $reasonCodes = $this->request->getBodyParam('reason_code');
                            $platformOrderId = isset($orderinfo->info) && isset($orderinfo->info->platform_order_id) ?
                                    $orderinfo->info->platform_order_id : '';
                            if (empty($itemPriceAmounts))
                                $this->_showMessage('退款明细不能空', false);
                            foreach ($itemPriceAmounts as $itemID => $itemPriceAmount) {
                                if (empty($itemID))
                                    $this->_showMessage('退款Item ID为空', false);
                                if ($itemPriceAmount <= 0)
                                    continue;
//                                    $this->_showMessage('退款金额不能小于等于0', false);
                                $reasonCode = isset($reasonCodes[$itemID]) ? trim($reasonCodes[$itemID]) : '';
                                if (empty($reasonCode))
                                    $this->_showMessage('退款原因不能为空', false);
                                $refundDetail[$itemID]['reason_code'] = $reasonCode;
                                $refundDetail[$itemID]['item_price_amount'] = $itemPriceAmount;
                                $refundAmount += $itemPriceAmount;
                                if (isset($itemShippingAmounts[$itemID]) && $itemShippingAmounts[$itemID] > 0) {
                                    $refundDetail[$itemID]['item_shipping_amount'] = $itemShippingAmounts[$itemID];
                                    $refundAmount += $refundDetail[$itemID]['item_shipping_amount'];
                                }
                                if (isset($itemTaxAmounts[$itemID]) && $itemTaxAmounts[$itemID] > 0) {
                                    $refundDetail[$itemID]['item_tax_amount'] = $itemTaxAmounts[$itemID];
                                    $refundAmount += $refundDetail[$itemID]['item_tax_amount'];
                                }
                                if (isset($shippingTaxAmounts[$itemID]) && $shippingTaxAmounts[$itemID] > 0) {
                                    $refundDetail[$itemID]['shipping_tax_amount'] = $shippingTaxAmounts[$itemID];
                                    $refundAmount += $refundDetail[$itemID]['shipping_tax_amount'];
                                }
                                if (isset($shippingDiscountAmounts[$itemID]) && $shippingDiscountAmounts[$itemID] > 0) {
                                    $refundDetail[$itemID]['shipping_discount_amount'] = $shippingDiscountAmounts[$itemID];
                                    $refundAmount += $refundDetail[$itemID]['shipping_discount_amount'];
                                }
                                if (isset($promotionDiscountAmounts[$itemID]) && $promotionDiscountAmounts[$itemID] > 0) {
                                    $refundDetail[$itemID]['promotion_discount_amount'] = $promotionDiscountAmounts[$itemID];
                                    $refundAmount += $refundDetail[$itemID]['promotion_discount_amount'];
                                }
                            }
                        } elseif ($platform == Platform::PLATFORM_CODE_WALMART) {
                            $refundSku = $this->request->getBodyParam('refundsku');
                            $refundComments = $this->request->getBodyParam('refundComments');
                            if (empty($refundSku)) {
                                $this->_showMessage('退款明细不能空', false);
                            }
                            $refundDetail = $redetail = [];
                            foreach ($refundSku as $key => $value) {
                                foreach ($value as $val) {
                                    if (empty($val['amount']) || $val['amount'] == '0.00') {
                                        $this->_showMessage('请输入退款相关金额', false);
                                    }
                                    $val['item_id'] = $key;
                                    $redetail[] = $val;
                                    $refundAmount += $val['amount'];
                                    if ($val['chargeType'] == 'PRODUCT') {
                                        $reasonCode = $val['refundReason'];
                                    }
                                }
                            }
                            $platformOrderId = isset($orderinfo->info) && isset($orderinfo->info->platform_order_id) ? $orderinfo->info->platform_order_id : '';
                            $refundDetail = [
                                'platform_order_id' => $platformOrderId,
                                'refundComments' => $refundComments,
                                'returnDetail' => $redetail
                            ];
                        } else {
                            $reasonCode = trim($this->request->getBodyParam('reason_code'));
                            $refundAmount = floatval($this->request->getBodyParam('refund_amount'));
                        }
                        $message = trim($this->request->getBodyParam('message'));
                        $refundAmount = (float) $refundAmount;
                        $allow_refund_amount = (float) $allow_refund_amount;
                        if ($refundAmount <= 0.00)
                            $this->_showMessage('退款金额不能小于0', false);


                        $afterSaleOrderRefund = AfterSalesRefund::findOne(['after_sale_id' => $after_sale_order_id]);
                        $allow_refund_amount += $afterSaleOrderRefund->refund_amount;

                        if (bccomp($refundAmount, $allow_refund_amount) > 0) {
                            $this->_showMessage('退款金额不能大于可退款金额', false);
                        }

                        $afterSalesOrderModel = AfterSalesOrder::findOne(['after_sale_id' => $after_sale_order_id]);
                        $afterSalesOrderModel->order_id = $orderId;
                        $afterSalesOrderModel->type = $type;
                        $afterSalesOrderModel->platform_code = $platform;
                        $afterSalesOrderModel->department_id = $departmentId;
                        $afterSalesOrderModel->reason_id = $reasonId;
                        $afterSalesOrderModel->remark = $remark;
                        $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_WATTING_AUDIT;
                        $flag = $afterSalesOrderModel->save(false);
                        if (!$flag)
                            throw new \Exception('保存售后单失败', false);


                        $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
                        if ($refundAmount < $order_amount) {
                            $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_PARTIAL;
                        }
                        $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                        $afterSaleOrderRefund->refund_amount = $refundAmount;
                        $afterSaleOrderRefund->refund_detail = isset($refundDetail) ? json_encode($refundDetail) : null;
                        $afterSaleOrderRefund->currency = $currencyCode;
                        $afterSaleOrderRefund->reason_code = $reasonCode;
                        $afterSaleOrderRefund->message = $message;
                        $afterSaleOrderRefund->platform_code = $platform;
                        $afterSaleOrderRefund->order_id = $orderId;
                        $afterSaleOrderRefund->order_amount = $orderinfo->info->total_price;
                        $flag = $afterSaleOrderRefund->save();
                        if (!$flag)
                            throw new \Exception('保存退款数据失败');
                        break;

                    //处理退货
                    case AfterSalesOrder::ORDER_TYPE_RETURN:
                        //todo
                        $returnSkuArr = $this->request->getBodyParam('return_sku');
                        $returnProductId = $this->request->getBodyParam('return_product_id');
                        $returnProductIdValue = [];
                        $returnTitleArr = $this->request->getBodyParam('return_title');
                        $returnQuantityArr = $this->request->getBodyParam('return_quantity');
                        $returnLinelistCnNameArr = $this->request->getBodyParam('return_linelist_cn_name');
                        $returnWarehouseId = $this->request->getBodyParam('return_warehouse_id');
                        $returnCarrier = trim($this->request->getBodyParam('return_carrier'));
                        $returnTrackingNo = trim($this->request->getBodyParam('return_tracking_no'));
                        $return_rma = trim($this->request->getBodyParam('return_rma'));
                        $return_remark = trim($this->request->getBodyParam('return_remark'));
                        $returnItems = [];
                        $returnSkuList = [];
                        if (empty($returnWarehouseId))
                            $this->_showMessage('退回仓库不能为空', false);
                        if (empty($returnSkuArr))
                            $this->_showMessage('未选择产品', false);
                        foreach ($returnSkuArr as $key => $returnSku) {
                            $returnSku = trim($returnSku);
                            if (empty($returnSku))
                                $this->_showMessage('SKU为空', false);
                            if (in_array($returnSku, $returnSkuList))
                                $this->_showMessage('SKU{' . $returnSku . '}重复', false);
                            $returnTitle = isset($returnTitleArr[$key]) ? trim($returnTitleArr[$key]) : '';
                            if (empty($returnTitle))
                                $this->_showMessage('产品标题为空', false);
                            $returnQuantity = isset($returnQuantityArr[$key]) ? (int) $returnQuantityArr[$key] : 0;
                            if ($returnQuantity <= 0)
                                $this->_showMessage('产品数量必须大于0', false);
                            $returnLinelistCnName = isset($returnLinelistCnNameArr[$key]) ? $returnLinelistCnNameArr[$key] : '';
                            $returnItems[] = [
                                'id' => $returnProductId[$key],
                                'sku' => $returnSku,
                                'productTitle' => $returnTitle,
                                'quantity' => $returnQuantity,
                                'linelist_cn_name' => $returnLinelistCnName,
                            ];
                            array_push($returnSkuList, $returnSku);
                        }
                        $afterSalesOrderModel = AfterSalesOrder::findOne(['after_sale_id' => $after_sales_id]);
                        $afterSalesOrderModel->order_id = $orderId;
                        $afterSalesOrderModel->type = $type;
                        $afterSalesOrderModel->platform_code = $platform;
                        $afterSalesOrderModel->department_id = $departmentId;
                        $afterSalesOrderModel->reason_id = $reasonId;
                        $afterSalesOrderModel->remark = $remark;
                        $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_WATTING_AUDIT;
                        $flag = $afterSalesOrderModel->save(false);
                        if (!$flag)
                            throw new \Exception('保存售后单失败', false);
                        $afterSaleOrderReturn = AfterSalesReturn::findOne_overwrite($after_sales_id);
                        $afterSaleOrderReturn->warehouse_id = $returnWarehouseId;
                        $afterSaleOrderReturn->carrier = $returnCarrier;
                        $afterSaleOrderReturn->tracking_no = $returnTrackingNo;
                        $afterSaleOrderReturn->platform_code = $platform;
                        $afterSaleOrderReturn->order_id = $orderId;
                        $afterSaleOrderReturn->rma = $return_rma; //rma
                        $afterSaleOrderReturn->remark = $return_remark; //退货备注
                        $afterSaleOrderReturn->modify_by = Yii::$app->user->identity->login_name;
                        $afterSaleOrderReturn->modify_time = date('Y-m-d H:i:s');
                        $flag = $afterSaleOrderReturn->save();
                        if (!$flag)
                            throw new \Exception('保存退货数据失败');
                        foreach ($returnItems as $row) {
                            $orderReturnDetail = new OrderReturnDetail();
                            if (!empty($row['id']))
                                $orderReturnDetail = OrderReturnDetail::findOne(['id' => $row['id']]);
                            $orderReturnDetail->after_sale_id = $afterSalesOrderModel->after_sale_id;
                            $orderReturnDetail->sku = $row['sku'];
                            $orderReturnDetail->product_title = $row['productTitle'];
                            $orderReturnDetail->quantity = $row['quantity'];
                            $orderReturnDetail->linelist_cn_name = $row['linelist_cn_name'];
                            $flag = $orderReturnDetail->save();
                            if (!$flag)
                                throw new \Exception('保存退货详情是吧');
                            // 构建需要修改/保留的详情信息
                            $returnProductIdValue[] = $orderReturnDetail->id;
                        }
                        $oldOrderReturnDetails = OrderReturnDetail::find()->where(['after_sale_id' => $after_sales_id])->all();
                        foreach ($oldOrderReturnDetails as $val) {
                            if (!in_array($val->id, $returnProductIdValue)) {
                                $val->delete();
                            }
                        }
                        break;


                    //处理重寄
                    case $type == AfterSalesOrder::ORDER_TYPE_REDIRECT:
                        $skuArr = $this->request->getBodyParam('sku');
                        $redirectProductIdArr = $this->request->getBodyParam('redirect_product_id');
                        $redirectProductIdValue = [];
                        $titleArr = $this->request->getBodyParam('product_title');
                        $quantityArr = $this->request->getBodyParam('quantity');
                        $redirectLinelistCnNameArr = $this->request->getBodyParam('redirect_linelist_cn_name');
                        $returnItemIdArr = $this->request->getBodyParam('item_id');
                        $returnTransactionIdArr = $this->request->getBodyParam('transaction_id');

                        $warehouse_name = $this->request->getBodyParam('warehouse_name');
                        $ship_code_name = $this->request->getBodyParam('ship_code_name');
                        $redirect_order_amount = $this->request->getBodyParam('order_amount');
                        $currency = !empty($this->request->getBodyParam('currency')) ? $this->request->getBodyParam('currency') : '';
                        $paypal_id = !empty($this->request->getBodyParam('paypal_id')) ? $this->request->getBodyParam('paypal_id') : '';
                        $paypal_email = !empty($this->request->getBodyParam('paypal_email')) ? $this->request->getBodyParam('paypal_email') : '';

                        $items = [];
                        $skuList = [];
                        if (empty($skuArr))
                            $this->_showMessage('未选择产品', false);
                        foreach ($skuArr as $key => $sku) {
                            $sku = trim($sku);
                            if (empty($sku))
                                $this->_showMessage('SKU为空', false);
                            if (in_array($sku, $skuList))
                                $this->_showMessage('SKU{' . $sku . '}重复', false);
                            $title = isset($titleArr[$key]) ? trim($titleArr[$key]) : '';
                            if (empty($title))
                                $this->_showMessage('产品标题为空', false);
                            $quantity = isset($quantityArr[$key]) ? (int) $quantityArr[$key] : 0;
                            if ($quantity <= 0)
                                $this->_showMessage('产品数量必须大于0', false);
                            $redirectLinelistCnName = isset($redirectLinelistCnNameArr[$key]) ? $redirectLinelistCnNameArr[$key] : '';
                            $returnItemId = isset($returnItemIdArr[$key]) ? $returnItemIdArr[$key] : '';
                            $returnTransactionId = isset($returnTransactionIdArr[$key]) ? $returnTransactionIdArr[$key] : '';
                            $items[] = [
                                'id' => $redirectProductIdArr[$key],
                                'item_id' => $returnItemId,
                                'transaction_id' => $returnTransactionId,
                                'sku' => $sku,
                                'productTitle' => $title,
                                'quantity' => $quantity,
                                'linelist_cn_name' => $redirectLinelistCnName,
                            ];
                            array_push($skuList, $sku);
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

                        $order_remark = trim($this->request->getBodyParam('order_remark'));
                        $print_remark = trim($this->request->getBodyParam('print_remark'));
                        if (strlen($print_remark) > 200)
                            $this->_showMessage('订单备货字节长度不能超过200', false);

                        $afterSalesOrderModel = AfterSalesOrder::findOne(['after_sale_id' => $after_sales_id]);
                        $afterSalesOrderModel->order_id = $orderId;
                        $afterSalesOrderModel->type = $type;
                        $afterSalesOrderModel->platform_code = $platform;
                        $afterSalesOrderModel->department_id = $departmentId;
                        $afterSalesOrderModel->reason_id = $reasonId;
                        $afterSalesOrderModel->remark = $remark;
                        $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_WATTING_AUDIT;
                        $flag = $afterSalesOrderModel->save(false);
                        if (!$flag)
                            throw new \Exception('保存售后单失败', false);
                        $afterSaleOrderRedirect = AfterSalesRedirect::findOne(['after_sale_id' => $after_sales_id]);
                        $afterSaleOrderRedirect->ship_name = $shipName;
                        $afterSaleOrderRedirect->ship_street1 = $address1;
                        $afterSaleOrderRedirect->ship_street2 = $address2;
                        $afterSaleOrderRedirect->ship_zip = $postCode;
                        $afterSaleOrderRedirect->ship_city_name = $shipCityName;
                        $afterSaleOrderRedirect->ship_stateorprovince = $state;
                        $afterSaleOrderRedirect->ship_country = $shipCountry;
                        //$afterSaleOrderRedirect->ship_country_name = $address2;
                        $afterSaleOrderRedirect->ship_phone = $shipPhone;
                        $afterSaleOrderRedirect->warehouse_id = $warehouseId;
                        $afterSaleOrderRedirect->warehouse_name = $warehouse_name;
                        $afterSaleOrderRedirect->ship_code = $shipCode;
                        $afterSaleOrderRedirect->ship_code_name = $ship_code_name;
                        $afterSaleOrderRedirect->platform_code = $platform;
                        $afterSaleOrderRedirect->order_id = $orderId;
                        $afterSaleOrderRedirect->order_remark = $order_remark;
                        $afterSaleOrderRedirect->print_remark = $print_remark;
                        $afterSaleOrderRedirect->order_amount = $redirect_order_amount;
                        $afterSaleOrderRedirect->currency = $currency;
                        $afterSaleOrderRedirect->paypal_id = $paypal_id;
                        $afterSaleOrderRedirect->paypal_email = $paypal_email;
                        $flag = $afterSaleOrderRedirect->save();
                        if (!$flag)
                            throw new \Exception('保存重寄数据失败');
                        foreach ($items as $row) {
                            $orderReturnDetail = new OrderRedirectDetail();
                            if (!empty($row['id']))
                                $orderReturnDetail = OrderRedirectDetail::findOne(['id' => $row['id']]);

                            $orderReturnDetail->after_sale_id = $afterSalesOrderModel->after_sale_id;
                            $orderReturnDetail->item_id = $row['item_id'];
                            $orderReturnDetail->transaction_id = $row['transaction_id'];
                            $orderReturnDetail->sku = $row['sku'];
                            $orderReturnDetail->product_title = $row['productTitle'];
                            $orderReturnDetail->quantity = $row['quantity'];
                            $orderReturnDetail->linelist_cn_name = $row['linelist_cn_name'];
                            $flag = $orderReturnDetail->save();
                            if (!$flag)
                                throw new \Exception('保存重寄详情失败');
                            $redirectProductIdValue[] = $orderReturnDetail->id;
                        }
                        $oldOrderReturnDetails = OrderRedirectDetail::find()->where(['after_sale_id' => $after_sales_id])->all();
                        foreach ($oldOrderReturnDetails as $val) {
                            if (!in_array($val->id, $redirectProductIdValue)) {
                                $val->delete();
                            }
                        }

                        break;
                }
                //保存问题产品
                if (!empty($issueProducts)) {
                    foreach ($issueProducts as $row) {
                        $afterSalesProductModel = new AfterSalesProduct();
                        if (!empty($row['id']))
                            $afterSalesProductModel = AfterSalesProduct::findOne(['id' => $row['id']]);
                        $afterSalesProductModel->platform_code = $row['platform_code'];
                        $afterSalesProductModel->order_id = $row['order_id'];
                        $afterSalesProductModel->sku = $row['sku'];
                        $afterSalesProductModel->product_title = $row['product_title'];
                        $afterSalesProductModel->quantity = $row['quantity'];
                        $afterSalesProductModel->issue_quantity = $row['issue_quantity'];
                        $afterSalesProductModel->reason_id = $row['reason_id'];
                        $afterSalesProductModel->linelist_cn_name = $row['linelist_cn_name'];
                        $afterSalesProductModel->after_sale_id = $afterSaleOrderModel->after_sale_id;
                        $flag = $afterSalesProductModel->save();
                        if (!$flag)
                            throw new \Exception('保存问题产品失败');
                        $issueProductIdValue[] = $afterSalesProductModel->id;
                    }
                }

                $oldSalesProductDetails = AfterSalesProduct::find()->where(['order_id' => $orderId, 'platform_code' => $platform])->all();
                foreach ($oldSalesProductDetails as $val) {
                    if (!in_array($val->id, $issueProductIdValue)) {
                        $val->delete();
                    }
                }

                $dbTransaction->commit();
                $this->_showMessage('操作成功', true, null, false, null, 'top.refreshTable(location.href);');
            } catch (\Exception $e) {
                $dbTransaction->rollBack();
                var_dump($e->getMessage());
                var_dump($e->getLine());
                $this->_showMessage('操作失败', false);
            }
        }

        $datas = ['orderId' => $orderId, 'platformCode' => $platform];
        $orderinfo = Json::decode(Json::encode($orderinfo), true);
        $countires = Country::getCodeNamePairs();
        $warehouseList = Warehouse::getWarehouseList();
        $warehouseList_new = [];
        foreach ($warehouseList as $key => $value) {
            if (!in_array('请选择发货仓库', $warehouseList))
                $warehouseList_new[' '] = '请选择发货仓库';
            $warehouseList_new[$key] = $value;
        }
        $warehouse_id = isset($afterSaleDetail->warehouse_id) ? $afterSaleDetail->warehouse_id : $orderinfo['info']['warehouse_id'];
        $logistics = Logistic::getWarehouseLogistics($warehouse_id);

        switch ($platform) {
            case Platform::PLATFORM_CODE_ALI:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/aliexpress_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_EB:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/ebay_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_WISH:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/wish_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/amazon_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_WALMART:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/walmart_reason_code.php';
                break;
            default:
                $reasonCodeList = array();
        }
        $refundItems = isset($orderinfo['items']) ? $orderinfo['items'] : [];

        $viewFile = 'edit.php';
        if ($platform == Platform::PLATFORM_CODE_AMAZON) {
            $viewFile = 'amazon_refund_edit.php';
            if (empty($refundItems))
                $refundItems = OrderAmazonItem::getOrderItems($orderinfo['info']['platform_order_id']);
        } elseif ($platform == Platform::PLATFORM_CODE_WALMART) {
            $viewFile = 'walmart_refund_edit.php';
            if (empty($refundItems))
                $refundItems = OrderWalmartItem::getOrderItems($orderinfo['info']['platform_order_id']);
        }

        foreach ($refundItems as &$row) {
            if ($platform == Platform::PLATFORM_CODE_AMAZON) {
                $itemId = $row['item_id'];
                //计算已退金额和可退金额
                $row['refund_item_price_amount'] = 0.00;
                $row['refund_item_shipping_amount'] = 0.00;
                $row['refund_item_tax_amount'] = 0.00;
                $row['refund_shipping_tax_amount'] = 0.00;
                $row['reason_code'] = 'CustomerCancel';
                if (isset($refundDetails[$itemId])) {
                    $row['refund_item_price_amount'] = isset($refundDetails[$itemId]['item_price_amount']) ?
                            floatval($refundDetails[$itemId]['item_price_amount']) : 0.00;
                    $row['refund_item_shipping_amount'] = isset($refundDetails[$itemId]['item_shipping_amount']) ?
                            floatval($refundDetails[$itemId]['item_shipping_amount']) : 0.00;
                    $row['refund_item_tax_amount'] = isset($refundDetails[$itemId]['item_tax_amount']) ?
                            floatval($refundDetails[$itemId]['item_tax_amount']) : 0.00;
                    $row['refund_shipping_tax_amount'] = isset($refundDetails[$itemId]['shipping_tax_amount']) ?
                            floatval($refundDetails[$itemId]['shipping_tax_amount']) : 0.00;
                    $row['reason_code'] = isset($refundDetails[$itemId]['reason_code']) ?
                            $refundDetails[$itemId]['reason_code'] : 'CustomerCancel';
                }
            } elseif ($platform == Platform::PLATFORM_CODE_WALMART) {
                $itemId = $row['line_number'];
                //计算已退金额和可退金额
                $row['refund_product_price'] = 0.00;
                $row['refund_shipping_price'] = 0.00;
                $row['reason_code'] = 'Customer Changed Mind';
                if (isset($refundDetails[$itemId])) {
                    $row['refund_product_price'] = isset($refundDetails[$itemId]['product_price']) ?
                            floatval($refundDetails[$itemId]['product_price']) : 0.00;
                    $row['refund_shipping_price'] = isset($refundDetails[$itemId]['shipping_price']) ?
                            floatval($refundDetails[$itemId]['shipping_price']) : 0.00;
                    $row['reason_code'] = isset($refundDetails[$itemId]['reason_code']) ?
                            $refundDetails[$itemId]['reason_code'] : 'Customer Changed Mind';
                }
            }
        }
        //判断当前订单是否有退款单
        $refundOrderInfo = AfterSalesOrder::isSetAfterSalesOrder($orderId, 1);
        //判断当前订单是否有重寄单
        $redirectOrderInfo = AfterSalesOrder::isSetAfterSalesOrder($orderId, 3);
        //判断当前订单是否有退货单
        $returnOrderInfo = AfterSalesOrder::isSetAfterSalesOrder($orderId, 2);

        // 重寄选择币种
        $currencys = array('USD', 'AUD', 'CAD', 'EUR', 'GBP', 'HKD');

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }

        $departmentList = BasicConfig::getParentList(52);
        if ($afterSaleOrderModel->department_id) {
            $reasonList = $afterSaleOrderModel->department_id ? BasicConfig::getParentList($afterSaleOrderModel->department_id) : [];
        } else {
//            $reasonList = RefundReturnReason::getList('Array');
            $reasonList = [];
        }

        $departmentList_new = [];
        foreach ($departmentList as $k => &$v) {
            $departmentList_new[$k]['depart_id'] = $k;
            $departmentList_new[$k]['depart_name'] = $v;
        }
        //paypal账号信息
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }

        if (!empty($afterSaleDetail->refund_detail)) {
            $refund_detail = !empty(json_decode($afterSaleDetail->refund_detail, true)['returnDetail']) ? json_decode($afterSaleDetail->refund_detail, true)['returnDetail'] : [];
            $refund_comments = !empty(json_decode($afterSaleDetail->refund_detail, true)['refundComments']) ? json_decode($afterSaleDetail->refund_detail, true)['refundComments'] : '';
        } else {
            $refund_detail = [];
            $refund_comments = '';
        }

        $logistics_arr = [];
        if (!empty($logistics)) {
            foreach ($logistics as $key => $value) {
                $logistics_arr[$value->ship_code] = $value->ship_name;
            }
        }


        return $this->render($viewFile, [
                    'type' => $type,
                    'info' => $orderinfo,
                    'afterSaleOrderModel' => $afterSaleOrderModel,
                    'afterSaleDetail' => $afterSaleDetail,
                    'countries' => $countires,
                    'warehouseList' => $warehouseList_new,
                    'logistics' => $logistics,
                    'departmentList' => json_encode($departmentList_new),
                    'reasonList' => $reasonList,
                    'reasonCodeList' => $reasonCodeList,
                    'allow_refund_amount' => $allow_refund_amount,
                    'refundDetails' => $refundDetails,
//            'orderAmount' => isset($orderinfo['info']['total_price']) ? $orderinfo['info']['total_price'] : 0.00,
                    'currencyCode' => isset($orderinfo['info']['currency']) ? $orderinfo['info']['currency'] : '',
                    'refundItems' => $refundItems,
                    'refundOrderInfo' => $refundOrderInfo,
                    'redirectOrderInfo' => $redirectOrderInfo,
                    'returnOrderInfo' => $returnOrderInfo,
                    'currencys' => $currencys,
                    'isAuthority' => $isAuthority,
                    'paypallist' => $palPalList,
                    'refund_detail' => $refund_detail,
                    'refund_comments' => $refund_comments,
                    'logistics_arr' => $logistics_arr
        ]);
    }

    /**
     * 修改售后原因
     * @return string
     * @throws Exception
     */
    public function actionEditdepart() {
        $this->isPopup = true;
        $after_sales_id = $this->request->getQueryParam('after_sales_id');
        $afterSaleOrderModel = AfterSalesOrder::findOne($after_sales_id);
        if (empty($afterSaleOrderModel)) {
            $this->_showMessage('找不到对应售后单', false, null, false, null, 'layer.closeAll()');
        }

        if ($this->request->getIsPost()) {
            $dbTransaction = AfterSalesOrder::getDb()->beginTransaction();
            try {
                $departmentId = $this->request->getBodyParam('department_id');
                $reasonId = $this->request->getBodyParam('reason_id');
                $remark = $this->request->getBodyParam('remark');
                $after_sale_order_id = $this->request->getBodyParam('after_sale_order_id');
                if (empty($departmentId)) {
                    $this->_showMessage('请选择责任归属部门', false);
                }
                if (empty($reasonId))
                    $this->_showMessage('请选择原因类型', false);
                $afterSalesOrderModel = AfterSalesOrder::findOne(['after_sale_id' => $after_sale_order_id]);
                $check = AfterSalesOrder::changeReasonCheckDate($after_sale_order_id, $afterSalesOrderModel);
                if (!$check) {
                    $this->_showMessage('售后单不能跨月操作!', false);
                }


                $afterSalesOrderModel->department_id = $departmentId;
                $afterSalesOrderModel->reason_id = $reasonId;
                $afterSalesOrderModel->remark = $remark;
                $flag = $afterSalesOrderModel->save(false);
                $dbTransaction->commit();
                $this->_showMessage('操作成功', true, null, true, null, 'top.refreshTable(location.href);');
            } catch (\Exception $e) {
                $dbTransaction->rollBack();
                var_dump($e->getMessage());
                var_dump($e->getLine());
                $this->_showMessage('操作失败', false);
            }
        }
        $departmentList = BasicConfig::getParentList(52);
        if ($afterSaleOrderModel->department_id) {
            $reasonList = $afterSaleOrderModel->department_id ? BasicConfig::getParentList($afterSaleOrderModel->department_id) : [];
        } else {
            $reasonList = [];
        }
        $departmentList_new = [];
        foreach ($departmentList as $k => &$v) {
            $departmentList_new[$k]['depart_id'] = $k;
            $departmentList_new[$k]['depart_name'] = $v;
        }
        return $this->render('editdepart', [
                    'afterSaleOrderModel' => $afterSaleOrderModel,
                    'departmentList' => json_encode($departmentList_new),
                    'reasonList' => $reasonList,
        ]);
    }

    /**
     * @desc 登记售后退款单
     * @return \yii\base\string
     */
    public function actionRegister() {
        set_time_limit(0);
        $this->isPopup = true;
        $orderId = $this->request->getQueryParam('order_id');
        $platform = $this->request->getQueryParam('platform');
        $returngoodsid = $this->request->getBodyParam('id');
        //订单信息
        $orderinfo = [];
        if (empty($platform)) {
            $this->_showMessage('平台CODE无效', false, null, false, null, 'layer.closeAll()');
        }
        if (empty($orderId)) {
            $this->_showMessage('订单号无效', false, null, false, null, 'layer.closeAll()');
        }
        //$orderinfo = Order::getOrderStackByOrderId($platform, '', $orderId);

        $orderinfo = OrderKefu::getOrderStackByOrderId($platform, '', $orderId);
        if (empty($orderinfo)) {
            $this->_showMessage('找不到对应订单', false, null, false, null, 'layer.closeAll()');
        }
        //查找订单对应的买家账号
        $accountname=Account::find()->select('account_name')->where(['platform_code'=>$platform])->andWhere(['old_account_id'=>$orderinfo->info->account_id])->asArray()->one();
        $order_amount = isset($orderinfo->info) && isset($orderinfo->info->total_price) ? $orderinfo->info->total_price : 0.00;
        $allow_refund_amount = AfterSalesRefund::getAllowRefundAmount($orderId, $order_amount, $platform);

        if ($this->request->getIsPost()) {
            //问题产品
            $orderProducts = [];
            if (isset($orderinfo->product) && !empty($orderinfo->product)) {
                foreach ($orderinfo->product as $row) {
                    $orderProducts[trim($row->sku)] = $row;
                }
            }
            $dbTransaction = AfterSalesOrder::getDb()->beginTransaction();

            $account_name = $this->request->getBodyParam('account_name');
            $account_info = Account::findAccountOne($account_name, $platform);
            $buyer_id = $this->request->getBodyParam('buyer_id');

            try {
                $issueProductArr = $this->request->getBodyParam('issue_product');
                $departmentId = $this->request->getBodyParam('department_id');
                $reasonId = $this->request->getBodyParam('reason_id');
                $remark = $this->request->getBodyParam('remark');
                $type = $this->request->getBodyParam('after_sales_type');
                if (empty($departmentId)) {
                    $this->_showMessage('请选择责任归属部门', false);
                }
                if (empty($reasonId)) {
                    $this->_showMessage('请选择原因类型', false);
                }

                //如果部门是供应商(55)并且原因是12 产品质量问题(74) 则备注必填
                if ($departmentId == 55 && $reasonId == 74 && empty($remark)) {
                    $this->_showMessage('当前原因条件下备注必填', false);
                }


                $issueProducts = [];
                if (!empty($issueProductArr)) {
                    $isSetNum = FALSE;
                    foreach ($issueProductArr as $sku => $quantity) {
                        $sku = trim($sku);
                        $quantity = (int) $quantity;
                        if ($quantity) {
                            $isSetNum = TRUE;
                        }
                        if (empty($sku)) {
                            throw new \Exception('无效的SKU', false);
                        }
                        if (!isset($orderProducts[$sku])) {
                            throw new \Exception($sku . 'SKU不存在于订单中', false);
                        }
                        if ($quantity <= 0) {
                            continue;
                        }

                        $issueProducts[] = [
                            'platform_code' => $platform,
                            'order_id' => $orderId,
                            'sku' => $sku,
                            'product_title' => isset($orderProducts[$sku]->picking_name) ? $orderProducts[$sku]->picking_name : $orderProducts[$sku]->title,
                            'quantity' => $orderProducts[$sku]->quantity,
                            'linelist_cn_name' => $orderProducts[$sku]->linelist_cn_name,
                            'issue_quantity' => $quantity,
                            'reason_id' => $reasonId,
                        ];
                    }

                    if (!$isSetNum) {
                        $this->_showMessage('问题产品数量必填', false);
                    }
                }
                if (!in_array($type, [
                            AfterSalesOrder::ORDER_TYPE_REFUND,
                            AfterSalesOrder::ORDER_TYPE_RETURN,
                            AfterSalesOrder::ORDER_TYPE_REDIRECT,
                        ])) {
                    $this->_showMessage('无效的售后类型');
                }
                //处理退款
                if ($type != AfterSalesOrder::ORDER_TYPE_REFUND) {
                    $this->_showMessage('登记售后单类型错误');
                }
                $refundAmount = floatval($this->request->getBodyParam('refund_amount'));
                $currencyCode = $orderinfo->info->currency;
                $message = trim($this->request->getBodyParam('message'));
                if ($refundAmount <= 0.00) {
                    $this->_showMessage('退款金额不能小于0', false);
                }
//                if ($refundAmount > $allow_refund_amount)
//                    $this->_showMessage('退款金额不能大于可退款金额', false);
                $afterSalesOrderModel = new AfterSalesOrder();
                $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                $afterSalesOrderModel->order_id = $orderId;
                $afterSalesOrderModel->type = $type;
                $afterSalesOrderModel->platform_code = $platform;
                $afterSalesOrderModel->department_id = $departmentId;
                $afterSalesOrderModel->reason_id = $reasonId;
                $afterSalesOrderModel->remark = $remark;
                $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                $afterSalesOrderModel->account_name = $account_name;
                $afterSalesOrderModel->account_id = !empty($account_info) ? $account_info->id : '';
                $afterSalesOrderModel->buyer_id = $buyer_id;
                $afterSalesOrderModel->approver = Yii::$app->user->identity->login_name;
                $afterSalesOrderModel->approve_time = date('Y-m-d H:i:s');
                $afterSalesOrderModel->create_by = Yii::$app->user->identity->login_name;
                $afterSalesOrderModel->create_time = date('Y-m-d H:i:s');

                $flag = $afterSalesOrderModel->save(false);
                                        //平台订单模型
                        $orderQuery = null;

                        switch ($platform) {
                            case Platform::PLATFORM_CODE_EB:
                                $orderQuery = OrderEbayKefu::find();
                                break;
                            case Platform::PLATFORM_CODE_ALI:
                                $orderQuery = OrderAliexpressKefu::find();
                                break;
                            case Platform::PLATFORM_CODE_AMAZON:
                                $orderQuery = OrderAmazonKefu::find();
                                break;
                            case Platform::PLATFORM_CODE_WISH:
                                $orderQuery = OrderWishKefu::find();
                                break;
                            case Platform::PLATFORM_CODE_CDISCOUNT:
                            case Platform::PLATFORM_CODE_SHOPEE:
                            case Platform::PLATFORM_CODE_LAZADA:
                            case Platform::PLATFORM_CODE_WALMART:
                            case Platform::PLATFORM_CODE_OFFLINE:
                            case Platform::PLATFORM_CODE_MALL:
                            case Platform::PLATFORM_CODE_JOOM:
                            case Platform::PLATFORM_CODE_PF:
                            case Platform::PLATFORM_CODE_BB:
                            case Platform::PLATFORM_CODE_DDP:
                            case Platform::PLATFORM_CODE_STR:
                            case Platform::PLATFORM_CODE_JUM:
                            case Platform::PLATFORM_CODE_JET:
                            case Platform::PLATFORM_CODE_GRO:
                            case Platform::PLATFORM_CODE_DIS:
                            case Platform::PLATFORM_CODE_SPH:
                            case Platform::PLATFORM_CODE_INW:
                            case Platform::PLATFORM_CODE_JOL:
                            case Platform::PLATFORM_CODE_SOU:
                            case Platform::PLATFORM_CODE_PM:
                            case Platform::PLATFORM_CODE_WADI:
                            case Platform::PLATFORM_CODE_OBERLO:
                            case Platform::PLATFORM_CODE_WJFX:
                            case Platform::PLATFORM_CODE_ALIXX:
                                $orderQuery = OrderOtherKefu::find();
                                break;
                        }
                        //查询erp订单
                        $orderInfo = $orderQuery
                                ->andWhere(['platform_code' => $platform])
                                ->andWhere(['order_id' => $orderId])
                                ->asArray()
                                ->one();
                        if (!empty($orderInfo)) {
                            //更新平台退款订单is_aftersale is_match_rule状态
                              PlatformRefundOrder::updateAll(['is_aftersale'=>1,'is_match_rule'=>1], ['platform_code'=>$platform,'platform_order_id'=>$orderInfo['platform_order_id']]);
//                            $rlatformrefundorder = PlatformRefundOrder::find()->where(['platform_code' => $platform])->andWhere(['platform_order_id' => $orderInfo['platform_order_id']])->one();
//                            $rlatformrefundorder->is_aftersale = 1;
//                            $rlatformrefundorder->is_match_rule = 1;
//                            $rlatformrefundorder->save();
                        }
                
                
                  
                
                
                if (!$flag) {
                    $this->_showMessage('保存售后单失败', false);
                }
                $afterSaleOrderRefund = new AfterSalesRefund();
                $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
                if ($refundAmount < $order_amount) {
                    $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_PARTIAL;
                }
                $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                $afterSaleOrderRefund->refund_amount = $refundAmount;
                $afterSaleOrderRefund->currency = $currencyCode;
                $afterSaleOrderRefund->reason_code = $this->request->getBodyParam('reason_code');
                $afterSaleOrderRefund->message = $message;
                $afterSaleOrderRefund->platform_code = $platform;
                $afterSaleOrderRefund->order_id = $orderId;
                $afterSaleOrderRefund->order_amount = $orderinfo->info->total_price;
                $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                $afterSaleOrderRefund->refund_time = date('Y-m-d H:i:s'); //添加退款时间
                $flag = $afterSaleOrderRefund->save();
                if (!$flag) {
                    $this->_showMessage('保存退款数据失败', false);
                }
                $t_after_id = $afterSalesOrderModel->after_sale_id;
                $after_id = isset($t_after_id) ? $t_after_id : '';

                //保存问题产品
                if (!empty($issueProducts)) {
                    foreach ($issueProducts as $row) {
                        $afterSalesProductModel = new AfterSalesProduct();
                        $data = AfterSalesOrder::getRefundRedirectData($row['platform_code'], $row['order_id'], $row['sku'], $refundAmount, $currencyCode, 1);
                        $afterSalesProductModel->refund_redirect_price = $data['sku_refund_amt'];
                        $afterSalesProductModel->refund_redirect_price_rmb = $data['sku_refund_amt_rmb'];
                        $afterSalesProductModel->platform_code = $row['platform_code'];
                        $afterSalesProductModel->order_id = $row['order_id'];
                        $afterSalesProductModel->sku = $row['sku'];
                        $afterSalesProductModel->product_title = $row['product_title'];
                        $afterSalesProductModel->quantity = $row['quantity'];
                        $afterSalesProductModel->issue_quantity = $row['issue_quantity'];
                        $afterSalesProductModel->reason_id = $row['reason_id'];
                        $afterSalesProductModel->linelist_cn_name = $row['linelist_cn_name'];
                        $afterSalesProductModel->after_sale_id = $after_id;
                        $flag = $afterSalesProductModel->save();
                        
                        if (!$flag) {
                            $this->_showMessage('保存问题产品失败', false);
                        }
                    }
                }
                //更新退件列表的操作记录
                $article = Domesticreturngoods::findOne(['order_id' => $orderId]);
                $datetime = date('Y-m-d H:i:s');
                if ($article !== null) {

                    $article->state = 3;
                    $article->handle_type = 3;
                    $article->handle_user = Yii::$app->user->identity->login_name;
                    $article->handle_time = $datetime;
                    $article->record = '退件单号:' . $afterSalesOrderModel->after_sale_id;
                    if (!$article->save())
                        throw new \Exception('更新退件表失败');
                }
                if ($flag) {
                    $erp_refund_model = new AfterSalesRefund();
                    $flag = $erp_refund_model->audit($afterSalesOrderModel, 2);
                }
                if ($flag) {
                    $dbTransaction->commit();
                    $this->_showMessage('操作成功', true, null, false, null, 'top.window.location.reload();', true, msg);
                } else {
                    //这里失败，回滚
                    $dbTransaction->rollBack();
                    $this->_showMessage('操作失败，接口。' . $erp_refund_model->error_message, false);
                }
            } catch (\Exception $e) {
                $dbTransaction->rollBack();
                $this->_showMessage('操作失败，异常。' . $e->getMessage(), false);
            }
        }
        $datas = ['orderId' => $orderId, 'platformCode' => $platform];
        $orderinfo = Json::decode(Json::encode($orderinfo), true);
        $countires = Country::getCodeNamePairs();
        $warehouseList = Warehouse::getWarehouseList();
        $warehouseList_new = [];
        $warehouseList_new[' '] = '请选择发货仓库';
        foreach ($warehouseList as $key => $value) {
            $warehouseList_new[$key] = $value;
        }
        $logistics = Logistic::getWarehouseLogistics($orderinfo['info']['warehouse_id']);
        $reasonList = RefundReturnReason::getList();

        switch ($platform) {
            case Platform::PLATFORM_CODE_ALI:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/aliexpress_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_EB:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/ebay_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_WISH:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/wish_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/amazon_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_SHOPEE:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/shopee_reason_code.php';
                break;
            default:
                $reasonCodeList = array();
        }

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }

        $departmentList = BasicConfig::getParentList(52);
        $departmentList_new = [];
        foreach ($departmentList as $k => &$v) {
            $departmentList_new[$k]['depart_id'] = $k;
            $departmentList_new[$k]['depart_name'] = $v;
        }
        return $this->render('register', [
                    'info' => $orderinfo,
                    'countries' => $countires,
                    'platform' => $platform,
                    'warehouseList' => $warehouseList_new,
                    'logistics' => $logistics,
                    'departmentList' => json_encode($departmentList_new),
                    'reasonList' => $reasonList,
                    'reasonCodeList' => $reasonCodeList,
                    'allow_refund_amount' => $allow_refund_amount,
                    //'orderAmount' => isset($orderinfo['info']['total_price']) ? $orderinfo['info']['total_price'] : 0.00,
                    'currencyCode' => isset($orderinfo['info']['currency']) ? $orderinfo['info']['currency'] : '',
                    'isAuthority' => $isAuthority,
                    'accountName'=>$accountname['account_name']
        ]);
    }

    /**
     * @author
     * @desc 删除售后单
     * @throws Exception
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete() {
        $after_sales_id = $this->request->getQueryParam('after_sales_id');
        $type = $this->request->getQueryParam('type');

        $afterSaleOrderModel = AfterSalesOrder::findOne(['after_sale_id' => $after_sales_id]);
        if (!in_array($afterSaleOrderModel->platform_code, ['EB', 'INW'])) {
            if ($afterSaleOrderModel->status == AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED) {
                $this->_showMessage('审核通过，无法修改', false);
            }

            if ($afterSaleOrderModel->status != AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED) {
                $this->_showMessage('未经退回，无法修改', false);
            }
        }

        $after_sale_id = $afterSaleOrderModel->after_sale_id;

        $dbTransaction = AfterSalesOrder::getDb()->beginTransaction();

        switch ($type) {
            case AfterSalesOrder::ORDER_TYPE_REFUND:
                if (!AfterSalesRefund::deleteAll("after_sale_id='" . $after_sale_id . "'")) {
                    $this->_showMessage('售后单详情删除失败');
                }
                if (!$afterSaleOrderModel->delete()) {
                    $this->_showMessage('售后单删除失败', false);
                }
                break;
            //退件不能删除 只能取消
//            case AfterSalesOrder::ORDER_TYPE_RETURN:
//                if (!AfterSalesReturn::deleteAll("after_sale_id='" . $after_sale_id . "'")) {
//                    $this->_showMessage('售后单详情删除失败', false);
//                }
//                // 删除退货产品
//                $ProductModel = OrderReturnDetail::findOne(['after_sale_id' => $after_sales_id]);
//                if (!empty($ProductModel)) {
//                    if (!OrderReturnDetail::deleteAll("after_sale_id='" . $after_sales_id . "'")) {
//                        $this->_showMessage('退货售后单产品删除失败', false);
//                    }
//
//                }
//                if (!$afterSaleOrderModel->delete()) {
//                    $this->_showMessage('售后单删除失败', false);
//                }
//                
//                break;

            case AfterSalesOrder::ORDER_TYPE_REDIRECT:
                if (!AfterSalesRedirect::deleteAll("after_sale_id='" . $after_sale_id . "'")) {
                    $this->_showMessage('售后单详情删除失败', false);
                }
                // 删除退货产品
                $ProductModel = OrderRedirectDetail::findOne(['after_sale_id' => $after_sales_id]);
                if (!empty($ProductModel)) {
                    if (!OrderRedirectDetail::deleteAll("after_sale_id='" . $after_sales_id . "'")) {
                        $this->_showMessage('重寄售后单售后单产品删除失败', false);
                    }
                }
                if (!$afterSaleOrderModel->delete()) {
                    $this->_showMessage('售后单删除失败', false);
                }
                break;
        }

        // 删除问题产品
        $ProductModel = AfterSalesProduct::findOne(['after_sale_id' => $after_sales_id]);
        if (!empty($ProductModel)) {
            if (!AfterSalesProduct::deleteAll("after_sale_id = '" . $after_sales_id . "'"))
                $this->_showMessage('删除订单问题产品失败', false);
        }

        $dbTransaction->commit();
        $this->_showMessage('操作成功', true, null, false, null, 'top.refreshTable(location.href);');
    }

    /**
     * 将退款失败状态修改为退款成功
     * @return mixed
     */
    public function actionChangestatus() {
        $after_sale_id = $this->request->getQueryParam('after_sale_id');

        $refundModel = AfterSalesRefund::findOne(['after_sale_id' => $after_sale_id]);

        if (empty($refundModel))
            $this->_showMessage('该售后单不存在', false);

        if (!in_array($refundModel->refund_status, array(AfterSalesRefund::REFUND_STATUS_WAIT, AfterSalesRefund::REFUND_STATUS_FAIL, AfterSalesRefund::REFUND_STATUS_ING))) {
            $this->_showMessage('还未退款，不能修改！', false);
        }

        //修改退款状态与时间
        $refundModel->refund_status = 3;
        $refundModel->refund_time = date('Y-m-d H:i:s', time());
        $refundModel->fail_reason = '';
        $refundModel->remark = Yii::$app->user->identity->user_name.' 在 ['.date('Y-m-d H:i:s').']手动更新退款状态为退款成功!';
        if ($refundModel->save()) {
            $this->_showMessage('操作成功', true, null, false, null, 'top.refreshTable(location.href);');
        } else {
            $this->_showMessage('操作失败', false);
        }
    }

    /**
     * 售后列表下载退款/退货数据
     * @author allen <2018-1-8>
     */
    public function actionDownload() {
        set_time_limit(0);
        error_reporting(E_ERROR);
        $request = Yii::$app->request->get();
        $platformCode = isset($request['platformCode']) ? $request['platformCode'] : ""; //平台
        $afterSaleId = isset($request['afterSaleId']) ? $request['afterSaleId'] : ""; //售后订单号
        $time_type = isset($request['time_type']) ? intval($request['time_type']) : ""; //时间类型
        $orderId = isset($request['orderId']) ? $request['orderId'] : ""; //系统订单号
        $buyerId = isset($request['buyerId']) ? $request['buyerId'] : ""; //客户ID
        $departmentId = isset($request['departmentId']) ? $request['departmentId'] : ""; //售后责任归属部门
        $reasonId = isset($request['reasonId']) ? $request['reasonId'] : ""; //售后原因ID
        $statusText = isset($request['statusText']) ? $request['statusText'] : ""; //售后单审核状态
        $refundStatus = isset($request['refundStatus']) ? $request['refundStatus'] : ""; //售后单退款状态
        $createBy = isset($request['createBy']) ? $request['createBy'] : ""; //售后单创建人
        $startTime = isset($request['startTime']) ? $request['startTime'] : ""; //创建时间(搜索开始时间)
        $endTime = isset($request['endTime']) ? $request['endTime'] : ""; //创建时间(搜索结束时间)
        $type = isset($request['type']) ? intval($request['type']) : ""; //售后类型
        $json = isset($request['json']) ? $request['json'] : []; //选中的行数据
        $sku = isset($request['sku']) ? trim($request['sku']) : '';
        $rma = isset($request['rma']) ? trim($request['rma']) : '';
        $return_status = isset($request['return_status']) ? trim($request['return_status']) : '';
        $tracking_no = isset($request['tracking_no']) ? trim($request['tracking_no']) : '';
        $where = "1";
        $andWhere = [];
        $aFterOId = [];
        if (!empty($json)) {
            //获取选中的售后订单号
            $aFterOId = explode(',', $json);
        } else {
            if ($afterSaleId) {
                $where = ['t.after_sale_id' => $afterSaleId];
            } else {
                if ($platformCode && $platformCode != "undefined") {
                    $andWhere[] = ['t.platform_code' => $platformCode];
                } else {
                    if ($request['platform_code'] != "all") {
                        $andWhere[] = ['t.platform_code' => $request['platform_code']];
                    }
                }
                if ($departmentId) {
                    $andWhere[] = ['t.department_id' => $departmentId];
                }
                if ($orderId) {
                    $andWhere[] = ['t.order_id' => $orderId];
                }
                if ($buyerId) {
                    $andWhere[] = ['buyer_id' => $buyerId];
                }
                if ($reasonId) {
                    $andWhere[] = ['reason_id' => $reasonId];
                }
                if ($statusText) {
                    $andWhere[] = ['t.status' => $statusText];
                }

                if ($createBy) {
                    $andWhere[] = ['t.create_by' => $createBy];
                }

                if ($startTime && $endTime) {
                    $andWhere[] = ['between', 't.create_time', $startTime, $endTime];
                } elseif ($startTime) {
                    $andWhere[] = ['>=', 't.create_time', $startTime];
                } elseif ($endTime) {
                    $andWhere[] = ['<=', 't.t.create_time', $endTime];
                }

                if ($sku && $platformCode) {
                    $orderIds = OrderKefu::getOrderIdsBySku($platformCode, $sku);
                    $andWhere[] = ['in', 't.order_id', $orderIds];
                }
            }
        }
        //1:退款 2:退货 3:重寄
        switch ($type) {
            case 1:
                if ($refundStatus) {
                    $andWhere[] = ['t1.refund_status' => $refundStatus];
                }
                if ($time_type == 3) {
                    if ($startTime && $endTime) {
                        $andWhere[] = ['between', 't1.refund_time', $startTime, $endTime];
                    } elseif ($startTime) {
                        $andWhere[] = ['>=', 't1.refund_time', $startTime];
                    } elseif ($endTime) {
                        $andWhere[] = ['<=', 't1.refund_time', $endTime];
                    }
                }
                if ($time_type == 2) {
                    if ($startTime && $endTime) {
                        $andWhere[] = ['between', 't.approve_time', $startTime, $endTime];
                        $andWhere[] = ['t.status' => '2'];
                    } elseif ($startTime) {
                        $andWhere[] = ['>=', 't.approve_time', $startTime];
                        $andWhere[] = ['t.status' => '2'];
                    } elseif ($endTime) {
                        $andWhere[] = ['<=', 't.approve_time', $endTime];
                        $andWhere[] = ['t.status' => '2'];
                    }
                }
                if ($time_type == 1) {
                    if ($startTime && $endTime) {
                        $andWhere[] = ['between', 't.create_time', $startTime, $endTime];
                    } elseif ($startTime) {
                        $andWhere[] = ['>=', 't.create_time', $startTime];
                    } elseif ($endTime) {
                        $andWhere[] = ['<=', 't.t.create_time', $endTime];
                    }
                }

                $fileName = 'refund_' . date('Y-m-d');
                $andWhere[] = ['type' => 1];
                $model = AfterSalesOrder::getReFundData($aFterOId, $where, $andWhere);
                $mergeArr = [];
                $curRow = 1;
                $mergeCol = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
                    'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AM', 'AN', 'AO'];
                $data = [];
                foreach ($model as $k => $v) {
                    $curRow++;
                    $v['type'] = AfterSalesOrder::getOrderTypeList(1);
                    $v['refund_amt'] = abs($v['refund_amt']);
                    $v['refund_amt_rmb'] = abs($v['refund_amt_rmb']);
                    if (!$v['refund_currency']) {
                        $v['refund_currency'] = isset($v['currency']) ? $v['currency'] : "";
                    }
                    $v['refund_status'] = $this->refund_status_map[$v['refund_status']];
                    if ($v['order_type']) {
                        $v['order_type'] = strip_tags(VHelper::getOrderType($v['order_type']));
                    }
                    $v['sku'] = explode(',', $v['sku']);
                    $v['pro_name'] = explode(',', $v['pro_name']);
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
                            $tmp = clone $v;
                            $value = explode('*', $vv);
                            if (count($value) > 1) {
                                $tmp['sku'] = $value[0];
                                $tmp['quantity'] = $value[1];
                                $tmp['sku_total_price'] = $value[2];
                            } else {
                                $tmp['sku'] = $vv;
                            }
                            $tmp['pro_name'] = $v['pro_name'][$kk];


                            $data[] = $tmp;
                        }
                    } else {

                        foreach ($v['sku'] as $kk => $vv) {
                            $value = explode('*', $vv);
                            if (count($value) > 1) {
                                $v['sku'] = $value[0];
                                $v['quantity'] = $value[1];
                                $v['sku_total_price'] = $value[2];
                            }
                            $v['pro_name'] = $v['pro_name'][$kk];
                            $data[] = $v;
                        }
                    }
                }
                foreach ($data as &$v2) {
                    //单个sku产品线
                    $v2['line_cn_name'] = Product::getLineListNameBySku($v2['sku']);
                    //计算SKU的退款金额
                    //SKU总计金额/订单总费用/SKU购买数量*退款金额*SKU退款数量
                    $target_amt = ($v2['sku_total_price'] / $v2['total_price'] / $v2['quantity']) * $v2['refund_amt'] * $v2['quantity'];
                    $v2['sku_refund_amt'] = sprintf("%.2f", $target_amt);
                    $rateMonth = date('Ym');
                    $rmbReturn = VHelper::getTargetCurrencyAmtAll($rateMonth);
                    $target_currency_amt = $target_amt * $rmbReturn[$v2['currency']];
                    $rmb = sprintf("%.2f", $target_currency_amt);
                    //rmb
                    $v2['sku_refund_amt_rmb'] = $rmb ? $rmb : '';


                    $v2['is_fbc'] = '';
                    if ($v2['platform_code'] == "CDISCOUNT") {
                        if ($v2['warehouse_name'] == "FBC法国仓") {
                            $v2['is_fbc'] = 'FBC订单';
                        } else {
                            $v2['is_fbc'] = '否';
                        }
                    }
                }

                $model = $data;
                $headers = ['platform_code' => '平台',
                    'account_name' => '帐号',
                    'after_sale_id' => '售后单号',
                    'order_type' => '订单类型',
                    'department_id' => '责任归属部门',
                    'order_id' => '系统订单号',
                    'platform_order_id' => '平台订单号',
                    'orientation_order_id' => '定位订单号',
                    'paytime' => '付款时间',
                    'buyer_id' => '客户id',
                    'ship_country_name' => '收件人国家',
                    'order_status' => '平台订单状态',
                    'complete_status' => '系统订单状态',
                    'shipped_date' => '发货时间',
                    'warehouse_name' => '发货仓库',
                    'ship_name' => '邮寄方式',
                    'receiver_email' => '收款PayPal帐号',
                    'transaction_id' => '收款PayPal交易号',
                    'currency' => '收款币种',
                    'total_price' => '收款金额',
                    'payer_email' => '付款PayPal帐号',
                    'refund_transaction_id' => '退款PayPal交易号',
                    'refund_currency' => '退款币种',
                    'refund_amt' => '退款金额',
                    'refund_amt_rmb' => '退款金额(RMB)',
                    'sku_refund_amt' => 'sku退款金额',
                    'sku_refund_amt_rmb' => 'sku退款金额(RMB)',
                    'line_cn_name' => '产品线',
                    'sku' => 'sku',
                    'quantity' => '数量',
                    'pro_name' => '产品名',
                    'sum_quantity' => '产品总数量',
                    'reason_id' => '原因',
                    'reason' => '平台退款原因',
                    'create_by' => '提交人',
                    'create_time' => '创建时间',
                    'approve_time' => '审核时间',
                    'refund_time' => '退款时间',
                    'remark' => '备注',
                    'status' => '审核状态',
                    'refund_status' => '退款状态',
                    'amazon_fulfill_channel' => 'amazon订单类型',
                    'is_fbc' => '是否为FBC订单'
                ];
                $columns = ['platform_code',
                    'account_name',
                    'after_sale_id',
                    'order_type',
                    'department_id',
                    'order_id',
                    'platform_order_id',
                    'orientation_order_id',
                    'paytime',
                    'buyer_id',
                    'ship_country_name',
                    'order_status',
                    'complete_status',
                    'shipped_date',
                    'warehouse_name',
                    'ship_name',
                    'receiver_email',
                    'transaction_id',
                    'currency',
                    'total_price',
                    'payer_email',
                    'refund_transaction_id',
                    'refund_currency',
                    'refund_amt',
                    'refund_amt_rmb',
                    'sku_refund_amt',
                    'sku_refund_amt_rmb',
                    'line_cn_name',
                    'sku',
                    'quantity',
                    'pro_name',
                    'sum_quantity',
                    'reason_id',
                    'reason',
                    'create_by',
                    'create_time',
                    'approve_time',
                    'refund_time',
                    'remark',
                    'status',
                    'refund_status',
                    'amazon_fulfill_channel',
                    'is_fbc',
                ];
                break;
            case 2:
                if ($return_status) {
                    $andWhere[] = ['t1.return_status' => $return_status];
                }
                if ($rma) {
                    $andWhere[] = ['t1.rma' => $rma];
                }
                if ($tracking_no) {
                    $andWhere[] = ['t1.tracking_no' => $tracking_no];
                }
                if ($time_type == 4) {
                    if ($startTime && $endTime) {
                        $andWhere[] = ['between', 't1.return_time', $startTime, $endTime];
                    } elseif ($startTime) {
                        $andWhere[] = ['>=', 't1.return_time', $startTime];
                    } elseif ($endTime) {
                        $andWhere[] = ['<=', 't1.return_time', $endTime];
                    }
                }
                if ($time_type == 2) {
                    if ($startTime && $endTime) {
                        $andWhere[] = ['between', 't.approve_time', $startTime, $endTime];
                        $andWhere[] = ['t.status' => '2'];
                    } elseif ($startTime) {
                        $andWhere[] = ['>=', 't.approve_time', $startTime];
                        $andWhere[] = ['t.status' => '2'];
                    } elseif ($endTime) {
                        $andWhere[] = ['<=', 't.approve_time', $endTime];
                        $andWhere[] = ['t.status' => '2'];
                    }
                }
                if ($time_type == 1) {
                    if ($startTime && $endTime) {
                        $andWhere[] = ['between', 't.create_time', $startTime, $endTime];
                    } elseif ($startTime) {
                        $andWhere[] = ['>=', 't.create_time', $startTime];
                    } elseif ($endTime) {
                        $andWhere[] = ['<=', 't.t.create_time', $endTime];
                    }
                }

                $fileName = 'return_' . date('Y-m-d');
                $andWhere[] = ['type' => 2];
                $model = AfterSalesOrder::getRetuenData($aFterOId, $where, $andWhere);
                $mergeArr = [];
                $curRow = 1;
                $mergeCol = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'T', 'U',
                    'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF'];
                $data = [];
                foreach ($model as $k => $v) {
                    $curRow++;
                    $v['type'] = AfterSalesOrder::getOrderTypeList(2);
                    $v['return_status'] = $this->return_status_map[$v['return_status']];
                    if ($v['order_type']) {
                        $v['order_type'] = strip_tags(VHelper::getOrderType($v['order_type']));
                    }
                    $v['sku'] = explode(',', $v['sku']);
                    $v['pro_name'] = explode(',', $v['pro_name']);
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
                            $tmp = clone $v;
                            $value = explode('*', $vv);
                            if (count($value) > 1) {
                                $tmp['sku'] = $value[0];
                                $tmp['quantity'] = $value[1];
                            } else {
                                $tmp['sku'] = $vv;
                            }
                            $tmp['pro_name'] = $v['pro_name'][$kk];
                            $data[] = $tmp;
                        }
                    } else {
                        foreach ($v['sku'] as $kk => $vv) {
                            $value = explode('*', $vv);
                            if (count($value) > 1) {
                                $v['sku'] = $value[0];
                                $v['quantity'] = $value[1];
                            }
                            $v['pro_name'] = $v['pro_name'][$kk];

                            $data[] = $v;
                        }
                    }
                    $v['reason'] = '';
                }
                $model = $data;
                $headers = ['platform_code' => '平台',
                    'account_name' => '帐号',
                    'after_sale_id' => '售后单号',
                    'order_type' => '订单类型',
                    'department_id' => '责任归属部门',
                    'order_id' => '系统订单号',
                    'platform_order_id' => '平台订单号',
                    'orientation_order_id' => '定位订单号',
                    'paytime' => '付款时间',
                    'buyer_id' => '客户id',
                    'ship_country_name' => '收件人国家',
                    'order_status' => '平台订单状态',
                    'complete_status' => '系统订单状态',
                    'shipped_date' => '发货时间',
                    'warehouse_name' => '发货仓库',
                    'ship_name' => '邮寄方式',
                    'sku' => 'sku',
                    'quantity' => '数量',
                    'pro_name' => '产品名',
                    'sum_quantity' => '产品总数量',
                    'reason_id' => '原因',
                    'create_by' => '提交人',
                    'create_time' => '创建时间',
                    'approve_time' => '审核时间',
                    'return_time' => '退货时间',
                    'remark' => '备注',
                    'status' => '审核状态',
                    'return_status' => '退货状态',
                    'rma' => 'rma',
                    'tracking_no' => '退货追踪号',
                    'line_cn_name' => '产品线',
                    'amazon_fulfill_channel' => 'amazon订单类型'];
                $columns = [
                    'platform_code',
                    'account_name',
                    'after_sale_id',
                    'order_type',
                    'department_id',
                    'order_id',
                    'platform_order_id',
                    'orientation_order_id',
                    'paytime',
                    'buyer_id',
                    'ship_country_name',
                    'order_status',
                    'complete_status',
                    'shipped_date',
                    'warehouse_name',
                    'ship_name',
                    'sku',
                    'quantity',
                    'pro_name',
                    'sum_quantity',
                    'reason_id',
                    'reason',
                    'create_by',
                    'create_time',
                    'approve_time',
                    'return_time',
                    'remark',
                    'status',
                    'return_status',
                    'rma',
                    'tracking_no',
                    'line_cn_name',
                    'amazon_fulfill_channel'];
                break;
            case 3:
                //平台	售后单号	订单类型 系统订单号
                //付款时间	买家ID	收件人国家
                //订单状态	原单发货时间	原单发货仓库
                //原订单Sku及数量	原订单产品名	原订单总数量 原单发货邮寄方式
                //重发单号 加价金额 重寄币种 paypal交易号
                // 收款账号 付款账号	重发Sku  重发数量
                //重发产品名	重发总数量重发货仓库
                //重发邮寄方式	重发发货时间	原因	提交人
                //备注	审核状态	产品线	发货帐号

                if ($time_type == 2) {
                    if ($startTime && $endTime) {
                        $andWhere[] = ['between', 't.approve_time', $startTime, $endTime];
                        $andWhere[] = ['t.status' => '2'];
                    } elseif ($startTime) {
                        $andWhere[] = ['>=', 't.approve_time', $startTime];
                        $andWhere[] = ['t.status' => '2'];
                    } elseif ($endTime) {
                        $andWhere[] = ['<=', 't.approve_time', $endTime];
                        $andWhere[] = ['t.status' => '2'];
                    }
                }
                if ($time_type == 1) {
                    if ($startTime && $endTime) {
                        $andWhere[] = ['between', 't.create_time', $startTime, $endTime];
                    } elseif ($startTime) {
                        $andWhere[] = ['>=', 't.create_time', $startTime];
                    } elseif ($endTime) {
                        $andWhere[] = ['<=', 't.t.create_time', $endTime];
                    }
                }
                $fileName = 'resend_' . date('Y-m-d');
                $andWhere[] = ['type' => 3];
                $model = AfterSalesRedirect::getRedirectData($aFterOId, $where, $andWhere);
                $mergeArr = [];
                $curRow = 1;
                $preRow = 0;
                $nxtRow = 0;
                $mergeCol = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
                    'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM'];
                $data = [];
                foreach ($model as $k => $v) {
                    $curRow++;
                    $v['type'] = AfterSalesOrder::getOrderTypeList(3);
                    if ($v['order_type']) {
                        $v['order_type'] = strip_tags(VHelper::getOrderType($v['order_type']));
                    }
                    $v['reSku'] = explode(',', $v['reSku']);
                    $v['rePname'] = explode(',', $v['rePname']);
                    $v['quantity'] = explode(',', $v['quantity']);
                    if (count($v['reSku']) > 1) {
                        $preRow = $curRow;
                        $nxtRow = $curRow + count($v['reSku']) - 1;

                        if ($curRow == $preRow) {
                            foreach ($mergeCol as $item) {
                                $mergeArr[] = "{$item}{$preRow}:{$item}{$nxtRow}";
                            }
                        }
                        $curRow += count($v['reSku']) - 1;

                        foreach ($v['reSku'] as $kk => $vv) {
                            $tmp = clone $v;
                            $tmp['reSku'] = trim($vv);
                            $tmp['rePname'] = $v['rePname'][$kk];
                            $tmp['quantity'] = $v['quantity'][$kk];
                            $sku_total_price = OrderDetail::getOrderdetailTotalPrice($v['order_id'], $v['platform_code'], trim($vv));
                            $tmp['sku_total_price'] = $sku_total_price;
                            $data[] = $tmp;
                        }
                    } else {
                        foreach ($v['reSku'] as $kk => $vv) {
                            $v['reSku'] = trim($vv);
                            $v['rePname'] = $v['rePname'][$kk];
                            $v['quantity'] = $v['quantity'][$kk];
                            $sku_total_price = OrderDetail::getOrderdetailTotalPrice($v['order_id'], $v['platform_code'], trim($vv));
                            $v['sku_total_price'] = $sku_total_price;
                            $data[] = $v;
                        }
                    }
                }

                if (in_array(Yii::$app->user->identity->login_name, ['徐阳鹏'])) {
                    //echo '<pre>';
                    //print_r($data);
                }
                foreach ($data as &$v2) {

                    $v2['line_cn_name'] = Product::getLineListNameBySku($v2['reSku']);
                    //调用接口获取单个sku的重寄成本 todo platform_code order_id
                    /*                     * *****
                     * purchase_cost_new1，最新采购价，
                     * package_cost包装成本，
                     * packing_cost包材成本，
                     * exchange_price汇况损失成本，
                     * shipping_cost运费成本，
                     * stock_price库存折扣，
                     * first_carrier_cost头程费用，
                     * duty_cost_new1关税，
                     * extra_price偏远附加费，
                     * exceedprice超尺寸附加费，
                     * processing海外仓处理费，
                     * pack复核打包费，
                     * residence_price住宅费
                     * ********* */

                    //    $url          = 'http:/1m7597h064.iok.la:10006/services/orders/order/getprofitdetail';
                    $url = "http://120.78.243.154/services/orders/order/getprofitdetail";
                    //   $url          .= '?order_id=EB180913004899-RE02&platform_code=EB';
                    $url .= '?order_id=' . $v2['resendId'] . '&platform_code=' . $v2['platform_code'];
                    $amt_redirect = VHelper::curl_post_async($url);

                    $amt_redirect = mb_convert_encoding($amt_redirect, "gb2312", "utf-8");
                    if (substr($amt_redirect, 0, 1) != '{') {
                        $amt_redirect = substr($amt_redirect, strpos($amt_redirect, '{"status"'));
                    }
                    $sku_data = json_decode($amt_redirect, true);

                    if ($sku_data['status'] == true) {
                        $order_sku_arr = $sku_data['data'];
                        foreach ($order_sku_arr as $k => $item) {

                            if ($k == $v2['reSku']) {
                                //SKU加钱重寄金额
                                $rmbReturn = VHelper::getTargetCurrencyAmtAll(date('Ym'));
                                $sku_redirect_price = $v2['sku_total_price'] / $v2['total_price'] / $v2['sum_quantity'] * $v2['order_amount'] * $v2['quantity'] * $rmbReturn[$v2['currency']];
                                $rmb = sprintf("%.2f", $sku_redirect_price);


                                //获取当前sku的重寄成本
                                if (in_array($v2['warehouse_name'], ['虚拟海外仓', '易佰东莞仓'])) {
                                    //国内仓

                                    $sku_redirect_amt_rmb = ($item['purchase_cost_new1'] + $item['package_cost'] +
                                            $item['packing_cost'] + $item['exchange_price'] + $item['shipping_cost']) - $rmb;

                                    $sku_redirect_amt = $sku_redirect_amt_rmb / $rmbReturn[$v2['currency']];
                                    $v2['sku_redirect_amt'] = sprintf("%.2f", $sku_redirect_amt);
                                    $v2['sku_redirect_amt_rmb'] = sprintf("%.2f", $sku_redirect_amt_rmb);
                                } else {
                                    //海外仓
                                    $sku_redirect_amt_rmb = ($item['purchase_cost_new1'] + $item['package_cost'] +
                                            $item['packing_cost'] + $item['exchange_price'] + $item['shipping_cost'] + $item['stock_price'] + $item['first_carrier_cost'] + $item['duty_cost_new1'] + $item['extra_price'] + $item['exceedprice'] + $item['processing'] + $item['pack'] + $item['residence_price']) - $rmb;
                                    $sku_redirect_amt = $sku_redirect_amt_rmb / $rmbReturn[$v2['currency']];
                                    $v2['sku_redirect_amt'] = sprintf("%.2f", $sku_redirect_amt);

                                    $v2['sku_redirect_amt_rmb'] = sprintf("%.2f", $sku_redirect_amt_rmb);
                                }
                            }
                        }
                    }
                    $v2['is_fbc'] = '';
                    if ($v2['platform_code'] == "CDISCOUNT") {
                        if ($v2['warehouse_name'] == "FBC法国仓") {
                            $v2['is_fbc'] = 'FBC订单';
                        } else {
                            $v2['is_fbc'] = '否';
                        }
                    }
                    $v2['reason'] = '';
                }
                $model = $data;
                $headers = ['platform_code' => '平台',
                    'account_name' => '账号',
                    'after_sale_id' => '售后单号',
                    'order_type' => '订单类型',
                    'department_id' => '责任归属部门',
                    'order_id' => '系统订单号',
                    'platform_order_id' => '平台订单号',
                    'order_status' => '原订单状态',
                    'shipped_date' => '原单发货时间',
                    'warehouse_name' => '原单发货仓库',
                    'resendId' => '重发单号',
                    'order_amount' => '加价金额',
                    'currency' => '重寄币种',
                    'paypal_id' => 'paypal交易号',
                    'payer_email' => '付款PayPal帐号',
                    'receiver_email' => '收款PayPal帐号',
                    'reSku' => '重发Sku',
                    'quantity' => '重发数量',
                    'rePname' => '重发产品名',
                    'sku_redirect_amt' => 'SKU重寄金额',
                    'sku_redirect_amt_rmb' => 'SKU重寄金额（RMB）',
                    'reSumqty' => '重发总数量',
                    'sku' => '原订单Sku及数量',
                    'pro_name' => '原订单产品名',
                    'sum_quantity' => '原订单总数量',
                    'ship_name' => '原单发货邮寄方式',
                    'reOrderStatus' => '重寄订单状态',
                    'reWarehouse' => '重发货仓库',
                    'reShipName' => '重发邮寄方式',
                    'reShippedDate' => '重发发货时间',
                    'reason_id' => '原因',
                    'reason'=>'平台退款原因',
                    'createBy' => '提交人',
                    'createTime' => '创建时间',
                    'approve_time' => '审核时间',
                    'remark' => '备注',
                    'status' => '审核状态',
                    'line_cn_name' => '产品线',
                    'amazon_fulfill_channel' => 'amazon订单类型',
                    'ship_country_name' => '发货国家',
                    'is_fbc' => '是否为FBC订单',
                ];
                $columns = ['platform_code',
                    'account_name',
                    'after_sale_id',
                    'order_type',
                    'department_id',
                    'order_id',
                    'platform_order_id',
                    'order_status',
                    'shipped_date',
                    'warehouse_name',
                    'resendId',
                    'order_amount',
                    'currency',
                    'paypal_id',
                    'payer_email',
                    'receiver_email',
                    'reSku',
                    'quantity',
                    'rePname',
                    'sku_redirect_amt',
                    'sku_redirect_amt_rmb',
                    'reSumqty',
                    'sku',
                    'pro_name',
                    'sum_quantity',
                    'ship_name',
                    'reOrderStatus',
                    'reWarehouse',
                    'reShipName',
                    'reShippedDate',
                    'reason_id',
                    'reason',
                    'createBy',
                    'createTime',
                    'approve_time',
                    'remark',
                    'status',
                    'line_cn_name',
                    'amazon_fulfill_channel',
                    'ship_country_name',
                    'is_fbc',
                ];
                break;
        }
        if ($platformCode == Platform::PLATFORM_CODE_WALMART) {
            $headers = array_merge($headers, array('order_number' => 'Order Number'));
            $columns = array_merge($columns, array('order_number'));
        }
        if (empty($model)) {
            $this->_showMessage('当前条件查询数据为空', false);
        } else {
            $this->exportExcel([
                'fileName' => $fileName,
                'models' => $model,
                'mode' => 'export', //default value as 'export'
                'columns' => $columns, //without header working, because the header will be get label from attribute label.
                'headers' => $headers,
                'format' => 'Excel5',
                    ], $mergeArr);
        }
    }

    /**
     *
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

    public function actionRefundeb() {
        $after_sale_id = $this->request->getQueryParam('after_sale_id');

        $refund_model = AfterSalesRefund::find()
                        ->select('t.*,t1.status')
                        ->from(AfterSalesRefund::tableName() . ' as t')
                        ->innerJoin(AfterSalesOrder::tableName() . ' as t1', 't1.after_sale_id = t.after_sale_id')
                        ->where(['t.after_sale_id' => $after_sale_id])->one();
        if ($refund_model->status != AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED && !$refund_model->refund_status != AfterSalesRefund::REFUND_STATUS_FAIL)
            $this->_showMessage('状态错误，无法重新', false);

        list ($transaction_id, $old_account_id, $item_id, $platform_order_id) = Order::getTransactionId(
                        $refund_model->platform_code, $refund_model->order_id
        );

        $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_ING;
        //修改退款状态为退款中
        if (!$refund_model->save()) {
            $this->_showMessage('修改状态为退款中失败', false);
        }

        list ($result, $message) = $this->refundEbay($refund_model, $transaction_id, $item_id);

        $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;

        //退款成功状态
        if ($result) {
            $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
        }

        if ($refund_model->refund_status == AfterSalesRefund::REFUND_STATUS_FAIL) {
            $refund_model->fail_count = $refund_model->fail_count + 1;
            $refund_model->fail_reason = $message;
        }
        //更新退款结果
        if ($refund_model->save())
            $this->_showMessage('操作成功', true);
    }

    /**
     * @desc EBAY退票入口
     * @param object $refund_model 售后退款记录对象
     * @param string $transaction_id 订单交易号
     * @param string $accountId 老的erp系统的account_id
     */
    protected function refundEbay($refund_model, $transaction_id, $item_id = '') {
        //参数缺失
        if (empty($refund_model) || empty($transaction_id)) {
            return [false, 'Required parameter missing'];
        }

        //通过从eb拉下来的交易信息表中找到用来提款的退票账户信息
        $transactions_model = Transactions::find()->where(['transaction_id' => $transaction_id])->one();

        //没有绑定退款账号
        if (empty($transactions_model)) {
            $receiver_business = Order::getTransactionInfo($transaction_id);
            if (empty($receiver_business)) {
                $receiverModel = new ErpProductApi();
                $result = $receiverModel->getProductPaypal(['itemId' => $item_id]);

                if ($result->ack == true && !empty($result->datas)) {
                    $receiver_business = $result->datas;
                } else {
                    $receiver_business = '';
                }
            }

            if (empty($receiver_business))
                return [false, 'No transactions info'];
        } else
            $receiver_business = $transactions_model->receiver_email;

        $refund_account_model = RefundAccount::find()->where(['email' => $receiver_business])->one();

        //没有找到退票账号信息
        if (empty($refund_account_model)) {
            return [false, 'No find account info'];
        }

        //构造ebay退票接口的请求配置参数
        $params['refund_config'] = [
            'acct1.UserName' => $refund_account_model->api_username,
            'acct1.Password' => $refund_account_model->api_password,
            'acct1.Signature' => $refund_account_model->api_signature,
        ];

        $params['transaction_id'] = $transaction_id;
        $params['refund_amount'] = $refund_model->refund_amount;
        $params['currency_code'] = $refund_model->currency;
        $params['refund_type'] = "Partial"; //部分退款
        //全部退款
        if ($refund_model->refund_type == AfterSalesRefund::REFUND_TYPE_FULL) {
            $params['refund_type'] = "Full";
        }

        //调用ebay封装的退票接口
        $result = VHelper::ebayRefund($params);
        //list ($result, $message) = VHelper::ebayRefund($params);
        //返回退票结果
        return $result;
    }

    /** 添加 或修改收款单
     * @return string
     * @throws Exception
     */
    public function actionEbayreceipt() {
        $orderId = $this->request->getQueryParam('order_id');
        $platform = $this->request->getQueryParam('platform');
        $buyer_id = $this->request->getQueryParam('buyer_id');
        $account_id = $this->request->getQueryParam('account_id');
        $this->isPopup = true;
        if ($this->request->getIsPost()) {
            $data = Yii::$app->request->post();
            $after_sale_receipt_id = $data['after_sale_receipt_id'];
            //收款类型 1收到退回 2 加钱重寄 3假重寄 4 其他(必须有备注)
            if ($after_sale_receipt_id) {
                $dbTransaction = AfterSalesReceipt::getDb()->beginTransaction();
                //编辑
                try {
                    $afterSaleReceiptModel = AfterSalesReceipt::findOne($after_sale_receipt_id);
                    $afterSaleReceiptModel->receipt_type = $data['receipt_type']; //收款方式
                    if (intval($data['receipt_type']) == 1) {
                        //paypal收款
                        $transaction_id = $data['transaction_id_2']; //交易号
                        if (empty($data['paypal_account_id'])) {
                            $this->_showMessage('请输入paypal账号', false);
                        }
                        if (empty($transaction_id)) {
                            $this->_showMessage('请输入交易流水号', false);
                        }
                        $record = Yii::$app->request->post('record');
                        $address = Yii::$app->request->post('address');
                        $model = new Transactionrecord();
                        $Transactionrecord_info = Transactionrecord::find()->where(['transaction_id' => $transaction_id])->asArray()->one();
                        $count = Transactionrecord::find()->where(['transaction_id' => $transaction_id])->count();
                        if (!$count) {
                            $model->transaction_id = $transaction_id;
                            $model->receive_type = $record['receive_type'] > 0 ? 1 : 2;
                            $model->receiver_business = isset($record['receiver_business']) ? $record['receiver_business'] : null;
                            $model->receiver_email = isset($record['receiver_email']) ? $record['receiver_email'] : null;
                            $model->receiver_id = isset($record['receiver_id']) ? $record['receiver_id'] : null;
                            $model->payer_id = isset($record['payer_id']) ? $record['payer_id'] : null;
                            $model->payer_name = isset($record['payer_name']) ? $record['payer_name'] : null;
                            $model->payer_email = isset($record['payer_email']) ? $record['payer_email'] : null;
                            $model->payer_status = isset($record['payer_status']) ? $record['payer_status'] : null;
                            $model->transaction_type = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                            $model->payment_type = isset($record['payment_type']) ? $record['payment_type'] : null;
                            $model->order_time = date("Y-m-d H:i:s", strtotime($record['order_time']));
                            $model->amt = isset($record['amt']) ? $record['amt'] : null;
                            $model->tax_amt = isset($record['tax_amt']) ? $record['tax_amt'] : 0;
                            $model->fee_amt = isset($record['fee_amt']) ? $record['fee_amt'] : 0;
                            $model->currency = isset($record['currency']) ? $record['currency'] : null;
                            $model->payment_status = isset($record['payment_status']) ? $record['payment_status'] : null;
                            $model->status = 1;
                            $bool_record = $model->save();
                            //paypal收款
                            $receipt_currency = $record['currency'];
                            $receipt_money = $record['amt'];
                        } else {
                            $receipt_currency = $Transactionrecord_info['currency'];
                            $receipt_money = $Transactionrecord_info['amt'];
                        }

                        $transaction_address_model = new TransactionAddress();
                        $address_count = TransactionAddress::find()->where(['transaction_id' => $transaction_id])->count();
                        //address 表
                        if (!$address_count) {
                            $transaction_address_model->transaction_id = $transaction_id;
                            $transaction_address_model->name = isset($address['name']) ? $address['name'] : null;
                            $transaction_address_model->street1 = isset($address['street1']) ? $address['street1'] : null;
                            $transaction_address_model->street2 = isset($address['street2']) ? $address['street2'] : null;
                            $transaction_address_model->city_name = isset($address['city_name']) ? $address['city_name'] : null;
                            $transaction_address_model->country = isset($address['country']) ? $address['country'] : null;
                            $transaction_address_model->country_name = isset($address['country_name']) ? $address['country_name'] : null;
                            $transaction_address_model->phone = isset($address['phone']) ? $address['phone'] : null;
                            $transaction_address_model->postal_code = isset($address['postal_code']) ? $address['postal_code'] : null;
                            $bool_address = $transaction_address_model->save();
                        }
                    } else {
                        if (empty($data['receipt_bank'])) {
                            $this->_showMessage('请选择线下收款银行', false);
                        }
                        if (empty($data['receipt_money'])) {
                            $this->_showMessage('请输入收款金额', false);
                        }

                        //线下收款
                        $transaction_id = $data['transaction_id']; //交易号
                        $receipt_currency = $data['receipt_currency'];
                        $receipt_money = $data['receipt_money'];
                        if (empty($transaction_id)) {
                            $this->_showMessage('请输入交易流水号', false);
                        }
                    }
                    $afterSaleReceiptModel->after_sale_receipt_id = intval($after_sale_receipt_id);
                    $afterSaleReceiptModel->transaction_id = $transaction_id;
                    $afterSaleReceiptModel->receipt_currency = $receipt_currency; //收款币种
                    $afterSaleReceiptModel->receipt_money = $receipt_money; //收款金额
                    if (isset($data['paypal_account_id']) && !empty($data['paypal_account_id'])) {
                        $afterSaleReceiptModel->paypal_account_id = $data['paypal_account_id'];
                        $afterSaleReceiptModel->paypal_account = RefundAccount::getOne(intval($data['paypal_account_id']))['email'];
                    }
                    $afterSaleReceiptModel->receipt_reason_type = $data['receipt_reason_type']; //收款原因类型
                    $afterSaleReceiptModel->receipt_reason_remark = $data['receipt_reason_remark']; //收款原因备注
                    if (isset($data['receipt_bank']) && !empty($data['receipt_bank'])) {
                        $afterSaleReceiptModel->receipt_bank = $data['receipt_bank'];
                    }
                    $afterSaleReceiptModel->modified_time = date('Y-m-d H:i:s', time()); //修改人
                    $afterSaleReceiptModel->modifier = Yii::$app->user->identity->login_name; //修改时间
                    if (empty($data['receipt_reason_type'])) {
                        $this->_showMessage('请选择收款原因类型', false);
                    }

                    $flag = $afterSaleReceiptModel->save();
                    if ($flag) {
                        $dbTransaction->commit();
                        //查询
                        if ($afterSaleReceiptModel->transaction_id != $transaction_id) {
                            //同步erp
                            $erp_result = AfterSalesReceipt::Orderbindtransaction($data['order_id'], $data['account_id'], $transaction_id);
                            $erp_result = json_decode($erp_result, true);
                            if ($erp_result) {
                                if ($erp_result['bool'] == true) {
                                    $this->_showMessage('修改成功' . '<br>' . $erp_result['msg'], true, null, false, null, 'top.window.location.reload();');
                                }
                            }
                            $this->_showMessage('修改成功' . '<br>' . $erp_result['msg'], true, null, false, null, 'top.window.location.reload();');
                        }

                        $this->_showMessage('修改成功', true, null, false, null, 'top.window.location.reload();');
                    } else {
                        $dbTransaction->rollBack();
                        $this->_showMessage('操作失败。', false);
                    }
                } catch (\Exception $e) {
                    $dbTransaction->rollBack();
                    $this->_showMessage('操作失败，异常。' . $e->getMessage(), false);
                }
            } else {
                //添加
                $afterReceiptModel = new AfterSalesReceipt();

                //验证
                $dbTransaction = AfterSalesReceipt::getDb()->beginTransaction();
                try {
                    $afterReceiptModel->order_id = $data['order_id'];
                    $afterReceiptModel->platform_code = $data['platform_code'];
                    $afterReceiptModel->account_id = $data['account_id'];
                    $afterReceiptModel->buyer_id = $data['buyer_id'];
                    $afterReceiptModel->receipt_type = $data['receipt_type'];
                    if (intval($data['receipt_type']) == 1) {
                        //paypal收款
                        $transaction_id = $data['transaction_id_2'];
                        if (empty($data['paypal_account_id'])) {
                            $this->_showMessage('请输入paypal账号', false);
                        }
                        if (empty($transaction_id)) {
                            $this->_showMessage('请输入交易流水号', false);
                        }
                        //transcation record address
                        $record = Yii::$app->request->post('record');
                        $address = Yii::$app->request->post('address');
                        $model = new Transactionrecord();
                        $count = Transactionrecord::find()->where(['transaction_id' => $transaction_id])->count();
                        if (!$count) {
                            $model->transaction_id = $transaction_id;
                            $model->receive_type = $record['receive_type'] > 0 ? 1 : 2;
                            $model->receiver_business = isset($record['receiver_business']) ? $record['receiver_business'] : null;
                            $model->receiver_email = isset($record['receiver_email']) ? $record['receiver_email'] : null;
                            $model->receiver_id = isset($record['receiver_id']) ? $record['receiver_id'] : null;
                            $model->payer_id = isset($record['payer_id']) ? $record['payer_id'] : null;
                            $model->payer_name = isset($record['payer_name']) ? $record['payer_name'] : null;
                            $model->payer_email = isset($record['payer_email']) ? $record['payer_email'] : null;
                            $model->payer_status = isset($record['payer_status']) ? $record['payer_status'] : null;
                            $model->transaction_type = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                            $model->payment_type = isset($record['payment_type']) ? $record['payment_type'] : null;
                            $model->order_time = date("Y-m-d H:i:s", strtotime($record['order_time']));
                            $model->amt = isset($record['amt']) ? $record['amt'] : null;
                            $model->tax_amt = isset($record['tax_amt']) ? $record['tax_amt'] : 0;
                            $model->fee_amt = isset($record['fee_amt']) ? $record['fee_amt'] : 0;
                            $model->currency = isset($record['currency']) ? $record['currency'] : null;
                            $model->payment_status = isset($record['payment_status']) ? $record['payment_status'] : null;
                            $model->status = 1;
                            $flag_record = $model->save();
                        }
                        //paypal收款
                        $receipt_currency = $record['currency'];
                        $receipt_money = $record['amt'];
                        $transaction_address_model = new TransactionAddress();
                        $address_count = TransactionAddress::find()->where(['transaction_id' => $transaction_id])->count();
                        //address 表
                        if (!$address_count) {
                            $transaction_address_model->transaction_id = $transaction_id;
                            $transaction_address_model->name = isset($address['name']) ? $address['name'] : null;
                            $transaction_address_model->street1 = isset($address['street1']) ? $address['street1'] : null;
                            $transaction_address_model->street2 = isset($address['street2']) ? $address['street2'] : null;
                            $transaction_address_model->city_name = isset($address['city_name']) ? $address['city_name'] : null;
                            $transaction_address_model->country = isset($address['country']) ? $address['country'] : null;
                            $transaction_address_model->country_name = isset($address['country_name']) ? $address['country_name'] : null;
                            $transaction_address_model->phone = isset($address['phone']) ? $address['phone'] : null;
                            $transaction_address_model->postal_code = isset($address['postal_code']) ? $address['postal_code'] : null;
                            $flag_address = $transaction_address_model->save();
                        }
                    } else {
                        //
                        if (empty($data['receipt_bank'])) {
                            $this->_showMessage('请选择线下收款银行', false);
                        }
                        if (empty($data['receipt_money'])) {
                            $this->_showMessage('请输入收款金额', false);
                        }
                        //线下收款
                        $transaction_id = $data['transaction_id'];
                        $receipt_currency = $data['receipt_currency'];
                        $receipt_money = $data['receipt_money'];
                        if (empty($transaction_id)) {
                            $this->_showMessage('请输入交易流水号', false);
                        }
                    }
                    $afterReceiptModel->transaction_id = $transaction_id;
                    $afterReceiptModel->receipt_currency = $receipt_currency;
                    $afterReceiptModel->receipt_money = $receipt_money;
                    if (isset($data['paypal_account_id']) && !empty($data['paypal_account_id'])) {
                        $afterReceiptModel->paypal_account_id = $data['paypal_account_id'];
                        $afterReceiptModel->paypal_account = RefundAccount::getOne(intval($data['paypal_account_id']))['email'];
                    }
                    $afterReceiptModel->receipt_reason_type = $data['receipt_reason_type'];
                    $afterReceiptModel->receipt_reason_remark = $data['receipt_reason_remark'];
                    if (isset($data['receipt_bank']) && !empty($data['receipt_bank'])) {
                        $afterReceiptModel->receipt_bank = $data['receipt_bank'];
                    }
                    $afterReceiptModel->created_time = date('Y-m-d H:i:s', time());
                    $afterReceiptModel->creater = Yii::$app->user->identity->login_name;
                    $afterReceiptModel->audit_status = 1; //默认未审核
                    if (empty($data['receipt_reason_type'])) {
                        $this->_showMessage('请选择收款原因类型', false);
                    }
                    $flag = $afterReceiptModel->save();
                    if ($flag) {
                        //提交后
                        $dbTransaction->commit();
                        //同步erp
                        $erp_result = AfterSalesReceipt::Orderbindtransaction($data['order_id'], $data['account_id'], $transaction_id);
                        $erp_result = json_decode($erp_result, true);
                        if ($erp_result) {
                            if ($erp_result['bool'] == true) {
                                $this->_showMessage('添加成功' . '<br>' . $erp_result['msg'], true, null, false, null, 'top.window.location.reload();');
                            }
                        }
                        $this->_showMessage('添加成功' . '<br>' . $erp_result['msg'], true, null, false, null, 'top.window.location.reload();');
                    } else {
                        $dbTransaction->rollBack();
                        $this->_showMessage('添加失败', false);
                    }
                } catch (\Exception $e) {
                    $dbTransaction->rollBack();
                    $this->_showMessage('操作失败，异常。' . $e->getMessage(), false);
                }
            }
        }
        $receipt_reason_types = [
            '1' => '收到退回',
            '2' => '加钱重寄',
            '3' => '假重寄',
            '4' => '其他'
        ];
        $receipt_bank = BasicConfig::getParentList(118);
        $currencys = array('USD', 'AUD', 'CAD', 'EUR', 'GBP');

        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }
        return $this->render('receipt', [
                    'platform' => $platform,
                    'order_id' => $orderId,
                    'buyer_id' => $buyer_id,
                    'account_id' => $account_id,
                    'receipt_reason_types' => $receipt_reason_types,
                    'paypallist' => $palPalList,
                    'currencys' => $currencys,
                    'receipt_banks' => $receipt_bank
        ]);
    }

    /**
     * 修改
     */
    public function actionEditreceipt() {
        $this->isPopup = true;
        $after_sale_receipt_id = $this->request->getQueryParam('after_sale_receipt_id');
        $afterSaleReceiptModel = AfterSalesReceipt::findOne($after_sale_receipt_id);
        if (empty($afterSaleReceiptModel)) {
            $this->_showMessage('找不到收款单', false, null, false, null, 'layer.closeAll()');
        }
        $currencys = array('USD', 'AUD', 'CAD', 'EUR', 'GBP');
        $receipt_reason_types = [
            '1' => '收到退回',
            '2' => '加钱重寄',
            '3' => '假重寄',
            '4' => '其他'
        ];
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }
        $receipt_bank = BasicConfig::getParentList(118);
        return $this->render('receipt', [
                    'afterSaleReceipt' => $afterSaleReceiptModel,
                    'currencys' => $currencys,
                    'receipt_reason_types' => $receipt_reason_types,
                    'paypallist' => $palPalList,
                    'receipt_banks' => $receipt_bank
        ]);
    }

    /**
     * 收款单列表
     * @return string
     */
    public function actionReceiptlist() {
        $platform_code = isset($_REQUEST['platform_code']) ? $_REQUEST['platform_code'] : null; //平台
        $account_id = isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : null; //账号id
        $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null; //订单id
        $buyer_id = isset($_REQUEST['buyer_id']) ? trim($_REQUEST['buyer_id']) : null; //buyer_id
        $creater = isset($_REQUEST['creater']) ? trim($_REQUEST['creater']) : null; //申请人
        $begin_date = isset($_REQUEST['begin_date']) ? trim($_REQUEST['begin_date']) : null; //申请开始日期
        $end_date = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null; //申请结束日期
        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : null; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        $result = AfterSalesReceipt::getReceiptList($platform_code, $account_id, $order_id, $buyer_id, $begin_date, $end_date, $pageCur, $pageSize, $creater);
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => $result['count'],
            //分页大小
            'pageSize' => 10,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);

        $platformList = Platform::getPlatformAsArray();

        if ($platform_code == null) {
            $ImportPeople_list = Account::getIdNameKefuList('EB');
        } else {
            $ImportPeople_list = Account::getIdNameKefuList($platform_code);
        }

        return $this->render('receiptlist', [
                    'receipts' => $result['data_list'],
                    'page' => $page,
                    'count' => $result['count'],
                    'buyer_id' => $buyer_id,
                    'creater' => $creater,
                    'platformCode' => $platform_code,
                    'begin_date' => $begin_date,
                    'end_date' => $end_date,
                    'order_id' => $order_id,
                    'account_id' => $account_id,
                    'ImportPeople_list' => $ImportPeople_list,
                    'platform_code' => $platform_code,
                    'platformList' => $platformList
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
     * 审核
     * @return mixed
     */
    public function actionAuditreceipt() {
        $after_sale_receipt_id = Yii::$app->request->post('after_sale_receipt_id');
        $remark = Yii::$app->request->post('audit_remark');
        $audit_status = Yii::$app->request->post('audit_status');
        if (strstr($after_sale_receipt_id, ',')) {
            //多个审核
            $ids_arr = explode(',', $after_sale_receipt_id);
            foreach ($ids_arr as $v) {
                $receiptModel = AfterSalesReceipt::findOne(['after_sale_receipt_id' => $v]);
                if (empty($receiptModel)) {
                    $this->_showMessage('该收款单不存在', false);
                }
                //修改退款状态与时间
                $receiptModel->audit_status = $audit_status;
                $receiptModel->audit_time = date('Y-m-d H:i:s', time());
                $receiptModel->auditer = Yii::$app->user->identity->login_name;
                $receiptModel->audit_remark = $remark;

                //$orderId
                $orderId = $receiptModel->order_id;
                $account = $receiptModel->paypal_account;
                $transactionId = $receiptModel->transaction_id;
                $currency = $receiptModel->receipt_currency;
                $amount = $receiptModel->receipt_money;
                //同步erp
                $erp_result = AfterSalesReceipt::Orderbindtransaction($orderId, $account, $transactionId, $currency, $amount);
                if ($receiptModel->save() && $erp_result->bool == true) {
                    $this->_showMessage('操作成功', true, null, false, null, 'top.refreshTable(location.href);');
                } else {
                    $this->_showMessage('操作失败', false);
                }
            }
        } else {
            $receiptModel = AfterSalesReceipt::findOne(['after_sale_receipt_id' => $after_sale_receipt_id]);
            if (empty($receiptModel)) {
                $this->_showMessage('该收款单不存在', false);
            }
            //修改退款状态与时间
            $receiptModel->audit_status = $audit_status;
            $receiptModel->audit_time = date('Y-m-d H:i:s', time());
            $receiptModel->auditer = Yii::$app->user->identity->login_name;
            $receiptModel->audit_remark = $remark;
            //$orderId
            $orderId = $receiptModel->order_id;
            $account = $receiptModel->paypal_account;
            $transactionId = $receiptModel->transaction_id;
            $currency = $receiptModel->receipt_currency;
            $amount = $receiptModel->receipt_money;
            //同步erp
            $erp_result = AfterSalesReceipt::Orderbindtransaction($orderId, $account, $transactionId, $currency, $amount);
            if ($receiptModel->save() && $erp_result->bool == true) {

                //
                $receiptModel->erp_status = 2; //同步成功
                $this->_showMessage('操作成功', true, null, false, null, 'top.refreshTable(location.href);');
            } else {
                $this->_showMessage('操作失败', false);
            }
        }
    }

    /**
     * 收款单下载
     */
    public function actionDownloadreceipt() {
        $platform_code = isset($_REQUEST['platform_code']) ? $_REQUEST['platform_code'] : null; //平台
        $account_id = isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : null; //账号id
        $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null; //订单id
        $buyer_id = isset($_REQUEST['buyer_id']) ? trim($_REQUEST['buyer_id']) : null; //buyer_id
        $begin_date = isset($_REQUEST['begin_date']) ? trim($_REQUEST['begin_date']) : null; //申请开始日期
        $end_date = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null; //申请结束日期
        $json = isset($_REQUEST['json']) ? trim($_REQUEST['json']) : null;
        $json_arr = [];
        if (!empty($json)) {
            $json_arr = explode(',', $json);
        } else {
            if (empty($platform_code)) {
                $platform_code = null;
            }
            if (empty($account_id)) {
                $account_id = null;
            }
            if (empty($order_id)) {
                $order_id = null;
            }
            if (empty($buyer_id)) {
                $buyer_id = null;
            }
            if (empty($begin_date)) {
                $begin_date = null;
            }
            if (empty($end_date)) {
                $end_date = null;
            }
        }

        $data = AfterSalesReceipt::getDownloadList($json_arr, $platform_code, $account_id, $order_id, $buyer_id, $begin_date, $end_date);
        //标题数组
        $fieldArr = [
            '平台',
            '申请人/申请时间',
            '更新人/更新时间',
            '平台账号',
            '买家id',
            '订单号',
            'PayPal账号',
            '交易号',
            '补收款币种',
            '补收款金额',
//            '审核人/审核日期',
//            '审核状态',
//            '审核备注',
            '收款方式',
            '收款原因',
        ];
        //导出数据数组
        $dataArr = [];
        foreach ($data['data_list'] as $item) {

            //导出数据数组
            $dataArr[] = [
                $item['platform_code'], //
                $item['creater'] . '/' . $item['created_time'],
                $item['modifier'] . '/' . $item['modified_time'],
                Account::getAccountNameByOldAccountId($item['account_id'], $item['platform_code']),
                $item['buyer_id'],
                $item['order_id'],
                $item['paypal_account'],
                $item['transaction_id'],
                $item['receipt_currency'],
                $item['receipt_money'],
//                $item['auditer'] . '/' . $item['audit_time'],
//                AfterSalesReceipt::getReceiptAuditStatus($item['audit_status']),//
//                $item['audit_remark'],
                AfterSalesReceipt::getReceiptType($item['receipt_type']),
                AfterSalesReceipt::getReceiptReasonType($item['receipt_reason_type']), //
            ];
        }
        VHelper::exportExcel($fieldArr, $dataArr, 'receipt_' . date('Y-m-d'));
    }

    /**
     * 删除
     * @throws \yii\db\Exception
     */
    public function actionDeletereceipt() {
        $after_sale_receipt_id = $this->request->getQueryParam('after_sale_receipt_id');
        $dbTransaction = AfterSalesReceipt::getDb()->beginTransaction();
        //多个删除
        if (strstr($after_sale_receipt_id, ',')) {
            $ids_arr = explode(',', $after_sale_receipt_id);
            //
            foreach ($ids_arr as $v) {
                $afterSalesReceiptModel = AfterSalesReceipt::findOne(['after_sale_receipt_id' => $v]);
//                if ($afterSalesReceiptModel->audit_status == 2) {
//                    $this->_showMessage('审核通过，无法删除', false);
//                }

                $orderId = $afterSalesReceiptModel->order_id;
                $account = $afterSalesReceiptModel->paypal_account;
                $transactionId = $afterSalesReceiptModel->transaction_id;
                //同步erp 删除记录
                $erp_result = AfterSalesReceipt::Orderunbindtransaction($orderId, $account, $transactionId);
                $erp_result = json_decode($erp_result, true);
                if (!AfterSalesReceipt::deleteAll("after_sale_receipt_id='" . $v . "'")) {
                    $this->_showMessage('收款单详情删除失败', false);
                }
                $dbTransaction->commit();
                if ($erp_result) {
                    if ($erp_result['bool'] == true) {
                        $this->_showMessage('删除成功' . '<br>' . $erp_result['msg'], true, null, false, null, 'top.window.location.reload();');
                    }
                }
                $this->_showMessage('删除成功' . '<br>' . $erp_result['msg'], true, null, false, null, 'top.window.location.reload();');
            }
        } else {
            $afterSalesReceiptModel = AfterSalesReceipt::findOne(['after_sale_receipt_id' => $after_sale_receipt_id]);
//            if ($afterSalesReceiptModel->audit_status == 2) {
//                $this->_showMessage('审核通过，无法删除', false);
//            }
            $orderId = $afterSalesReceiptModel->order_id;
            $account = $afterSalesReceiptModel->paypal_account;
            $transactionId = $afterSalesReceiptModel->transaction_id;
            //同步erp 删除记录
            $erp_result = AfterSalesReceipt::Orderunbindtransaction($orderId, $account, $transactionId);
            $erp_result = json_decode($erp_result, true);
            if (!AfterSalesReceipt::deleteAll("after_sale_receipt_id='" . $after_sale_receipt_id . "'")) {
                $this->_showMessage('收款单详情删除失败', false);
            }
            $dbTransaction->commit();
            if ($erp_result) {
                if ($erp_result['bool'] == true) {
                    $this->_showMessage('删除成功' . '<br>' . $erp_result['msg'], true, null, false, null, 'top.window.location.reload();');
                }
            }
            $this->_showMessage('删除成功' . '<br>' . $erp_result['msg'], true, null, false, null, 'top.window.location.reload();');
        }
    }

    /**
     * @author alpha
     * @desc 取消退件
     */
    public function actionCancelreturn() {
        $afterSalesId = trim($this->request->getQueryParam('after_sales_id'));
        $status = (int) $this->request->getQueryParam('return_status');
        $afterSaleOrderReturn = AfterSalesReturn::findOne_overwrite($afterSalesId);
        if (empty($afterSaleOrderReturn)) {
            $this->_showMessage('该退货单不存在', false);
        }
        //修改退款状态与时间
        $afterSaleOrderReturn->modify_by = Yii::$app->user->identity->login_name;
        $afterSaleOrderReturn->modify_time = date('Y-m-d H:i:s');
        $afterSaleOrderReturn->return_status = $status;
        $data = [];
        $data['order_id'] = $afterSaleOrderReturn->order_id;
        $data['create_by'] = Yii::$app->user->identity->login_name;
        $data['create_time'] = date('Y-m-d H:i:s');
        $orderModel = new ErpOrderApi();
        $res = $orderModel->cancelaftersaleorder($data);
        if ($res->statusCode == 200) {
            //erp 修改状态成功
            $flag = $afterSaleOrderReturn->save();
            if ($flag)
                $this->_showMessage('操作成功:' . '<br>' . $res->message, true, null, false, null, 'top.refreshTable(location.href);');
        } else {
            $this->_showMessage('操作失败:' . '<br>' . $res->message, false);
        }
    }

    /*     * *
     * @author harvin
     * 批量标记退款
     * 
     * ** */

    public function actionRefund() {
        $request = Yii::$app->request->get();
        $type = isset($request['type']) ? $request['type'] : ""; //售后单类型 
        $refundStatus = isset($request['refundStatus']) ? $request['refundStatus'] : ""; //售后单退款状态，只有类型为退款失败的情况才有用 4 
        $selectIds = isset($request['selectIds']) ? $request['selectIds'] : ""; //勾选数据 
        if (empty($type)) {
            return json_encode(['state' => 0, 'msg' => '请选择售后类型']);
        }
        if ($type != "1") {
            return json_encode(['state' => 0, 'msg' => '请选择退款售后单']);
        }
        if ($refundStatus !== "4") {
            return json_encode(['state' => 0, 'msg' => '只有退款失败的退款售后单才能标记']);
        }
        if (empty($refundStatus)) {
            return json_encode(['state' => 0, 'msg' => '只有退款失败的退款售后单才能标记']);
        }
        if (empty($selectIds)) {
            return json_encode(['state' => 0, 'msg' => '请勾选数据']);
        }
        $json = explode(',', $selectIds);

        //查找售后表数据及售后退款表数据
        // $aftersales= AfterSalesOrder::find()->where(['in', 'after_sale_id', $json])->andwhere(['type'=>$type])->andwhere()->asArray()->all();
        $query = AfterSalesOrder::find();
        $aftersales = $query
                ->from(AfterSalesOrder::tableName() . ' A')->select('A.after_sale_id')
                ->leftJoin(AfterSalesRefund::tableName() . ' B', 'A.after_sale_id = B.after_sale_id')
                ->where(['A.type' => $type])->andWhere(['B.refund_status' => $refundStatus])->andwhere(['in', 'A.after_sale_id', $json])
                ->asArray()
                ->all();


        if (empty($aftersales)) {
            return json_encode(['state' => 0, 'msg' => '请勾选符合条件数据']);
        }
        //改变表售后退款表状态
        foreach ($aftersales as $key => $val) {
            $aftersaleid[] = $val['after_sale_id'];
        }
        //批量更新
        $result = AfterSalesRefund::updateAll(['refund_status' => 3, 'refund_time' => date("Y-m-d H:s:i"),'fail_reason' => '','remark' => Yii::$app->user->identity->user_name.' 在 ['.date('Y-m-d H:i:s').']手动批量更新退款状态为退款成功!'], ['in', 'after_sale_id', $aftersaleid]);
        if ($result) {
            return json_encode(['state' => 1, 'msg' => '操作成功']);
        } else {
            return json_encode(['state' => 0, 'msg' => '操作失败']);
        }
    }

    /*     * *
     * @author harvin
     * 批量删除
     * 
     * ** */

    public function actionDeleteall() {
        $request = Yii::$app->request->get();
        $type = isset($request['type']) ? $request['type'] : ""; //售后单类型 
        $time_type = isset($request['time_type']) ? $request['time_type'] : ""; //时间类型
        $selectIds = isset($request['selectIds']) ? $request['selectIds'] : ""; //勾选数据 
        $startTime = isset($request['startTime']) ? $request['startTime'] : ""; //开始时间 
        $endTime = isset($request['endTime']) ? $request['endTime'] : ""; //结束时间 
        if (empty($type)) {
            return json_encode(['state' => 0, 'msg' => '请选择售后类型']);
        }
        if (empty($time_type)) {
            return json_encode(['state' => 0, 'msg' => '请选择时间类型']);
        }
        if (empty($startTime)) {
            return json_encode(['state' => 0, 'msg' => '请选择开始时间']);
        }
        if (empty($endTime)) {
            return json_encode(['state' => 0, 'msg' => '请选择结束时间']);
        }
        if (empty($selectIds)) {
            return json_encode(['state' => 0, 'msg' => '请勾选数据']);
        }
        $json = explode(',', $selectIds);

        //如果选择是退款必须指定退款完成时间类型 时间类型为退款完成时间$type 为1退款  3为重寄
        if ($type == 1) {
            if ($time_type == 3) {
                $query = AfterSalesOrder::find();
                $aftersales = $query
                        ->from(AfterSalesOrder::tableName() . ' A')->select('A.after_sale_id')
                        ->leftJoin(AfterSalesRefund::tableName() . ' B', 'A.after_sale_id = B.after_sale_id')
                        ->where(['A.type' => $type])->andWhere(['between', 'B.refund_time', $startTime, $endTime])->andwhere(['in', 'A.after_sale_id', $json])
                        ->asArray()
                        ->all();
            } else {
                return json_encode(['state' => 0, 'msg' => '请退款完成时间类型']);
            }

            if (empty($aftersales)) {
                return json_encode(['state' => 0, 'msg' => '过了当月的数据不能删除']);
            }
            foreach ($aftersales as $v) {
                $aftersaleid[] = $v['after_sale_id'];
            }
            //启动事物
            $transaction = Yii::$app->db->beginTransaction();
            try {
                AfterSalesOrder::deleteAll(['in', 'after_sale_id', $aftersaleid]);
                AfterSalesRefund::deleteAll(['in', 'after_sale_id', $aftersaleid]);
                foreach ($aftersaleid as $key => $val) {
                    AfterSalesProduct::deleteAll("after_sale_id = '" . $val . "'");
                }
                $transaction->commit();
                return json_encode(['state' => 1, 'msg' => '操作成功']);
            } catch (Exception $e) {
                $transaction->rollBack();
                return json_encode(['state' => 0, 'msg' => '操作失败']);
            }
        }
        //重寄

        if ($type == 3) {
            if ($time_type == 2) {
                $query = AfterSalesOrder::find();
                $aftersales = $query
                        ->from(AfterSalesOrder::tableName() . ' A')->select('A.after_sale_id')
                        ->leftJoin(AfterSalesRefund::tableName() . ' B', 'A.after_sale_id = B.after_sale_id')
                        ->where(['A.type' => $type])->andWhere(['between', 'B.refund_time', $startTime, $endTime])->andwhere(['in', 'A.after_sale_id', $json])
                        ->asArray()
                        ->all();
            } else {
                return json_encode(['state' => 0, 'msg' => '请选择退款完成时间类型']);
            }

            if (empty($aftersales)) {
                return json_encode(['state' => 0, 'msg' => '过了当月的数据不能删除']);
            }
            foreach ($aftersales as $v) {
                $aftersaleid[] = $v['after_sale_id'];
            }
            //启动事物
            $transaction = Yii::$app->db->beginTransaction();
            try {
                AfterSalesOrder::deleteAll(['in', 'after_sale_id', $aftersaleid]);
                AfterSalesRefund::deleteAll(['in', 'after_sale_id', $aftersaleid]);
                foreach ($aftersaleid as $key => $val) {
                    AfterSalesProduct::deleteAll("after_sale_id = '" . $val . "'");
                }
                $transaction->commit();
                return json_encode(['state' => 1, 'msg' => '操作成功']);
            } catch (Exception $e) {
                $transaction->rollBack();
                return json_encode(['state' => 0, 'msg' => '操作失败']);
            }
        }
    }

    /*     * *
     * @author harvin
     * 
     * 
     * ** */

    public function actionReversion() {
        
    }

}
