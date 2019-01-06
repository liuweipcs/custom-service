<?php

namespace app\modules\services\modules\walmart\models;

use app\modules\accounts\models\Platform;
use Yii;
use app\modules\mails\models\WalmartInboxTmp;
use app\modules\accounts\models\Account;
use app\modules\systems\models\Email;
use app\modules\services\modules\amazon\components\MailConfig;
use app\modules\mails\models\AmazonMailList;

class WalmartMail
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

    public function __construct($config = [])
    {
        $this->config = $config;
        $this->init();
    }

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

        if (strpos($this->config['emailaddress'], 'outlook.com') !== false ||
            strpos($this->config['emailaddress'], 'hotmail.com') !== false) {
            //针对微软系的邮箱，默认设置US-ASCII字符集
            $this->mailbox = new \PhpImap\Mailbox($dsn, $this->config['emailaddress'], $accesskey, $attachmentsDir, 'US-ASCII');
        } else {
            $this->mailbox = new \PhpImap\Mailbox($dsn, $this->config['emailaddress'], $accesskey, $attachmentsDir);
        }
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
     * 获取邮件列表
     */
    public function getFolders($ref = '')
    {
        $ref = $ref ? $ref : '{' . $this->config['server'] . '}';

        try {
            $imapStream = $this->mailbox->getImapStream();
            $folders = imap_list($imapStream, $ref, '*');
        } catch (\Exception $e) {
            return [];
        }

        return !empty($folders) ? $folders : [];
    }

    /**
     * @desc 根据邮箱地址确认该邮箱是否进入mongodb
     * @param string $email
     * @return boolean  true表示过滤该邮件，false表示不能过滤
     */
    protected function isFilterEmail($email, $accountEmail)
    {
        //如果买家邮箱等于账号邮箱，说明是客服回复的，直接过滤，不用拉进系统
        $email = strtolower(trim($email));
        $accountEmail = strtolower(trim($accountEmail));
        if ($email == $accountEmail) {
            return true;
        }
        return false;
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
     * 获取邮件站点后缀
     */
    protected function getSiteSuffix($email)
    {
        $pos = strrpos($email, '.');
        $pos = $pos === false ? -2 : $pos + 1;
        return substr($email, $pos, 2);
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
            if (empty($this->mailbox)) {
                return false;
            }

            //获取邮件列表
            $folders = $this->getFolders();

            //设置筛选项
            $this->getFilterOption($email);

            //通过邮箱获取账户
            $account = Account::find()->where(['platform_code' => Platform::PLATFORM_CODE_WALMART, 'email' => $email])->one();

            //调试是否正常获取到邮件
            if (isset($_REQUEST['is_debug'])) {
                echo '<pre>';
                print_r($account);
                print_r($folders);
            }

            if (empty($account)) {
                return false;
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
                    if (stripos($rlname, '垃圾') !== false || stripos($rlname, 'Junk') !== false) {
                        $isGarbage = 1;
                    }

                    //切换目录
                    $this->mailbox->switchMailbox($folder);

                    if (stripos($email, 'outlook.com') !== false ||
                        stripos($email, 'hotmail.com') !== false) {
                        $mids = $this->mailbox->searchMailbox('ALL');
                    } else {
                        //1点或7点，拉取前一天所有数据
                        if (date('G') == 1 || date('G') == 7) {
                            $since = $days + 1;
                            $mids = $this->mailbox->searchMailbox('SINCE "' . date('d-M-Y', strtotime('-' . $since . ' days')) . '"');
                        } else {
                            //拉取当天未回复的邮件
                            $mids = $this->mailbox->searchMailbox('UNANSWERED SINCE "' . date('d-M-Y', strtotime('-' . $days . ' days')) . '"');
                        }
                    }

                    //调试是否正常获取到邮件
                    if (isset($_REQUEST['is_debug'])) {
                        echo '<pre>';
                        print_r($mids);
                    }
//                    if($email == 'yarongrose@hotmail.com' || $email == 'lifetravel55@hotmail.com'){
//                        echo '<pre>';
//                        var_dump($mids);
//                        echo '</pre>';
//                        die;
//                    }
                    if (!empty($mids)) {
                        foreach ($mids as $mid) {
                            try {
                                //检查邮件是否拉取过
                                if (AmazonMailList::findOne(['email' => $email, 'folder' => $folder, 'mid' => $mid])) {
                                    continue;
                                }
                                //获取邮件数据
                                $mail = $this->mailbox->getMail($mid);
                                //临时加入，收件时间小于这个时间点的过滤掉
                                if ($mail->date < '2018-08-27 00:00:00') {
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
                                //获取邮件数据
                                $mail = $this->mailbox->getMail($mid);

                                //过滤walmart平台邮件
                                if ($this->isFilterEmail($mail->fromAddress, $email)) {
                                    continue;
                                }

                                //获取邮件信息
                                $info = $this->mailbox->getMailsInfo(array($mid))[0];

                                $walmartInboxTmp = WalmartInboxTmp::findOne(['account_id' => $account->id, 'mid' => $mid]);
                                if (empty($walmartInboxTmp)) {
                                    $walmartInboxTmp = new WalmartInboxTmp();
                                    $walmartInboxTmp->account_id = $account->id;
                                    $walmartInboxTmp->mid = $mid;
                                    $walmartInboxTmp->create_time = date('Y-m-d H:i:s');
                                    $walmartInboxTmp->folder = $folder;
                                }
                                $walmartInboxTmp->mail = json_encode($mail);
                                $walmartInboxTmp->is_read = !empty($info->seen) ? $info->seen : 0;
                                $walmartInboxTmp->is_replied = !empty($info->answered) ? $info->answered : 0;
                                $walmartInboxTmp->is_garbage = $isGarbage;

                                //获取邮件附件
                                $attachments = $mail->getAttachments();
                                if (empty($attachments)) {
                                    $attachments = [];
                                }
                                $walmartInboxTmp->attachments = json_encode($attachments);
                                $walmartInboxTmp->save();
                            } catch (\Exception $e) {
                                //避免拉取邮件出现异常，导致整个程序中断
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }
}