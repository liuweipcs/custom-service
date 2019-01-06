<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/12 0012
 * Time: 下午 5:40
 */

namespace app\modules\mails\models;


use app\common\VHelper;
use app\modules\accounts\models\EbayCaseRefund;
use app\modules\accounts\models\UserAccount;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\orders\models\Order;
use yii\helpers\Json;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\AutoCode;
use app\modules\aftersales\models\AfterSalesRefund;
use yii\helpers\Url;
use app\modules\orders\models\OrderEbay;

class EbayReturnsRequests extends MailsModel
{
    protected $ebayAccountModel;
    protected $isDealMaps = array('SYSTEM_CREATE_RETURN', 'SUBMIT_FILE', 'NOTIFIED_DELIVERED', 'BUYER_SEND_MESSAGE', 'BUYER_PROVIDE_TRACKING_INFO', 'BUYER_DECLINE_PARTIAL_REFUND', 'BUYER_CREATE_RETURN', 'REMINDER_FOR_SHIPPING', 'REMINDER_FOR_REFUND_NO_SHIPPING', 'REMINDER_FOR_REFUND');

    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%ebay_returns_requests}}';
    }

    /**
     * 额外属性
     * @return array
     */
    public function attributes()
    {
        $attributes   = parent::attributes();
        $attributes[] = 'order_type';
        $attributes[] = 'order_id';
        $attributes[] = 'son_order_id';
        $attributes[] = 'parent_order_id';
        $attributes[] = 'old_account_id';
        return $attributes;
    }

    /**
     * 属性标签
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'buyer_login_name'               => '买家',
            'buyer_response_activity_due'    => '买家下步操作',
            'buyer_response_date'            => '买家回复截止时间',
            'currency'                       => '货币',
            'actual_refund_amount'           => '实际退款金额',
            'buyer_estimated_refund_amount'  => '买家预计退款金额',
            'seller_estimated_refund_amount' => '卖家预计退款金额',
            'return_reason'                  => '原因',
            'current_type'                   => '买家期望',
            'return_comments'                => '留言',
            'return_creation_date'           => '创建时间',
            'item_id'                        => 'ItemID',
            'return_quantity'                => '退货数量',
            'transaction_id'                 => '交易ID',
            'platform_order_id'              => '平台订单号',
            'disposition_rule_triggered'     => '触发处理规则',
            'buyer_escalation_eligible'      => '买家是否有升级资格',
            'buyer_escalation_end_time'      => '买家升级截止时间',
            'buyer_escalation_start_time'    => '买家最早能升级时间',
            'case_id'                        => '案件ID',
            'seller_escalation_eligible'     => '卖家是否有升级资格',
            'seller_escalation_end_time'     => '卖家升级截止时间',
            'seller_escalation_start_time'   => '卖家最早能升级时间',
            'return_id'                      => 'Return Id',
            'return_policy_rma_required'     => '需要提供RMA码',
            'seller_login_name'              => '卖家',
            'seller_response_activity_due'   => '卖家下步操作',
            'seller_response_date'           => '买家回复截止时间',
            'state'                          => '状况',
            'status'                         => '状态',
            'account_id'                     => 'eBay账号',
            'auto_refund'                    => '升级自动退款',
            'update_time'                    => '更新时间',
            'is_deal'                        => '是否已经处理',
            'is_transition'                  => '处理过渡中',
            'order_id'                       => '订单号',
            'order_type'                     => '订单类型',
        ];
    }

    /**
     * 验证规则
     * @return array
     */
    public function rules()
    {
        return [
            ['auto_refund', 'default', 'value' => 0],
            ['is_deal','default','value'=>0],
            ['is_transition', 'default', 'value' => 1],
            [['buyer_login_name', 'buyer_response_activity_due', 'buyer_response_date', 'currency', 'actual_refund_amount', 'buyer_estimated_refund_amount', 'seller_estimated_refund_amount', 'return_reason', 'current_type', 'return_comments', 'return_creation_date', 'item_id', 'return_quantity', 'transaction_id', 'platform_order_id', 'disposition_rule_triggered', 'buyer_escalation_eligible', 'buyer_escalation_end_time', 'buyer_escalation_start_time', 'case_id', 'seller_escalation_eligible', 'seller_escalation_end_time', 'seller_escalation_start_time', 'return_id', 'return_policy_rma_required', 'seller_login_name', 'seller_response_activity_due', 'seller_response_date', 'state', 'status', 'auto_refund', 'update_time', 'account_id', 'create_by', 'create_time', 'modify_by', 'modify_time', 'buyer_address', 'seller_address'], 'safe'],
            [['order_id'], 'safe'],
        ];
    }

    public function dynamicChangeFilter(&$filterOptions, &$query, &$params)
    {
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 'return_creation_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 'return_creation_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 'return_creation_date', $params['end_time']]);
        }
    }

    /**
     * ebay纠纷 退款退货查询
     * @param array $params
     * @return \yii\data\ActiveDataProvider
     */
    public function searchList($params = [])
    {
        $query              = self::find();
        $sort               = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'return_creation_date' => SORT_DESC
        );
        if (isset($params['status'])) {
            switch ($params['status']) {
                case 'wait_seller':
                    $query->where('status in ("PARTIAL_REFUND_REQUESTED","REPLACEMENT_LABEL_REQUESTED","REPLACEMENT_REQUESTED","REPLACEMENT_WAITING_FOR_RMA","RETURN_LABEL_REQUESTED","RETURN_REQUESTED","RETURN_REQUESTED_TIMEOUT","WAITING_FOR_RETURN_LABEL","WAITING_FOR_RMA")');
                    break;
                case 'closed':
                    $query->where('status in ("CLOSED","REPLACEMENT_CLOSED","REPLACEMENT_DELIVERED","RETURN_REJECTED")');
                    break;
                case 'other':
                    $query->where('status in ("ESCALATED","ITEM_DELIVERED","ITEM_SHIPPED","LESS_THAN_A_FULL_REFUND_ISSUED","PARTIAL_REFUND_DECLINED","PARTIAL_REFUND_INITIATED","READY_FOR_SHIPPING","REPLACED","REPLACEMENT_SHIPPED","REPLACEMENT_STARTED","UNKNOWN")');
            }
            $params['status'] = '';
        }

        // 只能查询到客服绑定账号的纠纷
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

        $query->andWhere(['in', 'account_id', $accountIds]);

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

    /**
     * 模型修改
     * @param \app\components\unknown $models
     * @return \app\components\unknown|void
     */
    public function addition(&$models)
    {
        foreach ($models as $model) {
            isset($model->buyer_response_date) && $model->buyer_response_date = date('Y-m-d H:i:s', strtotime($model->buyer_response_date) + 28800);
            isset($model->return_creation_date) && $model->return_creation_date = date('Y-m-d H:i:s', strtotime($model->return_creation_date) + 28800);
            isset($model->buyer_escalation_end_time) && $model->buyer_escalation_end_time = date('Y-m-d H:i:s', strtotime($model->buyer_escalation_end_time) + 28800);
            isset($model->buyer_escalation_start_time) && $model->buyer_escalation_start_time = date('Y-m-d H:i:s', strtotime($model->buyer_escalation_start_time) + 28800);
            isset($model->seller_escalation_end_time) && $model->seller_escalation_end_time = date('Y-m-d H:i:s', strtotime($model->seller_escalation_end_time) + 28800);
            isset($model->seller_escalation_start_time) && $model->seller_escalation_start_time = date('Y-m-d H:i:s', strtotime($model->seller_escalation_start_time) + 28800);
            isset($model->seller_response_date) && $model->seller_response_date = date('Y-m-d H:i:s', strtotime($model->seller_response_date) + 28800);
            $account_info                         = Account::getHistoryAccountInfo($model->old_account_id, 'EB');
            $accountName                          = Account::findOne((int)$model->account_id)->account_name;
            $model->actual_refund_amount          = $model->actual_refund_amount . ' ' . $model->currency;
            $model->buyer_estimated_refund_amount = $model->buyer_estimated_refund_amount . ' ' . $model->currency;
//            $ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
            $model->account_id = $accountName;
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
    }

    /**
     * 查询过滤
     * @return \app\components\multitype|array
     */
    public function filterOptions()
    {
        return [
            [
                'name'   => 'return_id',
                'type'   => 'text',
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
                'name'   => 'buyer_login_name',
                'type'   => 'text',
//                'data' => Account::getIdNameKVList(),
                'search' => 'LIKE'
            ],
            [
                'name'   => 'sku',
                'type'   => 'text',
                'search' => '='
            ],
            [
                'name'   => 'state',
                'type'   => 'dropDownList',
                'data'   => self::getFieldList('state', 'state', 'state'),
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
                'name'   => 'is_deal',
                'type'   => 'dropDownList',
                'data'   => [0 => '否', 1 => '是'],
                'value'  => 0,
                'search' => '=',
            ],
            [
                'name'   => 'is_transition',
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
        ];
    }

    public function refreshApi()
    {
        set_time_limit(150);

        $memcache = \Yii::$app->memcache;

        $ebayAccountModel = Account::findOne((int)$this->account_id);
        $accountName      = $ebayAccountModel->account_name;
        $token            = $memcache->get($accountName);
        if (!$token) {
            $this->ebayAccountModel = $ebayAccountModel;
            $token                  = $this->ebayAccountModel->user_token;
            $memcache->set($accountName, $token, '', 600);
        }

        return $this->detailApi($token, '', 'https://api.ebay.com/post-order/v2/return/' . $this->return_id, 'get');
    }

    private function detailApi($token, $site, $serverUrl, $method)
    {
//        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
//        $response = $api->sendHttpRequest();
//        return $this->handleDetailResponse($response,$token);

        $transfer_ip = include \Yii::getAlias('@app') . '/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
        if (empty($transfer_ip))
            return ['flag' => false, 'info' => '中转服务器配置错误'];

        $post_data = ['serverUrl' => $serverUrl, 'authorization' => $token, 'data' => '', 'method' => $method, 'responseHeader' => false, 'urlParams' => ''];
        $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();

        if (empty($response)) {
            return ['flag' => 'false', 'info' => '香港服务器无响应'];
        } else {
            return $this->handleDetailResponse(json_decode($response->response), $token);
        }

    }

    private function handleDetailResponse($data, $token)
    {
        $historys = $data->detail->responseHistory;
        $summary  = $data->summary;
        $returnId = $data->summary->returnId;
        $flag     = true;
        $info     = '';

        $transfer_ip = include \Yii::getAlias('@app') . '/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
        if (empty($transfer_ip))
            return ['flag' => false, 'info' => '中转服务器配置错误'];

        if (isset($data->detail->files)) {
            $hasImgae     = count($data->detail->files);
            $nowImageNums = EbayReturnImage::find()->where(['return_id' => $returnId])->count();
//            $getImageApi = new PostOrderAPI($token,'','https://api.ebay.com/post-order/v2/return/'.$returnId.'/files','get');
//            $imageInfo = $getImageApi->sendHttpRequest();
            if ($nowImageNums != $hasImgae) {
                $serverUrl = 'https://api.ebay.com/post-order/v2/return/' . $returnId . '/files';
                $post_data = ['serverUrl' => $serverUrl, 'authorization' => $token, 'data' => '', 'method' => 'get', 'responseHeader' => false, 'urlParams' => ''];
                $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                $api->setData($post_data);
                $response = $api->sendHttpRequest();
                if (empty($response)) {
                    return ['flag' => false, 'info' => '香港服务器查询图片无响应'];
                } else {
                    if (in_array($response->code, [200, 201, 202])) {
                        $imageInfo = json_decode($response->response);
                    }
                }
            }

        }

        $this->buyer_response_activity_due = $data->summary->buyerResponseDue->activityDue;
        $this->buyer_response_date         = isset($data->summary->buyerResponseDue->respondByDate->value) ? explode('.', str_replace('T', ' ', $data->summary->buyerResponseDue->respondByDate->value))[0] : null;
        $this->buyer_address               = isset($data->detail->buyerAddress) ? serialize($data->detail->buyerAddress) : '';
        $this->seller_address              = isset($data->detail->sellerAddress) ? serialize($data->detail->sellerAddress) : '';
        if (empty($this->currency))
            $this->currency = $data->summary->buyerTotalRefund->actualRefundAmount->currency;
        if (isset($data->summary->buyerTotalRefund->actualRefundAmount))
            $this->actual_refund_amount = $data->summary->buyerTotalRefund->actualRefundAmount->value;
        $this->buyer_estimated_refund_amount = $data->summary->buyerTotalRefund->estimatedRefundAmount->value;
        if (empty($this->currency))
            $this->currency = $data->summary->buyerTotalRefund->estimatedRefundAmount->currency;
        $this->seller_estimated_refund_amount = $data->summary->sellerTotalRefund->estimatedRefundAmount->value;
        if (empty($this->currency))
            $this->currency = $data->summary->sellerTotalRefund->estimatedRefundAmount->currency;
        if (empty($this->currency))
            $this->currency = $data->summary->sellerTotalRefund->actualRefundAmount->currency;
        if (empty($this->actual_refund_amount))
            $this->actual_refund_amount = isset($data->summary->sellerTotalRefund->actualRefundAmount->value) ? $data->summary->sellerTotalRefund->actualRefundAmount->value : 0;
        $this->current_type = $data->summary->currentType;
        if (!empty($data->summary->disposition_rule_triggered))
            $this->disposition_rule_triggered = implode('|', (array)$data->summary->disposition_rule_triggered);
        if (isset($data->summary->escalationInfo->buyerEscalationEligibilityInfo->eligible))
            $this->buyer_escalation_eligible = $data->summary->escalationInfo->buyerEscalationEligibilityInfo->eligible;
        if (isset($data->summary->escalationInfo->buyerEscalationEligibilityInfo->endTime))
            $this->buyer_escalation_end_time = explode('.', str_replace('T', ' ', $data->summary->escalationInfo->buyerEscalationEligibilityInfo->endTime->value))[0];
        if (isset($data->summary->escalationInfo->buyerEscalationEligibilityInfo->startTime))
            $this->buyer_escalation_start_time = explode('.', str_replace('T', ' ', $data->summary->escalationInfo->buyerEscalationEligibilityInfo->startTime->value))[0];
        $this->has_image = isset($hasImgae) ? $hasImgae : 0;
        if (empty($this->platform_order_id) || empty($this->order_id)) {
            $platformOrderId = '';
            $orderinfo       = '';
            $order_id        = '';
            if ($data->summary->creationInfo->item->transactionId) {
                $orderinfo = Order::getOrderStackByTransactionId('EB', $data->summary->creationInfo->item->transactionId);
            } else {
                $platformOrderId = $data->summary->creationInfo->item->itemId . '-0';
                $orderinfo       = Order::getOrderStackByOrderId('EB', $platformOrderId);
            }
            if (!empty($orderinfo) && !empty($orderinfo->info)) {
                $orderinfo       = Json::decode(Json::encode($orderinfo), true);
                $platformOrderId = $orderinfo['info']['platform_order_id'];
                $totalPrice      = $orderinfo['info']['total_price'];
                $order_id        = $orderinfo['info']['order_id'];
            }
            $this->platform_order_id = $platformOrderId;
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

        if (isset($totalPrice)) {
            //$finalCNY = VHelper::getTargetCurrencyAmt($this->currency, $currency, $totalPrice);
            $finalCNY = VHelper::getTargetCurrencyAmtKefu($this->currency, $currency, $totalPrice);
        } else {
            //$finalCNY = VHelper::getTargetCurrencyAmt($this->currency, $currency, $this->buyer_estimated_refund_amount);
            $finalCNY = VHelper::getTargetCurrencyAmtKefu($this->currency, $currency, $this->buyer_estimated_refund_amount);
        }

        if ($is_refund && (int)$this->auto_refund == 0 && $this->case_id == '' && isset($data->summary->escalationInfo->caseId) && $finalCNY < $max_claim_amount) {
            $flag_refund = true;
            //升级case,自动退款
            // 查询升级信息
//            $searchApi = new PostOrderAPI($this->ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/casemanagement/search?return_id=' . $returnId, 'get');
//            $caseSearch = $searchApi->sendHttpRequest();

            $serverUrl = 'https://api.ebay.com/post-order/v2/casemanagement/search?return_id=' . $returnId;
            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $this->ebayAccountModel->user_token, 'data' => '', 'method' => 'get', 'responseHeader' => false, 'urlParams' => ''];
            $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
            $api->setData($post_data);
            $caseSearch = $api->sendHttpRequest();

            if (empty($caseSearch)) {
                $caseResponse             = new EbayCaseResponse();
                $caseResponse->content    = '';
                $caseResponse->type       = 1;
                $caseResponse->status     = 0;
                $caseResponse->account_id = $this->account_id;
                $caseResponse->error      = "退款退货纠纷{$returnId}获取升级信息香港服务器无响应";
                $caseResponse->save();
                $flag_refund = false;
            } else {
                if (in_array($caseSearch->code, [200, 201, 202])) {
                    $caseSearch = json_decode($caseSearch->response);
                    if (empty($caseSearch->members)) {
                        $caseResponse             = new EbayCaseResponse();
                        $caseResponse->content    = '';
                        $caseResponse->type       = 1;
                        $caseResponse->status     = 0;
                        $caseResponse->account_id = $this->account_id;
                        $caseResponse->error      = "退款退货纠纷{$returnId}未获取到升级信息";
                        $caseResponse->save();
                        $flag_refund = false;
                    } else {
                        $caseDetail = $caseSearch->members[0];
                    }
                } else {
                    $caseResponse             = new EbayCaseResponse();
                    $caseResponse->content    = '';
                    $caseResponse->type       = 1;
                    $caseResponse->status     = 0;
                    $caseResponse->account_id = $this->account_id;
                    $caseResponse->error      = "退款退货纠纷{$returnId}获取升级信息无响应";
                    $caseResponse->save();
                    $flag_refund = false;
                }


            }
            if ($flag_refund == true && isset($caseDetail->caseStatusEnum) && $caseDetail->caseStatusEnum != 'CLOSED' && $caseDetail->caseStatusEnum != 'CS_CLOSED') {
                $caseModel = EbayCase::findOne(['case_id' => $caseDetail->caseId]);
                if ($caseModel)
                    $flag_refund = false;

                if ($flag_refund) {
                    $caseModel                     = new EbayCase();
                    $caseModel->case_id            = isset($caseDetail->caseId) ? $caseDetail->caseId : '';
                    $caseModel->item_id            = isset($caseDetail->itemId) ? $caseDetail->itemId : '';
                    $caseModel->return_id          = $returnId;
                    $caseModel->case_type          = EbayCase::CASE_TYPE_REFUND;
                    $caseModel->transaction_id     = isset($caseDetail->transactionId) ? $caseDetail->transactionId : '';
                    $caseModel->buyer              = isset($caseDetail->buyer) ? $caseDetail->buyer : '';
                    $caseModel->account_id         = $this->account_id;
                    $caseModel->status             = isset($caseDetail->caseStatusEnum) ? $caseDetail->caseStatusEnum : '';
                    $caseModel->claim_amount       = isset($caseDetail->claimAmount) && !empty($caseDetail->claimAmount->value) ? $caseDetail->claimAmount->value : '';
                    $caseModel->currency           = isset($caseDetail->claimAmount) && !empty($caseDetail->claimAmount->currency) ? $caseDetail->claimAmount->currency : '';
                    $caseModel->creation_date      = isset($caseDetail->creationDate) && !empty($caseDetail->creationDate->value) ? date('Y-m-d H:i:s', strtotime($caseDetail->creationDate->value)) : '';
                    $caseModel->last_modified_date = isset($caseDetail->lastModifiedDate) && !empty($caseDetail->lastModifiedDate->value) ? date('Y-m-d H:i:s', strtotime($caseDetail->lastModifiedDate->value)) : '';

//                    $refundApi = new PostOrderAPI($this->ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/casemanagement/' . $summary->escalationInfo->caseId . '/issue_refund', 'post');
//                    $message = ['comments' => ['content' => '']];
//                    $refundApi->setData($message);
//                    $refundResponse = $refundApi->sendHttpRequest();

                    $serverUrl = 'https://api.ebay.com/post-order/v2/casemanagement/' . $summary->escalationInfo->caseId . '/issue_refund';
                    $post_data = ['serverUrl' => $serverUrl, 'authorization' => $this->ebayAccountModel->user_token, 'data' => json_encode(['comments' => ['content' => '']]), 'method' => 'post', 'responseHeader' => false, 'urlParams' => ''];
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
                        $caseResponse->error  = '自动退款调用接口失败,香港服务器无返回值';
                    } else {
                        if (in_array($refundResponse->code, [200, 201, 202])) {
                            $refundResponse              = json_decode($refundResponse->response);
                            $caseResponse->status        = 1;
                            $caseResponse->error         = '';
                            $caseResponse->refund_source = isset($refundResponse->refundResult->refundSource) ? $refundResponse->refundResult->refundSource : '';
                            $caseResponse->refund_status = $refundResponse->refundResult->refundStatus;
                            //建立退款售后处理单
                            if ($refundResponse->refundResult->refundStatus == 'SUCCESS') {
                                $afterSalesOrderModel                 = new AfterSalesOrder();
                                $afterSalesOrderModel->after_sale_id  = AutoCode::getCode('after_sales_order');
                                $afterSalesOrderModel->transaction_id = $this->transaction_id;
                                $afterSalesOrderModel->type           = AfterSalesOrder::ORDER_TYPE_REFUND;
                                $afterSalesOrderModel->reason_id      = isset(RefundReturnReason::$returnReasonMaps[$this->return_reason]) ? RefundReturnReason::$returnReasonMaps[$this->return_reason] : 27;
                                $afterSalesOrderModel->platform_code  = Platform::PLATFORM_CODE_EB;
                                $afterSalesOrderModel->order_id       = $this->order_id;
                                $afterSalesOrderModel->account_id     = $this->account_id;

                                $afterSalesOrderModel->status       = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                                $afterSalesOrderModel->approver     = 'system';
                                $afterSalesOrderModel->approve_time = date('Y-m-d H:i:s');
                                $afterSalesOrderModel->buyer_id     = $this->buyer_login_name;
                                $afterSalesOrderModel->account_id   = $this->account_id;
                                $afterSalesOrderModel->account_name = Account::getAccountName($caseModel->account_id, Platform::PLATFORM_CODE_EB);

                                $afterSaleOrderRefund                 = new AfterSalesRefund();
                                $afterSaleOrderRefund->refund_type    = AfterSalesRefund::REFUND_TYPE_FULL;
                                $afterSaleOrderRefund->refund_amount  = $caseModel->claim_amount;
                                $afterSaleOrderRefund->currency       = $caseModel->currency;
                                $afterSaleOrderRefund->transaction_id = $caseModel->transaction_id;
                                $afterSaleOrderRefund->order_id       = $this->order_id;
                                $afterSaleOrderRefund->platform_code  = Platform::PLATFORM_CODE_EB;
                                $afterSaleOrderRefund->order_amount   = $caseModel->claim_amount;
                                $afterSaleOrderRefund->reason_code    = $afterSalesOrderModel->reason_id;
                                $afterSaleOrderRefund->refund_time    = date('Y-m-d H:i:s');
                                $afterSaleOrderRefund->refund_status  = AfterSalesRefund::REFUND_STATUS_FINISH;
                            }
                        } else {
                            $caseResponse->status = 0;
                            $caseResponse->error  = '自动退款调用接口失败,无返回值';
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
                        $caseResponse->error      = $e->getMessage() . "。退款退货纠纷{$this->return_id}个案编号{$caseModel->case_id}保存失败！";
                        $caseResponse->save();
                    }

                    if ($refundResponse->refundResult->refundStatus != 'SUCCESS') {
                        $info .= '[错误码：' . $this->errorCode . '。returnID:' . $returnId . '升级自动退款出错。';
                    }
                    /*elseif (isset($afterSalesOrderModel)) {
                        $transaction = EbayReturnsRequests::getDb()->beginTransaction();

                        try {
                            $flag_after = $afterSalesOrderModel->save();
                            if (!$flag_after) {
                                $info .= '[错误码：' . $this->errorCode . '。returnID:' . $returnId . '升级自动退款建立售后处理单出错。' . VHelper::getModelErrors($afterSalesOrderModel) . ']';
                            } elseif (isset($afterSaleOrderRefund)) {
                                $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                                $flag_after = $afterSaleOrderRefund->save();
                                if (!$flag_after)
                                    $info .= '[错误码：' . $this->errorCode . '。returnID:' . $returnId . '升级自动退款建立售后退款单出错。' . VHelper::getModelErrors($afterSaleOrderRefund) . ']';
                            }
                        } catch (Exception $e) {
                            $flag_after = false;
                            $info .= '[错误码：' . $this->errorCode . '。returnID:' . $returnId . '升级自动退款出错。' . $e->getMessage() . ']';
                        }
                        if ($flag_after == true)
                            $transaction->commit();
                        else
                            $transaction->rollBack();
                    }*/
                }
            }

        }
        if ($flag) {
            if (isset($data->summary->escalationInfo->caseId))
                $this->case_id = $data->summary->escalationInfo->caseId;
            if (isset($data->summary->escalationInfo->sellerEscalationEligibilityInfo->eligible))
                $this->seller_escalation_eligible = $data->summary->escalationInfo->sellerEscalationEligibilityInfo->eligible;

            if (isset($data->summary->escalationInfo->sellerEscalationEligibilityInfo->endTime))
                $this->seller_escalation_end_time = explode('.', str_replace('T', ' ', $data->summary->escalationInfo->sellerEscalationEligibilityInfo->endTime->value))[0];
            if (isset($data->summary->escalationInfo->sellerEscalationEligibilityInfo->startTime))
                $this->seller_escalation_start_time = explode('.', str_replace('T', ' ', $data->summary->escalationInfo->sellerEscalationEligibilityInfo->startTime->value))[0];
            if (isset($data->summary->returnPolicy->sellerEscalationEligibilityInfo->startTime->value))
                $this->return_policy_rma_required = explode('.', str_replace('T', ' ', $data->summary->returnPolicy->sellerEscalationEligibilityInfo->startTime->value))[0];
            $this->seller_response_activity_due = $data->summary->sellerResponseDue->activityDue;
            $this->seller_response_date         = explode('.', str_replace('T', ' ', $data->summary->sellerResponseDue->respondByDate->value))[0];
            $this->state                        = $data->summary->state;
            $this->status                       = $data->summary->status;
            $this->update_time                  = date('Y-m-d H:i:s');
            $this->is_transition                = 1;
            if (!isset($transaction))
                $transaction = self::getDb()->beginTransaction();
            try {
                $flag = $this->save();
                if (!$flag)
                    $info .= VHelper::getModelErrors($this);
            } catch (Exception $e) {
                $flag = false;
                $info .= $e->getMessage();
            }
        }
        if ($flag && isset($imageInfo)) {
            EbayReturnImage::deleteAll(['return_id' => $returnId]);
            foreach ($imageInfo->files as $image) {
                $returnImageModel                = new EbayReturnImage();
                $returnImageModel->return_id     = $returnId;
                $returnImageModel->file_id       = $image->fileId;
                $returnImageModel->file_purpose  = $image->filePurpose;
                $returnImageModel->file_purpose  = $image->filePurpose;
                $returnImageModel->creation_date = explode('.', str_replace('T', ' ', $image->creationDate->value))[0];
                $path                            = 'uploads/ebay_return/' . str_replace('-', '/', explode('T', $returnImageModel->creation_date)[0]);
                if (!is_dir($path))
                    mkdir($path, 0760, true);
                $filePath = $path . '/' . $image->fileId . '_return.' . $image->fileFormat;
                file_put_contents($filePath, base64_decode($image->fileData));
                $returnImageModel->file_path = $filePath;
                $resizeFilePath              = $path . '/' . $image->fileId . '_return_resize.' . $image->fileFormat;
                file_put_contents($resizeFilePath, base64_decode($image->resizedFileData));
                $returnImageModel->resize_file_path = $resizeFilePath;
                $returnImageModel->file_format      = $image->fileFormat;
                $returnImageModel->submitter        = $image->submitter;
                $returnImageModel->file_status      = $image->fileStatus;
                try {
                    $flag = $returnImageModel->save();
                    if (!$flag)
                        $info .= VHelper::getModelErrors($returnImageModel);
                } catch (Exception $e) {
                    $flag = false;
                    $info .= $e->getMessage();
                }
                if (!$flag)
                    break;
            }
        }
        if ($flag && !empty($historys)) {
            EbayReturnsRequestsDetail::deleteAll(['return_id' => $returnId]);
            // 最新状态
            $lastStatus = '';
            // 次新状态
            $secondStatus = '';
            // 是否需要更新售后单退款状态
            $is_after_refund = false;
            //add by allen 关闭的纠纷判断 【如果卖家提供了部分退款 并且客户接受超时的条件下 把售后单(退款单状态改成退款失败)】 2018-05-29
            $isClose                     = 0;//纠纷是否关闭
            $isSellerOfferPartialRefund  = 0;//是否卖家提供部分退款
            $isTimeOutForAuthorize       = 0;//授权是否超时
            $isTimeOutForEscalation      = 0;//是否超时退出
            $isPartialRefundFailed       = 0;//部分退款失败 state
            $isPARTIAL_REFUND_FAILED     = 0;//部分退款失败 status
            $isPartialRefundDeclined     = 0;//客服拒绝接受部分退款
            $isBuyerAcceptsPartialRefund = 0;//客户接受部分退款
            $isBuyerCloseReturn          = 0;//客人主动关闭纠纷
            foreach ($historys as $history) {
                //纠纷关闭
                if ($history->toState == 'CLOSED') {
                    $isClose = 1;//有状态为关闭的时候记录状态关闭
                }

                //部分退款失败
                if ($history->toState == 'PARTIAL_REFUND_FAILED') {
                    $isPartialRefundFailed = 1;
                }

                //客户拒绝部分退款
                if ($history->toState == 'PARTIAL_REFUND_DECLINED') {
                    $isPartialRefundDeclined = 1;
                }

                //客户接受部分退款
                if ($history->activity == 'BUYER_ACCEPTS_PARTIAL_REFUND') {
                    $isBuyerAcceptsPartialRefund = 1;
                }

                //卖家提供部分退款
                if ($history->activity == 'SELLER_OFFER_PARTIAL_REFUND') {
                    $isSellerOfferPartialRefund = 1;
                }

                //授权超时
                if ($history->activity == 'TIME_OUT_FOR_AUTHORIZE') {
                    $isTimeOutForAuthorize = 1;
                }

                //客人主动关闭纠纷
                if ($history->activity == 'BUYER_CLOSE_RETURN') {
                    $isBuyerCloseReturn = 1;
                }

                //超时退出
                if ($history->activity == 'TIME_OUT_FOR_ESCALATION') {
                    $isTimeOutForEscalation = 1;
                }

                //买家接受失败
                if ($history->activity == 'PARTIAL_REFUND_FAILED') {
                    $isPARTIAL_REFUND_FAILED = 1;
                }
                //add by allen 关闭的纠纷判断 【如果卖家提供了部分退款 并且客户接受超时的条件下 把售后单(退款单状态改成退款失败)】 2018-05-29 end

                $detailModel                      = new EbayReturnsRequestsDetail();
                $detailModel->return_id           = $returnId;
                $detailModel->activity            = $history->activity;
                $detailModel->author              = $history->author;
                $detailModel->creation_date_value = explode('.', str_replace('T', ' ', $history->creationDate->value))[0];
                $detailModel->from_state          = $history->fromState;
                $detailModel->to_state            = $history->toState;
//                if($detailModel->activity == 'BUYER_ACCEPTS_PARTIAL_REFUND')
//                {
//                    $is_after_refund = true;
//                    $to_state = $detailModel->to_state;
//                }
//                elseif($detailModel->activity == 'BUYER_DECLINE_PARTIAL_REFUND')
//                {
//                    $is_after_refund = true;
//                    $to_state = $detailModel->to_state;
//                }
//                findClass($history,1,0);
//                ob_flush();
//                flush();
                $detailModel->notes                              = isset($history->notes) ? $history->notes : '';
                $detailModel->carrier_used                       = isset($history->attributes->carrierUsed) ? $history->attributes->carrierUsed : '';
                $detailModel->escalate_reason                    = isset($history->attributes->escalateReason) ? $history->attributes->escalateReason : '';
                $detailModel->money_movement_ref                 = isset($history->attributes->moneyMovementRef) ? $history->attributes->moneyMovementRef->idref : '';
                $detailModel->partial_refund_amount              = isset($history->attributes->partialRefundAmount) ? $history->attributes->partialRefundAmount->value : 0;
                $detailModel->currency                           = isset($history->attributes->partialRefundAmount) ? $history->attributes->partialRefundAmount->currency : '';
                $detailModel->email                              = isset($history->attributes->toEmailAddress) ? $history->attributes->toEmailAddress : '';
                $detailModel->tracking_uumber                    = isset($history->attributes->trackingNumber) ? $history->attributes->trackingNumber : '';
                $detailModel->address_address_line1              = isset($history->attributes->attributes->sellerReturnAddress->addressLine1) ? $history->attributes->attributes->sellerReturnAddress->addressLine1 : '';
                $detailModel->address_address_line2              = isset($history->attributes->attributes->sellerReturnAddress->addressLine2) ? $history->attributes->attributes->sellerReturnAddress->addressLine2 : '';
                $detailModel->address_address_type               = isset($history->attributes->attributes->sellerReturnAddress->addressType) ? $history->attributes->attributes->sellerReturnAddress->addressType : '';
                $detailModel->address_city                       = isset($history->attributes->attributes->sellerReturnAddress->city) ? $history->attributes->attributes->sellerReturnAddress->city : '';
                $detailModel->address_country                    = isset($history->attributes->attributes->sellerReturnAddress->country) ? $history->attributes->attributes->sellerReturnAddress->country : '';
                $detailModel->address_county                     = isset($history->attributes->attributes->sellerReturnAddress->county) ? $history->attributes->attributes->sellerReturnAddress->county : '';
                $detailModel->address_is_transliterated          = isset($history->attributes->attributes->sellerReturnAddress->address_is_transliterated) ? (int)$history->attributes->attributes->sellerReturnAddress->address_is_transliterated : '';
                $detailModel->address_national_region            = isset($history->attributes->attributes->sellerReturnAddress->nationalRegion) ? $history->attributes->attributes->sellerReturnAddress->nationalRegion : '';
                $detailModel->address_postal_code                = isset($history->attributes->attributes->sellerReturnAddress->postalCode) ? $history->attributes->attributes->sellerReturnAddress->postalCode : '';
                $detailModel->address_script                     = isset($history->attributes->attributes->sellerReturnAddress->script) ? $history->attributes->attributes->sellerReturnAddress->script : '';
                $detailModel->address_state_or_province          = isset($history->attributes->attributes->sellerReturnAddress->stateOrProvince) ? $history->attributes->attributes->sellerReturnAddress->stateOrProvince : '';
                $detailModel->address_transliterated_from_script = isset($history->attributes->attributes->sellerReturnAddress->transliteratedFromScript) ? $history->attributes->attributes->sellerReturnAddress->transliteratedFromScript : '';
                $detailModel->address_world_region               = isset($history->attributes->attributes->sellerReturnAddress->worldRegion) ? $history->attributes->attributes->sellerReturnAddress->worldRegion : '';
                $secondStatus                                    = $lastStatus;
                $lastStatus                                      = $detailModel->activity;
                try {
                    $flag = $detailModel->save();
                    if (!$flag)
                        $info .= VHelper::getModelErrors($detailModel);
                } catch (Exception $e) {
                    $flag = false;
                    $info .= $e->getMessage();
                }
                if (!$flag)
                    break;
            }

//            if($isClose && $isSellerOfferPartialRefund && $isTimeOutForAuthorize){
//                $is_after_refund = true;
//                $changeColse = 1;
//            }

            if (isset($detailModel)) {
                if ($detailModel->author != 'SELLER' && in_array($detailModel->activity, $this->isDealMaps)) {
                    $this->is_deal = 0;
                } else {
                    $this->is_deal = 1;
                }
                if ($lastStatus == 'REMINDER_SELLER_TO_RESPOND' && in_array($secondStatus, $this->isDealMaps)) {
                    $this->is_deal = 0;
                }
                if ($lastStatus == 'TIME_OUT_FOR_AUTHORIZE' && $secondStatus == 'BUYER_SEND_MESSAGE') {
                    $this->is_deal = 0;
                }

                if ($lastStatus == 'AUTO_APPROVE_REMORSE') {
                    $this->is_deal = 0;
                }
//                if($lastStatus == 'TIME_OUT_FOR_ESCALATION' && $secondStatus == 'SELLER_OFFER_PARTIAL_REFUND')
//                {
//                    $is_after_refund = true;
//                    $to_state = 'PARTIAL_REFUND_DECLINED';
//                }
                $flag = $this->save();
            }

            //更新退款单状态
            if ($flag) {
                $after_sale_id = "";
                $changeArr     = [];
                //如果纠纷状态为关闭  最新状态或者历史状态中有出现过客户接受退款  则退款单改成退款成功
                if ($isClose && $isSellerOfferPartialRefund && $isBuyerAcceptsPartialRefund) {
                    $changeArr['refund_status'] = 3;//退款成功
                    $changeArr['refund_time']   = date('Y-m-d H:i:s');//退款时间
                    $changeArr['fail_reason']   = '';
                } else {
                    //纠纷状态关闭 最新状态中出现有卖家提供部分退款和接受失败  则退款单状态改为失败
                    if ($isClose && $isSellerOfferPartialRefund && $lastStatus == 'PARTIAL_REFUND_FAILED') {
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason']   = '买家接受失败';
                    }

                    //纠纷关闭    最新状态中出现有卖家提供部分退款和超时退出  退款单状态改为失败
                    if ($isClose && $isSellerOfferPartialRefund && $lastStatus == 'TIME_OUT_FOR_ESCALATION') {
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason']   = '超时退出';
                    }

                    //纠纷关闭 最新状态出现有卖家提供部分退款,授权超时状态时  退款单状态改为失败
                    if ($isClose && $isSellerOfferPartialRefund && $lastStatus == 'TIME_OUT_FOR_AUTHORIZE') {
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason']   = '授权超时';
                    }

                    //纠纷关闭 状态中有出现过卖家提交部分退款和客户主动关闭纠纷 则售后单状态改完失败
                    if ($isClose && $isBuyerCloseReturn && $isSellerOfferPartialRefund) {
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason']   = '客户关闭纠纷';
                    }

                    //客户接受失败
                    if ($isPartialRefundFailed) {
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason']   = '买家接受失败';
                    }

                    //客户拒绝
                    if ($isPartialRefundDeclined) {
                        $changeArr['refund_status'] = 4;//退款失败
                        $changeArr['fail_reason']   = '买家拒绝';
                    }
                }

                $after_sale_id = AfterSalesRefund::find()
                    ->select('after_sale_id')
                    ->where(['order_id' => $this->order_id])
                    ->andWhere(['<>', 'refund_status', AfterSalesRefund::REFUND_STATUS_FINISH])
                    ->andWhere(['>=', 'fail_count', '88'])
                    ->column();
                if (!empty($after_sale_id) && !empty($changeArr)) {
                    AfterSalesRefund::updateAll($changeArr, ['in', 'after_sale_id', $after_sale_id]);
                }
            }


//            if($flag && $is_after_refund)
//            {
//                $afterRefundModels = array();
//                $afterRefundModels = AfterSalesRefund::find()
//                    ->select('after_sale_id')
//                    ->where(['order_id'=>$this->order_id])
//                    ->andWhere(['<>','refund_status',AfterSalesRefund::REFUND_STATUS_FINISH])
//                    ->andWhere(['>=','fail_count','88'])
//                    ->column();
//                //如果卖家提供了部分退款 并且客户接受超时的条件下 把售后单(退款单状态改成退款失败) add by allen 2018-05-29
//                if($changeColse){
//                    $afterRefundModel = $afterRefundModels->column();
//                    $return_status = AfterSalesRefund::REFUND_STATUS_FAIL;
//                    $changeArr['fail_reason'] = 'the buyer declined the partial refund you offered.';
//                }elseif($to_state == 'CLOSED')
//                {
//                    $afterRefundModels->andWhere(['<>','refund_status',AfterSalesRefund::REFUND_STATUS_FINISH]);
//                    $afterRefundModel = $afterRefundModels->column();
//                    $return_status = AfterSalesRefund::REFUND_STATUS_FINISH;
//                }
//                elseif($to_state == 'PARTIAL_REFUND_DECLINED')
//                {
//                    $afterRefundModels->andWhere(['refund_status'=>AfterSalesRefund::REFUND_STATUS_WAIT_RECEIVE]);
//                    $afterRefundModel = $afterRefundModels->column();
//                    $return_status = AfterSalesRefund::REFUND_STATUS_FAIL;
//                    $changeArr['fail_reason'] = 'the buyer declined the partial refund you offered.';
//                    if($lastStatus == 'TIME_OUT_FOR_ESCALATION')
//                        $changeArr['fail_reason'] = 'TIME_OUT_FOR_ESCALATION';
//                }
//                
//                if($afterRefundModel)
//                {
//                    $changeArr['refund_status'] = $return_status;
//                    AfterSalesRefund::updateAll($changeArr,['in','after_sale_id',$afterRefundModel]);
//                }
//            }
        }
        if ($flag)
            $transaction->commit();
        else
            $transaction->rollBack();
        return ['flag' => $flag, 'info' => $info];
    }

    /**
     * 获取指定id的纠纷id:return_id
     * param string 主键id
     * return string return_id
     **/
    public static function getReturnIDByID($id)
    {
        if (empty($id)) {
            return 0;
        }

        $return_id = self::findOne($id)->return_id;

        return $return_id;
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
     * param string 平台订单id
     * return int 0-无纠纷，1-已经关闭，2-已解决，3-有纠纷，4-升级
     **/
    public static function disputeLevel($platform_order_id, $obj = '')
    {
        if (empty($platform_order_id)) {
            return [0, 0, 0];
        }

        $return_array = array();

        //是否存在子订单信息
        $sonPlatformOrderId = isset($obj['platform_order_ids']) ? $obj['platform_order_ids'] : [];
        if (!empty($sonPlatformOrderId)) {
            //查询子订单是否有纠纷信息
            $models = self::find()->where(['in', 'platform_order_id', $sonPlatformOrderId])->all();
            if (empty($models)) {
                $res = [];
                foreach ($models as $val) {
                    $arr = explode('-', $val);
                    if (!empty($arr)) {
                        $itemId        = isset($arr[0]) ? $arr[0] : 0;
                        $transactionId = isset($arr[1]) ? $arr[1] : 0;
                        $res[]         = self::find()->where(['item_id' => $itemId, 'transaction_id' => $transactionId])->one();
                    }
                }
                $models = $res;
            }
        } else {
            //查询当前订单是否有纠纷
            $models = self::find()->where(['platform_order_id' => $platform_order_id])->all();
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
            // 案件id存在判断为4-升级
            if (!empty($model->case_id)) {
                $return_array[$key] = [4, $model->id, $model->return_id];
                /*
                    预留：此处需要根据case_id查询具体信息方可判断升级纠纷有没有被解决！！！！
                */

            } // 判断1-已关闭和3-有纠纷
            else {
                if ($model->state == 'CLOSED')
                    $return_array[$key] = [1, $model->id, $model->return_id];
                else
                    $return_array[$key] = [3, $model->id, $model->return_id];
            }
        }
        return $return_array;

    }

    /**
     * 获取return 原因
     * @param type $returnId
     * @return type
     * @author allen <2018-04-06>
     */
    public static function getReturnReason($returnId)
    {
        $model = self::find()->select('return_reason')->where(['return_id' => $returnId])->asArray()->one();
        if ($model) {
            $reason = $model['return_reason'];

            switch ($reason) {
                case 'OUT_OF_STOCK':
                    $info = [
                        'department_id' => 56,//销售部
                        'reason_id'     => 77,
                    ];
                    break;
                case 'BUYER_CANCEL_ORDER':
                case 'VALET_DELIVERY_ISSUES':
                case 'VALET_UNAVAILABLE':
                    $info = [
                        'department_id' => 58,//顾客原因
                        'reason_id'     => 91,//客户取消订单 作废不发
                    ];
                    break;
                case 'EXPIRED_ITEM':
                case 'FOUND_BETTER_PRICE':
                case 'NO_LONGER_NEED_ITEM':
                case 'NO_REASON':
                case 'ORDERED_ACCIDENTALLY':
                case 'ORDERED_WRONG_ITEM':
                case 'OTHER':
                case 'RETURNING_GIFT':
                case 'WRONG_SIZE':
                case 'ARRIVED_LATE':
                    $info = [
                        'department_id' => 58,//顾客原因
                        'reason_id'     => 90,//客户改变主意，期望过高，地址有误，骗子，拒付关税或者是不清关，价格不满意，重复下单，操作不当
                    ];
                    break;
                case 'DIFFERENT_FROM_LISTING':
                case 'NOT_AS_DESCRIBED':
                    $info = [
                        'department_id' => 56,//销售部
                        'reason_id'     => 80,//19 描述性错误（包含标题，图片，参数，副标题等）
                    ];
                    break;
                case 'MISSING_PARTS':
                    $info = [
                        'department_id' => 55,//供应商
                        'reason_id'     => 83,//15 产品不完整,缺少配件
                    ];
                    break;
                case 'ORDERED_DIFFERENT_ITEM':
                    $info = [
                        'department_id' => 57,//仓库部
                        'reason_id'     => 85,//23 发错货（配错插头，贴错地址）
                    ];
                    break;
                case 'ARRIVED_DAMAGED':
                    $info = [
                        'department_id' => 55,//供应商
                        'reason_id'     => 73,//11 包装破损
                    ];
                    break;
                case 'DEFECTIVE_ITEM':
                case 'FAKE_OR_COUNTERFEIT':
                    $info = [
                        'department_id' => 55,//供应商
                        'reason_id'     => 74,//12 产品质量问题
                    ];
                    break;
                case 'BUYER_NO_SHOW':
                case 'BUYER_NOT_SCHEDULED':
                case 'BUYER_REFUSED_TO_PICKUP':
                case 'IN_STORE_RETURN':
                    $info = [
                        'department_id' => 53,//物流公司
                        'reason_id'     => 67,//5 投诉未收到-派送不成功，已经退回仓库
                    ];
                    break;
            }

            return $info;
        }
        return [];
    }

}