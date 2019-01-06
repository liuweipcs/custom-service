<?php

namespace app\modules\services\modules\amazon\components;

class Refund
{
	//REFUND REASON
	const NO_INVENTORY = 'NoInventory';
	const CUSTOMER_RETURN = 'CustomerReturn';
	const GENERAL_ADJUSTMENT = 'GeneralAdjustment';
	const COULD_NOT_SHIP = 'CouldNotShip';
	const DIFFERENT_ITEM = 'DifferentItem';
	const ABANDONED = 'Abandoned';
	const CUSTOMER_CANCEL = 'CustomerCancel';
	const PRICE_ERROR = 'PriceError';
	const PRODUCT_OUT_OF_STOCK = 'ProductOutofStock';
	const CUSTOMER_ADDRESS_INCORRECT = 'CustomerAddressIncorrect';
	const EXCHANGE = 'Exchange';
	const OTHER = 'Other';
	const CARRIER_CREDIT_DECISION = 'CarrierCreditDecision';
	const RISK_ASSESSMENT_INFORMATION_NOT_VALID = 'RiskAssessmentInformationNotValid';
	const Carrier_Coverage_Failure = 'CarrierCoverageFailure';
	const TRANSACTION_RECORD = 'TransactionRecord';
	const UNDELIVERABLE = 'Undeliverable';
	const REFUSED_DELIVERY = 'RefusedDelivery';

	//REFUND TYPE
	const PRINCIPAL = 'Principal'; //本金
	const SHIPPING = 'Shipping';
	const TAX = 'Tax';
	const SHIPPING_TAX = 'ShippingTax';
	const RESTOCKING_FEE = 'RestockingFee';
	const RESTOCKING_FEE_TAX = 'RestockingFeeTax';
	const GIFT_WRAP = 'GiftWrap';
	const GIFT_WRAP_TAX = 'GiftWrapTax';
	const SURCHARGE = 'Surcharge';
	const RETURN_SHIPPING = 'ReturnShipping';
	const GOOD_WILL = 'Goodwill';
	const EXPORT_CHARGE = 'ExportCharge';
	const COD = 'COD';
	const COD_TAX = 'CODTax';
	const ORTHER = 'Other';
	const FREE_REPLACEMENT_RETURN_SHIPPING = 'FreeReplacementReturnShipping';

	const URL = 'http://erp.cc/services/api/order/index/';

	/**
	 * Amazon Refund Function
	 *
	 * Usage :
	 * <?php
	 * 		Refund::httpReqeust('xuuyuu', '1234', '1234', Refund::CUSTOMER_RETURN, [
	 * 			[Refund::PRINCIPAL, 12.6, 'USD'], 
	 *   		[Refund::COD, 2.6, 'USD'], 
	 *     ], '1234');
	 * ?>
	 * 
	 *
	 * @param  string $account  The account name
	 * @param  string $orderId  The order id
	 * @param  string $itemId   The item id in order
	 * @param  string $reason   Refund reason
	 * @param  array  $co       Refund unit
	 * @param  string $slItemId Mechant self-define item it
	 * 
	 * @return boolean
	 */
	public static function httpReqeust(
		$account, $orderId, $itemId, $reason, array $co, $slItemId = '')
	{
		$params = [
			'method' => 'amazonrefund',
			'token' => '5E17C4488C2AC591',
		];

		if ($account)   $params['account_name'] = $account;
		if ($orderId)  	$params['orderid']      = $orderId;
		if ($itemId)   	$params['itemid']       = $itemId;
		if ($reason)   	$params['reason']       = $reason;
		if ($slItemId)	$params['slitemid']     = $slItemId;

		foreach ($co as $i => $v) {
			$s = implode('+', $v);
			$params['co.'.($i+1)] = $s;
		}

		$requestUrl = self::URL . '?' . http_build_query($params);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $requestUrl);

		$body  = curl_exec($ch);
		$info  = curl_getinfo($ch);
		$errno = curl_errno($ch);

		// echo self::prettyXML($body);
		return json_decode($body);
	}

	/**
	 * echo pretty xml data
	 * 
	 * @param  string $xml
	 * 
	 * @return string
	 */
	public static function prettyXML($xml)
	{
		$dom = new \DOMDocument();
		$dom->formatOutput = true;
		$dom->loadXML($xml);

		return $dom->saveXML();
	}
}

