<?php

namespace app\modules\mails\models;

use app\components\MongodbModel;

class AccountTaskQueue extends MongodbModel
{

    const TASK_TYPE_MESSAGE = 'message';
    const TASK_TYPE_RETURN = 'return'; //return纠纷
    const TASK_TYPE_RETURN_UPDATE = 'return_update'; //return纠纷update
    const TASK_TYPE_INQUIRY = 'inquiry';//inquiry纠纷
    const TASK_TYPE_INQUIRY_UPDATE = 'inquiry_update';//inquiry纠纷
    const TASK_TYPE_CANCELLATION = 'cancellation'; //Cancellation纠纷
    const TASK_TYPE_CANCELLATION_UPDATE = 'cancellation_update'; //Cancellation纠纷update
    const TASK_TYPE_FEEDBACK = 'feedback';
    const LIST_OF_DISPUTES = 'disputes_list'; //获取纠纷列表
    const LIST_OF_EVALUATE = 'evaluate_list'; //获取评价列表
    const UPDATE_LIST_OF_DISPUTES = 'update_disputes_list'; //更新评价列表
    const LIST_OF_MID_EVALUATE = 'evaluate_mid_list'; //获取中差评价列表
    const INQUIRY_SEND_MSG = 'inquiry_send_msg';    // 纠纷留言发送
    const LIST_OF_NOTIFICATION = 'notification_list'; //通知列表

    const TASK_TYPE_CASE = 'case';

    const AMAZON_REFUND = 'amazon_refund';
    const AMAZON_REFUND_RESULT = 'amazon_refund_result';

    const SHOPEE_RETURN = 'shopee_return'; //退款退货
    const SHOPEE_ORDER_STATUS = 'shopee_order_status'; //订单状态
    const SHOPEE_REFUND = 'shopee_refund'; //退款

    const WISH_REFUND = 'wish_refund'; //wish退款

    const JOOM_REFUND = 'joom_refund';
    const JUMIA_REFUND = 'jumia_refund';
    const CDISCOUNT_REFUND = 'cdiscount_refund';
    const CDISCOUNT_SELLER_INDICATORS = 'cdiscount_seller_Indicators'; //cd账号表现
    const LAZADA_REFUND = 'lazada_refund';
    const MALL_REFUND = 'mall_refund';
    const CDISCOUNT_ORDER_CLIAM_LIST = 'cdiscount_order_claim_list';
    const CDISCOUNT_OFFER_QUESTION_LIST = 'cdiscount_offer_question_list';
    const CDISCOUNT_ORDER_QUESTION_LIST = 'cdiscount_order_question_list';
    const EB_ACCOUNT_OVERVIEW = 'eb_account_overview'; //ebay账号表现
    const EB_SELLER_ACCOUNT_OVERVIEW = 'eb_seller_account_overview'; //卖家成绩表
    const EB_BUYER_ACCOUNT_OVERVIEW = 'eb_buyer_account_overview'; //买家体验报告
    const EB_FLUSH_ACCESS_TOKEN = 'eb_flush_access_token'; //刷新access_token

    const AFTERSALES_FINANCIAL_STATISTICS = 'aftersales_financial_statistics'; //售后单成本数据统计

    const NUMBER_PER_TASK = 5;

    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'account_task_queue';
    }

    public function attributes()
    {
        return [
            '_id', 'account_id', 'platform_code', 'type', 'create_time'
        ];
    }

    public static function findByPlatform($platformCode, $type)
    {
        return self::findAll(['platform_code' => $platformCode, 'type' => $type]);
    }

    /**
     * 随机返回任务列表
     */
    public static function getTaskList($condition, $limit = 5)
    {
        $limit = !empty($limit) ? $limit : self::NUMBER_PER_TASK;

        //一次性取出所有任务
        $tasks = self::find()->where($condition)->all();
        //任务列表
        $taskList = [];
        if (!empty($tasks)) {
            if (count($tasks) <= $limit) {
                foreach ($tasks as $task) {
                    $taskList[] = $task->account_id;
                    $task->delete();
                }
            } else {
                //打乱数组，随机获取账号
                shuffle($tasks);

                $tasks = array_slice($tasks, 0, $limit);
                if (!empty($tasks)) {
                    foreach ($tasks as $task) {
                        $taskList[] = $task->account_id;
                        $task->delete();
                    }
                }
            }
        }
        return $taskList;
    }

    /**
     * 随机返回下一个任务
     */
    public static function getNextAccountTask($platformCode, $type)
    {
        $tasks = self::find()->where(['platform_code' => $platformCode, 'type' => $type])->all();
        if (empty($tasks)) {
            return [];
        }
        if (count($tasks) == 1) {
            return $tasks[0];
        }
        $ix = mt_rand(0, count($tasks) - 1);
        return array_key_exists($ix, $tasks) ? $tasks[$ix] : [];
    }

    /**
     * 随机返回下一个任务
     */
    public static function getNextAccountTaskOfType($type)
    {
        $tasks = self::find()->where(['type' => $type])->all();
        if (empty($tasks)) {
            return [];
        }
        if (count($tasks) == 1) {
            return $tasks[0];
        }
        $ix = mt_rand(0, count($tasks) - 1);
        return array_key_exists($ix, $tasks) ? $tasks[$ix] : [];
    }
}
