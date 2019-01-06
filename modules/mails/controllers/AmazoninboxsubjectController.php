<?php

namespace app\modules\mails\controllers;

use app\modules\mails\models\AmazonReplyAttachment;
use app\modules\orders\models\OrderKefu;
use app\modules\systems\models\Keyboard;
use app\modules\systems\models\Rule;
use Yii;
use app\modules\mails\models\AmazonInbox;
use app\modules\mails\models\AmazonReply;
use app\modules\mails\models\AmazonInboxAttachment;
use app\modules\mails\models\AmazonInboxSubject;
use app\modules\services\modules\amazon\components\MailBox;
use app\modules\mails\models\MailTemplate;
use app\components\Controller;
use app\common\VHelper;
use yii\helpers\Url;
use app\modules\orders\models\Order;
use app\modules\accounts\models\Platform;
use yii\helpers\Json;
use app\modules\mails\models\MailSubjectTag;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\UserAccount;
use app\modules\mails\models\InboxSubjectSite;

/**
 * AmazonInboxController implements the CRUD actions for AmazonInbox model.
 */
class AmazoninboxsubjectController extends Controller
{

    /**
     *
     * @return \yii\base\string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex()
    {
        $model = new AmazonInboxSubject();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        $tagList = AmazonInboxSubject::getTagsList($params);
        //获取站点列表统计
        $siteList = AmazonInboxSubject::getSiteList($params);

        //获取账号列表统计
        $accountList = AmazonInboxSubject::getAccountCountList($params);

        $processedList = AmazonInboxSubject::getNoProcessSubjectIds();
        $session = \Yii::$app->session;
        $sessionKey = AmazonInboxSubject::PLATFORM_CODE . '_INBOX_SUBJECT_PROCESSED_LIST';

        $session->set($sessionKey, $processedList);

        // 查询用户设置的快捷键
        $keyboards = json_encode(Keyboard::getKeyboardsAsArray(Platform::PLATFORM_CODE_AMAZON));

        return $this->renderList('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'tagList' => $tagList,
            'siteList' => $siteList,
            'account_email' => $accountList,
            'keyboards' => $keyboards,
        ]);
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
            $tag_data = MailSubjectTag::get_tag_by_platformcode_and_subject($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

            if ($tag_data) {
                //标签存在就删除
                $result = MailSubjectTag::delete_mail_tag($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

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

                list($result, $message) = MailSubjectTag::batch_save_mail_tags($platform_code, $post_data['MailTag']['tag_id'], $inbox_ids);
                //获取打上标签数据
                $tags_data = MailSubjectTag::get_tags_by_platformcode_and_subject($platform_code, $inbox_ids);
                if (!$result) {
                    $this->_showMessage($message, false);
                }
                //成功后跳转的url
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, 'add', false, $tags_data, null);
                exit;
            }

        }
    }

    /**
     * 批量标记为已回复
     */
    public function actionBatchmark()
    {
        $ids = Yii::$app->request->post('ids', []);

        if (empty($ids)) {
            $this->_showMessage('请选中标记项', false);
        }

        $result = AmazonInboxSubject::updateAll(['is_replied' => 2], ['in', 'id', $ids]);
        if ($result !== false) {
            //标记后不刷新页面
            $this->_showMessage('标记为已回复成功', true, null, false);
        } else {
            $this->_showMessage('标记为已回复失败', false);
        }
    }

