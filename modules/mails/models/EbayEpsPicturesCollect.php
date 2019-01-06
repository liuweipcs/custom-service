<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/2 0002
 * Time: 下午 2:25
 */

namespace app\modules\mails\models;


class EbayEpsPicturesCollect extends MailsModel
{
    public static function tableName()
    {
        return '{{%ebay_eps_pictures_collect}}';
    }

    public function getMaster()
    {
        return $this->hasOne(EbayEpsPictures::className(),['id'=>'master_id']);
    }
}