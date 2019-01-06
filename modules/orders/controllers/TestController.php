<?php
namespace app\modules\orders\controllers;

use Yii;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\services\modules\aliexpress\components\TaobaoQimenApi;
use app\components\Controller;

class TestController extends Controller
{

    public function actionTest()
    {

        $erpAccountInfo = AliexpressAccount::findById(3);

        $appKey = $erpAccountInfo->app_key;
        $secretKey = $erpAccountInfo->secret_key;
        $accessToken = $erpAccountInfo->access_token;
        $taobaoQimenApi = new TaobaoQimenApi($appKey, $secretKey, $accessToken);

        $orderListQuery = new \MinxinAliexpressOrdersimplificationqueryRequest();

        $orderListQuery->setAccountId($erpAccountInfo->account);
        $orderListQuery->setOrderStatus('BUYER_ACCEPT_GOODS');
        $orderListQuery->setPage(1);
        $orderListQuery->setPageSize(10);

        $taobaoQimenApi->doRequest($orderListQuery);
        if (!$taobaoQimenApi->isSuccess()) {
            return $taobaoQimenApi->getErrorMessage();
        }

        $data = $taobaoQimenApi->getResponse();

        echo '<pre>';
        var_dump($data);
        exit;
    }
}