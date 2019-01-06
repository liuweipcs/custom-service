<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/11 0011
 * Time: 下午 3:51
 */

namespace app\modules\services\modules\walmart\controllers;

use app\common\VHelper;
use app\modules\accounts\models\EbayCaseRefund;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\mails\models\EbayCase;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayCaseResponse;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\orders\models\Order;
use app\modules\products\models\EbaySiteMapAccount;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\systems\models\EbayAccount;
use PhpImap\Exception;
use yii\helpers\Json;
use yii\helpers\Url;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\AutoCode;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\systems\models\EbayApiTask;
use app\components\Controller;
class RefundController extends Controller
{
    private $ebayAccountModel;
    private $accountId;
    private $apiTaskModel;
    private $errorCode = 0;

    private $send_failure_times = 2;

    public function actionRefund()
    {

    }

    private function requestXmlBody($platform_order_id,$after_sale_refund)
    {
        $refund_details = json_decode($after_sale_refund->refund_detail,true);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                    <orderRefund
                      xmlns:ns2="http://walmart.com/mp/v3/orders"
                      xmlns:ns3="http://walmart.com/">
                      <purchaseOrderId>'.$platform_order_id.'</purchaseOrderId>
                      <orderLines>';
        $i = 1;
        foreach($refund_details as $k => $refund_detail)
        {
            $xml .= '<orderLine>
                        <lineNumber>'.$k.'</lineNumber>
                        <refunds>
                            <refund>
                                <refundComments>'.$after_sale_refund->remark.'</refundComments>
                                <refundCharges>
                                    <refundCharge>
                                        <refundReason>
                                            '.$refund_detail['reason_code'].'
                                        </refundReason>
                                        <charge>
                                            <chargeType>PRODUCT</chargeType>
                                            <chargeName>Item Price</chargeName>
                                            <chargeAmount>
                                                <currency>'.$after_sale_refund->currency.'</currency>
                                                <amount>-'.$refund_detail['item_price_amount'].'</amount>
                                            </chargeAmount>';
            if(isset($refund_detail['item_tax_amount']) && !empty($refund_detail['item_tax_amount']))
            {
                $xml .= '<tax>
                            <taxName>Item Price Tax</taxName>
                            <taxAmount>
                                <currency>'.$after_sale_refund->currency.'</currency>
                                <amount>-'.$refund_detail['item_tax_amount'].'</amount>
                            </taxAmount>
                        </tax>';
            }
            $xml .= '</charge></refundCharge>';
            if(isset($refund_detail['item_shipping_amount']) && !empty($refund_detail['item_shipping_amount']))
            {
                $xml .= '<refundCharge>
                            <refundReason>TaxExemptCustomer</refundReason>
                            <charge>
                                <chargeType>SHIPPING</chargeType>
                                <chargeName>Shipping Price</chargeName>
                                <chargeAmount>
                                  <currency>'.$after_sale_refund->currency.'</currency>
                                  <amount>-'.$refund_detail['item_shipping_amount'].'</amount>
                                </chargeAmount>';
                if(isset($refund_detail['shipping_tax_amount']) && !empty($refund_detail['shipping_tax_amount']))
                {
                    $xml .= '<tax>
                                <taxName>Shipping Tax</taxName>
                                <taxAmount>
                                    <currency>'.$after_sale_refund->currency.'</currency>
                                    <amount>-'.$refund_detail['shipping_tax_amount'].'</amount>
                                </taxAmount>
                            </tax>';
                }
                $xml .= '</charge>
                                    </refundCharge>';
            }
            $xml .= '                       
                                </refundCharges>
                            </refund>
                        </refunds>
                    </orderLine>
                    ';
            $i++;
        }
        $xml .= '</orderLines>
            </orderRefund>';
        return $xml;
    }
}