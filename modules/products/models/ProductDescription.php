<?php

namespace app\modules\products\models;

use Yii;
use app\components\Model;

class ProductDescription extends Model
{
    public static function getDb()
    {
        return Yii::$app->db_product;
    }

    public static function tableName()
    {
        return '{{%product_description}}';
    }

    /**
     * 获取产品sku
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAllSku()
    {
        $sku_data = [];
        $res      = self::find()->select(['id', 'sku', 'title'])->where(['language_code' => 'Chinese'])->limit(20)->asArray()->all();
        if (!empty($res)) {
            foreach ($res as $v) {
                $sku_data[$v['sku']] = $v['sku'] . $v['title'];
            }
        }
        return $sku_data;
    }

    /**
     * 获取sku中文名称
     * @param  [string] $sku [sku]
     * @return [string]      
     */
    public static function getProductCnNameBySku($sku){
        $languageCode = 'Chinese';
        $picking_name = self::find()
                    ->select('title')
                    ->where(['sku' => $sku, 'language_code' => $languageCode])
                    ->scalar();

        return $picking_name;
    }

}
