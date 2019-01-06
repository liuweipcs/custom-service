<?php
namespace app\modules\services\modules\amazon\components;
/**
 * Date: 2017/03/01
 * Time: 11:07am
 * Modified by Leal
 */
class GetFeedSubmissionListRequest extends AmazonApiAbstract
{   
    const DATE_FORMAT = 'Y-m-d H:i:s';
    /**
     * @var null
     */
    protected $_statusArr = null;

    /**
     * 
     * @var datetime SubmittedFromDate
     */
    protected $_submittedFromDate = null;

    /**
     * @param $statusArr
     * @return $this
     */
    public function setStatus($statusArr)
    {
        $this->_statusArr = $statusArr;

        return $this;
    }

    /**
     * @param $idArr
     * @return $this
     */
    public function setSubmissionId($idArr)
    {

        $this->_submissionIdArr = $idArr;

        return $this;
    }
    /**
     * @param $date
     * @return $this
     */
    public function setSubmittedFromDate($date)
    {
        $this->_submittedFromDate = $date;

        return $this;
    }
    
    public function getXmlResult() {
        return $this->_xml;
    }
    /**
     * @inheritdoc
     */
    public function setRequest()
    {
        // TODO: Implement setRequest() method.
        $parameters = array(
            'Merchant' => $this->_merchantID,
            'FeedProcessingStatusList' => array('Status' => $this->_statusArr),
            'SubmittedFromDate' => $this->_submittedFromDate,
        );

        $this->request = new \MarketplaceWebService_Model_GetFeedSubmissionListRequest($parameters);
        //$this->request->setSubmittedFromDate($this->_submittedFromDate);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setOneRequest()
    {
        // TODO: Implement setRequest() method.
        $parameters = array(
            'Merchant' => $this->_merchantID,
            'FeedProcessingStatusList' => array('Status' => $this->_statusArr),
            'FeedSubmissionIdList' => array('Id' => $this->_submissionIdArr),
        );

        $this->request = new \MarketplaceWebService_Model_GetFeedSubmissionListRequest($parameters);
        //$this->request->setSubmittedFromDate($this->_submittedFromDate);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function sendHttpRequest()
    {
        // TODO: Implement sendHttpRequest() method.

        try {
            $this->response = $this->_service->getFeedSubmissionList($this->request);
        } catch (\Exception $e) {
            echo $e->getMessage();
            //Yii::apiDbLog($e->getMessage(), $e->getCode(), get_class($this), 'amazon', ULogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     *
     * 解析响应报文
     *
     * @param $response
     *
     * @return array
     *
     */
    public function parseResponse($response = null)
    {
        $data = array();

        if (!is_object($response )) {
            return $data;
        }

        if ($response->isSetGetFeedSubmissionListResult()) {
            $getFeedSubmissionListResult = $response->getGetFeedSubmissionListResult();

            if ($getFeedSubmissionListResult->isSetNextToken())
            {
                $data['NextToken'] = $getFeedSubmissionListResult->getNextToken();
            }
            if ($getFeedSubmissionListResult->isSetHasNext())
            {
                $data['HasNext'] = $getFeedSubmissionListResult->getHasNext();
            }

            $data['list'] = array();
            $feedSubmissionInfoList = $getFeedSubmissionListResult->getFeedSubmissionInfoList();

            foreach ($feedSubmissionInfoList as $feedSubmissionInfo) {
                $item = array();

                if ($feedSubmissionInfo->isSetFeedSubmissionId())
                {
                    $item['FeedSubmissionId'] = $feedSubmissionInfo->getFeedSubmissionId();
                }
                if ($feedSubmissionInfo->isSetFeedType())
                {
                    $item['FeedType'] = $feedSubmissionInfo->getFeedType();
                }
                if ($feedSubmissionInfo->isSetSubmittedDate())
                {
                    $item['SubmittedDate'] = $feedSubmissionInfo->getSubmittedDate()->format(self::DATE_FORMAT);

                }
                if ($feedSubmissionInfo->isSetFeedProcessingStatus())
                {
                    $item['FeedProcessingStatus'] = $feedSubmissionInfo->getFeedProcessingStatus();
                }
                if ($feedSubmissionInfo->isSetStartedProcessingDate())
                {
                    $item['StartedProcessingDate'] = $feedSubmissionInfo->getStartedProcessingDate()->format(self::DATE_FORMAT);
                }
                if ($feedSubmissionInfo->isSetCompletedProcessingDate())
                {
                    $item['CompletedProcessingDate'] = $feedSubmissionInfo->getCompletedProcessingDate()->format(self::DATE_FORMAT);
                }

                $data['list'][] = $item;
            }
        }
        if ($response->isSetResponseMetadata()) {
            $responseMetadata = $response->getResponseMetadata();

            if ($responseMetadata->isSetRequestId())
            {
                $data['RequestId'] = $responseMetadata->getRequestId();
            }
        }

        $data['ResponseHeaderMetadata'] = $response->getResponseHeaderMetadata();

        return $data;
    }
}