<?php
namespace app\modules\services\modules\api\models;
use yii\helpers\Json;
/**
 * @desc Api基类
 * @author Fun
 *
 */
 
class ApiAbstract
{
    /**
     * @desc 访问秘钥
     * @var unknown
     */
    protected $_secretKey = '5E17C4488C2AC591';
    
    /**
     * @desc 身份是否通过验证
     * @var unknown
     */   
    protected $_identified = false;
    
    /**
     * @desc token
     * @var unknown
     */
    protected $_token = null;
    
    /**
     * @desc Api 错误信息
     * @var unknown
     */
    protected static $_errorMaps = array();
    
    /**
     * @desc 日志对象
     * @var unknown
     */
    protected $_logger = null;
    
    /**
     * @desc 是否开启日志
     * @var unknown
     */
    protected $_enableLog = true;
    
    /**
     * @desc 用户信息
     * @var unknown
     */
    protected $_user = null;
    
    /**
     * @desc 请求端服务器IP
     * @var unknown
     */
    protected $_ip = null;
    
    /**
     * @desc 日志文件名称
     * @var unknown
     */
    protected $_logFilename = '';
    
    const EXPIRE_SECONDS = 86400;
    
    public function __construct()
    {
        
    }
    
    /**
     * @desc 初始化方法
     */
    public function init()
    {
        $this->_initErrorMaps();
    }
    
    /**
     * @desc 初始化错误信息
     */
    private function _initErrorMaps()
    {
        self::$_errorMaps = array(
            '1001' => 'Access Key is Required',
            '1002' => 'Access Key is Invalid'
        );
    }
    
    /**
     * @desc 添加错误信息
     * @param unknown $errorCode
     * @param unknown $errorMsg
     */
    public function addError($errorCode, $errorMsg)
    {
        self::$_errorMaps[$errorCode] = $errorMsg;
    }
    
    /**
     * @desc 身份是否有效
     * @return unknown
     */
    protected function _isIdentified()
    {
        return $this->_identified;
    }
    
    /**
     * @desc 验证身份
     * @param unknown $accessKey
     */
    public function validateIdentity()
    {
        return true;
    }
    
    /**
     * @desc 发送请求响应
     * @param string $code
     * @param string $body
     * @param string $type
     */
    public function sendResponse($code = '200', $body = null, $type = 'application/json')
    {
        $responseBody = '';
        switch ($code)
        {
            case '200':
                $header = 'HTTP/1.0 200 OK';
                $responseBody = 'OK';
                break;
            case '400':
                $header = 'HTTP/1.0 400 Not Found';
                $responseBody = 'Bad Request';
                $type = 'text/html';
                break;
            case '403':
                $header = 'HTTP/1.0 403 Forbidden';
                $responseBody = 'Token is Invalid';
                $type = 'text/html';
                break;                
            case '404':
                $header = 'HTTP/1.0 404 Not Found';
                $responseBody = 'Not Found';
                $type = 'text/html';
                break;
            case '500':
                $header = 'HTTP/1.0 500 Server Internal Error';
                $responseBody = 'Server Internal Error';
                $type = 'text/html';
                break;
        }
        if (!empty($body) && $type == 'application/json') {
            $body = Json::encode($body);
        }
        if (!empty($body))
            $responseBody = $body;
        header($header);
        header('Content-type: ' . $type);
/*         $this->setLogs('RESPONSE CONTENT: ' . var_export($responseBody, true));
        $this->setLogs('============================================');
        $this->writeLog(); */
        echo $responseBody;
        exit;
    }
    
    /**
     * @desc 记录日志
     * @param unknown $filename
     * @param unknown $log
     * @return boolean
     */
    public function writeLog()
    {
        if ($this->_enableLog && $this->_logger instanceof ApiLog)
            return $this->_logger->writeLog($this->_logFilename);
        return false;
    }
    
    /**
     * @desc 设置日志信息
     * @param unknown $log
     */
    public function setLogs($log)
    {
        $this->_logger->setLogs($log);
    }
    
    public function setLogFilename($filename)
    {
        $this->_logFilename = $filename;
    }
    
    /**
     * @desc 触发错误响应
     * @param string $errorCode
     * @param string $errorMsg
     */
    public function triggerError($errorCode = '', $errorMsg = '') {
        $responseObj = new \stdClass();
        $responseObj->ack = false;
        if (!empty($errorCode))
            $responseObj->code = $errorCode;
        if (!empty($errorMsg))
            $responseObj->message = $errorMsg;
        else {
            isset(self::$_errorMaps[$errorCode]) && $errorMsg = self::$_errorMaps[$errorCode];
            $responseObj->message = $errorMsg;
        }
        $this->sendResponse('200', $responseObj);
    }
    
    public function getErrorMessage($errorCode = '')
    {
        $message = '';
        if (isset(self::$_errorMaps[$errorCode]))
            $message = self::$_errorMaps[$errorCode];
        return $message;
    }
    
    public static function apiInit()
    {
        try {
            $remoteIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $time = date('Y-m-d H:i:s');
            $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $apiMethod = \Yii::$app->request->getQueryParam('method');
            $logs = $remoteIp . ' -- ' . $time . ' -- ' . $uri;
            $apiModel = new static();
            $apiModel->_ip = $remoteIp;
            //$apiModel->setLogs($logs);
            if (empty($apiMethod) || !method_exists($apiModel, $apiMethod)) {
                $logFilename = 'ApiLog/' . date('Ym') . '/Api_error-' . date('Y-m-d') . '-log.txt';
                //$apiModel->setLogFilename($logFilename);
                //$apiModel->setLogs('REQUEST PARAMS: ' . var_export($_REQUEST, true));
                $apiModel->sendResponse('400');
            }
            $logFilename = 'ApiLog/' . date('Ym') . '/Api_' . $apiMethod . '_' . date('Y-m-d') . '-log.txt';
            //$apiModel->setLogFilename($logFilename);
    
            $params1 = $_GET;
            $params2 = [];
            if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {
                $params2 = file_get_contents('php://input');
                $params2 = Json::decode($params2);
            }
            else
            {
                $params2 = $_POST;
            }
            //$apiModel->setLogs('REQUEST PARAMS: ' . var_export(array_merge($params1, $params2), true));
            $apiModel->init();
            call_user_func(array($apiModel, $apiMethod), $params1, $params2);
        }
        catch (\Exception $e) {var_dump($e->getMessage());exit;
            $apiModel->sendResponse('500');
        }
        \Yii::$app->end();
    }
    
    
    /**
     * @desc 检查签名
     * @param unknown $params1
     * @param unknown $params2
     * @return boolean
     */
    protected function checkSignature($params1, $params2)
    {
        if (empty($params1))
            $params1 = [];
        if (empty($params2))
            $params2 = [];
        $queryParams = array_merge($params1, $params2);
        $signature = '';
        if (isset($queryParams['signature']))
        {
            $signature =trim($queryParams['signature']);
            unset($queryParams['signature']);
        }
        if (isset($queryParams['method']))
            unset($queryParams['method']);
        $url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $urls = parse_url($url);
        $signatureStr = $urls['path'];
        ksort($queryParams);
        foreach ($queryParams as $key => $value)
        {
            $signatureStr .= $key . $value;
        }
        $safeSignature = hash_hmac('sha256', $signatureStr, $this->_secretKey, true);
        $safeSignature = base64_encode($safeSignature);
        return strcmp($safeSignature, $signature) === 0 ? true : false;
    }
}