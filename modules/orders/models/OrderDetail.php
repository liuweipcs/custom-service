<?php

namespace app\modules\orders\models;

use app\modules\accounts\models\Platform;
use Yii;

class OrderDetail extends OrderModel
{
    public static $platform_code = '';

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {

        return Yii::$app->db_order;

    }

    /**
     * 返回当前模型的表名
     */
    public static function tableName()
    {
        if (self::$platform_code == 'EB') {
            return '{{%order_ebay_detail}}';
        } elseif (self::$platform_code == 'ALI') {
            return '{{%order_aliexpress_detail}}';
        } elseif (self::$platform_code == 'AMAZON') {
            return '{{%order_amazon_detail}}';
        } elseif (self::$platform_code == 'WISH') {
            return '{{%order_wish_detail}}';
        } elseif (self::$platform_code == 'OTHER') {
            return '{{%order_other_detail}}';
        }else{
            return '{{%order_ebay_detail}}';
        }
    }

    /**
     * @desc 获取订单详情
     **/
    public static function getOrderdetail($platformcode, $order_id)
    {

        self::$platform_code = $platformcode;

        $reslut = self::find()
            ->from(self::tableName())
            ->where(['order_id' => $order_id])
            ->all();
        return $reslut;
    }


    /**
     * 获取sku总计金额
     * @param $order_id
     * @param $platformcode
     * @param $sku
     * @return mixed
     *
     */
    public static function getOrderdetailTotalPrice($order_id, $platformcode, $sku)
    {
        self::$platform_code = $platformcode;
        $total_price         = self::find()
            ->from(self::tableName())
            ->select('total_price')
            ->where(['order_id' => $order_id])
            ->andWhere(['sku' => $sku])
            ->asArray()
            ->one();
        return $total_price['total_price'];
    }

}