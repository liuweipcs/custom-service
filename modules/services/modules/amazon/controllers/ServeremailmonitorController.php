<?php

namespace app\modules\services\modules\amazon\controllers;

use Yii;
use yii\web\Controller;
use app\components\Curl;
use app\modules\systems\models\Email;
use app\modules\systems\models\ServerEmailMonitor;
use app\modules\systems\models\ServerSwitchLog;

/**
 * 用于监测当前服务器拉取邮件是否可用(避免被屏蔽)
 */
class ServeremailmonitorController extends Controller
{
    //最大错误数量，如果连接错误数量超过6次，则认为该服务器不可用
    const MAX_ERROR_NUM = 6;

    //邮件附件地址(相对于web目录)                                                                                  
    const EMAIL_ATTACHMENT_PATH = 'attachments/';

    //邮件附件备份地址(相对于web目录)
    const EMAIL_ATTACHMENT_COPY_PATH = 'attachments_copy/';

    //发送附件的地址
    const SEND_ATTACHMENT_URL = 'http://kefu.yibainetwork.com/attachment_api.php';

    //客服系统主机名
    public $hostNameArr = [
        'kefu.yibainetwork.com',
        '10.29.70.87',
    ];

    /**
     * 监测服务器状态
     */
    public function actionMonitor()
    {
        set_time_limit(0);

        //打开所有错误
        error_reporting(E_ALL);
        ini_set('display_errors', true);

        try {
            //随机的获取10个邮箱配置，来进行监测
            $emailConfigs = Email::find()
                ->andWhere(['in', 'platform_code', ['AMAZON', 'WALMART']])
                ->andWhere([
                    'or',
                    ['like', 'emailaddress', '163.com'],
                    ['like', 'emailaddress', '126.com'],
                ])
                ->orderBy('RAND()')
                ->limit(10)
                ->asArray()
                ->all();

            //所有错误信息
            $errors = [];
            //所有错误数量
            $errorNum = 0;

            if (!empty($emailConfigs)) {
                foreach ($emailConfigs as $emailConfig) {
                    try {
                        //imap服务器地址
                        $server = trim($emailConfig['imap_server']);
                        //imap端口
                        $port = trim($emailConfig['imap_port']);
                        //imap协议
                        $protocol = trim($emailConfig['imap_protocol']);

                        if (!empty($emailConfig['ssl'])) {
                            $mailbox = sprintf('{%s:%d/%s/%s}INBOX', $server, $port, $protocol, 'ssl');
                        } else {
                            $mailbox = sprintf('{%s:%d/%s}INBOX', $server, $port, $protocol);
                        }

                        //邮件地址
                        $username = trim($emailConfig['emailaddress']);
                        $password = !empty($emailConfig['accesskey']) ? trim($emailConfig['accesskey']) : trim($emailConfig['password']);

                        //连接imap服务器
                        $result = imap_open($mailbox, $username, $password);
                        if (!empty($result)) {
                            $errors[] = "{$username}连接imap服务器成功\n";

                            echo "{$username}连接imap服务器成功<br>";
                        } else {
                            $errors[] = "{$username}连接imap服务器错误,错误信息:" . imap_last_error();
                            $errorNum++;

                            echo "{$username}连接imap服务器错误,错误信息:" . imap_last_error() . "<br>";
                        }
                    } catch (\Exception $e) {
                        $errors[] = "{$username}连接imap服务器错误,错误信息:" . $e->getMessage();
                        $errorNum++;

                        echo "{$username}连接imap服务器错误,错误信息:" . $e->getMessage() . "<br>";
                    }

                    //防止有可能出现的Fatal error没有被异常捕获到
                    //这地方还是有点问题的，如果出现Fatal error这种错误，虽然能捕获，但是程序无法继续执行
                    //php7中可以作为异常处理，被捕获，然后继续执行下去。php5可以捕获，但是不能继续执行
                    $errorArr = error_get_last();
                    if (!empty($errorArr)) {
                        $errors[] = "{$username}连接imap服务器错误,错误信息:" . $errorArr['message'];
                        $errorNum++;

                        echo "{$username}连接imap服务器错误,错误信息:" . $errorArr['message'] . "<br>";
                    }

                    if (!empty($result) && is_resource($result)) {
                        imap_close($result);
                    }
                    usleep(200);
                }
            }

            //获取当前服务器的主机名
            $hostName = Yii::$app->request->hostName;
            $monitor = ServerEmailMonitor::findOne(['server_address' => $hostName, 'status' => 1]);
            if (empty($monitor)) {
                $monitor = new ServerEmailMonitor();
                $monitor->is_use = 0;
                $monitor->create_by = 'system';
                $monitor->create_time = date('Y-m-d H:i:s');

                //判断错误数量
                if ($errorNum < self::MAX_ERROR_NUM) {
                    $monitor->is_enable = 1;
                } else {
                    $monitor->is_enable = 0;
                }
            } else {
                //判断错误数量
                if ($errorNum < $monitor->max_error_num) {
                    $monitor->is_enable = 1;
                } else {
                    $monitor->is_enable = 0;
                }
            }

            $monitor->server_address = $hostName;
            $monitor->errors = json_encode($errors, JSON_UNESCAPED_UNICODE);
            $monitor->modify_by = 'system';
            $monitor->modify_time = date('Y-m-d H:i:s');
            $monitor->save();

        } catch (\Exception $e) {
            //防止出现的异常中断程序
            echo $e->getMessage();
        }

        die('SERVIER EMAIL MONITOR END');
    }

