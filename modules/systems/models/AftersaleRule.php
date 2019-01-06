<?php
namespace app\modules\systems\models;

use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;

class AftersaleRule extends Model
{
    public static function tableName()
    {
        return '{{%aftersale_rule}}';
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
            [['department_id', 'reason_id', 'formula_id'], 'required'],
            [['platform_code', 'platform_reason_code', 'erp_order_status', 'sku_status'], 'string'],
            [['order_profit_cond', 'department_id', 'reason_id', 'formula_id'], 'integer'],
            [['order_profit_value'], 'double'],
            [['aftersale_manage_id'], 'safe']
        ];
    }
}