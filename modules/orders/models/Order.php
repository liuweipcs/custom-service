<?php

namespace app\modules\orders\models;

use app\modules\accounts\models\Platform;
use app\modules\systems\models\ErpOrderApi;

class Order extends OrderModel {

    const COMPLETE_STATUS_INIT = 0;        //初始化状态
    const COMPLETE_STATUS_NORMAL = 1;        //正常订单
    const COMPLETE_STATUS_ABNORMAL = 5;        //异常订单
    const COMPLETE_STATUS_STOCKOUT = 10;       //缺货订单
    const COMPLETE_STATUS_GOODS_PREPARE = 13;       //已备货订单
    const COMPLETE_STATUS_WAITTING_SHIP = 15;       //待发货订单
    const COMPLETE_STATUS_EXPIRED = 17;       //超期订单
    const COMPLETE_STATUS_PARTIAL_SHIP = 19;       //部分发货订单
    const COMPLETE_STATUS_SHIPPED = 20;       //已发货订单
    const COMPLETE_STATUS_HOLD = 25;       //暂扣订单
    /*     const COMPLETE_STATUS_PARTIAL_REFUND   = 30;       //部分退款订单
      const COMPLETE_STATUS_ALL_REFUND       = 35;       //全部退款订单 */
    const COMPLETE_STATUS_CANCELED = 40;       //已取消订单
    const COMPLETE_STATUS_COMPLETED = 45;       //已完成订单
    const COMPLETE_STATUS_TONGTU = 99;        //通途订单
    const COMPLETE_STATUS_PAY = 119;        //待检测paypal账号    
    const ORDER_HOLD_YES = 1;      //订单已暂扣
    const ORDER_HOLD_NO = 0;       //订单未暂扣
    const ORDER_EXPIRED_YES = 1;   //订单已过期
    const ORDER_EXPIRED_NO = 0;    //订单未过期
    const ORDER_TYPE_NORMAL = 1;        //普通订单
    const ORDER_TYPE_MERGE_MAIN = 2;        //合并后的订单
    const ORDER_TYPE_MERGE_RES = 3;        //被合并的订单
    const ORDER_TYPE_SPLIT_MAIN = 4;        //拆分的主订单
    const ORDER_TYPE_SPLIT_CHILD = 5;        //拆分后的子订单
    const ORDER_TYPE_REDIRECT_MAIN = 6;        //普通订单[已创建过重寄单]
    const ORDER_TYPE_REDIRECT_ORDER = 7;        //重寄后的订单
    const ORDER_REFUND_NO = 0;        //未退款
    const ORDER_REFUND_PARTIAL = 1;        //订单部分退款
    const ORDER_REFUND_ALL = 2;        //订单全部退款

    public $exceptionMessage = null;

    public static function getOrderCompleteStatus() {
        return [
            self::COMPLETE_STATUS_INIT => '初始化',
            self::COMPLETE_STATUS_NORMAL => '正常订单',
            self::COMPLETE_STATUS_ABNORMAL => '异常订单',
            self::COMPLETE_STATUS_STOCKOUT => '缺货订单',
            self::COMPLETE_STATUS_GOODS_PREPARE => '已备货订单',
            self::COMPLETE_STATUS_WAITTING_SHIP => '待发货订单',
            self::COMPLETE_STATUS_EXPIRED => '超期订单',
            self::COMPLETE_STATUS_PARTIAL_SHIP => '部分发货订单',
            self::COMPLETE_STATUS_SHIPPED => '已发货订单',
            self::COMPLETE_STATUS_HOLD => '<b style="color:orange;">暂扣订单</b>',
            self::COMPLETE_STATUS_CANCELED => '<b style="color:red;">已取消订单</b>',
            self::COMPLETE_STATUS_COMPLETED => '已完成订单',
            99 => '通途处理订单',
            119=>'待检测paypal账号',
        ];
    }

