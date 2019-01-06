<?php

namespace app\modules\customer\models;

use app\components\Model;
use app\modules\accounts\models\Platform;

class CustomerTagsRule extends Model
{

    //累计订单数
    const RULE_TYPE_ORDER = 1;
    //累计成交金额
    const RULE_TYPE_MONERY = 2;
    //最后下单时间
    const RULE_TYPE_TIME = 3;
    //累计纠纷次数
    const RULE_TYPE_DISPUTE = 4;
    //累计产品数
    const RULE_TYPE_PRODUCT = 5;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%customer_tags_rule}}';
    }

    /**
     * 返回规则类型
     */
    public static function getRuleTypeList()
    {
        return [
            '1' => '累计订单数',
            '2' => '累计成交金额',
            '3' => '最后下单时间',
            '4' => '累计纠纷次数',
            '5' => '累计产品数',
        ];
    }

    /**
     * 返回客户规则列表
     */
    public static function getManageRuleList($tags_id)
    {
        $data =  self::find()
            ->select('id, type, value,value1')
            ->where(['tags_id' => $tags_id, 'status' => 1])
            ->asArray()
            ->all();

        return $data;
    }
}