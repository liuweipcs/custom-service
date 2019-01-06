<?php

namespace app\modules\customer\models;

use app\components\Model;
use yii\data\Sort;
use app\modules\accounts\models\Platform;
use app\modules\users\models\Role;
use Yii;
use app\modules\customer\models\CustomerTagsDetail;
use app\modules\customer\models\CustomerOperation;
use app\modules\systems\models\BasicConfig;
use app\modules\accounts\models\Account;


class CustomerList extends Model
{

    const WECHAT = 'Wechat';
    const SKYPE = 'skype';
    const WHATSAPP = 'Whatsapp';
    const TRADEMANAGER = 'trademanager';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%customer_list}}';
    }


    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = [
            'buyer_id_email',
            'purchase_times_start',
            'purchase_times_end',
            'turnover_start',
            'turnover_end',
            'credit_rating_start',
            'credit_rating_end',
            'start_time',
            'end_time',
            'disputes_start',
            'disputes_end',
            'create_by_time',
            'tags_id',
            'group_id',
            'tag_name',
            'follow_status'
        ];
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * 设置规则
     */
    public function rules()
    {
        return [
            [['platform_code', 'buyer_id'], 'required'],
            [['platform_code', 'buyer_id', 'buyer_email', 'pay_email', 'account_name', 'phone', 'other_contacts', 'wechat', 'skype', 'whatsapp', 'trademanager', 'buyer_name'], 'string'],
            [['purchase_times', 'turnover', 'credit_rating', 'disputes_number', 'type'], 'integer'],
            [['modify_by', 'modify_time', 'create_by', 'create_time'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => '对应订单号',
            'platform_code' => '所属平台',
            'buyer_id_email' => '客户ID/邮箱',
            'buyer_id' => '客户姓名',
            'phone' => '电话号码',
            'pay_email' => '付款邮箱',
            'other_contacts' => '在线联系方式',
            'account_name' => '店铺',
            'status' => '状态',
            'purchase_times_start' => '客户购买次数',
            'purchase_times_end' => '~',
            'turnover_start' => '成交金额',
            'turnover_end' => '~',
            'credit_rating_start' => '信用评级',
            'credit_rating_end' => '~',
            'start_time' => '创建日期开始',
            'end_time' => '~',
            'disputes_start' => '纠纷次数',
            'disputes_end' => '~',
            'modify_by' => '更新人/更新时间',
            //'modify_time' => '更新时间',
            'customer_number' => '客服数',
            'tags_id' => '客户标签',
            'tag_name' => '客户标签',
            'group_id' => '客户分组',
            'create_by_time' => '创建人/创建日期',
            'type' => '添加类型',
            'purchase_times' => '购买次数',
            'turnover' => '成交金额',
            'disputes_number' => '纠纷次数',
            'credit_rating' => '信用评级',
            'buyer_name' => '客户名称',
            'follow_status' => '跟进状态',
        ];
    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {

        $role_id = Yii::$app->user->identity->role_id;
        //获取角色对应平台
        $platfrom = Role::findOne(['id' => $role_id])->platform_code;
        $platfrom = explode(',', $platfrom);
        $getGroup = Yii::$app->request->get();
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => Platform::getRolePlatform($platfrom),
                'search' => '=',
                'alis' => 't',
            ],
            [
                'name' => 'buyer_id_email',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name' => 'pay_email',
                'type' => 'text',
                'search' => 'LIKE',
                'alis' => 't',
            ],
            [
                'name' => 'purchase_times_start',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'purchase_times_end',
                'type' => 'text',
                'search' => '=',
            ],

            [
                'name' => 'turnover_start',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'turnover_end',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'credit_rating_start',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'credit_rating_end',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'start_time',
                'type' => 'date_picker',
                'search' => '>',
            ],
            [
                'name' => 'end_time',
                'type' => 'date_picker',
                'search' => '<',
            ],
            [
                'name' => 'disputes_start',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'disputes_end',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'tags_id',
                'type' => 'dropDownList',
                'data' => CustomerTags::getPlatformTags($platfrom),
                'search' => '=',
                'alis' => 't2',
            ],
            [
                'name' => 'group_id',
                'type' => 'dropDownList',
                'data' => CustomerGroup::getPlatformTags($platfrom),
                'search' => '=',
                'alis' => 't1',
                'value' => $getGroup['group_id'] ? $getGroup['group_id'] : null,
            ],
            [
                'name'   => 'tag_id',
                'type'   => 'hidden',
                'search' => false,
                'alias'  => 't3',
            ],
        ];
    }

    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [], $sort = NULL)
    {
        $query = self::find()->alias('t')->distinct();
        //默认排序方式
        $sort = new Sort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
        ]);
        if (!empty($params['platform_code'])) {
            $query->andWhere(['t.platform_code' => $params['platform_code']]);
            unset($params['platform_code']);
        }
        if (isset($params['buyer_id_email']) && !empty($params['buyer_id_email'])) {
            $query->andWhere([
                'or',
                ['t.buyer_id' => $params['buyer_id_email']],
                ['like', 't.buyer_email', '%' . $params['buyer_id_email'] . '%', false],
            ]);
            unset($params['buyer_id_email']);
        }
        if (!empty($params['tags_id'])) {
            $query->innerJoin(['t2' => CustomerTagsDetail::tableName()], 't2.buyer_id = t.id');
            $query->andWhere(['t2.tags_id' => $params['tags_id']]);
            unset($params['tags_id']);
        }

        if (!empty($params['group_id'])) {
            $query->innerJoin(['t1' => CustomerGroupDetail::tableName()], 't1.buyer_id = t.id');
            $query->andWhere(['t1.group_id' => $params['group_id']]);
            unset($params['group_id']);
        }

        if (!empty($params['tag_id'])) {
            $followStatusList = BasicConfig::getParentList(35);
            $follow_name = $followStatusList[$params['tag_id']];
            $query->innerJoin(['t3' => CustomerOperation::tableName()], 't3.buyer_id = t.id');
            $query->andWhere(['t3.follow_status' => $follow_name]);
            unset($params['tag_id']);
        }

        /* if(!empty($params['order_id'])){
             $query->andWhere(['t.order_id' => $params['order_id']]);
             unset($params['order_id']);
         }*/
        if (!empty($params['pay_email'])) {
            $query->andWhere(['t.pay_email' => $params['pay_email']]);
            unset($params['pay_email']);
        }

        //购买次数
        if (!empty($params['purchase_times_start']) && !empty($params['purchase_times_end'])) {
            $query->andWhere(['between', 't.purchase_times', $params['purchase_times_start'], $params['purchase_times_end']]);
            unset($params['purchase_times_start']);
            unset($params['purchase_times_end']);
        } else if (!empty($params['purchase_times_start'])) {
            $query->andWhere(['>=', 't.purchase_times', $params['purchase_times_start']]);
            unset($params['purchase_times_start']);
            unset($params['purchase_times_end']);
        } else if (!empty($params['purchase_times_end'])) {
            $query->andWhere(['<=', 't.purchase_times', $params['purchase_times_end']]);
            unset($params['purchase_times_start']);
            unset($params['purchase_times_end']);
        }

        //成交金额
        if (!empty($params['turnover_start']) && !empty($params['turnover_end'])) {
            $query->andWhere(['between', 't.turnover', $params['turnover_start'], $params['turnover_end']]);
            unset($params['turnover_start']);
            unset($params['turnover_end']);
        } else if (!empty($params['turnover_start'])) {
            $query->andWhere(['>=', 't.turnover', $params['turnover_start']]);
            unset($params['turnover_start']);
            unset($params['turnover_end']);
        } else if (!empty($params['turnover_end'])) {
            $query->andWhere(['<=', 't.turnover', $params['turnover_end']]);
            unset($params['turnover_start']);
            unset($params['turnover_end']);
        }

        //信用评级
        if (!empty($params['credit_rating_start']) && !empty($params['credit_rating_end'])) {
            $query->andWhere(['between', 't.credit_rating', $params['credit_rating_start'], $params['credit_rating_end']]);
            unset($params['credit_rating_start']);
            unset($params['credit_rating_end']);
        } else if (!empty($params['credit_rating_start'])) {
            $query->andWhere(['>=', 't.credit_rating', $params['credit_rating_start']]);
            unset($params['credit_rating_start']);
            unset($params['credit_rating_end']);
        } else if (!empty($params['credit_rating_end'])) {
            $query->andWhere(['<=', 't.credit_rating', $params['credit_rating_end']]);
            unset($params['credit_rating_start']);
            unset($params['credit_rating_end']);
        }
        //创建日期
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 't.create_time', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 't.create_time', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 't.create_time', $params['end_time']]);
        }

        //纠纷次数
        if (!empty($params['disputes_start']) && !empty($params['disputes_end'])) {
            $query->andWhere(['between', 't.disputes_number', $params['disputes_start'], $params['disputes_end']]);
            unset($params['disputes_start']);
            unset($params['disputes_end']);
        } else if (!empty($params['disputes_start'])) {
            $query->andWhere(['>=', 't.disputes_number', $params['disputes_start']]);
            unset($params['disputes_start']);
            unset($params['disputes_end']);
        } else if (!empty($params['disputes_end'])) {
            $query->andWhere(['<=', 't.disputes_number', $params['disputes_end']]);
            unset($params['disputes_start']);
            unset($params['disputes_end']);
        }

        // return parent::searchList($params, $sort);
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function addition(&$models)
    {
        foreach ($models as $key => $model) {
            $model->create_by_time = $model->create_by . '<br >' . $model->create_time;
            $model->modify_by = $model->modify_by . '<br >' . $model->modify_time;
            $model->buyer_id_email = $model->buyer_id . '<br >' . $model->buyer_email;
            $model->type = empty($model->type) ? '系统' : '手动';
            $model->pay_email = isset($model->pay_email) ? $model->pay_email : '';
            $model->purchase_times = '<a target="_blank" href="/orders/order/list?platform_codes='.$model->platform_code.'&condition_option=buyer_id&condition_value='.$model->buyer_id.'">'.$model->purchase_times.'</a>';
            if($model->disputes_number){
                $model->disputes_number ='<a target="_blank" href="/aftersales/sales/list?platform_code='.$model->platform_code.'&buyer_id='.$model->buyer_id.'">'.$model->disputes_number.'</a>';
            }
            $wechat = self::WECHAT . ':' . $model->wechat;
            $skype = self::SKYPE . ':' . $model->skype;
            $whatsapp = self::WHATSAPP . ':' . $model->whatsapp;
            $trademanager = self::TRADEMANAGER . ':' . $model->trademanager;
            $model->other_contacts = $wechat . "<br />" . $skype . "<br />" . $whatsapp . "<br />" . $trademanager;
            $tags = CustomerTagsDetail::find()
                ->andwhere([
                    'or',
                    ['platform_code' => $model->platform_code],
                    ['platform_code' => 'ALL']
                ])
                ->andWhere(['buyer_id' => $model->id])
                ->all();
            $tags_name = '';
            foreach ($tags as $item) {
                $tag_name = CustomerTags::findOne($item->tags_id);
                $tags_name .= $tag_name->tag_name . '<br >';
            }
            $model->tag_name = $tags_name;

            $follow_log = CustomerOperation::getFollowData($model->id);
            if($follow_log->mark) {
                $model->follow_status = '<span style="cursor:pointer;" data="'. $model->id .'" data1="1" data2="" class="not-set" data-toggle="modal" data-target="#myModal">('.$follow_log->follow_status.')</span><br/>'.$follow_log->mark;
            } else {
                $model->follow_status = '<span style="cursor:pointer;" data="' . $model->id . '" data1="1" data2="" class="not-set" data-toggle="modal" data-target="#myModal">(未跟进)</span>';

            }


            
        }
    }

    public static function getFollowList($params = [])
    {
        $followStatusList = BasicConfig::getParentList(35);
        unset($followStatusList[' ']);

/*        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $query->andWhere(['in', 't.account_id', $accountIds]);*/

        $data  = [];
        $query = CustomerOperation::find();
        $res   = $query->select('follow_status,count(1) as count')
            ->from('{{%customer_operation}}')
            ->groupBy('follow_status')
            ->asArray()
            ->all();

        foreach ($res as $k=>&$value) {
            if (!in_array($value['follow_status'],$followStatusList)) { 
                unset($res[$k]);
                continue;
            }  
            foreach ($followStatusList as $key => $val) {
                    if ($value['follow_status']==$val) {
                        $value['id'] = $key; 
                    }
            }    
            $value['name'] = $value['follow_status'];
            unset($value['follow_status']);
        }    

        return $res;
    }

    /**
     * @return int
     * 平台客户回购次数
     */
    public static function getRepurchaseTimes($platform_code, $account_id, $start_time, $end_time)
    {
        $query = self::find();

        $query->select('platform_code , sum(purchase_times - 1) as times')
            ->andWhere(['>=','purchase_times',2]);
        if(!empty($platform_code)){
           $query->andWhere(['platform_code' => $platform_code]);
        }
        if(!empty($start_time)){
            $query->andWhere(['>=','create_time',$start_time]);
        }
        if(!empty($start_time)){
            $query->andWhere(['<=','create_time',$end_time]);
        }
        if(!empty($account_id)){
            $query->andWhere(['in','account_id',$account_id]);
        }
        $data = $query->groupBy('platform_code')
            ->asArray()
            ->all();

        $data = array_column($data,'times','platform_code');
        $teams = 0;
        foreach($data as $k => $item){
            $teams += $item;
        }
        return $teams;
    }

    /**
 * @param $platform_code
 * @param $account_id
 * 平台客户回购率
 */
    public static function getBuyerRepurchase($platform_code, $account_id, $start_time, $end_time)
    {

        $query = self::find();
        $query1 = self::find();
        $query->andWhere(['>=','purchase_times',1]);
        $query1->andWhere(['>=','purchase_times',2]);
        if(!empty($platform_code)){
            $query->andWhere(['platform_code' => $platform_code]);
            $query1->andWhere(['platform_code' => $platform_code]);
        }
        if(!empty($start_time)){
            $query->andWhere(['>=','create_time',$start_time]);
            $query1->andWhere(['>=','create_time',$start_time]);
        }
        if(!empty($start_time)){
            $query->andWhere(['<=','create_time',$end_time]);
            $query1->andWhere(['<=','create_time',$end_time]);
        }

        if(!empty($account_id)){
            $query->andWhere(['in', 'account_id', $account_id]);
            $query1->andWhere(['in', 'account_id', $account_id]);
        }
        $data = $query->count();
        $data1 = $query1->count();

        if ($data) {
            $plat_repurchase_times = round($data1 / $data, 4) * 100 . "%";
        } else {
            $plat_repurchase_times = 0;
        }
        return $plat_repurchase_times;

    }


    /**
     * @return int
     * 账号客户回购次数
     */
    public static function getAccountRepurchaseTimes($platform_code, $account_id, $start_time, $end_time)
    {
        $query = self::find();

        $query->select('account_id , sum(purchase_times - 1) as times')
            ->andWhere(['>=','purchase_times',2]);
        if(!empty($platform_code)){
            $query->andWhere(['platform_code' => $platform_code]);
        }
        if(!empty($start_time)){
            $query->andWhere(['>=','create_time',$start_time]);
        }
        if(!empty($end_time)){
            $query->andWhere(['<=','create_time',$end_time]);
        }
        if(!empty($account_id)){
            $query->andWhere(['in','account_id',$account_id]);
        }
            $data = $query->groupBy('account_id')
            ->asArray()
            ->all();
        $teams = 0;
        foreach($data as $k => $item){
            $teams += $item;
        }

        return $teams;
    }

    /**
     * @param $range
     * @param $account
     * @param $platform_code
     * @return array
     * 根据账号统计回购次数
     */
    public static function getAccountNumber($range, $account, $platform_code)
    {
        $data = [];

        $init = [];

        if(!empty($platform_code) && !empty($account)){
            $account_info = Account::find()->select('old_account_id,account_name')->andWhere(['platform_code' => $platform_code])->andWhere(['in','old_account_id',$account])->andWhere(['status' => 1])->asArray()->all();
        }elseif(!empty($platform_code) && empty($account)){
            $account_info = Account::find()->select('old_account_id,account_name')->andWhere(['platform_code' => $platform_code])->andWhere(['status' => 1])->asArray()->all();
        }elseif(empty($platform_code) && empty($account)){
            $account_info = Account::find()->select('old_account_id,account_name')->andWhere(['platform_code' => 'EB'])->andWhere(['status' => 1])->asArray()->all();
        }
            $result = [];
            foreach ($account_info as $key => $value) {
                $result[$value['account_name']] = $value['old_account_id'];
            }
            $account = array_values($result);
            foreach ($result as $key => $value){
                $init[$key] = 0;
            }

        foreach ($range as $key => $item) {
            $data[$key] = $init;
            $query = self::find()
                ->select('account_name, sum(purchase_times - 1) as times')
                ->andWhere(['>=', 'purchase_times', 2])
                ->andWhere(['between', 'create_time', $item['start_time'], $item['end_time']]);
            if(!empty($account)){
                $query = $query->andWhere(['in', 'account_id', $account]);
            }
            $query = $query->groupBy('account_id')
                ->orderBy('times DESC')
                ->limit(10)
                ->asArray()
                ->all();

            if (!empty($query)) {
                $result1 = array_column($query,'times','account_name');
                if (!empty($result)) {
                    foreach ($result as $kk => $vv) {
                        if (array_key_exists($kk, $result1) && !empty($result1[$kk])) {
                            $data[$key][$kk] = $result1[$kk];
                        }
                    }
                }
            }
            $tmp = $data[$key];
            arsort($tmp);
            $data[$key] = $tmp;
        }
        return $data;
    }

    /**
     * @param $range
     * @param $account
     * @param $platform_code
     * @return array
     * 根据账号统计回购率
     */
    public static function getAccountNumberRate($range, $account, $platform_code)
    {
        $data = [];

        $init = [];

        if(!empty($platform_code) && !empty($account)){
            $account_info = Account::find()->select('old_account_id,account_name')->andWhere(['platform_code' => $platform_code])->andWhere(['in','old_account_id',$account])->andWhere(['status' => 1])->asArray()->all();
        }elseif(!empty($platform_code) && empty($account)){
            $account_info = Account::find()->select('old_account_id,account_name')->andWhere(['platform_code' => $platform_code])->andWhere(['status' => 1])->asArray()->all();
        }elseif(empty($platform_code) && empty($account)){
            $account_info = Account::find()->select('old_account_id,account_name')->andWhere(['platform_code' => 'EB'])->andWhere(['status' => 1])->asArray()->all();
        }
        $result = [];
        foreach ($account_info as $key => $value) {
            $result[$value['account_name']] = $value['old_account_id'];
        }
        $account = array_values($result);
        foreach ($result as $key => $value){
            $init[$key] = 0;
        }

        foreach ($range as $key => $item) {
            $data[$key] = $init;
            $query = self::find()
                ->select('account_name, count(*) as times')
                ->andWhere(['>=', 'purchase_times', 1])
                ->andWhere(['between', 'create_time', $item['start_time'], $item['end_time']]);
            if(!empty($account)){
                $query = $query->andWhere(['in', 'account_id', $account]);
            }
            $query = $query->groupBy('account_id')
                ->orderBy('times DESC')
                ->asArray()
                ->all();

            $query2 = self::find()
                ->select('account_name, count(*) as times')
                ->andWhere(['>=', 'purchase_times', 2])
                ->andWhere(['between', 'create_time', $item['start_time'], $item['end_time']]);
            if(!empty($account)){
                $query2 = $query2->andWhere(['in', 'account_id', $account]);
            }
            $query2 = $query2->groupBy('account_id')
                ->orderBy('times DESC')
                ->asArray()
                ->all();

            if (!empty($query)) {
                $result1 = array_column($query,'times','account_name');
                $result2 = array_column($query2,'times','account_name');
                if (!empty($result)) {
                    foreach ($result as $kk => $vv) {
                        if (array_key_exists($kk, $result1) && array_key_exists($kk, $result2) && !empty($result1[$kk])) {
                            $data[$key][$kk] = round($result2[$kk] / $result1[$kk], 4) * 100 . "%";
                        }
                    }
                }
            }
            $tmp = $data[$key];
            arsort($tmp);
            $data[$key] = $tmp;
        }
        return $data;
    }

    /**
     * 各平台客户回购次数统计
     */
    public static function getQuarter($range, $account)
    {
        $data = [];

        $init = [];
        $platformList = Platform::getPlatformAsArray();
        if (!empty($platformList)) {
            foreach ($platformList as $key => $val) {
                $init[$key] = 0;
            }
        }
        foreach ($range as $key => $item) {
            $data[$key] = $init;
            $query = self::find()
                ->select('platform_code, sum(purchase_times - 1) as times')
                ->andWhere(['>=', 'purchase_times', 2])
                ->andWhere(['between', 'create_time', $item['start_time'], $item['end_time']]);
            if(!empty($account)){
                $query = $query->andWhere(['in', 'account_id', $account]);
            }
            $query = $query->groupBy('platform_code')
                ->orderBy('times DESC')
                ->asArray()
                ->all();
            if (!empty($query)) {
                $result = array_column($query,'times','platform_code');

                if (!empty($platformList)) {
                    foreach ($platformList as $kk => $vv) {
                        if (array_key_exists($kk, $result) && !empty($result[$kk])) {
                            $data[$key][$kk] = $result[$kk];
                        }
                    }
                }
            }

            $tmp = $data[$key];
            arsort($tmp);
            $data[$key] = $tmp;
        }

        return $data;
    }

    /**
     * 获取平台回购率
     */
    public static function getPlatformRate($range, $account)
    {

        $data = [];

        $init = [];
        $platformList = Platform::getPlatformAsArray();
        if (!empty($platformList)) {
            foreach ($platformList as $key => $val) {
                $init[$key] = 0;
            }
        }

          foreach ($range as $key => $item){
              $data[$key] = $init;
              $query = self::find()
                  ->select('platform_code,count(*) as times')
                  ->andWhere(['>=', 'purchase_times', 1])
                  ->andWhere(['between', 'create_time', $item['start_time'], $item['end_time']]);
              if(!empty($account)){
                  $query = $query->andWhere(['in', 'account_id', $account]);
              }
              $query = $query->groupBy('platform_code')
                     ->orderBy('times DESC')
                     ->asArray()
                     ->all();
              $query1 = self::find()
                  ->select('platform_code,count(*) as times')
                  ->andWhere(['>=', 'purchase_times', 2])
                  ->andWhere(['between', 'create_time', $item['start_time'], $item['end_time']]);
              if(!empty($account)){
                  $query1 = $query1->andWhere(['in', 'account_id', $account]);
              }
              $query1 = $query1->groupBy('platform_code')
                  ->orderBy('times DESC')
                  ->asArray()
                  ->all();

              if (!empty($query) && !empty($query1)) {
                  $result = array_column($query,'times','platform_code');
                  $result1 = array_column($query1,'times','platform_code');
                  if (!empty($platformList)) {
                      foreach ($platformList as $kk => $vv) {
                          if (array_key_exists($kk, $result) && !empty($result[$kk]) && array_key_exists($kk, $result1) && !empty($result1[$kk])) {
                              $data[$key][$kk] = round($result1[$kk] / $result[$kk], 4) * 100 . "%";
                          }
                      }
                  }
              }
              $tmp = $data[$key];
              arsort($tmp);
              $data[$key] = $tmp;
          }
        return $data;
    }

}