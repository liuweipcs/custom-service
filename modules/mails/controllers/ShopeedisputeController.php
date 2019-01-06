<?php

namespace app\modules\mails\controllers;

use app\common\VHelper;
use app\modules\mails\models\ShopeeAttachment;
use app\modules\mails\models\ShopeeDisputeList;
use app\modules\mails\models\ShopeeOrderLogistics;
use app\modules\orders\models\OrderOtherSearch;
use app\modules\systems\models\AftersaleManage;
use app\modules\systems\models\BasicConfig;
use Yii;
use app\components\Controller;
use app\modules\orders\models\OrderKefu;
use app\modules\accounts\models\Platform;
use yii\helpers\Json;
use app\modules\orders\models\Warehouse;
use app\modules\systems\models\Country;
use app\modules\orders\models\Transactionrecord;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\RefundAccount;
use yii\web\UploadedFile;

class ShopeedisputeController extends Controller
{
    const ISSUE_UPLOAD_PATH = './uploads/shopeeissue/';
    //最大纠纷图片大小2M
    const MAX_ISSUE_IMAGE_SIZE = 2097152;

    /**
     * 列表
     */
    public function actionList()
    {
        $params       = \Yii::$app->request->getBodyParams();
        $model        = new ShopeeDisputeList();
        $dataProvider = $model->searchList($params);
        return $this->renderList('index', [
            'model'        => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 处理纠纷
     */
    public function actionHandle()
    {
        $this->isPopup = true;
        $id            = Yii::$app->request->get('id', 0);
        if (empty($id)) {
            $this->_showMessage('纠纷ID不能为空', false);
        }
        $issueList = ShopeeDisputeList::findOne($id);
        if (empty($issueList)) {
            $this->_showMessage('没有找到纠纷信息', false);
        }

        //纠纷ID
        $issueId = $issueList['returnsn'];
        //平台订单ID
        $orderId = $issueList['ordersn'];

        //获取订单信息
        $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_SHOPEE, $orderId);

        if (!empty($orderinfo)) {
            $orderinfo = Json::decode(Json::encode($orderinfo), true);
        } else {
            $orderinfo = [];
        }

        //如果在erp没获取到交易信息  则在客服系统重新获取一遍
        if (!empty($orderinfo['trade'])) {
            foreach ($orderinfo['trade'] as $key => $value) {
                if ($value['receiver_email'] == "" || $value['payer_email'] == "") {
                    $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->asArray()->one();
                    if (!empty($transactionRecord)) {
                        $orderinfo['trade'][$key]['receiver_email'] = $transactionRecord['receiver_email'];
                        $orderinfo['trade'][$key]['payer_email']    = $transactionRecord['payer_email'];
                        $orderinfo['trade'][$key]['payment_status'] = $transactionRecord['payment_status'];
                    }
                }
            }
        }

        //组装库存和在途数
        if (!empty($orderinfo['product'])) {
            $result = isset($orderinfo['wareh_logistics']) && isset($orderinfo['wareh_logistics']['warehouse']);
            foreach ($orderinfo['product'] as $key => $value) {
                $orderinfo['info']['product_weight'] += $value['product_weight'] * $value['quantity'];
                list($stock, $on_way_count) = [null, null];
                if ($result) {
                    $data         = [];
                    $stock        = isset($data['available_stock']) ? $data['available_stock'] : 0;
                    $on_way_count = isset($data['on_way_stock']) ? $data['on_way_stock'] : 0;
                }
                $orderinfo['product'][$key]['stock']        = $stock;
                $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;

            }
        }

        //获取售后信息
        $afterSalesOrders = [];
        if (!empty($orderinfo['info']['order_id'])) {
            $afterSalesOrders = AfterSalesOrder::getByOrderId(Platform::PLATFORM_CODE_SHOPEE, $orderinfo['info']['order_id']);
        }
        $countires = Country::getCodeNamePairsList();
        //获取仓库列表
        $warehouseList = Warehouse::getWarehouseListAll();
        //获取paypal账号信息
        $palPalList  = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }
        $accountid = $issueList['account_id'];
        //获取原因
        $ReturnDisputeReasonList = ['NON_RECEIPT '  => 'NON_RECEIPT ',
                                    'OTHER '        => 'OTHER ',
                                    'NOT_RECEIVED ' => 'NOT_RECEIVED ',
                                    'UNKNOWN'       => 'UNKNOWN'];
        return $this->render('issueinfo/handle', [
            'id'                      => $id,
            'order_id'                => $orderId,
            'account_id'              => $accountid,
            'returnsn'                => $issueId,
            'issueList'               => $issueList,
            'info'                    => $orderinfo,
            'paypallist'              => $palPalList,
            'countries'               => $countires,
            'warehouseList'           => $warehouseList,
            'afterSalesOrders'        => $afterSalesOrders,
            'ReturnDisputeReasonList' => $ReturnDisputeReasonList,
            'platform'                => Platform::PLATFORM_CODE_SHOPEE,
        ]);
    }


    /**
     * 纠纷详情
     */
    public function actionDetails()
    {
        $this->isPopup = true;

        //获取纠纷ID
        $id = Yii::$app->request->get('id');
        if (empty($id)) {
            $this->_showMessage('纠纷ID不能为空', false);
        }

        //获取纠纷列表信息
        $issueList = ShopeeDisputeList::find()->where(['id' => $id])->asArray()->one();
        if (empty($issueList)) {
            $this->_showMessage('没有找到纠纷信息', false);
        }
        //平台订单ID
        $orderId   = $issueList['ordersn'];
        $issueId   = $issueList['returnsn'];
        $accountid = $issueList['account_id'];

        //获取订单信息
        $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_SHOPEE, $orderId);

        if (empty($orderinfo)) {
            $orderinfo         = [];
            $orderinfo['info'] = [];
        } else {
            $orderinfo = Json::decode(Json::encode($orderinfo), true);
        }

        //如果在erp没获取到交易信息  则在客服系统重新获取一遍
        if (!empty($orderinfo['trade'])) {
            foreach ($orderinfo['trade'] as $key => $value) {
                if ($value['receiver_email'] == "" || $value['payer_email'] == "") {
                    $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->asArray()->one();
                    if (!empty($transactionRecord)) {
                        $orderinfo['trade'][$key]['receiver_email'] = $transactionRecord['receiver_email'];
                        $orderinfo['trade'][$key]['payer_email']    = $transactionRecord['payer_email'];
                        $orderinfo['trade'][$key]['payment_status'] = $transactionRecord['payment_status'];
                    }
                }
            }
        }
        //组装库存和在途数
        if (!empty($orderinfo['product'])) {
            $result = isset($orderinfo['wareh_logistics']) && isset($orderinfo['wareh_logistics']['warehouse']);
            foreach ($orderinfo['product'] as $key => $value) {
                $orderinfo['info']['product_weight'] += $value['product_weight'] * $value['quantity'];
                list($stock, $on_way_count) = [null, null];
                if ($result) {
                    $data         = [];
                    $stock        = isset($data['available_stock']) ? $data['available_stock'] : 0;
                    $on_way_count = isset($data['on_way_stock']) ? $data['on_way_stock'] : 0;
                }
                $orderinfo['product'][$key]['stock']        = $stock;
                $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;
            }
        }

        //获取售后信息
        $afterSalesOrders = [];
        if (!empty($orderinfo['info']['order_id'])) {
            $afterSalesOrders = AfterSalesOrder::getByOrderId(Platform::PLATFORM_CODE_SHOPEE, $orderinfo['info']['order_id']);
        }
        $countires = Country::getCodeNamePairsList();
        //获取仓库列表
        $warehouseList = Warehouse::getWarehouseListAll();
        //获取paypal账号信息
        $palPalList  = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }
        return $this->render('issueinfo/index', [
            'id'               => $id,
            'order_sn'         => $orderId,
            'returnsn'         => $issueId,
            'account_id'       => $accountid,
            'issueList'        => $issueList,
            'info'             => $orderinfo,
            'paypallist'       => $palPalList,
            'countries'        => $countires,
            'warehouseList'    => $warehouseList,
            'afterSalesOrders' => $afterSalesOrders,
            'platform'         => Platform::PLATFORM_CODE_SHOPEE,
        ]);
    }

    /**
     * 同意退款
     */
    public function actionConfirmreturn()
    {
        $returnsn   = Yii::$app->request->post('returnsn', 0);
        $account_id = Yii::$app->request->post('account_id', 0);

        if (empty($returnsn)) {
            die(json_encode([
                'code'    => 0,
                'message' => 'retern_sn不能为空',
            ]));
        }
        if (empty($account_id)) {
            die(json_encode([
                'code'    => 0,
                'message' => '账号ID不能为空',
            ]));
        }
        $result = OrderOtherSearch::ConfirmReturn($returnsn, $account_id);

        if ($result['code'] == 200) {
            die(json_encode([
                'code'    => 1,
                'message' => $result['msg'],
            ]));
        } else {
            die(json_encode([
                'code'    => 0,
                'message' => $result['msg'],
            ]));
        }
    }

    /**
     * 拒绝
     */
    public function actionDisputereturn()
    {
        $returnsn            = Yii::$app->request->post('returnsn', 0);
        $account_id          = Yii::$app->request->post('account_id', 0);
        $email               = Yii::$app->request->post('email', '');//Seller's email.
        $dispute_reason      = Yii::$app->request->post('dispute_reason', '');
        $dispute_text_reason = Yii::$app->request->post('dispute_text_reason', '');
        $images              = Yii::$app->request->post('images', '');//
        //转数组
        $images = json_decode($images);
        if (empty($returnsn)) {
            die(json_encode([
                'code'    => 0,
                'message' => 'retern_sn不能为空',
            ]));
        }
        if (empty($account_id)) {
            die(json_encode([
                'code'    => 0,
                'message' => '账号ID不能为空',
            ]));
        }
        if (empty($email)) {
            die(json_encode([
                'code'    => 0,
                'message' => '卖家邮箱不能为空',
            ]));
        }
        if (empty($dispute_reason)) {
            die(json_encode([
                'code'    => 0,
                'message' => '纠纷原因(ch)不能为空',
            ]));
        }
        if (empty($dispute_text_reason)) {
            die(json_encode([
                'code'    => 0,
                'message' => '纠纷原因(ch)不能为空',
            ]));
        }
        if (empty($images)) {
            die(json_encode([
                'code'    => 0,
                'message' => '图片不能为空',
            ]));
        }
        $result = OrderOtherSearch::DisputeReturn($returnsn, intval($account_id), $email, $dispute_reason, $dispute_text_reason, $images);
        return $result;
    }


    /**
     * 卖家上传纠纷证据图片
     */
    public function actionAddissueimage()
    {

        $id       = Yii::$app->request->post('id', 0);
        $issue_id = Yii::$app->request->post('issue_id', '');
        $file_url = [];
        $filePath = self::ISSUE_UPLOAD_PATH . date('Ym') . '/';
        if (!file_exists($filePath)) {
            @mkdir($filePath, 0777, true);
            @chmod($filePath, 0777);
        }
        if (empty($id)) {
            die(json_encode([
                'code'    => 201,
                'message' => 'ID不能为空',
            ]));
        }
        if (empty($issue_id)) {
            die(json_encode([
                'code'    => 201,
                'message' => '纠纷ID不能为空',
            ]));
        }
        $images = UploadedFile::getInstancesByName('image');
        foreach ($images as $image) {
            if (empty($image)) {
                die(json_encode([
                    'code'    => 201,
                    'message' => '图片上传失败',
                ]));
            }
            //图片格式
            if (!in_array($image->extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                die(json_encode([
                    'code'    => 201,
                    'message' => '图片格式错误',
                ]));
            }
            //图片大小，不超过2M
            if ($image->size > self::MAX_ISSUE_IMAGE_SIZE) {
                die(json_encode([
                    'code'    => 201,
                    'message' => '图片大小不能超过2M',
                ]));
            }
            $fileName = md5($image->baseName) . '.' . $image->extension;
            $file     = $filePath . $fileName;
            if (empty($fileName)) {
                return false;
            }
            $file_url [] = Yii::$app->request->hostInfo . ltrim($file, ".");
            if (!$image->saveAs($file)) {
                $this->_showMessage('保存图片失败', false);
            }

        }
        $image_list_arr = [];//图片地址
        $result         = OrderOtherSearch::addIssueImage($issue_id, $file_url);
        //获取图片信息
        $image_lists = ShopeeAttachment::find()->where(['returnsn' => $issue_id])->asArray()->one();
        if (!empty($image_lists)) {
            $image_urls = json_decode($image_lists['shopee_image_url'], true);
            if (!empty($image_urls)) {
                foreach ($image_urls as &$image_list) {
                    $image_list_arr[] = $image_list;
                }
            }
        }
        //不管有没有上传成功，都把文件删除
//        @unlink(realpath($file));
        if ($result === true) {
            die(json_encode([
                'code'      => 200,
                'message'   => '上传成功',
                'image_url' => json_encode($image_list_arr)
            ]));
        } else {
            die(json_encode([
                'code'    => 201,
                'message' => '上传失败',
            ]));
        }
    }

    /**
     * @author alpha
     * @desc /删除图片
     */
    public function actionDeleteimage()
    {
        $url              = trim($this->request->post('url'));
        $returnsn         = trim($this->request->post('returnsn'));
        $shopeeAttachment = ShopeeAttachment::findOne(['returnsn' => $returnsn]);
        $image_url_arr    = json_decode($shopeeAttachment['image_url']);
        $shopee_image_url = json_decode($shopeeAttachment['shopee_image_url']);
        foreach ($shopee_image_url as $k => $v) {
            if (trim($v) == trim($url)) {
                array_splice($shopee_image_url, $k, 1);
            }
        }
        foreach ($image_url_arr as $k => $v) {
            if (trim($v) == trim($url)) {
                array_splice($image_url_arr, $k, 1);
            }
        }
        $shopeeAttachment->image_url        = json_encode($image_url_arr);
        $shopeeAttachment->shopee_image_url = json_encode($shopee_image_url);
        $shopeeAttachment->save();
        $host = $this->request->hostInfo;
        $file = str_replace(Yii::$app->request->hostInfo, '.', $url);

        if (strpos($url, $host) === false) {
            $response = ['status' => 'error', 'info' => '参数错误。'];
        } else {
            $url = str_replace($host . '/', '', $url);
            if (file_exists($url)) {
                unlink($url);
                //删除文件
                @unlink(realpath($file));
                $response = ['status' => 'success', 'image_url' => json_encode($image_url_arr)];
            } else {
                $response = ['status' => 'error', 'info' => '图片不存在。'];
            }
        }
        die(json_encode($response));
    }

    /**
     * @author alpha
     * @desc 售后规则匹配
     */
    public function actionJudgerule()
    {
        //原因
        $platformOrderId       = Yii::$app->request->post('ordersn', '');
        $platformDisputeReason = Yii::$app->request->post('dispute_reason', '');

        $rules = AftersaleManage::getMatchAfterSaleOrderRule(Platform::PLATFORM_CODE_SHOPEE, $platformOrderId, $platformDisputeReason);
        $have  = 0;
        $data  = [];
        if (!empty($rules)) {
            $have = 1;
            $data = array_shift($rules);

            $allBasicConfig          = BasicConfig::getAllConfigData();
            $data['department_name'] = array_key_exists($data['department_id'], $allBasicConfig) ? $allBasicConfig[$data['department_id']] : '';
            $data['reason_name']     = array_key_exists($data['reason_id'], $allBasicConfig) ? $allBasicConfig[$data['reason_id']] : '';
        }

        $data['order_id'] = '';
        $orderInfo        = OrderOtherSearch::findOne(['platform_code' => Platform::PLATFORM_CODE_SHOPEE, 'platform_order_id' => $platformOrderId]);
        if (!empty($orderInfo)) {
            $data['order_id']       = $orderInfo->order_id;
            $data['order_amount']   = $orderInfo->total_price;
            $data['order_currency'] = $orderInfo->currency;
        } else {
            $data['order_id']       = '';
            $data['order_amount']   = 0;
            $data['order_currency'] = '';
        }
        die(json_encode([
            'code'    => 1,
            'message' => '找到匹配的规则',
            'data'    => $data,
            'have'    => $have,
        ]));
    }

    /**
     * 创建售后单
     */
    public function actionCreateaftersaleorder()
    {
        $issueId = Yii::$app->request->post('returnsn');
        $amount  = Yii::$app->request->post('amount');
        if (empty($issueId)) {
            die(json_encode([
                'code'    => 0,
                'message' => '纠纷ID为空',
            ]));
        }

        if (!isset($amount) || $amount < 0) {
            die(json_encode([
                'code'    => 0,
                'message' => '金额不能小于0',
            ]));
        }

        $issueInfo = ShopeeDisputeList::findOne(['returnsn' => $issueId]);
        if (empty($issueInfo)) {
            die(json_encode([
                'code'    => 0,
                'message' => '没有找到纠纷信息',
            ]));
        }
        $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_SHOPEE, $issueInfo->ordersn, $issueInfo->reason, '', $amount);
        if ($result) {
            die(json_encode([
                'code'    => 1,
                'message' => '成功',
            ]));
        } else {
            die(json_encode([
                'code'    => 0,
                'message' => '失败',
            ]));
        }
    }

    /**
     * 导出数据
     */
    public function actionExport()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
        $request    = Yii::$app->request->get();
        $returnsn   = isset($request['returnsn']) ? $request['returnsn'] : "";
        $ordersn    = isset($request['ordersn']) ? $request['ordersn'] : "";
        $order_id   = isset($request['order_id']) ? $request['order_id'] : "";
        $account_id = isset($request['account_id']) ? $request['account_id'] : "";
        $buyer_id   = isset($request['buyer_id']) ? $request['buyer_id'] : "";
        $is_deal    = isset($request['is_deal']) ? $request['is_deal'] : "";
        $reason     = isset($request['reason']) ? $request['reason'] : "";
        $status     = isset($request['status']) ? $request['status'] : "";
        $item_id    = isset($request['item_id']) ? $request['item_id'] : "";//
        $start_time = isset($request['start_time']) ? $request['start_time'] : '';
        $end_time   = isset($request['end_time']) ? $request["end_time"] : '';
        $json       = isset($request['ids']) ? $request['ids'] : [];//选中的行数据
        if (!empty($json)) {
            $query = ShopeeDisputeList::find();
            $query->select('t.*')
                ->from('{{%shopee_dispute_list}} t')
                ->where(['in', 't.id', $json]);
            $data = $query->asArray()
                ->all();
        } else {
            $query = ShopeeDisputeList::find();
            $query->select('t.*')
                ->from('{{%shopee_dispute_list}} t');

            if (!empty($start_time) && !empty($end_time)) {
                $query->andWhere(['between', 't.create_time', $start_time, $end_time]);
            } else if (!empty($start_time)) {
                $query->andWhere(['>=', 't.create_time', $start_time]);
            } else if (!empty($end_time)) {
                $query->andWhere(['<=', 't.create_time', $end_time]);
            }
            if ($buyer_id) {
                //
                $plat_order_id = OrderOtherSearch::getPlatOrderId($buyer_id);
                if (!empty($plat_order_id)) {
                    $query->andWhere(['in', 'l.ordersn', $plat_order_id]);
                }
            }
            if ($order_id) {
                $platform_order_id = OrderOtherSearch::getPlatform($order_id);
                if (!empty($platform_order_id)) {
                    $query->andWhere(['l.ordersn' => $platform_order_id]);
                }
            }
            $query->andFilterWhere(
                [
                    't.account_id' => $account_id,
                    't.reason'     => $reason,
                    't.status'     => $status,
                    't.returnsn'   => $returnsn,
                    't.ordersn'    => $ordersn,
                    't.is_deal'    => $is_deal,

                ]
            );

            $data = $query->asArray()
                ->all();
        }
        //标题数组
        $fieldArr = [
            'return id',
            '账号',
            '订单号',
            '平台订单号',
            '买家ID',
            '纠纷原因',
            '回复截止日期',
            '开始时间',
            '退款金额',
            '纠纷状态',
            '是否处理',
        ];

        $orderIds = [];
        foreach ($data as $key => $model) {
            $orderIds[] = $model['ordersn'];
        }
        //获取订单ID和买家ID
        $orderIdAndBuyerIds = OrderOtherSearch::getOrderIdAndBuyerId($orderIds);
        //导出数据数组
        $dataArr = [];
        foreach ($data as $item) {
            $item['order_id'] = $orderIdAndBuyerIds[$item['ordersn']]['order_id'];
            $item['buyer_id'] = $orderIdAndBuyerIds[$item['ordersn']]['buyer_id'];
            $is_deal          = [1 => '未处理', 2 => '已处理'];
            //导出数据数组
            $dataArr[] = [
                $item['returnsn'],//
                ShopeeDisputeList::getAccountName($item['account_id']),
                $item['order_id'],
                $item['ordersn'],
                $item['buyer_id'],
                $item['reason'],
                date('Y-m-d H:i:s', $item['due_date']),
                date('Y-m-d H:i:s', $item['create_time']),
                $item['refund_amount'] . '(' . $item['currency'] . ')',
                $item['status'],
                $is_deal[$item['is_deal']],
            ];
        }
        VHelper::exportExcel($fieldArr, $dataArr, 'shopeedispute' . date('Y-m-d'));
    }


    /**
     * 更新纠纷信息
     */
    public function actionUpdateissueinfo()
    {
        $returnsn = !empty($_REQUEST['returnsn']) ? trim($_REQUEST['returnsn']) : '';

        if (empty($returnsn)) {
            die(json_encode([
                'code'    => 0,
                'message' => '退款退货编号不能为空',
            ]));
        }

        $shopeeIssue = ShopeeDisputeList::findOne(['returnsn' => $returnsn]);
        $accountId   = $shopeeIssue['account_id'];
        //调用接口  获取改订单详情
        $returnDetail = OrderOtherSearch::updateReturnStatus($accountId);

        if (empty($returnDetail['returns'])) {
            die(json_encode([
                'code'    => 0,
                'message' => '暂无更新信息',
            ]));
        }
        foreach ($returnDetail['returns'] as $issue) {
            if (empty($issue['returnsn'])) {
                continue;
            }
            if ($issue['returnsn'] == $returnsn) {

                $shopeeIssue->status                 = !empty($issue['status']) ? $issue['status'] : '';
                $shopeeIssue->due_date               = !empty($issue['due_date']) ? $issue['due_date'] : '';
                $shopeeIssue->update_time            = !empty($issue['update_time']) ? $issue['update_time'] : 0;
                $shopeeIssue->amount_before_discount = !empty($issue['amount_before_discount']) ? $issue['amount_before_discount'] : 0;
                $shopeeIssue->text_reason            = !empty($issue['text_reason']) ? $issue['text_reason'] : '';
                $shopeeIssue->needs_logistics        = !empty($issue['needs_logistics']) ? 1 : 0;
                $shopeeIssue->refund_amount          = !empty($issue['refund_amount']) ? $issue['refund_amount'] : 0;
                $shopeeIssue->tracking_number        = !empty($issue['tracking_number']) ? $issue['tracking_number'] : '';
                $shopeeIssue->currency               = !empty($issue['currency']) ? $issue['currency'] : '';
                $shopeeIssue->reason                 = !empty($issue['reason']) ? $issue['reason'] : '';
                $shopeeIssue->dispute_text_reason    = !empty($issue['dispute_text_reason']) ? json_encode($issue['dispute_text_reason']) : '';
                $shopeeIssue->create_time            = !empty($issue['create_time']) ? $issue['create_time'] : 0;
                $shopeeIssue->returnsn               = !empty($issue['returnsn']) ? $issue['returnsn'] : '';
                $shopeeIssue->ordersn                = !empty($issue['ordersn']) ? $issue['ordersn'] : '';
                $shopeeIssue->user                   = !empty($issue['user']) ? json_encode($issue['user']) : '';
                $shopeeIssue->dispute_reason         = !empty($issue['dispute_reason']) ? json_encode($issue['dispute_reason']) : '';
                $shopeeIssue->items                  = !empty($issue['items']) ? json_encode($issue['items']) : '';
                $shopeeIssue->images                 = !empty($issue['images']) ? json_encode($issue['images']) : '';
                $shopeeIssue->save();
                // 更改纠纷订单状态
                if ($shopeeIssue->save()) {
                    die(json_encode([
                        'code'    => 1,
                        'message' => '更新退款退货纠纷成功',
                        'data'    => ['returnsn' => $returnsn],
                    ]));
                } else {
                    die(json_encode([
                        'code'    => 0,
                        'message' => '更新退款退货纠纷失败',
                        'data'    => ['returnsn' => $returnsn],
                    ]));
                }
            } else {
                break;
            }
        }
    }

    /**
     * 标记处理
     */
    public function actionMarkdeal()
    {
        $returnsn = !empty($_REQUEST['returnsn']) ? trim($_REQUEST['returnsn']) : '';
        if (empty($returnsn)) {
            die(json_encode([
                'code'    => 0,
                'message' => '退款退货编号不能为空',
            ]));
        }
        $shopeeIssue          = ShopeeDisputeList::findOne(['returnsn' => $returnsn]);
        $shopeeIssue->is_deal = 2;
        if ($shopeeIssue->save()) {
            die(json_encode([
                'code'    => 1,
                'message' => '标记处理成功',
            ]));
        } else {
            die(json_encode([
                'code'    => 0,
                'message' => '标记处理失败',
            ]));
        }
    }

    /**
     * 获取订单物流信息
     */
  /*  public function actionGetorderlogistics()
    {
        $returnsn = !empty($_REQUEST['returnsn']) ? trim($_REQUEST['returnsn']) : '';
        if (empty($returnsn)) {
            die(json_encode([
                'code'    => 0,
                'message' => '退款退货编号不能为空',
            ]));
        }
        $shopeeIssue = ShopeeDisputeList::findOne(['returnsn' => $returnsn]);
        $accountId   = $shopeeIssue['account_id'];
        $ordersn     = $shopeeIssue['ordersn'];
        //调用接口  获取改订单详情
        $accountId=835;
        $ordersn='18070202440746H';
        $tracking_no='MY1870170579421';
        $tracking_data=OrderOtherSearch::getLogisticsMessage($accountId, $ordersn,$tracking_no);
        //先查询 是否有物流轨迹
        $orderLogistics = ShopeeOrderLogistics::findOne(['ordersn' => $ordersn]);
        if (empty($orderLogistics)) {
            $orderLogistics = OrderOtherSearch::getOrderLogistics($accountId, $ordersn);
        }
        if (!empty($orderLogistics)) {
            die(json_encode([
                'code'           => 1,
                'message'        => '获取物流信息成功',
                'orderLogistics' => $orderLogistics
            ]));
        } else {
            die(json_encode([
                'code'    => 0,
                'message' => '暂无订单物流信息',
            ]));
        }
    }*/
}
