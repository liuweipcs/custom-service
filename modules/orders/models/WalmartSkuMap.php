<?php

namespace app\modules\orders\models;

use Yii;

class WalmartSkuMap extends OrderModel
{

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {

        return Yii::$app->db_product;

    }

    /**
     * 返回当前模型的表名 
     */
    public static function tableName()
    {
        return '{{%walmart_sku_map}}';
    }

}