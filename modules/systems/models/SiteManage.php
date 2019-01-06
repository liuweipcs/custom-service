<?php

namespace app\modules\systems\models;

use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;

class SiteManage extends Model
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
        return '{{%site_manage}}';
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
            [['site_name', 'platform_code'], 'required'],
            [['site_name', 'platform_code'], 'string'],
            [['status', 'sort'], 'integer'],
            [['create_by', 'create_time', 'modify_by', 'modify_time'], 'safe']
        ];
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels()
    {
        return [
            'site_name' => '站点名称',
            'platform_code' => '所属平台',
            'status' => '是否有效',
            'sort' => '排序(值越小越前)',
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
                'name' => 'site_name',
                'type' => 'text',
                'search' => '=',
            ]
        ];
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
     * 返回平台下拉框数据
     */
    public static function platformDropdown()
    {
        return Platform::getPlatformAsArray();
    }

    /**
     * 获取站点列表
     */
    public static function getSiteList($platformCode)
    {
        $query = self::find()
            ->select('id, site_name as name')
            ->where(['status' => 1])
            ->orderBy('sort ASC, id DESC');

        if (!empty($platformCode)) {
            $query->andWhere(['platform_code' => $platformCode]);
        }

        $data = $query->asArray()->all();

        return $data;
    }

    /**
     * 通过id数组获取站点
     */
    public static function getSiteByIds($ids = [])
    {
        return self::find()
            ->select('id, site_name as name')
            ->where(['in', 'id', $ids])
            ->orderBy('sort ASC, id DESC')
            ->asArray()
            ->all();
    }
}