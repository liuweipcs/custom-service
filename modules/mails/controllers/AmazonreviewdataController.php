<?php

namespace app\modules\mails\controllers;

use app\common\VHelper;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\ActiveSendEmail;
use app\modules\mails\models\AmazonInbox;
use app\modules\mails\models\AmazonInboxAttachment;
use app\modules\mails\models\AmazonInboxSubject;
use app\modules\orders\models\OrderAmazonDetail;
use app\modules\orders\models\OrderAmazonKefu;
use app\modules\orders\models\OrderKefu;
use Yii;
use app\modules\mails\models\AmazonReviewData;
use app\modules\mails\models\AmazonReviewDataSearch;
use app\modules\accounts\models\Account;
use app\modules\mails\models\AmazonReviewLog;
use app\modules\systems\models\BasicConfig;
use app\modules\orders\models\Order;
//use yii\web\Controller;
use app\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use app\modules\services\modules\amazon\components\Mail;
use app\modules\mails\models\MailTemplate;
use yii\web\UploadedFile;

/**
 * AmazonReviewDataController implements the CRUD actions for AmazonReviewData model.
 */
class AmazonreviewdataController extends Controller
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
     * Lists all AmazonReviewData models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AmazonReviewDataSearch();
        $params = Yii::$app->request->queryParams;
        $params['type'] = 1;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $statisticsData = $searchModel->getstatistics();
        $accountList = ['' => '--请选择账号--'] + Account::getAccount('AMAZON', 2);
        $followStatusList = BasicConfig::getParentList(35);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'accountList' => $accountList,
            'statisticsData' => $statisticsData,
            'followStatusList' => $followStatusList,
        ]);
    }

    /**
     * Displays a single AmazonReviewData model.
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
     * Creates a new AmazonReviewData model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AmazonReviewData();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing AmazonReviewData model.
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
     * Deletes an existing AmazonReviewData model.
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
     * Finds the AmazonReviewData model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return AmazonReviewData the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AmazonReviewData::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * review处理动作【设置原因/跟进状态】
     * @author allen <2018-03-27>
     */
    public function actionProcess()
    {
        error_reporting(E_ALL);
        $return_arr = ['status' => 1, 'info' => '操作成功!'];
        $request = Yii::$app->request->post();
        $id = isset($request['id']) ? $request['id'] : "";
        $type_id = isset($request['type_id']) ? $request['type_id'] : "";
        $reason_id = isset($request['reason_id']) ? $request['reason_id'] : "";
        $step_id = isset($request['step_id']) ? $request['step_id'] : "";
        $remark = isset($request['text']) ? $request['text'] : "";

        $reasonList = BasicConfig::getParentList(34); //reView差评原因
        $stepList = BasicConfig::getParentList(35); //review跟进状态
        $bool = FALSE;
        $msg = "操作成功";
        if (empty($id)) {
            echo json_encode(['status' => 0, 'info' => '无效数据']);
            die;
        }

        $transaction = Yii::$app->db->beginTransaction();
        $model = AmazonReviewData::find()->where(['id' => $id])->one();
        $oldReason = $model->review_status ? $reasonList[$model->review_status] : '未设置'; //更新前差评原因
        $oldStep = $model->follow_status ? $stepList[$model->follow_status] : '未设置'; //更新前跟进状态


        switch ($type_id) {
            //差评原因
            case 1:
                $action = "设置差评原因";
                if (empty($reason_id)) {
                    echo json_encode(['status' => 0, 'info' => '请选择差评原因']);
                    die;
                }

                //更新
                $model->review_status = $reason_id;
                $model->modified_id = Yii::$app->user->identity->id;
                $model->modified_name = Yii::$app->user->identity->user_name;
                $model->modified_time = date('Y-m-d H:i:s');
                $res = $model->save();
//                $res = AmazonReviewData::updateAll(['review_status' => $reason_id], 'id = :id', [':id' => $id]);
                if ($res === false) {
                    $bool = TRUE;
                    $return_arr = ['status' => 0, 'info' => '设置review差评原因失败!'];
                } else {
                    $newReason = $reasonList[$reason_id];
                    $remark = '[' . $oldReason . ' - ' . $newReason . '] ' . $remark;

                    //同步更新到erp
                    $syncData = [
                        'id' => $id,
                        'type' => 1,
                        'value' => $reason_id
                    ];
                    $syncStatus = Order::syncAmazonReviewProcess($syncData); //返回 true 更新成功 false 更新失败
                    if (!$syncStatus) {
                        $bool = TRUE;
                        $msg = '<b style="color:red;">【同步ERP失败】</b>';
                    } else {
                        $msg .= '<b style="color:green;">【同步ERP成功】</b>';
                    }
                }
                break;

            case 2:
                $action = "更新跟进状态";
                if (empty($step_id)) {
                    echo json_encode(['status' => 0, 'info' => '请选择跟进状态']);
                    die;
                }

                //更新跟进状态
                $model->follow_status = $step_id;
                $model->modified_id = Yii::$app->user->identity->id;
                $model->modified_name = Yii::$app->user->identity->user_name;
                $model->modified_time = date('Y-m-d H:i:s');

//                echo '<pre>';
//                var_dump($model->attributes);
//                echo '</pre>';
//                die;
                $res = $model->save();
                if ($res === false) {
                    $bool = TRUE;
                    $return_arr = ['status' => 0, 'info' => '设置review跟进状态失败!'];
                } else {
                    $newStep = $stepList[$step_id];
                    $remark = '[' . $oldStep . ' -> ' . $newStep . '] ' . $remark;

                    //同步更新到erp
                    $syncData = [
                        'id' => $id,
                        'type' => 2,
                        'value' => $step_id
                    ];
                    $syncStatus = Order::syncAmazonReviewProcess($syncData); //返回 true 更新成功 false 更新失败
                    if (!$syncStatus) {
                        $bool = TRUE;
                        $msg = '<b style="color:red;">【同步ERP失败】</b>';
                    } else {
                        $msg .= '<b style="color:green;">【同步ERP成功】</b>';
                    }
                }
                break;
        }

        //记录操作日志
        if (!$bool) {
            $logData = [
                'review_data_id' => $id,
                'action' => $action,
                'remark' => $remark,
                'create_time' => date("Y-m-d H:i:s"),
                'create_by' => Yii::$app->user->identity->user_name
            ];
            $res = AmazonReviewLog::addData($logData);
            if (!$res) {
                $bool = TRUE;
                $msg .= ' 保存操作日志失败';
            }
        }

        //处理事务
        if (!$bool) {
            $transaction->commit();
            $return_arr = ['status' => 1, 'info' => $msg];
        } else {
            $return_arr = ['status' => 0, 'info' => '失败: ' . $msg];
            $transaction->rollBack();
        }

        echo json_encode($return_arr);
        die;
    }

    /**
     * 获取reView操作日志
     * author allen <2018-03-27>
     */
    public function actionGetlog()
    {

        $request = Yii::$app->request->post();
        $id = isset($request['id']) ? $request['id'] : "";

        if (empty($id)) {
            echo json_encode(['status' => 0, 'info' => '无效数据']);
            die;
        }

        $data = AmazonReviewLog::getLogData($id);
        if (empty($data)) {
            $returnArr = ['status' => 0, 'info' => '暂无操作记录....'];
        } else {
            $returnArr = ['status' => 1, 'info' => $data];
        }

        echo json_encode($returnArr);
        die;
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
        } else if (!empty($oldAccountId)) {
            $account = Account::findOne(['platform_code' => Platform::PLATFORM_CODE_AMAZON, 'old_account_id' => $oldAccountId]);
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
        $asin = Yii::$app->request->post('asin', '');
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
            echo json_encode([
                'bool' => 0,
                'msg' => '发件人邮箱不能为空',
            ]);
            die;
        }
        if (empty($receiveEmail)) {
            echo json_encode([
                'bool' => 0,
                'msg' => '收件人邮箱不能为空',
            ]);
            die;
        }
        if (empty($subject)) {
            echo json_encode([
                'bool' => 0,
                'msg' => '主题不能为空',
            ]);
            die;
        }
        if (empty($replyContentEn)) {
            echo json_encode([
                'bool' => 0,
                'msg' => '回复内容(英文)不能为空',
            ]);
            die;
        }

        //所有发件服务器都改成亚马逊邮箱服务器 update by allen str <2018-10-08>
        if (Platform::PLATFORM_CODE_AMAZON == 'AMAZON') {
            $from = 'email-smtp.us-east-1.amazonaws.com';
        } else {
            $from = $senderEmail;
        }
        //所有发件服务器都改成亚马逊邮箱服务器 update by allen end <2018-10-08>
        //指定邮箱用亚马逊邮件服务器发送 update by allen str <2018-10-11>
        if ($senderEmail == 'fantastic78c@hotmail.com' || $senderEmail == 'tofutofu8@hotmail.com' || $senderEmail == 'goodshop666@hotmail.com' || $senderEmail == 'eternity520a@hotmail.com') {
            $from = 'email-smtp.us-east-1.amazonaws.com';
        }

        //126  163邮箱所有邮件都从亚马逊邮件服务器发送
        //if(stripos($senderEmail, '@163.com') !== false || stripos($senderEmail, '@126.com') !== false){
        //$from = 'email-smtp.us-east-1.amazonaws.com';
        //}else{
        //$from = $senderEmail;
        //}

        $email = Mail::instance($from);
        if (empty($email)) {
            echo json_encode([
                'bool' => 0,
                'msg' => "实例化{$senderEmail}邮箱失败,请检查邮箱SMTP配置",
            ]);
            die;                
        }

        $uploadImages = UploadedFile::getInstancesByName('uploadImage');

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
                    $webAttachments[] = '/' . ltrim($file, '/');
                    $atchmentTotalSize += $uploadImage->size;
                }
            }
        }

        //判断上传文件的总大小
        if ($atchmentTotalSize > self::ATTACHMENT_MAX_SIZE) {            
            echo json_encode([
                'bool' => 0,
                'msg' => '上传附件总大小超过' . (self::ATTACHMENT_MAX_SIZE / 1048576) . 'M',
            ]);
            die;
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
            echo json_encode([
                'bool' => 0,
                'msg' => '错误原因:' . current(Mail::$errorMsg),
            ]);
            die;
        }

        //保存主动发送邮件信息
        $active = new ActiveSendEmail();
        $active->account_id = $accountId;
        $active->platform_code = Platform::PLATFORM_CODE_AMAZON;
        $active->platform_order_id = $platformOrderId;
        $active->sender_email = $senderEmail;
        $active->receive_email = $receiveEmail;
        $active->title = $subject;
        $active->content = $content;
        $active->asin = $asin;
        $active->tag = implode(',', $tag);
        $active->attachments = json_encode($attachments);
        $active->create_by = Yii::$app->user->identity->login_name;
        $active->create_time = date('Y-m-d H:i:s');
        $active->modify_by = Yii::$app->user->identity->login_name;
        $active->modify_time = date('Y-m-d H:i:s');
        $active->save();

        //主动联系发送的邮件，根据情况创建邮件主题
        if (!empty($platformOrderId)) {
            $orderInfo = OrderAmazonKefu::findOne(['platform_order_id' => $platformOrderId]);

            //判断是否已经存在了主题
            $inboxSubject = AmazonInboxSubject::findOne([
                'order_id' => $platformOrderId,
                'account_id' => $accountId,
            ]);
            if (empty($inboxSubject)) {
                //创建一个新的主题
                $inboxSubject = new AmazonInboxSubject();
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

                $inbox = new AmazonInbox();
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
                            $attach = new AmazonInboxAttachment();
                            $attach->amazon_inbox_id = $inbox->id;
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

        echo json_encode([
            'bool' => 1,
            'msg' => '发送成功',
        ]);
        die;
    }

    /**
     * 导出数据
     */
    public function actionDownload()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
        $request = Yii::$app->request->get();
        $account_id = isset($request['account_id']) ? $request['account_id'] : "";
        $asin = isset($request['asin']) ? $request['asin'] : "";
        $customerName = isset($request['customerName']) ? $request['customerName'] : "";
        $star = isset($request['star']) ? $request['star'] : "";
        $title = isset($request['title']) ? $request['title'] : "";
        $review_status = isset($request['review_status']) ? $request['review_status'] : "";
        $follow_status = isset($request['follow_status']) ? $request['follow_status'] : "";
        $is_station = isset($request['is_station']) ? $request['is_station'] : "";
        $reviewDate = isset($request['reviewDate']) ? $request['reviewDate'] : "";//
        $selectIds = isset($request['selectIds']) ? $request['selectIds'] : [];//选中的行数据
        if (!empty($selectIds)) {
            $query = AmazonReviewData::find();
            $selectIds = explode(',', $selectIds);
            $query->select('t.accountId,t.reviewDate,m.orderId,t.asin,m.custEmail,t.customerName,t.star,t.review_status,t.follow_status')
                ->from('{{%amazon_review_data}} t')
                ->join('LEFT JOIN', '{{%amazon_review_message_data}} m', 't.customerId = m.custId')
                ->where(['in', 't.id', $selectIds]);
            $data = $query->asArray()->all();
        } else {
            $query = AmazonReviewData::find();

            $query->select('t.accountId,t.reviewDate,m.orderId,t.asin,m.custEmail,t.customerName,t.star,t.review_status,t.follow_status')
                ->from('{{%amazon_review_data}} t')
                ->join('LEFT JOIN', '{{%amazon_review_message_data}} m', 't.customerId = m.custId');

            if (!empty($reviewDate)) {
                $query->andFilterWhere(['between', 't.reviewDate', explode('/', $reviewDate)[0], explode('/', $reviewDate)[1]]);
            }
            if (!empty($asin)) {
                $query->andWhere(['or', ['like', 't.asin', $asin], ['like', 'm.orderid', $asin], ['like', 'm.custEmail', $asin]]);
            }
            if (!empty($customerName)) {
                $query->andWhere(['like', 't.customerName', $customerName]);
            }
            if (!empty($title)) {
                $query->andWhere(['like', 't.title', $title]);
            }

            $query->andFilterWhere(
                [
                    't.accountId' => $account_id,
                    't.review_status' => $review_status,
                    't.follow_status' => $follow_status,
                    't.is_station' => $is_station,

                ]
            );
            switch ($star) {
                case 1:
                    $query->andFilterWhere(['in', 't.star', [1, 2, 3]]);
                    break;
                case 2:
                    $query->andFilterWhere(['in', 't.star', [4, 5]]);
                    break;
                default:
                    break;
            }
            $data = $query->asArray()->all();
//            echo $query->createCommand()->getRawSql();die;
        }
        //标题数组
        $fieldArr = [
            '账号',
            'Review时间',
            '订单',
            'Asin',
            'Email',
            '留评客户',
            '星级',
            '差评原因',
            '跟进状态',
            '更新人',

        ];

        //导出数据数组
        $dataArr = [];
        foreach ($data as $item) {
            $platformList = Account::getAccount('AMAZON', 2);
            $followStatus = BasicConfig::getParentList(35);
            $reviewStatusList = BasicConfig::getParentList(34);
            //导出数据数组
            $dataArr[] = [
                $platformList[$item['accountId']],//
                $item['reviewDate'],
                $item['orderId'],
                $item['asin'],
                $item['custEmail'],
                $item['customerName'],
                $item['star'],
                $reviewStatusList[$item['review_status']],
                $followStatus[$item['follow_status']],
            ];
        }
        VHelper::exportExcel($fieldArr, $dataArr, 'amazonreviewdata_' . date('Y-m-d'));
    }

}
