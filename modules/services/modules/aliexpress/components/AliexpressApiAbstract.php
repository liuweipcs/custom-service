<?php
/**
 *  aliexpress api abstract
 * 
 * @package Ueb.modules.services.modules.aliexpress.components
 * @auther Bob <zunfengke@gmail.com>
 */
abstract class AliexpressApiAbstract implements ApiInterface {

    /**
     * @var object client    
     */
    protected $_client = null;
    
    /**
     * @var object request
     */
    protected $request = null;
    
    /**
     *
     * @var object response
     */
    protected $response = null;


    /**
     * @var array account info 
     */
    protected static $_accountInfo = array();
    
    /**
     * @var string app key 
     */
    protected $_appKey = null;
    
    /**
     * @var string secret key
     */
    protected $_secretKey = null;
    
    /**
     * @var string account short name 
     */
    protected $_shortName = null;
    
    /**
     * add by Tom 2014-02-25
     * @var string access_token
     */
    public  $accessToken = null;
    /**
     *
     * @var string gateway authorize url 
     */
    public $gatewayAuthorizeUrl = null;
    
    /**
     *
     * @var string gateway open api url 
     */
    public $gatewayOpenApiUrl = null;

    public function __construct() {       
        $this->_setOption();          
    }
    
    /**
     *  set short name 
     * @param string $shortName
     * @return \AliexpressModel
     */
    public function setShortName($shortName) {
        $this->_shortName = $shortName;
        if ( empty(self::$_accountInfo) ) {
            self::$_accountInfo = UebModel::model('AliexpressAccount')
                ->getAccountInfoByShortName($shortName);
         
            if ( empty(self::$_accountInfo) ) {
                 throw new Exception("Account information does not exist");
            }
            $this->_client->accessToken = self::$_accountInfo->access_token;
            $this->_appKey = self::$_accountInfo->app_key;
            $this->_secretKey = self::$_accountInfo->secret_key;
        }
     
        return $this;
    }
    
    /**
     * @return string
     */
    public function getAccountInfo() {
        return self::$_accountInfo;
    }

    /**
     *  set app key
     * @param type $appKey
     * @return \AliexpressModel
     */
    public function setAppKey($appKey) {
        $this->_appKey = $appKey;
        
        return $this;
    }

    /**
     * get app key
     * 
     * @return string
     * @throws Exception
     */
    public function getAppKey() {
        if (empty($this->_appKey)) {
            throw new Exception("App key can't be empty");
        }
        
        return $this->_appKey;
    }
    
    /**
     * set secret key
     * 
     * @param string $secretKey
     * @return \AliexpressModel
     */
    public function setSecretKey($secretKey) {
        $this->_secretKey = $secretKey;
        
        return $this;
    }

    /**
     * @return string get secret key
     */
    public function getSecretKey() {
        if (empty($this->_appKey)) {
            throw new Exception("Secret key can't be empty");
        }
        
        return $this->_secretKey;
    }
    
    /**
     * set option
     */
    protected function _setOption() {
        $this->_client = ObjectFactory::getObject('Client');
        $aliexpressKeys = ConfigFactory::getConfig('aliexpressKeys');   
        $this->_appKey = $aliexpressKeys['appKey'];
        $this->_secretKey = $aliexpressKeys['appSecret'];
        $this->gatewayAuthorizeUrl = $aliexpressKeys['gatewayAuthorizeUrl'];
        $this->gatewayOpenApiUrl = $aliexpressKeys['gatewayOpenApiUrl'];
//        $this->_client->appKey =  $this->_appKey;
//        $this->_client->secretKey =  $this->_secretKey;
        
        return $this;
    }
    
    /**
     * get request
     * 
     * @throws Exception
     */
    public function getRequest() {
        if ( empty($this->request) )  {
            throw new Exception('The request is not allowed to be empty');
        }
        
        return $this->request;
    }

    /**
     * get api result
     */
    public function sendHttpRequest() {
        try {

        	$response = $this->_client->setRequest($this->getRequest())
                    ->exec();
            $this->response = $response;                     
        } catch (Exception $e ) {
            Yii::apiDbLog($e->getMessage(), $e->getCode(), get_class($this), 'aliexpress', ULogger::LEVEL_ERROR);
        }       
                
        return $this;
    }	
    
    /**
     * @return object get response
     */
    public function getResponse() {
        
        return $this->response;
    }
}

?>