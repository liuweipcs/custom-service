<?php

namespace app\modules\systems\models;

use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;

class PlatformDisputeReason extends Model
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
        return '{{%platform_dispute_reason}}';
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
            [['platform_code', 'reason_name', 'reason_code', 'status'], 'required'],
            [['reason_name', 'reason_code'], 'string'],
            [['status'], 'integer'],
            [['create_by', 'create_time', 'modify_by', 'modify_time'], 'safe']
        ];
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels()
    {
        return [
            'platform_code' => '所属平台',
            'reason_name' => '纠纷原因名称',
            'reason_code' => '纠纷原因code',
            'status' => '状态',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
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
                'name' => 'reason_name',
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
        }
    }

    /**
     * 获取平台纠纷原因列表
     */
    public static function getDisputeReasonList($platformCode)
    {
        $data = self::find()
            ->select('reason_code id, reason_name name')
            ->where(['platform_code' => $platformCode, 'status' => 1])
            ->asArray()
            ->all();

        if (!empty($data)) {
            $data = array_column($data, 'name', 'id');
        }
        return $data;
    }
}