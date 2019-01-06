<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家业绩指标
 */
class EbaySellerStandardsProfile extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_standards_profile}}';
    }
}