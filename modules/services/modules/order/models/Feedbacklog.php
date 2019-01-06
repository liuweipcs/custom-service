<?php
namespace app\modules\services\modules\order\models;
use app\components\Model;
class Feedbacklog extends Model
{
    public static function getDb()
    {
        return \Yii::$app->db;
    }
    
    
    public static function tableName()
    {
        return '{{%ebay_feedback_log}}';
    }
}    


