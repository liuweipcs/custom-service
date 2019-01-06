<?php
/**
 * @desc 速卖通消息临时表
 * @author Fun
 */

namespace app\modules\mails\models;

use app\components\MongodbModel;

class AliexpressIssueTmp extends MongodbModel
{
    public $exceptionMessage = null;

    const SOLUTION_TYPE_BUYER = 'buyer';               //买家方案
    const SOLUTION_TYPE_SELLER = 'seller';              //卖家方案
    const SOLUTION_TYPE_PLATFORM = 'platform';             //买家方案

    /**
     * @desc 设置集合
     * @return string
     */
    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'aliexpress_issue_tmp';
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\mongodb\ActiveRecord::attributes()
     */
    public function attributes()
    {
        return [
            '_id', 'account_id', 'issue_id', 'info', 'detail', 'create_time'
        ];
    }

    /**
     * 获取待处理的纠纷信息
     * @param int $offset 偏移量
     * @param int $limit 大小
     */
    public function getWaitingProcessList($limit = 200)
    {
        return self::find()->limit($limit)->all();
    }

    /**
     * 获取待处理的纠纷数量
     */
    public function getWaitingProcessCount()
    {
        return self::find()->count();
    }

    /**
     * 将mongodb中保存的纠纷信息转存入mysql中
     */
    public function processTmpIssue($tmpIssue)
    {
        $dbTransaction = AliexpressDisputeList::getDb()->beginTransaction();
        try {
            //纠纷ID
            $issueId = $tmpIssue->issue_id;
            //账号ID
            $accountId = $tmpIssue->account_id;
            //纠纷信息
            $info = json_decode($tmpIssue->info, true, 512, JSON_BIGINT_AS_STRING);
            //纠纷详情
            $detail = json_decode($tmpIssue->detail, true, 512, JSON_BIGINT_AS_STRING);

            //1、保存纠纷信息到列表中({{%aliexpress_dispute_list}})
            $issueList = AliexpressDisputeList::findOne([
                'platform_dispute_id' => $issueId,
                'account_id' => $accountId,
            ]);
            if (empty($issueList)) {
                $issueList = new AliexpressDisputeList();
                $issueList->create_by = 'system';
                $issueList->create_time = date('Y-m-d H:i:s', time());
            }
            $issueList->platform_dispute_id = $issueId;
            $issueList->account_id = $accountId;
            $issueList->buyer_login_id = $info['buyer_login_id'];
            $issueList->gmt_modified = $info['gmt_modified'];
            $issueList->issue_status = $info['issue_status'];
            $issueList->gmt_create = $info['gmt_create'];
            $issueList->reason_chinese = $info['reason_chinese'];
            $issueList->platform_order_id = $info['order_id'];
            $issueList->platform_parent_order_id = !empty($info['parent_order_id']) ? $info['parent_order_id'] : '';
            $issueList->reason_english = $info['reason_english'];
            $issueList->update_time = date('Y-m-d H:i:s', time());
            $issueList->modify_by = 'system';
            $issueList->modify_time = date('Y-m-d H:i:s', time());
            if (!$issueList->save(false)) {
                throw new \Exception('save issue list error');
            }

            //2、保存纠纷详情到表中({{%aliexpress_dispute_detail}})
            $issueDetail = AliexpressDisputeDetail::findOne([
                'platform_dispute_id' => $issueId,
                'account_id' => $accountId,
            ]);
            if (empty($issueDetail)) {
                $issueDetail = new AliexpressDisputeDetail();
                $issueDetail->create_by = 'system';
                $issueDetail->create_time = date('Y-m-d H:i:s', time());
            }
            $issueDetail->platform_dispute_id = $issueId;
            $issueDetail->account_id = $accountId;
            $issueDetail->buyer_login_id = $detail['buyer_login_id'];
            $issueDetail->platform_parent_order_id = !empty($detail['parent_order_id']) ? $detail['parent_order_id'] : '';
            $issueDetail->platform_order_id = $detail['order_id'];
            $issueDetail->buyer_aliid = '';
            $issueDetail->issue_reason_id = !empty($detail['issue_reason_id']) ? $detail['issue_reason_id'] : '';
            $issueDetail->issue_reason = !empty($detail['issue_reason']) ? $detail['issue_reason'] : '';
            $issueDetail->issue_status = !empty($detail['issue_status']) ? $detail['issue_status'] : '';
            $issueDetail->refund_money_max = $detail['refund_money_max'];
            $issueDetail->refund_money_max_local = $detail['refund_money_max_local'];
            $issueDetail->product_name = isset($detail['product_name']) ? $detail['product_name'] : '';
            $issueDetail->product_price = isset($detail['product_price']) ? $detail['product_price'] : 0;
            $issueDetail->buyer_return_logistics_company = !empty($detail['buyer_return_logistics_company']) ? $detail['buyer_return_logistics_company'] : '';
            $issueDetail->buyer_return_no = !empty($detail['buyer_return_no']) ? $detail['buyer_return_no'] : '';
            $issueDetail->buyer_return_logistics_lp_no = !empty($detail['buyer_return_logistics_lp_no']) ? $detail['buyer_return_logistics_lp_no'] : '';
            $issueDetail->gmt_create = $detail['gmt_create'];
            $issueDetail->modify_by = 'system';
            $issueDetail->modify_time = date('Y-m-d H:i:s', time());
            $issueDetail->refund_money_max_currency = isset($detail['refund_money_max_currency']) ? 
                $detail['refund_money_max_currency'] : '';
            $issueDetail->refund_money_max_local_currency = isset($detail['refund_money_max_local_currency']) ?
                $detail['refund_money_max_local_currency'] : '';
            $issueDetail->product_price_currency = isset($detail['product_price_currency']) ? 
                $detail['product_price_currency'] : '';
            $issueDetail->after_sale_warranty = !empty($detail['after_sale_warranty']) ? intval($detail['after_sale_warranty']) : 0;

            if (!$issueDetail->save(false)) {
                throw new \Exception('save issue detail error');
            }

            //3、保存纠纷协商方案({{%aliexpress_dispute_solution}})

            //卖家纠纷方案列表
            $sellerSolutionList = !empty($detail['seller_solution_list']) ? $detail['seller_solution_list'] : [];
            //买家纠纷方案列表
            $buyerSolutionList = !empty($detail['buyer_solution_list']) ? $detail['buyer_solution_list'] : [];
            //平台纠纷方案列表
            $platformSolutionList = !empty($detail['platform_solution_list']) ? $detail['platform_solution_list'] : [];

            if (!empty($sellerSolutionList)) {
                $flag = $this->saveIssueSolution($accountId, $sellerSolutionList, self::SOLUTION_TYPE_SELLER);
                if (!$flag) {
                    throw new \Exception('save issue solution error');
                }
            }
            if (!empty($buyerSolutionList)) {
                $flag = $this->saveIssueSolution($accountId, $buyerSolutionList, self::SOLUTION_TYPE_BUYER);
                if (!$flag) {
                    throw new \Exception('save issue solution error');
                }
            }
            if (!empty($platformSolutionList)) {
                $flag = $this->saveIssueSolution($accountId, $platformSolutionList, self::SOLUTION_TYPE_PLATFORM);
                if (!$flag) {
                    throw new \Exception('save issue solution error');
                }
            }

            //4、保存纠纷的操作记录
            $issueProcessList = !empty($detail['process_dto_list']) ? $detail['process_dto_list'] : [];
            if (!empty($issueProcessList['api_issue_process_dto'])) {
                $this->saveIssueProcess($accountId, $issueId, $issueProcessList['api_issue_process_dto']);
            }

            //提交事务
            $dbTransaction->commit();
            //删除处理成功的数据
            $tmpIssue->delete();
            return true;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
            $dbTransaction->rollBack();
            $this->exceptionMessage = $e->getMessage();
            return false;
        }
    }

