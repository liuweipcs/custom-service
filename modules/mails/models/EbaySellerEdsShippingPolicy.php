<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家物流标准(美国小于5美金)
 */
class EbaySellerEdsShippingPolicy extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_eds_shipping_policy}}';
    }
}