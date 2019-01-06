<?php

namespace app\modules\mails\models;

use app\components\Model;
use app\modules\orders\models\OrderOtherSearch;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;

class ShopeeCancellationList extends Model
{
    //取消原因
    public static $CancelReason = ['OUT_OF_STOCK'       => 'OUT_OF_STOCK', 'CUSTOMER_REQUEST' => 'CUSTOMER_REQUEST',
                                   'UNDELIVERABLE_AREA' => 'UNDELIVERABLE_AREA', 'COD_NOT_SUPPORTED' => 'COD_NOT_SUPPORTED'];
    //订单状态
    public static $OrderStatus = ['UNPAID'    => 'UNPAID', 'READY_TO_SHIP' => 'READY_TO_SHIP',
                                  'SHIPPED'   => 'SHIPPED', 'TO_CONFIRM_RECEIVE' => 'TO_CONFIRM_RECEIVE',
                                  'CANCELLED' => 'CANCELLED', 'INVALID' => 'INVALID', 'TO_RETURN' => 'TO_RETURN',
                                  'COMPLETED' => 'COMPLETED', 'IN_CANCEL' => 'IN_CANCEL', 'RETRY_SHIP' => 'RETRY_SHIP'];
    public static $is_deal_list = [1 => '未处理', 2 => '已处理'];

    public function attributes()
    {
        $attributes   = parent::attributes();
        $attributes[] = 'order_id';
        $attributes[] = 'buyer_id';
        $attributes[] = 'is_deal';
        return $attributes;
    }

    public static function tableName()
    {
        return '{{%shopee_cancellation}}';
    }


