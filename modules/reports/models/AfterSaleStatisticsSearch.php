<?php

namespace app\modules\reports\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\reports\models\AfterSaleStatistics;
use app\modules\accounts\models\UserAccount;
use app\modules\systems\models\BasicConfig;
use app\modules\aftersales\models\AfterSaleTotalStatistics;
use yii\web\Session;
use app\modules\users\models\User;

/**
 * AfterSaleStatisticsSearch represents the model behind the search form about `app\modules\reports\models\AfterSaleStatistics`.
 */
class AfterSaleStatisticsSearch extends AfterSaleStatistics {
    public static $limit = 10;
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'department_id', 'reason_type_id', 'formula_id', 'account_id', 'type', 'status'], 'integer'],
            [['after_sale_id', 'platform_code', 'account_name', 'currency', 'create_by', 'create_time', 'add_time'], 'safe'],
            [['refund_amount', 'refund_amount_rmb', 'subtotal', 'subtotal_rmb', 'exchange_rate', 'pro_cost_rmb'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchData($params,$userList) {
        $limit = self::$limit;
        $platformList = UserAccount::getLoginUserPlatformAccounts('code'); //当前登录用户有的平台权限
        $siteList = UserAccount::getAccoutOrSite('AMAZON', ['site']);
        $accountIds = $defaultAccountIds = [];
        
        $totalSalesPriceArr = AfterSaleTotalStatistics::getData();
        
        $now_year = date('Y'); //参数

        if (!empty($post_year)) {
            $now_year = $post_year;

            if ($action == 'up') {
                //上一年
                $now_year = (int) $now_year - 1;
            } elseif ($action == 'down') {
                //下一年
                $now_year = (int) $now_year + 1;
            }
        }

        $categories = [];
        if (empty($now_year)) {
            $now_year = date('Y');
        }
        for ($i = 1; $i <= 12; $i++) {
            $month = '';
            if (strlen($i) < 2) {
                $month = '0' . $i;
            } else {
                $month = $i;
            }
            $categories[] = $now_year . '-' . $month;
        }
        
        $type = "account"; //默认账号 账号 类型
        if (!empty($params['platform_code']) && $params['platform_code'] == 'AMAZON' && $params['type'][0] == 'site') {
            $type = 'site';
        }

        if ($params['platform_code'] != 'AMAZON' && $params['type'] && $type == 'account') {
            $defaultAccountIds = $params['type'];
        }

        if ($params['platform_code'] == 'AMAZON' && $params['type'][0] == 'site') {
            if(!empty($params['account_site'])){
                $defaultAccountIds = $params['account_site'];
            }else{
                $defaultAccountIds = $siteList;
            }
        }
        
        $andWhere = [];
        $query = AfterSaleStatistics::find();
        $query2 = clone $query;
        $reasonList = [];
        
        if (!empty(trim($params['department_id']))) {
            $reasonList = BasicConfig::getParentList($params['department_id']);
            $reasonList = array_keys($reasonList);
            unset($reasonList[0]); //去除第一个为空的选项

            $andWhere[] = ['department_id' => $params['department_id']];
        }

        if (!empty($params['reason_id'])) {
            $andWhere[] = ['in', 'reason_type_id', $params['reason_id']];
        } else {
            if(!empty($reasonList)){
                $andWhere[] = ['in', 'reason_type_id', $reasonList];
            }
        }

        if (!empty($params['user_id'])) {
            $andWhere[] = ['in', 'create_by', $params['user_id']];
        }
        if (!empty($platformList)) {
            //如果没有选择平台则默认获取默认平台信息   如果默认平台大于10个则取1年内总退款额最高的10个平台展示
            if (!empty($params['platform_code'])) {
                $platformList = [$params['platform_code']];
            }
            
            //如果当前当前登录用户超过10个平台的权限 默认查询退款率top10的平台数据(以年为单位)
            if (count($platformList) > $limit) {
                if (!empty($defaultAccountIds)) {
                    $accountList = $defaultAccountIds;
                } else {
                    $accountList = self::getPlatformAccountList($platformList);
                }
                $query->select(['platform_code', 'SUM(refund_amount_rmb) AS refund_amount_rmb']);
                $query->where(['type' => 1]);
                $query->andWhere(['in', 'platform_code', $platformList]);
//                if($type == 'site'){
//                    $query->andWhere(['in', 'site_code', $accountList]);
//                }else{
                $query->andWhere(['in', 'account_id', $accountList]);
//                }
                $query->andWhere("date_format(`create_time`, '%Y') = " . date("Y"));
                if (!empty($andWhere)) {
                    foreach ($andWhere as $where) {
                        $query->andWhere($where);
                    }
                }

                $query->groupBy('platform_code');
                $query->orderBy('refund_amount_rmb DESC');
                $query->limit($limit);
                $query->asArray();
                $platformInfo = $query->all();
//                echo $query->createCommand()->getRawSql().'<Br/>';
//                die;
                if (!empty($platformInfo)) {
                    $platformList = [];
                    foreach ($platformInfo as $value) {
                        $platformList[] = $value['platform_code'];
                    }
                }
            }
            
            if (!empty($defaultAccountIds)) {
                $accountList = $defaultAccountIds;
            } else {
                $accountList = self::getPlatformAccountList($platformList);
            }
            
//            $accountList = self::getPlatformAccountList($platformList);
            //获取到符合条件的平台 获取账号信息
            if (is_array($platformList) && !empty($platformList)) {
                //默认获取第一个平台
                $defaultPlatform = $platformList[0];

                if (!empty($params['platform_code']) && $params['platform_code'] == 'AMAZON' && $params['type'][0] == 'site') {
                    $type = 'site';
                }

                if ($params['platform_code'] != 'AMAZON' && $params['type'] && $type == 'account') {
                    $defaultAccountIds = $params['type'];
                } else {
                    $defaultAccountIds = self::getPlatformAccountList(array($defaultPlatform));
                }

                if ($params['platform_code'] == 'AMAZON' && $params['type'][0] == 'account') {
                    $defaultAccountIds = $params['account_site'];
                }
//                echo '<pre>';
//                var_dump($defaultAccountIds);
//                echo '</pre>';
//                die;
                //如果账户超过10个 默认取退款最多的10个账号
                if ($type == 'account') {
                    if (count($defaultAccountIds) > $limit) {
                        $query2->select(['account_id', "date_format(`create_time`, '%Y') as date_time", 'SUM(refund_amount_rmb) as refund_amount_rmb']);
                        $query2->where(['type' => 1]);
                        $query2->andWhere(['platform_code' => $defaultPlatform]);
                        $query2->andWhere(['in', 'account_id', $defaultAccountIds]);
                        $query2->groupBy('account_id,date_time');
                        $query2->limit($limit);
                        $res2 = $query2->orderBy('`date_time`,`refund_amount_rmb` DESC')->asArray()->all();
//                        echo '<br/>账号sql: ---' . $query2->createCommand()->getRawSql() . '---<Br/>';
                        if (is_array($res2) && !empty($res2)) {
                            foreach ($res2 as $r2) {
                                $accountIds[] = $r2['account_id'];
                            }
                        }
                    } else {
                        $accountIds = $defaultAccountIds;
                    }
                } else {
                    $accountIds = $accountList;
                }

                //平台统计
                $platform = self::getPlatformInfo($platformList, $accountIds, $andWhere, $type,$totalSalesPriceArr);

                //当前用户有的所有权限的账号ID
                if (empty($accountIds)) {
                    $accountIds = self::getPlatformAccountList($platformList);
                }

                //账号统计
                $accountInfo = self::accountInfo($defaultPlatform, $accountIds, $andWhere, $type,$totalSalesPriceArr);

                //部门统计
                $department = self::departmentInfo($defaultPlatform, $accountIds, $andWhere, $type,$totalSalesPriceArr);

                //原因统计
                $reason = self::reasonInfo($defaultPlatform, $accountIds, $andWhere, $type,$totalSalesPriceArr);

                //客服统计
                $customerService = self::customerService($defaultPlatform, $accountIds, $andWhere, $type,$userList,$totalSalesPriceArr);
            }
        }





//        =========================华丽分割线====================================
        //
       // $defaultPlatform = !empty($params['platform_code']) ? $params['platform_code'] : $platformList[0]; //默认取当前用户的第一个平台获取账号信息  如果有选择平台就获取当前选择平台
//        echo '<pre>';
//        var_dump($platformInfo,$platformList,$defaultPlatform);
//        echo '</pre>';
//        die;
//        if (!empty($platformList)) {
//            $accountList = UserAccount::getAccoutOrSite($defaultPlatform, $type); //获取默认平台下的账号信息
//            if (!empty($accountList)) {
//                $accountIds = array_keys($accountList);
//            }
//            //如果账户超过10个 默认取退款最多的10个账号
//            if (count($accountIds) > 10) {
//                $query2->select(['account_id', "date_format(`create_time`, '%Y') as date_time", 'SUM(refund_amount_rmb) as refund_amount_rmb']);
//                $query2->where(['type' => 1]);
//                $query2->andWhere(['platform_code' => $defaultPlatform]);
//                $query2->andWhere(['in', 'account_id', array_keys($accountList)]);
//                $res2 = $query2->groupBy('account_id,date_time')
//                                ->orderBy('`date_time`,`refund_amount_rmb` DESC')->asArray()->all();
//                if (is_array($res2) && !empty($res2)) {
//                    $accountIds = [];
//                    foreach ($res2 as $r2) {
//                        $accountIds[] = $r2['account_id'];
//                    }
//                }
//            }
//
//            if (!empty($accountIds)) {
//                $accountInfo = self::accountInfo($defaultPlatform, $accountIds); //账号信息
//            } else {
//                echo '当前用户在' . $defaultPlatform . '平台未设置账号信息';
//            }
//        } else {
//            $defaultPlatform = [];
//        }
        //数据整理
        $res = [
            'ploatform' => $platform,
            'account' => $accountInfo,
            'department' => $department,
            'reason' => $reason,
            'customerService' => $customerService
        ];
        return $res;
    }

    /**
     * 根据当前平台获取对应的账号
     * @param type $platformList
     * @author allen <2018-07-13>
     */
    public static function getPlatformAccountList($platformList) {
        $accountLists = $accountList = [];
        if (!empty($platformList)) {
            foreach ($platformList as $value) {
//                $arr = [];
                //循环获取平台下的有权限的账号id  
                $accountList = array_keys(UserAccount::getAccoutOrSite($value, 1)); //第二个参数  1:账号   2:站点
                //合并各平台有权限的账号ID
                $accountLists = array_merge($accountLists, $accountList);
            }
        }
        return $accountLists;
    }

    //平台统计
    /**
     * 
     * @param type $platformList  当前条件下的账号
     * @return type
     */
    public static function getPlatformInfo($platformList, $accountIds, $andWhere = null, $type = 'account',$totalSalesPriceArr) {
        $dataOneArr = [];
        $query = AfterSaleStatistics::find();
        $query->select(['platform_code', 'account_id', 'account_name', 'site_code', "date_format(`create_time`, '%Y-%m') as date_time", 'SUM(refund_amount_rmb) as refund_amount_rmb']);
        $query->where(['type' => 1]);
        $query->andWhere(['in', 'platform_code', $platformList]);
//        if ($type == 'site') {
//            $query->andWhere(['in', 'site_code', $accountIds]);
//        } else {
//            $query->andWhere(['in', 'account_id', $accountIds]);
//        }

        if (!empty($andWhere)) {
            foreach ($andWhere as $where) {
                $query->andWhere($where);
            }
        }
        $data1 = $query->groupBy('platform_code,date_time')->orderBy('`date_time`,`refund_amount_rmb` DESC,`platform_code`')->asArray()->all();
//        echo '<br/>平台: ' . $query->createCommand()->getRawSql() . '<br/>';
        foreach ($data1 as $key => $value) {
            if (count($dataOneArr[$value['date_time']]) < 3) {
                $dataOneArr[$value['date_time']][$value['platform_code']] = $value['refund_amount_rmb'];
            }
        }

        $now_year = date('Y'); //参数

        if (!empty($post_year)) {
            $now_year = $post_year;

            if ($action == 'up') {
                //上一年
                $now_year = (int) $now_year - 1;
            } elseif ($action == 'down') {
                //下一年
                $now_year = (int) $now_year + 1;
            }
        }

        $categories = [];
        if (empty($now_year)) {
            $now_year = date('Y');
        }
        for ($i = 1; $i <= 12; $i++) {
            $month = '';
            if (strlen($i) < 2) {
                $month = '0' . $i;
            } else {
                $month = $i;
            }
            $categories[] = $now_year . '-' . $month;
        }

        $returnData = [];
        if (!empty($data1)) {
            foreach ($data1 as $value) {
                if (!empty($value['platform_code'])) {
                    $returnPrice = $value['refund_amount_rmb'];
                    $totalPrice = $totalSalesPriceArr[$value['platform_code']][$value['date_time']];
                    $refundTax = 0;
                    if($returnPrice > 0 && $totalPrice > 0){
                        $refundTax = round($returnPrice/$totalPrice,5);
                    }
                    $returnData[$value['platform_code']][$value['date_time']] = $refundTax;
                }
            }
        }
        
        //补充默认值
        $messageData = [];
        if (!empty($returnData)) {
            foreach ($returnData as $key => $value) {
                foreach ($categories as $val) {
                    if (isset($value[$val])) {
                        $messageData[$key][$val] = (float) $value[$val];
                    } else {
                        $messageData[$key][$val] = 0;
                    }
                }
            }
        }

        //处理展示需要的格式
        $series = [];
        if (!empty($messageData)) {
            $index = 0;
            foreach ($messageData as $key => $value) {
                $series[$index]['name'] = $key;
                $arr = [];
                foreach ($value as $val) {
                    $arr[] = $val;
                }
                $series[$index]['data'] = $arr;
                $index++;
            }
        }

        return [
            'categories' => json_encode($categories),
            'series' => json_encode($series),
            'year' => $now_year
        ];
    }

    //账号信息
    public static function accountInfo($platform, $accountIds, $andWhere = null, $type = 'account',$totalSalesPriceArr) {
        $query = AfterSaleStatistics::find();
        $dataOneArr = [];
        $groupBy = 'account_id';
        $query->select(['platform_code', 'account_name', 'site_code', "date_format(`create_time`, '%Y-%m') as date_time", 'SUM(refund_amount_rmb) as refund_amount_rmb']);
        $query->where(['type' => 1]);
        $query->andWhere(['platform_code' => $platform]);
        if ($type == 'site') {
            $query->andWhere(['in', 'site_code', $accountIds]);
            $groupBy = 'site_code';
        } else {
            $query->andWhere(['in', 'account_id', $accountIds]);
        }
        if (!empty($andWhere)) {
            foreach ($andWhere as $where) {
                $query->andWhere($where);
            }
        }
        $groupBy .= ',date_time';
        $data1 = $query->groupBy($groupBy)->orderBy('`date_time`,`refund_amount_rmb` DESC,`platform_code`')->asArray()->all();
//        echo '<Br/>账号/站点: ' . $query->createCommand()->getRawSql() . '<Br/>';
        foreach ($data1 as $key => $value) {
            if (count($dataOneArr[$value['date_time']]) < 3) {
                $dataOneArr[$value['date_time']][$value['account_name']] = $value['refund_amount_rmb'];
            }
        }
        $now_year = date('Y'); //参数

        if (!empty($post_year)) {
            $now_year = $post_year;

            if ($action == 'up') {
                //上一年
                $now_year = (int) $now_year - 1;
            } elseif ($action == 'down') {
                //下一年
                $now_year = (int) $now_year + 1;
            }
        }

        $categories = [];
        if (empty($now_year)) {
            $now_year = date('Y');
        }
        for ($i = 1; $i <= 12; $i++) {
            $month = '';
            if (strlen($i) < 2) {
                $month = '0' . $i;
            } else {
                $month = $i;
            }
            $categories[] = $now_year . '-' . $month;
        }

        $returnData = [];
        if (!empty($data1)) {
            foreach ($data1 as $value) {
                $returnPrice = $value['refund_amount_rmb'];
                $totalPrice = $totalSalesPriceArr[$platform][$value['date_time']];
                $refundTax = 0;
                    if($returnPrice > 0 && $totalPrice > 0){
                        $refundTax = round($returnPrice/$totalPrice,5);
                    }
                               
                if ($type == 'site') {
                    if (!empty($value['site_code'])) {
                        $returnData[$value['site_code']][$value['date_time']] = $refundTax;
                    }
                } else {
                    if (!empty($value['account_name'])) {
                        $returnData[$value['account_name']][$value['date_time']] = $refundTax;
                    }
                }
            }
        }
        
        //补充默认值
        $messageData = [];
        if (!empty($returnData)) {
            foreach ($returnData as $key => $value) {
                foreach ($categories as $val) {
                    if (isset($value[$val])) {
                        $messageData[$key][$val] = (float) $value[$val];
                    } else {
                        $messageData[$key][$val] = 0;
                    }
                }
            }
        }

        //处理展示需要的格式
        $series = [];
        if (!empty($messageData)) {
            $index = 0;
            foreach ($messageData as $key => $value) {
                $series[$index]['name'] = $key;
                $arr = [];
                foreach ($value as $val) {
                    $arr[] = $val;
                }
                $series[$index]['data'] = $arr;
                $index++;
            }
        }

        return [
            'categories' => json_encode($categories),
            'series' => json_encode($series),
            'year' => $now_year
        ];
    }

    //责任部分统计
    public static function departmentInfo($platform, $accountIds, $andWhere = null, $type = 'account',$totalSalesPriceArr) {
        $baseConfigData = BasicConfig::getParentList(52);
        $query = AfterSaleStatistics::find();
        $dataOneArr = [];
        $query->select(['platform_code', 'department_id', 'site_code', "date_format(`create_time`, '%Y-%m') as date_time", 'SUM(refund_amount_rmb) as refund_amount_rmb']);
        $query->where(['type' => 1]);
        $query->andWhere(['platform_code' => $platform]);
        if ($type == 'site') {
            $query->andWhere(['in', 'site_code', $accountIds]);
        } else {
            $query->andWhere(['in', 'account_id', $accountIds]);
        }
        if (!empty($andWhere)) {
            foreach ($andWhere as $where) {
                $query->andWhere($where);
            }
        }

        $data1 = $query->groupBy('department_id,date_time')->orderBy('`date_time`,`refund_amount_rmb` DESC')->asArray()->all();

//        echo '<br/> 部门: ' . $query->createCommand()->getRawSql() . '<Br/>';
        foreach ($data1 as $key => $value) {
            if (count($dataOneArr[$value['date_time']]) < self::$limit) {
                $dataOneArr[$value['date_time']][$value['department_id']] = $value['refund_amount_rmb'];
            }
        }
        $now_year = date('Y'); //参数

        if (!empty($post_year)) {
            $now_year = $post_year;

            if ($action == 'up') {
                //上一年
                $now_year = (int) $now_year - 1;
            } elseif ($action == 'down') {
                //下一年
                $now_year = (int) $now_year + 1;
            }
        }

        $categories = [];
        if (empty($now_year)) {
            $now_year = date('Y');
        }
        for ($i = 1; $i <= 12; $i++) {
            $month = '';
            if (strlen($i) < 2) {
                $month = '0' . $i;
            } else {
                $month = $i;
            }
            $categories[] = $now_year . '-' . $month;
        }

        $returnData = [];
        if (!empty($data1)) {
            foreach ($data1 as $value) {
                if (!empty($value['department_id'])) {
                    $returnPrice = $value['refund_amount_rmb'];
                    $totalPrice = $totalSalesPriceArr[$platform][$value['date_time']];
                    $refundTax = 0;
                    if($returnPrice > 0 && $totalPrice > 0){
                        $refundTax = round($returnPrice/$totalPrice,5);
                    }
                    $returnData[$baseConfigData[$value['department_id']]][$value['date_time']] = $refundTax;
                }
            }
        }
        //补充默认值
        $messageData = [];
        if (!empty($returnData)) {
            foreach ($returnData as $key => $value) {
                foreach ($categories as $val) {
                    if (isset($value[$val])) {
                        $messageData[$key][$val] = (float) $value[$val];
                    } else {
                        $messageData[$key][$val] = 0;
                    }
                }
            }
        }

        //处理展示需要的格式
        $series = [];
        if (!empty($messageData)) {
            $index = 0;
            foreach ($messageData as $key => $value) {
                $series[$index]['name'] = $key;
                $arr = [];
                foreach ($value as $val) {
                    $arr[] = $val;
                }
                $series[$index]['data'] = $arr;
                $index++;
            }
        }
        return [
            'categories' => json_encode($categories),
            'series' => json_encode($series),
            'year' => $now_year
        ];
    }

    //原因统计
    public static function reasonInfo($platform, $accountIds, $andWhere = null, $type = 'account',$totalSalesPriceArr) {
        $reasonList = BasicConfig::getAllConfigData();
        $query = AfterSaleStatistics::find();
        $dataOneArr = [];
        $query->select(['platform_code', 'account_name', 'department_id', 'reason_type_id', 'site_code', "date_format(`create_time`, '%Y') AS date_time", 'SUM(refund_amount_rmb) AS refund_amount_rmb']);
        $query->where(['type' => 1]);
        $query->andWhere(['platform_code' => $platform]);
        if ($type == 'site') {
            $query->andWhere(['in', 'site_code', $accountIds]);
        } else {
            $query->andWhere(['in', 'account_id', $accountIds]);
        }
        if (!empty($andWhere)) {
            foreach ($andWhere as $where) {
                $query->andWhere($where);
            }
        }
        $data = $query->groupBy('reason_type_id')->orderBy('`refund_amount_rmb` DESC')->limit(self::$limit)->asArray()->all();
//        echo '<br>原因获取前10条原因sql: '.$query->createCommand()->getRawSql().'<Br/>';

        $reasonIds = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $reasonIds[] = $value['reason_type_id'];
            }
        }
        

        $query1 = AfterSaleStatistics::find();
        $query1->select(['platform_code', 'account_name', 'department_id', 'reason_type_id', 'site_code', "date_format(`create_time`, '%Y-%m') AS date_time", 'SUM(refund_amount_rmb) AS refund_amount_rmb']);
        $query1->where(['type' => 1]);
        $query1->andWhere(['in', 'reason_type_id', $reasonIds]);
        $query1->andWhere(['platform_code' => $platform]);
        if ($type == 'site') {
            $query1->andWhere(['in', 'site_code', $accountIds]);
        } else {
            $query1->andWhere(['in', 'account_id', $accountIds]);
        }
        if (!empty($andWhere)) {
            foreach ($andWhere as $where) {
                $query1->andWhere($where);
            }
        }
        $data1 = $query1->groupBy('reason_type_id,date_time')->orderBy('`refund_amount_rmb` DESC')->asArray()->all();
//        echo '<br>原因sql: '.$query1->createCommand()->getRawSql().'<Br/>';

        if (!empty($data1)) {
            foreach ($data1 as $key => $value) {
                if (count($dataOneArr[$value['date_time']]) < self::$limit) {
                    $dataOneArr[$value['date_time']][$value['reason_type_id']] = $value['refund_amount_rmb'];
                }
            }
        }


        $now_year = date('Y'); //参数

        if (!empty($post_year)) {
            $now_year = $post_year;

            if ($action == 'up') {
                //上一年
                $now_year = (int) $now_year - 1;
            } elseif ($action == 'down') {
                //下一年
                $now_year = (int) $now_year + 1;
            }
        }

        $categories = [];
        if (empty($now_year)) {
            $now_year = date('Y');
        }
        for ($i = 1; $i <= 12; $i++) {
            $month = '';
            if (strlen($i) < 2) {
                $month = '0' . $i;
            } else {
                $month = $i;
            }
            $categories[] = $now_year . '-' . $month;
        }

        $returnData = [];
        if (!empty($data1)) {
            foreach ($data1 as $value) {
                if (!empty($value['reason_type_id'])) {
                    $returnPrice = $value['refund_amount_rmb'];
                    $totalPrice = $totalSalesPriceArr[$platform][$value['date_time']];
                    $refundTax = 0;
                    if($returnPrice > 0 && $totalPrice > 0){
                        $refundTax = round($returnPrice/$totalPrice,5);
                    }
                
                    $returnData[$reasonList[$value['reason_type_id']]][$value['date_time']] = $refundTax;
                }
            }
        }
        //补充默认值
        $messageData = [];
        if (!empty($returnData)) {
            foreach ($returnData as $key => $value) {
                foreach ($categories as $val) {
                    if (isset($value[$val])) {
                        $messageData[$key][$val] = (float) $value[$val];
                    } else {
                        $messageData[$key][$val] = 0;
                    }
                }
            }
        }

        //处理展示需要的格式
        $series = [];
        if (!empty($messageData)) {
            $index = 0;
            foreach ($messageData as $key => $value) {
                $series[$index]['name'] = $key;
                $arr = [];
                foreach ($value as $val) {
                    $arr[] = $val;
                }
                $series[$index]['data'] = $arr;
                $index++;
            }
        }
        return [
            'categories' => json_encode($categories),
            'series' => json_encode($series),
            'year' => $now_year
        ];
    }

    //按客服统计
    public static function customerService($platform, $accountIds, $andWhere = null, $type = 'account',$userList,$totalSalesPriceArr) {
        $query = AfterSaleStatistics::find();
        $createBy = $dataOneArr = [];
        $query->select(['platform_code', 'account_name', 'department_id', 'create_by', 'site_code', "date_format(`create_time`, '%Y') AS date_time", 'SUM(refund_amount_rmb) AS refund_amount_rmb']);
        $query->where(['type' => 1]);
        $query->andWhere(['platform_code' => $platform]);
        if(!IS_ADMIN && !empty($userList)){
            $query->andWhere(['in', 'create_by', $userList]);
        }
        if ($type == 'site') {
            $query->andWhere(['in', 'site_code', $accountIds]);
        } else {
            $query->andWhere(['in', 'account_id', $accountIds]);
        }
        if (!empty($andWhere)) {
            foreach ($andWhere as $where) {
                $query->andWhere($where);
            }
        }
        $data = $query->groupBy('create_by')->orderBy('`refund_amount_rmb` DESC')->limit(self::$limit)->asArray()->all();

//        echo '<Br/>客服前10统计sql: '.$query->createCommand()->getRawSql();
        if (!empty($data)) {
            foreach ($data as $value) {
                $createBy[] = $value['create_by'];
            }
        }


        $query1 = AfterSaleStatistics::find();
        $query1->select(['platform_code', 'account_name', 'department_id', 'create_by', 'site_code', "date_format(`create_time`, '%Y-%m') AS date_time", 'SUM(refund_amount_rmb) AS refund_amount_rmb']);
        $query1->where(['type' => 1]);
        $query1->andWhere(['in', 'create_by', $createBy]);
        $query1->andWhere(['platform_code' => $platform]);
        if ($type == 'site') {
            $query1->andWhere(['in', 'site_code', $accountIds]);
        } else {
            $query1->andWhere(['in', 'account_id', $accountIds]);
        }
        if (!empty($andWhere)) {
            foreach ($andWhere as $where) {
                $query1->andWhere($where);
            }
        }
        $data1 = $query1->groupBy('create_by,date_time')->orderBy('`refund_amount_rmb` DESC')->asArray()->all();
//        echo '<br>客服sql: '.$query1->createCommand()->getRawSql().'<Br/>';
        if (!empty($data1)) {
            foreach ($data1 as $key => $value) {
                $dataOneArr[$value['date_time']][$value['create_by']] = $value['refund_amount_rmb'];
            }
        }


        $now_year = date('Y'); //参数

        if (!empty($post_year)) {
            $now_year = $post_year;

            if ($action == 'up') {
                //上一年
                $now_year = (int) $now_year - 1;
            } elseif ($action == 'down') {
                //下一年
                $now_year = (int) $now_year + 1;
            }
        }

        $categories = [];
        if (empty($now_year)) {
            $now_year = date('Y');
        }
        for ($i = 1; $i <= 12; $i++) {
            $month = '';
            if (strlen($i) < 2) {
                $month = '0' . $i;
            } else {
                $month = $i;
            }
            $categories[] = $now_year . '-' . $month;
        }

        $returnData = [];
        if (!empty($data1)) {
            foreach ($data1 as $value) {
                if (!empty($value['create_by'])) {
                    $returnPrice = $value['refund_amount_rmb'];
                    $totalPrice = $totalSalesPriceArr[$platform][$value['date_time']];
                    $refundTax = 0;
                    if($returnPrice > 0 && $totalPrice > 0){
                        $refundTax = round($returnPrice/$totalPrice,5);
                    }
                    $returnData[$value['create_by']][$value['date_time']] = $refundTax;
                }
            }
        }
        //补充默认值
        $messageData = [];
        if (!empty($returnData)) {
            foreach ($returnData as $key => $value) {
                foreach ($categories as $val) {
                    if (isset($value[$val])) {
                        $messageData[$key][$val] = (float) $value[$val];
                    } else {
                        $messageData[$key][$val] = 0;
                    }
                }
            }
        }

        //处理展示需要的格式
        $series = [];
        if (!empty($messageData)) {
            $index = 0;
            foreach ($messageData as $key => $value) {
                $series[$index]['name'] = $key;
                $arr = [];
                foreach ($value as $val) {
                    $arr[] = $val;
                }
                $series[$index]['data'] = $arr;
                $index++;
            }
        }
        return [
            'categories' => json_encode($categories),
            'series' => json_encode($series),
            'year' => $now_year
        ];
    }

    public function searchs($params) {
        $accouontList = UserAccount::getLoginUserPlatformAccounts('code'); //当前登录用户有的平台权限
        $dataOneArr = [];
        $query = AfterSaleStatistics::find();
        // add conditions that should always apply here
        $platform_code = isset($params['platform_code']) ? $params['platform_code'] : "";
        $type = isset($params['type']) ? $params['type'] : "";
        $groupBy = "";
        //上一年 下一年
        $action = isset($params['year_judge']) ? $params['year_judge'] : "";
        $post_year = isset($params['now_year']) ? $params['now_year'] : "";
        $query->select(['platform_code', 'account_name', 'site_code', "date_format(`create_time`, '%Y-%m') as date_time", 'SUM(refund_amount_rmb) as refund_amount_rmb']);
        $query->where(['type' => 1]);
        if (!empty($platform_code)) {
            $query->andWhere(['platform_code' => $platform_code]);
        } else {
            $query->andWhere(['in', 'platform_code', $accouontList]);
        }

        //如果是亚马逊平台   type则有可能是account 或者 site
        if ($platform_code == 'AMAZON') {
            $account_site = isset($params['account_site']) ? $params['account_site'] : "";
            $type = $type[0];
            switch ($type) {
                case 'account':
                    $groupBy = 'account_id,';
                    if (!empty($account_site)) {
                        $query->andWhere(['in', 'account_id', $account_site]);
                    }
                    break;

                case 'site':
                    if (!empty($account_site)) {
                        $query->andWhere(['in', 'site_code', $account_site]);
                    }
                    $groupBy = 'site_code,';
                    break;
            }
        } else {
            //其他平台
            $account_site = $type;
            $groupBy = 'account_id,';
            if (!empty($account_site)) {
                $query->andWhere(['in', 'account_id', $account_site]);
            }
        }
        $query1 = clone $query;
        $query2 = clone $query;
        $query3 = clone $query;
        $query4 = clone $query;
        $query5 = clone $query;
        $query6 = clone $query;

        //按平台统计每个月前10数据
        $data1 = $query1->groupBy('platform_code,date_time')->orderBy('`date_time`,`refund_amount_rmb` DESC,`platform_code`')->asArray()->all();
//        echo $query1->createCommand()->getRawSql();
//        die;

        foreach ($data1 as $key => $value) {
            if (count($dataOneArr[$value['date_time']]) < 3) {
                $dataOneArr[$value['date_time']][$value['platform_code']] = $value['refund_amount_rmb'];
            }
        }

//        foreach ($dataOneArr as $key => $value) {
//            $oneArr = 
//        }
//        echo '<pre>';
//        var_dump($data1,$dataOneArr);
//        echo '</pre>';
//        die;
//        $query->groupBy($groupBy);
//        $data = $query->asArray()->all();
        $now_year = date('Y'); //参数

        if (!empty($post_year)) {
            $now_year = $post_year;

            if ($action == 'up') {
                //上一年
                $now_year = (int) $now_year - 1;
            } elseif ($action == 'down') {
                //下一年
                $now_year = (int) $now_year + 1;
            }
        }

        $categories = [];
        if (empty($now_year)) {
            $now_year = date('Y');
        }
        for ($i = 1; $i <= 12; $i++) {
            $month = '';
            if (strlen($i) < 2) {
                $month = '0' . $i;
            } else {
                $month = $i;
            }
            $categories[] = $now_year . '-' . $month;
        }

        $returnData = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                if ($type == 'site') {
                    if (!empty($value['site_code'])) {
                        $returnData[$value['site_code']][$value['date_time']] = $value['refund_amount_rmb'];
                    }
                } else {
                    if (!empty($value['account_name'])) {
                        $returnData[$value['account_name']][$value['date_time']] = $value['refund_amount_rmb'];
                    }
                }
            }
        }
        //补充默认值
        $messageData = [];
        if (!empty($returnData)) {
            foreach ($returnData as $key => $value) {
                foreach ($categories as $val) {
                    if (isset($value[$val])) {
                        $messageData[$key][$val] = (float) $value[$val];
                    } else {
                        $messageData[$key][$val] = 0;
                    }
                }
            }
        }

        //处理展示需要的格式
        $series = [];
        if (!empty($messageData)) {
            $index = 0;
            foreach ($messageData as $key => $value) {
                $series[$index]['name'] = $key;
                $arr = [];
                foreach ($value as $val) {
                    $arr[] = $val;
                }
                $series[$index]['data'] = $arr;
                $index++;
            }
        }

        return [
            'categories' => json_encode($categories),
            'series' => json_encode($series),
            'year' => $now_year
        ];
    }

}
