<?php

/**
 * @desc 账号模型
 * @author Fun
 */

namespace app\modules\accounts\models;

use app\modules\systems\models\ErpAccountApi;
use app\modules\accounts\models\UserAccount;

class Account extends AccountsModel {

    const STATUS_VALID = 1;     //有效
    const STATUS_INVALID = 0;   //无效
    const ACCOUNT_PRICE = 500;   //金额
    const CURRENCY = "CNY";     //币种

    /**
     * @desc 设置表名
     * @return string
     */

    public static function tableName() {
        return '{{%account}}';
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes() {
        $attributes = parent::attributes();
        $extraAttributes = ['status_text'];              //状态
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Model::rules()
     */
    public function rules() {
        return [
            [['platform_code', 'account_name', 'status'], 'required'],
            [['site_code'], 'required', 'on' => 'Amazon'],
            [['email', 'account_short_name', 'account_discussion_name'], 'string', 'max' => 50],
            [['user_token', 'site', 'seller_id'], 'safe'],
            ['email', 'trim'],
        ];
    }

    /*
     * @desc 指定场景
     * */

    public function scenarios() {
        return [
            'Amazon' => ['platform_code', 'account_name', 'status', 'site_code', 'site', 'email', 'account_short_name'],
            'default' => ['platform_code', 'account_name', 'status', 'email', 'account_short_name', 'seller_id', 'account_discussion_name']
        ];
    }

    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = []) {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );
        $dataProvider = parent::search(null, $sort, $params);
        $models = $dataProvider->getModels();
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('status_text', self::getStatusList($model->status));
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function dynamicChangeFilter(&$filterOptions, &$query, &$params) {
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : Platform::getPlatformAsArray();

        if (empty($params['platform_code'])) {
            $query->andWhere(['in', 'platform_code', $platformArray]);
        }
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Model::attributeLabels()
     */
    public function attributeLabels() {
        return [
            'id' => \Yii::t('account', 'Id'),
            'account_name' => \Yii::t('account', 'Account Name'),
            'site_code' => \Yii::t('account', 'Site Code'),
            'account_short_name' => \Yii::t('account', 'Short Name'),
            'platform_code' => \Yii::t('account', 'Platform Code'),
            'email' => \Yii::t('account', 'Email'),
            'status' => \Yii::t('system', 'Status'),
            'create_by' => \Yii::t('system', 'Create By'),
            'create_time' => \Yii::t('system', 'Create Time'),
            'modify_by' => \Yii::t('system', 'Modify By'),
            'modify_time' => \Yii::t('system', 'Modify Time'),
            'status_text' => \Yii::t('system', 'Status Text'),
            'site' => \Yii::t('system', 'Site'),
            'seller_id' => \Yii::t('account', 'Seller Id'),
            'account_discussion_name' => '账号讨论名称',
        ];
    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions() {
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : array();
        $platform = array();
        $allplatform = Platform::getPlatformAsArray();
        if ($platformArray) {
            foreach ($platformArray as $value) {
                $platform[$value] = isset($allplatform[$value]) ? $allplatform[$value] : $value;
            }
        }
        $platform = !empty($platform) ? $platform : $allplatform;
        return [
            [
                'name' => 'account_name',
                'type' => 'text',
                'search' => '='
            ],
            [
                'name' => 'account_short_name',
                'type' => 'text',
                'search' => '='
            ],
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => $platform,
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'email',
                'type' => 'text',
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
     * @desc 获取所有账号
     * @return multitype:\yii\db\static
     */
    public static function getAllAccounts() {
        $platformArr = !empty(Platform::getPlatformAsArray()) ? array_keys(Platform::getPlatformAsArray()) : [];
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : $platformArr;

        $data          = self::find()->where('platform_code != "' . Platform::PLATFORM_CODE_EB . '" AND status = ' . self::STATUS_VALID)
            ->orWhere('platform_code = "' . Platform::PLATFORM_CODE_EB . '" ')
            ->andWhere(['in', 'platform_code', $platformArray])
            ->all();
        return $data;
//        return self::findAll(['status' => self::STATUS_VALID]);
    }

    public static function getPlatformAccounts($platformCode, $status = null) {
        $condition = ['platform_code' => $platformCode];
        if (!is_null($status))
            $condition['status'] = (int) $status;
        return self::findAll($condition);
    }

    /*
     * @desc 获取有平台站点
     * */

    public static function getPlatformSite() {

        $siteArray = array(
            'ca' => '加拿大',
            'mx' => '墨西哥',
            'de' => '德国',
            'it' => '意大利',
            'jp' => '日本',
            'fr' => '法国',
            'us' => '美国',
            'uk' => '英国',
            'sp' => '西班牙',
            'au' => '澳大利亚',
        );

        return $siteArray;
    }

    public static function findAccountOne($account, $platform_code) {

        $data = self::find()->where("account_name=:account AND platform_code = :platform_code", [':account' => $account, ':platform_code' => $platform_code])->one();

        return $data;
    }

    public static function findOldAccountOne($id, $platform_code) {

        $data = self::find()->select('old_account_id')->where("id=:id AND platform_code = :platform_code", [':id' => $id, ':platform_code' => $platform_code])->scalar();

        return $data;
    }

    public static function findAccountAll($id, $platform_code) {

        $data = self::find()
                ->select('id,account_name')
                ->where(['in', 'old_account_id', $id])
                ->andWhere(['platform_code' => $platform_code])
                ->asArray()
                ->all();
        $account = [];
        foreach ($data as $k => $v) {
            $account[$v['id']] = $v['account_name'];
        }
        return $account;
    }

    public static function findCountAll($id) {

        $data = self::find()
            ->select('id,account_name')
            ->where(['in', 'id', $id])
            ->asArray()
            ->all();
        $account = [];
        foreach ($data as $k => $v) {
            $account[$v['id']] = $v['account_name'];
        }
        return $account;
    }

    public static function findAccountId($id, $platform_code) {

        $data = self::find()
                ->select('id')
                ->where(['in', 'old_account_id', $id])
                ->andWhere(['platform_code' => $platform_code])
                ->asArray()
                ->column();

        return $data;
    }

    /**
     * 通过account_id查询ship_code
     */
    public static function findSiteCode($account_id, $platform_code) {

        $data = self::find()->where("old_account_id=:account AND platform_code = :platform_code", [':account' => $account_id, ':platform_code' => $platform_code])->one();

        return $data['site_code'];
    }

    /**
     * @desc 获取账号相关信息
     * @param unknown $platformCode
     * @param unknown $accountName
     * @return multitype:
     */
    public static function getAccountFromErp($platformCode, $accountName) {
        $account = [];
        if (empty($platformCode) || empty($accountName))
            return $account;
        $cacheKey = md5($platformCode . '_' . $accountName);
        //从缓存获取订单数据
        if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, 'erp_account') && !empty(\Yii::$app->memcache->get($cacheKey, 'erp_account'))) {
            return \Yii::$app->memcache->get($cacheKey, 'erp_account');
        }
        //从接口获取订单数据
        $params = ['platformCode' => $platformCode, 'accountName' => $accountName];
        $ErpAccountApi = new ErpAccountApi();
        $ErpAccountApi->setApiMethod('getAccount')
                ->sendRequest($params, 'get');
        if ($ErpAccountApi->isSuccess()) {
            $response = $ErpAccountApi->getResponse();
            $account = $response->account;
            if (!empty($account) && isset(\Yii::$app->memcache))
                \Yii::$app->memcache->set($cacheKey, $account, 'erp_account');
        }
        return $account;
    }

    /**
     * @desc 获取账号相关信息
     * @param unknown $platformCode
     * @param unknown $accountName
     * @return multitype:
     */
    public static function getPlatformAccountsFromErp($platformCode) {
        $accounts = [];
        if (empty($platformCode))
            return $accounts;
        $cacheKey = md5($platformCode);
        $namespace = 'erp_account';
        //从缓存获取订单数据
        if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $namespace) && !empty(\Yii::$app->memcache->get($cacheKey, $namespace))) {
            return \Yii::$app->memcache->get($cacheKey, $namespace);
        }
        //从接口获取订单数据
        $erpAccountApi = new ErpAccountApi();
        $result = $erpAccountApi->getPlatformAccounts($platformCode);
        if (empty($result))
            return $accounts;
        $accounts = $result->accounts;
        if (!empty($accounts) && isset(\Yii::$app->memcache))
            \Yii::$app->memcache->set($cacheKey, $accounts, $namespace);
        return $accounts;
    }

    /**
     * 根据平台code获取该平台下的账号数据
     * @param $platform_code
     */
    public static function getAccountByPlatformCode($platform_code, $type = 1) {
        $query = new \yii\db\Query();
//        if ($type == 1)
//            $select = 'id,account_name';
//        else
        $select = 'id, account_name';
        $data = $query->from(self::tableName())->select($select)
                ->where('status = :status and platform_code=:platform_code', [':status' => static::STATUS_VALID, ':platform_code' => $platform_code])
                ->orderBy('account_name asc')
                ->all();
        return $data;
    }

    /**
     * 返回指定平台code的所有账号，停用的也返回
     */
    public static function getAllAccountByPlatformCode($platform_code) {
        return self::find()->select('id, account_name')
                        ->where(['platform_code' => $platform_code])
                        ->asArray()
                        ->all();
    }

    public static function getAccount($platform_code, $type = 1) {
        $returnData = [];
        if ($type == 1) {
            $select = 'id,account_name';
        } else {
            $select = 'old_account_id as id, account_name';
        }
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())->select($select)
                ->where('status = :status and platform_code=:platform_code', [':status' => static::STATUS_VALID, ':platform_code' => $platform_code])
                ->orderBy('account_name asc')
                ->all();
        if (!empty($data)) {
            foreach ($data as $value) {
                $returnData[$value['id']] = $value['account_name'];
            }
        }
        return $returnData;
    }

