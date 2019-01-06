<?php

namespace app\modules\customer\models;

use app\components\Model;
use yii\data\Sort;
use app\modules\accounts\models\Platform;

class CustomerGroup extends Model{

    public static $isStatus = [0 => '无效', 1 => '有效'];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%customer_group}}';
    }

    /**
     * 设置规则
     */
    public function rules()
    {
        return [
            [['group_name', 'platform_code','status'], 'required'],
            [['group_name', 'platform_code','instruction'], 'string'],
            [['status'], 'integer'],
            [['modify_by', 'modify_time','create_by','create_time'], 'safe']
        ];
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = [
            'customer_number',
        ];
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        $all = [Platform::PLATFORM_CODE_ALL => '全平台'];

        $platform = array_merge($all,Platform::getPlatformAsArray());

        return [
            [
                'name' => 'platform_code',
                'type'  => 'dropDownList',
                'data'   => $platform,
                'search' => '=',
            ],
            [
                'name' => 'group_name',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name'   => 'status',
                'type'   => 'dropDownList',
                'data'   => self::$isStatus,
                'search' => '=',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_name' => '分组名称',
            'platform_code' => '平台',
            'instruction' => '说明',
            'status' => '状态',
            'modify_by' => '更新人',
            'modify_time' => '更新时间',
            'customer_number'=> '客户数',
        ];
    }

    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [], $sort = NULL)
    {

        //默认排序方式
        $sort = new Sort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
        ]);

        $query = self::find();

        // return parent::searchList($params, $sort);
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
            $model->platform_code = $model->platform_code == Platform::PLATFORM_CODE_ALL ? '全平台' : $model->platform_code;
            if($model->platform_code == '全平台'){
                $list_number = CustomerList::find()->count();
            }else{
                $list_number = CustomerList::find()->where(['platform_code' => $model->platform_code])->count();
            }

            $model->customer_number = '<a target="_blank" href="/customer/customer/list?group_id='.$model->id.'">'.$list_number.'</a>';
        }
    }


    public static function getPlatformTags($platfrom)
    {
        $query = self::find();
        $data = $query->select(['id','group_name'])
            ->where([
                'or',
                ['in','platform_code',$platfrom],
                ['platform_code' => 'ALL'],
            ])
            ->all();
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['id']] = $value['group_name'];
        }
        return $result;
    }




}