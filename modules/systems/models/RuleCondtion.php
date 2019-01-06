<?php

namespace app\modules\systems\models;

use app\modules\systems\models\Condition;
use app\modules\accounts\models\Account;

/**
 * This is the model class for table "{{%rule_condtion}}".
 *
 * @property integer $id
 * @property integer $rule_id
 * @property integer $condtion_id
 * @property integer $option_id
 * @property string $oprerator
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 * @property string $option_value
 */
class RuleCondtion extends SystemsModel
{   
    const RULE_CONDITION_OPRERATOR_DAYU = 1;
    const RULE_CONDITION_OPRERATOR_XIAOYU = 2;
    const RULE_CONDITION_OPRERATOR_DENGYU = 3;
    const RULE_CONDITION_OPRERATOR_BAOHAN = 4;
    const RULE_CONDITION_OPRERATOR_BUBAOHAN = 5;
    const RULE_CONDITION_OPRERATOR_RANGE = 6;
    const RULE_CONDITION_OPRERATOR_BAOHANIN = 7;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%rule_condtion}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['rule_id', 'condtion_id', 'oprerator', 'option_value','condition_name','input_type','condition_key'], 'required'],
            [['rule_id', 'condtion_id', 'option_id','input_type'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['oprerator'], 'string', 'max' => 10],
            [['create_by', 'modify_by','condition_name'], 'string', 'max' => 50],
            [['option_value'], 'string', 'max' => 200],
            [['condition_key'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'rule_id' => 'Rule ID',
            'condtion_id' => 'Condtion ID',
            'option_id' => 'Option ID',
            'oprerator' => 'Oprerator',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'modify_by' => 'Modify By',
            'modify_time' => 'Modify Time',
            'option_value' => 'Option Value',
        ];
    }
    /**
     * 根据条件的input_type获取操作符静态数据
     * @param int $input_type 条件的input_type 类型 
     */
    public static function getOpreratorAsArray($input_type = null)
    {   
        if ($input_type == Condition::CONDITION_INPUT_TYPE_RANGE) {
            return [
                static::RULE_CONDITION_OPRERATOR_RANGE => \Yii::t('system', 'oprerator Range'),
            ];
        }

        if ($input_type == Condition::CONDITION_INPUT_TYPE_CHECKBOX) {
            return [
                static::RULE_CONDITION_OPRERATOR_BAOHAN => \Yii::t('system', 'oprerator Baohan'),
                static::RULE_CONDITION_OPRERATOR_BUBAOHAN => \Yii::t('system', 'oprerator Bubaohan'),
            ];
        }
        
        return [
            static::RULE_CONDITION_OPRERATOR_DAYU => \Yii::t('system', 'oprerator Dayu'),
            static::RULE_CONDITION_OPRERATOR_XIAOYU => \Yii::t('system', 'oprerator Xiaoyu'),
            static::RULE_CONDITION_OPRERATOR_DENGYU => \Yii::t('system', 'oprerator Dengyu'),
            static::RULE_CONDITION_OPRERATOR_BAOHAN => \Yii::t('system', 'oprerator Baohan'),
            static::RULE_CONDITION_OPRERATOR_BUBAOHAN => \Yii::t('system', 'oprerator Bubaohan'),
            static::RULE_CONDITION_OPRERATOR_BAOHANIN => \Yii::t('system', 'oprerator Baohanin'),
        ];
    }
    /**
     * 获取操作符名称
     */
    public static function getOpreratorName($key = null)
    {
        $list = [
            static::RULE_CONDITION_OPRERATOR_RANGE => \Yii::t('system', 'oprerator Range'),
            static::RULE_CONDITION_OPRERATOR_DAYU => \Yii::t('system', 'oprerator Dayu'),
            static::RULE_CONDITION_OPRERATOR_XIAOYU => \Yii::t('system', 'oprerator Xiaoyu'),
            static::RULE_CONDITION_OPRERATOR_DENGYU => \Yii::t('system', 'oprerator Dengyu'),
            static::RULE_CONDITION_OPRERATOR_BAOHAN => \Yii::t('system', 'oprerator Baohan'),
            static::RULE_CONDITION_OPRERATOR_BUBAOHAN => \Yii::t('system', 'oprerator Bubaohan'),
            static::RULE_CONDITION_OPRERATOR_BAOHANIN => \Yii::t('system', 'oprerator Baohanin'),
        ];

        if ($key===null || !array_key_exists($key, $list)){
            return null;
        }

        return $list[$key];
    }
    /**
     * 获取规则对应的条件选项情况
     * @param int $ruleId 规则id
     */
    public static function getRuleConditionName($ruleId, $platformCode = '')
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
                      ->select('condtion_id,oprerator')
                      ->where('rule_id = :rule_id', [':rule_id' => $ruleId])
                      ->all();

        if (empty($data)) {
            return null;
        }

        foreach ($data as $key => $value) {
            $condition_ids[] = $value['condtion_id'];
        }
        
        $nameData = Condition::getConditionDataByIds($condition_ids,$data);

        if (empty($nameData)) {
            return null;
        } 

        $result = '';
        foreach ($nameData as $k => $v) {
           $result = $result . $v['condition_name'] . ':'.$v['oprerator'].'['.static::getRuleOptionValue($ruleId,$v['id'],$platformCode). '],';
        }

        return substr($result,0,strlen($result)-1); 
    }
    /**
     *根据规则id和条件id获选项值
     * @param int $ruleid 规则id
     * @param int $conditionId 条件id
     */
    public static function getRuleOptionValue($ruleId,$conditionId, $platformCode = '')
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
                      ->select('option_value,condition_key')
                      ->where('rule_id = :rule_id and condtion_id = :condtion_id', [':rule_id' => $ruleId,':condtion_id'=>$conditionId])
                      ->distinct()
                      ->all();

        if (empty($data)) {
            return null;
        }

        $accountNames = [];
        if (!empty($platformCode)) {
            $accountNames = Account::getAllAccountByPlatformCode($platformCode);
            if (!empty($accountNames)) {
                $accountNames = array_column($accountNames, 'account_name', 'id');
            }
        }

        $result = '';
        foreach ($data as $key => $value) {
            //如果标签规则验证的是账号，则把账号ID换成账号名称显示
            if (strpos($value['condition_key'], 'account_id') !== false) {
                $result = $result . (array_key_exists($value['option_value'], $accountNames) ? $accountNames[$value['option_value']] : '-') . ',';
            } else {
                $result = $result . $value['option_value'] . ',';
            }
        }

        return substr($result,0,strlen($result)-1); 
    }
    /**
     * 获取指定rule_id的rule_condtion表数据
     * @param int $rule_id 规则id
     */
    public static function getRuleConditionData($rule_id)
    {   
        //没有具有重复的数据
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
                      ->select('condtion_id,option_id,oprerator,input_type,condition_name,option_value')
                      ->where('rule_id = :rule_id', [':rule_id' => $rule_id])
                      ->all();

        //取出制定规则id的所有条件id
        $result_condition_ids = [];
        foreach ($data as $key => $value) {
            $result_condition_ids[] = $value['condtion_id'];
        }

        //去重复
        $result_condition_ids = array_unique($result_condition_ids);

        //组装返回结果
        $result['condition_ids'] = $result_condition_ids;
        foreach ($result_condition_ids as $k => $v) {
            $option_data    = static::getRuleConditionOptionValue($rule_id,$v);
            $condition_data = Condition::getConditionInputTypeById($v);
            $result['condition_data'][$k]['condition_id']   = $v;
            $result['condition_data'][$k]['input_type']     = $condition_data['input_type'];
            $result['condition_data'][$k]['condition_name'] = $condition_data['condition_name'];
            $result['condition_data'][$k]['condition_key']  = $condition_data['condition_key'];
            $result['condition_data'][$k]['option_value']   = $option_data['option_value'];
            $result['condition_data'][$k]['oprerator']      = $option_data['oprerator'];
        }
        
        return $result;
    }
    /**
     * 获取指定rule_id,condition_id的rule_condtion表的option_value
     * @param int $rule_id 规则id
     * @param int $condition_id 条件id
     */
    public static function getRuleConditionOptionValue($rule_id,$condition_id)
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
                      ->select('option_id,option_value,oprerator')
                      ->where('rule_id = :rule_id and condtion_id=:condtion_id', [':rule_id' => $rule_id,':condtion_id'=>$condition_id])
                      ->all();

        //只有一条记录
        if (count($data) <= 1) {
            $data = current($data);
            $result['oprerator'] = $data['oprerator'];
            $result['option_value'] = $data['option_value'];
            return $result;
        }
        
        //有多条记录
        $result = [];
        foreach ($data as $key => $value) {
            $result['oprerator'] = $value['oprerator'];
            $result['option_value'][] = $value['option_value'];
        }
        return $result;
    }
    /** 
     * 一次性获取rule_condition表的所有数据
     */
    public static function getAllRuleConditionData()
    {
        $query = new \yii\db\Query();
        return $query->from(self::tableName())
                     ->select("id,rule_id,condtion_id,option_id,oprerator,option_value,input_type,condition_name,condition_key")
                     ->all();
    }
}
