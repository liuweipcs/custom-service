<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/5 =
 * Time: 下午 14:36
 */
namespace app\modules\mails\controllers;

use app\components\Controller;
use app\common\VHelper;
use app\modules\mails\models\EbayDisputes;
use app\modules\mails\models\MailTemplate;
use app\modules\mails\models\EbayDisputesResponse;
use app\modules\orders\models\Order;

class EbaydisputesController extends Controller
{
    public function actionList()
    {
        
        error_reporting(E_ALL);
        $model = new EbayDisputes();

        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('list',[
            'model'=>$model,
            'dataProvider' => $dataProvider,
        ]);
    }
    

    public function actionShoworder(){
    
        //        exit;
    
        $this->isPopup = true;
        //        var_dump($_REQUEST);
        //        exit;
        //        $passid='98d';
        $order_id = $this->request->getQueryParam('order_id');
        $platform ='EB';
    
        $retuelt = [];
        if($platform && $order_id){

            $retuelt = Order::getOrderStack($platform,$order_id);

            $retuelt = json_decode(json_encode($retuelt),true);

//            $string = 'order_id='.$order_id.'&token=5E17C4488C2AC591';
//            $retuelt = VHelper::getSendreQuest($string,true,$platform);
        }

//        var_dump($order_id);
//        exit;
        return $this->render('showorder',
            [
                'info'              => $retuelt,
                'order_id'          => $order_id
            ]);
    }
    
    
    public function actionSavehandle(){
        
        $sealmodel = New MailTemplate();
        $disputes_response_model= New EbayDisputesResponse();

        $params = \Yii::$app->request->getBodyParams();
        
        $arr=[];
        $arr['respond_type']=   $params['operation'];
        $arr['message_text']=   $params['msgcontent'];
        $arr['shipping_time']=  $params['ship_date'];
        $arr['shipment_track_number'] =   $params['tracking_num'];
        
        
//        $arr['respond_type']=2;
        
        if( $arr['respond_type']==3){
                if($params['shipping']==1){
                    $arr['shipping_carrier_used']=$params['ship_company_name_no_track'];
                    $arr['shipping_time']=$params['ship_date'];
                }else{
                    $arr['shipping_carrier_used']=$params['ship_company_name_with_track'];
                    $arr['shipment_track_number']=$params['tracking_num'];                    
                }
        }
    
        $result = $sealmodel->Add($disputes_response_model, $arr);

        if($result){
            return true;
        }else{
            return false;
        }

        
    }
    
    public function actionSavesnadhandle(){
        $sealmodel = New MailTemplate();
        $disputes_response_model= New EbayDisputesResponse();
        $params = \Yii::$app->request->getBodyParams();
//        var_dump($params);
//        exit;
        $arr=[];
        $arr['respond_type'] =   $params['operation'];
        $arr['message_text'] =   $params['msgcontent'];
        $arr['partial_refund_amount'] =  $params['partialrefund'];
        
        if($arr['respond_type'] !=5){
            unset($arr['partialrefund']);
        }
        
        $result = $sealmodel->Add($disputes_response_model, $arr);
        
        if($result){
            return true;
        }else{
            return false;
        }
        
    }
    
}