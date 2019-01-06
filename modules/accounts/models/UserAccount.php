<?php

/**
 * @desc 账号模型
 * @author Fun
 */

namespace app\modules\accounts\models;

use Yii;

class UserAccount extends AccountsModel {

    /**
     * @desc 设置表名
     * @return string
     */
    public $platform_name;
    public $site_code;
    public $account_name;
    public $type;

    public static function tableName() {
        return '{{%user_account}}';
    }

    /**
     * @desc 获取用户绑定平台账号ID
     * @param unknown $userId
     * @return Ambigous <multitype:, \yii\db\static>
     */
    public static function getUserAccountIds($userId) {
        $list = [];
        $res = self::findAll(['user_id' => $userId]);
        if (!empty($res)) {
            foreach ($res as $row)
                $list[$row['platform_code']][] = $row['account_id'];
        }
        return $list;
    }

    /**
     * @desc 绑定用户账号
     * @param unknown $userId
     * @param unknown $accountIds
     * @throws \Exception
     * @return boolean
     */
    public static function bindUserAccount($userId, $accountIds) {
        $dbTransaction = self::getDb()->beginTransaction();
        try {
            $inserDatas = [];
            foreach ($accountIds as $accountId) {
                $accountInfo = Account::findById($accountId);
                if (!empty($accountInfo))
                    $inserDatas[] = [$userId, $accountInfo->platform_code, $accountId];
            }
            self::deleteAll('user_id = :user_id', ['user_id' => $userId]);
            if (!empty($inserDatas)) {
                //删除用户所有已经绑定的账号
                self::deleteAll('user_id = :user_id', ['user_id' => $userId]);
                $flag = self::getDb()->createCommand()
                        ->batchInsert(self::tableName(), ['user_id', 'platform_code', 'account_id'], $inserDatas)
                        ->execute();
                if (!$flag)
                    throw new \Exception('Delete Failed');
            }
            $dbTransaction->commit();
            return true;
        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            return false;
        }
    }

    /**
     * @desc 获取
     * @param unknown $userId
     * @param unknown $platformCode
     * @return Ambigous <multitype:, \yii\db\array>
     */
    public static function getUserPlatformAccountIds($userId, $platformCode) {
        $query = new \yii\db\Query();
        $query->from(self::tableName())
                ->select('account_id')
                ->where('user_id = :user_id', ['user_id' => $userId])
                ->andWhere('platform_code = :platform_code', ['platform_code' => $platformCode]);
        $list = $query->column();
        if (empty($list))
            $list = [];
        return $list;
    }

    /**
     * @desc 获取
     * @param unknown $userId
     * @param unknown $platformCode
     * @return Ambigous <multitype:, \yii\db\array>
     */
    public static function getCurrentUserPlatformAccountIds($platformCode) {
        /*         $session = \Yii::$app->session;
          if ($session->has('_user_account_ids'))
          return $session->get('_user_account_ids'); */
        $userId = '';
        $userAccountIds = [];
        if (isset(\Yii::$app->user) && ($idenetity = \Yii::$app->user->getIdentity()))
            $userId = $idenetity->id;
        if ($userId == '')
            $userAccountIds = [];
        $userAccountIds = self::getUserPlatformAccountIds($userId, $platformCode);
        //$session->set('_user_account_ids', $userAccountIds);
        return $userAccountIds;
    }

    /**
     * 获取登录用户绑定的平台账号
     * @author allen <2018-06-14>
     */
    public static function getLoginUserPlatformAccounts($code = Null) {
        $data = ['' => '--请选择--'];
        $userId = Yii::$app->user->getIdentity()->id;
        if ($userId) {
            $model = UserAccount::find()
                    ->select(['t.platform_code', 'p.platform_name'])
                    ->from('{{%user_account}} t')
                    ->join('LEFT JOIN', '{{%platform}} p', 't.platform_code = p.platform_code')
                    ->where(['user_id' => $userId])
                    ->groupBy('t.platform_code')
                    ->orderBy('t.platform_code')
                    ->all();

            if (!empty($model)) {
                    foreach ($model as $value) {
                        if($code == 'code'){
                            $data[] = $value['platform_code'];
                        }else{
                            $data[$value['platform_code']] = $value['platform_name'];
                        }
                }
            }
        }
        return $data;
    }

    /**
     * 获取登录用户绑定的平台账号
     * 仅限EB，ALI
     * @author JD <2018-10-15>
     */
    public static function getLoginUserPlatformList($code = Null) {
        $data = [0 => '全部'];
        $userId = Yii::$app->user->getIdentity()->id;
        if ($userId) {
            $model = UserAccount::find()
                ->select(['t.platform_code', 'p.platform_name'])
                ->from('{{%user_account}} t')
                ->join('LEFT JOIN', '{{%platform}} p', 't.platform_code = p.platform_code')
                ->where(['user_id' => $userId])
                ->groupBy('t.platform_code')
                ->orderBy('t.platform_code')
                ->all();

            if (!empty($model)) {
                foreach ($model as $value) {
                    if($value['platform_code'] == 'EB' || $value['platform_code'] == 'ALI' ){
                        if($code == 'code'){
                            $data[] = $value['platform_code'];
                        }else{
                            $data[$value['platform_code']] = $value['platform_name'];
                        }
                    }

                }
            }
        }
        return $data;
    }

    public static function getAccoutOrSite($platform_code, $type = '') {
        $data = [];
        $userId = Yii::$app->user->getIdentity()->id;
        if ($userId) {
            $query = UserAccount::find();
            $query->select(['t.account_id', 'a.account_name', 'a.site_code']);
            $query->from('{{%user_account}} t');
            $query->join('LEFT JOIN', '{{%account}} a', 'a.id = t.account_id');
            $query->where(['t.platform_code' => $platform_code, 'user_id' => $userId]);

            //type：2=>根据站点分组  
            if(is_array($type)){
                if($type[0] == 'site'){
                    $type = 2;
                }
                
                if($type[0] == 'account'){
                    $type = 1;
                }
            }
            if ($type == 2) {
                $query->groupBy('a.site_code');
                $query->orderBy('a.site_code');
            } else {
                //未设置type 或者type=1按账号分组
                $query->groupBy('t.account_id');
                $query->orderBy('t.account_id');
            }
            $model = $query->all();

            if (!empty($model)) {
                if ($platform_code == 'AMAZON') {
                    switch ($type) {
                        case '':
                            $data = [
                                'account' => '账号',
                                'site' => '站点'
                            ];
                            break;
                        case '1':
                            foreach ($model as $value) {
                                $data[$value['account_id']] = $value['account_name'];
                            }
                            break;
                        case '2':
                            foreach ($model as $value) {
                                $data[$value['site_code']] = $value['site_code'];
                            }
                            break;
                    }
                } else {
                    foreach ($model as $value) {
                        $data[$value['account_id']] = $value['account_name'];
                    }
                }
            }
        }
        return $data;
    }

}
