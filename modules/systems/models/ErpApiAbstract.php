<?php
namespace app\modules\systems\models;
use app\components\Curl;
use Yii;
abstract class ErpApiAbstract
{
    /**
     * @desc api url 地址
     * @var unknown
     */
    public $url = null;
    
    /**
     * @desc api base url
     * @var unknown
     */
    public $baseUrl = null;
    
    /**
     * @desc 请求url
     * @var unknown
     */
    public $requestUri = null;
    
    /**
     * @desc api 方法名称
     * @var unknown
     */
    public $apiMethod = null;
    
    /**
     * @desc 异常信息
     * @var unknown
     */
    public $exceptionMessage = null;
    
    /**
     * @desc 发送请求的客户端
     * @var unknown
     */
    protected $_client = null;
    
    /**
     * @desc 签名
     * @var unknown
     */
    protected $_signature = null;
    
    /**
     * @desc api token
     * @var unknown
     */
    protected $_token = '';
    
    protected $_secretKey = '';
    
    protected $_responseBody = null;
    
    protected $_response = null;
    
    public function __construct()
    {
        $config = include \Yii::getAlias('@app') . '/config/erp_api.php';
        $this->baseUrl = isset($config['baseUrl']) ? $config['baseUrl'] : '';
        $this->_secretKey = isset($config['sercetKey']) ? $config['sercetKey'] : '';
        if (isset(\Yii::$app->user))
        {
            $indentity = \Yii::$app->user->getIdentity();
            if (!empty($indentity)){
                $this->_token = $indentity->token;
            }
        }
        $this->_client = new Curl();
    }
    
    public function setRequestUri($uri)
    {
        $this->requestUri = $uri;
    }
    
    public function setApiMethod($apiMethod)
    {
        $this->apiMethod = $apiMethod;
        return $this;
    }
    
    public function setUrl($url = null)
    {
        //$tokenFile = Yii::getAlias('@app') . '/web/tokens.php';
        //if(file_exists($tokenFile)){
        //    $token = file_get_contents($tokenFile);
        //    echo $token;
        //}
        if ($url != '')
            $this->url = $url;
        else
            $this->url = $this->baseUrl . '/' . trim($this->requestUri, '/') . '/' . $this->apiMethod;
        if ($this->_token) 
            $this->url .= strpos($this->url, '?') === false ?
                '?token=' . $this->_token : '&token=' . rawurlencode($this->_token);
    }
    
    public function sendRequest($params = [], $type = 'post')
    {
        $this->setUrl();
        if ($type == 'post')
        {
            if (is_array($params))
                $params = json_encode($params);
            $this->_client->setOption(CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            $this->calculateSignature([]);
            $this->url .= strpos($this->url, '?') === false ?
            '?signature=' . rawurlencode($this->_signature) : '&signature=' . rawurlencode($this->_signature);
//            echo $this->url;die
            $this->_responseBody = $this->_client->post($this->url, $params);
//            echo '<pre>';
//           var_dump($this->url,$params,$this->_responseBody);
//            echo '</pre>';
//            die;
        }
        else
        {
            $this->calculateSignature($params);
            $this->url .= strpos($this->url, '?') === false ?
            '?signature=' . rawurlencode($this->_signature) : '&signature=' . rawurlencode($this->_signature);
            $this->_responseBody = $this->_client->get($this->url, $params);
//            echo '<pre>';
//            var_dump($this->_responseBody);
//            echo '</pre>';
//            die;
        }
        if($this->_responseBody == 'Token is Invalid')
        {
            $host = \Yii::$app->request->getHostInfo();
            $url = $host.'/users/user/logout';
            header('location: '.$url);
        }
    }
    
    /**
     * @desc 生成签名
     * @param unknown $params
     */
    public function calculateSignature($params)
    {
        //将请求url和参数生成签名串
        $urls = parse_url($this->url);
        $signatureStr = $urls['host'] . $urls['path'];
        $queryStr = isset($urls['query']) ? $urls['query'] : '';
        $queryArr = explode('&', $queryStr);
        $queryParams = [];
        array_walk($queryArr, function($value) use (&$queryParams) {
            if (empty($value)) return;
            $arr = explode('=', $value);
            $queryParams = array_merge($queryParams, [$arr[0] => $arr[1]]);
        });
        if (empty($params))
            $params = [];
        $queryParams = array_merge($queryParams, $params);
        ksort($queryParams);
        foreach ($queryParams as $key => $value)
        {
            $signatureStr .= $key . $value;
        }
        $signature = hash_hmac('sha256', $signatureStr, $this->_secretKey, true);
        $signature = base64_encode($signature);
        $this->_signature = $signature;
    }
    
    public function getResponse()
    {
        return $this->_response;
    }
    
    public function isSuccess()
    {
        if (empty($this->_responseBody))
        {
            $this->exceptionMessage = 'Server Response Empty';
            return false;
        }
        $httpCode = $this->_client->getHttpCode();
        if ($httpCode != '200')
        {
            $this->exceptionMessage = 'Request Failed, HTTP CODE ' . $httpCode;
            return false;
        }
        
        try
        {
            //去掉返回数据中的BOM头
            $this->_responseBody = trim($this->_responseBody, "\xEF\xBB\xBF");
            $responseJson = \yii\helpers\Json::decode($this->_responseBody, false);
            $this->_response = $responseJson;
            if (isset($responseJson->ack) && $responseJson->ack != true)
            {
                $errorMessage = isset($responseJson->message) ? $responseJson->message : '';
                $this->exceptionMessage = $errorMessage;
                return false;
            }
            return true;
        }
        catch (\Exception $e)
        {
            $this->exceptionMessage = 'Server Response Error';
            return false;
        }
    }
    
    public function getExcptionMessage()
    {
        return $this->exceptionMessage;
    }
    
    public function getToken(){
        $url = "http://tkk.yibainetwork.com/token.php?callback=http://kefu.yibainetwork.com/services/order/order/token?token={token}";
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        
        $info = json_encode($data);
        return $data;
    }
    
    /**
     * @desc 返回响应原始数据
     */
    public function getResponseRaw()
    {
        return $this->_responseBody;
    }
    
}