<?php

namespace app\modules\aftersales\models;

use Yii;

/**
 * This is the model class for table "{{%return_rule_processing_result}}".
 *
 * @property integer $id
 * @property string $platform_code
 * @property string $order_id
 * @property integer $erp_rule
 * @property integer $is_run_kfrule
 * @property integer $is_case
 * @property integer $is_return
 * @property integer $is_resend
 * @property integer $is_message
 * @property integer $is_feedback
 * @property string $result
 * @property string $processing_date
 */
class ReturnRuleProcessingResult extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%return_rule_processing_result}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['erp_rule', 'is_run_kfrule', 'is_case', 'is_return', 'is_resend', 'is_message', 'is_feedback'], 'integer'],
            [['processing_date'], 'safe'],
            [['platform_code', 'result'], 'string', 'max' => 255],
            [['order_id'], 'string', 'max' => 40],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_code' => 'Platform Code',
            'order_id' => 'Order ID',
            'erp_rule' => 'Erp Rule',
            'is_run_kfrule' => 'Is Run Kfrule',
            'is_case' => 'Is Case',
            'is_return' => 'Is Return',
            'is_resend' => 'Is Resend',
            'is_message' => 'Is Message',
            'is_feedback' => 'Is Feedback',
            'result' => 'Result',
            'processing_date' => 'Processing Date',
        ];
    }
}
