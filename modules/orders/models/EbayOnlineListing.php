<?php

namespace app\modules\orders\models;

use app\components\Model;
use Yii;

class EbayOnlineListing extends Model
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
        return '{{%ebay_online_listing}}';
    }

    /**
     * @author alpha
     * @desc 获取sku location
     * @param $item_id
     * @return mixed|string
     */
    public static function getItemLocation($item_id)
    {
        $location = self::find()
            ->select('location')
            ->from(self::tableName())
            ->where(['itemid' => $item_id])
            ->asArray()
            ->one();
        if(!empty($location)){
            return isset($location['location'])?$location['location']:'';
        }else{
            return '';
        }
    }

    public static function getItemLocationArr($item_id_arr)
    {
        $location_arr=self::find()
            ->select(['itemid','location'])
            ->from(self::tableName())
            ->where(['in','itemid' , $item_id_arr])
            ->asArray()
            ->all();
        return !empty($location_arr)?$location_arr:[];
    }

}