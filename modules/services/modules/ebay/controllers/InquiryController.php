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
use app\modules\mails\models\EbayCaseResponse;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayInquiryHistory;
use app\modules\mails\models\EbayInquiryResponse;
use app\modules\orders\models\Order;
use app\modules\products\models\EbaySiteMapAccount;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\systems\models\AftersaleManage;
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
use app\modules\reports\models\DisputeStatistics;
class InquiryController extends Controller
{
    private $ebayAccountModel;
    private $accountId;
    private $apiTaskModel;
    private $errorCode = 0;
    private $is_refund = false;
    private $path;

    private $claim_amount;
    private $currency;

    public $limit = 100;
    public $offset = 0;

    private $send_failure_times = 2;

    public function actionIndex()
    {
        if(isset($_REQUEST['account']))
        {
            $account = $_REQUEST['account'];
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $ebayAccountModel = EbayAccount::findOne((int)$account);
                $this->ebayAccountModel = $ebayAccountModel;
                $uncloseds = EbayInquiry::find()->where('account_id=:account_id and state<>"CLOSED"',[':account_id'=>$account])->all();
                set_time_limit(480);
                if(!empty($uncloseds))
                {
                    foreach($uncloseds as $unclosed)
                    {
                        $this->detailApi($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$unclosed->inquiry_id,'get');
                    }
                }
                $maxInquiryCreationDateRangeFrom = EbayInquiry::find()->select('max(creation_date)')->distinct()->where(['account_id'=>$account])->asArray()->one()['max(creation_date)'];
                if(empty($maxInquiryCreationDateRangeFrom))
                    $inquiryCreationDateRangeFrom = date('Y-m-d\TH:i:s',strtotime('-60 days')).'.000Z';
                else
                    $inquiryCreationDateRangeFrom = $maxInquiryCreationDateRangeFrom.'.000Z';
                $inquiryCreationDateRangeTo = date('Y-m-d\TH:i:s').'.000Z';
                if(strcasecmp($inquiryCreationDateRangeTo,$inquiryCreationDateRangeFrom) > 0)
                    $this->searchApi($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/search',['limit'=>20,'offset'=>1,'inquiry_creation_date_range_from'=>$inquiryCreationDateRangeFrom,'inquiry_creation_date_range_to'=>$inquiryCreationDateRangeTo],'get');
            }
        }
        else
        {
            $accounts = EbaySiteMapAccount::find()->select('ebay_account_id')->distinct()->where('is_delete=0')->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/inquiry/index','account'=>$accountV['ebay_account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('{{%ebay_site_map_account}}没有账号数据');
            }
        }
    }

    public function actionInquiry()
    {
        if(isset($_REQUEST['id']))
        {
            $account = trim($_REQUEST['id']);
            $logFile = 'F:\testContent\text'.$account.'.txt';
            file_put_contents($logFile,$account.PHP_EOL);
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $this->accountId = $account;
                $accountName = Account::findById((int)$this->accountId)->account_name;
                $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName, 900);
                if(empty($this->ebayAccountModel))
                    exit('无法获取账号信息。');
                if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_INQUIRY,$this->accountId))
                {
                    echo "account:{$this->accountId};Task Running.".PHP_EOL;
                    exit;
                }
                ignore_user_abort(true);
                set_time_limit(1200);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_INQUIRY;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();
                $uncloseds = EbayInquiry::find()->where('account_id=:account_id and status<>"CLOSED"',[':account_id'=>$account])->all();
                if(!empty($uncloseds))
                {
                    foreach($uncloseds as $unclosed)
                    {
                        $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$unclosed->inquiry_id,'get');
                    }
                }
                $maxStartTime = EbayApiTask::find()->select('max(start_time)')->where(['account_id'=>$this->accountId,'task_name'=>AccountTaskQueue::TASK_TYPE_INQUIRY,'exec_status'=>2,'status'=>[2,3]])->asArray()->one()['max(start_time)'];
                if(empty($maxStartTime))
                    $inquiryCreationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z',strtotime(' -30 days') - 32400);
                else
                    $inquiryCreationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z',strtotime($maxStartTime) - 32400);
                $inquiryCreationDateRangeTo = date('Y-m-d\TH:i:s').'.000Z';
                file_put_contents($logFile,'开始时间：'.$inquiryCreationDateRangeFrom.PHP_EOL,FILE_APPEND);
                if(strcasecmp($inquiryCreationDateRangeTo,$inquiryCreationDateRangeFrom) > 0)
                    $this->searchApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/search',['limit'=>20,'offset'=>1,'inquiry_creation_date_range_from'=>$inquiryCreationDateRangeFrom,'inquiry_creation_date_range_to'=>$inquiryCreationDateRangeTo],'get');
                else
                {
                    $this->apiTaskModel->exec_status = 1;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。StartTime:{$inquiryCreationDateRangeFrom} 不能小于 EndTime:{$inquiryCreationDateRangeTo}。]";
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                }
                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                    AccountTaskQueue::TASK_TYPE_INQUIRY);
                if (!empty($accountTask))
                {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/inquiry/inquiry', ['id'=> $accountId]);
                }
                exit('DONE');
            }
        }
        else
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB,Account::STATUS_VALID);
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::TASK_TYPE_INQUIRY,'platform_code'=>$account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_INQUIRY;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::TASK_TYPE_INQUIRY]);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/inquiry/inquiry', ['id'=> $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    public function actionInquirygetnew()
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
                if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_INQUIRY,$this->accountId,900))
                {
                    echo "account:{$this->accountId};Task Running.".PHP_EOL;
                    exit;
                }
                ignore_user_abort(true);
                set_time_limit(1200);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_INQUIRY;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();
                $maxStartTime = EbayApiTask::find()->select('max(data_end_time)')->where(['account_id'=>$this->accountId,'task_name'=>AccountTaskQueue::TASK_TYPE_INQUIRY,'exec_status'=>2,'status'=>[0,2,3]])->asArray()->one()['max(data_end_time)'];
                if(empty($maxStartTime))
                    $inquiryCreationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z',strtotime(' -30 days') - 29700);
                else
                    $inquiryCreationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z',strtotime($maxStartTime) - 29700);
