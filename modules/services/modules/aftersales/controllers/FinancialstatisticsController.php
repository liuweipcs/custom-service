<?php
namespace app\modules\services\modules\aftersales\controllers;

use app\modules\services\modules\aftersales\models\AftersalesFinancialStatistics;
use app\modules\services\modules\aftersales\models\AftersalesFinancialStatisticsDel;
use Yii;
use yii\web\Controller;
use app\common\VHelper;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\accounts\models\Account;
use app\modules\services\modules\aftersales\models\FinancialStatistics;

class FinancialstatisticsController extends Controller
{
    /**
     * 用于刷新售后单成本数据统计(aftersales_financial_statistics表)
     * /services/aftersales/financialstatistics/flushdata
     */
    public function actionFlushdata()
    {
        set_time_limit(0);

        try {
            //账号ID(客服系统的)
            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $model = new FinancialStatistics();
                $model->flushData($accountId);

                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTaskOfType(AccountTaskQueue::AFTERSALES_FINANCIAL_STATISTICS);
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/aftersales/financialstatistics/flushdata', ['id' => $nextAccountId], 'GET', 1200);
                }

                die('Aftersales Financial Statistics End');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()->where([
                    'type' => AccountTaskQueue::AFTERSALES_FINANCIAL_STATISTICS
                ])->count();

                if (empty($count)) {
                    //把客服系统所有有效账号全部取出来
                    $accounts = Account::find()->where(['status' => 1])->all();

                    if (!empty($accounts)) {
                        foreach ($accounts as $account) {
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::AFTERSALES_FINANCIAL_STATISTICS;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::AFTERSALES_FINANCIAL_STATISTICS,
                ], 10);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/aftersales/financialstatistics/flushdata', ['id' => $accountId], 'GET', 1200);
                        sleep(2);
                    }
                }

                die('Run Aftersales Financial Statistics');
            }
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
            echo $e->getFile(), "\n";
            echo $e->getLine(), "\n";
        }
    }

    /**
     * 获取售后单成本数据统计
     * /services/aftersales/financialstatistics/getfinancialstatistics
     */
    public function actionGetfinancialstatistics()
    {
        //类型
        $type = !empty($_REQUEST['type']) ? trim($_REQUEST['type']) : 0;
        //开始时间
        $startTime = !empty($_REQUEST['start_time']) ? trim($_REQUEST['start_time']).' 00:00:00' : '';
        //结束时间
        $endTime = !empty($_REQUEST['end_time']) ? trim($_REQUEST['end_time']).' 00:00:00' : '';
        //当前页数
        $nowPage = !empty($_REQUEST['page']) ? trim($_REQUEST['page']) : 1;
        //分页大小
        $limit = !empty($_REQUEST['limit']) ? trim($_REQUEST['limit']) : 500;
        //位移
        $offset = ($nowPage-1)*$limit;
        

        //平台CODE
        $platformCode = !empty($_REQUEST['platform_code']) ? trim($_REQUEST['platform_code']) : '';
        //账号ID(客服系统的)
        $accountId = !empty($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) : 0;
        //账号ID(ERP系统的)
        $erpAccountId = !empty($_REQUEST['erp_account_id']) ? trim($_REQUEST['erp_account_id']) : 0;

        $query = AftersalesFinancialStatistics::find();
        
        
        $query->select('t.id,t.platform_code,t.after_sales_id,t.platform_order_id,t.order_id,t.erp_account_id,t.warehouse_code,t.refund_time,d.sku,d.qty,d.resend_cost,d.resend_cost_rmb');
        $query->from('yibai_aftersales_financial_statistics as t');
        $query->leftJoin('yibai_aftersales_financial_statistics_del as d', 't.id = d.financial_id');
        $query->where(1);
        if(!empty($type)){
            $query->andWhere(['t.type'=>$type]);
        }
        
        if (!empty($startTime) && !empty($endTime)) {
            if ($type == AftersalesFinancialStatistics::AFTER_TYPE_REFUND) {
                $query->andWhere(['between', 't.refund_time', $startTime, $endTime]);
            } else if ($type == AftersalesFinancialStatistics::AFTER_TYPE_REDIRECT) {
                $query->andWhere(['between', 't.resend_time', $startTime, $endTime]);
            } else {
                $query->andWhere(['between', 't.create_time', $startTime, $endTime]);
            }
        } else if (!empty($startTime)) {
            if ($type == AftersalesFinancialStatistics::AFTER_TYPE_REFUND) {
                $query->andWhere(['>=', 't.refund_time', $startTime]);
            } else if ($type == AftersalesFinancialStatistics::AFTER_TYPE_REDIRECT) {
                $query->andWhere(['>=', 't.resend_time', $startTime]);
            } else {
                $query->andWhere(['>=', 't.create_time', $startTime]);
            }
        } else if (!empty($endTime)) {
            if ($type == AftersalesFinancialStatistics::AFTER_TYPE_REFUND) {
                $query->andWhere(['<=', 't.refund_time', $endTime]);
            } else if ($type == AftersalesFinancialStatistics::AFTER_TYPE_REDIRECT) {
                $query->andWhere(['<=', 't.resend_time', $endTime]);
            } else {
                $query->andWhere(['<=', 't.create_time', $endTime]);
            }
        }

        if (!empty($platformCode)) {
            $query->andWhere(['t.platform_code' => $platformCode]);
        }
        if (!empty($accountId)) {
            $query->andWhere(['t.account_id' => $accountId]);
        }
        if (!empty($erpAccountId)) {
            $query->andWhere(['t.erp_account_id' => $erpAccountId]);
        }

        $result = [];
        $countQuery = clone $query;
        $count = $countQuery->count();
        $totalPage = 0;
        if($count){
            $totalPage = ceil($count/$limit);
        }
        $data = $query->offset($offset)
                        ->limit($limit)
                        ->orderBy('id ASC')
                        ->asArray()
                        ->all();
        //echo $query->createCommand()->getRawSql().'<br/>---<br/>';
        $result = [
            'nowPage' => $nowPage,
            'totalPage' => $totalPage,
            'data' => json_encode($data)
        ];
        echo json_encode($result);
        die;
//        $data = $query
//            ->select([
//                'id',
//                'platform_code',
//                'type',
//                'after_sales_id',
//                'account_id',
//                'erp_account_id',
//                'platform_order_id',
//                'order_id',
//                're_order_id',
//                'warehouse_code',
//                're_warehouse_code',
//                'total',
//                'currency',
//                'rate',
//                'profit',
//                're_profit',
//                'cost',
//                'cost_rmb',
//                'shipping_cost',
//                're_shipping_cost',
//                'refund_time',
//                'resend_time',
//                'create_time',
//                'remark',
//            ])
//            ->offset($offset)
//            ->limit($limit)
//            ->orderBy('id ASC')
//            ->asArray()
//            ->all();
//
//        if (!empty($data)) {
//            foreach ($data as $item) {
//                $dels = AftersalesFinancialStatisticsDel::find()
//                    ->select([
//                        'sku',
//                        'qty',
//                        'refund_cost',
//                        'refund_cost_rmb',
//                        'resend_cost',
//                        'resend_cost_rmb',
//                        'avg_purchase_cost',
//                    ])
//                    ->andWhere(['financial_id' => $item['id']])
//                    ->asArray()
//                    ->all();
//                if (!empty($dels)) {
//                    foreach ($dels as $del) {
//                        $tmp = $item;
//                        $tmp = array_merge($tmp, $del);
//                        $result[] = $tmp;
//                    }
//                }
//            }
//        }

        //die(json_encode($result));
    }
}