<?php 
/*
 * @description 客服系统获取交易信息  计划任务
 * */

namespace app\commands;
use yii\console\Controller;
use app\modules\systems\models\Task;
use app\modules\systems\models\Transactions;
use app\modules\systems\models\TransactionAddress;
use app\modules\systems\models\RefundAccount;
use PayPal\PayPalAPI\TransactionSearchReq;
use PayPal\PayPalAPI\TransactionSearchRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Yii;
use PayPal\PayPalAPI\GetTransactionDetailsReq;
use PayPal\PayPalAPI\GetTransactionDetailsRequestType;
use app\common\VHelper;

class TransactionController extends Controller{

    const GET_DETAIL_COUNT = 3;

	public function actionIndex()
	{   
		set_time_limit(3000);
		
		//新建一条计划任务
        $task_model = Task::insertTask();
        
        //新增失败
		if ($task_model === false) {
            exit('inser task fail');
		}
        
        //将计划任务标记为执行中
        $result_task_execution = $this->saveTaskStatus($task_model, Task::IN_EXECUTION_TASK, null);
        
        //获取所有的eb账号信息
        $refund_account_info = RefundAccount::getList();
        
        //没有要拉取数据的eb账号
        if (empty($refund_account_info)) {
            $result_task_success = $this->saveTaskStatus($task_model, Task::EXECUTE_SUCCESS, 'no account info');
            exit('no account need to get data');
        }

        //遍历账号信息拉取每个账号的交易信息
        foreach ($refund_account_info as $key => $value) 
        {   
        	//获取请求拉取交易信息的接口要传的参数
        	$params_search = $this->getTransactionSearchConfigParams($value, $task_model);

            //请求拉取交易信息的接口获取数据
//            list ($result, $message) = VHelper::ebTransactionSearch($params_search);
            $count = 0;
            do{
                list ($result, $message) = VHelper::ebTransactionSearch($params_search);

                $count++;
            }while($result === false && $count<self::GET_DETAIL_COUNT );
            //获取数据失败
            if ($result === false) {
            	$result_task_fail = $this->saveTaskStatus($task_model, Task::EXECUTE_FAILED, $message);
                exit($message);
            }

            //在指定日期内没有交易信息则跳过此账号
            if (empty($result->PaymentTransactions)) {
                continue;
            }
            
            //遍历获取到的交易信息然后去拉取交易详情信息
            foreach ($result->PaymentTransactions as $detail_info) {
                $isExisted = Transactions::isExisted($detail_info->TransactionID);
                if($isExisted) continue;
                // 只有交易类型为支付和退款时查询交易详情
                if ($detail_info->Type == 'Payment' || $detail_info->Type == 'Refund') {

                    //获取请求拉取交易详情信息的接口要传的参数
                    $params_detail = $this->getTransactionDetailConfigParams($value, $detail_info->TransactionID);

                    $count = 0;
                    do{
                        list ($result, $message) = VHelper::ebTransactionDeail($params_detail);
//                        var_dump($count);
                        $count++;
                    }while($result === false && $count<self::GET_DETAIL_COUNT );

                    if($result === false)
                    {
//                        var_dump('Get Detail false Finally!');
                        list($result, $message) = Transactions::insertPartTransactionData($detail_info);
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
        $end_time = mktime(date('H',$end),date('i',$end),date('s',$end),date('m',$end),date('d',$end),date('Y',$end));
        $params['endDate'] = date("Y-m-d\TH:i:sO", $end_time);
        
        $start = strtotime($task_model->start_date_time);
        $start_time = mktime(date('H',$start),date('i',$start),date('s',$start),date('m',$start),date('d',$start),date('Y',$start));
        $params['startDate'] = date("Y-m-d\TH:i:sO", $start_time);

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
?>