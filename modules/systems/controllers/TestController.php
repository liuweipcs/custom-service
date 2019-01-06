<?php
namespace app\modules\systems\controllers;
use app\components\Controller;
use PayPal\PayPalAPI\RefundTransactionRequestType;
use PayPal\PayPalAPI\RefundTransactionReq;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use PayPal\PayPalAPI\ReverseTransactionReq;
use PayPal\EBLBaseComponents\ReverseTransactionRequestDetailsType;
use PayPal\PayPalAPI\ReverseTransactionRequestType;
use PayPal\PayPalAPI\PayPal\PayPalAPI;
use app\common\VHelper;
use app\modules\orders\models\Order;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use wish\components\WishApi;
use app\modules\services\modules\amazon\components\SubmitFeedRequest;
use app\modules\services\modules\amazon\components\GetFeedSubmissionResultRequest;
use app\modules\services\modules\amazon\components\GetFeedSubmissionListRequest;
use app\modules\systems\models\ErpOrderApi;
use yii\swiftmailer\Mailer;
use moonland\phpexcel\Excel;
use app\modules\systems\models\Tag;

use PayPal\PayPalAPI\TransactionSearchReq;
use PayPal\PayPalAPI\TransactionSearchRequestType;


use PayPal\PayPalAPI\GetTransactionDetailsReq;
use PayPal\PayPalAPI\GetTransactionDetailsRequestType;
//use PayPal\Service\PayPalAPIInterfaceServiceService;
//use PayPal\Service\PayPalAPIInterfaceServiceService;

class TestController extends Controller
{
    public function actionPaypalrefund()
    {
        $config = array(
            // values: 'sandbox' for testing
            //		   'live' for production
            //         'tls' for testing if your server supports TLSv1.2
            "mode" => "sandbox",
            // TLSv1.2 Check: Comment the above line, and switch the mode to tls as shown below
            // "mode" => "tls"
        
            'log.LogEnabled' => true,
            'log.FileName' => '../PayPal.log',
            'log.LogLevel' => 'FINE',
            // Signature Credential
            "acct1.UserName" => "1143621529_seller_api1.qq.com",
            "acct1.Password" => "SSF8V6276NGNDP2Y",
            "acct1.Signature" => "AFcWxV21C7fd0v3bYYYRCpSSRl31Ar01O0xKTWFUkq0Jurz2vQQJtD6-gP",
            // Subject is optional and is required only in case of third party authorization
            //"acct1.Subject" => "seller_1353049363_biz@gmail.com",
        
            // Sample Certificate Credential
            // "acct1.UserName" => "certuser_biz_api1.paypal.com",
            // "acct1.Password" => "D6JNKKULHN3G5B8A",
            // Certificate path relative to config folder or absolute path in file system
            // "acct1.CertPath" => "cert_key.pem",
            // Subject is optional and is required only in case of third party authorization
            // "acct1.Subject" => "",
        
            // These values are defaulted in SDK. If you want to override default values, uncomment it and add your value.
            // "http.ConnectionTimeOut" => "5000",
            // "http.Retry" => "2",
        );
        
        /*
         * The RefundTransaction API operation issues a refund to the PayPal account holder associated with a transaction.
         This sample code uses Merchant PHP SDK to make API call
        */
        $refundReqest = new RefundTransactionRequestType();
        
        /*
         * 	 Type of refund you are making. It is one of the following values:
        
         * `Full` - Full refund (default).
         * `Partial` - Partial refund.
         * `ExternalDispute` - External dispute. (Value available since
         version
         82.0)
         * `Other` - Other type of refund. (Value available since version
         82.0)
        */
        
        $refundReqest->RefundType = 'Full';
        
        /*
         *  Either the `transaction ID` or the `payer ID` must be specified.
         PayerID is unique encrypted merchant identification number
         For setting `payerId`,
         `refundTransactionRequest.setPayerID("A9BVYX8XCR9ZQ");`
        
         Unique identifier of the transaction to be refunded.
         */
        $refundReqest->TransactionID = '4J664876WA738913W';
        
        /*
         *  (Optional)Type of PayPal funding source (balance or eCheck) that can be used for auto refund. It is one of the following values:
        
         any � The merchant does not have a preference. Use any available funding source.
        
         default � Use the merchant's preferred funding source, as configured in the merchant's profile.
        
         instant � Use the merchant's balance as the funding source.
        
         eCheck � The merchant prefers using the eCheck funding source. If the merchant's PayPal balance can cover the refund amount, use the PayPal balance.
        
         */
        //$refundReqest->RefundSource = 'balance';
        //$refundReqest->Memo = 'dsadsa';
        /*
         *
         (Optional) Maximum time until you must retry the refund.
         */
        //$refundReqest->RetryUntil = $_REQUEST['retryUntil'];
        
        $refundReq = new RefundTransactionReq();
        $refundReq->RefundTransactionRequest = $refundReqest;
        
        /*
         * 	 ## Creating service wrapper object
         Creating service wrapper object to make API call and loading
         Configuration::getAcctAndConfig() returns array that contains credential and config parameters
         */
        $paypalService = new PayPalAPIInterfaceServiceService($config);

        try {
            /* wrap API method calls on the service object with a try catch */
            $refundResponse = $paypalService->RefundTransaction($refundReq);
        } catch (\Exception $ex) {
           $ex_detailed_message = null;
            if($ex instanceof PPConnectionException) {
                $ex_detailed_message = "Error connecting to " . $ex->getUrl();
            } else if($ex instanceof PPMissingCredentialException || $ex instanceof PPInvalidCredentialException) {
                $ex_detailed_message = $ex->errorMessage();
            } else if($ex instanceof PPConfigurationException) {
               $ex_detailed_message = "Invalid configuration. Please check your configuration file";
            }
           echo $ex_detailed_message;
          
        }
        if(isset($refundResponse)) {
            echo "<table>";
            echo "<tr><td>Ack :</td><td><div id='Ack'>$refundResponse->Ack</div> </td></tr>";
            //echo "<tr><td>RefundStatus :</td><td><div id='RefundStatus'>$refundResponse->RefundInfo->RefundStatus</div> </td></tr>";
            echo "</table>";
            echo "<pre>";
            print_r($refundResponse);
            echo "</pre>";
        }
        exit;
    }

