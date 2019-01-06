<?php
namespace app\modules\orders\models;

use app\components\Model;
class OrderModel extends Model
{
    public static function getDb()
    {
        return \Yii::$app->db;
    }
}