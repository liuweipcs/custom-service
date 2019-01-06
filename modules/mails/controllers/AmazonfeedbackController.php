<?php

namespace app\modules\mails\controllers;

use Yii;
use app\common\VHelper;
use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonFeedBackSearch;
use app\modules\mails\models\AmazonFeedBackLog;
use app\components\Controller;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\modules\accounts\models\Account;
use app\modules\systems\models\BasicConfig;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\ActiveSendEmail;

/**
 * AmazonfeedbackController implements the CRUD actions for AmazonFeedBack model.
 */
class AmazonfeedbackController extends Controller
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
     * Lists all AmazonFeedBack models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AmazonFeedBackSearch();
        $params = Yii::$app->request->queryParams;
        $params['type'] = 1;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $statisticsData = $searchModel->getstatistics();
        $accountList = ['' => '--请选择账号--'] + Account::getAccount('AMAZON', 2);
        $followStatusList = BasicConfig::getParentList(35);
        unset($followStatusList[" "]);

        return $this->renderList('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'accountList' => $accountList,
            'statisticsData' => $statisticsData,
            'followStatusList' => $followStatusList
        ]);
    }

    /**
     * Displays a single AmazonFeedBack model.
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
     * Deletes an existing AmazonFeedBack model.
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
     * Finds the AmazonFeedBack model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return AmazonFeedBack the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AmazonFeedBack::findOne($id)) !== null) {
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
        $model = AmazonFeedBack::find()->where(['id' => $id])->one();
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

                if ($res === false) {
                    $bool = TRUE;
                    $return_arr = ['status' => 0, 'info' => '设置feedback差评原因失败!'];
                } else {
                    $newReason = $reasonList[$reason_id];
                    $remark = '[' . $oldReason . ' - ' . $newReason . '] ' . $remark;
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
                /*               echo '<pre>';
                               var_dump(Yii::$app->user->identity->id);
                               echo '</pre>';
                               die;*/
                $res = $model->save();
                if ($res === false) {
                    $bool = TRUE;
                    $return_arr = ['status' => 0, 'info' => '设置feedback跟进状态失败!'];
                } else {
                    $newStep = $stepList[$step_id];
                    $remark = '[' . $oldStep . ' -> ' . $newStep . '] ' . $remark;
                }
                break;
            case 3:
                $action = "回复Feedback";
                if (empty($remark)) {
                    echo json_encode(['status' => 0, 'info' => '请填写Feedback回复内容']);
                    die;
                }

                //更新跟进状态
                $model->your_response = $remark;
                $model->modified_id = Yii::$app->user->identity->id;
                $model->modified_name = Yii::$app->user->identity->user_name;
                $model->modified_time = date('Y-m-d H:i:s');

                $res = $model->save();
                if ($res === false) {
                    $bool = TRUE;
                    $return_arr = ['status' => 0, 'info' => '设置feedback跟进状态失败!'];
                } else {
                    $newStep = $stepList[$step_id];
                    $remark = '[' . $oldStep . ' -> ' . $newStep . '] ' . $remark;
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
            $res = AmazonFeedBackLog::addData($logData);
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
            $transaction->rollBack();
        }

        echo json_encode($return_arr);
        die;
    }

    /**
     * 获取feedback操作日志
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

        $data = AmazonFeedBackLog::getLogData($id);
        if (empty($data)) {
            $returnArr = ['status' => 0, 'info' => '暂无操作记录....'];
        } else {
            $returnArr = ['status' => 1, 'info' => $data];
        }

        echo json_encode($returnArr);
        die;
    }
}
