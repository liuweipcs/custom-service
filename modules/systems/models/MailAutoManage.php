<?php

namespace app\modules\systems\models;

use app\modules\mails\models\Sendingmail;
use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;

class MailAutoManage extends Model
{
    public $mails;

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
        return '{{%mail_auto_manage}}';
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = [
            'auto_rule_detail', 'active_time'
        ];
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * 设置规则
     */
    public function rules()
    {
        return [
            [['rule_name', 'platform_code', 'sending_template', 'start_time', 'end_time'], 'required'],
            [['rule_name', 'platform_code'], 'string'],
            [['move_site_ids', 'create_by', 'create_time', 'modify_by', 'modify_time'], 'safe']
        ];
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels()
    {
        return [
            'rule_name'        => '规则名称',
            'auto_rule_detail' => '规则明细',
            'platform_code'    => '所属平台',
            'status'           => '是否有效',
            'status_'          => '状态',
            'sendmail'         => '应用邮件数',
            'is_active'        => '是否激活',
            'create_by'        => '创建人',
            'create_time'      => '创建时间',
            'modify_by'        => '修改人',
            'modify_time'      => '修改时间',
            'active_time'      => '有效期',
        ];
    }

    /**
     *  匹配定时任务的规则
     * @ 查询出在有效期内的，状态为可用的。然后具体匹配规则.
     */
    public static function autoMailRule()
    {
        $new_rule = self::find()->where(['status' => 1])->asArray()->all();

        return $new_rule;
    }

    /**
     * 返回表单筛选项
     */
    public function filterOptions()
    {
        return [
            [
                'name'   => 'platform_code',
                'type'   => 'dropDownList',
                'data'   => self::platformDropdown(),
                'search' => '='
            ],
            [
                'name'   => 'rule_name',
                'type'   => 'text',
                'search' => 'FULL LIKE',
            ],
            [
                'name' => 'status_',
                'type' => 'dropDownList',
                'data' => ['' => '全部', 1 => '进行中', 2 => '未开始', 3 => '已结束'],
            ],
            [
                'name' => 'is_active',
                'type' => 'dropDownList',
                'data' => ['' => '全部', 1 => '是', 2 => '否'],
            ],
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
        $this->mails = 10;
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
            $model->active_time = $model->start_time . '至' . $model->end_time;
            //查询应用邮件总数
            $rule_mail_count = Sendingmail::getMailCount(intval($model->id));
            $model->sendmail = '<a href="/systems/sendingmail/list?platform_code=' . $model->platform_code . '&send_rule_id=' . $model->id . '">' . $rule_mail_count . '</a>';
            //规则明细
            $filter_rule_detail = '';
            $filter_rule_detail .= '<p class="filterRuleDetailWhen">当: ';
            //获取当前过滤器的所有规则
            if (!empty($model->sender_type) && !empty($model->sender_content)) {
                if ($model->sender_type == 1) {
                    $filter_rule_detail .= '发件人包含';
                } else {
                    $filter_rule_detail .= '发件人不包含';
                }
                $filter_rule_detail .= $model->sender_content . '<br>';
            }
            if (!empty($model->subject_type) && !empty($model->subject_content)) {
                if ($model->subject_type == 1) {
                    $filter_rule_detail .= '邮件主题包含';
                } else {
                    $filter_rule_detail .= '邮件主题不包含';
                }
                $filter_rule_detail .= $model->subject_content;
            }
            if (!empty($model->subject_body_type) && !empty($model->subject_body_content)) {
                if ($model->subject_body_type == 1) {
                    $filter_rule_detail .= '邮件正文包含';
                } else {
                    $filter_rule_detail .= '邮件正文不包含';
                }
                $filter_rule_detail .= $model->subject_body_content;
            }
            if (!empty($model->erp_sku_type) && !empty($model->erp_sku_content)) {
                if ($model->erp_sku_type == 1) {
                    $filter_rule_detail .= 'erp sku包含';
                } else {
                    $filter_rule_detail .= 'erp sku不包含';
                }
                $filter_rule_detail .= $model->erp_sku_content;
            }
            if (!empty($model->product_id_type) && !empty($model->product_id_content)) {
                if ($model->product_id_type == 1) {
                    $filter_rule_detail .= '产品ID包含';
                } else {
                    $filter_rule_detail .= '产品ID不包含';
                }
                $filter_rule_detail .= $model->product_id_content;
            }
            if (!empty($model->country_type) && !empty($model->country_content)) {
                if ($model->country_type == 1) {
                    $filter_rule_detail .= '国家包含';
                } else {
                    $filter_rule_detail .= '国家不包含';
                }
                $filter_rule_detail .= $model->country_content;
            }
            if (!empty($model->order_id_type) && !empty($model->order_id_content)) {
                if ($model->order_id_type == 1) {
                    $filter_rule_detail .= '系统订单号包含';
                } else {
                    $filter_rule_detail .= '系统订单号不包含';
                }
                $filter_rule_detail .= $model->order_id_content;
            }
            if (!empty($model->platform_order_id_type) && !empty($model->platform_order_id_content)) {
                if ($model->platform_order_id_type == 1) {
                    $filter_rule_detail .= '平台订单号包含';
                } else {
                    $filter_rule_detail .= '平台订单号不包含';
                }
                $filter_rule_detail .= $model->platform_order_id_content . '<br>';
            }
            if (!empty($model->customer_email_type) && !empty($model->customer_email_content)) {
                if ($model->customer_email_type == 1) {
                    $filter_rule_detail .= '客户邮箱包含';
                } else {
                    $filter_rule_detail .= '客户邮箱不包含';
                }
                $filter_rule_detail .= $model->customer_email_content;
            }
            if (!empty($model->buyer_id_type) && !empty($model->buyer_id_content)) {
                if ($model->buyer_id_type == 1) {
                    $filter_rule_detail .= '客户id包含';
                } else {
                    $filter_rule_detail .= '客户id不包含';
                }
                $filter_rule_detail .= $model->buyer_id_content;
            }

            $filter_rule_detail .= ' 时; </p>';
            $model->setAttribute('auto_rule_detail', $filter_rule_detail);
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
     * 返回规则名称id列表
     */
    public static function getRule()
    {

        $rules = self::find()->select('id,rule_name')->asArray()->all();
        $new_rule = ['' => '--请选择--'];
        if (is_array($rules)) {
            foreach ($rules as $rule) {
                $new_rule[$rule['id']] = $rule['rule_name'];
            }
        }
        return $new_rule;
    }

}