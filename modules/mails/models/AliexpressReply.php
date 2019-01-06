<?php

namespace app\modules\mails\models;
use app\common\VHelper;
use app\components\Model;
use Yii;
use app\modules\mails\models\AliexpressSummary;
use app\modules\mails\models\AliexpressFilepath;
use app\modules\mails\models\AliexpressInbox;
use app\modules\users\models\User;
use app\modules\mails\models\Reply;
use app\modules\accounts\models\Platform;
use app\modules\services\modules\aliexpress\models\UpdateMsgProcessed;
use app\modules\mails\models\AliexpressExpression;
use app\modules\services\modules\aliexpress\models\AliexpressMessage;
use app\modules\mails\models\AliexpressSkuList;
/**
 * This is the model class for table "{{%aliexpress_relation_list}}".
 *
 * @property integer $id
 * @property string $msg_sources
 * @property integer $unread_count
 * @property string $channel_id
 * @property string $last_message_id
 * @property integer $read_stat
 * @property string $ast_message_content
 * @property integer $ast_message_is_own
 * @property string $child_name
 * @property string $message_time
 * @property string $child_id
 * @property string $other_name
 * @property string $other_login_id
 * @property integer $deal_stat
 * @property integer $rank
 */
class AliexpressReply extends Reply
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_reply}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'inbox_id' => 'Inbox Id',
            'reply_content' => 'Reply Content',
            'reply_title' => 'Reply Title',
            'reply_by' => 'Reply By',
            'is_draft' => 'Is Draft',
            'is_delete' => 'Is Delete',
            'is_send' => 'Is Send',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'modify_by' => 'Modify By',
            'modify_time' => 'Modify Time',
            'gmt_create' => 'Gmt Create',
            'message_type' => 'Message_Type',
            'type_id' => 'Type Id',
            'reply_from' => 'Reply From',
        ];
    }

    /**
     * 获取回复列表
     * @param $accountId
     * @param $channelId
     * @return array
     */
    public function getReplyList($accountId,$channelId)
    {
        $list = self::find()->where(['account_id'=>$accountId,'channel_id'=>$channelId])
            ->orderBy([
                'gmt_create'=>SORT_DESC,
            ])->asArray()->all();
        $data = [];
        $productIds = [];
        $orderIds = [];
        if (!empty($list)){
            $expressionModel = new AliexpressExpression();
            $remarks = [];
            foreach ($list as $value){
                $typeId = $value['type_id'];
                if (!isset($remarks[$typeId]) || empty($remarks[$typeId]))
                    $remarks[$typeId] = $value['remark'];
                $value['reply_content'] = $expressionModel->queryExpression(nl2br($value['reply_content']));
                $value['summary'] = AliexpressSummary::find()->where(['reply_id'=>$value['id']])->asArray()->one(); 
                /////////////////////////////////////////////////////////////////////////////////////////////////////////
                $skus=AliexpressSkuList::find()->select('sku')->where(['product_id'=>$value['type_id']])->asArray()->one();
                $value['sku'] =$skus['sku']; 
                ////////////////////////////////////////////////////////////////////////////////////////////////////////
//                if(empty(  $value['summary'])){
//                    //
//                    $summary_data=AliexpressSummary::find()->where(['reply_from'=>1])->orderBy('id desc')->asArray()->one();
//                    $value['summary']= AliexpressSummary::find()->where(['reply_id'=>$value['id']])->asArray()->one();
//                }
                $value['filepath'] = AliexpressFilepath::find()->where(['reply_id'=>$value['id']])->asArray()->one();
                if($value['filepath']){
                    $value['fileImg'] = "";//小图
                    $value['fileBImg'] = "";//大图
                    if($value['filepath']['s_path']){
                        if(strstr($value['filepath']['s_path'],'http://')){
                            $value['fileImg'] = $value['filepath']['s_path'];
                        }else{
                            $value['fileImg'] = 'http://ae01.alicdn.com/kf/'.$value['filepath']['s_path'];
                        }
                    }

                    if($value['filepath']['l_path']){
                        if(strstr($value['filepath']['l_path'],'http://')){
                            $value['fileBImg'] = $value['filepath']['l_path'];
                        }else{
                            $value['fileBImg'] = 'http://ae01.alicdn.com/kf/'.$value['filepath']['l_path'];
                        }
                    }
                }
                //summary 图片处理
                if($value['summary']){
                    if($value['summary']['product_image_url']){
                        $value['summary']['product_image_url'] = VHelper::processingPic($value['summary']['product_image_url'],'120x120',TRUE);
                    }
                }
                if($value['message_type']=='product'){
                    $productIds[] = $value['type_id'];
                }
                if($value['message_type']=='order'){
                    $orderIds[] = $value['type_id'];
                }
                if(!empty($value['type_id'])){
                    $data[$value['type_id']][] = $value;
                    //排序问题
//                    array_multisort(array_column($data[$value['type_id']],'message_id'),SORT_DESC,$data[$value['type_id']]);
                }else{
                    $data[] = $value;
                }
            }
        } 
        foreach ($data as $k=>$v)
        {
            $summary = [];
            $reply_id=self::find()
                ->where(['account_id'=>$accountId,'channel_id'=>$channelId,'type_id'=>$k,'message_type'=>"product",'reply_from'=>2])
                ->asArray()->one();
            if(isset($reply_id)&&!empty($reply_id)){
                $summary = AliexpressSummary::find()
                    ->where(['reply_id'=>$reply_id['id']])
                    ->asArray()->one();
            }
          
            foreach($v as $key => $val){
                if(empty($val['summary']) && !empty($summary)){
                    $data[$k][$key]['summary'] = $summary;
                    if($data[$k][$key]['summary']['product_image_url']){
                        $data[$k][$key]['summary']['product_image_url'] = VHelper::processingPic($data[$k][$key]['summary']['product_image_url'],'120x120',TRUE);
                    }
                }
                if (is_array($val))
                {
                    if (isset($val['type_id']))
                        $data[$k][$key]['remark'] = $remarks[$val['type_id']];
                    else
                        $data[$k][$key]['remark'] = isset($val['remark']) ? $val['remark'] : '';
                }
                //else
                    //$data[$k][$key]['remark'] = '';
            }
        }
        $arrData = [
            'orderIds'=>$orderIds,
            'productIds'=>$productIds,
            'list'=>$data
        ];
        return $arrData;
    }
    /*添加*/
    public function getAdd($data){
        $aliexpressMessage = new AliexpressMessage();
        $filepathModel = new AliexpressFilepath();
        $dbTransaction = self::getDb()->beginTransaction();
        $aliexpress_reply=new AliexpressReply();
        try
        {
            /*标记为已处理*/
            $Inbox = AliexpressInbox::findOne(['channel_id'=>$data['channel_id']]);
            if($aliexpressMessage->updateMessageProcessingState($data['account_id'],$data['channel_id'],1)){
                $Inbox->deal_stat = 1;
            }else{
                throw new \Exception('Failed when marked as processed operation');
            }
            //标记为已读
            $aliexpressMessage->markMessageBeenRead($data['account_id'], $data['channel_id']);
            /*标记已回复*/
            $content = MailTemplateStrReplacement::replaceContent($data['content'], Platform::PLATFORM_CODE_ALI,
                $Inbox->getOrderId());
            //获取翻译后的内容
            $content_en=MailTemplateStrReplacement::replaceContent($data['content_en'], Platform::PLATFORM_CODE_ALI,
                $Inbox->getOrderId());
            $Inbox->is_replied = 1;
            $Expression = new AliexpressExpression();
            $data['content'] = $Expression->replyContentReplace($data['content']);
            $data['content_en'] = $Expression->replyContentReplace($data['content_en']);
            $User = User::findIdentity(\Yii::$app->user->id);
            $aliexpress_reply->inbox_id = $Inbox->id;
            $aliexpress_reply->channel_id = $data['channel_id'];
            $aliexpress_reply->reply_content = isset($content)?$content:$data['content'];
            //同一语种不插入reply_content_en
            if(strcmp($content,$content_en)!=0){
                //翻译后的内容
                $aliexpress_reply->reply_content_en = isset($content_en)?$content_en:$data['content_en'];
            }
            $aliexpress_reply->is_draft = 0;
            $aliexpress_reply->is_delete = 0;
            $aliexpress_reply->is_send = 0;
            $aliexpress_reply->message_type = !empty($data['message_type'])?$data['message_type']:'';
            $aliexpress_reply->type_id = !empty($data['type_id'])?$data['type_id']:'';
            $aliexpress_reply->reply_by = $User->login_name;
            $aliexpress_reply->gmt_create = date('Y-m-d H:i:s');
            $aliexpress_reply->account_id = $data['account_id'];
            $aliexpress_reply->reply_from = 1;
            $aliexpress_reply->sender_ali_id = $Inbox->child_id;
            $flag = $aliexpress_reply->save();
            if (!$flag)
                throw new \Exception('Save Reply Failed');
            if(!empty($data['imgPath']))
                $filepathModel->getInsert($aliexpress_reply->id,$data['imgPath']);
            $flag = $aliexpress_reply->sendToOutBox($Inbox);
            if (!$flag)
                throw new \Exception('Save Outbox Info Failed');
            $flag = $Inbox->save();
            if (!$flag)
                throw new \Exception('Save Inbox Failed');
            $dbTransaction->commit();
            /*保存已回复状态*/
            return true;
        }
        catch (\Exception $e)
        {
            echo '<pre>';
            var_dump($e->getMessage());
            echo '</pre>';
            $dbTransaction->rollBack();
            return false;
        }
    }

    /**
     * @desc 获取添加到发件箱参数
     * @param unknown $inbox
     * @return \yii\helpers\string
     */
    public function getSendParams($inbox)
    {
        $sendParams = '';
        try
        {
            $sendParams = [
                'account_id' => $inbox->account_id,
                'channel_id' => $inbox->channel_id,
                'message_type' => $this->message_type,
                'buyer_id' => $inbox->other_login_id,
                'imgPath' => $this->getImgPath(),
                'extern_id' => $this->type_id,
            ];
            $sendParams = \yii\helpers\Json::encode($sendParams);
        }
        catch (\Exception $e)
        {
            //echo $e->getMessage();exit;
        }
        return $sendParams;
    }

    /**
     * @desc 获取回复内容
     */
    public function getReplyContent()
    {
        if(!empty($this->reply_content_en)&&strcmp($this->reply_content,$this->reply_content_en)!=0){
            //添加翻译后的内容
            return $this->reply_content_en."\n\n".$this->reply_content;
        }
        return $this->reply_content;
    }
    /**
     * @desc 获取回复图片
     */
    public function getImgPath()
    {
        $replyId = $this->id;
        $imageInfo = AliexpressFilepath::findOne(['reply_id' => $replyId]);
        if (!empty($imageInfo))
            return $imageInfo->l_path;
        return '';
    }

    /**
     * @desc 获取回复标题
     */
    public function getSubject()
    {
        return $this->reply_title;
    }

    /**
     * @desc 获取所属平台
     * @return string
     */
    public function getPlatformCode()
    {
        return \app\modules\accounts\models\Platform::PLATFORM_CODE_ALI;
    }

    /**
     * @desc 添加回复
     * @param unknown $data
     * @return boolean|number
     */
    public function saveReply($inbox, $relyData){
        $this->inbox_id = $inbox->id;
        $this->channel_id = $inbox->channel_id;
        $this->reply_content = $relyData['content'];
        $this->reply_by = $relyData['reply_by'];
        $this->is_draft = 0;
        $this->is_delete = 0;
        $this->is_send = 0;
        $this->gmt_create = date('Y-m-d H:i:s');
        $this->account_id = $inbox->account_id;
        $this->reply_from = 1;
        $flag = $this->save();
        if (!$flag)
            return false;
        return $this;
    }

    public function getInboxIdByPlatOrderId($platOrderId){
        //$sql = 'SELECT DISTINCT (inbox_id) id FROM {{%crm}}.{{%aliexpress_reply}} WHERE message_type = "order" AND type_id = "'.$platOrderId.'"';
        $res = (new \yii\db\Query())
            ->select(['inbox_id'])
            ->from('{{%aliexpress_reply}}')
            ->where(['message_type' => 'order','type_id'=>$platOrderId])
            ->one();
        // $res = $this->findBySql($sql)->one();
        if(!empty($res)){
            return $res['inbox_id'];
        }else{
            return false;
        }
    }

    /**
     * 根据inbox_id获取message_type类型为member的remark站内信备注
     * @param $inbox_id
     * @return array
     */
    public function getRemark($inbox_id)
    {
        $list = self::find()->where(['inbox_id'=>$inbox_id,'reply_from'=>1])->orderBy(['create_time'=>SORT_DESC,])->asArray()->all();

        if (!empty($list)){
            $remarkData['list'] = array_column($list, 'remark','id');

        }else{
            $remarkData['list'] =[];
        }
        $remarkData['inbox_id'] = $inbox_id;
        return $remarkData;
    }    
}
