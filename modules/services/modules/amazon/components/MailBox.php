<?php
/**
 * @author mrlin <714480119@qq.com>
 * @package ~
 */

namespace app\modules\services\modules\amazon\components;

use app\modules\mails\models\AmazonTask;
use Yii;
use yii\db\Query;
use app\modules\mails\models\AmazonInboxTmp;
use app\modules\mails\models\AmazonInboxAttachment;
use app\modules\accounts\models\Account;
use app\modules\systems\models\Email;
use app\modules\mails\models\AmazonMailList;

class MailBox
{
    /**
     * 配置数组
     */
    public $config = [];

    /**
     * PhpImap对象
     */
    public $mailbox = null;

    /**
     * 过滤的邮件目录
     */
    public $filterFolders = [
        //'INBOX',
        '草稿箱',
        '已发送',
        //'垃圾邮件',
        '已删除',
        //'病毒文件夹',
        //'广告邮件',
        //'订阅邮件',
        '客户端删信',
    ];

    /**
     * 站点map
     */
    public $site = [
        'es' => 'sp',
    ];

    /**
     * $config = [
     *    'server' => '服务器地址',
     *    'port' => 端口号,
     *    'protocol' => '协议',
     *    'ssl' => true,
     *    'emailaddress' => 'email地址',
     *    'accesskey' => '访问私钥',
     *    'attachments_dir' => '附件目录',
     * ]
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->init();
    }

    /**
     * 初始化函数
     */
    public function init()
    {
        if (empty($this->config)) {
            return false;
        }
        //构造连接dsn
        if ($this->config['ssl']) {
            $dsn = sprintf('{%s:%d/%s/%s}INBOX', $this->config['server'], $this->config['port'], $this->config['protocol'], 'ssl');
        } else {
            $dsn = sprintf('{%s:%d/%s}INBOX', $this->config['server'], $this->config['port'], $this->config['protocol']);
        }
        //附件目录
        $attachmentsDir = empty($this->config['attachments_dir']) ? Yii::$app->basePath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'attachments' : $this->config['attachments_dir'];

        if (!is_dir($attachmentsDir)) {
            @mkdir($attachmentsDir, 0777, true);
        }
        //创建PhpImap对象
        $accesskey = !empty($this->config['accesskey']) ? $this->config['accesskey'] : $this->config['password'];
        $this->mailbox = new \PhpImap\Mailbox($dsn, $this->config['emailaddress'], $accesskey, $attachmentsDir);
    }

    /**
     * 返回一个实例
     */
    public static function instance($email)
    {
        return new self(MailConfig::fetchImapConfig($email));
    }

    /**
     * 获取筛选项
     */
    public function getFilterOption($email)
    {
        $model = new Email();
        $filterOptions = $model->getFilterOption($email);

        if (!empty($filterOptions)) {
            $this->filterFolders = array_merge($this->filterFolders, $filterOptions);
        }
    }

