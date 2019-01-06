<?php

namespace app\modules\services\modules\cdiscount\components;

class cdiscountApi
{

    private $token = null;
    private $xmlHelper;
    private $xmlReader;

    function __construct($token = null)
    {
        $this->xmlHelper = new cdXmlHelper(null);
        $this->xmlReader = new cdXmlReader();
        !empty($token) && $this->token = $token;
    }

    public function refreshToken($username, $password)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, cdConfig::$tokenUrl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . base64_encode($username . ':' . $password)
        ));
        $data = curl_exec($ch);
        curl_close($ch);
        return !empty($data) ? trim(strip_tags($data)) : '';
    }

    public function init($username, $password)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, cdConfig::$tokenUrl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . base64_encode($username . ':' . $password)
        ));
        $data = curl_exec($ch);
        curl_close($ch);
        $this->token = trim(strip_tags($data));
    }

    public function isTokenAvaliable()
    {
        return !empty($this->token);
    }


    public function getResult($action, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, cdConfig::$methodUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept-Encoding: gzip,deflate',
            'Content-Type: text/xml;charset=UTF-8',
            'SOAPAction: http://www.cdiscount.com/IMarketplaceAPIService/' . $action . '',
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $data = curl_exec($ch);
        curl_close($ch);
        if (isset($data[2])) {
            $data = str_replace('i:nil="true"', '', $data);
            $result = $this->xmlReader->fromString($data);
            return !empty($result['s:Body']) ? $result['s:Body'] : false;
        }
        return $data;
    }

    //获取header部分
    private function getHeaderMessage()
    {
        cdConfig::$xmlValues['Security']['TokenId'] = $this->token;
        $result = $this->xmlHelper->generateOpenBaliseWithInline('headerMessage', cdConfig::$xmlAttributes['headerMessage']['attr']);
        if (!empty(cdConfig::$xmlAttributes['headerMessage']['son'])) {
            foreach (cdConfig::$xmlAttributes['headerMessage']['son'] as $value) {
                $this->xmlHelper->setGlobalPrefix(cdConfig::$xmlAttributes[$value]['prefix']);
                if (is_array(cdConfig::$xmlValues[$value])) {
                    $result .= $this->xmlHelper->generateOpenBalise($value);
                    foreach (cdConfig::$xmlValues[$value] as $key => $val) {
                        if (is_array($val)) {
                            $first = current($val);
                            $result .= $this->xmlHelper->generateAutoClosingBalise($key, key($val), $first);
                        } else {
                            $result .= $this->xmlHelper->generateBalise($key, $val);
                        }
                    }
                    $result .= $this->xmlHelper->generateCloseBalise($value);
                } else {
                    $result .= $this->xmlHelper->generateBalise($value, cdConfig::$xmlValues[$value]);
                }
                $this->xmlHelper->setGlobalPrefix(null);
            }
        }
        $result .= $this->xmlHelper->generateCloseBalise('headerMessage');
        return $result;
    }

    //获取部分存在额外内容的部分
    private function getActionContent($action, $data = array())
    {
        $this->xmlHelper->setGlobalPrefix(null);
        if (isset(cdConfig::$xmlAttributes[$action])) {
            $result = $this->xmlHelper->generateOpenBaliseWithInline($action, cdConfig::$xmlAttributes[$action]['attr']);
            if (!empty(cdConfig::$xmlValues[$action])) {
                foreach (cdConfig::$xmlValues[$action] as $key => $val) {
                    if (isset($data[$key])) $val = $data[$key];
                    $result .= $this->xmlHelper->generateBalise($key, $val);
                }
            }
            $result .= $this->xmlHelper->generateCloseBalise($action);
            return $result;
        }
        return false;
    }

    //根据不同action，组装不同的body
    private function getBodyContent($action, $content)
    {
        $this->xmlHelper->setGlobalPrefix(null);
        $result = $this->xmlHelper->generateOpenBaliseWithInline($action, cdConfig::$xmlAttributes['default']['attr']);
        $result .= $content;
        return $result . $this->xmlHelper->generateCloseBalise($action);
    }

    //整理最终的文本，并上传
    private function getBody($content)
    {
        $this->xmlHelper->setGlobalPrefix(cdConfig::$xmlAttributes['Body']['prefix']);
        $result = $this->xmlHelper->generateBalise('Body', $content);
        $this->xmlHelper->setGlobalPrefix(cdConfig::$xmlAttributes['Envelope']['prefix']);
        $envelope = $this->xmlHelper->generateOpenBaliseWithInline('Envelope', cdConfig::$xmlAttributes['Envelope']['attr']);
        $envelope .= $result;
        $envelope .= $this->xmlHelper->generateCloseBalise('Envelope');
        return $envelope;
    }

    public function test()
    {
        $content = $this->getBody($this->getBodyContent('SubmitProductPackage', $this->getHeaderMessage() . $this->getActionContent('productPackageRequest', array(
                'ZipFileFullPath' => 'http://images.yibainetwork.com/cdiscount.zip'
            ))));
        $result = $this->getResult('SubmitProductPackage', $content);
        return $result;
    }

    //上传包裹
    public function uploadPackage($url)
    {
        $content = $this->getBody($this->getBodyContent('SubmitProductPackage', $this->getHeaderMessage() . $this->getActionContent('productPackageRequest', array(
                'ZipFileFullPath' => $url
            ))));
        echo
        $result = $this->getResult('SubmitProductPackage', $content);
        return $result;
    }

    //检查包裹是否成功提交
    public function checkPackage($package_id)
    {
        $this->xmlHelper->setGlobalPrefix(null);
        $body = $this->xmlHelper->generateOpenBaliseWithInline('productPackageFilter', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));
        $body .= $this->xmlHelper->generateOpenBalise('PackageID');
        $body .= $package_id;
        $body .= $this->xmlHelper->generateCloseBalise('PackageID');
        $body .= $this->xmlHelper->generateCloseBalise('productPackageFilter');
        $content = $this->getBody($this->getBodyContent('GetProductPackageSubmissionResult', $this->getHeaderMessage() . $body));
        $result = $this->getResult('GetProductPackageSubmissionResult', $content);
        return $result;
    }

    //校验package中的产品刊登信息
    public function checkPackageContent($package_id)
    {
        $this->xmlHelper->setGlobalPrefix(null);
        $body = $this->xmlHelper->generateOpenBalise('productPackageFilter');
        $body .= $this->xmlHelper->generateOpenBalise('PackageID');
        $body .= $package_id;
        $body .= $this->xmlHelper->generateCloseBalise('PackageID');
        $body .= $this->xmlHelper->generateCloseBalise('productPackageFilter');
        $content = $this->getBody($this->getBodyContent('GetProductPackageProductMatchingFileData', $this->getHeaderMessage() . $body));
        return $this->getResult('GetProductPackageProductMatchingFileData', $content);
    }

    public function sellerInfo()
    {
// 		GetSellerInformation
        $content = $this->getBody($this->getBodyContent('GetSellerInformation', $this->getHeaderMessage()));
        $result = $this->getResult('GetSellerInformation', $content);
        var_dump($result);
    }

    public function getCategory()
    {
        $content = $this->getBody($this->getBodyContent('GetAllowedCategoryTree', $this->getHeaderMessage()));
        $result = $this->getResult('GetAllowedCategoryTree', $content);

        echo '<pre>';
        print_r($result);
    }

