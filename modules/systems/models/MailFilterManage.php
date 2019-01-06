<?php

namespace app\modules\systems\models;

use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;

class MailFilterManage extends Model
{

    //满足所有规则
    const COND_TYPE_ALL = 1;
    //满足任一规则
    const COND_TYPE_ANY = 2;

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
        return '{{%mail_filter_manage}}';
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = [
            'filter_rule_detail',
        ];
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * 设置规则
     */
    public function rules()
    {
        return [
            [['filter_name', 'platform_code'], 'required'],
            [['filter_name', 'platform_code'], 'string'],
            [['status', 'cond_type', 'type_mark', 'mark_read'], 'integer'],
            [['move_site_ids', 'create_by', 'create_time', 'modify_by', 'modify_time'], 'safe']
        ];
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels()
    {
        return [
            'filter_name' => '过滤器名称',
            'filter_rule_detail' => '规则明细',
            'platform_code' => '所属平台',
            'status' => '是否有效',
            'cond_type' => '条件类型',
            'move_site_ids' => '邮件移动到站点ID',
            'type_mark' => '邮件类型标记',
            'mark_read' => '邮件标记为已读',
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
                'name' => 'filter_name',
                'type' => 'text',
                'search' => 'FULL LIKE',
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

            //获取当前过滤器的所有规则
            $ruleList = MailFilterRule::getManageRuleList($model->id);

            //规则明细
            $filter_rule_detail = '';

            if (!empty($ruleList)) {
                $filter_rule_detail .= '<p class="filterRuleDetailWhen">当: ';
                foreach ($ruleList as $rule) {
                    switch ($rule['type']) {
                        case MailFilterRule::RULE_TYPE_SEND_CONTAIN:
                            $filter_rule_detail .= '发件人包含';
                            break;
                        case MailFilterRule::RULE_TYPE_SEND_NOT_CONTAIN:
                            $filter_rule_detail .= '发件人不包含';
                            break;
                        case MailFilterRule::RULE_TYPE_RECEIVE_CONTAIN:
                            $filter_rule_detail .= '收件人包含';
                            break;
                        case MailFilterRule::RULE_TYPE_RECEIVE_NOT_CONTAIN:
                            $filter_rule_detail .= '收件人不包含';
                            break;
                        case MailFilterRule::RULE_TYPE_SUBJECT_CONTAIN:
                            $filter_rule_detail .= '主题包含';
                            break;
                        case MailFilterRule::RULE_TYPE_SUBJECT_NOT_CONTAIN:
                            $filter_rule_detail .= '主题不包含';
                            break;
                        case MailFilterRule::RULE_TYPE_BODY_CONTAIN:
                            $filter_rule_detail .= '正文包含';
                            break;
                        case MailFilterRule::RULE_TYPE_BODY_NOT_CONTAIN:
                            $filter_rule_detail .= '正文不包含';
                            break;
                        case MailFilterRule::RULE_TYPE_SEND_EQUAL:
                            $filter_rule_detail .= '发件人等于';
                            break;
                    }
                    $valueArr = explode(',', $rule['value']);

                    if (!empty($valueArr)) {
                        foreach ($valueArr as $value) {
                            $filter_rule_detail .= ' "' . $value . '", ';
                        }
                    }

                    $filter_rule_detail = rtrim($filter_rule_detail, ', ');
                    if ($model->cond_type == MailFilterManage::COND_TYPE_ALL) {
                        $filter_rule_detail .= ' 且 ';
                    } else if ($model->cond_type == MailFilterManage::COND_TYPE_ANY) {
                        $filter_rule_detail .= ' 或 ';
                    }
                }
                $filter_rule_detail = rtrim($filter_rule_detail, '且 或 ');
                $filter_rule_detail .= ' 时; </p>';
            }

            if (!empty($model->move_site_ids) || !empty($model->type_mark) || !empty($model->mark_read)) {
                $filter_rule_detail .= '<p class="filterRuleDetailBe">则: ';

                if (!empty($model->move_site_ids)) {
                    $sites = SiteManage::getSiteByIds(explode(',', $model->move_site_ids));
                    if (!empty($sites)) {
                        $filter_rule_detail .= '邮件移动到站点';
                        foreach ($sites as $site) {
                            $filter_rule_detail .= ' "' . $site['name'] . '", ';
                        }
                        $filter_rule_detail = rtrim($filter_rule_detail, ', ') . '; ';
                    }
                }

                if (!empty($model->type_mark)) {
                    $typeList = MailFilterManage::getMailTypeList($model->platform_code);
                    $filter_rule_detail .= '邮件类型标记为 "' . (array_key_exists($model->type_mark, $typeList) ? $typeList[$model->type_mark] : '') . '" ; ';
                }

                if (!empty($model->mark_read)) {
                    $filter_rule_detail .= '邮件标记为已读; ';
                }

                $filter_rule_detail .= '</p>';
            }

            $model->setAttribute('filter_rule_detail', $filter_rule_detail);
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
     * 返回平台列表
     */
    public static function getPlatformList()
    {
        return [
            Platform::PLATFORM_CODE_AMAZON => 'Amazon平台',
            Platform::PLATFORM_CODE_WALMART => '沃尔玛平台',
        ];
    }

    /**
     * 邮件类型列表
     */
    public static function getMailTypeList($platformCode = '')
    {
        $data = [
            //每个平台的下标相隔大点(以100递增)，便于后期添加新的类型
            Platform::PLATFORM_CODE_AMAZON => [
                '11' => '客户来信',
                '12' => 'AZ',
                '13' => 'QA',
                '14' => '警告信',
                '15' => 'VAT',
                '16' => '收件箱',
                '17' => '垃圾邮件',
                '18' => '系统退信',
            ],
            Platform::PLATFORM_CODE_WALMART => [
                '100' => '客户来信',
                '101' => '收件箱',
                '102' => '垃圾邮件',
                '103' => '紧急通知的邮件',
                '104' => '账号表现通知',
                '105' => 'DUNSreport',
                '106' => '系统退信',
            ],
        ];

        if (!empty($platformCode)) {
            return array_key_exists($platformCode, $data) ? $data[$platformCode] : [];
        }

        return $data;
    }

    /**
     * 获取邮件过滤器列表
     */
    public static function getMailFilterManageList($platformCode)
    {
        $mailFilterManageList = self::find()
            ->select('id, filter_name, cond_type, move_site_ids, type_mark, mark_read')
            ->where(['status' => 1, 'platform_code' => $platformCode])
            ->asArray()
            ->all();

        if (!empty($mailFilterManageList)) {
            foreach ($mailFilterManageList as &$mailFilterManage) {
                $mailFilterRuleList = MailFilterRule::getManageRuleList($mailFilterManage['id']);

                if (!empty($mailFilterRuleList)) {
                    $mailFilterManage['filter_rule_list'] = $mailFilterRuleList;
                } else {
                    $mailFilterManage['filter_rule_list'] = [];
                }
            }
        }

        return $mailFilterManageList;
    }
}