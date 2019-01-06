<?php
namespace app\modules\mails\models;

use app\components\Model;

class EbayListDownload extends Model
{
    /*
     * 获取SpeedPAK 物流管理方案表
     */
     public static function tableName()
    {
        return '{{%ebay_list_download}}';
    }
    
    
}

