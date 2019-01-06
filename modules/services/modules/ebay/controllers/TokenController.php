<?php
namespace app\modules\services\modules\ebay\controllers;

use Yii;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbayNewApiToken;
use yii\web\Controller;
use app\modules\services\modules\ebay\models\EbayOAuth;
use app\modules\mails\models\AccountTaskQueue;
use app\common\VHelper;

class TokenController extends Controller
{

    /**
     * 刷新客服系统Ebay账号的access_token
     */
    public function actionFlushaccesstoken()
    {
        try {
            set_time_limit(0);
            ignore_user_abort(true);

            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $account = Account::findOne($accountId);
                if (!empty($account) && !empty($account->refresh_token)) {
                    $accessToken = EbayOAuth::flushAccessToken($account->refresh_token);
                    if (!empty($accessToken)) {
                        $account->access_token = $accessToken;
                        $account->modify_time = date('Y-m-d H:i:s');
                        if ($account->save()) {
                            echo "account id {$accountId} flush access token success\r\n";
                        }
                    }
                }

                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB, AccountTaskQueue::EB_FLUSH_ACCESS_TOKEN);

                //如果不为空，则请求下一个接口
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/ebay/token/flushaccesstoken', ['id' => $nextAccountId], 'GET', 1200);
                }

                die('FLUSH EBAY ACCESS TOKEN END');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_EB])
                    ->andWhere(['type' => AccountTaskQueue::EB_FLUSH_ACCESS_TOKEN])
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
                            $accountTaskQenue->type = AccountTaskQueue::EB_FLUSH_ACCESS_TOKEN;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::EB_FLUSH_ACCESS_TOKEN,
                    'platform_code' => Platform::PLATFORM_CODE_EB
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/ebay/token/flushaccesstoken', ['id' => $accountId], 'GET', 1200);
                        sleep(2);
                    }
                }

                die('RUN FLUSH EBEAY ACCESS TOKEN');
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }
}