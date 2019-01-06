<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/10 0010
 * Time: 下午 8:52
 */

namespace app\modules\mails\models;


use app\common\VHelper;
use app\modules\accounts\models\UserAccount;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\Order;
use yii\helpers\Json;
use app\modules\systems\models\ErpOrderApi;
use yii\helpers\Url;
use app\modules\orders\models\OrderEbay;

class EbayCancellations extends MailsModel
{
    public static $requestorTypeMap = [1=>'BUYER',2=>'SELLER',3=>'UNKNOWN'];
    public static $cancelStateMap = [1=>'APPROVAL_PENDING',2=>'CLOSED',3=>'CONFIRM_REFUND_PENDING',4=>'INITIAL',5=>'OTHER',6=>'REFUND_INITIATED',7=>'REFUND_PENDING'];
    public static $cancelStatusMap = [1=>'CANCEL_CLOSED_FOR_COMMITMENT',2=>'CANCEL_CLOSED_NO_REFUND',3=>'CANCEL_CLOSED_UNKNOWN_REFUND',4=>'CANCEL_CLOSED_WITH_REFUND',5=>'CANCEL_PENDING',6=>'CANCEL_REJECTED',7=>'CANCEL_REQUESTED',8=>'INVALID'];
    public static $ReasonMap = [1=>'BUYER_CONFIRM_NOT_PAID',2=>'BUYER_CONFIRM_REFUND',3=>'BUYER_CONFIRM_TIMEOUT_NON_PAYPAL_PAID',4=>'BUYER_CONFIRM_TIMEOUT_PAYPAL_PAID',5=>'FULL_REFUNDED',6=>'INELIGIBLE_FOR_CANCEL',7=>'REFUND_FAILED',8=>'SELLER_APPROVE_TIMEOUT_PAID',9=>'SELLER_APPROVE_TIMEOUT_UNPAID',10=>'SELLER_APPROVE_UNPAID',11=>'SELLER_DECLINE',12=>'SYSTEM_NOTIFY_SHIPPING_STATUS',13=>'UNKNOWN'];
    public static $paymentStatusMap = [1=>'MARK_AS_PAID',2=>'NOT_PAID',3=>'PAYPAL_PAID',4=>'UNKNOWN'];

