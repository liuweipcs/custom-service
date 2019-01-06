<?php

namespace app\modules\mails\controllers;

use app\modules\mails\models\ShopeeCancellationList;
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

class ShopeecancellationController extends Controller
{
    const ISSUE_UPLOAD_PATH = './uploads/issue/';
    //最大纠纷图片大小2M
    const MAX_ISSUE_IMAGE_SIZE = 2097152;

    /**
     * 列表
     */
    public function actionList()
    {
        $params       = \Yii::$app->request->getBodyParams();
        $model        = new ShopeeCancellationList();
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
            $this->_showMessage('ID不能为空', false);
        }

        $cancellationList = ShopeeCancellationList::find()->where(['id' => intval($id)])->asArray()->one();
        if (empty($cancellationList)) {
            $this->_showMessage('没有找到交易信息', false);
        }
        //平台订单ID
        $orderId = $cancellationList['ordersn'];
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
        $accountid = $cancellationList['account_id'];
        return $this->render('cancellationinfo/handle', [
            'id'               => $id,
            'order_id'         => $orderId,
            'cancellationList' => $cancellationList,
            'info'             => $orderinfo,
            'account_id'       => $accountid,
            'paypallist'       => $palPalList,
            'countries'        => $countires,
            'warehouseList'    => $warehouseList,
            'afterSalesOrders' => $afterSalesOrders,
            'platform'         => Platform::PLATFORM_CODE_SHOPEE,
        ]);
    }


    /**
     * 交易详情
     */
    public function actionDetails()
    {
        $this->isPopup = true;

        //获取纠纷ID
        $id               = Yii::$app->request->get('id');
        $cancellationList = ShopeeCancellationList::find()->where(['id' => $id])->asArray()->one();
        if (empty($cancellationList)) {
            $this->_showMessage('没有找到交易信息', false);
        }
        $orderId = $cancellationList['ordersn'];
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
        return $this->render('cancellationinfo/index', [
            'id'               => $id,
            'order_id'         => $orderId,
            'cancellationList' => $cancellationList,
            'info'             => $orderinfo,
            'paypallist'       => $palPalList,
            'countries'        => $countires,
            'warehouseList'    => $warehouseList,
            'afterSalesOrders' => $afterSalesOrders,
            'platform'         => Platform::PLATFORM_CODE_SHOPEE,
        ]);
    }


    /**
     * 同意方案
     */
    public function actionAgreeissuesolution()
    {
        //传订单号
        $ordersn    = Yii::$app->request->post('ordersn', 0);
        $account_id = Yii::$app->request->post('account_id', 0);


        if (empty($ordersn)) {
            die(json_encode([
                'code'    => 0,
                'message' => '订单号不能为空',
            ]));
        }
        if (empty($account_id)) {
            die(json_encode([
                'code'    => 0,
                'message' => '账号ID不能为空',
            ]));
        }

        $result = OrderOtherSearch::AcceptBuyerCancellation($ordersn, intval($account_id));

        if ($result === true) {
            die(json_encode([
                'code'    => 1,
                'message' => '同意买家取消交易成功',
            ]));
        } else {
            die(json_encode([
                'code'    => 0,
                'message' => '同意买家取消交易失败',
            ]));
        }
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
        $rules                 = AftersaleManage::getMatchAfterSaleOrderRule(Platform::PLATFORM_CODE_SHOPEE, $platformOrderId, $platformDisputeReason);
        $have                  = 0;
        $data                  = [];
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
        $ordersn               = Yii::$app->request->post('ordersn');
        $amount                = Yii::$app->request->post('amount');
        $platformDisputeReason = '客户要求取消';//填写固定原因
        if (empty($ordersn)) {
            die(json_encode([
                'code'    => 0,
                'message' => '订单编号不能为空',
            ]));
        }

        if (!isset($amount) || $amount < 0) {
            die(json_encode([
                'code'    => 0,
                'message' => '金额不能小于0',
            ]));
        }

        $cancellationInfo = ShopeeCancellationList::findOne(['ordersn' => $ordersn]);
        if (empty($cancellationInfo)) {
            die(json_encode([
                'code'    => 0,
                'message' => '没有找到交易信息',
            ]));
        }
        $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_SHOPEE, $ordersn, $platformDisputeReason, '', $amount);
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
     * 拒绝协商方案
     */
    public function actionRefuseissuesolution()
    {
        //传订单号
        $ordersn    = Yii::$app->request->post('ordersn', 0);
        $account_id = Yii::$app->request->post('account_id', 0);

        if (empty($ordersn)) {
            die(json_encode([
                'code'    => 0,
                'message' => '订单号不能为空',
            ]));
        }
        if (empty($account_id)) {
            die(json_encode([
                'code'    => 0,
                'message' => '账号ID不能为空',
            ]));
        }

        $result = OrderOtherSearch::RejectBuyerCancellation($ordersn, intval($account_id));

        if ($result === true) {
            die(json_encode([
                'code'    => 1,
                'message' => '拒绝方案成功',
            ]));
        } else {
            die(json_encode([
                'code'    => 0,
                'message' => $result,
            ]));
        }
    }

    /**
     * 更新纠纷信息
     */
    public function actionUpdateissueinfo()
    {
        $ordersn = !empty($_REQUEST['ordersn']) ? trim($_REQUEST['ordersn']) : '';

        if (empty($ordersn)) {
            die(json_encode([
                'code'    => 0,
                'message' => '平台订单号不能为空',
            ]));
        }

        $cancellationInfo = ShopeeCancellationList::findOne(['ordersn' => $ordersn]);
        $accountId        = $cancellationInfo['account_id'];
        //调用接口  获取改订单详情
        $orderDetail = OrderOtherSearch::getOrderDetail($ordersn, $accountId);
        if (empty($orderDetail)) {
            die(json_encode([
                'code'    => 0,
                'message' => '获取订单详情失败',
            ]));
        }
        // 更改订单状态
        $cancellationInfo->order_status = $orderDetail[$ordersn]['order_status'];
        $cancellationInfo->is_deal      = 2;//更新状态已处理
        if ($cancellationInfo->save()) {
            die(json_encode([
                'code'    => 1,
                'message' => '成功',
                'data'    => ['id' => $cancellationInfo['id']],
            ]));
        } else {
            die(json_encode([
                'code'    => 0,
                'message' => '更新失败',
            ]));
        }
    }

    /**
     * 标记处理
     */
    public function actionMarkdeal()
    {
        //订单号
        $ordersn = !empty($_REQUEST['ordersn']) ? trim($_REQUEST['ordersn']) : '';
        if (empty($ordersn)) {
            die(json_encode([
                'code'    => 0,
                'message' => '平台订单号不能为空',
            ]));
        }
        $cancellationInfo          = ShopeeCancellationList::findOne(['ordersn' => $ordersn]);
        $cancellationInfo->is_deal = 2;
        if ($cancellationInfo->save()) {
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

}
