<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/5 0005
 * Time: 下午 6:51
 */

namespace app\modules\services\modules\ebay\models;

use app\modules\mails\models\EbayDisputesResponse;
use app\modules\services\modules\ebay\components\EbayApiAbstract;
use app\modules\systems\models\EbayAccount;
use PhpImap\Exception;

class AddDisputeResponse extends EbayApiAbstract
{
    public $DisputeActivity;
    public $DisputeID;
    public $MessageText;
    public $ShipmentTrackNumber;
    public $ShippingCarrierUsed;
    public $ShippingTime;

    protected $ebayDisputesResponseModel;
    protected $ebayAccountModel;
    protected $sendXml;
    protected $errors;

    public function __construct($idOrModel)
    {
        if(is_numeric($idOrModel))
            $idOrModel = EbayDisputesResponse::findOne($idOrModel);
        if($idOrModel instanceof EbayDisputesResponse)
        {
            $this->ebayDisputesResponseModel = $idOrModel;
            $this->ebayAccountModel = EbayAccount::findOne($idOrModel->account_id);
            if(empty($this->ebayAccountModel))
                throw new Exception('未找到Ebay账号数据');
            $this->siteID = $idOrModel->siteid;
        }
        else
        {
            throw new Exception('未找到要回复上传Ebay的数据。');
        }
    }

    public function getData()
    {
        $this->setRequest()->sendHttpRequest();
        return $this->requestStatus && !empty($this->response);
    }

    public function handleResponse()
    {
        if($this->getData())
        {
//            findClass($this->response,1);
            $simXml = simplexml_load_string($this->response);
            switch($simXml->Ack->__toString())
            {
                case 'Failure':
                    break;
                case 'Warning':
                case 'Success':
                    $this->ebayDisputesResponseModel->status = 1;
                    $this->ebayDisputesResponseModel->save();
            }
        }
    }

    public function setRequest()
    {
        $ebayKeys = \app\components\ConfigFactory::getConfig('ebayKeys');
        $this->_userToken = $this->ebayAccountModel->user_token;
        $this->appID = $ebayKeys['appID'];
        $this->devID = $ebayKeys['devID'];
        $this->certID = $ebayKeys['certID'];
        $this->serverUrl = $ebayKeys['serverUrl'];
        $this->compatabilityLevel = 983;
        $this->verb = 'AddDisputeResponse';
        return $this;
    }

    public function requestXmlBody()
    {
        $this->sendXml = '';
        if(!$this->validate())
        {
            return false;
        }
        $this->sendXml = '<?xml version="1.0" encoding="utf-8" ?>';
        $this->sendXml .= '<AddDisputeResponseRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";
        $this->sendXml .= "<DisputeActivity>{$this->DisputeActivity}</DisputeActivity>";
        $this->sendXml .= "<DisputeID>{$this->DisputeID}</DisputeID>";
        if(!empty($this->MessageText))
            $this->sendXml .= "<MessageText>{$this->MessageText}</MessageText>";
        if(!empty($this->ShipmentTrackNumber))
            $this->sendXml .= "<ShipmentTrackNumber>{$this->ShipmentTrackNumber}</ShipmentTrackNumber>";
        if(!empty($this->MessageText))
            $this->sendXml .= "<ShippingCarrierUsed>{$this->ShippingCarrierUsed}</ShippingCarrierUsed>";
        if(!empty($this->MessageText))
            $this->sendXml .= "<ShippingTime>{$this->ShippingTime}</ShippingTime>";
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</AddDisputeResponseRequest>';
       return true;
    }

    public function validate()
    {
        $flag = true;
        $this->errors = [];
        if(empty($this->DisputeActivity))
        {
            $flag = false;
            $this->errors[] = ['DisputeActivity'=>'不能为空'];
        }
        else
        {
            if(is_numeric($this->DisputeActivity))
            {
                if(isset(EbayDisputesResponse::$disputeActivityMap[$this->DisputeActivity]))
                    $this->DisputeActivity = EbayDisputesResponse::$disputeActivityMap[$this->DisputeActivity];
                else
                {
                    $flag = false;
                    $this->errors[] = ['DisputeActivity'=>'值错误'];
                }
            }
            else
            {
                if(!in_array($this->DisputeActivity,EbayDisputesResponse::$disputeActivityMap))
                {
                    $flag = false;
                    $this->errors[] = ['DisputeActivity'=>'值错误'];
                }
            }
        }
        if(empty($this->DisputeID))
        {
            $flag = false;
            $this->errors[] = ['DisputeID'=>'不能为空'];
        }
        if(in_array($this->DisputeActivity,['SellerAddInformation','SellerComment','SellerPaymentNotReceived']) && empty($this->MessageText))
        {
            $flag = false;
            $this->errors[] = ['MessageText'=>'不能为空'];
        }
        if($this->DisputeActivity == 'SellerShippedItem')
        {
            if(empty($this->ShipmentTrackNumber))
            {
                $flag = false;
                $this->errors[] = ['ShipmentTrackNumber'=>'不能为空'];
            }
            if(empty($this->ShippingCarrierUsed))
            {
                $flag = false;
                $this->errors[] = ['ShippingCarrierUsed'=>'不能为空'];
            }
            if(empty($this->ShippingTime))
            {
                $flag = false;
                $this->errors[] = ['ShippingTime'=>'不能为空'];
            }
            else
            {
                if(!self::isTimeDate($this->ShippingTime))
                {
                    $flag = false;
                    $this->errors[] = ['ShippingTime'=>'格式错误'];
                }
            }
        }
        return $flag;
    }
}