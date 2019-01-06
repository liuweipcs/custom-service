<?php

namespace app\modules\systems\models;

use app\modules\accounts\models\Platform;
use app\modules\systems\models\RuleCondtion;
use app\modules\systems\models\Tag;
use app\modules\mails\models\MailTemplate;
use app\modules\orders\models\Order;

/**
 * This is the model class for table "{{%rule}}".
 *
 * @property integer $id
 * @property string $rule_name
 * @property integer $platform_id
 * @property integer $status
 * @property integer $sort_order
 * @property integer $priority
 * @property integer $type
 * @property integer $relation_id
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 */
class Rule extends SystemsModel
{
    const RULE_TYPE_ALL = 0; //通用规则
    const RULE_TYPE_TAG = 1; //标签规则
    const RULE_TYPE_AUTO_ANSWER = 2; //自动回复规则
    const RULE_STATUS_VALID = 1;
    const RULE_STATUS_INVALID = 0;

    //邮件提醒
    const RULE_MAIL_NOTIFY_VALID = 1;
    const RULE_MAIL_NOTIFY_INVALID = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%rule}}';
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = ['status_text', 'rule_tag_name', 'rule_type', 'rule_condition_name', 'rule_template_name'];
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['rule_name', 'relation_id', 'platform_code'], 'required'],
            [['status', 'sort_order', 'priority', 'type', 'relation_id', 'mail_notify'], 'integer'],
            [['create_time', 'modify_time','is_timed','survival_str_time','survival_end_time'], 'safe'],
            [['rule_name', 'create_by', 'modify_by', 'platform_code'], 'string', 'max' => 50],
            [['execute_day', 'execute_hour', 'execute_id'], 'default', 'value' => 0]
        ];
    }

    /**
     * @param array $params
     * @param int $type
     * @return \yii\data\ActiveDataProvider
     */
    public function searchList($params = [], $type = self::RULE_TYPE_TAG)
    {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_DESC
        );

        $query = self::find();
        $query->where('type = :type', [':type' => $type]);
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $new_rule = '';
        foreach ($models as $key => $model) {
            if (!empty($model->condition_by)) {
                foreach (json_decode($model->condition_by) as &$v) {
                    if (trim($v) == 'feedback_negative') {
                        $v = '排除已经留过中差评feedback订单';
                    } elseif (trim($v) == 'feedback_positive') {
                        $v = '排除已经留过好评feedback订单';
                    } elseif (trim($v) == 'review_negative') {
                        $v='排除已经留过中差评Review订单';
                    }elseif (trim($v)=='review_positive'){
                        $v='排除已经留过好评Review订单';
                    }elseif (trim($v)=='dispute'){
                        $v='排除已有纠纷订单';
                    }else  if(strpos($v, 'buyer_message') !== false){
                        $v='排除有往来邮件(Buyer message)的订单';
                    }
                    $new_rule .= $v . '<br>';
                    $models[$key]->condition_by = rtrim($new_rule, '<br>');
                }
                unset($new_rule);
            }

            $models[$key]->setAttribute('status_text', self::getStatusList($model->status));
            $models[$key]->setAttribute('rule_type', self::getRuleTypeName($model->type));
            $models[$key]->setAttribute('rule_condition_name', RuleCondtion::getRuleConditionName($model->id, $model->platform_code));
            $models[$key]->setAttribute('rule_tag_name', Tag::getTagNameById($model->relation_id));
            $models[$key]->setAttribute('rule_template_name', MailTemplate::getTemplateNameById($model->relation_id));
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function dynamicChangeFilter(&$filterOptions, &$query, &$params)
    {
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : Platform::getPlatformAsArray();

        if (empty($params['platform_code'])) {
            $query->andWhere(['in', 'platform_code', $platformArray]);
        }

    }

    public function getMailNotifyList()
    {
        return [
            0 => '无效',
            1 => '有效',
        ];
    }

    public function filterOptions()
    {
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : array();
        $platform = array();
        $allplatform = Platform::getPlatformAsArray();
        if ($platformArray) {
            foreach ($platformArray as $value) {
                $platform[$value] = isset($allplatform[$value]) ? $allplatform[$value] : $value;
            }
        }
        $platform = !empty($platform) ? $platform : $allplatform;
        return [
            [
                'name'        => 'platform_code',
                'type'        => 'dropDownList',
                'data'        => $platform,
                'htmlOptions' => [],
                'search'      => '='
            ],
            [
                'name'   => 'rule_name',
                'type'   => 'text',
                'search' => 'FULL LIKE',
            ]

        ];
    }

    /**
     * 获取规则类型
     */
    public static function getRuleTypeAsArray()
    {
        return [
            static::RULE_TYPE_ALL         => \Yii::t('rule', 'rule Type All'),
            static::RULE_TYPE_TAG         => \Yii::t('rule', 'rule Type Tag'),
            static::RULE_TYPE_AUTO_ANSWER => \Yii::t('rule', 'rule Type Auto Answer'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                  => 'ID',
            'rule_name'           => \Yii::t('rule', 'rule Name'),
            'platform_id'         => \Yii::t('rule', 'rule Platform Name'),
            'status'              => \Yii::t('rule', 'rule Status'),
            'sort_order'          => \Yii::t('rule', 'rule Sort'),
            'priority'            => \Yii::t('rule', 'rule Priority'),
            'type'                => \Yii::t('rule', 'rule Type'),
            'relation_id'         => \Yii::t('rule', 'relation ID'),
            'create_by'           => \Yii::t('rule', 'Create By'),
            'create_time'         => \Yii::t('rule', 'Create Time'),
            'modify_by'           => \Yii::t('rule', 'Modify By'),
            'modify_time'         => \Yii::t('rule', 'Modify Time'),
            'platform_code'       => \Yii::t('rule', 'rule Platform Code'),
            'rule_type'           => \Yii::t('rule', 'rule Type'),
            'status_text'         => \Yii::t('rule', 'rule Status'),
            'rule_option_value'   => \Yii::t('rule', 'rule Option Value'),
            'rule_condition_name' => \Yii::t('rule', 'rule Condition Name'),
            'rule_tag_name'       => \Yii::t('rule', 'rule Tag Name'),
            'rule_template_name'  => \Yii::t('rule', 'rule Template Name'),
            'mail_notify'         => \Yii::t('rule', 'rule mail notify'),
            'condition_by'        => '新加规则',
            'is_timed'            => '触发时间点',
            'survival_str_time'   => '开始时间',
            'survival_end_time'   => '结束时间',
        ];
    }

    /**
     * 根据平台id获取规则的平台名称
     * @param int $platform_id
     */
    public static function getRulePlatName($platform_id)
    {
        return Platform::getPlatformNameById($platform_id);
    }

    /**
     * 获取标签类型
     */
    public static function getRuleTypeName($key = null)
    {
        $list = [
            static::RULE_TYPE_ALL         => \Yii::t('rule', 'rule Type All'),
            static::RULE_TYPE_TAG         => \Yii::t('rule', 'rule Type Tag'),
            static::RULE_TYPE_AUTO_ANSWER => \Yii::t('rule', 'rule Type Auto Answer'),
        ];

        if ($key === null || !array_key_exists($key, $list)) {
            return null;
        }

        return $list[$key];
    }

    /**
     * 判断标签或者自动回复模板是否已经被绑定规则
     * @param int $type 使用范围标记（0=通用，1=标签设置可用，2=自动回复设置可用）
     * @param int $relation_id 标签ID或者模板ID
     */
    public static function getCountByTypeAndRelationId($type, $relation_id)
    {
        $query = new \yii\db\Query();
        $count = $query->from(self::tableName())
            ->where('status=:status and type=:type and relation_id=:relation_id', [
                ':status'      => static::RULE_STATUS_VALID,
                ':type'        => $type,
                ':relation_id' => $relation_id])
            ->count();
        return $count;
    }

    /**
     * 通过规则去匹配标签id
     * 包含着平台信息的消息对象的具体实例
     */
    public function getTagIdByCondition($inbox_model)
    {
        //当前平台的消息模型对象
        $inbox_model = $inbox_model;
        //获取消息模型关联的订单模型对象
        $order_model = null;
        $order_id = $inbox_model->order_id;

        //如果订单号不为空则根据订单号获取相对应平台的订单模型对象
        if (!empty($order_id)) {
            $order_model = $this->getOrderModel($inbox_model->platform_code, $order_id);
        }
        if ($inbox_model->platform_code == 'ALI' && empty($order_model)) {
            return null;
        }
        //此处还有其他模型对象,如果要增加其他匹配关联的字段则需要再次增加对应的模型对象
        //$object_array['order'] = $order_model;
        //$object_array['inbox'] = $inbox_model->fields();

        //根据平台code查出所有的规则id
        $rule_id_info = $this->getRuleInfoByPlatformCode($inbox_model->platform_code, static::RULE_TYPE_TAG);
        if (empty($rule_id_info)) {
            return null;
        }

        //一次性获取rule_condition表的数据
        $rule_condition_data = RuleCondtion::getAllRuleConditionData();

        $result = [];//匹配上的标签id
        //进行匹配
        foreach ($rule_id_info as $key => $value) {
            //根据规则id获取相对应的条件id  (condition_id)
            $conditionData = $this->getConditionDataByRuleId($value['id'], $rule_condition_data);

            foreach ($conditionData as $kc => $vc) {
                //获取指定规则id和指定条件下的option_value数据 (一个规则id的同一个指定条件存在多个值)
                $option_value_data = $this->getOptionValeDataByRuleIdAndConditionId($value['id'], $vc, $rule_condition_data);

                //根据指定规则id和指定条件id的option_value数据进行匹配标签id
                $this->matchingOptionValue($result, $value, $option_value_data, $order_model, $inbox_model);
                if (!isset($result[$value['id']]) || empty($result[$value['id']])) {
                    unset($result[$value['id']]);
                    break;
                }
            }
        }
        //将匹配到的标签id结果进行返回
        return empty($result) ? null : implode(",", $result);
    }

    /**
     * 根据指定规则id和指定条件id的option_value数据进行匹配标签id
     * @param array $result 由已经匹配上的标签id组成的数组
     * @param array $rule_data 包含标签id的规则数据
     * @param array $option_value_data 指定的option_value_data
     * @param object $order_model 包含跟订单有关的所有数据的对象
     * @param object $inbox_model 消息模型对象
     */
    protected static function matchingOptionValue(&$result, $rule_data, $option_value_data, $order_model, $inbox_model)
    {
        //将option_value_data数据指向第一个元素
        $info = current($option_value_data);

        //指定规则和指定条件的操作符是一样的所以获取option_value_data的第一条记录代表该规则指定条件的所有的操作符
        $oprerator = $info['oprerator'];

        //获取要拿来跟规则数据进行匹配的字段值,有可能有多个(模型多条记录的值或者多个字段的值)
        $match_value = self::getMatchValue($option_value_data, $order_model, $inbox_model);

        //对拿到的消息相关的值进行匹配,多个就循环匹配只要匹配上了就返回标签id或者模板id
        if (!empty($match_value)) {
            foreach ($match_value as $kwg => $vluematch) {
                self::match($result, $rule_data, $option_value_data, $vluematch, $oprerator);
            }
        }
    }

    /**
     * 进行匹配操作
     * @param array $result 由已经匹配上的标签id组成的数组
     * @param array $rule_data 包含标签id的规则数据
     * @param array $option_value_data 指定的option_value_data
     * @param string $vluematch 要进行匹配的值
     * @param string $oprerator 操作符(1代表大于2代表小于，3代表等于，4代表包含，5代表不包含,6代表范围（大于等于并且小于等于)
     */
    protected static function match(&$result, $rule_data, $option_value_data, $vluematch, $oprerator)
    {
        $ruleId = $rule_data['id'];
        //对操作符为大于的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_DAYU) {
            $option_value_data = current($option_value_data);
            if (!($vluematch > $option_value_data['option_value'])) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为小于的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_XIAOYU) {
            $option_value_data = current($option_value_data);
            if (!($vluematch < $option_value_data['option_value'])) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为等于的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_DENGYU) {
            $option_value_data = current($option_value_data);
            if (!($vluematch == $option_value_data['option_value'])) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为包含的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_BAOHAN) {
            $option_value = [];
            foreach ($option_value_data as $key => $value) {
                $option_value[] = $value['option_value'];
            }
            $flag = false;
            foreach ($option_value as $value) {
                if (strpos($vluematch, $value) !== false) {
                    $flag = true;
                    break;
                }
            }
            if (!$flag) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        // 对操作符为全等包含的情况进行匹配（目前针对帐号id，int类型数据）
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_BAOHANIN) {
            $option_value = [];
            foreach ($option_value_data as $key => $value) {
                $option_value[] = $value['option_value'];
            }
            $flag = false;
            foreach ($option_value as $value) {
                if ($vluematch == $value) {
                    $flag = true;
                    break;
                }
            }
            if (!$flag) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为不包含的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_BUBAOHAN) {
            $option_value = [];
            foreach ($option_value_data as $key => $value) {
                $option_value[] = $value['option_value'];
            }
            $flag = true;
            foreach ($option_value as $value) {
                if (strpos($vluematch, $value) !== false) {
                    $flag = false;
                    break;
                }
            }
            if (!$flag) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为范围的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_RANGE) {
            $option_value = [];
            foreach ($option_value_data as $key => $value) {
                $option_value[] = $value['option_value'];
            }

            //要进行匹配的开始范围和结束范围
            $rang_value = self::getRangeValue($option_value);

            //对范围进行匹配
            if (!($vluematch > $rang_value['start_value'] && $vluematch <= $rang_value['end_value'])) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }
        $result[$ruleId] = $rule_data['relation_id'];
    }

    /**
     *对操作符为范围的情况进行匹配得出开始范围和结束范围
     * @param array $option_value 指定规则和指定条件下option_value组成的数组
     */
    protected static function getRangeValue($option_value)
    {
        $result['start_value'] = current($option_value);
        next($option_value);
        $result['end_value'] = current($option_value);

        //如果是时间格式要先转成时间戳的格式
        if (strpos($result['start_value'], "-") !== false) {
            //$result['start_value'] = strtotime($start_value);
            //$result['end_value']   = strtotime($start_value.' 23:59:59');
            $result['start_value'] = strtotime($result['start_value']);
            $result['end_value'] = strtotime($result['end_value']);
        }
        return $result;
    }

    /**
     * 获取要拿来跟规则数据进行匹配的字段值,有可能有多个(模型多条记录的值或者多个字段的值)
     * @param array $option_value_data 指定规则id制定条件id下的option_value_data
     * @param object $order_model 包含跟订单有关的所有数据的对象
     * @param object $inbox_model 消息模型对象
     */
    protected static function getMatchValue($option_value_data, $order_model, $inbox_model)
    {
        //指定规则下的指定条件的拿来匹配的condition_key是相同的
        $info = current($option_value_data);
        $condition_key_info = explode('.', $info['condition_key']);

        //取用来匹配的字段名称的模型名称
        //取出inbox.title.content中的inbox也就是模型标示
        $object_field = current($condition_key_info);

        //匹配多个字段的情况就是inbox.title.content这种情况获取要匹配的字段组成数组
        //匹配单个字段的情况就是inbox.title这种情况获取要匹配的字段组成数组
        $fields_info = self::getMatchFieldsInfo($condition_key_info);

        //如果没有要匹配的字段则跳过该规则下指定条件的匹配
        if (empty($fields_info)) {
//            continue;
        }
        //用来获取匹配的字段的模型要么是消息模型要么是消息模型关联的订单相关模型
        $match_value_model = $object_field == 'inbox' ? $inbox_model : $order_model->$object_field;

        //没有相对应的模型对象跳过该规则下指定条件的匹配
        if (empty($match_value_model)) {
//            continue;
        }

        $mantchValue = [];//组装用来匹配的值

        //需要获取值的模型是一个数组即有多条记录
        if (is_array($match_value_model)) {
            foreach ($match_value_model as $km => $v_model) {
                foreach ($fields_info as $kfied => $vfied) {
                    if (!empty($v_model->$vfied)) {
                        $mantchValue[] = $v_model->$vfied;
                    }
                }
            }
        }

        //需要获取的模型只有单个即只有单条记录
        if (is_object($match_value_model)) {
            foreach ($fields_info as $k_field => $v_field) {
                if (!empty($match_value_model->$v_field)) {
                    $mantchValue[] = $match_value_model->$v_field;
                }
            }
        }

        //返回需要匹配的值
        return $mantchValue;
    }

    /**
     * 根据条件key获取要进行匹配的字段名称
     * @param array $condition_key_info 有条件key 组成的数组
     * @return array
     */
    protected static function getMatchFieldsInfo($condition_key_info)
    {
        $result = [];
        //匹配多个字段的情况就是inbox.title.content这种情况获取要匹配的字段组成数组
        //现在的需求是只可能是inbox模型上有多个字段
        array_shift($condition_key_info);
        foreach ($condition_key_info as $khh => $vhh) {
            $result[] = $vhh;
        }
        return $result;
    }

    /**
     * 获取指定规则id和条件id下所有供匹配的option_value数据
     * @param  int $rule_id 规则id
     * @param  int $condition_id 条件id
     * @param  array $rule_condition_data 一次性查出来的所有的规则条件表的数据
     * @return array
     */
    protected static function getOptionValeDataByRuleIdAndConditionId($rule_id, $condition_id, $rule_condition_data)
    {
        $result = [];

        //获取指定规则id和指定条件下的option_value数据
        foreach ($rule_condition_data as $key => $value) {
            if ($value['rule_id'] == $rule_id && $value['condtion_id'] == $condition_id) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * 根基规则id获取该规则下不重复的条件id数据
     * @param  int $rule_id 条件id
     * @param  array $rule_condition_data 一次性查出来的所有的规则条件表的数据
     * @return array
     */
    protected static function getConditionDataByRuleId($rule_id, $rule_condition_data)
    {
        $result = [];

        //返回指定规则id下的条件id
        foreach ($rule_condition_data as $key => $value) {
            if ($value['rule_id'] == $rule_id) {
                $result[] = $value['condtion_id'];
            }
        }

        //去重复后返回指定规则id对应的条件id
        return array_unique($result);
    }

    /**
     * 根据平台code和订单号得出跟平台对应的订单模型实例
     * @param string $platform_code 平台code
     * @param string $order_id 订单号
     */
    protected static function getOrderModel($platform_code, $order_id)
    {
        /*         $string = 'order_id='.$order_id.'&token=5E17C4488C2AC591';
                $order_model = VHelper::getSendreQuest($string, false, $platform_code); */
        $order_model = Order::getOrderStack($platform_code, $order_id);
        if (empty($order_model) || (isset($order_model->ack) && $order_model->ack == false))
            return false;
        //返回跟消息模型相关联的订单模型
        return $order_model;
    }

    /**
     * 根据平台code和规则type查出所有的规则id和规则绑定的标签或者模板
     * @param string $platform_code 平台code
     * @param int $rule_type 使用范围标记(0=通用，1=标签设置可用，2=自动回复设置可用)
     */
    protected static function getRuleInfoByPlatformCode($platform_code, $rule_type)
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
            ->select("id,relation_id")
            ->where('status=:status and type=:type and platform_code=:platform_code', [':status' => static::RULE_STATUS_VALID,
                                                                                       ':type'   => $rule_type, ':platform_code' => $platform_code])
            ->all();
        return $data;
    }

    /**
     * 通过规则去匹配邮件模板id
     * 包含着平台信息的消息对象的具体实例
     */
    public static function getMailTemplateIdByCondition($inbox_model)
    {
        //当前平台的消息模型对象
        $inbox_model = $inbox_model;
        //获取消息模型关联的订单模型对象
        $order_model = null;
        $order_id = $inbox_model->order_id;

        //如果订单号不为空则根据订单号获取相对应平台的订单模型对象
        if (!empty($order_id)) {
            $order_model = self::getOrderModel($inbox_model->platform_code, $order_id);
        }
        //此处还有其他模型对象,如果要增加其他匹配关联的字段则需要再次增加对应的模型对象
        //$object_array['order'] = $order_model;
        //$object_array['inbox'] = $inbox_model;

        //根据平台code查出所有的规则id
        $rule_id_info = self::getRuleInfoByPlatformCode($inbox_model->platform_code, static::RULE_TYPE_AUTO_ANSWER);

        if (empty($rule_id_info)) {
            return null;
        }

        //一次性获取rule_condition表的数据
        $rule_condition_data = RuleCondtion::getAllRuleConditionData();

        $result = [];//匹配上的标签id
        //进行匹配
        foreach ($rule_id_info as $key => $value) {
            if (!empty($result))
                return implode(",", $result);
            //根据规则id获取相对应的条件id
            $conditionData = self::getConditionDataByRuleId($value['id'], $rule_condition_data);

            foreach ($conditionData as $kc => $vc) {
                //获取制定规则id和指定条件下的option_value数据
                $option_value_data = self::getOptionValeDataByRuleIdAndConditionId($value['id'], $vc, $rule_condition_data);

                //根据指定规则id和指定条件id的option_value数据进行匹配标签id
                self::matchingOptionValue($result, $value, $option_value_data, $order_model, $inbox_model);
                if (!isset($result[$value['id']]) || empty($result[$value['id']])) {
                    unset($result[$value['id']]);
                    break;
                }
            }
        }

        //将匹配到的标签id结果进行返回
        return empty($result) ? null : implode(",", $result);
    }

    /**
     * 获取规则列表
     * @param type $ruleId
     * @return type
     * @author allen <2018-1-29>
     */
    public static function getRuleList($ruleId = "")
    {
        $data = [];
        $model = self::find()->where(['type' => 2, 'status' => 1])->asArray()->all();
        if (!empty($model)) {
            foreach ($model as $value) {
                $data[$value['id']] = $value['rule_name'];
            }
        }
        if (!empty($ruleId)) {
            return isset($data[$ruleId]) ? $data[$ruleId] : "-";
        }
        return $data;
    }
}
