<?php

namespace app\modules\aftersales\controllers;

use Yii;
use app\modules\aftersales\models\RefundReason;
use app\modules\aftersales\models\RefundReasonSearch;
use app\components\Controller;
//use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\modules\systems\models\BasicConfig;

/**
 * RefundReasonController implements the CRUD actions for RefundReason model.
 */
class RefundreasonController extends Controller {

    /**
     * @inheritdoc
     */
    public function behaviors() {
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
     * Lists all RefundReason models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new RefundReasonSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single RefundReason model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id) {
        $model = $this->findModel($id);
        $departmentList = BasicConfig::getParentList(52);
        $reasonTypeList = BasicConfig::getParentList($model->department_id);
        $formulaList = BasicConfig::getParentList(108);
        return $this->render('view', [
                    'model' => $model,
                    'departmentList' => $departmentList,
                    'reasonTypeList' => $reasonTypeList,
                    'formulaList' => $formulaList
                    
        ]);
    }

    /**
     * Creates a new RefundReason model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = new RefundReason();
        $departmentList = BasicConfig::getParentList(52);
        $formulaList = BasicConfig::getParentList(108);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                    'model' => $model,
                    'departmentList' => $departmentList,
                    'reasonTypeList' => [],
                    'formulaList' => $formulaList
            ]);
        }
    }

    /**
     * Updates an existing RefundReason model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            $reasonTypeList = BasicConfig::getParentList($model->department_id);
            return $this->render('update', [
                        'model' => $model,
                        'reasonTypeList' => $reasonTypeList
            ]);
        }
    }

    /**
     * Deletes an existing RefundReason model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id) {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the RefundReason model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RefundReason the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = RefundReason::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * 获取责任归属部门对应的原因类别
     * @author allen <2018-03-29>
     */
    public function actionGetnetleveldata() {
        $data = [];
        $id = Yii::$app->request->post('id');
        if(is_array($id)){
            $id = $id[0];
        }
        if ($id) {
            $data = BasicConfig::getParentList($id);
        }

        echo json_encode($data);
        die;
    }

}
