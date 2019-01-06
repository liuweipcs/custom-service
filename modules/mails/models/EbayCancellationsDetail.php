<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/16 0016
 * Time: 下午 5:53
 */

namespace app\modules\mails\models;


class EbayCancellationsDetail extends MailsModel
{
    public static function tableName()
    {
        return '{{%ebay_cancellations_detail}}';
    }
}