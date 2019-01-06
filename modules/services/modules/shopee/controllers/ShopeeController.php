<?php

namespace app\modules\services\modules\shopee\controllers;

use Yii;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\services\modules\shopee\models\ShopeeGetOrdersByStatus;
use app\modules\services\modules\shopee\models\ShopeeGetRuturnList;
use yii\web\Controller;
use app\modules\mails\models\AccountTaskQueue;

class ShopeeController extends Controller
{


    /**
     * @author alpha
     * @desc 获取退款退货数据
     */
    public function actionGetreturnlist()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);

            //开始时间
            $startTime = null;
            //结束时间
            $endTime = null;

            $accountId = Yii::$app->request->get('id', '');

            if (!empty($accountId)) {
                $account = Account::findOne($accountId);
                if (!empty($account)) {
                    $issue = new ShopeeGetRuturnList();
                    $issue->GetReturnList($account, $startTime, $endTime);
                }

                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_SHOPEE, AccountTaskQueue::SHOPEE_RETURN);
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/shopee/shopee/getreturnlist', ['id' => $nextAccountId], 'GET', 1200);
                }

                die('GET SHOPEE RETURN END');

            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()->where([
                    'platform_code' => Platform::PLATFORM_CODE_SHOPEE,
                    'type' => AccountTaskQueue::SHOPEE_RETURN
                ])->count();

                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_SHOPEE, Account::STATUS_VALID);

                    if (!empty($accountList)) {
                        foreach ($accountList as $account) {
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::SHOPEE_RETURN;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::SHOPEE_RETURN,
                    'platform_code' => Platform::PLATFORM_CODE_SHOPEE
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/shopee/shopee/getreturnlist', ['id' => $accountId], 'GET', 1200);
                        sleep(2);
                    }
                }

                die('RUN GET SHOPEE RETURN');
            }

        } catch (\Exception $e) {
            echo $e->getFile(), "\n";
            echo $e->getLine(), "\n";
            echo $e->getMessage();
        }
    }

    /**
     * 获取订单所有状态
     */
    public function actionGetordersbystatus()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);

            //开始时间
            $startTime = null;
            //结束时间
            $endTime = null;

            $accountId = Yii::$app->request->get('id', '');

            if (!empty($accountId)) {
                $account = Account::findOne($accountId);

                if (!empty($account)) {
                    $issue = new ShopeeGetOrdersByStatus();
                    $issue->GetOrdersByStatus($account, $startTime, $endTime);
                }

                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_SHOPEE, AccountTaskQueue::SHOPEE_ORDER_STATUS);
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/shopee/shopee/getordersbystatus', ['id' => $nextAccountId], 'GET', 1200);
                }

                die('GET SHOPEE ORDER STATUS END');
            } else {

                //获取当前任务队列数
                $count = AccountTaskQueue::find()->where([
                    'platform_code' => Platform::PLATFORM_CODE_SHOPEE,
                    'type' => AccountTaskQueue::SHOPEE_ORDER_STATUS
                ])->count();

                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_SHOPEE, Account::STATUS_VALID);

                    if (!empty($accountList)) {
                        foreach ($accountList as $account) {
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::SHOPEE_ORDER_STATUS;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::SHOPEE_ORDER_STATUS,
                    'platform_code' => Platform::PLATFORM_CODE_SHOPEE
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/shopee/shopee/getordersbystatus', ['id' => $accountId], 'GET', 1200);
                        sleep(2);
                    }
                }

                die('RUN GET SHOPEE ORDER STATUS');
            }

        } catch (\Exception $e) {
            echo $e->getFile(), "\n";
            echo $e->getLine(), "\n";
            echo $e->getMessage();
        }
    }

    /**
     * 获取退款订单数据
     */
    public function actionGetrefundorder()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);

            $start_time = Yii::$app->request->get('start_time', '');


            $accountId = Yii::$app->request->get('id', '');

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
                    $issue = new ShopeeGetRuturnList();
                    $issue->refundOrderList($account, $startTime, $endTime);
                }

                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_SHOPEE, AccountTaskQueue::SHOPEE_REFUND);
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/shopee/shopee/getrefundorder', ['id' => $nextAccountId, 'start_time' => $start_time], 'GET', 1200);
                }

                die('GET SHOPEE REFUND END');
            } else {

                //获取当前任务队列数
                $count = AccountTaskQueue::find()->where([
                    'platform_code' => Platform::PLATFORM_CODE_SHOPEE,
                    'type' => AccountTaskQueue::SHOPEE_REFUND
                ])->count();

                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_SHOPEE, Account::STATUS_VALID);

                    if (!empty($accountList)) {
                        foreach ($accountList as $account) {
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::SHOPEE_REFUND;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::SHOPEE_REFUND,
                    'platform_code' => Platform::PLATFORM_CODE_SHOPEE
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/shopee/shopee/getrefundorder', ['id' => $accountId, 'start_time' => $start_time], 'GET', 1200);
                        sleep(2);
                    }
                }

                die('RUN GET SHOPEE REFUND');
            }

        } catch (\Exception $e) {
            echo $e->getFile(), "\n";
            echo $e->getLine(), "\n";
            echo $e->getMessage();
        }
    }
}