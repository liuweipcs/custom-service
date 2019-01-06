<?php

namespace app\modules\mails\models;

use Yii;
use app\modules\accounts\models\Account;


class WalmartReply extends Reply
{
   // public $reply_content_en;
    /**
     * 返回操作表名
     */
    public static function tableName()
    {
        return '{{%walmart_reply}}';
    }

    /**
     * 返回规则
     */
    public function rules()
    {
        return [
            [['inbox_id', 'reply_title', 'reply_content'], 'required'],
            [['id', 'inbox_id', 'is_draft', 'is_delete', 'is_send'], 'integer'],
            [['reply_content', 'reply_content_en'], 'string'],
            [['create_time', 'modify_time'], 'safe'],
            [['reply_title'], 'string', 'max' => 256],
            [['reply_by', 'create_by', 'modify_by'], 'string', 'max' => 32],
        ];
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [
            'attachments',
        ];
        return array_merge($attributes, $extAttributes);
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'inbox_id' => '邮件ID',
            'reply_title' => '回复标题',
            'reply_content' => '回复内容',
            'reply_content_en' => '翻译的回复内容',
            'reply_by' => '回复人',
            'is_draft' => '是否为草稿',
            'is_delete' => '是否删除',
            'is_send' => '是否发送成功',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
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