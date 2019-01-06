<?php
/**
 * Date: 2017/1/11 0011
 * Time: 下午 6:11
 */

namespace app\modules\services\modules\amazon\components;

class RequestReportRequest extends AmazonApiAbstract
{
    /**
     * The report type
     *
     * @var string
     */
    protected $_reportType = '';

    /**
     * The request report result
     * @var array
     */
    protected $_result = null;

    /**
     * start date
     * 
     * @var string
     */
    protected $_startDate = '';

    /**
     * end date
     * 
     * @var string
     */
    protected $_endDate = '';

    /**
     * @param $reportType
     *
     * @return $this
     */
    public function setReportType($reportType)
    {
        $this->_reportType = $reportType;

        return $this;
    }

    /**
     * set end date
     * 
     * @param [type] $date utc
     */
    public function setStartDate($date)
    {
        $this->_startDate = $date;

        return $this;
    }

    /**
     * set end date
     * @param [type] $date utc
     */
    public function setEndDate($date)
    {
        $this->_endDate = $date;

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

    /**
     * @inheritdoc
     */
    public function setRequest()
    {
        // TODO: Implement setRequest() method.
        $request = new \MarketplaceWebService_Model_RequestReportRequest();
        $request->setMerchant($this->_merchantID);

        $request->setReportType($this->_reportType);
        $request->setMarketplaceIdList(array('Id' => array($this->_marketplaceID)));

        if ($this->fromDate != '') {
            $fromDate = date('Y-m-d H:i:s', strtotime($this->fromDate));
            $fromDate = new \DateTime($fromDate, new \DateTimeZone('UTC'));
            $request->setStartDate($fromDate);
        }

        if ($this->toDate != '') {
            $toDate = date('Y-m-d H:i:s', strtotime($this->toDate));
            $toDate = new \DateTime($toDate, new \DateTimeZone('UTC'));
            $request->setEndDate($toDate);
        }

        $this->request = $request;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function sendHttpRequest()
    {
        // TODO: Implement sendHttpRequest() method.
        try {
            $this->response = $this->_service->requestReport($this->request);
        } catch (Exception $e) {
            $this->response = $e->getMessage();
        }

        return $this;
    }

    public function sendHttp()
    {
        try {
            $this->response = $this->_service->requestReport($this->request);
        } catch (Exception $e) {
            $this->response = $e->getStatusCode();

            $model = new AmazonApiErrorLog();
            $model->account = $this->_accountName;
            $model->event_type = 'report';
            $model->event_desc = $this->_reportType;
            $model->status_code = $e->getStatusCode();
            $model->message = $e->getMessage();
            $model->save();
        }
        return $this;
    }

    /**
     *
     * 解析返回的响应报文
     *
     * @param $response
     * @return array
     *
     */
    public function parseResponse($response = null)
    {
        $data     = array();
        $response = $response ? $response : $this->response;

        if (!is_object($response)) return [];

        if (!$response->isSetRequestReportResult()) return [];

        $requestReportResult = $response->getRequestReportResult();

        if (!$requestReportResult->isSetReportRequestInfo()) return [];

        $requestReportInfo = $requestReportResult->getReportRequestInfo();

        if ($requestReportInfo->isSetReportRequestId()) {
            $data['report_request_id'] = $requestReportInfo->getReportRequestId();
        }

        if ($requestReportInfo->isSetReportType()) {
            $data['report_type'] = $requestReportInfo->getReportType();
        }

        if ($requestReportInfo->isSetStartDate()) {
            $data['start_date'] = $requestReportInfo->getStartDate()->format('Y-m-d H:i:s');
        }

        if ($requestReportInfo->isSetEndDate()) {
            $data['end_date'] = $requestReportInfo->getEndDate()->format('Y-m-d H:i:s');
        }

        if ($requestReportInfo->isSetReportProcessingStatus()) {
            $data['processing_status'] = $requestReportInfo->getReportProcessingStatus();
        }

        if ($requestReportInfo->isSetSubmittedDate()) {
            $data['submitted_date'] = $requestReportInfo->getSubmittedDate()->format('Y-m-d H:i:s');
        }

        $this->_result = $data;

        return $data;
    }
}