<?php

namespace app\modules\services\modules\lazada\components;

use app\modules\accounts\models\LazadaAccount;
use app\modules\accounts\models\LazadaApp;

class LazClient extends LazopClient
{
    private $site = [
        "MY" => "https://api.lazada.com.my/rest",
        "SG" => "https://api.lazada.sg/rest",
        "TH" => "https://api.lazada.co.th/rest",
        "ID" => "https://api.lazada.co.id/rest",
        "VN" => "https://api.lazada.vn/rest",
        "PH" => "https://api.lazada.com.ph/rest"
    ];

    private $token = null;

    /**
     * LazClient constructor.
     * @param $accountId
     */
    public function __construct($accountId)
    {
        $account = LazadaAccount::findOne($accountId);
        if (!empty($account)) {
            $app = LazadaApp::findOne($account->app_id);//
            if (!empty($app)) {
                $url         = $this->site[strtoupper($account->country_code)];
                $appkey      = $app->app_key;
                $secretKey   = $app->app_secret;
                $this->token = $this->setToken($account);
                parent::__construct($url, $appkey, $secretKey);
            } else {
                die('暂未绑定账号');
            }
        } else {
            die('没有对应的账号');
        }
    }

    /**
     * 描述:自动获取token
     * @param $account
     * @return mixed
     */
    public function setToken($account)
    {
        if (time() > $account->expires_time) {
            if (empty($account->api_refresh_token)) {
                echo '账号(' . $account->seller_name . ')还没绑定过账号,不能自动刷新token';
                return false;
            }
            $res    = LazRequestTool::refreshAccessToken($account);
            $result = json_decode($res);
            if ($result->country != 'cb') {
                $account->api_token    = '';
                $account->expires_time = 0;
                $account->api_code     = '';
                $account->update();
                echo '账号(' . $account->seller_name . ')绑定错误,站点应该绑定国家是cb' . "当前绑定站点国家是" . $result->country . "请重新打开界面获取code绑定<br/>";
                return false;
            }
            if (isset($result->access_token)) {
                $account->api_refresh_token = $result->refresh_token;
                $account->api_token         = $result->access_token;
                $account->expires_time      = time() + (int)$result->expires_in;
                $account->update();
                return $result->access_token;
            } else {
                echo '接口出错了';
                return false;
            }
        } else {
            return $account->api_token;
        }
    }

    /**
     * 描述:获取token
     * @return mixed|null
     */
    public function getToken()
    {
        return $this->token;
    }
}