<?php

namespace app\modules\systems\models;

use app\components\Model;

class ServerSwitchLog extends Model
{
    public static function tableName()
    {
        return '{{%server_switch_log}}';
    }
}