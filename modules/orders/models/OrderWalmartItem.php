<?php

namespace app\modules\orders\models;

use app\common\VHelper;
use Yii;

/**
 * This is the model class for table "{{%order_walmart_item}}".
 *
 * @property integer $id
 * @property integer $purchase_order_id
 * @property string $line_number
 * @property string $product_name
 * @property string $sku
 * @property string $product_price
 * @property string $product_price_currency
 * @property string $product_tax_price
 * @property string $product_tax_price_currency
 * @property string $shipping_price
 * @property string $shipping_price_currency
 * @property string $shipping_tax_price
 * @property string $shipping_tax_price_currency
 * @property string $create_time
 */
class OrderWalmartItem extends \yii\db\ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%order_walmart_item}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['order_id'], 'required'],
            [['product_price', 'product_tax_price', 'shipping_price', 'shipping_tax_price'], 'number'],
            [['create_time'], 'safe'],
            [['line_number', 'sku'], 'string', 'max' => 128],
            [['product_name'], 'string', 'max' => 255],
            [['order_id'], 'string', 'max' => 50],
            [['product_price_currency', 'product_tax_price_currency', 'shipping_price_currency', 'shipping_tax_price_currency'], 'string', 'max' => 5],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'purchase_order_id' => 'Purchase Order ID',
            'line_number' => 'Line Number',
            'product_name' => 'Product Name',
            'sku' => 'Sku',
            'product_price' => 'Product Price',
            'product_price_currency' => 'Product Price Currency',
            'product_tax_price' => 'Product Tax Price',
            'product_tax_price_currency' => 'Product Tax Price Currency',
            'shipping_price' => 'Shipping Price',
            'shipping_price_currency' => 'Shipping Price Currency',
            'shipping_tax_price' => 'Shipping Tax Price',
            'shipping_tax_price_currency' => 'Shipping Tax Price Currency',
            'create_time' => 'Create Time',
        ];
    }

    /**
     * @desc 获取订单所有item信息
     * @param unknown $platformOrderId 
     */
    public static function getOrderItems($platformOrderId) {
        $query = new \yii\db\Query();
        return $query->from(self::tableName())
                        ->select("*")
                        ->where('order_id = :order_id', [':order_id' => $platformOrderId])
                        ->all();
    }

    /**
     * 保存拉取的沃尔玛订单产品税相关数据
     * @param type $result
     * @return type
     * @author allen <2018-03-07>
     */
    public static function saveData($result) {
        $bool = FALSE;
        $message = "操作成功!";
        $jsonToArr = json_decode($result, TRUE);

        if (isset($jsonToArr['list']) && !empty($jsonToArr['list'])) {
            if (isset($jsonToArr['list']['elements']['order']) && !empty($jsonToArr['list']['elements']['order'])) {
                foreach ($jsonToArr['list']['elements']['order'] as $infos) {
                    $orderLines = isset($infos['orderLines']['orderLine']) ? $infos['orderLines']['orderLine'] : [];
                    if (!empty($orderLines)) {
                        foreach ($orderLines as $orderLine) {
                            $model = new OrderWalmartItem();
                            $datas['order_id'] = $infos['purchaseOrderId'];
                            $datas['line_number'] = $orderLine['lineNumber'];
                            $datas['product_name'] = $orderLine['item']['productName'];
                            $datas['sku'] = $orderLine['item']['sku'];

                            $charges = $orderLine['charges']['charge'];
                            foreach ($charges as $val) {
                                //保存产品税信息
                                if ($val['chargeType'] == 'PRODUCT') {
                                    $datas['product_price'] = isset($val['chargeAmount']['amount']) ? $val['chargeAmount']['amount'] : "";
                                    $datas['product_price_currency'] = isset($val['chargeAmount']['currency']) ? $val['chargeAmount']['currency'] : "";
                                    $datas['product_tax_price'] = !empty($val['tax']) ? $val['tax']['taxAmount']['amount'] : "";
                                    $datas['product_tax_price_currency'] = !empty($val['tax']) ? $val['tax']['taxAmount']['currency'] : "";
                                }

                                //保存运费税信息
                                if ($val['chargeType'] == 'SHIPPING') {
                                    $datas['shipping_price'] = $val['chargeAmount']['amount'];
                                    $datas['shipping_price_currency'] = $val['chargeAmount']['currency'];
                                    $datas['shipping_tax_price'] = !empty($val['tax']) ? $val['tax']['taxAmount']['amount'] : "";
                                    $datas['shipping_tax_price_currency'] = !empty($val['tax']) ? $val['tax']['taxAmount']['currency'] : "";
                                }
                            }

                            $datas['create_time'] = date("Y-m-d H:i:s");
                            $model->attributes = $datas;
                            if (!$model->save()) {
                                $bool = TRUE;
                                $message = '保存数据失败: ' . VHelper::errorToString($model->getErrors());
                            }
                        }
                    }
                }
            }
        } else if (isset($jsonToArr['error']) && !empty($jsonToArr['error'])) {
            $bool = TRUE;
            $message = $jsonToArr['error'][0]['info'];
        } else {
            $bool = TRUE;
            $message = '获取数据失败!';
        }
        return ['bool' => $bool, 'message' => $message];
    }

}
