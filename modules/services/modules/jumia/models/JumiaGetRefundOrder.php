<?php

namespace app\modules\services\modules\jumia\models;

use app\modules\accounts\models\Platform;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\services\modules\jumia\components\JumiaApi;
use app\modules\systems\models\AftersaleManage;

class JumiaGetRefundOrder
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
                'Limit' => $limit,
                'Offset' => $offset,
                'SortBy' => 'created_at',
                'SortDirection' => 'ASC',
                'CreatedAfter' => str_replace(' ', 'T', $startTime),
                'CreatedBefore' => str_replace(' ', 'T', $endTime),
            ];

            $request = new JumiaApi($account->email, $account->access_token, $account->country_code);
            //获取订单列表
            $orderResult = $request->getOrders($condition);
            $orderResult = json_decode($orderResult, false, 512, JSON_BIGINT_AS_STRING);

            if (empty($orderResult->SuccessResponse) || empty($orderResult->SuccessResponse->Body->Orders->Order)) {
                break;
            }

            //订单ID数组
            $orderIds = [];
            foreach ($orderResult->SuccessResponse->Body->Orders->Order as $item) {
                $orderIds[] = $item->OrderId;
            }

            //获取订单的商品
            $itemsResult = $request->getOrdersGoods($orderIds);
            $itemsResult = json_decode($itemsResult, false, 512, JSON_BIGINT_AS_STRING);

            if (empty($itemsResult->SuccessResponse) || empty($itemsResult->SuccessResponse->Body->Orders->Order)) {
                break;
            }
            //商品列表
            $goodsList = [];
            $order = $itemsResult->SuccessResponse->Body->Orders->Order;
            if (isset($order->OrderId)) {
                if (is_array($order->OrderItems->OrderItem)) {
                    //一个订单多个商品
                    $goodsList[$order->OrderId] = $order->OrderItems->OrderItem;
                } else {
                    //一个订单一个商品
                    $goodsList[$order->OrderId][] = $order->OrderItems->OrderItem;
                }
            } else {
                foreach ($order as $item) {
                    if (is_array($item->OrderItems->OrderItem)) {
                        $goodsList[$item->OrderId] = $item->OrderItems->OrderItem;
                    } else {
                        $goodsList[$item->OrderId][] = $item->OrderItems->OrderItem;
                    }
                }
            }

            //商品纠纷原因
            $goodReason = [];
            //计算订单不含运费总价
            $orderPrice = [];
            foreach ($goodsList as $orderId => $items) {
                foreach ($items as $item) {
                    if (!isset($orderPrice[$orderId])) {
                        $orderPrice[$orderId] = 0;
                    }
                    if (!isset($goodReason[$orderId])) {
                        $goodReason[$orderId] = [];
                    }
                    $orderPrice[$orderId] += $item->ItemPrice;

                    if (!empty($item->Reason)) {
                        $goodReason[$orderId][] = $item->Reason;
                    }
                }
                $goodReason[$orderId] = array_unique($goodReason[$orderId]);
            }

            foreach ($orderResult->SuccessResponse->Body->Orders->Order as $val) {
                try {
                    //订单的状态
                    $orderStatus = [];
                    if (!empty($val->Statuses)) {
                        if (is_array($val->Statuses->Status)) {
                            foreach ($val->Statuses->Status as $status) {
                                $orderStatus[] = $status;
                            }
                        } else {
                            $orderStatus[] = $val->Statuses->Status;
                        }
                    }

                    //判断订单状态
                    //returned
                    $intersect = array_intersect($orderStatus, ['canceled', 'shipped', 'return_waiting_for_approval', 'return_shipped_by_customer', 'return_rejected', 'ready_to_ship', 'processing', 'pending', 'failed', 'delivered']);
                    if (!empty($intersect)) {
                        continue;
                    }

                    //如果在ERP中没有找到该订单号，则不用拉取
                    $orderExists = OrderOtherKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_JUM, 'platform_order_id' => $val->OrderId]);
                    if (empty($orderExists)) {
                        continue;
                    }

                    $refundOrder = PlatformRefundOrder::findOne(['platform_code' => Platform::PLATFORM_CODE_JUM, 'platform_order_id' => $val->OrderId]);
                    if (empty($refundOrder)) {
                        $refundOrder = new PlatformRefundOrder();
                        $refundOrder->create_by = 'system';
                        $refundOrder->create_time = date('Y-m-d H:i:s');
                    }
                    $refundOrder->platform_code = Platform::PLATFORM_CODE_JUM;
                    $refundOrder->platform_order_id = $val->OrderId;
                    $refundOrder->transaction_id = '';
                    $refundOrder->account_id = $account->id;
                    $refundOrder->buyer_id = $val->CustomerFirstName;
                    $refundOrder->email = '';
                    $refundOrder->amount = array_key_exists($val->OrderId, $orderPrice) ? $orderPrice[$val->OrderId] : 0;
                    $refundOrder->ship_amount = 0;
                    $refundOrder->currency = self::currency($account->country_code);
                    $refundOrder->order_status = implode(',', $orderStatus);
                    $refundOrder->reason = array_key_exists($val->OrderId, $goodReason) ? (implode(',', $goodReason[$val->OrderId])) : '';
                    $refundOrder->refund_time = !empty($val->UpdatedAt) ? $val->UpdatedAt : date('Y-m-d H:i:s');
                    $refundOrder->order_create_time = !empty($val->CreatedAt) ? $val->CreatedAt : date('Y-m-d H:i:s');
                    $refundOrder->order_update_time = !empty($val->UpdatedAt) ? $val->UpdatedAt : date('Y-m-d H:i:s');
                    $refundOrder->modify_by = 'system';
                    $refundOrder->modify_time = date('Y-m-d H:i:s');

                    if ($refundOrder->save()) {
                        $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_JUM, $refundOrder->platform_order_id, $refundOrder->reason, '', $refundOrder->amount, $refundOrder->refund_time);
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
     * 获取币种
     */
    public static function currency($countryCode)
    {
        $countryArr = [
            'EG' => 'EGP',
            'CI' => 'XOF',
            'MA' => 'MAD',
            'KE' => 'KES',
            'NG' => 'NGN',
        ];
        return array_key_exists($countryCode, $countryArr) ? $countryArr[$countryCode] : '';
    }
}