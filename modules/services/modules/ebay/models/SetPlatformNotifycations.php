<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/26 0026
 * Time: 下午 4:14
 */

namespace app\modules\services\modules\ebay\models;

use app\common\VHelper;
use app\modules\services\modules\ebay\components\EbayApiAbstract;
use app\modules\systems\models\EbayAccount;
use PhpImap\Exception;

class SetPlatformNotifycations extends EbayApiAbstract
{
    public $PreferenceLevel = 'Application';
    public $notificationEnable;
    const APPLICATION_URL = 'http://47.90.106.87/ebayNotice.php';

    public $accountId;
    public $ebayApiTaskModel;
    protected $errors;
    protected $sendXml;

    public function getData()
    {
        $this->setRequest()->sendHttpRequest();
        return $this->requestStatus && !empty($this->response);
    }

    public function handleResponse()
    {
        if($this->getData())
        {
            $simXml = simplexml_load_string($this->response);
            switch($simXml->Ack)
            {
                case 'Failure':
                    var_dump('Failure');
                    break;
                case 'Warning':
                    var_dump('Warning');
                case 'Success':
                    date_default_timezone_set('Asia/Shanghai');
                    var_dump('Success');
//                    var_dump($simXml);exit;
            }
        }
    }

    public function setRequest()
    {
        $ebayKeys = \app\components\ConfigFactory::getConfig('ebayKeys');
        $this->appID = $ebayKeys['appID'];
        $this->devID = $ebayKeys['devID'];
        $this->certID = $ebayKeys['certID'];
        $this->serverUrl = $ebayKeys['serverUrl'];
        $this->compatabilityLevel = 991;
        $this->verb = 'SetNotificationPreferences';
        return $this;
    }

    public function requestXmlBody()
    {
        if(empty($this->notificationEnable))
            return false;
        $this->sendXml = '';

        $this->sendXml = '<?xml version="1.0" encoding="utf-8"?>';
        $this->sendXml .= '<SetNotificationPreferencesRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";

        $this->sendXml .= "<ApplicationDeliveryPreferences>";
        $this->sendXml .= "<ApplicationEnable>Enable</ApplicationEnable>";
        $this->sendXml .= "<ApplicationURL>".self::APPLICATION_URL."</ApplicationURL>";
        $this->sendXml .= "<DeviceType>Platform</DeviceType>";
        $this->sendXml .= "<DeliveryURLDetails>";
        $this->sendXml .= "<DeliveryURL>http://kefu.yibainetwork.com/services/ebay/platformnotifications/getbynotified</DeliveryURL>";
        $this->sendXml .= "<DeliveryURLName>customer_service</DeliveryURLName>";
        $this->sendXml .= "<Status>Enable</Status>";
        $this->sendXml .= "</DeliveryURLDetails>";
        $this->sendXml .= "</ApplicationDeliveryPreferences>";



        $this->sendXml .= "<UserDeliveryPreferenceArray>";
        foreach($this->notificationEnable as $key => $value)
        {
            $this->sendXml .= "<NotificationEnable><EventType>".$value['EventType']."</EventType><EventEnable>".$value['EventEnable']."</EventEnable></NotificationEnable>";
        }
        $this->sendXml .= "</UserDeliveryPreferenceArray>";
        $this->sendXml .= '<UserData><ExternalUserData>'.$this->accountId.'</ExternalUserData></UserData>';

        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '<Version>991</Version>';
//        $this->sendXml .= '<DeliveryURLName>customer_service</DeliveryURLName>';

        $this->sendXml .= '</SetNotificationPreferencesRequest>';
        return true;
    }

    public function validate()
    {
        $this->errors = [];
        $flag = true;

        return $flag;
    }

}