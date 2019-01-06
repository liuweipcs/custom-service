<?php
namespace app\modules\mails\models;
use app\components\Model;
use app\common\VHelper;
class AliexpressDisputeDetail extends MailsModel
{
     
    public static function tableName()
    {
        return '{{%aliexpress_dispute_detail}}';
    }
    /*
     * 获取纠纷详情
     * */
    public function getInfo($platform_dispute_id){
        return self::find()->where(['platform_dispute_id'=>$platform_dispute_id])->asArray()->one();
    }
    /*
     * 新增数据
     * */
    public function newlyAdded($account_id,$dispute_id,$data = null){
        try{
            if(!empty($data)){

                $platform = self::find()->where(['platform_dispute_id'=>$data->id])->one();
                if(isset($data->productPrice->amount) && isset($data->productPrice->currencyCode)){
                    $product_price = $data->productPrice->amount.$data->productPrice->currencyCode;
                }
                if(isset($data->refundMoneyMax->amount) && isset($data->refundMoneyMax->currencyCode)){
                    $refundMoneyMax = $data->refundMoneyMax->amount.$data->refundMoneyMax->currencyCode;
                }
                if(isset($data->refundMoneyMaxLocal->amount) && isset($data->refundMoneyMaxLocal->currencyCode)){
                    $refundMoneyMaxLocal = $data->refundMoneyMaxLocal->amount.$data->refundMoneyMaxLocal->currencyCode;
                }
                /*新增*/
                if(empty($platform)){
                    $AliexpressDisputeDetail = new AliexpressDisputeDetail();
                    $AliexpressDisputeDetail->platform_dispute_id = $data->id;
                    $AliexpressDisputeDetail->dispute_id = $dispute_id;
                    $AliexpressDisputeDetail->account_id = $account_id;
                    $AliexpressDisputeDetail->platform_parent_order_id = isset($data->parentOrderId)?$data->parentOrderId:'';
                    $AliexpressDisputeDetail->platform_order_id = isset($data->orderId)?$data->orderId:'';
                    $AliexpressDisputeDetail->buyer_aliid = isset($data->buyerAliid)?$data->buyerAliid:'';
                    $AliexpressDisputeDetail->issue_reason_id = isset($data->issueReasonId)?$data->issueReasonId:'';
                    $AliexpressDisputeDetail->issue_reason = isset($data->issueReason)?$data->issueReason:'';
                    $AliexpressDisputeDetail->issue_status = isset($data->issueStatus)?$data->issueStatus:'';
                    $AliexpressDisputeDetail->refund_money_max = isset($refundMoneyMax)?$refundMoneyMax:0;
                    $AliexpressDisputeDetail->refund_money_max_local = isset($refundMoneyMaxLocal)?$refundMoneyMaxLocal:0;
                    $AliexpressDisputeDetail->product_name = isset($data->productName)?$data->productName:'';
                    $AliexpressDisputeDetail->product_price = isset($product_price)?$product_price:0;
                    $AliexpressDisputeDetail->buyer_return_logistics_company = isset($data->buyerReturnLogisticsCompany)?$data->buyerReturnLogisticsCompany:'';
                    $AliexpressDisputeDetail->buyer_return_no = isset($data->buyerReturnNo)?$data->buyerReturnNo:'';
                    $AliexpressDisputeDetail->buyer_return_logistics_lp_no = isset($data->buyerReturnLogisticsLpNo)?$data->buyerReturnLogisticsLpNo:'';
                    $AliexpressDisputeDetail->gmt_create = VHelper::_toDate($data->gmtCreate);
                    $AliexpressDisputeDetail->save();
                }else{
                    /*编辑*/
                    $platform->issue_reason_id = isset($data->issueReasonId)?$data->issueReasonId:'';
                    $platform->issue_reason = isset($data->issueReason)?$data->issueReason:'';
                    $platform->issue_status = isset($data->issueStatus)?$data->issueStatus:'';
                    $platform->refund_money_max = isset($refundMoneyMax)?$refundMoneyMax:0;
                    $platform->refund_money_max_local = isset($refundMoneyMaxLocal)?$refundMoneyMaxLocal:0;
                    $platform->product_name = isset($data->productName)?$data->productName:'';
                    $platform->product_price = isset($product_price)?$product_price:0;
                    $platform->buyer_return_logistics_company = isset($data->buyerReturnLogisticsCompany)?$data->buyerReturnLogisticsCompany:'';
                    $platform->buyer_return_no = isset($data->buyerReturnNo)?$data->buyerReturnNo:'';
                    $platform->buyer_return_logistics_lp_no = isset($data->buyerReturnLogisticsLpNo)?$data->buyerReturnLogisticsLpNo:'';
                    $platform->save();
                }
                $id = isset($this->id)?$this->id:$platform->id;
                /*买家协商方案*/
                if(!empty($data->buyerSolutionList)){
                    foreach ($data->buyerSolutionList as $value){
                        $DisputeSolution = new AliexpressDisputeSolution();
                        $DisputeSolution->newlyAdded($id,'buyer',$value);
                    }
                }
                /*卖家协商方案*/
                if(!empty($data->sellerSolutionList)){
                    foreach ($data->sellerSolutionList as $value){
                        $DisputeSolution = new AliexpressDisputeSolution();
                        $DisputeSolution->newlyAdded($id,'seller',$value);
                    }
                }
                /*平台协商方案*/
                if(!empty($data->platformSolutionList)){
                    foreach ($data->platformSolutionList as $value){
                        $DisputeSolution = new AliexpressDisputeSolution();
                        $DisputeSolution->newlyAdded($id,'platform',$value);
                    }
                }
            }
        }catch (\Exception $e) {
            return false;
        }
    }
}    