<?php

namespace app\modules\systems\models;

use Yii;

class ErpOrderApi extends ErpApiAbstract
{

    public $requestUri = '/order/index/method/';

    /**
     * @desc 获取订单及相关信息
     * @param unknown $platformCode
     * @param unknown $accountName
     */
    public function getOrderStack($platformCode, $platformOrderId = null, $systemsOrderId = null)
    {
        $params = ['platformCode' => $platformCode, 'orderId' => $platformOrderId, 'systemOrderId' => $systemsOrderId];
        $this->setApiMethod('Mailrelatedorder')
            ->sendRequest($params, 'get');
        //var_dump($this->setApiMethod('Mailrelatedorder'));exit;
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    public function getOrderStackByCustom($platformCode, $fields)
    {
        $params = ['platformCode' => $platformCode];
        if (!is_array($fields))
            return false;
        foreach ($fields as $fieldK => $fieldV) {
            $params[$fieldK] = $fieldV;
        }
        $this->setApiMethod('Mailrelatedorder')
            ->sendRequest($params, 'get');

        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    public function getEbayOrderStack($fields)
    {
        if (!is_array($fields))
            return false;
        $params = array();
        foreach ($fields as $fieldK => $fieldV) {
            $params[$fieldK] = $fieldV;
        }
        $this->setApiMethod('GetEbayOrderStack')
            ->sendRequest($params, 'get');

        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    public function getProductImageThub($sku)
    {
        $params = ['sku' => $sku];
        $this->setApiMethod('getProductImageThub')
            ->sendRequest($params, 'get');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            //$response->image_url = str_replace('10.170.32.66', '120.24.249.36', $response->image_url);
            $response->image_url = str_replace('10.170.32.66', 'images.yibainetwork.com', $response->image_url);
            $response->image_url = str_replace('192.168.9.200', 'images.yibainetwork.com', $response->image_url);
            return $response;
        }
        return false;
    }

    //添加订单备注
    public function getAddremark($order_id, $remark, $userId, $username)
    {
        $params = ['order_id' => $order_id, 'remark' => $remark];
        $this->setApiMethod('AddRemark')
            ->sendRequest($params, 'get');

        if ($this->isSuccess()) {

            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /*
     * @desc 删除订单备注
     * */

    public function removeOrderRemark($id)
    {
        $params = ['id' => $id];
        $this->setApiMethod('RemoveRemark')
            ->sendRequest($params, 'get');

        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /*
     * @desc 添加出货备注
     * */

    public function Addprintremark($orderId, $platform, $print_remark)
    {
        $params = ['order_id' => $orderId, 'platform_code' => $platform, 'print_remark' => $print_remark];
        $this->setApiMethod('Addprintremark')
            ->sendRequest($params, 'get');

        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取订单列表
     */
    public function getOrders($platform_code, $platform_order_id, $system_order_id, $buyer_id, $ship_name, $email, $ship_phone, $item_id, $package_id, $track_number, $order_number, $paypal_id = null, $account_id = null, $complete_status = null, $sku = null)
    {

        $params = ['platform_code' => $platform_code, 'platform_order_id' => $platform_order_id, 'system_order_id' => $system_order_id, 'buyer_id' => $buyer_id, 'ship_name' => $ship_name, 'email' => $email, 'ship_phone' => $ship_phone, 'item_id' => $item_id, 'package_id' => $package_id, 'track_number' => $track_number, 'order_number' => $order_number, 'paypal_id' => $paypal_id, 'account_id' => $account_id, 'complete_status' => $complete_status, 'sku' => $sku];
        $this->setApiMethod('getSearchOrders')
            ->sendRequest($params, 'get');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取订单列表
     */
    public function getOrdersByPage($platform_code, $platform_order_id, $system_order_id, $buyer_id, $ship_name, $email, $ship_phone, $item_id, $package_id, $track_number, $order_number, $paypal_id = null, $account_id = null, $complete_status = null, $sku = null, $page_cur = 0, $page_size = 0)
    {

        $params = ['platform_code' => $platform_code, 'platform_order_id' => $platform_order_id, 'system_order_id' => $system_order_id, 'buyer_id' => $buyer_id, 'ship_name' => $ship_name, 'email' => $email, 'ship_phone' => $ship_phone, 'item_id' => $item_id, 'package_id' => $package_id, 'track_number' => $track_number, 'order_number' => $order_number, 'paypal_id' => $paypal_id, 'account_id' => $account_id, 'complete_status' => $complete_status, 'sku' => $sku, 'page_cur' => $page_cur, 'page_size' => $page_size];
        $this->setApiMethod('getSearchOrders')
            ->sendRequest($params, 'get');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /** 获取交易号 用于退款业务 * */
    public function getTransactionId($platform_code, $order_id)
    {
        $params = ['platform_code' => $platform_code, 'order_id' => $order_id];
        $this->setApiMethod('getTransactionId')->sendRequest($params, 'get');

        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }

        return false;
    }

    /** 获取交易信息 用于退款业务 * */
    public function getTransactionInfo($transaction_id)
    {
        $params = ['transaction_id' => $transaction_id];
        $this->setApiMethod('getTransactionInfo')->sendRequest($params, 'get');

        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }

        return false;
    }

    /**
     * @desc 获取买家历史订单
     * @param unknown $platformCode
     * @param unknown $buyerId
     */
    public function getHistoryOrders($platformCode, $buyerId, $email = '', $accountId = "")
    {
        $params = ['platformCode' => $platformCode, 'buyerId' => $buyerId, 'email' => $email, 'accountId' => $accountId];
        $this->setApiMethod('Historicalorder')
            ->sendRequest($params, 'get');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * @desc 添加退货退款信息
     * @param unknown $platformCode
     * @param unknown $buyerId
     */
    public function setRefund($platformCode, $data)
    {
        $data['platform_code'] = $platformCode;
        $this->setApiMethod('Setrefund')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
//            $response = $this->getResponse();
//
//            var_dump($response);die;
            return true;
        }
        return false;
    }

    /**
     * @author alpha
     * @desc 审核退货单 推送erp
     * @param $data
     * @return bool
     */
    public function setReturn($data)
    {
        $this->setApiMethod('SendReturn')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            return true;
        }
        return false;
    }

    /**
     * 退货退款时修改订单状态
     */
    public function setOrderStatus($data)
    {
        $params = ['data' => $data];
        $this->setApiMethod('SetOrderStatus')
            ->sendRequest($params, 'post');
        if ($this->isSuccess())
            return true;
        return false;
    }

    /**
     * @desc 人为取消订单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function cancelOrder($platformCode, $orderId, $platform_order_id = null, $remark)
    {

        $params             = ['platformCode' => $platformCode, 'orderId' => $orderId, 'platformOrderId' => $platform_order_id, 'remark' => $remark];
        $user               = \Yii::$app->user->getIdentity();
        $params['updateBy'] = $user->login_name;
        $this->setApiMethod('cancelOrder')
            ->sendRequest($params, 'get');
        if ($this->isSuccess())
            return true;
        return false;
    }

    /*
     * @desc 系统取消订单
     * */

    public function systemCancelOrder($platformCode, $orderId, $platform_order_id = null)
    {
        $params = ['platformCode' => $platformCode, 'orderId' => $orderId, 'platformOrderId' => $platform_order_id];

        $params['updateBy'] = 'system';
        $this->setApiMethod('cancelOrder')
            ->sendRequest($params, 'get');
        if ($this->isSuccess())
            return true;
        return false;
    }

    /**
     * @desc 暂扣订单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function holdOrder($platformCode, $orderId, $remark)
    {
        $params = ['platformCode' => $platformCode, 'orderId' => $orderId, 'remark' => $remark];
        $this->setApiMethod('holdOrder')
            ->sendRequest($params, 'get');
        if ($this->isSuccess())
            return true;
        return false;
    }

    /**
     * @desc 暂扣订单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function cancelHoldOrder($platformCode, $orderId)
    {
        $params = ['platformCode' => $platformCode, 'orderId' => $orderId];
        $this->setApiMethod('cancelHoldOrder')
            ->sendRequest($params, 'get');
        if ($this->isSuccess())
            return true;
        return false;
    }

    /**
     * @desc 修改订单发货地址
     * @param unknown $data
     * @return boolean
     */
    public function editShippingAddress($data)
    {
        $this->setApiMethod('Editorderbasicmessage')
            ->sendRequest($data, 'post');
        if ($this->isSuccess())
            return  $this->getResponse();
        return  $this->getExcptionMessage();
    }

    /**
     * @author alpha
     * @desc  修改订单产品
     * @param $data
     * @return unknown|null
     */
    public function editOrderProduct($data)
    {
        $this->setApiMethod('Editorderskuicmessage')
            ->sendRequest($data, 'post');
        if ($this->isSuccess())
            return  $this->getResponse();
        return  $this->getExcptionMessage();
    }

    /**
     * @author alpha
     * @desc 修改订单仓库物流
     * @param $data
     * @return unknown|null
     */
    public function editOrderWarehouse($data)
    {
        $this->setApiMethod('Editorderlogisticmessage')
            ->sendRequest($data, 'post');
        if ($this->isSuccess())
            return  $this->getResponse();
        return  $this->getExcptionMessage();
    }

    /**
     * @desc 重寄订单
     * @param unknown $data
     * @return boolean
     */
    public function redirectOrder($data)
    {
        $this->setApiMethod('redirectOrder')
            ->sendRequest($data, 'post');
        if ($this->isSuccess())
            return true;
        return false;
    }

//    public function getCertainInfo($order_id,$platform_code)
//    {
//        $params = ['order_id' => $order_id, 'platform_code' => $platform_code];
//        $this->setApiMethod('getCertainInfo')
//            ->sendRequest($params, 'get');
//        if ($this->isSuccess())
//        {
//            $response = $this->getResponse();
//            return $response;
//        }
//        return false;
//    }

    /**
     * @desc 获取订单利润
     * @param unknown $data
     * @return boolean
     */
    public function getOrderProfitByOrderId($data)
    {
        $this->setApiMethod('getOrderProfitByOrderId')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取预计重寄费用
     * @param unknown $data
     * @return boolean
     */
    public function getPreRedirectCost($data)
    {
        $this->setApiMethod('getPreRedirectCost')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
//            var_dump($response);exit;
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取重寄费用
     * @param unknown $data
     * @return boolean
     */
    public function getRedirectCost($data)
    {
        $this->setApiMethod('getRedirectCost')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
//            var_dump($response);exit;
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取重寄费用
     * @param unknown $data
     * @return boolean
     */
    public function getRedirectCostByOrderId($data)
    {
        $this->setApiMethod('getRedirectCostByOrderId')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
//            var_dump($response);exit;
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取指定订单
     * @param unknown $data
     * @return boolean
     */
    public function getOrder($data)
    {
        $this->setApiMethod('getOrder')
            ->sendRequest($data, 'get');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
//            var_dump($response);exit;
            return $response;
        }
        return false;
    }

    /**
     * @desc 根据sku获取订单ids
     * @param unknown $data
     * @return boolean
     */
    public function getOrderIdsBySku($data)
    {
        $this->setApiMethod('getOrderIdsBySku')
            ->sendRequest($data, 'get');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
//            var_dump($response);exit;
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取amazon订单的金额信息
     * @param unknown $data
     * @return boolean
     */
    public function getAmazonamtinfo($data)
    {
        $this->setApiMethod('getAmazonamtinfo')
            ->sendRequest($data, 'get');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
//            var_dump($response);exit;
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取指定订单
     * @param unknown $data
     * @return boolean
     */
    public function getOrderByPlatformOrderID($data)
    {
        $this->setApiMethod('getOrderByPlatformOrderID')
            ->sendRequest($data, 'get');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
//            var_dump($response);exit;
            return $response;
        }
        return false;
    }

    /**
     * 通过sku获取ebay评价中的item_id
     */
    public function getEbayFeedBackItemIdBySku($data)
    {
        $this->setApiMethod('getEbayFeedBackItemIdBySku')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取ebay评价订单信息
     * 通过多个平台订单ID，获取多个订单信息
     */
    public function getEbayFeedBackOrderInfos($data)
    {
        $this->setApiMethod('getEbayFeedBackOrderInfos')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取ebay订单信息
     * 通过平台订单ID和交易ID，一次性获取多个订单信息
     */
    public function getEbayOrderInfos($data)
    {
        $this->setApiMethod('getEbayOrderInfos')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取ebay评价产品信息
     * 通过多个item_id，获取多个产品信息
     */
    public function getEbayFeedBackItemInfos($data)
    {
        $this->setApiMethod('getEbayFeedBackItemInfos')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取售后导出退款相关订单数据
     * @param type $data
     * @return boolean
     * @author allen <2018-1-8>
     */
    public function getCertainInfo($data)
    {
        $this->setApiMethod('getCertainInfo')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取售后导出重寄相关订单数据
     * @param type $data
     * @return boolean
     * @author allen <2018-1-10>
     */
    public function getResendDataInfo($data)
    {
        $this->setApiMethod('getResendDataInfo')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * @desc 新增发票
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function setOrderInvoice($data)
    {
        $this->setApiMethod('setOrderInvoice')
            ->sendRequest($data, 'post');
        $result = $this->_responseBody;
        $result = json_decode($result);
        return $result->invoice;
    }

    /**
     * 给订单留言添加备注
     * @param $data
     */
    public function setMarkprocessed($data)
    {
        $this->setApiMethod('Setnoteread')
        ->sendRequest($data, 'post');
        $result = $this->_responseBody;
        $result = json_decode($result);
        return $result;
    }

    /**
     * 客服取消操作 【添加客户到黑名单/取消客户黑名单】
     * 保存操作日志
     * @author allen <2018-02-08>
     */
    public function blackOptions($data)
    {
        $this->setApiMethod('blackOptions')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 更新或添加GBC数据
     */
    public function updateGbcData($data)
    {
        $this->setApiMethod('updateGbcData')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     *
     * @param type $data
     * @return boolean
     * @author allen <2018-03-12>
     */
    public function orderbindtransaction($data)
    {
        $this->setApiMethod('orderbindtransaction')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 删除记录
     * @param $data
     * @return bool|null
     */
    public function orderunbindtransaction($data)
    {
        $this->setApiMethod('orderunbindtransaction')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }


    /**
     * 客服取消操作 【添加客户到黑名单/取消客户黑名单】
     * 保存操作日志
     * @author allen <2018-02-08>
     */
    public function syncAmazonReviewProcess($data)
    {
        $this->setApiMethod('syncAmazonReviewProcess')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取汇率
     * @param type $data
     * @return type
     * @author allen <2018-03-31>
     */
    public function getExchangeRate($data)
    {
        $this->setApiMethod('getExchangeRate')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取订单利润
     * @param type $data
     * @return type
     * @author allen <2018-04-18>
     */
    public function getProfit($data)
    {
        $this->setApiMethod('getProfit')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取产品平均成本
     * @param type $data
     * @return boolean
     * @author allen <2018-04-18>
     */
    public function getAvgCost($data)
    {
        $this->setApiMethod('getAvgCost')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 增加Gbc黑名单
     * @param type $data
     * @return type
     * @author zhangchu <2018-06-07>
     */
    public function addGbc($data)
    {
        $this->setApiMethod('addGbc')
            ->sendRequest($data, 'post');

        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * @desc 编辑及删除Gbc黑名单
     * @return \yii\base\string
     */
    public function editGbc($data)
    {
        $this->setApiMethod('editGbc')
            ->sendRequest($data, 'post');

        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;

    }

    /**
     * 批量设置优先配库
     * @param $data
     * @return bool|null
     */
    public function SetPriorityStatus($data)
    {
        $this->setApiMethod('SetPriorityStatus')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 永久作废恢复
     * @param $data
     * @return bool|null
     */
    public function Ordertoinit($data)
    {
        $this->setApiMethod('Ordertoinit')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 推送订单到仓库
     * @param $data
     * @return bool|null
     */
    public function batchsendorde($data)
    {
        $this->setApiMethod('batchsendorde')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 手动给订单配库
     * @param $data
     * @return bool|null
     */
    public function batchallotstock($data)
    {
        $this->setApiMethod('batchallotstock')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }


    /**
     * 更新订单状态
     */
    public function updateOrderStatus($data)
    {
        $this->setApiMethod('updateOrderStatus')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * @author alpha
     * @desc 忽略发货
     * @param $data
     * @return bool|null
     */
    public function Ignoreitem($data)
    {
        $this->setApiMethod('Ignoreitem')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }

    /**
     * @author alpha
     * @desc 恢复发货
     * @param $data
     * @return bool|null
     */
    public function Recoveritem($data)
    {
        $this->setApiMethod('Recoveritem')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }
    /**
     * @author alpha
     * @desc 国内退件是否发货
     * @param $data
     * @return bool|null
     */
    public function Whethership($data)
    {
        $this->setApiMethod('Whethership')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }
    /**
     * @author alpha
     * @desc 驳回订单
     * @param $data
     * @return bool|null
     */
    public function Refuseorder($data)
    {
        $this->setApiMethod('Refuseorder')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }
    /**
     * @author alpha
     * @desc 补款单同步到erp
     * @param $data
     * @return bool|null
     */
    public function Makeup($data)
    {
        $this->setApiMethod('makeup')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }
	
    /**
     * @author alpha
     * @desc 海外仓
     * @param $data
     * @return bool|null
     */
    public function Reviewaftersaleorder($data)
    {
        $this->setApiMethod('Reviewaftersaleorder')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }

    /**
     * @author alpha
     * @desc 永久作废，
     * @param $data
     * @return bool|null
     */
    public function cancelShippedOrder($data)
    {
        $this->setApiMethod('cancelShippedOrder')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }
    /**
     * @author alpha
     * @desc 暂扣订单接口。
     * @param $data
     * @return bool|null
     */
    public function holdShippedOrder($data)
    {
        $this->setApiMethod('holdShippedOrder')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }

    /**
     * @author alpha
     * @desc 海外仓
     * @param $data
     * @return bool|null
     */
    public function cancelaftersaleorder($data)
    {
        $this->setApiMethod('cancelaftersaleorder')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }
    /**
     * @author vc
     * @desc 拆单API
     * @param $data
     * @return bool|null
     */
    public function separateordermessage($data)
    {
        $this->setApiMethod('separateordermessage')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return  $this->getExcptionMessage();
    }
}
