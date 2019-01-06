<?php

namespace app\modules\orders\models;

use app\modules\accounts\models\Account;
use app\modules\mails\models\ShopeeAttachment;
use app\modules\mails\models\ShopeeCancellationList;
use app\modules\mails\models\ShopeeDisputeList;
use app\modules\mails\models\ShopeeOrderLogistics;
use app\modules\services\modules\shopee\components\ShopeeApi;
use Yii;
use yii\db\Query;

class OrderOtherSearch extends OrderModel
{
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
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    public static function tableName()
    {
        return '{{%order_other}}';
    }

    //订单查询
    public static function getOrder_list($platform_code, $buyer_id, $order_number, $item_id, $package_id, $paypal_id, $sku, $account_ids, $warehouse_id, $ship_code, $ship_country, $currency, $get_date, $begin_date, $end_date, $pageCur = 0, $pageSize = 0, $complete_status = null,$warehouse_res=[])
    {
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
                ['like', 't.order_id', $buyer_id . '%', false],
                ['t.ship_name' => $buyer_id],
                ['t.email' => $buyer_id],
                ['like', 't.platform_order_id', $buyer_id . '%', false],
                ['t.track_number' => $buyer_id],
            ]);
        }
        if (isset($order_number) && !empty($order_number)) {
            $query->andWhere(['t.order_number' => $order_number]);
        }

        //订单状态
        if (isset($complete_status) && is_numeric($complete_status) === true) {
            $query->andWhere(['t.complete_status' => $complete_status]);
        }

        //itemID
        if (isset($item_id) && !empty($item_id)) {
            $query->join('LEFT JOIN', '{{%order_other_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.item_id' => $item_id]);
        }

        //交易号
        if (isset($paypal_id) && !empty($paypal_id)) {
            $query->join('LEFT JOIN', '{{%order_other_transaction}} t2', 't.order_id = t2.order_id');
            $query->andWhere(['t2.transaction_id' => $paypal_id]);
        }

        //包裹号
        if (isset($package_id) && !empty($package_id)) {
            $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
            $query->andWhere(['t3.package_id' => $package_id]);
        }

        //sku
        if (isset($sku) && !empty($sku)) {
            $query->join('LEFT JOIN', '{{%order_other_detail}} t1', 't.order_id = t1.order_id');
            $query->andWhere(['t1.sku' => $sku]);
        }

        //账号
        if ($account_ids && $account_ids !== 0) {
            $query->andWhere(['t.account_id' => $account_ids]);
        }
        //发货仓库
        if ($warehouse_id && $warehouse_id !== 0) {
            $query->andWhere(['t.warehouse_id' => $warehouse_id]);
        }else{
            if(!empty($warehouse_res)){
                $query->andWhere(['in','t.warehouse_id',$warehouse_res]);
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

        $count     = $query->count();
        $pageCur   = $pageCur ? $pageCur : 1;
        $pageSize  = $pageSize ? $pageSize : Yii::$app->params['defaultPageSize'];
        $offset    = ($pageCur - 1) * $pageSize;
        $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
        if (!empty($data_list)) {
            foreach ($data_list as $key => $data) {
                $data_list[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                $data_list[$key]['warehouse']            = isset($data['warehouse_id']) ? Warehouse::getSendWarehouse($data['warehouse_id']) : null;  //发货仓库
                $data_list[$key]['logistics']            = isset($data['ship_code']) ? Logistic::getSendGoodsWay($data['ship_code']) : null; //发货方式

            }
            return [
                'count'     => $count,
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
            $query->from('{{%order_other_copy}} t');
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
            if (isset($order_number) && !empty($order_number)) {
                $query->andWhere(['t.order_number' => $order_number]);
            }

            //订单状态
            if (isset($complete_status) && is_numeric($complete_status) === true) {
                $query->andWhere(['t.complete_status' => $complete_status]);
            }

            //itemID
            if (isset($item_id) && !empty($item_id)) {
                $query->join('LEFT JOIN', '{{%order_other_detail_copy}} t1', 't.order_id = t1.order_id');
                $query->andWhere(['t1.item_id' => $item_id]);
            }

            //交易号
            if (isset($paypal_id) && !empty($paypal_id)) {
                $query->join('LEFT JOIN', '{{%order_other_transaction_copy}} t2', 't.order_id = t2.order_id');
                $query->andWhere(['t2.transaction_id' => $paypal_id]);
            }

            //包裹号
            if (isset($package_id) && !empty($package_id)) {
                $query->join('LEFT JOIN', '{{%order_package}} t3', 't.order_id = t3.order_id');
                $query->andWhere(['t3.package_id' => $package_id]);
            }

            //sku
            if (isset($sku) && !empty($sku)) {
                $query->join('LEFT JOIN', '{{%order_other_detail_copy}} t1', 't.order_id = t1.order_id');
                $query->andWhere(['t1.sku' => $sku]);
            }

            //账号
            if ($account_ids && $account_ids !== 0) {
                $query->andWhere(['t.account_id' => $account_ids]);
            }
            //发货仓库
            if ($warehouse_id && $warehouse_id !== 0) {
                $query->andWhere(['t.warehouse_id' => $warehouse_id]);
            }else{
            if(!empty($warehouse_res)){
                $query->andWhere(['in','t.warehouse_id',$warehouse_res]);
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

            $count1     = $query->count();
            $data_list1 = $query->offset($offset)->limit($pageSize)->orderBy(['t.paytime' => SORT_DESC])->groupBy('t.order_id')->asArray()->all();
            if ($data_list1) {
                foreach ($data_list1 as $key => $data) {
                    $data_list1[$key]['complete_status_text'] = Order::getOrderCompleteDiffStatus($data['complete_status']); //订单状态
                    $data_list1[$key]['warehouse']            = isset($data['warehouse_id']) ? Warehouse::getSendWarehouse($data['warehouse_id']) : null;  //发货仓库
                    $data_list1[$key]['logistics']            = isset($data['ship_code']) ? Logistic::getSendGoodsWay($data['ship_code']) : null; //发货方式
                }
                return [
                    'count'     => $count1,
                    'data_list' => $data_list1,
                ];
            }
        }
        return null;
    }

    //获取shopee的order_id
    public static function getOrderId($platform_order_id)
    {
        $order_id = self::find()
            ->select('order_id')
            ->from(self::tableName())
            ->where(['platform_order_id' => $platform_order_id])
            ->asArray()
            ->one();
        if (!$order_id) {
            $order_id1 = self::find()
                ->select('order_id')
                ->from('{{%order_other_copy}}')
                ->where(['platform_order_id' => $platform_order_id])
                ->asArray()
                ->one();

            return $order_id1['order_id'];
        }
        return $order_id['order_id'];
    }

    //获取速卖通的buyer_id
    public static function getBuyerId($platform_order_id)
    {
        $buyer_type = self::find()
            ->select('buyer_id')
            ->from(self::tableName())
            ->where(['platform_order_id' => $platform_order_id])
            ->asArray()
            ->one();
        if (!$buyer_type) {
            $buyer_type1 = self::find()
                ->select('buyer_id')
                ->from('{{%order_other_copy}}')
                ->where(['platform_order_id' => $platform_order_id])
                ->asArray()
                ->one();

            return $buyer_type1['buyer_id'];
        }
        return $buyer_type['buyer_id'];
    }

    //order_type
    public static function getOrderType($platform_order_id)
    {
        $order_type = self::find()
            ->select('order_type')
            ->from(self::tableName())
            ->where(['platform_order_id' => $platform_order_id])
            ->asArray()
            ->one();
        if (!$order_type) {
            $order_type1 = self::find()
                ->select('order_type')
                ->from('{{%order_other_copy}}')
                ->where(['platform_order_id' => $platform_order_id])
                ->asArray()
                ->one();

            return $order_type1['order_type'];
        }
        return $order_type['order_type'];
    }


    /**
     * 获取shopee order_id和buyer_id
     */
    public static function getOrderIdAndBuyerId($platform_order_ids)
    {
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
                ->from('{{%order_other_copy}}')
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
    public static function getProductSkuAndTitle($orderIds, $productIds)
    {
        $data = (new Query())
            ->select('o.platform_order_id,d.item_id,d.sku,d.title')
            ->from(['o' => '{{%order_other}}'])
            ->leftJoin(['d' => '{{%order_other_detail}}'], 'd.order_id = o.order_id')
            ->andWhere(['in', 'o.platform_order_id', $orderIds])
            ->andWhere(['in', 'd.item_id', $productIds])
            ->createCommand(Yii::$app->db_order)
            ->queryAll();

        if (empty($data)) {
            $data = (new Query())
                ->select('o.platform_order_id,d.item_id,d.sku,d.title')
                ->from(['o' => '{{%order_other}}'])
                ->leftJoin(['d' => '{{%order_other}}'], 'd.order_id = o.order_id')
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
                        'title'        => $product['title'],
                        'picking_name' => $product['picking_name'],
                    ];
                }
                $products = $tmp;
            }

            $tmp = [];
            foreach ($data as $item) {
                $tmp[$item['platform_order_id']] = [
                    'item_id'      => $item['item_id'],
                    'sku'          => $item['sku'],
                    'title'        => $item['title'],
                    'picking_name' => array_key_exists($item['sku'], $products) ? (!empty($products[$item['sku']]['title']) ? $products[$item['sku']]['title'] : $products[$item['sku']]['picking_name']) : '无中文名称',
                ];
            }
            $data = $tmp;
        }
        return $data;
    }


    /**
     * @author alpha
     * @desc shopee 交易  同意
     * @param $ordersn
     * @param $account_id
     * @return bool
     */
    public static function AcceptBuyerCancellation($ordersn, $account_id)
    {
        //获取账号信息
        $accountInfo = Account::findById($account_id);
        if (empty($accountInfo)) {
            return false;
        }
        $api  = new ShopeeApi(intval($accountInfo->shop_id), intval($accountInfo->partner_id), $accountInfo->secret_key);
        $data = $api->AcceptBuyerCancellation($ordersn);
        //更新
        $shopeeCancellationList = ShopeeCancellationList::findOne(['ordersn' => $ordersn]);
        if (!empty($data['modified_time'])) {
            $shopeeCancellationList->update_time = $data['modified_time'];
            $shopeeCancellationList->is_deal     = 2;//已取消
            $shopeeCancellationList->save();
            return true;
        } else {
            return false;
        }

    }

    /**
     * @author alpha
     * @desc shopee 交易  拒绝
     * @param $ordersn
     * @param $account_id
     * @return bool
     */
    public static function RejectBuyerCancellation($ordersn, $account_id)
    {
        //获取账号信息
        $accountInfo = Account::findById($account_id);
        if (empty($accountInfo)) {
            return false;
        }
        $api = new ShopeeApi(intval($accountInfo->shop_id), intval($accountInfo->partner_id), $accountInfo->secret_key);

        $data = $api->RejectBuyerCancellation($ordersn);
        //更新
        $shopeeCancellationList = ShopeeCancellationList::findOne(['ordersn' => $ordersn]);
        if (!empty($data['modified_time'])) {
            $shopeeCancellationList->update_time = $data['modified_time'];
            $shopeeCancellationList->is_deal     = 2;//已拒绝
            $shopeeCancellationList->save();
            return true;
        } else {
            return false;
        }

    }

    /**
     * @author alpha
     * @desc 纠纷同意
     * @param $returnsn
     * @param $account_id
     * @return bool
     */
    public static function ConfirmReturn($returnsn, $account_id)
    {
        //获取账号信息
        $accountInfo = Account::findById($account_id);
        if (empty($accountInfo)) {
            return false;
        }
        $api  = new ShopeeApi(intval($accountInfo->shop_id), intval($accountInfo->partner_id), $accountInfo->secret_key);
        $data = $api->ConfirmReturn($returnsn);
        if (!empty($data['returnsn'])) {
            $shopeeDisputeList = ShopeeDisputeList::findOne(['returnsn' => $returnsn]);
            if (empty($shopeeDisputeList)) {
                $shopeeDisputeList = new ShopeeDisputeList();
            }
            $shopeeDisputeList->returnsn = $data['returnsn'];
            $shopeeDisputeList->is_deal  = 2;
            $shopeeDisputeList->due_date = time();//修改回复时间
            $shopeeDisputeList->save();
            $return_data['code'] = 200;
            $return_data['msg']  = $data['msg'];
        } else {
            $return_data['code'] = 201;
            $return_data['msg']  = $data['msg'];
        }
        return $return_data;
    }

    /**
     * @author alpha
     * @desc 纠纷拒绝
     * @param $returnsn
     * @param $account_id
     * @param $email
     * @param $dispute_reason
     * @param $dispute_text_reason
     * @param $images
     * @return bool
     */
    public static function DisputeReturn($returnsn, $account_id, $email, $dispute_reason, $dispute_text_reason, $images)
    {
        //获取账号信息
        $accountInfo = Account::findById($account_id);
        if (empty($accountInfo)) {
            return false;
        }
        /********TEST DATA*******/
        /* $dispute_reason      = 'NON_RECEIPT';
         $dispute_text_reason = 'I would like to reject the non_';
         $images              = ['http://f.shopee.vn/file/6ff175db767798a5a1c05d2ba1523999'];*/
        /********TEST DATA*******/
        $api  = new ShopeeApi(intval($accountInfo->shop_id), intval($accountInfo->partner_id), $accountInfo->secret_key, $accountInfo->country_code);
        $data = $api->DisputeReturn($returnsn, trim($email), trim($dispute_reason), trim($dispute_text_reason), $images);
        if (!empty($data['returnsn'])) {
            $shopeeDisputeList                      = ShopeeDisputeList::findOne(['returnsn' => $returnsn]);
            $shopeeDisputeList->is_deal             = 2;
            $shopeeDisputeList->due_date            = time();//修改回复时间
            $shopeeDisputeList->dispute_reason      = json_encode([$dispute_reason]);
            $shopeeDisputeList->dispute_text_reason = json_encode([$dispute_text_reason]);//回复信息存进数据库
            $shopeeDisputeList->save();
            die(json_encode(['code' => 200, 'msg' => $data['msg']]));
        } else {
            die(json_encode(['code' => 201, 'msg' => $data['msg']]));
        }
    }

    /**
     * @author alpha
     * @desc  卖家上传纠纷证据图片
     * @param $returnsn
     * @param $file_url
     * @return bool|string
     */
    public static function addIssueImage($returnsn, $file_url)
    {
        //获取纠纷列表
        $issueInfo = ShopeeDisputeList::find()->where(['returnsn' => $returnsn])->asArray()->one();
        if (empty($issueInfo)) {
            return '没有找到纠纷信息';
        }
        //获取账号信息
        $accountInfo = Account::findOne($issueInfo['account_id']);
        if (empty($accountInfo)) {
            return '没有找到账号信息';
        }

        $api  = new ShopeeApi(intval($accountInfo->shop_id), intval($accountInfo->partner_id), $accountInfo->secret_key);
        $data = $api->UploadImage($file_url);

        if (!empty($data['images'])) {
            $shopeeAttachment = ShopeeAttachment::findOne(['returnsn' => $returnsn]);
            if (empty($shopeeAttachment)) {
                $shopeeAttachment = new ShopeeAttachment();
            }
            $image_url        = [];
            $shopee_image_url = [];
            foreach ($data['images'] as $v) {
                $image_url[]        = htmlspecialchars($v['image_url']);
                $shopee_image_url[] = htmlspecialchars($v['shopee_image_url']);
            }
            $shopeeAttachment->returnsn         = $returnsn;
            $shopeeAttachment->image_url        = json_encode($image_url);//
            $shopeeAttachment->shopee_image_url = json_encode($shopee_image_url);//
            $shopeeAttachment->create_time      = date('Y-m-d H:i:s');
            $shopeeAttachment->create_by        = Yii::$app->user->identity->login_name;
            $shopeeAttachment->save();
            return true;
        } else {
            return false;
        }
    }

    /**
     * @author alpha
     * @desc 获取订单详情
     * @param $ordersn
     * @param $account_id
     * @return array|bool|string
     */
    public static function getOrderDetail($ordersn, $account_id)
    {
        //获取账号信息
        $accountInfo = Account::findOne(intval($account_id));
        if (empty($accountInfo)) {
            return '没有找到账号信息';
        }
        $ordersn = [$ordersn];
        $api     = new ShopeeApi(intval($accountInfo->shop_id), intval($accountInfo->partner_id), $accountInfo->secret_key);
        $data    = $api->getorderdetail($ordersn);
        return $data;

    }

    /**
     * @author alpha
     * @desc 退款退货的更新status
     * @param $account_id
     * @return mixed|string
     */
    public static function updateReturnStatus($account_id)
    {
        //获取账号信息
        $accountInfo = Account::findOne(intval($account_id));
        if (empty($accountInfo)) {
            return '没有找到账号信息';
        }
        $api                                            = new ShopeeApi(intval($accountInfo->shop_id), intval($accountInfo->partner_id), $accountInfo->secret_key);
        $endTime                                        = !empty($endTime) ? strtotime($endTime) : time();
        $startTime                                      = !empty($startTime) ? strtotime($startTime) : ($endTime - 86400 * 2);
        $condition_other['pagination_offset']           = 100;
        $condition_other['pagination_entries_per_page'] = 1;
        $condition_other['create_time_from']            = $startTime;
        $condition_other['create_time_to']              = $endTime;
        $data                                           = $api->GetReturnList($condition_other);
        return $data;

    }


    /**
     * @author alpha
     * @desc 查询sku
     * @param $sku
     * @return array
     */
    public static function getOrder_id($sku)
    {
        $order_id = self::find()
            ->select('order_id')
            ->from('{{%order_other_detail}}')
            ->where(['sku' => $sku])
            ->asArray()
            ->column();

        if (empty($order_id)) {
            $order_id = self::find()
                ->select('order_id')
                ->from('{{%order_other_detail_copy}}')
                ->where(['sku' => $sku])
                ->asArray()
                ->column();
        }
        return $order_id;
    }

    /**
     * @author alpha
     * @desc 查询卖家id
     * @param $buyer_id
     * @return array
     */
    public static function getPlatOrderId($buyer_id)
    {
        $platform_order_id = self::find()
            ->select('platform_order_id')
            ->from(self::tableName())
            ->where(['buyer_id' => $buyer_id])
            ->asArray()
            ->column();

        if (empty($platform_order_id)) {
            $platform_order_id = self::find()
                ->select('platform_order_id')
                ->from('{{%order_other_copy}}')
                ->where(['buyer_id' => $buyer_id])
                ->asArray()
                ->column();
        }
        return $platform_order_id;
    }

    /**
     * @author alpha
     * @desc 查询订单id
     * @param $order_id
     * @return array
     */
    public static function getPlatformOrders($order_id)
    {
        $platform_order_id = self::find()
            ->select('platform_order_id')
            ->from('{{%order_other}}')
            ->where(['in', 'order_id', $order_id])
            ->asArray()
            ->column();

        if (empty($platform_order_id)) {
            $platform_order_id = self::find()
                ->select('platform_order_id')
                ->from('{{%order_other_copy}}')
                ->where(['in', 'order_id', $order_id])
                ->asArray()
                ->column();
        }
        return $platform_order_id;
    }

    /**
     * @author alpha
     * @desc 获取平台订单号
     * @param $order_id
     * @return mixed
     */
    public static function getPlatform($order_id)
    {
        $platform_order_id = self::find()
            ->select('platform_order_id')
            ->from(self::tableName())
            ->where(['order_id' => $order_id])
            ->asArray()
            ->one();
        if (!$platform_order_id) {
            $platform_order_id1 = self::find()
                ->select('platform_order_id')
                ->from('{{%order_other_copy}}')
                ->where(['order_id' => $order_id])
                ->asArray()
                ->one();
            return $platform_order_id1['platform_order_id'];
        }
        return $platform_order_id['platform_order_id'];
    }
}


