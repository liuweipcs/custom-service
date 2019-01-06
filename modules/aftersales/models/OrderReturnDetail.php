<?php
namespace app\modules\aftersales\models;
class OrderReturnDetail extends AfterSalesModel {
    
    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%order_return_detail}}';
    }
    /**
     * @desc 获取退货的产品信息
     */
    public static function getList($after_sale_id)
    {
        return self::find()->where(['after_sale_id' => $after_sale_id])->all();
    }
    /**
     * @desc 获取售后单的退货明细
     * @param unknown $id
     * @return multitype:\yii\db\static
     */
    public static function getByAfterSalesId($id)
    {
        return self::findAll(['after_sale_id' => $id]);
    }
}