<?php

namespace app\modules\services\modules\ebay\models;

class EbayOAuth
{
    const MODE = 'pro';
    const PRO_HOST_URL = 'https://api.ebay.com/identity/v1/oauth2/token';
    const SAN_HOST_URL = 'https://api.sandbox.ebay.com/identity/v1/oauth2/token';

    /**
     * 通过
     */
    public static function flushAccessToken($refreshToken)
    {
        if (self::MODE == 'pro') {
            $url = self::PRO_HOST_URL;
        } else if (self::MODE == 'san') {
            $url = self::SAN_HOST_URL;
        }

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => implode(' ', self::getScope()),
        ];

        $result = self::request($url, $data, self::getAuthHeader());
        return !empty($result['access_token']) ? $result['access_token'] : '';
    }

    public static function request($url, $data, $headers)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
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

    public static function getAuthHeader()
    {
        $eBayKey = self::getEbayKey();
        $baseCode = base64_encode($eBayKey['appid'] . ':' . $eBayKey['secretid']);
        $header[] = 'Authorization:Basic ' . $baseCode;
        $header[] = 'Content-Type:application/x-www-form-urlencoded';
        $header[] = 'Accept:application/json';
        return $header;
    }

    public static function getEbayKey()
    {
        if (self::MODE == 'pro') {
            return [
                'appid' => 'VincentW-YiBaiNet-PRD-4132041a0-92f00266',
                'secretid' => 'PRD-132041a00691-748a-4548-87cd-adfc',
                'runName' => 'Vincent_Wen-VincentW-YiBaiN-wnmgsyu',
            ];
        } else if (self::MODE == 'san') {
            return [
                'appid' => 'yuanbob-yuandeve-SBX-590ed7b6b-d235e8c2',
                'secretid' => 'SBX-90ed7b6b5e65-0447-4003-98c3-e43c',
                'runName' => 'yuan_bob-yuanbob-yuandev-kbxrfd',
            ];
        }
    }

    public static function getScope()
    {
        return [
            'https://api.ebay.com/oauth/api_scope/sell.account',
            'https://api.ebay.com/oauth/api_scope/sell.analytics.readonly',
            'https://api.ebay.com/oauth/api_scope/sell.inventory',
            'https://api.ebay.com/oauth/api_scope/sell.marketing.readonly',
            'https://api.ebay.com/oauth/api_scope/sell.marketing',
        ];
    }
}