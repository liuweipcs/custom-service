<?php

namespace app\modules\mails\models;

use app\components\MongodbModel;

/**
 * 速卖通评价临时表
 */
class AliexpressEvaluateTmp extends MongodbModel
{

    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'aliexpress_evaluate_tmp';
    }

    public function attributes()
    {
        return [
            '_id', 'account_id', 'info', 'create_time'
        ];
    }

    /**
     * 获取待处理的评价
     */
    public function getWaitingProcessList($limit = 500)
    {
        return self::find()->limit($limit)->all();
    }

    /**
     * 将mongodb中保存的评价信息转存入mysql中
     */
    public function processTmpEvaluate($tmpEvaluate)
    {
        if (empty($tmpEvaluate)) {
            return false;
        }

        //账号ID
        $accountId = $tmpEvaluate->account_id;
        //获取评价列表
        $info = json_decode($tmpEvaluate->info, true, 512, JSON_BIGINT_AS_STRING);

        if (empty($info)) {
            return false;
        }

        foreach ($info as $evaluate) {
            try {
                $evaluateList = AliexpressEvaluateList::find()->where([
                    'account_id' => $accountId,
                    'platform_order_id' => $evaluate['order_id'],
                    'platform_parent_order_id' => $evaluate['parent_order_id'],
                    'buyer_login_id' => $evaluate['buyer_login_id'],
                    'platform_product_id' => $evaluate['product_id'],
                ])->one();

                if (empty($evaluateList)) {
                    $evaluateList = new AliexpressEvaluateList();
                    $evaluateList->create_by = 'system';
                    $evaluateList->create_time = date('Y-m-d H:i:s');
                }

                $evaluateList->account_id = $accountId;
                $evaluateList->buyer_evaluation = !empty($evaluate['buyer_evaluation']) ? $evaluate['buyer_evaluation'] : 0;
                $evaluateList->buyer_fb_date = !empty($evaluate['buyer_fb_date']) ? $evaluate['buyer_fb_date'] : '';
                $evaluateList->buyer_feedback = !empty($evaluate['buyer_feedback']) ? $evaluate['buyer_feedback'] : '';
                $evaluateList->buyer_login_id = !empty($evaluate['buyer_login_id']) ? $evaluate['buyer_login_id'] : '';
                $evaluateList->buyer_reply = !empty($evaluate['buyer_reply']) ? $evaluate['buyer_reply'] : '';
                $evaluateList->gmt_create = !empty($evaluate['gmt_create']) ? $evaluate['gmt_create'] : '';
                $evaluateList->gmt_modified = !empty($evaluate['gmt_modified']) ? $evaluate['gmt_modified'] : '';
                $evaluateList->gmt_order_complete = !empty($evaluate['gmt_order_complete']) ? $evaluate['gmt_order_complete'] : '';
                $evaluateList->platform_order_id = !empty($evaluate['order_id']) ? $evaluate['order_id'] : '';
                $evaluateList->platform_parent_order_id = !empty($evaluate['parent_order_id']) ? $evaluate['parent_order_id'] : '';
                $evaluateList->platform_product_id = !empty($evaluate['product_id']) ? $evaluate['product_id'] : '';
                $evaluateList->seller_evaluation = !empty($evaluate['seller_evaluation']) ? $evaluate['seller_evaluation'] : 0;
                $evaluateList->seller_fb_date = !empty($evaluate['seller_fb_date']) ? $evaluate['seller_fb_date'] : '';
                $evaluateList->seller_feedback = !empty($evaluate['seller_feedback']) ? $evaluate['seller_feedback'] : '';
                $evaluateList->seller_login_id = !empty($evaluate['seller_login_id']) ? $evaluate['seller_login_id'] : '';
                $evaluateList->seller_reply = !empty($evaluate['seller_reply']) ? $evaluate['seller_reply'] : '';
                $evaluateList->valid_date = !empty($evaluate['valid_date']) ? $evaluate['valid_date'] : '';
                $evaluateList->modify_by = 'system';
                $evaluateList->modify_time = date('Y-m-d H:i:s');

                if (!empty($evaluate['seller_reply'])) {
                    $evaluateList->reply_status = 1;
                }

                $evaluateList->save(false);
            } catch (\Exception $e) {
                //防止程序异常，中断运行
            }
        }

        //删除处理成功的数据
        $tmpEvaluate->delete();
        return true;
    }
}