    public static function getOrderCompleteDiffStatus($key) {
        switch ($key) {
            case self::COMPLETE_STATUS_INIT:
                return "<span style='color:green;'>初始化</span>";
                break;
            case self::COMPLETE_STATUS_NORMAL:
                return "<span style='color:green;'>正常</span>";
                break;
            case self::COMPLETE_STATUS_ABNORMAL:
                return "<span style='color:#FFA500;'>异常</span>";
                break;
            case self::COMPLETE_STATUS_STOCKOUT:
                return "<span style='color:red;'>缺货</span>";
                break;
            case self::COMPLETE_STATUS_GOODS_PREPARE:
                return "<span style='color:green;'>已备货</span>";
                break;
            case self::COMPLETE_STATUS_EXPIRED:
                return "<span style='color:red;'>超期</span>";
                break;
            case self::COMPLETE_STATUS_WAITTING_SHIP:
                return "<span style='color:#8B0000;'>待发货</span>";
                break;
            case self::COMPLETE_STATUS_PARTIAL_SHIP:
                return "<span style='color:red;'>部分发货</span>"; //超期待处理
                break;
            case self::COMPLETE_STATUS_SHIPPED:
                return "<span style='color:green;'>已发货</span>"; //超期待处理
                break;
            case self::COMPLETE_STATUS_HOLD:
                return "<span style='color:#FFA500;'>暂扣</span>"; //部分退款
                break;
            /*             case self::COMPLETE_STATUS_PARTIAL_REFUND:
              return "<span style='color:red;'>部分退款</span>";//全部退款
              break;
              case self::COMPLETE_STATUS_ALL_REFUND:
              return "<span style='color:red;'>全部退款</span>";//已取消
              break; */
            case self::COMPLETE_STATUS_CANCELED:
                return "<span style='color:red;'>已取消</span>"; //已取消
                break;
            case self::COMPLETE_STATUS_COMPLETED:
                return "<span style='color:red;'>已完成</span>"; //已取消
                break;
            case self::COMPLETE_STATUS_TONGTU:
                return "<span style='color:green;'>通途处理订单</span>"; //通途处理订单
                break;
            case self::COMPLETE_STATUS_PAY:
                return "<span style='color:green;'>待检测paypal账号</span>"; //待检测paypal账号
        }
    }

    /**
     * @desc 获取订单数据
     * @param unknown $platformCode
     * @param unknown $platformOrderId
     * @return multitype:|Ambigous <multitype:, NULL, mixed>
     */
    public static function getOrderStack($platformCode, $platformOrderId, $system_order_id = null) {
        $orderInfo = [];
        if (empty($platformCode) || (empty($platformOrderId) && empty($system_order_id)))
            return $orderInfo;
        $cacheKey = md5('cache_erp_order_' . $platformCode . '_' . $platformOrderId . '_all');
        $cacheNamespace = 'namespace_erp_order_' . $platformCode . '_' . $platformOrderId;
        //从缓存获取订单数据
        /*          if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
          !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
          {
          return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
          } */
        //从接口获取订单数据
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getOrderStack($platformCode, $platformOrderId, $system_order_id);
        if (empty($result))
            return $orderInfo;
        $orderInfo = $result->order;
        if (!empty($orderInfo) && isset(\Yii::$app->memcache)) {
            \Yii::$app->memcache->set($cacheKey, $orderInfo, $cacheNamespace);
        }
        return $orderInfo;
    }

    /*
     * @desc 添加订单备注
     * */

    public static function getAddremark($order_id, $remark, $userId, $username) {

        $orderInfo = '';
        if (empty($order_id) || empty($remark))
            return $orderInfo;
        $erpOrderApi = new ErpOrderApi;

        $result = $erpOrderApi->getAddremark($order_id, $remark, $userId, $username);
        return empty($result) ? $orderInfo : $result;
    }

    /*
     * @desc 删除订单备注
     * */

    public static function removeMark($id, $userId, $username) {
        $remark = '';
        if (empty($id))
            return $remark;
        $erpOrderApi = new ErpOrderApi;
        $removeSign = $erpOrderApi->removeOrderRemark($id, $userId, $username);

        return empty($removeSign) ? $remark : $removeSign;
    }

    /*
     * @desc 添加出货备注
     * */

    public static function Addprintremark($orderId, $platform, $print_remark) {
        $orderInfo = '';
        if (empty($orderId))
            return $orderInfo;
        $erpOrderApi = new ErpOrderApi;
        $printRemark = $erpOrderApi->Addprintremark($orderId, $platform, $print_remark);

        return empty($printRemark) ? $orderInfo : $printRemark;
    }

