<?php

namespace app\modules\orders\controllers;

use app\components\Controller;
class OrderstockoutController extends Controller
{

    /**
     * ebay缺货列表
     */
    public function actionEbaystockout()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('ebaystockout',[]);
    }

    /**
     * Walmart缺货列表
     */
    public function actionWalmartstockout()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('walmartstockout',[]);
    }

    /**
     * Amazon缺货列表
     */
    public function actionAmazonstockout()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('amazonstockout',[]);
    }


}