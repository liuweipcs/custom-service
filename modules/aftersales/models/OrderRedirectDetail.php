<?php
namespace app\modules\aftersales\models;
class OrderRedirectDetail extends AfterSalesModel {
    
    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%order_redirect_detail}}';
    }
    /**
     * @desc 获取重寄的产品信息
     */
    public static function getList($after_sale_id)
    {
        return self::find()->where(['after_sale_id' => $after_sale_id])->all();
    }
    /**
     * @desc 获取售后单的重寄明细
     * @param unknown $id
     * @return multitype:\yii\db\static
     */
    public static function getByAfterSalesId($id)
    {
        return self::findAll(['after_sale_id' => $id]);
    }
}