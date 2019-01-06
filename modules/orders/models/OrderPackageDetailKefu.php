<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderPackageDetailKefu extends Model
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
        return '{{%order_package_detail}}';
    }

    /**
     * 获取包裹明细
     */
    public static function getPackageDetails($packageId)
    {
        return self::find()
            ->from(self::tableName())
            ->where(['package_id' => $packageId])
            ->asArray()
            ->all();
    }

    /**
     * 获取订单包裹明细
     */
    public static function getOrderPackageDetails($orderId)
    {
        return self::find()
            ->from(self::tableName())
            ->where(['order_id' => $orderId])
            ->asArray()
            ->all();
    }
}