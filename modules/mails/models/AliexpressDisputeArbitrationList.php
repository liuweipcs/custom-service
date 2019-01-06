<?php
namespace app\modules\mails\models;
use app\components\Model;
class AliexpressDisputeArbitrationList extends Model
{
    public static function getDb()
    {
        return \Yii::$app->db;
    }
    
    
    public static function tableName()
    {
        return '{{%aliexpress_dispute_arbitration_list}}';
    }
}    