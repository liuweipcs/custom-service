<?php

namespace app\modules\mails\models;

use app\components\Model;
use app\modules\orders\models\OrderOtherSearch;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;

class ShopeeDisputeList extends Model
{
    //纠纷原因
    public static $reason_list = [
        'NONE'                  => 'NONE',
        'NOT_RECEIPT'           => 'NOT_RECEIPT',
        'WRONG_ITEM'            => 'WRONG_ITEM',
        'ITEM_DAMAGED'          => 'ITEM_DAMAGED',
        'DIFFERENT_DESCRIPTION' => 'DIFFERENT_DESCRIPTION',
        'MUTUAL_AGREE'          => 'MUTUAL_AGREE',
        'OTHER'                 => 'OTHER',
        'ITEM_WRONGDAMAGED'     => 'ITEM_WRONGDAMAGED',
        'CHANGE_MIND'           => 'CHANGE_MIND',
        'ITEM_MISSING'          => 'ITEM_MISSING',
        'EXPECTATION_FAILED'    => 'EXPECTATION_FAILED',
        'ITEM_FAKE'             => 'ITEM_FAKE',
    ];

    //纠纷状态
    public static $status_list = [
        'REQUESTED'      => 'REQUESTED',
        'ACCEPTED'       => 'ACCEPTED',
        'REFUND_PAID'    => 'REFUND_PAID',
        'CANCELLED'      => 'CANCELLED',
        'JUDGING'        => 'JUDGING',
        'CLOSED'         => 'CLOSED',
        'PROCESSING'     => 'PROCESSING',
        'SELLER_DISPUTE' => 'SELLER_DISPUTE',
    ];

    public static $is_deal_list = [1 => '未处理', 2 => '已处理'];

    //定义纠纷关闭常量
    const DISPUTE_CLOSED = 'CLOSED';

    public function attributes()
    {

        $attributes    = parent::attributes();
        $extAttributes = [
            'order_id',
            'buyer_id',
            'seller_shop',
            'is_deal',
        ];
        return array_merge($attributes, $extAttributes);
    }

    public static function tableName()
    {
        return '{{%shopee_dispute_list}}';
    }


    public function searchList($params = [])
    {
        $query = self::find()
            ->alias('l')
            ->select('l.*');

        //只能查询到客服绑定账号的纠纷
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_SHOPEE);
        $query->andWhere(['in', 'l.account_id', $accountIds]);

        $sort               = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'due_date' => SORT_DESC,
            'id'       => SORT_DESC
        );
        if (!empty($params['sku'])) {
            //通过sku查询order_id
            $order_id = OrderOtherSearch::getOrder_id($params['sku']);
            if (!empty($order_id)) {
                //通过order_id查询platform_order_id
                $platform_order_id = OrderOtherSearch::getPlatformOrders($order_id);
                if ($platform_order_id) {
                    $query->andWhere(['in', 'l.ordersn', $platform_order_id]);
                }
            }
            unset($params['sku']);
        }


        //查询买家ID
        if (!empty($params['buyer_id'])) {
            $plat_order_id = OrderOtherSearch::getPlatOrderId($params['buyer_id']);
            if (!empty($plat_order_id)) {
                $query->andWhere(['in', 'l.ordersn', $plat_order_id]);
            }
            unset($params['buyer_id']);
        }

        if (!empty($params['order_id'])) {
            $platform_order_id = OrderOtherSearch::getPlatform($params['order_id']);
            if (!empty($platform_order_id)) {
                $query->andWhere(['l.ordersn' => $platform_order_id]);
            }
            unset($params['order_id']);
        }

        $begin_date = strtotime(trim($params['start_time']));
        $end_date   = strtotime(trim($params['end_time']));
        //created_time 下单时间
        if ($begin_date && $end_date) {
            $query->andWhere(['between', 'l.create_time', $begin_date, $end_date]);
        } elseif (!empty($begin_date)) {
            $query->andWhere(['>=', 'l.create_time', $begin_date]);
        } elseif (!empty($end_date)) {
            $query->andWhere(['<=', 'l.create_time', $end_date]);
        }

        //查询平台订单号
        if (!empty($params['order_id'])) {
            $platform_order_id = OrderOtherSearch::getOrderId($params['order_id']);
            if (!empty($platform_order_id)) {
                $query->andWhere(['l.ordersn' => $platform_order_id]);
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
            'account_id'     => '账号',
            'ordersn'        => '平台订单号',
            'returnsn'       => 'return id',
            'reason'         => '原因',
            'dispute_reason' => '纠纷原因',
            'seller_shop'    => '店铺ID',
            'create_time'    => '开始时间',
            'update_time'    => '完成时间',
            'status'         => '状态',
            'refund_amount'  => '退款金额',
            'order_id'       => '订单号',
            'buyer_id'       => '买家ID',
            'due_date'       => '回复截止日期',
            'is_deal'        => '是否处理',
        ];
    }

    /**
     * 搜索过滤项
     */
    public function filterOptions()
    {
        return [
            [
                'name'   => 'returnsn',//return id
                'alias'  => 'l',
                'type'   => 'text',
                'search' => '='
            ],
            [
                'name'        => 'ordersn',//平台订单号
                'alias'       => 'l',
                'type'        => 'text',
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'order_id',//订单号
                'alias'       => 'l',
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
                'alias'       => 'l',
                'type'        => 'dropDownList',
                'data'        => self::$is_deal_list,
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'reason',
                'alias'       => 'l',
                'type'        => 'dropDownList',
                'data'        => self::$reason_list,
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'status',
                'alias'       => 'l',
                'type'        => 'dropDownList',
                'data'        => self::$status_list,
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'        => 'item_id',
                'alias'       => 'l',
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
        //纠纷ID数组
        $issueIds = [];
        //平台订单ID数组
        $orderIds = [];
        foreach ($models as $key => $model) {
            $issueIds[] = $model->returnsn;
            $orderIds[] = $model->ordersn;
        }
        //获取订单ID和买家ID
        $orderIdAndBuyerIds = OrderOtherSearch::getOrderIdAndBuyerId($orderIds);

        foreach ($models as $key => $model) {

            $model->setAttribute('refund_amount', $model->refund_amount.$model->currency);
            $model->setAttribute('create_time', date('Y-m-d H:i:s', $model->create_time));
            $model->setAttribute('due_date', date('Y-m-d H:i:s', $model->due_date));
            $model->setAttribute('dispute_reason', json_decode($model->dispute_reason)[0]);
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

    /**
     * 获取订单的纠纷ID
     */
    public static function getOrderDisputes($platformOrderId)
    {
        if (empty($platformOrderId)) {
            return false;
        }

        $data = self::find()
            ->select('returnsn')
            ->where(['platform_order_id' => $platformOrderId])
            ->asArray()
            ->all();

        return $data;
    }
}