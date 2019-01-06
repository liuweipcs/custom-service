<?php

namespace app\modules\systems\models;

use app\components\Model;

class ServerEmailMonitor extends Model
{
    public static function tableName()
    {
        return '{{%server_email_monitor}}';
    }
}