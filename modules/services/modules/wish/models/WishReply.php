<?php

namespace wish\models;

use Yii;
use app\components\Model;

/**
 * This is the model class for table "{{%wish_reply}}".
 *
 * @property integer $id
 * @property integer $inbox_id
 * @property string $message
 * @property string $message_translated
 * @property string $message_zh
 * @property string $image_urls
 * @property string $message_time
 * @property string $type
 * @property string $reply_by
 * @property integer $is_draft
 * @property integer $is_delete
 * @property integer $is_send
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 */
class WishReply extends Model
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wish_reply}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id', 'inbox_id', 'is_draft', 'is_delete', 'is_send'], 'integer'],
            [['message_time', 'create_time', 'modify_time'], 'safe'],
            [['message', 'message_translated', 'message_zh'], 'string', 'max' => 1000],
            [['image_urls'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 20],
            [['reply_by', 'create_by', 'modify_by'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'inbox_id' => 'Inbox ID',
            'message' => 'Message',
            'message_translated' => 'Message Translated',
            'message_zh' => 'Message Zh',
            'image_urls' => 'Image Urls',
            'message_time' => 'Message Time',
            'type' => 'Type',
            'reply_by' => 'Reply By',
            'is_draft' => 'Is Draft',
            'is_delete' => 'Is Delete',
            'is_send' => 'Is Send',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'modify_by' => 'Modify By',
            'modify_time' => 'Modify Time',
        ];
    }
}
