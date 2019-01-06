<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderRemarkKefu extends Model
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
        return '{{%order_remark}}';
    }

    public static function getOrderRemarks($orderId)
    {
        return self::find()
            ->from(self::tableName())
            ->select("*")
            ->where(['order_id' => $orderId])
            ->asArray()
            ->all();
    }

    public static function getOrderRemarksByArray($orderId_arr)
    {
        return self::find()
            ->from(self::tableName())
            ->select(["remark",'order_id'])
            ->where(['in','order_id',$orderId_arr])
            ->asArray()
            ->all();
    }

    public static function getLastRemarks($orderId)
    {
        $data = array();
        $result = self::find()
            ->from(self::tableName())
            ->select("remark")
            ->where(['order_id' => $orderId])
            ->orderBy("create_time ASC")
            ->asArray()
            ->all();

        foreach ($result as $item) {
            $data[] = $item['remark'];
        }
        $str = implode(';', $data);
        $length = strlen(utf8_decode($str));
        if ($length > 30) {
            $str = '留言太长、请到原来的地方查看';
        }
        return $str;
    }

    public static function getOrderRemark($orderId)
    {
        return self::find()
            ->from(self::tableName())
            ->select("*")
            ->where(['order_id' => $orderId])
            ->asArray()
            ->one();
    }
}