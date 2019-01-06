<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/26 0026
 * Time: 下午 4:14
 */

namespace app\modules\services\modules\walmart\models;

use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\services\modules\walmart\components\WalmartApiAbstract;
use app\modules\systems\models\EbayAccount;
use app\modules\mails\models\EbayFeedback;
use PhpImap\Exception;

class Refund extends WalmartApiAbstract
{
    public $url = "https://marketplace.walmartapis.com/v3/orders/";

    public $after_sale_refund;

    public function __construct($accountName)
    {
        $platformCode = Platform::PLATFORM_CODE_WALMART;
        $this->accountInfo = Account::getAccountFromErp($platformCode, $accountName);
        $this->consumerId = $this->accountInfo->consumer_id;
        $this->privateKey = $this->accountInfo->private_key;
        $this->channelType = $this->accountInfo->channel_type;
        $this->method = 'POST';
//        $this->accountInfo->consumer_id = '0e923563-158e-430a-9e6f-91152d05a32b';
//        $this->accountInfo->private_key = 'MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAJtsRz+EhHJcp016/84CRruVRp6lx8yqe3ccH4n5FhP3+57bPrdLU0xlXC6pBCL89bkQieOiD1lnW6z9LEXCWrY3kY76c6c4Q05PAHw8K2rtqbv8v9ujlwDphkw6PzOg3qypmhdLU6E2uM5J0l+7eHv5dnQpqQLZvVrp/iGVLonnAgMBAAECgYAgRi7RYMpEGTtgmn8OH2jiwQ/GF/TSakBzLnLaKtBL2z3z8eEnHMwyXAX1ZoGGZnL8OBP6Igf/77eYx4XvAnnlcnzKTU/wBDffM0q4HSbLGrATO/tZ6Tth7sWwTQJnfr3zJlN6U8uQxvhW3hASvH3HfedMUJWTkhvNpSSyedG7CQJBAPCeZ+i0ve6bwt0dpLEipT4fOVMaNyuZ+xJl35H5ntpaGLW1I0UAixIa7nPAdEMIekLf5SqApVusSogTZ8wdfJUCQQClW7CTjndwq7aAFwj7Jmnlu370sTaZ3JIS8/j0vpCFeseHCqg6zzqWvcfwRmM/sZBJ048OK8RgTOzssthczhGLAkAzeW+5NJE9Lk0tiF3nFwZKl9tnj8Alr7cqZGjDjacSBxwqIyn8ZPVbVi+Uy6MThAjDraoUwZieV+lJ2vzliZlRAkBl3b9Al3JjGZU7AXXJ6lcwbDoAma8uR+BBBsUmWMMFR6blPR535DOOq2khTutTUJq3sDmfnDzEqn8GLgL14QiTAkBjiquysy+hZkzbbpiKkZ7kKNCD03uyY7J9aGznFjr6afpcY+TuMe2j5jwQoQqlaI0GG7nAAcNCbrMNbMgY39Ok';
//        $this->accountInfo->channel_type = '0f3e4dd4-0514-4346-b39d-af0e00ea066d';
    }

