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
use app\modules\mails\models\EbayFeedback;
use PhpImap\Exception;
use app\modules\accounts\models\Platform;
use app\modules\reports\models\FeedbackStatistics;
class GetFeedback extends EbayApiAbstract
{
    public $FeedbackID;  //此字段会是其他字段失效
    public $CommentType; //数组
    public $FeedbackType;
    public $ItemID;
    public $OrderLineItemID; //此字段会使FeedbackType and Pagination（分页）失效
    public $EntriesPerPage = 25;
    public $PageNumber = 1;
    public $TransactionID = null;   //此字段会使FeedbackType and Pagination（分页）失效
    public $UserID = null;
    public $DetailLevel = 'ReturnAll';

    public static $feedbackTypeMap = ['FeedbackLeft','FeedbackReceived','FeedbackReceivedAsBuyer','FeedbackReceivedAsSeller'];

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
                        $this->ebayApiTaskModel->error .= '[错误码：'.$this->errorCode.'。'.$simXml->asXML().']';
                        $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                        $this->ebayApiTaskModel->status = 1;
                        $this->ebayApiTaskModel->save();
                        $this->errorCode++;
                    }
                    break;
                case 'Warning':
                case 'Success':
                    date_default_timezone_set('Asia/Shanghai');
                    $feedbackDetails = $simXml->FeedbackDetailArray->FeedbackDetail;
                    if(empty($feedbackDetails))
                    {
                        findClass($simXml,1,0);
                        findClass(htmlspecialchars($this->sendXml),1,0);
                        findClass($feedbackDetails,1,0);
                        return;
                    }
                    foreach($feedbackDetails as $feedbackDetail)
                    {
                        $feedbackId = $feedbackDetail->FeedbackID->__toString();
                        echo $feedbackId,'<br/>';
                        ob_flush();
                        flush();

                        //将评价数据插入到评价统计表
                        $feedbackStatistics = FeedbackStatistics::findOne(['feedback_id'=>$feedbackId,'platform_code'=>Platform::PLATFORM_CODE_EB]);
                        if(empty($feedbackStatistics)){
                            $feedbackStatistics = new FeedbackStatistics();
                            $feedbackStatistics->status = 0;
                        }
                        $feedbackStatistics->platform_code = Platform::PLATFORM_CODE_EB;
                        $feedbackStatistics->account_id = $this->accountId;
                        if(isset($feedbackDetail->CommentType))
                            $feedbackStatistics->comment_type = array_search($feedbackDetail->CommentType->__toString(),EbayFeedback::$commentTypeMap);
                        $feedbackStatistics->create_time = date('Y-m-d H:i:s');
                        $feedbackStatistics->feedback_id = $feedbackId;
                        if(isset($feedbackDetail->CommentTime))
                            $feedbackStatistics->comment_time = date('Y-m-d H:i:s',strtotime($feedbackDetail->CommentTime->__toString()));
                        $feedbackStatistics->save(false);

                        $ebayFeedbackModel = EbayFeedback::findOne(['feedback_id'=>$feedbackId]);
                        $isUpdate = true;
                        if(empty($ebayFeedbackModel))
                        {
                            $ebayFeedbackModel = new EbayFeedback();
                            $isUpdate = false;
                        }
                        $ebayFeedbackModel->account_id = $this->accountId;
                        $ebayFeedbackModel->siteid = $this->siteID;
                        if(isset($feedbackDetail->CommentingUser))
                            $ebayFeedbackModel->commenting_user = $feedbackDetail->CommentingUser->__toString();
                        if(isset($feedbackDetail->CommentingUserScore))
                            $ebayFeedbackModel->commenting_user_score = $feedbackDetail->CommentingUserScore->__toString();
                        if(isset($feedbackDetail->CommentText))
                            $ebayFeedbackModel->comment_text = $feedbackDetail->CommentText->__toString();
                        if(isset($feedbackDetail->CommentTime))
                            $ebayFeedbackModel->comment_time = date('Y-m-d H:i:s',strtotime($feedbackDetail->CommentTime->__toString()));
                        if(isset($feedbackDetail->CommentType))
                            $ebayFeedbackModel->comment_type = array_search($feedbackDetail->CommentType->__toString(),EbayFeedback::$commentTypeMap);
                        if(isset($feedbackDetail->ItemID))
                            $ebayFeedbackModel->item_id = $feedbackDetail->ItemID->__toString();
                        if(isset($feedbackDetail->ItemTitle))
                            $ebayFeedbackModel->item_title = $feedbackDetail->ItemTitle->__toString();
                        if(isset($feedbackDetail->ItemPrice))
                        {
                            $ebayFeedbackModel->item_price = $feedbackDetail->ItemPrice->__toString();
                            $ebayFeedbackModel->currency = $feedbackDetail->ItemPrice->attributes()['currencyID'];
                        }
                        if(isset($feedbackDetail->Role))
                            $ebayFeedbackModel->role = array_search($feedbackDetail->Role->__toString(),EbayFeedback::$roleMap);
                        $ebayFeedbackModel->feedback_id = $feedbackId;
                        if(isset($feedbackDetail->TransactionID))
                            $ebayFeedbackModel->transaction_id = $feedbackDetail->TransactionID->__toString();
                        if(isset($feedbackDetail->OrderLineItemID))
                            $ebayFeedbackModel->order_line_item_id = $feedbackDetail->OrderLineItemID->__toString();
                        if(isset($feedbackDetail->FeedbackResponse))
                            $ebayFeedbackModel->feedback_response = $feedbackDetail->FeedbackResponse->__toString();
                        $ebayFeedbackModel->item_title = $feedbackDetail->ItemTitle->__toString();
                        if($isUpdate)
                        {
                            $ebayFeedbackModel->modify_by = 1;
                            $ebayFeedbackModel->modify_time = date('Y-m-d H:i:s');
                        }
                        else
                        {
                            $ebayFeedbackModel->create_by = 1;
                            $ebayFeedbackModel->create_time = date('Y-m-d H:i:s');
                        }
                        $errorInfo = null;
                        try{
                            $flag = $ebayFeedbackModel->save(false);
                            if(!$flag)
                                $errorInfo = VHelper::getModelErrors($ebayFeedbackModel);
                        }catch(Exception $e){
                            $flag = false;
                            $errorInfo = $e->getMessage();
                        }
                        if(isset($this->ebayApiTaskModel) && !$flag)
                        {
                            $this->ebayApiTaskModel->error .= '[错误码：'.$this->errorCode.'。'.$errorInfo.']';
                            $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                            $this->ebayApiTaskModel->status = 1;
                            $this->ebayApiTaskModel->save();
                            $this->errorCode++;
                        }
                    }
                    if($simXml->PaginationResult->TotalNumberOfPages > $this->PageNumber)
                    {
                        $this->PageNumber++;
                        $this->handleResponse();
                    }
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
        $this->compatabilityLevel = 983;
        $this->verb = 'GetFeedback';
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
        $this->sendXml .= '<GetFeedbackRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";
        if(empty($this->FeedbackID))
        {
            if(!empty($this->CommentType))
            {
                foreach($this->CommentType as $commentType)
                {
                    $this->sendXml .= "<CommentType>{$commentType}</CommentType>";
                }
            }
            if(!empty($this->ItemID))
            {
                $this->sendXml .= "<ItemID>{$this->ItemID}</ItemID>";
            }
            if(!empty($this->UserID))
            {
                $this->sendXml .= "<UserID>{$this->UserID}</UserID>";
            }
            if(!empty($this->OrderLineItemID))
            {
                $this->sendXml .= "<OrderLineItemID>{$this->OrderLineItemID}</OrderLineItemID>";
            }
            if(!empty($this->TransactionID))
            {
                $this->sendXml .= "<TransactionID>{$this->TransactionID}</TransactionID>";
            }
            if(empty($this->OrderLineItemID) && empty($this->TransactionID))
            {
                $this->sendXml .= '<Pagination>';
                $this->sendXml .= "<EntriesPerPage>{$this->EntriesPerPage}</EntriesPerPage>";
                $this->sendXml .= "<PageNumber>{$this->PageNumber}</PageNumber>";
                $this->sendXml .= '</Pagination>';
            }
        }
        else
            $this->sendXml .= "<FeedbackID>{$this->FeedbackID}</FeedbackID>";
        if(!empty($this->DetailLevel))
            $this->sendXml .= "<DetailLevel>{$this->DetailLevel}</DetailLevel>";
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</GetFeedbackRequest>';
        return true;
    }

    public function validate()
    {
        $this->errors = [];
        $flag = true;
        if(empty($this->FeedbackID))
        {
            if(!empty($this->CommentType))
            {
                if(is_array($this->CommentType))
                {
                    if(array_intersect($this->CommentType,EbayFeedback::$commentTypeMap) != $this->CommentType)
                    {
                        $this->errors[] = ['CommentType'=>'值错误'];
                        $flag = false;
                    }
                }
                else
                {
                    $this->errors[] = ['CommentType'=>'必需为数组'];
                    $flag = false;
                }
            }
            if(!empty($this->ItemID))
            {
                if(strlen($this->ItemID) > 12)
                {
                    $this->errors[] = ['ItemID'=>'长度不能大于12位'];
                    $flag = false;
                }
            }
            if(!empty($this->OrderLineItemID))
            {
                 if(strlen($this->OrderLineItemID)>100)
                 {
                     $this->errors[] = ['OrderLineItemID'=>'长度不能大于100位'];
                     $flag = false;
                 }
            }
            if(!empty($this->TransactionID))
            {
                if(strlen($this->TransactionID) > 12)
                {
                    $this->errors[] = ['TransactionID'=>'长度不能大于12位'];
                    $flag = false;
                }
            }
            if(empty($this->OrderLineItemID) && empty($this->TransactionID))
            {
                if(!empty($this->FeedbackType))
                {
                    if(!in_array($this->FeedbackType,self::$feedbackTypeMap))
                    {
                        $this->errors[] = ['FeedbackType'=>'值错误'];
                        $flag = false;
                    }
                }
                if(!is_numeric($this->EntriesPerPage) || $this->EntriesPerPage < 1 || $this->EntriesPerPage > 25 || $this->EntriesPerPage%1 !== 0)
                {
                    $this->errors[] = ['EntriesPerPage'=>'值错误'];
                    $flag = false;
                }
                if(!is_numeric($this->PageNumber) || $this->PageNumber < 1 || $this->PageNumber%1 !==0)
                {
                    $this->errors[] = ['PageNumber'=>'值错误'];
                    $flag = false;
                }
            }
        }
        return $flag;
    }

}