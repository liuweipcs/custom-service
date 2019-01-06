<?php

namespace app\modules\mails\models;

use Yii;
use app\components\Model;
/**
 * 速卖通纠纷操作记录
 */
class AliexpressDisputeProcess extends Model
{
    public static function getDb()
    {
        return Yii::$app->db;
    }

    public static function tableName()
    {
        return '{{%aliexpress_dispute_process}}';
    }
}