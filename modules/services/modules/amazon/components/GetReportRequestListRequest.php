<?php
namespace app\modules\services\modules\amazon\components;
/**
 * 
 * 根据reportType获取相应报告的内容
 * @author Tom 2014-03-01
 *
 */
class GetReportRequestListRequest extends AmazonApiAbstract
{
    /**
     * @var string
     */
	protected $_reportType = '';

    /**
     * @var string
     */
	protected $_reportProcessingStatus = ''; //

    /**
     * @var string
     */
	protected $_reportRequestId = '';

    /**
     * @var string
     */
	protected $_fromDate = '';

    /**
     * @var string
     */
    protected $_toDate = '';
	
	public function getReportType()
    {
		return $this->_reportType;
	}
	
	public function setReportType($reportType)
    {
		$this->_reportType = $reportType;
		return $this;
	}
	
	public function getReportRequestId()
    {
		return $this->_reportRequestId;
	}
		
	public function setReportRequestId($requestId)
    {
		$this->_reportRequestId = $requestId;
		return $this;
	}
	
	public function getReportProcessingStatus()
    {
		return $this->_reportProcessingStatus;
	}
	
	public function setReportProcessingStatus($reportStatus)
    {
		$this->_reportProcessingStatus = $reportStatus;
		return $this;
	}
	
	public function setFromDate($fromDate)
    {
		$this->_fromDate = $fromDate;
		return $this;
	}

	public function setToDate($toDate)
    {
        $this->_toDate = $toDate;
        return $this;
    }
	
	public function setRequest()
    {
		 $request = new \MarketplaceWebService_Model_GetReportRequestListRequest();
		 $request->setMerchant($this->_merchantID);
		 $request->setMarketplace($this->_marketplaceID);
		 		  
		if ($this->getReportRequestId()) {
			$requestID = new \MarketplaceWebService_Model_IdList();
			$requestID->setID($this->getReportRequestId());
			
			$request->setReportRequestIdList($requestID);
		}

		if ($this->getReportType()) {
		 	$reportTypObj = new \MarketplaceWebService_Model_TypeList();
			$reportTypObj->setType(array($this->getReportType()));
			$request->setReportTypeList($reportTypObj);
	 	}

	 	if ($this->getReportProcessingStatus()) {
			$statusObj = new \MarketplaceWebService_Model_StatusList();
			$statusObj->setStatus($this->getReportProcessingStatus());

			$request->setReportProcessingStatusList($statusObj);
		}

        if ($this->_fromDate != '') {
            $fromDate = date('Y-m-d H:i:s', strtotime($this->_fromDate));
            $fromDate = new \DateTime($fromDate, new \DateTimeZone('UTC'));

            $request->setRequestedFromDate($fromDate);
        }

        if ($this->_toDate != '') {
            $toDate = date('Y-m-d H:i:s', strtotime($this->_toDate));
            $toDate = new \DateTime($toDate, new \DateTimeZone('UTC'));

            $request->setRequestedToDate($toDate);
        }

		$this->request = $request;

		return $this;
	}
	
	/**
     * send http request
     * 
     * @return object 
     */
    public function sendHttpRequest() {
    	try {
    		$this->response = $this->_service->getReportRequestList($this->request);
    	} catch(Exception $e) {
            $this->response = $e->getMessage();

            try { Yii::apiDbLog($e->getMessage(), $e->getCode(), get_class($this), 'amazon', ULogger::LEVEL_ERROR);}
            catch(Exception $e){Yii::p($e->getMessage());}
    	}
    	return $this;
    }

    /**
     * parse response data
     *
     * @return null|false
     */
    public function parseResponse($response = null)
    {
        return '';
    }
}