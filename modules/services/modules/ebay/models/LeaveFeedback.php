<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/28 0028
 * Time: 下午 2:01
 */

namespace app\modules\services\modules\ebay\models;

use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\EbayFeedbackResponse;
//use app\modules\systems\models\EbayAccount;
use app\modules\systems\models\EbayApiTask;
use app\modules\services\modules\ebay\components\EbayApiAbstract;
//use PhpImap\Exception;
use yii\base\Exception;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;


class LeaveFeedback extends EbayApiAbstract
{
    public $ItemID;
    public $OrderLineItemID;
    public $CommentText;  //必需
    public $CommentType = 'Positive';  //必需
    public $TargetUser;  //必需
    public $TransactionID;

    protected $ebayAccountModel;
    protected $sendXml;
    protected $errors;

    protected $FeedBackModel;

    public function __construct($FeedBackModel,$buyer_id)
    {

        if($FeedBackModel instanceof EbayFeedback)
        {
            $accountName = 'NoAccount';
            if(empty($this->ebayAccountModel))
                $accountName = Account::getAccountName($FeedBackModel->account_id,Platform::PLATFORM_CODE_EB);
            if($accountName == 'NoAccount')
                throw new Exception('未找到帐号数据');
            $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
            if(empty($this->ebayAccountModel))
                throw new Exception('未找到eBay账号数据');
            $this->ItemID = $FeedBackModel->item_id;
            $this->OrderLineItemID = $FeedBackModel->order_line_item_id;
            $this->CommentText = $FeedBackModel->comment_text;
            $this->ItemID = $FeedBackModel->item_id;
            $this->TargetUser = $buyer_id;
            $this->TransactionID = $FeedBackModel->transaction_id;
        }
        else
        {
            throw new Exception('未找到回复数据');
        }


    }

    public function setRequest()
    {
      //  $ebayKeys = \app\components\ConfigFactory::getConfig('ebayKeys');

        //$ebayKeys = [
            //$this->_userToken = $this->ebayAccountModel->user_token;
        /*$this->_userToken ='AgAAAA**AQAAAA**aAAAAA**IAsTWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AGkouoCZKEqQWdj6x9nY+seQ**sHQDAA**AAMAAA**3HMTv7L6N+rpEtp9sV3SlYhBymmwNxti4CtcjfqlFFI9QCUV7mby55O632M5v0WI442wQ4dy1XpBcee+NuaAXE9W6YmCEg6SmVo2AytbYnsE48Juh6Yu6Ii2EBUoe1HvX12SkJUw6vtyXVPumTQ/+rXeQ22OzNg+zl5gp0W5WxFyvKRNyY3OGrlutuSFoM8u+emjt2k8e549r2IxOaXCwrFIgy+0wStqKpVMA5FBopEtQXonSCqI79m6vzFGqtCzpORO0aYfeQOs4ogFHkdLxjOhiMfD+U2b5XhqlRt4fgVTZ0rd0RWGA7P4wAu6GLa3nYs16vnqrgtBsVBHCp+/afIuxveEoGPGRfaz4PtlTQsHT0aEaLLf30vsQpZjqRa5WITQUbO6AOMo51vcroZWaj5ufY3p5yR9qDs64JMYa4dak33Y8Xq9DvuwP0dnNR4iROnIa7khtn9rWpmTnNxW5FIHSwGzeApTLE0L/1qokmqIxW3z/M9zGt9LC34eBU9odGshz2X7O8IxW6NDx415+iR6pu0BU5wjwqOgjYpdgU4eEhOwrCrUPB1JsJbsdokZErEGn9DEtJYp3t9uZMC+HdAPJJHcbieRO7ZEl8e22Hd9n8nYURe6hXnK/PIBXdjWBgJQUIfRGSzaxbJ/54R7jWsoD+FwaYep1cnR7GAWjBHf31yHX+6HJq/d9NMGaWpCCZTdtkmReTFdF1RftawSWGz9Zrdop/Ldj5FmRkjRkouUL6EoCy8CJhxgUiNBBJfR';
            $this->appID ='vakindd80-38d6-46c2-9b38-14d6cfd4c64';
            $this->devID ='433b57c3-cc37-4d73-a28d-8cc33791bb4';
            $this->certID ='97ee6168-6492-4e95-844b-1e15afdf907e';
            $this->serverUrl ='https://api.ebay.com/ws/api.dll';
            $this->compatabilityLevel =849;
            $this->verb = 'RespondToFeedback';*/
        //];

       $ebayKeys = \app\components\ConfigFactory::getConfig('ebayKeys');
        $this->_userToken = $this->ebayAccountModel->user_token;
        $this->appID = $ebayKeys['appID'];
        $this->devID = $ebayKeys['devID'];
        $this->certID = $ebayKeys['certID'];
        $this->serverUrl = $ebayKeys['serverUrl'];
        $this->compatabilityLevel = 983;
        $this->verb = 'LeaveFeedback';/**/
        return $this;
    }

    public function sendResponse()
    {

        $this->setRequest()->sendHttpRequest();

        return $this->requestStatus && !empty($this->response);
    }

    public function handleResponse()
    {   
        if($this->sendResponse())
        {
            return $this->response;
        }
    }

    public function requestXmlBody()
    {
        $this->sendXml = '';
        if(!$this->validate())
        {
            return false;
        }
        $this->sendXml = '<?xml version="1.0" encoding="utf-8" ?>';
        $this->sendXml .= '<LeaveFeedbackRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";

        $this->sendXml .= "<CommentText>{$this->CommentText}</CommentText>";
        $this->sendXml .= "<CommentType>{$this->CommentType}</CommentType>";
        if(!empty($this->ItemID))
            $this->sendXml .= "<ItemID>{$this->ItemID}</ItemID>";
        if(!empty($this->OrderLineItemID))
            $this->sendXml .= "<OrderLineItemID>{$this->OrderLineItemID}</OrderLineItemID>";
        if(!empty($this->TargetUser))
            $this->sendXml .= "<TargetUser>{$this->TargetUser}</TargetUser>";
        if(!empty($this->TransactionID))
        $this->sendXml .= "<TransactionID>{$this->TransactionID}</TransactionID>";
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</LeaveFeedbackRequest>';

        return true;
    }

    public function validate()
    {

        $flag = true;
        $this->errors = [];
        if(!empty($this->ItemID))
        {
           
            if(strlen($this->ItemID) > 19)
            {
                 
                $flag = false;
                $this->errors[] = ['ItemID'=>'长度不能大于19'];
            }
        }
        if(!empty($this->OrderLineItemID))
        {

            if(strlen($this->OrderLineItemID) > 50)
            {
                
                $flag = false;
                $this->errors[] = ['OrderLineItemID'=>'长度不能大于50'];
            }
        }
        if(empty($this->CommentText))
        {
            $flag = false;
            $this->errors[] = ['CommentText'=>'不能为空'];
        }
        else
        {
            if(strlen($this->CommentText) > 80)
            {
                 
                $flag = false;
                $this->errors[] = ['CommentText'=>'最大长度80'];
            }
        }

        if(empty($this->TargetUser))
        {
           
            $flag = false;
            $this->errors[] = ['TargetUser'=>'不能为空'];
        }
        if(!empty($this->TransactionID))
        {
            if(strlen($this->TransactionID) > 19)
            {
                
                $flag = false;
                $this->errors[] = ['TransactionID'=>'长度不能大于19'];
            }
        }

        return $flag;
    }


}