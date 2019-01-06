<?php

namespace wish\controllers;

use Yii;
use yii\web\Controller;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AccountTaskQueue;
use wish\models\WishNoti;

class WishController extends Controller
{
    const WAIT_SECONDS = 2;

    /**
     * 拉取wish的通知
     */
    public function actionGetnotification()
    {
        try {
            set_time_limit(0);

            //获取账号ID(客服系统的)
            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $noti = new WishNoti();
                //获取速卖通中差评的评价
                $noti->getAllNotification($accountId);
                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_WISH, AccountTaskQueue::LIST_OF_NOTIFICATION);

                //如果不为空，则请求下一个接口
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/wish/wish/getnotification', ['id' => $nextAccountId]);
                }
                //结束拉取信息
                die('GET NOTIFICATION END');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_WISH])
                    ->andWhere(['type' => AccountTaskQueue::LIST_OF_NOTIFICATION])
                    ->count();

                //如果任务队列中没有任务了才可继续添加任务，避免任务堆积。
                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_WISH, Account::STATUS_VALID);
                    if (!empty($accountList)) {
                        //循环的把需要拉取纠纷信息的账号加入任务队列中
                        foreach ($accountList as $account) {
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::LIST_OF_NOTIFICATION;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::LIST_OF_NOTIFICATION,
                    'platform_code' => Platform::PLATFORM_CODE_WISH
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/wish/wish/getnotification', ['id' => $accountId]);
                        sleep(2);
                    }
                }
                die('RUN GET NOTIFICATION');
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }
}