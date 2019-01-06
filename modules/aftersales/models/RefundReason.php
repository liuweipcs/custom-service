<?php

namespace app\modules\aftersales\models;

use Yii;

/**
 * This is the model class for table "{{%refund_reason}}".
 *
 * @property integer $id
 * @property integer $department_id
 * @property integer $reason_type_id
 * @property integer $formula_id
 * @property string $remark
 * @property integer $create_by_id
 * @property string $create_by
 * @property string $create_time
 * @property integer $update_by_id
 * @property string $update_by
 * @property string $update_time
 */
class RefundReason extends \yii\db\ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%refund_reason}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['department_id', 'reason_type_id', 'formula_id'], 'required'],
            [['department_id', 'reason_type_id', 'formula_id', 'create_by_id', 'update_by_id'], 'integer'],
            [['remark'], 'string'],
            [['create_time', 'update_time','refund_cost_id','resend_cost_id'], 'safe'],
            [['create_by', 'update_by'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'department_id' => '责任所属部门',
            'reason_type_id' => '原因类别',
            'formula_id' => '亏损计算方式',
            'refund_cost_id' => '退款成本计算方式',
            'resend_cost_id' => '重寄成本计算方式',
            'remark' => '备注',
            'create_by_id' => '创建人ID',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'update_by_id' => '最后修改人ID',
            'update_by' => '最后修改人',
            'update_time' => '最后修改时间',
        ];
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->create_by_id = Yii::$app->user->id;
                $this->create_by = Yii::$app->user->identity->user_name;
                $this->create_time = date("Y-m-d H:i:s");
            } else {
                $this->update_by_id = Yii::$app->user->id;
                $this->update_by = Yii::$app->user->identity->user_name;
                $this->update_time = date("Y-m-d H:i:s");
            }
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 获取亏损计算方式
     * @param type $departmentId
     * @param type $reasonId
     * @return type
     * @author allen <2018-03-31>
     */
     public static function getLossCalculationMethod($departmentId,$reasonId){
        $formulaId = "";
        $model = self::find()->select('formula_id')->where(['department_id' => $departmentId,'reason_type_id' => $reasonId])->asArray()->one();
        if($model){
            $formulaId = $model['formula_id'];
        }
        return $formulaId;
    }

}
