<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/8 0008
 * Time: 11:18
 */

namespace app\modules\services\modules\cdiscount\models;

use app\modules\accounts\models\Platform;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\services\modules\cdiscount\components\cdiscountApi;
use app\modules\systems\models\AftersaleManage;

class CdiscountGetRefundOrder {

    /**
     * 获取退款订单
     */
    public function refundOrderList($account, $startTime, $endTime) {
        if (empty($account)) {
            return false;
        }

        $cdApi = new cdiscountApi($account->refresh_token);

        $result = $cdApi->getOrderListrefund(strtotime($startTime), strtotime($endTime));
           
        if (empty($result['GetOrderListResponse']) || empty($result['GetOrderListResponse']['GetOrderListResult']['OrderList'])) {
            return false;
        }
     
        $orders = $result['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'];
     
        //判断是否一维或二维数组
        if (!empty($orders['OrderNumber'])) {
            $orders = [$orders];
        }
      
        foreach ($orders as $order) {

            if ($order['OrderState'] != 'ShipmentRefusedBySeller') {
                continue;
            }
              
            //如果在ERP中没有找到该订单号，则不用拉取
            $orderExists = OrderOtherKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT, 'platform_order_id' => $order['OrderNumber']]);
         
            if (empty($orderExists)) {
                continue;
            }
               
            //开启mysql事物
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $refundOrder = PlatformRefundOrder::findOne(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT, 'platform_order_id' => $order['OrderNumber']]);
                if (empty($refundOrder)) {
                    $refundOrder = new PlatformRefundOrder();
                    $refundOrder->create_by = 'system';
                    $refundOrder->create_time = date('Y-m-d H:i:s');
                }
                $refundOrder->platform_code = Platform::PLATFORM_CODE_CDISCOUNT;
                $refundOrder->platform_order_id = $order['OrderNumber'];
                $refundOrder->transaction_id = '';
                $refundOrder->account_id = $account->id;
                $refundOrder->buyer_id = !empty($order['Customer']['CustomerId']) ? $order['Customer']['CustomerId'] : '';
                $refundOrder->email = !empty($order['Customer']['EncryptedEmail']) ? $order['Customer']['EncryptedEmail'] : '';
                $refundOrder->amount = !empty($order['ValidatedTotalAmount']) ? $order['ValidatedTotalAmount'] : 0;
                $refundOrder->ship_amount = !empty($order['ValidatedTotalShippingCharges']) ? $order['ValidatedTotalShippingCharges'] : 0;
                $refundOrder->currency = 'EUR';
                $refundOrder->order_status = !empty($order['OrderState']) ? $order['OrderState'] : '';
                $refundOrder->reason = '';
                $refundOrder->refund_time = date('Y-m-d H:i:s', strtotime($order['ModifiedDate']));
                $refundOrder->order_create_time = date('Y-m-d H:i:s', strtotime($order['CreationDate']));
                $refundOrder->order_update_time = date('Y-m-d H:i:s', strtotime($order['ModifiedDate']));
                $refundOrder->modify_by = 'system';
                $refundOrder->modify_time = date('Y-m-d H:i:s');
                if ($refundOrder->save()) {
                    $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_CDISCOUNT, $refundOrder->platform_order_id, $refundOrder->reason, '', $refundOrder->amount, $refundOrder->refund_time);
                    if ($result) {
                        $refundOrder->is_aftersale = 1;
                        $refundOrder->is_match_rule = 1;
                        $refundOrder->save();
                    }
                }
                $transaction->commit();
            } catch (Exception $exc) {
                $transaction->rollBack();
                echo $exc->getTraceAsString();
            }
        }
    }

}
