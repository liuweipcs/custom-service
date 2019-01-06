<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderEbayKefu extends Model
{
    public $orderdetail = 'order_ebay_detail';
    public $orderdetailcopy = 'order_ebay_detail_copy';
    public $ordermain = 'order_ebay';
    public $ordermaincopy = 'order_ebay_copy';
    public $ordernote = 'order_ebay_note';
    public $ordertransaction = 'order_ebay_transaction';
    public $ordertransactioncopy = 'order_ebay_transaction_copy';
    public $orderremark = 'order_remark';

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    public static function tableName()
    {
        return '{{%order_ebay}}';
    }
}