<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/3 0003
 * Time: 下午 5:44
 */

namespace app\modules\services\modules\ebay\models;

use app\modules\mails\models\EbayDisputes;
use app\modules\services\modules\ebay\components\EbayApiAbstract;
use app\modules\systems\models\EbayAccount;
class GetUserCases extends EbayApiAbstract
{
    public $caseStatus;
    public $caseType;
    public $fromDate;
    public $toDate;
    public $itemId;
    public $transactionId;
    public $entriesPerPage = 25;
    public $pageNumber = 1;
    public $sortOrder;

    public static $caseStatusMap = ['CLOSED','ELIGIBLE_FOR_CREDIT','MY_PAYMENT_DUE','MY_RESPONSE_DUE','OPEN','OTHER_PARTY_RESPONSE_DUE'];
    public static $caseTypeMap = ['CANCEL_TRANSACTION','EBP_INR','EBP_SNAD','INR','PAYPAL_INR','PAYPAL_SNAD','RETURN','SNAD','UPI'];
    public static $sortOrderMap = ['CASE_STATUS_ASCENDING','CASE_STATUS_DESCENDING','CREATION_DATE_ASCENDING','CREATION_DATE_DESCENDING'];

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
//            findClass($this->response,1);
            $simXml = simplexml_load_string($this->response);
            switch($simXml->ack->__toString())
            {
                case 'Failure':
                    break;
                case 'PartialFailure':
                    break;
                case 'Warning':
                case 'Success':
                    $cases = $simXml->cases->caseSummary;
                    foreach($cases as $case)
                    {
                        $case_id = $case->caseId->id->__toString();
                        $ebayDisputesModel = EbayDisputes::findOne(['case_id'=>$case_id]);
                        if(empty($ebayDisputesModel))
                            $ebayDisputesModel = new EbayDisputes();
                        $ebayDisputesModel->case_id = $case_id;
                        $ebayDisputesModel->case_type = array_search(trim($case->caseId->type->__toString()),EbayDisputes::$caseTypeMap);
                        $ebayDisputesModel->case_amount = $case->caseAmount->__toString();
                        $ebayDisputesModel->case_quantity = $case->caseQuantity->__toString();
                        $ebayDisputesModel->creation_date = $case->creationDate->__toString();
                        $ebayDisputesModel->item_id = $case->item->itemId->__toString();
                        $ebayDisputesModel->item_title = $case->item->itemTitle->__toString();
                        $ebayDisputesModel->transaction_id = $case->item->transactionId->__toString();
                        $ebayDisputesModel->last_modified_date = $case->lastModifiedDate->__toString();
                        $ebayDisputesModel->make_side_role = array_search($case->user->role->__toString(),EbayDisputes::$roleMap);
                        if(isset($case->user->userId))
                            $ebayDisputesModel->make_side_user_id = $case->user->userId->__toString();
                        $ebayDisputesModel->other_side_role = array_search($case->otherParty->role->__toString(),EbayDisputes::$roleMap);
                        if(isset($case->otherParty->userId))
                            $ebayDisputesModel->other_side_user_id = $case->otherParty->userId->__toString();
                        $ebayDisputesModel->respond_by_date = $case->respondByDate->__toString();
                        $ebayDisputesModel->case_status = current($case->status->children());
                        $ebayDisputesModel->account_id = $this->ebayAccountModel->id;
                        $ebayDisputesModel->siteid = $this->siteID;
                        $ebayDisputesModel->save();
                    }
                    if($this->pageNumber < $simXml->paginationOutput->totalPages->__toString())
                    {
                        $this->pageNumber++;
                        $this->handleResponse();
                    }
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
        $this->serverUrl = $ebayKeys['resolutionsEndpoints'];
        $this->compatabilityLevel = 983;
        $this->verb = 'getUserCases';
        return $this;
    }