// 	获取listing
    public function getProductList($cateCode)
    {
        $info = $this->xmlHelper->generateOpenBaliseWithInline('productFilter', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));
        if (!empty($cateCode)) {
            $info .= $this->xmlHelper->generateOpenBalise('CategoryCode');
            $info .= $cateCode;
            $info .= $this->xmlHelper->generateCloseBalise('CategoryCode');
        }
        $info .= $this->xmlHelper->generateCloseBalise('productFilter');
// 		$info = '<productFilter ><CategoryCode>06010201</CategoryCode></productFilter>';
        $content = $this->getBody($this->getBodyContent('GetProductList ', $this->getHeaderMessage() . $info));
        return $this->getResult('GetProductList ', $content);
    }

    //
    public function getProductListByIdentifier(array $Identifier)
    {
        $idContent = '';
        if (!empty($Identifier)) {
            foreach ($Identifier as $val) {
                $idContent .= $this->xmlHelper->generateBalise('arr:string', $val);
            }
        }
        if (!empty($idContent)) {
            $this->xmlHelper->setGlobalPrefix('cdis:');
            $body = $this->xmlHelper->generateOpenBalise('identifierRequest');
            $body .= $this->xmlHelper->generateBalise('IdentifierType', 'EAN');
            $body .= $this->xmlHelper->generateOpenBalise('ValueList');
            $body .= $idContent;
            $body .= $this->xmlHelper->generateCloseBalise('ValueList');
            $body .= $this->xmlHelper->generateCloseBalise('identifierRequest');
            cdConfig::$xmlAttributes['Envelope']['attr'] = array(
                'xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"',
                'xmlns:cdis="http://www.cdiscount.com"',
                'xmlns:cdis1="http://schemas.datacontract.org/2004/07/Cdiscount.Framework.Core.Communication.Messages"',
                'xmlns:sys="http://schemas.datacontract.org/2004/07/System.Device.Location"',
                'xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays"'
            );
            cdConfig::$xmlAttributes['Envelope']['prefix'] = cdConfig::$xmlAttributes['Body']['prefix'] = cdConfig::$xmlAttributes['Header']['prefix'] = 'soapenv:';
            cdConfig::$xmlAttributes['Context']['prefix'] = cdConfig::$xmlAttributes['Localization']['prefix'] = cdConfig::$xmlAttributes['Security']['prefix'] = cdConfig::$xmlAttributes['Version']['prefix'] = 'cdis1:';
            $content = $this->getBody('<soapenv:Header/>' . $this->getBodyContent('cdis:GetProductListByIdentifier', $this->getHeaderMessage() . $body));
            return $this->getResult('GetProductListByIdentifier', $content);
        }
        return false;
    }

