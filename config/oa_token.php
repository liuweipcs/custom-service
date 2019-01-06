<?php

use app\config\Configuration;

switch (YII_ENV) {
    case Configuration::APP_EVN_PRO;
        return array(
            0 => [
                'appid'      => 4,
                'api_server' => 'http://.yibainetwork.com/services/account_api',
                'api_key'    => 'api_key',
                'api_secret' => 'o3NIjDTmMUYC'
            ],

        );
        break;
    case Configuration::APP_EVN_TEST;
        return array(
            0 => [
                'appid'      => 4,
                'api_server' => 'http://.yibainetwork.com/services/account_api',
                'api_key'    => '',
                'api_secret' => 'o3NIjDTmMUYC'
            ],
        );
        break;
    case Configuration::APP_EVN_DEV;
        return array(
            0 => [
                'appid'      => 4,
                'api_server' => 'http://.yibainetwork.com/services/account_api',
                'api_key'    => 'api_key',
                'api_secret' => 'o3NIjDTmMUYC'
            ],
        );
        break;
}
?>