    public function requestXmlBody()
    {
//        findClass($this->validate(),1);
        $this->sendXml = '';
        if(!$this->validate())
        {
            return false;
        }
        $this->sendXml = '<?xml version="1.0" encoding="utf-8" ?>';
        $this->sendXml .= '<getUserCasesRequest xmlns="http://www.ebay.com/marketplace/resolution/v1/services">';
        //$this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";
        if(!empty($this->caseStatus))
        {
            $this->sendXml .= '<caseStatusFilter>';
            foreach($this->caseStatus as $caseStatus)
            {
                $this->sendXml .= "<caseStatus>{$caseStatus}</caseStatus>";
            }
            $this->sendXml .= '</caseStatusFilter>';
        }
        if(!empty($this->caseType))
        {
            $this->sendXml .= '<caseTypeFilter>';
            foreach($this->caseType as $caseType)
                $this->sendXml .= "<caseType>{$caseType}</caseType>";
            $this->sendXml .= '</caseTypeFilter>';
        }
        $this->sendXml .= '<creationDateRangeFilter>';
        $this->sendXml .= "<fromDate>{$this->fromDate}</fromDate>";
        if(!empty($this->toDate))
        {
            $this->sendXml .= "<toDate>{$this->toDate}</toDate>";
        }
        $this->sendXml .= '</creationDateRangeFilter>';
        if(!empty($this->pageNumber))
        {
            $this->sendXml .= '<paginationInput>';
            $this->sendXml .= "<entriesPerPage>{$this->entriesPerPage}</entriesPerPage>";
            $this->sendXml .= "<pageNumber>{$this->pageNumber}</pageNumber>";
            $this->sendXml .= '</paginationInput>';
        }
        if(!empty($this->sortOrder))
        {
            $this->sendXml .= "<sortOrder>{$this->sortOrder}</sortOrder>";
        }
        $this->sendXml .= '</getUserCasesRequest>';
        return true;
    }

    public function validate()
    {
        $flag = true;
        $this->errors = [];
        if(!empty($this->caseStatus))
        {
            if(is_array($this->caseStatus))
            {
                if(array_intersect($this->caseStatus,self::$caseStatusMap) != $this->caseStatus)
                {
                    $flag = false;
                    $this->errors[] = ['caseStatus'=>'值错误'];
                }
            }
            else
            {
                $flag = false;
                $this->errors[] = ['caseStatus'=>'必需是数组'];
            }
        }

        if(!empty($this->caseType))
        {
            if(is_array($this->caseType))
            {
                if(array_intersect($this->caseType,self::$caseTypeMap) != $this->caseType)
                {
                    $flag = false;
                    $this->errors[] = ['caseType'=>'值错误'];
                }
            }
            else
            {
                $flag = false;
                $this->errors[] = ['caseType'=>'必需是数组'];
            }
        }

        if(empty($this->fromDate))
        {
            $flag = false;
            $this->errors[] = ['fromDate'=>'不能为空'];
        }
        else
        {
            if(!self::isTimeDate($this->fromDate))
            {
                $this->errors[] = ['fromDate' => '时间格式不为2017-03-24T11:29:36.000Z'];
                $flag = false;
            }
        }

        if(!empty($this->toDate))
        {
            if(!self::isTimeDate($this->toDate))
            {
                $this->errors[] = ['toDate' => '时间格式不为2017-03-24T11:29:36.000Z'];
                $flag = false;
            }
        }

        if(!empty($this->itemId))
        {
            if(strlen($this->itemId) > 19)
            {
                $flag = false;
                $this->errors[] = ['itemId'=>'长度不能大于19'];
            }
        }

        if(!empty($this->transactionId))
        {
            if(strlen($this->transactionId) > 19)
            {
                $flag = false;
                $this->errors[] = ['transactionId'=>'长度不能大于19'];
            }
        }

        if(!empty($this->entriesPerPage) || !empty($this->pageNumber))
        {
            if(empty($this->entriesPerPage))
            {
                $flag = false;
                $this->errors[] = ['entriesPerPage'=>'pageNumber不为空时,entriesPerPage也不能为空'];
            }
            else
            {
                if(!is_numeric($this->entriesPerPage) || $this->entriesPerPage < 1 || $this->entriesPerPage > 200 || $this->entriesPerPage%1 !== 0)
                {
                    $flag = false;
                    $this->errors[] = ['entriesPerPage'=>'值错误'];
                }
            }
            if(empty($this->pageNumber))
            {
                $flag = false;
                $this->errors[] = ['entriesPerPage'=>'entriesPerPage不为空时,pageNumber也不能为空'];
            }
            else
            {
                if(!is_numeric($this->pageNumber) || $this->pageNumber < 1 || $this->pageNumber%1 !== 0)
                {
                    $flag = false;
                    $this->errors[] = ['pageNumber'=>'值错误'];
                }
            }
        }

        if(!empty($this->sortOrder))
        {
            if(!in_array($this->sortOrder,self::$sortOrderMap))
            {
                $flag = false;
                $this->errors[] = ['sortOrder'=>'值错误'];
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