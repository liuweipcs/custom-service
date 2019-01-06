<?php

namespace app\modules\mails\models;

use app\components\Model;

class AliexpressHolidayResponseTime extends Model
{
    public static function tableName()
    {
        return '{{%aliexpress_holiday_response_time}}';
    }
}