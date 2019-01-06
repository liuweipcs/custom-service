<?php

namespace app\modules\services\modules\amazon\components;

class Account
{
	/**
	 * ERP api
	 */
	//const URL = 'http://erp.cc/services/api/account/index/';
    const URL = 'test.erp.com/services/api/account/index/';
	/**
	 * get amazon account information
	 * 
	 * @return object|false
	 */
	public static function getAccount($accountName)
	{
		$cachekey = sprintf('=Amazon:%s:mws=', $accountName);
        //从缓存获取数据
        $cacheKey = md5($cachekey);
        $cacheNamespace = 'cache_erp_account_name' . $accountName;
        //从缓存获取数据
//        if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
//            !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
//        {
//            return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
//        }
        $api_config = include \Yii::getAlias('@app') . '/config/erp_api.php';

		$params = [
			'method' => 'getAccount',
			'sercetKey' => $api_config['token'],
			'accountName' => $accountName,
			'platformCode' => 'AMAZON',
		];
        
		//$requestUrl = self::URL . '?'. http_build_query($params);

		$requestUrl = $api_config['baseUrl'] . '/account/index/'. '?'. http_build_query($params);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $requestUrl);

		$body  = curl_exec($ch);
		$info  = curl_getinfo($ch);
		$errno = curl_errno($ch);

		$object = json_decode($body);
    
		if ($object === NULL || $object->ack == false) return false;

//        if (!empty($response) && isset(\Yii::$app->memcache)){
//            \Yii::$app->memcache->set($cacheKey, $response->rate, $object->account);
//        }
		return $object->account;
	}

    public static function getAccountByOldId($oldAccountId)
    {
        $cachekey = sprintf('=Amazon:%s:mws=', $oldAccountId);
        //从缓存获取数据
        $cacheKey = md5($cachekey);
        $cacheNamespace = 'cache_erp_account_name' . $oldAccountId;
        //从缓存获取数据
//        if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
//            !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
//        {
//            return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
//        }
        $api_config = include \Yii::getAlias('@app') . '/config/erp_api.php';

        $params = [
            'method' => 'getAccountById',
            'sercetKey' => $api_config['token'],
            'oldAccountId' => $oldAccountId,
            'platformCode' => 'AMAZON',
        ];

        //$requestUrl = self::URL . '?'. http_build_query($params);

        $requestUrl = $api_config['baseUrl'] . '/account/index/'. '?'. http_build_query($params);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $requestUrl);

        $body  = curl_exec($ch);
        $info  = curl_getinfo($ch);
        $errno = curl_errno($ch);

        $object = json_decode($body);

        if ($object === NULL || $object->ack == false) return false;

//        if (!empty($response) && isset(\Yii::$app->memcache)){
//            \Yii::$app->memcache->set($cacheKey, $response->rate, $object->account);
//        }
        return $object->account[0];
    }
}