    public static function getOrderStackByTransactionId($platformCode, $transactionId) {
        $orderInfo = [];
        if (empty($platformCode) || empty($transactionId))
            return $orderInfo;
        $TransactionKey = 'Transaction_id_' . $platformCode . '_' . $transactionId;
        /*         if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($TransactionKey) &&
          !empty(\Yii::$app->memcache->get($TransactionKey)))
          {
          $platformOrderId = \Yii::$app->memcache->get($TransactionKey);
          return self::getOrderStack($platformCode,$platformOrderId);
          } */
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getOrderStackByCustom($platformCode, ['transactionId' => $transactionId]);
        if (empty($result))
            return $orderInfo;
        $orderInfo = $result->order;
        if (!empty($orderInfo) && isset(\Yii::$app->memcache)) {
            $platformOrderId = $orderInfo->info->platform_order_id;
            $cacheKey = md5('cache_erp_order_' . $platformCode . '_' . $platformOrderId . '_all');
            $cacheNamespace = 'namespace_erp_order_' . $platformCode . '_' . $platformOrderId;
            \Yii::$app->memcache->set($cacheKey, $orderInfo, $cacheNamespace);
            \Yii::$app->memcache->set($TransactionKey, $platformOrderId);
        }
        return $orderInfo;
    }

    /**
     * @desc 获取订单数据
     * @param unknown $platformCode
     * @param unknown $platformOrderId
     * @return multitype:|Ambigous <multitype:, NULL, mixed>
     */
    public static function getEbayOrderStack($account_id, $buyer_id, $item_id, $transaction_id, $platformCode = Platform::PLATFORM_CODE_EB) {
        $orderInfo = [];
        if (empty($account_id) || empty($buyer_id) || empty($item_id) || !isset($transaction_id) || empty($platformCode))
            return $orderInfo;

        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getEbayOrderStack(['accountId' => $account_id, 'buyerId' => $buyer_id, 'itemId' => $item_id, 'transactionId' => $transaction_id, 'platformCode' => $platformCode]);
        if (empty($result))
            return $orderInfo;
        $orderInfo = $result->order;
        return $orderInfo;
    }

    /**
     * @desc 获取订单数据
     * @param unknown $platformCode
     * @param unknown $platformOrderId
     * @return multitype:|Ambigous <multitype:, NULL, mixed>
     */
    public static function getHistoryOrders($platformCode, $buyerId, $email = '', $accountId = "") {
        $orderHistory = [];
        if (empty($platformCode) || empty($buyerId))
            return $orderHistory;
        $cacheKey = md5('cache_erp_customer_histroy_order_' . $platformCode . '_' . $buyerId);
        $cacheNamespace = 'namespace_erp_order';
        //从缓存获取订单数据
        /*          if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
          !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
          {
          return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
          } */
        //从接口获取订单数据
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getHistoryOrders($platformCode, $buyerId, $email, $accountId);
        if (empty($result))
            return $orderHistory;
        $orderHistory = $result->orders;
        if (!empty($orders) && isset(\Yii::$app->memcache))
            \Yii::$app->memcache->set($cacheKey, $orderHistory, $cacheNamespace);
        return $orderHistory;
    }

    public static function setOrderRefund($platformCode, $data) {
        $erpOrderApi = new ErpOrderApi;
        return $erpOrderApi->setRefund($platformCode, $data);
    }

    public static function setOrderStatus($data) {
        $erpOrderApi = new ErpOrderApi;
        return $erpOrderApi->setOrderStatus($data);
    }

