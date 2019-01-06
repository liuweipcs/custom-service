<?php
namespace app\modules\mails\models;
use app\components\Model;
class MailsModel extends Model
{
    public static function getDb()
    {
        return \Yii::$app->db;
    }

    public static function getFieldList($fields,$value,$key,$where = 1)
    {
        return array_column(self::find()->select($fields)->distinct()->where($where)->asArray()->all(),$value,$key);
    }
}