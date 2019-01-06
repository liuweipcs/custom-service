<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/11 0011
 * Time: 下午 3:51
 */

namespace app\modules\services\modules\ebay\controllers;

use app\common\VHelper;
use app\modules\accounts\models\EbayCaseRefund;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\mails\models\EbayCase;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayCaseResponse;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\orders\models\Order;
use app\modules\products\models\EbaySiteMapAccount;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\systems\models\EbayAccount;
use PhpImap\Exception;
use yii\helpers\Json;
use yii\helpers\Url;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\AutoCode;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\systems\models\EbayApiTask;
use app\components\Controller;

class CaseController extends Controller
{
    private $ebayAccountModel;
    private $accountId;
    private $apiTaskModel;
    private $errorCode = 0;

    private $claim_amount;
    private $currency;

    private $send_failure_times = 2;

    public function actionIndex()
    {
        if(isset($_REQUEST['id']))
        {
            $account = trim($_REQUEST['id']);
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $this->accountId = $account;
                $accountName = Account::findById((int)$this->accountId)->account_name;
                $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
                if(empty($this->ebayAccountModel))
                    exit('无法获取账号信息。');
                if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_CASE,$this->accountId,240))
                {
                    echo "account:{$this->accountId};Task Running.".PHP_EOL;
                    exit;
                }
                ignore_user_abort(true);
                set_time_limit(7200);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_CASE;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();
                $maxStartTime = EbayApiTask::find()->select('max(start_time)')->where(['account_id'=>$this->accountId,'task_name'=>AccountTaskQueue::TASK_TYPE_CASE,'exec_status'=>2,'status'=>[2,3]])->asArray()->one()['max(start_time)'];
                if(empty($maxStartTime))
                    $caseCreationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z',time() - 29100);
                else
                    $caseCreationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z',strtotime($maxStartTime)-28800);
                $caseCreationDateRangeTo = date('Y-m-d\TH:i:s.000\Z',time() - 28800);

                $this->apiTaskModel->data_start_time = date('Y-m-d H:i:s',strtotime($caseCreationDateRangeFrom));
                $this->apiTaskModel->data_end_time = date('Y-m-d H:i:s',strtotime($caseCreationDateRangeTo));
                $this->apiTaskModel->save();

