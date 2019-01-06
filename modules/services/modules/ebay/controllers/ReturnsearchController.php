<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/12 0012
 * Time: 下午 4:33
 */

namespace app\modules\services\modules\ebay\controllers;


use app\common\VHelper;
use app\components\Controller;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\EbayCaseRefund;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\mails\models\EbayCase;
use app\modules\mails\models\EbayCaseResponse;
use app\modules\mails\models\EbayReturnImage;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\mails\models\EbayReturnsRequestsDetail;
use app\modules\products\models\EbaySiteMapAccount;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\systems\models\EbayAccount;
use app\modules\systems\models\EbayApiTask;
use PhpImap\Exception;
use yii\helpers\Url;
use app\modules\orders\models\Order;
use yii\helpers\Json;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\AutoCode;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\reports\models\DisputeStatistics;


class ReturnsearchController extends Controller
{
    private $ebayAccountModel;
    private $accountId;
    private $apiTaskModel;
    private $errorCode = 0;

    private $is_refund = false;
    private $claim_amount;
    private $currency;

    private $limit = 15;
    private $offset = 0;
    private $is_log = false;
    private $path;

    protected $isDealMaps = array('SYSTEM_CREATE_RETURN','SUBMIT_FILE','NOTIFIED_DELIVERED','BUYER_SEND_MESSAGE','BUYER_PROVIDE_TRACKING_INFO','BUYER_DECLINE_PARTIAL_REFUND','BUYER_CREATE_RETURN','REMINDER_FOR_SHIPPING','REMINDER_FOR_REFUND_NO_SHIPPING','REMINDER_FOR_REFUND');


    public function actionIndex()  //请使用actionReturn 拉取return纠纷
    {
        if(isset($_REQUEST['account']))
        {

            $account = $_REQUEST['account'];
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $ebayAccountModel = EbayAccount::findOne((int)$account);

                $this->ebayAccountModel = $ebayAccountModel;
                $uncloseds = EbayReturnsRequests::find()->where('account_id=:account_id and state<>"CLOSED"',[':account_id'=>$account])->all();
                set_time_limit(6600);
                if(!empty($uncloseds))
                {
                    foreach($uncloseds as $unclosed)
                    {
                        $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/'.$unclosed->return_id,'get',$unclosed);
                    }
                }
                $maxCreationDateRangeFrom = EbayReturnsRequests::find()->select('max(return_creation_date)')->distinct()->where(['account_id'=>$account])->asArray()->one()['max(return_creation_date)'];
                if(empty($maxCreationDateRangeFrom))
                    $creationDateRangeFrom = date('Y-m-d\TH:i:s',strtotime('-30 days')).'.000Z';
                else
                    $creationDateRangeFrom = $maxCreationDateRangeFrom.'.000Z';
                $creationDateRangeTo = date('Y-m-d\TH:i:s').'.000Z';
                if(strcasecmp($creationDateRangeTo,$creationDateRangeFrom) > 0)
                    $this->searchApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/search',['limit'=>25,'offset'=>1,'creation_date_range_from'=>$creationDateRangeFrom,'creation_date_range_to'=>$creationDateRangeTo],'get');
            }
        }
        else
        {

            $accounts = EbaySiteMapAccount::find()->select('ebay_account_id')->distinct()->where('is_delete=0')->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/returnsearch/index','account'=>$accountV['ebay_account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('{{%ebay_site_map_account}}没有账号数据');
            }
        }
    }

    public function actionReturn()
    {
        if(isset($_REQUEST['id']))
        {
            $this->accountId = trim($_REQUEST['id']);
//            findClass($this->accountId,1,0);
            $accountName = Account::findById((int)$this->accountId)->account_name;
//            findClass($accountName,1,0);
            $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
//            findClass($this->ebayAccountModel,1);
            if(empty($this->ebayAccountModel))
                exit('无法获取账号信息。');
            if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_RETURN,$this->accountId))
            {
                echo "account:{$this->accountId};Task Running.".PHP_EOL;
                exit;
            }
            ignore_user_abort(true);
            set_time_limit(7200);
            $this->apiTaskModel = new EbayApiTask();
            $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_RETURN;
            $this->apiTaskModel->account_id = $this->accountId;
            $this->apiTaskModel->exec_status = 1;
            $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
            $this->apiTaskModel->save();

            $uncloseds = EbayReturnsRequests::find()->where('account_id=:account_id and state<>"CLOSED"',[':account_id'=>$this->accountId])->all();
            if(!empty($uncloseds))
            {
                foreach($uncloseds as $unclosed)
                {
                    $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/'.$unclosed->return_id,'get',$unclosed);
                }
            }
            $maxStartTime = EbayApiTask::find()->select('max(start_time)')->where(['account_id'=>$this->accountId,'task_name'=>AccountTaskQueue::TASK_TYPE_RETURN,'exec_status'=>2,'status'=>[2,3]])->asArray()->one()['max(start_time)'];
            if(empty($maxStartTime))
                $startTime = $startTime = date('Y-m-d\TH:i:s.000\Z',strtotime(' -30 days') - 32400);
            else
                $startTime = date('Y-m-d\TH:i:s.000\Z',strtotime($maxStartTime) - 32400);
            $endTime = date('Y-m-d\TH:i:s.000\Z');
            if(strcmp($endTime,$startTime) > 0)
            {
                $this->searchApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/search',['limit'=>25,'offset'=>1,'creation_date_range_from'=>$startTime,'creation_date_range_to'=>$endTime],'get');
            }
            else
            {
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。StartTime:{$startTime} 不能小于 EndTime:{$endTime}。]";
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
            $this->apiTaskModel->exec_status = 2;
            $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
            $this->apiTaskModel->save();

            $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                AccountTaskQueue::TASK_TYPE_RETURN);
            if (!empty($accountTask))
            {
                //在队列里面删除该记录
                $accountId = $accountTask->account_id;
                $accountTask->delete();
                VHelper::throwTheader('/services/ebay/returnsearch/return', ['id'=> $accountId]);
            }
            exit('DONE');
        }
        else
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB,Account::STATUS_VALID);
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::TASK_TYPE_RETURN,'platform_code'=>$account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_RETURN;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::TASK_TYPE_RETURN]);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/returnsearch/return', ['id'=> $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    //最新拉取解纷 return数据
    public function actionReturngetnew()
    {
        if(isset($_REQUEST['id']))
        {
            $this->accountId = trim($_REQUEST['id']);
//            findClass($this->accountId,1,0);
            $this->ebayAccountModel = Account::findById((int)$this->accountId);
            $accountName = $this->ebayAccountModel->account_name;
//            findClass($accountName,1,0);
//            $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
//            findClass($this->ebayAccountModel,1);
            if(empty($this->ebayAccountModel))
                exit('无法获取账号信息。');
            if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_RETURN,$this->accountId))
            {
                echo "account:{$this->accountId};Task Running.".PHP_EOL;
                exit;
            }
            ignore_user_abort(true);
            set_time_limit(7200);
            $this->apiTaskModel = new EbayApiTask();
            $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_RETURN;
            $this->apiTaskModel->account_id = $this->accountId;
            $this->apiTaskModel->exec_status = 1;
            $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
            $this->apiTaskModel->save();

            $maxStartTime = EbayApiTask::find()->select('max(start_time)')->where(['account_id'=>$this->accountId,'task_name'=>AccountTaskQueue::TASK_TYPE_RETURN,'exec_status'=>2,'status'=>[0,2,3]])->asArray()->one()['max(start_time)'];
            if(empty($maxStartTime))
                $startTime = date('Y-m-d\TH:i:s.000\Z',strtotime(' -30 days') - 29700);
            else
                $startTime = date('Y-m-d\TH:i:s.000\Z',strtotime($maxStartTime) - 29700);
            $startTime = '2018-09-10T21:30:00.000Z';
            $endTime = date('Y-m-d\TH:i:s.000\Z',time() - 28800);//测试
