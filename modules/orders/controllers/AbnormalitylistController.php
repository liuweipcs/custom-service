<?php

namespace app\modules\orders\controllers;
use app\components\Controller;

class AbnormalitylistController extends Controller
{
    /**
     * @return \yii\base\string
     * 售后部(异常订单需要联系客户)
     */
    public function actionDetainabnormalitylist()
    {
        $cookie = \Yii::$app->request->cookies;
 
        return  $this->renderList('detainabnormalitylist',[]);
    }

    /**
     * @return \yii\base\string
     * 售后部(正常订单需要联系客户)
     */
    public function actionArrestorderlist()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('arrestorderlist',[]);
    }

    /**
     *
     * (售后部)留言待处理
     */

    public function actionNoteabnormalitylist()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('noteabnormalitylist',[]);
    }

    /**
     * @return \yii\base\string
     * 需人共审核异常订单
     */

    public function actionNeedcheckabnormalitylist()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('needcheckabnormalitylist',[]);
    }

    /**
     * @return \yii\base\string
     * 付款金额错误异常订单
     */

    public function actionAmountabnormalitylist()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('amountabnormalitylist',[]);
    }

    /**
     * @return \yii\base\string
     * (售后部)客户取消订单
     */
    public function actionCancellationorderlist()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('cancellationorderlist',[]);
    }

    /**
     * @return \yii\base\string
     * 利润异常 需人工审核
     */
    public function actionProfitabnormalitylist()
    {
        $cookie = \Yii::$app->request->cookies;

        return  $this->renderList('profitabnormalitylist',[]);
    }
   /***
    * @return \yii\base\string
    * ebay交易信息异常订单列表
    * **/
  public function actionEbaychecktrans(){
       $cookie = \Yii::$app->request->cookies;
    
      return  $this->renderList('ebaychecktrans',[]);
      
  }
}