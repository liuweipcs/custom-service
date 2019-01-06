<?php
namespace app\modules\services\modules\aliexpress\models;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressDisputeNegotiationDetail;
use app\modules\mails\models\AliexpressDisputeArbitrationList;
use app\modules\accounts\models\app\modules\accounts\models;
use app\modules\mails\models\AliexpressDisputeDetail;
/**
 * 速卖通订单纠纷列表
 */
class Findissuedetails
{
    
    public $disputeid;
    public $id;
    public $accountid;
    protected $access;
    protected $app_key;
    protected $secret_key;
    protected $shortName;
    public function getIssueDetail($pass_data=null){
        
//        var_dump($pass_data);
//        exit;
       set_time_limit(60);
        $disputelistmodel= new AliexpressDisputeList();      
        $target_data=$disputelistmodel->find()->where(['caseid' =>$pass_data['issueId']])->one();

        $this->disputeid = $target_data['caseid'];
        $this->id        = $target_data['id']; 
        $this->accountid = $target_data['accountid'];  
       
//        $shortName = AliexpressAccount::findOne(['id'=>$this->accountid]);//获取账号的token信息
        $AliexpressAccountmodel= New AliexpressAccount();

        $shortName = $AliexpressAccountmodel->find()->where(['id'=>$pass_data['id']])->one();
//        var_dump($shortName);
//        exit;
             
        $this->shortName = $shortName->short_name;
        $this->access =  $shortName->access_token;
        $this->app_key = $shortName->app_key;
        $this->secret_key = $shortName->secret_key; 
        
//        echo $this->shortName.'<br>'.$this->access.'<br>'.$this->app_key.'<br>'.$this->secret_key;
//        exit;
        $this->runGetIssueDetail();      
    }
    
