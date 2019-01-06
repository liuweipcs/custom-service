<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/11 0011
 * Time: 下午 8:41
 */

namespace app\modules\mails\models;


use app\common\VHelper;
use app\modules\accounts\models\EbayCaseRefund;
use app\modules\accounts\models\UserAccount;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\orders\models\Logistic;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderRemarkKefu;
use app\modules\orders\models\Warehouse;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\systems\models\Country;
use app\modules\systems\models\EbayAccount;
use PhpImap\Exception;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\AutoCode;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\accounts\models\Platform;
use yii\helpers\Json;
use app\modules\accounts\models\Account;
use yii\helpers\Url;
use Yii;
use app\modules\orders\models\OrderEbay;

class EbayInquiry extends MailsModel
{
    public static $initiatorMap = [1 => 'BUYER', 2 => 'CSR', 3 => 'SELLER', 4 => 'SYSTEM', 5 => 'UNKNOWN'];
    public static $appealCloseReasonEnumMap = [1 => 'APPEAL_BUYER_DENIED', 2 => 'APPEAL_BUYER_WINS', 3 => 'APPEAL_BUYER_WINS_PARTIAL_CREDIT', 4 => 'APPEAL_SELLER_DENIED', 5 => 'APPEAL_SELLER_WINS_FULL_CREDIT', 6 => 'APPEAL_SELLER_WINS_FULL_CREDIT_CANCEL_RECOUP', 7 => 'APPEAL_SELLER_WINS_PARTIAL_CREDIT', 8 => 'OTHER'];
    public static $appealStatusMap = [1 => 'DENIED', 2 => 'GRANTED', 3 => 'OTHER'];
    public static $appealStatusEnum = [1 => 'BUYER_APPEALED', 2 => 'BUYER_SELLER_APPEALED', 3 => 'OTHER', 4 => 'SELLER_APPEALED', 5 => 'SELLER_INVOICED_BUYER_APPEALED', 6 => 'SELLER_RECOUP_APPEALED', 7 => 'SELLER_RECOUP_NEEDED_BUYER_APPEALED', 8 => 'SELLER_RECOUP_NEEDED_BUYER_APPEALED_CLOSED_CASE', 9 => 'SELLER_RECOUPED_BUYER_APPEALED', 10 => 'SELLER_RECOUPED_BUYER_APPEALED_CLOSED_CASE'];
    public static $eligibleForAppealMap = [1 => true, 2 => false];

    public $ebayAccountModel;

    public function attributes()
    {
        $attributes   = parent::attributes();
        $attributes[] = 'order_type';
        $attributes[] = 'order_id';
        $attributes[] = 'son_order_id';
        $attributes[] = 'parent_order_id';
        $attributes[] = 'old_account_id';
        $attributes[] = 'warehouse_id';
        $attributes[] = 'warehouse';
        $attributes[] = 'logistics';
        $attributes[] = 'ship_code';    //发货方式代码
        $attributes[] = 'logistics';    //发货方式
        $attributes[] = 'ship_country';
        $attributes[] = 'shipped_date';
        $attributes[] = 'location';
        $attributes[] = 'platform_order_id_old';
        $attributes[] = 'pay_time';
        return $attributes;
    }

    public static function tableName()
    {
        return '{{%ebay_inquiry}}';
    }

    public function attributeLabels()
    {
        return [
            'claim_amount'                      => '涉及金额',
            'currency'                          => '货币',
            'extTransaction_id'                 => '交易支付ID',
            'initiator'                         => '发起方',
            'appeal_close_reason_enum'          => '上诉关闭原因',
            'appeal_date'                       => '上诉时间',
            'appeal_reason_code'                => '上诉原因',
            'appeal_status'                     => 'eBay接受上诉状态',
            'appeal_status_enum'                => '上诉状态',
            'eligible_for_appeal'               => '是否有资格上诉',
            'creation_date'                     => 'Inquiry创建时间',
            'escalation_date'                   => '升级时间',
            'expiration_date'                   => '过期时间',
            'last_buyer_respdate'               => '买家上次回复时间',
            'buyer_final_accept_refund_amt'     => '买家最终接受退款金额',
            'buyer_init_expect_refund_amt'      => '买家最初期望退款金额',
            'refund_amount'                     => '卖家发放退款金额',
            'refund_deadline_date'              => '卖家发放退款最迟时间',
            'total_amount'                      => '订单总金额',
            'inquiry_id'                        => 'Inquiry Id',
            'inquiry_quantity'                  => '涉及产品数量',
            'item_picture_url'                  => '产品图片URL',
            'item_price'                        => '产品价格',
            'item_title'                        => '产品名称',
            'item_id'                           => 'ItemID',
            'seller_make_it_right_by_date'      => '卖家最迟解决时间',
            'state'                             => '状况',
            'status'                            => '状态',
            'buyer'                             => '买家',
            'buyer_initial_expected_resolution' => '买家期望',
            'transaction_id'                    => '交易ID',
            'platform_order_id'                 => '平台订单号',
            'account_id'                        => 'eBay账号',
            'update_time'                       => '更新时间',
            'order_id'                          => '订单号',
            'is_deal'                           => '处理过渡中',
            'order_type'                        => '订单类型',
            'warehouse_id'                      => '发货仓库',
            'shipped_date'                      => '发货日期',
            'ship_country'                      => '目的国家',
            'ship_code'                         => '发货方式code',
            'logistics'                         => '发货方式',
            'location'                          => 'Item Location',
            'warehouse'                         => '发货仓库',
            'pay_time'                          => '付款时间',
            'ship_date_start_time'              => '发货开始日期',
            'ship_date_end_time'                => '发货截至日期',
        ];
    }

