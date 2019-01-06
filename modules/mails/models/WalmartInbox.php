<?php

namespace app\modules\mails\models;

use app\modules\accounts\models\Platform;

class WalmartInbox extends Inbox
{
    const IS_REPLIED_NO = 0; //未回复
    const IS_REPLIED_YES_NO_SYNCHRO = 1; //已回复未同步
    const IS_REPLIED_YES_YES_SYNCHRO = 2; //已回复已同步

    const PLATFORM_CODE = Platform::PLATFORM_CODE_WALMART;

    //默认无附件
    public $attch = 0;
    public $account_name;
    public $inbox_id;
    public $reply_content;
    public $reply_title;
    public $reply_by;

    /**
     * 返回操作的表名
     */
    public static function tableName()
    {
        return '{{%walmart_inbox}}';
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [
            'history',
            'attachments',
        ];
        return array_merge($attributes, $extAttributes);
    }

    /**
     * 获取订单
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * 获取消息标题
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * 获取账号ID
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * 获取发件人
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * 获取发件邮箱
     */
    public function getSenderEmail()
    {
        return $this->sender_email;
    }

    /**
     * 获取消息内容
     */
    public function getContent()
    {
        return $this->body;
    }

    /**
     * 是否有附件
     */
    public static function wherethrAttch($id, $attchSign)
    {
        $attch = WalmartInboxAttachment::find()
            ->where(['walmart_inbox_id' => $id])
            ->all();

        if (!empty($attch)) {
            $attchSign = 1;
        } else {
            $attchSign = 0;
        }

        $attchType = ['', '<i class="fa fa-file-archive-o" style="color:#000; font-size:18px;"></i>'];

        return $attchType[$attchSign];
    }

    /**
     * 邮件判断是否回复
     */
    public static function getReplied($id)
    {
        $inboxDate = self::find()->select('is_replied')->where(['id' => $id])->asArray()->scalar();

        return $inboxDate;
    }
}