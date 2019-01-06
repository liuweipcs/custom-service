<?php
namespace app\modules\services\modules\paypal\controllers;

use app\modules\services\modules\amazon\components\Refund;
use Yii;
use yii\helpers\Url;
use app\common\VHelper;
use yii\web\Controller;
use yii\web\HttpException;
use app\modules\systems\models\RefundAccount;
use app\modules\systems\models\TaskSupply;
use app\modules\systems\models\Task;
use app\modules\systems\models\Transactions;
use app\modules\systems\models\TransactionAddress;
use PayPal\PayPalAPI\TransactionSearchReq;
use PayPal\PayPalAPI\TransactionSearchRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use PayPal\PayPalAPI\GetTransactionDetailsReq;
use PayPal\PayPalAPI\GetTransactionDetailsRequestType;

class PaypalsupplyController extends Controller
{
    const GET_DETAIL_COUNT = 3;
    const THREAD_TIME = 1200;   // 执行时间
    const TIME_OFFSET = 1200;
    const GET_THEADER_COUNT = 5;

    public function actionAccount()
    {
        //获取补充的paypal账号信息
        $ids = array(7,9,10,11,12,13,14,16,17,18,19,20,21);
        $refund_account_info = RefundAccount::find()
            ->select('id,email,api_username,api_password,api_signature')
            ->where(['status' => RefundAccount::STATUS_START])
            ->andWhere(['in','id',$ids])
            ->asArray()
            ->all();

        //没有要拉取数据的eb账号
        if (empty($refund_account_info)) {
//            $result_task_success = $this->saveTaskStatus($task_model, Task::EXECUTE_SUCCESS, 'no account info');
            exit('no account need to get data');
        }
        //遍历账号信息拉取每个账号的交易信息
        foreach ($refund_account_info as $key => $value) {
            sleep(2);
            $thread = Task::find()->where(['account_id'=>$value['id'], 'status' => 2])->one();
            if($thread)
            {
                if(time() - strtotime($thread->create_time) >= self::THREAD_TIME)
                {
                    $thread->status = 4;
                    $thread->message = 'time out';
                    $thread->save();
                    VHelper::throwTheader('/services/paypal/paypalsupply/index', ['account_id'=> $value['id']]);
                }
                else
                {
                    continue;
                }
            }
            else
            {
                VHelper::throwTheader('/services/paypal/paypalsupply/index', ['account_id'=> $value['id']]);
            }

        }

        exit('do it success');
    }

