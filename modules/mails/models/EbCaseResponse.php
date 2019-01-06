<?php

namespace app\modules\mails\models;

use Yii;

/**
 * This is the model class for table "{{%ebay_case_response}}".
 *
 * @property string $id
 * @property string $case_id
 * @property string $content
 * @property integer $type
 * @property integer $status
 * @property string $refund_source
 * @property string $refund_status
 * @property string $error
 * @property integer $lock_status
 * @property string $lock_time
 * @property string $account_id
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 */
class EbCaseResponse extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ebay_case_response}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['content', 'error', 'account_id'], 'required'],
            [['content', 'error'], 'string'],
            [['type', 'status', 'lock_status', 'account_id'], 'integer'],
            [['lock_time', 'create_time', 'modify_time'], 'safe'],
            [['case_id', 'create_by', 'modify_by'], 'string', 'max' => 50],
            [['refund_source'], 'string', 'max' => 30],
            [['refund_status'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'case_id' => 'Case ID',
            'content' => 'Content',
            'type' => 'Type',
            'status' => 'Status',
            'refund_source' => 'Refund Source',
            'refund_status' => 'Refund Status',
            'error' => 'Error',
            'lock_status' => 'Lock Status',
            'lock_time' => 'Lock Time',
            'account_id' => 'Account ID',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'modify_by' => 'Modify By',
            'modify_time' => 'Modify Time',
        ];
    }
}
