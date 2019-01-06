<?php
/**
 * 
 * 功能:分页获取report_id
 * @author Tom 2014-03-03
 *
 */
class ReportRequestNextToken extends AmazonApiAbstract
{
    /**
     * @var string
     */
	protected $_nextToken = '';

	public function getNextToken()
    {
		return $this->_nextToken;
	}
	
	public function setNextToken($nextToken)
    {
		$this->_nextToken = $nextToken;
		return $this;
	}
	
	public function setRequest()
    {
		$request = new MarketplaceWebService_Model_GetReportRequestListByNextTokenRequest();
		$request->setMerchant($this->_merchantID);
		$request->setNextToken($this->getNextToken());
		$this->request = $request;
		return $this;
	}
	
	/**
     * send http request
     * 
     * @return object 
     */
    public function sendHttpRequest()
    {
    	try{
   			$this->response = $this->_service->getReportRequestListByNextToken(
   				$this->request
   			);
    	}catch(MarketplaceWebService_Exception $e){
            $this->response = $e->getMessage();

    		try{ Yii::apiDbLog($e->getMessage(), $e->getCode(), get_class($this), 'amazon', ULogger::LEVEL_ERROR);}
            catch(Exception $e) {}
    	}
    	
    	return $this;
    }
}