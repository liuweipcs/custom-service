<?php 

namespace app\modules\services\modules\amazon\components;

class GetReportListRequestNT extends AmazonApiAbstract
{
    /**
     * @var string
     */
	protected $nextToken = '';

	/**
	 * the return report id
	 * 
	 * @var array
	 */
	protected $reportIds = [];

	public function getReportIdList()
	{
		return $this->reportIds;
	}

	public function getNextToken()
    {
		return $this->nextToken;
	}
	
	public function setNextToken($nextToken)
    {
		$this->nextToken = $nextToken;
		return $this;
	}
	
	public function setRequest()
    {
		$request = new \MarketplaceWebService_Model_GetReportListByNextTokenRequest();
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
   			$this->response = $this->_service->getReportListByNextToken(
   				$this->request
   			);
    	}catch(MarketplaceWebService_Exception $e){
            $this->response = $e->getMessage();
    	}
    	
    	return $this;
    }

    public function parseResponse($response = null)
    {
    	$token = false;

    	if (!is_object($this->response)) {
    		return false;
    	}

    	if (!$this->response->isSetGetReportListByNextTokenResult()) {
    		return false;
    	}
    	
		$getReportListByNextTokenResult = $this->response->getGetReportListByNextTokenResult();

		if ($getReportListByNextTokenResult->isSetNextToken()) {
			$token = $getReportListByNextTokenResult->getNextToken();
		}

		$reportInfoList = $getReportListByNextTokenResult->getReportInfoList();

		foreach ($reportInfoList as $reportInfo) {
			if ($reportInfo->isSetReportId()) {
				$this->reportIds[] = $reportInfo->getReportId();
			}
		}

    	return $token;
    }
}