<?php

namespace app\modules\systems\models;

use Yii;

/**
 * This is the model class for table "{{%condition_group}}".
 *
 * @property integer $id
 * @property string $group_name
 * @property integer $status
 * @property integer $sort_order
 * @property integer $type
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 */
class ConditionGroup extends SystemsModel
{   
    const GROUP_TYPE_ALL = 0; //通用分组
    const GROUP_TYPE_TAG = 1; //标签设置分组
    const GROUP_TYPE_AUTO_ANSWER = 2; //自动回复设置分组
    const GROUP_STATUS_VALID = 1; //有效分组
    const GROUP_STATUS_INVALID = 0; //无效分组
    const GROUP_DISPLAY_ACCOUNT_NAME = '平台账/号';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%condition_group}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'sort_order', 'type'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['group_name'], 'string', 'max' => 50],
            [['create_by', 'modify_by'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_name' => 'Group Name',
            'status' => 'Status',
            'sort_order' => 'Sort Order',
            'type' => 'Type',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'modify_by' => 'Modify By',
            'modify_time' => 'Modify Time',
        ];
    }
    /**
     * 根据条件分组的使用范围标记获取条件分组数据
     * @param int $type 使用范围0=通用，1=标签设置可用，2=自动回复设置可用
     * @return array
     */
    public static function getGroupByType($type)
    {
        $query = new \yii\db\Query();
        $data  = $query->from(self::tableName())
               ->select("id,group_name")
               ->where('type = :type and status = :status',[':type' => $type,':status' => static::GROUP_STATUS_VALID])
               ->all();
        return $data;
    }
}
