<?php

namespace wish\components;

use app\modules\accounts\models\Account;
use wish\models\WishAccount;
use Wish\WishAuth;
use Wish\WishClient;

/**
 * 新wish接口类
 */
class MerchantWishApi
{
    //账户ID(注意这里是客服系统的)
    public $accountId = 0;
    //应用ID
    public $clientId = '';
    //应用密码
    public $clientSecret = '';
    //访问token
    public $accessToken = '';
    //刷新token
    public $refreshToken = '';

    public $client = null;

    /**
     * 构造函数
     * @param $accountId 账户ID(注意这里是客服系统的)
     */
    public function __construct($accountId)
    {
        $this->accountId = $accountId;
        $this->init();
    }

    /**
     * 初始化函数
     */
    protected function init()
    {
        if (empty($this->accountId)) {
            return false;
        }

        $accountInfo = Account::findOne($this->accountId);
        if (empty($accountInfo)) {
            return false;
        }

        $erpAccountInfo = WishAccount::findOne(['wish_id' => $accountInfo['old_account_id']]);
        if (empty($erpAccountInfo)) {
            return false;
        }

        $this->clientId = $erpAccountInfo->client_id;
        $this->clientSecret = $erpAccountInfo->client_secret;
        $this->accessToken = $erpAccountInfo->access_token;
        $this->refreshToken = $erpAccountInfo->refresh_token;
        $this->client = new WishClient($this->accessToken, 'prod');
    }

    /**
     * 重新获取access token
     */
    public function getAccessToken()
    {
        $auth = new WishAuth($this->clientId, $this->clientSecret, 'prod');
        $response = $auth->refreshToken($this->refreshToken);

        $this->accessToken = $response->getData()->access_token;
        return $this->accessToken;
    }

    /**
     * 获取所有未查看的通知列表
     */
    public function fetchAllUnviewed()
    {
        if (empty($this->client)) {
            return false;
        }
        $data = $this->client->getAllNotifications();
        return json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
    }

    /**
     * 获取未查看的通知列表
     */
    public function fetchUnviewed($start = 0, $limit = 50)
    {
        if (empty($this->client)) {
            return false;
        }
        $data = $this->client->getNotifications($start, $limit);
        return json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
    }

    /**
     * 返回未查看的通知数量
     */
    public function getUnviewedCount()
    {
        if (empty($this->client)) {
            return false;
        }
        $data = $this->client->getUnviewedNotiCount();
        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        return !empty($data['count']) ? $data['count'] : 0;
    }

    /**
     * 刚通知标记为已查看
     * @param $id 通知ID
     */
    public function markAsViewed($id)
    {
        if (empty($this->client)) {
            return false;
        }
        $data = $this->client->markNotificationAsViewed($id);
        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        return !empty($data['success']) ? true : false;
    }
}