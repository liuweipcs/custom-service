<?php

namespace app\modules\mails\models;

use app\components\Model;

class CancelOrderHandle extends Model
{
    public static function tableName()
    {
        return '{{%cancel_order_handle}}';
    }
}