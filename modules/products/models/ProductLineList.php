<?php

namespace app\modules\products\models;

class ProductLineList extends ProductsModel
{

    public static function tableName()
    {
        return '{{%product_linelist}}';
    }


    /**
     * @return array
     */
    public static function getLineList()
    {
        $list = array();
        if (empty($list)) {
            $result=self::find()->select('id,linelist_cn_name,linelist_parent_id')->asArray()->all();
            $list   = array();
            if (!empty($result)) {
                foreach ($result as $key => $val) {
                    $list[intval($val['id'])] = $val;
                }
            }
            \Yii::$app->cache->set('pls', $list, 3600 * 6);
        }
        return $list;
    }

}