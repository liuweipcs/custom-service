<?php
namespace app\modules\systems\models;
class ErpProductApi extends ErpApiAbstract
{
    public $requestUri = '/product/index/method/';

    /**
     * @desc 根据sku获取产品信息
     * @param unknown $data
     * @return boolean
     */
    public function getProductData($data)
    {
        $this->setApiMethod('getProductData')
            ->sendRequest($data, 'get');
        if ($this->isSuccess())
        {
            $response = $this->getResponse();
//            var_dump($response);exit;
            return $response;
        }
        return false;
    }

    /**
     * @desc 根据item_id获取paypal交易账号
     * @param unknown $data
     * @return boolean
     */
    public function getProductPaypal($data)
    {
        $this->setApiMethod('getPaypalEmailAddressByItemId')
            ->sendRequest($data, 'get');
        if ($this->isSuccess())
        {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 根据多个item_id，获取多个paypal交易账号
     * @param $data
     * @return bool|null
     */
    public function getProductPaypals($data)
    {
        $this->setApiMethod('getPaypalEmailAddressByItemIds')
            ->sendRequest($data, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }
}