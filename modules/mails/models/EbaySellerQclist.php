<?php
namespace app\modules\mails\models;

use app\components\Model;

/**
 * ebay卖家待处理刊登列表
 */
class EbaySellerQclist extends Model
{

    public static function tableName()
    {
        return '{{%ebay_seller_qclist}}';
    }
}