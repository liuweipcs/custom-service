<?php

namespace app\modules\services\modules\order\controllers;

use app\common\VHelper;
use Yii;
use yii\web\Controller;
use app\modules\aftersales\models\ComplaintskuModel;

/**
 *
 * Class AccountController
 * @package app\commands
 */
class SkuController extends Controller {
    /*     * **
     * 定时任务获取60天发货量
     * ** */

    public function actionGetorder() {
        //避免服务器拉取信息超时  
        set_time_limit(0);
        ignore_user_abort(true);
        $page = Yii::$app->request->get('page', '');
      
        //获取不同平台的sku
        if (!empty($page)) {
            //当前时间前1天时间开始
            //$star_time = date('2018-04-01 00:00:00');
            $star_time = date('Y-m-d 00:00:00', strtotime('-1 days'));
            //当前时间前一天时间结束
            $end_time = date('Y-m-d 23:59:59', strtotime('-1 days'));
            $limit = 100;
            $pages = ($page - 1) * $limit;
            //aliexpress平台
            //分页读取类别数据
            $sql = "SELECT A.order_id,A.shipped_date,A.platform_code,B.sku FROM {{%order_aliexpress_detail}} AS B LEFT JOIN {{%order_aliexpress}} AS A ON A.order_id=B.order_id WHERE A.shipped_date  BETWEEN " . "'$star_time'" . " AND " . "'$end_time'" . " LIMIT " . $pages . ',' . $limit;
            $aliexpress = Yii::$app->db_order->createCommand($sql)->queryAll();
            
            if (!empty($aliexpress)) {
                foreach ($aliexpress as $key => $val) {
                    $skumodel = new ComplaintskuModel();
                    $skumodel->platform_code = $val['platform_code'];
                    $skumodel->sku = $val['sku'];
                    $skumodel->shipped_date = $val['shipped_date'];
                    $skumodel->save();
                }
            }
           
            //ebay平台
            //$model = new OrderEbayKefu();
            $sql = "SELECT A.order_id,A.shipped_date,A.platform_code,B.sku FROM {{%order_ebay_detail}} AS B LEFT JOIN {{%order_ebay}} AS A ON A.order_id=B.order_id WHERE A.shipped_date  BETWEEN " . "'$star_time'" . " AND " . "'$end_time'" . " LIMIT " . $pages . ',' . $limit;
            $ebay = Yii::$app->db_order->createCommand($sql)->queryAll();
            if (!empty($ebay)) {
                foreach ($ebay as $key => $val) {
                    $skumodel = new ComplaintskuModel();
                    $skumodel->platform_code = $val['platform_code'];
                    $skumodel->sku = $val['sku'];
                    $skumodel->shipped_date = $val['shipped_date'];
                    $skumodel->save();
                }
            }
            //Amazon平台
            //$model = new OrderAmazonKefu();
            $sql = "SELECT A.order_id,A.shipped_date,A.platform_code,B.sku FROM {{%order_amazon_detail}} AS B LEFT JOIN {{%order_amazon}} AS A ON A.order_id=B.order_id WHERE A.shipped_date  BETWEEN " . "'$star_time'" . " AND " . "'$end_time'" . " LIMIT " . $pages . ',' . $limit;
            $amazon = Yii::$app->db_order->createCommand($sql)->queryAll();
            if (!empty($amazon)) {
                foreach ($amazon as $key => $val) {
                    $skumodel = new ComplaintskuModel();
                    $skumodel->platform_code = $val['platform_code'];
                    $skumodel->sku = $val['sku'];
                    $skumodel->shipped_date = $val['shipped_date'];
                    $skumodel->save();
                }
            }
            //Wish平台
            //$model = new OrderAmazonKefu();
            $sql = "SELECT A.order_id,A.shipped_date,A.platform_code,B.sku FROM {{%order_wish_detail}} AS B LEFT JOIN {{%order_wish}} AS A ON A.order_id=B.order_id WHERE A.shipped_date  BETWEEN " . "'$star_time'" . " AND " . "'$end_time'" . " LIMIT " . $pages . ',' . $limit;
            $wish = Yii::$app->db_order->createCommand($sql)->queryAll();
            if (!empty($wish)) {
                foreach ($wish as $key => $val) {
                    $skumodel = new ComplaintskuModel();
                    $skumodel->platform_code = $val['platform_code'];
                    $skumodel->sku = $val['sku'];
                    $skumodel->shipped_date = $val['shipped_date'];
                    $skumodel->save();
                }
            }
            //其他平台
            //$model = new OrderOtherKefu();   
            $sql = "SELECT A.order_id,A.shipped_date,A.platform_code,B.sku FROM {{%order_other_detail}} AS B LEFT JOIN {{%order_other}} AS A ON A.order_id=B.order_id WHERE A.shipped_date  BETWEEN " . "'$star_time'" . " AND " . "'$end_time'" . " LIMIT " . $pages . ',' . $limit;
            $other = Yii::$app->db_order->createCommand($sql)->queryAll();
            if (!empty($other)) {
                foreach ($other as $key => $val) {
                    $skumodel = new ComplaintskuModel();
                    $skumodel->platform_code = $val['platform_code'];
                    $skumodel->sku = $val['sku'];
                    $skumodel->shipped_date = $val['shipped_date'];
                    $skumodel->save();
                }
            }
            if (empty($aliexpress) && empty($ebay) && empty($amazon) && empty($wish) && empty($other)) {
                die('没有相关数据');
            }
          
            $page++;
            VHelper::throwTheader('/services/order/sku/getorder', ['page' => $page], 'GET', 1200);
        } else {
            VHelper::throwTheader('/services/order/sku/getorder', ['page' => 1], 'GET', 1200);
            sleep(2);
            die('没有相关数据？');
        }
    }

}
