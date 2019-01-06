<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/26 0026
 * Time: 下午 4:14
 */

namespace app\modules\services\modules\walmart\models;

use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\services\modules\walmart\components\WalmartApiAbstract;
use app\modules\systems\models\EbayAccount;
use app\modules\mails\models\EbayFeedback;
use PhpImap\Exception;

class GetOrder extends WalmartApiAbstract
{
    public $url = "https://marketplace.walmartapis.com/v3/orders/";

    public function __construct($accountName)
    {
        $platformCode = Platform::PLATFORM_CODE_WALMART;
        $this->accountInfo = Account::getAccountFromErp($platformCode, $accountName);
    }

    public function setRequest()
    {
        $this->config['consumerId'] = $this->accountInfo->consumer_id;
        $this->config['privateKey'] = $this->accountInfo->private_key;
        $this->config['channelType'] = $this->accountInfo->channel_type;
        $this->method = 'GET';
    }

    public function handleResponse($url)
    {
        $this->url = $url;
        $this->getHeaders();
        $this->response = $this->sendHttpRequest();
        if(!empty($this->response)){
            return $this->response;
        }else{
            return false;
        }

    }

    public function requestXmlBody()
    {
        $this->sendXml = '';

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