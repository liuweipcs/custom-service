<?php
namespace app\modules\mails\controllers;

use Yii;
use app\components\Controller;
use app\modules\mails\models\ActiveSendEmail;

class ActivesendemailController extends Controller
{
    /**
     * 列表
     */
    public function actionList()
    {
        $params = Yii::$app->request->getBodyParams();
        $model = new ActiveSendEmail();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }
}