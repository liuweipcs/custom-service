<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家政策状态
 */
class EbaySellerAccountOverview extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_account_overview}}';
    }
}