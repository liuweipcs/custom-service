<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家非货运表现
 */
class EbaySellerTci extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_tci}}';
    }
}