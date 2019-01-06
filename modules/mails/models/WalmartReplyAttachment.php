<?php

namespace app\modules\mails\models;

use Yii;

class WalmartReplyAttachment extends MailsModel
{
    /**
     * 返回操作表名
     */
    public static function tableName()
    {
        return '{{%walmart_reply_attachment}}';
    }

    /**
     * 返回规则
     */
    public function rules()
    {
        return [
            [['walmart_reply_id'], 'integer'],
            [['name'], 'string', 'max' => 128],
            [['file_path'], 'string', 'max' => 256],
        ];
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'walmart_reply_id' => '回复ID',
            'name' => '附件名称',
            'file_path' => '储存路径',
        ];
    }
}