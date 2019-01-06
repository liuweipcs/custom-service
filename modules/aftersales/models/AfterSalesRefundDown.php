<?php

namespace app\modules\aftersales\models;

use Yii;
use yii\db\Query;

class AfterSalesRefundDown extends AfterSalesModel
{

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    /**
     * 默认ebay订单表
     * @return string
     */
    public static function tableName()
    {

        return '{{%order_ebay}}';

    }

    /**
     * 获取导出需要的退款相关数据
     * @param $datas
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function getCertainInfo($datas)
    {
        if (is_array($datas) && !empty($datas)) {
            $returnData = [];
            foreach ($datas as $key => $value) {
                switch ($key) {
                    case 'EB':
                        $orderTableName           = '{{%order_ebay}}';
                        $orderTableNameCopy       = '{{%order_ebay_copy}}';
                        $orderDetailTableName     = '{{%order_ebay_detail}}';
                        $orderDetailTableNameCopy = '{{%order_ebay_detail_copy}}';
                        $transactionRecord        = '{{%order_ebay_transaction}}';
                        $transactionRecordCopy    = '{{%order_ebay_transaction_copy}}';
                        break;
                    case 'ALI':
                        $orderTableName           = '{{%order_aliexpress}}';
                        $orderTableNameCopy       = '{{%order_aliexpress_copy}}';
                        $orderDetailTableName     = '{{%order_aliexpress_detail}}';
                        $orderDetailTableNameCopy = '{{%order_aliexpress_detail_copy}}';
                        $transactionRecord        = '{{%order_aliexpress_transaction}}';
                        $transactionRecordCopy    = '{{%order_aliexpress_transaction_copy}}';
                        break;
                    case 'AMAZON':
                        $orderTableName           = '{{%order_amazon}}';
                        $orderTableNameCopy       = '{{%order_amazon_copy}}';
                        $orderDetailTableName     = '{{%order_amazon_detail}}';
                        $orderDetailTableNameCopy = '{{%order_amazon_detail_copy}}';
                        $transactionRecord        = '{{%order_amazon_transaction}}';
                        $transactionRecordCopy    = '{{%order_amazon_transaction_copy}}';
                        break;
                    case 'WISH':
                        $orderTableName           = '{{%order_wish}}';
                        $orderTableNameCopy       = '{{%order_wish_copy}}';
                        $orderDetailTableName     = '{{%order_wish_detail}}';
                        $orderDetailTableNameCopy = '{{%order_wish_detail_copy}}';
                        $transactionRecord        = '{{%order_wish_transaction}}';
                        $transactionRecordCopy    = '{{%order_wish_transaction_copy}}';
                        break;
                    default:
                        $orderTableName           = '{{%order_other}}';
                        $orderTableNameCopy       = '{{%order_other_copy}}';
                        $orderDetailTableName     = '{{%order_other_detail}}';
                        $orderDetailTableNameCopy = '{{%order_other_detail_copy}}';
                        $transactionRecord        = '{{%order_other_transaction}}';
                        $transactionRecordCopy    = '{{%order_other_transaction_copy}}';
                        break;
                }

                if ($orderTableName == '{{%order_other}}') {
                    $select = "distinct (t.order_id),"
                        . "t.platform_order_id,"
                        . "t.buyer_id,"
                        . "t.order_type,"
                        . "t3.warehouse_name,"
                        . "t.complete_status,"
                        . "t.order_status,"
                        . "t.ship_country_name,"
                        . "t.paytime,"
                        . "t.shipped_date,"
                        . "t.currency,"
                        . "t.total_price,"
                        . "t.amazon_fulfill_channel,"
                        . "t8.amazon_fulfill_channel as `parent_amazon_fulfill_channel`,"
                        . "t.order_number,"
                        . "group_concat(concat(`t1`.`sku`,('*'),`t1`.`quantity`,('*'),`t1`.`total_price`) separator ',') as `sku`"
                        . ",sum(`t1`.`quantity`) as `sum_quantity`,"
                        . "group_concat(`t6`.`title` separator ',') as `pro_name`,"
                        . "group_concat(`t5`.`linelist_cn_name` separator ',') as `line_cn_name`,"
                        . "t7.ship_name";
                } else {
                    $select = "t.order_id,"
                        . "t.platform_order_id,"
                        . "t.buyer_id,"
                        . "t.order_type,"
                        . "t3.warehouse_name,"
                        . "t.complete_status,"
                        . "t.order_status,"
                        . "t.ship_country_name,"
                        . "t.paytime,"
                        . "t.shipped_date,"
                        . "t.currency,"
                        . "t.total_price,"//订单总计
                        . "t.amazon_fulfill_channel,"
                        . "t8.amazon_fulfill_channel as parent_amazon_fulfill_channel,"
                        . "group_concat(concat(`t1`.`sku`,('*'),`t1`.`quantity`,('*'),`t1`.`total_price`) separator ',') as `sku`"
                        . ",sum(`t1`.`quantity`) as `sum_quantity`,"
                        . "group_concat(`t6`.`title` separator ',') as `pro_name`,"
                        . "group_concat(`t5`.`linelist_cn_name` separator ',') as `line_cn_name`,"
                        . "t7.ship_name";
                }
                //查询数据
                $query = (new Query())
                    ->select($select)
                    ->from($orderTableName . ' as `t`')
                    ->leftJoin($orderDetailTableName . ' as `t1`', '`t1`.`order_id` = `t`.`order_id`')
                    ->leftJoin("{{%warehouse}}.{{%warehouse}} as `t3`", "`t3`.`id` = `t`.`warehouse_id`")
                    ->leftJoin("{{%product}}.{{%product}} as `t4`", "`t4`.`sku` = `t1`.`sku`")
                    ->leftJoin("{{%product}}.{{%product_linelist}} as `t5`", "`t5`.`id` = `t4`.`product_linelist_id`")
                    ->leftJoin("{{%product}}.{{%product_description}} as `t6`", "`t6`.`sku` = `t1`.`sku` and `t6`.language_code = 'Chinese'")
                    ->leftJoin("{{%logistics}}.{{%logistics}} as `t7`", "`t7`.`ship_code` = `t`.`ship_code`")
                    ->leftJoin($orderTableName . ' as `t8`', '`t8`.`order_id` = `t`.`parent_order_id`')
                    ->where(['in', '`t`.`order_id`', $value])
                    ->groupBy('`t`.`order_id`')
                    ->createCommand(Yii::$app->db_order);

                $data       = $query->queryAll();
                $returnData = array_merge($returnData, $data);

                $noFindIds = [];
                if (!empty($data)) {
                    $tmp = array_column($data, 'platform_order_id', 'order_id');

                    if (!empty($value)) {
                        foreach ($value as $orderId) {
                            if (!array_key_exists($orderId, $tmp)) {
                                $noFindIds[] = $orderId;
                            }
                        }
                    }
                } else {
                    $noFindIds = $value;
                }

                //查询半年前数据
                if (!empty($noFindIds)) {
                    unset($query);
                    $query = (new Query())
                        ->select($select)
                        ->from($orderTableNameCopy . ' as `t`')
                        ->leftJoin($orderDetailTableNameCopy . ' as `t1`', '`t1`.`order_id` = `t`.`order_id`')
                        ->leftJoin("{{%warehouse}}.{{%warehouse}} as `t3`", "`t3`.`id` = `t`.`warehouse_id`")
                        ->leftJoin("{{%product}}.{{%product}} as `t4`", "`t4`.`sku` = `t1`.`sku`")
                        ->leftJoin("{{%product}}.{{%product_linelist}} as `t5`", "`t5`.`id` = `t4`.`product_linelist_id`")
                        ->leftJoin("{{%product}}.{{%product_description}} as `t6`", "`t6`.`sku` = `t1`.`sku` and `t6`.language_code = 'Chinese'")
                        ->leftJoin("{{%logistics}}.{{%logistics}} as `t7`", "`t7`.`ship_code` = `t`.`ship_code`")
                        ->leftJoin($orderTableNameCopy . ' as `t8`', '`t8`.`order_id` = `t`.`parent_order_id`')
                        ->where(['in', '`t`.`order_id`', $noFindIds])
                        ->groupBy('`t`.`order_id`')
                        ->createCommand(Yii::$app->db_order);

                    $noFindData = $query->queryAll();
                    $returnData = array_merge($returnData, $noFindData);
                }

                foreach ($returnData as &$returnmodel) {
                    $detailData = (new Query())
                        ->select("group_concat(`transaction_id` separator ',') as `rtransaction_id`,group_concat(`receive_type` separator ',') as `receive_type`")
                        ->from($transactionRecord)
                        ->where('order_id = "' . $returnmodel['order_id'] . '"')
                        ->groupBy('`order_id`')
                        ->createCommand(Yii::$app->db_order)
                        ->queryOne();
                    if (empty($detailData)) {
                        unset($detailData);
                        $detailData = (new Query())
                            ->select("group_concat(`transaction_id` separator ',') as `rtransaction_id`,group_concat(`receive_type` separator ',') as `receive_type`")
                            ->from($transactionRecordCopy)
                            ->where('order_id = "' . $returnmodel['order_id'] . '"')
                            ->groupBy('`order_id`')
                            ->createCommand(Yii::$app->db_order)
                            ->queryOne();
                    }
                    if ($detailData) {
                        $returnmodel['rtransaction_id'] = $detailData['rtransaction_id'];
                        $returnmodel['receive_type']    = $detailData['receive_type'];
                    } else {
                        $returnmodel['rtransaction_id'] = '';
                        $returnmodel['receive_type']    = '';
                    }
                }

            }
        }
        if ($returnData) {
            return $returnData;
        } else {
            return false;
        }
    }

}