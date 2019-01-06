<?php
namespace app\modules\services\modules\aliexpress\models;
use app\common\VHelper;
use app\modules\mails\models\AliexpressTask;
use app\modules\mails\models\AliexpressInbox;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\services\modules\aliexpress\models\MsgDetailList;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\accounts\models\Account;
use app\modules\systems\models\ErpAccountApi;
use app\modules\accounts\models\Platform;
use app\modules\users\models\User;
use app\modules\mails\models\AliexpressInboxTmp;
/**
 * 站内信
 */
class QueryMsgDetailList
{
    protected $access;
    protected $app_key;
    protected $secret_key;
    protected $_taskId = 0;
    protected $_account_id = '';
    protected $_shortName = '';
    protected $_totalNumber = 0;
    protected $state = 0;
    protected $_errorMessage = '';

    /**
     * 通过帐号获取栏目列表
     * @param $account
     */
    public function getMsgList($accountId)
    {
        //实例话这个模型
        $aliexpressTaskModel = new AliexpressTask();
        if ($aliexpressTaskModel->checkIsRunning($accountId, 'Aliexpressmail'))
            exit('Task Running');

        try
        {
            $taskId = $aliexpressTaskModel->getAdd($accountId,'Aliexpressmail');
            //set task_id
            $this->_taskId = $taskId;
            $account = Account::findById($accountId);
            if (empty($account)) return false;
            $accountName = $account->account_name;
            $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_ALI, $accountName);
            if (empty($erpAccount))
            {
                $TaskModel = AliexpressTask::find()->where(['id'=>$this->_taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = 'Get Account Info Failed';
                $TaskModel->save();
                return false;
            }
            //set account_id
            $this->_account_id = $accountId;
            $this->_shortName = $erpAccount->short_name;
            $this->access =  $erpAccount->access_token;
            $this->app_key = $erpAccount->app_key;
            $this->secret_key = $erpAccount->secret_key;
            $TaskModel = AliexpressTask::find()->where(['id'=>$this->_taskId])->one();
            $TaskModel->status = 1;
            $TaskModel->save();
            // start to catch order
            $flag = $this->getProductResponseByShortName();
            if (!$flag)
            {
                $TaskModel = AliexpressTask::find()->where(['id'=>$this->_taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = $this->_errorMessage;
                $TaskModel->save();
                return false;
            }
            else
            {
                $TaskModel = AliexpressTask::find()->where(['id'=>$this->_taskId])->one();
                $TaskModel->status = 2;
                $TaskModel->save();
                return true;
            }
        }
        catch (\Exception $e)
        {
            $TaskModel = AliexpressTask::find()->where(['id'=>$this->_taskId])->one();
            $TaskModel->status = -1;
            $TaskModel->errors = $e->getMessage();
            $TaskModel->save();
            return false;            
        }
    }

   public function getProductResponseByShortName($type = 'message_center',$pagenum = 1)
    {
        //实例化这个类
        $orderObj = new MsgDetailList();
        //构造参数
        $response_s = $orderObj
            ->setPage($pagenum)
            ->setNum($orderObj->getNum())
            ->setAccessToken($this->access)
            ->putOtherTextParam('app_key', $this->app_key)
            ->putOtherTextParam('secret_key', $this->secret_key)
            ->putOtherTextParam('msgSources', $type)
            ->putOtherTextParam('filter', 'readStat');//按未读筛选

        //引入发送类
        $client = new AliexpressApi();

        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        $flag = false;
        try{
            if (!empty($response->result)) {
                $User = User::findIdentity(\Yii::$app->user->id);
                /*实例化回复表*/
                $detailReply = new Detail();
                $detailReply->access($this->access);
                $detailReply->appKey($this->app_key);
                $detailReply->secretKey($this->secret_key);
                $detailReply->shortName($this->_shortName);
                $detailReply->taskId($this->_taskId);
                foreach ($response->result as  $v)
                {
                    $aliexpressInbox = AliexpressInboxTmp::findOne(['channel_id' => $v->channelId]);
                    if(empty($aliexpressInbox)){
                        $aliexpressInbox = new AliexpressInboxTmp();
                        $aliexpressInbox->account_id = $this->_account_id;
                        $aliexpressInbox->channel_id = $v->channelId;
                        $aliexpressInbox->type = $type;
                    }
                    $aliexpressInbox->relation = $v;
                    //$cats = $aliexpressInbox->save();
                    //$primaryKey = $relationList->primaryKey?$relationList->primaryKey:$relationListModel->primaryKey;
                    /*获取站信新内容*/
                    $relationDetails = [];
                    $detailReply->getRelationDetails($v->channelId,$type,1, $relationDetails);
                    if (empty($relationDetails))
                    {   continue;
/*                         $this->_errorMessage = 'Get Relation Detials Failed';
                        return false; */
                    }
                    $aliexpressInbox->relation_detail = $relationDetails;
                    $aliexpressInbox->create_time = date('Y-m-d H:i:s');
                    $flag = $aliexpressInbox->save(false);
                }
                $pagenum++;
                $this->getProductResponseByShortName($type,$pagenum);
            }else{
                /*如果报错*/
                if(!empty($response) && !empty($response->error_code)){
                    //如果接口提示未授权，清空账号缓存
                    if ($response->error_code == '401')
                    {
                        if (isset(\Yii::$app->memcache))
                            \Yii::$app->memcache->flush('erp_account');
                    }
                    $this->_errorMessage = $response->error_message;
                    return false;
                }
                else{
                    if($this->state){
                        if ($flag) {
                            return true;
                        } else {
                            $this->_errorMessage = 'No Datas';
                            return false;
                        }
                    }else{
                        $this->state = 1;
                        $this->getProductResponseByShortName('order_msg',1);
                    }
                }
            }
            return $flag;
        } catch (\Exception $e) {
            $this->_errorMessage = $e->getMessage();
            return false;
        }
    }
    
    public function getProductResponseByShortNameBak($type = 'message_center',$pagenum = 1)
    {
        //实例化这个类
        $orderObj = new MsgDetailList();
        //构造参数
        $response_s = $orderObj
        ->setPage($pagenum)
        ->setNum($orderObj->getNum())
        ->setAccessToken($this->access)
        ->putOtherTextParam('app_key', $this->app_key)
        ->putOtherTextParam('secret_key', $this->secret_key)
        ->putOtherTextParam('msgSources', $type)
        ->putOtherTextParam('filter', 'readStat');//按未读筛选
        //引入发送类
        $client = new AliexpressApi();
    
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        $cats = false;
        try{
            if (!empty($response->result)) {
                $User = User::findIdentity(\Yii::$app->user->id);
                /*实例化回复表*/
                $detailReply = new Detail();
                $detailReply->access($this->access);
                $detailReply->appKey($this->app_key);
                $detailReply->secretKey($this->secret_key);
                $detailReply->shortName($this->_shortName);
                $detailReply->taskId($this->_taskId);
                foreach ($response->result as  $v)
                {
                    $relationList = AliexpressInbox::findOne(['channel_id'=>$v->channelId,'msg_sources'=>$type]);
                    if(empty($relationList)){
                        $relationListModel = new AliexpressInbox();
                        $relationListModel->msg_sources = $type;
                        $relationListModel->unread_count = $v->unreadCount;
                        $relationListModel->account_id = $this->_account_id;
                        $relationListModel->channel_id = $v->channelId;
                        $relationListModel->last_message_id = $v->lastMessageId;
                        $relationListModel->read_stat = $v->readStat;
                        $relationListModel->last_message_content = $v->lastMessageContent;
                        $relationListModel->last_message_is_own = isset($v->lastMessageIsOwn)?$v->lastMessageIsOwn:0;
                        $relationListModel->child_name = isset($v->childName)?$v->childName:'';
                        $relationListModel->message_time = date('Y-m-d H:i:s',substr($v->messageTime,0,-3));
                        $relationListModel->child_id = $v->childId;
                        $relationListModel->other_name = $v->otherName;
                        $relationListModel->other_login_id = $v->otherLoginId;
                        $relationListModel->deal_stat = $v->dealStat;
                        $relationListModel->rank = $v->rank;
                        $relationListModel->create_by = $User->user_name;
                        $relationListModel->create_time = date('Y-m-d H:i:s');
                        $cats = $relationListModel->save();
                    }else{
                        $relationList->msg_sources = $type;
                        $relationList->unread_count = $v->unreadCount;
                        $relationList->channel_id = $v->channelId;
                        $relationList->last_message_id = $v->lastMessageId;
                        $relationList->read_stat = $v->readStat;
                        $relationList->account_id = $this->_account_id;
                        $relationList->last_message_content = $v->lastMessageContent;
                        $relationList->last_message_is_own = isset($v->lastMessageIsOwn)?$v->lastMessageIsOwn:0;
                        $relationList->child_name = isset($v->childName)?$v->childName:'';
                        $relationList->message_time = date('Y-m-d H:i:s',substr($v->messageTime,0,-3));
                        $relationList->child_id = $v->childId;
                        $relationList->other_name = $v->otherName;
                        $relationList->other_login_id = $v->otherLoginId;
                        $relationList->deal_stat = $v->dealStat;
                        $relationList->rank = $v->rank;
                        $relationList->modify_by = $User->user_name;
                        $relationList->modify_time = date('Y-m-d H:i:s');
                        $cats = $relationList->save();
                    }
                    $primaryKey = $relationList->primaryKey?$relationList->primaryKey:$relationListModel->primaryKey;
                    /*获取站信新内容*/
                    $detailReply->formattedSendMessage($v->channelId,$type,1,$primaryKey,false,$v->otherName);
                }
                $pagenum++;
                $this->getProductResponseByShortName($type,$pagenum);
            }else{
                /*如果报错*/
                if(!empty($response) && !empty($response->error_code)){
                    $this->_errorMessage = $response->error_message;
                    return false;
                }else{
                    if($this->state){
                        if ($cats) {
                            $this->_errorMessage = 'Try ErrorMessage';
                            return false;
                        } else {
                            return true;
                        }
                    }else{
                        $this->state = 1;
                        $this->getProductResponseByShortName('order_msg',1);
                    }
                }
            }
            return $cats;
        } catch (\Exception $e) {
            $this->_errorMessage = $e->getMessage();
            return false;
        }
    }
}