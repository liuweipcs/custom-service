<?php
namespace app\modules\services\modules\aftersales\models;

use app\components\Model;

class AftersalesFinancialStatistics extends Model
{
    //退款
    const AFTER_TYPE_REFUND = 1;
    //退货
    const AFTER_TYPE_RETURN = 2;
    //重寄
    const AFTER_TYPE_REDIRECT = 3;

    public static function tableName()
    {
        return '{{%aftersales_financial_statistics}}';
    }
}