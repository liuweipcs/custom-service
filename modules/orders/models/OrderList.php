<?php

namespace app\modules\orders\models;

use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressEvaluateList;
use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonReviewData;
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayReturnsRequests;
use Yii;
use yii\data\Pagination;
use yii\db\Query;

class OrderList extends OrderModel {

    const ORDER_TYPE_NORMAL = 1;        //普通订单
    const ORDER_TYPE_MERGE_MAIN = 2;        //合并后的订单
    const ORDER_TYPE_MERGE_RES = 3;        //被合并的订单
    const ORDER_TYPE_SPLIT_MAIN = 4;        //拆分的主订单
    const ORDER_TYPE_SPLIT_CHILD = 5;        //拆分后的子订单
    const ORDER_TYPE_REDIRECT_MAIN = 6;        //被重寄的订单
    const ORDER_TYPE_REDIRECT_ORDER = 7;        //重寄后的订单
    const ORDER_TYPE_REPAIR_ORDER = 8;        //客户补款的订单

    public static $platform_code;
    public static $platform_code_copy;

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb() {
        return Yii::$app->db_order;
    }

    /**
     * 返回表名
     * @return string
     */
    public static function tableName() {
        if (self::$platform_code == 'EB') {
            if (self::$platform_code_copy == 'EB_COPY') {
                return '{{%order_ebay_copy}}';
            }
            return '{{%order_ebay}}';
        } elseif (self::$platform_code == 'ALI') {
            if (self::$platform_code_copy == 'ALI_COPY') {
                return '{{%order_aliexpress_copy}}';
            }
            return '{{%order_aliexpress}}';
        } elseif (self::$platform_code == 'AMAZON') {
            if (self::$platform_code_copy == 'AMAZON_COPY') {
                return '{{%order_amazon_copy}}';
            }
            return '{{%order_amazon}}';
        } elseif (self::$platform_code == 'WISH') {
            if (self::$platform_code_copy == 'WISH_COPY') {
                return '{{%order_wish_copy}}';
            }
            return '{{%order_wish}}';
        } elseif (self::$platform_code == 'OTHER') {
            if (self::$platform_code_copy == 'OTHER_COPY') {
                return '{{%order_other_copy}}';
            }
            return '{{%order_other}}';
        }
    }

    /**
     * @author alpha
     * @desc 连续空格合并一个空格
     * @param $string
     * @return null|string|string[]\
     */
    public static function merge_spaces($string) {
        return preg_replace("/\s(?=\s)/", "\\1", $string);
    }

