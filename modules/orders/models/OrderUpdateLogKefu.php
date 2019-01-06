<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderUpdateLogKefu extends Model
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
        return '{{%order_update_log}}';
    }

    /**
     * 查询订单日志信息
     */
    public static function getOrderUpdateLog($orderId)
    {
        $list = self::find()
            ->select('t.order_id,t.update_content,t.create_time,u.user_full_name')
            ->from('{{%order_update_log}} t')
            ->leftjoin('{{%system}}.{{%user}} u', 't.create_user_id = u.id')
            ->where(['order_id' => $orderId])
            ->orderBy("t.create_time DESC")
            ->asArray()
            ->all();

        return $list;
    }

    /**
     * save
     * @param array $attr
     * @return boolean
     */
    public static function saveUpdateLog($attr = array()){
        $model = new self();
        $model -> order_id = $attr['order_id'];
        $model -> update_content = $attr['update_content'];
        $model -> create_time = $attr['create_time'];
        $model -> create_user_id = $attr['create_user_id'];
        if ($model->save()){
            return $model->id;
        }else{
    //        echo $model->errors;
            return false;
        }
    }
}