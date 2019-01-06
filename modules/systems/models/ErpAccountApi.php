<?php
namespace app\modules\systems\models;
class ErpAccountApi extends ErpApiAbstract
{
    public $requestUri = '/account/index/method/';
    
    public function getAccount($platformCode, $accountName)
    {
        $params = ['platformCode' => $platformCode, 'accountName' => $accountName]; 
        $erpAccountApi = new self();
        $erpAccountApi->setApiMethod('getAccount')
            ->sendRequest($params, 'get');
    }
    
    public function getPlatformAccounts($platformCode)
    {
        $params = ['platformCode' => $platformCode];
        $this->setApiMethod('getPlatformAccounts')
            ->sendRequest($params, 'get');

        if ($this->isSuccess())
        {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }
}