//            echo $startTime.'<br/>'.$endTime;
            if(strcmp($endTime,$startTime) > 0)
            {
                $this->apiTaskModel->data_start_time = date('Y-m-d H:i:s',strtotime($startTime));
                $this->apiTaskModel->data_end_time = date('Y-m-d H:i:s',strtotime($endTime));
                $this->apiTaskModel->save();
                $refundInfo = EbayCaseRefund::findOne(['account_id'=>$this->accountId,'is_refund'=>EbayCaseRefund::STATUS_REFUND_YES]);
                if($refundInfo)
                {
                    $this->is_refund = true;
                    $this->claim_amount = $refundInfo->claim_amount;
                    $this->currency = $refundInfo->currency;
                }
                $this->searchApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/search',['limit'=>25,'offset'=>1,'creation_date_range_from'=>$startTime,'creation_date_range_to'=>$endTime],'get');
                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();
            }
            else
            {
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。StartTime:{$startTime} 不能小于 EndTime:{$endTime}。]";
                $this->apiTaskModel->save();
                $this->errorCode++;
            }

            $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                AccountTaskQueue::TASK_TYPE_RETURN);
            if (!empty($accountTask))
            {
                //在队列里面删除该记录
                $accountId = $accountTask->account_id;
                $accountTask->delete();
                VHelper::throwTheader('/services/ebay/returnsearch/returngetnew', ['id'=> $accountId]);
            }
            exit('DONE');
        }
        else
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB,Account::STATUS_VALID);
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::TASK_TYPE_RETURN,'platform_code'=>$account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_RETURN;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::TASK_TYPE_RETURN]);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/returnsearch/returngetnew', ['id'=> $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    // 手动补齐return
    public function actionReturngetnewcc()
    {
        if(isset($_REQUEST['id']))
        {
            $this->accountId = trim($_REQUEST['id']);
//            findClass($this->accountId,1,0);
            $accountName = Account::findById((int)$this->accountId)->account_name;
//            findClass($accountName,1,0);
            $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
//            findClass($this->ebayAccountModel,1);
            if(empty($this->ebayAccountModel))
                exit('无法获取账号信息。');
            if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_RETURN,$this->accountId))
            {
                echo "account:{$this->accountId};Task Running.".PHP_EOL;
                exit;
            }
            ignore_user_abort(true);
            set_time_limit(7200);

            $startTime = $_REQUEST['start_time'];
            $endTime = $_REQUEST['end_time'];
            if(strcmp($endTime,$startTime) > 0)
            {
                $refundInfo = EbayCaseRefund::findOne(['account_id'=>$this->accountId,'is_refund'=>EbayCaseRefund::STATUS_REFUND_YES]);
                if($refundInfo)
                {
                    $this->is_refund = true;
                    $this->claim_amount = $refundInfo->claim_amount;
                    $this->currency = $refundInfo->currency;
                }
                $this->searchApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/search',['limit'=>25,'offset'=>1,'creation_date_range_from'=>$startTime,'creation_date_range_to'=>$endTime],'get');
            }
            else
            {
                exit('false');
            }
            exit('DONE');
        }
    }

    public function actionReturnupdate()
    {
        if(isset($_REQUEST['id']))
        {
            $this->accountId = trim($_REQUEST['id']);
            $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $this->limit;
            if($limit <= 0)
                $limit = $this->limit;
            $offset = isset($_REQUEST['offset'])? intval($_REQUEST['offset']) : '0';
            $this->is_log = isset($_REQUEST['is_log']) ? $_REQUEST['is_log'] : false;
//            findClass($this->accountId,1,0);
            $this->ebayAccountModel = Account::findById((int)$this->accountId);
            $accountName = $this->ebayAccountModel->account_name;
//            findClass($accountName,1,0);
//            $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
//            findClass($this->ebayAccountModel,1);
            if(empty($this->ebayAccountModel))
                exit('无法获取账号信息。');
            if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_RETURN_UPDATE,$this->accountId))
            {
                echo "account:{$this->accountId};Task Running.".PHP_EOL;
            }
            else
            {
                if($this->is_log)
                {
                    $this->path = \Yii::getAlias('@runtime').'/return_update_'.date('YmdHis').'_'.$this->accountId.'.html';
                    file_put_contents($this->path,' start_time:'.time(),FILE_APPEND);
                }

                $uncloseds = EbayReturnsRequests::find()->where('account_id=:account_id and state<>"CLOSED"',[':account_id'=>$this->accountId])->limit($limit)->offset($offset)->all();

                ignore_user_abort(true);
                set_time_limit(7200);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_RETURN_UPDATE;
                $this->apiTaskModel->sendContent = ' offset:'.$offset.' limit:'.$limit;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                if($this->is_log && $this->path)
                {
                    file_put_contents($this->path,' theader_start_time:'.time(),FILE_APPEND);
                }

                if(!empty($uncloseds))
                {
                    $refundInfo = EbayCaseRefund::findOne(['account_id'=>$this->accountId,'is_refund'=>EbayCaseRefund::STATUS_REFUND_YES]);
                    if($refundInfo)
                    {
                        $this->is_refund = true;
                        $this->claim_amount = $refundInfo->claim_amount;
                        $this->currency = $refundInfo->currency;
                    }
                    foreach($uncloseds as $unclosed)
                    {
                        $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/'.$unclosed->return_id,'get',$unclosed);
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
                VHelper::throwTheader('/services/ebay/returnsearch/returnupdate', ['id'=> $this->accountId,'limit'=>$limit,'offset'=>$offset]);
            }
            else
            {
                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                    AccountTaskQueue::TASK_TYPE_RETURN_UPDATE);
                if (!empty($accountTask))
                {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/returnsearch/returnupdate', ['id'=> $accountId]);
                }
            }

            exit('DONE');
        }
        else
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB);
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::TASK_TYPE_RETURN_UPDATE,'platform_code'=>$account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_RETURN_UPDATE;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_EB,'type'=>AccountTaskQueue::TASK_TYPE_RETURN_UPDATE]);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    VHelper::throwTheader('/services/ebay/returnsearch/returnupdate', ['id'=> $accountId]);
                    sleep(2);
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
//        file_put_contents(\Yii::getAlias('@runtime').'/test.php',$content);

        $content = simplexml_load_string($content)->children('soapenv',true)->Body->children()->NotificationEvent;

        $return_id = $content->ReturnId->__toString();

        $returnReturnsModel = EbayReturnsRequests::findOne(['return_id'=>$return_id]);
        if($returnReturnsModel)
        {
            $result = $returnReturnsModel->refreshApi();
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

    private function searchApi($token,$site,$serverUrl,$params,$method)
    {
//        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
//        $api->urlParams = $params;
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
//            $this->handleSearchResponse($response);
//            if(isset($params['offset']) && $response->paginationOutput->totalPages > $params['offset'])
//            {
//                $params['offset']++;
//                $this->searchApi($token,$site,$serverUrl,$params,$method);
//            }
//        }

        $transfer_ip = include \Yii::getAlias('@app').'/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
        if(empty($transfer_ip))
            return ['flag'=>false,'info'=>'中转服务器配置错误'];

        $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$token,'data'=>'','method'=>$method,'responseHeader'=>false,'urlParams'=>$params];
        $api = new PostOrderAPI('ceshi','',$transfer_ip,'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();
        if(empty($response))
        {
            $this->apiTaskModel->status = 1;
            $this->apiTaskModel->error = "[错误码：{$this->errorCode}。".$api->getServerUrl()."香港服务器无响应。]";
            $this->apiTaskModel->save();
            $this->errorCode++;
        }
        else
        {
            if(in_array($response->code,[200,201,202]))
            {
                $response = json_decode($response->response);
                if($response->paginationOutput->totalEntries == 0)
                {
                    $this->apiTaskModel->status = 2;
                    $this->apiTaskModel->error = "[警告：{$this->errorCode}。".$api->getServerUrl()."无数据。]";
                    $this->apiTaskModel->save();
                }
                else if($response->total > 0)
                {
                    $this->handleSearchResponse($response);
                    if(isset($params['offset']) && $response->paginationOutput->totalPages > $params['offset'])
                    {
                        $params['offset']++;
                        $this->searchApi($token,$site,$serverUrl,$params,$method);
                    }
                }

            }
            else
            {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->error = "[错误码：{$this->errorCode}。".$api->getServerUrl()."拉取无数据。]";
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
        }
    }

    private function handleSearchResponse($data)
    {
        if(isset($data->members))
        {
            foreach($data->members as $member)
            {
                /*$buyer = '';
                if($member->creationInfo->item->transactionId){
                    $orderinfo = Order::getOrderStackByTransactionId('EB', $member->creationInfo->item->transactionId);
                    if (!empty($orderinfo))
                    {
                        $orderinfo = Json::decode(Json::encode($orderinfo), true);
                        $buyer = $orderinfo['info']['buyer_id'];
                    }
                }*/
                $this->detailApi($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/'.$member->returnId,'get');
            }
        }

    }

    //升级成case自动退款
    private function autoRefundAfterEscalate($caseId,$returnModel)
    {
        //升级case,自动退款
        $refundApi = new PostOrderAPI($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/casemanagement/'.$caseId.'/issue_refund','post');
        $refundResponse = $refundApi->sendHttpRequest();
        $responseModel = new EbayCaseResponse();
        $responseModel->case_id = $caseId;
        $responseModel->type = 1;
        $responseModel->content = '';
        $responseModel->account_id = $returnModel->account_id;
        if(empty($refundResponse))
        {
            $responseModel->status = 0;
            $responseModel->error = 'return升级自动退款调用接口失败,无返回值';
        }
        else
        {
            $responseModel->status = 1;
            $responseModel->error = '';
            $responseModel->refund_source = $refundResponse->refundResult->refundSource;
            $responseModel->refund_status = $refundResponse->refundResult->refundStatus;
            $returnModel->auto_refund = 2;
        }
        try{
            $flag = $responseModel->save();
        }catch(Exception $e){
            $flag = false;
        }
        $returnModel->save();
    }

    private function detailApi($token,$site,$serverUrl,$method,$masterModel = '')
    {
//        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
//        $response = $api->sendHttpRequest();
//        if(empty($response))
//        {
//            $this->apiTaskModel->status = 1;
//            $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}拉取无数据。]";
//            $this->apiTaskModel->save();
//            $this->errorCode++;
//        }
//        else
//            $this->handleDetailResponse($response,$masterModel);

        $transfer_ip = include \Yii::getAlias('@app').'/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';

        $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$token,'data'=>'','method'=>$method,'responseHeader'=>false,'urlParams'=>''];
        $api = new PostOrderAPI('ceshi','',$transfer_ip,'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();
        if($this->is_log && $this->path)
        {
            file_put_contents($this->path,' get_return_time:'.time(),FILE_APPEND);
        }
        if(empty($response))
        {
            $this->apiTaskModel->status = 1;
            $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}香港服务器无响应。]";
            $this->apiTaskModel->save();
            $this->errorCode++;
        }
        else
        {
            if(in_array($response->code,[200,201,202]))
            {
                $response = json_decode($response->response);
                $this->handleDetailResponse($response,$masterModel);
            }
            else
            {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}拉取无数据。]";
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
        }
    }

    private function handleDetailResponse($data,$masterModel)
    {
        $return_reason = array('FOUND_BETTER_PRICE', 'NO_LONGER_NEED_ITEM', 'NO_REASON', 'ORDERED_ACCIDENTALLY', 'ORDERED_WRONG_ITEM', 'WRONG_SIZE', 
            'BUYER_CANCEL_ORDER', 'EXPIRED_ITEM', 'OTHER', 'RETURNING_GIFT', 'BUYER_NO_SHOW', 'BUYER_NOT_SCHEDULED', 'BUYER_REFUSED_TO_PICKUP');
        if($this->is_log && $this->path)
        {
            file_put_contents($this->path,' handle_start_time:'.time(),FILE_APPEND);
        }
        $summary = $data->summary;
        $detail = $data->detail;
        $returnId = $summary->returnId;
        $flag = true;
        $platformOrderId = '';
        $orderinfo = '';
        $order_id = '';


        if($data->summary->creationInfo->item->transactionId)
        {
            $orderinfo = Order::getOrderStackByTransactionId('EB', $data->summary->creationInfo->item->transactionId);
        }
        else
        {
            $platformOrderId =  $data->summary->creationInfo->item->itemId.'-0';
            $orderinfo = Order::getOrderStackByOrderId('EB', $platformOrderId);
        }
        if (!empty($orderinfo) && !empty($orderinfo->info))
        {
            $orderinfo = Json::decode(Json::encode($orderinfo), true);
            $platformOrderId = $orderinfo['info']['platform_order_id'];
            $totalPrice = $orderinfo['info']['total_price'];
            $order_id = $orderinfo['info']['order_id'];
        }
        if(isset($detail->files))
        {
            $hasImgae = count($detail->files);
            $nowImageNums = EbayReturnImage::find()->where(['return_id'=>$returnId])->count();
            if($nowImageNums != $hasImgae)
            {
                //            $getImageApi = new PostOrderAPI($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/'.$returnId.'/files','get');
//            $imageInfo = $getImageApi->sendHttpRequest();

                $transfer_ip = include \Yii::getAlias('@app').'/config/transfer_ip.php';
                $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';

                $serverUrl = 'https://api.ebay.com/post-order/v2/return/'.$returnId.'/files';
                $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$this->ebayAccountModel->user_token,'data'=>'','method'=>'get','responseHeader'=>false,'urlParams'=>''];
                $api = new PostOrderAPI('ceshi','',$transfer_ip,'post');
                $api->setData($post_data);
                $response = $api->sendHttpRequest();
                if(empty($response))
                {
                    $this->apiTaskModel->status = 1;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}香港服务器无响应。]";
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                }
                else
                {
                    if(in_array($response->code,[200,201,202]))
                    {
                        $imageInfo = json_decode($response->response);
                    }
                    else
                    {
                        $this->apiTaskModel->status = 2;
                        $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}拉取无数据，原因：]".$response->response;
                        $this->apiTaskModel->save();
                        $this->errorCode++;
                    }
                }
                if($this->is_log && $this->path)
                {
                    file_put_contents($this->path,' get_img_time:'.time(),FILE_APPEND);
                }
            }
        }
        if(empty($masterModel))
        {
            $masterModel = EbayReturnsRequests::findOne(['return_id'=>$returnId]);
            if(empty($masterModel))
                $masterModel = new EbayReturnsRequests();
        }
        $masterModel->buyer_login_name = isset($summary->buyerLoginName) ? $summary->buyerLoginName : (isset($masterModel->buyer_login_name) ? $masterModel->buyer_login_name : '');
        $masterModel->buyer_address = isset($detail->buyerAddress) ? serialize($detail->buyerAddress):'';
        $masterModel->buyer_response_activity_due = isset($summary->buyerResponseDue->activityDue) ? $summary->buyerResponseDue->activityDue : '';
        $masterModel->buyer_response_date = isset($summary->buyerResponseDue->respondByDate->value) ? explode('.',str_replace('T',' ',$summary->buyerResponseDue->respondByDate->value))[0] : null;
        if(empty($masterModel->currency))
            $masterModel->currency = isset($summary->buyerTotalRefund->actualRefundAmount->currency) ? $summary->buyerTotalRefund->actualRefundAmount->currency : '';
        $masterModel->actual_refund_amount = isset($summary->buyerTotalRefund->actualRefundAmount->value) ? $summary->buyerTotalRefund->actualRefundAmount->value : 0;
        if(empty($masterModel->currency) && isset($summary->buyerTotalRefund->estimatedRefundAmount->currency))
            $masterModel->currency = $summary->buyerTotalRefund->estimatedRefundAmount->currency;
        $masterModel->buyer_estimated_refund_amount = isset($summary->buyerTotalRefund->estimatedRefundAmount->value) ? $summary->buyerTotalRefund->estimatedRefundAmount->value : 0;
        if(empty($masterModel->currency) && isset($summary->sellerTotalRefund->estimatedRefundAmount->currency))
            $masterModel->currency = $summary->sellerTotalRefund->estimatedRefundAmount->currency;
        $masterModel->seller_estimated_refund_amount = isset($summary->sellerTotalRefund->estimatedRefundAmount->value) ? $summary->sellerTotalRefund->estimatedRefundAmount->value : 0;
        $masterModel->return_reason = $summary->creationInfo->reason;
        $masterModel->current_type = $summary->currentType;
        $masterModel->return_comments = isset($summary->creationInfo->comments->content) ? $summary->creationInfo->comments->content : '';
        $masterModel->return_creation_date = explode('.',str_replace('T',' ',$summary->creationInfo->creationDate->value))[0];
        $masterModel->item_id = $summary->creationInfo->item->itemId;
        $masterModel->return_quantity = $summary->creationInfo->item->returnQuantity;
        $masterModel->transaction_id = $summary->creationInfo->item->transactionId;
        if(empty($summary->dispositionRuleTriggered))
            $masterModel->disposition_rule_triggered = '';
        else
        {
            $masterModel->disposition_rule_triggered = implode('|',(array)$summary->dispositionRuleTriggered);
        }
        $masterModel->buyer_escalation_eligible = isset($summary->escalationInfo->buyerEscalationEligibilityInfo->eligible) ? $summary->escalationInfo->buyerEscalationEligibilityInfo->eligible : '';
        $masterModel->buyer_escalation_end_time = isset($summary->escalationInfo->buyerEscalationEligibilityInfo->endTime->value) ? explode('.',str_replace('T',' ',$summary->escalationInfo->buyerEscalationEligibilityInfo->endTime->value))[0] : null;
        $masterModel->buyer_escalation_start_time = isset($summary->escalationInfo->buyerEscalationEligibilityInfo->startTime->value) ? explode('.',str_replace('T',' ',$summary->escalationInfo->buyerEscalationEligibilityInfo->startTime->value))[0] : null;
        $masterModel->has_image = isset($hasImgae) ? $hasImgae:0;
        $masterModel->platform_order_id = $platformOrderId;
        $masterModel->order_id = $order_id;
        
        //add by allen 指定退款原因无需自动退款 <2018-09-06> str
        if($masterModel->isNewRecord){
            if(in_array($summary->creationInfo->reason,$return_reason)){
                $auto_refund = 1;//升级无需退款  复选框选中
            }else{
                $auto_refund = 0;//默认升级需退款 复选框不选中
            }
            $masterModel->auto_refund = $auto_refund;
        }
        //add by allen 指定退款原因无需自动退款 <2018-09-06> end
        /*if(isset($this->currency))
            $currency = $this->currency;
        else
            $currency = Account::CURRENCY;

        if(isset($totalPrice))
            $finalCNY = VHelper::getTargetCurrencyAmt($masterModel->currency,$currency,$totalPrice);

        if(isset($this->claim_amount))
            $max_claim_amount = $this->claim_amount;
        else
            $max_claim_amount = Account::ACCOUNT_PRICE;*/

