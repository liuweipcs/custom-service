<?php
namespace app\modules\mails\models;
use app\components\Model;
use app\common\VHelper;
class AliexpressDisputeSolution extends Model
{
    public static function getDb()
    {
        return \Yii::$app->db;
    }
    
    
    public static function tableName()
    {
        return '{{%aliexpress_dispute_solution}}';
    }
    public function getInfo($platform_dispute_id){
        return self::find()->where(['platform_dispute_id'=>$platform_dispute_id])->asArray()->all();
    }
    public function newlyAdded($dispute_id = null,$scheme_type = null,$data = null){
        try{
            if (!empty($data)) {
                $platform = self::find()->where(['platform_programme_id'=>$data->id])->one();
                if (isset($data->refundMoney->amount) && isset($data->refundMoney->currencyCode)) {
                    $refundMoney = $data->refundMoney->amount . $data->refundMoney->currencyCode;
                }
                if (isset($data->refundMoneyPost->amount) && isset($data->refundMoneyPost->currencyCode)) {
                    $refundMoneyPost = $data->refundMoneyPost->amount . $data->refundMoneyPost->currencyCode;
                }
                if(empty($platform)){
                    $AliexpressDisputeSolution = new AliexpressDisputeSolution();
                    $AliexpressDisputeSolution->scheme_type = $scheme_type;
                    $AliexpressDisputeSolution->platform_programme_id = $data->id;
                    $AliexpressDisputeSolution->dispute_id = $dispute_id;
                    $AliexpressDisputeSolution->platform_dispute_id = $data->issueId;
                    $AliexpressDisputeSolution->gmt_create = VHelper::_toDate($data->gmtCreate);
                    $AliexpressDisputeSolution->gmt_modified = VHelper::_toDate($data->gmtModified);
                    $AliexpressDisputeSolution->version = isset($data->version) ? $data->version : '';
                    $AliexpressDisputeSolution->buyer_aliid = isset($data->buyerAliid) ? $data->buyerAliid : '';
                    $AliexpressDisputeSolution->seller_aliid = isset($data->sellerAliid) ? $data->sellerAliid : '';
                    $AliexpressDisputeSolution->order_id = isset($data->orderId) ? $data->orderId : '';
                    $AliexpressDisputeSolution->refund_money = isset($refundMoney) ? $refundMoney : '';
                    $AliexpressDisputeSolution->refund_money_post = isset($refundMoneyPost) ? $refundMoneyPost : '';
                    $AliexpressDisputeSolution->is_default = isset($data->isDefault) ? $data->isDefault : '';
                    $AliexpressDisputeSolution->solution_owner = isset($data->solutionOwner) ? $data->solutionOwner : '';
                    $AliexpressDisputeSolution->content = isset($data->content) ? $data->content : '';
                    $AliexpressDisputeSolution->logistics_feeBear_role = isset($data->ogisticsFeeBearRole)?$data->ogisticsFeeBearRole:'';
                    $AliexpressDisputeSolution->solution_type = isset($data->solutionType) ? $data->solutionType : '';
                    $AliexpressDisputeSolution->reached_time = isset($data->reachedTime) ? VHelper::_toDate($data->reachedTime): '';
                    $AliexpressDisputeSolution->status = isset($data->status) ? $data->status : '';
                    $AliexpressDisputeSolution->reached_type = isset($data->reachedType) ? $data->reachedType : '';
                    $AliexpressDisputeSolution->buyer_accept_time = isset($data->buyerAcceptTime) ? VHelper::_toDate($data->buyerAcceptTime) : '';
                    $AliexpressDisputeSolution->seller_accept_time = isset($data->sellerAcceptTimeisset) ? VHelper::_toDate($data->sellerAcceptTimeisset): '';
                    $AliexpressDisputeSolution->logistics_fee_amount = isset($data->logisticsFeeAmount) ? $data->logisticsFeeAmount : '';
                    $AliexpressDisputeSolution->logistics_fee_amount_currency = isset($data->logisticsFeeAmountCurrency) ? $data->logisticsFeeAmountCurrency : '';
                    return $AliexpressDisputeSolution->save();
                }else{
                    $platform->version = isset($data->version) ? $data->version : '';
                    $platform->buyer_aliid = isset($data->buyerAliid) ? $data->buyerAliid : '';
                    $platform->seller_aliid = isset($data->sellerAliid) ? $data->sellerAliid : '';
                    $platform->order_id = isset($data->orderId) ? $data->orderId : '';
                    $platform->refund_money = isset($refundMoney) ? $refundMoney : '';
                    $platform->refund_money_post = isset($refundMoneyPost) ? $refundMoneyPost : '';
                    $platform->is_default = isset($data->isDefault) ? $data->isDefault : '';
                    $platform->solution_owner = isset($data->solutionOwner) ? $data->solutionOwner : '';
                    $platform->content = isset($data->content) ? $data->content : '';
                    $platform->logistics_feeBear_role = isset($data->ogisticsFeeBearRole)?$data->ogisticsFeeBearRole:'';
                    $platform->solution_type = isset($data->solutionType) ? $data->solutionType : '';
                    $platform->reached_time = isset($data->reachedTime) ? VHelper::_toDate($data->reachedTime): '';
                    $platform->status = isset($data->status) ? $data->status : '';
                    $platform->reached_type = isset($data->reachedType) ? $data->reachedType : '';
                    $platform->buyer_accept_time = isset($data->buyerAcceptTime) ? VHelper::_toDate($data->buyerAcceptTime) : '';
                    $platform->seller_accept_time = isset($data->sellerAcceptTimeisset) ? VHelper::_toDate($data->sellerAcceptTimeisset): '';
                    $platform->logistics_fee_amount = isset($data->logisticsFeeAmount) ? $data->logisticsFeeAmount : '';
                    $platform->logistics_fee_amount_currency = isset($data->logisticsFeeAmountCurrency) ? $data->logisticsFeeAmountCurrency : '';
                    return $platform->save();
                }
            }
        } catch (\Exception $e) {
            return false;
        }

    }
}    