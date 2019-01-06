<?php
namespace app\modules\systems\models;
class ErpWarehouseApi extends ErpApiAbstract
{
    public $requestUri = '/warehouse/index/method/';
    
    /**
     * @desc 获取订单及相关信息
     * @param unknown $platformCode
     * @param unknown $accountName
     */
    public function getWarehouses()
    {
        $this->setApiMethod('getWarehouses')
            ->sendRequest(null, 'get');
        if ($this->isSuccess())
        {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }
}