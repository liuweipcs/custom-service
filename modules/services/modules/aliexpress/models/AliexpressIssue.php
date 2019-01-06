<?php

namespace app\modules\services\modules\aliexpress\models;

use app\modules\mails\models\AliexpressTask;
use app\modules\services\modules\aliexpress\components\TaobaoQimenApi;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\mails\models\AliexpressInbox;
use app\modules\systems\models\AliexpressLog;
use app\modules\mails\models\AliexpressIssueTmp;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressDisputeDetail;
use app\modules\mails\models\AliexpressDisputeSolution;
use app\modules\mails\models\AliexpressDisputeProcess;
use app\modules\mails\models\AliexpressDisputeAttachments;

/**
 * 速卖通纠纷信息处理
 */
class AliexpressIssue
{
    public $errorMessage = null;

    /**
     * 获取账号的纠纷信息
     */
    public function getAccountIssue($accountId)
    {
        try {
            $aliexpressTaskModel = new AliexpressTask();
            //添加任务
            $taskId = $aliexpressTaskModel->getAdd($accountId, 'Aliexpressissue');
            //查询任务是否已经运行
            if ($aliexpressTaskModel->checkIsRunning($accountId, 'Aliexpressissue')) {
                $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = 'Task Running';
                $TaskModel->save();
                return false;
            }
            //获取账号信息
            $accountInfo = Account::findById($accountId);
            if (empty($accountInfo)) {
                $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = '账号不存在';
                $TaskModel->save();
                return false;
            }
            //获取erp账号信息
            $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
            if (empty($erpAccountInfo)) {
                $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = 'ERP系统对应账号不存在';
                $TaskModel->save();
                return false;
            }
            //将当前任务状态设为运行中
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = 1;
            $TaskModel->save();

            //获取速卖通纠纷列表
            //分别拉取三种状态的纠纷
            //$issueStatusArr = ['processing', 'finish', 'canceled_issue'];
            //数据量过大，只拉取处理中的纠纷
            $issueStatusArr = ['processing'];

            foreach ($issueStatusArr as $issueStatus) {
                //当前页数
                $pageCur = 1;
                //每页大小
                $pageSize = 50;

                do {
                    //获取纠纷列表
                    $issueList = self::getIssueList($erpAccountInfo, $issueStatus, $pageCur, $pageSize);
                    if (empty($issueList)) {
                        break;
                    }

                    if (!empty($issueList['data_list']) && !empty($issueList['data_list']['issue_api_issue_dto'])) {
                        $issueList = $issueList['data_list']['issue_api_issue_dto'];
                    } else {
                        $issueList = [];
                    }

                    if (!empty($issueList)) {
                        foreach ($issueList as $issue) {
                            //如果纠纷ID为空则跳过
                            if (empty($issue['issue_id'])) {
                                continue;
                            }
                            //如果纠纷信息已经在mongodb中，则跳过
                            $aliexpressIssue = AliexpressIssueTmp::findOne(['issue_id' => $issue['issue_id']]);
                            if (!empty($aliexpressIssue)) {
                                continue;
                            }
                            $aliexpressIssue = new AliexpressIssueTmp();
                            $aliexpressIssue->account_id = $accountId;
                            $aliexpressIssue->issue_id = $issue['issue_id'];
                            $aliexpressIssue->info = json_encode($issue);
                            $aliexpressIssue->create_time = date('Y-m-d H:i:s');
                            //获取纠纷详情
                            $issueDetail = self::getIssuedetails($erpAccountInfo, $issue['issue_id'], $issue['buyer_login_id']);
                            if (empty($issueDetail) || empty($issueDetail['result_object'])) {
                                continue;
                            }
                            $aliexpressIssue->detail = json_encode($issueDetail['result_object']);
                            //保存纠纷信息到mongodb中
                            $aliexpressIssue->save(false);
                        }
                    }
                    $pageCur++;
                } while (!empty($issueList));
            }

            //任务执行完成
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = 2;
            $TaskModel->save();
            return true;
        } catch (\Exception $e) {
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = -1;
            $TaskModel->errors = $e->getMessage();
            $TaskModel->save();
            return false;
        }
    }

