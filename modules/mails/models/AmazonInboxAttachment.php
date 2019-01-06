<?php

namespace app\modules\mails\models;

use Yii;

/**
 * This is the model class for table "{{%amazon_inbox_attachment}}".
 *
 * @property string $id
 * @property integer $amazon_inbox_id
 * @property string $attachment_id
 * @property string $name
 * @property string $file_path
 */
class AmazonInboxAttachment extends MailsModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_inbox_attachment}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['amazon_inbox_id'], 'integer'],
            [['attachment_id'], 'string', 'max' => 30],
            [['name'], 'string', 'max' => 128],
            [['file_path'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'amazon_inbox_id' => '消息ID',
            'attachment_id' => '附件ID',
            'name' => '附件名称',
            'file_path' => '储存路径',
        ];
    }
}
