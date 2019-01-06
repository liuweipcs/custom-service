<?php

namespace app\modules\reports\models;

use Yii;

/**
 * This is the model class for table "{{%after_sale_statistics}}".
 *
 * @property integer $id
 * @property string $after_sale_id
 * @property string $platform_code
 * @property integer $department_id
 * @property integer $reason_type_id
 * @property integer $formula_id
 * @property integer $account_id
 * @property string $account_name
 * @property integer $type
 * @property string $refund_amount
 * @property string $refund_amount_rmb
 * @property string $subtotal
 * @property string $subtotal_rmb
 * @property string $currency
 * @property string $exchange_rate
 * @property string $create_by
 * @property string $create_time
 * @property integer $status
 * @property string $pro_cost_rmb
 * @property string $add_time
 */
class AfterSaleStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%after_sale_statistics}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_code', 'account_id'], 'required'],
            [['department_id', 'reason_type_id', 'formula_id', 'account_id', 'type', 'status'], 'integer'],
            [['refund_amount', 'refund_amount_rmb', 'subtotal', 'subtotal_rmb', 'exchange_rate', 'pro_cost_rmb'], 'number'],
            [['create_time', 'add_time'], 'safe'],
            [['after_sale_id'], 'string', 'max' => 20],
            [['platform_code'], 'string', 'max' => 50],
            [['account_name'], 'string', 'max' => 128],
            [['currency'], 'string', 'max' => 32],
            [['create_by'], 'string', 'max' => 255],
            [['after_sale_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'after_sale_id' => 'After Sale ID',
            'platform_code' => 'Platform Code',
            'department_id' => 'Department ID',
            'reason_type_id' => 'Reason Type ID',
            'formula_id' => 'Formula ID',
            'account_id' => 'Account ID',
            'account_name' => 'Account Name',
            'type' => 'Type',
            'refund_amount' => 'Refund Amount',
            'refund_amount_rmb' => 'Refund Amount Rmb',
            'subtotal' => 'Subtotal',
            'subtotal_rmb' => 'Subtotal Rmb',
            'currency' => 'Currency',
            'exchange_rate' => 'Exchange Rate',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'status' => 'Status',
            'pro_cost_rmb' => 'Pro Cost Rmb',
            'add_time' => 'Add Time',
        ];
    }
}
