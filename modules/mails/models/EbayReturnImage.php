<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/29 0029
 * Time: 下午 7:32
 */

namespace app\modules\mails\models;


class EbayReturnImage extends MailsModel
{
    public static function tableName()
    {
        return '{{%ebay_return_image}}';
    }
}