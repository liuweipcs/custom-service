<?php

namespace app\modules\logistics\models;

use Yii;
use app\components\Model;

class LogisticsKefu extends Model
{
    public static $onUse = 1;
    public static $onUses = 3;
    public static $stopUse = 0;

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
        return '{{%logistics}}';
    }

    /**
     * 获取物流名称
     */
    public static function getLogisticsName($ship_code)
    {
        $shipType = LogisticsTypeKefu::getLogisticsType();
        if (isset($shipType[$ship_code])) {
            return $shipType[$ship_code];
        } else {
            $logisticsInfo = self::find()
                ->andwhere([
                    'or',
                    ['use_status' => self::$onUse],
                    ['use_status' => self::$onUses]
                ])
                ->andWhere(['ship_code' => $ship_code])
                ->asArray()
                ->one();
            return $logisticsInfo['ship_name'];
        }
    }

    /**
     * 获取物流信息
     */
    public static function getViewNameIdByShipCode($ship_code)
    {
        $logisticsInfo = self::findAll(['ship_code' => $ship_code]);
        return $logisticsInfo;
    }
}