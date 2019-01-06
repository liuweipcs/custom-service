<?php

namespace app\modules\orders\models;

use Yii;

class OderEbayDetail extends OrderModel
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
        return '{{%order_ebay_detail}}';
    }

    /**
     * @desc 获取交易记录
     **/
    public static function getOrderDetailByOrderId($orderId, $colums = '*')
    {
        $list = self::find()
            ->select($colums)
            ->from(self::tableName())
            ->where(['order_id' => $orderId])
            ->asArray()
            ->All();
        if (!$list) {
            $list1 = self::find()
                ->select($colums)
                ->from('{{%order_ebay_detail_copy}}')
                ->where(['order_id' => $orderId])
                ->asArray()
                ->All();
            return $list1;
        }
        return $list;
    }
}