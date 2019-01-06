<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/5 0005
 * Time: 下午 9:03
 */

namespace app\modules\services\modules\ebay\models;

use app\modules\mails\models\EbayAddDispute;
use app\modules\services\modules\ebay\components\EbayApiAbstract;
use app\modules\systems\models\EbayAccount;
use PhpImap\Exception;

class AddDispute extends EbayApiAbstract
{
    public $DisputeExplanation;
    public $DisputeReason;
    public $ItemID;
    public $OrderLineItemID;
    public $TransactionID;

    protected $ebayAddDisputeModel;
    protected $ebayAccountModel;
    protected $sendXml;
    protected $errors;

    public function __construct($idOrModel)
    {
        if(is_numeric($idOrModel))
        {
            $idOrModel = EbayAddDispute::findOne($idOrModel);
        }
        if($idOrModel instanceof EbayAddDispute)
        {
            $this->ebayAddDisputeModel = $idOrModel;
            $this->ebayAccountModel = EbayAccount::findOne($idOrModel->account_id);
            if(empty($this->ebayAccountModel))
                throw new Exception('未找到Ebay账号数据');
            $this->DisputeReason = $idOrModel->dispute_reason;
            $this->DisputeExplanation = $idOrModel->dispute_explanation;
            $this->ItemID = $idOrModel->item_id;
            $this->TransactionID = $idOrModel->transaction_id;
            $this->OrderLineItemID = $idOrModel->order_line_item_id;
            $this->siteID = $idOrModel->siteid;
        }
        else
        {
            throw new Exception('未找到EbayAddDispute数据');
        }
    }

    public function sendData()
    {
        $this->setRequest()->sendHttpRequest();
        return $this->requestStatus && !empty($this->response);
    }

    public function handleResponse()
    {
        if($this->sendData())
        {
            $simXml = simplexml_load_string($this->response);
            switch($simXml->Ack->__toString())
            {
                case 'Failure':
                    break;
                case 'Warning':
                case 'Success':
                    $this->ebayAddDisputeModel->status = 1;
                    $this->ebayAddDisputeModel->case_id = $simXml->DisputeID->__toString();
                    $this->ebayAddDisputeModel->save();
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
        $this->verb = 'AddDispute';
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
        $this->sendXml .= '<AddDisputeRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";
        $this->sendXml .= "<DisputeExplanation>{$this->DisputeExplanation}</DisputeExplanation>";
        $this->sendXml .= "<DisputeReason>{$this->DisputeReason}</DisputeReason>";
        if(!empty($this->ItemID))
            $this->sendXml .= "<ItemID>{$this->ItemID}</ItemID>";
        if(!empty($this->OrderLineItemID))
            $this->sendXml .= "<OrderLineItemID>{$this->OrderLineItemID}</OrderLineItemID>";
        if(!empty($this->TransactionID))
            $this->sendXml .= "<TransactionID>{$this->TransactionID}</TransactionID>";
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</AddDisputeRequest>';
        return true;
    }
    public function validate()
    {
        $flag = true;
        $this->errors = [];
        if(empty($this->DisputeExplanation))
        {
            $flag = false;
            $this->errors[] = ['DisputeExplanation'=>'不能为空'];
        }
        else
        {
            if(is_numeric($this->DisputeExplanation))
            {
                if(isset(EbayAddDispute::$disputeExplanationMap[$this->DisputeExplanation]))
                    $this->DisputeExplanation = EbayAddDispute::$disputeExplanationMap[$this->DisputeExplanation];
                else
                {
                    $flag = false;
                    $this->errors[] = ['DisputeExplanation'=>'值错误'];
                }
            }
            else
            {
                if(!in_array($this->DisputeExplanation,EbayAddDispute::$disputeExplanationMap))
                {
                    $flag = false;
                    $this->errors[] = ['DisputeExplanation'=>'值错误'];
                }
            }
        }
        if(empty($this->DisputeReason))
        {
            $flag = false;
            $this->errors[] = ['DisputeReason'=>'不能为空'];
        }
        else
        {
            if(is_numeric($this->DisputeReason))
            {
                if(isset(EbayAddDispute::$disputeReasonMap[$this->DisputeReason]))
                    $this->DisputeReason = EbayAddDispute::$disputeReasonMap[$this->DisputeReason];
                else
                {
                    $flag = false;
                    $this->errors[] = ['DisputeReason'=>'值错误'];
                }
            }
            else
            {
                if(!in_array($this->DisputeReason,EbayAddDispute::$disputeReasonMap))
                {
                    $flag = false;
                    $this->errors[] = ['DisputeReason'=>'值错误'];
                }
            }
        }
        if(empty($this->OrderLineItemID))
        {
            if(empty($this->ItemID))
            {
                $flag = false;
                $this->errors[] = ['ItemID'=>'当OrderLineItemID为空时，ItemID不能为空'];
            }
            if(empty($this->TransactionID))
            {
                $flag = false;
                $this->errors[] = ['TransactionID'=>'当OrderLineItemID为空时，TransactionID不能为空'];
            }
        }
        return $flag;
    }
}