    public function actionIndex()
    {
        set_time_limit(self::THREAD_TIME);

        $account_id = $_REQUEST['account_id'];
        $time_offset = isset($_REQUEST['time_offset']) ? $_REQUEST['time_offset'] : self::TIME_OFFSET;
        $count = isset($_REQUEST['count']) ? (int)$_REQUEST['count'] : 0;
        $time_offset = (int)$time_offset;
        if($time_offset <= 0)
            $time_offset = self::TIME_OFFSET;

        $account_info = RefundAccount::findOne($account_id);

        if(!$account_info)
            exit('this account_id is invalid');

        $task_model = Task::insertTask($account_info->id,$time_offset);

        //新增失败
        if ($task_model === false) {
            exit('insert task fail');
        }
        //将计划任务标记为执行中
        $result_task_execution = $this->saveTaskStatus($task_model, Task::IN_EXECUTION_TASK, null);

        // 获取请求拉取交易信息的接口要传的参数
        $params_search = $this->getTransactionSearchConfigParams($account_info, $task_model);

        //请求拉取交易信息的接口获取数据
//        $count = 0;
//        do{
            list ($result, $message) = VHelper::ebTransactionSearch($params_search);

//            $count++;
//        }while($result === false && $count<self::GET_DETAIL_COUNT );

        //获取数据失败
        if ($result === false) {
            $result_task_fail = $this->saveTaskStatus($task_model, Task::EXECUTE_FAILED, $message);
            if($count <= self::GET_THEADER_COUNT)
            {
                $count++;
                VHelper::throwTheader('/services/paypal/paypalsupply/index',['account_id'=>$account_id,'time_offset'=>$time_offset/2,'count'=>$count]);
            }
            exit($message);
        }
        $paypal_count = count($result->PaymentTransactions);
        var_dump($paypal_count);
        //在指定日期内没有交易信息则跳过此账号
        if (empty($result->PaymentTransactions)) {
            $this->saveTaskStatus($task_model, Task::EXECUTE_SUCCESS, 'no data to insert');
            exit;
        }
        if(count($result->PaymentTransactions) >= 100)
        {
            $this->saveTaskStatus($task_model,Task::EXECUTE_FAILED,'该时间段内数据超出100条');
            if($count <= self::GET_THEADER_COUNT)
            {
                $count++;
                VHelper::throwTheader('/services/paypal/paypalsupply/index',['account_id'=>$account_id,'time_offset'=>$time_offset/2,'count'=>$count]);
            }
            exit;
        }

        //遍历获取到的交易信息然后去拉取交易详情信息
        foreach ($result->PaymentTransactions as $detail_info) {

            $isExisted = Transactions::isExisted($detail_info->TransactionID);
            if($isExisted) continue;
            // 只有交易类型为支付和退款时查询交易详情
            if ($detail_info->Type == 'Payment' || $detail_info->Type == 'Refund') {

                //获取请求拉取交易详情信息的接口要传的参数
                $params_detail = $this->getTransactionDetailConfigParams($account_info, $detail_info->TransactionID);

                $count = 0;
                do{
                    list ($result, $message) = VHelper::ebTransactionDeail($params_detail);
//                        var_dump($count);
                    $count++;
                }while($result === false && $count<self::GET_DETAIL_COUNT );

                if($result === false)
                {
//                        var_dump('Get Detail false Finally!');
                    list($result, $message) = Transactions::insertPartTransactionData($detail_info,$account_info->email);
                    if($result === false)
                    {
                        $result_task_fail = $this->saveTaskStatus($task_model, Task::EXECUTE_FAILED, $message);
                        exit($message);
                    }
                    else
                    {
                        continue;
                    }
                }

                // 开启事务
                $Transaction = Yii::$app->db->beginTransaction();

                // 地址详情
                $PaymentTransactionDetails = $result->PaymentTransactionDetails->PayerInfo->Address;

                //存取交易信息到客服系统的数据库里
                list ($result, $message) = Transactions::insertTransactionData($result->PaymentTransactionDetails,$detail_info);

                if ($result === false) {
                    $result_task_fail = $this->saveTaskStatus($task_model, Task::EXECUTE_FAILED, $message);
                    $Transaction->rollback();
                    exit($message);
                }

                // 存取交易地址信息到客服系统数据库
                list ($result, $message) = TransactionAddress::insertAddressData($PaymentTransactionDetails,$detail_info->TransactionID);

                if ($result === false) {
                    $result_task_fail = $this->saveTaskStatus($task_model, Task::EXECUTE_FAILED, $message);
                    $Transaction->rollback();
                    exit($message);
                }

                $Transaction->commit();
            } // 已获取部分信息存入数据库
//                else {
//                    list ($result, $message) = Transactions::insertPartTransactionData($detail_info);
//
//                    if ($result === false) {
//                        $result_task_fail = $this->saveTaskStatus($task_model, Task::EXECUTE_FAILED, $message);
//                        exit($message);
//                    }
//
//                }
        }

        $result_task_success = $this->saveTaskStatus($task_model, Task::EXECUTE_SUCCESS, 'excute success');

        exit('do it success');

    }

    /**
     * @desc 获取拉取eb交易信息要传的参数数组
     * @param array $account_info eb退票账号信息
     * @param object $task_model 当前计划任务的对象
     */
    private function getTransactionSearchConfigParams($account_info, $task_model)
    {
        $params['search_config'] = [
            'acct1.UserName'  => $account_info['api_username'],
            'acct1.Password'  => $account_info['api_password'],
            'acct1.Signature' => $account_info['api_signature'],
        ];

        //构造拉取的开始时间和结束时间
        $end = strtotime($task_model->end_date_time);
//        $end_time = mktime(date('H',$end),date('i',$end),date('s',$end),date('m',$end),date('d',$end),date('Y',$end));
//        $params['endDate'] = date("Y-m-d\TH:i:sO", $end_time);
        $params['endDate'] = str_replace('+00:00', 'Z', gmdate('c',$end));

        $start = strtotime($task_model->start_date_time);
//        $start_time = mktime(date('H',$start),date('i',$start),date('s',$start),date('m',$start),date('d',$start),date('Y',$start));
//        $params['startDate'] = date("Y-m-d\TH:i:sO", $start_time);
        $params['startDate'] = str_replace('+00:00', 'Z', gmdate('c',$start));

        //返回结果
        return $params;
    }
    /**
     * @desc 获取拉取eb交易详情信息要传的参数数组
     * @param array $account_info eb退票账号信息
     * @param object $transaction_info 当前的交易信息
     */
    private function getTransactionDetailConfigParams($account_info, $transaction_id)
    {
        $params['detail_config'] = [
            'acct1.UserName'  => $account_info['api_username'],
            'acct1.Password'  => $account_info['api_password'],
            'acct1.Signature' => $account_info['api_signature'],
        ];

        $params['transID'] = $transaction_id;

        return $params;
    }
    /**
     * @desc 修改计划任务的状态
     * @param object $task_model 计划任务模型
     * @param string $status 要修改成的状态值
     * @param string $message 描述
     */
    private function saveTaskStatus($task_model, $status, $message=null)
    {
        $task_model->status  = $status;

        //如果存在描述则修改计划任务的描述
        if (!empty($message)) {
            $task_model->message = $message;
        }
        //如果计划任务执行成功则修改完成时间
        if ($status == Task::EXECUTE_SUCCESS) {
            $task_model->complete_time = date('Y-m-d H:i:s', time());
        }

        return $task_model->save();
    }

}