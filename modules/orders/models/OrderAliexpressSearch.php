<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/19 0019
 * Time: 下午 3:56
 */

namespace app\modules\orders\models;

use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressEvaluateList;
use Yii;
use yii\db\Query;

class OrderAliexpressSearch extends OrderModel {

    const ORDER_TYPE_NORMAL = 1;        //普通订单
    const ORDER_TYPE_MERGE_MAIN = 2;        //合并后的订单
    const ORDER_TYPE_MERGE_RES = 3;        //被合并的订单
    const ORDER_TYPE_SPLIT_MAIN = 4;        //拆分的主订单
    const ORDER_TYPE_SPLIT_CHILD = 5;        //拆分后的子订单
    const ORDER_TYPE_REDIRECT_MAIN = 6;        //被重寄的订单
    const ORDER_TYPE_REDIRECT_ORDER = 7;        //重寄后的订单
    const ORDER_TYPE_REPAIR_ORDER = 8;        //客户补款的订单

    /**
     * 返回当前模型连接的数据库
     */

    public static function getDb() {
        return Yii::$app->db_order;
    }

    public static function tableName() {
        return '{{%order_aliexpress}}';
    }

    /**
     * @author alpha
     * @desc 速卖通订单查询
     * @param $platform_code
     * @param $buyer_id
     * @param $item_id
     * @param $package_id
     * @param $paypal_id
     * @param $sku
     * @param $account_ids
     * @param $warehouse_id
     * @param $ship_code
     * @param $ship_country
     * @param $currency
     * @param $get_date
     * @param $begin_date
     * @param $end_date
     * @param int $pageCur
     * @param int $pageSize
     * @param $complete_status
     * @param $order_status
     * @return array|null
     */
    public static function getOrder_list($platform_code, $buyer_id, $item_id, $package_id, $paypal_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur = 0, $pageSize = 0, $complete_status, $order_status, $start_money = null, $end_money = null, $warehouse_res=[]) {

        $query = self::find();
        $query->select(['`t`.`order_id`,`t`.`platform_code`,`t`.`platform_order_id`,`t`.`account_id`,`t`.`order_status`,`t`.`email`,`t`.`buyer_id`,
            `t`.`created_time`,`t`.`paytime`,`t`.`ship_name`,`t`.`ship_country`,`t`.`final_value_fee`,
            `t`.`subtotal_price`,`t`.`total_price`,`t`.`currency`,`t`.`payment_status`,`t`.`ship_status`,`t`.`refund_status`,`t`.`ship_code`,
            `t`.`complete_status`,`t`.`warehouse_id`,`t`.`parent_order_id`,`t`.`order_type`,`t`.`track_number`,`t`.`shipped_date`,`t`.`is_upload`,`t`.`buyer_user_id`']);
        $query->from(self::tableName() . ' t');

        //所属平台
        if (isset($platform_code) && !empty($platform_code)) {
            $query->andWhere(['t.platform_code' => $platform_code]);
        }
        //订单状态
        if (isset($complete_status) && is_numeric($complete_status) === true) {
            $query->andWhere(['t.complete_status' => $complete_status]);
        }
        //订单金额
        if (!empty($start_money) && !empty($end_money)) {
            $query->andWhere(['between', 't.total_price', $start_money, $end_money]);
        } elseif (!empty($start_money)) {
            $query->andWhere(['>=', 't.total_price', $start_money]);
        } elseif (!empty($end_money)) {
            $query->andWhere(['<=', 't.total_price', $end_money]);
        }
        //卖家ID
        if (isset($buyer_id) && !empty($buyer_id)) {
            $query->andWhere([
                'or',
                ['t.buyer_id' => $buyer_id],
                ['like', 't.order_id', $buyer_id . '%', false],
                ['t.ship_name' => $buyer_id],
                ['t.email' => $buyer_id],
                ['like', 't.platform_order_id', $buyer_id . '%', false],
                ['t.track_number' => $buyer_id],
            ]);
        }
        //itemID
        if (isset($item_id) && !empty($item_id)) {
            $query->join('LEFT JOIN', '{{%order_aliexpress_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.item_id' => $item_id]);
        }
        //交易号
        if (isset($paypal_id) && !empty($paypal_id)) {
            $query->join('LEFT JOIN', '{{%order_aliexpress_transaction}} t2', 't.order_id = t2.order_id');
            $query->andWhere(['t2.transaction_id' => $paypal_id]);
        }

        //包裹号
        if (isset($package_id) && !empty($package_id)) {
            $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
            $query->andWhere(['t3.package_id' => $package_id]);
        }

        //sku
        if (isset($sku) && !empty($sku)) {
            $query->join('LEFT JOIN', '{{%order_aliexpress_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.sku' => $sku]);
        }
        //账号
        if ($account_ids && $account_ids !== 0) {
            $query->andWhere(['t.account_id' => $account_ids]);
        }
        //店铺订单状态
        if (isset($order_status) && !empty($order_status)) {
            $query->andWhere(['t.order_status' => $order_status]);
        }
        //发货仓库
        if ($warehouse_id && $warehouse_id !== 0) {
            $query->andWhere(['t.warehouse_id' => $warehouse_id]);
        } else {
            if (!empty($warehouse_res)) {
                $query->andWhere(['in', 't.warehouse_id', $warehouse_res]);
            }
        }
        if ($get_date == 'order_time') {
            //created_time 下单时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.created_time', $begin_date, $end_date]);
            } elseif (!empty($begin_date)) {
                $query->andWhere(['>=', 't.created_time', $begin_date]);
            } elseif (!empty($end_date)) {
                $query->andWhere(['<=', 't.created_time', $end_date]);
            }
        } elseif ($get_date == 'shipped_date') {
            //发货时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.shipped_date', $begin_date, $end_date]);
            } elseif (!empty($begin_date)) {
                $query->andWhere(['>=', 't.shipped_date', $begin_date]);
            } elseif (!empty($end_date)) {
                $query->andWhere(['<=', 't.shipped_date', $end_date]);
            }
        } elseif ($get_date == 'paytime') {
            //付款时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.paytime', $begin_date, $end_date]);
            } elseif (!empty($begin_date)) {
                $query->andWhere(['>=', 't.paytime', $begin_date]);
            } elseif (!empty($end_date)) {
                $query->andWhere(['<=', 't.paytime', $end_date]);
            }
        }

        //出货方式
        if ($ship_code && $ship_code !== 0) {
            $query->andWhere(['t.ship_code' => $ship_code]);
        }
        //目的国
        if ($ship_country && $ship_country !== 0) {
            $query->andWhere(['t.ship_country' => $ship_country]);
        }
        $count = $query->count();
        $pageCur = $pageCur ? $pageCur : 1;
        $pageSize = $pageSize ? $pageSize : Yii::$app->params['defaultPageSize'];
        $offset = ($pageCur - 1) * $pageSize;
        $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();

        if (!empty($data_list)) {
            foreach ($data_list as $key => $data) {
                $data_list[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                $data_list[$key]['warehouse'] = isset($data['warehouse_id']) ? Warehouse::getSendWarehouse($data['warehouse_id']) : null;  //发货仓库
                $data_list[$key]['logistics'] = isset($data['ship_code']) ? Logistic::getSendGoodsWay($data['ship_code']) : null; //发货方式
                $data_list[$key]['order_status_text'] = OrderKefu::getOrderStatus($data['order_status']); //订单状态
            }
            return [
                'count' => $count,
                'data_list' => $data_list,
            ];
        } else {
            //取copy表数据
            unset($query);
            $query = self::find();
            $query->select(['`t`.`order_id`,`t`.`platform_code`,`t`.`platform_order_id`,`t`.`account_id`,`t`.`order_status`,`t`.`email`,`t`.`buyer_id`,
            `t`.`created_time`,`t`.`paytime`,`t`.`ship_name`,`t`.`ship_country`,`t`.`final_value_fee`,
            `t`.`subtotal_price`,`t`.`total_price`,`t`.`currency`,`t`.`payment_status`,`t`.`ship_status`,`t`.`refund_status`,`t`.`ship_code`,
            `t`.`complete_status`,`t`.`warehouse_id`,`t`.`parent_order_id`,`t`.`order_type`,`t`.`track_number`,`t`.`shipped_date`,`t`.`is_upload`,`t`.`buyer_user_id`']);

            $query->from('{{%order_aliexpress_copy}} t');
            //所属平台
            if (isset($platform_code) && !empty($platform_code)) {
                $query->andWhere(['t.platform_code' => $platform_code]);
            }
            //卖家ID
            if (isset($buyer_id) && !empty($buyer_id)) {
                $query->andWhere([
                    'or',
                    ['t.buyer_id' => $buyer_id],
                    ['like', 't.order_id', $buyer_id . '%', false],
                    ['t.ship_name' => $buyer_id],
                    ['t.email' => $buyer_id],
                    ['like', 't.platform_order_id', $buyer_id . '%', false],
                    ['t.track_number' => $buyer_id],
                ]);
            }

            //订单状态
            if (isset($complete_status) && is_numeric($complete_status) === true) {
                $query->andWhere(['t.complete_status' => $complete_status]);
            }


            //订单金额
            if (!empty($start_money) && !empty($end_money)) {
                $query->andWhere(['between', 't.total_price', $start_money, $end_money]);
            } elseif (!empty($start_money)) {
                $query->andWhere(['>=', 't.total_price', $start_money]);
            } elseif (!empty($end_money)) {
                $query->andWhere(['<=', 't.total_price', $end_money]);
            }


            //itemID
            if (isset($item_id) && !empty($item_id)) {
                $query->join('LEFT JOIN', '{{%order_aliexpress_detail_copy}} t1', 't.order_id = t1.order_id');
                $query->andWhere(['t1.item_id' => $item_id]);
            }

            //交易号
            if (isset($paypal_id) && !empty($paypal_id)) {
                $query->join('LEFT JOIN', '{{%order_aliexpress_transaction_copy}} t2', 't.order_id = t2.order_id');
                $query->andWhere(['t2.transaction_id' => $paypal_id]);
            }

            //包裹号
            if (isset($package_id) && !empty($package_id)) {
                $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
                $query->andWhere(['t3.package_id' => $package_id]);
            }

            //sku
            if (isset($sku) && !empty($sku)) {
                $query->join('LEFT JOIN', '{{%order_aliexpress_detail_copy}} t1', 't.order_id = t1.order_id');
                $query->andWhere(['t1.sku' => $sku]);
            }

            //账号
            if ($account_ids && $account_ids !== 0) {
                $query->andWhere(['t.account_id' => $account_ids]);
            }
            //店铺订单状态
            if ($order_status) {
                $query->andWhere(['t.order_status' => $order_status]);
            }

            if ($get_date == 'order_time') {
                //created_time 下单时间
                if ($begin_date && $end_date) {
                    $query->andWhere(['between', 't.created_time', $begin_date, $end_date]);
                } elseif (!empty($begin_date)) {
                    $query->andWhere(['>=', 't.created_time', $begin_date]);
                } elseif (!empty($end_date)) {
                    $query->andWhere(['<=', 't.created_time', $end_date]);
                }
            } elseif ($get_date == 'shipped_date') {
                //发货时间
                if ($begin_date && $end_date) {
                    $query->andWhere(['between', 't.shipped_date', $begin_date, $end_date]);
                } elseif (!empty($begin_date)) {
                    $query->andWhere(['>=', 't.shipped_date', $begin_date]);
                } elseif (!empty($end_date)) {
                    $query->andWhere(['<=', 't.shipped_date', $end_date]);
                }
            } elseif ($get_date == 'paytime') {
                //付款时间
                if ($begin_date && $end_date) {
                    $query->andWhere(['between', 't.paytime', $begin_date, $end_date]);
                } elseif (!empty($begin_date)) {
                    $query->andWhere(['>=', 't.paytime', $begin_date]);
                } elseif (!empty($end_date)) {
                    $query->andWhere(['<=', 't.paytime', $end_date]);
                }
            }
            //出货方式
            if ($ship_code && $ship_code !== 0) {
                $query->andWhere(['t.ship_code' => $ship_code]);
            }
            //目的国
            if ($ship_country && $ship_country !== 0) {
                $query->andWhere(['t.ship_country' => $ship_country]);
            }
            $count1 = $query->count();
            $data_list1 = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            if ($data_list1) {
                foreach ($data_list1 as $key => $data) {
                    $data_list1[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                    $data_list1[$key]['warehouse'] = isset($data['warehouse_id']) ? Warehouse::getSendWarehouse($data['warehouse_id']) : null;  //发货仓库
                    $data_list1[$key]['logistics'] = isset($data['ship_code']) ? Logistic::getSendGoodsWay($data['ship_code']) : null; //发货方式
                    $data_list[$key]['order_status_text'] = OrderKefu::getOrderStatus($data['order_status']); //订单状态
                }
                return [
                    'count' => $count1,
                    'data_list' => $data_list1,
                ];
            }
        }
        return null;
    }

    /**
     * 批量发送站内信
     * @param $condition_option
     * @param $condition_value
     * @param $platform_code
     * @param $get_date
     * @param $begin_date
     * @param $end_date
     * @param $order_status
     * @param $account_ids
     * @param $ship_code
     * @param $ship_country
     * @return array|null
     */
    public static function get_list($condition_option, $condition_value, $platform_code, $get_date, $begin_date, $end_date, $order_status, $account_ids, $ship_code, $ship_country) {
        switch ($condition_option) {
            case 'buyer_id':
                if (strstr($condition_value, '--')) {
                    $arr = explode('--', $condition_value);
                    if (!empty($arr)) {
                        $condition_value = $arr[1];
                    }
                    $buyer_id = $condition_value;
                } else {
                    $buyer_id = $condition_value;
                }
                break;
            case 'item_id':
                $item_id = $condition_value;
                break;
            case 'package_id':
                $package_id = $condition_value;
                break;
            case 'paypal_id':
                $paypal_id = $condition_value;
                break;
            case 'sku':
                $sku = $condition_value;
                break;
        }
        $query = self::find();
        $query->from(self::tableName() . ' t');
        $query->select('platform_order_id,account_id,buyer_user_id');
        //所属平台
        if (isset($platform_code) && !empty($platform_code)) {
            $query->andWhere(['t.platform_code' => $platform_code]);
        }
        //卖家ID
        if (isset($buyer_id) && !empty($buyer_id)) {
            $query->andWhere([
                'or',
                ['t.buyer_id' => $buyer_id],
                ['like', 't.order_id', $buyer_id . '%', false],
                ['t.ship_name' => $buyer_id],
                ['t.email' => $buyer_id],
                ['like', 't.platform_order_id', $buyer_id . '%', false],
                ['t.track_number' => $buyer_id],
            ]);
        }
        //itemID
        if (isset($item_id) && !empty($item_id)) {
            $query->join('LEFT JOIN', '{{%order_aliexpress_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.item_id' => $item_id]);
        }

        //交易号
        if (isset($paypal_id) && !empty($paypal_id)) {
            $query->join('LEFT JOIN', '{{%order_aliexpress_transaction}} t2', 't.order_id = t2.order_id');
            $query->andWhere(['t2.transaction_id' => $paypal_id]);
        }

        //包裹号
        if (isset($package_id) && !empty($package_id)) {
            $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
            $query->andWhere(['t3.package_id' => $package_id]);
        }
        //sku
        if (isset($sku) && !empty($sku)) {
            $query->join('LEFT JOIN', '{{%order_aliexpress_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.sku' => $sku]);
        }
        //账号
        if ($account_ids && $account_ids !== 0) {
            $query->andWhere(['t.account_id' => $account_ids]);
        }
        //店铺订单状态
        if ($order_status) {
            $query->andWhere(['t.order_status' => $order_status]);
        }

        if ($get_date == 'order_time') {
            //created_time 下单时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.created_time', $begin_date, $end_date]);
            } elseif (!empty($begin_date)) {
                $query->andWhere(['>=', 't.created_time', $begin_date]);
            } elseif (!empty($end_date)) {
                $query->andWhere(['<=', 't.created_time', $end_date]);
            }
        } elseif ($get_date == 'shipped_date') {
            //发货时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.shipped_date', $begin_date, $end_date]);
            } elseif (!empty($begin_date)) {
                $query->andWhere(['>=', 't.shipped_date', $begin_date]);
            } elseif (!empty($end_date)) {
                $query->andWhere(['<=', 't.shipped_date', $end_date]);
            }
        } elseif ($get_date == 'paytime') {
            //付款时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.paytime', $begin_date, $end_date]);
            } elseif (!empty($begin_date)) {
                $query->andWhere(['>=', 't.paytime', $begin_date]);
            } elseif (!empty($end_date)) {
                $query->andWhere(['<=', 't.paytime', $end_date]);
            }
        }


        //出货方式
        if ($ship_code && $ship_code !== 0) {
            $query->andWhere(['t.ship_code' => $ship_code]);
        }
        //目的国
        if ($ship_country && $ship_country !== 0) {
            $query->andWhere(['t.ship_country' => $ship_country]);
        }
        $data_list = $query->orderBy(['t.paytime' => SORT_DESC])->asArray()->all();
        $three_ids = '';
        foreach ($data_list as $v) {
            $three_ids .= ',' . $v['platform_order_id'] . "&" . $v['account_id'] . "&" . $v['buyer_user_id'];
        }

        if (!empty($three_ids)) {

            return [
                'three_ids' => $three_ids,
            ];
        } else {
            //取copy表数据
            unset($query);
            $query = self::find();
            $query->from('{{%order_aliexpress_copy}} t');
            $query->select('platform_order_id,account_id,buyer_user_id');
            //所属平台
            if (isset($platform_code) && !empty($platform_code)) {
                $query->andWhere(['t.platform_code' => $platform_code]);
            }
            //卖家ID
            if (isset($buyer_id) && !empty($buyer_id)) {
                $query->andWhere([
                    'or',
                    ['t.buyer_id' => $buyer_id],
                    ['like', 't.order_id', $buyer_id . '%', false],
                    ['t.ship_name' => $buyer_id],
                    ['t.email' => $buyer_id],
                    ['like', 't.platform_order_id', $buyer_id . '%', false],
                    ['t.track_number' => $buyer_id],
                ]);
            }
            //itemID
            if (isset($item_id) && !empty($item_id)) {
                $query->join('LEFT JOIN', '{{%order_aliexpress_detail_copy}} t1', 't.order_id = t1.order_id');
                $query->andWhere(['t1.item_id' => $item_id]);
            }

            //交易号
            if (isset($paypal_id) && !empty($paypal_id)) {
                $query->join('LEFT JOIN', '{{%order_aliexpress_transaction_copy}} t2', 't.order_id = t2.order_id');
                $query->andWhere(['t2.transaction_id' => $paypal_id]);
            }

            //包裹号
            if (isset($package_id) && !empty($package_id)) {
                $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
                $query->andWhere(['t3.package_id' => $package_id]);
            }

            //sku
            if (isset($sku) && !empty($sku)) {
                $query->join('LEFT JOIN', '{{%order_aliexpress_detail_copy}} t1', 't.order_id = t1.order_id');
                $query->andWhere(['t1.sku' => $sku]);
            }

            //账号
            if ($account_ids && $account_ids !== 0) {
                $query->andWhere(['t.account_id' => $account_ids]);
            }
            //店铺订单状态
            if ($order_status) {
                $query->andWhere(['t.order_status' => $order_status]);
            }

            if ($get_date == 'order_time') {
                //created_time 下单时间
                if ($begin_date && $end_date) {
                    $query->andWhere(['between', 't.created_time', $begin_date, $end_date]);
                } elseif (!empty($begin_date)) {
                    $query->andWhere(['>=', 't.created_time', $begin_date]);
                } elseif (!empty($end_date)) {
                    $query->andWhere(['<=', 't.created_time', $end_date]);
                }
            } elseif ($get_date == 'shipped_date') {
                //发货时间
                if ($begin_date && $end_date) {
                    $query->andWhere(['between', 't.shipped_date', $begin_date, $end_date]);
                } elseif (!empty($begin_date)) {
                    $query->andWhere(['>=', 't.shipped_date', $begin_date]);
                } elseif (!empty($end_date)) {
                    $query->andWhere(['<=', 't.shipped_date', $end_date]);
                }
            } elseif ($get_date == 'paytime') {
                //付款时间
                if ($begin_date && $end_date) {
                    $query->andWhere(['between', 't.paytime', $begin_date, $end_date]);
                } elseif (!empty($begin_date)) {
                    $query->andWhere(['>=', 't.paytime', $begin_date]);
                } elseif (!empty($end_date)) {
                    $query->andWhere(['<=', 't.paytime', $end_date]);
                }
            }
            //出货方式
            if ($ship_code && $ship_code !== 0) {
                $query->andWhere(['t.ship_code' => $ship_code]);
            }
            //目的国
            if ($ship_country && $ship_country !== 0) {
                $query->andWhere(['t.ship_country' => $ship_country]);
            }
            $data_list1 = $query->orderBy(['t.paytime' => SORT_DESC])->asArray()->all();
            $three_ids = '';
            foreach ($data_list1 as $v) {
                $three_ids .= ',' . $v['platform_order_id'] . "&" . $v['account_id'] . "&" . $v['buyer_user_id'];
            }

            if ($three_ids) {
                return [
                    'data_list' => $three_ids,
                ];
            }
        }
        return null;
    }

    //获取速卖通Order_id
    public static function getOrder_id($sku) {
        $order_id = self::find()
                ->select('order_id')
                ->from('{{%order_aliexpress_detail}}')
                ->where(['sku' => $sku])
                ->asArray()
                ->column();

        if (empty($order_id)) {
            $order_id = self::find()
                    ->select('order_id')
                    ->from('{{%order_aliexpress_detail_copy}}')
                    ->where(['sku' => $sku])
                    ->asArray()
                    ->column();
        }
        return $order_id;
    }

    //获取速卖通platform_order_id
    public static function getPlatformOrders($order_id) {
        $platform_order_id = self::find()
                ->select('platform_order_id')
                ->from('{{%order_aliexpress}}')
                ->where(['in', 'order_id', $order_id])
                ->asArray()
                ->column();

        if (empty($platform_order_id)) {
            $platform_order_id = self::find()
                    ->select('platform_order_id')
                    ->from('{{%order_aliexpress_copy}}')
                    ->where(['in', 'order_id', $order_id])
                    ->asArray()
                    ->column();
        }

        return $platform_order_id;
    }

    //获取速卖通平台订单号
    public static function getPlatform($order_id) {
        $platform_order_id = self::find()
                ->select('platform_order_id')
                ->from(self::tableName())
                ->where(['order_id' => $order_id])
                ->asArray()
                ->one();
        if (!$platform_order_id) {
            $platform_order_id1 = self::find()
                    ->select('platform_order_id')
                    ->from('{{%order_aliexpress_copy}}')
                    ->where(['order_id' => $order_id])
                    ->asArray()
                    ->one();

            return $platform_order_id1['platform_order_id'];
        }
        return $platform_order_id['platform_order_id'];
    }

    //获取速卖通买家platform_order_id
    public static function getPlatOrderId($buyer_id) {
        $platform_order_id = self::find()
                ->select('platform_order_id')
                ->from(self::tableName())
                ->where(['buyer_id' => $buyer_id])
                ->asArray()
                ->column();

        if (empty($platform_order_id)) {
            $platform_order_id = self::find()
                    ->select('platform_order_id')
                    ->from('{{%order_aliexpress_copy}}')
                    ->where(['buyer_id' => $buyer_id])
                    ->asArray()
                    ->column();
        }
        return $platform_order_id;
    }

    //获取速卖通的order_id
    public static function getOrderId($platform_order_id) {
        $order_type = self::find()
                ->select('order_id')
                ->from(self::tableName())
                ->where(['platform_order_id' => $platform_order_id])
                ->asArray()
                ->one();
        if (!$order_type) {
            $order_type1 = self::find()
                    ->select('order_id')
                    ->from('{{%order_aliexpress_copy}}')
                    ->where(['platform_order_id' => $platform_order_id])
                    ->asArray()
                    ->one();

            return $order_type1['order_id'];
        }
        return $order_type['order_id'];
    }

    //获取速卖通的buyer_id
    public static function getBuyerId($platform_order_id) {
        $buyer_type = self::find()
                ->select('buyer_id')
                ->from(self::tableName())
                ->where(['platform_order_id' => $platform_order_id])
                ->asArray()
                ->one();
        if (!$buyer_type) {
            $buyer_type1 = self::find()
                    ->select('buyer_id')
                    ->from('{{%order_aliexpress_copy}}')
                    ->where(['platform_order_id' => $platform_order_id])
                    ->asArray()
                    ->one();

            return $buyer_type1['buyer_id'];
        }
        return $buyer_type['buyer_id'];
    }

    /**
     * 获取速卖通order_id和buyer_id
     */
    public static function getOrderIdAndBuyerId($platform_order_ids) {
        if (empty($platform_order_ids) || !is_array($platform_order_ids)) {
            return false;
        }

        $data = self::find()
                ->select('order_id, buyer_id, platform_order_id')
                ->where(['in', 'platform_order_id', $platform_order_ids])
                ->asArray()
                ->all();

        if (empty($data)) {
            $data = self::find()
                    ->select('order_id, buyer_id, platform_order_id')
                    ->from('{{%order_aliexpress_copy}}')
                    ->where(['in', 'platform_order_id', $platform_order_ids])
                    ->asArray()
                    ->all();
        }

        if (!empty($data)) {
            $tmp = [];
            foreach ($data as $item) {
                $tmp[$item['platform_order_id']] = [
                    'order_id' => $item['order_id'],
                    'buyer_id' => $item['buyer_id'],
                ];
            }
            $data = $tmp;
        }

        return $data;
    }

    /**
     * 获取产品的SKU和名称
     * @param $orderIds
     * @param $productIds
     */
    public static function getProductSkuAndTitle($orderIds, $productIds) {
        $data = (new Query())
                ->select('o.platform_order_id,d.item_id,d.sku,d.title')
                ->from(['o' => '{{%order_aliexpress}}'])
                ->leftJoin(['d' => '{{%order_aliexpress_detail}}'], 'd.order_id = o.order_id')
                ->andWhere(['in', 'o.platform_order_id', $orderIds])
                ->andWhere(['in', 'd.item_id', $productIds])
                ->createCommand(Yii::$app->db_order)
                ->queryAll();

        if (empty($data)) {
            $data = (new Query())
                    ->select('o.platform_order_id,d.item_id,d.sku,d.title')
                    ->from(['o' => '{{%order_aliexpress}}'])
                    ->leftJoin(['d' => '{{%order_aliexpress_detail_copy}}'], 'd.order_id = o.order_id')
                    ->andWhere(['in', 'o.platform_order_id', $orderIds])
                    ->andWhere(['in', 'd.item_id', $productIds])
                    ->createCommand(Yii::$app->db_order)
                    ->queryAll();
        }

        if (!empty($data)) {
            $skus = array_column($data, 'sku');

            $products = (new Query())
                    ->select('p.sku,p.picking_name,d.title')
                    ->from(['p' => '{{%product}}'])
                    ->leftJoin(['d' => '{{%product_description}}'], 'd.sku = p.sku AND d.language_code = "Chinese"')
                    ->andWhere(['in', 'p.sku', $skus])
                    ->createCommand(Yii::$app->db_product)
                    ->queryAll();

            if (!empty($products)) {
                $tmp = [];
                foreach ($products as $product) {
                    $tmp[$product['sku']] = [
                        'title' => $product['title'],
                        'picking_name' => $product['picking_name'],
                    ];
                }
                $products = $tmp;
            }

            $tmp = [];
            foreach ($data as $item) {
                $tmp[$item['platform_order_id']] = [
                    'item_id' => $item['item_id'],
                    'sku' => $item['sku'],
                    'title' => $item['title'],
                    'picking_name' => array_key_exists($item['sku'], $products) ? (!empty($products[$item['sku']]['title']) ? $products[$item['sku']]['title'] : $products[$item['sku']]['picking_name']) : '无中文名称',
                ];
            }
            $data = $tmp;
        }

        return $data;
    }

}
