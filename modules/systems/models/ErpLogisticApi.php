<?php
namespace app\modules\systems\models;
class ErpLogisticApi extends ErpApiAbstract
{
    public $requestUri = '/logistic/index/method/';
    
    /**
     * @desc 获取订单及相关信息
     * @param unknown $platformCode
     * @param unknown $accountName
     */
    public function getWarehouseLogistics($warehouseId)
    {
        $params = ['warehouseId' => $warehouseId];
        $this->setApiMethod('getWarehouseLogistics')
            ->sendRequest($params, 'get');
        if ($this->isSuccess())
        {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取所有邮寄
     * @param unknown $platformCode
     * @param unknown $accountName
     */
    public function getAllLogistics()
    {
        $params = [];
        $this->setApiMethod('getAllLogistics')
            ->sendRequest($params, 'get');
        if ($this->isSuccess())
        {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取所有的物流，包括停止使用的
     */
    public function getAllStatusLogistics()
    {
        $params = [];
        $this->setApiMethod('getAllStatusLogistics')
            ->sendRequest($params, 'get');
        if ($this->isSuccess())
        {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * @desc 获取所有邮寄
     * @param unknown $platformCode
     * @param unknown $accountName
     */
    public function getBuyerOptionLogistics($platform_code)
    {
        $params = ['platform_code'=>$platform_code];
        $this->setApiMethod('getBuyerOptionLogistics')
            ->sendRequest($params, 'get');
        if ($this->isSuccess())
        {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }
}