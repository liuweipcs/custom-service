<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家海外仓标准
 */
class EbaySellerSdWarehouse extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_sd_warehouse}}';
    }
}