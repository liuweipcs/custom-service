<?php
namespace app\modules\services\modules\amazon\components;
/**
 *
 * Date: 2016/12/26 0026
 * Time: 下午 3:12
 *
 * Class SubmitFeedRequest
 *
 * @package ~.*
 * @author mrlin <714480119@qq.com>
 */
class SubmitFeedRequest extends AmazonApiAbstract
{
    const DATE_FORMAT = 'Y-m-d H:i:s';
    /**
     * @constance var
     */
    const INVENTORY           = 1;
    const OFFLINE_PRODUCT     = 2;
    const ONLINE_PRODUCT      = 3;
    const PRICE               = 4;
    const AVAILABLE_INVENTORY = 5;
    const REMOVE_PRODUCT      = 6;
    const NEW_PRODUCT         = 7;
    const ESTABLISH_PRODUCT   = 8;
    const SEND_IMAGE          = 9;
    const REFUND              = 10;

    /**
     * post类型
     *
     * @var string
     */
    protected $feedType = '_POST_PRODUCT_DATA_';

    /**
     * 待更新sku列表
     *
     * @var mixed (string|array)
     */
    protected $reqArrList = array();

    /**
     * 业务类型
     * @var int
     */
    protected $businesstype = 0;

    /**
     * currect xml
     * 
     * @var string
     */
    protected $xml = '';

    /**
     *
     * 库存请求列表
     *
     * @param array $reqArrList
     *
     * @return $this
     */
    public function setReqArrList(array $reqArrList)
    {
        $this->reqArrList = $reqArrList;

        return $this;
    }
    
    /**
     * 设置FeetType
     * 
     * @param $feeType
     * @return $this
     */
    public function setFeedType($feedType)
    {
        $this->feedType = $feedType;

        return $this;
    }

    /**
     *
     * 设置业务类型
     * 
     * @param $type
     * @return $this
     */
    public function setBusinessType($type)
    {
        $this->businesstype = $type;

        return $this;
    }

    /**
     * 设上传xml数据
     * 
     * @param $this
     */
    public function setXML($xml)
    {
        $this->xml = $xml;

        return $this;
    }

    /**
     * 返回最后的XML
     * 
     * @return string
     */
    public function getLastXML()
    {
        return $this->xml;
    }

    /**
     * 构建可用库存xml数据报文
     *
     * sample parameter:
     *
     * reqArrList => array(
     *      array(
     *          'sku' => 'ABDEFE',
     *          'qty' => 3,
     *          'latency' => 1,
     *      ),
     * )
     *
     * @return string
     * @throws Exception
     */
    protected function getAvailableInventoryXMLData()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>$this->_merchantID</MerchantIdentifier></Header><MessageType>Inventory</MessageType>
EOF;

        if (!is_array($this->reqArrList) && empty($this->reqArrList)) {
            throw new \Exception('Bad reqArrList parameter', 1);
        }

        $i = 1;
        foreach ($this->reqArrList as $value) {
            $xml .= <<<EOF
<Message><MessageID>$i</MessageID><OperationType>Update</OperationType><Inventory><SKU>{$value['sku']}</SKU><Quantity>{$value['qty']}</Quantity><FulfillmentLatency>{$value['latency']}</FulfillmentLatency></Inventory></Message>
EOF;
            $i++;
        }

        $xml .= <<<EOF
</AmazonEnvelope>
EOF;

        return $xml;
    }

    /**
     *
     * 构建库存xml请求报文
     *
     * reqArrList => array(
     *      array('sku' => 'ABCDWS', 'qty' => 23),
     *      array('sku' => 'ABCDWD', 'qty' => 44),
     * )
     *
     * @return string
     * @throws Exception
     */
    protected function getInventoryXMLData()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>$this->_merchantID</MerchantIdentifier></Header><MessageType>Inventory</MessageType>
EOF;
        if (!is_array($this->reqArrList) && empty($this->reqArrList)) {
            throw new \Exception("Bad reqArrList parameter", 1);
        }

        $i = 1;
        foreach ($this->reqArrList as $value) {
            $xml .= <<<EOF
<Message><MessageID>$i</MessageID><OperationType>Update</OperationType><Inventory><SKU>{$value['sku']}</SKU><Quantity>{$value['qty']}</Quantity></Inventory></Message>
EOF;
            $i++;
        }

        $xml .= <<<EOF