    public function setRequest()
    {
//        $this->config['consumerId'] = $this->accountInfo->consumer_id;
//        $this->config['privateKey'] = $this->accountInfo->private_key;
//        $this->config['channelType'] = $this->accountInfo->channel_type;
        $this->consumerId = $this->accountInfo->consumer_id;
        $this->privateKey = $this->accountInfo->private_key;
        $this->channelType = $this->accountInfo->channel_type;
        $this->method = 'POST';
        
        
    }

    
    
    
    public function handleResponse($data)
    {
        $this->url .= $data['platform_order_id'].'/refund';
        $this->after_sale_refund = $data;
        $this->getHeaders('application/xml');
        if($this->requestXmlBody())
        {
            return $this->sendHttpRequest();
        }
    }
    
    
    
    
    public function requestJsonBodytest()
    {
        $after_sale_refund = $this->after_sale_refund;
        $sendXml = [];
        $sendXml['orderRefund'] = [];
        $sendXml['orderRefund']['purchaseOrderId'] = $after_sale_refund['platform_order_id'];
//        $orderLines = '';
        $refund_details = $after_sale_refund['sku'];
        $refunds = [];
        $orderLine = [];
        $orderLine['lineNumber'] = '1';

        $refund =[];
        $refund['refundComments'] = $after_sale_refund['refundComments'];
        $refund['refundCharges'] = [];
        $refundCharge = [];
            
        foreach($refund_details as $refund_detail)
        {
            $currency = $refund_detail['currency'];
            $refundCharge['refundReason'] = $refund_detail['refundReason'];
            $charge = [];
            if($refund_detail['chargeType'] == 'PRODUCT'){
                $charge['chargeType'] = 'PRODUCT';
                $charge['chargeName'] = 'Item Price';
                $charge['chargeAmount']['currency'] = $refund_detail['currency'];
                $charge['chargeAmount']['amount'] = - (float)$refund_detail['amount'];
            }
            $charge['tax']['taxName'] = 'Item Price Tax';
            $charge['tax']['taxAmount']['currency'] = $currency;
            $charge['tax']['taxAmount']['amount'] = -0;
            $refundCharge['charge'] = $charge;
//            $refundCharge['tax'] = "";
        }
        
        $refund['refundCharges']['refundCharge'] = [$refundCharge];
        $refunds['refund'] = [$refund];
        $orderLine['refunds'] = $refunds;
        $sendXml['orderRefund']['orderLines']['orderLine'] = [$orderLine];
        
        $this->sendXml = json_encode($sendXml);
//          $this->sendXml = '{
//  "orderRefund": {
//    "purchaseOrderId": "2781657082007",
//    "orderLines": {
//      "orderLine": [
//        {
//          "lineNumber": "1",
//          "refunds": {
//            "refund": [
//              {
//                "refundComments": "test test",
//                "refundCharges": {
//                  "refundCharge": [
//                    {
//                      "refundReason": "Customer Changed Mind",
//                      "charge": {
//                        "chargeType": "PRODUCT",
//                        "chargeName": "Item Price",
//                        "chargeAmount": {
//                          "currency": "USD",
//                          "amount": -11.39
//                        },
//                        "tax": {
//                          "taxName": "Item Price Tax",
//                          "taxAmount": {
//                            "currency": "USD",
//                            "amount": -0.00
//                          }
//                        }
//                      }
//                    }
//                  ]
//                }
//              }
//            ]
//          }
//        }
//      ]
//    }
//  }
//}';
//        echo '<pre>';
//        var_dump($this->sendXml);
//        echo '</pre>';
//        die;
        return true;
    }
    
    
    
    public function requestXmlBody(){
        $items = [];
        $itemsInfos = $this->after_sale_refund;
        $platformOrderId = $itemsInfos['platform_order_id'];//平台订单ID
        $refundComments = $itemsInfos['refundComments'];
        $item_list = $itemsInfos['returnDetail'];
        if (empty($item_list)) return false;
        $orderLine = '';
        $orderLine .= '<ns2:purchaseOrderId>'.$platformOrderId.'</ns2:purchaseOrderId>
            <ns2:orderLines>';
        
        foreach ($item_list as $val) {
            $items[$val['item_id']][] = $val;
        }
        
        foreach ($items as $key => $Items) {
            $orderLine .='<ns2:orderLine>
                <ns2:lineNumber>'.$key.'</ns2:lineNumber>
                <ns2:refunds>
                    <ns2:refund>
                        <ns2:refundComments>'.$refundComments.'</ns2:refundComments>
                        <ns2:refundCharges>';
                            foreach ($Items as $item) {
                                if(!empty($item['amount']) && $item['amount'] > 0){
                                    $orderLine .='<ns2:refundCharge>
                                                    <ns2:refundReason>'.$item['refundReason'].'</ns2:refundReason>
                                                    <ns2:charge>';
                                                    if($item['chargeType'] == 'PRODUCT'){
                                                      $orderLine .='<ns2:chargeType>PRODUCT</ns2:chargeType><ns2:chargeName>Item Price</ns2:chargeName>';
                                                    }else{
                                                      $orderLine .='<ns2:chargeType>SHIPPING</ns2:chargeType><ns2:chargeName>Shipping Price</ns2:chargeName>';  
                                                    }
                                                    $orderLine .='<ns2:chargeAmount>
                                                        <ns2:currency>'.$item['currency'].'</ns2:currency>
                                                        <ns2:amount>-'.$item['amount'].'</ns2:amount>
                                                      </ns2:chargeAmount>';
                                                      if($item['tax']) {
                                                          $orderLine .= '<ns2:tax>
                                                        <ns2:taxName>Item Price Tax</ns2:taxName>
                                                        <ns2:taxAmount>
                                                          <ns2:currency>'.$item['currency'].'</ns2:currency>
                                                          <ns2:amount>-'.$item['tax'].'</ns2:amount>
                                                        </ns2:taxAmount>
                                                      </ns2:tax>';
                                                      }
                                                      $orderLine .= '
                                                          </ns2:charge>
                                                    </ns2:refundCharge>';
                                }
                            }
            $orderLine .= '
                        </ns2:refundCharges>
                    </ns2:refund>
                  </ns2:refunds>
            </ns2:orderLine>';
        }        
        $orderLine .= '</ns2:orderLines>';
        
        
        $this->sendXml .= '<?xml version="1.0" encoding="UTF-8"?> 
                <ns2:orderRefund xmlns:ns2="http://walmart.com/mp/v3/orders" xmlns:ns3="http://walmart.com/">
                    ' . $orderLine . '
                </ns2:orderRefund>';
        
//        echo $this->sendXml;die;
        return TRUE;
    }
    
