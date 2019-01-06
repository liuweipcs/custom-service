<?php
namespace app\modules\aftersales\models;
class AfterSalesProduct extends AfterSalesModel {
    
    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%after_sales_product}}';
    }

    public function attributes() {
        $attributes = parent::attributes();
        $extraAttributes = ['refund_amount','currency']; // 退款金额,退款人民币

        return array_merge($attributes, $extraAttributes);
    }
}