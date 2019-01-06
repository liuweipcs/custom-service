<?php

namespace app\modules\mails\controllers;

use Yii;
use app\modules\mails\models\AmazonInbox;
use app\modules\mails\models\AmazonReply;
use app\modules\mails\models\AmazonInboxAttachment;
use app\modules\services\modules\amazon\components\MailBox;
use app\modules\mails\models\MailTemplate;
use app\components\Controller;
use yii\web\NotFoundHttpException;
use app\common\VHelper;
use yii\helpers\Url;
use app\modules\orders\models\Order;
use app\modules\accounts\models\Platform;
use yii\helpers\Json;
use app\modules\mails\models\MailTag;
use app\modules\systems\models\Tag;
use app\modules\mails\models\AliexpressInbox;
use app\modules\mails\models\app\modules\mails\models;
use app\modules\accounts\models\Account;
use app\modules\systems\models\Keyboard;

/**
 * AmazonInboxController implements the CRUD actions for AmazonInbox model.
 */
class AmazoninboxController extends Controller
{
    /**
     * @inheritdoc
     */
    /*
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    } */

    /**
     * Lists all AmazonInbox models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new AmazonInbox();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        $tagList = AmazonInbox::getTagsList();
        return $this->renderList('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'tagList' => $tagList,            
        ]);
    }

    /**
     * Displays a single AmazonInbox model.
     * @param string $id
     * @return mixed
     */
    public function actionView()
    {   
        $inbox = new AmazonInbox();
        $reply = new AmazonReply();
        $next = $this->request->getQueryParam('next');
        if (isset($next) && !empty($next))
        {
            $inboxId = AmazonInbox::getNextNoProcessId();
            if (empty($inboxId))
            {
                AmazonInbox::destroyProcessedList();
                $this->_showMessage('没有未处理的消息了', false);
            }
            AmazonInbox::pushProccessedList($inboxId);
            $this->redirect(Url::toRoute(['/mails/amazoninbox/view', 'id' => $inboxId]));
            \Yii::$app->end();
        }
        $id = $this->request->getQueryParam('id');
        if (empty($id))
            $this->_showMessage(\Yii::t('system', 'Invalid Id'));
        AmazonInbox::pushProccessedList($id);
        
        $model = $this->findModel($id);
        //异步请求标记为已读
        if ($model->is_read != 1) {
            VHelper::throwTheader(Url::toRoute(['/services/amazon/amazon/mark',
                'inboxid' => $model->id,
                'msgtype' => '1']));
        }
        //回复历史
        $history = AmazonReply::find()
            ->select('*')
            ->where(['=', 'inbox_id', $id])
            ->orderBy(['create_time' => SORT_DESC])
            ->all();

        $reply->inbox_id = $model->id;
        $reply->reply_title = sprintf('RE:%s', $model->subject);

        $params = \Yii::$app->request->getBodyParams();
        $params['pageSize'] = 5;
        $params['sender'] = $model->sender;

        $query = $inbox::find()->from(AmazonInbox::tableName() . ' as t');
        // $query->andFilterCompare('receive_date', '<='.$model->receive_date.'');
//        $query->andFilterCompare('sender', $model->sender);
        $query->andWhere(['sender' => $model->sender]);
        $historyInboxs = $query->all();
        //$dataProvider = $inbox->searchList($params, $query);

        //attachments
        $attachments = AmazonInboxAttachment::find()
            ->where(['=', 'amazon_inbox_id', $id])
            ->all();
        
            /*获取订单详情*/
        $orderinfo = [];
        if(!empty($model->order_id)){
            $orderinfo = Order::getOrders(Platform::PLATFORM_CODE_AMAZON, $model->order_id, null, null);
            //$orderinfo = Order::getOrderStack(Platform::PLATFORM_CODE_AMAZON, $model->order_id);
            //$orderinfo = Json::decode(Json::encode($orderinfo), true);
        }
        /*获取历史订单*
        $Historica = [];
        $buyerId = isset($orderinfo['info']) ? $orderinfo['info']['buyer_id'] : '';
        if($buyerId){
            $Historica = Order::getHistoryOrders(Platform::PLATFORM_CODE_AMAZON, $buyerId);
            if (!empty($Historica))
                $Historica = Json::decode(Json::encode($Historica), true);
            else
                $Historica = [];
        } */
        $templates = MailTemplate::getMailTemplateDataAsArray(Platform::PLATFORM_CODE_AMAZON);
        $accounts = AmazonInbox::getAccountList();

        // 获取已标记标签
        $inbox_id = (int)$this->request->getQueryParam('id');
        $platform_code = Platform::PLATFORM_CODE_AMAZON;

        $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code,$inbox_id);

        // 查询用户设置的快捷键
        $keyboards = json_encode(Keyboard::getKeyboardsAsArray($platform_code, \Yii::$app->user->identity->id));
        
