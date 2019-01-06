<?php

namespace app\modules\services\modules\lazada\components;

class LazopRequest
{
    public $apiName;

    public $headerParams = array();

    public $udfParams = array();

    public $fileParams = array();

    public $httpMethod = 'POST';

    public function __construct($apiName, $httpMethod = 'POST')
    {
        $this->apiName    = $apiName;
        $this->httpMethod = $httpMethod;

        if ($this->startWith($apiName, "//")) {
            die("api name is invalid. It should be start with /");
        }
    }

    function addApiParam($key, $value)
    {

        if (!is_string($key)) {
            die("api param key should be string");
        }

        if (is_object($value)) {
            $this->udfParams[$key] = json_decode($value);
        } else {
            $this->udfParams[$key] = $value;
        }
    }

    function addFileParam($key, $content, $mimeType = 'application/octet-stream')
    {
        if (!is_string($key)) {
            die("api file param key should be string");
        }

        $file                   = array(
            'type'    => $mimeType,
            'content' => $content,
            'name'    => $key
        );
        $this->fileParams[$key] = $file;
    }

    function addHttpHeaderParam($key, $value)
    {
        if (!is_string($key)) {
            die("http header param key should be string");
        }

        if (!is_string($value)) {
            die("http header param value should be string");
        }

        $this->headerParams[$key] = $value;
    }

    function startWith($str, $needle)
    {
        return strpos($str, $needle) === 0;
    }
}

?>