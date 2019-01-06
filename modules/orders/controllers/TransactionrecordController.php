<?php
/**
 * Created by PhpStorm.
 * User: miko
 * Date: 2017/8/2
 * Time: 10:07
 */
namespace app\modules\orders\controllers;
use app\components\Controller;
use app\modules\orders\models\Transactionrecord;


class TransactionrecordController extends Controller{

    /*
     * @desc 退票信息show
     * */
    public function actionList(){

        $params = \Yii::$app->request->getBodyParams();

        $model = new Transactionrecord();
        $dataProvider = $model->searchList($params);
        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }
}
