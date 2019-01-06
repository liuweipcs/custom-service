<?php

namespace app\modules\services\modules\ebay\controllers;

use app\modules\services\modules\ebay\models\GetPlatformNotifycations;
use app\modules\services\modules\ebay\models\SetPlatformNotifycations;
use yii\web\Controller;
use app\modules\services\modules\ebay\models\GetFeedback;
use PhpImap\Exception;
use app\modules\systems\models\EbayAccount;
use app\modules\products\models\EbaySiteMapAccount;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\accounts\models\Account;
use app\modules\systems\models\EbayApiTask;
use app\modules\accounts\models\Platform;
use app\common\VHelper;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/11 0011
 * Time: 下午 6:29
 */
class PlatformnotificationsController extends Controller
{
    public function actionGetnotificationpreferences()
    {
        set_time_limit(7200);
        $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB,Account::STATUS_VALID);
        foreach ($accountList as $account)
        {
            $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $account->account_name);
            $api = new PlatformNotifycations();


            $api->setUserToken($erpAccount->user_token);
            $api->xmlTagArray = [
                'GetNotificationPreferencesRequest'=>[
                    'PreferenceLevel'=>'Application'
                ]
            ];
            $response = $api->send()->response;
            $ebayNotificationPreferences = UebModel::model('EbayNotificationPreferences')->find('account_id='.$accountModel->id);
            if(empty($ebayNotificationPreferences))
                $ebayNotificationPreferences = new EbayNotificationPreferences();
            if(isset($response->Ack) && in_array($response->Ack,array('Success','Warning')))
            {
                $ebayNotificationPreferences->account_id = $accountModel->id;
                $applicationDeliveryPreferences = $response->ApplicationDeliveryPreferences;
                $ebayNotificationPreferences->application_url = isset($applicationDeliveryPreferences->ApplicationURL) ? $applicationDeliveryPreferences->ApplicationURL:'';
                $ebayNotificationPreferences->application_enable = $applicationDeliveryPreferences->ApplicationEnable;
                $ebayNotificationPreferences->alert_email = isset($applicationDeliveryPreferences->AlertEmail) ? $applicationDeliveryPreferences->AlertEmail:'';
                $ebayNotificationPreferences->alert_enable = $applicationDeliveryPreferences->AlertEnable;
                $ebayNotificationPreferences->device_type = $applicationDeliveryPreferences->DeviceType;
                $ebayNotificationPreferences->payload_encoding_type = $applicationDeliveryPreferences->PayloadEncodingType;
                $ebayNotificationPreferences->payload_version = $applicationDeliveryPreferences->PayloadVersion;
                $ebayNotificationPreferences->update_time = date('Y-m-d H:i:s');
                $ebayNotificationPreferences->save();
            }
            $api->xmlTagArray = [
                'GetNotificationPreferencesRequest'=>[
                    'PreferenceLevel'=>'UserData'
                ]
            ];
            $response = $api->send()->response;
            if(isset($response->Ack) && in_array($response->Ack,array('Success','Warning')))
            {
                $ebayNotificationPreferences->external_user_data = isset($response->UserData->ExternalUserData) ? $response->UserData->ExternalUserData : '';
                $ebayNotificationPreferences->save();
            }
            $api->xmlTagArray = [
                'GetNotificationPreferencesRequest'=>[
                    'PreferenceLevel'=>'User'
                ]
            ];
            $response = $api->send()->response;
            if(isset($response->Ack) && in_array($response->Ack,array('Success','Warning')))
            {
                $notificationEnables = $response->UserDeliveryPreferenceArray->NotificationEnable;
                UebModel::model('EbayNotificationPreferencesEvent')->deleteAll('notification_preferences_id='.$ebayNotificationPreferences->id);
                if(!empty($notificationEnables))
                {
                    foreach ($notificationEnables as $notificationEnable)
                    {
                        $ebayNotificationPreferencesEvent = UebModel::model('EbayNotificationPreferencesEvent')->find('notification_preferences_id='.$ebayNotificationPreferences->id.' and event_type=:event_type',array(':event_type'=>$notificationEnable->EventType));
                        if(empty($ebayNotificationPreferencesEvent))
                            $ebayNotificationPreferencesEvent = new EbayNotificationPreferencesEvent();
                        $ebayNotificationPreferencesEvent->notification_preferences_id = $ebayNotificationPreferences->id;
                        $ebayNotificationPreferencesEvent->event_type = $notificationEnable->EventType;
                        $ebayNotificationPreferencesEvent->event_enable = $notificationEnable->EventEnable;
                        $ebayNotificationPreferencesEvent->save();
                    }
                }
            }
            echo $accountModel->user_name,'<br/>';
            ob_flush();
            flush();
        }
        exit('DONE');
    }

    public function actionSetnotification()
    {
        set_time_limit(5400);
//        $account_name = '3c_topshop';
//        $account_name = 'echoii_mall';

        $notificationEnable = [
            [
                'EventType' => 'OrderInquiryEscalatedToCase',
                'EventEnable' => 'Disable',
            ],
            [
                'EventType'=>'OrderInquiryOpened',
                'EventEnable'=>'Disable',
            ],
            [
                'EventType'=>'ReturnEscalated',
                'EventEnable'=>'Disable',
            ],
            [
                'EventType'=>'ReturnCreated',
                'EventEnable'=>'Disable',
            ],
            [
                'EventType'=>'Feedback',
                'EventEnable'=>'Disable',
            ],
            [
                'EventType'=>'EBPEscalatedCase',
                'EventEnable'=>'Disable',
            ],
            [
                'EventType'=>'OrderInquiryClosed',
                'EventEnable'=>'Disable',
            ],
            [
                'EventType'=>'OrderInquiryProvideShipmentInformation',
                'EventEnable'=>'Disable',
            ],
            [
                'EventType'=>'OrderInquiryReminderForEscalation',
                'EventEnable'=>'Disable',
            ],
        ];

        if(isset($_REQUEST['account_name']))
        {
            $account_name = $_REQUEST['account_name'];

            $accountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $account_name);

            $api = new SetPlatformNotifycations();
            $api->notificationEnable = $notificationEnable;
            $api->accountId = $accountModel->id;
            $api->setUserToken($accountModel->user_token);

            $response = $api->handleResponse();
        }
        else
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB,Account::STATUS_VALID);

            foreach($accountList as $account)
            {
                $accountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $account->account_name);

                $api = new SetPlatformNotifycations();
                $api->notificationEnable = $notificationEnable;
                $api->accountId = $accountModel->id;
                $api->setUserToken($accountModel->user_token);

                $response = $api->handleResponse();
            }
        }

        exit('DONE');
    }

    public function actionGetbynotified()
    {

        $content = file_get_contents("php://input");
//        $log = '/mnt/sdb1/www/crm/runtime/logs/notify_datas.log';
//        file_put_contents($log,$content.PHP_EOL,FILE_APPEND);exit;
//        $content = file_get_contents('D:\phpStudy\WWW\kf\trunk\runtime\cache\feedback\FeedbackReceived\15330352205b6042d41d6e8760498947.xml');
        
        $body = simplexml_load_string($content)->children('soapenv',true)->Body;
        if(!empty($body->children('urn:ebay:apis:eBLBaseComponents')))
        {
            $contentXml = $body->children('urn:ebay:apis:eBLBaseComponents');
            $response_type = $contentXml->getName();
        }
        else
        {
            $contentXml = $body->children();
            $response_type = 'NotificationEvent';
        }
        $event_type = $contentXml->$response_type->NotificationEventName->__toString();

        $urls = include \Yii::getAlias('@app') . '/config/notifications.php';
        if(isset($urls[$event_type]))
            $url = \Yii::$app->request->getHostInfo().$urls[$event_type];
        else
            header('HTTP/1.0 400 Bad Request');
        
        
        if(VHelper::throwTheader($url,$content,'post'))
            header('HTTP/1.0 200 OK');
        else
            header('HTTP/1.0 400 Bad Request');

    }

   public function actionTest()
   {
       $userToken = 'AgAAAA**AQAAAA**aAAAAA**egQTWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AEkYKlDJaDow2dj6x9nY+seQ**sHQDAA**AAMAAA**+hM07RFVx7IPh1+lqDmXLyUMJ4w+IXwRUskNjijYFVlVhAvEZnTPIiaWUih/sfzABeQIHV77AI2MKeVsXDnxh7hgdWsSUuysRt/+1twaxQrhOPgykoDLeA/EKLtPXnWmjyym182XvfwcvcdGfrDGjMP7vj4NbN61aonFt5RakiFlmDwpHVS6VcdVCImQ2JTHau+VXgiUBat2cSR8J2kE3dtzQDLJ1YfCQvtx9w1hKcNZX/JnjlpRFEaTF8gfmgWPg6llIH6YZyeEdLwfICWm5edYMGwn4BY+5UhkbSUFgD+YeNMcVDSWfHBR1kk65Zl85kgJN8I02CMnkeE4n2U+gZb1orBttPVhbN7gLas9qO6vNiH8PiYhVmBrIziipVWNPXRQYDcS76oEz+AeR24Z80iL7XrzcLaiZfmQzYg5jUOXkTAiXknxqjAfgiPxzATfRh/18NeAvklocK935BzduJceFwV7KWXsTcnUMNgYmVJlkwdO1eWgNptdkJGDk3g7AL3g8gMZ7KHRNA5ioqb1SJHvVfdHIKH/IPeEvC+JOmY3IiMXeluwubl3ZKvP3xKpr+7/iFZgGklasN3w3xdWhJEKrOKMR0xTXeRBt3SCELWI0QgoqIb7bnsC9yTQp9f6nDywZbuDvFf1enTkYwTOoFAkVmWjmdDUflY7rtpSwTy1O76Zl38iOvRqEt2cFIaE1+NvgKGsGmrqtIz0XDHS18xTbDkOgwIXSg31boZaXJIEIYjYvv5K4dhJrBrQlsjC';
       $api = new GetPlatformNotifycations();
       $api->setUserToken($userToken);

       $response = $api->handleResponse();
       findClass($response,1);
   }
}