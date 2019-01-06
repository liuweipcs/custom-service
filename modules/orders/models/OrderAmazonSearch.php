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
use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonReviewData;
use Yii;
use app\modules\accounts\models\Platform;

class OrderAmazonSearch extends OrderModel {

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
        return '{{%order_amazon}}';
    }

    /**
     * 亚马逊订单查询
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
     * @param null $complete_status
     * @return array|null
     */
    public static function getOrder_list($platform_code, $buyer_id, $item_id, $package_id, $paypal_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur = 0, $pageSize = 0, $complete_status = null, $warehouse_res = []) {
        $query = self::find();
        $query->select(['`t`.`order_id`,`t`.`platform_code`,`t`.`platform_order_id`,`t`.`account_id`,`t`.`order_status`,`t`.`email`,`t`.`buyer_id`,
            `t`.`created_time`,`t`.`paytime`,`t`.`ship_name`,`t`.`ship_country`,`t`.`final_value_fee`,
            `t`.`subtotal_price`,`t`.`total_price`,`t`.`currency`,`t`.`payment_status`,`t`.`ship_status`,`t`.`refund_status`,`t`.`ship_code`,
            `t`.`complete_status`,`t`.`warehouse_id`,`t`.`parent_order_id`,`t`.`order_type`,`t`.`track_number`,`t`.`shipped_date`,`t`.`is_upload`']);
        $query->from(self::tableName() . ' t');

        //所属平台
        if (isset($platform_code) && !empty($platform_code)) {
            $query->andWhere(['t.platform_code' => $platform_code]);
        }
        //卖家ID
        if (isset($buyer_id) && !empty($buyer_id)) {
            $query->andWhere([
                'or',
                ['t.buyer_id' => $buyer_id],
//                ['like', 't.order_id', $buyer_id . '%', false],
                ['t.order_id' => $buyer_id],
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
        //itemID
        if (isset($item_id) && !empty($item_id)) {
            $query->join('LEFT JOIN', '{{%order_amazon_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.item_id' => $item_id]);
        }

        //交易号
        if (isset($paypal_id) && !empty($paypal_id)) {
            $query->join('LEFT JOIN', '{{%order_amazon_transaction}} t2', 't.order_id = t2.order_id');
            $query->andWhere(['t2.transaction_id' => $paypal_id]);
        }

        //包裹号
        if (isset($package_id) && !empty($package_id)) {
            $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
            $query->andWhere(['t3.package_id' => $package_id]);
        }

        //sku
        if (isset($sku) && !empty($sku)) {
            $query->join('LEFT JOIN', '{{%order_amazon_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.sku' => $sku]);
        }

        //账号
        if ($account_ids && $account_ids !== 0) {
            $query->andWhere(['t.account_id' => $account_ids]);
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
        //货币类型
        if ($currency && $currency !== 0) {
            $query->andWhere(['t.currency' => $currency]);
        }

        $count = $query->count();
        $pageCur = $pageCur ? $pageCur : 1;
        $pageSize = $pageSize ? $pageSize : Yii::$app->params['defaultPageSize'];
        $offset = ($pageCur - 1) * $pageSize;
        $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();


        if (!empty($data_list)) {
            foreach ($data_list as $key => &$data) {
                if ($platform_code == Platform::PLATFORM_CODE_AMAZON) {
                    //获取站点site_code
                    $data['site_code'] = Account::findSiteCode($data['account_id'], $platform_code);
                }
                $data_list[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                $data_list[$key]['warehouse'] = isset($data['warehouse_id']) ? Warehouse::getSendWarehouse($data['warehouse_id']) : null;  //发货仓库
                $data_list[$key]['logistics'] = isset($data['ship_code']) ? Logistic::getSendGoodsWay($data['ship_code']) : null; //发货方式
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
            `t`.`complete_status`,`t`.`warehouse_id`,`t`.`parent_order_id`,`t`.`order_type`,`t`.`track_number`,`t`.`shipped_date`,`t`.`is_upload`']);
            $query->from('{{%order_amazon_copy}} t');
            //所属平台
            if (isset($platform_code) && !empty($platform_code)) {
                $query->andWhere(['t.platform_code' => $platform_code]);
            }
            //卖家ID
            if (isset($buyer_id) && !empty($buyer_id)) {
                $query->andWhere([
                    'or',
                    ['t.buyer_id' => $buyer_id],
//                    ['like', 't.order_id', $buyer_id . '%', false],
                    ['t.order_id' => $buyer_id],
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

            //itemID
            if (isset($item_id) && !empty($item_id)) {
                $query->join('LEFT JOIN', '{{%order_amazon_detail_copy}} t1', 't.order_id = t1.order_id');
                $query->andWhere(['t1.item_id' => $item_id]);
            }

            //交易号
            if (isset($paypal_id) && !empty($paypal_id)) {
                $query->join('LEFT JOIN', '{{%order_amazon_transaction_copy}} t2', 't.order_id = t2.order_id');
                $query->andWhere(['t2.transaction_id' => $paypal_id]);
            }

            //包裹号
            if (isset($package_id) && !empty($package_id)) {
                $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
                $query->andWhere(['t3.package_id' => $package_id]);
            }

            //sku
            if (isset($sku) && !empty($sku)) {
                $query->join('LEFT JOIN', '{{%order_amazon_detail_copy}} t1', 't.order_id = t1.order_id');
                $query->andWhere(['t1.sku' => $sku]);
            }

            //账号
            if ($account_ids && $account_ids !== 0) {
                $query->andWhere(['t.account_id' => $account_ids]);
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
            //货币类型
            if ($currency && $currency !== 0) {
                $query->andWhere(['t.currency' => $currency]);
            }

            $count1 = $query->count();
            $data_list1 = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            if ($data_list1) {
                foreach ($data_list1 as $key => $data) {
                    if ($platform_code == Platform::PLATFORM_CODE_AMAZON) {
                        //获取站点site_code
                        $data['site_code'] = Account::findSiteCode($data['account_id'], $platform_code);
                    }
                    $data_list1[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                    $data_list1[$key]['warehouse'] = isset($data['warehouse_id']) ? Warehouse::getSendWarehouse($data['warehouse_id']) : null;  //发货仓库
                    $data_list1[$key]['logistics'] = isset($data['ship_code']) ? Logistic::getSendGoodsWay($data['ship_code']) : null; //发货方式
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
     * 获取amazon_fulfill_channel
     * @param $platform_order_id
     * @return mixed
     */
    public static function getAmazonfulfillChannel($platform_order_id) {
        $amazon_fulfill_channel = self::find()
                ->select('amazon_fulfill_channel')
                ->from(self::tableName())
                ->where(['platform_order_id' => $platform_order_id])
                ->asArray()
                ->one();
        if (empty($amazon_fulfill_channel)) {
            $amazon_fulfill_channel = self::find()
                    ->select('amazon_fulfill_channel')
                    ->from('{{%order_amazon_copy}}')
                    ->where(['platform_order_id' => $platform_order_id])
                    ->asArray()
                    ->one();
            return $amazon_fulfill_channel['amazon_fulfill_channel'];
        }
        return $amazon_fulfill_channel['amazon_fulfill_channel'];
    }

}
