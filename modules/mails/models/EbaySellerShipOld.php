<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家货运表现(5-12周)
 */
class EbaySellerShipOld extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_ship_old}}';
    }
}