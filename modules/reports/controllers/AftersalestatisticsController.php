<?php

namespace app\modules\reports\controllers;

use Yii;
use app\modules\reports\models\AfterSaleStatistics;
use app\modules\accounts\models\UserAccount;
use app\modules\reports\models\AfterSaleStatisticsSearch;
//use yii\web\Controller;
use app\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\modules\users\models\User;
use app\modules\systems\models\BasicConfig;

/**
 * AfterSaleStatisticsController implements the CRUD actions for AfterSaleStatistics model.
 */
class AftersalestatisticsController extends Controller
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
     * Lists all AfterSaleStatistics models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AfterSaleStatisticsSearch();
        $params = Yii::$app->request->post();
        $role_id = Yii::$app->user->identity->role_id; //当前用户角色
        $platform_code = $typeArr = $accountSiteArr = [];
        $type = $account_site = '';
        if (Yii::$app->request->isPost) {
            $platform_code = isset($params['platform_code']) ? $params['platform_code'] : "";//选择的平台
            $type = isset($params['type']) ? $params['type'] : "";//选择的类型
            $account_site = isset($params['account_site']) ? $params['account_site'] : ""; //站点
            $typeArr = UserAccount::getAccoutOrSite($platform_code);//平台下对应的站点
            $accountSiteArr = UserAccount::getAccoutOrSite($platform_code, $type);//对应的类型或者账号信息
            
            $department_id = isset($params['department_id']) ? $params['department_id'] : "";//选择的部门
            $reasonList = BasicConfig::getParentList($department_id);//选中部门对应的原因数据
            $reason_id = isset($params['reason_id']) ? $params['reason_id'] : [];//原因
            $users = isset($params['user_id']) ? $params['user_id'] : [];
        }
        
        $uList = User::getUserInfoByRole($role_id);
        $userList = !empty($uList) ? array_column($uList, 'user_name', 'user_name') : [];
        $userIdList = !empty($userList) ? array_keys($userList) : [];
        $data = $searchModel->searchData($params,$userList);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'data' => $data,
            'platform_code' => $platform_code,
            'typeArr' => $typeArr,
            'typeVal' => $type,
            'accountSiteArr' => $accountSiteArr,
            'accountSiteVal' => $account_site,
            'userIdList' => $userIdList,
            'userList' => $userList,
            'department_id' => $department_id,
            'reasonList' => $reasonList,
            'reason_id' => $reason_id,
            'users' => $users,
        ]);
    }
    
    /**
     * 退款预估统计
     * @author allen <2018-08-14>
     */
    public function actionEstimatedrefund(){
        $searchModel = new AfterSaleStatisticsSearch();
        $params = Yii::$app->request->post();
        $role_id = Yii::$app->user->identity->role_id; //当前用户角色
        $platform_code = $typeArr = $accountSiteArr = [];
        $type = $account_site = '';
        if (Yii::$app->request->isPost) {
            
            $platform_code = isset($params['platform_code']) ? $params['platform_code'] : "";//选择的平台
            $type = isset($params['type']) ? $params['type'] : "";//选择的类型
            $account_site = isset($params['account_site']) ? $params['account_site'] : ""; //站点
            $typeArr = UserAccount::getAccoutOrSite($platform_code);//平台下对应的站点
            $accountSiteArr = UserAccount::getAccoutOrSite($platform_code, $type);//对应的类型或者账号信息
            
            $department_id = isset($params['department_id']) ? $params['department_id'] : "";//选择的部门
            $reasonList = BasicConfig::getParentList($department_id);//选中部门对应的原因数据
            $reason_id = isset($params['reason_id']) ? $params['reason_id'] : [];//原因
            $users = isset($params['user_id']) ? $params['user_id'] : [];
            $startTime = isset($params['start_time']) ? $params['start_time'] : '';//搜索开始时时间
            $endTime = isset($params['end_time']) ? $params['end_time'] : "";//搜索结束时间
        }
        
        $uList = User::getUserInfoByRole($role_id);
        $userList = !empty($uList) ? array_column($uList, 'user_name', 'user_name') : [];
        $userIdList = !empty($userList) ? array_keys($userList) : [];
        $data = $searchModel->estimatedRefundData($params,$userList);
        return $this->render('estimatedrefund', [
            'searchModel' => $searchModel,
            'data' => $data,
            'platform_code' => $platform_code,
            'typeArr' => $typeArr,
            'typeVal' => $type,
            'accountSiteArr' => $accountSiteArr,
            'accountSiteVal' => $account_site,
            'userIdList' => $userIdList,
            'userList' => $userList,
            'department_id' => $department_id,
            'reasonList' => $reasonList,
            'reason_id' => $reason_id,
            'users' => $users,
            'startTime' => $startTime,
            'endTime' => $endTime
        ]);
    }


    /**
     * Displays a single AfterSaleStatistics model.
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
     * Creates a new AfterSaleStatistics model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AfterSaleStatistics();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing AfterSaleStatistics model.
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
     * Deletes an existing AfterSaleStatistics model.
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
     * Finds the AfterSaleStatistics model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AfterSaleStatistics the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AfterSaleStatistics::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