        return $this->renderList('view', [
            'model'        => $model,
            'inbox'        => $inbox,
            'reply'        => $reply,
/*             'prev'         => $prev,
            'next'         => $next, */
            'accounts'     => $accounts,
            'attachments'  => $attachments,
            'keyboards' => $keyboards,
            'templates'    => $templates,
            'orderInfo'    => $orderinfo,
            //'historica'    => $Historica,
            'historyInboxs' => $historyInboxs,
            'history'      => $history,
            'tags_data' => $tags_data,
        ]);
    }

    /**
     * Get an inbox content
     * 
     * @param  string $id
     * 
     * @return mixed
     */
    public function actionContent($id)
    {
        echo nl2br(AmazonInbox::find()->select(['body'])->where(['=', 'id', $id])->one()->body);

        Yii::$app->end();
    }

    /**
     * Mark an email as read or replied ?
     * 
     * @param  int $id inbox id
     * 
     * @noreturn 
     */
    public function actionMark()
    {
        $id   = Yii::$app->request->get('id');
        $stat = Yii::$app->request->get('stat');

        $model = AmazonInbox::findOne($id);

        if (!$model) {
            $this->_showMessage('未找此邮件!', false);
        }

        if ($stat == '1') {
            if ($model->is_read == '2') $this->_showMessage('Done!', false);

            //mark as read not sync
            $model->is_read = 1;
            $model->save();
        }
        $url_make = Url::toRoute(['/mails/amazoninbox/view', 'next' => 1]);

        if ($stat == '2') {
            if ($model->is_replied == '2') $this->_showMessage('Done!', true, $url_make);

            //mark as answered not sync
            $model->is_replied = 1;
            $model->save();
        }

        $flag = [
            '1' => '\\Seen',
            '2' => '\\Answered',
        ];

        if (!array_key_exists($stat, $flag)) {
            $this->_showMessage('状态参数有误!', false);
        }

        $isok = MailBox::instance(trim($model->receive_email))
            ->mailbox
            ->setFlag([$model->mid], $flag[$stat]);

        if ($isok == true) {
            if ($stat == '1')
                $model->is_read = 2;
            else
                $model->is_replied = 2;

            $model->save();

            if($stat == 1)
            	$this->_showMessage('已读同步成功!', false);
            else
            	$this->_showMessage('已读同步成功!', true, $url_make);
        } else {
            $this->_showMessage('已读同步失败!', false);
        }

        Yii::$app->end();
    }

    /**
     * Creates a new AmazonInbox model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $this->isPopup = true;

        $model = new AmazonInbox();
        var_dump($model->load(Yii::$app->request->post()));die();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing AmazonInbox model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $this->isPopup = true;

        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    public function actionOrder($id)
    {
        $params = [
            'order_id' => $id,
            'token' => '5E17C4488C2AC591',
            'platform' => 'AMAZON',
        ];
        $query = http_build_query($params);
        $retuelt  = \app\common\VHelper::getDataApi('http://erp.cc/services/api/order/index/method/mailrelatedorder',$query);

        echo $retuelt;

        Yii::$app->end();
    }

    /**
     * Deletes an existing AmazonInbox model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    public function actionBatchdelete()
    {
        $ids = $this->request->getBodyParam('ids');
        
        if (empty($ids)) {
            $this->_showMessage(\Yii::t('tag', 'Not Selected inbox'), false);
        }
        
        $model = new AmazonInbox();
        $result = $model->deleteByIds($ids);

        if (!$result) {
            $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
        }

        //删除成功
        $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/amazoninbox/index') . '");';
        $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
    }

    /**
     * Finds the AmazonInbox model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return AmazonInbox the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AmazonInbox::findOne($id)) !== null) {
            $accounts  = AmazonInbox::getAccountList();
            $mailType  = ['1' => '平台邮件', '2' => '买家邮件'];
            $readType  = ['0' => '未读', '1' => '已读未同步', '2' => '已读'];
            $replyType = ['0' => '未回复', '1' => '已回复未同步', '2' => '已回复'];

            $model->setAttribute('account_id', isset($accounts[$model->account_id]) ? $accounts[$model->account_id] : '');
            $model->setAttribute('mail_type', $mailType[$model->mail_type]);
            $model->is_read_text = $readType[$model->is_read];
            $model->is_replied_text = $replyType[$model->is_replied];

            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Get order infomation
     * 
     * @param  string $orderid 
     * 
     * @return array
     */
    protected function getOrder($orderid)
    {
        $params = [
            'order_id' => $orderid,
            'token' => '5E17C4488C2AC591',
            'platform' => 'AMAZON',
        ];

        $url = 'http://erp.cc/services/api/order/index/method/mailrelatedorder';
        $query = http_build_query($params);
        $json = \app\common\VHelper::getDataApi($url, $query);

        $data = json_decode($json, true);

        if (isset($data['ack']) && $data['ack'] == false)
            return [];

        return $data;
    }

    /**
     * 快捷键批量或者单条添加或删除消息标签
     */
    public function actionAddretags()
    {
        if ($this->request->getIsAjax()) {

            $platform_code = Platform::PLATFORM_CODE_AMAZON;
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
        $platform_code = Platform::PLATFORM_CODE_AMAZON;
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
                $url = \yii\helpers\Url::toRoute('/mails/amazoninbox/index');
            break;
            case 'detail':
                $url = \yii\helpers\Url::toRoute(['/mails/amazoninbox/view','id'=> $post_data['MailTag']['inbox_id']]);
            break;
            default:
                $url = \yii\helpers\Url::toRoute('/mails/amazoninbox/index');
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
        $platform_code = Platform::PLATFORM_CODE_AMAZON;

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

}
