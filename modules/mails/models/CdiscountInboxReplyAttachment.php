<?php

namespace app\modules\mails\models;

use app\components\Model;

class CdiscountInboxReplyAttachment extends Model
{
    public static function tableName()
    {
        return '{{%cdiscount_inbox_reply_attachment}}';
    }
}