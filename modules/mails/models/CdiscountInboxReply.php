<?php

namespace app\modules\mails\models;

use app\components\Model;

class CdiscountInboxReply extends Model
{
    public static function tableName()
    {
        return '{{%cdiscount_inbox_reply}}';
    }

    public function rules()
    {
        return [
            [['account_id', 'inbox_subject_id', 'inbox_id', 'is_send'], 'integer'],
            [['reply_title', 'reply_content', 'reply_content_en'], 'string'],
            [['reply_by', 'reply_time'], 'safe'],
        ];
    }


    public function setHadSync($replyId)
    {
        $reply = self::findOne($replyId);
        if (empty($reply)) {
            return false;
        }
        $reply->is_send = 1;
        return $reply->save();
    }
}