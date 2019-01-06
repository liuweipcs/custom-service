<?php
namespace app\modules\services\modules\joom\models;

use Yii;
use app\components\Model;

/**
 * joom的ERP账号模型
 */
class JoomAccount extends Model
{
    public static function getDb()
    {
        return Yii::$app->db_system;
    }

    public static function tableName()
    {
        return '{{%joom_account}}';
    }
}