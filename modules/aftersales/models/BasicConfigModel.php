<?php
/**
 * @desc accounts model base class
 * @author Fun
 */
namespace app\modules\aftersales\models;
use app\components\Model;
class BasicConfigModel extends Model
{
    /**
     * @desc set db components
     */
    public static function getDb()
    {
        return \Yii::$app->db;
    }
    
      /**
     * 默认表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%basic_config}}';
    }
    
}