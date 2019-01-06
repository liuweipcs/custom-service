<?php

/**
 *  ebay api abstract
 * 
 * @package Ueb.modules.services.modules.aliexpress.components
 * @auther Bob <zunfengke@gmail.com>
 */

namespace app\modules\services\modules\walmart\components;

use app\common\OpensslRSA;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\services\components\ApiInterface;
use yii\base\Exception;

abstract class WalmartApiAbstract implements ApiInterface {

    public $config;
    public $method;
    public $accountInfo;
    public $consumerId;
    public $privateKey;
    public $channelType;

    /**
     * @var string short name
     */
    protected $_shortName = null;

    /**
     * @var string usertoken
     */
    protected $_userToken = null;

    /**
     * @var object request xml body
     */
    public $requestXmlBody = null;

    /**
     * @var type set request
     */
    public $request = null;

    /**
     * @var object response
     */
    public $response = null;
    public $headers = array();

    /**
     * request url
     *
     * @var string
     */
    public $serverUrl = null;
    protected $errors;
    protected $sendXml; //存发送的xml
    protected $errorCode = 1;

    public function __construct() {
        
    }

    public function getHeaders($content_type = 'application/xml') {
        list($signature, $timestamp) = $this->getSignature($this->consumerId, $this->privateKey, $this->url, $this->method);
        $headers[] = 'WM_SVC.NAME: Walmart Marketplace';
        $headers[] = 'WM_CONSUMER.ID: ' . $this->consumerId;
        $headers[] = 'WM_SEC.TIMESTAMP: ' . $timestamp;
        $headers[] = 'WM_SEC.AUTH_SIGNATURE: ' . $signature;
        $headers[] = 'WM_QOS.CORRELATION_ID: ' . $this->getRandStr();
        $headers[] = 'WM_CONSUMER.CHANNEL.TYPE: ' . $this->channelType;
        $headers[] = 'Accept: application/xml';
        $headers[] = 'Content-Type: application/xml';
        $this->headers = $headers;
    }

    public function getSignature($consumerId, $privateKey, $url, $method) {
        require_once \Yii::getAlias('@vendor') . '/phpseclib/Crypt/RSA.php';
        //生成以毫秒为单位的时间戳
        list($tmp1, $tmp2) = explode(' ', microtime());
        $timestamp = (float) sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
        //待签名的字符
        $signature_str = $consumerId . "\n" . $url . "\n" . $method . "\n" . $timestamp . "\n";
        $rsa = new \Crypt_RSA();
        $rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS8);
        $rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS8);
        $rsa->loadKey($privateKey); // private key
        $rsa->setHash('sha256');
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $signature = $rsa->sign($signature_str);

        //返回签名后的字符和时间戳
        return [base64_encode($signature), $timestamp];
    }

    /* function getSignature($customerId,$privateKey,$url, $RequestMethod) {
      $timestamp = round(microtime(true) * 1000);
      $timestamp = '1520320142819';
      $AuthData = $customerId."\n";
      $AuthData .= $url."\n";
      $AuthData .= $RequestMethod."\n";
      $AuthData .= $timestamp."\n";
      // GET AN OPENSSL USABLE PRIVATE KEY FROMM THE WARMART SUPPLIED SECRET
      $Pem = $this->_ConvertPkcs8ToPem(base64_decode($privateKey));
      $PrivateKey = openssl_pkey_get_private($Pem);
      // SIGN THE DATA. USE sha256 HASH
      $Hash = defined("OPENSSL_ALGO_SHA256") ? OPENSSL_ALGO_SHA256 : "sha256";
      if (openssl_sign($AuthData, $Signature, $PrivateKey, $Hash))
      { // IF ERROR RETURN NULL return null; }
      //ENCODE THE SIGNATURE AND RETURN
      return [base64_encode($Signature),$timestamp];
      }

      } */

    function _ConvertPkcs8ToPem($der) {
        static $BEGIN_MARKER = "-----BEGIN RSA PRIVATE KEY-----";
        static $END_MARKER = "-----END RSA PRIVATE KEY-----";
        $key = base64_encode($der);
        $pem = $BEGIN_MARKER . "\n";
        $pem .= chunk_split($key, 64, "\n");
        $pem .= $END_MARKER . "\n";
        return $pem;
    }

    private function getRandStr() {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z",
        );
        $chars_len = count($chars) - 1;
        shuffle($chars);
        list($letter, $number) = ["", ""];
        for ($i = 0; $i < 4; $i++) {
            $letter .= $chars[mt_rand(0, $chars_len)];
            $number .= mt_rand(1, 9);
        }
        return $number . $letter;
    }

    public function getSendXml() {
        return $this->sendXml;
    }

    public function setRequest() {
        
    }

    /**
     * send http request
     * 
     * @return object 
     */
    public function sendHttpRequest() {
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $this->url);

        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        switch (strtolower($this->method)) {
            case 'post':
                curl_setopt($connection, CURLOPT_POST, 1);
                if (isset($this->sendXml)) {
                    curl_setopt($connection, CURLOPT_POSTFIELDS, $this->sendXml);
                }
                break;
            case 'get':
                curl_setopt($connection, CURLOPT_HTTPGET, true);
                break;
            case 'put':
                curl_setopt($connection, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case 'delete':
                curl_setopt($connection, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        curl_setopt($connection, CURLOPT_HTTPHEADER, $this->headers);

        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 120);

        $response = curl_exec($connection);
        $httpCode = curl_getinfo($connection, CURLINFO_HTTP_CODE);

        curl_close($connection);
//        list($header, $body) = explode("\r\n\r\n", $response, 2);
        $responses = array(
            'state' => $httpCode,
            'response' => $response,
        );
        
        if ($responses['state']) {
            $xmlstr = preg_replace('/\sxmlns="(.*?)"/', ' _xmlns="${1}"', $responses['response']); //替换头部命名空间
            $xmlstr = preg_replace('/<(\/)?(\w+):(\w+)/', '<${1}${3}', $xmlstr); //替换标签
            $xmlstr = preg_replace('/(\w+):(\w+)="(.*?)"/', '${1}_${2}="${3}"', $xmlstr); //替换属性
            $response = json_decode(json_encode(simplexml_load_string($xmlstr)), true);
            if ($responses['state'] == 200) {
                $finalResult = [TRUE,'退款成功'];
            } else {
                $finalResult = [FALSE,$response['error']['description']];
            }
        }

        return $finalResult;
    }

    /**
     * request xml body
     */
    abstract function requestXmlBody();

    /**
     * get xml generator obj 
     * 
     * @return \XmlGenerator
     */
    public static function getXmlGeneratorObj() {
        return new XmlGenerator();
    }

    /**
     * get request
     * 
     * @throws Exception
     */
    public function getRequest() {
        if (empty($this->request)) {
            throw new Exception('The request is not allowed to be empty');
        }

        return $this->request;
    }

    /**
     * @return object get response
     */
    public function getResponse() {

        return $this->response;
    }

    public function getIfSuccess() {
        if (isset($this->response->Ack) && ($this->response->Ack == 'Success' || $this->response->Ack == 'Warning')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorMsg() {
        $msg = '';
        if (isset($this->response->Errors)) {
            foreach ($this->response->Errors as $error) {
                $msg .= $error->LongMessage . ".";
            }
        }
        return $msg;
    }

    public static function isTimeDate($var) {
        if (preg_match('/^\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-6]\d:[0-6]\d[.]\d{3}Z$/', $var))
            return true;
        else
            return false;
    }

}

?>