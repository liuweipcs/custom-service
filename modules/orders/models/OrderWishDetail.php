<?php
namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderWishDetail extends Model
{
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    public static function tableName()
    {
        return '{{%order_wish_detail}}';
    }
}