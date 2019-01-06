<?php

namespace app\modules\systems\models;

use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;

/**
 * 自动催付订单规则
 */
class ReminderMsgRule extends Model
{

    /**
     * 返回操作数据库
     */
    public static function getDb()
    {
        return Yii::$app->db;
    }

    /**
     * 返回操作的表名
     */
    public static function tableName()
    {
        return '{{%auto_reminder_msg_rule}}';
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = [];
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * 设置规则
     */
    public function rules()
    {
        return [
            [['rule_name', 'platform_code', 'account_type', 'trigger_time', 'content', 'status'], 'required'],
            [['rule_name', 'platform_code', 'not_reminder_buyer', 'content'], 'string'],
            [['trigger_time', 'buyer_once_time', 'status'], 'integer'],
            [['account_short_names', 'account_ids', 'create_by', 'create_time', 'update_by', 'update_time'], 'safe']
        ];
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels()
    {
        return [
            'rule_name' => '规则名称',
            'platform_code' => '所属平台',
            'account_short_names' => '关联的账号',
            'account_ids' => '关联的账号id',
            'trigger_time' => '触发时间',
            'buyer_once_time' => '同一个买家多少小时内只催付一次',
            'not_reminder_buyer' => '不执行催付的买家',
            'content' => '催付内容',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'update_by' => '修改人',
            'update_time' => '修改时间',
            'status' => '状态',
        ];
    }

    /**
     * 返回表单筛选项
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => self::platformDropdown(),
                'search' => '='
            ],
            [
                'name' => 'rule_name',
                'type' => 'text',
                'search' => '=',
            ]
        ];
    }

    /**
     * 返回平台下拉框数据
     */
    public static function platformDropdown()
    {
        return Platform::getPlatformAsArray();
    }

    /**
     * 查询列表
     */
    public function searchList($params = [])
    {
        //默认排序方式
        $sort = new Sort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
        ]);

        $query = self::find();
        $dataProvider = parent::search($query, $sort, $params);

        $models = $dataProvider->getModels();
        $this->chgModelData($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 修改模型中的数据
     */
    public function chgModelData(&$models)
    {
        foreach ($models as $model) {
            $model->trigger_time = "下单超过{$model->trigger_time}小时未付款";
            $model->status = empty($model->status) ? '无效' : '有效';

            if ($model->account_type == 'all') {
                $model->account_short_names = '所有';
            }
        }
    }

    /**
     * 获取平台列表
     */
    public static function getPlatformList()
    {
        return [
            Platform::PLATFORM_CODE_ALI => '速卖通平台',
        ];
    }

    /**
     * 判断买家ID是否不催付，返回true为不催付，false为催付
     */
    public static function buyerIdIsNotReminder($platformCode, $buyerId)
    {
        $rules = self::find()
            ->select('not_reminder_buyer')
            ->andWhere(['platform_code' => $platformCode])
            ->andWhere(['status' => 1])
            ->asArray()
            ->all();

        $notReminderBuyerArr = [];
        if (!empty($rules)) {
            foreach ($rules as $rule) {
                if (!empty($rule['not_reminder_buyer'])) {
                    $notReminderBuyerArr = array_merge($notReminderBuyerArr, explode(',', $rule['not_reminder_buyer']));
                }
            }
        }

        return in_array($buyerId, $notReminderBuyerArr) ? true : false;
    }
}