<?php

namespace app\modules\services\modules\lazada\components;

use app\modules\accounts\models\LazadaApp;

/**
 * 描述:lazada open platform 2.0接口工具类库
 * Class LazRequestTool
 */
class LazRequestTool
{

    /**
     * 描述:GetOrders
     * @param $accountId
     * @param array $array api请求参数
     * @return mixed|string
     */
    public static function getOrders($accountId, array $array)
    {
        $c = new LazClient($accountId);
        $request = new LazopRequest('/orders/get', 'GET');
        foreach ($array as $k => $v) {
            $request->addApiParam($k, $v);
        }
        $res = $c->execute($request, $c->getToken());
        return $res;
    }

    //20180530 hyr
    public static function getOrder($accountId, array $array)
    {
        $c = new LazClient($accountId);
        $request = new LazopRequest('/order/get', 'GET');
        foreach ($array as $k => $v) {
            $request->addApiParam($k, $v);
        }
        $res = $c->execute($request, $c->getToken());
        return $res;
    }

    /**
     * 描述:Use this API to get the item information of one or more orders.
     * @param $accountId
     * @param array $array 订单号
     * @return mixed|string
     */
    public static function getOrdersGoods($accountId, array $array)
    {
        $c = new LazClient($accountId);
        $request = new LazopRequest('/orders/items/get', 'GET');
        $str = implode(',', $array);
        $request->addApiParam('order_ids', '[' . $str . ']');
        $res = $c->execute($request, $c->getToken());
        return $res;
    }

    /**
     * 描述:GetOrderItems
     * @param $accountId
     * @param int $orderId 单个订单号
     * @return mixed|string
     */
    public static function getOrderItems($accountId, $orderId)
    {
        $c = new LazClient($accountId);
        $request = new LazopRequest('/order/items/get', 'GET');
        $request->addApiParam('order_id', $orderId);
        $res = $c->execute($request, $c->getToken());
        return $res;
    }

    /**
     * 描述:刷新token
     * @param $account
     * @return mixed
     */
    public static function refreshAccessToken($account)
    {
        $app = LazadaApp::findOne($account->app_id);
        if (!empty($app) && !empty($account->api_refresh_token)) {
            $c = new LazopClient('https://api.lazada.com/rest', $app->app_key, $app->app_secret);
            $request = new LazopRequest('/auth/token/refresh');
            $request->addApiParam('refresh_token', $account->api_refresh_token);
            $res = $c->execute($request);
            return $res;
        } else {
            echo '账号(' . $account->seller_name . ')还没绑定过账号,不能自动刷新token' . "<br/>";
            return false;
        }
    }

}
