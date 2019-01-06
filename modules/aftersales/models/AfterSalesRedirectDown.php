<?php

namespace app\modules\aftersales\models;

use Yii;
use yii\db\Query;

class AfterSalesRedirectDown extends AfterSalesModel
{

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    /**
     * 默认表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%order_ebay}}';
    }


    /**
     * 获取导出重寄订单需要的相关数据
     * @param $datas
     * @return array|bool
     * @throws \yii\db\Exception
     * @author allen <2018-1-10>
     */
    public static function getResendDataInfo($datas)
    {
        if (is_array($datas) && !empty($datas)) {
            $returnData = [];
            foreach ($datas as $key => $value) {
                $order_ids   = $value['order_id'];//原订单号
                $re_order_id = $value['re_order_id'];//重寄订单号

                switch ($key) {
                    case 'EB':
                        $orderTableName       = '{{%order_ebay}}';
                        $orderDetailTableName = '{{%order_ebay_detail}}';
                        break;
                    case 'ALI':
                        $orderTableName       = '{{%order_aliexpress}}';
                        $orderDetailTableName = '{{%order_aliexpress_detail}}';
                        break;
                    case 'AMAZON':
                        $orderTableName       = '{{%order_amazon}}';
                        $orderDetailTableName = '{{%order_amazon_detail}}';
                        break;
                    case 'WISH':
                        $orderTableName       = '{{%order_wish}}';
                        $orderDetailTableName = '{{%order_wish_detail}}';
                        break;
                    default:
                        $orderTableName       = '{{%order_other}}';
                        $orderDetailTableName = '{{%order_other_detail}}';
                        break;
                }

                //查询原订单数据
                $orderData = (new Query())
                    ->select("t.order_id,"
                        . "t.order_type,"
                        . "t.platform_order_id,"
                        . "t.complete_status as `order_status`,"
                        . "group_concat(concat(`t1`.`sku`,('*'),`t1`.`quantity`,('*'),`t1`.`total_price`) separator ',') as `sku`,"
                        . "group_concat(`t2`.`title` separator ',') as `pro_name`,"
                        . "sum(`t1`.`quantity`) as `sum_quantity`,"
                        . "t.shipped_date,"
                        . "t3.warehouse_name,"
                        . "t.total_price,"//订单总金额
                        . "t4.ship_name")
                    ->from($orderTableName . ' as `t`')
                    ->leftJoin($orderDetailTableName . ' as `t1`', '`t1`.`order_id` = `t`.`order_id`')
                    ->leftJoin("{{%product}}.{{%product_description}} as `t2`", "`t2`.`sku` = `t1`.`sku` and `t2`.language_code = 'Chinese'")
                    ->leftJoin("{{%warehouse}}.{{%warehouse}} as `t3`", "`t3`.`id` = `t`.`warehouse_id`")
                    ->leftJoin("{{%logistics}}.{{%logistics}} as `t4`", "`t4`.`ship_code` = `t`.`ship_code`")
                    ->where(['in', '`t`.`order_id`', $order_ids])
                    ->groupBy('`t`.`order_id`')
                    ->createCommand(Yii::$app->db_order)
                    ->queryAll();

                //查询重发订单数据
                $reOrderData = (new Query())
                    ->select("t.order_id,"
                        . "t.complete_status as `reOrderStatus`,"
                        . "t2.warehouse_name as `reWarehouse`,"
                        . "t3.ship_name as `reShipName`,"
                        . "t.shipped_date as `reShippedDate`")
                    ->from($orderTableName . ' as `t`')
                    ->leftJoin("{{%warehouse}}.{{%warehouse}} as `t2`", "`t2`.`id` = `t`.`warehouse_id`")
                    ->leftJoin("{{%logistics}}.{{%logistics}} as `t3`", "`t3`.`ship_code` = `t`.`ship_code`")
                    ->where(['in', '`t`.`order_id`', $re_order_id])
                    ->groupBy('`t`.`order_id`')
                    ->createCommand(Yii::$app->db_order)
                    ->queryAll();
                //整合数据将重发订单的信息整合到原订单上
                if (!empty($orderData) && !empty($reOrderData)) {
                    foreach ($orderData as $key => $value) {
                        foreach ($reOrderData as $val) {
                            $reOderId = substr($val['order_id'], 0, strlen($val['order_id']) - 5);
                            if ($reOderId == $value['order_id']) {
                                $orderData[$key]['reOrderStatus'] = $val['reOrderStatus'];
                                $orderData[$key]['reWarehouse']   = $val['reWarehouse'];
                                $orderData[$key]['reShipName']    = $val['reShipName'];
                                $orderData[$key]['reShippedDate'] = $val['reShippedDate'];
                            }
                        }
                    }
                    $returnData = array_merge($returnData, $orderData);
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