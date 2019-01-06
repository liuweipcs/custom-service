<?php

namespace app\modules\mails\models;

use app\components\Model;
use app\modules\accounts\models\Platform;

class AmazonTask extends Model
{
    public static function tableName()
    {
        return '{{%amazon_task}}';
    }

    /**
     * 创建任务
     */
    public static function createTask($email, $taskName)
    {
        $task = new self();
        $task->platform_code = Platform::PLATFORM_CODE_AMAZON;
        $task->email = $email;
        $task->type = 1;
        $task->task_name = $taskName;
        $task->start_time = date('Y-m-d H:i:s');
        $task->create_by = 'system';
        $task->create_time = date('Y-m-d H:i:s');

        if (!$task->save()) {
            return false;
        }
        return $task;
    }

    /**
     * 检测任务是否运行
     */
    public static function checkIsRunning($email, $taskName)
    {
        $task = self::find()->where(['email' => $email, 'task_name' => $taskName, 'status' => 1])->one();
        if (empty($task)) {
            return false;
        }
        //如果任务执行30分钟还没结束，默认该任务执行失败
        if ((time() - strtotime($task->start_time)) > 1800) {
            $task->status = -1;
            $task->error = '任务执行超时';
            $task->end_time = date('Y-m-d H:i:s');
            $task->save(false);
        } else {
            return true;
        }
        return false;
    }
}