    /**
     * @author alpha
     * @desc 查询
     * @param array $params
     * @return \yii\data\ActiveDataProvider
     */
    public function searchList($params = [])
    {
        $query = self::find()
            ->select('*');
        //只能查询到客服绑定账号的交易
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_SHOPEE);
        $query->andWhere(['in', 'account_id', $accountIds]);
        $sort               = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'update_time' => SORT_DESC,
            'id'          => SORT_DESC
        );
        //筛选状态是in_cancel
        $query->andWhere(['order_status'=>'IN_CANCEL']);
        if (!empty($params['sku'])) {
            //通过sku查询order_id
            $order_id = OrderOtherSearch::getOrder_id($params['sku']);
            if (!empty($order_id)) {
                //通过order_id查询platform_order_id
                $platform_order_id = OrderOtherSearch::getPlatformOrders($order_id);
                if ($platform_order_id) {
                    $query->andWhere(['in', 'ordersn', $platform_order_id]);
                }
            }
            unset($params['sku']);
        }

        //查询买家ID
        if (!empty($params['buyer_id'])) {
            $plat_order_id = OrderOtherSearch::getPlatOrderId($params['buyer_id']);
            if (!empty($plat_order_id)) {
                $query->andWhere(['in', 'ordersn', $plat_order_id]);
            }
            unset($params['buyer_id']);
        }

        if (!empty($params['order_id'])) {
            $platform_order_id = OrderOtherSearch::getPlatform($params['order_id']);
            if (!empty($platform_order_id)) {
                $query->andWhere(['ordersn' => $platform_order_id]);
            }
            unset($params['order_id']);
        }

        $begin_date = strtotime(trim($params['start_time']));
        $end_date   = strtotime(trim($params['end_time']));
        //created_time 下单时间
        if ($begin_date && $end_date) {
            $query->andWhere(['between', 'update_time', $begin_date, $end_date]);
        } elseif (!empty($begin_date)) {
            $query->andWhere(['>=', 'update_time', $begin_date]);
        } elseif (!empty($end_date)) {
            $query->andWhere(['<=', 'update_time', $end_date]);
        }
        //查询平台订单号
        if (!empty($params['order_id'])) {
            $platform_order_id = OrderOtherSearch::getOrderId($params['order_id']);
            if (!empty($platform_order_id)) {
                $query->andWhere(['ordersn' => $platform_order_id]);
            }
            unset($params['order_id']);
        }

        $dataProvider = parent::search($query, $sort, $params);
        $models       = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * @author alpha
     * @desc label名称
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'order_id'      => '订单号',
            'ordersn'       => '平台订单号',
            'order_type'    => '订单类型',
            'cancel_reason' => '取消原因',
            'buyer_id'      => '买家ID',
            'account_id'    => '账号',
            'update_time'   => '完成时间',
            'order_status'  => '状态',
            'is_deal'       => '是否处理',
        ];
    }

    /**
     * 搜索过滤项
     */
    public function filterOptions()
    {
        return [
            [
                'name'        => 'ordersn',//平台订单号
                'type'        => 'text',
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'order_id',//订单号
                'type'        => 'text',
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'   => 'account_id',
                'alias'  => 'l',
                'type'   => 'search',
                'data'   => self::dropdown(),
                'search' => '='
            ],
            [
                'name'        => 'buyer_id',//买家id
                'type'        => 'text',
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'is_deal',//是否处理
                'type'        => 'dropDownList',
                'data'        => self::$is_deal_list,
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'cancel_reason',
                'type'        => 'dropDownList',
                'data'        => self::$CancelReason,
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'order_status',
                'type'        => 'dropDownList',
                'data'        => self::$OrderStatus,
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'item_id',
                'type'        => 'text',
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'start_time',
                'type'        => 'date_picker',
                'htmlOptions' => ['width:320px'],
                'search'      => '>'
            ],
            [
                'name'        => 'end_time',
                'type'        => 'date_picker',
                'htmlOptions' => ['width:320px'],
                'search'      => '<'
            ],
        ];
    }


    /**
     * 修改模型数据
     */
    public function addition(&$models)
    {
        //获取速卖通所有账号信息
        $accounts = Account::getAccount(Platform::PLATFORM_CODE_SHOPEE, 1);
        //平台订单ID数组
        $orderIds = [];
        foreach ($models as $key => $model) {
            $orderIds[] = $model->ordersn;
        }
        //获取订单ID和买家ID
        $orderIdAndBuyerIds = OrderOtherSearch::getOrderIdAndBuyerId($orderIds);

        foreach ($models as $key => $model) {
            //update_time 时间戳修改
            $model->setAttribute('update_time', date('Y-m-d H:i:s', $model->update_time));
            if (array_key_exists($model->ordersn, $orderIdAndBuyerIds)) {
                $model->setAttribute('order_id', $orderIdAndBuyerIds[$model->ordersn]['order_id']);
                $model->setAttribute('buyer_id', $orderIdAndBuyerIds[$model->ordersn]['buyer_id']);
            }
            $model->setAttribute('is_deal', self::$is_deal_list[$model->is_deal]);
            $model->setAttribute('ordersn', Html::a($model->ordersn, Url::toRoute(['/orders/order/orderdetails', 'order_id' => $model->ordersn, 'platform' => Platform::PLATFORM_CODE_SHOPEE]),
                ['class' => 'add-button', '_width' => '90%', '_height' => '90%']));

            $accountName = array_key_exists($model->account_id, $accounts) ? $accounts[$model->account_id] : '';
            //如果账号名称还为空，则查询客服系统中的信息
            if (empty($accountName)) {
                $accountName = $this->getAccountName($model->account_id);
            }
            $model->setAttribute('account_id', $accountName);
        }
    }

    /**
     * 账号列表
     */
    public static function dropdown()
    {

        $accountmodel = new Account();
        $all_account  = $accountmodel->findAll(['status' => 1, 'platform_code' => Platform::PLATFORM_CODE_SHOPEE]);
        $arr          = [' ' => '全部'];
        foreach ($all_account as $key => $value) {
            $arr[$value['id']] = $value['account_name'];
        }
        return $arr;
    }

    /**
     * 获取账号名称
     */
    public static function getAccountName($account_id)
    {
        $accountModel = new Account();
        $accountModel = $accountModel->find()->where(['id' => $account_id])->select('account_name')->asArray()->one();
        return $accountModel['account_name'];
    }

}