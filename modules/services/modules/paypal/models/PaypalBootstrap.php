<?php
namespace app\modules\services\modules\paypal\models;

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PaypalBootstrap {
	
	// Replace these values by entering your own ClientId and Secret by visiting https://developer.paypal.com/developer/applications/
	//$clientId = 'AYSq3RDGsmBLJE-otTkBtM-jBRd1TCQwFf9RGfwddNXWz0uFU9ztymylOhRS';
	//$clientSecret = 'EGnHDxD_qRPdaLdZz8iCr8N7_MzF-YHPTkjs6NKYQvQSBngp4PTTVWkPZRbL';
	/** @var \Paypal\Rest\ApiContext $apiContext */
	//$apiContext = getApiContext($clientId, $clientSecret);

	public static function getApiContext($clientId, $clientSecret)
	{

	    $apiContext = new ApiContext(
	        new OAuthTokenCredential(
	        	$clientId,
	        	$clientSecret
	            /*'AXl7OiXmCwM-65J0g7z_dS96734j3pYC2FMXVePw9IUjVOyalszivgqT1id2ml-h9xKezmYVBTO2YKQv',
	            'EM0L_W3U7PmHejxs9gq-EuNZDdZpnVDw7SjvJOvAT3ogL4-u7-Xn-h_FICX3m5lio-FBJ5a7rp8VsF73'*/
	        )
	    );

	    $apiContext->setConfig(
	        array(
	            //'mode' => 'sandbox',
	            'mode' => 'live',
	            'log.LogEnabled' => true,
	            'log.FileName' => './PayPal.log',
	            'log.LogLevel' => 'DEBUG',
	            'cache.enabled' => true,
	        )
	    );
	    return $apiContext;
	}

}

?>
