<?php

namespace app\modules\aftersales\models;

use Yii;

/**
 * This is the model class for table "ueb_amazon_fba_return_detail".
 *
 * @property integer $id
 * @property integer $account_id
 * @property integer $old_account_id
 * @property string $platform_order_id
 * @property string $order_id
 * @property string $sku
 * @property integer $qty
 * @property string $created_time
 * @property string $add_time
 */
class AmazonFbaReturnDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_fba_return_detail}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'old_account_id', 'qty'], 'integer'],
            [['created_time', 'add_time'], 'safe'],
            [['platform_order_id', 'sku'], 'string', 'max' => 80],
            [['order_id'], 'string', 'max' => 100],
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
            'platform_order_id' => 'Platform Order ID',
            'order_id' => 'Order ID',
            'sku' => 'Sku',
            'qty' => 'Qty',
            'created_time' => 'Created Time',
            'add_time' => 'Add Time',
        ];
    }
}
