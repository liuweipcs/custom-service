<?php

namespace app\modules\mails\controllers;

use app\modules\mails\models\WalmartInboxAttachment;
use app\modules\orders\models\OrderOtherKefu;
use Yii;
use app\common\VHelper;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\web\UploadedFile;
use app\components\Controller;
use app\modules\mails\models\WalmartInbox;
use app\modules\mails\models\WalmartInboxSubject;
use app\modules\mails\models\WalmartReply;
use app\modules\mails\models\WalmartReplyAttachment;
use app\modules\mails\models\MailOutbox;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\services\modules\amazon\components\Mail;
use app\modules\mails\models\ActiveSendEmail;
use app\modules\mails\models\MailTemplate;
use app\modules\systems\models\Email;

class WalmartreplyController extends Controller
{
    const UPLOAD_IMAGE_PAHT = 'attachments/reply/';

    /**
     * @return string
     */
    public function actionCreate()
    {
        $this->isPopup = true;

        $inboxid = Yii::$app->request->get('id', 0);
        $next = Yii::$app->request->get('next');
        if (empty($inboxid)) {
            $this->_showMessage('邮件ID不能为空', false);
        }
        $inbox = WalmartInbox::findOne(['id' => $inboxid]);
        if (empty($inbox)) {
            $this->_showMessage('没有找到该邮件', false);
        }

        $model = new WalmartReply();
        $model->inbox_id = $inbox->id;
        $model->reply_title = sprintf('RE:%s', $inbox->subject);
        $model->reply_content = $inbox->body;

        $receiver = sprintf('%s <%s>', $inbox->sender, $inbox->sender_email);

        if (isset($_POST['WalmartReply'])) {
            //开启事物保障数据的完整性
            $transaction = WalmartReply::getDb()->beginTransaction();
            $model->load(Yii::$app->request->post());

            //保存回复表成功再保存mail_outbox表数据
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                //邮件附件
                $attachmentDir = Yii::$app->basePath . '/web/attachments/reply/' . date('Ymd', time()) . '/';
                if (!is_dir($attachmentDir)) {
                    mkdir($attachmentDir, 0777, true);
                }
                $m = UploadedFile::getInstancesByName('WalmartReply');
                $attachments = [];
                foreach ($m as $file) {
                    $filename = $file->name;
                    $oldPath = $attachmentDir . $filename . '_' . substr(md5(time()), 8, 16) . '.' . $file->extension;
                    $newPath = mb_convert_encoding($oldPath, 'gb2312', 'UTF-8');;
                    $isok = $file->saveAs($newPath);
                    if (!rename($newPath, $oldPath)) {
                        if (copy($newPath, $oldPath)) {
                            unlink($newPath);
                        }
                    }
                    $attachments[] = $oldPath;
                    if ($isok) {
                        $replyAttachment = new WalmartReplyAttachment();
                        $replyAttachment->walmart_reply_id = $model->id;
                        $replyAttachment->name = $filename;
                        $replyAttachment->file_path = str_replace(Yii::$app->basePath . '/web', '', $oldPath);
                        $replyAttachment->save();
                    }
                }

                //保存mail_outbox表数据
                $result_outbox = $this->save_mail_outbox($transaction, $model, $inbox, $attachments);
                if (empty($result_outbox)) {
                    $transaction->rollBack();
                    $this->_showMessage('保存发送邮件失败', false);
                }

                //修改邮件的状态值
                $result_inbox = $this->update_Inbox_is_replied($transaction, $inbox);
                if (empty($result_inbox)) {
                    $transaction->rollBack();
                    $this->_showMessage('修改邮件状态失败', false);
                }

                //邮件回复后找到其主题下面是否还有其他未回复邮件来确定主题的回复状态
                if (isset($inbox->inbox_subject_id) && !empty($inbox->inbox_subject_id)) {
                    $inboxs = WalmartInbox::find()->where([
                        'inbox_subject_id' => $inbox->inbox_subject_id,
                        'is_replied' => 0
                    ])
                        ->orderby('receive_date DESC')
                        ->one();
                    if (empty($inboxs)) {
                        $subject_model = WalmartInboxSubject::findOne(['id' => $inbox->inbox_subject_id]);
                        $subject_model->is_replied = 1;
                        $subject_model->save();
                    }
                }

                //成功之后的跳转url
                $url_mark = empty($next) ? '/mails/walmartinbox/index' : Url::toRoute(['/mails/walmartinbox/view', 'next' => 1]);
                $redirectUrl = Url::toRoute($url_mark);

                $transaction->commit();
                $this->_showMessage('回复成功', true, $redirectUrl);

            } else {
                $transaction->rollBack();
                $this->_showMessage('回复失败', false);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'receiver' => $receiver,
        ]);
    }

    /**
     * 回复邮件主题
     */
    public function actionCreatesubject()
    {
        $this->isPopup = true;

        $request = Yii::$app->request->post();
        $inbox_id = $request['inbox_id'];   //给回复邮件id
        $reply_title = $request['reply_title']; //回复title
        $reply_content = $request['reply_content'] ? trim($request['reply_content']) : ''; //回复内容
        $reply_content_en = $request['reply_content_en'] ? trim($request['reply_content_en']) : ' '; //回复内容英文 

        if (empty($reply_content) && empty($reply_content_en)) {
            $this->_showMessage('回复内容不能为空', false);
        } else {
            if (empty($reply_content)) {
                //如果翻译内容为空 则获取翻译前内容
                $reply_content = $reply_content_en;
            }
        }

        //主题id
        $subject_id = Yii::$app->request->get('id');
        $next = Yii::$app->request->get('next');
        $userinfo = Yii::$app->user;
        if (isset($userinfo->identity) && !empty($userinfo->identity)) {
            $reply_by = $userinfo->identity->user_name;
        }

        if (empty($subject_id)) {
            $this->_showMessage('没有找到邮件主题', false);
        }

        //获取邮件
        $inbox = WalmartInbox::find()->where(['inbox_subject_id' => $subject_id])->orderby('receive_date DESC')->all();
        if (empty($inbox)) {
            $this->_showMessage('没有找到该主题的邮件', false);
        }
        //收件人和收件人邮箱
        $receiver = sprintf('%s <%s>', $inbox[0]->sender, $inbox[0]->sender_email);
        //开启事物保障数据的完整性
        $transaction = WalmartReply::getDb()->beginTransaction();
        if ($inbox_id) {

            //回复全部情况
            if ($inbox_id == 'all') {
                $inbox_id = WalmartInbox::find()
                    ->where(['inbox_subject_id' => $subject_id, 'is_replied' => '0'])
                    ->orderby('receive_date DESC')
                    ->all();
            }

            if (!is_string($inbox_id)) {
                foreach ($inbox_id as $key => $value) {
                    $model = new WalmartReply();
                    $model->inbox_id = $value->id;
                    $model->reply_title = $reply_title;
                    $model->reply_content_en = $reply_content_en;
                    $model->reply_content = $reply_content;
                    $model->reply_by = $reply_by;
                    $model->is_draft = 1;
                    if ($model->save(false)) {

                        $uploadImages = UploadedFile::getInstancesByName('WalmartReply[file]');

                        //附件
                        $attachments = [];
                        //用于web访问的附件
                        $webAttachments = [];
                        if (!empty($uploadImages)) {
                            $filePath = self::UPLOAD_IMAGE_PAHT . date('Ymd') . '/';
                            //判断文件上传路径，如果不存在，则创建
                            if (!file_exists($filePath)) {
                                @mkdir($filePath, 0777, true);
                                @chmod($filePath, 0777);
                            }

                            foreach ($uploadImages as $uploadImage) {
                                //文件名，我们通过md5文件名加上扩展名
                                $fileName = md5($uploadImage->baseName . microtime(true)) . '.' . $uploadImage->extension;
                                $file = $filePath . $fileName;
                                //保存文件到我们的服务器上
                                if ($uploadImage->saveAs($file)) {
                                    $attachments[] = Yii::getAlias('@webroot') . '/' . ltrim($file, '/');
                                    $webAttachments[] = [
                                        'name' => $uploadImage->name,
                                        'file_path' => '/' . ltrim($file, '/'),
                                    ];
                                }
                            }
                        }

                        if (!empty($webAttachments)) {
                            foreach ($webAttachments as $webAttachment) {
                                $replyAttachment = new WalmartReplyAttachment();
                                $replyAttachment->walmart_reply_id = $model->id;
                                $replyAttachment->name = $webAttachment['name'];
                                $replyAttachment->file_path = $webAttachment['file_path'];
                                $replyAttachment->save();
                            }
                        }

                        //保存mail_outbox表数据
                        $result_outbox = $this->save_mail_outbox($transaction, $model, $value, $attachments);

                        if (empty($result_outbox)) {
                            $transaction->rollBack();
                            $this->_showMessage('保存发送邮件失败', false);
                        }
                    } else {
                        $transaction->rollBack();
                        $this->_showMessage('添加回复失败', false);
                    }

                    //修改邮件的状态值
                    $result_inbox = $this->update_Inbox_is_replied($transaction, $value);

                    if (!$result_inbox) {
                        $transaction->rollBack();
                        $this->_showMessage('修改邮件状态失败', false);
                    }
                }
                $subject_model = WalmartInboxSubject::findOne($subject_id);
                $subject_model->is_replied = 1;
                if (!$subject_model->save()) {
                    $transaction->rollBack();
                    $this->_showMessage('保存邮件主题状态失败', false);
                }
                //成功之后的跳转url
                $url_mark = empty($next) ? '/mails/walmartinboxsubject/list' : Url::toRoute(['/mails/walmartinboxsubject/view', 'next' => 1]);

            } else {
                $model = new WalmartReply();
                $model->inbox_id = $inbox_id;
                $model->reply_title = $reply_title;
                $model->reply_content_en = $reply_content_en;
                $model->reply_content = $reply_content;
                $model->reply_by = $reply_by;
                $model->is_draft = 1;

                if ($model->save()) {

                    $uploadImages = UploadedFile::getInstancesByName('WalmartReply[file]');
                    //附件
                    $attachments = [];
                    //用于web访问的附件
                    $webAttachments = [];

                    if (!empty($uploadImages)) {
                        $filePath = self::UPLOAD_IMAGE_PAHT . date('Ymd') . '/';
                        //判断文件上传路径，如果不存在，则创建
                        if (!file_exists($filePath)) {
                            @mkdir($filePath, 0777, true);
                            @chmod($filePath, 0777);
                        }

                        foreach ($uploadImages as $uploadImage) {
                            //文件名，我们通过md5文件名加上扩展名
                            $fileName = md5($uploadImage->baseName . microtime(true)) . '.' . $uploadImage->extension;
                            $file = $filePath . $fileName;
                            //保存文件到我们的服务器上
                            if ($uploadImage->saveAs($file)) {
                                $attachments[] = Yii::getAlias('@webroot') . '/' . ltrim($file, '/');
                                $webAttachments[] = [
                                    'name' => $uploadImage->name,
                                    'file_path' => '/' . ltrim($file, '/'),
                                ];
                            }
                        }
                    }

                    if (!empty($webAttachments)) {
                        foreach ($webAttachments as $webAttachment) {
                            $replyAttachment = new WalmartReplyAttachment();
                            $replyAttachment->walmart_reply_id = $model->id;
                            $replyAttachment->name = $webAttachment['name'];
                            $replyAttachment->file_path = $webAttachment['file_path'];
                            $replyAttachment->save();
                        }
                    }

                    //保存mail_outbox表数据
                    $result_outbox = $this->save_mail_outbox($transaction, $model, $inbox[0], $attachments);

                    if (empty($result_outbox)) {
                        $transaction->rollBack();
                        $this->_showMessage('保存发送邮件失败', false);
                    }
                } else {
                    $transaction->rollBack();
                    $this->_showMessage('添加回复失败', false);
                }

                $inbox_model = WalmartInbox::find()->where(['id' => $inbox_id, 'is_replied' => '0'])->one();
                if ($inbox_model) {  //针对已回复邮件再次回复
                    //修改邮件的状态值
                    $result_inbox = $this->update_Inbox_is_replied($transaction, $inbox_model);
                    if (!$result_inbox) {
                        $transaction->rollBack();
                        $this->_showMessage('修改邮件状态失败', false);
                    }
                }
                // 如果回复邮件，查询是否还有未回复的邮件
                $inbox = WalmartInbox::findOne(['inbox_subject_id' => $subject_id, 'is_replied' => 0]);

                // 该主题没有未回复邮件，则跳转到下一个主题
                if (!$inbox) {
                    $subject_model = WalmartInboxSubject::findOne($subject_id);
                    $subject_model->is_replied = 1;
                    if (!$subject_model->save()) {
                        $transaction->rollBack();
                        $this->_showMessage('保存邮件主题状态失败', false);
                    }
                    $url_mark = empty($next) ? '/mails/walmartinboxsubject/index' : Url::toRoute(['/mails/walmartinboxsubject/view', 'next' => 1]);
                }
            }

            $redirectUrl = \yii\helpers\Url::toRoute($url_mark);

            $transaction->commit();
            $this->_showMessage('回复成功', true, $redirectUrl);

        } else {
            // 回复主题跳转到下一个主题
            $subject_model = WalmartInboxSubject::findOne($subject_id);
            $subject_model->is_replied = 1;
            if (!$subject_model->save()) {
                $transaction->rollBack();
                $this->_showMessage('保存邮件主题状态失败', false);
            }
            //成功之后的跳转url
            $url_mark = empty($next) ? '/mails/walmartinboxsubject/index' : Url::toRoute(['/mails/walmartinboxsubject/view', 'next' => 1]);
            $redirectUrl = \yii\helpers\Url::toRoute($url_mark);
        }


        return $this->render('create', [
            'subject_id' => $subject_id,
            'model' => $model,
            'receiver' => $receiver,
            'subject' => 1,
        ]);
    }

    /**
     * 添加完回复表数据之后接着添加mail_outbox表的数据
     * @param object $transaction 事务对象
     * @param object $reply_model 回复表模型对象
     * @param object $inbox_model 消息表模型对象
     */
    protected function save_mail_outbox($transaction, $reply_model, $inbox_model, $attachments)
    {
        $model = new MailOutbox();
        $model->platform_code = Platform::PLATFORM_CODE_WALMART;
        $model->inbox_id = $inbox_model->id;
        $model->account_id = $inbox_model->account_id;
        $model->reply_id = $reply_model->id;
        $model->subject = $inbox_model->subject;
        $model->buyer_id = $inbox_model->sender;
        $model->receive_email = $inbox_model->sender_email;
        $model->platform_order_id = $inbox_model->order_id;

        //回复内容

        $account_model = Account::find()->select('email')->where(['id' => $inbox_model->account_id, 'platform_code' => Platform::PLATFORM_CODE_WALMART, 'status' => 1])->asArray()->one();

        $email = $account_model['email'];

        $content = '';
        if (!empty($reply_model->reply_content)) {
            $content = $reply_model->reply_content;
        } else {
            $content = $reply_model->reply_content_en;
        }
        $model->content = $content;
        $model->send_status = MailOutbox::SEND_STATUS_WAITTING;
        $model->send_params = Json::encode([
            'sender_email' => $email,//修改邮箱地址
            'receive_email' => $inbox_model->sender_email,
            'order_id' => $inbox_model->order_id,
            'attachments' => $attachments,
        ]);

        if (!$model->save()) {
            $transaction->rollBack();
            $this->_showMessage(current(current($model->getErrors())), false);
        }

        return $model->id;
    }

    /**
     * 新增完回复之后将邮件标记为已回复未同步
     * @param object $transaction 事务对象
     * @param object $inbox 邮件模型
     */
    protected function update_Inbox_is_replied($transaction, $inbox)
    {
        $inbox->is_replied = WalmartInbox::IS_REPLIED_YES_NO_SYNCHRO;
        $inbox->reply_date = date('Y-m-d H:i:s', time());

        $result = $inbox->save();

        if (!$result) {
            $transaction->rollBack();
            $this->_showMessage(current(current($inbox->getErrors())), false);
        }

        return $result;
    }

    /**
     * 跟进账号ID 站点获取站点邮箱
     * @author allen <2018-03-28>
     */
    public function actionGetsendemail()
    {
        //账号ID(客服系统的)
        $accountId = !empty($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) : 0;

        //账号ID(ERP系统的)
        $oldAccountId = !empty($_REQUEST['old_account_id']) ? trim($_REQUEST['old_account_id']) : 0;

        //收件箱
        $toemail = !empty($_REQUEST['toemail']) ? trim($_REQUEST['toemail']) : '';

        //平台订单ID
        $platformOrderId = !empty($_REQUEST['platform_order_id']) ? trim($_REQUEST['platform_order_id']) : '';

        if (!empty($accountId)) {
            $account = Account::findOne($accountId);
        } else if ($oldAccountId) {
            $account = Account::findOne(['platform_code' => Platform::PLATFORM_CODE_WALMART, 'old_account_id' => $oldAccountId]);
        }

        //post请求
        if (Yii::$app->request->isPost) {
            die(json_encode($account->email));
        }

        //谷歌翻译
        $googleLangCode = VHelper::googleLangCode();

        //get请求
        return $this->render('send_email', [
            'fromEmail' => $account->email,
            'toEmail' => $toemail,
            'platformOrderId' => $platformOrderId,
            'accouontId' => $account->id,
            'googleLangCode' => $googleLangCode,
        ]);
    }

    /**
     * 搜索邮件模板
     */
    public function actionSearchmailtemplate()
    {
        $platformCode = Yii::$app->request->post('platform_code', '');
        $name = Yii::$app->request->post('name', '');

        $data = MailTemplate::searchMailTemplate($name, $platformCode);
        if (empty($data)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到邮件模板',
            ]));
        } else {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
                'data' => $data,
            ]));
        }
    }

    /**
     * 发邮件
     * @author allen <2018-03-28>
     */
    public function actionSendemail()
    {
        set_time_limit(0);
        $sku = Yii::$app->request->post('sku', '');
        $senderEmail = Yii::$app->request->post('sender_email', '');
        $receiveEmail = Yii::$app->request->post('receive_email', '');
        $subject = Yii::$app->request->post('subject', '');
        $tag = Yii::$app->request->post('tag', []);
        $replyContentEn = Yii::$app->request->post('reply_content_en', '');
        $replyContent = Yii::$app->request->post('reply_content', '');
        //账号ID(客服系统的)
        $accountId = Yii::$app->request->post('account_id', '');
        $platformOrderId = Yii::$app->request->post('platform_order_id', '');

        if (empty($senderEmail)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '发件人邮箱不能为空',
            ]));
        }
        if (empty($receiveEmail)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '收件人邮箱不能为空',
            ]));
        }
        if (empty($subject)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '主题不能为空',
            ]));
        }
        if (empty($replyContentEn)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '回复内容(英文)不能为空',
            ]));
        }


        $email = Email::findOne(['emailaddress' => $senderEmail]);

        //指定邮箱用亚马逊邮件服务器发送 update by allen str <2018-10-11>
        if($email->is_amazon_send == 1){
            $from = 'email-smtp.us-east-1.amazonaws.com';
        } else {
            $from = $senderEmail;
        }
        //指定邮箱用亚马逊邮件服务器发送 update by allen end <2018-10-11>


        $email = Mail::instance($from);
        if (empty($email)) {
            die(json_encode([
                'bool' => 0,
                'msg' => "实例化{$senderEmail}邮箱失败,请检查邮箱SMTP配置",
            ]));
        }

        $uploadImages = UploadedFile::getInstancesByName('uploadImage');

        //附件
        $attachments = [];
        //用于web访问的附件
        $webAttachments = [];
        if (!empty($uploadImages)) {
            $filePath = self::UPLOAD_IMAGE_PAHT . date('Ymd') . '/';
            //判断文件上传路径，如果不存在，则创建
            if (!file_exists($filePath)) {
                @mkdir($filePath, 0777, true);
                @chmod($filePath, 0777);
            }

            foreach ($uploadImages as $uploadImage) {
                //文件名，我们通过md5文件名加上扩展名
                $fileName = md5($uploadImage->baseName . microtime(true)) . '.' . $uploadImage->extension;
                $file = $filePath . $fileName;
                //保存文件到我们的服务器上
                if ($uploadImage->saveAs($file)) {
                    $attachments[] = Yii::getAlias('@webroot') . '/' . ltrim($file, '/');
                    $webAttachments[] = '/' . ltrim($file, '/');
                }
            }
        }

        //如果回复内容为空，则将回复内容(英文)发送
        $content = $replyContent;
        if (empty($content)) {
            $content = $replyContentEn;
        }
        //将\n替换成<br>
        $content = str_replace("\n", '<br>', $content);

        $email->setTo($receiveEmail)->setSubject($subject)->seHtmlBody($content)->setFrom($senderEmail);
        //添加附件
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $email->addAttach($attachment);
            }
        }
        //发送
        $result = $email->sendmail();

        if (!$result) {
            die(json_encode([
                'bool' => 0,
                'msg' => '错误原因:' . current(Mail::$errorMsg),
            ]));
        }

        //保存主动发送邮件信息
        $active = new ActiveSendEmail();
        $active->account_id = $accountId;
        $active->platform_code = Platform::PLATFORM_CODE_WALMART;
        $active->platform_order_id = $platformOrderId;
        $active->sender_email = $senderEmail;
        $active->receive_email = $receiveEmail;
        $active->title = $subject;
        $active->content = $content;
        $active->sku = $sku;
        $active->tag = implode(',', $tag);
        $active->attachments = json_encode($attachments);
        $active->create_by = Yii::$app->user->identity->login_name;
        $active->create_time = date('Y-m-d H:i:s');
        $active->modify_by = Yii::$app->user->identity->login_name;
        $active->modify_time = date('Y-m-d H:i:s');
        $active->save();

        //主动联系发送的邮件，根据情况创建邮件主题
        if (!empty($platformOrderId)) {
            $orderInfo = OrderOtherKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_WALMART, 'platform_order_id' => $platformOrderId]);

            //判断是否已经存在了主题
            $inboxSubject = WalmartInboxSubject::findOne([
                'order_id' => $platformOrderId,
                'account_id' => $accountId,
            ]);
            if (empty($inboxSubject)) {
                //创建一个新的主题
                $inboxSubject = new WalmartInboxSubject();
                $inboxSubject->order_id = $platformOrderId;
                $inboxSubject->first_subject = $subject;
                $inboxSubject->now_subject = $subject;
                $inboxSubject->buyer_id = !empty($orderInfo) ? $orderInfo->buyer_id : '';
                $inboxSubject->sender_email = $receiveEmail;
                $inboxSubject->account_id = $accountId;
                $inboxSubject->receive_email = $senderEmail;
                $inboxSubject->is_read = 1;
                $inboxSubject->is_replied = 1;
                $inboxSubject->is_attached = !empty($attachment) ? 1 : 0;
                $inboxSubject->receive_date = date('Y-m-d H:i:s');
                $inboxSubject->create_by = Yii::$app->user->identity->login_name;
                $inboxSubject->create_time = date('Y-m-d H:i:s');
                $inboxSubject->modify_by = Yii::$app->user->identity->login_name;
                $inboxSubject->modify_time = date('Y-m-d H:i:s');
                $inboxSubject->save();
            }

            if (!empty($inboxSubject->id)) {
                //创建一个邮件
                $account = Account::findOne($accountId);

                $inbox = new WalmartInbox();
                $inbox->order_id = $platformOrderId;
                $inbox->account_id = $accountId;
                $inbox->subject = $subject;
                $inbox->body = $content;
                $inbox->mail_type = 3;
                $inbox->sender = !empty($account->account_name) ? $account->account_name : '';
                $inbox->sender_email = $senderEmail;
                $inbox->receiver = !empty($orderInfo) ? $orderInfo->buyer_id : '';
                $inbox->receive_email = $receiveEmail;
                $inbox->receive_date = date('Y-m-d H:i:s');
                $inbox->message_time = date('Y-m-d H:i:s');
                $inbox->is_read = 2;
                $inbox->is_replied = 2;
                $inbox->reply_date = date('Y-m-d H:i:s');
                $inbox->create_by = Yii::$app->user->identity->login_name;
                $inbox->create_time = date('Y-m-d H:i:s');
                $inbox->modify_by = Yii::$app->user->identity->login_name;
                $inbox->modify_time = date('Y-m-d H:i:s');
                $inbox->content_type = 2;
                $inbox->inbox_subject_id = $inboxSubject->id;
                if ($inbox->save()) {
                    if (!empty($webAttachments)) {
                        foreach ($webAttachments as $key => $attachment) {
                            $attach = new WalmartInboxAttachment();
                            $attach->walmart_inbox_id = $inbox->id;
                            $attach->name = "附件{$key}";
                            $attach->file_path = $attachment;
                            $attach->save();
                        }
                    }
                    $active->inbox_id = $inbox->id;
                    $active->save();
                }
            }
        }

        die(json_encode([
            'bool' => 1,
            'msg' => '发送成功',
        ]));
    }

}