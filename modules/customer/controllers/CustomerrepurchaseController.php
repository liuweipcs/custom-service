<?php

namespace app\modules\customer\controllers;

use app\components\Controller;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;
use app\modules\customer\models\CustomerList;
use yii\web\Response;

class CustomerrepurchaseController extends Controller
{
    public function actionIndex()
    {
        //所有平台
        $platformList = Platform::getPlatformAsArray();
        array_unshift($platformList, '全部');
        $platform_code = $typeArr = $accountSiteArr = [];
        $type = $account_site = '';
        $end_time = date('Y-m');
        $start_time = date('Y-m', strtotime('-3 month'));
        return $this->render('index', [
            'platformList' => $platformList,
            'platform_code' => $platform_code,
            'typeArr' => $typeArr,
            'typeVal' => $type,
            'accountSiteArr' => $accountSiteArr,
            'start_time' => $start_time,
            'end_time' => $end_time,
        ]);

    }

    /**
     * @return array
     * 根据类型获取统计数据
     */
    public function actionGetplatrate()
    {
        $plat_type = $this->request->post('plat_type');
        $platform_code = $this->request->post('platform_code');
        $type = $this->request->post('type');
        $accountSiteVal = $this->request->post('accountSiteVal');
        $start_time = $this->request->post('start_time');
        $end_time = $this->request->post('end_time');

        $account_id = $this->getAccountdata($platform_code, $type, $accountSiteVal);

        $customer_list = new CustomerList();
        if ($plat_type) {
            switch ($plat_type) {
                case 1:
                    $plat_repurchase_times = $customer_list::getRepurchaseTimes($platform_code, $account_id, $start_time, $end_time);
                    $plat_repurchase_times = '各平台累计回购次数 : ' . $plat_repurchase_times;
                    break;
                case 2:

                    $repurchase_times = $customer_list::getBuyerRepurchase($platform_code, $account_id, $start_time, $end_time);
                    $plat_repurchase_times = '各平台平均回购率 : ' . $repurchase_times;
                    break;
                case 3:
                    $plat_repurchase_times = $customer_list::getAccountRepurchaseTimes($platform_code, $account_id, $start_time, $end_time);
                    $plat_repurchase_times = '各账号累计回购次数 : ' . $plat_repurchase_times;
                    break;
                case 4:
                    $repurchase_times = $customer_list::getBuyerRepurchase($platform_code, $account_id, $start_time, $end_time);

                    $plat_repurchase_times = '各账号平均回购率 : ' . $repurchase_times;
                    break;
            }

        }

        $result = $plat_repurchase_times;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'result' => $result,
            'code' => 200,
            'message' => '请求成功',
        ];

    }

    /**
     *统计图
     */
    public function actionGetstatistics()
    {
        $params = \Yii::$app->request->post();
        $start_time = isset($params['start_time']) ? $params['start_time'] : "";//开始时间
        $end_time = isset($params['end_time']) ? $params['end_time'] : "";//结束时间
        $platform_code = isset($params['platform_code']) ? $params['platform_code'] : "";//选择的平台
        $plat_type = isset($params['plat_type']) ? $params['plat_type'] : "";//统计类型
        $cycle_type = isset($params['cycle_type']) ? $params['cycle_type'] : "";//统计周期
        $type = isset($params['type']) ? $params['type'] : "";//账号类型
        $accountSiteVal = isset($params['accountSiteVal']) ? $params['accountSiteVal'] : "";//站点/账号
        if ($plat_type) {
            $quarter_date = $this->getQuarter($start_time, $end_time); //季度区间
            $month_date = $this->getDateTime($start_time, $end_time); //月度区间
            $years_date = $this->getYears($start_time, $end_time);   //年度区间
            $account = $this->getAccountdata($platform_code, $type, $accountSiteVal);  //获取站点或账号
            switch ($plat_type) {
                case 1:
                    //按季度显示
                    if ($cycle_type == 5) {
                        $quarters = CustomerList::getQuarter($quarter_date, $account);   //获取统计数据
                        $data = $this->datestatistics($quarters, $platform_code, 5, 1); //数据重组
                    } elseif ($cycle_type == 6) {
                        $quarters = CustomerList::getQuarter($month_date, $account);   //获取统计数据
                        $data = $this->datestatistics($quarters, $platform_code, 6, 1); //数据重组
                    } elseif ($cycle_type == 7) {
                        $quarters = CustomerList::getQuarter($years_date, $account);   //获取统计数据
                        $data = $this->datestatistics($quarters, $platform_code, 7, 1); //数据重组
                    }
                    $data['title'] = '平台客户回购次数报表（个）';
                    $data['text'] = '数量（个）';
                    die(json_encode([
                        'code' => 1,
                        'message' => '成功',
                        'data' => $data,
                    ]));
                    break;
                case 2:
                    //按季度显示
                    if ($cycle_type == 5) {
                        $result = CustomerList::getPlatformRate($quarter_date, $account);   //获取统计数据
                        $data = $this->datestatistics($result, $platform_code, 5, 1); //数据重组
                    } elseif ($cycle_type == 6) {
                        $result = CustomerList::getPlatformRate($month_date, $account);   //获取统计数据
                        $data = $this->datestatistics($result, $platform_code, 6, 1); //数据重组
                    } elseif ($cycle_type == 7) {
                        $result = CustomerList::getPlatformRate($years_date, $account);   //获取统计数据
                        $data = $this->datestatistics($result, $platform_code, 7, 1); //数据重组
                    }

                    $data['title'] = '平台客户回购率（%）';
                    $data['text'] = '百分比（%）';
                    die(json_encode([
                        'code' => 1,
                        'message' => '成功',
                        'data' => $data,
                    ]));
                    break;
                case 3:
                    if ($cycle_type == 5) {
                        $result = CustomerList::getAccountNumber($quarter_date, $account, $platform_code);   //获取统计数据
                        $data = $this->datestatistics($result, $platform_code, 5, 2); //数据重组
                    } elseif ($cycle_type == 6) {
                        $result = CustomerList::getAccountNumber($month_date, $account, $platform_code);   //获取统计数据
                        $data = $this->datestatistics($result, $platform_code, 6, 2); //数据重组
                    } elseif ($cycle_type == 7) {
                        $result = CustomerList::getAccountNumber($years_date, $account, $platform_code);   //获取统计数据
                        $data = $this->datestatistics($result, $platform_code, 7, 2); //数据重组
                    }
                    $data['title'] = '账号客户回购次数（个）';
                    $data['text'] = '数量（个）';
                    die(json_encode([
                        'code' => 1,
                        'message' => '成功',
                        'data' => $data,
                    ]));
                    break;
                case 4:
                    if ($cycle_type == 5) {
                        $result = CustomerList::getAccountNumberRate($quarter_date, $account, $platform_code);   //获取统计数据
                        $data = $this->datestatistics($result, $platform_code, 5, 2); //数据重组
                    } elseif ($cycle_type == 6) {
                        $result = CustomerList::getAccountNumberRate($month_date, $account, $platform_code);   //获取统计数据
                        $data = $this->datestatistics($result, $platform_code, 6, 2); //数据重组
                    } elseif ($cycle_type == 7) {
                        $result = CustomerList::getAccountNumberRate($years_date, $account, $platform_code);   //获取统计数据
                        $data = $this->datestatistics($result, $platform_code, 7, 2); //数据重组
                    }
                    $data['title'] = '账号客户回购率（%）';
                    $data['text'] = '百分比（%）';
                    die(json_encode([
                        'code' => 1,
                        'message' => '成功',
                        'data' => $data,
                    ]));
                    break;
            }

        }
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
     * @param $quarters
     * @param $platform_code
     * @param $param
     * @return mixed
     * 返回重组后的统计数据
     */
    public function datestatistics($quarters, $platform_code, $param, $type)
    {
        $tmp = [];
        switch ($param) {
            case 5:
                $select = '季度';
                break;
            case 6:
                $select = '月份';
                break;
            case 7:
                $select = '年';
                break;
        }
        foreach ($quarters as $key => $quarter) {
            $data['categories'][] = $key . $select;
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
        if ($type == 1) {
            if ($platform_code && !empty($tmp)) {
                $one_plat = $tmp[$platform_code];
                $data['series'][] = $one_plat;
            } else {
                foreach ($tmp as $val) {
                    $data['series'][] = $val;
                }
            }
        }
        if ($type == 2) {

            if (!empty($tmp)) {
                $series = $tmp;
                usort($series, function ($a, $b) {
                    $sumA = (!empty($a->data[0]) ? $a->data[0] : 0) + (!empty($a->data[1]) ? $a->data[1] : 0);
                    $sumB = (!empty($b->data[0]) ? $b->data[0] : 0) + (!empty($b->data[1]) ? $b->data[1] : 0);

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
        }

        return $data;
    }

    /**
     * @param $platform_code
     * @param $type
     * @param $accountSiteVal
     * @return array
     * 获取账号
     */
    public function getAccountdata($platform_code, $type, $accountSiteVal)
    {
        $account = [];
        if ($platform_code && $platform_code != 'AMAZON') {
            if ($type) {
                $account = Account::find()->select('old_account_id')->andWhere(['in', 'id', $type])->andWhere(['platform_code' => $platform_code])->column();
            }
        } else if ($platform_code && $platform_code == 'AMAZON') {
            if ($type == 'account' && $accountSiteVal) {
                $account = Account::find()->select('old_account_id')->andWhere(['in', 'id', $accountSiteVal])->andWhere(['platform_code' => $platform_code])->column();
            } else if ($type == 'site' && $accountSiteVal) {
                $account = Account::find()->select('old_account_id')->andWhere(['in', 'site_code', $accountSiteVal])->andWhere(['platform_code' => $platform_code])->column();
            }
        }
        return $account;
    }

}
