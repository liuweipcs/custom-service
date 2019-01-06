<?php

namespace app\modules\services\modules\jumia\controllers;

use Yii;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AccountTaskQueue;
use yii\web\Controller;
use app\modules\services\modules\jumia\models\JumiaGetRefundOrder;

class JumiaController extends Controller
{
    /**
     * 获取退款订单数据
     */
    public function actionGetrefundorder()
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $accountId = Yii::$app->request->get('id');

        if (!empty($accountId)) {
            //默认拉取前一周的退款
            $startTime = date('Y-m-d 00:00:00', strtotime('-2 week'));
            $endTime   = date('Y-m-d 23:59:59');

            $account = Account::findOne($accountId);
            if (!empty($account)) {
                $refundOrder = new JumiaGetRefundOrder();
                $refundOrder->refundOrderList($account, $startTime, $endTime);
            }

            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_JUM, AccountTaskQueue::JUMIA_REFUND);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/jumia/jumia/getrefundorder', ['id' => $nextAccountId], 'GET', 1200);
            }

            die('GET JUMIA REFUND');
        } else {

            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_JUM,
                'type' => AccountTaskQueue::JUMIA_REFUND
            ])->count();

            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_JUM, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::JUMIA_REFUND;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }

            //默认先从队列中取5条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::JUMIA_REFUND,
                'platform_code' => Platform::PLATFORM_CODE_JUM
            ]);

            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/jumia/jumia/getrefundorder', ['id' => $accountId], 'GET', 1200);
                    sleep(2);
                }
            }
            die('RUN GET JUMIA');
        }
    }
}