//                $inquiryCreationDateRangeTo = date('Y-m-d\TH:i:s.000\Z',time()-28800);
                $nowTime = date('Y-m-d\TH:i:s.000\Z',time()-28800);
                $inquiryCreationDateRangeTo = date('Y-m-d\TH:i:s.000\Z',strtotime($inquiryCreationDateRangeFrom)+144000);
                if(strcmp($inquiryCreationDateRangeTo ,$nowTime) > 0)
                    $inquiryCreationDateRangeTo = $nowTime;
                
                //$inquiryCreationDateRangeFrom = '2018-10-02T19:45:21.000Z';
                //$inquiryCreationDateRangeTo = '2018-10-11T22:01:00.000Z';
                
                if(strcasecmp($inquiryCreationDateRangeTo,$inquiryCreationDateRangeFrom) > 0)
                {
                    $this->apiTaskModel->data_start_time = date('Y-m-d H:i:s',strtotime($inquiryCreationDateRangeFrom));
                    $this->apiTaskModel->data_end_time = date('Y-m-d H:i:s',strtotime($inquiryCreationDateRangeTo));
                    $this->apiTaskModel->save();
                    $refundInfo = EbayCaseRefund::findOne(['account_id'=>$account,'is_refund'=>EbayCaseRefund::STATUS_REFUND_YES]);
                    if($refundInfo)
                    {
                        $this->is_refund = true;
                        $this->claim_amount = $refundInfo->claim_amount;
                        $this->currency = $refundInfo->currency;
                    }
                    $this->searchApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/search',['limit'=>20,'offset'=>1,'inquiry_creation_date_range_from'=>$inquiryCreationDateRangeFrom,'inquiry_creation_date_range_to'=>$inquiryCreationDateRangeTo],'get');
                }else
                {
                    $this->apiTaskModel->exec_status = 1;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。StartTime:{$inquiryCreationDateRangeFrom} 不能小于 EndTime:{$inquiryCreationDateRangeTo}。]";
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                }
                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                    AccountTaskQueue::TASK_TYPE_INQUIRY);
                if (!empty($accountTask))
                {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/inquiry/inquirygetnew', ['id'=> $accountId]);
                }
                exit('DONE');
            }
        }
        else
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB,Account::STATUS_VALID);
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::TASK_TYPE_INQUIRY,'platform_code'=>$account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_INQUIRY;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::TASK_TYPE_INQUIRY]);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/inquiry/inquirygetnew', ['id'=> $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    public function actionInquiryupdate()
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
                if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE,$this->accountId,900))
                {
                    echo "account:{$this->accountId};Task Running.".PHP_EOL;
                    exit;
                }
                ignore_user_abort(true);
                set_time_limit(480);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();
                $uncloseds = EbayInquiry::find()->where('account_id=:account_id and status<>"CLOSED"',[':account_id'=>$account])->all();
                if(!empty($uncloseds))
                {
                    foreach($uncloseds as $unclosed)
                    {
                        $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$unclosed->inquiry_id,'get');
                    }
                }
                else
                {
                    $this->apiTaskModel->status = 2;
                    $this->apiTaskModel->error = 'no datas';
                }

                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                    AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE);
                if (!empty($accountTask))
                {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/inquiry/inquiryupdate', ['id'=> $accountId]);
                }
                exit('DONE');
            }
        }
        else
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB,Account::STATUS_VALID);
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE,'platform_code'=>$account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE]);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/inquiry/inquiryupdate', ['id'=> $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    public function actionInquiryupdatenew()
    {
        if(isset($_REQUEST['id']))
        {
            var_dump('start_time:'.time());
            $account = trim($_REQUEST['id']);
            $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $this->limit;
            if($limit <= 0)
                $limit = $this->limit;
            $offset = isset($_REQUEST['offset'])? intval($_REQUEST['offset']) : '0';
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $this->accountId = $account;
                $this->ebayAccountModel = Account::findById((int)$this->accountId);
                $accountName = $this->ebayAccountModel->account_name;
//                $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
                if(empty($this->ebayAccountModel))
                    exit('无法获取账号信息。');
                if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE,$this->accountId))
                {
                    echo "account:{$this->accountId};Task Running.".PHP_EOL;
                }
                else
                {
                    ignore_user_abort(true);
                    set_time_limit(480);

                    $uncloseds = EbayInquiry::find()->where('account_id=:account_id and status not in ("CLOSED","CS_CLOSED")',[':account_id'=>$account])->limit($limit)->offset($offset)->all();

                    $this->apiTaskModel = new EbayApiTask();
                    $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE;
                    $this->apiTaskModel->sendContent = ' offset:'.$offset.' limit:'.$limit;
                    $this->apiTaskModel->account_id = $this->accountId;
                    $this->apiTaskModel->exec_status = 1;
                    $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                    $this->apiTaskModel->save();

                    $refundInfo = EbayCaseRefund::findOne(['account_id'=>$account,'is_refund'=>EbayCaseRefund::STATUS_REFUND_YES]);
                    if($refundInfo)
                    {
                        $this->is_refund = true;
                        $this->claim_amount = $refundInfo->claim_amount;
                        $this->currency = $refundInfo->currency;
                    }

                    if(!empty($uncloseds))
                    {
                        foreach($uncloseds as $unclosed)
                        {
                            $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$unclosed->inquiry_id,'get');
                        }
                    }
                    else
                    {
                        $this->apiTaskModel->status = 2;
                        $this->apiTaskModel->error = 'no datas';
                    }

                    $this->apiTaskModel->exec_status = 2;
                    $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                    $this->apiTaskModel->save();

                }

                if(isset($uncloseds) && count($uncloseds) == $limit)
                {
                    $offset = $limit + $offset;
                    VHelper::throwTheader('/services/ebay/inquiry/inquiryupdatenew', ['id'=> $this->accountId,'limit'=>$limit,'offset'=>$offset]);
                }
                else
                {
                    $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                        AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE);
                    if (!empty($accountTask))
                    {
                        //在队列里面删除该记录
                        $accountId = $accountTask->account_id;
                        $accountTask->delete();
                        VHelper::throwTheader('/services/ebay/inquiry/inquiryupdatenew', ['id'=> $accountId]);
                    }
                }
                var_dump('end_time:'.time());
                exit('DONE');
            }
        }
        else
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB);

            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE,'platform_code'=>$account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::TASK_TYPE_INQUIRY_UPDATE],20);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/inquiry/inquiryupdatenew', ['id'=> $accountId]);
                    sleep(1);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    public function actionGetbynotify()
    {
        $content = file_get_contents("php://input");

        $content = simplexml_load_string($content)->children('soapenv',true)->Body->children()->NotificationEvent;

        $inquiry_id = $content->InquiryId->__toString();

        $inquiryModel = EbayInquiry::findOne(['inquiry_id'=>$inquiry_id]);
        if($inquiryModel)
        {
            $result = $inquiryModel->refreshApi();
            if($result['flag'])
            {
                return true;
            }
            else
            {
                return false;
            }
        }

    }

    // 异步更新一条纠纷数据（页面处理纠纷时使用）
    public function actionRefresh()
    {
        $id = $this->request->get('id');
        if(is_numeric($id) && $id > 0 && $id%1 === 0)
        {
            $result = EbayInquiry::findOne((int)$id)->refreshApi();
            if($result['flag'])
            {
                echo 'success';
            }
            else
            {
                echo 'false';
            }
        }
    }

    protected function searchApi($token,$site,$serverUrl,$params,$method)
    {
        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
        $api->urlParams = $params;
//        $response = $api->sendHttpRequest();
//        if(empty($response))
//        {
//            $this->apiTaskModel->status = 1;
//            $this->apiTaskModel->error = "[错误码：{$this->errorCode}。".$api->getServerUrl()."拉取无数据。]";
//            $this->apiTaskModel->save();
//            $this->errorCode++;
//        }
//        else
//        {
//            echo '<hr/>',$response->paginationOutput->totalPages,'---',$params['offset'],'<br/>';
//            ob_flush();
//            flush();
//            foreach ($response->members as $member)
//            {
//                $this->detailApi($token,'','https://api.ebay.com/post-order/v2/inquiry/'.$member->inquiryId,'get');
//            }
//            if(isset($params['offset']) && $response->paginationOutput->totalPages > $params['offset'])
//            {
//                $params['offset']++;
//                $this->searchApi($token,$site,$serverUrl,$params,$method);
//            }
//        }

        $transfer_ip = include \Yii::getAlias('@app').'/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';

        $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$token,'data'=>'','method'=>$method,'responseHeader'=>false,'urlParams'=>$params];
        $api = new PostOrderAPI('ceshi','',$transfer_ip,'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();

        if(empty($response))
        {
            $this->apiTaskModel->status = 1;
            $this->apiTaskModel->error = "[错误码：{$this->errorCode}。".$api->getServerUrl()."调用香港服务器无响应。]";
            $this->apiTaskModel->save();
            $this->errorCode++;
        }
        else
        {
            if(in_array($response->code,[200,201,202]))
            {
                $response = json_decode($response->response);
                echo '<hr/>',$response->paginationOutput->totalPages,'---',$params['offset'],'<br/>';
                ob_flush();
                flush();
                if($response->paginationOutput->totalEntries == 0 && $response->paginationOutput->totalPages == 0)
                {
                    $this->apiTaskModel->status = 2;
                    $this->apiTaskModel->error = "[警告：{$this->errorCode}。".$api->getServerUrl()."无数据。]";
                    $this->apiTaskModel->save();
                }
                else
                {
                    if(count($response->members)){
                        foreach ($response->members as $member)
                        {
                            $this->detailApi($token,'','https://api.ebay.com/post-order/v2/inquiry/'.$member->inquiryId,'get',$member);
                        }
                        if(isset($params['offset']) && $response->paginationOutput->totalPages > $params['offset'])
                        {
                            $params['offset']++;
                            $this->searchApi($token,$site,$serverUrl,$params,$method);
                        }
                    }else{
                        $this->apiTaskModel->status = 2;
                        $this->apiTaskModel->error = 'no data';
                        $this->apiTaskModel->save();
                    }
                }
            }
            else
            {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->error = "[错误码：{$this->errorCode}。".$api->getServerUrl().$response->response."]";
                $this->apiTaskModel->save();
                $this->errorCode++;
            }

        }
    }

    protected function detailApi($token,$site,$serverUrl,$method,$member=null)
    {
//        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
//        $response = $api->sendHttpRequest();
//        if(empty($response))
//        {
//            if(isset($this->apiTaskModel))
//            {
//                $this->apiTaskModel->status = 1;
//                $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}拉取无数据。]";
//                $this->apiTaskModel->save();
//                $this->errorCode++;
//            }
//            else
//            {
//                echo $serverUrl,'拉取无数据。';
//            }
//        }
//        else
//            $this->handleResponse($response);

        $transfer_ip = include \Yii::getAlias('@app').'/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';

        $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$token,'data'=>'','method'=>$method,'responseHeader'=>false,'urlParams'=>''];
        $api = new PostOrderAPI('ceshi','',$transfer_ip,'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();
        
        if(empty($response))
        {
            if(isset($this->apiTaskModel))
            {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}调用香港服务器无响应。]";
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
            else
            {
                echo $serverUrl,'调用香港服务器无响应。';
            }
        }
        else
        {
            if(in_array($response->code,[200,201,202]))
            {
                $this->handleResponse(json_decode($response->response));
            }
            else
            {
                if(isset($this->apiTaskModel))
                {
                    $this->apiTaskModel->status = 1;
                    $this->apiTaskModel->error .= "[错误码cs：{$this->errorCode}。{$serverUrl}{$response->response}。]";     
                    if(strpos($response->response,"This operation doesn't support claims of type 'CPS_SNAD'.") === false)
                        $this->apiTaskModel->status == 1;
                    else
                    {
                        $flag = $this->handmember($member);
                        if($flag)
                            $this->apiTaskModel->status = 2;
                        else
                            $this->apiTaskModel->status = 1;
                    }
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                }
                else
                {
                    echo $serverUrl,$response->response;
                }
            }
        }
    }

    /**
     * 纠纷更新自动退款(发现升级时)
     * @param type $data
     */
    protected function handleResponse($data)
    {

        $flag = true;
//        $logFile = 'F:\testContent\text'.$this->accountId.'.txt';
        $inquiryModel = EbayInquiry::findOne(['inquiry_id'=>$data->inquiryId]);
        if(empty($inquiryModel))
            $inquiryModel = new EbayInquiry();
        $inquiryModel->account_id = $this->accountId;
        $inquiryModel->claim_amount = $data->claimAmount->value;
        $inquiryModel->inquiry_id = $data->inquiryId;
        $inquiryModel->currency = $data->claimAmount->currency;
        $inquiryModel->extTransaction_id = isset($data->extTransactionId) ?$data->extTransactionId : '';
        $inquiryModel->initiator = array_search(trim($data->initiator),EbayInquiry::$initiatorMap);
        $inquiryModel->appeal_close_reason_enum = isset($data->inquiryDetails->appealDetails->appealCloseReasonEnum) ? array_search(trim($data->inquiryDetails->appealDetails->appealCloseReasonEnum),EbayInquiry::$appealCloseReasonEnumMap) : 0;
        if(isset($data->inquiryDetails->appealDetails->appealDate))
            $inquiryModel->appeal_date = explode('.',str_replace('T',' ',$data->inquiryDetails->appealDetails->appealDate->value))[0];
        $inquiryModel->appeal_reason_code = isset($data->inquiryDetails->appealDetails->appealReasonCode) ? $data->inquiryDetails->appealDetails->appealReasonCode : '';
        $inquiryModel->appeal_status = isset($data->inquiryDetails->appealDetails->appealStatus) ? array_search(trim($data->inquiryDetails->appealDetails->appealStatus),EbayInquiry::$appealStatusMap) : 0;
        $inquiryModel->appeal_status_enum = isset($data->inquiryDetails->appealDetails->appealStatusEnum) ? array_search(trim($data->inquiryDetails->appealDetails->appealStatusEnum),EbayInquiry::$appealStatusEnum) : 0;
        $inquiryModel->buyer_initial_expected_resolution = isset($data->inquiryDetails->buyerInitialExpectedResolution) ? $data->inquiryDetails->buyerInitialExpectedResolution : '';
        if(isset($data->buyer))
            $inquiryModel->buyer = $data->buyer;
        $orderinfo = '';
        if(empty($inquiryModel->order_id) || empty($inquiryModel->buyer) || empty($inquiryModel->platform_order_id))
        {
            $buyer = '';
            $order_id = '';
            $platformOrderId = '';
            if(isset($data->transactionId) && !empty($data->transactionId))
            {
                $orderinfo = Order::getOrderStackByTransactionId('EB',$data->transactionId);
                if($orderinfo){
                    $platformOrderId = $orderinfo->info->platform_order_id;
                }
            }
            else
            {
                $old_account_id = Account::findOne($this->accountId)->old_account_id;
                $orderinfo = Order::getEbayOrderStack($old_account_id,$data->buyer,$data->itemId,$data->transactionId);
            }

            if (!empty($orderinfo))
            {
                $orderinfo = Json::decode(Json::encode($orderinfo), true);
                $buyer = $orderinfo['info']['buyer_id'];
                $order_id = $orderinfo['info']['order_id'];
                $platformOrderId = $orderinfo['info']['platform_order_id'];
            }
            $inquiryModel->order_id = $order_id;
            $inquiryModel->buyer = $buyer;
            $inquiryModel->platform_order_id = $platformOrderId;
        }

        if(isset($this->currency)) {
            $currency = $this->currency;
        } else {
            $currency = Account::CURRENCY;
        }
        //$finalCNY = VHelper::getTargetCurrencyAmt($inquiryModel->currency,$currency,$inquiryModel->claim_amount);
        $finalCNY = VHelper::getTargetCurrencyAmtKefu($inquiryModel->currency,$currency,$inquiryModel->claim_amount);
        if(isset($this->claim_amount)) {
            $max_claim_amount = $this->claim_amount;
        } else {
            $max_claim_amount = Account::ACCOUNT_PRICE;
        }

        if($this->is_refund == true && !empty($data->inquiryDetails->escalationDate) && (int)$inquiryModel->auto_refund == 0 && $finalCNY < $max_claim_amount)
        {
            if(isset($data->state) && $data->state != 'CLOSED')
            {
                $flag_refund = true;
                $caseModel = EbayCase::findOne(['case_id'=>$data->inquiryId]);
                if($caseModel)
                    $flag_refund = false;

                if($flag_refund)
                {
                    $caseModel = new EbayCase();
                    $caseModel->case_id = $data->inquiryId;
                    $caseModel->item_id = $data->itemId;
                    $caseModel->case_type = EbayCase::CASE_TYPE_ITEM_NOT_RECEIVED;
                    $caseModel->transaction_id = isset($data->transactionId) ? $data->transactionId : '';
                    $caseModel->buyer = $inquiryModel->buyer;
                    $caseModel->account_id = $this->accountId;
                    $caseModel->status = isset($data->state) ? $data->state : '';
                    $caseModel->claim_amount = $data->claimAmount->value;
                    $caseModel->currency = isset($data->claimAmount) && !empty($data->claimAmount->currency) ? $data->claimAmount->currency : '';
                    $caseModel->case_quantity = isset($data->inquiryQuantity) ? $data->inquiryQuantity : '';
                    $caseModel->initiator = isset($data->initiator) ? array_search(trim($data->initiator),EbayInquiry::$initiatorMap) : 0;
                    $caseModel->creation_date = isset($data->inquiryDetails->escalationDate) && !empty($data->inquiryDetails->escalationDate->value) ? date('Y-m-d H:i:s',strtotime($data->inquiryDetails->escalationDate->value)) : '';

//                    $refundApi = new PostOrderAPI($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/casemanagement/'.$caseModel->case_id.'/issue_refund','post');
//                    $message = ['comments'=>['content'=>'']];
//                    $refundApi->setData($message);
//                    $refundResponse = $refundApi->sendHttpRequest();

                    $transfer_ip = include \Yii::getAlias('@app').'/config/transfer_ip.php';
                    $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';

                    $serverUrl = 'https://api.ebay.com/post-order/v2/casemanagement/'.$caseModel->case_id.'/issue_refund';
                    $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$this->ebayAccountModel->user_token,'data'=>json_encode(['comments'=>['content'=>'']]),'method'=>'post','responseHeader'=>false,'urlParams'=>''];
                    $api = new PostOrderAPI('ceshi','',$transfer_ip,'post');
                    $api->setData($post_data);
                    $refundResponse = $api->sendHttpRequest();

                    $caseResponse = new EbayCaseResponse();
                    $caseResponse->content = '';
                    $caseResponse->type = 1;
                    $caseResponse->account_id = $this->accountId;
                    $caseResponse->case_id = $caseModel->case_id;
                    if(empty($refundResponse))
                    {
                        $caseResponse->status = 0;
                        $caseResponse->error = '调用香港服务器无响应';
                    }
                    else
                    {
                        if(in_array($refundResponse->code,[200,201,202]))
                        {
                            $refundResponse = json_decode($refundResponse->response);
                            $caseResponse->status = 1;
                            $caseResponse->error = '';
                            $caseResponse->refund_source = isset($refundResponse->refundResult->refundSource) ? $refundResponse->refundResult->refundSource : '';
                            $caseResponse->refund_status = $refundResponse->refundResult->refundStatus;
                            //建立退款售后处理单
                            /*if($refundResponse->refundResult->refundStatus == 'SUCCESS')
                            {
                                $afterSalesOrderModel = new AfterSalesOrder();
                                $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                                $afterSalesOrderModel->transaction_id = $inquiryModel->transaction_id;
                                $afterSalesOrderModel->type = AfterSalesOrder::ORDER_TYPE_REFUND;
                                if(empty($orderinfo))
                                {
                                    $orderinfo = Order::getOrderStack(Platform::PLATFORM_CODE_EB, '',$inquiryModel->order_id);
                                    $orderinfo = Json::decode(Json::encode($orderinfo), true);
                                }
                                if(isset($orderinfo['info']) && $orderinfo['info']['complete_status'] >= Order::COMPLETE_STATUS_PARTIAL_SHIP)
                                    $reason = RefundReturnReason::REASON_NOT_RECEIVE;
                                else
                                    $reason = RefundReturnReason::REASON_NOT_SEND;

                                $afterSalesOrderModel->reason_id = $reason;
                                $afterSalesOrderModel->platform_code = Platform::PLATFORM_CODE_EB;
                                $afterSalesOrderModel->order_id = $inquiryModel->order_id;
                                $afterSalesOrderModel->account_id = $this->accountId;

                                $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                                $afterSalesOrderModel->approver = 'system';
                                $afterSalesOrderModel->approve_time = date('Y-m-d H:i:s');
                                $afterSalesOrderModel->buyer_id = $inquiryModel->buyer;
                                $afterSalesOrderModel->account_name = Account::getAccountName($caseModel->account_id,Platform::PLATFORM_CODE_EB);

                                $afterSaleOrderRefund = new AfterSalesRefund();
                                $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
                                $afterSaleOrderRefund->refund_amount = $caseModel->claim_amount;
                                $afterSaleOrderRefund->currency = $caseModel->currency;
                                $afterSaleOrderRefund->transaction_id = $caseModel->transaction_id;
                                $afterSaleOrderRefund->order_id = $inquiryModel->order_id;
                                $afterSaleOrderRefund->platform_code = Platform::PLATFORM_CODE_EB;
                                $afterSaleOrderRefund->order_amount = $caseModel->claim_amount;
                                $afterSaleOrderRefund->reason_code = $afterSalesOrderModel->reason_id;
                                $afterSaleOrderRefund->refund_time = date('Y-m-d H:i:s');
                                $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                            }*/

                            if ($refundResponse->refundResult->refundStatus == 'SUCCESS') {
                                //根据售后单规则，自动建立售后单
                                AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_EB, $inquiryModel->platform_order_id, '', $caseModel->transaction_id, $caseModel->claim_amount);
                            }
                        }
                        else
                        {
                            $caseResponse->status = 0;
                            $caseResponse->error = $refundResponse->response;
                        }
                    }

                    try{
                        // 保存退款结果
                        $caseResponse->save();
                        // 保存case到主表
                        $caseModel->save();
                    }
                    catch (\Exception $e)
                    {
                        $caseResponse = new EbayCaseResponse();
                        $caseResponse->content = '';
                        $caseResponse->type = 1;
                        $caseResponse->status = 0;
                        $caseResponse->account_id = $this->accountId;
                        $caseResponse->case_id = $caseModel->case_id;
                        $caseResponse->error = $e->getMessage()."。未收到纠纷{$inquiryModel->inquiry_id}个案编号{$caseModel->case_id}保存失败！";
                        $caseResponse->save();
                    }


                    if($refundResponse->refundResult->refundStatus != 'SUCCESS')
                    {
                        if(isset($this->apiTaskModel))
                        {
                            $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。inquiryID:'.$data->inquiryId.'升级自动退款出错。';
                        }
                    }
                    /*elseif(isset($afterSalesOrderModel))
                    {
                        $transaction = EbayInquiry::getDb()->beginTransaction();

                        try{
                            $flag_after = $afterSalesOrderModel->save();
                            if(!$flag_after && isset($this->apiTaskModel))
                            {
                                $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。inquiryID:'.$data->inquiryId.'升级自动退款建立售后处理单出错。'.VHelper::getModelErrors($afterSalesOrderModel).']';
                            }
                            elseif(isset($afterSaleOrderRefund))
                            {
                                $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                                $flag_after = $afterSaleOrderRefund->save();
                                if(!$flag_after && isset($this->apiTaskModel))
                                    $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。inquiryID:'.$data->inquiryId.'升级自动退款建立售后退款单出错。'.VHelper::getModelErrors($afterSaleOrderRefund).']';
                                elseif($flag_after)
                                    $afterSaleOrderRefund->audit($afterSalesOrderModel);
                            }
                        }catch(Exception $e){
                            $flag_after = false;
                            if(isset($this->apiTaskModel))
                                $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。inquiryID:'.$data->inquiryId.'升级自动退款出错。'.$e->getMessage().']';
                        }
                        if($flag_after == true)
                            $transaction->commit();
                        else
                            $transaction->rollBack();
                    }*/
                }
            }
            else{

            }
        }
        else
        {
            $escalationDate = isset($data->inquiryDetails->escalationDate) ?  explode('.',str_replace('T',' ',$data->inquiryDetails->escalationDate->value))[0]: '';

            if(!empty($escalationDate))
            {
                if($this->path)
                {
                    file_put_contents($this->path,' inquiry_id:'.$inquiryModel->inquiry_id,FILE_APPEND);
                    file_put_contents($this->path,' account_id:'.$this->accountId,FILE_APPEND);
                    file_put_contents($this->path,' time:'.date('Y-m-d H:i:s'),FILE_APPEND);
                    file_put_contents($this->path,' is_refund:'.$this->is_refund,FILE_APPEND);
                    file_put_contents($this->path,' auto_refund:'.$inquiryModel->auto_refund,FILE_APPEND);
                    file_put_contents($this->path,' state:'.$data->state,FILE_APPEND);
                    file_put_contents($this->path,' original_escalation_date:'.$inquiryModel->escalation_date,FILE_APPEND);

                    file_put_contents($this->path,' now_escalation_date:'.$escalationDate,FILE_APPEND);
                    file_put_contents($this->path,' finalCNY:'.$finalCNY,FILE_APPEND);
                    file_put_contents($this->path,' max_claim_amount:'.$max_claim_amount,FILE_APPEND);
                }

            }

        }

        $transaction1 = EbayInquiry::getDb()->beginTransaction();
        // 保存inquiry主表信息
        if($flag)
        {
            $inquiryModel->eligible_for_appeal = isset($data->inquiryDetails->appealDetails->eligibleForAppeal) ? array_search($data->inquiryDetails->appealDetails->eligibleForAppeal,EbayInquiry::$eligibleForAppealMap) : 0;
            $inquiryModel->creation_date = explode('.',str_replace('T',' ',$data->inquiryDetails->creationDate->value))[0];
            $inquiryModel->escalation_date = isset($data->inquiryDetails->escalationDate) ?  explode('.',str_replace('T',' ',$data->inquiryDetails->escalationDate->value))[0]: null;
            $inquiryModel->expiration_date = isset($data->inquiryDetails->expirationDate) ? explode('.',str_replace('T',' ',$data->inquiryDetails->expirationDate->value))[0] : null;
            $inquiryModel->last_buyer_respdate = isset($data->inquiryDetails->lastBuyerRespDate) ? explode('.',str_replace('T',' ',$data->inquiryDetails->lastBuyerRespDate->value))[0] : null;
            $inquiryModel->buyer_final_accept_refund_amt = $data->inquiryDetails->refundAmounts->buyerFinalAcceptRefundAmt->value;
            if(empty($inquiryModel->currency))
                $inquiryModel->currency = $data->inquiryDetails->refundAmounts->buyerFinalAcceptRefundAmt->currency;
            $inquiryModel->buyer_init_expect_refund_amt = $data->inquiryDetails->refundAmounts->buyerInitExpectRefundAmt->value;
            if(empty($inquiryModel->currency))
                $inquiryModel->currency = $data->inquiryDetails->refundAmounts->buyerInitExpectRefundAmt->currency;
            $inquiryModel->international_refund_amount = isset($data->inquiryDetails->refundAmounts->internationalRefundAmount) ? $data->inquiryDetails->refundAmounts->internationalRefundAmount->value : 0;
            $inquiryModel->refund_amount = isset($data->inquiryDetails->refundAmounts->refundAmount) ? $data->inquiryDetails->refundAmounts->refundAmount->value : 0;
//            var_dump(explode('.',str_replace('T',' ',$data->inquiryDetails->refundDeadlineDate->value))[0]);exit;
            $inquiryModel->refund_deadline_date = explode('.',str_replace('T',' ',$data->inquiryDetails->refundDeadlineDate->value))[0];

            $inquiryModel->total_amount = $data->inquiryDetails->totalAmount->value;
            if(empty($inquiryModel->currency))
                $inquiryModel->currency = $data->inquiryDetails->totalAmount->currency;
            $inquiryModel->inquiry_quantity = $data->inquiryQuantity;
            $inquiryModel->item_picture_url = isset($data->itemDetails->itemPictureUrl) ? $data->itemDetails->itemPictureUrl : '';
            $inquiryModel->item_price = $data->itemDetails->itemPrice->value;
            if(empty($inquiryModel->currency))
                $inquiryModel->currency = $data->itemDetails->itemPrice->currency;
            $inquiryModel->item_title = $data->itemDetails->itemTitle;
            $inquiryModel->view_purchased_item_url = isset($data->itemDetails->viewPurchasedItemUrl) ? $data->itemDetails->viewPurchasedItemUrl : '';
            $inquiryModel->item_id = $data->itemId;
            $inquiryModel->seller_make_it_right_by_date = explode('.',str_replace('T',' ',$data->sellerMakeItRightByDate->value))[0];
            $inquiryModel->state = $data->state;
            $inquiryModel->status = $data->status;
            $inquiryModel->transaction_id = isset($data->transactionId) ? $data->transactionId : '';
            $inquiryModel->view_pp_trasanction_url = isset($data->viewPPTrasanctionUrl) ? $data->viewPPTrasanctionUrl : '';
            $inquiryModel->update_time = date('Y-m-d H:i:s');
            $inquiryModel->is_deal = 1;

            try{
                $flag = $inquiryModel->save();
                if(!$flag)
                {
                    if(isset($this->apiTaskModel))
                        $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。inquiryID:'.$data->inquiryId.'主表信息保存出错。'.VHelper::getModelErrors($inquiryModel).']';
                    else
                        echo 'inquiryID:',$data->inquiryId,'主表信息保存出错。',VHelper::getModelErrors($inquiryModel);
                }
            }catch(Exception $e){
                $flag = false;
                if(isset($this->apiTaskModel))
                    $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。inquiryID:'.$data->inquiryId.'主表信息保存出错。'.$e->getMessage().']';
                else
                    echo 'inquiryID:',$data->inquiryId,'主表信息保存出错。',$e->getMessage();
            }
            if(!$flag && isset($this->apiTaskModel))
            {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
        }

        // 保存inquiry详情
        if($flag)
        {
            $date_t = '';
            EbayInquiryHistory::deleteAll(['inquiry_table_id'=>$inquiryModel->id]);
            foreach($data->inquiryHistoryDetails->history as $history)
            {
                if($history->actor == 'BUYER'){
                    $date_t = explode('.',str_replace('T',' ',$history->date->value))[0];
                }
                $inquiryHistoryModel = new EbayInquiryHistory();
                $inquiryHistoryModel->inquiry_table_id = $inquiryModel->id;
                $inquiryHistoryModel->inquiry_id = $data->inquiryId;
                $inquiryHistoryModel->action = $history->action;
                $inquiryHistoryModel->actor = array_search(trim($history->actor),EbayInquiry::$initiatorMap);
                $inquiryHistoryModel->date = explode('.',str_replace('T',' ',$history->date->value))[0];
                $inquiryHistoryModel->description = isset($history->description) ? $history->description : '';
                try{
                    $flag = $inquiryHistoryModel->save();
                    if(!$flag)
                    {
                        if(isset($this->apiTaskModel))
                            $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。inquiryID:'.$data->inquiryId.'历史信息保存出错。'.VHelper::getModelErrors($inquiryHistoryModel).']';
                        else
                            echo 'inquiryID:',$data->inquiryId,'历史信息保存出错。',VHelper::getModelErrors($inquiryHistoryModel);
                    }
                }catch(Exception $e){
                    $flag = false;
                    if(isset($this->apiTaskModel))
                        $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。inquiryID:'.$data->inquiryId.'历史信息保存出错。'.$e->getMessage().']';
                    else
                        echo 'inquiryID:',$data->inquiryId,'历史信息保存出错。',$e->getMessage();
                }