</AmazonEnvelope>
EOF;

            return $xml;
        }


    /**
     *
     * 构建移除商品xml请求报文
     *
     * reqArrList => array(
     *      array('sku' => 'ABCDWS'),
     *      array('sku' => 'ABCDWD'),
     * )
     *
     * @return string
     */
    public function getRemoveProductXMLData()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>$this->_merchantID</MerchantIdentifier></Header><MessageType>Product</MessageType>
EOF;

        if (!is_array($this->reqArrList) && empty($this->reqArrList)) {
            throw new \Exception('Bad reqArrList parameter', 1);
        }

        $i = 1;
        foreach ($this->reqArrList as $value) {
            $xml .= <<<EOF
<Message><MessageID>$i</MessageID><OperationType>Delete</OperationType><Product><SKU>{$value['sku']}</SKU></Product></Message>
EOF;
            $i++;
        }

        $xml .= <<<EOF
</AmazonEnvelope>
EOF;

        return $xml;
    }

    /**
     *
     * 构建价格报文
     *
     * sample parameter:
     *
     * reqArrList => array(
     *      array(
     *          'sku' => 'ABDEFE',
     *          'currency' => 'USD',
     *          'stdprice' => '204.99',
     *          'stime' => 'xxx',
     *          'etime' => 'xxx',
     *          'saleprice' => '12.9',
     *      ),
     * )
     *
     * @return string
     * @throws Exception
     *
     */
    public function getPriceXMLData()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>$this->_merchantID</MerchantIdentifier></Header><MessageType>Price</MessageType>
EOF;
        if (!is_array($this->reqArrList) && empty($this->reqArrList)) {
            throw new \Exception('Bad reqArrList parameter', 1);
        }

        $i = 1;
        foreach ($this->reqArrList as $value) {
            $xml .= <<<EOF
<Message><MessageID>$i</MessageID><Price><SKU>{$value['sku']}</SKU><StandardPrice currency="{$value['currency']}">{$value['stdprice']}</StandardPrice><Sale><StartDate>{$value['stime']}</StartDate><EndDate>{$value['etime']}</EndDate><SalePrice currency="{$value['currency']}">{$value['saleprice']}</SalePrice></Sale></Price></Message>
EOF;
            $i++;
        }

        $xml .= <<<EOF
</AmazonEnvelope>
EOF;
        return $xml;
    }

    /**
     * @see this
     * @return string
     */
    protected function getOfflineProductXMLData()
    {
        return "";
    }

    /**
     *
     * 构建刊登报文
     * 
     * reqArrList => array(
     *      'product1' => 'You xml string',
     *      'product2' => 'You xml string',
     * )
     * 
     * @return string
     */
    protected function getCatalogProductXMLData()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>$this->_merchantID</MerchantIdentifier></Header><MessageType>Product</MessageType>
EOF;

        if (!is_array($this->reqArrList) && empty($this->reqArrList)) {
            throw new \Exception('Bad reqArrList parameter', 1);
        }

        $i = 1;
        foreach ($this->reqArrList as $value) {
            $xml .= <<<EOF
<Message><MessageID>$i</MessageID><OperationType>Update</OperationType>{$value}</Message>
EOF;
            $i++;
        }

        $xml .= <<<EOF
</AmazonEnvelope>
EOF;
        return $xml;
    }

    /**
     * 构建产品关系XML
     *
     *
     * reqArrList = array(
     *      array(
     *          'parentsku' => 'xxxx', 
     *          'type' => 'Variation',
     *          'sku' => array(
     *              'xxxx',
     *              'xxxx',
     *              'xxxx',
     *          )
     *      ),
     *      array(
     *          'parentsku' => 'xxxx', 
     *          'type' => 'Variation',
     *          'sku' => array(
     *              'xxxx',
     *              'xxxx',
     *              'xxxx',
     *          )
     *      )
     * )
     * 
     * @return string
     */
    protected function getEstablishProductXMLData()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>$this->_merchantID</MerchantIdentifier></Header><MessageType>Relationship</MessageType>
EOF;

        if (!is_array($this->reqArrList) && empty($this->reqArrList)) {
            throw new \Exception('Bad reqArrList parameter', 1);
        }

        $i = 1;
        foreach ($this->reqArrList as $value) {
            $xml .= <<<EOF
<Message><MessageID>{$i}</MessageID><OperationType>Update</OperationType><Relationship><ParentSKU>{$value['parentsku']}</ParentSKU>
EOF;
            foreach ($value['sku'] as $sku) {
                $xml .= <<<EOF
<Relation><SKU>{$sku}</SKU><Type>{$value['type']}</Type></Relation>
EOF;
            }

            $xml .= <<<EOF
</Relationship></Message>
EOF;
            $i++;
        }

        $xml .= <<<EOF