    /**
     * @desc 获取当前用户指定平台账号列表
     * @param unknown $userId
     * @param unknown $platformCode
     * @return Ambigous <multitype:, \yii\db\array>
     */
    public static function getCurrentUserPlatformAccountList($platformCode, $status = null) {
        $userId = '';
        $userAccountList = [];
        if (isset(\Yii::$app->user) && ($idenetity = \Yii::$app->user->getIdentity()))
            $userId = $idenetity->id;
        if ($userId == '')
            $userAccountList = [];
        $userAccountIds = UserAccount::getCurrentUserPlatformAccountIds($platformCode);
        $query = self::find();
        $query->from(self::tableName())
                ->select("*")
                ->where('platform_code = :platform_code', ['platform_code' => $platformCode]);
//        if (!empty($userAccountIds))
        $query->andWhere(['in', 'id', $userAccountIds]);
        if (!is_null($status))
            $query->andWhere(['status' => (int) $status]);
        return $query->all();
    }

    /**
     * @desc 根据邮箱获取所有账号
     * @param unknown $email
     */
    public static function getAccountsByEmail($email) {
        $query = self::find();
        return $query->where(['email' => $email])->all();
    }

    /**
     * @desc 根据账号id和平台code查找site_code
     * @param unknown $old_account_id erp系统里面对应账号ID
     * @param unknown $platform_code
     */
    public static function getSiteCode($old_account_id, $platform_code) {
        $condition = 'platform_code=:platform_code and old_account_id=:old_account_id';
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())->select('id,site_code')
                ->where($condition, [':platform_code' => $platform_code, ':old_account_id' => $old_account_id])
                ->one();

