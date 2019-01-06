<?php
namespace app\modules\services\modules\amazon\components;

class GetReportRequest extends AmazonApiAbstract
{
	/**
	 * reporit id 由ReportListRequest处获得
	 * 
	 * @var string
	 */
	protected $reportId = '';

	/**
	 * php std
	 * 
	 * @var string
	 */
	protected $tmp = '';


	/**
	 * @see before
	 * @return string
	 */
	public function getReportId()
	{
		return $this->reportId;
	}
	
	public function setReportId($reportId)
	{
		$this->reportId = $reportId;
		return $this;
	}
	
	public function getFileName()
	{
		return $this->tmp;
	}
	
	public function setFileName($file)
	{
		$this->tmp = $file;
	}
	
	public function setRequest()
	{
   		$request = new \MarketplaceWebService_Model_GetReportRequest();
		$request->setMerchant($this->_merchantID);

		$tmpFile = @fopen('php://memory', 'rw+');
		$this->setFileName($tmpFile);

		$request->setReport($tmpFile);
		$request->setReportId($this->getReportId());

		$this->request = $request;

		return $this;
	}

	/**
	 * 获取request
	 * 
	 * @return object
	 */
	public function getRequest()
	{
		return $this->request;
	}
	
	/**
     * send http request
     * 
     * @return object 
     */
    public function sendHttpRequest()
    {
    	try{
   			$this->response = $this->_service->getReport($this->request);
    		
    	}catch(\MarketplaceWebService_Exception $e){
            $this->response = $e->getMessage();
    	}
    	
    	return $this;
    }

    /**
     * paser response data
     * 
     * @return string|false
     */
    public function parseResponse($response = null)
    {
    	if (!is_object($this->response)) return false;

    	return stream_get_contents($this->getFileName());
    }
}