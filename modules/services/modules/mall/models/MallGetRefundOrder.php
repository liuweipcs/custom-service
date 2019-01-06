<?php

namespace app\modules\services\modules\mall\models;

use app\modules\accounts\models\Platform;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\systems\models\AftersaleManage;

class MallGetRefundOrder
{
    const MAX_PAGE_SIZE = 9999;

    /**
     * 返回所有更改状态的订单
     */
    public function getOrder($account, $orderId)
    {
        if (empty($account)) {
            return false;
        }
        $access_token = $account->access_token;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://mall.my.com/merchant/wish/api/v2/order/?id={$orderId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer {$access_token}",
                "content-type: application/json"
            ),
        ));
        $result = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);

        if (empty($result) || empty($result['data']) || empty($result['data']['Order'])) {
            return false;
        }

        return $result['data']['Order'];
    }

    /**
     * @param $account
     * @param $orderId
     * 通过订单号拉取MALL订单
     */
    public function refundOrder($account, $orderId)
    {
        if(empty($account)){
            return false;
        }

        if(empty($orderId)){
            return false;
        }

        try {
            //如果在ERP中没有找到该订单号，则不用拉取
            $orderExists = OrderOtherKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_MALL, 'order_id' => $orderId]);
            if (empty($orderExists)) {
                return false;
            }


            //通过接口获取订单详细信息
            $orderInfo = $this->getOrder($account, $orderExists->platform_order_id);


            if ($orderInfo['state'] != 'refunded') {
                return false;
            }

            $refundOrder = PlatformRefundOrder::findOne(['platform_code' => Platform::PLATFORM_CODE_MALL, 'platform_order_id' => $orderExists->platform_order_id]);
            if (empty($refundOrder)) {
                $refundOrder = new PlatformRefundOrder();
                $refundOrder->create_by = 'system';
                $refundOrder->create_time = date('Y-m-d H:i:s');
            }

            $refundOrder->platform_code = Platform::PLATFORM_CODE_MALL;
            $refundOrder->platform_order_id = $orderExists->platform_order_id;
            $refundOrder->transaction_id = !empty($orderInfo['transaction_id']) ? $orderInfo['transaction_id'] : '';
            $refundOrder->account_id = $account->id;
            $refundOrder->buyer_id = !empty($orderInfo['ShippingDetail']['name']) ? $orderInfo['ShippingDetail']['name'] : '';
            $refundOrder->email = '';
            $refundOrder->amount = !empty($orderInfo['order_total']) ? $orderInfo['order_total'] : 0;
            $refundOrder->ship_amount = !empty($orderInfo['order_total']) ? $orderInfo['order_total'] : 0;
            $refundOrder->currency = !empty($orderExists->currency) ? $orderExists->currency : '';
            $refundOrder->order_status = !empty($orderInfo['state']) ? $orderInfo['state'] : '';
            $refundOrder->reason = !empty($orderInfo['refunded_reason']) ? $orderInfo['refunded_reason'] : '';
            $refundOrder->refund_time = !empty($orderInfo['refunded_time']) ? date('Y-m-d H:i:s', strtotime($orderInfo['refunded_time'])) : date('Y-m-d H:i:s', strtotime($orderInfo['last_updated']));
            $refundOrder->order_create_time = date('Y-m-d H:i:s', strtotime($orderExists->created_time));
            $refundOrder->order_update_time = date('Y-m-d H:i:s', strtotime($orderExists->last_update_time));
            $refundOrder->modify_by = 'system';
            $refundOrder->modify_time = date('Y-m-d H:i:s');

            if ($refundOrder->save()) {
                $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_MALL, $refundOrder->platform_order_id, $refundOrder->reason, $refundOrder->transaction_id, $refundOrder->amount, $refundOrder->refund_time);
                if ($result) {
                    $refundOrder->is_aftersale = 1;
                    $refundOrder->is_match_rule = 1;
                    $refundOrder->save();
                }
                return true;
            }
        }catch (\Exception $e){

        }

    }


    /**
     * 获取退款订单
     */
    public function refundOrderList($account, $startTime)
    {
        if (empty($account)) {
            return false;
        }

        $limit = 50;
        $access_token = $account->access_token;

        for ($pageCur = 1; $pageCur < self::MAX_PAGE_SIZE; $pageCur++) {
            $offset = ($pageCur - 1) * $limit;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://mall.my.com/merchant/api/v1/purchase/order/_search?offset={$offset}&limit={$limit}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\n  \"filter\" : {\n   \"fulfilled\": true,\n  \"from_created_at\": \"{$startTime}\"\n  }\n}",
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer {$access_token}",
                    "content-type: application/json"
                ),
            ));
            $result = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
            if (empty($result) || empty($result['data']['orders'])) {
                break;
            }

            $orders = $result['data']['orders'];
            foreach ($orders as $order) {
                try {
                    if ($order['state'] != 'refunded') {
                        continue;
                    }

                    //如果在ERP中没有找到该订单号，则不用拉取
                    $orderExists = OrderOtherKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_MALL, 'platform_order_id' => $order['id']]);
                    if (empty($orderExists)) {
                        continue;
                    }

                    //通过接口获取订单详细信息
                    $orderInfo = $this->getOrder($account, $order['id']);

                    $refundOrder = PlatformRefundOrder::findOne(['platform_code' => Platform::PLATFORM_CODE_MALL, 'platform_order_id' => $order['id']]);
                    if (empty($refundOrder)) {
                        $refundOrder = new PlatformRefundOrder();
                        $refundOrder->create_by = 'system';
                        $refundOrder->create_time = date('Y-m-d H:i:s');
                    }
                    $refundOrder->platform_code = Platform::PLATFORM_CODE_MALL;
                    $refundOrder->platform_order_id = $order['id'];
                    $refundOrder->transaction_id = !empty($orderInfo['transaction_id']) ? $orderInfo['transaction_id'] : '';
                    $refundOrder->account_id = $account->id;
                    $refundOrder->buyer_id = !empty($orderInfo['ShippingDetail']['name']) ? $orderInfo['ShippingDetail']['name'] : $order['shippingDetails']['name'];
                    $refundOrder->email = '';
                    $refundOrder->amount = !empty($order['totalPrice']['amount']) ? $order['totalPrice']['amount'] : 0;
                    $refundOrder->ship_amount = !empty($order['shippingPrice']['amount']) ? $order['shippingPrice']['amount'] : 0;
                    $refundOrder->currency = !empty($order['totalPrice']['currency']) ? $order['totalPrice']['currency'] : '';
                    $refundOrder->order_status = !empty($order['state']) ? $order['state'] : '';
                    $refundOrder->reason = !empty($orderInfo['refunded_reason']) ? $orderInfo['refunded_reason'] : '';
                    $refundOrder->refund_time = !empty($orderInfo['refunded_time']) ? date('Y-m-d H:i:s', strtotime($orderInfo['refunded_time'])) : date('Y-m-d H:i:s', strtotime($orderInfo['last_updated']));
                    $refundOrder->order_create_time = date('Y-m-d H:i:s', strtotime($order['createdAt']));
                    $refundOrder->order_update_time = date('Y-m-d H:i:s', strtotime($order['updatedAt']));
                    $refundOrder->modify_by = 'system';
                    $refundOrder->modify_time = date('Y-m-d H:i:s');

                    if ($refundOrder->save()) {
                        $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_MALL, $refundOrder->platform_order_id, $refundOrder->reason, $refundOrder->transaction_id, $refundOrder->amount, $refundOrder->refund_time);
                        if ($result) {
                            $refundOrder->is_aftersale = 1;
                            $refundOrder->is_match_rule = 1;
                            $refundOrder->save();
                        }
                    }
                } catch (\Exception $e) {
                    //防止出现的异常中断整个程序执行
                }
            }
        }
    }


    /**
     * buyer_id
     */
    public static function emoji_encode($str)
    {
        $strEncode = '';
        $length = mb_strlen($str, 'utf-8');
        for ($i = 0; $i < $length; $i++) {
            $_tmpStr = mb_substr($str, $i, 1, 'utf-8');
            if (strlen($_tmpStr) >= 4) {
                $strEncode .= '';
                $insert = 2;
            } else {
                $strEncode .= $_tmpStr;
            }
        }
        return $strEncode;
    }
}