// 	根据listing的在线id，获取offer
    public function getOfferList(array $productId)
    {
        $body = $this->xmlHelper->generateOpenBalise('offerFilter');
        $body .= $this->xmlHelper->generateBalise('OfferPoolId', 1);
        $body .= $this->xmlHelper->generateOpenBalise('SellerProductIdList');
        foreach ($productId as $val) {
            $body .= $this->xmlHelper->generateBalise('arr:string', $val);
        }
        $body .= $this->xmlHelper->generateCloseBalise('SellerProductIdList');
        $body .= $this->xmlHelper->generateCloseBalise('offerFilter');
        array_push(cdConfig::$xmlAttributes['Envelope']['attr'], 'xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays"');
        $content = $this->getBody($this->getBodyContent('GetOfferList', $this->getHeaderMessage() . $body));
        return $this->getResult('GetOfferList', $content);
    }

// 	分页获取offer
    public function getOfferPage($pageNum = 1)
    {
        $body = $this->xmlHelper->generateOpenBalise('offerFilter');
        $body .= $this->xmlHelper->generateBalise('OfferPoolId', 1);
        $body .= $this->xmlHelper->generateBalise('PageNumber', $pageNum);
        $body .= $this->xmlHelper->generateCloseBalise('offerFilter');
        $content = $this->getBody($this->getBodyContent('GetOfferListPaginated', $this->getHeaderMessage() . $body));
        var_dump($content);
        return $this->getResult('GetOfferListPaginated', $content);
    }

    public function getBrand()
    {
        $content = $this->getBody($this->getBodyContent('GetBrandList', $this->getHeaderMessage()));
        $result = $this->getResult('GetBrandList', $content);
        echo '<pre>';
        print_r($result);
    }

    public function GetModelList($code)
    {
        $body = $this->xmlHelper->generateOpenBaliseWithInline('modelFilter ', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));
        $body .= $this->xmlHelper->generateOpenBaliseWithInline('CategoryCodeList ', array(
            'xmlns:a="http://schemas.microsoft.com/2003/10/Serialization/Arrays"'
        ));
        $body .= $this->xmlHelper->generateOpenBalise("a:string");
        $body .= $code;
        $body .= $this->xmlHelper->generateCloseBalise("a:string");
        $body .= $this->xmlHelper->generateCloseBalise("CategoryCodeList");
        $body .= $this->xmlHelper->generateCloseBalise("modelFilter");
        $content = $this->getBody($this->getBodyContent('GetModelList', $this->getHeaderMessage() . $body));
        $result = $this->getResult('GetModelList', $content);
        return $result;
    }

    /**
     * 获取订单列表
     * @param $startTime
     * @param $endTime
     * @return bool|mixed
     */
    public function getOrderList($startTime, $endTime)
    {
        $body = $this->xmlHelper->generateOpenBaliseWithInline('orderFilter', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));
        $body .= $this->xmlHelper->generateBalise('BeginCreationDate', $startTime);
        $body .= $this->xmlHelper->generateBalise('EndCreationDate', $endTime);
        //按修改时间拉取订单
        $body .= $this->xmlHelper->generateBalise('FetchOrderLines', 'true');
        $body .= $this->xmlHelper->generateOpenBalise('States');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'WaitingForShipmentAcceptation');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'Shipped');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'RefusedBySeller');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'PaymentRefused');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'ShipmentRefusedBySeller');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'NonPickedUpByCustomer');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'PickedUp');
