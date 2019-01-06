<?php

namespace app\modules\services\modules\lazada\controllers;

use Yii;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use yii\web\Controller;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\services\modules\lazada\models\LazadaGetRefundOrder;

class LazadaController extends Controller
{

    /**
     * 获取退款订单数据
     */
    public function actionGetrefundorder()
    {
        //避免服务器拉取信息超时
        set_time_limit(0);
        ignore_user_abort(true);

        $accountId = Yii::$app->request->get('id', 0);

        $start_time = Yii::$app->request->get('start_time', '');

        if (!empty($accountId)) {


            $account = Account::findOne($accountId);
            //默认拉取前一周的退款
            if(!empty($start_time)){
                $startTime = date('Y-m-d 00:00:00',strtotime($start_time));
                $endTime = date('Y-m-d 23:59:59', strtotime("+10 day", strtotime($start_time)));

            }else{
                $startTime = date('Y-m-d 00:00:00', strtotime('-1 week'));
                $endTime   = date('Y-m-d 23:59:59');

            }

            if (!empty($account)) {
                $refundOrder = new LazadaGetRefundOrder();
                $refundOrder->refundOrderList($account, $startTime, $endTime);
            }

            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_LAZADA, AccountTaskQueue::LAZADA_REFUND);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/lazada/lazada/getrefundorder', ['id' => $nextAccountId, 'start_time'=> $start_time], 'GET', 1200);
            }

            die('GET LAZADA REFUND');
        } else {
            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_LAZADA,
                'type' => AccountTaskQueue::LAZADA_REFUND
            ])->count();

            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_LAZADA, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::LAZADA_REFUND;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }

            //默认先从队列中取5条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::LAZADA_REFUND,
                'platform_code' => Platform::PLATFORM_CODE_LAZADA
            ]);


            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/lazada/lazada/getrefundorder', ['id' => $accountId, 'start_time'=> $start_time], 'GET', 1200);
                    sleep(2);
                }
            }
            die('RUN GET LAZADA');
        }
    }
}