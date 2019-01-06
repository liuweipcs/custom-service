<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/10 0010
 * Time: 下午 6:57
 */

namespace app\modules\services\modules\ebay\models;

use app\modules\mails\models\EbayEpsPictures;
use app\modules\mails\models\EbayReply;
use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\EbayReplyPicture;
use app\modules\services\modules\ebay\components\EbayApiAbstract;
use app\modules\systems\models\EbayAccount;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use PhpImap\Exception;

class AddMemberMessageRTQ extends EbayApiAbstract
{
    public $ItemID = null;
    public $Body = null;
    public $DisplayToPublic = null;  //回复消息是否在listing中可见
    public $EmailCopyToSender = null;
    public $MessageMedia;//['MediaName'=>'MediaURL']
    public $ParentMessageID = null;//回复的那一条消息ID
    public $RecipientID = null;


    public $ebayApiTaskModel;

    protected $sendXml;
    protected $errors;
    public $replyModel;
    public $ebayAccountModel;
    public $responseError;

    public function __construct($reply = null)
    {
        if($reply != null)
            $this->paramInit($reply);
    }

    public function paramInit($reply = null)
    {
        if($reply != null )
        {
            if(is_numeric($reply))
            {
                $reply = EbayReply::findOne((int)$reply);
            }
            if($reply instanceof EbayReply)
                $this->replyModel = $reply;
            else
                throw new Exception('未找到reply数据.');
            if($reply->is_delete)
                throw new Exception('reply已被删除，不能发送.');
            if($reply->is_draft)
                throw new Exception('reply是草稿.');
            if($reply->is_send)
                throw new Exception('reply不能重复发送.');
        }
        if(empty($this->replyModel))
            throw new Exception('未找到reply数据.');
        $this->ebayAccountModel = Account::findById((int)$this->replyModel->account_id);
        if(empty($this->ebayAccountModel))
            throw new Exception('ebay账号数据未找到.');
//        $accountName = $this->ebayAccountModel->account_name;
//        $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);

        $this->ItemID = $this->replyModel->item_id;
        $this->Body = htmlspecialchars($this->replyModel->reply_content);
        $this->ParentMessageID = $this->replyModel->parent_message_id;
        $this->RecipientID = [$this->replyModel->recipient_id];
        $pictureModels = EbayReplyPicture::findAll(['reply_table_id'=>$this->replyModel->id]);
        if(!empty($pictureModels))
        {
            foreach($pictureModels as $pictureModel)
            {
                $file = basename($pictureModel->picture_url);
                $startPos = strpos($file,'_')+1;
                $endPos = strrpos($file,'.');
                $name = substr($file,$startPos,$endPos-$startPos);
                $relevance = 'reply_picture_'.$pictureModel->id;
                $epsPicturesModel = EbayEpsPictures::find()->where(['relevance'=>$relevance])->andWhere(['>','use_by_date',date('Y-m-d H:i:s',strtotime('-4 days'))])->one();
                if(!empty($epsPicturesModel))
                {
                    $epsCollectModel = $epsPicturesModel->getMaxCollect();
                    if(!empty($epsCollectModel))
                    {
                        $this->MessageMedia[$name] = $epsCollectModel->member_url;
                        continue;
                    }
                }
                $uploadModel = new UploadSiteHostedPictures();
                $uploadModel->ebayAccountModel = $this->ebayAccountModel;
                $uploadModel->ExternalPictureURL = $pictureModel->picture_url;
                $uploadModel->PictureName = $name.'_'.microtime().'_'.mt_rand();
                $uploadModel->relevance = $relevance;
                $uploadModel->handleResponse();
                $pictures = $uploadModel->getPictureUrl();
                $this->MessageMedia[$name] = $pictures[max(array_keys($pictures))];
            }
        }
        return $this;
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
        $this->verb = 'AddMemberMessageRTQ';
        return $this;
    }

    public function addMessage()
    {
        $this->setRequest()->sendHttpRequest();
        return $this->requestStatus && !empty($this->response);
    }

