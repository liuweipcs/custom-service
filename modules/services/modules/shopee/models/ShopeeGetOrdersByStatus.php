<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/28 0028
 * Time: 17:01
 */

namespace app\modules\services\modules\shopee\models;

use app\modules\mails\models\ShopeeCancellationList;
use app\modules\orders\models\OrderOtherSearch;
use app\modules\services\modules\shopee\components\ShopeeApi;

class ShopeeGetOrdersByStatus
{

    public function GetOrdersByStatus($account, $startTime, $endTime)
    {
        if (empty($account)) {
            return false;
        }

        //当前页数
        $pageCur = 1;
        //每页大小
        $pageSize = 100;

        do {
            //偏移量
            $pageOffset = ($pageCur - 1) * $pageSize;
            //获取订单列表
            $statusList = self::getStatuslist($account, $startTime, $endTime, $pageOffset, $pageSize);

            if (empty($statusList) || empty($statusList['orders'])) {
                break;
            }

            if (!empty($statusList['orders'])) {
                foreach ($statusList['orders'] as $status) {
                    if (in_array($status['order_status'], ['UNPAID', 'READY_TO_SHIP', 'SHIPPED', 'TO_CONFIRM_RECEIVE', 'CANCELLED', 'INVALID', 'TO_RETURN', 'COMPLETED', 'RETRY_SHIP'])) {
                        continue;
                    }
                    //IN_CANCEL 的订单插入数据库
                    $shopeeCancellation = ShopeeCancellationList::findOne(['ordersn' => $status['ordersn']]);
                    if (empty($shopeeCancellation)) {
                        $shopeeCancellation = new ShopeeCancellationList();
                    }
                    //通过订单获取buyerid order_type order_id
                    $order_id                          = OrderOtherSearch::getOrderId($status['ordersn']);
                    $buyer_id                          = OrderOtherSearch::getBuyerId($status['ordersn']);
                    $order_type                        = OrderOtherSearch::getOrderType($status['ordersn']);
                    $shopeeCancellation->ordersn       = $status['ordersn'];
                    $shopeeCancellation->order_id      = $order_id;
                    $shopeeCancellation->buyer_id      = $buyer_id;
                    $shopeeCancellation->seller_shop   = $account->account_name;
                    $shopeeCancellation->order_status  = $status['order_status'];
                    $shopeeCancellation->update_time   = $status['update_time'];
                    $shopeeCancellation->order_type    = $order_type;
                    $shopeeCancellation->account_id    = $account->id;
                    $shopeeCancellation->cancel_reason = 'IN_CANCEL';
                    $shopeeCancellation->save();
                }
            }
            $pageCur++;
        } while (!empty($statusList));
    }

    /**
     * 获取订单列表
     */
    public static function getStatuslist($account, $startTime, $endTime, $pageOffset = 0, $pageSize = 100, $orderStatus = 'ALL')
    {
        if (empty($account)) {
            return false;
        }
        $api                                            = new ShopeeApi($account->shop_id, $account->partner_id, $account->secret_key, $account->country_code);
        $endTime                                        = !empty($endTime) ? strtotime($endTime) : time();
        $startTime                                      = !empty($startTime) ? strtotime($startTime) : ($endTime - 86400 * 15);
        $condition_other['pagination_offset']           = $pageOffset;
        $condition_other['pagination_entries_per_page'] = $pageSize;
        $condition_other['create_time_from']            = $startTime;
        $condition_other['create_time_to']              = $endTime;
        $condition_other['order_status']                = $orderStatus;
        $data                                           = $api->getOrderByStatus($condition_other);
        if (empty($data)) {
            return false;
        }
        return $data;
    }

}