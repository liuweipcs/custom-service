<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\ErpSystemApi;
use app\modules\accounts\models\EbayCaseRefund;
use app\modules\accounts\models\AliexpressAccount;
use yii\db\Query;

/**
 * 更新平台账号信息计划任务
 * Class AccountController
 * @package app\commands
 */
class AccountController extends Controller
{
    //获取ebay新token的请求地址(刊登系统那边提供)
    //http://47.106.161.131/services/ebay/ebayusertokens/gettokens/[account_id/id]
    //const EBAY_NEW_TOKEN_URL = 'http://47.106.161.131/services/ebay/ebayusertokens/gettokens';
    const EBAY_NEW_TOKEN_URL = 'http://crmtokenebay.yibainetwork.com/services/ebay/ebayusertokens/gettokens';

    //字段映射，注意ERP系统里的账号表字段名与客服系统中账号表字段名不一致，需单独指定，一一对应。
    private $map = [
        Platform::PLATFORM_CODE_EB => [
            'KF' => ['account_name', 'account_short_name', 'email', 'user_token', 'user_token_v2', 'user_token_endtime_v2', 'refresh_time_v2'],
            'ERP' => ['user_name', 'short_name', 'email', 'user_token', 'user_token_v2', 'user_token_endtime_v2', 'refresh_time_v2'],
        ],
        Platform::PLATFORM_CODE_WISH => [
            'KF' => ['account_name', 'account_short_name', 'user_token'],
            'ERP' => ['account_name', 'short_name', 'access_token'],
        ],
        Platform::PLATFORM_CODE_AMAZON => [
            'KF' => ['account_name', 'account_short_name', 'site_code', 'email'],
            'ERP' => ['account_name', 'short_name', 'site', 'amzsite_email'],
        ],
        Platform::PLATFORM_CODE_WALMART => [
            'KF' => ['account_name', 'account_short_name'],
            'ERP' => ['account_name', 'short_name'],
        ],
        Platform::PLATFORM_CODE_ALI => [
            'KF' => ['account_name', 'account_short_name', 'email', 'access_token', 'refresh_token', 'seller_id'],
            'ERP' => ['account', 'short_name', 'email', 'access_token', 'refresh_token', 'buyer_login_id'],
        ],
        Platform::PLATFORM_CODE_MALL => [
            'KF' => ['account_name', 'account_short_name', 'access_token', 'refresh_token'],
            'ERP' => ['user_name', 'short_name', 'access_token', 'refresh_token'],
        ],
        Platform::PLATFORM_CODE_SHOPEE => [
            'KF' => ['account_name', 'account_short_name', 'shop_id', 'partner_id', 'secret_key', 'country_code'],
            'ERP' => ['seller_name', 'short_name', 'shop_id', 'partner_id', 'secret_key', 'country_code'],
        ],
        Platform::PLATFORM_CODE_JOOM => [
            'KF' => ['account_name', 'account_short_name', 'access_token', 'refresh_token'],
            'ERP' => ['account_name', 'short_name', 'access_token', 'refresh_token'],
        ],
        Platform::PLATFORM_CODE_CDISCOUNT => [
            'KF' => ['account_name', 'account_short_name', 'api_name', 'access_token', 'refresh_token'],
            'ERP' => ['seller_name', 'short_name', 'email', 'token', 'platform_token'],
        ],
        Platform::PLATFORM_CODE_JUM => [
            'KF' => ['account_name', 'account_short_name', 'email', 'access_token', 'country_code'],
            'ERP' => ['seller_name', 'short_name', 'email', 'token', 'country_code'],
        ],
        Platform::PLATFORM_CODE_LAZADA => [
            'KF' => ['account_name', 'account_short_name', 'email', 'access_token', 'site', 'country_code'],
            'ERP' => ['seller_name', 'short_name', 'email', 'token', 'website', 'country_code'],
        ],
    ];

