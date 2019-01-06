<?php

namespace app\modules\mails\models;

use Yii;
use app\modules\accounts\models\Account;

/**
 * This is the model class for table "{{%amazon_fba_return}}".
 *
 * @property string $id
 * @property integer $account_id
 * @property string $return_date
 * @property string $order_id
 * @property string $sku
 * @property string $asin
 * @property string $fnsku
 * @property string $product_name
 * @property integer $quantity
 * @property string $fulfillment_center_id
 * @property string $detailed-disposition
 * @property string $reason
 * @property string $status
 * @property string $upadte_date
 */
class AmazonFBAReturn extends MailsModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_fba_return}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'return_date', 'hash'], 'required'],
            [['account_id', 'quantity'], 'integer'],
            [['return_date', 'upadte_date'], 'safe'],
            [['order_id', 'sku', 'asin', 'fnsku', 'fulfillment_center_id', 'detailed_disposition', 'reason'], 'string', 'max' => 20],
            [['product_name'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 80],
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
            'return_date' => 'Return Date',
            'order_id' => 'Order ID',
            'sku' => 'Sku',
            'asin' => 'Asin',
            'fnsku' => 'Fnsku',
            'product_name' => 'Product Name',
            'quantity' => 'Quantity',
            'fulfillment_center_id' => 'Fulfillment Center ID',
            'detailed-disposition' => 'Detailed Disposition',
            'reason' => 'Reason',
            'status' => 'Status',
            'upadte_date' => 'Upadte Date',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(Account::className(), ['id' => 'account_id']);
    }
    
    /**
     * @inheritdoc
     */
    public function searchList($params = [], $sort = null)
    {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = [
            'return_date' => SORT_DESC,
        ];

        $dataProvider = parent::search(null, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);

        $dataProvider->setModels($models);

        return $dataProvider;
    }

    public function addition(&$models)
    {
        foreach ($models as $model) {
            $model->account_id = $model->account->account_name;
        }
    }

    /**
     * @inheritdoc
     */
    public function filterOptions()
    {
        return [
            [
                'name'   => 'account_id',
                'type'   => 'search',
                'data'   => AmazonInbox::getAccountList(),
                'search' => '=',
            ],
        ];
    }
}
