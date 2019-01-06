<?php

namespace app\modules\accounts\controllers;

use Yii;
use app\components\Controller;
use app\common\VHelper;
use app\modules\accounts\models\CdiscountAccountOverview;
use app\modules\users\models\User;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;
use app\modules\users\models\UserRole;
use yii\helpers\Url;
use yii\db\Query;
use app\modules\accounts\models\Account;

/**
 * CD账号表现
 */
class CdiscountaccountoverviewController extends Controller
{

    const URL_PATH = '/accounts/cdiscountaccountoverview/list';
    /**
     * 列表
     */
    public function actionList()
    {
        $years = date('Y');

        $months = CdiscountAccountOverview::$sellerMonths;
        $searchModel = new CdiscountAccountOverview();
        $params = Yii::$app->request->queryParams;
        $dataProvider = $searchModel->searchList($params);

        //默认一页显示20条记录
        $params['page_size'] = !empty($params['page_size']) ? $params['page_size'] : 20;

        return $this->renderList('list', [
            'params' => $params,
            'years'  => $years,
            'months' => $months,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * cd账号表现分析
     */
    public function actionIndex()
    {
        $user_name = Yii::$app->user->identity->login_name;
        $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $user_name])
            ->createCommand(Yii::$app->db_system)
            ->queryColumn();
        $account_old_id = (new Query())->select('account_ids')->from('{{%orderservice}}')->where(['user_id' => $user_id, 'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
            ->createCommand(Yii::$app->db_system)
            ->queryOne();
        if ($account_old_id['account_ids']) {
            $account_old_id = explode(',', $account_old_id['account_ids']);
        }


        $account = Account::findAccountAll($account_old_id, Platform::PLATFORM_CODE_CDISCOUNT);

        if(empty($account)){
            $account_id = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_CDISCOUNT);
            $account = Account::findCountAll($account_id);
        }

        return $this->renderList('index', [
            'account' => $account,
        ]);
    }

    /**
     * cd账号表现统计图
     */
    public function actionCdiscountstatistics()
    {
        $params = \Yii::$app->request->post();
        $name = isset($params['name']) ? $params['name'] : "";//时间
        $account_ids = isset($params['account_ids']) ? $params['account_ids'] : "";//账号
        $startTime = isset($params['start_time']) ? $params['start_time'] : "";//开始时间
        $endTime = isset($params['end_time']) ? $params['end_time'] : "";//结束时间


        if(!empty($name)){
            $absName = abs($name);
            $name = -$absName;
            $start_time = date('Y-m-d 00:00:00', strtotime("{$name} day"));
            $range = [];
            for ($i = 0; $i < $absName; $i++) {
                $range[date('Y-m-d', strtotime("+{$i} day", strtotime($start_time)))] = [
                    'start_time' => date('Y-m-d 00:00:00', strtotime("+{$i} day", strtotime($start_time))),
                ];
            }
        }
        if($startTime && $endTime) {

            $start = strtotime($startTime . ' 00:00:00');
            $end = strtotime($endTime . ' 00:00:00');

            //计算天数
            $timediff = $end - $start;
            $days = intval($timediff / 86400);
            $start_time = date('Y-m-d 00:00:00', $start);

            for ($i = 0; $i <= $days; $i++) {
                $range[date('Y-m-d', strtotime("+{$i} day", strtotime($start_time)))] = [
                    'start_time' => date('Y-m-d 00:00:00', strtotime("+{$i} day", strtotime($start_time))),
                ];

            }
        }

        $result = CdiscountAccountOverview::getCdiscountStatistics($range, $account_ids);

        $claim_rate = [];
        $refund_rate = [];
        $claims_rate = [];
        $refunds_rate = [];
        foreach ($result as $k => $item){
            foreach ($item as $v){
                $claim_rate[$k][$v['account_name']] = round($v['claim_rate'],2).'%';
                $refund_rate[$k][$v['account_name']] = round($v['refund_rate'],2).'%';
                $claims_rate[$k][$v['account_name']] = round($v['claims_rate'],2).'%';
                $refunds_rate[$k][$v['account_name']] = round($v['refunds_rate'],2).'%';
            }
        }
        $claim_tmp = [];
        $refund_tmp = [];
        $claims_tmp = [];
        $refunds_tmp = [];
        $claim_data = [];
        $refund_data = [];
        $claims_data = [];
        $refunds_data = [];

        //30天纠纷
        foreach ($claim_rate as $key => $quarter) {
            $claim_data['categories'][] =  $key;
            if (is_array($quarter) && !empty($quarter)) {
                foreach ($quarter as $qkey => $num) {
                    if (!isset($claim_tmp[$qkey])) {
                        $claim_tmp[$qkey] = new \stdClass();
                        $claim_tmp[$qkey]->name = $qkey;
                    }
                    $claim_tmp[$qkey]->data[] = intval($num);
                }
            }
        }


        //30天退款
        foreach ($refund_rate as $key => $quarter) {
            $refund_data['categories'][] =  $key;
            if (is_array($quarter) && !empty($quarter)) {
                foreach ($quarter as $qkey => $num) {
                    if (!isset($refund_tmp[$qkey])) {
                        $refund_tmp[$qkey] = new \stdClass();
                        $refund_tmp[$qkey]->name = $qkey;
                    }
                    $refund_tmp[$qkey]->data[] = intval($num);
                }
            }
        }

        //60天纠纷
        foreach ($claims_rate as $key => $quarter) {
            $claims_data['categories'][] =  $key;
            if (is_array($quarter) && !empty($quarter)) {
                foreach ($quarter as $qkey => $num) {
                    if (!isset($claims_tmp[$qkey])) {
                        $claims_tmp[$qkey] = new \stdClass();
                        $claims_tmp[$qkey]->name = $qkey;
                    }
                    $claims_tmp[$qkey]->data[] = intval($num);
                }
            }
        }

        //60天退款
        foreach ($refunds_rate as $key => $quarter) {
            $refunds_data['categories'][] =  $key;
            if (is_array($quarter) && !empty($quarter)) {
                foreach ($quarter as $qkey => $num) {
                    if (!isset($refunds_tmp[$qkey])) {
                        $refunds_tmp[$qkey] = new \stdClass();
                        $refunds_tmp[$qkey]->name = $qkey;
                    }
                    $refunds_tmp[$qkey]->data[] = intval($num);
                }
            }
        }



        foreach ($claim_tmp as $val) {
            $claim_data['series'][] = $val;
        }
        foreach ($refund_tmp as $val) {
            $refund_data['series'][] = $val;
        }
        foreach ($claims_tmp as $val) {
            $claims_data['series'][] = $val;
        }
        foreach ($refunds_tmp as $val) {
            $refunds_data['series'][] = $val;
        }
        $claim_data['title'] = '30天纠纷趋势（%）';
        $refund_data['title'] = '30天退款趋势（%）';
        $claims_data['title'] = '60天纠纷趋势（%）';
        $refunds_data['title'] = '60天退款趋势（%）';
        $claim_data['text'] = '数量（%）';
        $refund_data['text'] = '数量（%）';
        $claims_data['text'] = '数量（%）';
        $refunds_data['text'] = '数量（%）';

        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'claim_data' => $claim_data,
            'refund_data' => $refund_data,
            'claims_data' => $claims_data,
            'refunds_data' => $refunds_data,
        ]));

    }


    /*
 * 数据导出
 */
    public function actionExcel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');
        $params = \Yii::$app->request->get();
        $yeras = isset($params['years']) ? $params['years'] : "";//年
        $months = isset($params['months']) ? $params['months'] : "";//月

        $yeras_months = $this->getMonth($yeras, $months);
        if($months == date('m')){
            $day = date('Y-m-d 00:00:00',strtotime('-2 day'));
        }else{
            $day = date('Y-m-d 00:00:00',strtotime($yeras_months));
        }

        $result = CdiscountAccountOverview::getSellerAccount($day);
        //标题数组
        $fieldArr = [
            '账号简称',
            '30天退款率',
            '30天纠纷率',
            '60天退款率',
            '60天纠纷率',
        ];
        $dataArr = [];
        if(!empty($result)){
            foreach ($result as $k => $value){
                $dataArr[] = [
                    $value['account_short_name'],
                    $value['refund_rate'].'%',
                    $value['claim_rate'].'%',
                    $value['refunds_rate'].'%',
                    $value['claims_rate'].'%',
                ];
            }
        }else{
            $this->_showMessage(Yii::t('system', '数据为空'), false, self::URL_PATH);
        }


        VHelper::exportExcel($fieldArr, $dataArr, 'indicators_' . date('Y-m-d'));

    }



    /**
     * @param $time
     * @return array
     *
     */
    public function getMonth($years, $months)
    {
        switch ($months) {
            case 1:
            case 3:
            case 5:
            case 7:
            case 8:
            case 10:
            case 12:
                $yearsMonths = $years.'-'.$months.'-31';
                break;
            case 2:
                $yearsMonths = $years.'-'.$months.'-28';
                break;
            case 4:
            case 6:
            case 9:
            case 11:
                $yearsMonths = $years.'-'.$months.'-30';
                break;
        }

        return $yearsMonths;

    }

}