<?php

namespace app\modules\services\modules\lazada\models;

use app\modules\accounts\models\Platform;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\services\modules\lazada\components\LazRequestTool;
use app\modules\systems\models\AftersaleManage;

class LazadaGetRefundOrder
{
    const MAX_PAGE_SIZE = 9999;

    /**
     * 获取退款订单
     */
    public function refundOrderList($account, $startTime, $endTime)
    {
        if (empty($account)) {
            return false;
        }

        $limit = 100;

        for ($pageCur = 1; $pageCur < self::MAX_PAGE_SIZE; $pageCur++) {
            $offset = ($pageCur - 1) * $limit;

            $condition = [
                'limit' => $limit,
                'offset' => $offset,
                'sort_by' => 'created_at',
                'sort_direction' => 'ASC',
                'created_after' => str_replace(' ', 'T', $startTime),
                'created_before' => str_replace(' ', 'T', $endTime),
            ];

            //转成下划线方式
            $condition = self::convertToUnderlineKey($condition);
            //这里取得是old_account_id
            $orderResult = LazRequestTool::getOrders($account->old_account_id, $condition);
            $orderResult = json_decode($orderResult, false, 512, JSON_BIGINT_AS_STRING);

            if (empty($orderResult) || empty($orderResult->data->orders)) {
                break;
            }

            $orders = $orderResult->data->orders;
            foreach ($orders as $order) {
                //订单的状态
                $orderStatus = [];
                if (!empty($order->statuses)) {
                    foreach ($order->statuses as $status) {
                        $orderStatus[] = $status;
                    }
                }

                //判断订单状态
                //returned 和 canceled
                $intersect = array_intersect($orderStatus, ['pending', 'ready_to_ship', 'delivered', 'shipped', 'failed']);
                if (!empty($intersect)) {
                    continue;
                }

                //如果在ERP中没有找到该订单号，则不用拉取
                $orderExists = OrderOtherKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_LAZADA, 'platform_order_id' => $order->order_id]);
                if (empty($orderExists)) {
                    continue;
                }

                //获取订单商品纠纷问题
                $items = LazRequestTool::getOrderItems($account->old_account_id, $order->order_id);
                $items = json_decode($items, false, 512, JSON_BIGINT_AS_STRING);
                $reason = [];
                if (!empty($items) && !empty($items->data)) {
                    foreach ($items->data as $item) {
                        if (!empty($item->reason)) {
                            $reason[] = $item->reason;
                        }
                    }
                    $reason = array_unique($reason);
                }

                $totalPrice = (self::getFloatPrice($order->price) + self::getFloatPrice($order->shipping_fee));

                $refundOrder = PlatformRefundOrder::findOne(['platform_code' => Platform::PLATFORM_CODE_LAZADA, 'platform_order_id' => $order->order_id]);
                if (empty($refundOrder)) {
                    $refundOrder = new PlatformRefundOrder();
                    $refundOrder->create_by = 'system';
                    $refundOrder->create_time = date('Y-m-d H:i:s');
                }
                $refundOrder->platform_code = Platform::PLATFORM_CODE_LAZADA;
                $refundOrder->platform_order_id = $order->order_id;
                $refundOrder->transaction_id = '';
                $refundOrder->account_id = $account->id;
                $refundOrder->buyer_id = !empty($order->customer_first_name) ? $order->customer_first_name : '';
                $refundOrder->email = '';
                $refundOrder->amount = $totalPrice;
                $refundOrder->ship_amount = self::getFloatPrice($order->shipping_fee);
                $refundOrder->currency = self::currency($account->country_code);
                $refundOrder->order_status = implode('|', $orderStatus);
                $refundOrder->reason = current($reason);
                $refundOrder->refund_time = date('Y-m-d H:i:s', strtotime($order->updated_at));
                $refundOrder->order_create_time = date('Y-m-d H:i:s', strtotime($order->created_at));
                $refundOrder->order_update_time = date('Y-m-d H:i:s', strtotime($order->updated_at));
                $refundOrder->modify_by = 'system';
                $refundOrder->modify_time = date('Y-m-d H:i:s');

                if ($refundOrder->save()) {
                    $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_LAZADA, $refundOrder->platform_order_id, $refundOrder->reason, '', $refundOrder->amount, $refundOrder->refund_time);
                    if ($result) {
                        $refundOrder->is_aftersale = 1;
                        $refundOrder->is_match_rule = 1;
                        $refundOrder->save();
                    }
                }
            }
        }
    }

    /**
     * 获取币种
     */
    public static function currency($countryCode)
    {
        $countryArr = [
            'my' => 'MYR',
            'ph' => 'PHP',
            'th' => 'THB',
            'id' => 'IDR',
            'sg' => 'SGD',
            'vn' => 'VND',
        ];
        return array_key_exists($countryCode, $countryArr) ? $countryArr[$countryCode] : '';
    }

    /**
     * 价格格式化
     */
    public static function getFloatPrice($price)
    {
        if (strpos($price, ',')) {
            $price = str_replace(',', '', $price);
        }
        return $price;
    }

    /**
     * condition转下划线
     */
    public static function convertToUnderlineKey($arr)
    {
        $data = [];
        foreach ($arr as $k => $v) {
            $data[self::toUnderScore($k)] = $v;
        }
        return $data;
    }

    public static function toUnderScore($str)
    {
        $dstr = preg_replace_callback('/([A-Z]+)/', function ($matchs) {
            return '_' . strtolower($matchs[0]);
        }, $str);
        return trim(preg_replace('/_{2,}/', '_', $dstr), '_');
    }
}