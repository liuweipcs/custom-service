<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 下午 18:00
 */

namespace app\modules\services\modules\ebay\controllers;

use app\modules\accounts\models\Platform;
use Yii;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\aftersales\models\AfterSalesRefund;
use yii\web\Controller;
use app\modules\aftersales\models\AfterSalesProduct;

class GetrefundcostController extends Controller
{
    //补齐退款重寄单问题产品的sku成本
    public function actionIndex()
    {

        set_time_limit(0);
        $start_time = Yii::$app->request->get('start');
        $end_time = Yii::$app->request->get('end');

        if (empty($start_time) || empty($end_time)) {
            $start_time = "2018-08-01 00:00:00";
            $end_time = "2018-09-30 23:59:59";
        }

        //取出所有审核通过退款售后单
        $after_order = AfterSalesProduct::find()
            ->select('t.id, t.platform_code, t.order_id, t.sku, t.refund_redirect_price, t.refund_redirect_price_rmb, t1.refund_amount, t1.currency')
            ->from('{{%after_sales_product}} as t')
            ->join('LEFT JOIN', '{{%after_sales_refund}} t1','t.after_sale_id = t1.after_sale_id')
            ->andWhere(['t1.refund_status' => 3])
            ->andWhere(['between', 't1.refund_time', $start_time, $end_time])
            ->all();

        if(!empty($after_order)){
            foreach ($after_order as $k => $v) {
                try {
                    $data = AfterSalesOrder::getRefundRedirectData($v->platform_code, $v->order_id, $v->sku, $v->refund_amount, $v->currency, 1);
                    $product_model = AfterSalesProduct::find()->where(['id' => $v->id])->one();
                    $product_model->refund_redirect_price = $data['sku_refund_amt'];
                    $product_model->refund_redirect_price_rmb = $data['sku_refund_amt_rmb'];
                    $product_model->save(false);

                } catch (\Exception $e) {
                    //防止出现的异常中断整个程序执行
                }

            }
        }

        exit('RUN END');
    }

}