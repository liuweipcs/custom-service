<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderAbnormityKefu extends Model
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
        return '{{%order_abnormity_reason}}';
    }

    /**
     * 获取订单异常
     */
    public static function getOrderAbnormals($orderId)
    {
        return self::findAll(['order_id' => $orderId]);
    }
}