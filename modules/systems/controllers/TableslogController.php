<?php
namespace app\modules\systems\controllers;
use app\components\Controller;
use app\modules\systems\models\TablesChangeLog;

class TableslogController extends Controller 
{
    public function actionList()
    {
        $model = new TablesChangeLog();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

}
?>