//        if($this->is_refund && $masterModel->auto_refund == 0 && empty($masterModel->case_id) && isset($summary->escalationInfo->caseId) && isset($totalPrice) && $finalCNY < $max_claim_amount)
//        {
//            $flag_refund = true;
//            //升级case,自动退款
//            // 查询升级信息
////            $searchApi = new PostOrderAPI($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/casemanagement/search?return_id='.$returnId,'get');
////            $caseSearch = $searchApi->sendHttpRequest();
//
//            $serverUrl = 'https://api.ebay.com/post-order/v2/casemanagement/search?return_id='.$returnId;
//            $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$this->ebayAccountModel->user_token,'data'=>'','method'=>'get','responseHeader'=>false,'urlParams'=>''];
//            $api = new PostOrderAPI('ceshi','','http://47.89.227.98/getInfoFromCustomer.php','post');
//            $api->setData($post_data);
//            $caseSearch = $api->sendHttpRequest();
//
//            if(empty($caseSearch))
//            {
//                $caseResponse = new EbayCaseResponse();
//                $caseResponse->content = '';
//                $caseResponse->type = 1;
//                $caseResponse->status = 0;
//                $caseResponse->account_id = $this->accountId;
//                $caseResponse->error = "退款退货纠纷{$returnId}获取升级信息香港服务器无响应";
//                $caseResponse->save();
//                $flag_refund = false;
//            }
//            else
//            {
//                if(in_array($caseSearch->code,[200,201,202]))
//                {
//                    $caseSearch = json_decode($caseSearch->response);
//                    if(empty($caseSearch->members))
//                    {
//                        $caseResponse = new EbayCaseResponse();
//                        $caseResponse->content = '';
//                        $caseResponse->type = 1;
//                        $caseResponse->status = 0;
//                        $caseResponse->account_id = $this->accountId;
//                        $caseResponse->error = "退款退货纠纷{$returnId}未获取到升级信息";
//                        $caseResponse->save();
//                        $flag_refund = false;
//                    }
//                    else
//                    {
//                        $caseDetail = $caseSearch->members[0];
//                    }
//                }
//                else
//                {
//                    $caseResponse = new EbayCaseResponse();
//                    $caseResponse->content = '';
//                    $caseResponse->type = 1;
//                    $caseResponse->status = 0;
//                    $caseResponse->account_id = $this->accountId;
//                    $caseResponse->error = "退款退货纠纷{$returnId}获取升级信息无响应";
//                    $caseResponse->save();
//                    $flag_refund = false;
//                }
//
//
//            }
//
//            if($flag_refund == true && isset($caseDetail->caseStatusEnum) && $caseDetail->caseStatusEnum != 'CLOSED' && $caseDetail->caseStatusEnum != 'CS_CLOSED')
//            {
//                $caseModel = EbayCase::findOne(['case_id'=>$caseDetail->caseId]);
//                if($caseModel)
//                    $flag_refund = false;
//
//                if($flag_refund)
//                {
//                    $caseModel = new EbayCase();
//                    $caseModel->case_id = isset($caseDetail->caseId) ? $caseDetail->caseId : '';
//                    $caseModel->item_id = isset($caseDetail->itemId) ? $caseDetail->itemId : '';
//                    $caseModel->return_id = $returnId;
//                    $caseModel->case_type = EbayCase::CASE_TYPE_REFUND;
//                    $caseModel->transaction_id = isset($caseDetail->transactionId) ? $caseDetail->transactionId : '';
//                    $caseModel->buyer = isset($caseDetail->buyer) ? $caseDetail->buyer : '';
//                    $caseModel->account_id = $this->accountId;
//                    $caseModel->status = isset($caseDetail->caseStatusEnum) ? $caseDetail->caseStatusEnum : '';
//                    $caseModel->claim_amount = isset($caseDetail->claimAmount) && !empty($caseDetail->claimAmount->value) ? $caseDetail->claimAmount->value : '';
//                    $caseModel->currency = isset($caseDetail->claimAmount) && !empty($caseDetail->claimAmount->currency) ? $caseDetail->claimAmount->currency : '';
//                    $caseModel->creation_date = isset($caseDetail->creationDate) && !empty($caseDetail->creationDate->value) ? date('Y-m-d H:i:s',strtotime($caseDetail->creationDate->value)) : '';
//                    $caseModel->last_modified_date = isset($caseDetail->lastModifiedDate) && !empty($caseDetail->lastModifiedDate->value) ? date('Y-m-d H:i:s',strtotime($caseDetail->lastModifiedDate->value)) : '';
//
////                    $refundApi = new PostOrderAPI($this->ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/casemanagement/'.$summary->escalationInfo->caseId.'/issue_refund','post');
////                    $message = ['comments'=>['content'=>'']];
////                    $refundApi->setData($message);
////                    $refundResponse = $refundApi->sendHttpRequest();
//
//                    $serverUrl = 'https://api.ebay.com/post-order/v2/casemanagement/'.$summary->escalationInfo->caseId.'/issue_refund';
//                    $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$this->ebayAccountModel->user_token,'data'=>json_encode(['comments'=>['content'=>'']]),'method'=>'post','responseHeader'=>false,'urlParams'=>''];
//                    $api = new PostOrderAPI('ceshi','','http://47.89.227.98/getInfoFromCustomer.php','post');
//                    $api->setData($post_data);
//                    $refundResponse = $api->sendHttpRequest();
//
//                    $caseResponse = new EbayCaseResponse();
//                    $caseResponse->content = '';
//                    $caseResponse->type = 1;
//                    $caseResponse->account_id = $this->accountId;
//                    $caseResponse->case_id = $caseModel->case_id;
//                    if(empty($refundResponse))
//                    {
//                        $caseResponse->status = 0;
//                        $caseResponse->error = '自动退款调用接口失败,香港服务器无返回值';
//                    }
//                    else
//                    {
//                        if(in_array($refundResponse->code,[200,201,202]))
//                        {
//                            $refundResponse = json_decode($refundResponse->response);
//                            $caseResponse->status = 1;
//                            $caseResponse->error = '';
//                            $caseResponse->refund_source = isset($refundResponse->refundResult->refundSource) ? $refundResponse->refundResult->refundSource : '';
//                            $caseResponse->refund_status = $refundResponse->refundResult->refundStatus;
//                            //建立退款售后处理单
//                            /*if($refundResponse->refundResult->refundStatus == 'SUCCESS')
//                            {
//                                $afterSalesOrderModel = new AfterSalesOrder();
//                                $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
//                                $afterSalesOrderModel->transaction_id = $masterModel->transaction_id;
//                                $afterSalesOrderModel->type = AfterSalesOrder::ORDER_TYPE_REFUND;
//                                $afterSalesOrderModel->reason_id = 27;
//                                $afterSalesOrderModel->platform_code = Platform::PLATFORM_CODE_EB;
//                                $afterSalesOrderModel->order_id = $masterModel->order_id;
//                                $afterSalesOrderModel->account_id = $this->accountId;
//
//                                $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
//                                $afterSalesOrderModel->approver = 'system';
//                                $afterSalesOrderModel->approve_time = date('Y-m-d H:i:s');
//                                $afterSalesOrderModel->buyer_id = $masterModel->buyer_login_name;
//                                $afterSalesOrderModel->account_id = $this->accountId;
//                                $afterSalesOrderModel->account_name = Account::getAccountName($caseModel->account_id,Platform::PLATFORM_CODE_EB);
//
//                                $afterSaleOrderRefund = new AfterSalesRefund();
//                                $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
//                                $afterSaleOrderRefund->refund_amount = $caseModel->claim_amount;
//                                $afterSaleOrderRefund->currency = $caseModel->currency;
//                                $afterSaleOrderRefund->transaction_id = $caseModel->transaction_id;
//                                $afterSaleOrderRefund->order_id = $masterModel->order_id;
//                                $afterSaleOrderRefund->platform_code = Platform::PLATFORM_CODE_EB;
//                                $afterSaleOrderRefund->order_amount = $caseModel->claim_amount;
//                                $afterSaleOrderRefund->reason_code = $afterSalesOrderModel->reason_id;
//                                $afterSaleOrderRefund->refund_time = date('Y-m-d H:i:s');
//                                $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
//                            }*/
//                        }
//                        else
//                        {
//                            $caseResponse->status = 0;
//                            $caseResponse->error = '自动退款调用接口失败,无返回值';
//                        }
//
//                    }
//                    try{
//                        // 保存退款结果
//                        $caseResponse->save();
//                        // 保存case到主表
//                        $caseModel->save();
//                    }
//                    catch (\Exception $e)
//                    {
//                        $caseResponse = new EbayCaseResponse();
//                        $caseResponse->type = 1;
//                        $caseResponse->content = '';
//                        $caseResponse->status = 0;
//                        $caseResponse->account_id = $this->accountId;
//                        $caseResponse->case_id = $caseModel->case_id;
//                        $caseResponse->error = $e->getMessage()."。退款退货纠纷{$masterModel->return_id}个案编号{$caseModel->case_id}保存失败！";
//                        $caseResponse->save();
//                    }
//
//                    if($refundResponse->refundResult->refundStatus != 'SUCCESS')
//                    {
//                        if(isset($this->apiTaskModel))
//                        {
//                            $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。returnID:'.$returnId.'升级自动退款出错。';
//                        }
//                    }
//                    /*elseif(isset($afterSalesOrderModel))
//                    {
//                        $transaction = EbayReturnsRequests::getDb()->beginTransaction();
//
//                        try{
//                            $flag_after = $afterSalesOrderModel->save();
//                            if(!$flag_after && isset($this->apiTaskModel))
//                            {
//                                $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。returnID:'.$returnId.'升级自动退款建立售后处理单出错。'.VHelper::getModelErrors($afterSalesOrderModel).']';
//                            }
//                            elseif(isset($afterSaleOrderRefund))
//                            {
//                                $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
//                                $flag_after = $afterSaleOrderRefund->save();
//                                if(!$flag_after && isset($this->apiTaskModel))
//                                    $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。returnID:'.$returnId.'升级自动退款建立售后退款单出错。'.VHelper::getModelErrors($afterSaleOrderRefund).']';
//                            }
//                        }catch(Exception $e){
//                            $flag_after = false;
//                            if(isset($this->apiTaskModel))
//                                $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。returnID:'.$returnId.'升级自动退款出错。'.$e->getMessage().']';
//                        }
//                        if($flag_after == true)
//                            $transaction->commit();
//                        else
//                            $transaction->rollBack();
//                    }*/
//                }
//            }
//        }

        // 保存纠纷主表
        if($flag)
        {
            $masterModel->case_id = isset($summary->escalationInfo->caseId) ? $summary->escalationInfo->caseId : '';
            $masterModel->seller_escalation_eligible = isset($summary->escalationInfo->sellerEscalationEligibilityInfo->eligible) ? $summary->escalationInfo->sellerEscalationEligibilityInfo->eligible : '';
            $masterModel->seller_escalation_end_time = isset($summary->escalationInfo->sellerEscalationEligibilityInfo->endTime->value) ? explode('.',str_replace('T',' ',$summary->escalationInfo->sellerEscalationEligibilityInfo->endTime->value))[0] : null;
            $masterModel->seller_escalation_start_time = isset($summary->escalationInfo->sellerEscalationEligibilityInfo->startTime->value) ? explode('.',str_replace('T',' ',$summary->escalationInfo->sellerEscalationEligibilityInfo->startTime->value))[0] : null;
            $masterModel->return_id = $returnId;
            $masterModel->return_policy_rma_required = isset($summary->returnPolicy->rmarequired) ? $summary->returnPolicy->rmarequired : '';
            $masterModel->seller_login_name = $summary->sellerLoginName;
            $masterModel->seller_address = isset($detail->sellerAddress) ? serialize($detail->sellerAddress):'';
            $masterModel->seller_response_activity_due = isset($summary->sellerResponseDue->activityDue) ? $summary->sellerResponseDue->activityDue : '';
            $masterModel->seller_response_date = isset($summary->sellerResponseDue->respondByDate->value) ? explode('.',str_replace('T',' ',$summary->sellerResponseDue->respondByDate->value))[0] : null;
            $masterModel->state = $summary->state;
            $masterModel->status = $summary->status;
            $masterModel->update_time = date('Y-m-d H:i:s');
            $masterModel->account_id = $this->accountId;
            
            //需求优化 1256  update by allen str <2018-09-04> 
            if($masterModel->isNewRecord){
                if($summary->status == 'WAITING_FOR_RETURN_LABEL' && (in_array($summary->state,['RETURN_LABEL_PENDING','RETURN_LABEL_PENDING_TIMEOUT']))){
                    $masterModel->is_transition = 0;
                }else{
                    $masterModel->is_transition = 1;
                }
            }
            //需求优化 1256  update by allen end <2018-09-04> 
            if(!isset($transaction))
                $transaction = EbayReturnsRequests::getDb()->beginTransaction();
            try{
                $flag = $masterModel->save();
                if(!$flag)
                    $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。returnID:'.$returnId.'主表信息保存出错。'.VHelper::getModelErrors($masterModel).']';
            }catch (Exception $e) {
                $flag = false;
                $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。returnID:'.$returnId.'主表信息保存出错。'.$e->getMessage().']';
            }
            if(!$flag)
            {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->save();
                $this->errorCode++;
            }

        }
        if($flag && isset($imageInfo))
        {
            EbayReturnImage::deleteAll(['return_id'=>$returnId]);

            if(isset($imageInfo->files))
            {
                foreach($imageInfo->files as $image)
                {
                    $returnImageModel = new EbayReturnImage();
                    $returnImageModel->return_id = $returnId;
                    $returnImageModel->file_id = $image->fileId;
                    $returnImageModel->file_purpose = $image->filePurpose;
                    $returnImageModel->file_purpose = $image->filePurpose;
                    $returnImageModel->creation_date = explode('.',str_replace('T',' ',$image->creationDate->value))[0];
                    $path = 'uploads/ebay_return/'.str_replace('-','/',explode('T',$returnImageModel->creation_date)[0]);
                    if(!is_dir($path))
                        mkdir($path,0760,true);
                    $filePath = $path.'/'.$image->fileId.'_return.'.$image->fileFormat;
                    file_put_contents($filePath,base64_decode($image->fileData));
                    $returnImageModel->file_path = $filePath;
                    $resizeFilePath = $path.'/'.$image->fileId.'_return_resize.'.$image->fileFormat;
                    file_put_contents($resizeFilePath,base64_decode($image->resizedFileData));
                    $returnImageModel->resize_file_path = $resizeFilePath;
                    $returnImageModel->file_format = $image->fileFormat;
                    $returnImageModel->submitter = $image->submitter;
                    $returnImageModel->file_status = $image->fileStatus;
                    try{
                        $flag = $returnImageModel->save();
                        if(!$flag)
                            $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。FileId:'.$image->fileId.'图片保存出错。'.VHelper::getModelErrors($returnImageModel).']';
                    }catch (Exception $e){
                        $flag = false;
                        $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。FileId:'.$image->fileId.'图片保存出错。'.$e->getMessage().']';
                    }
                    if(!$flag)
                    {
                        $this->apiTaskModel->status = 1;
                        $this->apiTaskModel->save();
                        $this->errorCode++;
                        break;
                    }
                }
            }

            if($this->is_log && $this->path)
            {
                file_put_contents($this->path,' save_return_time:'.time(),FILE_APPEND);
            }

        }
        if($flag)
        {
            $historys = $detail->responseHistory;
            if(!empty($historys))
            {
                EbayReturnsRequestsDetail::deleteAll(['return_id'=>$returnId]);
                // 最新状态
                $lastStatus = '';
                // 次新状态
                $secondStatus = '';
                // 是否需要更新售后单退款状态
                $is_after_refund = false;
                
                //add by allen 关闭的纠纷判断 【如果卖家提供了部分退款 并且客户接受超时的条件下 把售后单(退款单状态改成退款失败)】 2018-05-29
                $isClose = 0;//纠纷是否关闭
                $isSellerOfferPartialRefund = 0;//是否卖家提供部分退款
                $isTimeOutForAuthorize = 0;//授权是否超时
                $isTimeOutForEscalation = 0;//是否超时退出
                $isPartialRefundFailed = 0;//部分退款失败 state
                $isPARTIAL_REFUND_FAILED = 0;//部分退款失败 status
                $isPartialRefundDeclined = 0;//客服拒绝接受部分退款
                $isBuyerAcceptsPartialRefund = 0;//客户接受部分退款
                $isBuyerCloseReturn = 0;//客人主动关闭纠纷
                
                $creat_d = '';
                foreach($historys as $history)
                {
                    if($history->author == 'BUYER'){
                        $creat_d = explode('.',str_replace('T',' ',$history->creationDate->value))[0];
                    }
                    //纠纷关闭
                    if($history->toState == 'CLOSED'){
                        $isClose = 1;//有状态为关闭的时候记录状态关闭
                    }

                    //部分退款失败
                    if($history->toState == 'PARTIAL_REFUND_FAILED'){
                        $isPartialRefundFailed = 1;
                    }

                    //客户拒绝部分退款
                    if($history->toState == 'PARTIAL_REFUND_DECLINED'){
                        $isPartialRefundDeclined = 1;
                    }

                    //客户接受部分退款
                    if($history->activity == 'BUYER_ACCEPTS_PARTIAL_REFUND'){
                        $isBuyerAcceptsPartialRefund = 1;
                    }

                    //卖家提供部分退款
                    if($history->activity == 'SELLER_OFFER_PARTIAL_REFUND'){
                        $isSellerOfferPartialRefund = 1;
                    }

                    //授权超时
                    if($history->activity == 'TIME_OUT_FOR_AUTHORIZE'){
                        $isTimeOutForAuthorize = 1;
                    }
                    
                    //客人主动关闭纠纷
                    if($history->activity == 'BUYER_CLOSE_RETURN'){
                        $isBuyerCloseReturn = 1;
                    }

                    //超时退出
                    if($history->activity == 'TIME_OUT_FOR_ESCALATION'){
                        $isTimeOutForEscalation = 1;
                    }

                    //买家接受失败
                    if($history->activity == 'PARTIAL_REFUND_FAILED'){
                        $isPARTIAL_REFUND_FAILED = 1;
                    }
                    //add by allen 关闭的纠纷判断 【如果卖家提供了部分退款 并且客户接受超时的条件下 把售后单(退款单状态改成退款失败)】 2018-05-29 end
                    
                    $detailModel = new EbayReturnsRequestsDetail();
                    $detailModel->return_id = $returnId;
                    $detailModel->activity = $history->activity;
                    $detailModel->author = $history->author;
                    $detailModel->creation_date_value = explode('.',str_replace('T',' ',$history->creationDate->value))[0];
                    $detailModel->from_state = $history->fromState;
                    $detailModel->to_state = $history->toState;
                    
                    $detailModel->notes = isset($history->notes) ? $history->notes : '';
                    $detailModel->carrier_used = isset($history->attributes->carrierUsed) ? $history->attributes->carrierUsed : '';
                    $detailModel->escalate_reason = isset($history->attributes->escalateReason) ? $history->attributes->escalateReason : '';
                    $detailModel->money_movement_ref = isset($history->attributes->moneyMovementRef) ? $history->attributes->moneyMovementRef->idref : '';
                    $detailModel->partial_refund_amount = isset($history->attributes->partialRefundAmount) ? $history->attributes->partialRefundAmount->value : 0;
                    $detailModel->currency = isset($history->attributes->partialRefundAmount) ? $history->attributes->partialRefundAmount->currency : '';
                    $detailModel->email = isset($history->attributes->toEmailAddress) ? $history->attributes->toEmailAddress : '';
                    $detailModel->tracking_uumber = isset($history->attributes->trackingNumber) ? $history->attributes->trackingNumber : '';
                    $detailModel->address_address_line1 = isset($history->attributes->attributes->sellerReturnAddress->addressLine1) ? $history->attributes->attributes->sellerReturnAddress->addressLine1 : '';
                    $detailModel->address_address_line2 = isset($history->attributes->attributes->sellerReturnAddress->addressLine2) ? $history->attributes->attributes->sellerReturnAddress->addressLine2 : '';
                    $detailModel->address_address_type = isset($history->attributes->attributes->sellerReturnAddress->addressType) ? $history->attributes->attributes->sellerReturnAddress->addressType : '';
                    $detailModel->address_city = isset($history->attributes->attributes->sellerReturnAddress->city) ? $history->attributes->attributes->sellerReturnAddress->city : '';
                    $detailModel->address_country = isset($history->attributes->attributes->sellerReturnAddress->country) ? $history->attributes->attributes->sellerReturnAddress->country : '';
                    $detailModel->address_county = isset($history->attributes->attributes->sellerReturnAddress->county) ? $history->attributes->attributes->sellerReturnAddress->county : '';
                    $detailModel->address_is_transliterated = isset($history->attributes->attributes->sellerReturnAddress->address_is_transliterated) ? (int)$history->attributes->attributes->sellerReturnAddress->address_is_transliterated : '';
                    $detailModel->address_national_region = isset($history->attributes->attributes->sellerReturnAddress->nationalRegion) ? $history->attributes->attributes->sellerReturnAddress->nationalRegion : '';
                    $detailModel->address_postal_code = isset($history->attributes->attributes->sellerReturnAddress->postalCode) ? $history->attributes->attributes->sellerReturnAddress->postalCode : '';
                    $detailModel->address_script = isset($history->attributes->attributes->sellerReturnAddress->script) ? $history->attributes->attributes->sellerReturnAddress->script : '';
                    $detailModel->address_state_or_province = isset($history->attributes->attributes->sellerReturnAddress->stateOrProvince) ? $history->attributes->attributes->sellerReturnAddress->stateOrProvince : '';
                    $detailModel->address_transliterated_from_script = isset($history->attributes->attributes->sellerReturnAddress->transliteratedFromScript) ? $history->attributes->attributes->sellerReturnAddress->transliteratedFromScript : '';
                    $detailModel->address_world_region = isset($history->attributes->attributes->sellerReturnAddress->worldRegion) ? $history->attributes->attributes->sellerReturnAddress->worldRegion : '';
                    $secondStatus = $lastStatus;
                    $lastStatus = $detailModel->activity;


                    try{
                        $flag = $detailModel->save();
                        if(!$flag)
                            $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。returnID:'.$returnId.'历史信息保存出错。'.VHelper::getModelErrors($masterModel).']';
                    }catch(Exception $e){
                        $flag = false;
                        $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。returnID:'.$returnId.'历史信息保存出错。'.$e->getMessage().']';
                    }
                    if(!$flag)
                    {
                        $this->apiTaskModel->status = 1;
                        $this->apiTaskModel->save();
                        $this->errorCode++;
                        break;
                    }
                    
                    //客户接受部分退款
//                    if($detailModel->activity == 'BUYER_ACCEPTS_PARTIAL_REFUND')
//                    {
//                        $is_after_refund = true;
//                        $to_state = $history->toState;
//                    }elseif($detailModel->activity == 'BUYER_DECLINE_PARTIAL_REFUND'){
//                        //客户拒绝接受部分退款
//                        $is_after_refund = true;
//                        $to_state = $history->toState;
//                    }
                }
                
