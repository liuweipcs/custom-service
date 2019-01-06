<?php

namespace app\modules\services\modules\mall\controllers;

use Yii;
use app\common\VHelper;
use yii\web\Controller;
use app\modules\accounts\models\MallAccount;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\services\modules\mall\models\MallGetRefundOrder;


class MallController extends Controller
{

    /**
     * 获取退款订单数据
     */
    public function actionGetrefundorder()
    {
        //避免服务器拉取信息超时
        set_time_limit(0);
        ignore_user_abort(true);

        $accountId = Yii::$app->request->get('id', '');

        if (!empty($accountId)) {
            //默认拉取前一周的退款
            $startTime = date('Y-m-d 00:00:00', strtotime('-1 week'));

            $account = Account::findOne($accountId);
            if (!empty($account)) {
                $refundOrder = new MallGetRefundOrder();
                $refundOrder->refundOrderList($account, $startTime);
            }

            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_MALL, AccountTaskQueue::MALL_REFUND);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/mall/mall/getrefundorder', ['id' => $nextAccountId], 'GET', 1200);
            }

            die('GET MALL REFUND');

        } else {

            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_MALL,
                'type' => AccountTaskQueue::MALL_REFUND
            ])->count();


            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_MALL, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::MALL_REFUND;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }

            //默认先从队列中取5条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::MALL_REFUND,
                'platform_code' => Platform::PLATFORM_CODE_MALL
            ]);

            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/mall/mall/getrefundorder', ['id' => $accountId], 'GET', 1200);
                    sleep(2);
                }
            }
            die('RUN GET MALL');
        }
    }

    /**
     * 刷新mall平台token
     */
    public function actionRefreshtoken()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);

            $accountId = Yii::$app->request->get('id', '');

            if (!empty($accountId)) {
                $account = Account::findOne($accountId);
                if (!empty($account)) {
                    $mall_account = MallAccount::findOne($account->old_account_id);
                    $client_id = $mall_account->client_id;
                    $client_secret = $mall_account->client_secret;
                    $refresh_token = $mall_account->refresh_token;
                    $user_name = $mall_account->user_name;
                    $password = $mall_account->password;

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://mall.my.com/oauth/v2/token?grant_type=refresh_token&client_id={$client_id}&client_secret={$client_secret}&refresh_token={$refresh_token}",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_SSL_VERIFYPEER => FALSE,
                        CURLOPT_SSL_VERIFYHOST => FALSE,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                    ));

                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);

                    if ($err) {
                        echo "刷新token错误" . $err;
                    } else {
                        $refresh = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
                        if (!empty($refresh['access_token'])) {
                            $account->access_token = $refresh['access_token'];
                            $account->refresh_token = $refresh['refresh_token'];
                            $mall_account->save();
                        } else {
                            if ($refresh['error_description'] == "Invalid refresh token") {
                                $curl = curl_init();
                                curl_setopt_array($curl, array(
                                    CURLOPT_URL => "https://mall.my.com/oauth/v2/token?grant_type=password&client_id={$client_id}&client_secret={$client_secret}&username={$user_name}&password={$password}",
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "GET",
                                    CURLOPT_SSL_VERIFYPEER => FALSE,
                                    CURLOPT_SSL_VERIFYHOST => FALSE,
                                ));
                                $response = curl_exec($curl);
                                $err = curl_error($curl);

                                if ($err) {
                                    echo '刷新token错误' . $err;
                                } else {
                                    $refresh = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
                                    if (!empty($refresh['access_token'])) {
                                        $account->access_token = $refresh['access_token'];
                                        $account->refresh_token = $refresh['refresh_token'];
                                        $account->save();
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                //拉取所有账号的的token
                $accountList = Account::find()->where(['platform_code' => Platform::PLATFORM_CODE_MALL])->all();
                foreach ($accountList as $account) {
                    if (!empty($account)) {
                        $mall_account = MallAccount::findOne($account->old_account_id);
                        $client_id = $mall_account->client_id;
                        $client_secret = $mall_account->client_secret;
                        $refresh_token = $mall_account->refresh_token;
                        $user_name = $mall_account->user_name;
                        $password = $mall_account->password;

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => "https://mall.my.com/oauth/v2/token?grant_type=refresh_token&client_id={$client_id}&client_secret={$client_secret}&refresh_token={$refresh_token}",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_SSL_VERIFYPEER => FALSE,
                            CURLOPT_SSL_VERIFYHOST => FALSE,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "GET",
                        ));
                        $response = curl_exec($curl);
                        $err = curl_error($curl);
                        curl_close($curl);

                        if ($err) {
                            echo "刷新token错误" . $err;
                        } else {
                            $refresh = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
                            if (!empty($refresh['access_token'])) {
                                $account->access_token = $refresh['access_token'];
                                $account->refresh_token = $refresh['refresh_token'];
                                $mall_account->save();
                            } else {
                                if ($refresh['error_description'] == "Invalid refresh token") {
                                    $curl = curl_init();
                                    curl_setopt_array($curl, array(
                                        CURLOPT_URL => "https://mall.my.com/oauth/v2/token?grant_type=password&client_id={$client_id}&client_secret={$client_secret}&username={$user_name}&password={$password}",
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => "",
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 30,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => "GET",
                                        CURLOPT_SSL_VERIFYPEER => FALSE,
                                        CURLOPT_SSL_VERIFYHOST => FALSE,
                                    ));
                                    $response = curl_exec($curl);
                                    $err = curl_error($curl);
                                    if ($err) {
                                        echo '刷新token错误' . $err;
                                    } else {
                                        $refresh = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
                                        if (!empty($refresh['access_token'])) {
                                            $account->access_token = $refresh['access_token'];
                                            $account->refresh_token = $refresh['refresh_token'];
                                            $account->save();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            die('GET MALL TOKEN END');
        } catch (\Exception $e) {
            echo $e->getFile(), "\n";
            echo $e->getLine(), "\n";
            echo $e->getMessage();
        }
    }

}