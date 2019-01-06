<?php

namespace app\modules\mails\models;

use Yii;

/**
 * This is the model class for table "{{%amazon_sendmail_attachment}}".
 *
 * @property string $id
 * @property integer $amazon_sendmail_id
 * @property string $name
 * @property string $file_path
 */
class AmazonSendMailAttachment extends \app\modules\mails\models\MailsModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_sendmail_attachment}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['amazon_sendmail_id'], 'integer'],
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
            'amazon_sendmail_id' => 'ID',
            'name' => '附件名称',
            'file_path' => '储存路径',
        ];
    }
}
