<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/20 0020
 * Time: 上午 9:56
 */

namespace app\modules\mails\models;
use app\components\MongodbModel;

class EbayInboxContentMongodb extends MongodbModel
{
    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'ebay_inbox_content';
    }

    public function attributes()
    {
        return [
            '_id', 'ueb_ebay_inbox_id','message_id', 'content','image_url'
        ];
    }
    
}