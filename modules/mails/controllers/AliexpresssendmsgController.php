<?php

namespace app\modules\mails\controllers;

use app\modules\orders\models\OrderAliexpressSearch;
use Yii;
use app\components\Controller;
use app\components\ConfigFactory;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\services\modules\aliexpress\components\TaobaoQimenApi;

class AliexpresssendmsgController extends Controller
{

    /**
     * 速卖通发送站内信
     */
    public function actionSendmsg()
    {
        //平台订单ID
        $orderId = Yii::$app->request->post('orderId', '');
        //买家登陆ID
        $buyerUserId = Yii::$app->request->post('buyerUserId', 0);
        //账号ID
        $accountId = Yii::$app->request->post('accountId', 0);
        //消息内容
        $msg = Yii::$app->request->post('msg', '');

        if (empty($orderId)) {
            die(json_encode([
                'code' => 0,
                'message' => '平台订单ID不能为空',
            ]));
        }
        if (empty($buyerUserId)) {
            die(json_encode([
                'code' => 0,
                'message' => '买家登陆ID不能为空',
            ]));
        }
        if (empty($accountId)) {
            die(json_encode([
                'code' => 0,
                'message' => '账号ID不能为空',
            ]));
        }
        if (empty($msg)) {
            die(json_encode([
                'code' => 0,
                'message' => '消息内容不能为空',
            ]));
        }

        //获取速卖通账号信息
        $account = AliexpressAccount::find()->where(['id' => $accountId])->asArray()->one();

        //获取速卖通客服系统账号信息
        $accountsKefu = Account::getAccounts(Platform::PLATFORM_CODE_ALI);

        if (empty($account) || empty($accountsKefu)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到账号信息',
            ]));
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            die(json_encode([
                'code' => 0,
                'message' => '接口网关地址为空',
            ]));
        }

        //卖家登陆ID
        $seller_login_id = array_key_exists($accountId, $accountsKefu) ? $accountsKefu[$accountId]['seller_id'] : '';

        if (empty($seller_login_id)) {
            die(json_encode([
                'code' => 0,
                'message' => '卖家登陆ID为空',
            ]));
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        //配置请求参数
        $request = new \MinxinAliexpressNewstationletterRequest();
        //设置账号id
        $request->setAccountId($account['id']);
        //设置卖家登录帐号
        $request->setSellerId($seller_login_id);
        //设置买家登录帐号
        $request->setBuyerId($buyerUserId);
        //设置消息类型
        $request->setMessageType('order');
        //设置订单id
        $request->setExternId($orderId);
        //设置消息
        $request->setContent($msg);

        $taobaoQimenApi->doRequest($request);
        //如果消息成功
        if ($taobaoQimenApi->isSuccess()) {
            die(json_encode([
                'code' => 1,
                'message' => '发送消息成功',
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '发送消息失败',
            ]));
        }
    }

    /**
     * 批量发生速卖通站内信
     */
    public function actionSendmsgs()
    {
        /**
         * 1 点击选择框 2 条件搜索
         */
        if(empty($_REQUEST['three_ids'])){
            //查询添加搜索
            $get_date=isset($_REQUEST['get_date'])?$_REQUEST['get_date']:null;
            $begin_date=isset($_REQUEST['begin_date'])?$_REQUEST['begin_date']:null;
            $end_date=isset($_REQUEST['end_date'])?$_REQUEST['end_date']:null;
            $order_status=isset($_REQUEST['order_status'])?$_REQUEST['order_status']:null;
            $condition_option = isset($_REQUEST['condition_option']) ? trim($_REQUEST['condition_option']) : null;
            $condition_value = isset($_REQUEST['condition_value']) ? trim($_REQUEST['condition_value']) : null;
            $platform_code = Platform::PLATFORM_CODE_ALI;
            $account_ids  = isset($_REQUEST['account_ids']) ? trim($_REQUEST['account_ids']) : null;//Aliexpresslist账号
            $ship_code  = isset($_REQUEST['ship_code']) ? trim($_REQUEST['ship_code']) : null;//出货方式
            $ship_country = isset($_REQUEST['ship_country']) ? trim($_REQUEST['ship_country']) : null;//目的国
            $res=OrderAliexpressSearch::get_list($condition_option,$condition_value,$platform_code,$get_date,$begin_date,$end_date,$order_status,$account_ids,$ship_code,$ship_country);
            $three_ids=$res['three_ids'];
            $three_ids=ltrim($three_ids,',');
        }else{
            $three_ids=$_REQUEST['three_ids'];
        }
        //消息内容
        $msg=$_REQUEST['msg'];
        //拆分数组
        $three_ids_arr=explode(',',$three_ids);
        $i = $j = 0;
        foreach ($three_ids_arr as $v)
        {
            //平台订单ID
            $orderId=explode('&',$v)[0];
            
            //账号ID
            $accountId= $buyerUserId=explode('&',$v)[1];
            //买家登陆ID
            $buyerUserId=explode('&',$v)[2];
            if (empty($orderId)) {
                die(json_encode([
                    'code' => 0,
                    'message' => '平台订单ID不能为空',
                ]));
            }
            if (empty($buyerUserId)) {
                die(json_encode([
                    'code' => 0,
                    'message' => '该订单'.$orderId.'买家登陆ID不能为空',
                ]));
            }
            if (empty($accountId)) {
                die(json_encode([
                    'code' => 0,
                    'message' => '账号ID不能为空',
                ]));
            }
            if (empty($msg)) {
                die(json_encode([
                    'code' => 0,
                    'message' => '消息内容不能为空',
                ]));
            }

            //获取速卖通账号信息
            $account = AliexpressAccount::find()->where(['id' => $accountId])->asArray()->one();

            //获取速卖通客服系统账号信息
            $accountsKefu = Account::getAccounts(Platform::PLATFORM_CODE_ALI);

            if (empty($account) || empty($accountsKefu)) {
                die(json_encode([
                    'code' => 0,
                    'message' => '没有找到账号信息',
                ]));
            }
            //获取奇门网关地址
            $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
            $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

            if (empty($gatewayUrl)) {
                die(json_encode([
                    'code' => 0,
                    'message' => '接口网关地址为空',
                ]));
            }

            //卖家登陆ID
            $seller_login_id = array_key_exists($accountId, $accountsKefu) ? $accountsKefu[$accountId]['seller_id'] : '';

            if (empty($seller_login_id)) {
                die(json_encode([
                    'code' => 0,
                    'message' => '卖家登陆ID为空',
                ]));
                continue;
            }

            //创建奇门请求api
            $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
            $taobaoQimenApi->setGatewayUrl($gatewayUrl);

            //配置请求参数
            $request = new \MinxinAliexpressNewstationletterRequest();
            //设置账号id
            $request->setAccountId($account['id']);
            //设置卖家登录帐号
            $request->setSellerId($seller_login_id);
            //设置买家登录帐号
            $request->setBuyerId($buyerUserId);
            //设置消息类型
            $request->setMessageType('order');
            //设置订单id
            $request->setExternId($orderId);
            //设置消息
            $request->setContent($msg);

            $taobaoQimenApi->doRequest($request);
            //如果消息成功
            if ($taobaoQimenApi->isSuccess()) {
                $i++;
//                die(json_encode([
//                    'code' => 1,
//                    'message' => '发送消息成功',
//                ]));
            } else {
                $j++;
//                die(json_encode([
//                    'code' => 0,
//                    'message' => '发送消息失败',
//                ]));
            }
        }
        die(json_encode([
            'code' => 0,
            'message' => $i.'条记录发送成功，'.$j.'条记录发送失败!',
        ]));
    }
}