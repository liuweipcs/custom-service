<?php

namespace app\modules\services\modules\jumia\components;

final class JumiaApi
{
    private $_version = '1.0';
    private $_signature;
    private $_user;
    private $_country_code;
    private $_url;
    private $normalAttr = array(
        'name', 'name_ms', 'model', 'brand', 'primary_category', 'categories', 'product_measures', 'product_weight', 'package_height', 'package_length', 'package_width', 'package_weight',
        'published_date', 'special_price', 'special_from_date', 'special_to_date', 'price', 'tax_class', 'short_description', 'description', 'description_ms', 'package_content',
        'parent_sku', 'quantity', 'warranty_type', 'warranty', 'product_warranty', 'product_warranty_en', 'TaxClass', 'ShipmentType', 'browse_nodes', 'variation',
        'barcode_ean', 'sku_supplier_source', 'SellerSku', '__images__', 'description_en', 'short_description_en', 'name_en', 'manufacturer_txt',
    );

    function __construct($email, $token, $country_code)
    {
        $this->_user = $email;
        $this->_signature = $token;
        $this->_country_code = $country_code;
        $this->_url = $this->getCountryUrl($country_code);
    }

    private function getCountryUrl($country_code)
    {
        $urlData = array(
            'NG' => 'https://sellercenter-api.jumia.com.ng',
            'CI' => 'https://sellercenter-api.jumia.ci',
            'MA' => 'https://sellercenter-api.jumia.ma',
            'KE' => 'https://sellercenter-api.jumia.co.ke',
            'EG' => 'https://sellercenter-api.jumia.com.eg',
        );
        return isset($urlData[$country_code]) ? $urlData[$country_code] : '';
    }

    private function getUrl($action, $queryArray = array())
    {
        $dateTime = new \DateTime();
        $array = array(
            'UserID' => $this->_user,
            'Version' => $this->_version,
            'Action' => $action,
            'Format' => 'JSON',
            'Timestamp' => $dateTime->format(\DateTime::ISO8601)
        );
        if (!empty($queryArray)) {
            foreach ($queryArray as $key => $val) {
                if (is_array($val)) {
                    $queryArray[$key] = json_encode($val);
                }
            }
        }
        $data = array_merge($array, $queryArray);
        ksort($data);
        $concatenated = http_build_query($data);
        $data['Signature'] = rawurlencode(hash_hmac('sha256', $concatenated, $this->_signature, false));
        $urlData = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        return $this->_url . '?' . $urlData;
    }

    protected function getResult($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    protected function postResult($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }


    /**
     * 描述:获取订单基本信息
     * @param array $conditions
     */
    public function getOrders(array $conditions)
    {
        return $this->getResult($this->getUrl('GetOrders', $conditions));
    }


    /**
     * 描述:获取订单
     * @param array $oIds
     * @return mixed
     */
    public function getOrdersGoods(array $oIds)
    {
        $url = $this->getUrl('GetMultipleOrderItems', array(
            'OrderIdList' => $oIds
        ));
        return $this->getResult($url);
    }

    //获取分类私有属性
    public function getCategoryAttrList($categoryId, $site)
    {
        $attr = $this->getCategoryAttr($categoryId, $site);
        $attr = $attr->Attribute;
        if (empty($attr)) return false;
        foreach ($attr as $key => $val) {
            if (in_array($val->Name, $this->normalAttr)) {
                unset($attr[$key]);
            }
        }
        return $attr;
    }

    public function getProducts($sellerSku = array())
    {
        $url = $this->getUrl('GetProducts', $sellerSku);
        return $this->getResult($url);
    }
}