    /**
     * 同步ERP系统中{{%ebay_account}}表数据到客服系统{{%account}}中
     */
    public function actionSyncebayaccount()
    {

        $erpSystemApi = new ErpSystemApi();
        $result = $erpSystemApi->getAllEbayAccount();
        if (empty($result)) {
            return false;
        }
        $account = json_decode(json_encode($result->account), true);

        if (!empty($account)) {
            foreach ($account as $item) {
                $data = Account::find()->where(['platform_code' => Platform::PLATFORM_CODE_EB, 'old_account_id' => $item['id']])->one();

                if (empty($data)) {
                    //如果不存在，则添加
                    $add = new Account();
                    $add->platform_code = Platform::PLATFORM_CODE_EB;
                    $add->account_name = $item['user_name'];
                    $add->account_short_name = $item['short_name'];
                    $add->status = 1;
                    $add->old_account_id = $item['id'];
                    $add->site_code = '';
                    $add->email = $item['email'];
                    $add->create_by = '';
                    $add->create_time = date('Y-m-d H:i:s', time());
                    $add->modify_by = '';
                    $add->modify_time = date('Y-m-d H:i:s', time());
                    $add->user_token = $item['user_token'];
                    $add->site = '';
                    //添加账号信息
                    if ($add->save()) {
                        echo "old_account_id : {$item['id']} add success\n";
                    } else {
                        echo "old_account_id : {$item['id']} add error\n";
                    }
                } else {
                    //否则，更新原数据
                    $data->account_name = $item['user_name'];
                    $data->account_short_name = $item['short_name'];
                    $data->status = 1;
                    $data->email = $item['email'];
                    $data->modify_time = date('Y-m-d H:i:s', time());
                    $data->user_token = $item['user_token'];
                    //更新账号信息
                    if ($data->save()) {
                        echo "EB old_account_id : {$item['id']} update success\n";
                    } else {
                        echo "EB old_account_id : {$item['id']} update error\n";
                    }
                }
            }
        }
    }

    /**
     * 同步ERP账号信息到客服系统，可指定需要同步的账号信息
     * 例子：
     * yii account/syncaccount EB
     * yii account/syncaccount WISH
     * yii account/syncaccount ALI
     */
    public function actionSyncaccount($platformCode)
    {
        //判断平台code
        if (empty($platformCode)) {
            return false;
        }
        //转换成大写
        $platformCode = strtoupper($platformCode);

        //获取平台的账号信息
        $account = $this->getAccount($platformCode);

        if (!empty($account) && array_key_exists($platformCode, $this->map)) {

            //如果是ebay平台，则需要从刊登系统那边获取新token
            if ($platformCode == Platform::PLATFORM_CODE_EB) {
                $newTokens = $this->request(self::EBAY_NEW_TOKEN_URL);
                if (!empty($newTokens)) {
                    $newTokens = json_decode($newTokens, true, 512, JSON_BIGINT_AS_STRING);
                    if (!empty($newTokens) && is_array($newTokens)) {
                        $tmp = [];
                        foreach ($newTokens as $newToken) {
                            $tmp[$newToken['account_id']] = $newToken;
                        }
                        $newTokens = $tmp;
                    }
                }
            }

            foreach ($account as $item) {
                $data = Account::find()->where(['platform_code' => $platformCode, 'old_account_id' => $item['id']])->one();

                if (empty($data)) {
                    //如果不存在，则添加
                    $add = new Account();
                    $add->platform_code = $platformCode;
                    $add->status = 1;
                    $add->old_account_id = $item['id'];
                    $add->create_by = 'system';
                    $add->create_time = date('Y-m-d H:i:s', time());
                    $add->modify_by = 'system';
                    $add->modify_time = date('Y-m-d H:i:s', time());

                    //循环把ERP账号信息赋值给客服账号信息
                    if (!empty($this->map[$platformCode]['KF'])) {
                        foreach ($this->map[$platformCode]['KF'] as $key => $field) {
                            $add->{$field} = $item[$this->map[$platformCode]['ERP'][$key]];
                        }
                    }

                    //eBay平台的新token需要单独设置
                    if ($platformCode == Platform::PLATFORM_CODE_EB) {
                        if (!empty($newTokens) && array_key_exists($item['id'], $newTokens)) {
                            $add->refresh_token = $newTokens[$item['id']]['refresh_token'];
                        }
                    }

                    //添加账号信息
                    if ($add->save()) {

                        //如果是eBay平台 保存升级自动退款配置 add by allen <2018-07-31>
                        if ($platformCode == Platform::PLATFORM_CODE_EB) {
                            $caseRefund = new EbayCaseRefund();
                            $caseRefund->account_id = $add->id;
                            $caseRefund->is_refund = 1;
                            $caseRefund->currency = 'CNY';
                            $caseRefund->claim_amount = 500;
                            $caseRefund->create_by = 'system';
                            $caseRefund->create_time = date('Y-m-d H:i:s', time());
                            $caseRefund->save();
                        }

                        echo "{$platformCode} old_account_id : {$item['id']} add success\n";
                    } else {
                        echo "{$platformCode} old_account_id : {$item['id']} add error\n";
                    }
                } else {
                    //否则，更新原数据
                    $data->status = 1;
                    $data->modify_time = date('Y-m-d H:i:s', time());

                    //循环把ERP账号信息赋值给客服账号信息
                    if (!empty($this->map[$platformCode]['KF'])) {
                        foreach ($this->map[$platformCode]['KF'] as $key => $field) {
                            $data->{$field} = $item[$this->map[$platformCode]['ERP'][$key]];
                        }
                    }

                    //eBay平台的新token需要单独设置
                    if ($platformCode == Platform::PLATFORM_CODE_EB) {
                        if (!empty($newTokens) && array_key_exists($item['id'], $newTokens)) {
                            $data->refresh_token = $newTokens[$item['id']]['refresh_token'];
                        }
                    }

                    //更新账号信息
                    if ($data->save()) {
                        echo "{$platformCode} old_account_id : {$item['id']} update success\n";
                    } else {
                        echo "{$platformCode} old_account_id : {$item['id']} update error\n";
                    }
                }
            }
        }
    }

