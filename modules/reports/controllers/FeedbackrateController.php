<?php

namespace app\modules\reports\controllers;

use app\components\Controller;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;
use app\modules\customer\models\CustomerList;
use app\modules\mails\models\EbayFeedback;
use DTS\eBaySDK\Trading\Types\EBayMotorsProAdFormatEnabledDefinitionType;
use yii\web\Response;
use app\modules\users\models\User;
use Yii;
use app\modules\mails\models\AliexpressEvaluateList;
use app\common\VHelper;

class FeedbackrateController extends Controller{

    public function actionIndex()
    {
        //所有平台
        $platformList = UserAccount::getLoginUserPlatformList();
        $platform_code = $account = $accountArr = [];
        $role_id = Yii::$app->user->identity->role_id;
        $uList = User::getUserInfoByRole($role_id);
        $userList = !empty($uList) ? array_column($uList, 'user_name', 'user_name') : [];
        $userIdList = !empty($userList) ? $userList : [];

        $end_time = date('Y-m');
        $start_time = date('Y-m', strtotime('-3 month'));

        return $this->render('index', [
            'platformList' => $platformList,
            'platform_code' => $platform_code,
            'account' => $account,
            'userIdList' => $userIdList,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'accountArr' => $accountArr,
            'role_id' => $role_id,
        ]);

    }

    /**
     * 评价统计图
     */
    public function actionFeedbackstatistics()
    {
        $params = \Yii::$app->request->post();
        $start_time = isset($params['start_time']) ? $params['start_time'] : "";//开始时间
        $end_time = isset($params['end_time']) ? $params['end_time'] : "";//结束时间
        $platform_code = isset($params['platform_code']) ? $params['platform_code'] : "";//选择的平台
        $plat_type = isset($params['plat_type']) ? $params['plat_type'] : "";//统计类型
        $cycle_type = isset($params['cycle_type']) ? $params['cycle_type'] : "";//统计周期
        $account = isset($params['account']) ? $params['account'] : "";//账号
        $user_name = isset($params['user_name']) ? $params['user_name'] : ""; //用户ID

        if ($plat_type) {
            $quarter_date = $this->getQuarter($start_time, $end_time); //季度区间
            $month_date = $this->getDateTime($start_time, $end_time); //月度区间
            $years_date = $this->getYears($start_time, $end_time);   //年度区间

            switch ($plat_type) {
                case 1:
                    //按平台统计
                        if ($cycle_type == 5) {
                            $data =  $this->datestatistics($platform_code, $month_date, 5);
                        } elseif ($cycle_type == 6) {
                           $data =  $this->datestatistics($platform_code, $quarter_date, 6);
                        } elseif ($cycle_type == 7) {
                           $data =  $this->datestatistics($platform_code, $years_date, 7);
                        }
                    die(json_encode([
                        'code' => 1,
                        'message' => '成功',
                        'data' => $data['data'],
                        'data1' => $data['data1'],
                    ]));
                    break;
                case 2:
                    //按账号统计
                    if ($cycle_type == 5) {
                        $data =  $this->getAccountstatics($platform_code, $month_date, 5, $account);
                    } elseif ($cycle_type == 6) {
                        $data =  $this->getAccountstatics($platform_code, $quarter_date, 6, $account);
                    } elseif ($cycle_type == 7) {
                        $data =  $this->getAccountstatics($platform_code, $years_date, 7, $account);
                    }
                    die(json_encode([
                        'code' => 1,
                        'message' => '成功',
                        'data' => $data['data'],
                        'data1' => $data['data1'],
                    ]));
                case 3:
                    //按平台客服统计
                    if ($cycle_type == 5) {
                        $data =  $this->getKefutstatics($platform_code, $month_date, 5, $user_name);
                    } elseif ($cycle_type == 6) {
                        $data =  $this->getKefutstatics($platform_code, $quarter_date, 6, $user_name);
                    } elseif ($cycle_type == 7) {
                        $data =  $this->getKefutstatics($platform_code, $years_date, 7, $user_name);
                    }
                    die(json_encode([
                        'code' => 1,
                        'message' => '成功',
                        'data' => $data['data'],
                        'data1' => $data['data1'],
                    ]));
                    break;

            }

        }

    }

