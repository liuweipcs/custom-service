<?php

namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家SpeedPAK物流管理方案
 */
class EbaySellerSpeedpakList extends Model
{
    public static function tableName()
    {
        return '{{%ebay_seller_speedpak_list}}';
    }
}