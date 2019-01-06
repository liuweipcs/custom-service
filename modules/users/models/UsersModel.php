<?php
/**
 * @desc users model base class
 * @author Fun
 */
namespace app\modules\users\models;
use app\components\Model;
class UsersModel extends Model
{
    /**
     * @desc set db components
     */
    public static function getDb()
    {
        return \Yii::$app->db;
    }
}