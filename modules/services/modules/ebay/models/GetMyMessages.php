<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 下午 8:02
 */

namespace app\modules\services\modules\ebay\models;

use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\EbayInboxTmp;
use app\modules\services\modules\ebay\components\EbayApiAbstract;
use yii\base\Exception;

class GetMyMessages extends EbayApiAbstract {

    public $EndTime = null;   //2017-03-24T11:29:36.000Z
    public $FolderID = null;    //整数
    public $IncludeHighPriorityMessageOnly = null; //boolean
    public $MessageID = null;
    public $StartTime = null;
    public $DetailLevel = null;
    public $EntriesPerPage = 10;
    public $PageNumber = 1;
    protected $sendXml;
    protected $errors;
    public static $detailLevelValues = ['ReturnHeaders', 'ReturnMessages', 'ReturnSummary'];
    public static $poolTransform = ['true' => 1, 'false' => 0];
    public static $flaggedTransform = ['true' => 1, 'false' => 2];
    protected $oldMessageIDs = array();
    protected $ebayAccountModel;
    public $ebayApiTaskModel;

    public function __construct($account) {
        $this->ebayAccountModel = $account;
    }

    public function getMessages($path = '') {
        if ($path)
            file_put_contents($path, ' ' . $this->PageNumber . '_start_time:' . time(), FILE_APPEND);
        if ($this->getMessagesHeaders()) {

            $isRepetition = false;
            $headersSimXml = simplexml_load_string($this->response);
            
            if ($path)
                file_put_contents($path, ' time1:' . time(), FILE_APPEND);
            switch ($headersSimXml->Ack) {
                case 'Failure':
                    if (isset($this->ebayApiTaskModel)) {
                        $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $headersSimXml->asXML() . ']';
                        $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                        $this->ebayApiTaskModel->exec_status = 2;
                        $this->ebayApiTaskModel->status = 1;
                        $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                        $this->ebayApiTaskModel->save();
                        $this->errorCode++;
                    }
                    throw new Exception('getMessagesHeaders时Ack:Failure');
                    break;
                case 'Warning':
                    if (isset($this->ebayApiTaskModel)) {
                        $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $headersSimXml->Errors->asXML() . ']';
                        $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                        $this->ebayApiTaskModel->exec_status = 1;
                        if ($this->ebayApiTaskModel->status > 2 || empty($this->ebayApiTaskModel->status))
                            $this->ebayApiTaskModel->status = 2;
                        $this->errorCode++;
                        $this->ebayApiTaskModel->save();
                    }
            }
            $headersMessages = $headersSimXml->Messages->Message;
            if (empty($headersMessages)) {
                if (isset($this->ebayApiTaskModel)) {
                    $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。message为空]';
                    $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                    $this->ebayApiTaskModel->exec_status = 2;
                    $this->ebayApiTaskModel->status = 2;
                    $this->ebayApiTaskModel->save();
                    $this->errorCode++;
                }
                return true;
            }
            foreach ($headersMessages as $headersMessage) {
                $messageID = $headersMessage->MessageID->__toString();
                //判断$messageID是否重复
                if (in_array($messageID, $this->oldMessageIDs)) {
                    $isRepetition = true;
                    break;
                }
                if (count($this->oldMessageIDs) > $this->EntriesPerPage) {
                    array_shift($this->oldMessageIDs);
                    $this->oldMessageIDs[] = $messageID;
                } else
                    $this->oldMessageIDs[] = $messageID;
                $message_is_exist = EbayInbox::find()->where(['message_id' => "$messageID"])->exists();
                if ($message_is_exist)
                    continue;
                $models[$messageID] = EbayInboxTmp::findOne(['message_id' => $messageID]);
                if (empty($models[$messageID])) {
                    $models[$messageID] = new EbayInboxTmp();
                    $models[$messageID]->account_id = $this->ebayApiTaskModel->account_id;
                    $models[$messageID]->message_id = $messageID;
                }
                $models[$messageID]->relation = $headersMessage->asXML();
            }
            if ($path)
                file_put_contents($path, ' time2:' . time(), FILE_APPEND);
            if (!empty($models)) {
                $this->MessageID = array_keys($models);
                if ($this->getMessagesContents()) {
                    $contentSimXml = simplexml_load_string($this->response);
                    switch ($contentSimXml->Ack) {
                        case 'Failure':
                            if (isset($this->ebayApiTaskModel)) {
                                $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $contentSimXml->asXML() . ']';
                                $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                                $this->ebayApiTaskModel->exec_status = 2;
                                $this->ebayApiTaskModel->status = 1;
                                $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                                $this->ebayApiTaskModel->save();
                                $this->errorCode++;
                            }
                            throw new Exception('getMessagesContents时Ack:Failure');
                            break;
                        case 'Warning':
                            if (isset($this->ebayApiTaskModel)) {
                                $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $contentSimXml->Errors->asXML() . ']';
                                $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                                $this->ebayApiTaskModel->exec_status = 1;
                                if ($this->ebayApiTaskModel->status > 2 || empty($this->ebayApiTaskModel->status))
                                    $this->ebayApiTaskModel->status = 2;
                                $this->ebayApiTaskModel->save();
                                $this->errorCode++;
                            }
                    }
                    $contentMessages = $contentSimXml->Messages->Message;

                    foreach ($contentMessages as $contentMessage) {
                        $messageID = $contentMessage->MessageID->__toString();
                        $models[$messageID]->relation_detail = $contentMessage->asXML();
                        $models[$messageID]->create_time = date('Y-m-d H:i:s');
                        $models[$messageID]->save();
                    }
                } else {
                    if (isset($this->ebayApiTaskModel)) {
                        $this->ebayApiTaskModel->sendContent .= empty($this->sendXml) ? '[' . $this->getParamsSerialize() . ']' : "[错误码：{$this->errorCode}。{$this->sendXml}]";
                        $this->ebayApiTaskModel->error .= '[错误码:' . $this->errorCode . '。ReturnMessages错误]';
                        $this->ebayApiTaskModel->exec_status = 2;
                        $this->ebayApiTaskModel->status = 1;
                        $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                        $this->ebayApiTaskModel->save();
                        $this->errorCode++;
                    }
                    throw new Exception('ReturnMessages错误');
                }
            }
            if ($path)
                file_put_contents($path, ' time3:' . time(), FILE_APPEND);
            if (!empty($headersMessages) && $headersMessages->count() == $this->EntriesPerPage && !$isRepetition) {
                $this->PageNumber++;
                $this->MessageID = null;
                $this->getMessages($path);
            } else {
                if (isset($this->ebayApiTaskModel)) {
                    $this->ebayApiTaskModel->exec_status = 2;
                    if (empty($this->ebayApiTaskModel->status))
                        $this->ebayApiTaskModel->status = 3;
                    $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                    $this->ebayApiTaskModel->save();
                }
            }
        }
        else {
            if (isset($this->ebayApiTaskModel)) {
                $this->ebayApiTaskModel->sendContent .= empty($this->sendXml) ? '[' . $this->getParamsSerialize() . ']' : "[错误码：{$this->errorCode}。{$this->sendXml}]";
                $this->ebayApiTaskModel->error .= '[错误码:' . $this->errorCode . '。ReturnHeaders错误]';
                $this->ebayApiTaskModel->exec_status = 2;
                $this->ebayApiTaskModel->status = 1;
                $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->ebayApiTaskModel->save();
                $this->errorCode++;
            }
            throw new Exception('ReturnHeaders错误');
        }
    }

    //测试地址
    public function getMessagescs($path = '') {
        if ($path)
            file_put_contents($path, ' ' . $this->PageNumber . '_start_time:' . time(), FILE_APPEND);
        if ($this->getMessagesHeaders()) {

            $isRepetition = false;
            $headersSimXml = simplexml_load_string($this->response);
            if ($path)
                file_put_contents($path, ' time1:' . time(), FILE_APPEND);
            switch ($headersSimXml->Ack) {
                case 'Failure':
                    if (isset($this->ebayApiTaskModel)) {
                        $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $headersSimXml->asXML() . ']';
                        $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                        $this->ebayApiTaskModel->exec_status = 2;
                        $this->ebayApiTaskModel->status = 1;
                        $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                        $this->ebayApiTaskModel->save();
                        $this->errorCode++;
                    }
                    throw new Exception('getMessagesHeaders时Ack:Failure');
                    break;
                case 'Warning':
                    if (isset($this->ebayApiTaskModel)) {
                        $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $headersSimXml->Errors->asXML() . ']';
                        $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                        $this->ebayApiTaskModel->exec_status = 1;
                        if ($this->ebayApiTaskModel->status > 2 || empty($this->ebayApiTaskModel->status))
                            $this->ebayApiTaskModel->status = 2;
                        $this->errorCode++;
                        $this->ebayApiTaskModel->save();
                    }
            }
            $headersMessages = $headersSimXml->Messages->Message;
            if (empty($headersMessages)) {
                if (isset($this->ebayApiTaskModel)) {
                    $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。message为空]';
                    $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                    $this->ebayApiTaskModel->exec_status = 2;
                    $this->ebayApiTaskModel->status = 2;
                    $this->ebayApiTaskModel->save();
                    $this->errorCode++;
                }
                return true;
            }
            foreach ($headersMessages as $headersMessage) {
                $messageID = $headersMessage->MessageID->__toString();
                //判断$messageID是否重复
                if (in_array($messageID, $this->oldMessageIDs)) {
                    $isRepetition = true;
                    break;
                }
                if (count($this->oldMessageIDs) > $this->EntriesPerPage) {
                    array_shift($this->oldMessageIDs);
                    $this->oldMessageIDs[] = $messageID;
                } else
                    $this->oldMessageIDs[] = $messageID;
                $message_is_exist = EbayInbox::find()->where(['message_id' => "$messageID"])->exists();
                if ($message_is_exist)
                    continue;
                $models[$messageID] = EbayInboxTmp::findOne(['message_id' => $messageID]);
                if (empty($models[$messageID])) {
                    $models[$messageID] = new EbayInboxTmp();
                    $models[$messageID]->account_id = $this->ebayApiTaskModel->account_id;
                    $models[$messageID]->message_id = $messageID;
                }
                $models[$messageID]->relation = $headersMessage->asXML();
            }
            if ($path)
                file_put_contents($path, ' time2:' . time(), FILE_APPEND);
            if (!empty($models)) {
                $this->MessageID = array_keys($models);
                if ($this->getMessagesContents()) {
                    $contentSimXml = simplexml_load_string($this->response);
                    switch ($contentSimXml->Ack) {
                        case 'Failure':
                            if (isset($this->ebayApiTaskModel)) {
                                $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $contentSimXml->asXML() . ']';
                                $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                                $this->ebayApiTaskModel->exec_status = 2;
                                $this->ebayApiTaskModel->status = 1;
                                $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                                $this->ebayApiTaskModel->save();
                                $this->errorCode++;
                            }
                            throw new Exception('getMessagesContents时Ack:Failure');
                            break;
                        case 'Warning':
                            if (isset($this->ebayApiTaskModel)) {
                                $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $contentSimXml->Errors->asXML() . ']';
                                $this->ebayApiTaskModel->sendContent .= "[错误码：{$this->errorCode}。{$this->sendXml}]";
                                $this->ebayApiTaskModel->exec_status = 1;
                                if ($this->ebayApiTaskModel->status > 2 || empty($this->ebayApiTaskModel->status))
                                    $this->ebayApiTaskModel->status = 2;
                                $this->ebayApiTaskModel->save();
                                $this->errorCode++;
                            }
                    }
                    $contentMessages = $contentSimXml->Messages->Message;

                    foreach ($contentMessages as $contentMessage) {
                        $messageID = $contentMessage->MessageID->__toString();
                        $models[$messageID]->relation_detail = $contentMessage->asXML();
                        $models[$messageID]->create_time = date('Y-m-d H:i:s');
                        $models[$messageID]->save();
                    }
                } else {
                    if (isset($this->ebayApiTaskModel)) {
                        $this->ebayApiTaskModel->sendContent .= empty($this->sendXml) ? '[' . $this->getParamsSerialize() . ']' : "[错误码：{$this->errorCode}。{$this->sendXml}]";
                        $this->ebayApiTaskModel->error .= '[错误码:' . $this->errorCode . '。ReturnMessages错误]';
                        $this->ebayApiTaskModel->exec_status = 2;
                        $this->ebayApiTaskModel->status = 1;
                        $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                        $this->ebayApiTaskModel->save();
                        $this->errorCode++;
                    }
                    throw new Exception('ReturnMessages错误');
                }
            }
            if ($path)
                file_put_contents($path, ' time3:' . time(), FILE_APPEND);
            if (!empty($headersMessages) && $headersMessages->count() == $this->EntriesPerPage && !$isRepetition) {
                $this->PageNumber++;
                $this->MessageID = null;
                $this->getMessages($path);
            } else {
                if (isset($this->ebayApiTaskModel)) {
                    $this->ebayApiTaskModel->exec_status = 2;
                    if (empty($this->ebayApiTaskModel->status))
                        $this->ebayApiTaskModel->status = 3;
                    $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                    $this->ebayApiTaskModel->save();
                }
            }
        }
        else {
            if (isset($this->ebayApiTaskModel)) {
                $this->ebayApiTaskModel->sendContent .= empty($this->sendXml) ? '[' . $this->getParamsSerialize() . ']' : "[错误码：{$this->errorCode}。{$this->sendXml}]";
                $this->ebayApiTaskModel->error .= '[错误码:' . $this->errorCode . '。ReturnHeaders错误]';
                $this->ebayApiTaskModel->exec_status = 2;
                $this->ebayApiTaskModel->status = 1;
                $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->ebayApiTaskModel->save();
                $this->errorCode++;
            }
            throw new Exception('ReturnHeaders错误');
        }
    }
    
    
    public function getMessagesHeaders() {
        $this->DetailLevel = 'ReturnHeaders';
        $this->setRequest()->sendHttpRequest();
        return $this->requestStatus && !empty($this->response);
    }

    public function getMessagesContents() {
        $this->DetailLevel = 'ReturnMessages';
        $this->setRequest()->sendHttpRequest();
        return $this->requestStatus && !empty($this->response);
    }

    public function getMessagesSummary() {
        $this->DetailLevel = 'ReturnSummary';
        $this->setRequest()->sendHttpRequest();
        return $this->requestStatus && !empty($this->response);
    }

    public function setRequest() {
        $ebayKeys = \app\components\ConfigFactory::getConfig('ebayKeys');
        $this->_userToken = $this->ebayAccountModel->user_token;
        $this->appID = $ebayKeys['appID'];
        $this->devID = $ebayKeys['devID'];
        $this->certID = $ebayKeys['certID'];
        $this->serverUrl = $ebayKeys['serverUrl'];
        $this->compatabilityLevel = 983;
        $this->verb = 'GetMyMessages';
        return $this;
    }

    public function getSendXml() {
        return $this->sendXml;
    }

    public function getParamsSerialize() {
        return serialize(['EndTime' => $this->EndTime, 'FolderID' => $this->FolderID, 'IncludeHighPriorityMessageOnly' => $this->IncludeHighPriorityMessageOnly, 'MessageID' => $this->MessageID, 'StartTime' => $this->StartTime, 'DetailLevel' => $this->DetailLevel, 'EntriesPerPage' => $this->EntriesPerPage, 'PageNumber' => $this->PageNumber, 'errorCode' => $this->errorCode]);
    }

    public function requestXmlBody() {
        $this->sendXml = '';
        if (!$this->validate()) {
            if (isset($this->ebayApiTaskModel)) {
                $this->ebayApiTaskModel->error .= '[错误码：' . $this->errorCode . '。参数字段错误。';
                $errors = $this->getErrors();
                foreach ($errors as $error) {
                    $key = key($error);
                    $this->ebayApiTaskModel->error .= $key . '(' . $this->$key . '):' . current($error) . '。';
                }
                $this->ebayApiTaskModel->error .= ']';
                $this->ebayApiTaskModel->sendContent .= '[' . $this->getParamsSerialize() . ']';
                $this->ebayApiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->ebayApiTaskModel->exec_status = 2;
                $this->ebayApiTaskModel->status = 1;
                $this->ebayApiTaskModel->save();
                $this->errorCode++;
            }
            throw new \Exception('参数字段错误.');
        }
        $this->sendXml = '<?xml version="1.0" encoding="utf-8" ?>';
        $this->sendXml .= '<GetMyMessagesRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $this->sendXml .= "<RequesterCredentials><eBayAuthToken>{$this->_userToken}</eBayAuthToken></RequesterCredentials>";
        $this->sendXml .= "<StartTime>{$this->StartTime}</StartTime>";
        $this->sendXml .= "<EndTime>{$this->EndTime}</EndTime>";
        if (isset($this->FolderID))
            $this->sendXml .= "<FolderID>{$this->FolderID}</FolderID>";
        if (isset($this->IncludeHighPriorityMessageOnly))
            $this->sendXml .= "<IncludeHighPriorityMessageOnly>{$this->IncludeHighPriorityMessageOnly}</IncludeHighPriorityMessageOnly>";
        if (isset($this->MessageID)) {
            $this->sendXml .= '<MessageIDs>';
            foreach ($this->MessageID as $messageId) {
                $this->sendXml .= "<MessageID>{$messageId}</MessageID>";
            }
            $this->sendXml .= '</MessageIDs>';
        }
        $this->sendXml .= '<Pagination>';
        $this->sendXml .= "<EntriesPerPage>{$this->EntriesPerPage}</EntriesPerPage>";
        $this->sendXml .= "<PageNumber>{$this->PageNumber}</PageNumber>";
        $this->sendXml .= '</Pagination>';
        $this->sendXml .= "<DetailLevel>{$this->DetailLevel}</DetailLevel>";
        $this->sendXml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
        $this->sendXml .= '<WarningLevel>High</WarningLevel>';
        $this->sendXml .= '</GetMyMessagesRequest>';
        return true;
    }

    public function getErrors() {
        return $this->errors;
    }

    public static function isTimeDate($var) {
        if (preg_match('/^\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-6]\d:[0-6]\d[.]\d{3}Z$/', $var))
            return true;
        else
            return false;
    }

    public static function isInt($var) {
        if (is_numeric($var) && $var % 1 === 0)
            return true;
        else
            return false;
    }

    public function validate() {
        $flag = true;
        $this->errors = [];
        if (isset($this->EndTime)) {
            if (!self::isTimeDate($this->EndTime)) {
                $this->errors[] = ['EndTime' => '时间格式不为2017-03-24T11:29:36.000Z'];
                $flag = false;
            }
        } else {
            $this->errors[] = ['EndTime' => '不能为空'];
            $flag = false;
        }
        if (isset($this->FolderID)) {
            if (!self::isInt($this->FolderID)) {
                $this->errors[] = ['FolderID' => '不为整数'];
                $flag = false;
            }
        }
        if (isset($this->IncludeHighPriorityMessageOnly)) {
            $this->IncludeHighPriorityMessageOnly = $this->IncludeHighPriorityMessageOnly ? 'true' : 'false';
        }

        if (isset($this->MessageID)) {
            if (is_array($this->MessageID)) {
                foreach ($this->MessageID as $messageId) {
                    if (!self::isInt($messageId)) {
                        $this->errors[] = ['MessageID' => '数组元素不为整数'];
                        $flag = false;
                        break;
                    }
                }
            } else {
                $this->errors[] = ['MessageID' => '不为数组'];
                $flag = false;
            }
        }
        if (isset($this->StartTime)) {
            if (!self::isTimeDate($this->StartTime)) {
                $this->errors[] = ['StartTime' => '开始时间格式错误'];
                $flag = false;
            }
        } else {
            $this->errors[] = ['StartTime' => '不能为空'];
            $flag = false;
        }

        if (isset($this->DetailLevel)) {
            if (!in_array($this->DetailLevel, self::$detailLevelValues)) {
                $this->errors[] = ['DetailLevel' => '时间格式不为2017-03-24T11:29:36.000Z'];
                $flag = false;
            }
        } else {
            $this->errors[] = ['DetailLevel' => '不能为空'];
            $flag = false;
        }
        if (isset($this->EntriesPerPage)) {
            if (!self::isInt($this->EntriesPerPage) || $this->EntriesPerPage < 1 || $this->EntriesPerPage > 10) {
                $this->errors[] = ['EntriesPerPage' => '不为大于0小于11的整数'];
                $flag = false;
            }
        }
        return $flag;
    }

}
