<?php

namespace app\modules\services\modules\walmart\controllers;

use Yii;
use yii\web\Controller;
use app\common\VHelper;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\services\modules\amazon\components\MailConfig;
use app\modules\services\modules\walmart\models\WalmartMail;
use app\modules\systems\models\Email;
use app\modules\mails\models\WalmartInbox;
use app\modules\systems\models\ServerEmailMonitor;

class WalmartController extends Controller
{
    const WAIT_SECONDS = 2;

    /**
     * 拉取邮件
     */
    public function actionMail()
    {
        //调试打开所有错误信息
        if (isset($_REQUEST['is_debug'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        }

        try {
            set_time_limit(0);

            $email = Yii::$app->request->get('email', '');
            if (!empty($email)) {
                $days = Yii::$app->request->get('days', 0);
                $mail = new WalmartMail(MailConfig::fetchImapConfig($email));
                //拉取邮件
                $mail->processMail($email, $days);
                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_WALMART, AccountTaskQueue::TASK_TYPE_MESSAGE);
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    $accountInfo = Account::findById($nextAccountId);
                    if (!empty($accountInfo) && !empty($accountInfo->email)) {
                        //获取当前可用并且正在使用的服务器地址
                        $monitor = ServerEmailMonitor::findOne(['is_enable' => 1, 'is_use' => 1, 'status' => 1]);
                        echo '<pre>';
                        var_dump($monitor->server_address);
                        echo '</pre>';
                        if (!empty($monitor)) {
                            $url = 'http://' . $monitor->server_address . '/services/walmart/walmart/mail';
                            VHelper::throwTheader($url, ['email' => trim($accountInfo->email)], 'GET', 1200);
                        } else {
                            VHelper::throwTheader('/services/walmart/walmart/mail', ['email' => trim($accountInfo->email)], 'GET', 1200);
                        }                        
                    }
                }
                //结束拉取信息
                die('GET MAIL END');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_WALMART])
                    ->andWhere(['type' => AccountTaskQueue::TASK_TYPE_MESSAGE])
                    ->count();

                //如果任务队列中没有任务了才可继续添加任务，避免任务堆积。
                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_WALMART, Account::STATUS_VALID);

                    //获取系统中存在配置的邮箱
                    $emails = Email::find()->select('emailaddress')->column();
                    if (!empty($emails)) {
                        $tmp = [];
                        foreach ($emails as $value) {
                            $tmp[] = strtolower(trim($value));
                        }
                        $emails = $tmp;
                    }

                    if (!empty($accountList)) {
                        $accountDeap = [];
                        foreach ($accountList as $account) {
                            $email = strtolower(trim($account->email));
                            if (empty($email)) {
                                continue;
                            }
                            if (!in_array($email, $emails)) {
                                continue;
                            }
                            if (in_array($email, $accountDeap)) {
                                continue;
                            }
                            $accountDeap[] = $email;
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_MESSAGE;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::TASK_TYPE_MESSAGE,
                    'platform_code' => Platform::PLATFORM_CODE_WALMART,
                ], 10);

                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        $accountInfo = Account::findById($accountId);
                        if (!empty($accountInfo) && !empty($accountInfo->email)) {
                            //获取当前可用并且正在使用的服务器地址
                            $monitor = ServerEmailMonitor::findOne(['is_enable' => 1, 'is_use' => 1, 'status' => 1]);
                            if (!empty($monitor)) {
                                $url = 'http://' . $monitor->server_address . '/services/walmart/walmart/mail';
                                VHelper::throwTheader($url, ['email' => trim($accountInfo->email)], 'GET', 1200);
                            } else {
                                VHelper::throwTheader('/services/walmart/walmart/mail', ['email' => trim($accountInfo->email)], 'GET', 1200);
                            }
                            sleep(self::WAIT_SECONDS);
                        }
                    }
                }
                die('RUN GET MAIL');
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }

    /**
     * 标记邮件
     */
    public function actionMark()
    {
        $inboxid = Yii::$app->request->get('inboxid');
        $msgtype = Yii::$app->request->get('msgtype');

        $row = WalmartInbox::find()
            ->select(['mid', 'receive_email'])
            ->where(['id' => $inboxid])
            ->one();

        if (empty($row)) {
            throw new \Exception("Can Not found the record with inboxid $inboxid", 1);
        }

        $map = [
            '1' => '\\Seen',
            '2' => '\\Answered'
        ];

        if (!isset($map[$msgtype])) {
            throw new \Exception("Error msgtype", 1);
        }

        $status = WalmartMail::instance($row['receive_email'])->mailbox->setFlag([$row['mid']], $map[$msgtype]);
        //更新状态
        $row->is_read = WalmartInbox::READ_STATUS_READ;
        $row->save(false);
        echo $status ? 'Success' : 'Failure';
    }
}