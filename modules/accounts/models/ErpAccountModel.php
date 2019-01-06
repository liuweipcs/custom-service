<?php
/**
 * @desc Account Model
 * @author Fun
 */
namespace app\modules\accounts\models;
use app\components\Model;
class ErpAccountModel extends Model
{
    /**
     * @desc set db connection
     */
    public static function getDb()
    {
        return \yii::$app->db_system;
    }
}