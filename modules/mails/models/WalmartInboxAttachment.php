<?php

namespace app\modules\mails\models;

use Yii;

/**
 * 沃尔玛邮件附件
 */
class WalmartInboxAttachment extends MailsModel
{
    /**
     * 返回操作的表名
     */
    public static function tableName()
    {
        return '{{%walmart_inbox_attachment}}';
    }

    /**
     * 规则
     */
    public function rules()
    {
        return [
            [['walmart_inbox_id'], 'integer'],
            [['attachment_id'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 128],
            [['file_path'], 'string', 'max' => 256],
        ];
    }

    /**
     * 属性标签
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'walmart_inbox_id' => '邮件ID',
            'attachment_id' => '附件ID',
            'name' => '附件名称',
            'file_path' => '储存路径',
        ];
    }
}
