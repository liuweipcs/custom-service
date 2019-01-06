<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderTransactionKefu extends Model
{
    //接收
    const RECEIVE_TYPE_YES = 1;
    //发起
    const RECEIVE_TYPE_NO = 2;

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
        return '{{%order_transaction}}';
    }

    /**
     * 通过订单ID获取交易详情
     */
    public static function getOrderTransactionDetailByOrderId($orderId)
    {
        $list = self::find()
            ->from(self::tableName())
            ->where(['order_id' => $orderId])
            ->asArray()
            ->all();

        return $list;
    }

    /**
     * 获取订单交易类型
     */
    public static function getOrderTransactionType($key)
    {
        $type = array(
            self::RECEIVE_TYPE_YES => '接收',
            self::RECEIVE_TYPE_NO => '发起',
        );
        return array_key_exists($key, $type) ? $type[$key] : '';
    }
}