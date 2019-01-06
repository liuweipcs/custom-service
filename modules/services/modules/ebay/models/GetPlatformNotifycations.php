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

class GetPlatformNotifycations extends EbayApiAbstract
{
    public $PreferenceLevel = 'Application';

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
                    if(isset($this->ebayApiTaskModel))
                    {
                        var_dump($this->errorCode.$simXml->asXML());
                    }
                    break;
                case 'Warning':
                case 'Success':
                    date_default_timezone_set('Asia/Shanghai');
                    var_dump($simXml);exit;
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
        $this->verb = 'GetNotificationPreferences';
        return $this;
    }

    public function requestXmlBody()
    {
        $this->sendXml = '';
//        if(!$this->validate())
//        {
//            return false;
//        }
        $this->sendXml = '<?xml version="1.0" encoding="utf-8"?>';
        $this->sendXml .= '<GetNotificationPreferencesRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";

        $this->sendXml .= '<PreferenceLevel>'.$this->PreferenceLevel.'</PreferenceLevel>';

        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</GetNotificationPreferencesRequest>';
        return true;
    }

    public function validate()
    {
        $this->errors = [];
        $flag = true;

        return $flag;
    }

}