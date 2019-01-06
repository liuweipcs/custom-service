<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 下午 7:54
 */
namespace app\commands;

use app\modules\accounts\models\Platform;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\accounts\models\Account;
use app\modules\services\modules\ebay\models\GetMyMessages;
use app\modules\systems\models\EbayAccount;
use app\modules\systems\models\EbayApiTask;
use yii\base\Exception;
use yii\console\Controller;
use app\modules\products\models\EbaySiteMapAccount;
use app\common\VHelper;
use yii\helpers\Url;
use app\modules\mails\models\EbayInbox;
use app\modules\services\modules\ebay\models\AccountTaskQueueOne;
class GetmymessagesController extends Controller
{
   /* public function actionIndex()
    {
        if(isset($_REQUEST['siteid']))
        {
            $siteid = $_REQUEST['siteid'];
            if(!empty($accounts))
            {
                set_time_limit(0);
                foreach ($accounts as $account)
                {
                    $maxReceiveDate = EbayInbox::find()->select('max(receive_date)')->where('siteid=:siteid and account_id=:account_id',['siteid'=>$siteid,':account_id'=>$account['ebay_account_id']])->asArray()->one()['max(receive_date)'];
                    if(empty($maxReceiveDate))
                        $startTime = '2016-06-01T00:00:00';//date('Y').'-01-01T00:00:00';
                    else
                        $startTime = date('Y-m-d\TH:i:s',strtotime($maxReceiveDate));
                    $endTime = date('Y-m-d\TH:i:s');
                    if(strcmp($endTime,$startTime) < 1)
                        continue;
                    $getMyMessagesModel = new GetMyMessages($account['ebay_account_id']);
                    $getMyMessagesModel->StartTime = $startTime.'.000Z';
                    $getMyMessagesModel->EndTime = $endTime.'.000Z';
                    $getMyMessagesModel->siteID = $siteid;
                    try{
                        $getMyMessagesModel->getMessages();
                    }catch(Exception $e){
                        echo $e->getMessage();
                    }
                }
            }
        }
        else
        {
            $siteids = EbaySiteMapAccount::find()->select('siteid')->distinct()->where('is_delete=0')->asArray()->all();
            if(!empty($siteids))
            {
                foreach($siteids as $siteid)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/getmymessages/index','siteid'=>$siteid['siteid'])));
                    sleep(2);
                }
            }
            else
            {
                exit('{{%ebay_site_map_account}}没有站点数据');
            }
        }
    }*/

    public function actionMail($id,$start_time='',$end_time='')
    {

        $account = $id;
        $accountName = Account::findById((int)$account)->account_name;
        $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
        if(empty($erpAccount))
            exit('无法获取账号信息。');

        ignore_user_abort(true);
        set_time_limit(7200);
        $apiTaskModel = new EbayApiTask();
        $apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_MESSAGE;
//            $apiTaskModel->siteid = $siteId;
        $apiTaskModel->account_id = $account;
        $apiTaskModel->exec_status = 1;
        $apiTaskModel->start_time = date('Y-m-d H:i:s');
        $apiTaskModel->save();

        $maxStartTime = EbayApiTask::find()->select('max(data_end_time)')->where(['account_id'=>$account,'task_name'=>AccountTaskQueue::TASK_TYPE_MESSAGE,'exec_status'=>2,'status'=>[2,3]])->asArray()->one()['max(data_end_time)'];
        if(empty($maxStartTime))
            $startTime = date('Y-m-d\TH:i:s.000\Z',strtotime(' -60 days') - 32400);
        else
            $startTime = date('Y-m-d\TH:i:s.000\Z',strtotime($maxStartTime) - 28860);
        $nowTime = date('Y-m-d\TH:i:s.000\Z',time()-28800);
        $endTime = date('Y-m-d\TH:i:s.000\Z',strtotime($startTime)-25200);
        if(strcmp($endTime ,$nowTime) > 0)
            $endTime = $nowTime;
        if(!empty($start_time) && strtotime($start_time))
            $startTime = date('Y-m-d\TH:i:s.000\Z',strtotime($start_time)-28800);
        if(!empty($end_time) && strtotime($end_time))
            $endTime = date('Y-m-d\TH:i:s.000\Z',strtotime($end_time)-28800);

        if(strcmp($endTime,$startTime) > 0)
        {
            $apiTaskModel->data_start_time = date('Y-m-d H:i:s',strtotime($startTime));
            $apiTaskModel->data_end_time = date('Y-m-d H:i:s',strtotime($endTime));
            $apiTaskModel->save();
            $path = \Yii::getAlias('@runtime').'/message_'.date('YmdHis').'_'.$id.'.html';
            $getMyMessagesModel = new GetMyMessages($erpAccount);
            $getMyMessagesModel->StartTime = $startTime;
            $getMyMessagesModel->EndTime = $endTime;
//                    $getMyMessagesModel->siteID = $siteId;
            $getMyMessagesModel->ebayApiTaskModel = &$apiTaskModel;
            try{
                $getMyMessagesModel->getMessages($path);
            }catch(Exception $e){
                echo $e->getMessage().PHP_EOL;
                exit;
            }
        }
        else
        {
            $apiTaskModel->exec_status = 2;
            $apiTaskModel->exec_status = 1;
            $apiTaskModel->end_time = date('Y-m-d H:i:s');
            $apiTaskModel->error .= "[错误码：0。StartTime:{$startTime} 不小于 EndTime:{$endTime}。]";
            $apiTaskModel->save();
        }

        exit('DONE');

    }


