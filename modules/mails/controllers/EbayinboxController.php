<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/11 0011
 * Time: 下午 2:05
 */
namespace app\modules\mails\controllers;

use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\EbayReply;
use app\modules\orders\models\OrderEbay;
use app\components\Controller;
use app\modules\products\models\EbaySites;
use app\modules\mails\models\MailTag;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\Platform;
use PhpImap\Exception;
use yii\helpers\Url;
use app\modules\orders\models\Order;
use yii\helpers\Json;
use app\modules\mails\models\EbayInboxContentMongodb;
use app\modules\mails\models\EbayFeedback;
use app\modules\accounts\models\Account;
use app\modules\systems\models\Keyboard;

class EbayinboxController extends Controller
{
    public $storeSites = [];
    public function getSite($siteid)
    {
        if(!isset($this->storeSites[$siteid]))
        {
            $this->storeSites[$siteid] = EbaySites::findOne(['siteid'=>$siteid])->attributes;
        }
        return $this->storeSites[$siteid];
    }

    public function actionList()
    {
        $model = new EbayInbox();
        $params = \Yii::$app->request->getBodyParams();

        $tagList = EbayInbox::getTagsList();
        //$headSummary = ['data'=>EbayInbox::summaryByAccount(),'name'=>'account_id','label'=>'未回复统计:'];
        $sort = new \yii\data\Sort([
            'attributes' => [
                'receive_date',
                //'subject'
            ]
        ]);
        $sort->defaultOrder = array(
            'receive_date' => SORT_DESC,
			//'subject' => SORT_ASC,
        );
        $dataProvider = $model->searchList($params,$sort);
        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'tagList' => $tagList,
           // 'headSummary' => $headSummary,
        ]);
    }

    public function actionDetail($id)
    {
        $currentModel = EbayInbox::findOne((int)$id);
        $orderIdCondition = $currentModel->transaction_id == ''? '':" and transaction_id='{$currentModel->transaction_id}'";

        /*如果发送人是ebay,只显示自己*/
        if($currentModel->sender == EbayInbox::SENDER_EBAY)
        {
            $models = EbayInbox::find()->where('id=:id',[':id'=>$currentModel->id])->orderBy('receive_date DESC')->asArray()->all();

            //标记已读已回复
            EbayInbox::updateAll(['is_replied'=>3,'is_read'=>1],['=','id',$currentModel->id]);

        }else{
            $models = EbayInbox::find()->where("sender='{$currentModel->sender}' and account_id={$currentModel->account_id} and item_id='{$currentModel->item_id}'{$orderIdCondition}")->orderBy('receive_date DESC')->asArray()->all();

            //标记已读
            EbayInbox::updateAll(['is_read'=>1],['sender'=>$currentModel->sender,'account_id'=>$currentModel->account_id,'item_id'=>$currentModel->item_id,'transaction_id'=>$orderIdCondition]);

        }
        
        $imgBox = "";
        $orgialImage = array();
        if(!empty($models))
        {
            foreach ($models as $key => $value)
            {

                $id = $value['id'];
                $account = Account::findOne($value['account_id']);
                $value['account_name'] = $account->account_name;
                //从mogo中取产品图片
                if($value['img_exists']  == 1)
                {
                    $contentMongodb = EbayInboxContentMongodb::findOne(['ueb_ebay_inbox_id'=>(int)$id]);
                    $imgBox=$contentMongodb->image_url;
                    $value['image'] = str_replace('href', '#', html_entity_decode($imgBox));
                    array_push($orgialImage, $value['orgial_img']);
                }else
                    $value['image'] = '';

                //如果newmessage无内容则去匹配mongodb中的content
                if(empty($value['new_message'])){
                    $result = EbayInbox::getMongoContent($id);
                    $result > 0 && $this->redirect(['ebayinbox/detail','id'=>$id]);
                }
                $value['new_message'] = html_entity_decode($value['new_message']);
                $newArr[$value['ch_receive_date']] = $value;               
                //是否存在有回复的内容
                $Reply= EbayReply::find()->select('inbox_id,sender,item_id,account_id,reply_title,reply_content,create_time,recipient_id')->where('inbox_id=:iid and is_draft = :draft ',[':iid'=>$id,':draft'=>0])->orderBy('create_time DESC')->asArray()->all();
                if(!empty($Reply))
                {
                    foreach ($Reply as $reKey => $reVal)
                    {
                        $account = Account::findOne($reVal['account_id']);
                        $reVal['id'] = $reVal['inbox_id'];
                        $reVal['account_id'] = $account->account_name;
                        $newArr[$reVal['create_time']]  = $reVal;
                    }
                }
            }
            krsort($newArr);
            
        }
        if(!empty($orgialImage)){
            $orgialImage = array_unique($orgialImage);
            $orgialImage = implode(',', $orgialImage);  //图片存在
        }
        //是否卖家主动发送第一封邮件，查出主动发送的第一封邮件
        $firstReplyModel = EbayReply::find()->where(['inbox_id'=>0,'parent_message_id'=>'','account_id'=>$currentModel->account_id,'reply_title'=>$currentModel->subject,'recipient_id'=>$currentModel->sender,'is_draft'=>0,'is_delete'=>0,'is_send'=>1])->one();

        //用于生成回复表单
        $replyModel = EbayReply::findOne(['inbox_id'=>$currentModel->id,'is_draft'=>0,'is_send'=>0,'is_delete'=>0]);
        $replyModel = empty($replyModel) ? (new EbayReply()) : $replyModel;

        //用于生成草稿
        $draftModel = EbayReply::findOne(['inbox_id'=>$currentModel->id,'is_draft'=>1]);
        /*获取历史订单*/
        $Historica = [];
        if($currentModel->sender){
            ;
            $Historica = Order::getHistoryOrders(Platform::PLATFORM_CODE_EB, $currentModel->sender);

            if (!empty($Historica)){

                $Historica = Json::decode(Json::encode($Historica), true);
                foreach ($Historica as $historKey => &$historVal) {                    
					// 给当前订单数据添加评价等级
                    $historVal['detail'][0]['comment_type'] = EbayFeedback::getCommentByTransactionID($historVal['detail'][0]['transaction_id'],$historVal['detail'][0]['item_id']);
  
                }
            }
        }
        // 获取已标记标签
        $inbox_id = (int)$this->request->getQueryParam('id');
        $platform_code = Platform::PLATFORM_CODE_EB;

        $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code,$inbox_id);

        // 查询用户设置的快捷键
        $keyboards = json_encode(Keyboard::getKeyboardsAsArray(Platform::PLATFORM_CODE_EB, \Yii::$app->user->identity->id));

        return $this->renderList('detail', [
            'currentModel' => $currentModel,
            'models' => $newArr,
            'firstReplyModel' => $firstReplyModel,
            'replyModel' => $replyModel,
            'keyboards' => $keyboards,
            'draftModel' => $draftModel,
            'Historica'=>$Historica,
            'tags_data' => $tags_data,
            'orgialImage' => $orgialImage
        ]);
    }

    /**
     * 快捷键批量或者单条添加或删除消息标签
     */
    public function actionAddretags()
    {
        if ($this->request->getIsAjax()) {

            $platform_code = Platform::PLATFORM_CODE_EB;
            $post_data = $this->request->post();
            //判断按键下的标签是否存在
            $tag_data = MailTag::get_tag_by_platformcode_and_subject($platform_code,$post_data['MailTag']['inbox_id'],$post_data['MailTag']['tag_id']);

            if($tag_data){
                //标签存在就删除
                $result = MailTag::delete_mail_tag($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

                if (!$result) {
                    $this->_showMessage(\Yii::t('system', 'operation fail'), false);
                }
                $tag_id = $post_data['MailTag']['tag_id'][0];

                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, 'del', false, null,$tag_id);
                exit;
            }else{
                //消息id
                $inbox_ids = explode(',', $post_data['MailTag']['inbox_id']);

                //存取mail_tag表的数据

                list($result, $message) = MailTag::batch_save_mail_tags($platform_code, $post_data['MailTag']['tag_id'], $inbox_ids);
                //获取打上标签数据
                $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code,$inbox_ids);
                if (!$result) {
                    $this->_showMessage($message, false);
                }
                /*  //成功后跳转的url
                  $url = $this->get_loation_url($post_data);*/

                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, 'add', false, $tags_data, null);
                exit;
            }

        }
    }

    /**
     * 批量或者单条添加消息标签
     */
    public function actionAddtags()
    {   
        $this->isPopup = true;
        $model = new MailTag();
        $inbox_ids = $this->request->getQueryParam('ids');
        $type = $this->request->getQueryParam('type');
        
        //根据平台code获取标签数据
        $platform_code = Platform::PLATFORM_CODE_EB;
        //所有该平台下的标签数据
        $tags_data = Tag::getTagAsArray($platform_code);

        //该平台该消息已经有的标签数据
        $exist_data = MailTag::get_tag_ids_by_platformcode_and_inbox($platform_code,explode(',',$inbox_ids));
        
        if ($this->request->getIsAjax()) {

            $post_data = $this->request->post();
            
            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage(\Yii::t('system', 'no tag Data'), false);
            }

            $this->save_mail_tag($post_data,$platform_code); 
        }

        return $this->render('tags', [
            'model' => $model,
            'inbox_ids' => $inbox_ids,
            'tags_data' => $tags_data,
            'exist_data'=>$exist_data,
            'type' => $type
        ]);
    }

    /*
     * @desc 批量更新已回复
     **/
    public function actionSignreplied()
    {
        $ids = $this->request->post('ids');
       
        $result = EbayInbox::updateAll(['is_replied'=>3],['and',['in','id',$ids],['=','is_replied',0]]);
       
        if($result >= 1)
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                        'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayinbox/list') . '");',false);
        else
            $this->_showMessage('状态更新失败',false);

    }
    /**
     * 维护消息和标签的关系
     * @param array $post_data 表单数据
     * @param string $platform_code 平台code
     */
    protected function save_mail_tag($post_data,$platform_code)
    { 
        //消息id
        $inbox_ids = explode(',', $post_data['MailTag']['inbox_id']);   
        
        //存取mail_tag表的数据
        list($result,$message) = MailTag::batch_save_mail_tags($platform_code,$post_data['MailTag']['tag_id'],$inbox_ids);
            
        if (!$result) {
            $this->_showMessage($message, false);
        }
        
        //成功后跳转的url
        $url = $this->get_loation_url($post_data);
 
        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $url, false, null,null);
    }

    protected function get_loation_url($post_data)
    {
        //成功后跳转的url
        switch ($post_data['MailTag']['type']) {
            case 'list':
                $url = \yii\helpers\Url::toRoute('/mails/ebayinbox/list');
            break;
            case 'detail':
                $url = \yii\helpers\Url::toRoute(['/mails/ebayinbox/detail','id'=> $post_data['MailTag']['inbox_id']]);
            break;
            default:
                $url = \yii\helpers\Url::toRoute('/mails/ebayinbox/list');
            break;
        }
        return $url;
    }
    /**
     * 移除指定消息的标签
     */
    public function actionRemovetags()
    {
        $this->isPopup = true;
        $model = new MailTag();
        $inbox_id = (int)$this->request->getQueryParam('id');
        $type = $this->request->getQueryParam('type');
        $platform_code = Platform::PLATFORM_CODE_EB;

        $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code,$inbox_id);


        if ($this->request->getIsAjax()) {

            $post_data = $this->request->post();
            
            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage(\Yii::t('system', 'no tag Data'), false);
            }
            
            $result = MailTag::delete_mail_tag($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);
            
            if (!$result) {
                $this->_showMessage(\Yii::t('system', 'operation fail'), false);
            }
            
            //成功后跳转的url
            $url = $this->get_loation_url($post_data);

            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $url, false, null,null);

        }
        return $this->render('tags', [
            'model' => $model,
            'inbox_ids' => $inbox_id,
            'tags_data' => $tags_data,
            'exist_data'=>array(),
            'type' => $type
        ]);
    }



    //标记已回复或下一封
    public function actionMark()
    {
        $id = trim($this->request->post('inbox_id'));
        $type = trim($this->request->post('type'));
        if(is_numeric($id) && $id > 0 && $id%1 === 0)
        {
            $model = EbayInbox::findOne((int)$id);
            if(!empty($model))
            {
                switch($type)
                {
                    case 'replied':
                        try{
                            $model->is_replied = 3;
                            $flag = $model->save();
                            if(!$flag)
                                $response['info'] = '标记回复不成功。';
                        }catch(Exception $e){
                            $flag = false;
                            $response['info'] = $e->getMessage();
                        }
                        if($flag)
                        {
                            $nextModel = $model->nextInbox();
                            if(empty($nextModel))
                            {
                                $flag = false;
                                $response['info'] = '已是最后一封。';
                            }
                            else
                            {
                                $response['url'] = Url::toRoute(['/mails/ebayinbox/detail','id'=>$nextModel->id]);
                            }
                        }
                        $response['status'] = $flag ? 'success':'error';
                        break;
                    case 'next':
                        EbayInbox::setExcludeList($id);
                        $nextModel = $model->nextInbox();
                        if(empty($nextModel))
                            $response = ['status'=>'error','info'=>'已是最后一封。'];
                        else
                            $response = ['status'=>'success','url'=>Url::toRoute(['/mails/ebayinbox/detail','id'=>$nextModel->id])];
                        break;
                    default:
                        $response = ['status'=>'error','info'=>'type值错误。'];
                }
            }
            else
            {
                $response = ['status'=>'error','info'=>'inbox_id值错误。'];
            }
        }
        else
        {
            $response = ['status'=>'error','info'=>'inbox_id格式错误。'];
        }
        echo json_encode($response);
        \Yii::$app->end();
    }





}