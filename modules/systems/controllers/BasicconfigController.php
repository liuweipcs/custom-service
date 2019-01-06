<?php

namespace app\modules\systems\controllers;

use Yii;
use app\components\Controller;
use yii\web\NotFoundHttpException;
use app\modules\systems\models\BasicConfig;
use app\modules\systems\models\BasicConfigSearch;
use app\common\VHelper;
use yii\helpers\Html;

/**
 * BasicConfigController implements the CRUD actions for BasicConfig model.
 */
class BasicconfigController extends Controller {
    /**
     * @inheritdoc
     */
//    public function behaviors()
//    {
//        return [
//            'verbs' => [
//                'class' => VerbFilter::className(),
//                'actions' => [
//                    'delete' => ['POST'],
//                ],
//            ],
//        ];
//    }

    /**
     * Lists all BasicConfig models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new BasicConfigSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single BasicConfig model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id) {
        return $this->render('view', [
                    'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new BasicConfig model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = new BasicConfig();
        $request = Yii::$app->request;
        $model->status = 2; //默认启用状态
        if ($request->isPost) {
            $attributes = $request->post('BasicConfig');
            $model->attributes = $attributes;
            $model->create_time = date('Y-m-d H:i:s', time());
            $model->create_id = USER_ID;
            $model->create_name = USER_NAME;

            //如果parent_id = 0 说明是一级分类
            if ($attributes['parent_id'] == 0) {
                $model->level = 1;
            } else {
                //如果设置了2级目录 则保存相应parent_id和level
                if (isset($attributes['level_two']) && $attributes['level_two'] != " ") {
                    $model->parent_id = $attributes['level_two'];
                    $model->level = 3;
                }else{
                    $model->level = 2;
                }
            }
            
            if (!$model->save()) {
                Yii::$app->getSession()->setFlash('error', "新增失败: " . VHelper::errorToString($model->getErrors()));
                return $this->render('create', ['model' => $model]);
            } else {
                Yii::$app->getSession()->setFlash('success', "新增成功!");
                return $this->redirect(['index', 'id' => $model->id]);
            }
        }
        return $this->render('create', ['model' => $model]);
    }

    /**
     * Updates an existing BasicConfig model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);
        $request = Yii::$app->request;
        if ($request->isPost) {
            $attributes = $request->post('BasicConfig');
            $model->attributes = $attributes;
            $model->create_time = date('Y-m-d H:i:s', time());
            $model->create_id = USER_ID;
            $model->create_name = USER_NAME;
            
            //如果parent_id = 0 说明是一级分类
            if ($attributes['parent_id'] == 0) {
                $model->level = 1;
            } else {
                //如果设置了2级目录 则保存相应parent_id和level
                if (isset($attributes['level_two']) && $attributes['level_two']) {
                    $model->parent_id = $attributes['level_two'];
                    $model->level = 3;
                }else{
                    $model->level = 2;
                }
            }
            
            if ($model->save() === FALSE) {
                Yii::$app->getSession()->setFlash('error', "更新失败: " . VHelper::errorToString($model->getErrors()));
                return $this->render('update', [
                            'model' => $model,
                ]);
            } else {
                Yii::$app->getSession()->setFlash('success', "更新成功!");
                return $this->redirect(['index', 'id' => $model->id]);
            }
        }
        return $this->render('update', ['model' => $model]);
    }

    /**
     * Deletes an existing BasicConfig model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id) {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the BasicConfig model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return BasicConfig the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = BasicConfig::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionGetnextlevel($parentId) {
        if ($parentId) {
            $datas = BasicConfig::getParentList($parentId);
            if (!empty($datas)) {
                foreach ($datas as $value => $name) {
                    echo Html::tag('option', Html::encode($name), array('value' => $value));
                }
            }
        }
    }

}
