<?php
namespace app\modules\services\modules\aliexpress\models;
use app\common\VHelper;
use app\modules\mails\models\AliexpressTask;
use app\modules\mails\models\AliexpressReply;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\mails\models\AliexpressSummary;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\mails\models\AliexpressInbox;
use app\modules\mails\models\AliexpressFilepath;
/**
 * 站内信
 */
class Detail
{
    protected $access;
    protected $app_key;
    protected $secret_key;
    protected $_taskId = 0;
    protected $_account_id = '';
    protected $_shortName = '';
    protected $_totalNumber = 0;
    protected $parent_id = 0;

    public function access($access){
        $this->access = $access;
    }
    public function appKey($appKey){
        $this->app_key = $appKey;
    }
    public function secretKey($secretKey){
        $this->secret_key = $secretKey;
    }
    public function taskId($taskId){
        $this->_taskId = $taskId;
    }
    public function shortName($shortName){
        $this->_shortName = $shortName;
    }
    /**
     * 通过帐号获取栏目列表
     * @param $account
     */
    public function getDetailList($account)
    {
        //实例话这个模型
        $aliexpressTaskModel = new AliexpressTask();
        $taskId = $aliexpressTaskModel->getAdd($account,'AliexpressDetailList');
        //set task_id
        $this->_taskId = $taskId;
        //set account_id
        $this->_account_id = $account;
        $shortName = AliexpressAccount::findOne(['id'=>$account]);
        $this->_shortName = $shortName->short_name;
        $this->access =  $shortName->access_token;
        $this->app_key = $shortName->app_key;
        $this->secret_key = $shortName->secret_key;
        $relationList = AliexpressInbox::find()->select('id,channel_id,msg_sources,other_name')
            ->where(['account_id'=>$account])
            ->asArray()->all();
        if(!empty($relationList)){
            foreach ($relationList as $value){
                $this->formattedSendMessage($value['channel_id'],$value['msg_sources'],1,$value['id'],false,$value['other_name']);
            }
        }
        // start to catch order
    }
    
    public function getRelationDetails($channelId, $msgSources, $page, &$msgList)
    {
        $orderObj = new WhereDetail();
        $response_s = $orderObj
        ->setPage($page)
        ->setNum($orderObj->getNum())
        ->setAccessToken($this->access)
        ->putOtherTextParam('app_key', $this->app_key)
        ->putOtherTextParam('secret_key', $this->secret_key)
        ->putOtherTextParam('msgSources', $msgSources)
        ->putOtherTextParam('channelId', $channelId);
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        $cats = false;
        if (!empty($response->result)) {
            $msgList = array_merge($msgList, $this->stdClassObjectToArray($response->result));
            $page++;
            $this->getRelationDetails($channelId,$msgSources,$page,$msgList);
        }
        return $cats;        
    }

