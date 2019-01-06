<?php

namespace app\modules\customer\models;

use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;
use app\modules\customer\models\CustomerTagsRule;
class CustomerTags extends Model{


    public static $isStatus = [0 => '无效', 1 => '有效'];
    //满足所有规则
    const COND_TYPE_ALL = 1;
    //满足任一规则
    const COND_TYPE_ANY = 2;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%customer_tags}}';
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = [
            'tag_rule',
        ];
        return array_merge($attributes, $extraAttributes);
    }


    /**
     * 设置规则
     */
    public function rules()
    {
        return [
            [['tag_name', 'platform_code','status'], 'required'],
            [['tag_name', 'platform_code'], 'string'],
            [['status', 'cond_type'], 'integer'],
            [['modify_by', 'modify_time','create_by','create_time'], 'safe']
        ];
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
                'name' => 'tag_name',
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
            'tag_name' => '标签名称',
            'platform_code' => '平台',
            'tag_rule' => '标签规则',
            'status' => '状态',
            'modify_by' => '更新人',
            'modify_time' => '更新时间',
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

            //获取当前标签的所有规则
            $ruleList = CustomerTagsRule::getManageRuleList($model->id);

            //规则明细
            $tag_rule = '';

            if (!empty($ruleList)) {
                $tag_rule .= '<p class="filterRuleDetailWhen">';
                foreach ($ruleList as $rule) {
                    switch ($rule['type']) {
                        case CustomerTagsRule::RULE_TYPE_ORDER:
                            $tag_rule .= '累计订单数';
                            break;
                        case CustomerTagsRule::RULE_TYPE_MONERY:
                            $tag_rule .= '累计成交金额';
                            break;
                        case CustomerTagsRule::RULE_TYPE_TIME:
                            $tag_rule .= '最后下单时间';
                            break;
                        case CustomerTagsRule::RULE_TYPE_DISPUTE:
                            $tag_rule .= '累计纠纷次数';
                            break;
                        case CustomerTagsRule::RULE_TYPE_PRODUCT:
                            $tag_rule .= '累计产品数';
                            break;
                    }
                    $valueArr = explode(',', $rule['value']);
                    $value1Arr = explode(',',$rule['value1']);

                    if (!empty($valueArr)) {
                        foreach ($valueArr as $value) {
                            if(!empty($value)){
                                $tag_rule .= '>=';
                                $tag_rule .= $value .',';
                            }
                        }
                    }

                    if (!empty($value1Arr)) {
                        foreach ($value1Arr as $value) {
                            if(!empty($value)){
                                $tag_rule .= '<=';
                                $tag_rule .= $value .',';
                            }
                        }
                    }

                    $tag_rule = rtrim($tag_rule, ', ');
                    if ($model->cond_type == self::COND_TYPE_ALL) {
                        $tag_rule .= ' 且 ';
                    } else if ($model->cond_type == self::COND_TYPE_ANY) {
                        $tag_rule .= ' 或 ';
                    }
                }
                $tag_rule = rtrim($tag_rule, '且 或 ');
            }

            $model->setAttribute('tag_rule', $tag_rule);
        }
    }

    public static function getPlatformTags($platfrom)
    {
        $query = self::find();
        $data = $query->select(['id','tag_name'])
                      ->where([
                          'or',
                          ['in','platform_code',$platfrom],
                          ['platform_code' => 'ALL'],
                          ])
                      ->all();
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['id']] = $value['tag_name'];
        }
        return $result;
    }

    /**
     * 获取标签及规则
     */

    public static function getTagRule($platformCode)
    {
        $tagRule = self::find()
            ->select('id, tag_name, cond_type,')
            ->where(['status' => 1])
            ->andWhere([
            'or',
            ['platform_code' => $platformCode],
            ['platform_code' => 'ALL'],
            ])
            ->asArray()
            ->all();

        if (!empty($tagRule)) {
            foreach ($tagRule as &$item) {
                $tagRuleList = CustomerTagsRule::getManageRuleList($item['id']);
                if (!empty($tagRuleList)) {
                    $item['tag_rule'] = $tagRuleList;
                } else {
                    $item['tag_rule'] = [];
                }
            }
        }

        return $tagRule;
    }



}