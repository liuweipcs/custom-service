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
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayReturnsRequests;
use Yii;
use yii\db\Query;
use app\modules\accounts\models\Platform;

class OrderEbay extends OrderModel {

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

        return '{{%order_ebay}}';
    }
     /**
     * @author harvin
     * @desc 获取订单的交易号
     * @param $order_id
     * @return array
     */
     public static function getTransactionid($order_id){
          $query = self::find();
          $query->from(self::tableName() . ' t');
        $row= $query->join('LEFT JOIN', '{{%order_ebay_transaction}} t2', 't.order_id = t2.order_id')
          ->select('t2.transaction_id')->where(['t.order_id'=>$order_id])
          ->asArray()->one();
         return $row['transaction_id'];
     }
    /**
     * @author alpha
     * @desc 获取ebay订单同步状态
     * @param $is_upload
     * @return string
     */
    public static function getIsUpload($is_upload) {
        switch ($is_upload) {
            case 0:
                return "未同步";
                break;
            case 1:
                return "已同步";
                break;
            case 2:
                return "同步中";
                break;
            case 3:
                return "同步失败";
                break;
            case 4:
                return "忽略订单";
                break;
            case 5:
                return "跟踪号更新";
                break;
        }
    }

    //$platform_code, $buyer_id, $item_id, $package_id, $paypal_id, $account_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date,$end_date, $pageCur, $pageSize, $complete_status

    /**
     * ebay  订单查询
     * @param $platform_code
     * @param $buyer_id
     * @param $item_id
     * @param $package_id
     * @param $paypal_id
     * @param $account_id
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
     * @return array|null
     * @throws \yii\db\Exception
     */
    public static function getOrder_list($platform_code, $buyer_id, $item_id, $package_id, $paypal_id, $account_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur, $pageSize, $complete_status, $item_location, $remark, $warehouse_res = [], $created_state = null, $paytime_state = null, $shipped_state = null) {
        error_reporting(E_ALL & ~E_NOTICE);
        $query = self::find();
        $query_copy = self::find();
        $select = '`t`.`order_id`,`t`.`platform_code`,`t`.`platform_order_id`,`t`.`account_id`,`t`.`order_status`,`t`.`email`,`t`.`buyer_id`,
            `t`.`created_time`,`t`.`paytime`,`t`.`ship_name`,`t`.`ship_country`,`t`.`final_value_fee`,
            `t`.`subtotal_price`,`t`.`total_price`,`t`.`currency`,`t`.`payment_status`,`t`.`ship_status`,`t`.`refund_status`,`t`.`ship_code`,
            `t`.`complete_status`,`t`.`warehouse_id`,`t`.`parent_order_id`,`t`.`order_type`,`t`.`track_number`,`t`.`shipped_date`,`t`.`is_upload`';
        $query->from(self::tableName() . ' t');
        $query_copy->from('{{%order_ebay_copy}} t');
        //所属平台
        if (isset($platform_code) && !empty($platform_code)) {
            $query->andWhere(['t.platform_code' => $platform_code]);
            $query_copy->andWhere(['t.platform_code' => $platform_code]);
        }
        //订单备注
        if (isset($remark) && !empty($remark)) {
            $query->join('LEFT JOIN', '{{%order_remark}} t5', 't.order_id = t5.order_id');
            $query->andWhere(['t5.remark' => $remark]);

            $query_copy->join('LEFT JOIN', '{{%order_remark}} t5', 't.order_id = t5.order_id');
            $query_copy->andWhere(['t5.remark' => $remark]);
        }

        if (isset($item_location) && !empty($item_location)) {
            $select .= ",`t4`.`location`";
            $query->join('LEFT JOIN', '{{%order_ebay_detail}} t1', 't.order_id = t1.order_id');
            $query->join('LEFT JOIN', '{{%product}}.{{%ebay_online_listing}} t4', 't1.item_id = t4.itemid');
            $query->andWhere(['t4.location' => $item_location]);

            $query_copy->join('LEFT JOIN', '{{%order_ebay_detail_copy}} t1', 't.order_id = t1.order_id');
            $query_copy->join('LEFT JOIN', '{{%product}}.{{%ebay_online_listing}} t4', 't1.item_id = t4.itemid');
            $query_copy->andWhere(['t4.location' => $item_location]);
        }
        //卖家ID
        if (isset($buyer_id) && !empty($buyer_id)) {
            $cond = [];
            $cond[] = 'or';
            $cond[] = ['t.buyer_id' => $buyer_id];
            //$cond[] = ['like', 't.buyer_id', $buyer_id . '%', false];
            $cond[] = ['like', 't.order_id', $buyer_id . '%', false];
            $cond[] = ['in', 't.ship_name', $buyer_id];
            $cond[] = ['t.email' => $buyer_id];
            $cond[] = ['like', 't.platform_order_id', $buyer_id . '%', false];
            $cond[] = ['t.ship_phone' => $buyer_id];
            $cond[] = ['t.track_number' => $buyer_id];

            //查询订单的跟踪号，来获取订单ID
            $order_id_arr = OrderPackageKefu::find()
                    ->select('order_id')
                    ->andWhere(['platform_code' => $platform_code])
                    ->andWhere([
                        'or',
                        ['tracking_number_1' => $buyer_id],
                        ['tracking_number_2' => $buyer_id],
                    ])
                    ->column();
            if (!empty($order_id_arr)) {
                $order_id_arr = array_unique($order_id_arr);
                $cond[] = ['in', 't.order_id', $order_id_arr];
            }

            $query->andWhere($cond);
            $query_copy->andWhere($cond);
        }
        if (isset($account_id) && !empty($account_id)) {
            $query->andWhere(['in', 't.account_id', $account_id]);
            $query_copy->andWhere(['in', 't.account_id', $account_id]);
        }
        //订单状态
        if (isset($complete_status) && is_numeric($complete_status) === true) {
            $query->andWhere(['t.complete_status' => $complete_status]);
            $query_copy->andWhere(['t.complete_status' => $complete_status]);
        }

        //账号
        if ($account_ids && $account_ids !== 0) {
            $query->andWhere(['t.account_id' => $account_ids]);
            $query_copy->andWhere(['t.account_id' => $account_ids]);
        }
        //发货仓库
        if ($warehouse_id && $warehouse_id !== 0) {
            $query->andWhere(['t.warehouse_id' => $warehouse_id]);
            $query_copy->andWhere(['t.warehouse_id' => $warehouse_id]);
        } else {
            if (!empty($warehouse_res)) {
                $query->andWhere(['in', 't.warehouse_id', $warehouse_res]);
                $query_copy->andWhere(['in', 't.warehouse_id', $warehouse_res]);
            }
        }
        if ($get_date == 'order_time') {
            //created_time 下单时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.created_time', $begin_date, $end_date]);
                $query_copy->andWhere(['between', 't.created_time', $begin_date, $end_date]);
            } elseif (!empty($begin_date)) {
                $query->andWhere(['>=', 't.created_time', $begin_date]);
                $query_copy->andWhere(['>=', 't.created_time', $begin_date]);
            } elseif (!empty($end_date)) {
                $query->andWhere(['<=', 't.created_time', $end_date]);
                $query_copy->andWhere(['<=', 't.created_time', $end_date]);
            }
        } elseif ($get_date == 'shipped_date') {
            //发货时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.shipped_date', $begin_date, $end_date]);
                $query_copy->andWhere(['between', 't.shipped_date', $begin_date, $end_date]);
            } elseif (!empty($begin_date)) {
                $query->andWhere(['>=', 't.shipped_date', $begin_date]);
                $query_copy->andWhere(['>=', 't.shipped_date', $begin_date]);
            } elseif (!empty($end_date)) {
                $query->andWhere(['<=', 't.shipped_date', $end_date]);
                $query_copy->andWhere(['<=', 't.shipped_date', $end_date]);
            }
        } elseif ($get_date == 'paytime') {
            //付款时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.paytime', $begin_date, $end_date]);
                $query_copy->andWhere(['between', 't.paytime', $begin_date, $end_date]);
            } elseif (!empty($begin_date)) {
                $query->andWhere(['>=', 't.paytime', $begin_date]);
                $query_copy->andWhere(['>=', 't.paytime', $begin_date]);
            } elseif (!empty($end_date)) {
                $query->andWhere(['<=', 't.paytime', $end_date]);
                $query_copy->andWhere(['<=', 't.paytime', $end_date]);
            }
        }

        //出货方式
        if ($ship_code && $ship_code !== 0) {
            $query->andWhere(['t.ship_code' => $ship_code]);
            $query_copy->andWhere(['t.ship_code' => $ship_code]);
        }
        //目的国
        if ($ship_country && $ship_country !== 0) {
            $query->andWhere(['t.ship_country' => $ship_country]);
            $query_copy->andWhere(['t.ship_country' => $ship_country]);
        }
        //货币类型
        if ($currency && $currency !== 0) {
            $query->andWhere(['t.currency' => $currency]);
            $query_copy->andWhere(['t.currency' => $currency]);
        }

        //itemID
        if (isset($item_id) && !empty($item_id)) {
            $select .= ",`t1`.`item_id`";
            $query->join('LEFT JOIN', '{{%order_ebay_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.item_id' => $item_id]);

            $query_copy->join('LEFT JOIN', '{{%order_ebay_detail_copy}} t1', 't.order_id = t1.order_id');
            $query_copy->andWhere(['t1.item_id' => $item_id]);
        }
        //交易号
        if (isset($paypal_id) && !empty($paypal_id)) {
            $select .= ",`t2`.`transaction_id`";
            $query->join('LEFT JOIN', '{{%order_ebay_transaction}} t2', 't.order_id = t2.order_id');
            $query->andWhere(['t2.transaction_id' => $paypal_id]);

            $query_copy->join('LEFT JOIN', '{{%order_ebay_transaction_copy}} t2', 't.order_id = t2.order_id');
            $query_copy->andWhere(['t2.transaction_id' => $paypal_id]);
        }
        //包裹号
        if (isset($package_id) && !empty($package_id)) {
            $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
            $query->andWhere(['t3.package_id' => $package_id]);
            $query_copy->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
            $query_copy->andWhere(['t3.package_id' => $package_id]);
        }
        //sku
        if (isset($sku) && !empty($sku)) {
            $query->join('LEFT JOIN', '{{%order_ebay_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.sku' => $sku]);

            $query_copy->join('LEFT JOIN', '{{%order_ebay_detail_copy}} t1', 't.order_id = t1.order_id');
            $query_copy->andWhere(['t1.sku' => $sku]);
        }


