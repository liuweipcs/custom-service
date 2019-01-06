<?php 
namespace app\modules\services\modules\paypal\models;

use PayPal\Api\Address;
use PayPal\Api\BillingInfo;
use PayPal\Api\Cost;
use PayPal\Api\Currency;
use PayPal\Api\Invoice;
use PayPal\Api\InvoiceAddress;
use PayPal\Api\InvoiceItem;
use PayPal\Api\MerchantInfo;
use PayPal\Api\PaymentTerm;
use PayPal\Api\Phone;
use PayPal\Api\ShippingInfo;
use PayPal\Api\CancelNotification;

class PaypalInvoice {

	public static function getApiContext($clientId,$clientSecret) {
		$apiContext = PaypalBootstrap::getApiContext($clientId,$clientSecret);
		return $apiContext;
	}

	public static function getInvoiceInfo($invoice_id, $apiContext){

		try {
		    $invoiceInfo = Invoice::get($invoice_id, $apiContext);
		} catch (\Exception $e) {
			$errorMsg = 'Send Invoice: Error on line '.$e->getLine().' in '.$e->getFile().': <b>'.$e->getMessage();
			return $errorMsg; 
		}
		return $invoiceInfo;
	}

	public static function createInvoice($sendEmail, $receiveEmail, $itemName = '', $amount = 0, $currency = 'USD', $note = '',$apiContext) {
		$invoice = new Invoice();
		
		$invoice->setMerchantInfo(new MerchantInfo())
		    ->setBillingInfo(array(new BillingInfo()))
		    ->setNote($note)
		    ->setPaymentTerm(new PaymentTerm())
		    ->setShippingInfo(new ShippingInfo());


		$invoice->getMerchantInfo()
    		->setEmail($sendEmail);

    	$invoice->getPaymentTerm()->setTermType("NET_45");

		$billing = $invoice->getBillingInfo();
		$billing[0]->setEmail($receiveEmail);

		$items = array();
		$items[0] = new InvoiceItem();
		$items[0]->setName($itemName)
		    ->setQuantity(1)
		    ->setUnitPrice(new Currency());

		$items[0]->getUnitPrice()
		    ->setCurrency($currency)
		    ->setValue($amount);
		$invoice->setItems($items);

		$invoice->setLogoUrl('https://www.paypalobjects.com/webstatic/i/logo/rebrand/ppcom.svg');
		
		try {
			$invoice->create($apiContext);
		} catch (\Exception $e) {
    		$errorMsg = 'Create Invoice: Error on line '.$e->getLine().' in '.$e->getFile().': <b>'.$e->getMessage();
    		return $errorMsg;
		}	

		return $invoice;
	}

	public static function sendInvoice($invoice,$apiContext) {
		try {
			$sendStatus = $invoice->send($apiContext);
		} catch (\Exception $e) {
			$errorMsg = 'Send Invoice: Error on line '.$e->getLine().' in '.$e->getFile().': <b>'.$e->getMessage();
			return $errorMsg;
		}		

		
		try {
		    //$invoiceInfo = Invoice::get($invoice->getId(), $apiContext);
		    $invoiceInfo = $invoice->getId();
		} catch (\Exception $e) {
			$errorMsg = 'Send Invoice: Error on line '.$e->getLine().' in '.$e->getFile().': <b>'.$e->getMessage();
			return $errorMsg; 
		}	

		return ['status'=>$sendStatus,'invoiceData'=>$invoiceInfo];
	}

	//取消收款
	public static function cancelInvoice($invoice,$apiContext) {
		try {
			$notify = new CancelNotification();
		    $notify
		        ->setSubject("Past due")
		        ->setNote("Canceling invoice")
		        ->setSendToMerchant(true)
		        ->setSendToPayer(true);
		    $cancelStatus = $invoice->cancel($notify, $apiContext);    
		} catch (\Exception $e) {	
			$errorMsg = 'Send Invoice: Error on line '.$e->getLine().' in '.$e->getFile().': <b>'.$e->getMessage();
			return $errorMsg; 
		}	

		return $cancelStatus;
	}


}