    public static function getProductImageThub($sku) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getProductImageThub($sku);
        return $result ? $result->image_url : '';
    }

    public static function getOrders($platform_code = null, $platform_order_id = null, $system_order_id = null, $buyer_id = null, $ship_name = null, $email = null, $ship_phone = null, $item_id = null, $package_id = null, $track_number = null, $order_number = null, $paypal_id = null, $account_id = null, $complete_status = null, $sku = null) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getOrders($platform_code, $platform_order_id, $system_order_id, $buyer_id, $ship_name, $email, $ship_phone, $item_id, $package_id, $track_number, $order_number, $paypal_id, $account_id, $complete_status, $sku);

        return $result ? json_decode(json_encode($result->orders), true) : array();
    }

    public static function getOrdersByPage($platform_code = null, $platform_order_id = null, $system_order_id = null, $buyer_id = null, $ship_name = null, $email = null, $ship_phone = null, $item_id = null, $package_id = null, $track_number = null, $order_number = null, $paypal_id = null, $account_id = null, $complete_status = null, $sku = null, $page_cur = 0, $page_size = 0) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getOrdersByPage($platform_code, $platform_order_id, $system_order_id, $buyer_id, $ship_name, $email, $ship_phone, $item_id, $package_id, $track_number, $order_number, $paypal_id, $account_id, $complete_status, $sku, $page_cur, $page_size);

        if (!empty($result)) {
            return array(
                'orders' => json_decode(json_encode($result->orders), true),
                'count' => !empty($result->count) ? $result->count : 0,
            );
        } else {
            return array();
        }
    }

    /** 通过订单id获取交易号用于退款业务 */
    public static function getTransactionId($platform_code, $order_id) {
        if (empty($platform_code) || empty($order_id)) {
            return false;
        }

        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getTransactionId($platform_code, $order_id);

        //获取数据成功返回[transaction_id, $old_account_id, $item_id, platform_order_id];
        if (!empty($result)) {
            return [$result->transaction_id, $result->account_id, $result->item_id, $result->platform_order_id];
        }

        //获取数据失败
        return $result;
    }

    /** 通过订单id获取交易号用于退款业务 */
    public static function getTransactionInfo($transaction_id) {
        if (empty($transaction_id)) {
            return false;
        }

        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getTransactionInfo($transaction_id);

        //获取数据成功返回receiver_business
        if (!empty($result)) {
            return $result->receiver_business;
        }

        //获取数据失败
        return $result;
    }

    /**
     * @desc 暂扣订单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return multitype:boolean NULL |boolean
     */
    public static function holdOrder($platformCode, $orderId, $remark = '') {
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->holdOrder($platformCode, $orderId, $remark);
        if (!$flag)
            return [false, $erpOrderApi->getExcptionMessage()];
        return true;
    }

    /**
     * @desc 取消暂扣订单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return multitype:boolean NULL |boolean
     */
    public static function cancelHoldOrder($platformCode, $orderId) {
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->cancelHoldOrder($platformCode, $orderId);
        if (!$flag)
            return [false, $erpOrderApi->getExcptionMessage()];
        return true;
    }

    /**
     * @desc 取消订单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return multitype:boolean NULL |boolean
     */
    public static function cancelOrder($platformCode, $orderId, $platformOrderId = null, $remark) {
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->cancelOrder($platformCode, $orderId, $platformOrderId, $remark);
        if (!$flag)
            return [false, $erpOrderApi->getExcptionMessage()];
        return true;
    }

    /**
     * @desc 系统取消订单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return multitype:boolean NULL |boolean
     */
    public static function systemCancelOrder($platformCode, $orderId, $platformOrderId = null) {

        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->systemCancelOrder($platformCode, $orderId, $platformOrderId);
        if (!$flag)
            return [false, $erpOrderApi->getExcptionMessage()];
        return true;
    }

    /**
     * @desc 获取异常信息
     */
    public function getExceptionMessage() {
        return $this->exceptionMessage;
    }

    /**
     * @desc 根据订单ID获取订单数据
     * @param unknown $platformCode
     * @param unknown $platformOrderId
     * @return multitype:|Ambigous <multitype:, NULL, mixed>
     */
    public static function getOrderStackByOrderId($platformCode, $platformOrderId = null, $systemsOrderId = null) {
        $orderInfo = [];
        if (empty($platformCode) || (empty($platformOrderId) && empty($systemsOrderId)))
            return $orderInfo;
        $cacheKey = md5('cache_erp_order_' . $platformCode . '_' . $platformOrderId . '_all');
        $cacheNamespace = 'namespace_erp_order_' . $platformCode . '_' . $platformOrderId;
        //从缓存获取订单数据
        /*          if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
          !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
          {
          return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
          } */
        //从接口获取订单数据
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getOrderStack($platformCode, $platformOrderId, $systemsOrderId);
        if (empty($result))
            return $orderInfo;
        $orderInfo = $result->order;
        if (!empty($orderInfo) && isset(\Yii::$app->memcache)) {
            \Yii::$app->memcache->set($cacheKey, $orderInfo, $cacheNamespace);
        }
        return $orderInfo;
    }

