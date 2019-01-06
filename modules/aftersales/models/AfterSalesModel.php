<?php
/**
 * @desc accounts model base class
 * @author Fun
 */
namespace app\modules\aftersales\models;
use app\components\Model;
class AfterSalesModel extends Model
{
    /**
     * @desc set db components
     */
    public static function getDb()
    {
        return \Yii::$app->db;
    }
}