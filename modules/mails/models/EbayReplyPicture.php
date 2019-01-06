<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/2 0002
 * Time: 下午 8:58
 */

namespace app\modules\mails\models;


class EbayReplyPicture extends MailsModel
{
    public static function tableName()
    {
        return '{{%ebay_reply_picture}}';
    }

    public function rules()
    {
        return [
            [['reply_table_id','picture_url'],'required'],
            ['reply_table_id','integer','min'=>1],
            ['picture_name','default','value'=>'']
        ];
    }
}