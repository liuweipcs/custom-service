<?php

namespace app\modules\services\modules\automail\controllers;

use yii\web\Controller;
use app\modules\systems\models\MailAutoManage;
use app\modules\services\modules\automail\models\Automailsend;

class AutomailController extends Controller
{

    /**
     * 查询状态为有效的邮件匹配规则
     * @throws \yii\db\Exception
     */
    public function actionIndex()
    {
        $rules    = MailAutoManage::autoMailRule();
        $now_time = time();
        foreach ($rules as $rule) {
            if(!empty($rule['is_permanent'])){
                Automailsend::getPlatformmail($rule);
            }
            if(empty($rule['is_permanent'])&&(strtotime($rule['start_time']) <= $now_time && strtotime($rule['end_time']) >= $now_time)){
                Automailsend::getPlatformmail($rule);
            }
        }
    }
}