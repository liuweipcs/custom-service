<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/21 0021
 * Time: 下午 4:31
 */

namespace app\modules\services\modules\ebay\controllers;

use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbayEpsPictures;
use app\modules\mails\models\EbayReply;
use app\modules\services\modules\ebay\models\UploadSiteHostedPictures;
use app\modules\systems\models\EbayAccount;
use app\modules\systems\models\EbayApiTask;
use PhpImap\Exception;
use yii\web\Controller;
use app\modules\services\modules\ebay\models\AddMemberMessageAAQToPartner;
use app\modules\services\modules\ebay\models\AddMemberMessageRTQ;
use yii\helpers\Url;
class ReplymessageController extends Controller
{
    public function actionIndex()
    {
        if($account = $_REQUEST['account'])
        {
            ignore_user_abort(true);
            set_time_limit(600);
            $this->actionReplyinbox($account);
//            $this->actionReplysend($account);
        }
        else
        {
            $accounts = EbayReply::find()->select('account_id')->distinct()->where(['is_draft'=>0,'is_delete'=>0,'is_send'=>0])->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/replymessage/index','account'=>$accountV['account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('没有要发送的数据');
            }
        }
    }
    //回复收件箱
    public function actionReplyinbox($account)
    {
        $replys = EbayReply::findAll(['account_id'=>$account,'is_draft'=>0,'is_delete'=>0,'is_send'=>0,['>','inbox_id',0]]);
        $ebayApiTaskModel = new EbayApiTask();
        $ebayApiTaskModel->task_name = 'AddMemberMessageRTQ';
        $ebayApiTaskModel->account_id = $account;
        $ebayApiTaskModel->exec_status = 1;
        $ebayApiTaskModel->start_time = $ebayApiTaskModel->opration_date = date('Y-m-d H:i:s');
        $ebayApiTaskModel->opration_id = 1;//\Yii::$app->user->id;
        if(empty($replys))
        {
            $ebayApiTaskModel->exec_status = 2;
            $ebayApiTaskModel->end_time = $ebayApiTaskModel->opration_date = date('Y-m-d H:i:s');
            $ebayApiTaskModel->error = '未找到此账号的回复消息。';
            $ebayApiTaskModel->status = 1;
            $ebayApiTaskModel->save();
        }
        else
        {
            foreach($replys as $reply)
            {
                try{
                    $model = new AddMemberMessageRTQ($reply);
                    $model->ebayApiTaskModel = &$ebayApiTaskModel;
                    $model->addMessage();
                    $model->handleResponse();
                }catch(Exception $e){
                    $ebayApiTaskModel->error .= '[reply_id:'.$reply->id.'错误：'.$e->getMessage().']';
                    $ebayApiTaskModel->status = 1;
                    $ebayApiTaskModel->save();
                    continue;
                }
            }
            $ebayApiTaskModel->exec_status = 2;
            $ebayApiTaskModel->end_time = $ebayApiTaskModel->opration_date = date('Y-m-d H:i:s');
            $ebayApiTaskModel->status = $ebayApiTaskModel->status == 0 ? 3:$ebayApiTaskModel->status;
            $ebayApiTaskModel->save();
        }
    }
    //主动发送邮件
    public function actionReplysend($account)
    {
        $replys = EbayReply::findAll(['account_id'=>$account,'is_draft'=>0,'is_delete'=>0,'is_send'=>0,'inbox_id'=>0]);
        $ebayApiTaskModel = new EbayApiTask();
        $ebayApiTaskModel->task_name = 'AddMemberMessageAAQToPartner';
        $ebayApiTaskModel->account_id = $account;
        $ebayApiTaskModel->exec_status = 1;
        $ebayApiTaskModel->start_time = $ebayApiTaskModel->opration_date = date('Y-m-d H:i:s');
        $ebayApiTaskModel->opration_id = 1;//\Yii::$app->user->id;
        if(empty($replys))
        {
            $ebayApiTaskModel->exec_status = 2;
            $ebayApiTaskModel->end_time = $ebayApiTaskModel->opration_date = date('Y-m-d H:i:s');
            $ebayApiTaskModel->error = '未找到此账号的主动待发送消息。';
            $ebayApiTaskModel->status = 1;
            $ebayApiTaskModel->save();
        }
        else
        {
            $accountName = Account::findOne((int)$account)->account_name;
            $ebayAccountInfo = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB,$accountName);
            foreach($replys as $reply)
            {
                try{
                    $model = new AddMemberMessageAAQToPartner($reply);
                    $model->ebayApiTaskModel = &$ebayApiTaskModel;
                    $model->addMessage();
                    $model->handleResponse();
                }catch(Exception $e){
                    $ebayApiTaskModel->error .= '[reply_id:'.$reply->id.'错误：'.$e->getMessage().']';
                    $ebayApiTaskModel->status = 1;
                    $ebayApiTaskModel->save();
                    continue;
                }
            }
            $ebayApiTaskModel->exec_status = 2;
            $ebayApiTaskModel->end_time = $ebayApiTaskModel->opration_date = date('Y-m-d H:i:s');
            $ebayApiTaskModel->status = $ebayApiTaskModel->status == 0 ? 3:$ebayApiTaskModel->status;
            $ebayApiTaskModel->save();
        }
    }

    public function actionTest($id)
    {
        $accountModel = EbayAccount::findOne((int)$id);
        $uploadModel = new UploadSiteHostedPictures();
        $uploadModel->ebayAccountModel = $accountModel;
        $uploadModel->ExternalPictureURL = 'http://120.24.249.36/upload/image/assistant/FS00454-15/FS00454-15-1.JPG';
        $uploadModel->PictureName = 'test_eps_201706021022';
        $uploadModel->handleResponse();
        exit('DONE');
    }

    public function actionTestreply()
    {
        $sendMessage = new AddMemberMessageRTQ();
        $sendMessage->ItemID = '282558315754';
        $sendMessage->Body = 'really sorry , will you consider a replacement ? Thank you ! Best regards ,';
        $sendMessage->ParentMessageID = 1551882095016;
        $sendMessage->RecipientID = 'jenna3451';
        $sendMessage->ebayAccountModel = new \stdClass();
        $sendMessage->ebayAccountModel->user_token = 'AgAAAA**AQAAAA**aAAAAA**ev8TWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6ABkounDJKHpgudj6x9nY+seQ**sHQDAA**AAMAAA**Andn6bplwkMONTqkNEvSGb95eGws/eU47f88BfF3Q1hdSSa7iGMKZDhnqPXB/nc9ohTty5o02P4h0VnmUNocCl1HoXank/EgmyT8yGzQ+LXIyv6mISLnjZ+HEm42uOKNYKr9YCpHiigcQQBr5rC8WHeZQmWJIIPoOn20QDfr3/0iXh3IAiuMy68A/gw/UOJxk1SOFl/2+Wb4wXH44dRu22yAFb03+j5NabPQeOh1OquS8sNTqMzDQQMbL1jMm5UZemh5Gu3ftVHXrgbThx9sh9vYOVmyhGSkUcxgw0g1uoYZhgsTj3kTxNRiR1WZOT+YbEAZBZhv9Jc34+yUTUItOvn90FHSuYGKfwp9Vd0WhmOXq170gAofj6ztu0aeoCFGOSWc83AXR3LAxozBTenb5+IIGo7++2PnuQkLry15yDnJelgXJ+SqOa3DOisodxjXUDj0KGFDZS2XT2R8oIsimGrYjSdWHMX2Co7rkdIWSTj3lpXEPow7GIvvsho8Z74CKDnTA3jZqxwlb8oS2+iVojqN4N7tmUEwXMiGAYdh+rzmr2ADISZTpL2k2tp9DFRRKUVkSDJAvYo3KFwRp+vzz9WigEYikYcZ5AUbsE0wghiB6UT5eNjx5JviR9dOYH5yQCcTG2i9+93BtOziKpYumIlh+SXKPSAoM0RaJeAtYzSYZmLNnOUbux+B/kvFXjd6QqWKIL8Wntqij6UTP8x1u77Ghs5vBV4xoLdnsbt0CaxVa55UFyBeQya2VIwtPEXg';
        $sendMessage->addMessage();
    }
}