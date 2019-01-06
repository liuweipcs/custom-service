<?php

namespace app\modules\mails\models;

use app\common\VHelper;
use Yii;

/**
 * This is the model class for table "{{%aliexpress_task}}".
 *
 * @property integer $id
 * @property integer $property
 * @property string $task_name
 * @property string $start_time
 * @property string $end_time
 * @property integer $status
 * @property integer $nums
 * @property string $errors
 * @property integer $opration_id
 * @property string $opration_date
 */
class AliexpressTask extends MailsModule
{
    public static $tableChangeLogEnabled = false;        //是否记录数据表操作日志

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_task}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'task_name'], 'required'],
            [['property', 'status', 'nums', 'opration_id'], 'integer'],
            [['start_time', 'end_time', 'opration_date'], 'safe'],
            [['errors'], 'string'],
            [['task_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'property' => 'Property',
            'task_name' => 'Task Name',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'status' => 'Status',
            'nums' => 'Nums',
            'errors' => 'Errors',
            'opration_id' => 'Opration ID',
            'opration_date' => 'Opration Date',
        ];
    }

    /*查询账号的任务是否存在*/
    public function getAdd($accountId, $task_name)
    {
        $this->account_id = $accountId;
        $this->property = 1;
        $this->task_name = $task_name;
        $this->start_time = date('Y-m-d H:i:s');
        $this->status = 0;
        $this->opration_id = 0;
        $this->opration_date = date('Y-m-d H:i:s');
        $this->save();
        return $this->id;
    }

    public function checkIsRunning($accountId, $task_name)
    {
        $res = self::find()->where(['account_id' => $accountId, 'task_name' => $task_name, 'status' => 1])->one();
        if (empty($res)) {
            return false;
        }
        $startTime = $res->start_time;
        //检查运行时间是否超过30分钟，如果超过，手动标记失败
        $startTimeStamp = strtotime($startTime);
        $curentTimeStamp = time();
        if ($curentTimeStamp - $startTimeStamp > 1800) {
            $res->status = -1;
            $res->save(false, array('status'));
        } else {
            return true;
        }
        return false;
    }

}
