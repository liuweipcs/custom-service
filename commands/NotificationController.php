<?php

namespace app\commands;

use app\modules\accounts\models\Platform;
use app\modules\mails\models\WishNotificationTmp;
use Yii;
use yii\console\Controller;

/**
 * 处理通知的计划任务
 */
class NotificationController extends Controller
{
    /**
     * 将mongodb中的通知信息转存到mysql
     */
    public function actionProcesstmpnoti($platformCode, $limit = 1000)
    {
        $tmpNoti = null;
        switch ($platformCode) {
            case Platform::PLATFORM_CODE_WISH:
                $tmpNoti = new WishNotificationTmp();
                break;
            default:
                break;
        }
        if (empty($tmpNoti)) {
            return false;
        }


        $list = $tmpNoti->getWaitingProcessList($limit);

        if (!empty($list)) {
            foreach ($list as $noti) {
                $flag = $tmpNoti->processTmpNoti($noti);

                if ($flag) {
                    echo "notification process success\n";
                } else {
                    echo "notification process error\n";
                }
            }
        }

        die('NOTIFICATION PROCESS END');
    }
}