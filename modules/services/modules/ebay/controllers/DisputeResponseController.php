<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/19 0019
 * Time: 上午 9:49
 */

namespace app\modules\services\modules\ebay\controllers;

use app\modules\mails\models\EbayCancellationsResponse;
use app\modules\mails\models\EbayInquiryResponse;
use app\modules\mails\models\EbayReturnsRequestsResponse;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\systems\models\EbayAccount;
use yii\web\Controller;
class DisputeresponseController extends Controller
{
    public function actionCancellation()
    {
        if(isset($_REQUEST['account']))
        {
            $account = $_REQUEST['account'];
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $currentTime = date('Y-m-d H:i:s');
                $responseIds = array_column(EbayCancellationsResponse::find()->select('id')->where('account_id=:account_id and status=0 and (lock_status=0 or (lock_status=1 and TIMESTAMPDIFF(MINUTE,lock_time,"'.$currentTime.'")>90))',[':account_id'=>$account])->asArray()->all(),'id');
                if(!empty($responseIds))
                {
                    $ebayAccountModel = EbayAccount::findOne((int)$account);
                    EbayCancellationsResponse::updateAll(['lock_status'=>1,'lock_time'=>$currentTime],['id'=>$responseIds]);
                    $responseModels = EbayCancellationsResponse::find()->where(['id'=>$responseIds])->all();
                    set_time_limit(6600);
                    foreach($responseModels as $responseModel)
                    {
                        switch ($responseModel->type)
                        {
                            case '1':
                                $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/cancellation/'.$responseModel->cancel_id.'/approve','post');
                                $api->responseHeader = true;
                                $response = $api->sendHttpRequest();
                                break;
                            case '2':
                                $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/cancellation/'.$responseModel->cancel_id.'/reject','post');
                                $api->setData(['shipmentDate'=>['value'=>$responseModel->shipment_date.'.000Z'],'trackingNumber'=>$responseModel->tracking_number]);
                                $api->responseHeader = true;
                                $response = $api->sendHttpRequest();
                        }
                        if(empty($response))
                        {
                            $responseModel->status = 0;
                            $responseModel->error = '无返回值';
                        }
                        else
                        {
                            $responseArray = explode(PHP_EOL,$response);
                            if(preg_match('/\s20(0|1)\s/',$responseArray[0]))
                            {
                                $responseModel->status = 1;
                            }
                            else
                            {
                                $responseModel->status = 0;
                                $responseModel->error = $response;
                            }
                        }
                        $responseModel->save();
                    }
                }
            }
        }
        else
        {
            $accounts = EbayCancellationsResponse::find()->select('account_id')->distinct()->where('status=0')->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/disputeresponse/cancellation','account'=>$accountV['account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('没有数据');
            }
        }
    }

    public function actionInquiry()
    {
        if(isset($_REQUEST['account']))
        {
            $account = $_REQUEST['account'];
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $currentTime = date('Y-m-d H:i:s');
                $responseIds = array_column(EbayInquiryResponse::find()->select('id')->where('account_id=:account_id and status=0 and (lock_status=0 or (lock_status=1 and TIMESTAMPDIFF(MINUTE,lock_time,"'.$currentTime.'")>90))',[':account_id'=>$account])->asArray()->all(),'id');
                if(!empty($responseIds))
                {
                    $ebayAccountModel = EbayAccount::findOne((int)$account);
                    EbayInquiryResponse::updateAll(['lock_status'=>1,'lock_time'=>$currentTime],['id'=>$responseIds]);
                    $responseModels = EbayInquiryResponse::find()->where(['id'=>$responseIds])->all();
                    set_time_limit(6600);
                    foreach($responseModels as $responseModel)
                    {
                        switch ($responseModel->type)
                        {
                            case '1':
                                $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$responseModel->inquiry_id.'/send_message','post');
                                if(!empty($responseModel->content))
                                    $api->setData(['message'=>['content'=>$responseModel->content]]);
                                $api->responseHeader = true;
                                $response = $api->sendHttpRequest('json');
                                if(empty($response))
                                {
                                    $responseModel->status = 0;
                                    $responseModel->error = '无返回值';
                                }
                                else
                                {
                                    $responseArray = explode(PHP_EOL,$response);
                                    if(preg_match('/\s20(0|1)\s/',$responseArray[0]))
                                    {
                                        $responseModel->status = 1;
                                    }
                                    else
                                    {
                                        $responseModel->status = 0;
                                        $responseModel->error = $response;

                                    }
                                }

                                break;
                            case '2':
                                $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$responseModel->inquiry_id.'/issue_refund','post');
                                if(!empty($responseModel->content))
                                    $api->setData(['message'=>['content'=>$responseModel->content]]);
                                $response = $api->sendHttpRequest();
                                if(empty($response))
                                {
                                    $responseModel->status = 0;
                                    $responseModel->error = '无返回值';
                                }
                                else
                                {
                                    $responseModel->status = 1;
                                    $responseModel->error = '';
                                    $responseModel->refund_source = $response->refundResult->refundSource;
                                    $responseModel->refund_status = $response->refundResult->refundStatus;
                                }
                                break;
                            case '3':
                                $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$responseModel->inquiry_id.'/provide_shipment_info','post');
                                if(!empty($responseModel->content))
                                    $data['message'] = ['content'=>$responseModel->content];
                                $data['shippingCarrierName'] = $responseModel->shipping_carrier_name;
                                $data['shippingDate'] = ['value'=>$responseModel->shipping_date];
                                $data['trackingNumber'] = $responseModel->tracking_number;
                                $api->setData($data);
                                $api->responseHeader = true;
                                $response = $api->sendHttpRequest();
                                if(empty($response))
                                {
                                    $responseModel->status = 0;
                                    $responseModel->error = '无返回值';
                                }
                                else
                                {
                                    $responseArray = explode(PHP_EOL,$response);
                                    if(preg_match('/\s20(0|1)\s/',$responseArray[0]))
                                    {
                                        $responseModel->status = 1;
                                    }
                                    else
                                    {
                                        $responseModel->status = 0;
                                        $responseModel->error = $response;

                                    }
                                }
                                break;
                            case '4':
                                $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$responseModel->inquiry_id.'/escalate','post');
                                if(!empty($responseModel->content))
                                    $data['message'] = ['content'=>$responseModel->content];
                                $data['escalationReason'] = EbayInquiryResponse::$escalationReasonMap[$responseModel->escalation_reason];
                                $api->setData($data);
                                $api->responseHeader = true;
                                $response = $api->sendHttpRequest();
                                if(empty($response))
                                {
                                    $responseModel->status = 0;
                                    $responseModel->error = '无返回值';
                                }
                                else
                                {
                                    $responseArray = explode(PHP_EOL,$response);
                                    if(preg_match('/\s20(0|1)\s/',$responseArray[0]))
                                    {
                                        $responseModel->status = 1;
                                    }
                                    else
                                    {
                                        $responseModel->status = 0;
                                        $responseModel->error = $response;

                                    }
                                }
                                break;
                        }
                        $responseModel->save();
                    }

                }
            }
        }
        else
        {
            $accounts = EbayInquiryResponse::find()->select('account_id')->distinct()->where('status=0')->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/disputeresponse/inquiry','account'=>$accountV['account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('没有数据');
            }
        }
    }

    public function actionReturn()
    {
        if(isset($_REQUEST['account']))
        {
            $account = $_REQUEST['account'];
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $currentTime = date('Y-m-d H:i:s');
                $responseIds = array_column(EbayReturnsRequestsResponse::find()->select('id')->where('account_id=:account_id and status=0 and (lock_status=0 or (lock_status=1 and TIMESTAMPDIFF(MINUTE,lock_time,"'.$currentTime.'")>90))',[':account_id'=>$account])->all(),'id');
                if(!empty($responseIds))
                {
                    $ebayAccountModel = EbayAccount::findOne((int)$account);
                    EbayReturnsRequestsResponse::updateAll(['lock_status'=>1,'lock_time'=>$currentTime],['id'=>$responseIds]);
                    $responseModels = EbayReturnsRequestsResponse::find()->where(['id'=>$responseIds])->all();
                    set_time_limit(6600);
                    foreach($responseModels as $responseModel)
                    {
                        switch ($responseModel->type)
                        {
                            case '1':
                                $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/'.$responseModel->return_id.'/send_message','post');
                                $api->setData(['message'=>['content'=>$responseModel->content]]);
                                $api->responseHeader = true;
                                $response = $api->sendHttpRequest();
                                if(empty($response))
                                {
                                    $responseModel->status = 0;
                                    $responseModel->error = '无返回值';
                                }
                                else
                                {
                                    $responseArray = explode(PHP_EOL,$response);
                                    if(preg_match('/\s20(0|1)\s/',$responseArray[0]))
                                    {
                                        $responseModel->status = 1;
                                    }
                                    else
                                    {
                                        $responseModel->status = 0;
                                        $responseModel->error = $response;

                                    }
                                }
                                break;
                            case '2':
                                $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/'.$responseModel->return_id.'/issue_refund','post');
                                $api->setData(['comments'=>['content'=>$responseModel->content],'refundDetail'=>['itemizedRefundDetail'=>[['refundAmount'=>['value'=>$responseModel->ship_cost,'currency'=>$responseModel->currency],'refundFeeType'=>'ORIGINAL_SHIPPING'],['refundAmount'=>['value'=>$responseModel->subtotal_price,'currency'=>$responseModel->currency],'refundFeeType'=>'PURCHASE_PRICE']],'totalAmount'=>['currency'=>$responseModel->currency,'value'=>$responseModel->refund_amount]]]);
                                $response = $api->sendHttpRequest();
                                if(empty($response))
                                {
                                    $responseModel->status = 0;
                                    $responseModel->error = '无返回值';
                                }
                                else
                                {
                                    $responseModel->refund_status = $response->refundStatus;
                                    $responseModel->status = 1;
                                }
                                break;
                            case '3':
                                $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/return/'.$responseModel->return_id.'/issue_refund','post');
                                $api->setData(['comments'=>['content'=>$responseModel->content],'refundDetail'=>['itemizedRefundDetail'=>[['refundAmount'=>['value'=>$responseModel->refund_amount,'currency'=>$responseModel->currency],'refundFeeType'=>'OTHER']],'totalAmount'=>['currency'=>$responseModel->currency,'value'=>$responseModel->refund_amount]]]);
                                $response = $api->sendHttpRequest();
                                if(empty($response))
                                {
                                    $responseModel->status = 0;
                                    $responseModel->error = '无返回值';
                                }
                                else
                                {
                                    $responseModel->refund_status = $response->refundStatus;
                                    $responseModel->status = 1;
                                }
                        }
                        $responseModel->save();
                    }
                }


            }
        }
        else
        {
            $accounts = EbayReturnsRequestsResponse::find()->select('account_id')->distinct()->where('status=0')->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/disputeresponse/return','account'=>$accountV['account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('没有数据');
            }
        }
    }
}