    public function rules()
    {
        return [
            [['claim_amount', 'initiator', 'appeal_close_reason_enum', 'appeal_status', 'appeal_status_enum', 'eligible_for_appeal', 'buyer_final_accept_refund_amt', 'buyer_init_expect_refund_amt', 'international_refund_amount', 'refund_amount', 'total_amount', 'item_price', 'inquiry_quantity'], 'default', 'value' => 0],
            [['currency', 'extTransaction_id', 'appeal_reason_code', 'inquiry_id', 'item_title', 'item_picture_url', 'view_purchased_item_url', 'item_id', 'state', 'status', 'transaction_id', 'platform_order_id', 'view_pp_trasanction_url', 'buyer'], 'default', 'value' => ''],
            ['account_id', 'integer', 'min' => 1],
            ['inquiry_id', 'unique'],
            ['auto_refund', 'default', 'value' => 0],
            ['is_deal', 'default', 'value' => 1],
            ['auto_refund', 'in', 'range' => [0, 1, 2]],
            [['order_id'], 'safe'],
        ];
    }

    public function dynamicChangeFilter(&$filterOptions, &$query, &$params)
    {
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 'creation_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 'creation_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 'creation_date', $params['end_time']]);
        }
    }

    public function searchList($params = [])
    {
        $query              = self::find();
        $sort               = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'creation_date' => SORT_DESC
        );
        if (isset($params['status'])) {
            switch ($params['status']) {
                case 'wait_seller':
                    $query->where('status in ("OPEN","WAITING_SELLER_RESPONSE")');
                    break;
                case 'closed':
                    $query->where('status in ("CLOSED","CLOSED_WITH_ESCALATION","CS_CLOSED")');
                    break;
                case 'other':
                    $query->where('status in ("OTHER","WAITING_BUYER_RESPONSE")');
            }
            $params['status'] = '';
        }

        // 只能查询到客服绑定账号的纠纷
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);
        $query->andWhere(['in', 'account_id', $accountIds]);
        $query->andWhere(['<>', 'status', 'PENDING']);
        if (!empty($params['sku'])) {
            //通过sku查询item_id
            $itemIds = Order::getEbayFeedBackItemIdBySku([
                'sku' => $params['sku'],
            ]);

            if (!empty($itemIds)) {
                $query->andWhere(['in', 'item_id', $itemIds]);
            }

            unset($params['sku']);
        }

      /*  if(!empty($params['ship_code'])){

        }
        if(!empty($params['ship_country'])){

        }
        if(!empty($params['warehouse_id'])){

        }
        if(!empty($params['ship_date_start_time']&&!empty($params['ship_date_end_time']))){

        }*/

        //查询ebay订单表得平台订单号
        if (!empty($params['order_id'])) {
            $platform_order_id = OrderEbay::getPlatform($params['order_id']);
            if (!empty($platform_order_id)) {

                $query->andWhere(['platform_order_id' => $platform_order_id]);
            }
            unset($params['order_id']);
        }

        $dataProvider = parent::search($query, $sort, $params);

        $models = $dataProvider->getModels();
        foreach ($models as $k => $v) {
            $v['order_id']        = OrderEbay::getOrderId($v['platform_order_id']);
            $v['order_type']      = OrderEbay::getOrderType($v['platform_order_id']);
            $v['parent_order_id'] = OrderEbay::getParentOrderId($v['platform_order_id']);
            $v['old_account_id']  = OrderEbay::getAccountId($v['platform_order_id']);
            $son_order_id_arr     = array();
            // 根据订单类型获取关联订单单号
            switch ($v['order_type']) {
                // 合并后的订单、被拆分的订单查询子订单
                case OrderEbay::ORDER_TYPE_MERGE_MAIN:
                case OrderEbay::ORDER_TYPE_SPLIT_MAIN:
                    $son_order_ids = isset($v['order_id']) ? OrderEbay::getOrderSon($v['order_id']) : null;
                    foreach ($son_order_ids as $son_order_id) {
                        $son_order_id_arr[] = $son_order_id['order_id'];
                    }
                    $models[$k]['son_order_id'] = $son_order_id_arr;
                    break;
            }
        }
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function addition(&$models)
    {
        $platform_order_id_arr = [];
        $orderId_arr=[];
        $warehouseList         = Warehouse::getAllWarehouseList(true);
        $countryList           = Country::getCodeNamePairs('cn_name');
        foreach ($models as $model) {
            isset($model->appeal_date) && $model->appeal_date = date('Y-m-d H:i:s', strtotime($model->appeal_date) + 28800);
            isset($model->creation_date) && $model->creation_date = date('Y-m-d H:i:s', strtotime($model->creation_date) + 28800);
            isset($model->escalation_date) && $model->escalation_date = date('Y-m-d H:i:s', strtotime($model->escalation_date) + 28800);
            isset($model->expiration_date) && $model->expiration_date = date('Y-m-d H:i:s', strtotime($model->expiration_date) + 28800);
            isset($model->last_buyer_respdate) && $model->last_buyer_respdate = date('Y-m-d H:i:s', strtotime($model->last_buyer_respdate) + 28800);
            isset($model->refund_deadline_date) && $model->refund_deadline_date = date('Y-m-d H:i:s', strtotime($model->refund_deadline_date) + 28800);
            isset($model->seller_make_it_right_by_date) && $model->seller_make_it_right_by_date = date('Y-m-d H:i:s', strtotime($model->seller_make_it_right_by_date) + 28800);
            $model->initiator                = self::$initiatorMap[$model->initiator];
            $account_info                    = Account::getHistoryAccountInfo($model->old_account_id, 'EB');
            $model->appeal_close_reason_enum = $model->appeal_close_reason_enum == 0 ? '' : self::$appealCloseReasonEnumMap[$model->appeal_close_reason_enum];
            $model->appeal_status            = $model->appeal_status == 0 ? '' : self::$appealStatusMap[$model->appeal_status];
            $model->appeal_status_enum       = $model->appeal_status_enum == 0 ? '' : self::$appealStatusEnum[$model->appeal_status_enum];
            $model->eligible_for_appeal      = $model->eligible_for_appeal > 0 ? '' : ($model->eligible_for_appeal == 1 ? '是' : '否');
            $model->account_id               = Account::findOne((int)$model->account_id)->account_name;

            $model->buyer_init_expect_refund_amt = (empty($model->buyer_init_expect_refund_amt) || $model->buyer_init_expect_refund_amt == '0.00') ? $model->claim_amount . ' ' . $model->currency : $model->buyer_init_expect_refund_amt . ' ' . $model->currency;
            $model->refund_amount                = (empty($model->buyer_init_expect_refund_amt) || $model->refund_amount == '0.00') ? $model->claim_amount . ' ' . $model->currency : $model->refund_amount . ' ' . $model->currency;
            //平台订单id
            $model->platform_order_id_old = $model->platform_order_id;
            $platform_order_id_arr[]      = $model->platform_order_id;

            if (empty($model->platform_order_id))
                $platform_order_id = $model->item_id . '-' . $model->transaction_id;
            else
                $platform_order_id = $model->platform_order_id;
            $model->platform_order_id = '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/orders/order/orderdetails', 'order_id' => $platform_order_id, 'platform' => 'EB']) . '">' . $platform_order_id . '</a>';

            switch ($model->order_type) {
                case Order::ORDER_TYPE_MERGE_MAIN:
                    $rela_order_name = '合并前子订单';
                    $rela_is_arr     = true;
                    break;
                case Order::ORDER_TYPE_SPLIT_MAIN:
                    $rela_order_name = '拆分后子订单';
                    $rela_is_arr     = true;
                    break;
                case Order::ORDER_TYPE_MERGE_RES:
                    $rela_order_name = '合并后父订单';
                    $rela_is_arr     = false;
                    break;
                case Order::ORDER_TYPE_SPLIT_CHILD:
                    $rela_order_name = '拆分前父订单';
                    $rela_is_arr     = false;
                    break;
                default:
                    $rela_order_name = '';
            }
            $order_result = '';
            if (!empty($rela_order_name)) {
                if ($rela_is_arr) {
                    foreach ($model->son_order_id as $son_order_id) {
                        $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/orders/order/orderdetails', 'system_order_id' => $son_order_id, 'platform' => 'EB']) . '"title="订单信息">' . $son_order_id . '</a></p>';
                    }
                    if (!empty($order_result))
                        $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                } else {
                    $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/orders/order/orderdetails', 'system_order_id' => $model->parent_order_id, 'platform' => 'EB']) . '"title="订单信息">' . $model->parent_order_id . '</a></p>';
                    $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;

                }
            }
            $order_id          = isset($account_info->account_short_name) ? $account_info->account_short_name . '--' : '';
            $model->order_id   = $order_id . '<br />' . $model->order_id . $order_result;
            $model->order_type = (isset($model->order_type) && !empty($model->order_type)) ? VHelper::getOrderType($model->order_type) : "-";
        }
        //
        $platform_order_info = OrderEbay::getExtraInfo($platform_order_id_arr);
        foreach ($models as &$model_) {
            foreach ($platform_order_info as $v) {
                if ($model_->platform_order_id_old == $v['platform_order_id']) {
                    $model_->warehouse_id = $v['warehouse_id'];
                    $model_->ship_code    = $v['ship_code'];
                    $model_->ship_country = $v['ship_country'];
                    $model_->shipped_date = $v['shipped_date'];
                    $model_->location     = $v['location'];
                    $model_->warehouse    = isset($model_->warehouse_id) && (int)$model_->warehouse_id > 0 ? $warehouseList[$model_->warehouse_id] : null;  //发货仓库
                    $model_->logistics    = isset($model_->ship_code) ? Logistic::getSendGoodsWay($model_->ship_code) : null; //发货方式
                    $model_->ship_country = $model_->ship_country . (array_key_exists($model_->ship_country, $countryList)
                            ? '(' . $countryList[$model_->ship_country] . ')' : '');
                    $model_->pay_time     = $v['paytime'];
                }
            }
        }
    }

    public function filterOptions()
    {
        return [
            [
                'name'   => 'inquiry_id',
                'type'   => 'text',
                //'data' => EbayAccount::getIdNameKVList(),
                'search' => '='
            ],
            [
                'name'  => 'order_id',
                'type'  => 'text',
                'serch' => '=',
            ],
            [
                'name'   => 'platform_order_id',
                'type'   => 'text',
                'search' => '='
            ],
            [
                'name'   => 'item_id',
                'type'   => 'text',
                'search' => '='
            ],
            [
                'name'   => 'account_id',
                'type'   => 'search',
                'data'   => Account::getIdNameKVList(Platform::PLATFORM_CODE_EB),
                'search' => '='
            ],
            [
                'name'   => 'buyer',
                'type'   => 'text',
//                'data' => EbayAccount::getIdNameKVList(),
                'search' => 'LIKE'
            ],
            [
                'name'   => 'sku',
                'type'   => 'text',
                'search' => '='
            ],
            [
                'name'   => 'status',
                'type'   => 'dropDownList',
                'data'   => ['wait_seller' => '等待卖家回应', 'closed' => '已关闭', 'other' => '其他'],//self::getFieldList('status','status','status'),
                'value'  => 'wait_seller',
                'search' => '='
            ],
            [
                'name'   => 'state',
                'type'   => 'dropDownList',
                'data'   => self::getFieldList('state', 'state', 'state'),
                'search' => '='
            ],
            [
                'name'   => 'is_deal',
                'type'   => 'dropDownList',
                'data'   => [0 => '处理中', 1 => '已处理'],
                'value'  => 1,
                'search' => '='
            ],
            [
                'name'   => 'start_time',
                'type'   => 'date_picker',
                'search' => '<',
            ],
            [
                'name'   => 'end_time',
                'type'   => 'date_picker',
                'search' => '>',
            ],
           /* [
                'name'   => 'ship_code',//发货方式
                'type'   => 'search',
                'data'   => Logistic::getLogisArrCodeName(),
                'search' => '='
            ],
            [
                'name'   => 'ship_country',//目的国
                'type'   => 'search',
                'data'   => Country::getCodeNamePairsList('cn_name'),
                'search' => '=',
            ],
            [
                'name'   => 'warehouse_id',//发货仓库
                'type'   => 'search',
                'data'   => Warehouse::getWarehouseListAll(),
                'search' => '=',
            ],
            [
                'name'   => 'ship_date_start_time',
                'alias'  => 't1',
                'type'   => 'date_picker',
                'value'  => '',
                'search' => '<',
            ],
            [
                'name'   => 'ship_date_end_time',
                'alias'  => 't1',
                'value'  => '',
                'type'   => 'date_picker',
                'search' => '>',
            ],*/
        ];
    }

    public function refreshApi()
    {
        set_time_limit(150);

        $memcache = \Yii::$app->memcache;

        $accountName = Account::findById((int)$this->account_id)->account_name;

        $token = $memcache->get($accountName);
        if (!$token) {
            if (!isset($this->ebayAccountModel)) {
                $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
            }
            $token = $this->ebayAccountModel->user_token;
            $memcache->set($accountName, $token, '', 600);
        }

//        if(!isset($this->ebayAccountModel))
//        {
//            $accountName = Account::findById((int)$this->account_id)->account_name;
//            $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
//            $token = $this->ebayAccountModel->user_token;
//        }
        return $this->detailApi($token, '', 'https://api.ebay.com/post-order/v2/inquiry/' . $this->inquiry_id, 'get');
    }

    private function detailApi($token, $site, $serverUrl, $method)
    {
//        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
//        $response = $api->sendHttpRequest();
//        return $this->handleResponse($response,$token);

        $transfer_ip = include \Yii::getAlias('@app') . '/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
        if (empty($transfer_ip))
            return ['flag' => false, 'info' => '中转服务器配置错误'];

        $data      = '';
        $post_data = ['serverUrl' => $serverUrl, 'authorization' => $token, 'data' => $data, 'method' => $method, 'responseHeader' => false, 'urlParams' => ''];
        $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();
        if (empty($response)) {
            return ['flag' => false, 'info' => '调用香港服务器无响应'];
        } else {
            if (in_array($response->code, [200, 201, 202])) {
                return $this->handleResponse(json_decode($response->response), $token);
            } else {
                return ['flag' => false, 'info' => $response->response];
            }
        }
    }

    private function handleResponse($data, $token)
    {
        $flag                           = true;
        $info                           = '';
        $this->claim_amount             = $data->claimAmount->value - 0;
        $this->currency                 = $data->claimAmount->currency;
        $this->extTransaction_id        = $data->extTransactionId;
        $this->initiator                = array_search(trim($data->initiator), EbayInquiry::$initiatorMap);
        $this->appeal_close_reason_enum = isset($data->inquiryDetails->appealDetails->appealCloseReasonEnum) ? array_search(trim($data->inquiryDetails->appealDetails->appealCloseReasonEnum), EbayInquiry::$appealCloseReasonEnumMap) : 0;
        if (isset($data->inquiryDetails->appealDetails->appealDate))
            $this->appeal_date = explode('.', str_replace('T', ' ', $data->inquiryDetails->appealDetails->appealDate->value))[0];
        $this->appeal_reason_code                = isset($data->inquiryDetails->appealDetails->appealReasonCode) ? $data->inquiryDetails->appealDetails->appealReasonCode : '';
        $this->appeal_status                     = isset($data->inquiryDetails->appealDetails->appealStatus) ? array_search(trim($data->inquiryDetails->appealDetails->appealStatus), EbayInquiry::$appealStatusMap) : 0;
        $this->appeal_status_enum                = isset($data->inquiryDetails->appealDetails->appealStatusEnum) ? array_search(trim($data->inquiryDetails->appealDetails->appealStatusEnum), EbayInquiry::$appealStatusEnum) : 0;
        $this->buyer_initial_expected_resolution = isset($data->inquiryDetails->buyerInitialExpectedResolution) ? $data->inquiryDetails->buyerInitialExpectedResolution : '';
        if (isset($data->buyer))
            $this->buyer = $data->buyer;
        $orderinfo = '';
        if (empty($this->platform_order_id) || empty($this->buyer) || empty($this->order_id)) {
            $buyer    = '';
            $order_id = '';
            if (isset($data->transactionId) && !empty($data->transactionId)) {
                $orderinfo       = Order::getOrderStackByTransactionId('EB', $data->transactionId);
                $platformOrderId = $orderinfo->info->platform_order_id;
            } else {
                $old_account_id = Account::findOne($this->account_id)->old_account_id;
                $orderinfo      = Order::getEbayOrderStack($old_account_id, $data->buyer, $data->itemId, $data->transactionId);
            }

            if (!empty($orderinfo) && !empty($orderinfo->info)) {
                $orderinfo       = Json::decode(Json::encode($orderinfo), true);
                $buyer           = $orderinfo['info']['buyer_id'];
                $order_id        = $orderinfo['info']['order_id'];
                $platformOrderId = $orderinfo['info']['platform_order_id'];
            }
            $this->platform_order_id = isset($platformOrderId) ? $platformOrderId : '';
            $this->buyer             = $buyer;
            $this->order_id          = $order_id;
        }
        $is_refund  = false;
        $refundInfo = EbayCaseRefund::findOne(['account_id' => $this->account_id, 'is_refund' => EbayCaseRefund::STATUS_REFUND_YES]);
        if (!empty($refundInfo)) {
            $is_refund        = true;
            $max_claim_amount = $refundInfo->claim_amount;
            $currency         = $refundInfo->currency;
        }
        if (!isset($currency))
            $currency = Account::CURRENCY;
        if (!isset($max_claim_amount))
            $max_claim_amount = Account::ACCOUNT_PRICE;

        //$finalCNY = VHelper::getTargetCurrencyAmt($this->currency,$currency,$this->claim_amount);
        $finalCNY = VHelper::getTargetCurrencyAmtKefu($this->currency, $currency, $this->claim_amount);

        if ($is_refund && !empty($data->inquiryDetails->escalationDate) && (int)$this->auto_refund == 0 && $finalCNY < $max_claim_amount) {
            if (isset($data->state) && $data->state != 'CLOSED') {
                $flag_refund = true;
                $caseModel   = EbayCase::findOne(['case_id' => $this->inquiry_id]);
                if ($caseModel)
                    $flag_refund = false;
                if ($flag_refund) {
                    $caseModel                 = new EbayCase();
                    $caseModel->case_id        = $this->inquiry_id;
                    $caseModel->item_id        = $this->item_id;
                    $caseModel->case_type      = EbayCase::CASE_TYPE_ITEM_NOT_RECEIVED;
                    $caseModel->transaction_id = $this->transaction_id;
                    $caseModel->buyer          = $this->buyer;
                    $caseModel->account_id     = $this->account_id;
                    $caseModel->status         = isset($this->status) ? $this->status : '';
                    $caseModel->claim_amount   = $this->claim_amount;
                    $caseModel->currency       = $this->currency;
                    $caseModel->case_quantity  = isset($data->inquiryQuantity) ? $data->inquiryQuantity : '';
                    $caseModel->initiator      = isset($data->initiator) ? array_search(trim($data->initiator), EbayInquiry::$initiatorMap) : 0;
                    $caseModel->creation_date  = isset($data->inquiryDetails->escalationDate) && !empty($data->inquiryDetails->escalationDate->value) ? date('Y-m-d H:i:s', strtotime($data->inquiryDetails->escalationDate->value)) : '';

//                    $refundApi = new PostOrderAPI($token,'','https://api.ebay.com/post-order/v2/casemanagement/'.$caseModel->case_id.'/issue_refund','post');
//                    $message = ['comments'=>['content'=>'']];
//                    $refundApi->setData($message);
//                    $refundResponse = $refundApi->sendHttpRequest();

                    $transfer_ip = include Yii::getAlias('@app') . '/config/transfer_ip.php';
                    $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
                    if (empty($transfer_ip))
                        return ['flag' => false, 'info' => '中转站配置错误'];

                    $serverUrl = 'https://api.ebay.com/post-order/v2/casemanagement/' . $caseModel->case_id . '/issue_refund';
                    $post_data = ['serverUrl' => $serverUrl, 'authorization' => $token, 'data' => json_encode(['comments' => ['content' => '']]), 'method' => 'post', 'responseHeader' => false, 'urlParams' => ''];
                    $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                    $api->setData($post_data);
                    $refundResponse = $api->sendHttpRequest();

                    $caseResponse             = new EbayCaseResponse();
                    $caseResponse->content    = '';
                    $caseResponse->type       = 1;
                    $caseResponse->account_id = $this->account_id;
                    $caseResponse->case_id    = $caseModel->case_id;
                    if (empty($refundResponse)) {
                        $caseResponse->status = 0;
                        $caseResponse->error  = '调用香港服务器无响应';
                    } else {
                        if (in_array($refundResponse->code, [200, 201, 202])) {
                            $refundResponse              = json_decode($refundResponse->response);
                            $caseResponse->status        = 1;
                            $caseResponse->error         = '';
                            $caseResponse->refund_source = isset($refundResponse->refundResult->refundSource) ? $refundResponse->refundResult->refundSource : '';
                            $caseResponse->refund_status = $refundResponse->refundResult->refundStatus;
                            //建立退款售后处理单
                            /*if($refundResponse->refundResult->refundStatus == 'SUCCESS')
                            {
                                $afterSalesOrderModel = new AfterSalesOrder();
                                $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                                $afterSalesOrderModel->transaction_id = $this->transaction_id;
                                $afterSalesOrderModel->type = AfterSalesOrder::ORDER_TYPE_REFUND;
                                if(empty($orderinfo))
                                {
                                    $orderinfo = Order::getOrderStack(Platform::PLATFORM_CODE_EB, '',$this->order_id);
                                    $orderinfo = Json::decode(Json::encode($orderinfo), true);
                                }
                                if(isset($orderinfo['info']) && $orderinfo['info']['complete_status'] >= Order::COMPLETE_STATUS_PARTIAL_SHIP)
                                    $reason = RefundReturnReason::REASON_NOT_RECEIVE;
                                else
                                    $reason = RefundReturnReason::REASON_NOT_SEND;

                                $afterSalesOrderModel->reason_id = $reason;
                                $afterSalesOrderModel->platform_code = Platform::PLATFORM_CODE_EB;
                                $afterSalesOrderModel->order_id = $this->order_id;

                                $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                                $afterSalesOrderModel->approver = 'system';
                                $afterSalesOrderModel->approve_time = date('Y-m-d H:i:s');
                                $afterSalesOrderModel->buyer_id = $this->buyer;
                                $afterSalesOrderModel->account_name = Account::getAccountName($caseModel->account_id,Platform::PLATFORM_CODE_EB);

                                $afterSaleOrderRefund = new AfterSalesRefund();
                                $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
                                $afterSaleOrderRefund->refund_amount = $caseModel->claim_amount;
                                $afterSaleOrderRefund->currency = $caseModel->currency;
                                $afterSaleOrderRefund->transaction_id = $caseModel->transaction_id;
                                $afterSaleOrderRefund->order_id = $this->order_id;
                                $afterSaleOrderRefund->platform_code = Platform::PLATFORM_CODE_EB;
                                $afterSaleOrderRefund->order_amount = $caseModel->claim_amount;
                                $afterSaleOrderRefund->reason_code = $afterSalesOrderModel->reason_id;
                                $afterSaleOrderRefund->refund_time = date('Y-m-d H:i:s');
                                $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                            }*/
                        } else {
                            $caseResponse->status = 0;
                            $caseResponse->error  = $refundResponse->response;
                        }
                    }

                    try {
                        // 保存退款结果
                        $caseResponse->save();
                        // 保存case到主表
                        $caseModel->save();
                    } catch (\Exception $e) {
                        $caseResponse             = new EbayCaseResponse();
                        $caseResponse->content    = '';
                        $caseResponse->type       = 1;
                        $caseResponse->status     = 0;
                        $caseResponse->account_id = $this->account_id;
                        $caseResponse->case_id    = $caseModel->case_id;
                        $caseResponse->error      = $e->getMessage() . "。未收到纠纷{$this->inquiry_id}个案编号{$caseModel->case_id}保存失败！";
                        $caseResponse->save();
                    }

                    if ($refundResponse->refundResult->refundStatus != 'SUCCESS') {
                        $info .= ' inquiryID:' . $data->inquiryId . '升级自动退款出错。';
                    }
                    /*elseif(isset($afterSalesOrderModel))
                    {
                        $transaction = EbayInquiry::getDb()->beginTransaction();

                        try{
                            $flag_after = $afterSalesOrderModel->save();
                            if(!$flag_after)
                            {
                                $info .= ' inquiryID:'.$data->inquiryId.'升级自动退款建立售后处理单出错。';
                            }
                            elseif(isset($afterSaleOrderRefund))
                            {
                                $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                                $flag_after = $afterSaleOrderRefund->save();
                                if(!$flag_after)
                                    $info .= ' inquiryID:'.$data->inquiryId.'升级自动退款建立售后退款单出错。';
                            }
                        }catch(Exception $e){
                            $flag_after = false;
                            $info.= 'inquiryID:'.$data->inquiryId.'升级自动退款出错。'.$e->getMessage();
                        }
                        if($flag_after == true)
                            $transaction->commit();
                        else
                            $transaction->rollBack();
                    }*/
                }
            }
        }
        $transaction1 = self::getDb()->beginTransaction();
        if ($flag) {
            $this->eligible_for_appeal           = isset($data->inquiryDetails->appealDetails->eligibleForAppeal) ? array_search($data->inquiryDetails->appealDetails->eligibleForAppeal, EbayInquiry::$eligibleForAppealMap) : 0;
            $this->creation_date                 = explode('.', str_replace('T', ' ', $data->inquiryDetails->creationDate->value))[0];
            $this->escalation_date               = isset($data->inquiryDetails->escalationDate) ? explode('.', str_replace('T', ' ', $data->inquiryDetails->escalationDate->value))[0] : null;
            $this->expiration_date               = isset($data->inquiryDetails->expirationDate) ? explode('.', str_replace('T', ' ', $data->inquiryDetails->expirationDate->value))[0] : null;
            $this->last_buyer_respdate           = isset($data->inquiryDetails->lastBuyerRespDate) ? explode('.', str_replace('T', ' ', $data->inquiryDetails->lastBuyerRespDate->value))[0] : null;
            $this->buyer_final_accept_refund_amt = $data->inquiryDetails->refundAmounts->buyerFinalAcceptRefundAmt->value;
            if (empty($this->currency))
                $this->currency = $data->inquiryDetails->refundAmounts->buyerFinalAcceptRefundAmt->currency;
            $this->buyer_init_expect_refund_amt = $data->inquiryDetails->refundAmounts->buyerInitExpectRefundAmt->value;
            if (empty($this->currency))
                $this->currency = $data->inquiryDetails->refundAmounts->buyerInitExpectRefundAmt->currency;
            $this->international_refund_amount = isset($data->inquiryDetails->refundAmounts->internationalRefundAmount) ? $data->inquiryDetails->refundAmounts->internationalRefundAmount->value : 0;
            $this->refund_amount               = isset($data->inquiryDetails->refundAmounts->refundAmount) ? $data->inquiryDetails->refundAmounts->refundAmount->value : 0;
            $this->refund_deadline_date        = explode('.', str_replace('T', ' ', $data->inquiryDetails->refundDeadlineDate->value))[0];
            $this->total_amount                = $data->inquiryDetails->totalAmount->value;
            if (empty($this->currency))
                $this->currency = $data->inquiryDetails->totalAmount->currency;
            $this->inquiry_id       = $data->inquiryId;
            $this->inquiry_quantity = $data->inquiryQuantity;
            $this->item_picture_url = isset($data->itemDetails->itemPictureUrl) ? $data->itemDetails->itemPictureUrl : '';
            $this->item_price       = $data->itemDetails->itemPrice->value;
            if (empty($this->currency))
                $this->currency = $data->itemDetails->itemPrice->currency;
            $this->item_title                   = $data->itemDetails->itemTitle;
            $this->view_purchased_item_url      = isset($data->itemDetails->viewPurchasedItemUrl) ? $data->itemDetails->viewPurchasedItemUrl : '';
            $this->item_id                      = $data->itemId;
            $this->seller_make_it_right_by_date = explode('.', str_replace('T', ' ', $data->sellerMakeItRightByDate->value))[0];
            $this->state                        = $data->state;
            $this->status                       = $data->status;
            $this->transaction_id               = isset($data->transactionId) ? $data->transactionId : '';
            $this->view_pp_trasanction_url      = isset($data->viewPPTrasanctionUrl) ? $data->viewPPTrasanctionUrl : '';
            $this->update_time                  = date('Y-m-d H:i:s');
            $this->is_deal                      = 1;

            try {
                $flag = $this->save();
                if (!$flag)
                    $info .= VHelper::getModelErrors($this);

            } catch (Exception $e) {
                $flag = false;
                $info .= $e->getMessage();
            }
        }

        if ($flag) {
            EbayInquiryHistory::deleteAll(['inquiry_table_id' => $this->id]);
            foreach ($data->inquiryHistoryDetails->history as $history) {
                $inquiryHistoryModel                   = new EbayInquiryHistory();
                $inquiryHistoryModel->inquiry_table_id = $this->id;
                $inquiryHistoryModel->inquiry_id       = $data->inquiryId;
                $inquiryHistoryModel->action           = $history->action;
                $inquiryHistoryModel->actor            = array_search(trim($history->actor), EbayInquiry::$initiatorMap);
                $inquiryHistoryModel->date             = explode('.', str_replace('T', ' ', $history->date->value))[0];
                $inquiryHistoryModel->description      = isset($history->description) ? $history->description : '';
                try {
                    $flag = $inquiryHistoryModel->save();
                    if (!$flag)
                        $info .= VHelper::getModelErrors($inquiryHistoryModel);
                } catch (Exception $e) {
                    $flag = false;
                    $info .= $e->getMessage();
                }
                if (!$flag)
                    break;
            }
        }
        if ($flag) {
            $transaction1->commit();
        } else
            $transaction1->rollBack();
        return ['flag' => $flag, 'info' => $info];
    }

    /**
     * 获取指定id的纠纷id:inquiry_id
     * param string 主键id
     * return string inquiry_id
     **/
    public static function getInqueryByID($id)
    {
        if (empty($id)) {
            return 0;
        }

        $inquiry_id = self::findOne($id)->inquiry_id;

        return $inquiry_id;
    }

    /** 判断指定交易号是否有纠纷 **/
    public static function whetherExist($transaction_id)
    {
        if (empty($transaction_id)) {
            return false;
        }

        $model = self::find()->where(['transaction_id' => $transaction_id])->one();

        //指定订单存在纠纷
        if (!empty($model)) {
            return true;
        }

        //指定订单不存在纠纷
        return false;
    }

    /**
     * 获取指定交易号纠纷等级
     * param string 交易号id
     * return int 0-无纠纷，1-已经关闭，2-已解决，3-有纠纷，4-升级
     **/
    public static function disputeLevel($platform_order_id,$obj = '')
    {
        if (empty($platform_order_id)) {
            return [0, 0, 0];
        }

        $return_array = array();
        
        //是否存在子订单信息
        $sonPlatformOrderId = isset($obj['platform_order_ids']) ? $obj['platform_order_ids'] : [];
        if(!empty($sonPlatformOrderId)){
            //查询子订单是否有纠纷信息
            $models = self::find()->where(['in','platform_order_id',$sonPlatformOrderId])->all();
            if(empty($models)){
                $res = [];
                foreach ($models as $val) {
                    $arr = explode('-',$val);
                    if(!empty($arr)){
                        $itemId = isset($arr[0]) ? $arr[0] : 0;
                        $transactionId = isset($arr[1]) ? $arr[1] : 0;
                        $res[] = self::find()->where(['item_id'=> $itemId,'transaction_id'=>$transactionId])->one();
                    }
                }
                $models = $res;
            }
        }else{
            $models       = self::find()->where(['platform_order_id' => $platform_order_id])->all();
            if (empty($models)) {
                $arr = explode('-', $platform_order_id);
                if (!empty($arr)) {
                    $itemId        = isset($arr[0]) ? $arr[0] : 0;
                    $transactionId = isset($arr[1]) ? $arr[1] : 0;
                    $models        = self::find()->where(['item_id' => $itemId, 'transaction_id' => $transactionId])->all();
                }
            }
        }

        //指定订单不存在纠纷
        if (empty($models)) return $return_array;

        foreach ($models as $key => $model) {
            // 无上诉时间判断1-关闭、3-纠纷
            if ($model->escalation_date == null) {
                if ($model->state == 'CLOSED')
                    $return_array[$key] = [1, $model->id, $model->inquiry_id];
                else
                    $return_array[$key] = [3, $model->id, $model->inquiry_id];
            } // 判断2-已解决和4-升级
            else {
                if ($model->state != 'CLOSED')
                    $return_array[$key] = [4, $model->id, $model->inquiry_id];
                else
                    $return_array[$key] = [2, $model->id, $model->inquiry_id];
            }
        }
        return $return_array;
    }

}