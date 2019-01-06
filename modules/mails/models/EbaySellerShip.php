<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家货运表现(1-8周)
 */
class EbaySellerShip extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_ship}}';
    }
}