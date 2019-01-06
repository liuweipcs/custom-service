<?php

namespace app\modules\services\modules\lazada\components;

class LazopClient
{
    public $appkey;

    public $secretKey;

    public $gatewayUrl = "https://api.lazada.com/rest";

    public $connectTimeout;

    public $readTimeout;

    protected $signMethod = "sha256";

    protected $sdkVersion = "lazop-sdk-php-20180205";

    public function getAppkey()
    {
        return $this->appkey;
    }

    public function __construct($url = "https://api.lazada.com/rest", $appkey = "", $secretKey = "")
    {
        $this->gatewayUrl = $url;
        $this->appkey     = $appkey;
        $this->secretKey  = $secretKey;
    }

    protected function generateSign($apiName, $params)
    {
        ksort($params);

        $stringToBeSigned = '';
        $stringToBeSigned .= $apiName;
        foreach ($params as $k => $v) {
            $stringToBeSigned .= "$k$v";
        }
        unset($k, $v);

        return strtoupper($this->hmac_sha256($stringToBeSigned, $this->secretKey));
    }


    function hmac_sha256($data, $key)
    {
        return hash_hmac('sha256', $data, $key);
    }

    public function curl_get($url, $apiFields = null, $headerFields = null)
    {
        $ch = curl_init();

        foreach ($apiFields as $key => $value) {
            $url .= "&" . "$key=" . urlencode($value);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if ($headerFields) {
            $headers = array();
            foreach ($headerFields as $key => $value) {
                $headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            unset($headers);
        }

        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }

        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, $this->sdkVersion);

        //https ignore ssl check ?
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $output = curl_exec($ch);

        $errno = curl_errno($ch);

        if ($errno) {
            curl_close($ch);
            die($errno);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (200 !== $httpStatusCode) {
                die($httpStatusCode);
            }
        }

        return $output;
    }

    public function curl_post($url, $postFields = null, $fileFields = null, $headerFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }
        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        if ($headerFields) {
            $headers = array();
            foreach ($headerFields as $key => $value) {
                $headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            unset($headers);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, $this->sdkVersion);

        //https ignore ssl check ?
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $delimiter = '-------------' . uniqid();
        $data      = '';
        if ($postFields != null) {
            foreach ($postFields as $name => $content) {
                $data .= "--" . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"';
                $data .= "\r\n\r\n" . $content . "\r\n";
            }
            unset($name, $content);
        }

        if ($fileFields != null) {
            foreach ($fileFields as $name => $file) {
                $data .= "--" . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $file['name'] . "\" \r\n";
                $data .= 'Content-Type: ' . $file['type'] . "\r\n\r\n";
                $data .= $file['content'] . "\r\n";
            }
            unset($name, $file);
        }
        $data .= "--" . $delimiter . "--";

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'Content-Length: ' . strlen($data)
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $reponse = curl_exec($ch);
        unset($data);

        $errno = curl_errno($ch);
        if ($errno) {
            curl_close($ch);
            die($errno);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (200 !== $httpStatusCode) {
                die($reponse);
            }
        }
        return $reponse;
    }

    public function execute(LazopRequest $request, $accessToken = null)
    {
        $sysParams["app_key"]     = $this->appkey;
        $sysParams["sign_method"] = $this->signMethod;
        $sysParams["timestamp"]   = $this->msectime();
        if (null != $accessToken) {
            $sysParams["access_token"] = $accessToken;
        }

        $apiParams = $request->udfParams;

        $requestUrl = $this->gatewayUrl;

        if ($this->endWith($requestUrl, "/")) {
            $requestUrl = substr($requestUrl, 0, -1);
        }

        $requestUrl .= $request->apiName;
        $requestUrl .= '?';

        $sysParams["partner_id"] = $this->sdkVersion;

        $sysParams["sign"] = $this->generateSign($request->apiName, array_merge($apiParams, $sysParams));

        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }

        $requestUrl = substr($requestUrl, 0, -1);

        $resp = '';
        if ($request->httpMethod == 'POST') {
            $resp = $this->curl_post($requestUrl, $apiParams, $request->fileParams, $request->headerParams);
        } else {
            $resp = $this->curl_get($requestUrl, $apiParams, $request->headerParams);
        }

        unset($apiParams);

        return $resp;
    }

    function msectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        return sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    function endWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return false;
        }
        return (substr($haystack, -$length) === $needle);
    }

}