//    public static function getCertainInfo($order_id,$platform_code)
//    {
//        $erpOrderApi = new ErpOrderApi;
//        $result = $erpOrderApi->getCertainInfo($order_id, $platform_code);
//
//        return $result ? json_decode(json_encode($result->data),true) : array();
//    }

    /**
     * @desc 获取订单利润
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function getOrderProfitByOrderId($orderId) {
        $datas['order_id'] = $orderId;
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->getOrderProfitByOrderId($datas);
        if (!$flag) {
            $this->exceptionMessage = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return $flag;
    }

    /**
     * @desc 获取预计重寄费用
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function getPreRedirectCost($platform_code, $orderId, $sku, $quantity, $ship_code, $ship_country, $ship_country_name, $order_amount, $currency) {
        $datas['platform_code'] = $platform_code;
        $datas['order_id'] = $orderId;
        $datas['sku'] = $sku;
        $datas['quantity'] = $quantity;
        $datas['ship_code'] = $ship_code;
        $datas['ship_country'] = $ship_country;
        $datas['ship_country_name'] = $ship_country_name;
        $datas['order_amount'] = $order_amount;
        $datas['currency'] = $currency;
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->getPreRedirectCost($datas);
        if (!$flag) {
            $this->exceptionMessage = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return $flag;
    }

    /**
     * @desc 获取重寄费用
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function getRedirectCost($platform_code, $orderId, $sku, $quantity, $ship_code) {
        $datas['platform_code'] = $platform_code;
        $datas['order_id'] = $orderId;
        $datas['sku'] = $sku;
        $datas['quantity'] = $quantity;
        $datas['ship_code'] = $ship_code;
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->getRedirectCost($datas);
        if (!$flag) {
            $this->exceptionMessage = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return $flag;
    }

    /**
     * @desc 获取重寄费用
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function getRedirectCostByOrderId($platform_code, $orderId) {
        $datas['platform_code'] = $platform_code;
        $datas['order_id'] = $orderId;
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->getRedirectCostByOrderId($datas);
        if (!$flag) {
            $this->exceptionMessage = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return $flag;
    }

    /**
     * @desc 获取指定订单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function getOrder($platformCode, $orderId) {
        $datas['platformCode'] = $platformCode;
        $datas['orderId'] = $orderId;
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->getOrder($datas);
        if (!$flag) {
            $this->exceptionMessage = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return $flag;
    }

    /**
     * @desc 根据sku获取订单ids
     * @param unknown $platformCode
     * @param unknown $sku
     * @return boolean
     */
    public function getOrderIdsBySku($platformCode, $sku) {
        $datas['platformCode'] = $platformCode;
        $datas['sku'] = $sku;
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->getOrderIdsBySku($datas);
        if (!$flag) {
            $this->exceptionMessage = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return $flag;
    }

    /**
     * @desc 获取amazon订单的金额信息
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function getAmazonamtinfo($orderId, $itemId = null) {
        $datas['orderId'] = $orderId;
        $datas['itemId'] = $itemId;
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->getAmazonamtinfo($datas);
        if (!$flag) {
            $this->exceptionMessage = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return $flag;
    }

    /**
     * @desc 根据平台订单号获取指定订单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return boolean
     */
    public function getOrderByPlatformOrderID($platformCode, $PlatformOrderID) {
        $datas['platformCode'] = $platformCode;
        $datas['PlatformOrderID'] = $PlatformOrderID;
        $erpOrderApi = new ErpOrderApi;
        $flag = $erpOrderApi->getOrderByPlatformOrderID($datas);
        if (!$flag) {
            $this->exceptionMessage = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return $flag;
    }

    /**
     * 通过sku获取ebay评价中的item_id
     */
    public static function getEbayFeedBackItemIdBySku($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getEbayFeedBackItemIdBySku($data);
        return !empty($result) ? json_decode(json_encode($result->item), true) : array();
    }

    /**
     * 获取ebay评价订单信息
     * 通过多个平台订单ID，获取多个订单信息
     */
    public static function getEbayFeedBackOrderInfos($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getEbayFeedBackOrderInfos($data);
        return !empty($result) ? json_decode(json_encode($result->order), true) : array();
    }

    /**
     * 获取ebay订单信息
     * 通过平台订单ID和交易ID，一次性获取多个订单信息
     */
    public static function getEbayOrderInfos($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getEbayOrderInfos($data);
        return !empty($result) ? json_decode(json_encode($result->order), true) : array();
    }

    /**
     * 获取ebay评价产品信息
     * 通过多个item_id，获取多个产品信息
     */
    public static function getEbayFeedBackItemInfos($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getEbayFeedBackItemInfos($data);
        return !empty($result) ? json_decode(json_encode($result->item), true) : array();
    }

    /**
     * @return boolean
     * @author allen <2018-1-8>
     */
    public static function getCertainInfo($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getCertainInfo($data);
        return $result ? json_decode(json_encode($result->data), true) : array();
    }

    /**
     * 重寄下载需要的相关erp数据
     * @return boolean
     * @author allen <2018-1-10>
     */
    public static function getResendDataInfo($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getResendDataInfo($data);
        return $result ? json_decode(json_encode($result->data), true) : array();
    }

    /**
     * 客服取消操作 【添加客户到黑名单/取消客户黑名单】
     * 保存操作日志
     * @author allen <2018-02-08>
     */
    public static function blackOptions($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->blackOptions($data);
        return $result ? json_decode(json_encode($result->data), true) : array();
    }

    /**
     * 更新或添加GBC数据
     */
    public static function updateGbcData($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->updateGbcData($data);
        return $result ? json_decode(json_encode($result->data), true) : array();
    }

    /**
     * 订单绑定payPal账号
     * @param type $data
     * @return type
     * @author allen <2018-03012>
     */
    public static function orderbindtransaction($data) {
        $erpOrderApi = new ErpOrderApi();
        $result = $erpOrderApi->orderbindtransaction($data);
        return $result ? json_decode(json_encode($result->data), true) : [];
    }

    /**
     * 删除记录
     * @param $data
     * @return array|mixed
     */
    public static function orderunbindtransaction($data) {
        $erpOrderApi = new ErpOrderApi();
        $result = $erpOrderApi->orderunbindtransaction($data);
        return $result ? json_decode(json_encode($result->data), true) : [];
    }

    /**
     * 同步亚马逊review操作【更新review原因 跟进状态】
     * @param type $data
     * @return type
     * @author Allen <2018-03-27>
     */
    public static function syncAmazonReviewProcess($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->syncAmazonReviewProcess($data);
        return $result ? json_decode(json_encode($result->data), true) : array();
    }

    /**
     * 获取汇率
     * @param type $data
     * @return type
     * @author allen <2018-03-31>
     */
    public static function getExchangeRate($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getExchangeRate($data);
        return $result ? json_decode(json_encode($result->data), true) : array();
    }

    /**
     * 获取订单利润
     * @param type $data
     * @return type
     * @author allen <2018-04-18>
     */
    public static function getProfit($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getProfit($data);
        return $result ? json_decode(json_encode($result->data), true) : array();
    }

    /**
     * 获取产品平均成本
     * @param type $data
     * @return type
     * @author allen <2018-04-18>
     */
    public static function getAvgCost($data) {
        $erpOrderApi = new ErpOrderApi;
        $result = $erpOrderApi->getAvgCost($data);
        return $result ? json_decode(json_encode($result->data), true) : array();
    }

    /**
     * 优先配库
     * @param $data
     * @return array|mixed
     */
    public static function SetPriorityStatus($data) {
        $erpOrderApi = new ErpOrderApi();
        $result = $erpOrderApi->SetPriorityStatus($data);
        return $result ? json_decode(json_encode($result->data), true) : [];
    }

    /**
     * 永久作废恢复
     * @param $data
     * @return array|mixed
     */
    public static function Ordertoinit($data) {
        $erpOrderApi = new ErpOrderApi();
        $result = $erpOrderApi->Ordertoinit($data);
        return $result ? json_decode(json_encode($result->data), true) : [];
    }

    /**
     * 推送订单到仓库
     * @param $data
     * @return array|mixed
     */
    public static function batchsendorde($data) {
        $erpOrderApi = new ErpOrderApi();
        $result = $erpOrderApi->batchsendorde($data);
        return $result ? json_decode(json_encode($result->data), true) : [];
    }

    /**
     * 手动给订单配库
     * @param $data
     * @return array|mixed
     */
    public static function batchallotstock($data) {
        $erpOrderApi = new ErpOrderApi();
        $result = $erpOrderApi->batchallotstock($data);
        return $result ? json_decode(json_encode($result->data), true) : [];
    }

    /**
     * 更新订单状态
     * @param $platformCode
     * @param $platformOrderId
     * @param $platformOrderStatus
     * @return bool|null
     */
    public static function updateOrderStatus($platformCode, $platformOrderId, $platformOrderStatus) {
        $data['platformCode'] = $platformCode;
        $data['platformOrderId'] = $platformOrderId;
        $data['platformOrderStatus'] = $platformOrderStatus;
        $erpOrderApi = new ErpOrderApi();
        $result = $erpOrderApi->updateOrderStatus($data);
        return !empty($result->ack) ? true : false;
    }

}
