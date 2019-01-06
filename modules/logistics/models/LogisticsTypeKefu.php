<?php

namespace app\modules\logistics\models;

use Yii;
use app\components\Model;

class LogisticsTypeKefu extends Model
{
    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_logistics;
    }

    /**
     * 返回当前模型的表名
     */
    public static function tableName()
    {
        return '{{%logistics_type}}';
    }

    /**
     * 获取物流类型
     */
    public static function getLogisticsType()
    {
        $typeData = array();
        $logisticsTypeList = self::find()
            ->select('id, type_code, type_name, type_period')
            ->from(self::tableName())
            ->asArray()
            ->all();

        if (!empty($logisticsTypeList)) {
            foreach ($logisticsTypeList as $value) {
                $typeData[$value['id']] = $value['type_name'];
            }
        }
        return $typeData;
    }
}