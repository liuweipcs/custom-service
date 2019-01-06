<?php
/**
 * @desc 账号模型
 * @author Fun
 */
namespace app\modules\orders\models;

use app\modules\orders\models\OrderModel;
class OrderAmazonItem extends OrderModel
{
    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%order_amazon_item}}';
    }
    
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Model::rules()
     */
    public function rules()
    {
        return [
            [['user_token', 'order_id', 'asin', 'seller_sku', 'item_id', 'title', 'qty_ordered', 
              'qty_shipped', 'item_currency_code', 'item_price_amount', 'item_shipping_currency_code', 
              'item_shipping_amount', 'item_tax_currency_code', 'item_tax_amount', 'shipping_tax_currency_code',
              'shipping_tax_amount', 'shipping_discount_currency_code', 'shipping_discount_amount',
              'promotion_discount_currency_code', 'promotion_discount_amount', 'promotionId'],'safe'],
        ];
    }
    
    
    /**
     * @desc 获取订单所有item信息
     * @param unknown $platformOrderId
     */
    public static function getOrderItems($platformOrderId)
    {
        $query = new \yii\db\Query();
        return $query->from(self::tableName())
            ->select("*")
            ->where('order_id = :order_id', [':order_id' => $platformOrderId])
            ->all();
    }
}