    public function actionTest()
    {  
    
       $params = [
           'refund_config' => array(),
           'transaction_id' => '3K627997LH8728818',
           'refund_amount' => null,
           'currency_code' => null,
           'refund_type' => 'Full',
        ];
       $result = VHelper::ebayRefund($params);
       var_dump($result);
       //$result = Order::getTransactionId('EB', 'CO161118002183');
       //var_dump($result); 

       //$accountInfo = Account::findById(7);
       //$accountName = $accountInfo->account_name;
        /*
       $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_WISH, 'Fenglinjoy66');
       $api = new WishApi('521c4db028cf48b683ccffa8d8e4c8f4');
       $params = ['id'=>'098765432112345678901234','reason_code'=>18];
       $result = $api->refund($params);
       var_dump($result);*/
      /*
      'slitemid' => '1234',
     *         'reason' => 'CustomerReturn',
     *         'co' = array(
     *             array('type' => 'Principal', 'currency'=>'USD', 'amount' => 12),
     *             array('type' => 'Shipping', 'currency'=>'USD', 'amount' => 3.49),
     *             array('type' => 'Tax', 'currency'=>'USD', 'amount' => 3.49),
     *
      */
       /*
       $req['orderid'] = '302-6548083-7676322';
       $req['itemid'] = '61413243592899';
       $req['action_type'] = 'Refund';
       $req['slitemid'] = '';
       $req['reason'] = 'Other';
       $req['co'][] = array(
           'type'     => 'Principal',
           'amount'   => 14.99,
           'currency' => 'EUR',
        );
       $feed = new SubmitFeedRequest();
       $resp = $feed->setAccountName('sococo德国')
                ->setServiceUrl()
                ->setConfig()
                ->setFeedType('_POST_PAYMENT_ADJUSTMENT_DATA_')
                ->setBusinessType(SubmitFeedRequest::REFUND)
                ->setReqArrList(array($req))
                ->setRequest()
                ->setType('webservice')
                ->setService()
                ->sendHttpRequest()
                ->getResponse();
      
        $rs = $feed->parseResponse($resp);
        var_dump($rs); exit;*/
        /*
        $submission = new GetFeedSubmissionResultRequest();

         $xml = $submission->setAccountName('sococo德国')
             ->setServiceUrl()
             ->setConfig()
             ->setSubmissionId('118111017336')
             ->setRequest()
             ->setType('webservice')
             ->setService()
             ->sendHttpRequest()
             ->getXmlResult();
         //var_dump($xml);
         $sxe    = simplexml_load_string($xml);
         var_dump($sxe);die();
         $result =  $sxe->xpath('Message/ProcessingReport/ProcessingSummary');
         $abc = $sxe->xpath('Message/ProcessingReport/Result');
         var_dump($result);die();
         //var_dump($abc);die();
         $abc = current($abc);
         //var_dump($abc->ResultDescription);die();
         var_dump(current($abc->ResultDescription));
        //$result = $submission->parseResponse($response);
        
        // var_dump($result); 
         /*
         $status = [
          '_AWAITING_ASYNCHRONOUS_REPLY_',
          '_CANCELLED_',
          '_DONE_',
          '_IN_PROGRESS_',
          '_IN_SAFETY_NET_',
          '_SUBMITTED_',
          '_UNCONFIRMED_',
         ];
         $submission = new GetFeedSubmissionListRequest();

         $response = $submission->setAccountName('sococo英国')
            ->setServiceUrl()
             ->setConfig()
             ->setStatus($status)
             ->setSubmissionId(array('117187017333'))
             ->setOneRequest()
             ->setType('webservice')
             ->setService()
             ->sendHttpRequest()
             ->getResponse();

         $data = $submission->parseResponse($response);
         var_dump($data); */
         $result = Order::getTransactionId('EB','CO161218001308');
         var_dump($result);die();
    }

