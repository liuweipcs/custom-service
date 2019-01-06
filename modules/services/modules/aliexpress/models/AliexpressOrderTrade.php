<?php
namespace app\modules\services\modules\aliexpress\models;

use app\components\Model;

class AliexpressOrderTrade extends Model
{
    public static function tableName()
    {
        return '{{%aliexpress_order_trade}}';
    }
}