    public function handleResponse()
    {
         if(!empty($this->response))
         {
            $simXml = simplexml_load_string($this->response);
             switch ($simXml->Ack)
             {
                 case 'Failure':
                     $this->responseError = $simXml->asXML();
                     if(isset($this->ebayApiTaskModel))
                     {
                         $this->ebayApiTaskModel->error .= '['.$simXml->asXML().']';
                         $this->ebayApiTaskModel->sendContent .= "[{$this->sendXml}]";
                         $this->ebayApiTaskModel->exec_status = 2;
                         $this->ebayApiTaskModel->status = 1;
                         $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                         $this->ebayApiTaskModel->save();
                     }
                     return false;
                 case 'Warning':
                     if(isset($this->ebayApiTaskModel))
                     {
                         $this->ebayApiTaskModel->error .= '['.$simXml->Errors->asXML().']';
                         $this->ebayApiTaskModel->sendContent .= "[{$this->sendXml}]";
                         $this->ebayApiTaskModel->exec_status = 1;
                         if($this->ebayApiTaskModel->status > 2 || empty($this->ebayApiTaskModel->status))
                             $this->ebayApiTaskModel->status = 2;
                         $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                         $this->ebayApiTaskModel->save();
                     }
                 case 'Success':
                     if($this->replyModel instanceof EbayReply)
                     {
                         $this->replyModel->is_send = 1;
                         $this->replyModel->save();
                     }
                     return true;
             }
         }
    }

    public function getSendXml()
    {
        return $this->sendXml;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function requestXmlBody()
    {
        if(!$this->validate())
        {
            if(isset($this->ebayApiTaskModel))
            {
                $this->ebayApiTaskModel->error .= '[';
                $errors = $this->getErrors();
                foreach ($errors as $error)
                {
                    $key = key($error);
                    $this->ebayApiTaskModel->error .= $key.'('.$this->$key.'):'.current($error).'。';
                }
                $this->ebayApiTaskModel->error .= ']';
                $this->ebayApiTaskModel->end_time = $this->ebayApiTaskModel->opration_date = date('Y-m-d H:i:s');
                $this->ebayApiTaskModel->exec_status = 1;
                $this->ebayApiTaskModel->status = 1;
            }
            return false;
        }
        $this->sendXml = '<?xml version="1.0" encoding="utf-8" ?>';
        $this->sendXml .= '<AddMemberMessageRTQRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";
        $this->sendXml .= "<ItemID>{$this->ItemID}</ItemID>";
        $this->sendXml .= '<MemberMessage>';
        $this->sendXml .= "<Body>{$this->Body}</Body>";
//        $this->sendXml .= "<DisplayToPublic>{$this->DisplayToPublic}</DisplayToPublic>";
        if(!empty($this->EmailCopyToSender))
            $this->sendXml .= "<EmailCopyToSender>{$this->EmailCopyToSender}</EmailCopyToSender>";
        if(!empty($this->MessageMedia))
        {
            $this->sendXml .= '<MessageMedia>';
            foreach($this->MessageMedia as $mediaName=>$mediaURL)
            {
                $this->sendXml .= "<MediaName>$mediaName</MediaName>";
                $this->sendXml .= "<MediaURL>$mediaURL</MediaURL>";
            }
            $this->sendXml .= '</MessageMedia>';
        }
        foreach($this->RecipientID as $RecipientID)
        {
            $this->sendXml .= "<RecipientID>{$RecipientID}</RecipientID>";
        }
        $this->sendXml .= "<ParentMessageID>{$this->ParentMessageID}</ParentMessageID>";
        $this->sendXml .= '</MemberMessage>';
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</AddMemberMessageRTQRequest>';
        return true;
    }

    public function validate()
    {
        $flag = true;
        $this->errors = [];
/*         if(empty($this->ItemID))
        {
            $flag = false;
            $this->errors[] = ['ItemID'=>'不能为空'];
        } */
        if(empty($this->Body))
        {
            $flag = false;
            $this->errors[] = ['Body'=>'不能为空'];
        }
        if(!empty($this->EmailCopyToSender))
        {
            if(is_string($this->EmailCopyToSender))
            {
                if(in_array($this->EmailCopyToSender,array('true','false')))
                {
                    $flag = false;
                    $this->errors[] = ['EmailCopyToSender'=>'值错误'];
                }
            }
            else
            {
                $flag = false;
                $this->errors[] = ['EmailCopyToSender'=>'不能为空'];
            }
        }

        if(empty($this->RecipientID))
        {
            $flag = false;
            $this->errors[] = ['RecipientID'=>'不能为空'];
        }
        else
        {
            if(!is_array($this->RecipientID))
            {
                $this->RecipientID = (array)$this->RecipientID;
            }
        }

        if(empty($this->ParentMessageID))
        {
            $flag = false;
            $this->errors[] = ['ParentMessageID'=>'不能为空'];
        }
        return $flag;

    }
}