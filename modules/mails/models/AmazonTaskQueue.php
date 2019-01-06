<?php

namespace app\modules\mails\models;

use Yii;

/**
 * This is the model class for table "{{%amazon_task_queue}}".
 *
 * @property string $id
 * @property integer $account_id
 * @property string $type
 * @property string $describtion
 * @property string $create_date
 */
class AmazonTaskQueue extends \app\modules\mails\models\MailsModel
{
    public static $tableChangeLogEnabled = false;        //是否记录数据表操作日志
    
    const TASK_DESCRIPTION = 'description';

    const NUMBER_PER_TASK = 5;

    const FEEDBACK = '_GET_SELLER_FEEDBACK_DATA_';

    const GETREQUEST = '_GET_SELLER_FEEDBACK_DATA_ID';

    const FBA_RETURNS = '_GET_FBA_FULFILLMENT_CUSTOMER_RETURNS_DATA_';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_task_queue}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'type'], 'required'],
            [['account_id'], 'integer'],
            [['create_date'], 'safe'],
            [['type'], 'string', 'max' => 128],
            [['description'], 'string', 'max' => 80],
        ];
    }

    /**
     * Find all by type
     */
    public static function findByType($type)
    {
        return self::findAll(['type' => $type]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'type' => 'Type',
            'description' => 'Description',
            'create_date' => 'Create Date',
        ];
    }

    /**
     * @see doc
     */
    public static function getTaskList($list, $limit = null)
    {
        $limit = !is_null($limit) ? $limit : self::NUMBER_PER_TASK;
        $taskList = [];

        if (!empty($limit)) {
            $i = 0;
            foreach ($list as $row) {
                if ($i > $limit) break;
                $taskList[] = $row->account_id;
                $row->delete();
                $i++;
            }
        }

        return $taskList;
    }

    /**
     * Get next task
     */
    public static function getNextTask($type)
    {
        return self::findOne(['type' => $type]);
    }
}
