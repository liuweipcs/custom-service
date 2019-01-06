<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家综合表现
 */
class EbaySellerLtnp extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_ltnp}}';
    }
}