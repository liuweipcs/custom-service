<?php
/**
 * @desc systems model base class
 * @author Fun
 */
namespace app\modules\systems\models;
use app\components\Model;
class SystemsModel extends Model
{
    /**
     * @desc set db components
     */
    public static function getDb()
    {
        return \Yii::$app->db;
    }
}