    /**
     * 订单查询 订单导出  获取订单id
     * @param $condition_option
     * @param $condition_value
     * @param $platform_code
     * @param $account_id
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
     * @param $params
     * @param $order_status
     * @param $orders_arr
     * @param $order_type
     * @param $item_location
     * @return array|null
     * @throws \yii\db\Exception
     */
    public static function getOrder_list($condition_option, $condition_value, $platform_code, $account_id, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur = 0, $pageSize = 0, $complete_status, $params, $order_status, $orders_arr, $order_type, $item_location, $remark, $warehouse_res = [], $created_state = null, $paytime_state = null, $shipped_state = null) {

        //查询select
        $select = '';
        if (empty($platform_code)) {
            return null;
        }
        //条件处理
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
        $query_copy = self::find();
        if ($params == 'download_order') {
            $select = '`t`.`buyer_id`,`t`.`ship_name`,`t`.`platform_code`,`t`.`track_number`,
        `t`.`account_id`,`t`.`complete_status`,`t`.`warehouse_id`,`t`.`paytime`,`t`.`ship_code`,`t`.`ship_country`,
        `t`.`currency`,`t`.`email`,`t`.`total_price`,`t`.`order_type`,`t`.`created_time`,`t`.`shipped_date`,`t`.`ship_country_name`,`t`.`order_id`';
        } elseif ($params == 'order_list') {
            $select = '`t`.`order_id`,`t`.`platform_code`,`t`.`platform_order_id`,`t`.`account_id`,`t`.`order_status`,`t`.`email`,`t`.`buyer_id`,
            `t`.`created_time`,`t`.`paytime`,`t`.`ship_name`,`t`.`ship_country`,`t`.`final_value_fee`,
            `t`.`subtotal_price`,`t`.`total_price`,`t`.`currency`,`t`.`payment_status`,`t`.`ship_status`,`t`.`refund_status`,`t`.`ship_code`,
            `t`.`complete_status`,`t`.`warehouse_id`,`t`.`parent_order_id`,`t`.`order_type`,`t`.`track_number`,`t`.`shipped_date`,`t`.`is_upload`';
        } elseif ($params = 'batch_operate') {
            $query->select(['t.order_id']);
            $query_copy->select(['t.order_id']);
        }
        //平台code
        if ($platform_code == 'EB') {
            self::$platform_code = 'EB';
            $query->from(self::tableName() . ' t');
//            $order_id_arr             = (new Query())
//                ->select('order_id')
//                ->from('{{%mall_api_log}}')
//                ->where(['task_name' => 'ebaynotfind'])
//                ->createCommand(Yii::$app->db_system)
//                ->queryColumn();
            self::$platform_code_copy = 'EB_COPY';
            $query_copy->from(self::tableName() . ' t');
        } elseif ($platform_code == 'AMAZON') {
            self::$platform_code = 'AMAZON';
            $query->from(self::tableName() . ' t');
            self::$platform_code_copy = 'AMAZON_COPY';
            $query_copy->from(self::tableName() . ' t');
        } elseif ($platform_code == 'ALI') {
            self::$platform_code = 'ALI';
            $query->from(self::tableName() . ' t');
            self::$platform_code_copy = 'ALI_COPY';
            $query_copy->from(self::tableName() . ' t');
        } elseif ($platform_code == 'WISH') {
            self::$platform_code = 'WISH';
            $query->from(self::tableName() . ' t');
            self::$platform_code_copy = 'WISH_COPY';
            $query_copy->from(self::tableName() . ' t');
        } else {
            self::$platform_code = 'OTHER';
            $query->from(self::tableName() . ' t');
            self::$platform_code_copy = 'OTHER_COPY';
            $query_copy->from(self::tableName() . ' t');
        }
        //订单导出
        if ($orders_arr) {
            $query->where(['in', 't.order_id', $orders_arr]);
            $query_copy->where(['in', 't.order_id', $orders_arr]);
        } else {
            //所属平台
            if (isset($platform_code) && !empty($platform_code)) {
                $query->andWhere(['t.platform_code' => $platform_code]);
                $query_copy->andWhere(['t.platform_code' => $platform_code]);
            }
            if ($platform_code == "EB") {
                if (isset($item_location) && !empty($item_location)) {
                    $select .= ",`t4`.`location`";
                    $query->join('LEFT JOIN', '{{%order_ebay_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_ebay_detail_copy}} t1', 't.order_id = t1.order_id');
                    $query->join('LEFT JOIN', '{{%product}}.{{%ebay_online_listing}} t4', 't1.item_id = t4.itemid');
                    $query_copy->join('LEFT JOIN', '{{%product}}.{{%ebay_online_listing}} t4', 't1.item_id = t4.itemid');
                    $query->andWhere(['t4.location' => $item_location]);
                    $query_copy->andWhere(['t4.location' => $item_location]);
                }
            }
            //订单备注
            if (isset($remark) && !empty($remark)) {
                $query->join('LEFT JOIN', '{{%order_remark}} t5', 't.order_id = t5.order_id');
                $query_copy->join('LEFT JOIN', '{{%order_remark}} t5', 't.order_id = t5.order_id');
                $query->andWhere(['t5.remark' => $remark]);
                $query_copy->andWhere(['t5.remark' => $remark]);
            }

            //卖家ID
            if (isset($buyer_id) && !empty($buyer_id)) {
                //空格多个合并一个
                $buyer_id = self::merge_spaces($buyer_id);
                $buyer_id = rtrim($buyer_id, ' ');
                if (substr_count($buyer_id, ' ') >= 1) {
                    //多个
                    $buyer_id_arr = explode(' ', $buyer_id);

                    $cond = [];
                    $cond[] = 'or';
                    $cond[] = ['in', 't.buyer_id', $buyer_id_arr];
                    //$cond[] = ['like', 't.buyer_id', $buyer_id . '%', false];
                    $cond[] = ['in', 't.order_id', $buyer_id_arr];
                    $cond[] = ['t.ship_name' => $buyer_id];
                    $cond[] = ['in', 't.email', $buyer_id_arr];
                    $cond[] = ['in', 't.platform_order_id', $buyer_id_arr];
                    $cond[] = ['in', 't.ship_phone', $buyer_id_arr];
                    $cond[] = ['in', 't.track_number', $buyer_id_arr];

                    if (!empty($buyer_id_arr)) {
                        $order_id_arr = [];
                        foreach ($buyer_id_arr as $tracking_number) {
                            //查询订单的跟踪号，来获取订单ID
                            $order_package = OrderPackageKefu::find()
                                    ->select('order_id')
                                    ->andWhere(['platform_code' => $platform_code])
                                    ->andWhere([
                                        'or',
                                        ['tracking_number_1' => $tracking_number],
                                        ['tracking_number_2' => $tracking_number],
                                    ])
                                    ->column();
                            if (!empty($order_package)) {
                                $order_id_arr = array_merge($order_id_arr, $order_package);
                            }
                        }
                        if (!empty($order_id_arr)) {
                            $order_id_arr = array_unique($order_id_arr);
                            $cond[] = ['in', 't.order_id', $order_id_arr];
                        }
                    }

                    $query->andWhere($cond);
                    $query_copy->andWhere($cond);
                } else {

                    $cond = [];
                    $cond[] = 'or';
                    $cond[] = ['like', 't.buyer_id', $buyer_id . '%', false];
                    //$cond[] = ['t.buyer_id' => $buyer_id];
                    $cond[] = ['t.order_id' => $buyer_id];
                    $cond[] = ['t.ship_name' => $buyer_id];
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
            //速卖通店铺状态
            if ($platform_code == 'ALI') {
                //店铺订单状态
                if (isset($order_status) && !empty($order_status) && $order_status != 'All') {
                    $query->andWhere(['t.order_status' => $order_status]);
                    $query_copy->andWhere(['t.order_status' => $order_status]);
                }
            }
            unset($order_status);
            //账号
            if ($account_ids && $account_ids !== 0) {
                $query->andWhere(['t.account_id' => $account_ids]);
                $query_copy->andWhere(['t.account_id' => $account_ids]);
            }
            //发货仓库 及仓库类型
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
                    $query->andWhere([' >= ', 't.created_time', $begin_date]);
                    $query_copy->andWhere([' >= ', 't.created_time', $begin_date]);
                } elseif (!empty($end_date)) {
                    $query->andWhere([' <= ', 't.created_time', $end_date]);
                    $query_copy->andWhere([' <= ', 't.created_time', $end_date]);
                }
            } elseif ($get_date == 'shipped_date') {
                //发货时间
                if ($begin_date && $end_date) {
                    $query->andWhere(['between', 't.shipped_date', $begin_date, $end_date]);
                    $query_copy->andWhere(['between', 't.shipped_date', $begin_date, $end_date]);
                } elseif (!empty($begin_date)) {
                    $query->andWhere([' >= ', 't.shipped_date', $begin_date]);
                    $query_copy->andWhere([' >= ', 't.shipped_date', $begin_date]);
                } elseif (!empty($end_date)) {
                    $query->andWhere([' <= ', 't.shipped_date', $end_date]);
                    $query_copy->andWhere([' <= ', 't.shipped_date', $end_date]);
                }
            } elseif ($get_date == 'paytime') {
                //付款时间
                if ($begin_date && $end_date) {
                    $query->andWhere(['between', 't.paytime', $begin_date, $end_date]);
                    $query_copy->andWhere(['between', 't.paytime', $begin_date, $end_date]);
                } elseif (!empty($begin_date)) {
                    $query->andWhere([' >= ', 't . paytime', $begin_date]);
                    $query_copy->andWhere([' >= ', 't.paytime', $begin_date]);
                } elseif (!empty($end_date)) {
                    $query->andWhere([' <= ', 't.paytime', $end_date]);
                    $query_copy->andWhere([' <= ', 't.paytime', $end_date]);
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
                if ($platform_code == 'EB') {
                    $query->join('LEFT JOIN', '{{%order_ebay_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_ebay_detail_copy}} t1', 't.order_id = t1.order_id');
                } elseif ($platform_code == 'AMAZON') {
                    $query->join('LEFT JOIN', '{{%order_amazon_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_amazon_detail_copy}} t1', 't.order_id = t1.order_id');
                } elseif ($platform_code == 'ALI') {
                    $query->join('LEFT JOIN', '{{%order_aliexpress_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_aliexpress_detail_copy}} t1', 't.order_id = t1.order_id');
                } elseif ($platform_code == 'WISH') {
                    $query->join('LEFT JOIN', '{{%order_wish_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_wish_detail_copy}} t1', 't.order_id = t1.order_id');
                } elseif ($platform_code == '') {
                    $query->join('LEFT JOIN', '{{%order_other_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_other_detail_copy}} t1', 't.order_id = t1.order_id');
                }
                $select .= ",`t1`.`item_id`";
                $item_id = self::merge_spaces($item_id);
                $item_id = rtrim($item_id, ' ');
                if (substr_count($item_id, ' ') >= 1) {
                    $item_id_arr = explode(' ', $item_id);
                    $query->andWhere(['in', 't1.item_id', $item_id_arr]);
                    $query_copy->andWhere(['in', 't1.item_id', $item_id_arr]);
                } else {
                    $query->andWhere(['t1.item_id' => $item_id]);
                    $query_copy->andWhere(['t1.item_id' => $item_id]);
                }
            }
            //交易号
            if (isset($paypal_id) && !empty($paypal_id)) {
                $select .= ",`t2`.`transaction_id`";
                if ($platform_code == 'EB') {
                    $query->join('LEFT JOIN', '{{%order_ebay_transaction}} t2', 't.order_id = t2.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_ebay_transaction_copy}} t2', 't.order_id = t2.order_id');
                } elseif ($platform_code == 'AMAZON') {
                    $query->join('LEFT JOIN', '{{%order_amazon_transaction}} t2', 't.order_id = t2.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_amazon_transaction_copy}} t2', 't.order_id = t2.order_id');
                } elseif ($platform_code == 'ALI') {
                    $query->join('LEFT JOIN', '{{%order_aliexpress_transaction}} t2', 't.order_id = t2.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_aliexpress_transaction_copy}} t2', 't.order_id = t2.order_id');
                } elseif ($platform_code == 'WISH') {
                    $query->join('LEFT JOIN', '{{%order_wish_transaction}} t2', 't.order_id = t2.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_wish_transaction_copy}} t2', 't.order_id = t2.order_id');
                } elseif ($platform_code == 'OTHER') {
                    $query->join('LEFT JOIN', '{{%order_other_transaction}} t2', 't.order_id = t2.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_other_transaction_copy}} t2', 't.order_id = t2.order_id');
                }
                $query->andWhere(['t2.transaction_id' => $paypal_id]);
                $query_copy->andWhere(['t2.transaction_id' => $paypal_id]);
            }
            //包裹号
            if (isset($package_id) && !empty($package_id)) {
                $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
                $query_copy->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
                $query->andWhere(['t3.package_id' => $package_id]);
                $query_copy->andWhere(['t3.package_id' => $package_id]);
            }
            //sku
            if (isset($sku) && !empty($sku)) {
                if ($platform_code == 'EB') {
                    $query->join('LEFT JOIN', '{{%order_ebay_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_ebay_detail_copy}} t1', 't.order_id = t1.order_id');
                } elseif ($platform_code == 'AMAZON') {
                    $query->join('LEFT JOIN', '{{%order_amazon_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_amazon_detail_copy}} t1', 't.order_id = t1.order_id');
                } elseif ($platform_code == 'ALI') {
                    $query->join('LEFT JOIN', '{{%order_aliexpress_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_aliexpress_detail_copy}} t1', 't.order_id = t1.order_id');
                } elseif ($platform_code == 'WISH') {
                    $query->join('LEFT JOIN', '{{%order_wish_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_wish_detail_copy}} t1', 't.order_id = t1.order_id');
                } elseif ($platform_code == '') {
                    $query->join('LEFT JOIN', '{{%order_other_detail}} t1', 't.order_id = t1.order_id');
                    $query_copy->join('LEFT JOIN', '{{%order_other_detail_copy}} t1', 't.order_id = t1.order_id');
                }
                $query->andWhere(['t1.sku' => $sku]);
                $query_copy->andWhere(['t1.sku' => $sku]);
            }

//            if (!empty($order_id_arr)) {
//                $query->andWhere(['not in', 't.order_id', $order_id_arr]);
//                $query_copy->andWhere(['not in', 't.order_id', $order_id_arr]);
//            }
            if ($platform_code == 'EB') {
                $query->andWhere(['<>', 't.is_lock', 2]);
                $query_copy->andWhere(['<>', 't.is_lock', 2]);
            }
            //订单类型
            if (isset($order_type) && !empty($order_type)) {
                $query->andWhere(['t.order_type' => $order_type]);
                $query_copy->andWhere(['t.order_type' => $order_type]);
            }
        }

        $query->select([$select]);
        $query_copy->select([$select]);
        $warehouseTypeList = Warehouse::getWarehousetype();
        $warehouseList = Warehouse::getAllWarehouseList(true);
        $Logistics = Logistic::getLogisArrCodeName();
        if ($params == 'download_order') {
            //下载订单 组装订单id
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


            if ($created_state == 1) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.created_time' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($created_state == 2) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.created_time' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($paytime_state == 1) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($paytime_state == 2) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($shipped_state == 1) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.shipped_date' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($shipped_state == 2) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.shipped_date' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
            } else {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            }



            // $data_list = $query->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            // $data_list_copy = $query_copy->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            if (!empty($data_list)) {
                //添加订单备注
                $orderId_arr = [];
                $item_id_arr = [];
                foreach ($data_list as $v) {
                    $orderId_arr[] = $v['order_id'];
                    $item_id_arr[] = $v['item_id'];
                }
                $order_remark_arr = OrderRemarkKefu::getOrderRemarksByArray($orderId_arr);
                $order_eb_location_arr = EbayOnlineListing::getItemLocationArr($item_id_arr);
                foreach ($data_list as &$v1) {
                    foreach ($order_remark_arr as $value) {
                        if ($v1['order_id'] == $value['order_id']) {
                            $v1['remark'][] = $value['remark'];
                        }
                    }
                }

                if ($platform_code == "EB") {
                    foreach ($data_list as &$v2) {
                        foreach ($order_eb_location_arr as $value) {
                            if ($v2['item_id'] == $value['itemid']) {
                                $v2['location'] = $value['location'];
                            }
                        }
                    }
                }
                foreach ($data_list as $key => $data) {
                    /*                     * *************** 公用查询************************** */
                    $data_list[$key]['complete_status_text'] = strip_tags(Order::getOrderCompleteDiffStatus($data['complete_status'])); //订单状态
                    $data_list[$key]['order_type'] = VHelper::getOrderTypeText($data['order_type']);
                    $account_info = Account::getAccountNameAndShortName($data['account_id'], $data['platform_code']);
                    $data_list[$key]['account_name'] = $account_info['account_name'];
                    $data_list[$key]['account_short_name'] = $account_info['account_short_name'];
                    $data_list[$key]['warehouse'] = isset($data['warehouse_id']) && (int) $data['warehouse_id'] > 0 ? $warehouseList[$data['warehouse_id']] : null;  //发货仓库
                    $data_list[$key]['logistics'] = isset($data['ship_code']) && !empty($data['ship_code']) ? $Logistics[$data['ship_code']] : null; //发货方式
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
                    //获取订单利润详细
                    $profit = self::find()
                                    ->select('profit,profit_rate,refund_amount')
                                    ->from('{{%order_profit}}')->where(['order_id' => $data['order_id']])->asArray()->one();
                    if (!empty($profit)) {
                        $data_list[$key]['profit'] = $profit['profit'];
                        $data_list[$key]['profit_rate'] = $profit['profit_rate'];
                        $data_list[$key]['refund_amount'] = $profit['refund_amount'];
                    }
                    //站点
                    $site_code = Account::find()->select('site_code')->where(['old_account_id' => $account_id, 'platform_code' => $platform_code, 'status' => 1])->asArray()->one();
                    $data_list[$key]['site_code'] = $site_code['site_code'];
                    //售后单号
                    $afterSaleOrders = AfterSalesOrder::find()->where(['order_id' => $data['order_id'], 'platform_code' => $data['platform_code']])->all();
                    if (!empty($afterSaleOrders)) {
                        foreach ($afterSaleOrders as $afterSaleOrder) {
                            $data_list[$key]['after_sale_ids'] .= ';' . $afterSaleOrder['after_sale_id'];
                        }
                    } else {
                        $data_list[$key]['after_sale_ids'] = '无';
                    }
                    /*                     * *****************公用查询************************** */

                    if ($platform_code == 'EB') {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_ebay_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";

                        //站内信 ebayinbox
                        $isSetEbayInboxSubject = EbayInboxSubject::EbayInboxSubject($data['account_id'], $data['item_id'], $data['buyer_id']);
                        if (!empty($isSetEbayInboxSubject)) {
                            $data_list[$key]['inbox'] = $isSetEbayInboxSubject['id'];
                        } else {
                            $data_list[$key]['inbox'] = '无';
                        }
                        //纠纷
                        $cancel_cases = EbayCancellations::disputeLevel($data['platform_order_id']);
                        $inquiry_cases = EbayInquiry::disputeLevel($data['platform_order_id']);
                        $returns_cases = EbayReturnsRequests::disputeLevel($data['platform_order_id']);
                        if ($cancel_cases) {
                            foreach ($cancel_cases as $cancel_case) {
                                if (in_array($cancel_case[0], [1, 2, 3, 4])) {
                                    $data_list[$key]['dispute'] = '(' . 'cancel' . ')' . $cancel_case[2];
                                }
                            }
                        } elseif ($inquiry_cases) {
                            foreach ($inquiry_cases as $inquiry_case) {
                                if (in_array($inquiry_case, [1, 2, 3, 4])) {
                                    $data_list[$key]['dispute'] = '(' . 'inquiry' . ')' . $inquiry_case[2];
                                }
                            }
                        } elseif ($returns_cases) {
                            foreach ($returns_cases as $returns_case) {
                                if (in_array($returns_case, [1, 2, 3, 4])) {
                                    $data_list[$key]['dispute'] = '(' . 'return ' . ')' . $returns_case[2];
                                }
                            }
                        }
                        //评价
                        $comment_type = 6;
                        $feedbackinfo = EbayFeedback::getCommentByTransactionID($data['transaction_id'], $data['item_id']);
                        if (isset($feedbackinfo->comment_type) && $feedbackinfo->comment_type < $comment_type) {
                            $comment_type = $feedbackinfo->comment_type;
                        }
                        // 给当前订单数据添加评价等级
                        $data_list[$key]['comment_type'] = $comment_type;
                    } elseif ($platform_code == 'AMAZON') {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_amazon_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";

                        //站内信 amazon
                        $data_list[$key]['inbox'] = '无';
                        //feedback
                        $order_feedback = AmazonFeedBack::getFindOne($platform_order_id);
                        if (!empty($order_feedback)) {
                            $data_list[$key]['feedback'] = $order_feedback['rating'];
                        } else {
                            $data_list[$key]['feedback'] = '无';
                        }
                        //亚马逊review
                        $order_review = AmazonReviewData::find()
                                ->select('t.star')
                                ->from('{{%amazon_review_data}} t')
                                ->join('LEFT JOIN', '{{%amazon_review_message_data}} m', 't.customerId = m.custId')
                                ->where(['m . orderId' => $platform_order_id])
                                ->one();
                        if (!empty($order_review)) {
                            $data_list[$key]['review'] = $order_review['star'];
                        } else {
                            $data_list[$key]['review'] = 0;
                        }
                        $data_list[$key]['dispute'] = '无';
                        //评价
                        $data_list[$key]['comment_type'] = '无';
                    } elseif ($platform_code == 'ALI') {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_aliexpress_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";

                        //站内信 aliexpress
                        $data_list[$key]['inbox'] = '无';
                        //订单评价消息
                        $order_evaluate = AliexpressEvaluateList::getFindOne($platform_order_id); //平台订单id
                        if (!empty($order_evaluate)) {
                            $avalue['comment_type'] = $order_evaluate['buyer_evaluation'];
                        } else {
                            $avalue['comment_type'] = array();
                        }
                        //纠纷
                        $dispute_lists = AliexpressDisputeList::getOrderDisputesIssueStatus($platform_order_id);
                        if (!empty($dispute_list)) {

                            foreach ($dispute_lists as $dispute_list) {
                                $data_list[$key]['dispute'] .= ',' . $dispute_list['platform_dispute_id'] . '[' . $dispute_list['issue_status'] . ']';
                            }
                        } else {
                            $data_list[$key]['dispute'] = '无';
                        }
                    } elseif ($platform_code == 'WISH') {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_wish_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";
                        //站内信 wish
                        $data_list[$key]['inbox'] = '无';
                        //纠纷
                        $data_list[$key]['dispute'] = '无';
                        $data_list[$key]['comment_type'] = '无';
                    } else {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_other_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";
                        //站内信 other
                        $data_list[$key]['inbox'] = '无';
                        //订单评价消息
                        $data_list[$key]['comment_type'] = '无';
                        //纠纷
                        $data_list[$key]['dispute'] = '无';
                    }
                    $command = Yii::$app->db_order->createCommand($sql);
                    $datas = $command->queryAll();
                    $data_list[$key]['sku'] = $datas[0]['sku'];
                }
                return [
                    'data_list' => $data_list,
                ];
            } else {

                //添加订单备注
                $orderId_arr = [];
                $item_id_arr = [];
                foreach ($data_list_copy as $v) {
                    $orderId_arr[] = $v['order_id'];
                    $item_id_arr[] = $v['item_id'];
                }
                $order_remark_arr = OrderRemarkKefu::getOrderRemarksByArray($orderId_arr);
                $order_eb_location_arr = EbayOnlineListing::getItemLocationArr($item_id_arr);
                foreach ($data_list_copy as &$v1) {
                    foreach ($order_remark_arr as $value) {
                        if ($v1['order_id'] == $value['order_id']) {
                            $v1['remark'][] = $value['remark'];
                        }
                    }
                }

                if ($platform_code == "EB") {
                    foreach ($data_list_copy as &$v2) {
                        foreach ($order_eb_location_arr as $value) {
                            if ($v2['item_id'] == $value['itemid']) {
                                $v2['location'] = $value['location'];
                            }
                        }
                    }
                }
                foreach ($data_list_copy as $key => $data) {
                    /*                     * *************** 公用查询************************** */
                    $data_list[$key]['complete_status_text'] = strip_tags(Order::getOrderCompleteDiffStatus($data['complete_status'])); //订单状态
                    $data_list[$key]['order_type'] = VHelper::getOrderTypeText($data['order_type']);
                    $account_info = Account::getAccountNameAndShortName($data['account_id'], $data['platform_code']);
                    $data_list[$key]['account_name'] = $account_info['account_name'];
                    $data_list[$key]['account_short_name'] = $account_info['account_short_name'];
                    $data_list[$key]['warehouse'] = isset($data['warehouse_id']) ? $warehouseList[$data['warehouse_id']] : null;  //发货仓库
                    $data_list[$key]['logistics'] = isset($data['ship_code']) && !empty($data['ship_code']) ? $Logistics[$data['ship_code']] : null; //发货方式
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
                    //获取订单利润详细
                    $profit = self::find()
                                    ->select('profit,profit_rate,refund_amount')
                                    ->from('{{%order_profit}}')->where(['order_id' => $data['order_id']])->asArray()->one();
                    if (!empty($profit)) {
                        $data_list[$key]['profit'] = $profit['profit'];
                        $data_list[$key]['profit_rate'] = $profit['profit_rate'];
                        $data_list[$key]['refund_amount'] = $profit['refund_amount'];
                    }
                    //站点
                    $site_code = Account::find()->select('site_code')->where(['old_account_id' => $account_id, 'platform_code' => $platform_code, 'status' => 1])->asArray()->one();
                    $data_list[$key]['site_code'] = $site_code['site_code'];
                    //售后单号
                    $afterSaleOrders = AfterSalesOrder::find()->where(['order_id' => $data['order_id'], 'platform_code' => $data['platform_code']])->all();
                    if (!empty($afterSaleOrders)) {
                        foreach ($afterSaleOrders as $afterSaleOrder) {
                            $data_list[$key]['after_sale_ids'] .= ';' . $afterSaleOrder['after_sale_id'];
                        }
                    } else {
                        $data_list[$key]['after_sale_ids'] = '无';
                    }
                    /*                     * *****************公用查询************************** */

                    if ($platform_code == 'EB') {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_ebay_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";

                        //站内信 ebayinbox
                        $isSetEbayInboxSubject = EbayInboxSubject::EbayInboxSubject($data['account_id'], $data['item_id'], $data['buyer_id']);
                        if (!empty($isSetEbayInboxSubject)) {
                            $data_list[$key]['inbox'] = $isSetEbayInboxSubject['id'];
                        } else {
                            $data_list[$key]['inbox'] = '无';
                        }
                        //纠纷
                        $cancel_cases = EbayCancellations::disputeLevel($data['platform_order_id']);
                        $inquiry_cases = EbayInquiry::disputeLevel($data['platform_order_id']);
                        $returns_cases = EbayReturnsRequests::disputeLevel($data['platform_order_id']);
                        if ($cancel_cases) {
                            foreach ($cancel_cases as $cancel_case) {
                                if (in_array($cancel_case[0], [1, 2, 3, 4])) {
                                    $data_list[$key]['dispute'] = '(' . 'cancel' . ')' . $cancel_case[2];
                                }
                            }
                        } elseif ($inquiry_cases) {
                            foreach ($inquiry_cases as $inquiry_case) {
                                if (in_array($inquiry_case, [1, 2, 3, 4])) {
                                    $data_list[$key]['dispute'] = '(' . 'inquiry' . ')' . $inquiry_case[2];
                                }
                            }
                        } elseif ($returns_cases) {
                            foreach ($returns_cases as $returns_case) {
                                if (in_array($returns_case, [1, 2, 3, 4])) {
                                    $data_list[$key]['dispute'] = '(' . 'return ' . ')' . $returns_case[2];
                                }
                            }
                        }
                        //评价
                        $comment_type = 6;
                        $feedbackinfo = EbayFeedback::getCommentByTransactionID($data['transaction_id'], $data['item_id']);
                        if (isset($feedbackinfo->comment_type) && $feedbackinfo->comment_type < $comment_type) {
                            $comment_type = $feedbackinfo->comment_type;
                        }
                        // 给当前订单数据添加评价等级
                        $data_list[$key]['comment_type'] = $comment_type;
                    } elseif ($platform_code == 'AMAZON') {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_amazon_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";

                        //站内信 amazon
                        $data_list[$key]['inbox'] = '无';
                        //feedback
                        $order_feedback = AmazonFeedBack::getFindOne($platform_order_id);
                        if (!empty($order_feedback)) {
                            $data_list[$key]['feedback'] = $order_feedback['rating'];
                        } else {
                            $data_list[$key]['feedback'] = '无';
                        }
                        //亚马逊review
                        $order_review = AmazonReviewData::find()
                                ->select('t.star')
                                ->from('{{%amazon_review_data}} t')
                                ->join('LEFT JOIN', '{{%amazon_review_message_data}} m', 't.customerId = m.custId')
                                ->where(['m.orderId' => $platform_order_id])
                                ->one();
                        if (!empty($order_review)) {
                            $data_list[$key]['review'] = $order_review['star'];
                        } else {
                            $data_list[$key]['review'] = 0;
                        }
                        $data_list[$key]['dispute'] = '无';
                        //评价
                        $data_list[$key]['comment_type'] = '无';
                    } elseif ($platform_code == 'ALI') {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_aliexpress_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";

                        //站内信 aliexpress
                        $data_list[$key]['inbox'] = '无';
                        //订单评价消息
                        $order_evaluate = AliexpressEvaluateList::getFindOne($platform_order_id); //平台订单id
                        if (!empty($order_evaluate)) {
                            $avalue['comment_type'] = $order_evaluate['buyer_evaluation'];
                        } else {
                            $avalue['comment_type'] = array();
                        }
                        //纠纷
                        $dispute_lists = AliexpressDisputeList::getOrderDisputesIssueStatus($platform_order_id);
                        if (!empty($dispute_list)) {

                            foreach ($dispute_lists as $dispute_list) {
                                $data_list[$key]['dispute'] .= ',' . $dispute_list['platform_dispute_id'] . '[' . $dispute_list['issue_status'] . ']';
                            }
                        } else {
                            $data_list[$key]['dispute'] = '无';
                        }
                    } elseif ($platform_code == 'WISH') {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_wish_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";
                        //站内信 wish
                        $data_list[$key]['inbox'] = '无';
                        //纠纷
                        $data_list[$key]['dispute'] = '无';
                        $data_list[$key]['comment_type'] = '无';
                    } else {
                        $sql = "SELECT group_concat(concat(t.sku,' * ',t.quantity,' * ',p.title,' * ',t.item_id) separator ',') as sku 
                                                            FROM {{%order_other_detail}}  t
                                                            LEFT JOIN {{%product}}.{{%product_description}} p on t.sku = p.sku
                                                            WHERE t.order_id = '" . $data['order_id'] . "'
                                                            AND p.language_code='Chinese'";
                        //站内信 other
                        $data_list[$key]['inbox'] = '无';
                        //订单评价消息
                        $data_list[$key]['comment_type'] = '无';
                        //纠纷
                        $data_list[$key]['dispute'] = '无';
                    }
                    $command = Yii::$app->db_order->createCommand($sql);
                    $datas = $command->queryAll();
                    $data_list[$key]['sku'] = $datas[0]['sku'];
                }
                return [
                    'data_list' => $data_list_copy,
                ];
            }
        } elseif ($params == 'order_list') {

            $pageCur = $pageCur ? $pageCur : 1;
            $pageSize = $pageSize ? $pageSize : Yii::$app->params['defaultPageSize'];
            $offset = ($pageCur - 1) * $pageSize;
            //查询订单
            $count = $query->count();
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
            //   $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            $count_copy = $query_copy->count();
            if ($created_state == 1) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.created_time' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($created_state == 2) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.created_time' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($paytime_state == 1) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($paytime_state == 2) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($shipped_state == 1) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.shipped_date' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            } elseif ($shipped_state == 2) {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.shipped_date' => SORT_ASC])->groupBy('t.order_id')->asArray()->all();
            } else {
                $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            }
            //$data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            $orderId_arr = [];
            $item_id_arr = [];
            if (!empty($data_list)) {
                foreach ($data_list as $v) {
                    $orderId_arr[] = $v['order_id'];
                    $item_id_arr[] = $v['item_id'];
                }
                $order_remark_arr = OrderRemarkKefu::getOrderRemarksByArray($orderId_arr);
                $order_eb_location_arr = EbayOnlineListing::getItemLocationArr($item_id_arr);
                foreach ($data_list as &$v1) {
                    foreach ($order_remark_arr as $value) {
                        if ($v1['order_id'] == $value['order_id']) {
                            $v1['remark'][] = $value['remark'];
                        }
                    }
                }

                if ($platform_code == "EB") {
                    foreach ($data_list as &$v2) {
                        foreach ($order_eb_location_arr as $value) {
                            if ($v2['item_id'] == $value['itemid']) {
                                $v2['location'] = $value['location'];
                            }
                        }
                    }
                }

                foreach ($data_list as $key => $data) {
                    //获取订单利润详细
                    $profit = self::find()
                                    ->select('refund_amount')
                                    ->from('{{%order_profit}}')->where(['order_id' => $data['order_id']])->asArray()->one();
                    if (!empty($profit)) {
                        $data_list[$key]['refund_amount'] = $profit['refund_amount'];
                    }
                    $data_list[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                    $data_list[$key]['logistics'] = isset($data['ship_code']) && !empty($data['ship_code']) ? $Logistics[$data['ship_code']] : null; //发货方式
                    $data_list[$key]['warehouse'] = isset($data['warehouse_id']) && (int) $data['warehouse_id'] > 0 ? $warehouseList[$data['warehouse_id']] : null;  //发货仓库
                    $data_list[$key]['warehouse_type'] = isset($data['warehouse_id']) && (int) $data['warehouse_id'] > 0 ? $warehouseTypeList[$data['warehouse_id']] : null;  //发货仓库类型  1 国内备货仓 2 3 5
                    if ($platform_code == 'EB') {
                        $trade = isset($data['order_id']) ? Tansaction::getOrderTransactionEbayByOrderId($data['order_id'], $data['platform_code']) : null; //交易记录
                        $data_list[$key]['trade'] = isset($trade) ? current($trade) : null;
                        $data_list[$key]['detail'] = isset($data['order_id']) ? OderEbayDetail::getOrderDetailByOrderId($data['order_id']) : null; //订单明细
                    }
                    if ($platform_code == 'AMAZON') {
                        //获取站点site_code
                        $data_list[$key]['site_code'] = Account::findSiteCode($data['account_id'], $platform_code);
                    }

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

                    if ($platform_code == 'ALI') {
                        //获取站点site_code
                        $data_list[$key]['evaluate_id'] = AliexpressEvaluateList::getFindOne($platform_order_id)['id'];
                    }
                }
                return [
                    'count' => $count,
                    'data_list' => $data_list,
                ];
            } else {
                foreach ($data_list_copy as $v) {
                    $orderId_arr[] = $v['order_id'];
                    $item_id_arr[] = $v['item_id'];
                }
                $order_remark_arr = OrderRemarkKefu::getOrderRemarksByArray($orderId_arr);
                $order_eb_location_arr = EbayOnlineListing::getItemLocationArr($item_id_arr);
                foreach ($data_list_copy as &$v1) {
                    foreach ($order_remark_arr as $value) {
                        if ($v1['order_id'] == $value['order_id']) {
                            $v1['remark'][] = $value['remark'];
                        }
                    }
                }

                if ($platform_code == "EB") {
                    foreach ($data_list as &$v2) {
                        foreach ($order_eb_location_arr as $value) {
                            if ($v2['item_id'] == $value['itemid']) {
                                $v2['location'] = $value['location'];
                            }
                        }
                    }
                }
                foreach ($data_list_copy as $key => $data) {
                    //获取订单利润详细
                    $profit = self::find()
                                    ->select('refund_amount')
                                    ->from('{{%order_profit}}')->where(['order_id' => $data['order_id']])->asArray()->one();
                    if (!empty($profit)) {
                        $data_list[$key]['refund_amount'] = $profit['refund_amount'];
                    }
                    $data_list[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                    $data_list[$key]['warehouse'] = isset($data['warehouse_id']) && (int) $data['warehouse_id'] > 0 ? $warehouseList[$data['warehouse_id']] : null;  //发货仓库
                    $data_list[$key]['logistics'] = isset($data['ship_code']) && !empty($data['ship_code']) ? $Logistics[$data['ship_code']] : null; //发货方式
                    $data_list[$key]['warehouse_type'] = isset($data['warehouse_id']) && (int) $data['warehouse_id'] > 0 ? $warehouseTypeList[$data['warehouse_id']] : null;  //发货仓库类型  1 国内备货仓 2 3 5

                    if ($platform_code == 'EB') {
                        $trade = isset($data['order_id']) ? Tansaction::getOrderTransactionEbayByOrderId($data['order_id'], $data['platform_code']) : null; //交易记录
                        $data_list[$key]['trade'] = current($trade);
                        $data_list[$key]['detail'] = isset($data['order_id']) ? OderEbayDetail::getOrderDetailByOrderId($data['order_id']) : null; //订单明细
                    }
                    if ($platform_code == 'AMAZON') {
                        //获取站点site_code
                        $data_list[$key]['site_code'] = Account::findSiteCode($data['account_id'], $platform_code);
                    }

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
                    if ($platform_code == 'ALI') {
                        //获取站点site_code
                        $data_list[$key]['evaluate_id'] = AliexpressEvaluateList::getFindOne($platform_order_id)['id'];
                    }
                }
                return [
                    'count' => $count_copy,
                    'data_list' => $data_list_copy,
                ];
            }
        } elseif ($params = 'batch_operate') {
            //订单批量操作
            $data_list = $query->orderBy(['t.paytime' => SORT_DESC])->asArray()->all();
            $data_list_copy = $query_copy->orderBy(['t.paytime' => SORT_DESC])->asArray()->all();
            if (!empty($data_list)) {
                $order_ids = '';
                foreach ($data_list as $v) {
                    $order_ids .= ',' . $v['order_id'];
                }
                return [
                    'order_ids' => $order_ids,
                ];
            } else {
                $order_ids = '';
                foreach ($data_list_copy as $v) {
                    $order_ids .= ',' . $v['order_id'];
                }
                return [
                    'order_ids' => $order_ids,
                ];
            }
        }
        return null;
    }

    /**
     * 获取子订单
     * @param $orderId
     * @return array|\yii\db\ActiveRecord[]
     */
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

    /**
     * 获取订单信息
     * @param $orderId
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getOrderone($platformcode, $orderid) {
        self::$platform_code = $platformcode;
        $order = self::findOne(['order_id' => $orderid]);

        return $order;
    }

    /**
     * 跟进平台订单号获取订单信息
     * @param $orderId
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getPlatformOrderone($platformcode, $platform_order_id) {
        self::$platform_code = $platformcode;
        $order = self::findOne(['platform_order_id' => $platform_order_id]);

        return $order;
    }

}
