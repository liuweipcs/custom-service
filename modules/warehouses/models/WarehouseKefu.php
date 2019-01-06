<?php
namespace app\modules\warehouses\models;

use Yii;
use app\components\Model;

class WarehouseKefu extends Model
{
    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_warehouse;
    }

    /**
     * 返回当前模型的表名
     */
    public static function tableName() {
        return '{{%warehouse}}';
    }

    /**
     * 获取仓库信息
     */
    public static function getWarehouseById($id){
        return self::findOne(['id' => $id]);
    }

    /**
     * 返回仓库名
     */
    public static function getWarehouseNameById($id){
        $data = self::getWarehouseById($id);
        return isset($data->warehouse_name) ? $data->warehouse_name : '';
    }
}