<?php

namespace app\modules\mails\controllers;

use Yii;
use app\modules\mails\models\AmazonSendMail;
use yii\data\ActiveDataProvider;
use app\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\web\UploadedFile;

use app\modules\mails\models\MailOutbox;
use app\modules\mails\models\AmazonFBAReturn;
use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonSendMailAttachment;
use app\modules\orders\models\Order;
use app\modules\accounts\models\Platform;

/**
 * AmazonsendmailController implements the CRUD actions for AmazonSendMail model.
 */
class AmazonsendmailController extends Controller
{
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
     * Lists all AmazonSendMail models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => AmazonSendMail::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single AmazonSendMail model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new AmazonSendMail model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $this->isPopup = true;

        $id   = Yii::$app->request->get('id');
        $type = Yii::$app->request->get('type');

        switch ($type) {
            case 'Feedback':
                $record = AmazonFeedBack::find()->where(['id' => $id])->one();

                if (!$record)
                    $this->_showMessage(Yii::t('system', 'Record Not Found'), true, null, false, null,null);

                $typeId = 1;
                $toEmail = $record->rater_email;

                break;

            case 'FbaReturn':
                $record = AmazonFBAReturn::find()->where(['id' => $id])->one();

                if (!$record)
                    $this->_showMessage(Yii::t('system', 'Record Not Found'), true, null, false, null,null);

                $typeId = 2;
                $orderInfo = Order::getOrderStack(Platform::PLATFORM_CODE_AMAZON, $record->order_id);
                $toEmail = '';
                if (!empty($orderInfo) && isset($orderInfo->info))
                    $toEmail = $orderInfo->info->email;
                if (!$toEmail) $this->_showMessage('Not Buyer Email', false);

                break;
            
            default:
                throw new \Exception("Error Processing Request", 1);
                break;
        }



        $model = new AmazonSendMail();
        $model->type = $typeId;
        $model->order_id = $record->order_id;
        $model->from = $record->account->email;
        $model->to = $toEmail;
        $model->subject = sprintf('Order information from Amazon seller (Order:%s)', $record->order_id);

        $transaction = AmazonSendMail::getDb()->beginTransaction();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            //邮件附件
            $attachmentDir = Yii::$app->basePath .'/web/attachments/sendmail/'.date('Ymd',time()).'/';
            if (!is_dir($attachmentDir)) mkdir($attachmentDir, 0777, true);

            $attachments = [];
            $uploadedFiles = UploadedFile::getInstancesByName('AmazonSendMail');

            foreach ($uploadedFiles as $file) {
                $path = $attachmentDir . sprintf('%s_%s.%s', $file->name, substr(md5(time()),8,16), $file->extension);
                $isok = $file->saveAs($path);

                $attachments[] = $path;
                if ($isok) {
                    $replyAttachment = new AmazonSendMailAttachment();
                    $replyAttachment->amazon_sendmail_id= (int)$model->id;
                    $replyAttachment->name = $file->name;
                    $replyAttachment->file_path = str_replace(Yii::$app->basePath .'/web', '', $path);

                    if (!$replyAttachment->save()) {
                        $transaction->rollBack();
                        $this->_showMessage(current(current($replyAttachment->getErrors())), false);
                    }
                }
            }

            //Out Box
            $outbox = new MailOutbox();
            $outbox->platform_code = Platform::PLATFORM_CODE_AMAZON;
            //$outbox->inbox_id = -1;
            //$outbox->reply_id = -1;
            $outbox->subject = $model->subject;
            $outbox->content = $model->body;
            $outbox->send_status = MailOutbox::SEND_STATUS_WAITTING;
            $outbox->send_params = \yii\helpers\Json::encode([
                'sender_email'  => $model->from,
                'receive_email' => $model->to,
                'attachments'   => $attachments,
                ]);

            if (!$outbox->save()) {
                $transaction->rollBack();
                $this->_showMessage(current(current($outbox->getErrors())), false); 
            }
            
            $outboxId = $outbox->id;

            //事务回流
            if (!$outboxId) {
                $transaction->rollBack();
                $this->_showMessage(current(current($outbox->getErrors())), false);
            }

            $transaction->commit();
            $this->_showMessage(Yii::t('system', 'Send Email Successfully'), true, null, false, null, null);
        } else {
            return $this->render('create', [
                'model' => $model,
                'id' => $id,
                'type' => $type,
            ]);
        }
    }

    /**
     * Updates an existing AmazonSendMail model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
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
     * Deletes an existing AmazonSendMail model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the AmazonSendMail model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return AmazonSendMail the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AmazonSendMail::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
