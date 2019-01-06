<?php

namespace app\modules\accounts\models;

class Platform extends AccountsModel
{
    const PLATFORM_STATUS_VALID = 1;
    const PLATFORM_STATUS_INVALID = 0;
    const PLATFORM_CODE_ALI = 'ALI';
    const PLATFORM_CODE_EB = 'EB';
    const PLATFORM_CODE_WISH = 'WISH';
    const PLATFORM_CODE_AMAZON = 'AMAZON';
    const PLATFORM_CODE_ALL = 'ALL';
    const PLATFORM_CODE_LAZADA = 'LAZADA';
    const PLATFORM_CODE_WALMART = 'WALMART';
    const PLATFORM_CODE_SHOPEE = 'SHOPEE';
    const PLATFORM_CODE_CDISCOUNT = 'CDISCOUNT';
    const PLATFORM_CODE_OFFLINE = 'OFFLINE';
    const PLATFORM_CODE_MALL = 'MALL';
    const PLATFORM_CODE_GROUPON = 'GRO';
    const PLATFORM_CODE_STREET = 'STR';
    const PLATFORM_CODE_PM = 'PM';
    const PLATFORM_CODE_JOOM = 'JOOM';
    const PLATFORM_CODE_PF = 'PF';
    const PLATFORM_CODE_BB = 'BB';
    const PLATFORM_CODE_DDP = 'DDP';
    const PLATFORM_CODE_STR = 'STR';
    const PLATFORM_CODE_JUM = 'JUM';
    const PLATFORM_CODE_JET = 'JET';
    const PLATFORM_CODE_GRO = 'GRO';
    const PLATFORM_CODE_DIS = 'DIS';
    const PLATFORM_CODE_SPH = 'SPH';
    const PLATFORM_CODE_INW = 'INW';
    const PLATFORM_CODE_JOL = 'JOL';
    const PLATFORM_CODE_SOU = 'SOU';
    const PLATFORM_CODE_WADI = 'WADI';
    const PLATFORM_CODE_OBERLO = 'OBERLO';
    const PLATFORM_CODE_WJFX = 'WJFX';
    const PLATFORM_CODE_ALIXX = 'ALIXX';
    const PLATFORM_CODE_TOP = 'TOP';
    const PLATFORM_CODE_VOVA = 'VOVA';

    /**
     * 设置表名
     */
    public static function tableName()
    {
        return '{{%platform}}';
    }

    /**
     * 属性
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = [
            'status_text'
        ];
        return array_merge($attributes, $extraAttributes);
    }


    /**
     * 规则
     */
    public function rules()
    {
        return [
            [['platform_code', 'platform_name', 'status'], 'required'],
            [['platform_code', 'platform_name', 'status', 'platform_description'], 'safe']
        ];
    }

    /**
     * 搜索
     */
    public function searchList($params = [])
    {
        $query = self::find();
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('status_text', self::getStatusList($model->status));
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function dynamicChangeFilter(&$filterOptions, &$query, &$params)
    {

    }

    /**
     * 属性标签
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('platform', 'Id'),
            'platform_code' => \Yii::t('platform', 'Platform Code'),
            'platform_name' => \Yii::t('platform', 'Platform Name'),
            'platform_description' => \Yii::t('platform', 'Platform Description'),
            'status' => \Yii::t('system', 'Status'),
            'create_by' => \Yii::t('system', 'Create By'),
            'create_time' => \Yii::t('system', 'Create Time'),
            'modify_by' => \Yii::t('system', 'Modify By'),
            'modify_time' => \Yii::t('system', 'Modify Time'),
            'status_text' => \Yii::t('system', 'Status Text'),
        ];
    }

    /**
     * 搜索过滤项
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => Platform::getPlatformAsArray(),
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'status',
                'type' => 'dropDownList',
                'data' => self::getStatusList(),
                'search' => '=',
            ]
        ];
    }

    /**
     * 获取权限内平台
     */
    public static function getRolePlatform($platform_code)
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
            ->select('platform_code,platform_name')
            ->where([ 'status' => static::PLATFORM_STATUS_VALID])
            ->andWhere(['in','platform_code',$platform_code])
            ->all();
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['platform_code']] = $value['platform_name'];
        }
        return $result;
    }

    /**
     * 获取有效的平台
     */
    public static function getPlatformAsArray()
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
            ->select('platform_code,platform_name')
            ->where('status = :status', [':status' => static::PLATFORM_STATUS_VALID])
            ->all();
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['platform_code']] = $value['platform_name'];
        }
        return $result;
    }

    /**
     * 获取平台列表
     */
    public static function getPlatformAccountList()
    {
        $list = [];
        $accounts = Account::getAllAccounts();
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $list[$account->platform_code][] = [
                    'id' => $account->id,
                    'old_id' => $account->old_account_id,
                    'account_name' => $account->account_name,
                ];
            }
        }
        return $list;
    }


    /**
     * 获取平台列表
     */
    public static function getAllCountList()
    {
        $data = [];
        $model = self::find()->where(['status' => 1])->all();
        if (!empty($model)) {
            foreach ($model as $value) {
                $data[$value->id] = $value->platform_code;
            }
        }
        return $data;
    }
}