<?php

namespace app\modules\systems\models;

use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;

/**
 * 自动留评账号管理
 */
class FeedbackAccountRule extends Model
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
        return '{{%auto_feedback_account_rule}}';
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
            [['rule_name', 'platform_code', 'account_type', 'status'], 'required'],
            [['rule_name', 'platform_code'], 'string'],
            [['status'], 'integer'],
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
            Platform::PLATFORM_CODE_EB=>'ebay平台',
        ];
    }
}