    /**
     * 批量或者单条添加消息标签
     */
    public function actionAddtags()
    {
        $this->isPopup = true;
        $model = new MailSubjectTag();
        $subject_ids = $this->request->getQueryParam('ids');
        $type = $this->request->getQueryParam('type');

        //根据平台code获取标签数据
        $platform_code = Platform::PLATFORM_CODE_AMAZON;
        //所有该平台下的标签数据
        $tags_data = Tag::getTagAsArray($platform_code);

        //该平台该消息已经有的标签数据
        $exist_data = MailSubjectTag::get_tag_ids_by_platformcode_and_subject($platform_code, explode(',', $subject_ids));

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
            'subject_ids' => $subject_ids,
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
        list($result, $message) = MailSubjectTag::batch_save_mail_tags($platform_code, $post_data['MailTag']['tag_id'], $inbox_ids);

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
                $url = \yii\helpers\Url::toRoute('/mails/amazoninboxsubject/index');
                break;
            case 'detail':
                $url = \yii\helpers\Url::toRoute(['/mails/amazoninboxsubject/view', 'id' => $post_data['MailTag']['inbox_id']]);
                break;
            default:
                $url = \yii\helpers\Url::toRoute('/mails/amazoninboxsubject/index');
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
        $model = new MailSubjectTag();
        $subject_id = (int)$this->request->getQueryParam('id');
        $type = $this->request->getQueryParam('type');
        $platform_code = Platform::PLATFORM_CODE_AMAZON;

        $tags_data = MailSubjectTag::get_tags_by_platformcode_and_subject($platform_code, $subject_id);

        if ($this->request->getIsAjax()) {

            $post_data = $this->request->post();

            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage(\Yii::t('system', 'no tag Data'), false);
            }

            $result = MailSubjectTag::delete_mail_tag($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

            if (!$result) {
                $this->_showMessage(\Yii::t('system', 'operation fail'), false);
            }

            //成功后跳转的url
            $url = $this->get_loation_url($post_data);

            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $url, false, null, null);

        }
        return $this->render('tags', [
            'model' => $model,
            'subject_ids' => $subject_id,
            'tags_data' => $tags_data,
            'exist_data' => array(),
            'type' => $type
        ]);
    }

    /**
     * amazon邮件详情
     * @return \yii\base\string
     * @throws \yii\base\ExitException
     */
    public function actionView()
    {
        // 获取session中未处理的主题列表
        $session = Yii::$app->session;

        $next = $this->request->getQueryParam('next');
        $last = $this->request->getQueryParam('last');
        if (isset($next) && !empty($next)) {
            $subjectId = $session->get('next_amazon_subject_id');

            if (!$subjectId) {
                $this->_showMessage('没有未处理的主题了', false);
            }
            $this->redirect(Url::toRoute(['/mails/amazoninboxsubject/view', 'id' => $subjectId]));
            \Yii::$app->end();
        }
        if (isset($last) && !empty($last)) {
            $subjectId = $session->get('last_amazon_subject_id');

            if (!$subjectId) {
                $this->_showMessage('没有未处理的主题了', false);
            }
            $this->redirect(Url::toRoute(['/mails/amazoninboxsubject/view', 'id' => $subjectId]));
            \Yii::$app->end();
        }
        $id = $this->request->getQueryParam('id');

        $sessionKey = AmazonInboxSubject::PLATFORM_CODE . '_INBOX_SUBJECT_PROCESSED_LIST';
        $processList = $session->get($sessionKey);
        if (empty($processList)) {
            AmazonInboxSubject::pushProccessedList($id);
            $processList = $session->get($sessionKey);
        }

        // 当前subject_id的key值
        $now_key = array_search($id, $processList);
        $next_subjectId = isset($processList[$now_key + 1]) ? $processList[$now_key + 1] : '';
        $session->set('next_amazon_subject_id', $next_subjectId);
        $last_subjectId = isset($processList[$now_key - 1]) ? $processList[$now_key - 1] : '';
        $session->set('last_amazon_subject_id', $last_subjectId);
        $subject_model = AmazonInboxSubject::findOne($id);

        if (empty($id)) {
            $this->_showMessage(\Yii::t('system', 'Invalid Id'));
        }

        // 获取所有邮件
        $model = AmazonInbox::find()->where(['inbox_subject_id' => $id])->orderBy('receive_date DESC')->all();
        
        foreach ($model as $key => $value) {
            //异步请求标记为已读
            if ($value->is_read != 1) {
                VHelper::throwTheader(Url::toRoute(['/services/amazon/amazon/mark', 'inboxid' => $value->id, 'msgtype' => '1']));
            }

            $account = Account::findOne($value['account_id']);
            $model[$key]['account_name'] = $account->account_name;

            //邮件回复
            $history = AmazonReply::find()
                ->where(['inbox_id' => $value->id])
                ->orderBy(['create_time' => SORT_DESC])
                ->all();
            if (!empty($history)) {
                foreach ($history as $k => $item) {
                    $replyAttachments = AmazonReplyAttachment::findAll(['amazon_reply_id' => $item->id]);
                    //$item->setAttribute('attachments', $replyAttachments);
                    $history[$k]['attachments'] = $replyAttachments;
                }
            }
//            $value->setAttribute('history', $history);
            $model[$key]['history'] = $history;

            //附件
            $attachments = AmazonInboxAttachment::find()->where(['amazon_inbox_id' => $value->id])->all();
            //$value->setAttribute('attachments', $attachments);
            $model[$key]['attachments'] = $attachments;
        }
        
        
        
        if(isset($_GET['debug'])){
            echo '<pre>';
            var_dump($model);
            echo '</pre>';
        }
        $subject_model->is_read = 1;
        $subject_model->save();


        $reply = new AmazonReply();
        $reply->reply_title = sprintf('RE:%s', $subject_model->now_subject);

        //获取订单详情
        $orderInfo = [];
        if (!empty($subject_model->order_id)) {
            $orderInfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_AMAZON, $subject_model->order_id, null, null);
        }

        //获取历史订单
        $Historica = [];
        $email = '';
        if (!empty($orderInfo['info']) && !empty($orderInfo['info']['email'])) {
            $email = $orderInfo['info']['email'];
        }
        $buyerId = !empty($subject_model->buyer_id) ? $subject_model->buyer_id : '';
      /*  if (!empty($buyerId)) {
            $buyerId = AmazonInboxSubject::getBuyerId($buyerId);
        }*/
        if (!empty($orderInfo['info']) && !empty($orderInfo['info']['buyer_id'])) {
            $buyerId = $orderInfo['info']['buyer_id'];
        }

        if (!empty($buyerId) && $subject_model->account_id) {
            $crmAaccount = Account::findOne($subject_model->account_id);
            if ($crmAaccount) {
                $erpAccountId = $crmAaccount->old_account_id;
                //先获取当前账号下该买家ID和邮箱的历史订单
                if (empty($email)) {
                    $email = $subject_model->sender_email;
                    if ($email) {
                        $email = preg_replace('/\+.*?(@)/', '\1', $email);
                    }
                }
                $Historica = OrderKefu::getHistoryOrders(Platform::PLATFORM_CODE_AMAZON, $buyerId, $email, $erpAccountId,$subject_model->order_id);
                if (!empty($Historica)) {
                    $Historica = Json::decode(Json::encode($Historica), true);
                } else {
                    $Historica = [];
                }
            }
        }

        $templates = MailTemplate::getMailTemplateDataAsArrayByUserId(Platform::PLATFORM_CODE_AMAZON);
        $accounts = AmazonInbox::getAccountList();

        // 获取已标记标签
        $platform_code = Platform::PLATFORM_CODE_AMAZON;

        $tags_data = MailSubjectTag::get_tags_by_platformcode_and_subject($platform_code, $subject_model->id);

        // 查询用户设置的快捷键
        $keyboards = json_encode(Keyboard::getKeyboardsAsArray($platform_code, Yii::$app->user->identity->id));
        $googleLangCode = VHelper::googleLangCode();
        // echo '<pre>';var_dump($subject_model);exit;
        return $this->renderList('view', [
            'subject_model' => $subject_model,
            'models' => $model,
            'reply' => $reply,
            'accounts' => $accounts,
            'templates' => $templates,
            'historica' => $Historica,
            'tags_data' => $tags_data,
            'keyboards' => $keyboards,
            'googleLangCode' => $googleLangCode,
        ]);
    }

    /**
     * 获取邮件内容
     */
    public function actionGetinboxbody()
    {
        $id = Yii::$app->request->get('id', 0);
        if (empty($id)) {
            return '';
        }

        $inbox = AmazonInbox::findOne($id);
        if (empty($inbox)) {
            return '';
        }

        //用于设置body不出现水平滚动条
        $extStyle = '<style>body{overflow-x:auto;}</style>';

        $body = $inbox->body;
        if (!strstr($body, '<!DOCTYPE html')) {
            $body = preg_replace("/#yiv.*;\}/", '', $body);
            $body = str_replace("|", '', $body);
            $body = nl2br($body);
            $body = trim($body);
        }
        return $extStyle . $body;
    }

    /**
     * 获取邮件内容
     */
    public function actionGetinboxtransbody()
    {
        $id = Yii::$app->request->get('id', 0);
        if (empty($id)) {
            die(json_encode([
                'code' => 0,
                'message' => 'ID不能为空',
            ]));
        }

        $inbox = AmazonInbox::findOne($id);
        if (empty($inbox)) {
            die(json_encode([
                'code' => 0,
                'message' => '找不到站内信',
            ]));
        }

        $body = $inbox->body;
        $body = trim($body, ' ');
        $body = nl2br($body);
        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => $body,
        ]));
    }


    public function actionMark()
    {
        $id = Yii::$app->request->get('id');
        $stat = Yii::$app->request->get('stat');

        $subject_model = AmazonInboxSubject::findOne($id);

        if (!$subject_model) {
            $this->_showMessage('未找此主题!', false);
        }

        $inboxs = AmazonInbox::find()->where(['inbox_subject_id' => $subject_model->id])->all();

        if ($inboxs) {
            $flag = [
                '1' => '\\Seen',
                '2' => '\\Answered',
            ];

            if (!array_key_exists($stat, $flag)) {
                $this->_showMessage('状态参数有误!', false);
            }

            foreach ($inboxs as $model) {
                $isok = false;

                if ($stat == '1') {
                    if ($model->is_read == '2') {
                        continue;
                    }
                    $model->is_read = 1;
                    $model->save();
                }

                if ($stat == '2') {
                    if ($model->is_replied == '2') {
                        continue;
                    }
                    $model->is_replied = 1;
                    $model->save();
                }

                if (empty($model->receive_email)) {
                    $isok = true;
                } else {
                    try {
                        $mail = MailBox::instance(trim($model->receive_email));
                        if (!empty($mail) && !empty($mail->mailbox)) {
                            $isok = $mail->mailbox->setFlag([$model->mid], $flag[$stat]);
                        }
                    } catch (\Exception $e) {

                    }
                }

                if ($isok) {
                    if ($stat == '1') {
                        $model->is_read = 2;
                    } else {
                        $model->is_replied = 2;
                    }
                    $model->save();
                }
            }
        }

        if ($stat == 1) {
            $subject_model->is_read = 1;
            if (!$subject_model->save()) {
                $this->_showMessage('标记已读失败', false);
            } else {
                $this->_showMessage('标记已读成功', true);
            }
        } else {
            $subject_model->is_replied = 1;
            $url_make = Url::toRoute(['/mails/amazoninboxsubject/view', 'next' => 1]);

            if (!$subject_model->save()) {
                $this->_showMessage('标记已回复失败', false);
            } else {
                $this->_showMessage('标记已回复成功', true, $url_make);
            }
        }

        Yii::$app->end();
    }

    /**
     * 标记邮件
     * @throws \yii\base\ExitException
     */
    public function actionMarkemail()
    {
        $id = Yii::$app->request->get('id');
        $stat = Yii::$app->request->get('stat');

        $model = AmazonInbox::findOne($id);

        if (!$model) {
            $this->_showMessage('未找此邮件!', false);
        }

        if ($stat == '1') {
            if ($model->is_read == '2') {
                $this->_showMessage('已读!', true);
            }

            $model->is_read = 1;
            $model->save();
        }

        if ($stat == '2') {
            if ($model->is_replied == '2') {
                $this->_showMessage('已回复已同步!', true);
            }

            $model->is_replied = 1;
            $model->save();

            $inboxs = '';
            if (isset($model->inbox_subject_id) && !empty($model->inbox_subject_id)) {
                $inboxs = AmazonInbox::find()->where(['inbox_subject_id' => $model->inbox_subject_id, 'is_replied' => 0])->one();
            }

            if ($inboxs) {
                $url_make = Url::toRoute(['/mails/amazoninboxsubject/view', 'id' => $model->inbox_subject_id]);
            } else {
                $subject_model = AmazonInboxSubject::findOne(['id' => $model->inbox_subject_id]);
                $subject_model->is_replied = 1;
                $subject_model->save();
                $url_make = Url::toRoute(['/mails/amazoninboxsubject/view', 'next' => 1]);
            }
        }

        $flag = [
            '1' => '\\Seen',
            '2' => '\\Answered',
        ];

        if (!array_key_exists($stat, $flag)) {
            $this->_showMessage('状态参数有误!', false);
        }

        if ($model->receive_email == null) {
            $isok = true;
        } else {
            $isok = MailBox::instance(trim($model->receive_email))->mailbox->setFlag([$model->mid], $flag[$stat]);
        }

        if ($isok == true) {
            if ($stat == '1') {
                $model->is_read = 2;
            } else {
                $model->is_replied = 2;
            }
            $model->save();

            if ($stat == 1) {
                $this->_showMessage('已读同步成功!', true);
            } else {
                $this->_showMessage('已回复同步成功!', true, $url_make);
            }
        } else {
            $this->_showMessage('操作失败!', false);
        }

        Yii::$app->end();
    }

    // 旧邮件数据补全主题接口
    public function actionCc()
    {
        set_time_limit(120);
        $amazonInbox = AmazonInbox::find()->where(['inbox_subject_id' => 0])->limit(10)->orderby('id DESC')->all();
        if ($amazonInbox) {
            foreach ($amazonInbox as $value) {
                $subject_model = AmazonInboxSubject::getSubjectInfo($value);
                if ($subject_model !== false) {
                    $value->inbox_subject_id = $subject_model->id;
                    $value->save();
                }
            }
        }
    }


    /**
     * 给邮件主题添加备注
     */
    public function actionAddremark()
    {
        $params = Yii::$app->request->post();

        if (empty($params['id'])) {
            die(json_encode([
                'code' => 0,
                'message' => '邮件ID不能为空',
            ]));
        }

        if (empty($params['remark'])) {
            die(json_encode([
                'code' => 0,
                'message' => '备注不能为空',
            ]));
        }

        $inbox = AmazonInbox::findOne($params['id']);
        if (empty($inbox)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到邮件信息',
            ]));
        }

        $inbox->remark = $params['remark'];
        if ($inbox->save()) {
            die(json_encode([
                'code' => 1,
                'message' => '添加成功',
                'data' => $params,
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '添加失败',
            ]));
        }
    }

    /**
     * 清空邮件主题备注
     */
    public function actionClearremark()
    {
        $params = Yii::$app->request->post();

        if (empty($params['id'])) {
            die(json_encode([
                'code' => 0,
                'message' => '邮件ID不能为空',
            ]));
        }

        $inbox = AmazonInbox::findOne($params['id']);
        if (empty($inbox)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到邮件信息',
            ]));
        }

        $inbox->remark = '';
        if ($inbox->save()) {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '失败',
            ]));
        }
    }

    /**
     * 导出Excel
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
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_AMAZON);

        if (is_array($ids) && !empty($ids)) {
            //取出选中的数据
            $data = AmazonInboxSubject::find()
                ->alias('i')
                ->select('i.*, a.account_name')
                ->leftJoin(['a' => Account::tableName()], 'a.id = i.account_id')
                ->andWhere(['in', 'i.id', $ids])
                ->andWhere(['in', 'i.account_id', $accountIds])
                ->orderBy('i.receive_date DESC')
                ->asArray()
                ->all();

        } else {
            //取出筛选的数据
            $query = AmazonInboxSubject::find()
                ->alias('i')
                ->select('i.*, a.account_name')
                ->leftJoin(['a' => Account::tableName()], 'a.id = i.account_id')
                ->andWhere(['in', 'i.account_id', $accountIds])
                ->orderBy('i.receive_date DESC');

            if (!empty($get['now_subject'])) {
                $query->andWhere(['like', 'i.now_subject', $get['now_subject']]);
            }
            if (!empty($get['order_id'])) {
                $query->andWhere(['i.order_id' => $get['order_id']]);
            }
            if (isset($get['is_read']) && $get['is_read'] != '') {
                $query->andWhere(['i.is_read' => $get['is_read']]);
            }
            if (isset($get['is_replied']) && $get['is_replied'] != '') {
                $query->andWhere(['i.is_replied' => $get['is_replied']]);
            }
            if (!empty($get['buyer_id'])) {
                $query->andWhere(['i.buyer_id' => $get['buyer_id']]);
            }
            if (!empty($get['sender_email'])) {
                $query->andWhere(['i.sender_email' => $get['sender_email']]);
            }
            if (!empty($get['account_id'])) {
                $query->andWhere(['i.account_id' => $get['account_id']]);
            }
            if (!empty($get['receive_email'])) {
                $query->andWhere(['i.receive_email' => $get['receive_email']]);
            }
            if (isset($get['type_mark']) && $get['type_mark'] != '') {
                $query->andWhere(['i.type_mark' => $get['type_mark']]);
            }
            if (!empty($get['start_time']) && !empty($get['end_time'])) {
                $query->andWhere(['between', 'i.receive_date', $get['start_time'], $get['end_time']]);
            } else if (!empty($get['start_time'])) {
                $query->andWhere(['>=', 'i.receive_date', $get['start_time']]);
            } else if (!empty($get['end_time'])) {
                $query->andWhere(['<=', 'i.receive_date', $get['end_time']]);
            }

            if (isset($get['tag_id']) && !empty($get['tag_id'])) {
                $query->innerJoin(['t' => MailSubjectTag::tableName()], 't.subject_id = i.id AND t.platform_code = :platform_code1', ['platform_code1' => Platform::PLATFORM_CODE_AMAZON])
                    ->andWhere(['t.tag_id' => $get['tag_id']]);
            }

            if (isset($get['site_id']) && !empty($get['site_id'])) {
                $query->innerJoin(['m' => InboxSubjectSite::tableName()], 'm.inbox_subject_id = i.id AND m.platform_code = :platform_code2', ['platform_code2' => Platform::PLATFORM_CODE_AMAZON])
                    ->andWhere(['m.site_id' => $get['site_id']]);
            }

            $data = $query->asArray()->all();
        }

        //标题数组
        $fieldArr = [
            '订单ID',
            '主题',
            '买家ID',
            '买家邮箱',
            '是否已读',
            '是否已回复',
            '帐号',
            '接收邮箱',
            '最新收件时间',
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
                    case 2:
                        $repliedStatus = '标记回复';
                        break;
                }

                //回复人/回复时间
                $replyBy = '';
                $replyTime = '';
                $inboxIds = AmazonInbox::find()
                    ->select('id')
                    ->where(['inbox_subject_id' => $item['id']])
                    ->asArray()
                    ->column();
                if (!empty($inboxIds)) {
                    $reply = AmazonReply::find()
                        ->select('reply_by, modify_time')
                        ->where(['in', 'inbox_id', $inboxIds])
                        ->orderBy('modify_time DESC')
                        ->asArray()
                        ->one();
                    if (!empty($reply)) {
                        $replyBy = $reply['reply_by'];
                        $replyTime = $reply['modify_time'];
                    }
                }

                $dataArr[] = [
                    $item['order_id'],
                    $item['now_subject'],
                    $item['buyer_id'],
                    $item['sender_email'],
                    ($item['is_read'] == 1 ? '是' : '否'),
                    $repliedStatus,
                    $item['account_name'],
                    $item['receive_email'],
                    $item['receive_date'],
                    $replyBy,
                    $replyTime,
                ];
            }
        }

        VHelper::exportExcel($fieldArr, $dataArr, 'amazoninboxsubject_' . date('Y-m-d'));
    }

    /**
     * 获取邮件提醒
     */
    public function actionGetamazonmailnotify()
    {
        $params = Yii::$app->request->post();

        $query = AmazonInboxSubject::find()
            ->alias('s')
            ->select('s.id, s.now_subject, s.buyer_id, s.is_read, s.is_replied, s.type_mark')
            ->distinct()
            ->andWhere(['s.type_mark' => 12])
            ->andWhere(['s.is_replied' => 0])
            ->orderBy('s.id DESC');

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 's.receive_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 's.receive_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 's.receive_date', $params['end_time']]);
        }

        //获取当前客服绑定的账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_AMAZON);
        $query->andWhere(['in', 's.account_id', $accountIds]);

        $data = $query->asArray()->all();
        foreach ($data as &$val){
            $val['type_mark']=AmazonInboxSubject::amazonMailType($val['type_mark']);
        }

        if (!empty($data)) {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
                'data' => $data,
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '失败',
            ]));
        }
    }

    /**
     * 邮件主题添加备注
     */
    public function actionOperationremark()
    {
        $arr = ['status' => TRUE, 'info' => '操作成功!'];
        $request = Yii::$app->request->post();
        $id = trim($request['id']);
        $remark = trim($request['remark']);
        if (AmazonInboxSubject::updateAll(['remark' => $remark], 'id=:id', [':id' => $id]) === FALSE) {
            $arr = ['status' => FALSE, 'info' => '操作失败!'];
        }
        die(json_encode($arr));
    }

    /**
     * 移至客户来信
     */
    public function actionMoveclientletter()
    {
        $id = Yii::$app->request->get('id', 0);

        if (empty($id)) {
            $this->_showMessage('ID不能为空', false);
        }

        $subject = AmazonInboxSubject::findOne($id);
        if (empty($subject)) {
            $this->_showMessage('没有找到邮件主题', false);
        }

        //邮件分类定义，具体看MailFilterManage::getMailTypeList方法
        $subject->type_mark = 11;
        $subject->modify_by = Yii::$app->user->identity->user_name;
        $subject->modify_time = date('Y-m-d H:i:s');

        if (!$subject->save()) {
            $this->_showMessage('移至客户来信失败', false);
        } else {
            $this->_showMessage('移至客户来信成功', true);
        }
    }
}
