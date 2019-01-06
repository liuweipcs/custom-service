<?php

namespace app\modules\mails\models;

use Yii;

/**
 * This is the model class for table "{{%aliexpress_dispute_message_reply_list}}".
 *
 */
class AliexpressDisputeMessageReplyList extends MailsModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_dispute_message_reply_list}}';
    }
    
}
?>