    /**
     * 切换服务器
     */
    public function actionSwitchserver()
    {
        //获取当前服务器是否可用
        $hostName = Yii::$app->request->hostName;
        $monitor = ServerEmailMonitor::findOne(['server_address' => $hostName, 'status' => 1]);
        if (!empty($monitor)) {
            //如果当前服务不可用，则设置其他服务器为使用状态
            if (empty($monitor->is_enable)) {
                $enableMonitors = ServerEmailMonitor::find()
                    ->andWhere(['is_enable' => 1])
                    ->andWhere(['status' => 1])
                    ->all();

                if (!empty($enableMonitors)) {
                    //随机获取一个可用的服务器
                    $index = mt_rand(0, count($enableMonitors) - 1);
                    if (array_key_exists($index, $enableMonitors)) {
                        $useMonitor = $enableMonitors[$index];
                        if (!empty($useMonitor)) {
                            //将当前服务器使用状态设为0
                            $monitor->is_use = 0;
                            $monitor->save();

                            //将要使用的服务器使用状态设为1
                            $useMonitor->is_use = 1;
                            $useMonitor->save();
                            unset($enableMonitors[$index]);

                            //保存服务器切换日志
                            $now = date('Y-m-d H:i:s');
                            $log = new ServerSwitchLog();
                            $log->from_server = $monitor->server_address;
                            $log->to_server = $useMonitor->server_address;
                            $log->desc = "[{$now}]:{$monitor->server_address}服务不可用, 切换到{$useMonitor->server_address}";
                            $log->create_by = 'system';
                            $log->create_time = $now;
                            $log->save();
                        }
                    }

                    //将其他的服务器使用状态设为0
                    if (!empty($enableMonitors)) {
                        foreach ($enableMonitors as $enableMonitor) {
                            $enableMonitor->is_use = 0;
                            $enableMonitor->save();
                        }
                    }
                }
            } else {
                //如果当前服务器又可用了，则切换回当前服务器
                if (empty($monitor->is_use)) {
                    $monitor->is_use = 1;
                    $monitor->save();

                    $enableMonitors = ServerEmailMonitor::find()
                        ->andWhere(['is_enable' => 1])
                        ->andWhere(['status' => 1])
                        ->andWhere(['<>', 'id', $monitor->id])
                        ->all();

                    if (!empty($enableMonitors)) {
                        foreach ($enableMonitors as $enableMonitor) {
                            if (!empty($enableMonitor->is_use)) {
                                //保存服务器切换日志
                                $now = date('Y-m-d H:i:s');
                                $log = new ServerSwitchLog();
                                $log->from_server = $enableMonitor->server_address;
                                $log->to_server = $monitor->server_address;
                                $log->desc = "[{$now}]:{$enableMonitor->server_address}服务可用, 切换回{$monitor->server_address}";
                                $log->create_by = 'system';
                                $log->create_time = $now;
                                $log->save();
                            }

                            $enableMonitor->is_use = 0;
                            $enableMonitor->save();
                        }
                    }
                }
            }
        }

        die('SWITCH SERVER END');
    }

    /**
     * 同步服务器的附件
     */
    public function actionSendattachment()
    {
        set_time_limit(0);
        ignore_user_abort(true);

        //当前服务器主机名
        $hostName = Yii::$app->request->hostName;
        //获取当前可用并且正在使用的服务器
        $moitor = ServerEmailMonitor::findOne(['is_enable' => 1, 'is_use' => 1, 'status' => 1]);
        if (!empty($moitor)) {
            //如果正在使用的服务器名与当前主机名相同，说明该服务器正在拉取邮件
            if ($moitor->server_address == $hostName) {

                //如果当前主机名不等于客服系统，则需要把附件同步到客服系统
                if (!in_array($hostName, $this->hostNameArr)) {
                    $path = Yii::getAlias('@webroot') . '/' . self::EMAIL_ATTACHMENT_PATH;
                    $copyPath = Yii::getAlias('@webroot') . '/' . self::EMAIL_ATTACHMENT_COPY_PATH;
                    //判断目录是否存在
                    if (file_exists($path)) {
                        //打开目录
                        if ($dh = opendir($path)) {
                            //读取目录文件
                            while (($fileName = readdir($dh)) !== false) {
                                if ($fileName == '.' || $fileName == '..') {
                                    continue;
                                }

                                $filePath = $path . '/' . $fileName;
                                if (!is_file($filePath)) {
                                    continue;
                                }

                                $fileData = file_get_contents($filePath);
                                if (empty($fileData)) {
                                    continue;
                                }
                                $fileData = base64_encode($fileData);
                                //发送数据
                                $sendData = [
                                    'file_name' => $fileName,
                                    'data' => $fileData
                                ];

                                try {
                                    $curl = new Curl();
                                    $sendData = json_encode($sendData);
                                    $result = $curl->post(self::SEND_ATTACHMENT_URL, $sendData);
                                    if (empty($result)) {
                                        continue;
                                    }
                                    $result = json_decode($result);
                                    if ($result->ack) {
                                        echo "{$filePath},发送成功<br>";

                                        if (!file_exists($copyPath)) {
                                            @mkdir($copyPath, 0777, true);
                                            @chmod($copyPath, 0777);
                                        }
                                        //把附件拷贝到copy目录
                                        $copyFilePath = $copyPath . '/' . $fileName;
                                        if (copy($filePath, $copyFilePath)) {
                                            @unlink($filePath);
                                        }
                                    }
                                } catch (\Exception $e) {

                                }
                            }
                        }
                    }
                }
            }
        }

        die('SEND ATTACHMENT END');
    }
}
