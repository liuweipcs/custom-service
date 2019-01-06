<?php

namespace app\modules\services\modules\cdiscount\controllers;

use app\modules\services\modules\cdiscount\models\CdiscountGetAccount;
use app\modules\services\modules\cdiscount\models\CdiscountGetOfferQuestionList;
use app\modules\services\modules\cdiscount\models\CdiscountGetOrderClaimList;
use app\modules\services\modules\cdiscount\models\CdiscountGetOrderQuestionList;
use Yii;
use yii\web\Controller;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\common\VHelper;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\services\modules\cdiscount\components\cdiscountApi;
use app\modules\services\modules\cdiscount\models\CdiscountGetRefundOrder;

class CdiscountController extends Controller
{

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
                $cdApi = new cdiscountApi();
                $refresh_token = $cdApi->refreshToken($account->api_name, $account->access_token);
                if (!empty($refresh_token)) {
                    $account->refresh_token = $refresh_token;
                    if ($account->save()) {
                        echo "account id : {$account->id} refresh token success\n";
                    }
                }
            }
        } else {
            //获取账号列表
            $accountList = Account::find()->where(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])->all();
            if (!empty($accountList)) {
                foreach ($accountList as $account) {
                    if (!empty($account)) {
                        $cdApi = new cdiscountApi();
                        $refresh_token = $cdApi->refreshToken($account->api_name, $account->access_token);
                        if (!empty($refresh_token)) {
                            $account->refresh_token = $refresh_token;
                            if ($account->save()) {
                                echo "account id : {$account->id} refresh token success\n";
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * 获取退款订单数据
     */
    public function actionGetrefundorder()
    {
        //避免服务器拉取信息超时
        set_time_limit(0);
        ignore_user_abort(true);

        $accountId = Yii::$app->request->get('id', 0);
      
        if (!empty($accountId)) {
            //默认拉取前一周的退款
            $startTime = date('Y-m-d 00:00:00', strtotime('-1 week'));
            $endTime = date('Y-m-d 23:59:59');

            $account = Account::findOne($accountId);

            if (!empty($account)) {
                $refundOrder = new CdiscountGetRefundOrder();
                $refundOrder->refundOrderList($account, $startTime, $endTime);
                
            }

            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_CDISCOUNT, AccountTaskQueue::CDISCOUNT_REFUND);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/cdiscount/cdiscount/getrefundorder', ['id' => $nextAccountId], 'GET', 1200);
            }

            die('GET CDISCOUNT REFUND');
        } else {
            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT,
                'type' => AccountTaskQueue::CDISCOUNT_REFUND
            ])->count();
          
            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_CDISCOUNT, Account::STATUS_VALID);
 
                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::CDISCOUNT_REFUND;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }
          
            //默认先从队列中取5条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::CDISCOUNT_REFUND,
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT
            ]);

            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/cdiscount/cdiscount/getrefundorder', ['id' => $accountId], 'GET', 1200);
                    sleep(2);
                }
            }
            die('RUN GET CDISCOUNT');
        }
    }

    /**
     * 获取账号表现
     */
    public function actionGetaccount()
    {
        //避免服务器拉取信息超时
        set_time_limit(0);
        ignore_user_abort(true);

        $accountId = Yii::$app->request->get('id', 0);

        if (!empty($accountId)) {

            $account = Account::findOne($accountId);
            if (!empty($account)) {
                $refundOrder = new CdiscountGetAccount();
                $refundOrder->getSellerIndicators($account);

            }

            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_CDISCOUNT, AccountTaskQueue::CDISCOUNT_SELLER_INDICATORS);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/cdiscount/cdiscount/getaccount', ['id' => $nextAccountId]);

            }

            die('GET CDISCOUNT INDICATORS');
        } else {
            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT,
                'type' => AccountTaskQueue::CDISCOUNT_SELLER_INDICATORS
            ])->count();


            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_CDISCOUNT, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::CDISCOUNT_SELLER_INDICATORS;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }

            //默认先从队列中取5条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::CDISCOUNT_SELLER_INDICATORS,
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT
            ],5);

            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/cdiscount/cdiscount/getaccount', ['id' => $accountId]);
                    sleep(2);
                }
            }
            die('RUN GET CDISCOUNT');
        }
    }

    /**
     * 获取纠纷问题
     */
    public function actionGetorderclaimlist()
    {
        //避免服务器拉取信息超时
        set_time_limit(0);
        $accountId = Yii::$app->request->get('id', 0);

        if (!empty($accountId)) {
            //默认拉取7天的纠纷问题
            $startTime = date('Y-m-d 00:00:00', strtotime('-10 days'));
            $endTime = date('Y-m-d 23:59:59');

            $account = Account::findOne($accountId);
            if (!empty($account)) {
                $refundOrder = new CdiscountGetOrderClaimList();
                $refundOrder->getOrderClaimList($account, $startTime, $endTime);
            }

            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_CDISCOUNT, AccountTaskQueue::CDISCOUNT_ORDER_CLIAM_LIST);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/cdiscount/cdiscount/getorderclaimlist', ['id' => $nextAccountId]);
            }

            die('GET CDISCOUNT ORDER CLAIM LIST');
        } else {
            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT,
                'type' => AccountTaskQueue::CDISCOUNT_ORDER_CLIAM_LIST
            ])->count();

            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_CDISCOUNT, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::CDISCOUNT_ORDER_CLIAM_LIST;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }

            //默认先从队列中取6条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::CDISCOUNT_ORDER_CLIAM_LIST,
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT
            ], 6);

            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/cdiscount/cdiscount/getorderclaimlist', ['id' => $accountId]);
                    sleep(2);
                }
            }
            die('RUN GET CDISCOUNT ORDER CLAIM LIST');
        }

    }

    /**
     * 获取售前产品咨询
     */
    public function actionGetofferquestionlist()
    {
        //避免服务器拉取信息超时
        set_time_limit(0);
        $accountId = Yii::$app->request->get('id', 0);

        if (!empty($accountId)) {
            //默认拉取7天的售前产品咨询
            $startTime = date('Y-m-d 00:00:00', strtotime('-10 days'));
            $endTime = date('Y-m-d 23:59:59');

            $account = Account::findOne($accountId);
            if (!empty($account)) {
                $offerQuestionList = new CdiscountGetOfferQuestionList();
                $offerQuestionList->getOfferQuestionList($account, $startTime, $endTime);
            }

            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_CDISCOUNT, AccountTaskQueue::CDISCOUNT_OFFER_QUESTION_LIST);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/cdiscount/cdiscount/getofferquestionlist', ['id' => $nextAccountId]);
            }

            die('GET CDISCOUNT OFFER QUESTION LIST');
        } else {
            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT,
                'type' => AccountTaskQueue::CDISCOUNT_OFFER_QUESTION_LIST
            ])->count();

            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_CDISCOUNT, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::CDISCOUNT_OFFER_QUESTION_LIST;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }

            //默认先从队列中取6条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::CDISCOUNT_OFFER_QUESTION_LIST,
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT
            ], 6);

            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/cdiscount/cdiscount/getofferquestionlist', ['id' => $accountId]);
                    sleep(2);
                }
            }
            die('RUN GET CDISCOUNT  OFFER QUESTION LIST');
        }
    }


    /**
     * 获取订单咨询
     */
    public function actionGetorderquestionlist()
    {
        //避免服务器拉取信息超时
        set_time_limit(0);
        $accountId = Yii::$app->request->get('id', 0);

        if (!empty($accountId)) {
            //默认拉取7天的订单咨询
            $startTime = date('Y-m-d 00:00:00', strtotime('-10 days'));
            $endTime = date('Y-m-d 23:59:59');

            $account = Account::findOne($accountId);
            if (!empty($account)) {
                $orderQuestionList = new CdiscountGetOrderQuestionList();
                $orderQuestionList->getOrderQuestionList($account, $startTime, $endTime);
            }
            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_CDISCOUNT, AccountTaskQueue::CDISCOUNT_ORDER_QUESTION_LIST);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/cdiscount/cdiscount/getorderquestionlist', ['id' => $nextAccountId]);
            }

            die('GET CDISCOUNT ORDER QUESTION LIST ');
        } else {
            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT,
                'type' => AccountTaskQueue::CDISCOUNT_ORDER_QUESTION_LIST
            ])->count();

            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_CDISCOUNT, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::CDISCOUNT_ORDER_QUESTION_LIST;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }
            //默认先从队列中取6条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::CDISCOUNT_ORDER_QUESTION_LIST,
                'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT
            ], 6);

            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/cdiscount/cdiscount/getorderquestionlist', ['id' => $accountId]);
                    sleep(2);
                }
            }
            die('RUN GET CDISCOUNT ORDER QUESTION LIST');
        }
    }
}