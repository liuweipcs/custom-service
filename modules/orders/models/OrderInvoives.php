<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;
use yii\db\Query;

class OrderInvoives extends Model {


    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    public static function tableName() {
        return '{{%order_invoices}}';
    }

   /* /*
     * 插入 order_invoice_detail 详情的数据
    */
    public static function insertDetailByOrderId($orderID,$invoiceDetail,$invoiceLogistics){
        $model = new self();
        $model ->order_id = $orderID;
        $model ->order_invoice_detail = $invoiceDetail;
        $model -> invoice_logistics = $invoiceLogistics;
        if($model->insert()){
            return true;
        }else{
            return false;
        }
    }

    /*
     * 查询数据库是否存在该条记录
    */
    public static function checkDetailByOrderId($orderID){
        return self::find()
            ->select('*')
            ->from(self::tableName())
            ->where('order_id=:order_id', array(':order_id'=>$orderID))
            ->one();
    }
    /*
     *更新数据库记录
    */
    public static function updateDetailByOrderId($id,$invoiceDetail,$invoiceLogistics){
        $model = self::findOne($id);
        $model -> order_invoice_detail = $invoiceDetail;
        $model -> invoice_logistics = $invoiceLogistics;
        if($model->update()){
           return true;
        }else{
            return false;
        }
    }

}

