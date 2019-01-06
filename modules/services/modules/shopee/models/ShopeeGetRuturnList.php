<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/28 0028
 * Time: 9:36
 */

namespace app\modules\services\modules\shopee\models;

use app\modules\mails\models\ShopeeDisputeList;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\services\modules\shopee\components\ShopeeApi;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\systems\models\AftersaleManage;

class ShopeeGetRuturnList
{

    const MAX_PAGE_SIZE = 9999;

    /**
     * 退款退货数据
     */
    public function GetReturnList($account, $startTime, $endTime)
    {
        if (empty($account)) {
            return false;
        }

        //当前页数
        $pageCur = 1;
        //一页大小
        $pageSize = 50;
        do {
            //偏移量
            $pageOffset = ($pageCur - 1) * $pageSize;
            //获取纠纷列表
            $issueList = self::getIssueList($account, $startTime, $endTime, $pageOffset, $pageSize);
            if (empty($issueList) || empty($issueList['returns'])) {
                break;
            }
            foreach ($issueList['returns'] as $issue) {
                if (empty($issue['returnsn'])) {
                    continue;
                }
                $shopeeIssue = ShopeeDisputeList::findOne(['returnsn' => $issue['returnsn']]);
                if (empty($shopeeIssue)) {
                    $shopeeIssue = new ShopeeDisputeList();
                }

                $shopeeIssue->status = !empty($issue['status']) ? $issue['status'] : '';
                $shopeeIssue->due_date = !empty($issue['due_date']) ? $issue['due_date'] : '';
                $shopeeIssue->update_time = !empty($issue['update_time']) ? $issue['update_time'] : 0;
                $shopeeIssue->amount_before_discount = !empty($issue['amount_before_discount']) ? $issue['amount_before_discount'] : 0;
                $shopeeIssue->text_reason = !empty($issue['text_reason']) ? $issue['text_reason'] : '';
                $shopeeIssue->needs_logistics = !empty($issue['needs_logistics']) ? 1 : 0;
                $shopeeIssue->refund_amount = !empty($issue['refund_amount']) ? $issue['refund_amount'] : 0;
                $shopeeIssue->tracking_number = !empty($issue['tracking_number']) ? $issue['tracking_number'] : '';
                $shopeeIssue->currency = !empty($issue['currency']) ? $issue['currency'] : '';
                $shopeeIssue->reason = !empty($issue['reason']) ? $issue['reason'] : '';
                $shopeeIssue->dispute_text_reason = !empty($issue['dispute_text_reason']) ? json_encode($issue['dispute_text_reason']) : '';
                $shopeeIssue->create_time = !empty($issue['create_time']) ? $issue['create_time'] : 0;
                $shopeeIssue->returnsn = !empty($issue['returnsn']) ? $issue['returnsn'] : '';
                $shopeeIssue->ordersn = !empty($issue['ordersn']) ? $issue['ordersn'] : '';
                $shopeeIssue->user = !empty($issue['user']) ? json_encode($issue['user']) : '';
                $shopeeIssue->dispute_reason = !empty($issue['dispute_reason']) ? json_encode($issue['dispute_reason']) : '';
                $shopeeIssue->items = !empty($issue['items']) ? json_encode($issue['items']) : '';
                $shopeeIssue->images = !empty($issue['images']) ? json_encode($issue['images']) : '';
                $shopeeIssue->account_id = $account->id;
                $shopeeIssue->save();
            }
            $pageCur++;
        } while (!empty($issueList));
    }

    /**
     * 获取退款退货信息
     */
    public static function getIssueList($account, $startTime, $endTime, $pageOffset = 0, $pageSize = 50)
    {
        if (empty($account)) {
            return false;
        }
        $api = new ShopeeApi($account->shop_id, $account->partner_id, $account->secret_key, $account->country_code);
        $endTime = !empty($endTime) ? strtotime($endTime) : time();
        $startTime = !empty($startTime) ? strtotime($startTime) : ($endTime - 86400 * 15);
        $condition_other['pagination_offset'] = $pageOffset;
        $condition_other['pagination_entries_per_page'] = $pageSize;
        $condition_other['create_time_from'] = $startTime;
        $condition_other['create_time_to'] = $endTime;
        $data = $api->GetReturnList($condition_other);
        if (empty($data)) {
            return false;
        }
        return $data;
    }