    /**
     * 保存纠纷操作记录
     * @param $accountId 账号ID
     * @param $issueProcessList 操作列表
     */
    public function saveIssueProcess($accountId, $issueId, $issueProcessList)
    {
        if (empty($issueId) || empty($issueProcessList)) {
            return false;
        }

        //删除添加的操作记录
        $processDel = AliexpressDisputeProcess::deleteAll([
            'account_id' => $accountId,
            'platform_dispute_id' => $issueId,
        ]);

        //删除添加的附件
        $attachmentDel = AliexpressDisputeAttachments::deleteAll([
            'account_id' => $accountId,
            'platform_dispute_id' => $issueId,
        ]);

        if ($processDel === false || $attachmentDel === false) {
            return false;
        }

        foreach ($issueProcessList as $process) {
            //重新添加操作记录
            $issueProcess = new AliexpressDisputeProcess();
            $issueProcess->platform_dispute_id = $issueId;
            $issueProcess->account_id = $accountId;
            $issueProcess->platform_dispute_process_id = !empty($process['id']) ? $process['id'] : '';
            $issueProcess->action_type = $process['action_type'];
            $issueProcess->content = !empty($process['content']) ? $process['content'] : '';
            $issueProcess->gmt_create = $process['gmt_create'];
            $issueProcess->has_buyer_video = !empty($process['has_buyer_video']) ? 1 : 0;
            $issueProcess->has_seller_video = !empty($process['has_seller_video']) ? 1 : 0;
            $issueProcess->receive_goods = !empty($process['receive_goods']) ? $process['receive_goods'] : '';
            $issueProcess->submit_member_type = !empty($process['submit_member_type']) ? $process['submit_member_type'] : '';
            $issueProcess->create_by = 'system';
            $issueProcess->create_time = date('Y-m-d H:i:s', time());
            $issueProcess->modify_by = 'system';
            $issueProcess->modify_time = date('Y-m-d H:i:s', time());

            if ($issueProcess->save(false)) {
                //重新添加附件
                if (!empty($process['attachments']) && !empty($process['attachments']['api_attachment_dto'])) {
                    $attachments = $process['attachments']['api_attachment_dto'];

                    foreach ($attachments as $attachment) {
                        $issueAttachment = new AliexpressDisputeAttachments();
                        $issueAttachment->platform_dispute_id = $issueId;
                        $issueAttachment->process_id = $issueProcess->id;
                        $issueAttachment->platform_dispute_process_id = !empty($attachment['issue_process_id']) ? $attachment['issue_process_id'] : '';
                        $issueAttachment->account_id = $accountId;
                        $issueAttachment->gmt_create = $attachment['gmt_create'];
                        $issueAttachment->file_path = $attachment['file_path'];
                        $issueAttachment->owner = $attachment['owner'];
                        $issueAttachment->create_by = 'system';
                        $issueAttachment->create_time = date('Y-m-d H:i:s', time());
                        $issueAttachment->modify_by = 'system';
                        $issueAttachment->modify_time = date('Y-m-d H:i:s', time());
                        $issueAttachment->save();
                    }
                }
            }
        }

        return true;
    }

