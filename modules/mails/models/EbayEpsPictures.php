<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/2 0002
 * Time: 下午 2:23
 */

namespace app\modules\mails\models;


class EbayEpsPictures extends MailsModel
{
    public static function tableName()
    {
        return '{{%ebay_eps_pictures}}';
    }
    public function getCollects()
    {
        return $this->hasMany(EbayEpsPicturesCollect::className(),['master_id'=>'id']);
    }

    public function getMaxCollect()
    {
        return $this->hasMany(EbayEpsPicturesCollect::className(),['master_id'=>'id'])
                    ->orderBy('picture_height DESC')->one();
    }
}