    public function actionIndextest()
    {
        if(isset($_REQUEST['account']))
        {
            $account = $_REQUEST['account'];
//            file_put_contents('F:\testContent\text.txt',$account.PHP_EOL,FILE_APPEND);
            $siteids = EbaySiteMapAccount::find()->select('siteid')->distinct()->where('ebay_account_id=:ebay_account_id',[':ebay_account_id'=>$account])->asArray()->all();
            if(!empty($siteids))
            {
                set_time_limit(0);
                $accountModel = EbayAccount::findOne((int)$account);
                $apiTaskModel = new EbayApiTask();
                $apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_MESSAGE;
//            $apiTaskModel->siteid = $siteId;
                $apiTaskModel->account_id = $account;
                $apiTaskModel->exec_status = 1;
                $apiTaskModel->start_time = date('Y-m-d H:i:s');
                foreach ($siteids as $siteid)
                {
                    /*$maxReceiveDate = EbayInbox::find()->select('max(receive_date)')->where('siteid=:siteid and account_id=:account_id',['siteid'=>$siteid['siteid'],':account_id'=>$account])->asArray()->one()['max(receive_date)'];
                    if(empty($maxReceiveDate))*/
                        $startTime = '2016-06-01T00:00:00';//date('Y').'-01-01T00:00:00';
                    /*else
                        $startTime = date('Y-m-d\TH:i:s',strtotime($maxReceiveDate));*/
                    $endTime = date('Y-m-d\TH:i:s');
                    if(strcmp($endTime,$startTime) < 1)
                        continue;
                    $getMyMessagesModel = new GetMyMessages($accountModel);
                    $getMyMessagesModel->StartTime = $startTime.'.000Z';
                    $getMyMessagesModel->EndTime = $endTime.'.000Z';
                    $getMyMessagesModel->siteID = $siteid['siteid'];
                    $getMyMessagesModel->ebayApiTaskModel = &$apiTaskModel;
                    try{
                        $getMyMessagesModel->getMessagescc();
                    }catch(Exception $e){
                        echo $e->getMessage();
                        continue;
                    }
                }
            }
        }
        else
        {
            $accounts = EbaySiteMapAccount::find()->select('ebay_account_id')->distinct()->where('is_delete=0')->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/getmymessages/indextest','account'=>$accountV['ebay_account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('{{%ebay_site_map_account}}没有账号数据');
            }
        }
    }

    public function actionTest()
    {
        set_time_limit(0);
        $accountModel = new \stdClass();
        $accountModel->user_token = 'AgAAAA**AQAAAA**aAAAAA**iw8TWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkYuhCZaGqQ2dj6x9nY+seQ**sHQDAA**AAMAAA**f3cNXA8w7lUMPulAHw6J7fzqaLCR/z6AJlWBuA0tUTFWmCW0Fm2tvHcLyrOPFWEMBkwKWo2cFjvSth+byX6IbMO8Cm1rcxRCK1ENre0HoBNBd13Jn5BLTj7MBFjQ2eGsS/woOvinYFwKJjZdziJplMJOABLnK8wrdeY1XV/lq4P2RQbkTnV1cSxtCee0VmEJDbwv8+IJjHhll/4Mco/WZK89ocsaPxP20X5/rYtELmwcUPuODXpfeY3iotkgZs5NFNKAzMaufu4f991OqNX/DJWU0XvFMo9TY0ekLiK0Rfc4FfztRfk2YZwrT8L7WP+3n0JeRtUPUmglpTlQ82TqIP/F1d6Bi8hab+eLxoGLsRaxvKsVC1GWWOLcQKJrb8knfb0BGw0yXH+2TNyiQ1DZiKVqxApRWYKCnFASygy0tc1vKnBNOm6VrJ+DliY69ZLLqFi+dfHizbNCuvOspVhDxMgusWEydgYcXBXxciT/z6bGvvuB4BzR1ilFnlTKJs2PU0Y+rKbq+5VWloodumVQ1KTGUPlwDV3MGeaC+8eBdOt+WlIAzb2vrOC07vcQzbEFXw1xM1CDOTz8aZ1DLILHgSOBsRLkQ63Y/2E2gMK37yQwohlBy8ZBXSsw9w+aAtAaIl/ksmbyOPi2t/NL1DTp/B95zja2seVbcmX0gmglFjlN4KZaxXKN059fnNc5czCXtYPN/vHKnNpZbYsC+kYivMwJ2gIwc9jLVu3Q9yxjgTi+cMcxspzuCGQ7oPQyelXm';
        $messages = new GetMyMessages($accountModel);
//        $messages->MessageID = [91223270611];
        $messages->FolderID = 0;//'Y-m-d\TH:i:s.000\Z'
        $messages->StartTime = '2017-03-27T00:00:00.000Z';
        $messages->EndTime = '2017-09-25T00:00:00.000Z';
        $messages->getMessagesHeaders();
//        file_put_contents('F:\testContent\message.txt',$messages->response);
        echo time(),'<hr/>';
        foreach($messages->response->Messages->Message as $k=>$v)
        {
            echo $v->MessageID,'-----',$v->ReceiveDate,'<br/>';
        }
        findClass(simplexml_load_string($messages->response->Messages),1);
    }

    public function actionCc()
    {
        if(isset($_REQUEST['id']))
        {
            $account = trim($_REQUEST['id']);
            $accountName = Account::findById((int)$account)->account_name;
            $file_name = '../runtime/logs/ebMeassage'.$account.'_'.date('His').'.html';
//            file_put_contents($file_name,'<hr />account_id:'.$account.'<br>',FILE_APPEND);
            $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
            if(empty($erpAccount))
                exit('无法获取账号信息。');

//            if(EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_MESSAGE,$account))
//            {
//                echo "account:{$account};Task Running.".PHP_EOL;
//                exit;
//            }
            ignore_user_abort(true);
            set_time_limit(7200);

            $startTime = $_REQUEST['start_time'];
            $endTime = $_REQUEST['end_time'];

//            file_put_contents($file_name,'startTime:'.$startTime.'<br />',FILE_APPEND);
//            file_put_contents($file_name,'endTime:'.$endTime.'<br />',FILE_APPEND);
//            file_put_contents($file_name,'status:',FILE_APPEND);


            if(strcmp($endTime,$startTime) > 0)
            {
                $getMyMessagesModel = new GetMyMessages($erpAccount);
                $getMyMessagesModel->StartTime = $startTime;
                $getMyMessagesModel->EndTime = $endTime;
                try{
                    $getMyMessagesModel->getMessagescc($account);
                }catch(Exception $e){
                    echo $e->getMessage().PHP_EOL;
                    exit;
                }
            }
//            file_put_contents($file_name,'success<br />',FILE_APPEND);

            $accountTask = AccountTaskQueueOne::getNextAccountTask();
            if (!empty($accountTask))
            {
                //在队列里面删除该记录
                $accountId = $accountTask->account_id;
                $start_time = $accountTask->start_time;
                $end_time = $accountTask->end_time;
                $accountTask->delete();
                VHelper::throwTheader('/services/ebay/getmymessages/cc', ['id'=> $accountId,'start_time' => $start_time, 'end_time' => $end_time]);
            }

            exit('DONE');
        }
        else{
            $accountTask = AccountTaskQueueOne::getNextAccountTask();
            if (!empty($accountTask))
            {
                //在队列里面删除该记录
                $accountId = $accountTask->account_id;
                $start_time = $accountTask->start_time;
                $end_time = $accountTask->end_time;
                $accountTask->delete();
                VHelper::throwTheader('/services/ebay/getmymessages/cc', ['id'=> $accountId,'start_time' => $start_time, 'end_time' => $end_time]);
            }

            exit('DONE');
        }

    }

    // 补全数据加入队列消息
    public function actionCeshi()
    {
        set_time_limit(7200);

        $startTime = date('Y-m-d\TH:i:s.000\Z',strtotime('2017-12-08 14:00:00') - 28800);
        $final_time = date('Y-m-d\TH:i:s.000\Z',strtotime('2017-12-09 09:45:00') - 28800);

        $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB,Account::STATUS_VALID);
        foreach($accountList as $account)
        {
            for($i=0;$i<11;$i++)
            {
                $start_time =  date('Y-m-d\TH:i:s.000\Z',strtotime($startTime) + 7200 * $i);
                $end_time = date('Y-m-d\TH:i:s.000\Z',strtotime($startTime) + 7200 * ($i + 1));

                if(AccountTaskQueueOne::find()->where(['account_id'=>$account->id,'start_time'=>AccountTaskQueue::TASK_TYPE_MESSAGE,'platform_code'=>$account->platform_code])->exists())
                    continue;
                $AccountTaskQueueOne = new AccountTaskQueueOne();
                $AccountTaskQueueOne->account_id = $account->id;
                $AccountTaskQueueOne->start_time = $start_time;
                $AccountTaskQueueOne->end_time = $end_time;
                $AccountTaskQueueOne->create_time = time();
                $AccountTaskQueueOne->save(false);
                sleep(1);
            }
        }

        exit('DONE');
    }

}