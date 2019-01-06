<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderPackageKefu extends Model
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
        return '{{%order_package}}';
    }

    /**
     * 获取订单包裹信息
     */
    public static function getOrderPackages($orderId)
    {
        return self::find()
            ->from(self::tableName())
            ->where(['order_id' => $orderId])
            ->asArray()
            ->all();
    }

    /**
     * 获取仓库id
     * @param $orderId
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getOrderPackageWareHouseId($orderId)
    {
        $warehouse_id=self::find()
            ->from(self::tableName())
            ->select(['warehouse_id'])
            ->where(['order_id' => $orderId])
            ->asArray()
            ->one();
        return isset($warehouse_id)?$warehouse_id['warehouse_id']:0;
    }
}