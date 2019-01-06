<?php

namespace app\modules\services\modules\amazon\components;

/**
 * @package ~
 * @author hello world <iwk.123@qq.com>
 */
abstract class AmazonApiAbstract implements ApiInterface
{
    const DATE_SERVICE_MWS = '2010-10-01';
    
   /**
    * 
    * @var object in instance
    */
	private static $_instance;
	
	/**
     * 
     * @var string service url
     */
	protected $_serviceUrl;
    
    /**
     *
     * @var string service host 
     */
    protected $_serviceHost;

    /**
     *
     * @var object service
     */
	protected $_service;
	
	/**
     *
     * @var object request 
     */
	public $request;
    
    /**
     *
     * @var object response
     */
    public $response;
	
	/**
     *
     * @var object config
     */
	protected $_config;
	
	/**
     *
     * @var string merchant id 
     */
	protected $_merchantID;
		
    /**
     *
     * @var string  marketplace id
     */
	protected $_marketplaceID;
    /**
     *
     * @var string access key id 
     */
	protected $_awsAccessKeyID;
    /**
     *
     * @var string aws secret access ID
     */
	protected $_awsSecretAccessID;
	   
    /**
     *
     * @var string application name
     */
	protected $_applicationName = "yibai_app";
		
    /**
     *
     * @var string application version
     */
	protected $_applicationVersion = '1.0';
	
	/**
     *
     * @var string full fill channel
     */
	public $fulFillChannel;
	
    /**
     *
     * @var array cron log params
     */
	public $cronLogParams = array();
    
    /**
     * set the type
     * @var string type  
     */
    public $type = null;
    
    /**
     * set the account name
     * @var type 
     */
    protected $_accountName = null;

    /**
     * set the account id
     * @var type
     */
    protected $_accountId = null;

    public function __construct() {}
    
    /**
     * 
     * @return object get instance
     */
    public static function getInstance()
    {
		if(! self::$_instance instanceof self){
			self::$_instance = new self();
		}
		
		return self::$_instance; 
	}
    
    /**
     * service url
     * @param string $serviceUrl
     */
    public function setServiceUrl($serviceUrl='')
    {
        $this->_serviceUrl = $this->_serviceHost . $serviceUrl;

        return $this;
    }
    
    /**
     *  set account name
     * 
     * @param string $accountName
     */
    public function setAccountName($accountName)
    {
        $this->_accountName = $accountName;
        $accountInfo = Account::getAccount($accountName);
        if (!$accountInfo) throw new \Exception("Account Not Exists", 1);

        $this->_merchantID = $accountInfo->merchant_id;
        $this->_marketplaceID = $accountInfo->market_place_id;
        $this->_awsAccessKeyID = $accountInfo->aws_access_key_id;
        $this->_awsSecretAccessID = $accountInfo->secret_key;
        $this->_serviceHost = $accountInfo->service_url;
		
        return $this;
    }

    /**
     *  set account name
     *
     * @param string $accountName
     */
    public function setAccountId($oldAccountId)
    {
        $this->_accountId = $oldAccountId;
        $accountInfo = Account::getAccountByOldId($oldAccountId);
        if (!$accountInfo) throw new \Exception("Account Not Exists", 1);

        $this->_merchantID = $accountInfo->merchant_id;
        $this->_marketplaceID = $accountInfo->market_place_id;
        $this->_awsAccessKeyID = $accountInfo->aws_access_key_id;
        $this->_awsSecretAccessID = $accountInfo->secret_key;
        $this->_serviceHost = $accountInfo->service_url;

        return $this;
    }
    
    /**
     * set config
     * 
     * @return \AmazonApiAbstract
     */
    public function setConfig()
    {
        $this->_config = array (
			'ServiceURL'    => $this->_serviceUrl,
			'ProxyHost'     => null,
			'ProxyPort'     => -1,
			'MaxErrorRetry' => 3
        );
        
        return $this;
    }


    /**
     *  set type
     * @param string $type
     * @return \AmazonApiAbstract
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    
    /**
     * set ful fill channel
     * 
     * @param string $fulFillChannel
     * @return \AmazonApiAbstract
     */
    public function setFulFillChannel($fulFillChannel)
    {
        $this->fulFillChannel = $fulFillChannel;
        
        return $this;
    }
     
    /**
     * set service
     */
    public function setService()
    {   
    	switch (strtolower($this->type)){   	
    		case 'webserviceorders':                
                $this->_service = new \MarketplaceWebServiceOrders_Client(
                    $this->_awsAccessKeyID,
                    $this->_awsSecretAccessID,
                    $this->_applicationName,
                    $this->_applicationVersion,
                    $this->_config
                );
    			break;
            
    		case 'webserviceproducts':
    			$this->_service = new \MarketplaceWebServiceProducts_Client(
                    $this->_awsAccessKeyID,
                    $this->_awsSecretAccessID,
                    $this->_applicationName,
                    $this->_applicationVersion,
                    $this->_config
                );
    			break;
            
    		case 'webservice':   		
    			$this->_service = new \MarketplaceWebService_Client(
                    $this->_awsAccessKeyID,
                    $this->_awsSecretAccessID,
                    $this->_config,
                    $this->_applicationName,
                    $this->_applicationVersion
                );
    			break;
            
	    	 case 'servicemws':
                $this->_service = new \FBAInventoryServiceMWS_Client(
                    $this->_awsAccessKeyID,
                    $this->_awsSecretAccessID,
                    $this->_config,
                    $this->_applicationName,
                    self::DATE_SERVICE_MWS
	    		);	    		
	    	break;		  			   		
    	}
        return $this;
    }   
    
    /**
     * get request
     * 
     * @throws Exception
     */
    public function getRequest()
    {
        if (empty($this->request))  {
            throw new \Exception('The request is not allowed to be empty');
        }
        
        return $this->request;
    }
    
     /**
     * @return object get response
     */
    public function getResponse()
    {
        return $this->response;
    }	
    /**
     * transform text to array
     * 
     * @param  string $txt
     * 
     * @return array
     */
    public static function turnTxt2Array($txt)
    {
        $array = array();
        $txt   = explode("\n", $txt);
        $keys  = array_map('trim', explode("\t", trim(array_shift($txt))));

        foreach ($txt as $unit) {
            $unit = array_map('trim', explode("\t", $unit));

            if (count($keys) != count($unit)) {
                continue;
            }
            $array[] = array_combine($keys, $unit);
        }

        return $array;
	}
}
