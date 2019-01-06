<?php
namespace app\modules\systems\models;
class ErpSystemApi extends ErpApiAbstract
{
    public $requestUri = '/system/index/method/';
    
    /**
     * @desc 获取订单及相关信息
     * @param unknown $platformCode
     * @param unknown $accountName
     */
    public function getCountries()
    {
        $this->setApiMethod('getCountries')
            ->sendRequest(null, 'get');
        if ($this->isSuccess())
        {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }
    
    /**
     * @desc 登录
     * @param unknown $data
     * @return boolean
     */
    public function login($data)
    {
        $this->setApiMethod('login')
            ->sendRequest($data, 'get');
        if ($this->isSuccess())
        {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }
    /**
     * @desc 通过指定原货币以及目标货币获取对应汇率
     * @param string $base_currency_code 原货币code
     * @param string $target_currency_code 目标货币code
     */
    public function getCurrencyRate($base_currency_code, $target_currency_code)
    {   
        //组装请求参数
        $params = [
            'base_currency_code' => $base_currency_code,
            'target_currency_code' => $target_currency_code,
        ];

        $cacheKey = md5('cache_erp_currency_rate_' . $base_currency_code . '_' . $target_currency_code);
        $cacheNamespace = 'cache_erp_currency_rate_' . $base_currency_code . '_' . $target_currency_code;
        //从缓存获取数据
        if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
            !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
        {
            return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
        }

        //设置请求方法以及请求地址
        $this->setApiMethod('getCurrencyRate')->sendRequest($params, 'get');

        //获取成功,返回数据
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            if (!empty($response) && isset(\Yii::$app->memcache)){
                \Yii::$app->memcache->set($cacheKey, $response->rate, $cacheNamespace,86400);
            }
            return $response->rate;
        }

        //请求失败
        return false;
    }

    /**
     * 一次性获取ebay所有账号信息
     */
    public function getAllEbayAccount()
    {
        $params = [];
        //设置请求方法以及请求地址
        $this->setApiMethod('getAllEbayAccount')->sendRequest($params, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }

    /**
     * 获取指定平台的账号信息
     */
    public function getAccount($platformCode)
    {
        $params = [
            'platformCode' => $platformCode,
        ];
        //设置请求方法以及请求地址
        $this->setApiMethod('getAccount')->sendRequest($params, 'post');
        if ($this->isSuccess()) {
            $response = $this->getResponse();
            return $response;
        }
        return false;
    }
}