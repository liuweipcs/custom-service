<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/8 0008
 * Time: 下午 7:54
 */

namespace app\modules\services\modules\ebay\models;

//require_once \Yii::getAlias('@webroot').'/../vendor/ebay/EbaySession.php';
require_once \Yii::getAlias('@runtime').'/../vendor/ebay/EbaySession.php';
use ebay\eBaySession;

class PostOrderAPI
{
    private $authorization;
    private $site;
    private $serverUrl;
    private $method;
    private $data;
    private $httpCode;
    public $responseHeader = false; //返回头信息输出


    public $urlParams;

    public function setData($data)
    {
        $this->data = json_encode($data);
    }

    public function __construct($authorization,$siteID,$serverUrl,$method = 'post')
    {
        $this->authorization = $authorization;
        if(is_numeric($siteID))
            $this->site = eBaySession::getGlobalID($siteID);
        else
            $this->site = $siteID;
        $this->serverUrl = $serverUrl;
        $this->method = $method;
    }

    private function buildEbayHeaders()
    {
        $header = [
            'Authorization: TOKEN '.$this->authorization,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        if(isset($this->site) && $this->site != '')
            $header[] = 'X-EBAY-C-MARKETPLACE-ID: '.$this->site;
        return $header;
    }

    public function getServerUrl()
    {
        return $this->serverUrl;
    }

    private function buildUrl()
    {
        if(!empty($this->urlParams))
        {
            if(is_array($this->urlParams))
            {
                $this->serverUrl .= '?';
                foreach($this->urlParams as $paramK=>$paramV)
                {
                    $this->serverUrl .= "{$paramK}={$paramV}&";
                }
                $this->serverUrl = trim($this->serverUrl,'&');
            }
            else
            {
                if(strpos($this->urlParams,'?') === 0)
                    $this->serverUrl .= $this->urlParams;
                else
                    $this->serverUrl .= '?'.$this->urlParams;
            }
        }
        return $this->serverUrl;
    }

    public function sendHttpRequest($type = 'array')
    {
        $approve = false;
        /*if(strpos($this->serverUrl,'approve') !== false)
        {
            $time1 = microtime(true);
            echo '开始',$time1,'-----';
            $approve = true;
        }*/
        $connection = curl_init();
        $headers = $this->buildEbayHeaders();
        curl_setopt($connection, CURLOPT_URL, $this->buildUrl());
//stop CURL from verifying the peer's certificate
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
       /* if($approve === true)
        {
            $time2 = microtime(true);
            echo $time2-$time1,'-----';
        }*/

        switch(strtolower($this->method))
        {
            case 'post':
                curl_setopt($connection, CURLOPT_POST, 1);
                if(isset($this->data))
                {
                    curl_setopt($connection,CURLOPT_POSTFIELDS,$this->data);
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
        if($this->responseHeader)
            curl_setopt($connection,CURLOPT_HEADER,1);

        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
        //set it to return the transfer as a string from curl_exec
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 120);
        /*if($approve === true)
        {
            $time3 = microtime(true);
            echo $time3-$time2,'-----';
        }*/
        //Send the Request
        $response = curl_exec($connection);
       /* if($approve === true)
        {
            $time4 = microtime(true);
            echo $time4-$time3,'-----';
        }*/
        $this->httpCode = curl_getinfo($connection,CURLINFO_HTTP_CODE);
        //close the connection
        curl_close($connection);
        /*if($approve === true)
        {
            $time5 = microtime(true);
            echo $time5-$time4,'-----';
            findClass($this->httpCode,1,0);
            findClass($response,1);
        }*/

        if($type == 'array')
            return json_decode($response);
        else
            return $response;
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }
}