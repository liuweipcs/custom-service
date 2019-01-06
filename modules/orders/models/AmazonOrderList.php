<?php

namespace app\modules\orders\models;

use app\components\Model;
use Yii;

class AmazonOrderList extends OrderModel
{	
	/**
	 * 返回当前模型连接的数据库
	 */
	public static function getDb()
	{
	    return Yii::$app->db_order;
	}

    public static function tableName()
    {
        return '{{%amazon_order_list}}'; 
    }

    public static function dbTableName()
    {
        preg_match("/dbname=([^;]+)/i", static::getDb()->dsn, $matches);
        return $matches[1] . '.{{%amazon_order_list}}';
    }

}