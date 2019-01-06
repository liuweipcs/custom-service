<?php
namespace app\modules\mails\models;

use app\components\Model;

class EbayMisuseDownload extends Model
{
    /*
     * 买家选择SpeedPAK物流
     */
     public static function tableName()
    {
        return '{{%ebay_misuse_download}}';
    }
    
    
}

