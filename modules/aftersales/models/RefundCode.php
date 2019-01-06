<?php

namespace app\modules\aftersales\models;
use Yii;
class RefundCode extends AfterSalesModel {
    /**
     * 退货编码关联表
     * @return string
     */
    public static function tableName() {

        return '{{%refund_code}}';
    }

}
