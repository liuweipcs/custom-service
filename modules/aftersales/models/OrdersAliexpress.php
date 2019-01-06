<?php

namespace app\modules\aftersales\models;

use Yii;
use app\components\Model;

class OrdersAliexpress extends Model
{
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
        return '{{%order_aliexpress}}';
    }

    public static function getOrders($orderId = '',$platform_order_id = '')
    {
        if($platform_order_id != '' && $orderId != ''){
            return self::find()
                ->from(self::tableName())
                ->select("*")
                ->where(['platform_order_id' => $platform_order_id,'order_id' => $orderId,'order_type'=> 8])
                ->all();
        }else if($orderId != '' && $platform_order_id == '') {
            return self::find()
                ->from(self::tableName())
                ->select("*")
                ->where(['order_id' => $orderId,'order_type'=> 8])
                ->all();
        }else if($platform_order_id != '' && $orderId == ''){
            return self::find()
                ->from(self::tableName())
                ->select("*")
                ->where(['platform_order_id' => $platform_order_id,'order_type'=> 8])
                ->all();
        }else
        return array();
    }

}