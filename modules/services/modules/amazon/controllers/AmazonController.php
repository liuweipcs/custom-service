<?php

namespace app\modules\services\modules\amazon\controllers;

/**
 * @author mrlin <714480119@qq.com>
 * @package ~
 */

use Yii;
use app\modules\mails\models\AmazonMailOutbox;
use app\modules\orders\models\OrderAmazonSearch;
use app\modules\systems\models\Email;
use app\modules\systems\models\ServerEmailMonitor;
use yii\helpers\Url;
use app\common\VHelper;
use yii\web\Controller;
use yii\web\HttpException;
use app\modules\services\modules\amazon\components\MailConfig;
use app\modules\services\modules\amazon\components\MailBox;
use app\modules\services\modules\amazon\components\Mail;
use app\modules\services\modules\amazon\components\Refund;
use app\modules\services\modules\amazon\models\AmazonMWS;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\mails\models\AmazonTaskQueue;
use app\modules\mails\models\AmazonInbox;
use app\modules\accounts\models\UserAccount;

class AmazonController extends Controller
{

    /**
     * @constance int
     *
     * sleep seconds
     */
    const WAIT_SECONDS = 2;

    /**
     * 拉取邮件
     */
    public function actionMail()
    {
        //exit('MAINTAINING');
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
                $mailbox = new MailBox(MailConfig::fetchImapConfig($email));
                //拉取邮件
                $mailbox->processMail($email, $days);
                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_AMAZON, AccountTaskQueue::TASK_TYPE_MESSAGE);
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    $accountInfo = Account::findById($nextAccountId);
                    if (!empty($accountInfo) && !empty($accountInfo->email)) {
                        //获取当前可用并且正在使用的服务器地址
                        $monitor = ServerEmailMonitor::findOne(['is_enable' => 1, 'is_use' => 1, 'status' => 1]);
                        if (isset($_REQUEST['is_debug'])) {
                            echo '<pre>拉邮件服务器: ';
                            var_dump($monitor->server_address);
                            echo '</pre><br/>========<br/>';
                        }
                        if (!empty($monitor)) {
                            $url = 'http://' . $monitor->server_address . '/services/amazon/amazon/mail';
                            VHelper::throwTheader($url, ['email' => trim($accountInfo->email)], 'GET', 1200);
                        } else {
                            VHelper::throwTheader('/services/amazon/amazon/mail', ['email' => trim($accountInfo->email)], 'GET', 1200);
                        }
                    }
                }
                //结束拉取信息
                die('GET MAIL END');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()->where([
                    'platform_code' => Platform::PLATFORM_CODE_AMAZON,
                    'type' => AccountTaskQueue::TASK_TYPE_MESSAGE
                ])->count();

                //如果任务队列中没有任务了才可继续添加任务，避免任务堆积。
                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_AMAZON, Account::STATUS_VALID);

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
                    'platform_code' => Platform::PLATFORM_CODE_AMAZON,
                ], 15);

                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        $accountInfo = Account::findById($accountId);
                        if (!empty($accountInfo) && !empty($accountInfo->email)) {
                            //获取当前可用并且正在使用的服务器地址
                            $monitor = ServerEmailMonitor::findOne(['is_enable' => 1, 'is_use' => 1, 'status' => 1]);
                            if (!empty($monitor)) {
                                $url = 'http://' . $monitor->server_address . '/services/amazon/amazon/mail';
                                VHelper::throwTheader($url, ['email' => trim($accountInfo->email)], 'GET', 1200);
                            } else {
                                VHelper::throwTheader('/services/amazon/amazon/mail', ['email' => trim($accountInfo->email)], 'GET', 1200);
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
     * Send Mail
     *
     * \param: from    string required
     * \param: to      string required
     * \param: subject string required
     * \param: body    string required
     *
     * @noreturn
     */
    public function actionSendmail()
    {
        if (!Yii::$app->request->getIsPost())
            throw new HttpException(400, 'Wrong Http Method');

        $from = trim(Yii::$app->request->post('from'));
        $to = trim(Yii::$app->request->post('to'));
        $subject = Yii::$app->request->post('subject');
        $body = Yii::$app->request->post('body');

        if (!$from || !$to || !$subject || !$body)
            throw new HttpException(400, 'Parameter Must Required');
        if (Mail::instance($from)
            ->setTo($to)
            ->addAttach(Yii::$app->basePath . '/web/attachments/reply/20170525/amazon_xsd.txt_22028c5f1ab6cb32.txt')
            ->setSubject($subject)
            ->seHtmlBody($body)
            ->sendmail())
            echo 'Success';
        else
            echo 'Failure';
    }

    /**
     * Mark an email
     *
     * inboxid : ??
     * status 1:read 2:replied
     *
     * @noreturn
     */
    public function actionMark()
    {
        $inboxid = Yii::$app->request->get('inboxid');
        $msgtype = Yii::$app->request->get('msgtype');

        // fetch the register email
        $row = (new \yii\db\Query())
            ->select(['mid', 'receive_email'])
            ->from('{{%amazon_inbox}}')
            ->where(['id' => $inboxid])
            ->one();

        if (empty($row))
            throw new \Exception("Can Not found the record with inboxid $inboxid", 1);

        $map = [
            '1' => '\\Seen',
            '2' => '\\Answered'
        ];

        if (!isset($map[$msgtype]))
            throw new \Exception("Error msgtype", 1);

        $status = MailBox::instance($row['receive_email'])->mailbox->setFlag([$row['mid']], $map[$msgtype]);
        //更新状态
        AmazonInbox::updateAll(["is_read" => AmazonInbox::READ_STATUS_READ], "id = " . (int)$inboxid);
        echo $status ? 'Success' : 'Failure';
    }

    /**
     * get feedback list
     *
     * @noreturn
     */
    public function actionFeedback()
    {
        set_time_limit(0);

        try {
            if (isset($_REQUEST['accountId'])) {
                $accountId = Yii::$app->request->get('accountId');
                $account = Account::findOne($accountId);
                AmazonMWS::getFeedBack($accountId);

                $task = AmazonTaskQueue::getNextTask(AmazonTaskQueue::FEEDBACK);

                if (!empty($task)) {
                    $task->delete();

                    $accountInfo = Account::findById($task->account_id);
                    if (empty($accountInfo))
                        exit('Account Not Exists');

                    VHelper::throwTheader('/services/amazon/amazon/feedback', ['accountId' => $accountInfo->id], 'GET', 1200);
                    /*                    $url = sprintf('%s/services/amazon/amazon/feedback?accountId=%s', Yii::$app->request->hostInfo, $task->account_id);
                      VHelper::curl_post_async($url); */
                }
                exit('DONE');
            } else {
                $list = AmazonTaskQueue::findByType(AmazonTaskQueue::FEEDBACK);

                if (empty($list)) {
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_AMAZON, Account::STATUS_VALID);
                    if (!empty($accountList)) {
                        $list = [];

                        foreach ($accountList as $account) {

                            $model = new AmazonTaskQueue();
                            $model->account_id = $account->id;
                            $model->type = AmazonTaskQueue::FEEDBACK;
                            $model->description = 'feed back task';
                            $model->create_date = date('Y-m-d H:i:s', time());

                            $flag = $model->save(false);
                            if ($flag)
                                $list[] = $model;
                        }
                    }
                }

                $taskList = AmazonTaskQueue::getTaskList($list, 10);

                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        $accountInfo = Account::findById($accountId);
                        if (empty($accountInfo))
                            exit('Account Not Exists');

                        VHelper::throwTheader('/services/amazon/amazon/feedback', ['accountId' => $accountId], 'GET', 1200);
                        /*                       $url = sprintf('%s/services/amazon/amazon/feedback?accountId=%s', Yii::$app->request->hostInfo, $accountId);
                          VHelper::curl_post_async($url); */
                        sleep(self::WAIT_SECONDS);
                    }
                }
                exit('DONE');
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo $e->getFile();
            echo $e->getLine();
        }
    }

    /**
     * get request_id
     *
     * @noreturn
     */
    public function actionGetrequest()
    {
        set_time_limit(0);

        try {
            if (isset($_REQUEST['accountId'])) {

                $reportType = '_GET_SELLER_FEEDBACK_DATA_';
                $fromDate = '-15 days';
                $accountId = Yii::$app->request->get('accountId');
                $account = Account::findOne($accountId);

                AmazonMWS::getRequestIds($account->old_account_id, $reportType, $fromDate);

                $task = AmazonTaskQueue::getNextTask(AmazonTaskQueue::GETREQUEST);

                if (!empty($task)) {
                    $task->delete();
                    $accountInfo = Account::findById($task->account_id);
                    if (empty($accountInfo))
                        exit('Account Not Exists');
                    VHelper::throwTheader('/services/amazon/amazon/getrequest', ['accountId' => $accountInfo->id], 'GET', 1200);
                    /*                    $url = sprintf('%s/services/amazon/amazon/getrequest?accountId=%s', Yii::$app->request->hostInfo, $task->account_id);
                      VHelper::curl_post_async($url); */
                }
                exit('RUN GET REPORT');
            } else {
                $list = AmazonTaskQueue::findByType(AmazonTaskQueue::GETREQUEST);
                if (empty($list)) {
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_AMAZON, Account::STATUS_VALID);
                    if (!empty($accountList)) {
                        $list = [];

                        foreach ($accountList as $account) {
                            $model = new AmazonTaskQueue();
                            $model->account_id = $account->id;
                            $model->type = AmazonTaskQueue::GETREQUEST;
                            $model->description = 'feed back task';
                            $model->create_date = date('Y-m-d H:i:s', time());

                            $flag = $model->save(false);
                            if ($flag)
                                $list[] = $model;
                        }
                    }
                }
                $taskList = AmazonTaskQueue::getTaskList($list, 10);

                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        $accountInfo = Account::findById($accountId);
                        if (empty($accountInfo))
                            exit('Account Not Exists');

                        VHelper::throwTheader('/services/amazon/amazon/getrequest', ['accountId' => $accountId], 'GET', 1200);
                        /*                        $url = sprintf('%s/services/amazon/amazon/getrequest?accountId=%s', Yii::$app->request->hostInfo, $accountId);
                          VHelper::curl_post_async($url); */

                        sleep(self::WAIT_SECONDS);
                    }
                }
                exit('RUN GET REPORT');
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo $e->getFile();
            echo $e->getLine();
        }
    }

    /**
     * get feedback list  获取多个accountName情况
     *
     * @noreturn
     */
    public function actionGetfeedback()
    {
        set_time_limit(0);

        if (isset($_REQUEST['id'])) {
            $accountId = Yii::$app->request->get('id');

            $account = Account::findOne($accountId);
            if (!empty($account)) {
                AmazonMWS::getFeedBackData($account->account_name);
            }

            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_AMAZON, AccountTaskQueue::TASK_TYPE_FEEDBACK);

            //如果不为空，则请求下一个接口
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/amazon/amazon/getfeedback', ['id' => $nextAccountId]);
            }
        } else {
            //只能查询到客服绑定账号的纠纷
            $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_AMAZON);

            if (!empty($accountIds)) {
                foreach ($accountIds as $accountId) {
                    $task = AccountTaskQueue::findOne([
                        'account_id' => $accountId,
                        'type' => AccountTaskQueue::TASK_TYPE_FEEDBACK,
                        'platform_code' => Platform::PLATFORM_CODE_AMAZON
                    ]);

                    if (!empty($task)) {
                        continue;
                    }

                    $model = new AccountTaskQueue();
                    $model->account_id = $accountId;
                    $model->type = AccountTaskQueue::TASK_TYPE_FEEDBACK;
                    $model->platform_code = Platform::PLATFORM_CODE_AMAZON;
                    $model->create_time = date('Y-m-d H:i:s');
                    $model->save();
                }

                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::TASK_TYPE_FEEDBACK,
                    'platform_code' => Platform::PLATFORM_CODE_AMAZON,
                ]);

                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/amazon/amazon/getfeedback', ['id' => $accountId]);
                        sleep(self::WAIT_SECONDS);
                    }
                }
                die('RUN GET AMAZON FEEDBACK');
            }
        }
    }

    /**
     * get fba return
     *
     * @noreturn
     */
    public function actionFbareturn()
    {
        set_time_limit(0);

        if (isset($_REQUEST['account_name'])) {
            $accountName = Yii::$app->request->get('account_name');
            AmazonMWS::getFBAReturn($accountName);

            $task = AmazonTaskQueue::getNextTask(AmazonTaskQueue::FBA_RETURNS);

            if (!empty($task)) {
                $task = $task->account_id;
                $task->delete();

                $accountInfo = Account::findById($accountId);
                if (empty($accountInfo))
                    exit('Account Not Exists');

                VHelper::throwTheader('/services/amazon/amazon/fbareturn', ['account_name' => $accountInfo->account_name]);
            }
            exit('DONE');
        } else {
            $list = AmazonTaskQueue::findByType(AmazonTaskQueue::FBA_RETURNS);

            if (empty($list)) {
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_AMAZON, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    $accountDeap = [];
                    $list = [];

                    foreach ($accountList as $account) {
                        if (in_array($account->email, $accountDeap))
                            continue;

                        $accountDeap[] = $account->email;

                        $model = new AmazonTaskQueue();
                        $model->account_id = $account->id;
                        $model->type = AmazonTaskQueue::FBA_RETURNS;
                        $model->description = 'fba return task';
                        $model->create_date = date('Y-m-d H:i:s', time());

                        $flag = $model->save();

                        if ($flag)
                            $list[] = $model;
                    }
                }
            }

            $taskList = AmazonTaskQueue::getTaskList($list);

            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    $accountInfo = Account::findById($accountId);
                    if (empty($accountInfo))
                        exit('Account Not Exists');

                    VHelper::throwTheader('/services/amazon/amazon/fbareturn', ['account_name' => $accountInfo->account_name]);
                    sleep(self::WAIT_SECONDS);
                }
            }
            exit('DONE');
        }
    }

    /**
     * refund interface
     *
     * @noreturn
     */
    public function actionRefund()
    {

        Refund::httpReqeust('xuuyuu', '1234', '1234', Refund::CUSTOMER_RETURN, [
            [Refund::PRINCIPAL, 12.6, 'USD'],
            [Refund::COD, 2.6, 'USD'],
        ], '1234');
    }

    /**
     * 获取amazon 部分发信数据
     * @throws \yii\db\Exception
     */
    public function actionGetamazonorderemail()
    {
        //读取txt  获取account_name subject content
        $dir = Yii::getAlias('@webroot') . '/' . 'uploads' . '/' . 'amazonemail';
//        $file_arr   = VHelper::read_all($dir);
        $begin_date = Yii::$app->request->get('begin_date');
        $end_date = Yii::$app->request->get('end_date');
        //if (empty($begin_date)) {
        $begin_date = $begin_date . ' 00:00:00';
        //}
        //if (empty($end_date)) {
        $end_date = $end_date . ' 23:59:59';
        //}
        ini_set('max_execution_time', '0');

        $order_email = OrderAmazonSearch::find()->from('{{%order_amazon}}')
            ->select('account_id,email,order_id,platform_order_id,shipped_date')
            //->andWhere(['account_id' => $old_account_id])
            ->andWhere(['between', 'shipped_date', $begin_date, $end_date])
            ->asArray()
            ->all();
        if (!empty($order_email)) {
            //查询所有账号
            $sql = '';
            $accountList = Account::find()->select('id,account_name,old_account_id,email')->where(['status' => 1, 'platform_code' => 'AMAZON'])->asArray()->all();
            if (!empty($accountList)) {
                foreach ($accountList as $k => $account) {
                    foreach ($order_email as $key => $order) {
                        if ($order['account_id'] == $account['old_account_id']) {
                            $order_email[$key]['account_name'] = $account['account_name'];
                            $order_email[$key]['send_email'] = $account['email'];
                            $order_email[$key]['id'] = $account['id'];
                        }
                    }
                }

                $sql = "REPLACE INTO {{%amazon_mail_outbox}} (
                        `platform_code`,
                        `account_id`, 
                        `subject`,
                        `content`, 
                        `order_id`,
                        `platform_order_id`,
                        `receive_email`,
                        `send_status`,
                        `send_params`, 
                        `create_by`,
                        `create_time`,
                        `modify_by`,
                        `modify_time`,
                        `shipped_date`
                        ) VALUES ";

                foreach ($order_email as $k => $val) {
//                    if($k < 100){
                    $email_subject_coutent = '';
                    $explode = [];
                    $url = Yii::getAlias('@webroot') . '/' . 'uploads' . '/' . 'amazonemail/' . $val['account_name'] . '.txt';
                    if (!file_exists($url)) {
                        continue;
                    }
                    $email_subject_coutent = file_get_contents($url);

                    $explode = explode('=', $email_subject_coutent);
                    $subject = !empty($explode) ? trim(current($explode)) : '';
                    $content = !empty($explode) ? trim(end($explode)) : '';
                    $send_email = $val['send_email']; //邮件发送人
                    $receive_email = $val['email']; //收件发送人
                    $status = 0; //待发送
                    $platform_code = Platform::PLATFORM_CODE_AMAZON;
                    $old_account_id = $val['id'];
                    $send_params = json_encode(['sender_email' => $send_email, 'receive_email' => $receive_email]);
                    $create_by = 'system';
                    $modify_by = 'system';
                    $create_time = $modify_time = date('Y-m-d H:i:s');
                    if (empty($receive_email) || empty($send_email) || empty($subject) || empty($content)) {
                        $status = 4; //无需发送[数据不全]
                    }

                    $sql .= '("' . $platform_code . '","' . $old_account_id . '","' . $subject . '","' . $content . '","' . $val['order_id'] . '","' . $val['platform_order_id'] . '","' . $val['email'] . '","'
                        . '' . $status . '",' . "'" . $send_params . "'" . ',"' . $create_by . '","' . $create_time . '","' . $modify_by . '","' . $modify_time . '","' . $val['shipped_date'] . '"),';

                }

                $sql = rtrim($sql, ',');
                if (!empty($sql)) {
                    $result = Yii::$app->db->createCommand($sql)->execute();
                }
                if ($result !== false) {
                    echo "account id : {$old_account_id} pull email list success\n";
                } else {
                    echo "account id : {$old_account_id}  pull email list error\n";
                }
            }
        }
    }

}
