<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/10 0010
 * Time: 下午 6:57
 */

namespace app\modules\services\modules\ebay\models;

use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbayReply;
use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\EbayReplyPicture;
use app\modules\mails\models\EbayEpsPictures;
use app\modules\services\modules\ebay\components\EbayApiAbstract;
use PhpImap\Exception;

class AddMemberMessageAAQToPartner extends EbayApiAbstract
{
    public $ItemID = null;
    public $Body = null;
    public $EmailCopyToSender = null;
    public $MessageMedia;
    public $QuestionType = null;
    public $RecipientID = null;
    public $Subject = null;

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
        if($reply != null)
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
        if(!isset($this->ebayAccountModel))
        {
            $accountName = Account::findOne((int)$this->replyModel->account_id)->account_name;
            $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
            if(empty($this->ebayAccountModel))
                throw new Exception('ebay账号数据未找到.');
        }
        $this->ItemID = $this->replyModel->item_id;
        $this->Body = htmlspecialchars($this->replyModel->reply_content);
        $this->QuestionType = is_numeric($this->replyModel->question_type) ? EbayInbox::$questionTypeMap[$this->replyModel->question_type] : $this->replyModel->question_type;
        $this->RecipientID = [$this->replyModel->recipient_id];
        $this->Subject = $this->replyModel->reply_title;
        $pictureModels = EbayReplyPicture::findAll(['reply_table_id'=>$this->replyModel->id]);
        if(!empty($pictureModels))
        {
            foreach($pictureModels as $pictureModel)
            {
                if($pictureModel->picture_name == '' || $pictureModel->picture_name == null)
                {
                    $file = basename($pictureModel->picture_url);
                    $startPos = strpos($file,'_')+1;
                    $endPos = strrpos($file,'.');
                    $name = substr($file,$startPos,$endPos-$startPos);
                }
                else
                    $name = $pictureModel->picture_name;
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
        //$this->_userToken = 'AgAAAA**AQAAAA**aAAAAA**lx0TWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AElYKnAZiDqQidj6x9nY+seQ**sHQDAA**AAMAAA**pytMwIj1pwv4iT66a56C33BKKI9DALu2lEMsIMZ/PmGAFXslaVoLhSHKLV+PP8sAodgCozrSA1Nj6XOUwXGCngJTBfpFDwD7Q1GGYaLPVaS0J/vcbZ1yI24XJEOfpCpD/+eosiH0cpR4EFDsFfJKIlugDmDoABluqPfjuK+e8gdK+KoIg12+u9lUmpg+TR7CPCR6zNm2L7sVlFkmpIw4fyfezkWycUdJ9LqKdgqcvZPjoiBUY9x4EEC22r9UJzrXCQEJ2qeKWN5eXAHyPturxPC7ygj/uQZ97wP9diA7T78tchgo++jC9AID5briQxNys2Mqebuld/wQ0sDAodlzQgOeRG698lAGtu2JxAwKRgDRbnkgTuAd8GwRn4k0Qs0LiUBJuDSWS5+UE9YHYkS+PquW1U6PCA1wqxhYxIXzjEFfvCehCuEhEus/bhSEYtliS+I2VYvIRK3YmOt738QLaOQfZgyYdaz9r9HS6HVeAHD2IAx+6/lvYTHM8PtDxOXsdNqYnNC+Yz/LiW4Jli0+t5iul6BZ76JU7LPzd1P9nItTOfk29DF1VXEa5KihiYqsxmIbMRB1HeXlzC5sAp0YrDQ1Mj7YLOV+4g0suYC58pxOIxjikfwS1AIYcjasNAsDQUp8sFj3jd5dyVF+WKOY/IhNvxFL9MrDoNaSkilPi6j+2tZVhmrsLvG7WOD0VXNdAvXAEAXBS4zUat0dzrBNBd9V7H7m+0RdWYLPdnmnDm2h57SbqULpm2qEryEReOZU';
        $this->_userToken = $this->ebayAccountModel->user_token;
        $this->appID = $ebayKeys['appID'];
        $this->devID = $ebayKeys['devID'];
        $this->certID = $ebayKeys['certID'];
        $this->serverUrl = $ebayKeys['serverUrl'];
//        $this->siteID = $this->replyModel->siteid;
        $this->compatabilityLevel = 983;
        $this->verb = 'AddMemberMessageAAQToPartner';
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
                         $this->sendContent .= "[{$this->sendXml}]";
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
                         $this->sendContent .= "[{$this->sendXml}]";
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
         else
             return false;
    }

    public function getSendXml()
    {
        return $this->sendXml;
    }

    public function requestXmlBody()
    {
        if(!$this->validate())
        {
            $errors = $this->errors;
            foreach ($errors as $error)
            {
                $key = key($error);
                $errorInfo = $key.'('.$this->$key.'):'.current($error).'。';
            }
            if(isset($this->ebayApiTaskModel))
            {
                $this->ebayApiTaskModel->error .= '['.$errorInfo.']';
                $this->ebayApiTaskModel->end_time = $this->ebayApiTaskModel->opration_date = date('Y-m-d H:i:s');
                $this->ebayApiTaskModel->exec_status = 1;
                $this->ebayApiTaskModel->status = 1;
            }
            else
            {
                throw new \Exception($errorInfo);
            }
            return false;
        }
        $this->sendXml = '<?xml version="1.0" encoding="utf-8" ?>';
        $this->sendXml .= '<AddMemberMessageAAQToPartnerRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";
        $this->sendXml .= "<ItemID>{$this->ItemID}</ItemID>";
        $this->sendXml .= '<MemberMessage>';
        $this->sendXml .= "<Body>{$this->Body}</Body>";
        if(!empty($this->EmailCopyToSender))
            $this->sendXml .= "<EmailCopyToSender>{$this->EmailCopyToSender}</EmailCopyToSender>";
        if(!empty($this->MessageMedia))
        {
            $this->sendXml .= '<MessageMedia>';
            foreach($this->MessageMedia as $MediaName=>$MediaURL)
            {
                $this->sendXml .= "<MediaName>$MediaName</MediaName>";
                $this->sendXml .= "<MediaURL>$MediaURL</MediaURL>";
            }
            $this->sendXml .= '</MessageMedia>';
        }
        $this->sendXml .= "<QuestionType>{$this->QuestionType}</QuestionType>";
        foreach($this->RecipientID as $RecipientID)
        {
            $this->sendXml .= "<RecipientID>{$RecipientID}</RecipientID>";
        }
        $this->sendXml .= "<Subject>{$this->Subject}</Subject>";
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</MemberMessage>';
        $this->sendXml .= '</AddMemberMessageAAQToPartnerRequest>';
        return true;
    }

    public function validate()
    {
        $flag = true;
        $this->errors = [];
        if(empty($this->ItemID))
        {
            $flag = false;
            $this->errors[] = ['ItemID'=>'不能为空'];
        }
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
        if(empty($this->QuestionType))
        {
            $flag = false;
            $this->errors[] = ['QuestionType'=>'不能为空'];
        }
        else
        {
            if(in_array($this->QuestionType,array_keys(EbayReply::$questionTypeMap)))
            {
                $this->QuestionType = EbayReply::$questionTypeMap[$this->QuestionType];
            }
            if(!in_array($this->QuestionType,EbayReply::$questionTypeMap))
            {
                $flag = false;
                $this->errors[] = ['QuestionType'=>'值错误'];
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

        if(empty($this->Subject))
        {
            $flag = false;
            $this->errors[] = ['Subject'=>'不能为空'];
        }
        return $flag;

    }
}