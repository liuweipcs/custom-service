<?php
/**
 * @desc curl类
 * @author Fun
 */
namespace app\components;
class Curl {
	/**
	 * @desc curl实例
	 * @var unknown
	 */
	protected $_curl = null;
	
	/**
	 * @desc curl选项
	 * @var unknown
	 */
	protected $_options = [
	    CURLOPT_RETURNTRANSFER => true,
	    CURLOPT_CONNECTTIMEOUT => 60,
	    CURLOPT_TIMEOUT => 60,
	];
	
	/**
	 * @desc constructor
	 */
	public function __construct() {
		$this->_curl = curl_init();
	}
	
	/**
	 * @desc post request
	 * @param unknown $url
	 * @param unknown $params
	 * @return mixed
	 */
	public function post($url, $params)
	{
	    $requestParams = $this->parseParams($params);
	    $this->setOption(CURLOPT_URL, $url);
            $this->setOption(CURLOPT_POST, true);
            $this->setOption(CURLOPT_POSTFIELDS, $requestParams);
            $this->init();
	    return curl_exec($this->_curl);
	}
	
	/**
	 * @desc get request
	 * @param unknown $url
	 * @param unknown $params
	 * @return mixed
	 */
	public function get($url, $params)
	{
	    $requestParams = $this->parseParams($params);
	    if (strpos($url, '?') !== false)
	       $url .= '&' . $requestParams;
	    else
	        $url .= '?' . $requestParams;
	    $this->setOption(CURLOPT_URL, $url);
	    $this->setOption(CURLOPT_POST, false);
	    $this->init();
	    return curl_exec($this->_curl);
	}
	
	/**
	 * @desc init curl
	 */
	public function init()
	{
	    if (!empty($this->_options)) {
	        foreach ($this->_options as $key => $value) {
	            curl_setopt($this->_curl, $key, $value);
	        }
	    }	    
	}
	
	/**
	 * @desc 设置curl选项
	 * @param unknown $option
	 * @param unknown $value
	 * @return MtyCore_Classes_Curl
	 */
	public function setOption($option, $value) {
		$this->_options[$option] = $value;
		return $this;
	}
	
	/**
	 * @desc 解析请求参数
	 * @param unknown $params
	 * @return Ambigous <string, unknown>
	 */
	public function parseParams($params) {
		$postFields = '';
		if (is_array($params)) {
			foreach ($params as $key => $value) {
				$postFields .= $key . '=' . urlencode($value) . '&';
			}
			$postFields = rtrim($postFields, '&');
		} else {
			$postFields = $params;
		}
		
		return $postFields;
	}
	
	/**
	 * @desc 获取错误代码
	 * @return int
	 */
	public function getErrorNo() {
		return curl_errno($this->_curl);
	}
	
	/**
	 * @desc 获取错误描述
	 * @return string
	 */
	public function getError() {
		return curl_error($this->_curl);
	}

	public function close()
    {
        curl_close($this->_curl);
    }
	
	/**
	 * @desc 获取HTTP CODE
	 * @return mixed
	 */
	public function getHttpCode()
	{
	    $return = curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
        $this->close();
        return $return;
	}
}