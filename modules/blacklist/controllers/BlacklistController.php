<?php

namespace app\modules\blacklist\controllers;

use Yii;
use app\modules\blacklist\models\BlackList;
use app\modules\blacklist\models\BlackListSearch;
use app\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use simple_html_dom;
use app\modules\orders\models\Order;

/**
 * BlackListController implements the CRUD actions for BlackList model.
 */
class BlacklistController extends Controller {

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
     * Lists all BlackList models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new BlackListSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single BlackList model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id) {
        return $this->render('view', [
                    'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new BlackList model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = new BlackList();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                        'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing BlackList model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $names = trim(rtrim(trim($model->username),','));
            $newArr = $names ? explode(',',$names) : [];
            $data = [
                    'type'=> 3,
                    'userId' => Yii::$app->user->id,
                    'userName' => Yii::$app->user->identity->user_name,
                    'ebayId' => $newArr,
                    'updateGbc' => TRUE
            ];
            $optionsRes = Order::blackOptions($data);
            if($optionsRes){
                if(!$optionsRes['bool']){
                        Yii::$app->getSession()->setFlash('success', "同步成功！ERP数据同步成功");
                    }else{
                        Yii::$app->getSession()->setFlash('success', "客服系统设置成功,ERP数据同步失败 ".$optionsRes['info']);
                    }
                }else{
                    Yii::$app->getSession()->setFlash('success', "同步成功！ERP数据同步失败");
                }
            
            return $this->redirect(['view', 'id' => $model->id]);
            
            
        } else {
            return $this->render('update', [
                        'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing BlackList model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id) {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the BlackList model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return BlackList the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = BlackList::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * http://www.customer.com/blacklist/blacklist/autosync  本地地址
     * 计划任务抓取GBC黑名单数据(eBay平台)
     * @author allen <2018-2-3>
     */
    public function actionAutosync() {
        $dom = $gbc = new simple_html_dom();
        //获取GBC页面数据
        $dom->load_file('http://www.sellerdefense.com/index.php/GBC');
        $htmls = trim($dom->find('div[id=mw-content-text]', 0)->find('pre', 1)->innertext);
        $ebayId = rtrim($htmls, ',');
        $ebayIds = explode(',', $ebayId);
        
        //获取GBCID页面的数据
        $gbc->load_file("http://www.sellerdefense.com/index.php/GBC_ID");
        $gbc_data = trim($gbc->find('div[id=mw-content-text]', 0)->find('p',0)->innertext);
        $gbcId = rtrim($gbc_data,',');
        $gbcIds = explode(',', $gbcId);

        //获取现有ebay黑名单数据
        $model = BlackList::find()->select('username')->where(['platfrom_id' => 31])->asArray()->all();
        if (isset($model[0]['username']) && !empty($model[0]['username'])) {
            $usernameStr = trim($model[0]['username']);
            $usernameStr = str_replace('，', ',', $usernameStr);
            $newUserNameStr = $usernameStr; //默认是系统现有Ebay黑名单数据
            $userNameArr = explode(',', $usernameStr);
        } else {
            $userNameArr = [];
        }
        
        //合并并去重从GBC上抓取的最新黑名单数据($ebayIds)+系统现有Ebay黑名单数据
        $newArr = array_keys(array_flip($userNameArr)+array_flip($ebayIds)+array_flip($gbcIds));
        $newUserNameStr = implode(',', $newArr);

        if(BlackList::updateAll(['username' => $newUserNameStr], 'platfrom_id = 31')  === false){
            Yii::$app->getSession()->setFlash('error', "同步失败,请联系相关技术人员!");
        }else{
            $data = [
                    'type'=> 3,
                    'userId' => Yii::$app->user->id,
                    'userName' => Yii::$app->user->identity->user_name,
                    'ebayId' => $newArr,
                    'updateGbc' => true
            ];
            $optionsRes = Order::blackOptions($data);
            if($optionsRes){
                    if(!$optionsRes['bool']){
                        Yii::$app->getSession()->setFlash('success', "同步成功！ERP数据同步成功");
                    }else{
                        Yii::$app->getSession()->setFlash('success', "客服系统设置成功,ERP数据同步异常 ".$optionsRes['info']);
                    }
            }else{
                    Yii::$app->getSession()->setFlash('success', "同步成功！ERP数据同步失败");
            }
        }
        
        $this->redirect(['index']);
    }
    
    /**
     * 将客户设置黑名单(需同步推送到ERP)
     * @author allen <2018-02-08>
     */
    public function actionAddblacklist(){
        $bool = false;
        $returnArr = ['bool'=>false,'info'=>'操作成功!'];
        $request = Yii::$app->request->post();
        $platformId = $request['platformId'];//平台ID
        $ebayId = $request['ebayId'];//用户ID
        $orderId = $request['orderId'];//订单ID
        
        $model = BlackList::find()->where(['platfrom_id'=>$platformId])->one();
        if(!empty($model)){
            //修改平台黑名单信息
            if($model->myself_username){
                $names = explode(',',$model->myself_username);
                $newArr = array_keys(array_flip($names)+array_flip([$ebayId]));
                $myselfUserName = implode(',', $newArr);
            }else{
                $myselfUserName = $ebayId;
            }
            $model -> myself_username = $myselfUserName;
            if(!$model->save()){
                $bool = true;
            }else{
                $returnArr = ['bool'=>true,'info'=>'操作失败!'];
            }
        }else{
            //新增平台黑名单信息
            $blockModel = new BlackList();
            $blockModel -> platfrom_id = $platformId;
            $blockModel -> username = "";
            $blockModel -> myself_username = $ebayId;
            if(!$blockModel->save()){
                $bool = true;
            }else{
                $returnArr = ['bool'=>true,'info'=>'保存黑名单失败!'];
            }       
        }
        
        if(!$bool){
                $data = [
                    'type'=> 1,
                    'orderId' => $orderId,
                    'userId' => Yii::$app->user->id,
                    'userName' => Yii::$app->user->identity->user_name,
                    'remark' => '客服系统操作: 客户 【'.$ebayId.'】被设置成黑名单成员',
                    'ebayId' => $ebayId                    
                ];
                
                $optionsRes = Order::blackOptions($data);
                if($optionsRes){
                    if(!$optionsRes['bool']){
                        $returnArr = ['bool'=>true,'info'=>'客服系统设置成功,ERP数据同步成功'];
                    }else{
                        $returnArr = ['bool'=>true,'info'=>'客服系统设置成功,ERP数据同步异常 '.$optionsRes['info']];
                    }
                }else{
                    $returnArr = ['bool'=>false,'info'=>'客服系统设置成功,ERP数据同步失败'];
                }
        }
        
        
        echo json_encode($returnArr);
        die;
    }
    
    public function actionCancelblacklist(){
        $returnArr = [];
        $request = Yii::$app->request->post();
        $platformId = $request['platformId'];//平台ID
        $ebayId = $request['ebayId'];//用户ID
        $orderId = $request['orderId'];//订单ID
        
        $model = BlackList::find()->where(['platfrom_id'=>$platformId])->one();
        if(!empty($model)){
            $names = explode(',',$model->myself_username);
            $tempArr = array_flip($names);
            unset($tempArr[$ebayId]);
            $newArr = array_flip($tempArr);
            $model -> myself_username = implode(',', $newArr);
            if(!$model->save()){
                $returnArr = ['bool'=>true,'info'=>'取消黑名单失败!'];
            }else{
                $data = [
                    'type'=> 2,
                    'orderId' => $orderId,
                    'userId' => Yii::$app->user->id,
                    'userName' => Yii::$app->user->identity->user_name,
                    'remark' => '客服系统操作: 客户 【'.$ebayId.'】取消了黑名单成员',
                    'ebayId' => $ebayId                    
                ];
                
                $optionsRes = Order::blackOptions($data);
                if($optionsRes){
                    if(!$optionsRes['bool']){
                        $returnArr = ['bool'=>true,'info'=>'客服系统取消成功,ERP数据同步成功'];
                    }else{
                        $returnArr = ['bool'=>true,'info'=>'客服系统取消成功,ERP数据同步异常 '.$optionsRes['info']];
                    }
                }else{
                    $returnArr = ['bool'=>false,'info'=>'客服系统取消成功,ERP数据同步失败'];
                }
            }
        }
        echo json_encode($returnArr);
        die;
    }

}