    /*
     * 数据导出
     */
    public function actionExcel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');
        $params = \Yii::$app->request->get();
        $start_time = isset($params['start_time']) ? $params['start_time'] : "";//开始时间
        $end_time = isset($params['end_time']) ? $params['end_time'] : "";//结束时间
        $platform_code = isset($params['platform_code']) ? $params['platform_code'] : "";//选择的平台
        $user_name = isset($params['user_name']) ? $params['user_name'] : ""; //用户ID

        $cycle_type = $start_time.'-'.$end_time;

        $range = [];

        $range['start_time'] = date('Y-m-01 00:00:00', strtotime($start_time));
        $range['end_time'] =date('Y-m-31 23:59:59', strtotime($end_time));
        if($platform_code == 'EB'){
            $result = EbayFeedback::getExcelDate($range, $user_name); //ebay数据

        }else if($platform_code == 'ALI'){
            $result = AliexpressEvaluateList::getExcelDate($range, $user_name); //ALI数据
        }
        //标题数组
        $fieldArr = [
            '客服姓名',
            '周期',
            '总评价数',
            '好评数',
            '好评率',
            '差评数',
            '差评率',
        ];
        $dataArr = [];
        if(!empty($result)){
            foreach ($result as $k => $value){
                $dataArr[] = [
                    $k,
                    $cycle_type,
                    $value['res_zong'],
                    $value['res_postive'],
                    $value['res_postive_rate'],
                    $value['res_negative'],
                    $value['res_negative_rate'],
                ];
            }
        }


