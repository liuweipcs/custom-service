<?php
/**
 * @desc 奇门Api对象
 */
namespace app\modules\services\modules\aliexpress\components;
use app\components\ConfigFactory;
class TaobaoQimenApi
{
    /**
     * @desc app key
     * @var unknown
     */
    protected $_appKey = null;
    
    /**
     * @desc secret key
     * @var unknown
     */
    protected $_secretKey = null;
    
    /**
     * @desc access token
     * @var unknown
     */
    protected $_accessToken = null;
    
    /**
     * @desc 请求响应原始内容
     * @var unknown
     */
    protected $_responseRaw = null;
    
    /**
     * @desc 错误信息
     * @var unknown
     */
    protected $_errorMessage = null;
    
    /**
     * @desc 响应内容
     * @var unknown
     */
    protected $_response = null;
    
    /**
     * @desc 奇门客户端对象
     * @var unknown
     */
    protected $_client = null;
    
    protected $_success = true;
    
    /**
     * @desc construct function
     * @param string $appKey
     * @param string $secretKey
     * @param string $accessToken
     */
    public function __construct($appKey = null, $secretKey = null, $accessToken = null,$erp_gatewayUrl = null)
    {
        $this->_appKey = $appKey;
        $this->_secretKey = $secretKey;
        $this->_accessToken = $accessToken;
        require_once \yii::$app->getVendorPath() . '/taobao/TopSdk.php';
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $targetAppKey = isset($qimenApiInfo['targetAppKey']) ? trim($qimenApiInfo['targetAppKey']) : '';
        if(empty($erp_gatewayUrl)) {
            $gatewayUrl = isset($qimenApiInfo['gatewayUrl']) ? trim($qimenApiInfo['gatewayUrl']) : '';
        }else{
            $gatewayUrl = isset($qimenApiInfo['erp_gatewayUrl']) ? trim($qimenApiInfo['erp_gatewayUrl']) : '';
        }
        //创建qimenClient对象
        $qimenClient = new \QimenCloudClient();
        $qimenClient->targetAppkey = $targetAppKey;
        $qimenClient->gatewayUrl = $gatewayUrl;
        $qimenClient->appkey = $this->_appKey;
        $qimenClient->secretKey = $this->_secretKey;
        $qimenClient->format = 'json';
        $this->_client = $qimenClient;
    }
    
    /**
     * @desc 设置请求网关地址
     * @param unknown $url
     */
    public function setGatewayUrl($url)
    {
        $this->_client->gatewayUrl = $url;
    }
    
    /**
     * @desc 获取客户端对象
     * @return \app\modules\services\modules\aliexpress\components\unknown
     */
    public function getClient()
    {
        return $this->_client;
    }
    
    /**
     * @desc 处理API请求
     * @param unknown $request
     * @return \app\modules\services\modules\aliexpress\components\TaobaoQimenApi
     */
    public function doRequest($request)
    {
        $response = $this->_client->execute($request, $this->_accessToken);
        $this->_responseRaw = $response;
        //print_r($this->_responseRaw);
        return $this;
    }
    
    /**
     * @desc 判断请求是否成功
     * @return boolean
     */
    public function isSuccess()
    {
        if (empty($this->_responseRaw) || !is_object($this->_responseRaw))
        {
            $this->_errorMessage = '服务器响应错误';
            return false;
        }
        if (isset($this->_responseRaw->flag) && $this->_responseRaw->flag == 'failure')
        {
            if (isset($this->_responseRaw->sub_message))
                $this->_errorMessage = $this->_responseRaw->sub_message;
            else if (isset($this->_responseRaw->message))
                $this->_errorMessage = $this->_responseRaw->message;
            else
                $this->_errorMessage = '服务器返回未知错误';
            return false;
        }
        if (isset($this->_responseRaw->code))
        {
            if (isset($this->_responseRaw->msg))
                $this->_errorMessage = $this->_responseRaw->msg;            
        }
        if (isset($this->_responseRaw->result_code) && $this->_responseRaw->result_code != '200')
        {
            $this->_errorMessage = '服务器处理失败';
            return false;
        }
        $this->_processResponseRaw();
        return $this->_success;
    }
    
    /**
     * @desc 获取请求内容
     * @return \app\modules\services\modules\aliexpress\components\unknown
     */
    public function getResponse()
    {
        if ($this->_response === null)
            $this->_processResponseRaw();
        return $this->_response;
    }
    
    /**
     * @desc 处理响应的原始数据
     */
    protected function _processResponseRaw()
    {
        if (is_object($this->_responseRaw))
        {
            if (isset($this->_responseRaw->result_list)) {
                $response = unserialize($this->_responseRaw->result_list);
                if (isset($response->result)) {
                    $this->_response = $response->result;
                } else {
                    if (isset($response->sub_msg))
                    {
                        $this->_success = false;
                        $this->_errorMessage = $response->sub_msg;
                    }
                    else {
                        $this->_response = $response;
                    }
                }
            } else if (isset($this->_responseRaw->result_desc)) {
                $result_desc = trim($this->_responseRaw->result_desc, '{}');
                $result_desc_arr = explode(',', $result_desc);

                if (!empty($result_desc_arr)) {
                    $tmp = [];
                    foreach ($result_desc_arr as $item) {
                        $arr = explode('=', trim($item));
                        if (!empty($arr) && count($arr) == 2) {
                            $tmp[$arr[0]] = $arr[1];
                        }
                    }
                    $result_desc_arr = $tmp;
                }

                if (!empty($result_desc_arr['is_success']) && $result_desc_arr['is_success'] == 'false') {
                    $this->_success = false;
                }
                $this->_errorMessage = !empty($result_desc_arr['memo']) ? $result_desc_arr['memo'] : $this->_responseRaw->result_desc;
            }
        }
    }
    
    /**
     * @desc 获取错误信息
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }
    
    /**
     * @desc 获取响应原始数据
     * @return \app\modules\services\modules\aliexpress\components\unknown
     */
    public function getResponseRaw()
    {
        return $this->_responseRaw;
    }
}