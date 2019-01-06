<?php

namespace app\modules\services\modules\ebay\controllers;

use app\modules\services\modules\ebay\models\EbayGccbtApi;
use Yii;
use app\common\VHelper;
use yii\web\Controller;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\services\modules\ebay\models\EbayAccountOverview;

/**
 * 用于拉取ebay账号表现
 */
class DashboardController extends Controller
{
    /**
     * 拉取ebay账号表现数据
     */
    public function actionGetaccountoverview()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);
            //客户端关闭，仍继续执行
            ignore_user_abort(true);
            ini_set('memory_limit', '256M');
            //获取账号ID(客服系统的)
            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $overview = new EbayAccountOverview();
                //获取ebay的账号表现
                $overview->getAccountOverview($accountId);
                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB, AccountTaskQueue::EB_ACCOUNT_OVERVIEW);

                //如果不为空，则请求下一个接口
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/ebay/dashboard/getaccountoverview', ['id' => $nextAccountId], 'GET', 1200);
                    sleep(5);
                }
                //结束拉取信息
                die('GET ACCOUNT OVERVIEW END');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_EB])
                    ->andWhere(['type' => AccountTaskQueue::EB_ACCOUNT_OVERVIEW])
                    ->count();

                //如果任务队列中没有任务了才可继续添加任务，避免任务堆积。
                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB, Account::STATUS_VALID);
                    if (!empty($accountList)) {
                        //循环的把需要拉取纠纷信息的账号加入任务队列中
                        foreach ($accountList as $account) {
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::EB_ACCOUNT_OVERVIEW;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::EB_ACCOUNT_OVERVIEW,
                    'platform_code' => Platform::PLATFORM_CODE_EB
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/ebay/dashboard/getaccountoverview', ['id' => $accountId], 'GET', 1200);
                        sleep(5);
                    }
                }
                die('RUN GET ACCOUNT OVERVIEW');
            }

        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }

    /**
     * 刷新卖家成绩表
     */
    public function actionFlushselleroverview()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);
            //客户端关闭，仍继续执行
            ignore_user_abort(true);
            ini_set('memory_limit', '256M');

            //获取账号ID(客服系统的)
            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $overview = new EbayAccountOverview();
                //卖家成绩表
                $overview->getSellerOverview($accountId);
                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB, AccountTaskQueue::EB_SELLER_ACCOUNT_OVERVIEW);

                //如果不为空，则请求下一个接口
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/ebay/dashboard/flushselleroverview', ['id' => $nextAccountId], 'GET', 1200);
                }
                //结束拉取信息
                die('GET SELLER ACCOUNT OVERVIEW END');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_EB])
                    ->andWhere(['type' => AccountTaskQueue::EB_SELLER_ACCOUNT_OVERVIEW])
                    ->count();

                //如果任务队列中没有任务了才可继续添加任务，避免任务堆积。
                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB, Account::STATUS_VALID);
                    if (!empty($accountList)) {
                        //循环的把需要拉取纠纷信息的账号加入任务队列中
                        foreach ($accountList as $account) {
                            //当前天数
                            //$day = date('j');
                            //当前账号ID
                            //$accountId = $account->id;
                            //由于获取卖家成绩表的接口，EBAY平台限制每天只能进行500次调用
                            //所以通过当前天数对3取余如果等于账号ID对3取余，则该账号今天可以更新
                            //if (($day % 3) == ($accountId % 3)) {
                            //    $accountTaskQenue = new AccountTaskQueue();
                            //    $accountTaskQenue->account_id = $account->id;
                            //    $accountTaskQenue->type = AccountTaskQueue::EB_SELLER_ACCOUNT_OVERVIEW;
                            //    $accountTaskQenue->platform_code = $account->platform_code;
                            //    $accountTaskQenue->create_time = time();
                            //    $accountTaskQenue->save(false);
                            //}

                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::EB_SELLER_ACCOUNT_OVERVIEW;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取10条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::EB_SELLER_ACCOUNT_OVERVIEW,
                    'platform_code' => Platform::PLATFORM_CODE_EB
                ], 6);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/ebay/dashboard/flushselleroverview', ['id' => $accountId], 'GET', 1200);
                        sleep(3);
                    }
                }
                die('RUN GET SELLER ACCOUNT OVERVIEW');
            }

        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }

    /**
     * 刷新买家体验报告
     */
    public function actionFlushbuyeroverview()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);
            //客户端关闭，仍继续执行
            ignore_user_abort(true);
            ini_set('memory_limit', '256M');

            //获取账号ID(客服系统的)
            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $overview = new EbayAccountOverview();
                //买家体验报告
                $overview->getBuyerOverview($accountId);
                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB, AccountTaskQueue::EB_BUYER_ACCOUNT_OVERVIEW);

                //如果不为空，则请求下一个接口
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/ebay/dashboard/flushbuyeroverview', ['id' => $nextAccountId], 'GET', 1200);
                }
                //结束拉取信息
                die('GET BUYER ACCOUNT OVERVIEW END');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_EB])
                    ->andWhere(['type' => AccountTaskQueue::EB_BUYER_ACCOUNT_OVERVIEW])
                    ->count();

                //如果任务队列中没有任务了才可继续添加任务，避免任务堆积。
                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB, Account::STATUS_VALID);
                    if (!empty($accountList)) {
                        //循环的把需要拉取纠纷信息的账号加入任务队列中
                        foreach ($accountList as $account) {
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::EB_BUYER_ACCOUNT_OVERVIEW;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::EB_BUYER_ACCOUNT_OVERVIEW,
                    'platform_code' => Platform::PLATFORM_CODE_EB
                ], 6);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/ebay/dashboard/flushbuyeroverview', ['id' => $accountId], 'GET', 1200);
                        sleep(3);
                    }
                }
                die('RUN GET BUYER ACCOUNT OVERVIEW');
            }

        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }

    /**
     * 单独刷新账号的政策状态
     */
    public function actionFlushaccountoverview()
    {
        try {
            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $overview = new EbayAccountOverview();
                //获取ebay的账号表现
                $overview->getAccountOverview($accountId);

                die('flush account overview end');
            } else {
                //避免服务器拉取信息超时
                set_time_limit(0);
                //客户端关闭，仍继续执行
                ignore_user_abort(true);

                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    $overview = new EbayAccountOverview();
                    foreach ($accountList as $account) {
                        //获取ebay的账号表现
                        $overview->getAccountOverview($account->id);
                        echo "{$account->id} flush account overview end \r\n";
                        sleep(2);
                    }
                }

                die('flush all account overview end');
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }
}