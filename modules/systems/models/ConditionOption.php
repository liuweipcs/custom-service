<?php

namespace app\modules\systems\models;

use app\modules\orders\models\Logistic;
use app\modules\orders\models\Warehouse;
use Yii;
use app\modules\systems\models\Condition;
use app\modules\accounts\models\Account;

/**
 * This is the model class for table "{{%condition_option}}".
 *
 * @property integer $id
 * @property string $option_name
 * @property string $option_value
 * @property integer $status
 * @property integer $sort_order
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modfiy_time
 * @property integer $condition_id
 */
class ConditionOption extends SystemsModel
{
    const CONDITION_OPTION_STATUS_VAILD = 1;
    const CONDITION_OPTION_STATUS_INVAILD = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%condition_option}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['option_name', 'option_value', 'condition_id'], 'required'],
            [['status', 'sort_order', 'condition_id'], 'integer'],
            [['create_time', 'modfiy_time'], 'safe'],
            [['option_name', 'option_value', 'create_by', 'modify_by'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'option_name' => 'Option Name',
            'option_value' => 'Option Value',
            'status' => 'Status',
            'sort_order' => 'Sort Order',
            'create_by' => 'Create By',
            'create_time' => 'Create Time',
            'modify_by' => 'Modify By',
            'modfiy_time' => 'Modfiy Time',
            'condition_id' => 'Condition ID',
        ];
    }

    /**
     * 通过条件id获取选项数据
     * @param int $condition_id 条件id
     * @param string $platform_code 平台code
     * @param string $condition_key 条件key用于匹配的时候获取匹字段名称
     */
    public static function getOptionDataByConditionId($condition_id, $condition_key, $platfrom_code, $type = 1)
    {
        if (empty($condition_key) || empty($platfrom_code) || empty($condition_id)) {
            return [];
        }
        //如果是平台账号的条件则直接返回账号数据
        if ($condition_key == Condition::CONDITION_KEY_ACCOUNT || $condition_key == Condition::CONDITION_KEY_ORDER_ACCOUNT) {
            return Account::getAccountByPlatformCode($platfrom_code, $type);
        }

        // 平台客户选择运输方式
        if ($condition_key == Condition::CONDITION_KEY_BYUER_OPTION_LOGISTICS) {
            return Logistic::getBuyerOptionLogistics($platfrom_code);
        }

        // 发货仓库
        if ($condition_key == Condition::CONDITION_KEY_WAREHOUSE_ID) {
            return array_column(json_decode(json_encode(Warehouse::getAllWarehouse()), true), 'warehouse_name', 'id');
        }

        // 邮寄方式
        if ($condition_key == Condition::CONDITION_KEY_SHIP_CODE) {
            return json_decode(json_encode(Logistic::getAllLogistics()), true);
        }

        // 发货国家
        if ($condition_key == Condition::CONDITION_KEY_SHIP_COUNTRY) {
            $countries = Country::getAllCountries();
            if (!empty($countries)) {
                foreach ($countries as $row) {
                    $list[$row->en_abbr] = $row->cn_name;
                }
            } else {
                return [];
            }
            return $list;
        }

        // 客户国家检索
        if ($condition_key == Condition::CONDITION_KEY_CUSTOMER_COUNTRY) {
            $countryList = Country::getCodeNamePairsList('cn_name');
            return !empty($countryList) ? $countryList : [];
        }

        // 物流方式检索
        if ($condition_key == Condition::CONDITION_KEY_LOGISTICS_MODE) {
            $logisticsList = Logistic::getLogisArrCodeName();
            return !empty($logisticsList) ? $logisticsList : [];
        }

        // amazon站点邮寄方式
        if ($condition_key == Condition::CONDITION_KEY_PRODUCT_SITE || $condition_key == Condition::CONDITION_KEY_INFO_SITE) {
            return $siteList = Account::getPlatformSite();
        }

        //条件数据
        $status = static::CONDITION_OPTION_STATUS_VAILD;
        $query = new \yii\db\Query();
        return $query->from(self::tableName())
            ->select("id,option_name,option_value")
            ->where('status = :status and condition_id= :condition_id', [':status' => $status, ':condition_id' => $condition_id])
            ->all();
    }
}
