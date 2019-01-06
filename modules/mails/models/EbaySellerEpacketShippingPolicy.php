<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家物流标准(美国>$5交易)
 */
class EbaySellerEpacketShippingPolicy extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_epacket_shipping_policy}}';
    }
}