    /**
     * 保存纠纷协商方案
     * @param $accountId 账号ID
     * @param $solutionList 方案列表
     * @param $type 方案类型
     */
    public function saveIssueSolution($accountId, $solutionList, $type = self::SOULUTION_TYPE_BUYER)
    {
        if (empty($solutionList) || empty($solutionList['solution_api_dto'])) {
            return false;
        }
        $solutionList = $solutionList['solution_api_dto'];

        foreach ($solutionList as $solution) {

            $issueId = !empty($solution['issue_id']) ? $solution['issue_id'] : '';
            $solutionId = !empty($solution['id']) ? $solution['id'] : '';

            if (empty($issueId) || empty($solutionId)) {
                continue;
            }

            $issueSolution = AliexpressDisputeSolution::findOne([
                'platform_dispute_id' => $issueId,
                'account_id' => $accountId,
                'solution_id' => $solutionId,
            ]);
            if (empty($issueSolution)) {
                $issueSolution = new AliexpressDisputeSolution();
                $issueSolution->create_by = 'system';
                $issueSolution->create_time = date('Y-m-d H:i:s', time());
            }

            $issueSolution->platform_dispute_id = $issueId;
            $issueSolution->account_id = $accountId;
            $issueSolution->seller_ali_id = '';
            $issueSolution->gmt_modified = $solution['gmt_modified'];
            $issueSolution->order_id = $solution['order_id'];
            $issueSolution->refund_money = $solution['refund_money'];
            $issueSolution->refund_money_currency = $solution['refund_money_currency'];
            $issueSolution->gmt_create = $solution['gmt_create'];
            $issueSolution->version = !empty($solution['version']) ? $solution['version'] : '';
            $issueSolution->content = !empty($solution['content']) ? $solution['content'] : '';
            $issueSolution->buyer_ali_id = '';
            $issueSolution->is_default = !empty($solution['is_default']) ? $solution['is_default'] : '';
            $issueSolution->refund_money_post = $solution['refund_money_post'];
            $issueSolution->refund_money_post_currency = $solution['refund_money_post_currency'];
            $issueSolution->solution_id = $solutionId;
            $issueSolution->solution_type = $solution['solution_type'];
            $issueSolution->solution_owner = $solution['solution_owner'];
            $issueSolution->status = $solution['status'];
            $issueSolution->reached_type = !empty($solution['reached_type']) ? $solution['reached_type'] : '';
            $issueSolution->reached_time = !empty($solution['reached_time']) ? $solution['reached_time'] : '';
            $issueSolution->modify_by = 'system';
            $issueSolution->modify_time = date('Y-m-d H:i:s', time());
            $issueSolution->buyer_accept_time = !empty($solution['buyer_accept_time']) ? $solution['buyer_accept_time'] : '';
            $issueSolution->logistics_fee_amount = !empty($solution['logistics_fee_amount']) ? $solution['logistics_fee_amount'] : '';
            $issueSolution->logistics_fee_amount_currency = !empty($solution['logistics_fee_amount_currency']) ? $solution['logistics_fee_amount_currency'] : '';
            $issueSolution->logistics_fee_bear_role = !empty($solution['logistics_fee_bear_role']) ? $solution['logistics_fee_bear_role'] : '';
            $issueSolution->seller_accept_time = !empty($solution['seller_accept_time']) ? $solution['seller_accept_time'] : '';

            if (!$issueSolution->save(false)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取异常信息
     */
    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }
}