<?php
namespace app\modules\mails\controllers;
use app\components\Controller;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AliexpressInboxTmp;
use app\modules\mails\models\EbayInboxTmp;
class TestController extends Controller
{
    public function actionProcesstmpinbox($platform_code, $limit = 100, $offset = 0)
    {
//        ini_set('mongo.long_as_object', 1);
        ini_set("pcre.recursion_limit", "524");     //解决preg_match匹配较大的字符串是出现程序崩溃的问题
        $tmpInbox = null;
        switch ($platform_code)
        {
            case Platform::PLATFORM_CODE_ALI:
                $tmpInbox = new AliexpressInboxTmp();
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $tmpInbox = new AmazonInboxTmp();
                break;
            case Platform::PLATFORM_CODE_EB:
                $tmpInbox = new EbayInboxTmp();
            default:
                break;
        }
        if ($tmpInbox == null)
            exit('Invalid Platform Code');
        $list = $tmpInbox->getWaitingProcessList($limit,$offset);
        if (empty($list))
            exit('No Data');
        foreach ($list as $inbox)
        {//$startTimeStamp = microtime(true);
            $flag = $tmpInbox->processTmpInbox($inbox);
            //$endTimeStamp = microtime(true);
            //echo $endTimeStamp - $startTimeStamp . '<br />';
            if (!$flag)
                echo 'ID {' . $inbox->_id . '} Process Failed, ' . $tmpInbox->getExceptionMessage() . "\r\n";
        }
        exit('DONE');        
    }

}
