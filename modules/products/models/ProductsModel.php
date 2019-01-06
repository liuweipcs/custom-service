<?php

namespace app\modules\products\models;

use Yii;
use app\components\Model;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/13 0013
 * Time: 上午 10:27
 */
class ProductsModel extends Model
{
    /**
     * 返回操作数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_product;
    }
}