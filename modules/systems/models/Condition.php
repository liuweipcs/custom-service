<?php

namespace app\modules\systems\models;

/**
 * This is the model class for table "{{%condition}}".
 *
 * @property integer $id
 * @property integer $group_id
 * @property string $condition_name
 * @property integer $input_type
 * @property integer $status
 * @property integer $sort_order
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 */
class Condition extends SystemsModel
{   
    const CONDITION_STATUS_VAILD = 1; //有效条件
    const CONDITION_STATUS_INVAILD = 0; //无效条件
    const CONDITION_INPUT_TYPE_INPUT = 1;
    const CONDITION_INPUT_TYPE_RADIO = 2;
    const CONDITION_INPUT_TYPE_SELECT = 3;
    const CONDITION_INPUT_TYPE_CHECKBOX = 4;
    const CONDITION_INPUT_TYPE_RANGE = 5;
    const CONDITION_KEY_ACCOUNT = 'inbox.account_id';
    const CONDITION_KEY_ORDER_ACCOUNT = 'info.account_id';
    const CONDITION_KEY_BYUER_OPTION_LOGISTICS = 'info.buyer_option_logistics';
    const CONDITION_KEY_WAREHOUSE_ID = 'info.warehouse_id';
    const CONDITION_KEY_SHIP_CODE = 'info.ship_code';
    const CONDITION_KEY_SHIP_COUNTRY = 'info.ship_country';
    const CONDITION_KEY_PRODUCT_SITE = 'product.site';
    const CONDITION_KEY_INFO_SITE = 'info.site';
    const CONDITION_KEY_PRODUCT_SUBJECT = 'inbox.product_subject';
    const CONDITION_KEY_CUSTOMER_COUNTRY = 'inbox.customer_country';
    const CONDITION_KEY_LOGISTICS_MODE = 'inbox.logistics_mode';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%condition}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['group_id'], 'required'],
            [['group_id', 'input_type', 'status', 'sort_order'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['condition_name', 'create_by', 'modify_by'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_id' => 'Group ID',
            'condition_name' => 'Condition Name',
            'input_type' => 'Input Type',
            'status' => 'Status',
            'sort_order' => 'Sort Order',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'modify_by' => 'Modify By',
            'modify_time' => 'Modify Time',
        ];
    }
    /**
     * 获取所有的条件数据
     * @param int $groupType 条件分组使用范围0=通用，1=标签设置可用，2=自动回复设置可用
     * @return array 
     */
    public static function getCondition($groupType)
    {   
        //条件分组数据
        $groupData = ConditionGroup::getGroupByType($groupType);

        //条件分组数据为空直接返回空数组
        if (empty($groupData)) {
            return [];
        }

        //条件数据
        $query = new \yii\db\Query();
        $conditionData  = $query->from(self::tableName())
                        ->select("id,group_id,condition_name,input_type,condition_key")
                        ->where('status = :status',[':status' => static::CONDITION_STATUS_VAILD])
                        ->all();

        //条件数据为空直接返回条件分组数据
        if (empty($conditionData)) {
            return $groupData;
        }

        //将条件数据进行按分组进行归类
        foreach ($groupData as $key => $value) {
            $temp = [];
            foreach ($conditionData as $ke => $vl) {
                if ($value['id'] == $vl['group_id']) {
                   $temp[] = $vl;
                }
            }
            $groupData[$key]['condition'] = $temp;
        }
        
        return $groupData;
    }
    /**
     * 根据条件查询条件数据
     */
    public static function getConditionDataByIds($ids,$data)
    {
        $query = new \yii\db\Query();
        $conditionData  = $query->from(self::tableName())
                        ->select("id,condition_name")
                        ->where(['in', 'id', $ids])
                        ->all();

        //组装每个条件的操作符
        foreach ($conditionData as $key => $value) {
            foreach ($data as $kv => $ve) {
                if ($ve['condtion_id'] == $value['id']) {
                   $conditionData[$key]['oprerator'] = RuleCondtion::getOpreratorName($ve['oprerator']);
                }
            }
        }
        
        return  $conditionData;
    }
    /**
     * 根据制定条件id获取input_type,condition_name
     * @param int $condition_id 条件id
     */
    public static function getConditionInputTypeById($condition_id)
    {
        $query = new \yii\db\Query();
        $conditionData  = $query->from(self::tableName())
                        ->select("input_type,condition_name,condition_key")
                        ->where('id = :id',[':id' => $condition_id])
                        ->one();

        return $conditionData;
    }
}
