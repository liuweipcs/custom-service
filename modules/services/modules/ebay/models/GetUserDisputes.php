<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/3 0003
 * Time: 下午 4:31
 */

namespace app\modules\services\modules\ebay\models;

use app\modules\services\modules\ebay\components\EbayApiAbstract;
use app\modules\systems\models\EbayAccount;
class GetUserDisputes extends EbayApiAbstract
{
    public $DisputeFilterType; //可选
    public $DisputeSortType; //可选
    public $ModTimeFrom; //可选
    public $ModTimeTo; //可选
    public $EntriesPerPage = 25;
    public $PageNumber = 1;
    public $DetailLevel = 'ReturnAll';

    public static $disputeFilterTypeMap = ['AllInvolvedClosedDisputes','AllInvolvedDisputes','DisputesAwaitingMyResponse','DisputesAwaitingOtherPartyResponse','EligibleForCredit','ItemNotReceivedDisputes','UnpaidItemDisputes'];
    public static $disputeSortTypeMap = ['DisputeCreatedTimeAscending','DisputeCreatedTimeDescending','DisputeCreditEligibilityAscending','DisputeCreditEligibilityDescending','DisputeStatusAscending','DisputeStatusDescending'];
    public static $detailLevelMap = ['ReturnAll','ReturnSummary'];

    protected $ebayAccountModel;
    protected $sendXml;
    protected $errors;

    public function __construct($account)
    {
        if(is_numeric($account))
        {
            $account = EbayAccount::find()->where('id=:id',[':id'=>$account])->one();
        }
        if($account instanceof EbayAccount)
            $this->ebayAccountModel = $account;
        else
            throw new Exception('ebay账号数据未找到');
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
            findClass($this->response,1);
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
        $this->verb = 'GetUserDisputes';
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
        $this->sendXml .= '<GetUserDisputesRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";
        if(!empty($this->DisputeFilterType))
            $this->sendXml .= "<DisputeFilterType>{$this->DisputeFilterType}</DisputeFilterType>";
        if(!empty($this->DisputeSortType))
            $this->sendXml .= "<DisputeSortType>{$this->DisputeSortType}</DisputeSortType>";
        if(!empty($this->ModTimeFrom))
            $this->sendXml .= "<ModTimeFrom>{$this->ModTimeFrom}</ModTimeFrom>";
        if(!empty($this->ModTimeTo))
            $this->sendXml .= "<ModTimeTo>{$this->ModTimeTo}</ModTimeTo>";
        $this->sendXml .= '<Pagination>';
        if(!empty($this->EntriesPerPage))
            $this->sendXml .= "<EntriesPerPage>{$this->EntriesPerPage}</EntriesPerPage>";
        $this->sendXml .= "<PageNumber>{$this->PageNumber}</PageNumber>";
        $this->sendXml .= '</Pagination>';
        if(!empty($this->DetailLevel))
        {
            $this->DetailLevel .= "<DetailLevel>{$this->DetailLevel}</DetailLevel>";
        }
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</GetUserDisputesRequest>';
        return true;
    }

    public function validate()
    {
        $flag = true;
        $this->errors = [];
        if(!empty($this->DisputeFilterType))
        {
            if(!in_array($this->DisputeFilterType,self::$disputeFilterTypeMap))
            {
                $flag = false;
                $this->errors[] = ['DisputeFilterType','值错误'];
            }
        }
        if(!empty($this->DisputeSortType))
        {
            if(!in_array($this->DisputeSortType,self::$disputeSortTypeMap))
            {
                $flag = false;
                $this->errors[] = ['DisputeSortType','值错误'];
            }
        }
        if(!empty($this->ModTimeFrom))
        {
            if(!self::isTimeDate($this->ModTimeFrom))
            {
                $this->errors[] = ['ModTimeFrom' => '时间格式不为2017-03-24T11:29:36.000Z'];
                $flag = false;
            }
        }
        if(!empty($this->ModTimeTo))
        {
            if(!self::isTimeDate($this->ModTimeTo))
            {
                $this->errors[] = ['ModTimeTo' => '时间格式不为2017-03-24T11:29:36.000Z'];
                $flag = false;
            }
        }
        if(!empty($this->EntriesPerPage))
        {
            if(!is_numeric($this->EntriesPerPage) || $this->EntriesPerPage < 1 || $this->EntriesPerPage > 25 || $this->EntriesPerPage%1 !== 0 )
            {
                $flag = false;
                $this->errors[] = ['EntriesPerPage' => '值错误'];
            }
        }
        if(empty($this->PageNumber))
        {
            $flag = false;
            $this->errors[] = ['PageNumber'=>'不能为空'];
        }
        else
        {
            if(!is_numeric($this->PageNumber) || $this->PageNumber < 1 || $this->PageNumber%1 !== 0)
            {
                $flag = false;
                $this->errors[] = ['PageNumber'=>'值错误'];
            }
        }
        if(!empty($this->DetailLevel))
        {
            if(!in_array($this->DetailLevel,self::$detailLevelMap))
            {
                $flag = false;
                $this->errors[] = ['DetailLevel'=>'值错误'];
            }
        }
        return $flag;
    }

    public static function isTimeDate($var)
    {
        if(preg_match('/^\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-6]\d:[0-6]\d[.]\d{3}Z$/',$var))
            return true;
        else
            return false;
    }

}