    private $ebayAccountModel;

    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'order_type';
        $attributes[] = 'order_id';
        $attributes[] = 'son_order_id';
        $attributes[] = 'parent_order_id';
        $attributes[] = 'old_account_id';
        return $attributes;
    }

    public static function tableName()
    {
        return '{{%ebay_cancellations}}';
    }

    public function attributeLabels()
    {
        return [
            'cancel_id'                 => 'Cancel Id',
            'marketplace_id'            => '站点',
            'legacy_order_id'           => '平台订单号',
            'requestor_type'            => '发起方',
            'cancel_state'              => '状况',
            'cancel_status'             => '状态',
            'cancel_close_reason'       => '关闭原因',
            'seller_response_due_date'  => '卖家回复截止日期',
            'payment_status'            => '付款状态',
            'request_refund_amount'     => '申请退款金额',
            'currency'                  => '货币',
            'cancel_request_date'       => '开始日期',
            'cancel_close_date'         => '关闭日期',
            'buyer_response_due_date'   => '买家回复截止日期',
            'cancel_reason'             => '原因',
            'shipment_date'             => '发货时间',
            'buyer'                     => '买家',
            'account_id'                => 'eBay账号',
            'update_time'               => '更新时间',
            'order_id'                  => '订单号',
            'order_type'                => '订单类型',
        ];
    }

    public function rules()
    {
        return [
            [['cancel_id','marketplace_id','legacy_order_id','requestor_type'],'required'],
            ['currency','default','value'=>''],
            ['buyer','default','value'=>''],
            [['cancel_id','marketplace_id','legacy_order_id','requestor_type','cancel_state','cancel_status','cancel_close_reason','seller_response_due_date','payment_status','request_refund_amount','currency','cancel_request_date','cancel_close_date','buyer_response_due_date','cancel_reason','shipment_date','account_id','update_time'],'safe'],
            [['order_id'],'safe'],
            ];
    }

    public function searchList($params = [])
    {
        $query = self::find();
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'cancel_request_date' => SORT_DESC
        );

        if(isset($params['cancel_state']))
        {
            switch($params['cancel_state'])
            {
                case 'other':
                    $query->where('cancel_state <> 2');
                    $params['cancel_state'] = '';
                    break;
                case 'closed':
                    $query->where(['cancel_state'=>2]);
                    $params['cancel_state'] = '';
            }
        }

        // 只能查询到客服绑定账号的纠纷
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);
        $query->andWhere(['in','account_id',$accountIds]);

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
        if(!empty($params['order_id'])){
            $platform_order_id = OrderEbay::getPlatform($params['order_id']);
            if(!empty($platform_order_id)){

                $query->andWhere(['legacy_order_id' => $platform_order_id]);
            }
            unset($params['order_id']);
        }

        $dataProvider = parent::search($query, $sort, $params);

        $models = $dataProvider->getModels();

        foreach ($models as $k=>$v){
            $v['order_id'] = OrderEbay::getOrderId($v['legacy_order_id']);
            $v['order_type'] = OrderEbay::getOrderType($v['legacy_order_id']);
            $v['parent_order_id'] = OrderEbay::getParentOrderId($v['legacy_order_id']);
            $v['old_account_id'] = OrderEbay::getAccountId($v['legacy_order_id']);
            $son_order_id_arr = array();
            // 根据订单类型获取关联订单单号
            switch($v['order_type']){
                // 合并后的订单、被拆分的订单查询子订单
                case OrderEbay::ORDER_TYPE_MERGE_MAIN:
                case OrderEbay::ORDER_TYPE_SPLIT_MAIN:
                    $son_order_ids = isset($v['order_id']) ? OrderEbay::getOrderSon($v['order_id']):null;
                    foreach($son_order_ids as $son_order_id){
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
        foreach ($models as $model)
        {
            isset($model->cancel_request_date) && $model->cancel_request_date = date('Y-m-d H:i:s',strtotime($model->cancel_request_date)+28800);
            isset($model->seller_response_due_date) && $model->seller_response_due_date = date('Y-m-d H:i:s',strtotime($model->seller_response_due_date)+28800);
            isset($model->cancel_close_date) && $model->cancel_close_date = date('Y-m-d H:i:s',strtotime($model->cancel_close_date)+28800);
            isset($model->buyer_response_due_date) && $model->buyer_response_due_date = date('Y-m-d H:i:s',strtotime($model->buyer_response_due_date)+28800);
            $model->requestor_type = self::$requestorTypeMap[$model->requestor_type];
            $model->cancel_state = self::$cancelStateMap[$model->cancel_state];
            $model->cancel_status = self::$cancelStatusMap[$model->cancel_status];
            $model->cancel_close_reason = $model->cancel_close_reason == 0 ? '' : self::$ReasonMap[$model->cancel_close_reason];
            $model->cancel_reason = $model->cancel_reason == 0 ? '' : self::$ReasonMap[$model->cancel_reason];
            $model->payment_status = self::$paymentStatusMap[$model->payment_status];
            $account_info = Account::getHistoryAccountInfo($model->old_account_id, 'EB');
            $accountModel = Account::findOne((int)$model->account_id);
            if(!empty($accountModel))
            {
                $model->account_id = $accountModel->account_name;
            }
            if(empty($model->buyer))
            {
                /*从erp接口获取buyer信息*/
                $erpOrderApi = new ErpOrderApi;
                $result = $erpOrderApi->getOrderStack(Platform::PLATFORM_CODE_EB,$model->legacy_order_id);
                if(!$result)
                    $model->buyer = '';
                else{
                    if($result->ack == true)
                        $model->buyer = $result->order->info->buyer_id;
                    else
                        $model->buyer = '';
                }
            }
            $model->legacy_order_id = '<a _width="100%" _height ="100%" class="edit-button" href="'.Url::toRoute(['/orders/order/orderdetails','order_id'=>$model->legacy_order_id,'platform'=>'EB']).'">'.$model->legacy_order_id.'</a>';

            switch ($model->order_type) {
                case Order::ORDER_TYPE_MERGE_MAIN:
                    $rela_order_name = '合并前子订单';
                    $rela_is_arr = true;
                    break;
                case Order::ORDER_TYPE_SPLIT_MAIN:
                    $rela_order_name = '拆分后子订单';
                    $rela_is_arr = true;
                    break;
                case Order::ORDER_TYPE_MERGE_RES:
                    $rela_order_name = '合并后父订单';
                    $rela_is_arr = false;
                    break;
                case Order::ORDER_TYPE_SPLIT_CHILD:
                    $rela_order_name = '拆分前父订单';
                    $rela_is_arr = false;
                    break;
                default:
                    $rela_order_name = '';
            }
            $order_result = '';
            if (!empty($rela_order_name)) {
                if($rela_is_arr) {
                    foreach ($model->son_order_id as $son_order_id) {
                        $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="'.Url::toRoute(['/orders/order/orderdetails','system_order_id'=>$son_order_id ,'platform'=>'EB']).'"title="订单信息">' . $son_order_id . '</a></p>';
                    }
                    if (!empty($order_result))
                        $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                } else {
                    $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="'.Url::toRoute(['/orders/order/orderdetails','system_order_id'=>$model->parent_order_id ,'platform'=>'EB']).'"title="订单信息">' . $model->parent_order_id . '</a></p>';
                    $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;

                }
            }
            $order_id = isset($account_info->account_short_name) ? $account_info->account_short_name . '--' : '';
            $model->order_id = $order_id.'<br />'. $model->order_id . $order_result;
            $model->order_type = (isset($model->order_type) && !empty($model->order_type)) ? VHelper::getOrderType($model->order_type) : "-";
        }

    }

    public function filterOptions()
    {
        return [
            [
                'name'=>'cancel_id',
                'type' => 'text',
                //'data' => EbayAccount::getIdNameKVList(),
                'search'=> '='
            ],
            [
                'name'=>'order_id',
                'type'=>'text',
                'serch'=>'=',
            ],
            [
                'name' => 'legacy_order_id',
                'type' => 'text',
                'search'=> '='
            ],
            [
                'name'=>'account_id',
                'type' => 'search',
                'data' => Account::getIdNameKVList(Platform::PLATFORM_CODE_EB),
                'search'=> '='
            ],
            [
                'name'=>'buyer',
                'type' => 'text',
//                'data' => Account::getIdNameKVList(),
                'search'=> 'LIKE'
            ],
            [
                'name' => 'sku',
                'type' => 'text',
                'search' => '='
            ],
            [
                'name'=>'requestor_type',
                'type' => 'dropDownList',
                'data' => self::$requestorTypeMap,
                'value' => 1,
                'search'=> '='
            ],
            [
                'name'=>'cancel_status',
                'type' => 'dropDownList',
                'data' => self::$cancelStatusMap,
                'search'=> '='
            ],
            [
                'name'=>'cancel_state',
                'type' => 'dropDownList',
                'data' => ['other'=>'未关闭','closed'=>'关闭'],//self::$cancelStateMap,
                'value' => 'other',
                'search'=> '='
            ]
        ];
    }

    public function refreshApi()
    {
        set_time_limit(150);

        $memcache = \Yii::$app->memcache;

        $accountName = Account::findById((int)$this->account_id)->account_name;

        $token = $memcache->get($accountName);
        if(!$token)
        {
            $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
            $token = $this->ebayAccountModel->user_token;
            $memcache->set($accountName,$token,'',600);
        }

        return $this->searchApi($token,'','https://api.ebay.com/post-order/v2/cancellation/search',['cancel_id'=>$this->cancel_id],'get');
    }

    private function searchApi($token,$site,$serverUrl,$params,$method)
    {
        /*$api = new PostOrderAPI($token,$site,$serverUrl,$method);
        $api->urlParams = $params;
        $response = $api->sendHttpRequest();
        if(!empty($response))
        {
            return $this->handleSearchResponse($response,$token);
        }
        else
        {
            return ['flag'=>false,'info'=>'未返回数据。'];
        }*/

        $transfer_ip = include \Yii::getAlias('@app').'/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
        if(empty($transfer_ip))
            return ['flag'=>false,'info'=>'中转服务器配置错误'];

        $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$token,'data'=>'','method'=>$method,'responseHeader'=>false,'urlParams'=>$params];
        $api = new PostOrderAPI('ceshi','',$transfer_ip,'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();
        if(!empty($response))
        {
            if(in_array($response->code,[200,201,202]))
            {
                return $this->handleSearchResponse(json_decode($response->response),$token);
            }
            else
            {
                return ['flag'=>false,'info'=>'未返回数据。'];
            }
        }
        else
        {
            return ['flag'=>false,'info'=>'中转服务器无响应,请稍后再试'];
        }
    }

    private function handleSearchResponse($data,$token)
    {
        $cancellation = $data->cancellations[0];
        $oprationDetail = $this->detailApi($token,'','https://api.ebay.com/post-order/v2/cancellation/'.$this->cancel_id,'get');
        if($oprationDetail['flag'])
        {
            $this->cancel_id = $cancellation->cancelId;
            $this->marketplace_id = $cancellation->marketplaceId;
            $this->legacy_order_id = $cancellation->legacyOrderId;
            $this->requestor_type = array_search(trim($cancellation->requestorType),EbayCancellations::$requestorTypeMap);
            $this->cancel_state = array_search(trim($cancellation->cancelState),EbayCancellations::$cancelStateMap);
            $this->cancel_status = array_search(trim($cancellation->cancelStatus),EbayCancellations::$cancelStatusMap);
            if(isset($cancellation->cancelCloseReason))
                $this->cancel_close_reason = array_search(trim($cancellation->cancelCloseReason),EbayCancellations::$ReasonMap);
            if(isset($cancellation->sellerResponseDueDate))
                $this->seller_response_due_date = explode('.',str_replace('T',' ',$cancellation->sellerResponseDueDate->value))[0];
            $this->payment_status = array_search(trim($cancellation->paymentStatus),EbayCancellations::$paymentStatusMap);
            if(isset($cancellation->requestRefundAmount))
            {
                $this->request_refund_amount = $cancellation->requestRefundAmount->value;
                $this->currency = $cancellation->requestRefundAmount->currency;
            }
            $this->cancel_request_date = explode('.',str_replace('T',' ',$cancellation->cancelRequestDate->value))[0];
            if(isset($cancellation->cancelCloseDate))
                $this->cancel_close_date = explode('.',str_replace('T',' ',$cancellation->cancelCloseDate->value))[0];
            if(isset($cancellation->buyerResponseDueDate))
                $this->buyer_response_due_date = explode('.',str_replace('T',' ',$cancellation->buyerResponseDueDate->value))[0];
            if(isset($cancellation->cancelReason))
                $this->cancel_reason = array_search(trim($cancellation->cancelReason),EbayCancellations::$ReasonMap);
            if(isset($cancellation->shipmentDate))
                $this->shipment_date = explode('.',str_replace('T',' ',$cancellation->shipmentDate->value))[0];
            $this->update_time = date('Y-m-d H:i:s');
            if(empty($this->buyer) && isset($cancellation->legacyOrderId))
            {
                $orderinfo = Order::getOrderStack('EB', $cancellation->legacyOrderId);
                if (!empty($orderinfo))
                {
                    $orderinfo = Json::decode(Json::encode($orderinfo), true);
                    $buyer = $orderinfo['info']['buyer_id'];
                    $this->buyer = $buyer;
                }
            }
            try{
                $oprationDetail['flag'] = $this->save();
                if(!$oprationDetail['flag'])
                    $oprationDetail['info'] .= VHelper::getModelErrors($this);
            }catch(Exception $e){
                $oprationDetail['flag'] = false;
                $oprationDetail['info'] .= $e->getMessage();
            }
        }
        if(isset($oprationDetail['transaction']))
        {
            if($oprationDetail['flag'])
                $oprationDetail['transaction']->commit();
            else
                $oprationDetail['transaction']->rollback();
        }
        return $oprationDetail;
    }

    private function detailApi($token,$site,$serverUrl,$method)
    {
        /*$api = new PostOrderAPI($token,$site,$serverUrl,$method);
        $response = $api->sendHttpRequest();
//        findClass($response,1);
        return $this->handleDetailResponse($response);*/

        $transfer_ip = include \Yii::getAlias('@app').'/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
        if(empty($transfer_ip))
            return ['flag'=>false,'info'=>'中转服务器配置错误'];

        $post_data = ['serverUrl'=>$serverUrl,'authorization'=>$token,'data'=>'','method'=>$method,'responseHeader'=>false,'urlParams'=>''];
        $api = new PostOrderAPI('ceshi','',$transfer_ip,'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();
        if(!empty($response))
        {
            if(in_array($response->code,[200,201,202]))
                return $this->handleDetailResponse(json_decode($response->response));
            else
                return ['flag'=>false,'info'=>'获取详情无数据'];
        }
        else
            return ['flag'=>false,'info'=>'中转服务器获取详情无响应'];
    }

    private function handleDetailResponse($data)
    {
        $cancelId = $data->cancelDetail->cancelId;
        $return['transaction'] = self::getDb()->beginTransaction();
        $return['flag'] = true;
        $return['info'] = '';
        if(!empty($data->cancelDetail->activityHistories))
        {
            EbayCancellationsDetail::deleteAll(['cancel_id'=>$cancelId]);
            foreach($data->cancelDetail->activityHistories as $historie)
            {
                $detailModel = new EbayCancellationsDetail();
                $detailModel->cancel_id = $cancelId;
                $detailModel->activity_type = $historie->activityType;
                $detailModel->activity_party = $historie->activityParty;
                $detailModel->action_date = explode('.',str_replace('T',' ',$historie->actionDate->value))[0];
                $detailModel->state_from = $historie->cancelStateFrom;
                $detailModel->state_to = $historie->cancelStatetateTo;
                try{
                    $return['flag'] = $detailModel->save();
                    if(!$return['flag'])
                    {
                        $return['info'] .= VHelper::getModelErrors($detailModel);
                    }
                }catch(Exception $e){
                    $return['flag'] = false;
                    $return['info'] .= $e->getMessage();
                }
                if(!$return['flag'])
                    break;
            }
        }
        return $return;
    }

    /**
     * 获取指定id的纠纷id:cancel_id
     * param string 主键id
     * return string cancel_id
     **/
    public static function getCancelIDByID($id)
    {
        if (empty($id)) {
            return 0;
        }

        $cancel_id = self::findOne($id)->cancel_id;

        return $cancel_id;
    }

    /** 判断指定交易号是否有纠纷 **/
    public static function whetherExist($platform_order_id)
    {
        if (empty($platform_order_id)) {
            return false; 
        }

        $model = self::find()->where(['legacy_order_id'=> $platform_order_id])->one();
        
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
    public static function disputeLevel($platform_order_id)
    {
        if (empty($platform_order_id)) {
            return [0,0,0];
        }
        $return_array = array();
        $models = self::find()->where(['legacy_order_id'=> $platform_order_id])->all();

        //指定订单不存在纠纷
        if (empty($models)) return $return_array;

        foreach($models as $key => $model)
        {
            // 判断1-关闭、3-纠纷
            if($model->cancel_state == 2)
            {
                $return_array[$key] = [1,$model->id,$model->cancel_id];
            }
            else
            {
                $return_array[$key] = [3,$model->id,$model->cancel_id];
            }
            // 取消纠纷暂无升级
        }
        return $return_array;



    }

}