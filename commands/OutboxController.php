<?php
/**
 * @desc 发送消息计划任务
 */

namespace app\commands;

use app\modules\mails\models\AmazonMailOutbox;
use yii\console\Controller;
use app\modules\mails\models\MailOutbox;
use app\modules\mails\components\MessageSenderAbstract;

class OutboxController extends Controller
{
    public function actionSendmessage($platform_code, $limit = 100, $maxFailureTimes = null, $sortOrder = 'ASC',
                                      $modNumber = null, $modRemain = null)
    {
        $maxFailureTimes = 1;
        if ($maxFailureTimes !== null)
            $maxFailureTimes = (int)$maxFailureTimes;
        if (empty($sortOrder))
            $sortOrder = 'ASC';
        try {
            $outBoxList    = MailOutbox::getWaittingSendList($platform_code, $limit, $maxFailureTimes, $sortOrder,
                $modNumber, $modRemain);
            $messageSender = MessageSenderAbstract::getSender($platform_code);
            if (!$messageSender)
                exit('Invalid Platform Code');
            $messageSender->on(MessageSenderAbstract::EVENT_BEFORE_SEND, ['\app\modules\mails\models\MailOutbox',
                'beforeSend']);
            $messageSender->on(MessageSenderAbstract::EVENT_AFTER_SEND, ['\app\modules\mails\models\MailOutbox',
                'afterSend']);
            if (!empty($outBoxList)) {
                foreach ($outBoxList as $outBox) {
                    $flag = $messageSender->setMessageEntity($outBox)
                        ->sendMessage();
                    if (!$flag) {
                        echo 'Message Id {' . $outBox->id . '} Send Failed, ' .
                            $messageSender->getException() . "\r\n";
                    } else {
                        echo $outBox->id . ' Success!/n';
                    }
                }
            }
        } catch (\Exception $e) {
            var_dump($e->getFile());
            var_dump($e->getLine());
            echo 'Task Run Failed, ' . $e->getMessage();
        }
        exit('DONE');
    }

    public function actionSendmessagecc($maxFailureTimes = null)
    {
        $platform_code   = 'EB';
        $maxFailureTimes = 1;
        if ($maxFailureTimes !== null)
            $maxFailureTimes = (int)$maxFailureTimes;
        try {
            $outBoxList    = MailOutbox::find()->where('id = 3646')->all();
            $messageSender = MessageSenderAbstract::getSender($platform_code);
            if (!$messageSender)
                exit('Invalid Platform Code');
            $messageSender->on(MessageSenderAbstract::EVENT_BEFORE_SEND, ['\app\modules\mails\models\MailOutbox',
                'beforeSend']);
            $messageSender->on(MessageSenderAbstract::EVENT_AFTER_SEND, ['\app\modules\mails\models\MailOutbox',
                'afterSend']);
            if (!empty($outBoxList)) {
                foreach ($outBoxList as $outBox) {
                    $flag = $messageSender->setMessageEntity($outBox)
                        ->sendMessage();
                    if (!$flag)
                        echo 'Message Id {' . $outBox->id . '} Send Failed, ' .
                            $messageSender->getException() . "\r\n";
                }
            }
        } catch (\Exception $e) {
            echo 'Task Run Failed, ' . $e->getMessage();
        }
        exit('DONE');

    }

    /**
     * amazon平台发信
     * @param $platform_code
     * @param int $limit
     * @param string $sortOrder
     * @param null $modNumber
     * @param null $modRemain
     */
    public function actionSendamazonmessage($platform_code, $limit = 100, $maxFailureTimes = null, $sortOrder = 'ASC',
                                      $modNumber = null, $modRemain = null)
    {
        $maxFailureTimes = 1;
        if ($maxFailureTimes !== null)
            $maxFailureTimes = (int)$maxFailureTimes;
        if (empty($sortOrder))
            $sortOrder = 'ASC';
        try {
            $outBoxList    =AmazonMailOutbox::getWaittingSendList($platform_code, $limit, $maxFailureTimes, $sortOrder,
                $modNumber, $modRemain);
            $messageSender = MessageSenderAbstract::getSender($platform_code);
            if (!$messageSender)
                exit('Invalid Platform Code');
            $messageSender->on(MessageSenderAbstract::EVENT_BEFORE_SEND, ['\app\modules\mails\models\AmazonMailOutbox',
                'beforeSend']);
            $messageSender->on(MessageSenderAbstract::EVENT_AFTER_SEND, ['\app\modules\mails\models\AmazonMailOutbox',
                'afterSend']);
            if (!empty($outBoxList)) {
                foreach ($outBoxList as $outBox) {
                    $flag = $messageSender->setMessageEntity($outBox)
                        ->sendMessage();
                    if (!$flag) {
                        echo 'Message Id {' . $outBox->id . '} Send Failed, ' .
                            $messageSender->getException() . "\r\n";
                    } else {
                        echo $outBox->id . ' Success!/n';
                    }
                }
            }
        } catch (\Exception $e) {
            var_dump($e->getFile());
            var_dump($e->getLine());
            echo 'Task Run Failed, ' . $e->getMessage();
        }
        exit('DONE');
    }
}