<?php

namespace app\modules\services\modules\joom\controllers;

use Yii;
use yii\web\Controller;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\services\modules\joom\models\JoomGetRefundOrder;
use app\modules\services\modules\joom\models\JoomApi;

class JoomController extends Controller
{
    /**
     * 拉取退款单
     */
    public function actionGetrefundorder()
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $accountId = Yii::$app->request->get('id');

        if (!empty($accountId)) {
            //默认拉取前一周的退款
            $since = date('Y-m-d', strtotime('-1 week'));

            $account = Account::findOne($accountId);
            if (!empty($account) && !empty($account->access_token)) {
                $refundOrder = new JoomGetRefundOrder();
                $refundOrder->refundOrderList($account, $since);
            }

            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_JOOM, AccountTaskQueue::JOOM_REFUND);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/joom/joom/getrefundorder', ['id' => $nextAccountId], 'GET', 1200);
            }

            die('GET JOOM REFUND');
        } else {
            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_JOOM,
                'type' => AccountTaskQueue::JOOM_REFUND
            ])->count();

            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_JOOM, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::JOOM_REFUND;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }

            //默认先从队列中取5条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::JOOM_REFUND,
                'platform_code' => Platform::PLATFORM_CODE_JOOM
            ]);

            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/joom/joom/getrefundorder', ['id' => $accountId], 'GET', 1200);
                    sleep(2);
                }
            }
            die('RUN GET JOOM');
        }
    }

    /**
     * 刷新token
     */
    public function actionRefreshtoken()
    {
        //避免服务器拉取信息超时
        set_time_limit(0);

        $accountId = Yii::$app->request->get('id', 0);

        if (!empty($accountId)) {
            $account = Account::findOne($accountId);
            if (!empty($account)) {
                $access_token = JoomApi::refreshToken($account->id);
                if (!empty($access_token)) {
                    $account->access_token = $access_token;
                    if ($account->save()) {
                        echo "account id : {$account->id} refresh token success\n";
                    }
                }
            }
        } else {
            //获取账号列表
            $accountList = Account::find()->where(['platform_code' => Platform::PLATFORM_CODE_JOOM])->all();
            if (!empty($accountList)) {
                foreach ($accountList as $account) {
                    if (!empty($account)) {
                        $access_token = JoomApi::refreshToken($account->id);
                        if (!empty($access_token)) {
                            $account->access_token = $access_token;
                            if ($account->save()) {
                                echo "account id : {$account->id} refresh token success\n";
                            }
                        }
                    }
                }
            }
        }
    }
}