//        $order_id_arr = (new Query())
//            ->select('order_id')
//            ->from('{{%mall_api_log}}')
//            ->where(['task_name' => 'ebaynotfind'])
//            ->createCommand(Yii::$app->db_system)
//            ->queryColumn();
//        if (!empty($order_id_arr)) {
//            $query->andWhere(['not in', 't.order_id', $order_id_arr]);
//            $query_copy->andWhere(['not in', 't.order_id', $order_id_arr]);
//        }


        $query->select([$select]);
        $query_copy->select([$select]);

        if ($platform_code == Platform::PLATFORM_CODE_EB) {
            $query->andWhere(['<>', 't.is_lock', 2]);
            $query_copy->andWhere(['<>', 't.is_lock', 2]);
        }


        $count = $query->count();
        $count_copy = $query_copy->count();
        $pageCur = (int) $pageCur ? (int) $pageCur : 1;
        $pageSize = $pageSize ? $pageSize : Yii::$app->params['defaultPageSize'];
        $offset = ($pageCur - 1) * $pageSize;
        if ($created_state == 1) {
            $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.created_time' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
        } elseif ($created_state == 2) {
            $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.created_time' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
        } elseif ($paytime_state == 1) {
            $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
        } elseif ($paytime_state == 2) {
            $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
        } elseif ($shipped_state == 1) {
            $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.shipped_date' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
        } elseif ($shipped_state == 2) {
            $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.shipped_date' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
        } else {
            $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
        }


        $warehouseList = Warehouse::getAllWarehouseList(true);
        $Logistics = Logistic::getLogisArrCodeName();
        $orderId_arr = [];
        $item_id_arr = [];
        if (!empty($data_list)) {
            foreach ($data_list as $v) {
                $orderId_arr[] = $v['order_id'];
                $item_id_arr[] = $v['item_id'];
            }
            $order_remark_arr = OrderRemarkKefu::getOrderRemarksByArray($orderId_arr);
            $order_eb_location_arr = EbayOnlineListing::getItemLocationArr($item_id_arr);
            //备注
            foreach ($data_list as &$v1) {
                foreach ($order_remark_arr as $value) {
                    if ($v1['order_id'] == $value['order_id']) {
                        $v1['remark'][] = $value['remark'];
                    }
                }
            }
            //item location
            foreach ($data_list as &$v2) {
                foreach ($order_eb_location_arr as $value) {
                    if ($v2['item_id'] == $value['itemid']) {
                        $v2['location'] = $value['location'];
                    }
                }
            }
            foreach ($data_list as $key => $data) {
                $data_list[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                $data_list[$key]['warehouse'] = isset($data['warehouse_id']) ? $warehouseList[$data['warehouse_id']] : null;  //发货仓库
                $data_list[$key]['logistics'] = isset($data['ship_code']) ? $Logistics[$data['ship_code']] : null; //发货方式
                $trade = isset($data['order_id']) ? Tansaction::getOrderTransactionEbayByOrderId($data['order_id'], $data['platform_code']) : null; //交易记录
                $data_list[$key]['trade'] = current($trade);
                $data_list[$key]['detail'] = isset($data['order_id']) ? OderEbayDetail::getOrderDetailByOrderId($data['order_id']) : null; //订单明细

                $son_order_id_arr = array();
                // 根据订单类型获取关联订单单号
                switch ($data['order_type']) {
                    // 合并后的订单、被拆分的订单查询子订单
                    case self::ORDER_TYPE_MERGE_MAIN:
                    case self::ORDER_TYPE_SPLIT_MAIN:
                        $son_order_ids = isset($data['order_id']) ? self::getOrderSon($data['order_id']) : null;
                        foreach ($son_order_ids as $son_order_id) {
                            $son_order_id_arr[] = $son_order_id['order_id'];
                        }
                        $data_list[$key]['son_order_id'] = $son_order_id_arr;
                        break;
                }
                $platform_order_id = OrderKefu::getPlatformOrderId($data['order_id']);
                if (!empty($platform_order_id)) {
                    $data_list[$key]['platform_order_ids'] = $platform_order_id;
                }
            }
            return [
                'count' => $count,
                'data_list' => $data_list,
            ];
        } else {
            if ($created_state == 1) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.created_time' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($created_state == 2) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.created_time' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($paytime_state == 1) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($paytime_state == 2) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
            } elseif($shipped_state==1) {
                  $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.shipped_date' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            }elseif($shipped_state==2){
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.shipped_date' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
            }else{
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            }
            if (!empty($data_list_copy)) {
                foreach ($data_list_copy as $key => $data) {
                    $data_list_copy[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                    $data_list_copy[$key]['warehouse'] = isset($data['warehouse_id']) ? $warehouseList[$data['warehouse_id']] : null;  //发货仓库
                    $data_list_copy[$key]['logistics'] = isset($data['ship_code']) ? $Logistics[$data['ship_code']] : null; //发货方式
                    $trade = isset($data['order_id']) ? Tansaction::getOrderTransactionEbayByOrderId($data['order_id'], $data['platform_code']) : null; //交易记录
                    $data_list_copy[$key]['trade'] = current($trade);
                    $data_list_copy[$key]['detail'] = isset($data['order_id']) ? OderEbayDetail::getOrderDetailByOrderId($data['order_id']) : null; //订单明细
                    $son_order_id_arr = array();
                    // 根据订单类型获取关联订单单号
                    switch ($data['order_type']) {
                        // 合并后的订单、被拆分的订单查询子订单
                        case self::ORDER_TYPE_MERGE_MAIN:
                        case self::ORDER_TYPE_SPLIT_MAIN:
                            $son_order_ids = isset($data['order_id']) ? self::getOrderSon($data['order_id']) : null;
                            foreach ($son_order_ids as $son_order_id) {
                                $son_order_id_arr[] = $son_order_id['order_id'];
                            }
                            $data_list[$key]['son_order_id'] = $son_order_id_arr;
                            break;
                    }
                    $platform_order_id = OrderKefu::getPlatformOrderId($data['order_id']);
                    if (!empty($platform_order_id)) {
                        $data_list_copy[$key]['platform_order_ids'] = $platform_order_id;
                    }
                }
                return [
                    'count' => $count_copy,
                    'data_list' => $data_list_copy,
                ];
            }
        }
        return null;
    }

    public static function getOrderSon($orderId) {
        $order_id = self::find()
                ->select('order_id')
                ->from(self::tableName())
                ->where(['parent_order_id' => $orderId])
                ->asArray()
                ->All();
        if (!$order_id) {
            $order_id1 = self::find()
                    ->select('order_id')
                    ->from('{{%order_ebay_copy}}')
                    ->where(['parent_order_id' => $orderId])
                    ->asArray()
                    ->All();
            return $order_id1;
        }
        return $order_id;
    }

    //获取ebay的订单类型
    public static function getOrderType($platform_order_id) {
        $order_type = self::find()
                ->select('order_type')
                ->from(self::tableName())
                ->where(['platform_order_id' => $platform_order_id])
                ->asArray()
                ->one();
        if (!$order_type) {
            $order_type1 = self::find()
                    ->select('order_type')
                    ->from('{{%order_ebay_copy}}')
                    ->where(['platform_order_id' => $platform_order_id])
                    ->asArray()
                    ->one();

            return $order_type1['order_type'];
        }
        return $order_type['order_type'];
    }

    //获取ebay的order_id
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
                    ->from('{{%order_ebay_copy}}')
                    ->where(['platform_order_id' => $platform_order_id])
                    ->asArray()
                    ->one();

            return $order_type1['order_id'];
        }
        return $order_type['order_id'];
    }

    //获取ebay的parent_order_id
    public static function getParentOrderId($platform_order_id) {
        $order_type = self::find()
                ->select('parent_order_id')
                ->from(self::tableName())
                ->where(['platform_order_id' => $platform_order_id])
                ->asArray()
                ->one();
        if (!$order_type) {
            $order_type1 = self::find()
                    ->select('parent_order_id')
                    ->from('{{%order_ebay_copy}}')
                    ->where(['platform_order_id' => $platform_order_id])
                    ->asArray()
                    ->one();

            return $order_type1['parent_order_id'];
        }
        return $order_type['parent_order_id'];
    }

    //获取ebay的account_id
    public static function getAccountId($platform_order_id) {
        $order_type = self::find()
                ->select('account_id')
                ->from(self::tableName())
                ->where(['platform_order_id' => $platform_order_id])
                ->asArray()
                ->one();
        if (!$order_type) {
            $order_type1 = self::find()
                    ->select('account_id')
                    ->from('{{%order_ebay_copy}}')
                    ->where(['platform_order_id' => $platform_order_id])
                    ->asArray()
                    ->one();

            return $order_type1['account_id'];
        }
        return $order_type['account_id'];
    }

    //获取ebay平台订单号
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
                    ->from('{{%order_ebay_copy}}')
                    ->where(['order_id' => $order_id])
                    ->asArray()
                    ->one();

            return $platform_order_id1['platform_order_id'];
        }
        return $platform_order_id['platform_order_id'];
    }

    /**
     * @author alpha
     * @desc ebya糾紛获取订单部分信息
     * @param $platform_order_id_arr
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getExtraInfo($platform_order_id_arr) {
        $query = self::find();
        $query->select(['`t`.`platform_order_id`,`t`.`paytime`,`t`.`warehouse_id`,`t`.`ship_code`,`t`.`ship_country`,`t`.`shipped_date`,`t2`.`location`']);
        $query->from(self::tableName() . ' t');
        $query->join('LEFT JOIN', '{{%order_ebay_detail}} t1', 't.order_id = t1.order_id');
        $query->join('LEFT JOIN', '{{%product}}.{{%ebay_online_listing}} t2', 't1.item_id = t2.itemid');
        $query->andWhere(['in', 't.platform_order_id', $platform_order_id_arr]);
        $result = $query->asArray()->all();
        if (!empty($result)) {
            return $result;
        } else {
            unset($query);
            $query = self::find();
            $query->select(['`t`.`platform_order_id`,`t`.`paytime`,`t`.`warehouse_id`,`t`.`ship_code`,`t`.`ship_country`,`t`.`shipped_date`,`t2`.`location`']);
            $query->from('{{%order_ebay_copy}} t');
            $query->join('LEFT JOIN', '{{%order_ebay_detail_copy}} t1', 't.order_id = t1.order_id');
            $query->join('LEFT JOIN', '{{%product}}.{{%ebay_online_listing}} t2', 't1.item_id = t2.itemid');
            $query->andWhere(['in', 't.platform_order_id', $platform_order_id_arr]);
            $result = $query->asArray()->all();
            return $result;
        }
    }

    /**
     * @author alpha
     * @desc 返回site
     * @param $platform_order_id
     * @return mixed
     */
    public static function getSiteByPlatformId($platform_order_id) {
        $site_arr = self::find()->select(['t1.site'])
                        ->from(self::tableName() . ' t')
                        ->join('LEFT JOIN', '{{%order_ebay_detail}} t1', 't.order_id = t1.order_id')
                        ->andWhere(['t.platform_order_id' => $platform_order_id])
                        ->asArray()->one();
        if (!empty($site_arr)) {
            return $site_arr['site'];
        } else {
            unset($query);
            $site_arr = self::find()->select(['t1.site'])
                            ->from('{{%order_ebay_copy}} t')
                            ->join('LEFT JOIN', '{{%order_ebay_detail_copy}} t1', 't.order_id = t1.order_id')
                            ->andWhere(['t.platform_order_id' => $platform_order_id])
                            ->asArray()->one();
            if (!empty($site_arr)) {
                return $site_arr['site'];
            }
        }
    }

    /**
     * 获取订单详情表item id
     * @param $order_id
     * @return mixed
     */
    public static function getItemidArr($order_id) {
        $model = OrderKefu::model('order_ebay_detail');
        $detail = $model->select('item_id')
                ->where(['order_id' => $order_id])
                ->asArray()
                ->all();
        if (empty($detail)) {
            $model = OrderKefu::model('order_ebay_detail_copy');
            $detail = $model->select('item_id')
                    ->where(['order_id' => $order_id])
                    ->asArray()
                    ->all();
        }
        return $detail;
    }

}
