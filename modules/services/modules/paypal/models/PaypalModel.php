<?php
namespace app\modules\services\modules\paypal\models;

use app\components\Model;

class PaypalModel extends Model
{
    public static function getDb()
    {
        return \Yii::$app->db_system;
    }
}