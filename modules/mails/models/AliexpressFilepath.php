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
class AliexpressFilepath extends MailsModule
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_filepath}}';
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            's_path' => 's path',
            'm_path' => 'm path',
            'l_path' => 'l path',
            'message_id' => 'message id',
        ];
    }
    public function getInsert($reply_id,$image){
        $this->s_path = $image.'_140x140.jpg';
        $this->m_path = $image.'_350x350.jpg';
        $this->l_path = $image;
        $this->reply_id = $reply_id;
        return $this->save();
    }
}
