<?php
namespace app\modules\services\modules\joom\models;

use app\modules\accounts\models\Account;

class JoomApi
{
    const BASE_URL = 'https://api-merchant.joom.com/api/v2';

    /**
     * 刷新access_token
     * @param $accountId 客服系统账号ID
     */
    public static function refreshToken($accountId)
    {
        $account = Account::findOne($accountId);
        if (empty($account)) {
            return false;
        }

        $erpAccountInfo = JoomAccount::findOne($account->old_account_id);
        if (empty($erpAccountInfo)) {
            return false;
        }

        $url = '/oauth/refresh_token';
        $data = [
            'client_id' => $erpAccountInfo->client_id,
            'client_secret' => $erpAccountInfo->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $erpAccountInfo->refresh_token,
        ];
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ];
        $result = self::request($url, $data, 'POST', $headers);
        return !empty($result['data']) ? $result['data']['access_token'] : '';
    }

    /**
     * 返回自$since时间以来，更改状态的所有订单
     */
    public static function multiGet($accessToken, $since, $start = 0, $limit = 100)
    {
        $url = '/order/multi-get';
        $data = [
            'access_token' => $accessToken,
            'since' => $since,
            'start' => $start,
            'limit' => $limit,
        ];
        $result = self::request($url, $data, 'GET');
        return !empty($result['data']) ? $result['data'] : [];
    }

    /**
     * 请求方法
     */
    public static function request($url, $data = [], $type = 'POST', $headers = [])
    {
        $url = self::BASE_URL . $url;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        if ($type == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            $url = rtrim($url, '?') . '?' . http_build_query($data);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_URL, $url);
        $data = curl_exec($curl);
        if ($data === false) {
            echo curl_errno($curl) . ':' . curl_error($curl);
            return false;
        }
        curl_close($curl);
        $data = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
        if ($data === false) {
            echo 'json_decode() failure';
            return false;
        }
        return $data;
    }
}