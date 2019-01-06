<?php

namespace app\modules\mails\controllers;

use app\modules\mails\models\AmazonInboxSubject;
use Yii;
use app\modules\mails\models\AmazonReply;
use app\modules\mails\models\AmazonInbox;
use app\modules\mails\models\MailOutbox;
use app\modules\mails\models\AmazonReplyAttachment;
use app\modules\accounts\models\Platform;
use yii\data\ActiveDataProvider;
use app\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\modules\mails\models\MailTemplateStrReplacement;
use yii\web\UploadedFile;
use yii\helpers\Url;

/**
 * AmazonreplyController implements the CRUD actions for AmazonReply model.
 */
class AmazonreplyController extends Controller
{
    const UPLOAD_IMAGE_PAHT = 'attachments/reply/';

    //附件的最大大小(10M)
    const ATTACHMENT_MAX_SIZE = 10485760;

    /**
     * @inheritdoc
     */
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
    }

    /**
     * Lists all AmazonReply models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => AmazonReply::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single AmazonReply model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new AmazonReply model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $this->isPopup = true;

        $inboxid = Yii::$app->request->get('id');
        $next = Yii::$app->request->get('next');
        $inbox = AmazonInbox::findOne([
            'id' => $inboxid,
        ]);

        if (!$inbox) {
            throw new NotFoundHttpException('Not Found!');
        }

        $model = new AmazonReply();

        $model->inbox_id = $inbox->id;
        $model->reply_title = sprintf('RE:%s', $inbox->subject);
        $model->reply_content = $inbox->body;

        $receiver = sprintf('%s <%s>', $inbox->sender, $inbox->sender_email);

        if (isset($_POST['AmazonReply'])) {
            //开启事物保障数据的完整性

            $transaction = AmazonReply::getDb()->beginTransaction();//\Yii::$app->db->beginTransaction();
            $model->load(Yii::$app->request->post());
            //$model->reply_content = MailTemplateStrReplacement::replaceContent($model->reply_content,
            //Platform::PLATFORM_CODE_AMAZON, $inbox->order_id);
            //保存回复表成功再保存mail_outbox表数据
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                //邮件附件
                $attachmentDir = Yii::$app->basePath . '/web/attachments/reply/' . date('Ymd', time()) . '/';
                if (!is_dir($attachmentDir)) mkdir($attachmentDir, 0777, true);

                $m = UploadedFile::getInstancesByName('AmazonReply');
                $attachments = [];
                foreach ($m as $file) {
                    //$safeFilename = str_replace(' ', '-', $file->name);
                    $filename = $file->name;
                    $oldPath = $attachmentDir . $filename . '_' . substr(md5(time()), 8, 16) . '.' . $file->extension;
                    $newPath = mb_convert_encoding($oldPath, 'gb2312', 'UTF-8');;
                    $isok = $file->saveAs($newPath);
                    //$path = mb_convert_encoding($newPath, 'UTF-8', 'gb2312');
                    if (!rename($newPath, $oldPath)) {
                        if (copy($newPath, $oldPath))
                            unlink($newPath);
                    }
                    $attachments[] = $oldPath;
                    if ($isok) {
                        $replyAttachment = new AmazonReplyAttachment();
                        $replyAttachment->amazon_reply_id = (int)$model->id;
                        $replyAttachment->name = $filename;
                        $replyAttachment->file_path = str_replace(Yii::$app->basePath . '/web', '', $oldPath);
                        $replyAttachment->save();
                    }
                }

                //保存mail_outbox表数据
                $result_outbox = $this->save_mail_outbox($transaction, $model, $inbox, $attachments);

                if (empty($result_outbox)) {
                    $transaction->rollBack();
                    $this->_showMessage(\Yii::t('system', 'Operate mail outbox Failed'), false);
                }

                //修改邮件的状态值

                $result_inbox = $this->update_Inbox_is_replied($transaction, $inbox);

                if (!$result_inbox) {
                    $transaction->rollBack();
                    $this->_showMessage(\Yii::t('system', 'Operate inbox Failed'), false);
                }

                // 邮件回复后找到其主题下面是否还有其他未回复邮件来确定主题的回复状态
                if (isset($inbox->inbox_subject_id) && !empty($inbox->inbox_subject_id)) {
                    $inboxs = AmazonInbox::find()->where(['inbox_subject_id' => $inbox->inbox_subject_id, 'is_replied' => 0])->orderby('receive_date DESC')->one();
                    if (!$inboxs) {
                        $subject_model = AmazonInboxSubject::findOne(['id' => $inbox->inbox_subject_id]);
                        $subject_model->is_replied = 1;
                        $subject_model->save();
                    }
                }

                //成功之后的跳转url
                $url_mark = empty($next) ? '/mails/amazoninbox/index' : Url::toRoute(['/mails/amazoninbox/view', 'next' => 1]);
                $redirectUrl = \yii\helpers\Url::toRoute($url_mark);

                $transaction->commit();
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $redirectUrl);

            } else {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'receiver' => $receiver,
        ]);
    }

    /**
     * Creates a new AmazonReply model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreatesubject()
    {
        $this->isPopup = true;
        $request = Yii::$app->request->post();
        $inbox_id = $request['inbox_id'];   //给回复邮件id
        $reply_title = $request['reply_title']; //回复title
        $reply_content = $request['reply_content'] ? trim($request['reply_content']) : ''; //回复内容 发送给客户的
        $reply_content_en = $request['reply_content_en'] ? trim($request['reply_content_en']) : ' '; //回复内容英文 客服自己看的

        if (empty($reply_content) && empty($reply_content_en)) {
            $this->_showMessage('回复内容不能为空', false);
        } else {
            if (empty($reply_content)) {
                //如果翻译内容为空 则获取翻译前内容
                $reply_content = $reply_content_en;
            }
        }

        // 主题id
        $subject_id = Yii::$app->request->get('id');
        $next = Yii::$app->request->get('next');

        $userinfo = Yii::$app->user;
        if (isset($userinfo->identity) && !empty($userinfo->identity)) {
            $reply_by = $userinfo->identity->user_name;
        }


        if (!$subject_id) {
            $this->_showMessage('Not Found this subject!', false);
        }

        $inbox = AmazonInbox::find()->where(['inbox_subject_id' => $subject_id])->orderby('receive_date DESC')->all();

        if (!$inbox) {
            $subject_model = AmazonInboxSubject::findOne($subject_id);
            $subject_model->is_replied = 1;
            $subject_model->save();
            $this->_showMessage('Not Found email which is not replied with this subject!', false);
        }
        if (!$inbox_id) {
            $this->_showMessage('Not Found this email!', false);
        }

        $receiver = sprintf('%s <%s>', $inbox[0]->sender, $inbox[0]->sender_email);

        if ($inbox_id) {
            //开启事物保障数据的完整性
            $transaction = AmazonReply::getDb()->beginTransaction();
            //回复全部情况
            if ($inbox_id == 'all') {
                $inbox_id = AmazonInbox::find()->where(['inbox_subject_id' => $subject_id, 'is_replied' => '0'])->orderby('receive_date DESC')->all();
            }

            if (!is_string($inbox_id)) {
                foreach ($inbox_id as $key => $value) {
                    $model = new AmazonReply();
                    $model->inbox_id = $value->id;
                    $model->reply_title = $reply_title;
                    $model->reply_content_en = $reply_content_en;
                    $model->reply_content = $reply_content;
                    $model->reply_by = $reply_by;
                    $model->is_draft = 1;
                    if ($model->save(false)) {
                        //邮件附件
                        $uploadImages = UploadedFile::getInstancesByName('AmazonReply[file]');
                        //附件
                        $attachments = [];
                        //用于web访问的附件
                        $webAttachments = [];
                        //附件的总大小
                        $atchmentTotalSize = 0;

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
                                    $atchmentTotalSize += $uploadImage->size;
                                }
                            }
                        }

                        //判断上传文件的总大小
                        if ($atchmentTotalSize > self::ATTACHMENT_MAX_SIZE) {
                            $this->_showMessage('上传附件总大小超过' . (self::ATTACHMENT_MAX_SIZE / 1048576) . 'M', false);
                        }

                        if (!empty($webAttachments)) {
                            foreach ($webAttachments as $webAttachment) {
                                $replyAttachment = new AmazonReplyAttachment();
                                $replyAttachment->amazon_reply_id = $model->id;
                                $replyAttachment->name = $webAttachment['name'];
                                $replyAttachment->file_path = $webAttachment['file_path'];
                                $replyAttachment->save();
                            }
                        }

                        //保存mail_outbox表数据
                        $result_outbox = $this->save_mail_outbox($transaction, $model, $value, $attachments);

                        if (empty($result_outbox)) {
                            $transaction->rollBack();
                            $this->_showMessage(\Yii::t('system', 'Operate mail outbox Failed'), false);
                        }
                    } else {
                        $transaction->rollBack();
                        $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                    }

                    //修改邮件的状态值
                    $result_inbox = $this->update_Inbox_is_replied($transaction, $value);

                    if (!$result_inbox) {
                        $transaction->rollBack();
                        $this->_showMessage(\Yii::t('system', 'Operate inbox Failed'), false);
                    }
                }
                $subject_model = AmazonInboxSubject::findOne($subject_id);
                $subject_model->is_replied = 1;
                if (!$subject_model->save()) {
                    $transaction->rollBack();
                    $this->_showMessage(\Yii::t('system', 'Operate subject Failed'), false);
                }
                $url_mark = empty($next) ? '/mails/amazoninboxsubject/index' : Url::toRoute(['/mails/amazoninboxsubject/view', 'next' => 1]);
            } else {
                $model = new AmazonReply();
                $model->inbox_id = $inbox_id;
                $model->reply_title = $reply_title;
                $model->reply_content_en = $reply_content_en;
                $model->reply_content = $reply_content;
                $model->reply_by = $reply_by;
                $model->is_draft = 1;

                if ($model->save(false)) {

                    $uploadImages = UploadedFile::getInstancesByName('AmazonReply[file]');
                    //附件
                    $attachments = [];
                    //用于web访问的附件
                    $webAttachments = [];
                    //附件的总大小
                    $atchmentTotalSize = 0;

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
                                $atchmentTotalSize += $uploadImage->size;
                            }
                        }
                    }

                    //判断上传文件的总大小
                    if ($atchmentTotalSize > self::ATTACHMENT_MAX_SIZE) {
                        $this->_showMessage('上传附件总大小超过' . (self::ATTACHMENT_MAX_SIZE / 1048576) . 'M', false);
                    }

                    if (!empty($webAttachments)) {
                        foreach ($webAttachments as $webAttachment) {
                            $replyAttachment = new AmazonReplyAttachment();
                            $replyAttachment->amazon_reply_id = $model->id;
                            $replyAttachment->name = $webAttachment['name'];
                            $replyAttachment->file_path = $webAttachment['file_path'];
                            $replyAttachment->save();
                        }
                    }
                    //保存mail_outbox表数据
                    $result_outbox = $this->save_mail_outbox($transaction, $model, $inbox[0], $attachments);

                    if (empty($result_outbox)) {
                        $transaction->rollBack();
                        $this->_showMessage(\Yii::t('system', 'Operate mail outbox Failed'), false);
                    }
                } else {
                    $transaction->rollBack();
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                }

                $inbox_model = AmazonInbox::find()->where(['id' => $inbox_id, 'is_replied' => '0'])->one();
                if ($inbox_model) {  //针对已回复邮件再次回复
                    //修改邮件的状态值
                    $result_inbox = $this->update_Inbox_is_replied($transaction, $inbox_model);
                    if (!$result_inbox) {
                        $transaction->rollBack();
                        $this->_showMessage(\Yii::t('system', 'Operate inbox Failed'), false);
                    }
                }


                // 如果回复邮件，查询是否还有未回复的邮件
                $inbox = AmazonInbox::findOne(['inbox_subject_id' => $subject_id, 'is_replied' => 0]);

                // 该主题没有未回复邮件，则跳转到下一个主题
                if (!$inbox) {
                    $subject_model = AmazonInboxSubject::findOne($subject_id);
                    $subject_model->is_replied = 1;
                    if (!$subject_model->save()) {
                        $transaction->rollBack();
                        $this->_showMessage(\Yii::t('system', 'Operate subject Failed'), false);
                    }
                    $url_mark = empty($next) ? '/mails/amazoninboxsubject/index' : Url::toRoute(['/mails/amazoninboxsubject/view', 'next' => 1]);
                }
            }

            $redirectUrl = \yii\helpers\Url::toRoute($url_mark);

            $transaction->commit();
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $redirectUrl);

        } else {
            // 回复主题跳转到下一个主题
            $subject_model = AmazonInboxSubject::findOne($subject_id);
            $subject_model->is_replied = 1;
            if (!$subject_model->save()) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'Operate subject Failed'), false);
            }
            //成功之后的跳转url
            $url_mark = empty($next) ? '/mails/amazoninboxsubject/index' : Url::toRoute(['/mails/amazoninboxsubject/view', 'next' => 1]);
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
        $model->platform_code = Platform::PLATFORM_CODE_AMAZON;
        $model->inbox_id = $inbox_model->id;
        $model->account_id = $inbox_model->account_id;
        $model->reply_id = $reply_model->id;
        $model->subject = $inbox_model->subject;
        $model->buyer_id = $inbox_model->sender;
        $model->receive_email = $inbox_model->sender_email;
        $model->platform_order_id = $inbox_model->order_id;

        //回复内容 
        $content = '';
        if (!empty($reply_model->reply_content)) {
            $content = $reply_model->reply_content;
        } else {
            $content = $reply_model->reply_content_en;
        }
        $model->content = $content;
        $model->send_status = MailOutbox::SEND_STATUS_WAITTING;
        $model->send_params = \yii\helpers\Json::encode([
            'sender_email' => $inbox_model->receive_email,
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
        $inbox->is_replied = AmazonInbox::IS_REPLIED_YES_NO_SYNCHRO;
        $inbox->reply_date = date('Y-m-d H:i:s', time());

        $result = $inbox->save();

        if (!$result) {
            $transaction->rollBack();
            $this->_showMessage(current(current($inbox->getErrors())), false);
        }

        return $result;
    }

    /**
     * Updates an existing AmazonReply model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing AmazonReply model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the AmazonReply model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AmazonReply the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AmazonReply::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
