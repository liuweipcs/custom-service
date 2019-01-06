<?php

namespace wish\models;

use Yii;
use app\components\Model;

/**
 * This is the model class for table "{{%wish_notifaction}}".
 *
 * @property integer $id
 * @property integer $account_id
 * @property string $noti_id
 * @property string $title
 * @property string $message
 * @property string $perma_link
 * @property integer $is_checked
 * @property integer $check_user
 * @property string $add_time
 */
class WishNotifaction extends Model
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wish_notifaction}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'is_checked', 'check_user'], 'integer'],
            [['add_time'], 'safe'],
            [['noti_id'], 'string', 'max' => 30],
            [['title'], 'string', 'max' => 300],
            [['message'], 'string', 'max' => 1000],
            [['perma_link'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'noti_id' => 'Noti ID',
            'title' => 'Title',
            'message' => 'Message',
            'perma_link' => 'Perma Link',
            'is_checked' => 'Is Checked',
            'check_user' => 'Check User',
            'add_time' => 'Add Time',
        ];
    }
}