    public function actionTest2()
    {
        $datas = [
 'order_id' => "CO170512000755",
 'return_refund_id' => 'AS1706130004',
 'refund_sum' => 4.67,
 'refund_type' => 1,
 'real_refund_sum' => 4.67,
 'currency' => "USD",
 'reason' => 1,
];

        $erpOrderApi = new ErpOrderApi();
        $erpOrderApi->setRefund('ALI', $datas);
    }
    
    public function actionSendmail()
    {
        $mailer = new Mailer;
        $mailer->transport = [
            'class' => 'Swift_SmtpTransport',
            'host' => 'smtp.163.com',
            'username' => 'fastkk2016uk@163.com',
            'password' => 'comeon888',
            'port' => '25',
            'encryption' => 'tls',       
        ];
        $mailer->messageConfig = [
            'charset' => 'UTF-8',
            'from' => 'fastkk2016uk@163.com',
            //'bcc' => 'developer@mydomain.com',
        ];
        $result = $mailer->compose()
            ->setFrom('fastkk2016uk@163.com')
            ->setTo('361481974@qq.com')
            ->setSubject('Order delivery inquiry from Amazon customer Chetan Udeshi (Order: 205-2020858-6365913)')
            ->setTextBody('Dear Chetan Udeshi 
Feel so sorry for the trouble.
we sent the mattress out by UPS early.
here is the tracking number: 1ZW215236807985356 
you can check it here: https://wwwapps.ups.com/WebTracking/track
here is the information i have checked for you:
LONDON, GB 	2017/06/27 	10:56 	Delivered 
it shows that, it is delivered now,

may i know if you have received this mattress now?
may i know if you could kindly help me confirm this first?

waiting for your early reply.
best wishes ')
            ->send();
        var_dump($result);
        exit('test');
    }

    public function actionTest3()
    {   
        /*
        $result = [
          ['name'=>'我去玩','age'=>54],
          ['name'=>'还得使','age'=>35]
        ];
        Excel::export([
          'models' => $result,
          'columns' => ['name','age'],
           //without header working, because the header will be get label from attribute label.
           'headers' => ['name' => '姓名','age' => '年龄'],
     
        ]);
        */
       $fileName = "./exports.xls";
       $data = Excel::import($fileName, [
        'setFirstRecordAsKeys' => true, // if you want to set the keys of record column with first record, if it not set, the header with use the alphabet column on excel. 
        'setIndexSheetByName' => true, // set this if your excel data with multiple worksheet, the index of array will be set with the sheet name. If this not set, the index will use numeric. 
        'getOnlySheet' => 'sheet1', // you can set this property if you want to get the specified sheet from the excel data with multiple worksheet.
      ]);

      var_dump($data);
    }


    public function actionTest6()
    {     

         $config = array(
        // Signature Credential
        "acct1.UserName" => "jb-us-seller_api1.paypal.com",
        "acct1.Password" => "WX4WTU3S8MY44S7F",
        "acct1.Signature" => "AFcWxV21C7fd0v3bYYYRCpSSRl31A7yDhhsPUU2XhtMoZXsWHFxu-RWy",
        // Subject is optional and is required only in case of third party authorization
        // "acct1.Subject" => "",
        
        // Sample Certificate Credential
        // "acct1.UserName" => "certuser_biz_api1.paypal.com",
        // "acct1.Password" => "D6JNKKULHN3G5B8A",
        // Certificate path relative to config folder or absolute path in file system
        // "acct1.CertPath" => "cert_key.pem",
        // Subject is optional and is required only in case of third party authorization
        // "acct1.Subject" => "",
          "mode" => "sandbox",
          'log.LogEnabled' => true,
          'log.FileName' => '../PayPal.log',
          'log.LogLevel' => 'FINE'
        );
         //require_once 'Configuration.php';
         //include_once \Yii::getAlias('@app') . '/vendor/paypal/merchant-sdk-php/samples/Configuration.php';
         $currDate = getdate();
         //$endDate = $currDate['year'].'-'.$currDate['mon'].'-'.$currDate['mday'].' '.$currDate['hours'].':'.$currDate['minutes'].':'.$currDate['seconds'];
         $endDate = $currDate['year'].'-'.$currDate['mon'].'-'.$currDate['mday'];
         
         $endDate = strtotime($endDate);
         $endDate = date("Y-m-d\TH:i:sO", mktime(0,0,0,date('m',$endDate),date('d',$endDate),date('Y',$endDate)));
         
         //$startDate = strtDate($endDate, 1);


         $cd = strtotime($endDate);
         $startDate = date("Y-m-d\TH:i:sO", mktime(0,0,0,date('m',$cd),date('d',$cd)-1,date('Y',$cd)));

         //var_dump($startDate);
         //echo "</br>";
         //var_dump($endDate);
         //die();


           $transactionSearchRequest = new TransactionSearchRequestType();
$transactionSearchRequest->StartDate = $startDate;
$transactionSearchRequest->EndDate = $endDate;
$transactionSearchRequest->TransactionID = "1PH279347E9097705";//$_REQUEST['transactionID'];

$tranSearchReq = new TransactionSearchReq();
$tranSearchReq->TransactionSearchRequest = $transactionSearchRequest;

/*
 *     ## Creating service wrapper object
Creating service wrapper object to make API call and loading
Configuration::getAcctAndConfig() returns array that contains credential and config parameters
*/
$paypalService = new PayPalAPIInterfaceServiceService($config);
try {
  /* wrap API method calls on the service object with a try catch */
  $transactionSearchResponse = $paypalService->TransactionSearch($tranSearchReq);
} catch (Exception $ex) {
  //include_once \Yii::getAlias('@app') . '/vendor/paypal/merchant-sdk-php/sample/PPAutoloader.php';
  //include_once("../Error.php");
   $ex_message = "";
$ex_detailed_message = "";
$ex_type = "Unknown";

if(isset($ex)) {

  $ex_message = $ex->getMessage();
  $ex_type = get_class($ex);

  if($ex instanceof PPConnectionException) {
    $ex_detailed_message = "Error connecting to " . $ex->getUrl();
  } else if($ex instanceof PPMissingCredentialException || $ex instanceof PPInvalidCredentialException) {
    $ex_detailed_message = $ex->errorMessage();
  } else if($ex instanceof PPConfigurationException) {
    $ex_detailed_message = "Invalid configuration. Please check your configuration file";
  }
}
  echo $ex_detailed_message;
}
if(isset($transactionSearchResponse)) {
  echo "<table>";
  echo "<tr><td>Ack :</td><td><div id='Ack'>$transactionSearchResponse->Ack</div> </td></tr>";
  echo "</table>";
  echo "<pre>";
  print_r($transactionSearchResponse);
  echo "</pre>";
}







    }



    public function actionTest7()
    {   

       $config = array(
        // Signature Credential
        "acct1.UserName" => "jb-us-seller_api1.paypal.com",
        "acct1.Password" => "WX4WTU3S8MY44S7F",
        "acct1.Signature" => "AFcWxV21C7fd0v3bYYYRCpSSRl31A7yDhhsPUU2XhtMoZXsWHFxu-RWy",
        // Subject is optional and is required only in case of third party authorization
        // "acct1.Subject" => "",
        
        // Sample Certificate Credential
        // "acct1.UserName" => "certuser_biz_api1.paypal.com",
        // "acct1.Password" => "D6JNKKULHN3G5B8A",
        // Certificate path relative to config folder or absolute path in file system
        // "acct1.CertPath" => "cert_key.pem",
        // Subject is optional and is required only in case of third party authorization
        // "acct1.Subject" => "",
          "mode" => "sandbox",
          'log.LogEnabled' => true,
          'log.FileName' => '../PayPal.log',
          'log.LogLevel' => 'FINE'
        );

        $transactionDetails = new GetTransactionDetailsRequestType();
/*
 * Unique identifier of a transaction.3CX27279FC844523Y
*/
$transactionDetails->TransactionID = "5R1625625Y890805V";//$_POST['transID'];

$request = new GetTransactionDetailsReq();
$request->GetTransactionDetailsRequest = $transactionDetails;

/*
 *   ## Creating service wrapper object
Creating service wrapper object to make API call and loading
Configuration::getAcctAndConfig() returns array that contains credential and config parameters
*/
$paypalService = new PayPalAPIInterfaceServiceService($config);
try {
  /* wrap API method calls on the service object with a try catch */
  $transDetailsResponse = $paypalService->GetTransactionDetails($request);
} catch (Exception $ex) {
  
  if(isset($ex)) {

  $ex_message = $ex->getMessage();
  $ex_type = get_class($ex);

  if($ex instanceof PPConnectionException) {
    $ex_detailed_message = "Error connecting to " . $ex->getUrl();
  } else if($ex instanceof PPMissingCredentialException || $ex instanceof PPInvalidCredentialException) {
    $ex_detailed_message = $ex->errorMessage();
  } else if($ex instanceof PPConfigurationException) {
    $ex_detailed_message = "Invalid configuration. Please check your configuration file";
  }
}
  echo $ex_detailed_message;

}
if(isset($transDetailsResponse)) {
  echo "<table>";
  echo "<tr><td>Ack :</td><td><div id='Ack'>$transDetailsResponse->Ack</div> </td></tr>";
  echo "</table>";
  echo "<pre>";
  print_r($transDetailsResponse);
  echo "</pre>";
}

    }

 public function actionTest9()
 {  
  //echo 7.55 * 789;


    $a = VHelper::getTargetCurrencyAmt('USD', 'HKD', 0.00);
    var_dump($a);
 }



}