   public function formattedSendMessage($channelId,$msgSources,$pagenum = 1,$id,$reply = false,$other_name = '')
    {

        $orderObj = new WhereDetail();
        $response_s = $orderObj
            ->setPage($pagenum)
            ->setNum($orderObj->getNum())
            ->setAccessToken($this->access)
            ->putOtherTextParam('app_key', $this->app_key)
            ->putOtherTextParam('secret_key', $this->secret_key)
            ->putOtherTextParam('msgSources', $msgSources)
            ->putOtherTextParam('channelId', $channelId);
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        $cats = false;
        if (!empty($response->result)) {
            $list = $this->stdClassObjectToArray($response->result);
            //$list = $this->arraySort($list,'id');
            foreach ($list as  $v)
            {
                $relationList = AliexpressReply::findOne(['message_id'=>$v['id']]);
                /*如果这个信息不存在则新增*/
                if(empty($relationList)){
                    /*
                     * 如果是我们自己回复,
                     * 则查询回复的信息有没有同步
                     * 有则编辑
                     */
                    /*获取还没有同步回复*/
                    $senderNameReply = AliexpressReply::find()->where(['channel_id'=>$channelId,'is_send'=>0,'reply_from'=>1,'reply_content'=>$v['content']])->asArray()->orderBy(['id'=>SORT_DESC])->one();
                    if(!empty($senderNameReply)){
                        $syncStatusReply = AliexpressReply::findOne(['id'=>$senderNameReply['id']]);
                        $syncStatusReply->message_id = $v['id'];
                        $syncStatusReply->is_send = 1;
                        $syncStatusReply->gmt_create = date('Y-m-d H:i:s',substr($v['gmtCreate'],0,-3));
                        $syncStatusReply->message_type = $v['messageType'];
                        $syncStatusReply->type_id = $v['typeId'];
                        $syncStatusReply->modify_by = 'system';
                        $syncStatusReply->modify_time = date('Y-m-d H:i:s');
                        $cats = $syncStatusReply->save();
                        $reply_id = $syncStatusReply->id;
                    }else{
                        $relationListModel = new AliexpressReply();
                        $relationListModel->inbox_id = $id;
                        $relationListModel->message_id = $v['id'];
                        $relationListModel->channel_id = $channelId;
                        $relationListModel->gmt_create = date('Y-m-d H:i:s', substr($v['gmtCreate'], 0, -3));
                        $relationListModel->message_type = $v['messageType'];
                        $relationListModel->reply_content = $v['content'];
                        $relationListModel->reply_by = $v['senderName'];
                        $relationListModel->account_id = $this->_account_id;
                        $relationListModel->is_send = 1;
                        $relationListModel->create_by = 'system';
                        $relationListModel->create_time = date('Y-m-d H:i:s');
                        $relationListModel->type_id = $v['typeId'];
                        //判断是客户回复还是我们回复 1我们 2客户
                        if ($v['senderName'] == $other_name) {
                            $relationListModel->reply_from = 2;
                        } else {
                            $relationListModel->reply_from = 1;
                        }
                        $cats = $relationListModel->save();
                        $reply_id = $relationListModel->attributes['id'];
                    }
                    /*图片地址*/
                    if($cats && !empty($v['filePath'])){
                            $filepathModel = new AliexpressFilepath();
                            $filepathModel->s_path = $v['filePath'][0]['sPath'];
                            $filepathModel->m_path = $v['filePath'][0]['mPath'];
                            $filepathModel->l_path = $v['filePath'][0]['lPath'];
                            $filepathModel->message_id = $v['id'];
                            $filepathModel->reply_id = $reply_id;
                            $filepathModel->save();
                    }
                    /*附属信息*/
                    if($cats && !empty($v['summary'])){
                            $summaryModel = new AliexpressSummary();
                            $summaryModel->message_id = $v['id'];
                            $summaryModel->reply_id = $reply_id;
                            $summaryModel->product_name = isset($v['summary']['productName'])?$v['summary']['productName']:'';
                            $summaryModel->product_image_url = isset($v['summary']['productImageUrl'])?$v['summary']['productImageUrl']:'';
                            $summaryModel->product_detail_url = isset($v['summary']['productDetailUrl'])?$v['summary']['productDetailUrl']:'';
                            $summaryModel->order_url = isset($v['summary']['orderUrl'])?$v['summary']['orderUrl']:'';
                            $summaryModel->sender_name = isset($v['summary']['senderName'])?$v['summary']['senderName']:'';
                            $summaryModel->receiver_name = isset($v['summary']['receiverName'])?$v['summary']['receiverName']:'';
                            $summaryModel->sender_login_Id = isset($v['summary']['senderLoginId'])?$v['summary']['senderLoginId']:'';
                            $summaryModel->save();
                    }
                    $this->parent_id = $v['id'];
                }else{
                    /*如果这个信息存在则修改*/
                    $relationList->inbox_id = $id;
                    $relationList->message_id = $v['id'];
                    $relationList->channel_id = $channelId;
                    $relationList->gmt_create = date('Y-m-d H:i:s',substr($v['gmtCreate'],0,-3));
                    $relationList->message_type = $v['messageType'];
                    $relationList->reply_content = $v['content'];
                    $relationList->reply_by = $v['senderName'];
                    $relationList->is_draft = 0;
                    $relationList->is_delete = 0;
                    $relationList->is_send = 1;
                    $relationList->account_id = $this->_account_id;
                    $relationList->create_by = 'system';
                    $relationList->create_time =  date('Y-m-d H:i:s');
                    $relationList->modify_by = 'system';
                    $relationList->type_id = $v['typeId'];
                    $relationList->modify_time = date('Y-m-d H:i:s');
                    $cats = $relationList->save();
                    if($cats && !empty($v['filePath'])){
                        $filepath = AliexpressFilepath::findOne(['message_id'=>$v['id']]);
                        if(empty($filepath)){
                            $pathModel = new AliexpressFilepath();
                            $pathModel->s_path = $v['filePath'][0]['sPath'];
                            $pathModel->m_path = $v['filePath'][0]['mPath'];
                            $pathModel->l_path = $v['filePath'][0]['lPath'];
                            $pathModel->message_id = $v['id'];
                            $pathModel->reply_id = $reply_id;
                            $pathModel->save();
                        }else{
                            $filepath->s_path = $v['filePath'][0]['sPath'];
                            $filepath->m_path = $v['filePath'][0]['mPath'];
                            $filepath->l_path = $v['filePath'][0]['lPath'];
                            $filepath->message_id = $v['id'];
                            $filepath->save();
                        }
                    }
                    if($cats && !empty($v['summary'])){
                        $relationRow = AliexpressSummary::findOne(['message_id'=>$v['id']]);
                        if(empty($relationRow)) {
                            $summaryModel = new AliexpressSummary();
                            $summaryModel->message_id = $v['id'];
                            $summaryModel->product_name = isset($v['summary']['productName']) ? $v['summary']['productName'] : '';
                            $summaryModel->product_image_url = isset($v['summary']['productImageUrl']) ? $v['summary']['productImageUrl'] : '';
                            $summaryModel->product_detail_url = isset($v['summary']['productDetailUrl']) ? $v['summary']['productDetailUrl'] : '';
                            $summaryModel->order_url = isset($v['summary']['orderUrl']) ? $v['summary']['orderUrl'] : '';
                            $summaryModel->sender_name = isset($v['summary']['senderName'])?$v['summary']['senderName']:'';
                            $summaryModel->receiver_name = isset($v['summary']['receiverName'])?$v['summary']['receiverName']:'';
                            $summaryModel->sender_login_Id = isset($v['summary']['senderLoginId'])?$v['summary']['senderLoginId']:'';
                            $summaryModel->save();
                        }else{
                            $relationRow->message_id = $v['id'];
                            $relationRow->product_name = isset($v['summary']['productName']) ?$v['summary']['productName'] : '';
                            $relationRow->product_image_url = isset($v['summary']['productImageUrl']) ?$v['summary']['productImageUrl'] : '';
                            $relationRow->product_detail_url = isset($v['summary']['productDetailUrl']) ?$v['summary']['productDetailUrl'] : '';
                            $relationRow->order_url = isset($v['summary']['orderUrl']) ? $v['summary']['orderUrl'] : '';
                            $relationRow->sender_name = isset($v['summary']['senderName'])?$v['summary']['senderName']:'';
                            $relationRow->receiver_name = isset($v['summary']['receiverName'])?$v['summary']['receiverName']:'';
                            $relationRow->sender_login_Id = isset($v['summary']['senderLoginId'])?$v['summary']['senderLoginId']:'';
                            $relationRow->save();
                        }
                    }
                    $this->parent_id = $v['id'];
                }
            }
            $pagenum++;
            $this->formattedSendMessage($channelId,$msgSources,$pagenum,$id,false,$other_name);
        }
        return $cats;
    }
    /**
     * [std_class_object_to_array 将对象转成数组]
     * @param [stdclass] $stdclassobject [对象]
     * @return [array] [数组]
     */
    public function stdClassObjectToArray($stdclassobject)
    {
        $array = [];
        $_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
        foreach ($_array as $key => $value) {
            $value = (is_array($value) || is_object($value)) ? $this->stdClassObjectToArray($value) : $value;
            $array[$key] = $value;
        }
        return $array;
    }
    /**
     * @desc arraySort php二维数组排序 按照指定的key 对数组进行排序
     * @param array $arr 将要排序的数组
     * @param string $keys 指定排序的key
     * @param string $type 排序类型 asc | desc
     * @return array
     */
    public function arraySort($arr, $keys, $type = 'asc') {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v){
            $keysvalue[$k] = $v[$keys];
        }
        $type == 'asc' ? asort($keysvalue) : arsort($keysvalue);
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }
}