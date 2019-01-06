<?php

namespace app\common;
/**
 *  Api access for K3cloud
 * @author 凌云
 * @since 20180601
 *
 * 调用示例
 *
 *      $cloudRequest = CloudRequest::getInstance();
 *      $result = $cloudRequest->cloud_get('material/material/viewmaterial',array('sku' => '90508.01', 'material_id' => '100632'));
 *
 * 生成缩略图调用示例：
 *      CloudRequest::img2thumb($src_img, $dst_img, $width = 100, $height = 100, $cut = 0, $proportion = 0);
 *
 */
class CloudRequest
{

    /**
     * @var string api地址
     */
    public $api_server;
    /**
     * @var string api key
     */
    public $api_key;
    /**
     * @var string api密钥
     */
    public $api_secret;
    /**
     * 应用id
     * @var
     */
    public $appid;
    /**
     * 应用token
     * @var
     */
    public $token;

    /**
     * @var object CloudRequest
     */
    private static $_instance;

    /**
     * cloud api初始化
     */
    private function __construct()
    {

        $cloud_api_conf = require \Yii::getAlias('@app') . '/config/oa_token.php';

        if (!empty($cloud_api_conf[0]['api_server']) && !empty($cloud_api_conf[0]['api_key']) && !empty($cloud_api_conf[0]['api_secret'])) {
            $this->api_server = $cloud_api_conf[0]['api_server'];
            $this->api_secret = $cloud_api_conf[0]['api_secret'];
            $this->appid      = $cloud_api_conf[0]['appid'];
        }

    }


    /**
     * 实例访问入口，单例
     * @return CloudRequest实例
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function cloud_get($url = '', $params = array(), $format_json = 1)
    {
        $data = $this->request($url, 'GET', $params);

        if ($format_json) return json_decode($data, true);

        return $data;
    }

    public function cloud_post($url = '', $params = array(), $format_json = 1)
    {
        $data = $this->request($url, 'POST', $params);

        if ($format_json) return json_decode($data, true);

        return $data;
    }


    private function request($url, $method, $params = array())
    {
        if (empty($url) || empty($params))
            return '{"code":0, "msg":"url or params is null"}';

        $params['token'] = $this->token($params);

        if (!empty($this->api_key)) {
            $params['api_key'] = $this->api_key;
        }

        switch ($method) {
            case 'GET':
                $url      = $this->api_server . "/$url?" . $this->createLinkstring($params);
                $response = $this->http($url, 'GET');
                break;
            default:
                $url = $this->api_server . "/$url";

                $response = $this->http($url, 'POST', $params);
        }
        return $response;
    }

    /**
     * 将API数组参数转换为字符串a=a&b=b&c=c
     * @param $para API请求参数
     * @return string
     */
    public function createLinkstring($para)
    {

        $arg = "";

        foreach ($para as $key => $val) {
            $arg .= $key . "=" . urlencode($val) . "&";
        }

        $arg = substr($arg, 0, count($arg) - 2);

        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * token生成
     * md5("a=a&b=b&c=c".API密钥)
     */
    private function token($params)
    {
        unset($params['token']);
        unset($params['api_key']);

        ksort($params);
        reset($params);
        $token = $this->createLinkstring($params) . $this->api_secret;
        return strtolower(md5($token));
    }

    /**
     * @param $url API URL
     * @param null $data 请求参数
     * @param array $headers 请求头信息
     * @return mixed
     * @throws Exception
     */
    public function http_request($url, $data = null, $headers = array())
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($data) && 0 < count($data)) {
            $postBodyString = "";
            $postMultipart  = false;
            foreach ($data as $k => $v) {
                if ("@" != substr($v, 0, 1))//
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                else
                    $postMultipart = true;
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart)
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            else
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
        }

        $reponse = curl_exec($ch);

        if (curl_errno($ch))
            throw new Exception(curl_error($ch), 0);
        else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode)
                throw new Exception($reponse, $httpStatusCode);
        }

        curl_close($ch);

        return $reponse;
    }

    /**
     * @param $url api url
     * @param $method http请求方式，GET/POST
     * @param array $params 参数
     * @return mixed
     * @throws Exception
     */
    private function http($url, $method, $params = array())
    {

        if ($method == 'POST') {
            $response = $this->http_request($url, $params);
        } else {
            $response = $this->http_request($url);
        }

        return $response;
    }

}