        VHelper::exportExcel($fieldArr, $dataArr, 'feedback_' . date('Y-m-d'));

    }

    /**
     * @param $start_time
     * @param $end_time
     * @return array
     * 获取季度range
     */
    public function getQuarter($start_time, $end_time)
    {
        $start_time = $start_time . '-01 00:00:00';
        $end_time = $end_time . '-31 23:59:59';

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        $start = $start_time;
        $range = [];

        while ($start < $end_time) {
            $months = $this->getMonth(date('n', $start));
            $key = date('Y', $start) . '-' . ceil(intval($months['start_month']) / 3);
            $range[$key] = [
                'start_time' => date('Y', $start) . '-' . $months['start_month'] . '-01 00:00:00',
                'end_time' => date('Y', $start) . '-' . $months['end_month'] . '-31 23:59:59',
            ];

            $start = strtotime('+3 month', $start);
        }
        return $range;
    }

    /**
     * @param $time
     * @return array
     * 获取季度筛选条件
     */
    public function getMonth($time)
    {
        switch ($time) {
            case 1:
            case 2:
            case 3:
                return ['start_month' => '01', 'end_month' => '03'];
                break;
            case 4:
            case 5:
            case 6:
                return ['start_month' => '04', 'end_month' => '06'];
                break;
            case 7:
            case 8:
            case 9:
                return ['start_month' => '07', 'end_month' => '09'];
                break;
            case 10:
            case 11:
            case 12:
                return ['start_month' => '10', 'end_month' => '12'];
                break;
        }

    }

    /**
     * @param $start_time
     * @param $end_time
     * 获取月筛选条件
     */
    public function getDateTime($start_time, $end_time)
    {
        $datetime1 = new \DateTime($start_time);
        $datetime2 = new \DateTime($end_time);
        $interval = $datetime1->diff($datetime2);
        $time['y'] = $interval->format('%Y') * 12;
        $time['m'] = $interval->format('%m');
        $months = $time['y'] + $time['m'];
        for ($i = 0; $i <= $months; $i++) {
            $key = date('Y-m', strtotime("+{$i} month", strtotime($start_time)));
            $range[$key] = [
                'start_time' => date('Y-m-01 00:00:00', strtotime("+{$i} month", strtotime($start_time))),
                'end_time' => date('Y-m-31 23:59:59', strtotime("+{$i} month", strtotime($start_time))),
            ];
        }
        return $range;
    }



    /**
     * @param $start_time
     * @param $end_time
     * 年度统计
     */
    public function getYears($start_time, $end_time)
    {
        $datetime1 = new \DateTime($start_time);
        $datetime2 = new \DateTime($end_time);
        $interval = $datetime1->diff($datetime2);
        $years = $interval->format('%Y') + 1;

        for ($i = 0; $i <= $years; $i++) {
            $key = date('Y', strtotime("+{$i} year", strtotime($start_time)));
            $range[$key] = [
                'start_time' => date('Y-01-01 00:00:00', strtotime("+{$i} year", strtotime($start_time))),
                'end_time' => date('Y-12-31 23:59:59', strtotime("+{$i} year", strtotime($start_time))),
            ];
        }
        return $range;
    }


    /**
     * @param $quarters
     * @param $platform_code
     * @param $param
     * @return mixed
     * 返回重组后的统计数据
     */
    public function datestatistics($platform_code, $param, $type)
    {
        switch ($type) {
            case 5:
                $select = '月度';
                break;
            case 6:
                $select = '季度';
                break;
            case 7:
                $select = '年';
                break;
        }
        $data = [];
        $data1 = [];
        $feedback = 0;
        $zong_negative = 0;
        $zong_positive = 0;
        if ($platform_code == 'EB') {
            $eb_negative = EbayFeedback::getFeedbackPlatform($param, 2);   //获取ebay差评
            $eb_positive = EbayFeedback::getFeedbackPlatform($param, 4);  //获取ebay好评
            foreach ($eb_negative as $k => $v) {
                if ($eb_negative[$k] || $eb_positive[$k]) {
                    $eb_positive_rate[$k] = round($eb_positive[$k] / ($eb_negative[$k] + $eb_positive[$k]), 4);  //获取ebay好评率
                } else {
                    $eb_positive_rate[$k] = 0;
                }
                if ($eb_positive[$k]) {
                    $eb_negative_rate[$k] = round($eb_negative[$k] / $eb_positive[$k], 4); //差评率
                } else {
                    $eb_negative_rate[$k] = 0;
                }

                $data['categories'][] = $data1['categories'][] = $k . $select;
                $data['series'][0]['name'] = $data1['series'][0]['name'] = 'EB';
                $data['series'][0]['data'][] = $eb_positive_rate[$k];
                $data1['series'][0]['data'][] = $eb_negative_rate[$k];
                $feedback += $eb_negative[$k] + $eb_positive[$k];
                $zong_negative += $eb_negative[$k];
                $zong_positive += $eb_positive[$k];

            }
            $data['series'][0] = (object)$data['series'][0];
            $data1['series'][0] = (object)$data1['series'][0];

        }
        if ($platform_code == 'ALI') {
            $ali_negative = AliexpressEvaluateList::getFeedbackPlatform($param, [1, 2]);  //获取ALI差评
            $ali_positive = AliexpressEvaluateList::getFeedbackPlatform($param, [4, 5]);  //获取ALI好评
            $ali_feedback = AliexpressEvaluateList::getFeedbackPlatform($param, []);   //总评价数量

            foreach ($ali_negative as $k => $v) {
                if (!empty($ali_feedback[$k])) {
                    $ali_negative_rote[$k] = round($ali_negative[$k] / $ali_feedback[$k], 4); //速卖通差评率
                    $ali_positive_rote[$k] = round($ali_positive[$k] / $ali_feedback[$k], 4); //速卖通好评率
                } else {
                    $ali_negative_rote[$k] = 0;
                    $ali_positive_rote[$k] = 0;
                }
                $data['categories'][] = $data1['categories'][] = $k . $select;
                $data['series'][0]['name'] = $data1['series'][0]['name'] = 'ALI';
                $data['series'][0]['data'][] = $ali_positive_rote[$k];
                $data1['series'][0]['data'][] = $ali_negative_rote[$k];
                $feedback += $ali_feedback[$k];
                $zong_negative += $ali_negative[$k];
                $zong_positive += $ali_positive[$k];
            }
            $data['series'][0] = (object)$data['series'][0];
            $data1['series'][0] = (object)$data1['series'][0];
        }

        if(empty($platform_code)){
            $eb_negative = EbayFeedback::getFeedbackPlatform($param, 2);   //获取ebay差评
            $eb_positive = EbayFeedback::getFeedbackPlatform($param, 4);  //获取ebay好评
            foreach ($eb_negative as $k => $v) {
                if ($eb_negative[$k] || $eb_positive[$k]) {
                    $eb_positive_rate[$k] = round($eb_positive[$k] / ($eb_negative[$k] + $eb_positive[$k]), 4);  //获取ebay好评率
                } else {
                    $eb_positive_rate[$k] = 0;
                }
                if ($eb_positive[$k]) {
                    $eb_negative_rate[$k] = round($eb_negative[$k] / $eb_positive[$k], 4); //差评率
                } else {
                    $eb_negative_rate[$k] = 0;
                }

                $data['categories'][] = $data1['categories'][] = $k . $select;
                $data['series'][0]['name'] = $data1['series'][0]['name'] = 'EB';
                $data['series'][0]['data'][] = $eb_positive_rate[$k];
                $data1['series'][0]['data'][] = $eb_negative_rate[$k];
                $feedback += $eb_negative[$k] + $eb_positive[$k];
                $zong_negative += $eb_negative[$k];
                $zong_positive += $eb_positive[$k];

            }
            $data['series'][0] = (object)$data['series'][0];
            $data1['series'][0] = (object)$data1['series'][0];


            $ali_negative = AliexpressEvaluateList::getFeedbackPlatform($param, [1, 2]);  //获取ALI差评
            $ali_positive = AliexpressEvaluateList::getFeedbackPlatform($param, [4, 5]);  //获取ALI好评
            $ali_feedback = AliexpressEvaluateList::getFeedbackPlatform($param, []);   //总评价数量

            foreach ($ali_negative as $k => $v) {
                if (!empty($ali_feedback[$k])) {
                    $ali_negative_rote[$k] = round($ali_negative[$k] / $ali_feedback[$k], 4); //速卖通差评率
                    $ali_positive_rote[$k] = round($ali_positive[$k] / $ali_feedback[$k], 4); //速卖通好评率
                } else {
                    $ali_negative_rote[$k] = 0;
                    $ali_positive_rote[$k] = 0;
                }
                $data['categories'][] = $data1['categories'][] = $k . $select;
                $data['series'][1]['name'] = $data1['series'][1]['name'] = 'ALI';
                $data['series'][1]['data'][] = $ali_positive_rote[$k];
                $data1['series'][1]['data'][] = $ali_negative_rote[$k];
                $feedback += $ali_feedback[$k];
                $zong_negative += $ali_negative[$k];
                $zong_positive += $ali_positive[$k];
            }
            $data['series'][1] = (object)$data['series'][1];
            $data1['series'][1] = (object)$data1['series'][1];

        }

        $data['title'] = '平台好评率报表（%）';
        $data1['title'] = '平台差评率报表（%）';
        $data['text'] = $data1['text'] = '数量（%）';
        $data['feedback'] = $data1['feedback'] = $feedback;
        $data['zong_positive'] = $zong_positive;
        $data1['zong_negative'] = $zong_negative;
        if(!empty($feedback)){
            $data['zong_positive_rate'] = round($zong_positive / $feedback, 4) * 100 . "%";
            $data1['zong_negative_rate'] = round($zong_negative / $feedback, 4) * 100 . "%";
        }else{
            $data['zong_positive_rate'] = 0;
            $data1['zong_negative_rate'] = 0;
        }

        return ['data' => $data,'data1' => $data1];

    }

    /**
     * @param $platform_code
     * @param $param
     * @param $type
     * @param $account
     * @return array
     * 按账号统计
     */

    public function getAccountstatics($platform_code, $param, $type, $account)
    {
        switch ($type) {
            case 5:
                $select = '月';
                break;
            case 6:
                $select = '季度';
                break;
            case 7:
                $select = '年';
                break;
        }

        if(!empty($platform_code)){
            $data = [];
            $data1 = [];
            $tmp = [];
            $item = [];

            if($platform_code == 'EB'){
                $eb_negative = EbayFeedback::getFeedbackAccount($param, 2, $account);   //获取ebay差评率
                $eb_positive = EbayFeedback::getFeedbackAccount($param, 4, $account);  //获取ebay好评率
                foreach ($eb_negative['data'] as $key => $quarter) {
                    $data['categories'][] =  $key . $select;
                    if (is_array($quarter) && !empty($quarter)) {
                        foreach ($quarter as $qkey => $num) {
                            if (!isset($tmp[$qkey])) {
                                $tmp[$qkey] = new \stdClass();
                                $tmp[$qkey]->name = $qkey;
                            }
                            $tmp[$qkey]->data[] = intval($num);
                        }
                    }
                }

                foreach ($eb_positive['data'] as $key => $quarter) {
                    $data1['categories'][] =  $key . $select;
                    if (is_array($quarter) && !empty($quarter)) {
                        foreach ($quarter as $qkey => $num) {
                            if (!isset($item[$qkey])) {
                                $item[$qkey] = new \stdClass();
                                $item[$qkey]->name = $qkey;
                            }
                            $item[$qkey]->data[] = intval($num);
                        }
                    }
                }

                //差评率
                if (!empty($tmp)) {
                    $series = $tmp;
                    usort($series, function ($a, $b) {
                        $sumA = 0;
                        if (!empty($a->data)) {
                            foreach ($a->data as $val) {
                                $sumA += $val;
                            }
                        }
                        $sumB = 0;
                        if (!empty($b->data)) {
                            foreach ($b->data as $val) {
                                $sumB += $val;
                            }
                        }
                        if ($sumA == $sumB) {
                            return 0;
                        }
                        return $sumA > $sumB ? -1 : 1;
                    });

                    if (count($series) > 10) {
                        $series = array_slice($series, 0, 10);
                    }
                    if (!empty($series)) {
                        foreach ($series as $val) {
                            $data['series'][] = $val;
                        }
                    }
                }
                //好评率
                if (!empty($item)) {
                    $series = $item;
                    usort($series, function ($a, $b) {
                        $sumA = 0;
                        if (!empty($a->data)) {
                            foreach ($a->data as $val) {
                                $sumA += $val;
                            }
                        }
                        $sumB = 0;
                        if (!empty($b->data)) {
                            foreach ($b->data as $val) {
                                $sumB += $val;
                            }
                        }
                        if ($sumA == $sumB) {
                            return 0;
                        }
                        return $sumA > $sumB ? -1 : 1;
                    });

                    if (count($series) > 10) {
                        $series = array_slice($series, 0, 10);
                    }
                    if (!empty($series)) {
                        foreach ($series as $val) {
                            $data1['series'][] = $val;
                        }
                    }
                }

                $data['feedback'] = $data1['feedback'] = $eb_negative['feedback'];
                $data['zong_negative'] = $eb_negative['feedback1'];
                $data1['zong_positive'] = $eb_positive['feedback1'];
                if(!empty($eb_negative['feedback'])){
                    $data['zong_negative_rate'] = round($data['zong_negative'] / $eb_negative['feedback'], 4) * 100 . "%";
                    $data1['zong_positive_rate'] = round($data1['zong_positive'] / $eb_negative['feedback'], 4) * 100 . "%";
                }else{
                    $data['zong_negative_rate'] = 0 . "%";
                    $data1['zong_positive_rate'] = 0 . "%";
                }



            }

            if($platform_code == 'ALI') {
                $ali_negative = AliexpressEvaluateList::getFeedbackAccount($param, [1,2], $account);  //获取ALI差评率

                $ali_positive = AliexpressEvaluateList::getFeedbackAccount($param, [4,5], $account);  //获取ALI好评率

                foreach ($ali_negative['data'] as $key => $quarter) {
                    $data['categories'][] = $key . $select;
                    if (is_array($quarter) && !empty($quarter)) {
                        foreach ($quarter as $qkey => $num) {
                            if (!isset($tmp[$qkey])) {
                                $tmp[$qkey] = new \stdClass();
                                $tmp[$qkey]->name = $qkey;
                            }
                            $tmp[$qkey]->data[] = floatval($num);
                        }
                    }
                }

                //差评率
                if (!empty($tmp)) {
                    $series = $tmp;
                    usort($series, function ($a, $b) {
                        $sumA = 0;
                        if (!empty($a->data)) {
                            foreach ($a->data as $val) {
                                $sumA += $val;
                            }
                        }
                        $sumB = 0;
                        if (!empty($b->data)) {
                            foreach ($b->data as $val) {
                                $sumB += $val;
                            }
                        }
                        if ($sumA == $sumB) {
                            return 0;
                        }
                        return $sumA > $sumB ? -1 : 1;
                    });

                    if (count($series) > 10) {
                        $series = array_slice($series, 0, 10);
                    }
                    if (!empty($series)) {
                        foreach ($series as $val) {
                            $data['series'][] = $val;
                        }
                    }
                }

                foreach ($ali_positive['data'] as $key => $quarter) {
                    $data1['categories'][] =  $key . $select;
                    if (is_array($quarter) && !empty($quarter)) {
                        foreach ($quarter as $qkey => $num) {
                            if (!isset($item[$qkey])) {
                                $item[$qkey] = new \stdClass();
                                $item[$qkey]->name = $qkey;
                            }
                            $item[$qkey]->data[] = intval($num);
                        }
                    }
                }

                //好评率
                if (!empty($item)) {
                    $series = $item;
                    usort($series, function ($a, $b) {
                        $sumA = 0;
                        if (!empty($a->data)) {
                            foreach ($a->data as $val) {
                                $sumA += $val;
                            }
                        }
                        $sumB = 0;
                        if (!empty($b->data)) {
                            foreach ($b->data as $val) {
                                $sumB += $val;
                            }
                        }
                        if ($sumA == $sumB) {
                            return 0;
                        }
                        return $sumA > $sumB ? -1 : 1;
                    });

                    if (count($series) > 10) {
                        $series = array_slice($series, 0, 10);
                    }
                    if (!empty($series)) {
                        foreach ($series as $val) {
                            $data1['series'][] = $val;
                        }
                    }
                }

                $data['feedback'] = $data1['feedback'] = $ali_negative['feedback'];
                $data['zong_negative'] = $ali_negative['feedback1'];
                $data1['zong_positive'] = $ali_positive['feedback1'];
                if(!empty($ali_negative['feedback'])){
                    $data['zong_negative_rate'] = round($data['zong_negative'] / $ali_negative['feedback'], 4) * 100 . "%";
                    $data1['zong_positive_rate'] = round($data1['zong_positive'] / $ali_negative['feedback'], 4) * 100 . "%";
                }else{
                    $data['zong_negative_rate'] = 0 . "%";
                    $data1['zong_positive_rate'] = 0 . "%";
                }



            }


            $data['title'] = '账号差评率报表（%）';
            $data1['title'] = '账号好评率报表（%）';
            $data['text'] = $data1['text'] = '数量（%）';
            return ['data' => $data1,'data1' => $data];
        }
    }

    /**
     * @param $platform_code
     * @param $param
     * @param $type
     * @param $user_name
     * 按客服统计
     */
    public function getKefutstatics($platform_code, $param, $type, $user_name)
    {
        switch ($type) {
            case 5:
                $select = '月';
                break;
            case 6:
                $select = '季度';
                break;
            case 7:
                $select = '年';
                break;
        }

        if (!empty($platform_code)) {
            $data = [];
            $data1 = [];
            $tmp = [];
            $item = [];

            if ($platform_code == 'EB') {
                $eb_negative = EbayFeedback::getFeedbackKefu($param, 2, $user_name);   //获取ebay差评率
                $eb_positive = EbayFeedback::getFeedbackKefu($param, 4, $user_name);  //获取ebay好评率

                foreach ($eb_negative['data'] as $key => $quarter) {
                    $data['categories'][] =  $key . $select;
                    if (is_array($quarter) && !empty($quarter)) {
                        foreach ($quarter as $qkey => $num) {
                            if (!isset($tmp[$qkey])) {
                                $tmp[$qkey] = new \stdClass();
                                $tmp[$qkey]->name = $qkey;
                            }
                            $tmp[$qkey]->data[] = intval($num);
                        }
                    }
                }


                foreach ($eb_positive['data'] as $key => $quarter) {
                    $data1['categories'][] =  $key . $select;
                    if (is_array($quarter) && !empty($quarter)) {
                        foreach ($quarter as $qkey => $num) {
                            if (!isset($item[$qkey])) {
                                $item[$qkey] = new \stdClass();
                                $item[$qkey]->name = $qkey;
                            }
                            $item[$qkey]->data[] = intval($num);
                        }
                    }
                }


                //差评率
                if (!empty($tmp)) {
                    $series = $tmp;
                    usort($series, function ($a, $b) {
                        $sumA = 0;
                        if (!empty($a->data)) {
                            foreach ($a->data as $val) {
                                $sumA += $val;
                            }
                        }
                        $sumB = 0;
                        if (!empty($b->data)) {
                            foreach ($b->data as $val) {
                                $sumB += $val;
                            }
                        }
                        if ($sumA == $sumB) {
                            return 0;
                        }
                        return $sumA > $sumB ? -1 : 1;
                    });

                    if (count($series) > 10) {
                        $series = array_slice($series, 0, 10);
                    }
                    if (!empty($series)) {
                        foreach ($series as $val) {
                            $data['series'][] = $val;
                        }
                    }
                }

                //好评率
                if (!empty($item)) {
                    $series = $item;
                    usort($series, function ($a, $b) {
                        $sumA = 0;
                        if (!empty($a->data)) {
                            foreach ($a->data as $val) {
                                $sumA += $val;
                            }
                        }
                        $sumB = 0;
                        if (!empty($b->data)) {
                            foreach ($b->data as $val) {
                                $sumB += $val;
                            }
                        }
                        if ($sumA == $sumB) {
                            return 0;
                        }
                        return $sumA > $sumB ? -1 : 1;
                    });

                    if (count($series) > 10) {
                        $series = array_slice($series, 0, 10);
                    }
                    if (!empty($series)) {
                        foreach ($series as $val) {
                            $data1['series'][] = $val;
                        }
                    }
                }

                $data['feedback'] = $data1['feedback'] = $eb_negative['feedback'];
                $data['zong_negative'] = $eb_negative['feedback1'];
                $data1['zong_positive'] = $eb_positive['feedback1'];
                if(!empty($eb_negative['feedback'])){
                    $data['zong_negative_rate'] = round($data['zong_negative'] / $eb_negative['feedback'], 4) * 100 . "%";
                    $data1['zong_positive_rate'] = round($data1['zong_positive'] / $eb_negative['feedback'], 4) * 100 . "%";
                }else{
                    $data['zong_negative_rate'] = 0;
                    $data1['zong_positive_rate'] = 0;
                }

            }

            if($platform_code == 'ALI') {
                $ali_negative = AliexpressEvaluateList::getFeedbackKefu($param, [1,2], $user_name);  //获取ALI差评率

                $ali_positive = AliexpressEvaluateList::getFeedbackKefu($param, [4,5], $user_name);  //获取ALI好评率

                foreach ($ali_negative['data'] as $key => $quarter) {
                    $data['categories'][] = $key . $select;
                    if (is_array($quarter) && !empty($quarter)) {
                        foreach ($quarter as $qkey => $num) {
                            if (!isset($tmp[$qkey])) {
                                $tmp[$qkey] = new \stdClass();
                                $tmp[$qkey]->name = $qkey;
                            }
                            $tmp[$qkey]->data[] = floatval($num);
                        }
                    }
                }

                //差评率
                if (!empty($tmp)) {
                    $series = $tmp;
                    usort($series, function ($a, $b) {
                        $sumA = 0;
                        if (!empty($a->data)) {
                            foreach ($a->data as $val) {
                                $sumA += $val;
                            }
                        }
                        $sumB = 0;
                        if (!empty($b->data)) {
                            foreach ($b->data as $val) {
                                $sumB += $val;
                            }
                        }
                        if ($sumA == $sumB) {
                            return 0;
                        }
                        return $sumA > $sumB ? -1 : 1;
                    });

                    if (count($series) > 10) {
                        $series = array_slice($series, 0, 10);
                    }
                    if (!empty($series)) {
                        foreach ($series as $val) {
                            $data['series'][] = $val;
                        }
                    }
                }

                foreach ($ali_positive['data'] as $key => $quarter) {
                    $data1['categories'][] =  $key . $select;
                    if (is_array($quarter) && !empty($quarter)) {
                        foreach ($quarter as $qkey => $num) {
                            if (!isset($item[$qkey])) {
                                $item[$qkey] = new \stdClass();
                                $item[$qkey]->name = $qkey;
                            }
                            $item[$qkey]->data[] = intval($num);
                        }
                    }
                }

                //好评率
                if (!empty($item)) {
                    $series = $item;
                    usort($series, function ($a, $b) {
                        $sumA = 0;
                        if (!empty($a->data)) {
                            foreach ($a->data as $val) {
                                $sumA += $val;
                            }
                        }
                        $sumB = 0;
                        if (!empty($b->data)) {
                            foreach ($b->data as $val) {
                                $sumB += $val;
                            }
                        }
                        if ($sumA == $sumB) {
                            return 0;
                        }
                        return $sumA > $sumB ? -1 : 1;
                    });

                    if (count($series) > 10) {
                        $series = array_slice($series, 0, 10);
                    }
                    if (!empty($series)) {
                        foreach ($series as $val) {
                            $data1['series'][] = $val;
                        }
                    }
                }

                $data['feedback'] = $data1['feedback'] = $ali_negative['feedback'];
                $data['zong_negative'] = $ali_negative['feedback1'];
                $data1['zong_positive'] = $ali_positive['feedback1'];
                if(!empty($ali_negative['feedback'])){
                    $data['zong_negative_rate'] = round($data['zong_negative'] / $ali_negative['feedback'], 4) * 100 . "%";
                    $data1['zong_positive_rate'] = round($data1['zong_positive'] / $ali_negative['feedback'], 4) * 100 . "%";
                }else{
                    $data['zong_negative_rate'] = 0 . "%";
                    $data1['zong_positive_rate'] = 0 . "%";
                }



            }

            }

        $data['title'] = '客服差评率报表（%）';
        $data1['title'] = '客服好评率报表（%）';
        $data['text'] = $data1['text'] = '数量（%）';

        return ['data' => $data1,'data1' => $data];
        }



}