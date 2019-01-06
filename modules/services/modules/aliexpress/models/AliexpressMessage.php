<?php
/**
 * @desc 速卖通消息model
 */

namespace app\modules\services\modules\aliexpress\models;

use app\modules\mails\models\AliexpressTask;
use app\modules\services\modules\aliexpress\components\TaobaoQimenApi;
use app\modules\mails\models\AliexpressInboxTmp;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\mails\models\AliexpressInbox;
use app\modules\systems\models\AliexpressLog;

class AliexpressMessage
{
    public $errorMessage = null;

    /**
     * @desc 获取账号下的未读邮件
     * @param unknown $accountId
     * @return boolean
     */
    public function getAccountMessage($accountId)
    {
        try {
            //实例化任务日志类
            $aliexpressTaskModel = new AliexpressTask();
            $taskId              = $aliexpressTaskModel->getAdd($accountId, 'Aliexpressmail');
            if ($aliexpressTaskModel->checkIsRunning($accountId, 'Aliexpressmail')) {
                $TaskModel         = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = 'Task Running';
                $TaskModel->save();
                return false;
            }
            $accountInfo = Account::findById($accountId);
            if (empty($accountInfo)) {
                $TaskModel         = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = '账号不存在';
                $TaskModel->save();
                return false;
            }
            $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
            if (empty($erpAccountInfo)) {
                $TaskModel         = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = 'ERP系统对应账号不存在';
                $TaskModel->save();
                return false;
            }
            $TaskModel         = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = 1;
            $TaskModel->save();
            // start to catch order
            $page     = 1;
            $pageSize = 100;
            do {
                $messageList = [];
                $result      = self::queryMessageRelationList($erpAccountInfo, $page, $pageSize);
                if ($result === false) {
                    $TaskModel         = AliexpressTask::find()->where(['id' => $taskId])->one();
                    $TaskModel->status = -1;
                    $TaskModel->errors = $this->errorMessage;
                    $TaskModel->save();
                    return false;
                }
                if (isset($result->relation_list) && isset($result->relation_list->relation_result))
                    $messageList = $result->relation_list->relation_result;
                foreach ($messageList as $message) {
                    $aliexpressInbox = AliexpressInboxTmp::findOne(['channel_id' => $message->channel_id]);
                    if (empty($aliexpressInbox)) {
                        $aliexpressInbox             = new AliexpressInboxTmp();
                        $aliexpressInbox->account_id = $accountId;
                        $aliexpressInbox->channel_id = $message->channel_id;
                    }
                    $aliexpressInbox->relation = $message;
                    //$cats = $aliexpressInbox->save();
                    //$primaryKey = $relationList->primaryKey?$relationList->primaryKey:$relationListModel->primaryKey;
                    /*获取站信新内容*/
                    $relationDetails = [];
                    $relationDetails = self::queryMessagequerydetails($erpAccountInfo, $message->channel_id);
                    if (empty($relationDetails))
                        continue;
                    $aliexpressInbox->relation_detail = $relationDetails;
                    $aliexpressInbox->create_time     = date('Y-m-d H:i:s');
                    $flag                             = $aliexpressInbox->save(false);
                }
                $page++;
            } while ($messageList);

            $TaskModel         = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = 2;
            $TaskModel->save();
            return true;
        } catch (\Exception $e) {
            $TaskModel         = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = -1;
            $TaskModel->errors = $e->getMessage();
            $TaskModel->save();
            return false;
        }
    }

