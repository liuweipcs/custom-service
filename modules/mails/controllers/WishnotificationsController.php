<?php

namespace app\modules\mails\controllers;

use app\modules\mails\models\EbayNewApiToken;
use app\modules\mails\models\EbaySellerQclist;
use app\modules\services\modules\aliexpress\models\AliexpressOrder;
use Yii;
use wish\components\MerchantWishApi;
use app\modules\accounts\models\Account;
use app\components\Controller;
use app\modules\mails\models\WishNotifaction;
use yii\helpers\Url;

class WishnotificationsController extends Controller
{
    /**
     * 通知列表
     */
    public function actionIndex()
    {
        $params = Yii::$app->request->getBodyParams();
        $model = new WishNotifaction();
        $dataProvider = $model->searchList($params);

        return $this->renderList('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 设为已查看
     */
    public function actionCheck()
    {
        $id = Yii::$app->request->get('id', 0);

        if (empty($id)) {
            $this->_showMessage('ID不能为空', false);
        }

        $noti = WishNotifaction::findOne($id);
        if (empty($noti)) {
            $this->_showMessage('没有找到该通知', false);
        }

        $wish = new MerchantWishApi($noti->account_id);
        if ($wish->markAsViewed($noti->noti_id)) {
            $noti->is_view = 1;
            $noti->view_by = Yii::$app->user->identity->login_name;
            $noti->view_time = date('Y-m-d H:i:s');
            $noti->save();

            $this->_showMessage('标记查看失败', true, Url::toRoute('/mails/wishnotifications/index'), true);
        } else {
            $this->_showMessage('标记查看失败', false);
        }
    }

    public function actionTest()
    {

        $data = EbayNewApiToken::find()->where([])->asArray()->all();

        echo '<pre>';
        print_r($data);

        print_r(EbaySellerQclist::getTableSchema());
    }
}