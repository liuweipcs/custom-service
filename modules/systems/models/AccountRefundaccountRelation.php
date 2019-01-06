<?php

namespace app\modules\systems\models;

use Yii;

/**
 * This is the model class for table "{{%account_refund_account_relation}}".
 *
 * @property integer $id
 * @property integer $account_id
 * @property integer $old_account_id
 * @property integer $refund_account_id
 */
class AccountRefundaccountRelation extends SystemsModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%account_refund_account_relation}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'old_account_id', 'refund_account_id'], 'required'],
            [['account_id', 'old_account_id', 'refund_account_id'], 'integer'],
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
            'old_account_id' => 'Old Account ID',
            'refund_account_id' => 'Refund Account ID',
        ];
    }
    /**
     * 判断是否绑定退款账号
     */
    public static function getCountByRefundaccountId($refund_account_id)
    {
        $query = new \yii\db\Query();
        $count = $query->from(self::tableName())
                       ->where('refund_account_id=:refund_account_id',[':refund_account_id' => $refund_account_id])
                       ->count();
        return $count;
    }
    /** 根基账户id获取已经绑定的退票账号id **/
    public static function getRefundAccountId($account_id)
    {
        $model = self::find()->where(['account_id'=>$account_id])->one();

        if (empty($model)) {
            return null;
        }
        return $model->refund_account_id;
    }
}
