<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/2 0002
 * Time: 上午 10:45
 */

namespace app\modules\services\modules\ebay\models;


use app\modules\mails\models\EbayEpsPictures;
use app\modules\mails\models\EbayEpsPicturesCollect;
use app\modules\services\modules\ebay\components\EbayApiAbstract;

class UploadSiteHostedPictures extends EbayApiAbstract
{
    public $ExtensionInDays;
    public $ExternalPictureURL;
    public $PictureName;
    public $PictureSet;
    public $PictureUploadPolicy;

    protected $sendXml;
    protected $errors;
    public $ebayAccountModel;
    public $relevance = '';
    private $pictureUrl;

    public function getPictureUrl()
    {
        return $this->pictureUrl;
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
        $this->verb = 'UploadSiteHostedPictures';
        return $this;
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
                    $epsModel = EbayEpsPictures::findOne(['relevance'=>$this->relevance]);
                    if(empty($epsModel))
                        $epsModel = new EbayEpsPictures();
                    $epsModel->relevance = $this->relevance;
                    $epsModel->base_url = $simXml->SiteHostedPictureDetails->BaseURL->__toString();
                    $epsModel->external_picture_url = $simXml->SiteHostedPictureDetails->ExternalPictureURL->__toString();
                    $epsModel->full_url = $simXml->SiteHostedPictureDetails->FullURL->__toString();
                    $epsModel->picture_format = $simXml->SiteHostedPictureDetails->PictureFormat->__toString();
                    $epsModel->picture_name = $simXml->SiteHostedPictureDetails->PictureName->__toString();
                    $epsModel->picture_set = $simXml->SiteHostedPictureDetails->PictureSet->__toString();
                    $use_by_date = $simXml->SiteHostedPictureDetails->UseByDate->__toString();
                    $epsModel->use_by_date = date('Y-m-d H:i:s',strtotime($use_by_date));
                    $epsModel->save();
                    EbayEpsPicturesCollect::deleteAll(['master_id'=>$epsModel->id]);
                    foreach ($simXml->SiteHostedPictureDetails->PictureSetMember as $member)
                    {
                        $epsCollectModel = new EbayEpsPicturesCollect();
                        $epsCollectModel->master_id = $epsModel->id;
                        $epsCollectModel->member_url = $member->MemberURL->__toString();
                        $epsCollectModel->picture_height = $member->PictureHeight->__toString();
                        $epsCollectModel->picture_width = $member->PictureWidth->__toString();
                        $epsCollectModel->save();
                        $this->pictureUrl[$member->PictureHeight->__toString()] = $member->MemberURL->__toString();
                    }
            }
        }
    }

    public function requestXmlBody()
    {
        $this->sendXml = '';
        if(!$this->validate())
        {
            return false;
        }
        $this->sendXml .= '<?xml version="1.0" encoding="utf-8" ?>';
        $this->sendXml .= '<UploadSiteHostedPicturesRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->ebayAccountModel->user_token}</eBayAuthToken></RequesterCredentials>";
        if(isset($this->ExtensionInDays))
            $this->sendXml .= "<ExtensionInDays>$this->ExtensionInDays</ExtensionInDays>";
        $this->sendXml .= "<ExternalPictureURL>$this->ExternalPictureURL</ExternalPictureURL>";
        if(isset($this->PictureName))
            $this->sendXml .= "<PictureName>$this->PictureName</PictureName>";
        if(isset($this->PictureSet))
            $this->sendXml .= "<PictureSet>$this->PictureSet</PictureSet>";
        $this->sendXml .= "<PictureUploadPolicy>$this->PictureUploadPolicy</PictureUploadPolicy>";
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</UploadSiteHostedPicturesRequest>';
        return true;
    }

    public function validate()
    {
        $flag = true;
        $this->errors = [];
        if(isset($this->ExtensionInDays))
        {
            if(!is_numeric($this->ExtensionInDays) || $this->ExtensionInDays < 1 || $this->ExtensionInDays > 30 || $this->ExtensionInDays%1 !== 0)
            {
                $flag = false;
                $this->errors[] = ['ExtensionInDays'=>'需为1到30的整数'];
            }
        }
        if(empty($this->ExternalPictureURL))
        {
            $flag = false;
            $this->errors[] = ['ExternalPictureURL'=>'不能为空'];
        }
        if(!empty($this->PictureSet))
        {
            if(!in_array($this->PictureSet,['Standard','Supersize']))
            {
                $flag = false;
                $this->errors[] = ['PictureSet'=>'值错误'];
            }
        }
        if(empty($this->PictureUploadPolicy))
        {
            $this->PictureUploadPolicy = 'Add';
        }
        else
        {
            if(!in_array($this->PictureUploadPolicy,['Add','ClearAndAdd']))
            {
                $flag = false;
                $this->errors[] = ['PictureUploadPolicy'=>'值错误'];
            }
        }
        return $flag;
    }

}