    /**
     * 拉取邮件
     */
    public function processMail($email, $days = 1)
    {
        if (isset($_REQUEST['is_debug'])) {
            var_dump($this->mailbox->getImapStream());
        }

        try {
            $task = AmazonTask::createTask($email, 'AmazonMail');

            if (AmazonTask::checkIsRunning($email, 'AmazonMail')) {
                $task->status = -1;
                $task->error = '已经有该任务在运行中';
                $task->save();
                return false;
            }

            if (empty($this->mailbox)) {
                $task->status = -1;
                $task->error = 'phpimap对象为空';
                $task->save();
                return false;
            }

            //捕获Fatal Error的错误
            register_shutdown_function(function($task) {
                $errors = error_get_last();
                if (!empty($errors) && !empty($task)) {
                    $task->status = -1;
                    $task->error = $errors['message'];
                    $task->save();
                }
            }, $task);

            //获取邮件列表
            $folders = $this->getFolders();
            //设置筛选项
            $this->getFilterOption($email);
            //通过邮箱获取账户列表
            $accountList = Account::getAccountsByEmail($email);

            if (empty($accountList)) {
                $task->status = -1;
                $task->error = '账户列表为空';
                $task->save();
                return false;
            }

            $task->status = 1;
            $task->save();

            //构造站点code对应的账号id数组
            $accounts = [];
            foreach ($accountList as $account) {
                if ($account->site_code == 'us') {
                    $accounts['co'] = $account->id;
                } else if ($account->site_code == 'sp') {
                    $accounts['es'] = $account->id;
                }
                $accounts[$account->site_code] = $account->id;
            }

            $email = strtolower($email);
            $count = 0;

            if (isset($_REQUEST['is_debug'])) {
                echo '<pre>';
                print_r($folders);
            }

            if (!empty($folders)) {
                foreach ($folders as $index => $folder) {
                    $rlname = $this->convertEncoding($folder);
                    //判断是否过滤该目录
                    if (!empty($this->filterFolders)) {
                        $isFilter = false;
                        foreach ($this->filterFolders as $filterFolder) {
                            if (stripos($rlname, $filterFolder) !== false) {
                                $isFilter = true;
                            }
                        }
                        if ($isFilter) {
                            continue;
                        }
                    }

                    //判断是否是垃圾邮件
                    $isGarbage = 0;
                    if (stripos($rlname, '垃圾') !== false) {
                        $isGarbage = 1;
                    }

                    //切换目录
                    $this->mailbox->switchMailbox($folder);

                    //hotmail,outlook邮箱按正常条件无法获取到邮件，就拉取所有的邮件
                    if (stripos($email, '@outlook') !== false ||
                        stripos($email, '@hotmail') !== false) {
                        $mids = $this->mailbox->searchMailbox('ALL');
                    } else {
                        //1点或7点拉取前一天所有数据，其他时间拉取未回复的
                        if (date('G') == 1 || date('G') == 7) {
                            $since = $days + 1;
                            $mids = $this->mailbox->searchMailbox('SINCE "' . date('d-M-Y', strtotime('-' . $since . ' days')) . '"');
                        } else {
                            //拉取当天未回复的邮件
                            $mids = $this->mailbox->searchMailbox('UNANSWERED SINCE "' . date('d-M-Y', strtotime('-' . $days . ' days')) . '"');
                        }
                    }

                    if (isset($_REQUEST['is_debug'])) {
                        print_r($mids);
                    }

                    foreach ($mids as $mid) {
                        try {
                            //检查邮件是否拉取过
                            if (AmazonMailList::findOne(['email' => $email, 'folder' => $folder, 'mid' => $mid])) {
                                continue;
                            }
                            //获取邮件数据
                            $mail = $this->mailbox->getMail($mid);

                            //临时加入，收件时间小于这个时间点的过滤掉
                            if ($mail->date < '2018-08-10 00:00:00') {
                                $mailList = AmazonMailList::findOne(['email' => $email, 'folder' => $folder, 'mid' => $mid]);
                                if (empty($mailList)) {
                                    //将邮件ID插入到已拉取的列表
                                    $amazonMailList = new AmazonMailList;
                                    $amazonMailList->email = $email;
                                    $amazonMailList->folder = $folder;
                                    $amazonMailList->mid = $mid;
                                    $amazonMailList->create_time = date('Y-m-d H:i:s');
                                    $amazonMailList->save();
                                }
                                continue;
                            }
                            //过滤amazon平台邮件
                            if ($this->isFilterEmail($mail->fromAddress)) {
                                continue;
                            }

                            //获取邮件信息
                            $info = $this->mailbox->getMailsInfo(array($mid))[0];
                            //获取邮件站点后缀
                            $siteCode = $this->getSiteSuffix($mail->fromAddress);

                            $accountId = array_key_exists($siteCode, $accounts) ? $accounts[$siteCode] : 0;
                            //匹配不到账号，指定这个邮箱下面的任意一个账号上
                            if (empty($accountId)) {
                                $accountId = current($accounts);
                            }
                            $amazonInboxTmp = AmazonInboxTmp::findOne(['account_id' => $accountId, 'mid' => $mid]);
                            if (empty($amazonInboxTmp)) {
                                $amazonInboxTmp = new AmazonInboxTmp();
                                $amazonInboxTmp->account_id = $accountId;
                                $amazonInboxTmp->mid = $mid;
                                $amazonInboxTmp->folder = $folder;
                                $amazonInboxTmp->create_time = date('Y-m-d H:i:s');
                            }
                            $amazonInboxTmp->mail = json_encode($mail);
                            $amazonInboxTmp->is_read = !empty($info->seen) ? $info->seen : 0;
                            $amazonInboxTmp->is_replied = !empty($info->answered) ? $info->answered : 0;
                            $amazonInboxTmp->is_garbage = $isGarbage;

                            //获取邮件附件
                            $attachments = $mail->getAttachments();
                            if (empty($attachments)) {
                                $attachments = [];
                            }
                            $amazonInboxTmp->attachments = json_encode($attachments);
                            $amazonInboxTmp->save();
                            $count++;
                        } catch (\Exception $e) {
                            //避免有个别邮件拉取出现异常，阻塞其他邮件

                        }
                    }
                }
            }

            $task->status = 2;
            $task->affected_rows = $count;
            $task->end_time = date('Y-m-d H:i:s');
            $task->save();
        } catch (\Exception $e) {
            $task->status = -1;
            $task->error = $e->getMessage();
            $task->end_time = date('Y-m-d H:i:s');
            $task->save();
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }

    /**
     * @desc 根据邮箱地址确认该邮箱是否进入mongodb
     * @param string $email
     * @return boolean  true表示过滤该邮件，false表示不能过滤
     */
    protected function isFilterEmail($email)
    {
        //如果邮箱是onlineselling.compliance@hmrc.gsi.gov.uk 不过滤
        if($email == 'onlineselling.compliance@hmrc.gsi.gov.uk'){
            return FALSE;
        }
        //邮箱包含 HMRC Joint Liability Letter、Pre Joint Liability Letter、JSL、Pre JSL都不过滤
        if((stripos($email, 'HMRC Joint Liability Letter') !== false) || (stripos($email, 'Pre Joint Liability Letter') !== false) || (stripos($email, 'JSL') !== false) || (stripos($email, 'Pre JSL') !== false)){
            return FALSE;
        }
        
        if (stripos($email, 'amazon') === false &&
            stripos($email, 'postmaster') === false) {
            return true;
        }
        if (stripos($email, 'non-rispondere') !== false) {
            return true;
        }
        if (stripos($email, 'do-not-reply') !== false) {
            return true;
        }
        if (stripos($email, 'merch.service') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Add the flag \Seen to a mail
     *
     * @param  int $inboxid
     *
     * @return boolean
     */
    public function markMailAsRead($inboxid)
    {
        $row = AmazonInbox::find()
            ->select('mid')
            ->where(['id' => $inboxid])
            ->one();

        if (empty($row)) return false;

        $this->mailbox->markMailAsRead($row['mid']);

        return true;
    }

    /**
     * Add the flag \Answered to a mail
     *
     * @param  int $inboxid
     *
     * @return boolean
     */
    public function markMailAsAnswered($inboxid)
    {
        //fetch an email mid
        $row = AmazonInbox::find()
            ->select('mid')
            ->where(['id' => $inboxid])
            ->one();

        if (empty($row)) return false;

        return $this->mailbox->setFlag([$row['mid']], '\\Answered');
    }

    /**
     * 获取邮件列表
     */
    public function getFolders($ref = '')
    {
        $ref = $ref ? $ref : '{' . $this->config['server'] . '/' . $this->config['protocol'] . '}';
        try {
            $imapStream = $this->mailbox->getImapStream();
            $folders = imap_list($imapStream, $ref, '*');
        } catch (\Exception $e) {
            return [];
        }

        return $folders ? $folders : [];
    }

    /**
     * Convert email folder string
     *
     * @param  string $str
     * @return string
     */
    public function convertEncoding($str)
    {
        if (function_exists('mb_convert_encoding')) {
            $str = mb_convert_encoding($str, 'UTF-8', 'UTF7-IMAP');
        } else {
            $str = imap_utf7_decode($str);
        }

        return $str;
    }

    /**
     * Compare data
     *
     * @param  \stdClass $data1
     *
     * @param  \stdClass $data2
     *
     * @return boolean return true indicate that two data is equal, others not equal
     */
    protected function compareBoxStat(\stdClass $data1, \stdClass $data2)
    {
        if ($data1->Nmsgs == $data2->Nmsgs &&
            $data1->Size == $data2->Size) {
            return true;
        }

        return false;
    }

    /**
     * Classify email whether platform or buyer
     *
     * @param  string $sender
     *
     * @return int
     */
    protected function isPlatform($sender)
    {
        if (stristr($sender, 'amazon.com') === false) {
            return 2;
        }

        return 1;
    }

    /**
     * 获取邮件站点后缀
     */
    protected function getSiteSuffix($email)
    {
        $pos = strrpos($email, '.');
        $pos = $pos === false ? -2 : $pos + 1;
        return substr($email, $pos, 2);
    }

    /**
     * Another one ?
     *
     */
    public function getAccountId($email, $site)
    {
        $site = isset($this->site[$site]) ? $this->site[$site] : $site;

        $subQuery = (new Query())
            ->from('{{%system}}.{{%amazon_account}}');

        $row = (new Query())
            ->select([
                'id' => 'a.id',
                'accountname' => 'a.account_name',
            ])
            ->from('{{%account}} a')
            ->leftJoin(['b' => $subQuery], 'a.account_short_name = b.short_name')
            ->where('a.email=:email and a.platform_code=:code and b.site=:site', [
                ':email' => $email,
                ':code' => 'AMAZON',
                ':site' => $site,
            ])
            ->limit(1)
            ->one();

        return $row ? $row['id'] : '';
    }

    /**
     * Get Account Id By register email and site name
     *
     * @param  string $email
     *
     * @param  string $site
     *
     * @return string
     */
    public function getAccountIdBak($email, $site)
    {
        $site = isset($this->site[$site]) ? $this->site[$site] : $site;

        $subQuery1 = (new Query())
            ->from('{{%system}}.{{%amazon_account}}');
        $subQuery2 = (new Query())
            ->from('{{%crm}}.{{%platform}}');

        $row = (new Query())
            ->select([
                'id' => 'b.id',
                'accountname' => 'b.account_name'
            ])
            ->from('{{%account}} b')
            ->leftJoin(['a' => $subQuery1], 'a.short_name = b.account_short_name')
            ->leftJoin(['c' => $subQuery2], 'b.platform_id = c.id')
            ->where('a.site=:site and b.email=:email and c.platform_code=:code', [
                ':site' => $site,
                ':email' => $email,
                ':code' => 'AMZ',
            ])
            ->limit(1)
            ->one();

        return $row ? $row['id'] : '';
    }

    /**
     * Get order id from email's subject through regress express patten
     *
     * @param  string $subject
     *
     * @return string
     */
    public function getOrderId($subject)
    {
        $id = '';
        preg_replace_callback('/\d{3}-\d{7}-\d{7}/', function ($match) use (&$id) {
            $id = $match[0];
        }, $subject, 1);

        return $id;
    }
}