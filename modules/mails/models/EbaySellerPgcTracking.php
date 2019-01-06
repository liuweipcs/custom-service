<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家商业计划追踪
 */
class EbaySellerPgcTracking extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_pgc_tracking}}';
    }
}