//                if($isClose && $isSellerOfferPartialRefund && $isTimeOutForAuthorize){
//                    $is_after_refund = true;
//                    $changeColse = 1;
//                }
                if(isset($detailModel))
                {
                    if($detailModel->author != 'SELLER' && in_array($detailModel->activity,$this->isDealMaps)){
                        $masterModel->is_deal = 0;
                    }else{
                        $masterModel->is_deal = 1;
                    }

                    if($lastStatus == 'REMINDER_SELLER_TO_RESPOND' && in_array($secondStatus,$this->isDealMaps)){
                        $masterModel->is_deal = 0;
                    }
                    
                    if($lastStatus == 'TIME_OUT_FOR_AUTHORIZE' && $secondStatus == 'BUYER_SEND_MESSAGE'){
                        $masterModel->is_deal = 0;
                    }
                    
                    if($lastStatus == 'AUTO_APPROVE_REMORSE'){
                        $masterModel->is_deal = 0;
                    }

                    //状态为 RMA_PENDING 和 状况为 WAITING_FOR_RMA
                    if($masterModel->state == "RMA_PENDING" && $masterModel->status == "WAITING_FOR_RMA"){
                        $masterModel->is_deal = 0;
                    }

                    //将退款退货纠纷插入到纠纷统计表
                    $disputeStatistics = DisputeStatistics::findOne(['dispute_id'=>$returnId,'type' => AccountTaskQueue::TASK_TYPE_RETURN,'platform_code'=>Platform::PLATFORM_CODE_EB,'status' => 0]);
                    if($masterModel->is_deal == 0 && empty($disputeStatistics)){
                        $disputeStatistics = new DisputeStatistics();
                        $disputeStatistics->status = 0;
                        $disputeStatistics->platform_code = Platform::PLATFORM_CODE_EB;
                        $disputeStatistics->account_id = $this->accountId;
                        $disputeStatistics->type = AccountTaskQueue::TASK_TYPE_RETURN;
                        $disputeStatistics->create_time = date('Y-m-d H:i:s');
                        $disputeStatistics->dispute_id = $returnId;
                        $disputeStatistics->return_creation_date = $creat_d;
                        $disputeStatistics->save(false);
                    }


//                    if($lastStatus == 'TIME_OUT_FOR_ESCALATION' && $secondStatus == 'SELLER_OFFER_PARTIAL_REFUND')
//                    {
//                        $is_after_refund = true;
//                        $to_state = 'PARTIAL_REFUND_DECLINED';
//                    }
                    $flag = $masterModel->save();

                }
            }
        }

    //更新退款单状态
            if($flag){
                $after_sale_id = "";
                $changeArr =[];
                //如果纠纷状态为关闭  最新状态或者历史状态中有出现过客户接受退款  则退款单改成退款成功
                if($isClose && $isSellerOfferPartialRefund && $isBuyerAcceptsPartialRefund){
                    $changeArr['refund_status'] = 3;//退款成功
                    $changeArr['refund_time'] = date('Y-m-d H:i:s');//退款时间
                    $changeArr['fail_reason'] = '';
                }else{
                    //纠纷状态关闭 最新状态中出现有卖家提供部分退款和接受失败  则退款单状态改为失败
                    if($isClose && $isSellerOfferPartialRefund && $lastStatus == 'PARTIAL_REFUND_FAILED'){
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason'] = '买家接受失败';
                    }

                    //纠纷关闭    最新状态中出现有卖家提供部分退款和超时退出  退款单状态改为失败
                    if($isClose && $isSellerOfferPartialRefund && $lastStatus == 'TIME_OUT_FOR_ESCALATION'){
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason'] = '超时退出';
                    }

                    //纠纷关闭 最新状态出现有卖家提供部分退款,授权超时状态时  退款单状态改为失败
                    if($isClose && $isSellerOfferPartialRefund && $lastStatus == 'TIME_OUT_FOR_AUTHORIZE'){
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason'] = '授权超时';
                    }
                    
                    //纠纷关闭 状态中有出现过卖家提交部分退款和客户主动关闭纠纷 则售后单状态改完失败
                    if($isClose && $isBuyerCloseReturn && $isSellerOfferPartialRefund){
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason'] = '客户关闭纠纷';
                    }

                    //客户接受失败
                    if($isPartialRefundFailed){
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason'] = '买家接受失败';
                    }

                    //客户拒绝
                    if($isPartialRefundDeclined){
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason'] = '买家拒绝';
                    }
                }
                
                $after_sale_id = AfterSalesRefund::find()
                        ->select('after_sale_id')
                        ->where(['order_id'=>$masterModel->order_id])
                        ->andWhere(['<>','refund_status',AfterSalesRefund::REFUND_STATUS_FINISH])
                        ->andWhere(['>=','fail_count','88'])
                        ->column();
                if(!empty($after_sale_id) && !empty($changeArr)){
                    AfterSalesRefund::updateAll($changeArr,['in','after_sale_id',$after_sale_id]);
                }
            }
            
        if($this->is_log && $this->path)
        {
            file_put_contents($this->path,' save_return_detail_time:'.time(),FILE_APPEND);
        }

