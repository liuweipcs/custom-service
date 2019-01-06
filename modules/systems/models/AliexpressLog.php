<?php

namespace app\modules\systems\models;

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
class AliexpressLog extends \yii\db\ActiveRecord
{
    public static $tableChangeLogEnabled = false;        //是否记录数据表操作日志
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_log}}';
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'channel_id' => '即关系ID',
            'update_content' => '修改的内容',
            'opration_date' => '操作时间',
            'opration_name' => '操作人',
        ];
    }
    /*查询账号的任务是否存在*/
    public function getAdd($data)
    {
        $this->channel_id = $data['channel_id'];
        $this->update_content = $data['update_content'];
        $this->create_time = $data['create_time'];
        $this->account_id = $data['account_id'];
        $this->create_user_name = $data['create_user_name'];
        $this->save();
        return $this->id;

    }

}
