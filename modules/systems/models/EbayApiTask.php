<?php

namespace app\modules\systems\models;


class EbayApiTask extends SystemsModel
{
    //是否记录数据表操作日志
    public static $tableChangeLogEnabled = false;

    public static function tableName()
    {
        return '{{%ebay_api_task}}';
    }

    public function rules()
    {
        return [
            [['task_name', 'account_id', 'start_time', 'end_time', 'opration_date', 'data_start_time', 'data_end_time'], 'safe'],
            ['siteid', 'default', 'value' => '-999'],
            [['exec_status', 'status', 'opration_id'], 'default', 'value' => 0],
            [['sendContent', 'error'], 'default', 'value' => ''],
        ];
    }

    public static function createTask($accountId, $taskName)
    {
        $task = new self();
        $task->task_name = $taskName;
        $task->account_id = $accountId;
        $task->exec_status = 0;
        $task->status = 0;
        $task->start_time = date('Y-m-d H:i:s');

        if (!$task->save()) {
            return false;
        }
        return $task;
    }

    public static function checkIsRunning($taskName, $account, $time = 1800, $siteId = -999)
    {
        if (empty($time)) {
            $time = 1800;
        }
        $task = self::findOne(['task_name' => $taskName, 'account_id' => (int)$account, 'siteid' => $siteId, 'exec_status' => 1]);
        if (empty($task)) {
            return false;
        }
        //如果任务运行时间超过30分钟，手动标记失败
        if ((time() - strtotime($task->start_time)) > $time) {
            $task->exec_status = 2;
            $task->status = 1;
            $task->error .= '[任务运行超时]';
            $task->save();
        } else {
            return true;
        }
        return false;
    }
}