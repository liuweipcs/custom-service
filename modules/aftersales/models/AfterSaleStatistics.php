<?php

namespace app\modules\aftersales\models;

use Yii;

/**
 * This is the model class for table "{{%after_sale_statistics}}".
 *
 * @property integer $id
 * @property string $platform_code
 * @property integer $department_id
 * @property integer $reason_type_id
 * @property integer $formula_id
 * @property integer $account_id
 * @property string $account_name
 * @property string $after_sales_id
 * @property integer $type
 * @property string $refund_amount
 * @property string $refund_amount_rmb
 * @property string $subtotal
 * @property string $subtotal_rmb
 * @property string $currency
 * @property string $exchange_rate
 * @property integer $create_id
 * @property string $create_by
 * @property string $create_time
 * @property integer $status
 * @property string $pro_cost_rmb
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
            [['platform_code', 'account_id','after_sale_id'], 'required'],
            [['department_id', 'reason_type_id', 'formula_id', 'account_id', 'type', 'create_id', 'status'], 'integer'],
            [['refund_amount', 'refund_amount_rmb', 'subtotal', 'subtotal_rmb', 'exchange_rate', 'pro_cost_rmb'], 'number'],
            [['create_time'], 'safe'],
            [['platform_code', 'after_sales_id'], 'string', 'max' => 50],
            [['account_name'], 'string', 'max' => 128],
            [['currency'], 'string', 'max' => 32],
            [['create_by'], 'string', 'max' => 255],
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
            'after_sales_id' => 'After Sales ID',
            'type' => 'Type',
            'refund_amount' => 'Refund Amount',
            'refund_amount_rmb' => 'Refund Amount Rmb',
            'subtotal' => 'Subtotal',
            'subtotal_rmb' => 'Subtotal Rmb',
            'currency' => 'Currency',
            'exchange_rate' => 'Exchange Rate',
            'create_id' => 'Create ID',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'status' => 'Status',
            'pro_cost_rmb' => 'Pro Cost Rmb',
        ];
    }
}