//                if(!$flag)
//                {
//                    $this->apiTaskModel->status = 1;
//                    $this->apiTaskModel->save();
//                    $this->errorCode++;
//                    break;
//                }
            }
            //将未收到纠纷插入到纠纷统计表           
            
            $disputeStatistics = DisputeStatistics::findOne(['dispute_id'=>$data->inquiryId,'type' => AccountTaskQueue::TASK_TYPE_INQUIRY,'platform_code'=>Platform::PLATFORM_CODE_EB,'status' => 0]);
            if(empty($disputeStatistics) && in_array($data->status,["OPEN","PENDING","WAITING_SELLER_RESPONSE"])){
                $disputeStatistics = new DisputeStatistics();
                $disputeStatistics->status = 0;
                $disputeStatistics->platform_code = Platform::PLATFORM_CODE_EB;
                $disputeStatistics->account_id = $this->accountId;
                $disputeStatistics->type = AccountTaskQueue::TASK_TYPE_INQUIRY;
                $disputeStatistics->create_time = date('Y-m-d H:i:s');
                $disputeStatistics->dispute_id = $data->inquiryId;
                $disputeStatistics->creation_date = $date_t;
                $disputeStatistics->save(false);
            }
        }

        if($flag)
        {
            echo 'commit';
            $transaction1->commit();
        }
        else
        {
            echo 'rollback';
            $transaction1->rollBack();
            if(isset($this->apiTaskModel))
            {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
        }

        echo $data->inquiryId,'<br>';
        ob_flush();
        flush();
    }

    public function actionInquirysendmsg()
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

                $sendMsgs = EbayInquiryResponse::find()->where('type=1 and status=0 and account_id = '.$this->accountId)->orWhere('type=1 and status=-1 and account_id = '.$this->accountId.' and send_failure_times < '.$this->send_failure_times)->all();

                if(!empty($sendMsgs))
                {
                    if(EbayApiTask::checkIsRunning(AccountTaskQueue::INQUIRY_SEND_MSG,$this->accountId))
                    {
                        echo "account:{$this->accountId};Task Running.".PHP_EOL;
                        exit;
                    }
                    ignore_user_abort(true);
                    set_time_limit(480);
                    $this->apiTaskModel = new EbayApiTask();
                    $this->apiTaskModel->task_name = AccountTaskQueue::INQUIRY_SEND_MSG;
                    $this->apiTaskModel->account_id = $this->accountId;
                    $this->apiTaskModel->exec_status = 1;
                    $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                    $this->apiTaskModel->save();
                    foreach($sendMsgs as $sendMsg)
                    {
                        $api = new PostOrderAPI($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$sendMsg->inquiry_id.'/send_message','post');
                        if(!empty($sendMsg->content))
                            $api->setData(['message'=>['content'=>$sendMsg->content]]);
                        $api->responseHeader = true;
                        $response = $api->sendHttpRequest('json');
                        if(empty($response))
                        {
                            $sendMsg->status = 0;
                            $sendMsg->error = '无返回值';
                        }
                        else
                        {
                            if(in_array($api->getHttpCode(),[201,202]))
                            {
                                $sendMsg->status = 1;
                            }
                            else
                            {
                                $sendMsg->status = -1;
                                $sendMsg->error = $response;
                                $sendMsg->send_failure_times++;
                            }
                        }
                        try{
                            $flag = $sendMsg->save();
                            if(!$flag)
                                $errorInfo = VHelper::getModelErrors($sendMsg);
                            // 更新inquiry
                            $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$sendMsg->inquiry_id,'get');
                        }catch(Exception $e){
                            $flag = false;
                            $errorInfo = $e->getMessage();
                        }
                    }
                    $this->apiTaskModel->exec_status = 2;
                    $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                    $this->apiTaskModel->save();
                }

                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                    AccountTaskQueue::INQUIRY_SEND_MSG);
                if (!empty($accountTask))
                {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/inquiry/inquirysendmsg', ['id'=> $accountId]);
                }
                exit('DONE');
            }
        }
        else
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB,Account::STATUS_VALID);
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::INQUIRY_SEND_MSG,'platform_code'=>$account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::INQUIRY_SEND_MSG;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::INQUIRY_SEND_MSG],10);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/inquiry/inquirysendmsg', ['id'=> $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    public function actionOne()
    {
        $inquiryId = $_GET['inquiry'];
        $this->accountId = $_GET['account'];
        $accountName = Account::findById((int)$this->accountId)->account_name;
        $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
        $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$inquiryId,'get');
    }

    public function actionTest()
    {
        $userToken = 'AgAAAA**AQAAAA**aAAAAA**aAkTWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AEkYKnCpOKoA+dj6x9nY+seQ**sHQDAA**AAMAAA**vtX4CSDZdezcZETg1+YTW0Mrp9gAOqMbsYJHVinSMqud6nPqJBvM88tT3d6+Nul33HLmuH+Scrn7upJDh4IBA5K9S7TmDXl3bUJoL17ODpEoDBqUj9giFcIQqH38HWVDPnRGl+uRd/ghMOdlCki+X1BdAgTVfwN9IbuidYPBiMduBUBgEfo5O6jrmOyXeHB9d/gA0Hgmr2mIhQnWZ3pu46H7k9CIXUC4hx86T2tpXr0kqYpXh7p8WTRPyfpBJWMpmtq4N7ZveXpvBAPINlwGloLabWlMbtgNhJhc26NsaSDma01Ay6kerUNGTI4YP9qh7sHrFy+BbEdaotbEIQRQgGYx9RKHdgWy2zcfwaZJHl6S5IGoB4qxQoxYb5mceTyCyFiMwVt8DgGiQcMf6PZ7VOL6k1Xu/lnSV3ARZlVpWQf+bCjN9f6x7FZ0Nswlrx+M3lUgtQO+jNEwbsa9M7r9F1gZG17qIV9v0uFrcMvL1Yc45xWSut+9BG1ExGphYt0I3N3rv87VcIq4YbpDp2EDUBLbgWKjEZLL7pSYEbczKl/qit96hKmOYtOUlCz7lHq3SzQYDfilTSuQanh1MH1XwisSV5qjB+UoU8QMLGpWiIfyf8kq9PshiB4EOty6wODeF6meQftsx1Fy0vkCBx48R0kuMc3LfRpH/dOXVSQXcUxysdzDi68QT9daqj61l1E1CHBEpxgPv5OOfjPnT6///o58tQxPPTa+dx3TLivCW1BqWi/sBkB9eaWhZJSslR4w';
        $apiDetail = new PostOrderAPI($userToken,'','https://api.ebay.com/post-order/v2/inquiry/5164238057','get');
        findClass($apiDetail->sendHttpRequest(),1);
        findClass($apiDetail->sendHttpRequest()->inquiryDetails->buyerInitialExpectedResolution,1,0);
    }

    public function actionRefund()
    {
        $token = 'AgAAAA**AQAAAA**aAAAAA**DQUTWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6wNlIWnD5SKoQSdj6x9nY+seQ**sHQDAA**AAMAAA**sHq6OnnChkaUzhje5FMVNrGtB3fr/WtJQrZ6rODBSPtwJm8AOERfGOhogH5fftI0V8mBJXYPq6s7ZbtkySsYKKOHzfrxQhZgsRU3Zmv9gDgRCQUeP/PyBwCkGmDOPnmn3sUoPOnvFSw11yguKGrE53u2wed6ECqLRE1NvMwDgunABK+0BLZRU4LY7VtsnpHy3vgo4HwbZGYClfnns9AHAh+cOIEh1aeG56MX+e8ax099JQgb3PkL1c69S+ce1jDZW7eiDHyRgrb0RVIsVkI7E+d60wwHzyMZ8CXyrDVS0+L8MU3K7BT24rhgKYfuSJ6F6oqkT5X3rqi1e1xoRDD6H/ootnskP+4BeelUwaoPONtit6ftQiL28+PAqK5VRLpz7vKALkxCH/3cyY6r1co4GIrRrx4KaDV2UPC2uuIrZ0uHziyNf956I1+eYOcQPZcsClYy43NpvbI5Oo5oJ2FGLlNpyhMKfL2g/QMoPQ5QWE1CqTGxHG5tNrQIDt3s5tq6g/V6vV8WXe1tFFJ3cLAQhOlIvw5R1YYDqgMcHQnc8TosCaB/y8rVYPhwm55D+swPEnC7piNgAu08b2BdQ9g40NURJMxdH8uonKhRY/YVc/4qiIiA2cmkpIW8HAkws+waHXgYIWaIVLBn5Ht/F8K4SAYLqVelsIjHZwsQI2AyssVto3HNESlL2L1xsV/Ii1URtOPKh4DhpxyPxKcwH57uR2eOfRX1U6DLVIQHWMWkZx939QVmB/oMTFIdCPyYcmEz';
        $api = new PostOrderAPI($token,'','https://api.ebay.com/post-order/v2/inquiry/5144244576/issue_refund','post');

        findClass($api->sendHttpRequest('json'),1);
    }

    public function actionInquirygetnewsupply()
    {
        $start_time = $_REQUEST['start_time'];
        $end_time = $_REQUEST['end_time'];
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
                if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_INQUIRY,$this->accountId,900))
                {
                    echo "account:{$this->accountId};Task Running.".PHP_EOL;
                    exit;
                }
                ignore_user_abort(true);
                set_time_limit(1200);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_INQUIRY;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                $inquiryCreationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z',strtotime($start_time) - 28800);
                $inquiryCreationDateRangeTo = date('Y-m-d\TH:i:s.000\Z',strtotime($end_time)-28800);

                if(strcasecmp($inquiryCreationDateRangeTo,$inquiryCreationDateRangeFrom) > 0)
                {
                    $this->apiTaskModel->data_start_time = date('Y-m-d H:i:s',strtotime($inquiryCreationDateRangeFrom));
                    $this->apiTaskModel->data_end_time = date('Y-m-d H:i:s',strtotime($inquiryCreationDateRangeTo));
                    $this->apiTaskModel->save();
                    $refundInfo = EbayCaseRefund::findOne(['account_id'=>$account,'is_refund'=>EbayCaseRefund::STATUS_REFUND_YES]);
                    if($refundInfo)
                    {
                        $this->is_refund = true;
                        $this->claim_amount = $refundInfo->claim_amount;
                        $this->currency = $refundInfo->currency;
                    }
                    $this->searchApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/search',['limit'=>20,'offset'=>1,'inquiry_creation_date_range_from'=>$inquiryCreationDateRangeFrom,'inquiry_creation_date_range_to'=>$inquiryCreationDateRangeTo],'get');
                }

                else
                {
                    $this->apiTaskModel->exec_status = 1;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。StartTime:{$inquiryCreationDateRangeFrom} 不能小于 EndTime:{$inquiryCreationDateRangeTo}。]";
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                }
                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                exit('DONE');
            }
        }
    }

    protected function handmember($member)
    {
        if(isset($member->inquiryId) && !empty($member->inquiryId))
        {
            $inquiryModel = EbayInquiry::find()->where(['inquiry_id'=>$member->inquiryId])->one();
            if(empty($inquiryModel))
                $inquiryModel = new EbayInquiry();
            $inquiryModel->item_id = $member->itemId;
            $inquiryModel->transaction_id = $member->transactionId;
            $inquiryModel->inquiry_id = $member->inquiryId;
            $inquiryModel->buyer = $member->buyer;
            $inquiryModel->account_id = $this->accountId;
            $inquiryModel->status = $member->inquiryStatusEnum;
            $inquiryModel->claim_amount = $member->claimAmount->value;
            $inquiryModel->currency = isset($member->claimAmount) && !empty($member->claimAmount->currency) ? $member->claimAmount->currency : '';
            $inquiryModel->creation_date = explode('.',str_replace('T',' ',$member->creationDate->value))[0];
            $inquiryModel->initiator = 1;
            $transaction = EbayInquiry::getDb()->beginTransaction();

            $flag = $inquiryModel->save();
            if($flag)
            {
                $transaction->commit();
                return true;
            }
            else
            {
                $transaction->rollback();
                return false;
            }
        }
        else
        {
            return false;
        }
    }
}