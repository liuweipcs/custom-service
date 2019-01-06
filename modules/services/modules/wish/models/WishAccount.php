<?php

namespace wish\models;

use Yii;
use app\components\Model;

/**
 * Wish的ERP账号模型
 */
class WishAccount extends Model
{
    /**
     * 返回操作的表名
     */
    public static function tableName()
    {
        return '{{%wish_account}}';
    }

    /**
     * 返回操作数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_system;
    }
}