//        if($flag && $is_after_refund)
//        {
//            $afterRefundModels = AfterSalesRefund::find()
//                ->select('after_sale_id')
//                ->where(['order_id'=>$masterModel->order_id])
////                ->andWhere(['<>','refund_status',AfterSalesRefund::REFUND_STATUS_FINISH])
//                ->andWhere(['>=','fail_count','88']);
////                ->column();
//            $afterRefundModel = array();
//            if($changeColse){
//                    $afterRefundModel = $afterRefundModels->column();
//                    $return_status = AfterSalesRefund::REFUND_STATUS_FAIL;
//                    $changeArr['fail_reason'] = 'the buyer declined the partial refund you offered.';
//            }else if($to_state == 'CLOSED'){
//                $afterRefundModels->andWhere(['<>','refund_status',AfterSalesRefund::REFUND_STATUS_FINISH]);
//                $afterRefundModel = $afterRefundModels->column();
//                if($lastStatus == 'TIME_OUT_FOR_ESCALATION'){
//                    $return_status = AfterSalesRefund::REFUND_STATUS_FAIL;//退款失败
//                }else{
//                    $return_status = AfterSalesRefund::REFUND_STATUS_FINISH; //退款完成
//                }
//            }elseif($to_state == 'PARTIAL_REFUND_DECLINED'){
//                //客户拒绝部分退款
//                $afterRefundModels->andWhere(['refund_status'=>AfterSalesRefund::REFUND_STATUS_WAIT_RECEIVE]);
//                $afterRefundModel = $afterRefundModels->column();
//                $return_status = AfterSalesRefund::REFUND_STATUS_FAIL; //退款失败
//                $changeArr['fail_reason'] = 'the buyer declined the partial refund you offered.';
//                if($lastStatus == 'TIME_OUT_FOR_ESCALATION'){
//                    $changeArr['fail_reason'] = 'TIME_OUT_FOR_ESCALATION';
//                }
//            }
//            if($afterRefundModel)
//            {
//                $changeArr['refund_status'] = $return_status;
//                AfterSalesRefund::updateAll($changeArr,['in','after_sale_id',$afterRefundModel]);
//            }
//        }

        if($flag)
            $transaction->commit();
        else
            $transaction->rollBack();

    }

    // 异步更新一条纠纷数据（页面处理纠纷时使用）
    public function actionRefresh()
    {
        $id = $this->request->get('id');
        if(is_numeric($id) && $id > 0 && $id%1 === 0)
        {
            $result = EbayReturnsRequests::findOne((int)$id)->refreshApi();
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

    public function actionTest()
    {
        $account = $_REQUEST['account'];
//        findClass($account,1);
        $ebayAccountModel = EbayAccount::findOne((int)$account);
//        findClass($ebayAccountModel->attributes,1);
        $apiDetail = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/5059849211','get');
        $response = $apiDetail->sendHttpRequest();
        findClass($apiDetail->getHttpCode(),1,0);
        findClass($response,1);
        $file = $response->detail->files[0];
        file_put_contents('F:\testContent\pengsi.'.$file->fileFormat,base64_decode($file->fileData));
        findClass($apiDetail->sendHttpRequest()->detail->files,1,0);
    }

    public function actionFile()
    {
        set_time_limit(0);
        /*$account = $_REQUEST['account'];
        $ebayAccountModel = EbayAccount::findOne((int)$account);*/
        $user_token = 'AgAAAA**AQAAAA**aAAAAA**MgoTWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AGlISkDZWCog2dj6x9nY+seQ**sHQDAA**AAMAAA**6BqhO64nfKatHFBohN3/IDWnKgmUh18JJgZ+pbMtl1QYok7cCF89FtmcTIjC907Y48sB5rYEQLzzIqsfh+Iq7Lb0K/Hh8v5IVwdamDreqnh+1F6U6ipR4jPAoGKEP0CdIPpZwydv4fTicoLjg/XfrSjUmswgkCIWLyjtgFoFrlHTGkX6l2+xA2nxFur3Jg1iu0vYmMAcUsTr63ZyvFbdcYRUOaJZ/MB2HsqFj7c9eONyJ6ZDGG7o+Mm1mYHYbNI5HM39ndO42pR5jiFFKFdQC98KinNfpfLa0GQ6EC9RS3ofc/7+IhH5jEBiJ76qimlh0ogArz7xhaQq6rH6HIGyu/uazjfi4Dp77UYjVPZeySmtylPdAZDxlWDlrH4eDHHhlJu3tnuoRTs+mq50e1Uzzd6/FwlRT53TSVZLb67zi+iXXlq4FvLtIo4n7kpc8jo67aAtIMC1WFaqMA4OeFVvn0l4mMSGD88XN+FW/57JppqBn6bcQ49uD2MlWrsg9Mgaq/eg5VgOsJGOptu9H2mngBi1eiKV5Lf51VkFLLdrWxeObiSm2Ca5+a37MJXkLfSlewYbavaJgKzgWAZBkRv/t03Hyxaad7XzOd2UwiSLe1OfzU+WS4oXTv0siN+G/yBaRTJgWGr7ABaCdSgrG11fS6gg2/MnREqz2QmreeiS1c/PMpTozWDXwYRauNM1UcRZ1cucNANsDN7T8didwEdrHdQKPRXxYzONh/yIgQMCxv0Doy9nqDBKCirv+c8+5zoy';
        $apiDetail = new PostOrderAPI($user_token,'','https://api.ebay.com/post-order/v2/return/5075127952/files','get');
        $response = $apiDetail->sendHttpRequest();
        var_dump($response);exit;
        foreach($response->files as $key=>$image)
        {
            file_put_contents('F:\testContent\\'.$image->fileName,base64_decode($image->fileData));
            echo $key;
        }

    }
}