                if(strcasecmp($caseCreationDateRangeTo,$caseCreationDateRangeFrom) > 0)
                {
                    $refundInfo = EbayCaseRefund::findOne(['account_id'=>$account,'is_refund'=>EbayCaseRefund::STATUS_REFUND_YES]);
                    if(!empty($refundInfo))
                    {
                        $this->claim_amount = $refundInfo->claim_amount;
                        $this->currency = $refundInfo->currency;
                        $this->searchApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/casemanagement/search',['limit'=>20,'offset'=>1,'case_creation_date_range_from'=>$caseCreationDateRangeFrom,'case_creation_date_range_to'=>$caseCreationDateRangeTo],'get');}
                    else
                    {
                        $this->apiTaskModel->status = 2;
                        $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。帐号:{$accountName} 未设置为纠纷升级自动退款]";
                        $this->errorCode++;
                    }
                }
                else
                {
                    $this->apiTaskModel->status = 1;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。StartTime:{$caseCreationDateRangeFrom} 不能大于 EndTime:{$caseCreationDateRangeTo}。]";
                    $this->errorCode++;
                }
                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                    AccountTaskQueue::TASK_TYPE_CASE);
                if (!empty($accountTask))
                {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/case/index', ['id'=> $accountId]);
                }
                exit('DONE');
            }
        }
        else
        {
            $accountList = EbayCaseRefund::find()
                ->select('t.*')
                ->from(EbayCaseRefund::tableName().' as t')
                ->innerJoin(Account::tableName().' as t1','t1.id = t.account_id')
                ->where(['t1.status'=>Account::STATUS_VALID])
                ->andWhere(['t.is_refund'=>EbayCaseRefund::STATUS_REFUND_YES])
                ->all();
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->account_id,'type'=>AccountTaskQueue::TASK_TYPE_CASE,'platform_code'=>Platform::PLATFORM_CODE_EB])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->account_id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_CASE;
                    $accountTaskQenue->platform_code = Platform::PLATFORM_CODE_EB;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::TASK_TYPE_CASE],20);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/case/index', ['id'=> $accountId]);
                    sleep(1);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    public function actionIndexcc()
    {
        if(isset($_REQUEST['id']))
        {
            $account = trim($_REQUEST['id']);
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $this->accountId = $account;
                $accountName = Account::findById((int)$this->accountId)->account_name;
                $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
                if(empty($this->ebayAccountModel))
                    exit('无法获取账号信息。');
                if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_CASE,$this->accountId,240))
                {
                    echo "account:{$this->accountId};Task Running.".PHP_EOL;
                    exit;
                }
                ignore_user_abort(true);
                set_time_limit(7200);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_CASE;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = '2017-12-24 00:00:00';
                $this->apiTaskModel->save();

                $caseCreationDateRangeFrom = '2017-12-20T00:00:00.000Z';
                $caseCreationDateRangeTo = '2017-12-25T00:00:00.000Z';

                $this->apiTaskModel->data_start_time = date('Y-m-d H:i:s',strtotime($caseCreationDateRangeFrom));
                $this->apiTaskModel->data_end_time = date('Y-m-d H:i:s',strtotime($caseCreationDateRangeTo));
                $this->apiTaskModel->save();

                if(strcasecmp($caseCreationDateRangeTo,$caseCreationDateRangeFrom) > 0)
                {
                    $refundInfo = EbayCaseRefund::findOne(['account_id'=>$account,'is_refund'=>EbayCaseRefund::STATUS_REFUND_YES]);
                    if(!empty($refundInfo))
                    {
                        $this->claim_amount = $refundInfo->claim_amount;
                        $this->currency = $refundInfo->currency;
                        $this->searchApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/casemanagement/search',['limit'=>20,'offset'=>1,'case_creation_date_range_from'=>$caseCreationDateRangeFrom,'case_creation_date_range_to'=>$caseCreationDateRangeTo],'get');}
                    else
                    {
                        $this->apiTaskModel->status = 2;
                        $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。帐号:{$accountName} 未设置为纠纷升级自动退款]";
                        $this->errorCode++;
                    }
                }
                else
                {
                    $this->apiTaskModel->status = 1;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。StartTime:{$caseCreationDateRangeFrom} 不能大于 EndTime:{$caseCreationDateRangeTo}。]";
                    $this->errorCode++;
                }
                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                    AccountTaskQueue::TASK_TYPE_CASE);
                if (!empty($accountTask))
                {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/case/index', ['id'=> $accountId]);
                }
                exit('DONE');
            }
        }
        else
        {
            $accountList = EbayCaseRefund::find()
                ->select('t.*')
                ->from(EbayCaseRefund::tableName().' as t')
                ->innerJoin(Account::tableName().' as t1','t1.id = t.account_id')
                ->where(['t1.status'=>Account::STATUS_VALID])
                ->andWhere(['t.is_refund'=>EbayCaseRefund::STATUS_REFUND_YES])
                ->all();
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::TASK_TYPE_CASE,'platform_code'=>Platform::PLATFORM_CODE_EB])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->account_id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_CASE;
                    $accountTaskQenue->platform_code = Platform::PLATFORM_CODE_EB;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::TASK_TYPE_CASE],20);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/case/index', ['id'=> $accountId]);
                    sleep(1);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    protected function searchApi($token,$site,$serverUrl,$params,$method)
    {
        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
        $api->urlParams = $params;
        $response = $api->sendHttpRequest();
        if(empty($response))
        {
            $this->apiTaskModel->status = 2;
            $this->apiTaskModel->error = "[错误码：{$this->errorCode}。".$api->getServerUrl()."无响应。]";
            $this->apiTaskModel->save();
            $this->errorCode++;
        }
        else
        {
            echo '<hr/>',$response->paginationOutput->totalPages,'---',$params['offset'],'<br/>';
            ob_flush();
            flush();
            if($params['offset'] == 1)
            {
                $this->apiTaskModel->status = 3;
                if(empty($response->members))
                {
                    $this->apiTaskModel->status = 2;
                    $this->apiTaskModel->error = "[错误码：{$this->errorCode}。".$api->getServerUrl()."拉取无数据。]";
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                }
            }

            foreach ($response->members as $member)
            {
                $this->detailApi($token,'','https://api.ebay.com/post-order/v2/casemanagement/'.$member->caseId,'get');
            }
            if(isset($params['offset']) && $response->paginationOutput->totalPages > $params['offset'])
            {
                $params['offset']++;
                $this->searchApi($token,$site,$serverUrl,$params,$method);
            }
        }
    }

    protected function detailApi($token,$site,$serverUrl,$method)
    {
        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
        $response = $api->sendHttpRequest();
        if(empty($response))
        {
            if(isset($this->apiTaskModel))
            {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}拉取无数据。]";
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
            else
            {
                echo $serverUrl,'拉取无数据。';
            }
        }
        else
        {
            if(isset($response->status))
            {
                if($response->status == 'CLOSED' || $response->status == 'CS_CLOSED')
                {
                    var_dump($response->caseId);
                    $this->apiTaskModel->status = 2;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。个案编号：{$response->caseId}已经关闭。]";
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                    return ;
                }
                else
                    $this->handleResponse($response);
            }
            else
            {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}拉取数据状态异常。]";
                $this->apiTaskModel->save();
                $this->errorCode++;
                return ;
            }
        }
    }

    protected function handleResponse($data)
    {
        $caseModel = EbayCase::findOne(['case_id'=>$data->caseId]);
        if(!empty($caseModel))
        {
            return ;
        }

        $caseModel = new EbayCase();
        $caseModel->case_id = $data->caseId;
        $caseModel->return_id = isset($data->returnId) ? $data->returnId : '';
        $caseModel->account_id = $this->accountId;
        $caseModel->case_type = isset($data->caseType) ? array_search($data->caseType,EbayCase::$caseTypeMap) : 0;
        $caseModel->item_id = $data->itemId;
        $caseModel->transaction_id = $data->transactionId;
        $caseModel->claim_amount = isset($data->claimAmount) ? $data->claimAmount->value : '';
        $caseModel->currency = isset($data->claimAmount) ? $data->claimAmount->currency : '';
        $caseModel->initiator = isset($data->initiator) ? array_search(trim($data->initiator),EbayInquiry::$initiatorMap) : 0;
        $caseModel->case_quantity = isset($data->caseQuantity) ? $data->caseQuantity : '';
        $caseModel->creation_date = isset($data->creationDate) ? date('Y-m-d H:i:s',strtotime($data->creationDate->value)) : null;
        $caseModel->last_modified_date = isset($data->lastModifiedDate) ? date('Y-m-d H:i:s',strtotime($data->lastModifiedDate->value)) : null;
        $caseModel->seller_closure_reason = isset($data->sellerClosureReason) ? $data->sellerClosureReason : '';
        $caseModel->buyer_closure_reason = isset($data->buyerClosureReason) ? $data->buyerClosureReason : '';
        $caseModel->escalate_reason = isset($data->escalateReason) ? $data->escalateReason : '';
        $caseModel->status = isset($data->status) ? $data->status : '';
        $caseModel->buyer = isset($data->buyer) ? $data->buyer : '';

        $return_reason = 'DEFECTIVE_ITEM';
        $order_id = '';
        if($caseModel->case_type == EbayCase::CASE_TYPE_ITEM_NOT_RECEIVED)
        {
            $inquiryModel = EbayInquiry::findOne(['item_id'=>$caseModel->item_id,'transaction_id'=>$caseModel->transaction_id]);
            if(!empty($inquiryModel) && $inquiryModel->auto_refund != 0)
            {
                $this->apiTaskModel->status = 2;
                $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。未收到纠纷{$inquiryModel->inquiry_id}设置不自动退款。]";
                $this->apiTaskModel->save();
                $this->errorCode++;
                return ;
            }
            $order_id = $inquiryModel->order_id;
        }
        else
        {
            $returnModel = EbayReturnsRequests::findOne(['item_id'=>$caseModel->item_id,'transaction_id'=>$caseModel->transaction_id]);
            if(!empty($returnModel))
            {
                if($returnModel->auto_refund != 0)
                {
                    $this->apiTaskModel->status = 2;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。退款退货纠纷{$returnModel->return_id}设置不自动退款。]";
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                    return ;
                }
                $return_reason = $returnModel->return_reason;
            }
            $order_id = $returnModel->order_id;
        }

        //$finalCNY = VHelper::getTargetCurrencyAmt($caseModel->currency,Account::CURRENCY,$caseModel->claim_amount);
        $finalCNY = VHelper::getTargetCurrencyAmtKefu($caseModel->currency,Account::CURRENCY,$caseModel->claim_amount);
        if(isset($this->claim_amount)) {
            $max_claim_amout = $this->claim_amount;
        } else {
            $max_claim_amout = Account::ACCOUNT_PRICE;
        }
        if($finalCNY > $max_claim_amout)
        {
            $this->apiTaskModel->status = 2;
            $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。个案编号：{$caseModel->caseId}退款金额为：。]".$finalCNY."超出帐号设置的金额";
            $this->apiTaskModel->save();
            $this->errorCode++;
            return;
        }
        if($caseModel->status != 'CLOSED' && $caseModel->status != 'CS_CLOSED')
        {
            //升级case,自动退款
            $refundApi = new PostOrderAPI($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/casemanagement/'.$caseModel->case_id.'/issue_refund','post');
            $message = ['comments'=>['content'=>'']];
            $refundApi->setData($message);
            $refundResponse = $refundApi->sendHttpRequest();
            $responseModel = new EbayCaseResponse();
            $responseModel->case_id = $caseModel->case_id;
            $responseModel->type = 1;
            $responseModel->content = '';
            $responseModel->account_id = $this->accountId;
            if(empty($refundResponse))
            {
                $responseModel->status = 0;
                $responseModel->error = '自动退款调用接口失败,无返回值';
            }
            else
            {
                $responseModel->status = 1;
                $responseModel->error = '';
                $responseModel->refund_source = isset($refundResponse->refundResult->refundSource) ? $refundResponse->refundResult->refundSource : '';
                $responseModel->refund_status = $refundResponse->refundResult->refundStatus;
                if($refundResponse->refundResult->refundStatus == 'SUCCESS')
                {
                    $buyer = '';
                    if(isset($data->transactionId) && !empty($data->transactionId))
                    {
                        $orderinfo = Order::getOrderStackByTransactionId('EB',$data->transactionId);
                    }
                    else
                    {
                        $platformOrderId = $data->itemId.'-0';
                        $orderinfo = Order::getOrderStack('EB', $platformOrderId);
                    }

                    if (!empty($orderinfo))
                    {
                        $orderinfo = Json::decode(Json::encode($orderinfo), true);
                        if(isset($orderinfo['info']))
                        {
                            $buyer = $orderinfo['info']['buyer_id'];
                            $order_id = $orderinfo['info']['order_id'];
                        }
                    }
                    //建立退款售后处理单                    
                    $afterSalesOrderModel = new AfterSalesOrder();
                    $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                    $afterSalesOrderModel->transaction_id = $caseModel->transaction_id;
                    $afterSalesOrderModel->type = AfterSalesOrder::ORDER_TYPE_REFUND;
                    
                    //根据开return原因判断售后单责任归属部门
                    if($data->returnId){
                        $returnReason = EbayReturnsRequests::getReturnReason($data->returnId);
                        if(!empty($returnReason)){
                            $department_id = $returnReason['department_id'];
                            $reason_id = $returnReason['reason_id'];
                        }
                    }
                    
                    $afterSalesOrderModel->department_id=$department_id;
                    $afterSalesOrderModel->reason_id =$reason_id;
//                    $afterSalesOrderModel->reason_id = isset(RefundReturnReason::$returnReasonMaps[$return_reason]) ? RefundReturnReason::$returnReasonMaps[$return_reason]: 27;
                    $afterSalesOrderModel->platform_code = Platform::PLATFORM_CODE_EB;
                    $afterSalesOrderModel->order_id = $order_id;
                    $afterSalesOrderModel->account_id = $this->accountId;

//                    if($responseModel->refund_status == 'PENDING' || $responseModel->refund_status == 'OTHER')
//                        $afterSalesOrderModel->remark = $responseModel->refund_status;
                    $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                    $afterSalesOrderModel->approver = 'system';
                    $afterSalesOrderModel->approve_time = date('Y-m-d H:i:s');
                    $afterSalesOrderModel->buyer_id = isset($data->buyer) ? $data->buyer : $buyer;
                    $afterSalesOrderModel->account_name = Account::getAccountName($caseModel->account_id,Platform::PLATFORM_CODE_EB);

                    $afterSaleOrderRefund = new AfterSalesRefund();
                    $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
                    $afterSaleOrderRefund->refund_amount = $caseModel->claim_amount;
                    $afterSaleOrderRefund->currency = $caseModel->currency;
                    $afterSaleOrderRefund->transaction_id = $caseModel->transaction_id;
                    $afterSaleOrderRefund->order_id = $afterSalesOrderModel->order_id;
                    $afterSaleOrderRefund->platform_code = Platform::PLATFORM_CODE_EB;
                    $afterSaleOrderRefund->order_amount = $caseModel->claim_amount;
                    $afterSaleOrderRefund->reason_code = $afterSalesOrderModel->reason_id;
                    $afterSaleOrderRefund->refund_time = date('Y-m-d H:i:s');
                    $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                }
            }
            $transaction = EbayInquiry::getDb()->beginTransaction();
            $flag_case = false;
            try{
                $flag = $caseModel->save();
                if(!$flag)
                    $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。caseId:'.$data->caseId.'保存主表出错。'.VHelper::getModelErrors($caseModel).']';
                else
                    $flag_case = true;
            }catch(Exception $e)
            {
                $flag = false;
                $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。caseId:'.$data->caseId.'保存主表出错。'.VHelper::getModelErrors($caseModel).']';
            }
            try{
                $flag = $responseModel->save();
                if(!$flag)
                    $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。caseId:'.$data->caseId.'升级自动退款出错。'.VHelper::getModelErrors($responseModel).']';
                elseif(isset($afterSalesOrderModel))
                {
                    //return 升级自动退款登记退款单暂时取消 updateByAllen <2018-04-11> str
                    $flag = $afterSalesOrderModel->save();
                    if(!$flag)
                        $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。caseId:'.$data->caseId.'升级自动退款建立售后处理单出错。'.VHelper::getModelErrors($afterSalesOrderModel).']';
                    elseif(isset($afterSaleOrderRefund))
                    {
                        $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                        $flag = $afterSaleOrderRefund->save();
                        if(!$flag)
                            $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。caseId:'.$data->caseId.'升级自动退款建立售后退款单出错。'.VHelper::getModelErrors($afterSaleOrderRefund).']';
                        else
                            $afterSaleOrderRefund->audit($afterSalesOrderModel);
                    }
                    //return 升级自动退款登记退款单暂时取消 updateByAllen <2018-04-11> end
                }
            }catch(Exception $e){
                $flag = false;
                $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。caseID:'.$data->caseId.'升级自动退款出错。'.$e->getMessage().']';
            }

            if($flag)
            {
                echo 'commit';
                $transaction->commit();
            }
            else
            {
                echo 'rollback';
                $transaction->rollBack();
            }

            if(!$flag)
            {
                $this->apiTaskModel->status = 1;
                if($flag_case)
                {
                    $this->apiTaskModel->status = 2;
                }
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
        }
        echo $data->caseId,'<br>';
        ob_flush();
        flush();
    }

    public function actionOne()
    {
        $caseId = $_GET['case'];
        $this->accountId = $_GET['account'];
        $accountName = Account::findById((int)$this->accountId)->account_name;
        $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
        $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/casemanagement/'.$caseId,'get');
    }

    public function actionTestsearch()
    {
        $userToken = 'AgAAAA**AQAAAA**aAAAAA**NAgTWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6wNmISiCJCLpA6dj6x9nY+seQ**sHQDAA**AAMAAA**YF1h9yn8IX3rYvF+lhAv7EM6N8GBtMa0lGs99VMHDx6RQw0dmvH5RQoCEa4+XiMBZuPc1QQwFHey0lYsN6EOYIOEA2kjqY9ntLvODLJwbavuwvb7Vvgj2Av2vm72XyetofLe3uSttmjtsIWYWq0BS4zp6OEi6+uSwugIua0mThKtX5wQ0bVT4GSGrksnDoaHe/aWinhTQDPhkztF2jnNPuCQTJcl3KXVFwYPMCBLfmjp36cEESLoLgX8bz+akq+tYDqF3NQFci69EezZFVodYesnsrJsGiGDK5/f+o6nOLa6m9tl7lBQzYjRIZWB/s1kyTpm+9f9c7aNvcXEFPcfuxZBilW/x2UELaBrxT1EnJ8gyMlNgwahFl+3uaYeF3E/y/ZYxO48scxSys2RxsnhEktMg+tOR0n4gIQSmZXRlsS9Zkxl1m8C8sj79nh0DfrN58+NnNQxK9dt2jfzuM2rjrsaef7FhheMWQ4sQywwAr+lSJOvBzu5pdhFFsK39LpwmdbCHUuP9GjlXFq3Z8C0ryquRe7gKSkCUhrQbDugajRn8naBBt9POLxS+T0DIDzfc3r7EG5MCAZwcGGIXAyTY7QAV9SHPkuiwQmFtJchkUqHsT5axBvRzJEFttCrBRcHI1uUqyCBZZFRoc4uzDF7xoS8z/xd6qySGAhL1WwC1U7KilRTpL7VdBF/bNwX3bPxn8wBNWtJRpYOxAM37rpSLxYuBF2aTbgCvwoi6q8i2lQ5JhkXXxke4I0IYIO/HiPs';
        $fromtime = date('Y-m-d\TH:i:s.000Z',strtotime('2017-12-19 09:03:05') - 28800);
        $totime = date('Y-m-d\TH:i:s.000Z',strtotime('2017-12-21 18:01:27') - 28800);
        $apiDetail = new PostOrderAPI($userToken,'','https://api.ebay.com/post-order/v2/casemanagement/search?case_creation_date_range_from='.$fromtime.'&case_creation_date_range_to='.$totime,'get');
//        findClass($apiDetail->sendHttpRequest()->inquiryDetails,1,0);
        findClass($apiDetail->sendHttpRequest(),1);
    }

    public function actionTestdetail()
    {
        $userToken = 'AgAAAA**AQAAAA**aAAAAA**NAgTWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6wNmISiCJCLpA6dj6x9nY+seQ**sHQDAA**AAMAAA**YF1h9yn8IX3rYvF+lhAv7EM6N8GBtMa0lGs99VMHDx6RQw0dmvH5RQoCEa4+XiMBZuPc1QQwFHey0lYsN6EOYIOEA2kjqY9ntLvODLJwbavuwvb7Vvgj2Av2vm72XyetofLe3uSttmjtsIWYWq0BS4zp6OEi6+uSwugIua0mThKtX5wQ0bVT4GSGrksnDoaHe/aWinhTQDPhkztF2jnNPuCQTJcl3KXVFwYPMCBLfmjp36cEESLoLgX8bz+akq+tYDqF3NQFci69EezZFVodYesnsrJsGiGDK5/f+o6nOLa6m9tl7lBQzYjRIZWB/s1kyTpm+9f9c7aNvcXEFPcfuxZBilW/x2UELaBrxT1EnJ8gyMlNgwahFl+3uaYeF3E/y/ZYxO48scxSys2RxsnhEktMg+tOR0n4gIQSmZXRlsS9Zkxl1m8C8sj79nh0DfrN58+NnNQxK9dt2jfzuM2rjrsaef7FhheMWQ4sQywwAr+lSJOvBzu5pdhFFsK39LpwmdbCHUuP9GjlXFq3Z8C0ryquRe7gKSkCUhrQbDugajRn8naBBt9POLxS+T0DIDzfc3r7EG5MCAZwcGGIXAyTY7QAV9SHPkuiwQmFtJchkUqHsT5axBvRzJEFttCrBRcHI1uUqyCBZZFRoc4uzDF7xoS8z/xd6qySGAhL1WwC1U7KilRTpL7VdBF/bNwX3bPxn8wBNWtJRpYOxAM37rpSLxYuBF2aTbgCvwoi6q8i2lQ5JhkXXxke4I0IYIO/HiPs';
        $apiDetail = new PostOrderAPI($userToken,'','https://api.ebay.com/post-order/v2/casemanagement/5155997050','get');
//        findClass($apiDetail->sendHttpRequest()->inquiryDetails,1,0);
        findClass($apiDetail->sendHttpRequest(),1);
    }

    public function actionTestrefund()
    {
        $userToken = 'AgAAAA**AQAAAA**aAAAAA**NAgTWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6wNmISiCJCLpA6dj6x9nY+seQ**sHQDAA**AAMAAA**YF1h9yn8IX3rYvF+lhAv7EM6N8GBtMa0lGs99VMHDx6RQw0dmvH5RQoCEa4+XiMBZuPc1QQwFHey0lYsN6EOYIOEA2kjqY9ntLvODLJwbavuwvb7Vvgj2Av2vm72XyetofLe3uSttmjtsIWYWq0BS4zp6OEi6+uSwugIua0mThKtX5wQ0bVT4GSGrksnDoaHe/aWinhTQDPhkztF2jnNPuCQTJcl3KXVFwYPMCBLfmjp36cEESLoLgX8bz+akq+tYDqF3NQFci69EezZFVodYesnsrJsGiGDK5/f+o6nOLa6m9tl7lBQzYjRIZWB/s1kyTpm+9f9c7aNvcXEFPcfuxZBilW/x2UELaBrxT1EnJ8gyMlNgwahFl+3uaYeF3E/y/ZYxO48scxSys2RxsnhEktMg+tOR0n4gIQSmZXRlsS9Zkxl1m8C8sj79nh0DfrN58+NnNQxK9dt2jfzuM2rjrsaef7FhheMWQ4sQywwAr+lSJOvBzu5pdhFFsK39LpwmdbCHUuP9GjlXFq3Z8C0ryquRe7gKSkCUhrQbDugajRn8naBBt9POLxS+T0DIDzfc3r7EG5MCAZwcGGIXAyTY7QAV9SHPkuiwQmFtJchkUqHsT5axBvRzJEFttCrBRcHI1uUqyCBZZFRoc4uzDF7xoS8z/xd6qySGAhL1WwC1U7KilRTpL7VdBF/bNwX3bPxn8wBNWtJRpYOxAM37rpSLxYuBF2aTbgCvwoi6q8i2lQ5JhkXXxke4I0IYIO/HiPs';
        $refundApi = new PostOrderAPI($userToken,'','https://api.ebay.com/post-order/v2/casemanagement/fjkshgkjfdsh/issue_refund','post');
        findClass($refundApi->sendHttpRequest(),1);
    }
}