</AmazonEnvelope>
EOF;
        return $xml;        
    }


    /**
     * 
     * 构建image XML
     *
     * reqArrList => array(
     *      array('sku' => 'xxxx', 'type' => 'Main', 'url'=> 'xxxx'),
     *      array('sku' => 'xxxx', 'type' => 'Swatch', 'url' => 'xxxx'),
     * )
     * 
     * @return string
     */
    public function getSendImageXMLData()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>$this->_merchantID</MerchantIdentifier></Header><MessageType>ProductImage</MessageType>
EOF;
        if (!is_array($this->reqArrList) && empty($this->reqArrList)) {
            throw new \Exception('Bad reqArrList parameter', 1);
        }

        $i = 1;
        foreach ($this->reqArrList as $value) {
            $xml .= <<<EOF
<Message><MessageID>$i</MessageID><OperationType>Update</OperationType><ProductImage><SKU>{$value['sku']}</SKU><ImageType>{$value['type']}</ImageType><ImageLocation>{$value['url']}</ImageLocation></ProductImage></Message>
EOF;
            $i++;
        }

        $xml .= <<<EOF
</AmazonEnvelope>
EOF;
        return $xml;
    }

    /**
     * 构建 Refund XML
     * reqArrList => array(
     *     array(
     *         'orderid' => '1234567',
     *         'itemid' => '12343',
     *         'slitemid' => '1234',
     *         'reason' => 'CustomerReturn',
     *         'co' = array(
     *             array('type' => 'Principal', 'currency'=>'USD', 'amount' => 12),
     *             array('type' => 'Shipping', 'currency'=>'USD', 'amount' => 3.49),
     *             array('type' => 'Tax', 'currency'=>'USD', 'amount' => 3.49),
     *         )
     *     )
     * )
     *
     * 
     * @return string
     */
    public function getRefundXMLData($is_array=null)
    {
        if($is_array)
        {
            $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>$this->_merchantID</MerchantIdentifier></Header><MessageType>OrderAdjustment</MessageType>
EOF;
            if (!is_array($this->reqArrList) && empty($this->reqArrList)) {
                throw new \Exception("Bad reqArrList parameter", 1);
            }
            foreach ($this->reqArrList as $key => $value) {
                $xml .= '<Message>';
                $xml .= '<MessageID>'.($key+1).'</MessageID>';
                $xml .= '<OrderAdjustment>';
                //$xml .= '<MerchantOrderID>'.$value['orderid'].'</MerchantOrderID>';
                $xml .= '<AmazonOrderID>'.$value['orderid'].'</AmazonOrderID>';
                $xml .= '<ActionType>'.$value['action_type'].'</ActionType>';

                foreach($value['co'] as $k => $refund_detail)
                {
                    $xml .= '<AdjustedItem>';
                    $xml .= '<AmazonOrderItemCode>'.$k.'</AmazonOrderItemCode>';
                    if ($value['slitemid']) $xml .= '<MerchantAdjustmentItemID>'.$value['slitemid'].'</MerchantAdjustmentItemID>';
                    $xml .= '<AdjustmentReason>'.$refund_detail['reason_code'].'</AdjustmentReason>';
                    $xml .= '<ItemPriceAdjustments>';
                    $xml .= '<Component>';
                    $xml .= '<Type>Principal</Type>';
                    $xml .= '<Amount currency="'.$value['currency'].'">'.$refund_detail['item_price_amount'].'</Amount>';
                    $xml .= '</Component>';
                    if(isset($refund_detail['item_tax_amount']) && !empty($refund_detail['item_tax_amount']))
                    {
                        $xml .= '<Component>';
                        $xml .= '<Type>Tax</Type>';
                        $xml .= '<Amount currency="'.$value['currency'].'">'.$refund_detail['item_tax_amount'].'</Amount>';
                        $xml .= '</Component>';
                    }
                    if(isset($refund_detail['item_shipping_amount']) && !empty($refund_detail['item_shipping_amount']))
                    {
                        $xml .= '<Component>';
                        $xml .= '<Type>Shipping</Type>';
                        $xml .= '<Amount currency="'.$value['currency'].'">'.$refund_detail['item_shipping_amount'].'</Amount>';
                        $xml .= '</Component>';
                    }
                    if(isset($refund_detail['shipping_tax_amount']) && !empty($refund_detail['shipping_tax_amount']))
                    {
                        $xml .= '<Component>';
                        $xml .= '<Type>Shipping Tax</Type>';
                        $xml .= '<Amount currency="'.$value['currency'].'">'.$refund_detail['shipping_tax_amount'].'</Amount>';
                        $xml .= '</Component>';
                    }
                    $xml .= '</ItemPriceAdjustments>';
                    $xml .= '</AdjustedItem>';
                }

                $xml .= '</AdjustedItem>';
                $xml .= '</OrderAdjustment>';
                $xml .= '</Message>';

            }
            $xml .= <<<EOF
</AmazonEnvelope>
EOF;
        }
        else
        {
            $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>$this->_merchantID</MerchantIdentifier></Header><MessageType>OrderAdjustment</MessageType>
EOF;
            if (!is_array($this->reqArrList) && empty($this->reqArrList)) {
                throw new \Exception("Bad reqArrList parameter", 1);
            }
            $i = 1;
            foreach ($this->reqArrList as $value) {
                $xml .= '<Message>';
                $xml .= '<MessageID>'.$i.'</MessageID>';
                $xml .= '<OrderAdjustment>';
                //$xml .= '<MerchantOrderID>'.$value['orderid'].'</MerchantOrderID>';
                $xml .= '<AmazonOrderID>'.$value['orderid'].'</AmazonOrderID>';
                $xml .= '<ActionType>'.$value['action_type'].'</ActionType>';
                //$xml .= '<MerchantOrderItemID>'.$value['itemid'].'</MerchantOrderItemID>';
                /*$xml .= '<AmazonOrderItemCode>'.$value['itemid'].'</AmazonOrderItemCode>';
                if ($value['slitemid']) $xml .= '<MerchantAdjustmentItemID>'.$value['slitemid'].'</MerchantAdjustmentItemID>';
                $xml .= '<AdjustmentReason>'.$value['reason'].'</AdjustmentReason>';
                $xml .= '<ItemPriceAdjustments>';

                foreach ($value['co'] as $unit) {
                    $xml .= '<Component>';
                    $xml .= '<Type>'.$unit['type'].'</Type>';
                    $xml .= '<Amount currency="'.$unit['currency'].'">'.$unit['amount'].'</Amount>';
                    $xml .= '</Component>';
                }*/

                foreach($value['co'] as $k => $refund_detail)
                {
                    $xml .= '<AdjustedItem>';
                    $xml .= '<AmazonOrderItemCode>'.$k.'</AmazonOrderItemCode>';
                    if ($value['slitemid']) $xml .= '<MerchantAdjustmentItemID>'.$value['slitemid'].'</MerchantAdjustmentItemID>';
                    $xml .= '<AdjustmentReason>'.$refund_detail['reason_code'].'</AdjustmentReason>';
                    $xml .= '<ItemPriceAdjustments>';
                    $xml .= '<Component>';
                    $xml .= '<Type>Principal</Type>';
                    $xml .= '<Amount currency="'.$value['currency'].'">'.$refund_detail['item_price_amount'].'</Amount>';
                    $xml .= '</Component>';
                    if(isset($refund_detail['item_tax_amount']) && !empty($refund_detail['item_tax_amount']))
                    {
                        $xml .= '<Component>';
                        $xml .= '<Type>Tax</Type>';
                        $xml .= '<Amount currency="'.$value['currency'].'">'.$refund_detail['item_tax_amount'].'</Amount>';
                        $xml .= '</Component>';
                    }
                    if(isset($refund_detail['item_shipping_amount']) && !empty($refund_detail['item_shipping_amount']))
                    {
                        $xml .= '<Component>';
                        $xml .= '<Type>Shipping</Type>';
                        $xml .= '<Amount currency="'.$value['currency'].'">'.$refund_detail['item_shipping_amount'].'</Amount>';
                        $xml .= '</Component>';
                    }
                    if(isset($refund_detail['shipping_tax_amount']) && !empty($refund_detail['shipping_tax_amount']))
                    {
                        $xml .= '<Component>';
                        $xml .= '<Type>Shipping Tax</Type>';
                        $xml .= '<Amount currency="'.$value['currency'].'">'.$refund_detail['shipping_tax_amount'].'</Amount>';
                        $xml .= '</Component>';
                    }
                    $xml .= '</ItemPriceAdjustments>';
                    $xml .= '</AdjustedItem>';
                }

                $xml .= '</OrderAdjustment>';
                $xml .= '</Message>';

                $i++;
            }
            $xml .= <<<EOF
</AmazonEnvelope>
EOF;
        }
        return $xml;
    }

    /**
     * 获取上传XML数据
     * 
     * @return string
     * 
     */
    public function getXML($is_array=null)
    {
        switch ($this->businesstype) {
            case self::INVENTORY:
                $this->xml = $this->getInventoryXMLData();
                break;

            case self::OFFLINE_PRODUCT:
                $this->xml = $this->getOfflineProductXMLData();
                break;

            case self::ONLINE_PRODUCT:
                break;

            case self::PRICE:
                $this->xml = $this->getPriceXMLData();
                break;

            case self::AVAILABLE_INVENTORY:
                $this->xml = $this->getAvailableInventoryXMLData();
                break;

            case self::NEW_PRODUCT:
                $this->xml = $this->getCatalogProductXMLData();
                break;

            case self::ESTABLISH_PRODUCT:
                $this->xml = $this->getEstablishProductXMLData();
                break;

            case self::SEND_IMAGE:
                $this->xml = $this->getSendImageXMLData();
                break;

            case self::REFUND:
                $this->xml = $this->getRefundXMLData($is_array);
                break;

            default:
                ;
        }
        return trim($this->xml);
    }

    /**
     *
     * @param object $request
     */
    public function setRequest($is_array=null,&$logModel = '')
    {
        $xml = $this->getXML($is_array);

        if(!empty($logModel))
        {
            $logModel->xml = $xml;
            $logModel->save();
        }

        //echo $xml, "\n";exit; #for debug
        $marketplaceIdArray = array('Id' => array($this->_marketplaceID));
        $feedHandle = @fopen('php://temp', 'rw+');
        fwrite($feedHandle, $xml);
        rewind($feedHandle);
        $parameters = array(
            'Merchant'          => $this->_merchantID,
            'MarketplaceIdList' => $marketplaceIdArray,
            'FeedType'          => $this->feedType,
            'FeedContent'       => $feedHandle,
            'PurgeAndReplace'   => false,
            'ContentMd5'        => base64_encode(md5(stream_get_contents($feedHandle), true)),
        );
        rewind($feedHandle);
        $this->request = new \MarketplaceWebService_Model_SubmitFeedRequest($parameters);
        return $this;
    }

    /**
     *
     * 发送报文
     */
    public function sendHttpRequest()
    {
        // TODO: Implement sendHttpRequest() method.
        try {
            $this->response = $this->_service->submitFeed($this->request);
        } catch (MarketplaceWebService_Exception $e) {
            $this->response = $e->getMessage();
            echo $this->response;exit;
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
        $data = '';

        if (!is_object($response)) {
            return $data;
        }

        if (!$response->isSetSubmitFeedResult()){
            return $data;
        }

        if ($response->isSetResponseMetadata()) {
            $responseMetadata = $response->getResponseMetadata();

            if ($responseMetadata->isSetRequestId()) {
                $data['RequestId'] = $responseMetadata->getRequestId();
            }
        }

        $data['ResponseHeaderMetadata'] = $response->getResponseHeaderMetadata();

        $submitFeedResult = $response->getSubmitFeedResult();

        if (!$submitFeedResult->isSetFeedSubmissionInfo()) {
            return $data;
        }

        $feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();

        if ($feedSubmissionInfo->isSetFeedSubmissionId()){
            $data['FeedSubmissionId'] = $feedSubmissionInfo->getFeedSubmissionId();
        }

        if ($feedSubmissionInfo->isSetFeedType()) {
            $data['FeedType'] = $feedSubmissionInfo->getFeedType();
        }

        if ($feedSubmissionInfo->isSetSubmittedDate()) {
            $data['SubmittedDate'] = $feedSubmissionInfo
                ->getSubmittedDate()
                ->format(self::DATE_FORMAT);
        }

        if ($feedSubmissionInfo->isSetFeedProcessingStatus()) {
            $data['FeedProcessingStatus'] = $feedSubmissionInfo
                ->getFeedProcessingStatus();
        }

        if ($feedSubmissionInfo->isSetStartedProcessingDate()) {
            $data['StartedProcessingDate'] = $feedSubmissionInfo
                ->getStartedProcessingDate()
                ->format(self::DATE_FORMAT);
        }

        if ($feedSubmissionInfo->isSetCompletedProcessingDate()) {
            $data['CompletedProcessingDate'] = $feedSubmissionInfo
                ->getCompletedProcessingDate()
                ->format(self::DATE_FORMAT);
        }

        return $data;
    }

    public static function test()
    {
        echo 'hello world';
    }
}