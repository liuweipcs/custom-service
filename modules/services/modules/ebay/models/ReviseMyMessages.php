<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/8 0008
 * Time: 下午 8:52
 */

namespace app\modules\services\modules\ebay\models;


use app\modules\services\modules\ebay\components\EbayApiAbstract;

class ReviseMyMessages extends EbayApiAbstract
{
    public $Flagged;    //true|false
    public $FolderID;
    public $MessageIDs; //array
    public $Read;    //true|false

    protected $ebayAccountModel;
    protected $sendXml;
    protected $errors;

    public function __construct()
    {
        
    }

    public function sendData()
    {
        $this->setRequest()->sendHttpRequest();
        return $this->requestStatus && !empty($this->response);
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
        $this->verb = 'ReviseMyMessages';
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
        $this->sendXml .= '<ReviseMyMessagesRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";
        if(isset($this->Flagged))
            $this->sendXml .= "<Flagged>$this->Flagged</Flagged>";
        if(isset($this->FolderID))
            $this->sendXml .= "<FolderID>$this->FolderID</FolderID>";
        $this->sendXml .= '<MessageIDs>';
        foreach ($this->MessageIDs as $MessageID)
        {
            $this->sendXml .= "<MessageID>$MessageID</MessageID>";
        }
        $this->sendXml .= '</MessageIDs>';
        if(isset($this->Read))
            $this->sendXml .= "<Read>$this->Read</Read>";
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</ReviseMyMessagesRequest>';
        return true;
    }

    public function validate()
    {
        $flag = true;
        $this->errors = [];
        if(!isset($this->Flagged) && !isset($this->FolderID) && !isset($this->MessageIDs))
        {
            $this->errors[] = ['Flagged,FolderID,MessageIDs' => 'Flagged,FolderID,MessageIDs至少设置一个字段'];
            $flag = false;
        }

        if(isset($this->Flagged))
        {
            if(is_bool($this->Flagged))
            {
                $this->Flagged = $this->Flagged ? 'true' : 'false';
            }
            if(is_string($this->Flagged))
            {
                if(!in_array($this->Flagged,['true','false']))
                {
                    $this->errors[] = ['Flagged' => '值错误'];
                    $flag = false;
                }
            }
        }

        if(isset($this->FolderID))
        {
            if(!is_numeric($this->FolderID) || $this->FolderID%1 !== 0)
            {
                $this->errors[] = ['FolderID' => '必需是自然数'];
                $flag = false;
            }
        }

        if(isset($this->MessageIDs))
        {
            if(!is_array($this->MessageIDs))
            {
                $this->errors[] = ['FolderID' => '必需是数组'];
                $flag = false;
            }
        }
        else
        {
            $this->errors[] = ['FolderID' => '必填'];
            $flag = false;
        }

        if(isset($this->Read))
        {

            if(is_bool($this->Read))
            {
                $this->Read = $this->Read ? 'true' : 'false';
            }
            if(is_string($this->Read))
            {
                if(!in_array($this->Read,['true','false']))
                {
                    $this->errors[] = ['Read' => '值错误'];
                    $flag = false;
                }
            }
        }

        return $flag;
    }
}