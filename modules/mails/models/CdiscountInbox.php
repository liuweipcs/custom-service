<?php

namespace app\modules\mails\models;

use app\components\Model;

class CdiscountInbox extends Model
{
    public static function tableName()
    {
        return '{{%cdiscount_inbox}}';
    }

    /**
     * 设置回复状态
     */
    public function setReplyStatus($inboxId, $replyStatus)
    {
        return true;
    }
}