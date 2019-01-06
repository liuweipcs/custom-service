<?php

namespace app\modules\mails\controllers;

use app\components\Controller;
use app\modules\mails\models\WishInbox;
use app\modules\mails\models\WishReply;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\MailTag;
use app\modules\orders\models\OrderWishKefu;
use app\modules\systems\models\Tag;
use yii\helpers\Url;
use yii\helpers\Json;
use app\modules\systems\models\Keyboard;
use Yii;
use app\modules\accounts\models\UserAccount;
use app\modules\mails\models\WishInboxInfo;
use app\modules\orders\models\OrderKefu;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\aftersales\models\AfterSalesRedirect;
use yii\web\UploadedFile;
use app\models\UploadForm;
use wish\models\WishAccount;

class WishController extends Controller
{

    /**
     * 列表
     */
    public function actionIndex()
    {
        $params = \Yii::$app->request->getBodyParams();
        $model = new WishInbox();
        $dataProvider = $model->searchList($params);
        $tagList = WishInbox::getTagsList($params);
        $accountList = WishInbox::getAccountCountList($params);
        return $this->renderList('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'tagList' => $tagList,
            'account_email' => $accountList,
        ]);
    }

    /*
    * 详情
    * */
    public function actionDetails()
    {
        $reply = new WishReply();
        $id = $_REQUEST['id'];
        $next = $this->request->getQueryParam('next');
        if (isset($next) && !empty($next)) {
            $inboxId = WishInbox::getNextNoProcessId($id);
            if (empty($inboxId)) {
                WishInbox::destroyProcessedList();
                $this->_showMessage('没有未处理的消息了', false);
            }
            WishInbox::pushProccessedList($inboxId);
            $this->redirect(Url::toRoute(['/mails/wish/details', 'id' => $inboxId]));
            \Yii::$app->end();
        }
        $model = WishInbox::findOne(['info_id' => $id]);

        //标记为已读
        $model->read_stat = 1;
        $model->save();

        //获取站内信详情
        $info = $model->getInboxInfo($model->info_id);

        //获取站内信信息列表
        $replyList = $reply->getReplyList($model->platform_id);

        //获取订单详情
        $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_WISH, $info->order_id);

        //账号信息
        $account = Account::findOne($model->account_id);

        //获取历史订单
        $Historica = [];
        if (!empty($model->user_id)) {
            $Historica = OrderKefu::getHistoryOrders(Platform::PLATFORM_CODE_WISH, $model->user_id, '', $account->old_account_id);
        } else {
            if (!empty($orderinfo['info']['buyer_id'])) {
                $Historica = OrderKefu::getHistoryOrders(Platform::PLATFORM_CODE_WISH, $orderinfo['info']['buyer_id'], '', $account->old_account_id);
            }
        }
        if (!empty($Historica)) {
            $Historica = Json::decode(Json::encode($Historica), true);
        }

        //退款信息
        $salesrefund = AfterSalesRefund::find()
            ->where(['order_id' => $orderinfo['info']['order_id'], 'platform_code' => Platform::PLATFORM_CODE_WISH])
            ->asArray()
            ->all();

        //用于生成回复表单(草稿)
        $replyModel = WishReply::findOne(['platform_id' => $model->platform_id, 'is_send' => 0, 'is_delete' => 0, 'is_draft' => 1]);

        $replyModel = empty($replyModel) ? (new WishReply()) : $replyModel;

        //获取已标记标签
        $platform_code = Platform::PLATFORM_CODE_WISH;
        $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code, $model->id);

        // 查询用户设置的快捷键
        $keyboards = json_encode(Keyboard::getKeyboardsAsArray($platform_code, \Yii::$app->user->identity->id));

        $googleLangCode = VHelper::googleLangCode();

         foreach ($Historica as $key => $value) {
            if($value['platform_order_id']==$info->order_id){ 
                $Historica[$key]=$Historica[0];
                $Historica[0]=$value;              
             }   
        }
        
        
        return $this->renderList('details', [
            'model' => $model,
            'replyList' => $replyList,
            'next' => $next,
            'keyboards' => $keyboards,
            'info' => $info,
            'orderinfo' => $orderinfo,
            'salesrefund' => $salesrefund,
            'tags_data' => $tags_data,
            'Historica' => $Historica,
            'googleLangCode' => $googleLangCode,
            'replyModel' => $replyModel,
            'id' => $id,
        ]);
    }

    /*
   *标记未处理状态
   **/
    public function actionMarkerreply()
    {
        $data = \Yii::$app->request->post();


//        $orderObj = new UpdateMsgProcessed();
//        $retuelt = $orderObj->getProcessed($data['account_id'],$data['channel_id'],$data['deal_stat']);
        //直接更改数据库字段
        $box = new WishInbox();
        $retuelt = $box->find()->where(['info_id' => $data['id']])->one();
        $retuelt->is_replied = 2;
        $retuelt->status = 'Awaiting buyer response';
        if ($retuelt->update() !== false) {
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null);
        } else {
            $this->_showMessage('标记失败！', false);
        }
    }

    //上传图片
    public function actionUploadimage()
    {
        if (\Yii::$app->request->isPost) {
            $model = new UploadForm();
            $model->imageFile = UploadedFile::getInstanceByName('wish_reply_upload_image');
            if ($model->upload()) {
                echo json_encode(['status' => 'success', 'url' => $this->request->hostInfo . '/' . str_replace('\\', '/', $model->getFilePath())]);
            } else {
                $errorResponse = ['status' => 'error', 'info' => VHelper::getModelErrors($model)];
//                if(!empty($model->getFilePath()))
//                    $errorResponse['url'] = $this->request->hostInfo.'/'.str_replace('\\','/',$model->getFilePath());
                echo json_encode($errorResponse);
            }
            \Yii::$app->end();
        }
    }

    //删除图片
    public function actionDeleteimage()
    {
        $url = trim($this->request->post('url'));
        $host = $this->request->hostInfo;
        if (strpos($url, $host) === false) {
            $response = ['status' => 'error', 'info' => '参数错误。'];
        } else {
            $url = str_replace($host . '/', '', $url);
            if (file_exists($url)) {
                unlink($url);
                $response = ['status' => 'success'];
            } else {
                $response = ['status' => 'error', 'info' => '图片不存在。'];
            }
        }
        echo json_encode($response);
        \Yii::$app->end();
    }

    /*
    *回复信息
    **/
    public function actionReply()
    {

        $images = $_REQUEST['image'];
        $img = '';
        if ($images) {
            $img = implode(',', $images);
        }
        $data = [
            'account_id' => $_REQUEST['account_id'],//店铺账号ID
            'platform_id' => $_REQUEST['platform_id'],
            'content' => $_REQUEST['content'],
            'content_en' => $_REQUEST['content_en'],
            'image_url_merchant' => $img,
        ];
        $reply = new WishReply();
        $Reply_id = $reply->getAdd($data);
        $Reply_data = WishReply::find()->where(['id' => $Reply_id])->asArray()->one();
        if (!empty($Reply_data)) {
            $jsonData = array(
                'message' => '回复成功!',
                'status' => 1,
                'data' => $Reply_data
            );
            echo json_encode($jsonData);
            exit();
        } else {
            $this->_showMessage('操作失败！', false);
        }
    }

    /*关闭客户问题*/
    public function actionClose()
    {
        $api = new WishReply();
        $account_id = $_REQUEST['account_id'];
        $platform_id = $_REQUEST['platform_id'];
        $retuelt = $api->closeWish($account_id, $platform_id);
        if ($retuelt->data) {
            $Inbox = WishInbox::findOne(['platform_id' => $platform_id]);
            $Inbox->is_close = 1;
            $Inbox->status = 'Closed';
            $Inbox->save(false);
            $this->_showMessage('操作成功！', true, null, false, null);
        } else {
            $this->_showMessage('关闭失败！', false);
        }

    }

    /*请求Wish支持协助*/
    public function actionAssist()
    {
        $api = new WishReply();
        $account_id = $_REQUEST['account_id'];
        $platform_id = $_REQUEST['platform_id'];
        $accountInfo = Account::findOne($account_id);
        if (empty($accountInfo)) {
            return false;
        }
        $erpAccount = WishAccount::findOne(['wish_id' => $accountInfo->old_account_id]);
        if (empty($erpAccount)) {
            return false;
        }
        $token = $erpAccount->access_token;
        $url = 'https://merchant.wish.com/api/v2/ticket/appeal-to-wish-support';
        $post_data['access_token'] = $token;
        $post_data['id'] = $platform_id;
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);
        $retuelt = $api->sendWishInbox($url, $post_data);
        if ($retuelt->data) {
            $Inbox = WishInbox::findOne(['platform_id' => $platform_id]);
            $Inbox->is_assist = 1;
            $Inbox->save();
            $this->_showMessage('操作成功！', true, null, false, null);
        } else {
            $this->_showMessage('操作失败！', false);
        }
    }

    /**
     * 快捷键批量或者单条添加或删除消息标签
     */
    public function actionAddretags()
    {
        if ($this->request->getIsAjax()) {

            $platform_code = Platform::PLATFORM_CODE_WISH;
            $post_data = $this->request->post();
            //判断按键下的标签是否存在
            $tag_data = MailTag::get_tag_by_platformcode_and_subject($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

            if ($tag_data) {
                //标签存在就删除
                $result = MailTag::delete_mail_tag($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

                if (!$result) {
                    $this->_showMessage(\Yii::t('system', 'operation fail'), false);
                }
                $tag_id = $post_data['MailTag']['tag_id'][0];

                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, 'del', false, null, $tag_id);
                exit;
            } else {
                //消息id
                $inbox_ids = explode(',', $post_data['MailTag']['inbox_id']);

                //存取mail_tag表的数据

                list($result, $message) = MailTag::batch_save_mail_tags($platform_code, $post_data['MailTag']['tag_id'], $inbox_ids);
                //获取打上标签数据
                $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code, $inbox_ids);
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
        $platform_code = Platform::PLATFORM_CODE_WISH;
        //所有该平台下的标签数据
        $tags_data = Tag::getTagAsArray($platform_code);

        //该平台该消息已经有的标签数据
        $exist_data = MailTag::get_tag_ids_by_platformcode_and_inbox($platform_code, explode(',', $inbox_ids));

        if ($this->request->getIsAjax()) {

            $post_data = $this->request->post();

            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage(\Yii::t('system', 'no tag Data'), false);
            }

            $this->save_mail_tag($post_data, $platform_code);
        }

        return $this->render('tags', [
            'model' => $model,
            'inbox_ids' => $inbox_ids,
            'tags_data' => $tags_data,
            'exist_data' => $exist_data,
            'type' => $type
        ]);
    }

    /**
     * 维护消息和标签的关系
     * @param array $post_data 表单数据
     * @param string $platform_code 平台code
     */
    protected function save_mail_tag($post_data, $platform_code)
    {
        //消息id
        $inbox_ids = explode(',', $post_data['MailTag']['inbox_id']);

        //存取mail_tag表的数据
        list($result, $message) = MailTag::batch_save_mail_tags($platform_code, $post_data['MailTag']['tag_id'], $inbox_ids);

        if (!$result) {
            $this->_showMessage($message, false);
        }

        //成功后跳转的url
        $url = $this->get_loation_url($post_data);

        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $url, false, null, null);
    }

    protected function get_loation_url($post_data)
    {
        //成功后跳转的url
        switch ($post_data['MailTag']['type']) {
            case 'list':
                $url = \yii\helpers\Url::toRoute('/mails/wish/index');
                break;
            case 'detail':
                $url = \yii\helpers\Url::toRoute(['/mails/wish/details', 'id' => $post_data['MailTag']['inbox_id']]);
                break;
            default:
                $url = \yii\helpers\Url::toRoute('/mails/wish/index');
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
        $platform_code = Platform::PLATFORM_CODE_WISH;

        $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code, $inbox_id);


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

            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $url, false, null, null);

        }
        return $this->render('tags', [
            'model' => $model,
            'inbox_ids' => $inbox_id,
            'tags_data' => $tags_data,
            'exist_data' => array(),
            'type' => $type
        ]);
    }

    /**
     *批量添加回复
     */
    public function actionBatchmark()
    {
        $ids = Yii::$app->request->post('ids', []);

        if (empty($ids)) {
            $this->_showMessage('请选中标记项', false);
        }

        $result = WishInbox::updateAll(['is_replied' => 1, 'read_stat' => 1, 'status' => 'Awaiting buyer response'], ['in', 'id', $ids]);
        if ($result) {
            $this->_showMessage('标记为已回复成功', true, Url::toRoute('/mails/wish/index'), true);
        } else {
            $this->_showMessage('标记为已回复失败', false);
        }

    }

    /**
     * 导出excel
     */
    public function actionExport()
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        //获取get参数
        $get = YII::$app->request->get();
        //id数组
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        //导出数据
        $data = [];

        //只能查询到客服绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_WISH);

        if (is_array($ids) && !empty($ids)) {
            //取出选中的数据
            $data = WishInbox::find()
                ->alias('i')
                ->select('i.*, a.account_name, s.last_updated')
                ->leftJoin(['a' => Account::tableName()], 'a.id = i.account_id')
                ->leftJoin(['s' => WishInboxInfo::tableName()], 's.info_id = i.info_id')
                ->andWhere(['in', 'i.id', $ids])
                ->andWhere(['in', 'i.account_id', $accountIds])
                ->orderBy('s.last_updated DESC')
                ->asArray()
                ->all();

        } else {
            //取出筛选的数据
            $query = WishInbox::find()
                ->alias('i')
                ->select('i.*, a.account_name, s.last_updated')
                ->leftJoin(['a' => Account::tableName()], 'a.id = i.account_id')
                ->leftJoin(['s' => WishInboxInfo::tableName()], 's.info_id = i.info_id')
                ->andWhere(['in', 'i.account_id', $accountIds])
                ->orderBy('s.last_updated DESC');

            if (!empty($get['user_name'])) {
                $query->andWhere(['like', 'i.user_name', $get['user_name']]);
            }
            if (!empty($get['order_id'])) {
                $info = WishInboxInfo::find()->where(['order_id' => $get['order_id']])->one();
                if (!empty($info) && !empty($info->info_id)) {
                    $query->andWhere(['i.info_id' => $info->info_id]);
                }
            }
            if (isset($get['is_wish']) && $get['is_wish'] != '') {
                switch ($get['is_wish']) {
                    case 'yes':
                        $query->andWhere(['i.user_name' => 'Koko Wish']);
                        break;
                    case 'no':
                        $query->andWhere(['<>', 'i.user_name', 'Koko Wish']);
                        break;
                }
            }
            if (isset($get['is_replied']) && $get['is_replied'] != '') {
                $query->andWhere(['i.is_replied' => $get['is_replied']]);
            }
            if (!empty($get['label'])) {
                $query->andWhere(['i.label' => $get['label']]);
            }
            if (!empty($get['read_stat'])) {
                $query->andWhere(['i.read_stat' => $get['read_stat']]);
            }
            if (!empty($get['status'])) {
                $query->andWhere(['i.status' => $get['status']]);
            }
            if (isset($get['account_id']) && $get['account_id'] != '') {
                $query->andWhere(['i.account_id' => $get['account_id']]);
            }
            if (!empty($get['start_time']) && !empty($get['end_time'])) {
                $query->andWhere(['between', 's.last_updated', $get['start_time'], $get['end_time']]);
            } else if (!empty($get['start_time'])) {
                $query->andWhere(['>=', 's.last_updated', $get['start_time']]);
            } else if (!empty($get['end_time'])) {
                $query->andWhere(['<=', 's.last_updated', $get['end_time']]);
            }

            if (isset($get['tag_id']) && !empty($get['tag_id'])) {
                $query->innerJoin(['t' => MailTag::tableName()], 't.inbox_id = i.id AND t.platform_code = :platform_code1', ['platform_code1' => Platform::PLATFORM_CODE_WISH])
                    ->andWhere(['t.tag_id' => $get['tag_id']]);
            }


            $data = $query->asArray()->all();
        }

        //标题数组
        $fieldArr = [
            '站内信编号',
            '主题',
            '简短标题',
            '帐号',
            '买家姓名',
            '最新邮件时间',
            '剩余回复时间',
            '平台状态',
            '是否已读',
            '是否已回复',
            '回复人',
            '回复时间',
        ];
        //导出数据数组
        $dataArr = [];

        if (!empty($data)) {
            foreach ($data as $item) {

                $repliedStatus = '';
                switch ($item['is_replied']) {
                    case 0:
                        $repliedStatus = '否';
                        break;
                    case 1:
                        $repliedStatus = '是';
                        break;
                }
                $plat_status = '';
                switch ($item['status']) {
                    case 'Awaiting buyer response':
                        $plat_status = '等待客户回复';
                        break;
                    case 'Awaiting your response':
                        $plat_status = '等待客户回复';
                        break;
                    case 'Closed':
                        $plat_status = '过期关闭';
                        break;
                }


                $dataArr[] = [
                    $item['platform_id'],
                    $item['label'],
                    $item['sublabel'],
                    $item['account_name'],
                    $item['user_name'],
                    $item['last_updated'],
                    $item[''],
                    $plat_status,
                    ($item['read_stat'] == 1 ? '是' : '否'),
                    $repliedStatus,
                    $item['modify_by'],
                    $item['modify_time'],
                ];
            }
        }

        VHelper::exportExcel($fieldArr, $dataArr, 'wishinbox_' . date('Y-m-d'));
    }


}