    public function runGetIssueDetail(){
        set_time_limit(60);
        //实例化这个类
        $orderObj = new WhereFindIssueDetail();
        $response_s = $orderObj
        ->setAccessToken($this->access)
        ->putOtherTextParam('app_key', $this->app_key)
        ->putOtherTextParam('secret_key', $this->secret_key)
        ->putOtherTextParam('issueId', $this->disputeid);
        
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        header('Content-Type:text/html; charset=utf-8;');
//        var_dump($response);
//        exit;
//        echo '买家方案start'.'<hr>';
//        var_dump($response->resultObject->buyerSolutionList);
//        echo '买家方案end'.'<hr>';
 //       exit;
 //       echo '卖家方案start'.'<hr>';
 //       var_dump($response->resultObject->sellerSolutionList);
 //       echo '卖家方案end'.'<hr>';        
//        var_dump($response->resultObject->platformSolutionList);
//        exit;
//        echo '<hr>';
 //       var_dump($response->resultObject->platformSolutionList);

 //       $model->disputeid='345';
  //      $model->save();
//  exit;
        if($response->success){
            
            $Aliexpress_dispute_detail_model            = New AliexpressDisputeDetail();//纠纷详情表
            //下载纠纷列表字段
            $dispute_id =$Aliexpress_dispute_detail_model->find()->where(['dispute_id' => sprintf("%.0f", $response->resultObject->id)])->one();
            if(!$dispute_id)
            {
            $Aliexpress_dispute_detail_model->accountid  =   $this->accountid;
            $Aliexpress_dispute_detail_model->order_id   =   sprintf("%.0f", $response->resultObject->orderId);
            $Aliexpress_dispute_detail_model->dispute_id =   sprintf("%.0f", $response->resultObject->id);
            $Aliexpress_dispute_detail_model->amount     =   $response->resultObject->refundMoneyMaxLocal->amount;
            $Aliexpress_dispute_detail_model->currencyCode = $response->resultObject->refundMoneyMaxLocal->currencyCode;
            $Aliexpress_dispute_detail_model->issueReasonId= $response->resultObject->issueReasonId;
            $Aliexpress_dispute_detail_model->buyerAliid   = $response->resultObject->buyerAliid;
            $Aliexpress_dispute_detail_model->issueStatus   = $response->resultObject->issueStatus;
            $Aliexpress_dispute_detail_model->issueReason   = $response->resultObject->issueReason;
            $Aliexpress_dispute_detail_model->productName   = $response->resultObject->productName;
            $Aliexpress_dispute_detail_model->gmtCreate     = $response->resultObject->gmtCreate;            
            $Aliexpress_dispute_detail_model->save();
            }
            //下载买家方案字段        
            foreach($response->resultObject->buyerSolutionList as $key=>$value){

                echo sprintf("%.0f", $value->id);
                $Aliexpress_dispute_negotiation_detail_model= New AliexpressDisputeNegotiationDetail();//纠纷谈判表
                $buyer_negotiation_id = $Aliexpress_dispute_negotiation_detail_model->find()->where(['solution_id' => sprintf("%.0f", $value->id)])->one();
                echo '************买家字段start******************';
                var_dump($buyer_negotiation_id);
                echo '************买家字段 end ******************';
                if($buyer_negotiation_id==NULL){                                                    
                $Aliexpress_dispute_negotiation_detail_model->gmtModified = $value->gmtModified;
                $Aliexpress_dispute_negotiation_detail_model->disputeid   = sprintf("%.0f", $value->issueId);
                $Aliexpress_dispute_negotiation_detail_model->orderid     = sprintf("%.0f", $value->orderId);
                $Aliexpress_dispute_negotiation_detail_model->amount      = $value->refundMoney->amount;
                $Aliexpress_dispute_negotiation_detail_model->currencyCode= $value->refundMoney->currencyCode;
                $Aliexpress_dispute_negotiation_detail_model->gmtCreate   = $value->gmtCreate;
                $Aliexpress_dispute_negotiation_detail_model->gmtModified = $value->gmtModified;
                $Aliexpress_dispute_negotiation_detail_model->status      = $value->status;
                $Aliexpress_dispute_negotiation_detail_model->solutionType= $value->solutionType;
                $Aliexpress_dispute_negotiation_detail_model->solution_id = sprintf("%.0f", $value->id);
                $Aliexpress_dispute_negotiation_detail_model->content     = $value->content;
                $Aliexpress_dispute_negotiation_detail_model->solutionOwner=$value->solutionOwner;
                $Aliexpress_dispute_negotiation_detail_model->createDate   = date('Y-m-d H:i:s');
                $Aliexpress_dispute_negotiation_detail_model->save();
                        }               
            }
            
            

           //下载卖家方案字段
          foreach($response->resultObject->sellerSolutionList as $key=>$value){
              $Aliexpress_dispute_negotiation_detail_model= New AliexpressDisputeNegotiationDetail();//纠纷谈判表
              $seller_negotiation_id = $Aliexpress_dispute_negotiation_detail_model->find()->where(['solution_id' => sprintf("%.0f", $value->id)])->one();
              echo '************卖家字段start******************';
              var_dump($seller_negotiation_id);
              echo '************卖家字段 end ******************';              
              
              if($seller_negotiation_id==NULL){
             
              $Aliexpress_dispute_negotiation_detail_model->gmtModified = $value->gmtModified;
              $Aliexpress_dispute_negotiation_detail_model->disputeid   = sprintf("%.0f", $value->issueId);
              $Aliexpress_dispute_negotiation_detail_model->orderid     = sprintf("%.0f", $value->orderId);
              $Aliexpress_dispute_negotiation_detail_model->amount      = $value->refundMoney->amount;
              $Aliexpress_dispute_negotiation_detail_model->currencyCode= $value->refundMoney->currencyCode;
              $Aliexpress_dispute_negotiation_detail_model->gmtCreate   = $value->gmtCreate;
              $Aliexpress_dispute_negotiation_detail_model->gmtModified = $value->gmtModified;
              $Aliexpress_dispute_negotiation_detail_model->status      = $value->status;
              $Aliexpress_dispute_negotiation_detail_model->solutionType= $value->solutionType;
              $Aliexpress_dispute_negotiation_detail_model->solution_id = sprintf("%.0f", $value->id);
              $Aliexpress_dispute_negotiation_detail_model->content     = $value->content;
              $Aliexpress_dispute_negotiation_detail_model->solutionOwner=$value->solutionOwner;
              $Aliexpress_dispute_negotiation_detail_model->createDate   = date('Y-m-d H:i:s');
              $Aliexpress_dispute_negotiation_detail_model->save();
                            }
          }
          
          //下载平台方案字段
          foreach($response->resultObject->platformSolutionList as $key=>$value){
              $Aliexpress_dispute_arbitration_list_model  = New AliexpressDisputeArbitrationList();//纠纷仲裁表
              $platform_arbitration_id = $Aliexpress_dispute_arbitration_list_model->find()->where(['arbitrate_id' => sprintf("%.0f", $value->id)])->one();
              if(!$platform_arbitration_id){
         
              $Aliexpress_dispute_arbitration_list_model->orderid       = sprintf("%.0f", $value->orderId);
              $Aliexpress_dispute_arbitration_list_model->disputeid     = sprintf("%.0f", $value->issueId);
              $Aliexpress_dispute_arbitration_list_model->arbitrate_id  = sprintf("%.0f", $value->id);
              $Aliexpress_dispute_arbitration_list_model->sellerAliid   = $value->sellerAliid;
              $Aliexpress_dispute_arbitration_list_model->buyerAliid    = $value->buyerAliid;
              $Aliexpress_dispute_arbitration_list_model->solutionOwner = $value->solutionOwner;
              $Aliexpress_dispute_arbitration_list_model->gmtModified   = $value->gmtModified;
              $Aliexpress_dispute_arbitration_list_model->amount        = $value->refundMoney->amount;
              $Aliexpress_dispute_arbitration_list_model->currencyCode  = $value->refundMoney->currencyCode;
              $Aliexpress_dispute_arbitration_list_model->reachedType   = $value->reachedType;
              $Aliexpress_dispute_arbitration_list_model->gmtCreate     = $value->gmtCreate;
              $Aliexpress_dispute_arbitration_list_model->status        = $value->status;
              $Aliexpress_dispute_arbitration_list_model->solutionType  = $value->solutionType;
              $Aliexpress_dispute_arbitration_list_model->reachedTime   = $value->reachedTime;
              $Aliexpress_dispute_arbitration_list_model->version       = $value->version;
              $Aliexpress_dispute_negotiation_detail_model->createDate  = date('Y-m-d H:i:s');
              if($value->isDefault){
                  $Aliexpress_dispute_arbitration_list_model->isDefault ='1';
              }else{
                  $Aliexpress_dispute_arbitration_list_model->isDefault ='0';                  
              }
              $Aliexpress_dispute_arbitration_list_model->save();  

                    }
          }
                
        }
        
    }
    
    
   
}