<?php
namespace app\modules\services\modules\joom\models;

use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\systems\models\AftersaleManage;
use app\modules\accounts\models\Platform;

class JoomGetRefundOrder
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
            $orders = JoomApi::multiGet($account->access_token, $since, $start, $limit);
            if (empty($orders)) {
                break;
            }

            foreach ($orders as $order) {
                if (empty($order['Order'])) {
                    continue;
                }
                $order = $order['Order'];
                //如果订单状态不是退款，直接跳过
                if ($order['state'] != 'REFUNDED') {
                    continue;
                }

                //如果在ERP中没有找到该订单号，则不用拉取
                $orderExists = OrderOtherKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_JOOM, 'platform_order_id' => $order['order_id']]);
                if (empty($orderExists)) {
                    continue;
                }

                //保存平台退款订单
                $refundOrder = PlatformRefundOrder::findOne(['platform_code' => Platform::PLATFORM_CODE_JOOM, 'platform_order_id' => $order['order_id']]);
                if (empty($refundOrder)) {
                    $refundOrder = new PlatformRefundOrder();
                    $refundOrder->create_by = 'system';
                    $refundOrder->create_time = date('Y-m-d H:i:s');
                }
                $refundOrder->platform_code = Platform::PLATFORM_CODE_JOOM;
                $refundOrder->platform_order_id = $order['order_id'];
                $refundOrder->transaction_id = !empty($order['transaction_id']) ? $order['transaction_id'] : '';
                $refundOrder->account_id = $account->id;
                $refundOrder->buyer_id = !empty($order['buyer_id']) ? $order['buyer_id'] : '';
                $refundOrder->email = '';
                $refundOrder->amount = $order['order_total'];
                $refundOrder->ship_amount = !empty($order['shipping']) ? $order['shipping'] : 0;
                $refundOrder->currency = 'USD';
                $refundOrder->order_status = !empty($order['state']) ? $order['state'] : '';
                $refundOrder->reason = !empty($order['refunded_reason']) ? $order['refunded_reason'] : '';
                $refundOrder->refund_time = !empty($order['refunded_time']) ? date('Y-m-d H:i:s', strtotime($order['refunded_time'])) : '';
                $refundOrder->order_create_time = date('Y-m-d H:i:s', strtotime($order['order_time']));
                $refundOrder->order_update_time = date('Y-m-d H:i:s', strtotime($order['last_updated']));
                $refundOrder->modify_by = 'system';
                $refundOrder->modify_time = date('Y-m-d H:i:s');

                if ($refundOrder->save()) {
                    //根据规则，自动建立售后单
                    $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_JOOM, $refundOrder->platform_order_id, $refundOrder->reason, $refundOrder->transaction_id, $refundOrder->amount, $refundOrder->refund_time);
                    if ($result) {
                        $refundOrder->is_aftersale = 1;
                        $refundOrder->is_match_rule = 1;
                        $refundOrder->save();
                    }
                }
            }
        }
    }
}