    /**
     * @desc 获取消息关系列表
     * @param unknown $erpAccountInfo
     * @param number $page
     * @param number $pageSize
     * @return boolean|\app\modules\services\modules\aliexpress\components\unknown
     */
    public function queryMessageRelationList($erpAccountInfo, $page = 1, $pageSize = 50)
    {
        $page           = (int)$page;
        $pageSize       = (int)$pageSize;
        $appKey         = $erpAccountInfo->app_key;
        $secretKey      = $erpAccountInfo->secret_key;
        $accessToken    = $erpAccountInfo->access_token;
        $erpAccountId   = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        $request        = new \MinxinAliexpressMessageliststationRequest;
        $request->setAccountId($erpAccountId);
        //$request->setOnlyUnReaded('true');
        $request->setOnlyUnDealed('true');
        $request->setCurrentPage($page);
        $request->setPageSize($pageSize);
        $response = $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            return false;
        }
        $result = $taobaoQimenApi->getResponse();
        return $result;
    }

    /**
     * @desc 获取消息详情
     * @param unknown $erpAccountInfo
     * @param unknown $channelId
     * @return boolean|multitype:|\app\modules\services\modules\aliexpress\components\unknown
     */
    public function queryMessagequerydetails($erpAccountInfo, $channelId)
    {
        $page           = 1;
        $pageSize       = 100;
        $detailList     = [];
        $appKey         = $erpAccountInfo->app_key;
        $secretKey      = $erpAccountInfo->secret_key;
        $accessToken    = $erpAccountInfo->access_token;
        $erpAccountId   = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        do {
            $messageDetailList = [];
            $request           = new \MinxinAliexpressMessagequerydetailslistRequest();
            $request->setAccountId($erpAccountId);
            $request->setChannelId($channelId);
            $request->setCurrentPage($page);
            $request->setPageSize($pageSize);
            $response = $taobaoQimenApi->doRequest($request);
            if (!$taobaoQimenApi->isSuccess()) {
                $this->errorMessage = $taobaoQimenApi->getErrorMessage();
                return false;
            }
            $result = $taobaoQimenApi->getResponse();
            if (isset($result->message_detail_list)) {
                if (isset($result->message_detail_list->message_detail))
                    $messageDetailList = $result->message_detail_list->message_detail;
                if (empty($messageDetailList))
                    return $detailList;
                $detailList = array_merge($detailList, $messageDetailList);
            }
            $page++;
        } while ($messageDetailList);
        return $result;
    }

    /**
     * @desc 更新消息的处理状态
     * @param unknown $erpAccountInfo
     * @param unknown $channelId
     * @param number $status
     * @return boolean|\app\modules\services\modules\aliexpress\components\unknown
     */
    public function updateMessageProcessingState($accountId, $channelId, $status = 0)
    {
        $accountInfo = Account::findById($accountId);
        if (empty($accountInfo)) {
            $this->errorMessage = '账号不存在';
            return false;
        }
        $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'ERP系统对应账号不存在';
            return false;
        }
        $appKey         = $erpAccountInfo->app_key;
        $secretKey      = $erpAccountInfo->secret_key;
        $accessToken    = $erpAccountInfo->access_token;
        $erpAccountId   = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        $request        = new \MinxinAliexpressMessageupdateprocessingstateRequest;
        $request->setAccountId($erpAccountId);
        $request->setChannelId($channelId);
        $request->setDealStat((int)$status);
        $response = $taobaoQimenApi->doRequest($request);
        $stat     = ['未处理', '已处理'];
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage       = $taobaoQimenApi->getErrorMessage();
            $aliexpressLogModel       = new AliexpressLog();
            $data['create_user_name'] = \Yii::$app->user->id;
            $data['channel_id']       = $channelId;
            $data['account_id']       = $accountId;
            $data['update_content']   = '更新已处理时ID为' . $channelId . '站内信/订单留言更新处理状态时接口报错，错误信息为：' .
                $this->errorMessage;
            $data['create_time']      = date('Y-m-d H:i:s');
            $aliexpressLogModel->getAdd($data);
            $this->errorMsg = $response->error_message;
            return false;
        }
        $aliexpressLogModel       = new AliexpressLog();
        $data['create_user_name'] = \Yii::$app->user->id;
        $data['channel_id']       = $channelId;
        $data['account_id']       = $accountId;
        $data['update_content']   = '更新了关系ID为' . $channelId . '站内信/订单留言更新处理状态为' . $stat[$status];
        $data['create_time']      = date('Y-m-d H:i:s');
        $aliexpressLogModel->getAdd($data);
        $AliexpressInbox = AliexpressInbox::findOne(['channel_id' => $channelId]);
        //更新处理状态
        $AliexpressInbox->deal_stat = 1;
        $AliexpressInbox->save();
        return true;
    }

    /**
     * @desc 将消息标记成已读
     * @param unknown $erpAccountInfo
     * @param unknown $channelId
     * @return boolean|\app\modules\services\modules\aliexpress\components\unknown
     */
    public function markMessageBeenRead($accountId, $channelId)
    {
        $accountInfo = Account::findById($accountId);
        if (empty($accountInfo)) {
            $this->errorMessage = '账号不存在';
            return false;
        }
        $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'ERP系统对应账号不存在';
            return false;
        }
        $appKey         = $erpAccountInfo->app_key;
        $secretKey      = $erpAccountInfo->secret_key;
        $accessToken    = $erpAccountInfo->access_token;
        $erpAccountId   = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        $request        = new \MinxinAliexpressOrdermessageupdatehasbeenreadRequest;
        $request->setAccountId($erpAccountId);
        $request->setChannelId($channelId);
        $response = $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage       = $taobaoQimenApi->getErrorMessage();
            $aliexpressLogModel       = new AliexpressLog();
            $data['create_user_name'] = \Yii::$app->user->id;
            $data['channel_id']       = $channelId;
            $data['account_id']       = $accountId;
            $data['update_content']   = '更新关系ID为' . $channelId . '站内信/订单留言更新处理状态时接口报错，错误信息为：' . $this->errorMessage;
            $data['create_time']      = date('Y-m-d H:i:s');
            $aliexpressLogModel->getAdd($data);
            $AliexpressInbox               = AliexpressInbox::findOne(['channel_id' => $channelId]);
            $AliexpressInbox->read_stat    = 2;
            $AliexpressInbox->unread_count = 0;
            $AliexpressInbox->save();
            return false;
        }
        $aliexpressLogModel       = new AliexpressLog();
        $data['create_user_name'] = \Yii::$app->user->id;
        $data['channel_id']       = $channelId;
        $data['account_id']       = $accountId;
        $data['update_content']   = '更新了关系ID为' . $channelId . '站内信/订单留言更新处理状态为已读';
        $data['create_time']      = date('Y-m-d H:i:s');
        $aliexpressLogModel->getAdd($data);

        //更新成功则更新这条关系
        // $detail = new Detail();
        $AliexpressInbox = AliexpressInbox::findOne(['channel_id' => $channelId]);
        //$detail->getProductResponseByShortName($AliexpressInbox->channel_id,$AliexpressInbox->msg_sources,1,$AliexpressInbox->id);
        $AliexpressInbox->read_stat    = 1;
        $AliexpressInbox->unread_count = 0;
        $AliexpressInbox->save();
        return true;
    }

    /**
     * @desc 获取错误信息
     * @return Ambigous <string, \app\modules\services\modules\aliexpress\components\string>
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @desc 添加消息
     * @param unknown $data
     * @return boolean
     */
    public function addMessage($data)
    {
        $accountId   = isset($data['account_id']) ? $data['account_id'] : '';
        $accountInfo = Account::findById($accountId);
        if (empty($accountInfo)) {
            $this->errorMessage = '账号不存在';
            return false;
        }
        $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'ERP系统对应账号不存在';
            return false;
        }
        $appKey         = $erpAccountInfo->app_key;
        $secretKey      = $erpAccountInfo->secret_key;
        $accessToken    = $erpAccountInfo->access_token;
        $erpAccountId   = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        $request        = new \MinxinAliexpressNewstationletterRequest;
        $request->setAccountId($erpAccountId);
        $request->setMessageType($data['message_type']);
        $request->setContent($data['content']);
        $request->setBuyerId($data['buyer_id']);
        $request->setSellerId($accountInfo->seller_id);
        if (!empty($data['imgPath']))
            $request->setImgPath($data['imgPath']);//图片地址
        if (!empty($data['extern_id']))
            $request->setExternId($data['extern_id']);
        $response = $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            /*状态更新失败*/
            $aliexpressLogModel          = new AliexpressLog();
            $daraArr['create_user_name'] = 'system';
            $daraArr['channel_id']       = $data['channel_id'];
            $daraArr['account_id']       = $accountId;
            $daraArr['update_content']   = $this->errorMessage;
            $daraArr['create_time']      = date('Y-m-d H:i:s');
            $aliexpressLogModel->getAdd($daraArr);
            return false;
        }
        $aliexpressLogModel      = new AliexpressLog();
        $Log['create_user_name'] = 'system';
        $Log['channel_id']       = $data['channel_id'];
        $Log['account_id']       = $accountId;
        $Log['update_content']   = '回复成功！';
        $Log['create_time']      = date('Y-m-d H:i:s');
        $aliexpressLogModel->getAdd($Log);
        return true;
    }

    /**
     * @desc 上传图片
     * @param $accountId
     * @param $fileName
     * @param $filePath
     * @return bool
     */
    public function uploadImage($accountId, $fileName, $filePath)
    {
        $accountInfo = Account::findById($accountId);
        if (empty($accountInfo)) {
            $this->errorMessage = '账号不存在';
            return false;
        }
        $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'ERP系统对应账号不存在';
            return false;
        }
        $appKey         = $erpAccountInfo->app_key;
        $secretKey      = $erpAccountInfo->secret_key;
        $accessToken    = $erpAccountInfo->access_token;
        $erpAccountId   = $erpAccountInfo->id;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);
        $request        = new \MinxinAliexpressCustomerUploadimageforsdkRequest;
        $request->setAccountId($erpAccountId);
        $request->setFileName($fileName);
        $request->setImageBytes(base64_encode(file_get_contents($filePath)));
        $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $aliexpressLogModel      = new AliexpressLog();
            $arr['create_user_name'] = 'system';
            $arr['account_id']       = $accountId;
            $arr['update_content']   = '上传失败，错误代码为：' . $taobaoQimenApi->getErrorMessage();
            $arr['create_time']      = date('Y-m-d H:i:s');
            $arr['channel_id']       = '';
            $aliexpressLogModel->getAdd($arr);
            $this->errorMsg = $taobaoQimenApi->getErrorMessage();
            return false;
        } else {
            /*上传成功*/
            $aliexpressLogModel      = new AliexpressLog();
            $Log['create_user_name'] = 'system';
            $Log['account_id']       = $accountId;
            $Log['update_content']   = '上传成功！';
            $Log['create_time']      = date('Y-m-d H:i:s');
            $Log['channel_id']       = '';
            $aliexpressLogModel->getAdd($Log);
            $result = $taobaoQimenApi->getResponse();
            //转换成数组
            $data = json_decode(json_encode($result), true);
            return $data['photobank_url'];
        }
    }
}