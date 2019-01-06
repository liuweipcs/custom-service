<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/11 0011
 * Time: 下午 8:41
 */

namespace app\modules\mails\models;


use app\common\VHelper;
use app\modules\accounts\models\UserAccount;
use app\modules\orders\models\Order;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\systems\models\EbayAccount;
use PhpImap\Exception;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\AutoCode;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\accounts\models\Platform;
use yii\helpers\Json;
use app\modules\accounts\models\Account;
use yii\helpers\Url;

class EbayCase extends MailsModel
{
    public static $initiatorMap = [1=>'BUYER',2=>'CSR',3=>'SELLER',4=>'SYSTEM',5=>'UNKNOWN'];
    public static $caseTypeMap = [1=>'RETURN',2=>'ITEM_NOT_RECEIVED'];
    const CASE_TYPE_REFUND = 1;
    const CASE_TYPE_ITEM_NOT_RECEIVED = 2;

    public $ebayAccountModel;


    public static function tableName()
    {
        return '{{%ebay_case}}';
    }

    public function rules()
    {
        return [
        ];
    }

    public function searchList($params = [])
    {
        $query = self::find();
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'creation_date' => SORT_DESC
        );
        if(isset($params['status']))
        {
            switch ($params['status'])
            {
                case 'wait_seller':
                    $query->where('status in ("OPEN","PENDING","WAITING_SELLER_RESPONSE")');
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
        $query->andWhere(['in','account_id',$accountIds]);

        $dataProvider = parent::search($query, $sort, $params);

        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function addition(&$models)
    {
        foreach ($models as $model)
        {
            isset($model->appeal_date) && $model->appeal_date = date('Y-m-d H:i:s',strtotime($model->appeal_date)+28800);
            isset($model->creation_date) && $model->creation_date = date('Y-m-d H:i:s',strtotime($model->creation_date)+28800);
            isset($model->escalation_date) && $model->escalation_date = date('Y-m-d H:i:s',strtotime($model->escalation_date)+28800);
            isset($model->expiration_date) && $model->expiration_date = date('Y-m-d H:i:s',strtotime($model->expiration_date)+28800);
            isset($model->last_buyer_respdate) && $model->last_buyer_respdate = date('Y-m-d H:i:s',strtotime($model->last_buyer_respdate)+28800);
            isset($model->refund_deadline_date) && $model->refund_deadline_date = date('Y-m-d H:i:s',strtotime($model->refund_deadline_date)+28800);
            isset($model->seller_make_it_right_by_date) && $model->seller_make_it_right_by_date = date('Y-m-d H:i:s',strtotime($model->seller_make_it_right_by_date)+28800);
            $model->initiator = self::$initiatorMap[$model->initiator];
            $model->appeal_close_reason_enum = $model->appeal_close_reason_enum == 0 ? '' : self::$appealCloseReasonEnumMap[$model->appeal_close_reason_enum];
            $model->appeal_status = $model->appeal_status == 0 ? '' : self::$appealStatusMap[$model->appeal_status];
            $model->appeal_status_enum = $model->appeal_status_enum == 0 ? '' : self::$appealStatusEnum[$model->appeal_status_enum];
            $model->eligible_for_appeal = $model->eligible_for_appeal > 0 ? '':($model->eligible_for_appeal == 1 ? '是':'否');
            $model->account_id = Account::findOne((int)$model->account_id)->account_name;

            $model->buyer_init_expect_refund_amt = (empty($model->buyer_init_expect_refund_amt) || $model->buyer_init_expect_refund_amt == '0.00') ? $model->claim_amount.' '.$model->currency : $model->buyer_init_expect_refund_amt.' '.$model->currency;
            $model->refund_amount = (empty($model->buyer_init_expect_refund_amt) || $model->refund_amount == '0.00') ? $model->claim_amount.' '.$model->currency : $model->refund_amount.' '.$model->currency;

            if(empty($model->platform_order_id))
                $platform_order_id = $model->item_id.'-'.$model->transaction_id;
            else
                $platform_order_id = $model->platform_order_id;
            $model->platform_order_id = '<a class="edit-button" href="'.Url::toRoute(['/orders/order/orderdetails','order_id'=>$platform_order_id,'platform'=>'EB']).'">'.$platform_order_id.'</a>';
        }
        
    }

    public function filterOptions()
    {
        return [
            [
                'name'=>'inquiry_id',
                'type' => 'text',
                //'data' => EbayAccount::getIdNameKVList(),
                'search'=> '='
            ],
            [
                'name' => 'platform_order_id',
                'type' => 'text',
                'search'=> '='
            ],
            [
                'name' => 'item_id',
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
//                'data' => EbayAccount::getIdNameKVList(),
                'search'=> 'LIKE'
            ],
            [
                'name'=>'status',
                'type' => 'dropDownList',
                'data' => ['wait_seller'=>'等待卖家回应','closed'=>'已关闭','other'=>'其他'],//self::getFieldList('status','status','status'),
                'value' => 'wait_seller',
                'search'=> '='
            ],
            [
                'name'=>'state',
                'type' => 'dropDownList',
                'data' => self::getFieldList('state','state','state'),
                'search'=> '='
            ],
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
            if(!isset($this->ebayAccountModel))
            {
                $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
            }
            $token = $this->ebayAccountModel->user_token;
            $memcache->set($accountName,$token,'',600);
        }

//        if(!isset($this->ebayAccountModel))
//        {
//            $accountName = Account::findById((int)$this->account_id)->account_name;
//            $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
//            $token = $this->ebayAccountModel->user_token;
//        }
        return $this->detailApi($token,'','https://api.ebay.com/post-order/v2/inquiry/'.$this->inquiry_id,'get');
    }

    private function detailApi($token,$site,$serverUrl,$method)
    {
        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
        $response = $api->sendHttpRequest();
       return $this->handleResponse($response,$token);
    }

    private function handleResponse($data,$token)
    {
        $buyer = '';
        if(isset($data->transactionId) && !empty($data->transactionId))
        {
            $orderinfo = Order::getOrderStackByTransactionId('EB',$data->transactionId);
            $platformOrderId = $orderinfo->info->platform_order_id;
        }
        else
        {
            $platformOrderId = $data->itemId.'-0';
            $orderinfo = Order::getOrderStack('EB', $platformOrderId);
        }

        if (!empty($orderinfo))
        {
            $orderinfo = Json::decode(Json::encode($orderinfo), true);
            $buyer = $orderinfo['info']['buyer_id'];
        }
        $flag = true;
        $info = '';
        $this->claim_amount = $data->claimAmount->value - 0;
        $this->currency = $data->claimAmount->currency;
        $this->extTransaction_id = $data->extTransactionId;
        $this->initiator = array_search(trim($data->initiator),EbayInquiry::$initiatorMap);
        $this->appeal_close_reason_enum = isset($data->inquiryDetails->appealDetails->appealCloseReasonEnum) ? array_search(trim($data->inquiryDetails->appealDetails->appealCloseReasonEnum),EbayInquiry::$appealCloseReasonEnumMap) : 0;
        if(isset($data->inquiryDetails->appealDetails->appealDate))
            $this->appeal_date = explode('.',str_replace('T',' ',$data->inquiryDetails->appealDetails->appealDate))[0];
        $this->appeal_reason_code = isset($data->inquiryDetails->appealDetails->appealReasonCode) ? $data->inquiryDetails->appealDetails->appealReasonCode : '';
        $this->appeal_status = isset($data->inquiryDetails->appealDetails->appealStatus) ? array_search(trim($data->inquiryDetails->appealDetails->appealStatus),EbayInquiry::$appealStatusMap) : 0;
        $this->appeal_status_enum = isset($data->inquiryDetails->appealDetails->appealStatusEnum) ? array_search(trim($data->inquiryDetails->appealDetails->appealStatusEnum),EbayInquiry::$appealStatusEnum) : 0;
        $this->platform_order_id = isset($platformOrderId) ? $platformOrderId:'';
        $this->buyer_initial_expected_resolution = isset($data->inquiryDetails->buyerInitialExpectedResolution) ? $data->inquiryDetails->buyerInitialExpectedResolution : '';

        //$finalCNY = VHelper::getTargetCurrencyAmt($this->currency,Account::CURRENCY,$this->claim_amount);
        $finalCNY = VHelper::getTargetCurrencyAmtKefu($this->currency,Account::CURRENCY,$this->claim_amount);

        if(empty($this->escalation_date) && !empty($data->inquiryDetails->escalationDate) && (int)$this->auto_refund == 0 && $finalCNY < Account::ACCOUNT_PRICE)
        {   //升级case,自动退款
            $refundApi = new PostOrderAPI($token,'','https://api.ebay.com/post-order/v2/inquiry/'.$this->inquiry_id.'/issue_refund','post');
            $refundResponse = $refundApi->sendHttpRequest();
            $responseModel = new EbayInquiryResponse();
            $responseModel->inquiry_id = $this->inquiry_id;
            $responseModel->type = 2;
            $responseModel->content = '';
            $responseModel->account_id = $this->account_id;
            if(empty($refundResponse))
            {
                $responseModel->status = 0;
                $responseModel->error = '自动退款调用接口失败,无返回值';
            }
            else
            {
                $responseModel->status = 1;
                $responseModel->error = '';
                $responseModel->refund_source = $refundResponse->refundResult->refundSource;
                $responseModel->refund_status = $refundResponse->refundResult->refundStatus;
                $this->auto_refund = 2;
                //建立退款售后处理单
                $afterSalesOrderModel = new AfterSalesOrder();
                $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                $afterSalesOrderModel->transaction_id = $this->transaction_id;
                if(!empty($this->platform_order_id))
                    $afterSalesOrderModel->order_id = $this->platform_order_id;
                $afterSalesOrderModel->type = AfterSalesOrder::ORDER_TYPE_REFUND;
                $afterSalesOrderModel->reason_id = 7;
                if($responseModel->refund_status == 'PENDING' || $responseModel->refund_status == 'OTHER')
                    $afterSalesOrderModel->remark = $responseModel->refund_status;
                $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                $afterSalesOrderModel->approve_time = date('Y-m-d H:i:s');
                $afterSalesOrderModel->approver = 'system';
                $afterSalesOrderModel->buyer_id = $buyer;
                $afterSalesOrderModel->account_id = $this->account_id;
                $afterSaleOrderRefund = new AfterSalesRefund();
                $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
                $afterSaleOrderRefund->refund_amount = $this->total_amount;
                $afterSaleOrderRefund->currency = $this->currency;
                $afterSaleOrderRefund->message = $responseModel->content;
                $afterSaleOrderRefund->transaction_id = $this->transaction_id;
                if(!empty($this->platform_order_id))
                    $afterSaleOrderRefund->order_id = $this->platform_order_id;
                $afterSaleOrderRefund->platform_code = Platform::PLATFORM_CODE_EB;
                $afterSaleOrderRefund->order_amount = $this->total_amount;
                switch($responseModel->refund_status)
                {
                    case 'FAILED':
                        $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                        break;
                    case 'PENDING':
                        $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_ING;
                        break;
                    case 'SUCCESS':
                        $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                        break;
                }
            }
            $transaction = self::getDb()->beginTransaction();
            try{
                $flag = $responseModel->save();
                if(!$flag)
                    $info .= VHelper::getModelErrors($responseModel);
                elseif(isset($afterSalesOrderModel))
                {
                    $flag = $afterSalesOrderModel->save();
                    if(!$flag)
                        $info .= VHelper::getModelErrors($afterSalesOrderModel);
                    elseif(isset($afterSaleOrderRefund))
                    {
                        $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                        $flag = $afterSaleOrderRefund->save();
                        if(!$flag)
                            $info .= VHelper::getModelErrors($afterSaleOrderRefund);
                    }
                }
            }catch(Exception $e){
                $flag = false;
                $info .= $e->getMessage();
            }
        }
        if($flag)
        {
            $this->eligible_for_appeal = isset($data->inquiryDetails->appealDetails->eligibleForAppeal) ? array_search($data->inquiryDetails->appealDetails->eligibleForAppeal,EbayInquiry::$eligibleForAppealMap) : 0;
            $this->creation_date = explode('.',str_replace('T',' ',$data->inquiryDetails->creationDate->value))[0];
            $this->escalation_date = isset($data->inquiryDetails->escalationDate) ? explode('.',str_replace('T',' ',$data->inquiryDetails->escalationDate->value))[0] : null;
            $this->expiration_date = isset($data->inquiryDetails->expirationDate) ? explode('.',str_replace('T',' ',$data->inquiryDetails->expirationDate->value))[0] : null;
            $this->last_buyer_respdate = isset($data->inquiryDetails->lastBuyerRespDate) ? explode('.',str_replace('T',' ',$data->inquiryDetails->lastBuyerRespDate->value))[0] : null;
            $this->buyer_final_accept_refund_amt = $data->inquiryDetails->refundAmounts->buyerFinalAcceptRefundAmt->value;
            if(empty($this->currency))
                $this->currency = $data->inquiryDetails->refundAmounts->buyerFinalAcceptRefundAmt->currency;
            $this->buyer_init_expect_refund_amt = $data->inquiryDetails->refundAmounts->buyerInitExpectRefundAmt->value;
            if(empty($this->currency))
                $this->currency = $data->inquiryDetails->refundAmounts->buyerInitExpectRefundAmt->currency;
            $this->international_refund_amount = isset($data->inquiryDetails->refundAmounts->internationalRefundAmount) ? $data->inquiryDetails->refundAmounts->internationalRefundAmount->value : 0;
            $this->refund_amount = isset($data->inquiryDetails->refundAmounts->refundAmount) ? $data->inquiryDetails->refundAmounts->refundAmount->value : 0;
            $this->refund_deadline_date = explode('.',str_replace('T',' ',$data->inquiryDetails->refundDeadlineDate->value))[0];
            $this->total_amount = $data->inquiryDetails->totalAmount->value;
            if(empty($this->currency))
                $this->currency = $data->inquiryDetails->totalAmount->currency;
            $this->inquiry_id = $data->inquiryId;
            $this->inquiry_quantity = $data->inquiryQuantity;
            $this->item_picture_url = isset($data->itemDetails->itemPictureUrl) ? $data->itemDetails->itemPictureUrl : '';
            $this->item_price = $data->itemDetails->itemPrice->value;
            if(empty($this->currency))
                $this->currency = $data->itemDetails->itemPrice->currency;
            $this->item_title = $data->itemDetails->itemTitle;
            $this->view_purchased_item_url = isset($data->itemDetails->viewPurchasedItemUrl) ? $data->itemDetails->viewPurchasedItemUrl : '';
            $this->item_id = $data->itemId;
            $this->seller_make_it_right_by_date = explode('.',str_replace('T',' ',$data->sellerMakeItRightByDate->value))[0];
            $this->state = $data->state;
            $this->status = $data->status;
            $this->transaction_id = isset($data->transactionId)? $data->transactionId:'';
            $this->view_pp_trasanction_url = isset($data->viewPPTrasanctionUrl) ? $data->viewPPTrasanctionUrl : '';
            $this->update_time = date('Y-m-d H:i:s');
            $this->buyer = $buyer;
            if(!isset($transaction))
                $transaction = self::getDb()->beginTransaction();
            try{
                $flag = $this->save();
                if(!$flag)
                    $info .= VHelper::getModelErrors($this);

            }catch(Exception $e){
                $flag = false;
                $info .= $e->getMessage();
            }
        }

        if($flag)
        {
            EbayInquiryHistory::deleteAll(['inquiry_table_id'=>$this->id]);
            foreach($data->inquiryHistoryDetails->history as $history)
            {
                $inquiryHistoryModel = new EbayInquiryHistory();
                $inquiryHistoryModel->inquiry_table_id = $this->id;
                $inquiryHistoryModel->inquiry_id = $data->inquiryId;
                $inquiryHistoryModel->action = $history->action;
                $inquiryHistoryModel->actor = array_search(trim($history->actor),EbayInquiry::$initiatorMap);
                $inquiryHistoryModel->date = explode('.',str_replace('T',' ',$history->date->value))[0];
                $inquiryHistoryModel->description = isset($history->description) ? $history->description : '';
                try{
                    $flag = $inquiryHistoryModel->save();
                    if(!$flag)
                        $info .= VHelper::getModelErrors($inquiryHistoryModel);
                }catch(Exception $e){
                    $flag = false;
                    $info .= $e->getMessage();
                }
                if(!$flag)
                    break;
            }
        }
        if($flag)
            $transaction->commit();
        else
            $transaction->rollBack();
        return ['flag'=>$flag,'info'=>$info];
    }

}