<?php
/**
 *  aliexpress access token 
 * 
 * @package Ueb.modules.services.modules.aliexpress.components
 * @auther Bob <zunfengke@gmail.com>
 */
class AccessToken extends AliexpressApiAbstract {   
     
    /**
     * @var string code 
     */
    protected $_code = null;
    
    /**
     * set code
     * 
     * @param string $code
     * @return \AccessToken
     */
    public function setCode($code) {
        $this->_code = $code;
        
        return $this;
    }

    /**
     * get code
     */
    public function getCode() {           
        $this->_client->gatewayUrl = $this->gatewayAuthorizeUrl;
        $_SESSION['code_app_key'] = $this->getAppKey();
        $codeUrl = urldecode($this->_client->getCodeUrl());
        header("location:$codeUrl");
    }
    
    /**
     *  set request
     * @return \AccessToken
     */
    public function setRequest() {
        $this->_client->sysQuery = "/http/1/system.oauth2/";
        $this->_client->gatewayUrl =$this->gatewayOpenApiUrl;
        $request = ObjectFactory::getObject('GetAccessToken');
        $request->setGrantType('authorization_code')
                ->setNeedRefreshToken('true')
                ->setCode($this->_code)
                ->putOtherTextParam('client_id', $this->getAppKey())
                ->putOtherTextParam('client_secret', $this->getSecretKey())
                ->putOtherTextParam('redirect_uri', $this->_client->redirectUri);
        $this->request = $request;
        
        return $this;
    }  
}

?>