    /**
     * GET请求
     */
    public function request($url, $params = '')
    {
        $curl = curl_init();
        //设置返回原生raw内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //设置不返回头文件的信息
        curl_setopt($curl, CURLOPT_HEADER, false);
        //设置参数
        if (is_array($params) && !empty($params)) {
            $params = http_build_query($params);
        }
        $url = rtrim($url, '?') . '?' . $params;
        curl_setopt($curl, CURLOPT_URL, $url);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    /**
     * 获取平台账号信息
     */
    public function getAccount($platformCode)
    {
        if (empty($platformCode)) {
            return false;
        }

        $query = new Query();
        switch ($platformCode) {
            case Platform::PLATFORM_CODE_EB :
                $tableName = '{{%ebay_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_ALI:
                $tableName = '{{%aliexpress_account_qimen}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_WISH :
                $tableName = '{{%wish_account}}';
                //统一的把账号ID字段名转换成id
                $query->from($tableName)->select('*,wish_id as id')->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_AMAZON :
                $tableName = '{{%amazon_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_LAZADA :
                $tableName = '{{%lazada_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_SHOPEE :
                $tableName = '{{%shopee_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_CDISCOUNT :
                $tableName = '{{%cdiscount_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_WALMART :
                $tableName = '{{%walmart_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_MALL :
                $tableName = '{{%mall_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_JOOM :
                $tableName = '{{%joom_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_BB :
                $tableName = '{{%alibaba_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_STR :
                $tableName = '{{%11street_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_JUM :
                $tableName = '{{%jumia_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_JET :
                $tableName = '{{%jet_custom_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_GRO :
                $tableName = '{{%groupon_custom_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_DIS :
                $tableName = '{{%distribution_custom_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_JOL :
                $tableName = '{{%jollychic_custom_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_SOU :
                $tableName = '{{%souq_custom_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_PM :
                $tableName = '{{%priceminister_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_WADI :
                $tableName = '{{%wadi_account}}';
                $query->from($tableName)->where('status = 1');
                break;
            case Platform::PLATFORM_CODE_OBERLO :
                //该表没有status字段
                $tableName = '{{%oberlo_account}}';
                $query->from($tableName);
                break;
            case Platform::PLATFORM_CODE_WJFX :
                //该表没有status字段
                $tableName = '{{%wjfx_account}}';
                $query->from($tableName);
                break;
            default :
                break;
        }

        if (empty($tableName) || empty($query)) {
            return false;
        }

        return $query->createCommand(Yii::$app->db_system)->queryAll();
    }
}