        if (empty($data)) {
            return null;
        }

        return $data['site_code'];
    }

    public static function getIdNameKVList($paltformCode) {
        $accountIds = array_column(UserAccount::find()->select('account_id')->where(['platform_code' => $paltformCode, 'user_id' => \Yii::$app->user->getIdentity()->id])->asArray()->all(), 'account_id');
        $conidtion = ['id' => $accountIds, 'platform_code' => $paltformCode];
        if ($paltformCode != Platform::PLATFORM_CODE_EB)
            $conidtion['status'] = 1;
        $array = self::find()->select('id,account_name')->where($conidtion)->orderBy('account_name')->asArray()->all();
        $array = array_merge([['id' => ' ', 'account_name' => '全部']], $array);
        return array_column($array, 'account_name', 'id');

        return array_column(self::find()->select('id,account_name')->where($conidtion)->orderBy('account_name')->asArray()->all(), 'account_name', 'id');
    }

    /**
     * 获取平台的old account_id 和account_name
     * @param $paltformCode
     * @return array
     */
    public static function getIdNameKefuList($paltformCode) {
        $accountIds = array_column(UserAccount::find()->select('account_id')->where(['platform_code' => $paltformCode, 'user_id' => \Yii::$app->user->getIdentity()->id])->asArray()->all(), 'account_id');
        $conidtion = ['id' => $accountIds, 'platform_code' => $paltformCode];
        if ($paltformCode != Platform::PLATFORM_CODE_EB) {
            $conidtion['status'] = 1;
            $array = self::find()->select('old_account_id,account_name')->where($conidtion)->orderBy('account_name')->asArray()->all();

            return array_column($array, 'account_name', 'old_account_id');
        }
        return array_column(self::find()->select('old_account_id,account_name')->where($conidtion)->orderBy('account_name')->asArray()->all(), 'account_name', 'old_account_id');
    }

    public static function getIdNameList($paltformCode) {
        $accountIds = array_column(UserAccount::find()->select('account_id')->where(['platform_code' => $paltformCode, 'user_id' => \Yii::$app->user->getIdentity()->id])->asArray()->all(), 'account_id');
        $conidtion = ['id' => $accountIds, 'platform_code' => $paltformCode];
        if ($paltformCode != Platform::PLATFORM_CODE_EB) {
            $conidtion['status'] = 1;
            $array = self::find()->select('id,account_name')->where($conidtion)->orderBy('account_name')->asArray()->all();

            return array_column($array, 'account_name', 'id');
        }
        return array_column(self::find()->select('id,account_name')->where($conidtion)->orderBy('account_name')->asArray()->all(), 'account_name', 'id');
    }

    /*
     * @desc 根据历史订单帐号ID查找帐号
     * @param1 int id
     * @param2 string platformCode
     * */

    public static function getHistoryAccount($accountId, $platformCode) {
        $record = self::find()->where('old_account_id=:aid and platform_code = :platCode and status=:status', [':aid' => $accountId, ':platCode' => $platformCode, ':status' => self::STATUS_VALID])->one();
        if (!empty($record))
            return $record->account_name;
        else
            return 'NoAccount';
    }

    /*
     * @desc 根据历史订单帐号ID查找帐号
     * @param1 int id
     * @param2 string platformCode
     * */

    public static function getHistoryAccountInfo($accountId, $platformCode) {
        $record = self::find()->where('old_account_id=:aid and platform_code = :platCode and status=:status', [':aid' => $accountId, ':platCode' => $platformCode, ':status' => self::STATUS_VALID])->one();
        if (!empty($record))
            return $record;
        else
            return false;
    }

    /*
     * @desc 根据帐号ID查找帐号名
     * @param1 int id
     * @param2 string platformCode
     * */

    public static function getAccountName($accountId, $platformCode) {
        $record = self::find()->where('id=:aid and platform_code = :platCode and status=:status', [':aid' => $accountId, ':platCode' => $platformCode, ':status' => self::STATUS_VALID])->one();
        if (!empty($record))
            return $record->account_name;
        else
            return 'NoAccount';
    }

    public static function getAccountNameByOldAccountId($old_accountId, $platformCode) {
        $record = self::find()->where('old_account_id=:aid and platform_code = :platCode and status=:status', [':aid' => $old_accountId, ':platCode' => $platformCode, ':status' => self::STATUS_VALID])->one();
        if (!empty($record))
            return $record->account_name;
        else
            return 'NoAccount';
    }

    /**
     * @param $accountId
     * @param $platformCode
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getAccountNameAndShortName($accountId, $platformCode) {
        $record = self::find()->where('id=:aid and platform_code = :platCode and status=:status', [':aid' => $accountId, ':platCode' => $platformCode, ':status' => self::STATUS_VALID])->one();
        if (!empty($record))
            return $record;
        else
            return [
                'account_name' => 'NoAccount',
                'account_short_name' => 'NoAccountShortName'
            ];
    }

    /**
     * 获取站点域名列表,如果传了站点id参数则返回对应站点域名
     * @param type $account_id
     * @return type
     * @author allen <2018-04-03>
     */
    public static function getSiteList($platform_code, $account_id = NULL) {
        $returnData = [];
        $model = self::find()->select(['old_account_id', 'site'])->where(['platform_code' => $platform_code, 'status' => 1])->asArray()->all();
        if (!empty($model)) {
            foreach ($model as $value) {
                $returnData[$value['old_account_id']] = $value['site'];
            }

            if (!empty($account_id)) {
                $returnData = $returnData[$account_id];
            }
        }

        return $returnData;
    }

    /**
     * 把ERP账号ID转换成客服账号ID
     */
    public static function erpToKefuAccountIds($platformCode = '') {
        if (empty($platformCode)) {
            return false;
        }

        $data = self::find()->select('id, old_account_id')->where(['platform_code' => $platformCode])->asArray()->all();

        if (!empty($data)) {
            $tmp = [];
            foreach ($data as $item) {
                $tmp[$item['old_account_id']] = $item['id'];
            }
            $data = $tmp;
        }
        return $data;
    }

    /**
     * 获取指定平台所有账号信息
     * @param $platformCode
     */
    public static function getAccounts($platformCode = '') {
        if (empty($platformCode)) {
            return false;
        }
        $data = self::find()->where(['platform_code' => $platformCode, 'status' => self::STATUS_VALID])->asArray()->all();
        if (!empty($data)) {
            $tmp = [];
            foreach ($data as $item) {
                $tmp[$item['old_account_id']] = $item;
            }
            $data = $tmp;
        }
        return $data;
    }

    /**
     * 获取所有平台有效账号列表 【id -> account_name】
     * @return type
     * @author allen <>
     */
    public static function getFullAccounts() {
        $res = [];
        $data = self::find()->where(['status' => 1])->asArray()->all();
        if (!empty($data)) {
            foreach ($data as $value) {
                $res[$value['id']] = $value['account_name'];
            }
        }
        return $res;
    }

    /**
     * @desc 获取ID和简称的键值对
     * @param string $platformCode
     * @return multitype:Ambigous <\yii\db\ActiveRecord>
     */
    public static function getOldIdShortNamePairs($platformCode = '') {
        $list = [];
        /**
         * @var $query \yii\db\ActiveQuery
         */
        $query = self::find()->select('old_account_id, account_short_name');
        if (!empty($platformCode))
            $query->where('platform_code = :platform_code', [':platform_code' => $platformCode]);
        $result = $query->all();
        if (!empty($result)) {
            foreach ($result as $row) {
                $list[$row['old_account_id']] = $row['account_short_name'];
            }
        }
        return $list;
    }

    /**
     * 获取账号id(erp 账号id) 账号邮箱
     * @param $platform_code
     * @param $account_name
     * @return mixed|string
     */
    public static function getOldAccountId($platform_code, $account_name) {
        $model = self::find()->select('old_account_id,email')
                        ->where(['platform_code' => $platform_code, 'status' => 1, 'account_name' => $account_name])
                        ->asArray()->one();
        if (!empty($model['old_account_id'])) {
            return $model;
        } else {
            return '';
        }
    }
    
    /**
     * 获取亚马逊站点cod
     * @param type $oldAccountId
     * @return string
     * @author allen <2018-12-25>
     */
    public static function getAmazonSite($oldAccountId){
        $res = self::find()->select(['site_code'])->where(['old_account_id' => $oldAccountId])->asArray()->one();
        if(!empty($res)){
            return strtolower($res['site_code']);
        }else{
            return '';
        }
    }

}
