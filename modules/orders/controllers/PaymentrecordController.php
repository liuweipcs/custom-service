<?php

namespace app\modules\orders\controllers;
use app\components\Controller;

use app\common\VHelper;
use yii\helpers\Json;

use yii\web\Cookie;

class PaymentrecordController extends Controller
{
    //Paypal交易记录
    public function actionPaymentrecord()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('paymentrecord',[]);
    }

    //Paypal交易查询
    public function actionPaymentrecordlist()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('paymentrecordlist',[]);
    }

}