<?php
/**
 * @desc 消息计划任务
 */

namespace app\commands;

use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\EbayInboxTmp;
use app\modules\mails\models\WalmartInboxTmp;
use yii\console\Controller;
use app\modules\mails\models\Inbox;
use app\modules\mails\models\AliexpressInboxTmp;
use app\modules\mails\models\AmazonInboxTmp;
use app\modules\accounts\models\Platform;

class InboxController extends Controller
{
    public function actionMatchtags($platform_code, $limit = 100)
    {
        $inboxModel = Inbox::getInboxModel($platform_code);
        if (!$inboxModel) {
            exit('Invalid Platform Code');
        }
        $list = $inboxModel->getWattingMatchTagList($limit);
        if (empty($list)) {
            exit('No Data');
        }
        foreach ($list as $inbox) {
            $flag = $inboxModel->matchTags($inbox);
            if ($flag) {
                echo $inbox->id, "add tag success\n";
            } else {
                echo $inbox->id, "add tag error\n";
            }
        }
        exit('DONE');
    }

    public function actionMatchTemplates($platform_code, $limit = 100)
    {
        $inboxModel = Inbox::getInboxModel($platform_code);

        //没有找到对应平台的消息模型
        if (!$inboxModel) {
            exit('Invalid Platform Code');
        }

        //批量取出消息
        $list = $inboxModel->getWattingMatchTagList($limit);

        //没有取到消息数据
        if (empty($list)) {
            exit('No Data');
        }
        //根据取出的消息进行一一匹配模板
        foreach ($list as $inbox) {
            $inboxModel->matchTemplates($inbox);
        }
    }

    /**
     * @desc 将临时消息添加到消息表
     * @param string $platform_code 平台CODE
     * @param integer $limit 每次取多少条
     * @param integer $modNumber 按account_id取模的数
     * @param integer $modRemain 取模的余数
     */
    public function actionProcesstmpinbox($platform_code, $limit = 100, $modNumber = null, $modRemain = null)
    {
        //解决preg_match匹配较大的字符串是出现程序崩溃的问题
        ini_set("pcre.recursion_limit", "524");
        $tmpInbox = null;
        switch ($platform_code) {
            case Platform::PLATFORM_CODE_ALI:
                $tmpInbox = new AliexpressInboxTmp();
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $tmpInbox = new AmazonInboxTmp();
                break;
            case Platform::PLATFORM_CODE_EB:
                $tmpInbox = new EbayInboxTmp();
                break;
            case Platform::PLATFORM_CODE_WALMART:
                $tmpInbox = new WalmartInboxTmp();
                break;
            default:
                break;
        }
        if ($tmpInbox == null)
            exit('Invalid Platform Code');
        $startTime = microtime(true);
        $list = $tmpInbox->getWaitingProcessList($limit, $modNumber, $modRemain);
        $endTime = microtime(true);
        echo 'Point 1: ';
        var_dump($endTime - $startTime);
        if (empty($list)) {
            exit('No Data');
        }
        foreach ($list as $inbox) {
            $startTime = microtime(true);
            $flag = $tmpInbox->processTmpInbox($inbox);
            $endTime = microtime(true);
            echo 'Point 2: ';
            var_dump($endTime - $startTime);
            if (!$flag) {
                echo 'ID {' . $inbox->_id . '} Process Failed, ' . $tmpInbox->getExceptionMessage() . "\r\n";
            }
        }
        exit('DONE');
    }

    // 删除堆积{{%ebay_inbox_tmp}}重复数据
    public function actionDeletecc($limit = 100, $offset)
    {
        $list = EbayInboxTmp::find()->limit($limit)->offset($offset)->orderBy('_id DESC')->all();

        foreach ($list as $inbox) {
            $model = EbayInbox::findOne(['message_id' => $inbox->message_id]);
            if ($model != null) {
                $inbox->delete();
            } else {
                echo '1' . '<br>';
            }
        }
        exit('DONE');
    }

    // 处理{{%ebay_inbox_tmp}}堆积数据
    public function actionDealcc($limit = 100, $offset = 0)
    {
        ini_set("pcre.recursion_limit", "524");
        $tmpInbox = new EbayInboxTmp();
        $list = EbayInboxTmp::find()->limit($limit)->offset($offset)->orderBy('_id _DESC')->all();
        if (empty($list)) {
            exit('No Data');
        }
        foreach ($list as $inbox) {
            $flag = $tmpInbox->processTmpInbox($inbox);
            if (!$flag) {
                echo 'ID {' . $inbox->_id . '} Process Failed, ' . $tmpInbox->getExceptionMessage() . "\r\n";
            }
        }
        exit('DONE');
    }
}