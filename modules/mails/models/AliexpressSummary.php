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
class AliexpressSummary extends MailsModule
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_summary}}';
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
}
