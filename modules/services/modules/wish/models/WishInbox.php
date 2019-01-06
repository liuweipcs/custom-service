<?php

namespace wish\models;

use Yii;
use app\components\Model;

/**
 * This is the model class for table "{{%wish_inbox}}".
 *
 * @property integer $id
 * @property integer $info_id
 * @property string $transaction_id
 * @property string $platform_id
 * @property integer $account_id
 * @property string $merchant_id
 * @property string $label
 * @property string $sublabel
 * @property string $open_date
 * @property string $state
 * @property string $subject
 * @property integer $photo_proof
 * @property string $user_locale
 * @property string $user_id
 * @property string $user_name
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 */
class WishInbox extends Model
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wish_inbox}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['info_id', 'account_id', 'photo_proof'], 'integer'],
            [['open_date', 'create_time', 'modify_time'], 'safe'],
            [['transaction_id', 'platform_id', 'merchant_id', 'user_locale', 'user_id', 'user_name'], 'string', 'max' => 30],
            [['label', 'subject'], 'string', 'max' => 150],
            [['sublabel', 'state'], 'string', 'max' => 100],
            [['create_by', 'modify_by'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'info_id' => 'Info ID',
            'transaction_id' => 'Transaction ID',
            'platform_id' => 'Platform ID',
            'account_id' => 'Account ID',
            'merchant_id' => 'Merchant ID',
            'label' => 'Label',
            'sublabel' => 'Sublabel',
            'open_date' => 'Open Date',
            'state' => 'State',
            'subject' => 'Subject',
            'photo_proof' => 'Photo Proof',
            'user_locale' => 'User Locale',
            'user_id' => 'User ID',
            'user_name' => 'User Name',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'modify_by' => 'Modify By',
            'modify_time' => 'Modify Time',
        ];
    }
}