    /**
     * 获取订单的详情
     */
    public static function getOrderDetails($account, $orderIds)
    {
        if (empty($account)) {
            return false;
        }
        $api = new ShopeeApi($account->shop_id, $account->partner_id, $account->secret_key, $account->country_code);
        return $api->getorderdetail($orderIds);
    }

    /**
     * 获取退款列表
     */
    public function refundOrderList($account, $startTime, $endTime)
    {
        if (empty($account)) {
            return false;
        }

        $pageSize = 50;

        for ($pageCur = 1; $pageCur < self::MAX_PAGE_SIZE; $pageCur++) {
            $pageOffset = ($pageCur - 1) * $pageSize;

            $result = self::getIssueList($account, $startTime, $endTime, $pageOffset, $pageSize);
            if (empty($result) || empty($result['returns'])) {
                break;
            }
            $returns = $result['returns'];

            //获取订单ID数组
            $orderIds = array_column($returns, 'ordersn');
            //获取订单的详情
            $orderDetails = self::getOrderDetails($account, $orderIds);

            foreach ($returns as $return) {
                if (empty($return['returnsn'])) {
                    continue;
                }

                //订单详情
                $orderInfo = array_key_exists($return['ordersn'], $orderDetails) ? $orderDetails[$return['ordersn']] : [];

                //订单状态为COMPLETED，并且退款状态为REFUND_PAID
                if (!empty($orderInfo) && $orderInfo['order_status'] == 'COMPLETED' && $return['status'] == 'REFUND_PAID') {

                    //如果在ERP中没有找到该订单号，则不用拉取
                    $orderExists = OrderOtherKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_SHOPEE, 'platform_order_id' => $return['ordersn']]);
                    if (empty($orderExists)) {
                        continue;
                    }

                    $refundOrder = PlatformRefundOrder::findOne(['platform_code' => Platform::PLATFORM_CODE_SHOPEE, 'platform_order_id' => $return['ordersn']]);
                    if (empty($refundOrder)) {
                        $refundOrder = new PlatformRefundOrder();
                        $refundOrder->create_by = 'system';
                        $refundOrder->create_time = date('Y-m-d H:i:s');
                    }

                    $refundOrder->platform_code = Platform::PLATFORM_CODE_SHOPEE;
                    $refundOrder->platform_order_id = $return['ordersn'];
                    $refundOrder->transaction_id = '';
                    $refundOrder->account_id = $account->id;
                    $refundOrder->buyer_id = !empty($return['user']['username']) ? $return['user']['username'] : '';
                    $refundOrder->email = !empty($return['user']['email']) ? $return['user']['email'] : '';
                    $refundOrder->amount = !empty($return['refund_amount']) ? $return['refund_amount'] : 0;
                    $refundOrder->ship_amount = !empty($orderInfo['estimated_shipping_fee']) ? $orderInfo['estimated_shipping_fee'] : 0;
                    $refundOrder->currency = !empty($orderInfo['currency']) ? $orderInfo['currency'] : '';
                    $refundOrder->order_status = !empty($orderInfo['order_status']) ? $orderInfo['order_status'] : '';
                    $refundOrder->reason = !empty($return['reason']) ? $return['reason'] : '';
                    $refundOrder->refund_time = !empty($return['update_time']) ? date('Y-m-d H:i:s', $return['update_time']) : date('Y-m-d H:i:s', $return['create_time']);
                    $refundOrder->order_create_time = date('Y-m-d H:i:s', $return['create_time']);
                    $refundOrder->order_update_time = date('Y-m-d H:i:s', $return['update_time']);
                    $refundOrder->modify_by = 'system';
                    $refundOrder->modify_time = date('Y-m-d H:i:s');

                    if ($refundOrder->save()) {
                        $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_SHOPEE, $refundOrder->platform_order_id, $refundOrder->reason, $refundOrder->transaction_id, $refundOrder->amount, $refundOrder->refund_time);
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
}