    /**
     * 
     * @param type $orderIdList
     * @return boolean
     * 
     */    
    public function orderRefund($orderIdList) {
        if (empty($orderIdList)) return false;
        $orderLine = '';
        $orderLine .= '<ns2:purchaseOrderId>'.$orderIdList['order_id'].'</ns2:purchaseOrderId>
            <ns2:orderLines>';
        foreach ($orderIdList['item_list'] as $item) {
            $orderLine .='<ns2:orderLine>
            <ns2:lineNumber>'.$item['line'].'</ns2:lineNumber>
            <ns2:refunds>
            <ns2:refund>
              <ns2:refundComments>'.$orderIdList['comments'].'</ns2:refundComments>
              <ns2:refundCharges>';
            if(!empty($item['price']) && $item['price'] > 0){
                $orderLine .='
              <ns2:refundCharge>
              <ns2:refundReason>'.$orderIdList['item_reason'].'</ns2:refundReason>
              <ns2:charge>
                <ns2:chargeType>PRODUCT</ns2:chargeType>
                <ns2:chargeName>Item Price</ns2:chargeName>
                <ns2:chargeAmount>
                  <ns2:currency>'.$orderIdList['currency'].'</ns2:currency>
                  <ns2:amount>-'.$item['price'].'</ns2:amount>
                </ns2:chargeAmount>';
                if (!empty($item['item_price_tax'])) {
                    $orderLine .= '<ns2:tax>
                  <ns2:taxName>Item Price Tax</ns2:taxName>
                  <ns2:taxAmount>
                    <ns2:currency>'.$orderIdList['currency'].'</ns2:currency>
                    <ns2:amount>-'.$item['item_price_tax'].'</ns2:amount>
                  </ns2:taxAmount>
                </ns2:tax>';
                }
                $orderLine .= '
                    </ns2:charge>
              </ns2:refundCharge>';
            }
            if (!empty($item['shipping_price'])) {
                $orderLine .= '<ns2:refundCharge>
              <ns2:refundReason>'.$orderIdList['shipping_reason'].'</ns2:refundReason>
              <ns2:charge>
              <ns2:chargeType>SHIPPING</ns2:chargeType>
              <ns2:chargeName>Shipping Price</ns2:chargeName>
              <ns2:chargeAmount>
              <ns2:currency>'.$orderIdList['currency'].'</ns2:currency>
              <ns2:amount>-'.$item['shipping_price'].'</ns2:amount>
              </ns2:chargeAmount>
              </ns2:charge>
              </ns2:refundCharge>';
            }
            $orderLine .= '
              </ns2:refundCharges>
              </ns2:refund>
              </ns2:refunds>
            </ns2:orderLine>';
        }        
        $orderLine .= '</ns2:orderLines>';
        return array(
                'version' => 'v3',
                'method' => 'POST',
                'url' => '/orders/' . $orderIdList['order_id'] . '/refund',
                'body' => '<?xml version="1.0" encoding="UTF-8"?> 
                <ns2:orderRefund xmlns:ns2="http://walmart.com/mp/v3/orders" xmlns:ns3="http://walmart.com/">
                    ' . $orderLine . '
                </ns2:orderRefund>'
        );
    }

}