<?php
namespace app\modules\services\modules\api\controllers;
use app\components\Controller;
use app\modules\services\modules\api\models\AfterSalesOrderApi;
use app\modules\services\modules\api\models\DomesticreturngoodsApi;
class AftersalesorderController extends Controller
{
    /**
     * @desc 接口入口
     */
    public function actionIndex()
    {
        AfterSalesOrderApi::apiInit();
    }
    /**
     * @vc 国内退件跟进列表
     */
    public function actionReturnorder(){
        $models = new DomesticreturngoodsApi;
        $models->afferentReturnOrder();
    }


    /**
     * @author alpha
     * @desc 接受海外仓rma
     */
    public function actionOverseasrma()
    {

        $models = new DomesticreturngoodsApi;
        $models->getRma();
    }

    /**
     * 接受海外仓是否收到货
     */
    public function actionErpisreceive()
    {
        $models = new DomesticreturngoodsApi;
        $models->isReceive();

    }

}