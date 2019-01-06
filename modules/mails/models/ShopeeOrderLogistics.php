<?php

namespace app\modules\mails\models;

use app\components\Model;

class ShopeeOrderLogistics extends Model
{

    public static function tableName()
    {
        return '{{%shopee_order_logistics}}';
    }

}