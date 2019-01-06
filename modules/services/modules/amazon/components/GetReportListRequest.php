<?php
namespace app\modules\services\modules\amazon\components;

class GetReportListRequest extends AmazonApiAbstract
{
    /**
     * @var string
     */
	protected $reportType = '';

    /**
     * @var string
     */
	protected $fromDate = '';

    /**
     * @var string
     */
    protected $toDate = '';

    /**
     * the return report id
     * 
     * @var array
     */
    protected $reportIds = [];
	
	public function getReportType()
    {
		return $this->reportType;
	}
	
	public function setReportType($reportType)
    {
		$this->reportType = $reportType;
		return $this;
	}
	
	public function setFromDate($fromDate)
    {
		$this->fromDate = $fromDate;
		return $this;
	}

	public function setToDate($toDate)
    {
        $this->toDate = $toDate;
        return $this;
    }

    public function getReportIdList()
    {
    	return $this->reportIds;
    }

    public function setReportIdList($reportId = [])
    {
        $this->reportIds = array_merge($this->reportIds, $reportId);
    }
	
	public function setRequest()
    {
		$request = new \MarketplaceWebService_Model_GetReportListRequest();
		$request->setMerchant($this->_merchantID);  
		 		  

		if ($this->getReportType()) {
		 	$reportType = new \MarketplaceWebService_Model_TypeList();
			$reportType->setType([$this->getReportType()]);
			$request->setReportTypeList($reportType);
	 	}

        if ($this->fromDate != '') {
            $fromDate = date('Y-m-d H:i:s', strtotime($this->fromDate));
            $fromDate = new \DateTime($fromDate, new \DateTimeZone('UTC'));
            $request->setAvailableFromDate($fromDate);
        }

        if ($this->toDate != '') {
            $toDate = date('Y-m-d H:i:s', strtotime($this->toDate));
            $toDate = new \DateTime($toDate, new \DateTimeZone('UTC'));
            $request->setAvailableToDate($toDate);
        }

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
    	try {
    		$this->response = $this->_service->getReportList($this->request);
    	} catch(\Exception $e) {
            $this->response = $e->getMessage();
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
    	$token = false;

    	if (!is_object($this->response)) {
    		return false;
    	}

    	if (!$this->response->isSetGetReportListResult()) {
    		return false;
    	}

		$getReportListResult = $this->response->getGetReportListResult();

		if ($getReportListResult->isSetNextToken()) {
			$token = $getReportListResult->getNextToken();
		}

		$reportInfoList = $getReportListResult->getReportInfoList();

		foreach ($reportInfoList as $reportInfo) {
			if ($reportInfo->isSetReportId()) {
				$this->reportIds[] = $reportInfo->getReportId();
			}
		}

    	return $token;
    }
}