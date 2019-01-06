<?php
namespace app\modules\services\modules\ebay\models;
use app\components\MongodbModel;
class AccountTaskQueueOne extends MongodbModel
{
    const TASK_TYPE_MESSAGE = 'message';
    const TASK_TYPE_RETURN = 'return'; //return纠纷
    const TASK_TYPE_RETURN_UPDATE = 'return_update'; //return纠纷update
    const TASK_TYPE_INQUIRY = 'inquiry';//inquiry纠纷
    const TASK_TYPE_INQUIRY_UPDATE = 'inquiry_update';//inquiry纠纷update
    const TASK_TYPE_CANCELLATION = 'cancellation'; //Cancellation纠纷
    const TASK_TYPE_CANCELLATION_UPDATE = 'cancellation_update'; //Cancellation纠纷update
    const TASK_TYPE_FEEDBACK = 'feedback';
    const TASK_TYPE_FEEDBACK_UPDATE = 'feedback_update';
    const LIST_OF_DISPUTES = 'disputes_list'; //获取纠纷列表
    const INQUIRY_SEND_MSG = 'inquiry_send_msg';    // 纠纷留言发送
    
    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'account_task_queue_one';
    }
    
    public function attributes()
    {
        return [
            '_id', 'account_id', 'start_time', 'end_time', 'create_time'
        ];
    }

    public static function getTaskList($condition,$limit = null)
    {
        $limit = !is_null($limit) ? (int)$limit : self::NUMBER_PER_TASK;
        $list = self::find()->where($condition)->orderBy('create_time ASC')->limit($limit)->all();
        $taskList = [];
        if (!empty($limit))
        {
            foreach ($list as $row)
            {
                $taskList[] = $row->account_id;
                //将账号移除队列
                $row->delete();
            }
        }
        return $taskList;
    }
    
    public static function getNextAccountTask()
    {
        return self::find()->orderBy('account_id ASC')->one();
    }
}