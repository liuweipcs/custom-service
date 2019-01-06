<?php

namespace app\modules\mails\models;

use Yii;
use app\modules\mails\models\AmazonSendMailAttachment;

/**
 * This is the model class for table "{{%amazon_sendmail}}".
 *
 * @property string $id
 * @property string $order_id
 * @property integer $type
 * @property string $from
 * @property string $to
 * @property string $subject
 * @property string $body
 * @property string $creator
 * @property string $create_date
 */
class AmazonSendMail extends \app\modules\mails\models\MailsModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_sendmail}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type'], 'integer'],
            [['body'], 'string'],
            [['create_date'], 'safe'],
            [['order_id'], 'string', 'max' => 20],
            [['from', 'to'], 'string', 'max' => 128],
            [['subject'], 'string', 'max' => 255],
            [['creator'], 'string', 'max' => 80],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'type' => '1: feedback 2:return',
            'from' => 'From',
            'to' => 'To',
            'subject' => 'Subject',
            'body' => 'Body',
            'creator' => 'Creator',
            'create_date' => 'Create Date',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasMany(AmazonSendMailAttachment::className(), ['amazon_sendmail_id' => 'id']);
    }
}
