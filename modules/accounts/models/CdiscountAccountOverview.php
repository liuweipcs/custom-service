<?php

namespace app\modules\accounts\models;

use Yii;
use app\components\Model;
use yii\data\Sort;
use app\modules\users\models\Role;
use app\modules\users\models\UserRole;
use app\modules\users\models\User;
use yii\db\Query;
use yii\data\ActiveDataProvider;

/**
 * ebay账号表现
 */
class CdiscountAccountOverview extends Model
{

    //月份
    public static $sellerMonths = [
         0 => '全部',
        12 => '12月',
        11 => '11月',
        10 => '10月',
         9 => '9月',
         8 => '8月',
         7 => '7月',
         6 => '6月',
         5 => '5月',
         4 => '4月',
         3 => '3月',
         2 => '2月',
         1 => '1月',
    ];

    /**
     * 返回操作表名
     */
    public static function tableName()
    {
        return '{{%cdiscount_seller_indicators}}';
    }

    /**
     * 属性字段
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [
            'account_short_name',
            'user_name',
            'account_id',
            'refund_rate',
            'refunds_rate',
            'claim_rate',
            'claims_rate',

        ];
        return array_merge($attributes, $extAttributes);
    }

    public function rules()
    {
        return [
            [
                [
                    'account_short_name',
                    'user_name',
                    'account_id',
                    'refund_rate',
                    'refunds_rate',
                    'claim_rate',
                    'claims_rate',
                ],
                'safe'
            ],
        ];
    }

    /**
     * @param $day
     *
     */
    public static function getSellerAccount($day)
    {
        $result = self::find()
            ->select('t.account_id, t.claim_rate, t.refund_rate, t.claims_rate, t.refunds_rate, t1.account_short_name')
            ->from('{{%cdiscount_seller_indicators}} t')
            ->join('LEFT JOIN', '{{%account}} t1', 't.account_id = t1.id')
            ->where(['t.indicators_time' => $day])
            ->asArray()
            ->all();

        return $result;

    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     * CD客服
     */
   public static function getKefuName()
   {

       $user_id = Yii::$app->user->identity->id;
       $role_id = UserRole::findOne(['user_id' => $user_id])->role_id;
       $roleIds = [];
       //如果是admin 显示所有用户
       if ($role_id == 1) {
           $roleIds = self::getAllRoleIds(Platform::PLATFORM_CODE_CDISCOUNT);
           $user_id = UserRole::find()->select('user_id')->where(['in','role_id',$roleIds])->asArray()->column();
           $userList = User::find()
               ->select(['id', 'user_name'])
               ->where(['in', 'id', $user_id])
               ->andWhere(['status' => 1])
               ->andWhere(['<>', 'role_id', 1])
               ->asArray()
               ->all();
       } else {
           self::getChildRoleIds($role_id, $roleIds, Platform::PLATFORM_CODE_CDISCOUNT);
           $user_id = UserRole::find()->select('user_id')->where(['in','role_id',$roleIds])->column();
           $userList = User::find()
               ->select(['id', 'user_name'])
               ->where(['in', 'id', $user_id])
               ->andWhere(['status' => 1])
               ->asArray()
               ->all();
       }
       $user_list = array_column($userList, 'user_name', 'user_name');
       array_unshift($user_list, '全部');

       return $user_list;

   }

    /**
     * 获取客服与账号对应关系
     */
    public static function getkefuAccount()
    {
        $user_list = self::getKefuName();

        $userList = (new Query())->select('user_name, account_ids')
            ->from('{{%user}} t')
            ->join('LEFT JOIN','{{%orderservice}} t1','t.id = t1.user_id')
            ->where(['in', 't.user_name',$user_list])
            ->andWhere(['t1.platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
            ->createCommand(Yii::$app->db_system)
            ->queryAll();
        $userList = array_column($userList, 'account_ids', 'user_name');
        $user_account = [];
        foreach ($userList as $k =>$item){
            if(empty($item)){
                unset($item);
            }else{
                $old_account_id = explode(',',$item);
                $account_id = Account::find()->select('id')
                    ->where(['in','old_account_id',$old_account_id])
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
                    ->asArray()->column();
                $user_account[$k] = $account_id;
            }

        }
        return $user_account;

   }

    /**
     * @param $ranges
     * @param $account
     * cd账号对应某天的账号表现
     */
   public static function getCdiscountStatistics($ranges, $account)
   {
       foreach ($ranges as $k => $item){
           $query = self::find()
               ->select('t.claim_rate, t.refund_rate, t.claims_rate, t.refunds_rate, t1.account_name')
               ->from('{{%cdiscount_seller_indicators}} t')
               ->join('LEFT JOIN', '{{%account}} t1', 't.account_id = t1.id')
               ->andWhere(['t.indicators_time' => $item['start_time']]);
               if (!empty($account)) {
                   $query->andWhere(['in', 't.account_id', $account]);
               }
           $result[$k] = $query->limit(10)->asArray()
               ->all();
       }
       return $result;
   }
    /**
     * @param array $params
     * @param null $sort
     * 搜索
     */
    public function searchList($params = [])
    {

        $day = date('Y-m-d 00:00:00',strtotime('-2 day'));
        $query = self::find();
        $query->andWhere(['indicators_time' => $day]);

        $this->load($params);

        //只能查询到客服绑定账号
      /*  $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_CDISCOUNT);
        $query->andWhere(['in', 'account_id', $accountIds]);*/

        if(!empty($this->user_name)){
            $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $this->user_name])
                ->createCommand(Yii::$app->db_system)
                ->queryColumn();
            $account_old_id = (new Query())->select('account_ids')->from('{{%orderservice}}')->where(['user_id' => $user_id, 'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
                ->createCommand(Yii::$app->db_system)
                ->queryOne();
                $account_old_id = explode(',', $account_old_id['account_ids']);
                $account_id = Account::find()->select('id')->where(['in', 'old_account_id', $account_old_id])->asArray()->column();
                $query->andWhere(['in', 'account_id', $account_id]);
        }


        if(!empty($this->account_id)){
            $query->andWhere(['account_id' => $this->account_id]);
        }

        if(!empty($this->refund_rate)){
            if($this->refund_rate == 1){
                $query->andWhere(['>', 'refund_rate', 5]);
            }elseif($this->refund_rate == 2){
                $query->andWhere(['between', 'refund_rate', 4, 5]);
            }else{
                $query->andWhere(['<', 'refund_rate', 4]);
            }
        }

        if(!empty($this->claim_rate)){
            if($this->claim_rate == 1){
                $query->andWhere(['>', 'claim_rate', 1]);
            }elseif($this->claim_rate == 2){
                $query->andWhere(['between', 'claim_rate', 0.8, 1]);
            }else{
                $query->andWhere(['<', 'claim_rate', 0.8]);
            }
        }

        if(!empty($this->refunds_rate)){
            if($this->refunds_rate == 1){
                $query->andWhere(['>', 'refunds_rate', 5]);
            }elseif($this->refunds_rate == 2){
                $query->andWhere(['between', 'refunds_rate', 4, 5]);
            }else{
                $query->andWhere(['<', 'refunds_rate', 4]);
            }
        }

        if(!empty($this->claims_rate)){
            if($this->claims_rate == 1){
                $query->andWhere(['>', 'claims_rate', 1]);
            }elseif($this->claims_rate == 2){
                $query->andWhere(['between', 'claims_rate', 0.8, 1]);
            }else{
                $query->andWhere(['<', 'claims_rate', 0.8]);
            }
        }

        $sort = new Sort();
        $sort->defaultOrder = [
            'id' => SORT_DESC,
        ];

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => $sort,
            'pagination' => [
                'pageSize' => !empty($params['page_size']) ? $params['page_size'] : 20,
                'pageParam' => 'p',
                'pageSizeParam' => 'page_size',
            ],
        ]);
        $models = $dataProvider->getModels();


        if(empty($this->user_name)){
            $userList = self::getkefuAccount();

            foreach ($userList as $k => $v){
                foreach ($models as $kk => $vv){
                    if(in_array($vv->account_id,$v)){
                        $vv['user_name'] = $k;
                        $user_list[$kk] = $vv;
                    }
                }
            }
        }else{
            foreach ($models as $kk => $vv){
                $vv['user_name'] = $this->user_name;
                $user_list[$kk] = $vv;
            }
        }

        $this->chgModel($user_list, $dataProvider, $params);
        $dataProvider->setModels($user_list);
        return $dataProvider;
    }

    /**
     * @param string $platformCode
     * @return array
     */
    public static function getAllRoleIds($platformCode = '')
    {
        return Role::find()->select('id')
            ->andWhere(['like', 'platform_code', $platformCode])
            ->asArray()
            ->column();
    }

    /**
     * 修改模型数据
     */
    public function chgModel(&$user_list, $dataProvider, $params)
    {

        if(!empty($user_list)){
            foreach ($user_list as $key => $model) {
                    $account_short_name = Account::findOne(['id' => $model->account_id]);
                    $model->user_name = $model->user_name;
                    $model->account_short_name = $account_short_name->account_short_name;

                    if($model->refund_rate > 5){
                        $refundRate = $model->refund_rate .'%';
                        $refund_rate_data = "<span style='color:red;font-weight:bold;'>{$refundRate}</span>";
                    }elseif($model->refund_rate < 4){
                        $refundRate = $model->refund_rate .'%';
                        $refund_rate_data = "<span style='color:green;font-weight:bold;'>{$refundRate}</span>";
                    }else{
                        $refundRate = $model->refund_rate .'%';
                        $refund_rate_data = "<span style='color:orange;font-weight:bold;'>{$refundRate}</span>";
                    }

                    if($model->refunds_rate > 5){
                        $refundsRate = $model->refunds_rate .'%';
                        $refunds_rate_data = "<span style='color:red;font-weight:bold;'>{$refundsRate}</span>";
                    }elseif ($model->refund_rate < 4){
                        $refundsRate = $model->refunds_rate .'%';
                        $refunds_rate_data = "<span style='color:green;font-weight:bold;'>{$refundsRate}</span>";
                    }else{
                        $refundsRate = $model->refunds_rate .'%';
                        $refunds_rate_data = "<span style='color:orange;font-weight:bold;'>{$refundsRate}</span>";
                    }

                    if($model->claim_rate > 1){
                        $claimRate = $model->claim_rate .'%';
                        $claim_rate_data = "<span style='color:red;font-weight:bold;'>{$claimRate}</span>";
                    }elseif ($model->claim_rate < 0.8){
                        $claimRate = $model->claim_rate .'%';
                        $claim_rate_data = "<span style='color:green;font-weight:bold;'>{$claimRate}</span>";
                    }else{
                        $claimRate = $model->claim_rate .'%';
                        $claim_rate_data = "<span style='color:orange;font-weight:bold;'>{$claimRate}</span>";
                    }

                    if($model->claims_rate > 1){
                        $claimsRate = $model->claims_rate .'%';
                        $claims_rate_data = "<span style='color:red;font-weight:bold;'>{$claimsRate}</span>";
                    }elseif ($model->claims_rate < 0.8){
                        $claimsRate = $model->claims_rate .'%';
                        $claims_rate_data = "<span style='color:green;font-weight:bold;'>{$claimsRate}</span>";
                    }else{
                        $claimsRate = $model->claims_rate .'%';
                        $claims_rate_data = "<span style='color:orange;font-weight:bold;'>{$claimsRate}</span>";
                    }

                    $model->setAttribute('refund_rate', $refund_rate_data);
                    $model->setAttribute('refunds_rate', $refunds_rate_data);
                    $model->setAttribute('claim_rate', $claim_rate_data);
                    $model->setAttribute('claims_rate', $claims_rate_data);




            }
        }


    }

    /**
     * @param int $parentRoleId
     * @param array $roleIds
     * @param string $platformCode
     * @return array
     */
    public static function getChildRoleIds($parentRoleId = 0, &$roleIds = [], $platformCode = '')
    {
        $ids = Role::find()->select('id')
            ->andWhere(['parent_id' => $parentRoleId])
            ->andWhere(['like', 'platform_code', $platformCode])
            ->asArray()
            ->column();

        if (empty($ids)) {
            return array_unique($roleIds);
        }
        $roleIds = array_merge($roleIds, $ids);
        foreach ($ids as $id) {
            self::getChildRoleIds($id, $roleIds, $platformCode);
        }
    }

    /**
     * 获取账号列表
     */
    public static function getAccountList()
    {
        $data = Account::find()
            ->select('id, account_short_name')
            ->where(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT, 'status' => Account::STATUS_VALID])
            ->asArray()
            ->all();

        $accountList = ['' => '--请选择账号--'];
        if (!empty($data)) {
            foreach ($data as $item) {
                $accountList[$item['id']] = $item['account_short_name'];
            }
        }

        return $accountList;
    }

    /**
     * 30天退款率
     */
    public static function getReturnRate()
    {
        return [
            '' => '全部',
            1 => '大于5%',
            2 => '4%到5%',
            3 => '小于4%',
        ];
    }

    /**
     * 30天纠纷率
     */
    public static function getClaimRate()
    {
        return [
            '' => '全部',
            1 => '大于1%',
            2 => '0.8%到1%',
            3 => '小于0.8%',
        ];
    }

    /**
     * 60天退款率
     */
    public static function getReturnsRate()
    {
        return [
            '' => '全部',
            1 => '大于5%',
            2 => '4%到5%',
            3 => '小于4%',
        ];
    }

    /**
     * 60天纠纷率
     */
    public static function getClaimsRate()
    {
        return [
            '' => '全部',
            1 => '大于1%',
            2 => '0.8%到1%',
            3 => '小于0.8%',
        ];
    }
}