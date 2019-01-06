<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderEbayDetail extends Model
{
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    public static function tableName()
    {
        return '{{%order_ebay_detail}}';
    }

    /**
     * 获取订单详情的item id 交易id
     * @param $order_id
     * @return mixed
     */
    public static function getItemIdAndTransactionId($order_id)
    {
        $detail = self::find()->from(self::tableName())
            ->select('item_id,transaction_id')
            ->where(['order_id' => $order_id])
            ->asArray()
            ->all();
        if (empty($detail)) {
            $detail  = self::find()->from('{{%order_ebay_detail_copy}}')
                ->select('item_id,transaction_id')
                ->where(['order_id' => $order_id])
                ->asArray()
                ->all();
        }
        return $detail;
    }
}