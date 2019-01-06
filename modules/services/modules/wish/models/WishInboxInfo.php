<?php

namespace wish\models;

use Yii;
use app\components\Model;

/**
 * This is the model class for table "{{%wish_inbox_info}}".
 *
 * @property integer $info_id
 * @property string $order_id
 * @property string $product_id
 * @property string $variant_id
 * @property string $transaction_id
 * @property string $sku
 * @property string $goods_name
 * @property string $image_url
 * @property string $quantity
 * @property string $product_image
 * @property string $price
 * @property string $state
 * @property string $is_wish_express
 * @property string $shipping_cost
 * @property string $shipping_provider
 * @property string $tracking_confirmed
 * @property string $track_number
 * @property string $track_confrimed_date
 * @property string $phone_number
 * @property string $city
 * @property string $states
 * @property string $receiver_name
 * @property string $zipcode
 * @property string $street_address1
 * @property string $shipped_date
 * @property string $order_time
 * @property string $last_updated
 */
class WishInboxInfo extends Model
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wish_inbox_info}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['price', 'shipping_cost'], 'number'],
            [['track_confrimed_date', 'shipped_date', 'order_time', 'last_updated'], 'safe'],
            [['order_id', 'product_id', 'variant_id', 'transaction_id', 'sku', 'quantity'], 'string', 'max' => 30],
            [['goods_name', 'image_url'], 'string', 'max' => 150],
            [['product_image'], 'string', 'max' => 200],
            [['state', 'shipping_provider', 'track_number', 'phone_number', 'city', 'states'], 'string', 'max' => 20],
            [['receiver_name'], 'string', 'max' => 50],
            [['is_wish_express', 'tracking_confirmed'], 'string', 'max' => 5],
            [['zipcode'], 'string', 'max' => 8],
            [['street_address1'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'info_id' => 'Info ID',
            'order_id' => 'Order ID',
            'product_id' => 'Product ID',
            'variant_id' => 'Variant ID',
            'transaction_id' => 'Transaction ID',
            'sku' => 'Sku',
            'goods_name' => 'Goods Name',
            'image_url' => 'Image Url',
            'quantity' => 'Quantity',
            'product_image' => 'Product Image',
            'price' => 'Price',
            'state' => 'State',
            'is_wish_express' => 'Is Wish Express',
            'shipping_cost' => 'Shipping Cost',
            'shipping_provider' => 'Shipping Provider',
            'tracking_confirmed' => 'Tracking Confirmed',
            'track_number' => 'Track Number',
            'track_confrimed_date' => 'Track Confrimed Date',
            'phone_number' => 'Phone Number',
            'city' => 'City',
            'states' => 'States',
            'receiver_name' => 'Receiver Name',
            'zipcode' => 'Zipcode',
            'street_address1' => 'Street Address1',
            'shipped_date' => 'Shipped Date',
            'order_time' => 'Order Time',
            'last_updated' => 'Last Updated',
        ];
    }
    
}