// 		$body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'Filled');
        $body .= $this->xmlHelper->generateCloseBalise('States');
        $body .= $this->xmlHelper->generateCloseBalise('orderFilter');
        $content = $this->getBody($this->getBodyContent('GetOrderList', $this->getHeaderMessage() . $body));
        return $this->getResult('GetOrderList', $content);
    }

    /**
     * @return bool|mixed
     * 获取CD账号表现
     */
    public function getSellerIndicators()
    {
        $body = '';
        $content = $this->getBody($this->getBodyContent('GetSellerIndicators', $this->getHeaderMessage() . $body));
        return $this->getResult('GetSellerIndicators', $content);
    }

    /**
     * 获取退款订单
     * @param $startTime
     * @param $endTime
     * @return bool|mixed
     */
    public function getOrderListrefund($startTime, $endTime)
    {
        $body = $this->xmlHelper->generateOpenBaliseWithInline('orderFilter', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));
        $endTime = date('Y-m-d\TH:i:s.00', $endTime);
        $startTime = date('Y-m-d\TH:i:s.00', $startTime);
        $body .= $this->xmlHelper->generateBalise('BeginModificationDate', $startTime);
        $body .= $this->xmlHelper->generateBalise('EndModificationDate', $endTime);
        $body .= $this->xmlHelper->generateBalise('FetchOrderLines', 'true');
        $body .= $this->xmlHelper->generateOpenBalise('States');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'ShipmentRefusedBySeller');
        $body .= $this->xmlHelper->generateCloseBalise('States');
        $body .= $this->xmlHelper->generateCloseBalise('orderFilter');

        $content = $this->getBody($this->getBodyContent('GetOrderList', $this->getHeaderMessage() . $body));
        return $this->getResult('GetOrderList', $content);
    }

    /**
     * 获取订单信息
     */
    public function getOrderInfo($orderId = '')
    {
        $body = $this->xmlHelper->generateOpenBaliseWithInline('orderFilter', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));

        $body .= $this->xmlHelper->generateOpenBaliseWithInline('OrderReferenceList', array(
            'xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays"'
        ));
        $body .= $this->xmlHelper->generateBalise('arr:string', $orderId);
        $body .= $this->xmlHelper->generateCloseBalise('OrderReferenceList');
        $body .= $this->xmlHelper->generateCloseBalise('orderFilter');
        $content = $this->getBody($this->getBodyContent('GetOrderList', $this->getHeaderMessage() . $body));
        return $this->getResult('GetOrderList', $content);
    }

    /**
     * 获取取消订单
     * @param $startTime
     * @param $endTime
     * @return bool|mixed
     */
    public function getOrderListcancel($startTime, $endTime)
    {
        $body = $this->xmlHelper->generateOpenBaliseWithInline('orderFilter', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));
        $endTime = date('Y-m-d\TH:i:s.00', $endTime);
        $startTime = date('Y-m-d\TH:i:s.00', $startTime);
        $body .= $this->xmlHelper->generateBalise('BeginModificationDate', $startTime);
        $body .= $this->xmlHelper->generateBalise('EndModificationDate', $endTime);
        $body .= $this->xmlHelper->generateBalise('FetchOrderLines', 'true');
        $body .= $this->xmlHelper->generateOpenBalise('States');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'CancelledByCustomer');
        $body .= $this->xmlHelper->generateBalise('OrderStateEnum', 'WaitingForShipmentAcceptation');
        $body .= $this->xmlHelper->generateCloseBalise('States');
        $body .= $this->xmlHelper->generateCloseBalise('orderFilter');
        $content = $this->getBody($this->getBodyContent('GetOrderList', $this->getHeaderMessage() . $body));
        return $this->getResult('GetOrderList', $content);
    }

    /**
     * 获取
     * @param $startTime
     * @param $endTime
     * @return bool|mixed
     */
    public function GetOrderClaimList($startTime, $endTime, $state = 'All')
    {
        $body = $this->xmlHelper->generateOpenBaliseWithInline('orderClaimFilter', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));
        $endTime = date('Y-m-d\TH:i:s.00', $endTime);
        $startTime = date('Y-m-d\TH:i:s.00', $startTime);
        $body .= $this->xmlHelper->generateBalise('BeginModificationDate', $startTime);
        $body .= $this->xmlHelper->generateBalise('EndModificationDate', $endTime);
        $body .= $this->xmlHelper->generateOpenBalise('StatusList');
        //讨论的状态，默认有四种(All,Open,Closed,NotProcessed)
        $body .= $this->xmlHelper->generateBalise('DiscussionStateFilter', $state);
        $body .= $this->xmlHelper->generateCloseBalise('StatusList');
        $body .= $this->xmlHelper->generateCloseBalise('orderClaimFilter');
        $content = $this->getBody($this->getBodyContent('GetOrderClaimList', $this->getHeaderMessage() . $body));
        return $this->getResult('GetOrderClaimList', $content);
    }

    /**
     * @param $startTime
     * @param $endTime
     * @return bool|mixed
     */
    public function GetOfferQuestionList($startTime, $endTime, $state = 'All')
    {
        $body = $this->xmlHelper->generateOpenBaliseWithInline('offerQuestionFilter', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));
        $endTime = date('Y-m-d\TH:i:s.00', $endTime);
        $startTime = date('Y-m-d\TH:i:s.00', $startTime);
        $body .= $this->xmlHelper->generateBalise('BeginModificationDate', $startTime);
        $body .= $this->xmlHelper->generateBalise('EndModificationDate', $endTime);
        $body .= $this->xmlHelper->generateOpenBalise('StatusList');
        //讨论的状态，默认有四种(All,Open,Closed,NotProcessed)
        $body .= $this->xmlHelper->generateBalise('DiscussionStateFilter', $state);
        $body .= $this->xmlHelper->generateCloseBalise('StatusList');
        $body .= $this->xmlHelper->generateCloseBalise('offerQuestionFilter');
        $content = $this->getBody($this->getBodyContent('GetOfferQuestionList', $this->getHeaderMessage() . $body));
        return $this->getResult('GetOfferQuestionList', $content);
    }

    public function GetOrderQuestionList($startTime, $endTime, $state = 'All')
    {
        $body = $this->xmlHelper->generateOpenBaliseWithInline('orderQuestionFilter', array(
            'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
        ));
        $endTime = date('Y-m-d\TH:i:s.00', $endTime);
        $startTime = date('Y-m-d\TH:i:s.00', $startTime);
        $body .= $this->xmlHelper->generateBalise('BeginModificationDate', $startTime);
        $body .= $this->xmlHelper->generateBalise('EndModificationDate', $endTime);
        $body .= $this->xmlHelper->generateOpenBalise('StatusList');
        //讨论的状态，默认有四种(All,Open,Closed,NotProcessed)
        $body .= $this->xmlHelper->generateBalise('DiscussionStateFilter', $state);
        $body .= $this->xmlHelper->generateCloseBalise('StatusList');
        $body .= $this->xmlHelper->generateCloseBalise('orderQuestionFilter');
        $content = $this->getBody($this->getBodyContent('GetOrderQuestionList', $this->getHeaderMessage() . $body));
        return $this->getResult('GetOrderQuestionList', $content);
    }

    /**
     * 获取讨论邮箱列表
     */
    public function GetDiscussionMailList($discussionIds = [])
    {
        if (empty($discussionIds)) {
            return false;
        }
        $body = $this->xmlHelper->generateOpenBaliseWithInline('request', array(
            'xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays"'
        ));

        $body .= $this->xmlHelper->generateOpenBalise('DiscussionIds');
        foreach ($discussionIds as $discussionId) {
            $body .= $this->xmlHelper->generateBalise('arr:long', $discussionId);
        }
        $body .= $this->xmlHelper->generateCloseBalise('DiscussionIds');
        $body .= $this->xmlHelper->generateCloseBalise('request');
        $content = $this->getBody($this->getBodyContent('GetDiscussionMailList', $this->getHeaderMessage() . $body));
        return $this->getResult('GetDiscussionMailList', $content);
    }

    /**
     * 关闭讨论
     */
    public function CloseDiscussionList($discussionIds = [])
    {
        if (empty($discussionIds)) {
            return false;
        }

        $body = $this->xmlHelper->generateOpenBaliseWithInline('closeDiscussionRequest', array(
            'xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays"'
        ));
        $body .= $this->xmlHelper->generateOpenBalise('DiscussionIds');
        foreach ($discussionIds as $discussionId) {
            $body .= $this->xmlHelper->generateBalise('arr:long', $discussionId);
        }
        $body .= $this->xmlHelper->generateCloseBalise('DiscussionIds');
        $body .= $this->xmlHelper->generateCloseBalise('closeDiscussionRequest');
        $content = $this->getBody($this->getBodyContent('CloseDiscussionList', $this->getHeaderMessage() . $body));
        return $this->getResult('CloseDiscussionList', $content);
    }

    /**
     * 创建退款
     * @param $orderId 订单ID
     * @param $amount 金额
     * @param $motiveId 退款原因
     * 131 = Compensation on missing stock
     * 132 = Product / Accessory delivered damaged or missing
     * 133 = Error of reference, color, size
     * 134 = Fees unduly charged to the customer
     * 135 = Late delivery
     * 136 = Product return fees
     * 137 = Shipping fees
     * 138 = Warranty period or rights of with drawal passed
     * 139 = Others
     */
    public function CreateRefund($orderId, $amount, $motiveId = 139)
    {
        $body = $this->xmlHelper->generateOpenBalise('request');
        $body .= $this->xmlHelper->generateOpenBalise('CommercialGestureList');
        $body .= $this->xmlHelper->generateOpenBalise('RefundInformation');
        $body .= $this->xmlHelper->generateBalise('Amount', $amount);
        $body .= $this->xmlHelper->generateBalise('MotiveId', $motiveId);
        $body .= $this->xmlHelper->generateCloseBalise('RefundInformation');
        $body .= $this->xmlHelper->generateCloseBalise('CommercialGestureList');
        $body .= $this->xmlHelper->generateBalise('OrderNumber', $orderId);
        $body .= $this->xmlHelper->generateCloseBalise('request');
        $content = $this->getBody($this->getBodyContent('CreateRefundVoucher', $this->getHeaderMessage() . $body));
        return $this->getResult('CreateRefundVoucher', $content);
    }
}