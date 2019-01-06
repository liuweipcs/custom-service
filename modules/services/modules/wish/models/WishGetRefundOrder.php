<?php

namespace app\modules\services\modules\wish\models;

use app\modules\accounts\models\Platform;
use app\modules\orders\models\OrderWishKefu;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\systems\models\AftersaleManage;
use app\modules\accounts\models\Account;
use wish\models\WishAccount;

class WishGetRefundOrder
{
    const MAX_PAGE_SIZE = 9999;

    /**
     * 获取退款订单
     */
    public function refundOrderList($account, $since)
    {
        if (empty($account)) {
            return false;
        }

        $limit = 100;

        for ($pageCur = 1; $pageCur < self::MAX_PAGE_SIZE; $pageCur++) {
            $start = ($pageCur - 1) * $limit;
            $orders = self::sendWish($account, $since, $start, $limit);

            if (empty($orders->data)) {
                break;
            }
            foreach ($orders->data as $order) {
                try {
                    if (empty($order->Order)) {
                        continue;
                    }

                    $order = $order->Order;
                    //如果订单状态不是退款，直接跳过
                    if ($order->state != 'REFUNDED') {
                        continue;
                    }

                    //如果在ERP中没有找到该订单号，则不用拉取
                    $orderExists = OrderWishKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_WISH, 'platform_order_id' => $order->order_id]);
                    if (empty($orderExists)) {
                        continue;
                    }

                    //保存平台退款订单
                    $refundOrder = PlatformRefundOrder::findOne(['platform_code' => Platform::PLATFORM_CODE_WISH, 'platform_order_id' => $order->order_id]);
                    if (empty($refundOrder)) {
                        $refundOrder = new PlatformRefundOrder();
                        $refundOrder->create_by = 'system';
                        $refundOrder->create_time = date('Y-m-d H:i:s');
                    }
                    $refundOrder->platform_code = Platform::PLATFORM_CODE_WISH;
                    $refundOrder->platform_order_id = $order->order_id;
                    $refundOrder->transaction_id = !empty($order->transaction_id) ? $order->transaction_id : '';
                    $refundOrder->account_id = $account->id;
                    $refundOrder->buyer_id = !empty($order->buyer_id) ? $order->buyer_id : '';
                    $refundOrder->email = '';
                    $refundOrder->amount = $order->order_total;
                    $refundOrder->ship_amount = !empty($order->shipping) ? $order->shipping : 0;
                    $refundOrder->currency = 'USD';
                    $refundOrder->order_status = !empty($order->state) ? $order->state : '';
                    $refundOrder->reason = !empty($order->refunded_reason) ? $order->refunded_reason : '';
                    $refundOrder->refund_time = !empty($order->refunded_time) ? date('Y-m-d H:i:s', strtotime($order->refunded_time)) : '';
                    $refundOrder->order_create_time = date('Y-m-d H:i:s', strtotime($order->order_time));
                    $refundOrder->order_update_time = date('Y-m-d H:i:s', strtotime($order->last_updated));
                    $refundOrder->modify_by = 'system';
                    $refundOrder->modify_time = date('Y-m-d H:i:s');

                    if ($refundOrder->save()) {
                        //根据规则，自动建立售后单
                        $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_WISH, $refundOrder->platform_order_id, $refundOrder->reason, $refundOrder->transaction_id, $refundOrder->amount, $refundOrder->refund_time);
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

    public function sendWish($account, $since, $start, $limit)
    {

        $erpAccount = WishAccount::findOne(['wish_id' => $account->old_account_id]);
        if (empty($erpAccount)) {
            return false;
        }
        $token = $erpAccount->access_token;
        if (empty($token)) {
            return false;
        }
        $url = 'https://merchant.wish.com/api/v2/order/multi-get';
        $get_data['access_token'] = $token;
        $get_data['start'] = $start;
        $get_data['limit'] = $limit;
        $get_data['since'] = $since;
        $get_data['show_original_shipping_detail'] = "True";
        $res = $this->sendWishInbox($url, $get_data);
        return $res;
    }

    public function sendWishInbox($url, $data)
    {
        $ch = curl_init();
        $url = rtrim($url, '?') . '?' . http_build_query($data);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        if ($data === false) {
            echo curl_errno($ch) . ':' . curl_error($ch);
            return false;
        }
        curl_close($ch);
        return $this->getResponseResult($data);
    }

    protected function getResponseResult($result)
    {
        $obj = json_decode($result, false, 512, JSON_BIGINT_AS_STRING);
        if ($obj === false) {
            echo 'json_decode() failure';
            return false;
        }
        return $obj;
    }
}