    /**
     * 获取速卖通纠纷列表
     * @param $erpAccountInfo erp账号信息
     * @param string $issueStatus 纠纷状态
     * @param int $pageCur 当前页数
     * @param int $pageSize 每页大小
     */
    public function getIssueList($erpAccountInfo, $issueStatus = 'processing', $pageCur = 1, $pageSize = 50)
    {
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'erp账号信息不能为空';
            return false;
        }
        $taobaoQimenApi = new TaobaoQimenApi($erpAccountInfo->app_key, $erpAccountInfo->secret_key, $erpAccountInfo->access_token);
        $request = new \MinxinAliexpressDisputelistinformationRequest();
        //设置账号ID
        $request->setAccountId($erpAccountInfo->id);
        //设置纠纷状态
        $request->setIssueStatus($issueStatus);
        //设置当前页数
        $request->setCurrentPage($pageCur);
        //设置每页大小
        $request->setPageSize($pageSize);
        //请求接口
        $taobaoQimenApi->doRequest($request);

        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            return false;
        }
        $data = $taobaoQimenApi->getResponse();
        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        if (empty($data)) {
            $this->errorMessage = '解析json数据失败';
            return false;
        }
        return $data;
    }

    /**
     * 获取速卖通订单详情
     * @param $erpAccountInfo erp账号信息
     * @param $issueId 纠纷ID
     * @param $buyerLoginId 买家登录ID
     */
    public function getIssuedetails($erpAccountInfo, $issueId, $buyerLoginId)
    {
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'erp账号信息不能为空';
            return false;
        }
        $taobaoQimenApi = new TaobaoQimenApi($erpAccountInfo->app_key, $erpAccountInfo->secret_key, $erpAccountInfo->access_token);
        $request = new \MinxinAliexpressObtainingconsultativedataRequest();
        //设置账号ID
        $request->setAccountId($erpAccountInfo->id);
        //设置纠纷ID
        $request->setIssueId($issueId);
        //设置买家登录ID
        $request->setBuyerLoginId($buyerLoginId);
        $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            return false;
        }
        $data = $taobaoQimenApi->getResponse();
        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        if (empty($data)) {
            $this->errorMessage = '解析json数据失败';
            return false;
        }
        return $data;
    }

    /**
     * @desc 更新纠纷信息
     * @param $accountId 账号ID
     * @param $issueId 纠纷ID
     * @param $buyerLoginId 买家登录ID
     */
    public function updateissueinfo($accountId, $issueId, $buyerLoginId)
    {
        //获取账号信息
        $accountInfo = Account::findById($accountId);
        if (empty($accountInfo)) {
            $this->errors = '账号不存在';
            return false;
        }
        //获取纠纷ID
        if (empty($issueId)) {
            $this->errorMessage = '纠纷ID不能为空';
            return false;
        }

        $aliexpressTaskModel = new AliexpressTask();
        //添加任务
        $taskId = $aliexpressTaskModel->getAdd($accountId, 'Aliexpressissue_update');

        //获取erp账号信息
        $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
        if (empty($erpAccountInfo)) {
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = -1;
            $TaskModel->errors = 'ERP系统对应账号不存在';
            $TaskModel->save();
            return false;
        }
        //将当前任务状态设为运行中
        $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
        $TaskModel->status = 1;
        $TaskModel->save();

        try {
            //通过速卖通接口获取纠纷信息
            $issueInfo = self::getIssuedetails($erpAccountInfo, $issueId, $buyerLoginId);
            $issueInfo = $issueInfo['result_object'];

            if (empty($issueInfo)) {
                $this->errors = '解纷信息不存在';
                return false;
            }
            //纠纷列表中的信息
            $disputeList = AliexpressDisputeList::find()->where(['platform_dispute_id' => $issueId])->one();
            //账号ID
            $accountId = !empty($disputeList['account_id']) ? $disputeList['account_id'] : '';

            if (!empty($issueInfo['issue_status']) && ($disputeList->issue_status != $issueInfo['issue_status'])) {
                $disputeList->issue_status = $issueInfo['issue_status'];
                $disputeList->modify_by = '计划任务';
                $disputeList->modify_time = date('Y-m-d H:i:s', time());

                if ($disputeList->save(false) === false) {
                    $this->errors = '更新解纷列表失败';
                    return false;
                }
            }

            //纠纷完结,设置成已处理状态
            if (!empty($issueInfo['issue_status']) && $issueInfo['issue_status'] == 'finish') {
                $disputeList->is_handle = 1;
                $disputeList->save();
            }

            //纠纷详情中的信息
            $disputeDetail = AliexpressDisputeDetail::find()->where(['platform_dispute_id' => $issueId])->one();
            if (empty($disputeDetail)) {
                $disputeDetail = new AliexpressDisputeDetail();
                $disputeDetail->create_by = 'system';
                $disputeDetail->create_time = date('Y-m-d H:i:s', time());
                $disputeDetail->platform_dispute_id = $issueId;
                $disputeDetail->account_id = $accountId;
                $disputeDetail->buyer_login_id = $issueInfo['buyer_login_id'];
                $disputeDetail->platform_parent_order_id = !empty($issueInfo['parent_order_id']) ? $issueInfo['parent_order_id'] : '';
                $disputeDetail->platform_order_id = $issueInfo['order_id'];
                $disputeDetail->buyer_aliid = '';
                $disputeDetail->issue_reason_id = !empty($issueInfo['issue_reason_id']) ? $issueInfo['issue_reason_id'] : '';
                $disputeDetail->issue_reason = !empty($issueInfo['issue_reason']) ? $issueInfo['issue_reason'] : '';
                $disputeDetail->refund_money_max = $issueInfo['refund_money_max'];
                $disputeDetail->refund_money_max_local = $issueInfo['refund_money_max_local'];
                $disputeDetail->product_name = $issueInfo['product_name'];
                $disputeDetail->product_price = $issueInfo['product_price'];
                $disputeDetail->gmt_create = $issueInfo['gmt_create'];
                $disputeDetail->refund_money_max_currency = $issueInfo['refund_money_max_currency'];
                $disputeDetail->refund_money_max_local_currency = $issueInfo['refund_money_max_local_currency'];
                $disputeDetail->product_price_currency = $issueInfo['product_price_currency'];
                $disputeDetail->after_sale_warranty = !empty($issueInfo['after_sale_warranty']) ? intval($issueInfo['after_sale_warranty']) : 0;
            }
            $disputeDetail->buyer_return_logistics_company = !empty($issueInfo['buyer_return_logistics_company']) ? $issueInfo['buyer_return_logistics_company'] : '';
            $disputeDetail->buyer_return_no = !empty($issueInfo['buyer_return_no']) ? $issueInfo['buyer_return_no'] : '';
            $disputeDetail->buyer_return_logistics_lp_no = !empty($issueInfo['buyer_return_logistics_lp_no']) ? $issueInfo['buyer_return_logistics_lp_no'] : '';
            $disputeDetail->issue_status = !empty($issueInfo['issue_status']) ? $issueInfo['issue_status'] : '';
            $disputeDetail->modify_by = '计划任务';
            $disputeDetail->modify_time = date('Y-m-d H:i:s', time());
            if (!empty($issueInfo['platform_solution_list'])) {
                 $disputeDetail->refund_money_post =  $issueInfo['platform_solution_list']['solution_api_dto'][0]['refund_money_post'];
                 $disputeDetail->refund_money_post_currency =  $issueInfo['platform_solution_list']['solution_api_dto'][0]['refund_money_post_currency'];
                 $disputeDetail->solution_owner = $issueInfo['platform_solution_list']['solution_api_dto'][0]['solution_owner'];
            }elseif(!empty($issueInfo['seller_solution_list'])){
                 $disputeDetail->refund_money_post =  $issueInfo['seller_solution_list']['solution_api_dto'][0]['refund_money_post'];
                 $disputeDetail->refund_money_post_currency =  $issueInfo['seller_solution_list']['solution_api_dto'][0]['refund_money_post_currency'];
                 $disputeDetail->solution_owner = $issueInfo['seller_solution_list']['solution_api_dto'][0]['solution_owner'];
            }elseif(!empty($issueInfo['buyer_solution_list'])){
                 $disputeDetail->refund_money_post =  $issueInfo['buyer_solution_list']['solution_api_dto'][0]['refund_money_post'];
                 $disputeDetail->refund_money_post_currency =  $issueInfo['buyer_solution_list']['solution_api_dto'][0]['refund_money_post_currency'];
                 $disputeDetail->solution_owner = 'seller';     
            }elseif($issueInfo['issue_status'] == 'canceled_issue'){
                $disputeDetail->refund_money_post = '0.00';
                $disputeDetail->refund_money_post_currency = 'USD';
                $disputeDetail->solution_owner = 'not_issue';
            }

            if ($disputeDetail->save(false) === false) {
                $this->errors = '更新解纷详情失败';
                return false;
            }

            //纠纷协商方案
            $solutionList = [];
            if (!empty($issueInfo['seller_solution_list'])) {
                $solutionList = array_merge($solutionList, $issueInfo['seller_solution_list']['solution_api_dto']);
            }
            if (!empty($issueInfo['buyer_solution_list'])) {
                $solutionList = array_merge($solutionList, $issueInfo['buyer_solution_list']['solution_api_dto']);
            }
            if (!empty($issueInfo['platform_solution_list'])) {
                $solutionList = array_merge($solutionList, $issueInfo['platform_solution_list']['solution_api_dto']);
            }
            if (!empty($solutionList)) {
                foreach ($solutionList as $solution) {
                    $disputeSolution = AliexpressDisputeSolution::find()->where([
                        'platform_dispute_id' => $issueId,
                        'solution_id' => $solution['id'],
                    ])->one();

                    if (empty($disputeSolution)) {
                        $disputeSolution = new AliexpressDisputeSolution();
                        $disputeSolution->create_by = 'system';
                        $disputeSolution->create_time = date('Y-m-d H:i:s', time());
                    }
                    $disputeSolution->platform_dispute_id = $issueId;
                    $disputeSolution->account_id = $accountId;
                    $disputeSolution->seller_ali_id = '';
                    $disputeSolution->gmt_modified = $solution['gmt_modified'];
                    $disputeSolution->order_id = $solution['order_id'];
                    $disputeSolution->refund_money = $solution['refund_money'];
                    $disputeSolution->refund_money_currency = $solution['refund_money_currency'];
                    $disputeSolution->gmt_create = $solution['gmt_create'];
                    $disputeSolution->version = !empty($solution['version']) ? $solution['version'] : '';
                    $disputeSolution->content = !empty($solution['content']) ? $solution['content'] : '';
                    $disputeSolution->buyer_ali_id = '';
                    $disputeSolution->is_default = !empty($solution['is_default']) ? $solution['is_default'] : '';
                    $disputeSolution->refund_money_post = $solution['refund_money_post'];
                    $disputeSolution->refund_money_post_currency = $solution['refund_money_post_currency'];
                    $disputeSolution->solution_id = $solution['id'];
                    $disputeSolution->solution_type = $solution['solution_type'];
                    $disputeSolution->solution_owner = $solution['solution_owner'];
                    $disputeSolution->status = $solution['status'];
                    $disputeSolution->reached_type = !empty($solution['reached_type']) ? $solution['reached_type'] : '';
                    $disputeSolution->reached_time = !empty($solution['reached_time']) ? $solution['reached_time'] : '';
                    $disputeSolution->modify_by = '计划任务';
                    $disputeSolution->modify_time = date('Y-m-d H:i:s', time());
                    $disputeSolution->buyer_accept_time = !empty($solution['buyer_accept_time']) ? $solution['buyer_accept_time'] : '';
                    $disputeSolution->logistics_fee_amount = !empty($solution['logistics_fee_amount']) ? $solution['logistics_fee_amount'] : '';
                    $disputeSolution->logistics_fee_amount_currency = !empty($solution['logistics_fee_amount_currency']) ? $solution['logistics_fee_amount_currency'] : '';
                    $disputeSolution->logistics_fee_bear_role = !empty($solution['logistics_fee_bear_role']) ? $solution['logistics_fee_bear_role'] : '';
                    $disputeSolution->seller_accept_time = !empty($solution['seller_accept_time']) ? $solution['seller_accept_time'] : '';

                    if ($disputeSolution->save(false) === false) {
                        $this->errors = '更新解纷协商方案失败';
                        return false;
                    }
                }
            }

            //纠纷操作记录
            if (!empty($issueInfo['process_dto_list'])) {
                $processList = $issueInfo['process_dto_list']['api_issue_process_dto'];

                if (!empty($processList)) {
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
                        $this->errors = '删除操作记录或附件失败';
                        return false;
                    }

                    foreach ($processList as $process) {
                        //重新添加操作记录
                        $disputeProcess = new AliexpressDisputeProcess();
                        $disputeProcess->platform_dispute_id = $issueId;
                        $disputeProcess->account_id = $accountId;
                        $disputeProcess->platform_dispute_process_id = !empty($process['id']) ? $process['id'] : '';
                        $disputeProcess->action_type = $process['action_type'];
                        $disputeProcess->content = !empty($process['content']) ? $process['content'] : '';
                        $disputeProcess->gmt_create = $process['gmt_create'];
                        $disputeProcess->has_buyer_video = !empty($process['has_buyer_video']) ? 1 : 0;
                        $disputeProcess->has_seller_video = !empty($process['has_seller_video']) ? 1 : 0;
                        $disputeProcess->receive_goods = !empty($process['receive_goods']) ? $process['receive_goods'] : '';
                        $disputeProcess->submit_member_type = !empty($process['submit_member_type']) ? $process['submit_member_type'] : '';
                        $disputeProcess->create_by = 'system';
                        $disputeProcess->create_time = date('Y-m-d H:i:s', time());
                        $disputeProcess->modify_by = 'system';
                        $disputeProcess->modify_time = date('Y-m-d H:i:s', time());

                        if ($disputeProcess->save()) {
                            //重新添加附件
                            if (!empty($process['attachments']) && !empty($process['attachments']['api_attachment_dto'])) {
                                $attachments = $process['attachments']['api_attachment_dto'];

                                foreach ($attachments as $attachment) {
                                    $disputeAttachment = new AliexpressDisputeAttachments();
                                    $disputeAttachment->platform_dispute_id = $issueId;
                                    $disputeAttachment->process_id = $disputeProcess->id;
                                    $disputeAttachment->platform_dispute_process_id = !empty($attachment['issue_process_id']) ? $attachment['issue_process_id'] : '';
                                    $disputeAttachment->account_id = $accountId;
                                    $disputeAttachment->gmt_create = $attachment['gmt_create'];
                                    $disputeAttachment->file_path = $attachment['file_path'];
                                    $disputeAttachment->owner = $attachment['owner'];
                                    $disputeAttachment->create_by = 'system';
                                    $disputeAttachment->create_time = date('Y-m-d H:i:s', time());
                                    $disputeAttachment->modify_by = 'system';
                                    $disputeAttachment->modify_time = date('Y-m-d H:i:s', time());
                                    $disputeAttachment->save();
                                }
                            }
                        }
                    }
                }
            }
            //任务执行完成
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = 2;
            $TaskModel->save();
            return true;
        } catch (\Exception $e) {
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = -1;
            $TaskModel->errors = $e->getMessage();
            $TaskModel->save();
            return false;
        }
    }

    /**
     * @desc 更新消息的处理状态
     * @param unknown $erpAccountInfo
     * @param unknown $channelId
     * @param number $status
     * @return boolean|\app\modules\services\modules\aliexpress\components\unknown
     */
    public function updateMessageProcessingState($accountId, $channelId, $status = 0)
    {
        $accountInfo = Account::findById($accountId);
        if (empty($accountInfo)) {
            $this->errorMessage = '账号不存在';
            return false;
        }
        $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'ERP系统对应账号不存在';
            return false;
        }
        $appKey = $erpAccountInfo->app_key;
        $secretKey = $erpAccountInfo->secret_key;
        $accessToken = $erpAccountInfo->access_token;
        $erpAccountId = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        $request = new \MinxinAliexpressMessageupdateprocessingstateRequest;
        $request->setAccountId($erpAccountId);
        $request->setChannelId($channelId);
        $request->setDealStat((int)$status);
        $response = $taobaoQimenApi->doRequest($request);
        $stat = ['未处理', '已处理'];
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            $aliexpressLogModel = new AliexpressLog();
            $data['create_user_name'] = \Yii::$app->user->id;
            $data['channel_id'] = $channelId;
            $data['account_id'] = $accountId;
            $data['update_content'] = '更新已处理时ID为' . $channelId . '站内信/订单留言更新处理状态时接口报错，错误信息为：' .
                $this->errorMessage;
            $data['create_time'] = date('Y-m-d H:i:s');
            $aliexpressLogModel->getAdd($data);
            $this->errorMsg = $response->error_message;
            return false;
        }
        $aliexpressLogModel = new AliexpressLog();
        $data['create_user_name'] = \Yii::$app->user->id;
        $data['channel_id'] = $channelId;
        $data['account_id'] = $accountId;
        $data['update_content'] = '更新了关系ID为' . $channelId . '站内信/订单留言更新处理状态为' . $stat[$status];
        $data['create_time'] = date('Y-m-d H:i:s');
        $aliexpressLogModel->getAdd($data);
        $AliexpressInbox = AliexpressInbox::findOne(['channel_id' => $channelId]);
        //更新处理状态
        $AliexpressInbox->deal_stat = 1;
        $AliexpressInbox->save();

        return true;
    }

    /**
     * @desc 将消息标记成已读
     * @param unknown $erpAccountInfo
     * @param unknown $channelId
     * @return boolean|\app\modules\services\modules\aliexpress\components\unknown
     */
    public function markMessageBeenRead($accountId, $channelId)
    {
        $accountInfo = Account::findById($accountId);
        if (empty($accountInfo)) {
            $this->errorMessage = '账号不存在';
            return false;
        }
        $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'ERP系统对应账号不存在';
            return false;
        }
        $appKey = $erpAccountInfo->app_key;
        $secretKey = $erpAccountInfo->secret_key;
        $accessToken = $erpAccountInfo->access_token;
        $erpAccountId = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        $request = new \MinxinAliexpressOrdermessageupdatehasbeenreadRequest;
        $request->setAccountId($erpAccountId);
        $request->setChannelId($channelId);
        $response = $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            $aliexpressLogModel = new AliexpressLog();
            $data['create_user_name'] = \Yii::$app->user->id;
            $data['channel_id'] = $channelId;
            $data['account_id'] = $accountId;
            $data['update_content'] = '更新关系ID为' . $channelId . '站内信/订单留言更新处理状态时接口报错，错误信息为：' . $this->errorMessage;
            $data['create_time'] = date('Y-m-d H:i:s');
            $aliexpressLogModel->getAdd($data);
            $AliexpressInbox = AliexpressInbox::findOne(['channel_id' => $channelId]);
            $AliexpressInbox->read_stat = 2;
            $AliexpressInbox->unread_count = 0;
            $AliexpressInbox->save();
            return false;
        }
        $aliexpressLogModel = new AliexpressLog();
        $data['create_user_name'] = \Yii::$app->user->id;
        $data['channel_id'] = $channelId;
        $data['account_id'] = $accountId;
        $data['update_content'] = '更新了关系ID为' . $channelId . '站内信/订单留言更新处理状态为已读';
        $data['create_time'] = date('Y-m-d H:i:s');
        $aliexpressLogModel->getAdd($data);

        //更新成功则更新这条关系
        // $detail = new Detail();
        $AliexpressInbox = AliexpressInbox::findOne(['channel_id' => $channelId]);
        //$detail->getProductResponseByShortName($AliexpressInbox->channel_id,$AliexpressInbox->msg_sources,1,$AliexpressInbox->id);
        $AliexpressInbox->read_stat = 1;
        $AliexpressInbox->unread_count = 0;
        $AliexpressInbox->save();
        return true;
    }

    /**
     * @desc 获取错误信息
     * @return Ambigous <string, \app\modules\services\modules\aliexpress\components\string>
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @desc 添加消息
     * @param unknown $data
     * @return boolean
     */
    public function addMessage($data)
    {
        $accountId = isset($data['account_id']) ? $data['account_id'] : '';
        $accountInfo = Account::findById($accountId);
        if (empty($accountInfo)) {
            $this->errorMessage = '账号不存在';
            return false;
        }
        $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'ERP系统对应账号不存在';
            return false;
        }
        $appKey = $erpAccountInfo->app_key;
        $secretKey = $erpAccountInfo->secret_key;
        $accessToken = $erpAccountInfo->access_token;
        $erpAccountId = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        $request = new \MinxinAliexpressNewstationletterRequest;
        $request->setAccountId($erpAccountId);
        $request->setMessageType($data['message_type']);
        $request->setContent($data['content']);
        $request->setBuyerId($data['buyer_id']);
        $request->setSellerId($accountInfo->seller_id);
        if (!empty($data['imgPath']))
            $request->setImgPath($data['imgPath']);//图片地址
        if (!empty($data['extern_id']))
            $request->setExternId($data['extern_id']);
        $response = $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            /*状态更新失败*/
            $aliexpressLogModel = new AliexpressLog();
            $daraArr['create_user_name'] = 'system';
            $daraArr['channel_id'] = $data['channel_id'];
            $daraArr['account_id'] = $accountId;
            $daraArr['update_content'] = $this->errorMessage;
            $daraArr['create_time'] = date('Y-m-d H:i:s');
            $aliexpressLogModel->getAdd($daraArr);
            return false;
        }
        $aliexpressLogModel = new AliexpressLog();
        $Log['create_user_name'] = 'system';
        $Log['channel_id'] = $data['channel_id'];
        $Log['account_id'] = $accountId;
        $Log['update_content'] = '回复成功！';
        $Log['create_time'] = date('Y-m-d H:i:s');
        $aliexpressLogModel->getAdd($Log);
        return true;
    }

    public function uploadImage($accountId, $fileName, $filePath)
    {
        $accountInfo = Account::findById($accountId);
        if (empty($accountInfo)) {
            $this->errorMessage = '账号不存在';
            return false;
        }
        $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'ERP系统对应账号不存在';
            return false;
        }
        $appKey = $erpAccountInfo->app_key;
        $secretKey = $erpAccountInfo->secret_key;
        $accessToken = $erpAccountInfo->access_token;
        $erpAccountId = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        $request = new \MinxinAliexpressCustomerUploadimageforsdkRequest;
        $request->setAccountId($erpAccountId);
        $request->setFileName($fileName);
        $request->setImageBytes(base64_encode(file_get_contents($filePath)));
        $response = $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $aliexpressLogModel = new AliexpressLog();
            $arr['create_user_name'] = 'system';
            $arr['account_id'] = $accountId;
            $arr['update_content'] = '上传失败，错误代码为：' . $taobaoQimenApi->getErrorMessage();
            $arr['create_time'] = date('Y-m-d H:i:s');
            $arr['channel_id'] = '';
            $aliexpressLogModel->getAdd($arr);
            $this->errorMsg = $response->error_message;
            return false;
        } else {
            /*上传成功*/
            $aliexpressLogModel = new AliexpressLog();
            $Log['create_user_name'] = 'system';
            $Log['account_id'] = $accountId;
            $Log['update_content'] = '上传成功！';
            $Log['create_time'] = date('Y-m-d H:i:s');
            $Log['channel_id'] = '';
            $aliexpressLogModel->getAdd($Log);
            $result = $taobaoQimenApi->getResponse();
            return $result->photobank_url;
        }
    }
}
