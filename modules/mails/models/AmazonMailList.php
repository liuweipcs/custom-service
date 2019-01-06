<?php
/**
 * @desc 亚马逊已拉取的邮件id
 * @author Fun
 */

namespace app\modules\mails\models;

use app\components\MongodbModel;

class AmazonMailList extends MongodbModel
{
    public $exceptionMessage = null;

    /**
     * @desc 设置集合
     * @return string
     */
    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'amazon_mail_list';
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\mongodb\ActiveRecord::attributes()
     */
    public function attributes()
    {
        return [
            '_id', 'email', 'mid', 'folder','create_time'
        ];
    }
}