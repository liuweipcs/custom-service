<?php

namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家设置SpeedPAK物流选项
 */
class EbaySellerSpeedpakMisuse extends Model
{
    public static function tableName()
    {
        return '{{%ebay_seller_speedpak_misuse}}';
    }
}