<?php
namespace app\modules\services\modules\amazon\components;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/29 0029
 * Time: 下午 4:12
 */
class GetFeedSubmissionResultRequest extends AmazonApiAbstract
{
    /**
     * @var string
     */
    protected $_submissionId = '';

    /**
     * @var array
     */
    public $_submitResult =array();

    /**
     * @var string
     */
    public $_xml = '';

     /**
     *
     * 获取xml内容
     * @return string
     */

    public function getXmlResult() {
        return $this->_xml;
    }
    /**
     *
     * 获取SubmitResult
     *
     * @param $SubmitResult
     * @return array
     */

    public function getSubmitResult() {
        return $this->_submitResult;
    }

    /**
     *
     * 设置submissionId
     *
     * @param $submissionId
     * @return $this
     */
    public function setSubmissionId($submissionId)
    {
        $this->_submissionId = $submissionId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setRequest()
    {
        // TODO: Implement setRequest() method.
        $parameters = array(
            'Merchant' => $this->_merchantID,
            'FeedSubmissionId' => $this->_submissionId,
            'FeedSubmissionResult' => @fopen('php://temp', 'rw+'),
        );

        $this->request = new \MarketplaceWebService_Model_GetFeedSubmissionResultRequest($parameters);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function sendHttpRequest()
    {
        // TODO: Implement sendHttpRequest() method.
        try {
            $this->response = $this->_service->getFeedSubmissionResult($this->request);
            $this->_xml = stream_get_contents($this->request->getFeedSubmissionResult());
            //echo $this->_xml;die;
            $obj = simplexml_load_string($this->_xml);
            $this->_submitResult = json_decode(json_encode($obj),TRUE);
            //echo '<pre>';
            //var_dump($this->_submitResult);

        } catch (\Exception $e) {
            $this->response = $e->getMessage();
            echo $e->getMessage();
        }

        return $this;
    }

    /**
     *
     * 解析 response
     *
     * @param $response
     *
     * @return array
     *
     */
    public function parseResponse($response = null)
    {
        $data = array();

        if (!is_object($response)) {
            return $data;
        }

        if ($response->isSetGetFeedSubmissionResultResult()) {
            $getFeedSubmissionResultResult = $response->getGetFeedSubmissionResultResult();

            if ($getFeedSubmissionResultResult->isSetContentMd5()) {
                $data['ContentMd5'] = $getFeedSubmissionResultResult->getContentMd5();
            }
        }

        if ($response->isSetResponseMetadata()) {
            $responseMetadata = $response->getResponseMetadata();

            if ($responseMetadata->isSetRequestId()) {
                $data['RequestId'] = $responseMetadata->getRequestId();
            }
        }

        $data['ResponseHeaderMetadata'] = $response->getResponseHeaderMetadata();

        return $data;
    }
}
