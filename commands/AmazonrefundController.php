<?php
/**
 * @desc 客服系统退款计划任务
 */
namespace app\commands;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\systems\models\AmazonRefundLog;
use yii\console\Controller;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\orders\models\Order;
use app\modules\accounts\models\Platform;
use app\common\VHelper;
use app\modules\systems\models\AccountRefundaccountRelation;
use app\modules\accounts\models\Account;
use app\modules\systems\models\RefundAccount;
use app\modules\systems\models\FeedSubmission;
use app\modules\systems\models\Transactions;
use wish\components\WishApi;
use app\modules\services\modules\amazon\components\SubmitFeedRequest;
use app\modules\services\modules\amazon\components\GetFeedSubmissionResultRequest;
use app\modules\services\modules\amazon\components\GetFeedSubmissionListRequest;

class AmazonrefundController extends Controller
{
    /**
     * @desc 获取亚马逊的退款请求的退款结果的计划任务入口
     * @param int $limit 每次拉取多少条退款请求进行获取退款结果默认一次性获取500条
     */
    public function actionGetfeedsubmissionresult($feed_submission_id = '',$limit = 100)
    {
        if(empyt($feed_submission_id))
        {
            $feed_submission_list = FeedSubmission::getList($limit);
            if(!empty($feed_submission_list))
            {
                foreach ($feed_submission_list as $feed_submission_model)
                {
                    if(AccountTaskQueue::find()->where(['feed_submission_id'=>$feed_submission_model->id,'type'=>AccountTaskQueue::AMAZON_REFUND_RESULT,'platform_code'=>Platform::PLATFORM_CODE_AMAZON])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $feed_submission_model->id;
                    $accountTaskQenue->type = AccountTaskQueue::AMAZON_REFUND_RESULT;
                    $accountTaskQenue->platform_code = Platform::PLATFORM_CODE_AMAZON;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_AMAZON,'type'=>AccountTaskQueue::AMAZON_REFUND_RESULT],10);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    $cmd = '/usr/local/php/bin/php /mnt/data/www/crm/yii amazonrefund/getfeedsubmissionresult '.$accountId;
                    exec($cmd .' > /dev/null &');
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
        else
        {
            try
            {
                //拉取退款请求列表
//            $feed_submission_list = FeedSubmission::getList($limit);
                $feed_submission_list = AmazonRefundLog::find()->where(['feed_submission_id'=>$feed_submission_id])->all();

                //没有需要获取结果的请求列表
                if (empty($feed_submission_list)) {
                    exit('no list need deal');
                }

                //遍历所有的请求进行获取退款结果请求
                foreach ($feed_submission_list as $key => $feed_submission_model)
                {
                    //调用amazon获取退款请求处理状态的接口
                    $result_feed_submission = $this->getRequestResult($feed_submission_model);

                    //调用接口失败
                    if (empty($result_feed_submission) || empty($result_feed_submission['list'])) {
                        continue;
                    }

                    $result_feed_submission_list = current($result_feed_submission['list']);

                    if ($result_feed_submission_list['FeedProcessingStatus'] != FeedSubmission::STATUS__DONE_) {
                        $feed_submission_model->feed_processing_status = $result_feed_submission_list['FeedProcessingStatus'];
                        $feed_submission_model->save();
                        continue; //如果请求处理状态不是已经被平台处理的请求则跳过
//                    exit(); //如果请求处理状态不是已经被平台处理的请求则到此终止计划任务的执行
                    }

                    //如果请求处理状态是已被平台处理则进行获取退款结果并且更新售后退款单的退款状态
//                $refund_model = AfterSalesRefund::find()->where(['after_sale_id' => $feed_submission_model->after_sale_id])->one();

                    //获取退款结果
                    list ($feed_submission_result, $result_message, $error_array) = $this->getFeedSubmissionResultRequest($feed_submission_model);

                    $transaction = AfterSalesRefund::getDb()->beginTransaction();
                    $flag = true;
                    if($feed_submission_result)
                    {
                        $xml = simplexml_load_string($feed_submission_model->xml);
                        $messages = $xml->xpath('Message');
                        foreach($messages as $message)
                        {
                            $message_id = $message->MessageID->__toString();
                            $order_id = $message->OrderAdjustment->AmazonOrderID->__toString();
                            var_dump($message);
                            if(!in_array($message_id,$error_array))
                            {
                                $refund_model = AfterSalesRefund::find()
                                    ->select('t.*')
                                    ->from(AfterSalesRefund::tableName().' as t')
                                    ->innerJoin(AfterSalesOrder::tableName().' as t1','t1.after_sale_id = t.after_sale_id')
                                    ->where('t1.transaction_id = :transaction_id AND t.refund_status <> :refund_status',array(':transaction_id'=>$order_id,':refund_status'=>AfterSalesRefund::REFUND_STATUS_FINISH))->one();
                                if($refund_model)
                                {
                                    $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                                    $flag = $refund_model->save();
                                }

                            }
                            else
                            {
                                $refund_model = AfterSalesRefund::find()
                                    ->select('t.*')
                                    ->from(AfterSalesRefund::tableName().' as t')
                                    ->innerJoin(AfterSalesOrder::tableName().' as t1','t1.after_sale_id = t.after_sale_id')
                                    ->where('t1.transaction_id = :transaction_id AND t.refund_status <> :refund_status',array(':transaction_id'=>$order_id,':refund_status'=>AfterSalesRefund::REFUND_STATUS_FINISH))->one();
                                if($refund_model)
                                {
                                    $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                                    $refund_model->fail_count = $refund_model->fail_count + 1;
                                    $flag = $refund_model->save();
                                }

                            }
                        }

                    }
                    else
                    {
                        // 将所有退款全部改为失败
                        $xml = simplexml_load_string($feed_submission_model->xml);
                        $messages = $xml->xpath('Message');
                        foreach($messages as $message)
                        {
                            $order_id = $message->OrderAdjustment->AmazonOrderID->__toString();
                            $refund_model = AfterSalesRefund::find()
                                ->select('t.*')
                                ->from(AfterSalesRefund::tableName().' as t')
                                ->innerJoin(AfterSalesOrder::tableName().' as t1','t1.after_sale_id = t.after_sale_id')
                                ->where('t1.transaction_id = :transaction_id AND t.refund_status <> :refund_status',array(':transaction_id'=>$order_id,':refund_status'=>AfterSalesRefund::REFUND_STATUS_FINISH))->one();
                            if($refund_model)
                            {
                                $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                                $refund_model->fail_count = $refund_model->fail_count + 1;
                                $flag = $refund_model->save();
                            }

                        }

                    }

                    //更新请求的处理状态
                    if($flag)
                    {
                        $feed_submission_model->feed_processing_status = $result_feed_submission_list['FeedProcessingStatus'];
                        $feed_submission_model->save();
                        $transaction->commit();
                        exit('success');
                    }
                    else
                    {
                        $transaction->rollback();
                        exit('false');
                    }

                }

            } catch (\Exception $e){
                echo 'Task Run Failed, ' . $e->getMessage();
            }
            $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_AMAZON,
                AccountTaskQueue::AMAZON_REFUND_RESULT);
            if (!empty($accountTask))
            {
                //在队列里面删除该记录
                $accountId = $accountTask->account_id;
                $accountTask->delete();
                $cmd = '/usr/local/php/bin/php /mnt/data/www/crm/yii amazonrefund/getfeedsubmissionresult '.$accountId;
                exec($cmd .' > /dev/null &');
            }
        }

    }
    /**
     * @desc 调用amazon获取退款请求处理状态的接口
     * @param object $feed_submission_model 退款请求模型对象
     */
    protected function getRequestResult($feed_submission_model)
    {
        //参数缺失
        if (empty($feed_submission_model)) {
            return false; 
        }

        $feed_submission_status = FeedSubmission::getFeedProcessingStatus();

        $submission = new GetFeedSubmissionListRequest();
        $response = $submission->setAccountName($feed_submission_model->account_name)
                  ->setServiceUrl()
                  ->setConfig()
                  ->setStatus($feed_submission_status)
                  ->setSubmissionId(array($feed_submission_model->feed_submission_id))
                  ->setOneRequest()
                  ->setType('webservice')
                  ->setService()
                  ->sendHttpRequest()
                  ->getResponse();

        $result = $submission->parseResponse($response);

        return $result;
    }
    /**
     * @desc 获取amazon退款结果的接口
     * @param object $feed_submission_model 退款请求模型对象
     */
    protected function getFeedSubmissionResultRequest($feed_submission_model)
    {
        $submission = new GetFeedSubmissionResultRequest();

        $xml = $submission->setAccountName($feed_submission_model->account_name)
             ->setServiceUrl()
             ->setConfig()
             ->setSubmissionId($feed_submission_model->feed_submission_id)
             ->setRequest()
             ->setType('webservice')
             ->setService()
             ->sendHttpRequest()
             ->getXmlResult();

         $sxe    = simplexml_load_string($xml);
         $result = $sxe->xpath('Message/ProcessingReport/ProcessingSummary');
         $result = current($result);

         //退款成功
         if ($result->MessagesSuccessful > 0) {
             $error_result = $sxe->xpath('Message/ProcessingReport/Result');
             $error_array = array();

             foreach($error_result as $error_info)
             {
                $error_array[] = $error_info->MessageID->__toString();
             }
            return [true, 'refund success',$error_array];
         }
         else
         {
             return [false,'refund with error',[]];
         }
    }
    /**
     * 对售后退款单进行退款操作的计划任务接口
     * @param string $platform_code 平台code
     * @param int $limit 每次拉取多少退款单,默认500个退款单
     */
    public function actionRefund($platform_code, $limit = 500)
    {
        if (!empty($platform_code)) 
        {
            try
            {   
                //从售后退款单中拉取需要退款的申请（退款完成和退款中状态的申请不再拉取）
                $list = AfterSalesRefund::getListByPlatformCode($platform_code, $limit);

                foreach ($list as $key => $refund_model) 
                {
                    //通过订单号和平台code获取交易号和erp系统的old_account_id以及平台sellingId
                    list ($transaction_id, $old_account_id, $item_id, $platform_order_id) = Order::getTransactionId(
                          $refund_model->platform_code,
                          $refund_model->order_id
                    );
                    if($platform_code != Platform::PLATFORM_CODE_AMAZON)
                        $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_ING;
                    //修改退款状态为退款中
                    if (!$refund_model->save()) {
                        continue;
                    }
                       
                    //根据平台code调用各个平台的退款接口
                    list ($result, $message) = $this->getRefundResult($refund_model, $transaction_id, $old_account_id, 
                          $item_id,
                          $platform_order_id,
                        $refund_model->order_id
                    );

                    if($platform_code != Platform::PLATFORM_CODE_AMAZON)
                        $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;

                    //退款成功状态
                    if ($result && $platform_code != Platform::PLATFORM_CODE_AMAZON) {
                        $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                    }

                    //这里是wish和ebay平台修改退款结果amzaon另开计划任务去修改退款结果数据
                    if ($refund_model->platform_code != Platform::PLATFORM_CODE_AMAZON) {
                        //如果退款失败则更新失败的次数以及失败原因
                        if ($refund_model->refund_status == AfterSalesRefund::REFUND_STATUS_FAIL) {
                            $refund_model->fail_count  = $refund_model->fail_count + 1;
                            $refund_model->fail_reason = $message;
                        }
                        //更新退款结果
                        $refund_model->save();
                    }
                }
            }catch (\Exception $e){
                echo 'Task Run Failed, ' . $e->getMessage();
                echo $e->getFile().'<br>';
                echo $e->getLine();
            }
        }
    }
    /**
     * @desc 获取调用各个平台的退款接口的结果
     * @param object $refund_model 售后退款记录对象
     * @param string $transaction_id 交易号
     * @param string $old_account_id erp系统的old_account_id
     * @param string $item_id 平台sellingId
     * @param string $platform_order_id 平台订单号
     */
    protected function getRefundResult($refund_model, $transaction_id, $old_account_id, $item_id, $platform_order_id,$order_id = '')
    {
        list ($result, $message) = [false, 'unknow error'];

        //amazon退款入口
        if ($refund_model->platform_code == Platform::PLATFORM_CODE_AMAZON) {
            $result = $this->refundAmazon($refund_model, $old_account_id, $item_id, $platform_order_id,$order_id);
            $message = 'operate success';
        }

        //EB退款入口
        if ($refund_model->platform_code == Platform::PLATFORM_CODE_EB) {
            list ($result, $message) = $this->refundEb($refund_model, $transaction_id);
        }
        
        //wish退款入口
        if ($refund_model->platform_code == Platform::PLATFORM_CODE_WISH) {
            list ($result, $message) = $this->refundWish($refund_model, $old_account_id, $platform_order_id);
        }

        return [$result, $message];
    }
    /**
     * @desc amazon 退款接口入口
     * @param object $refund_model 售后退款记录对象
     * @param string $item_id 平台sellingId
     */
    protected function refundAmazon($account_id, $list, &$logModel)
    {
        $req = array();
        $transaction = AfterSalesRefund::getDb()->beginTransaction();

        foreach($list as $key => $refund_model)
        {
            if(empty($refund_model->transaction_id))
            {
                list ($transaction_id, $old_account_id, $item_id, $platform_order_id) = Order::getTransactionId(
                    Platform::PLATFORM_CODE_AMAZON,
                    $refund_model->order_id
                );
                $refund_model->transaction_id = $platform_order_id;
            }
            else
            {
                $platform_order_id = $refund_model->transaction_id;
            }

            $req[$key]['orderid'] = $platform_order_id;
            $req[$key]['currency'] = $refund_model->currency;
            $req[$key]['action_type'] = 'Refund';
            $req[$key]['slitemid'] = '';
            $req[$key]['reason'] = $refund_model->reason_code;
            $req[$key]['co'] = json_decode($refund_model->refund_detail,true);

            $refund_model->refund_status = 2;
            $refund_model->save();
        }

        //通过erp系统的old_account_id对应客服系统的账户信息
        $account_model = Account::find()->where(['id' => $account_id,'platform_code' => Platform::PLATFORM_CODE_AMAZON])->one();
//没有找到对应的账户信息
        if (empty($account_model)) {
            $logModel->error = 'account_id:'.$account_id.' info not find';
            $logModel->save();
            exit('account_id:'.$account_id.' info not find');
        }

        //实例化封装的amazon接口请求类,并且获取请求结果
        $feed = new SubmitFeedRequest();
        $resp = $feed->setAccountName($account_model->account_name)
            ->setServiceUrl()
            ->setConfig()
            ->setFeedType('_POST_PAYMENT_ADJUSTMENT_DATA_')
            ->setBusinessType(SubmitFeedRequest::REFUND)
            ->setReqArrList($req)
            ->setRequest($is_array=1,$logModel)
            ->setType('webservice')
            ->setService()
            ->sendHttpRequest()
            ->getResponse();

        $result = $feed->parseResponse($resp);
        /*$path = \Yii::getAlias('@runtime').'/amazon_refund_result.log';
        file_put_contents($path,' result:'.json_encode($result),FILE_APPEND);*/
        //代码或者其他意外造成请求失败
        if (empty($result)) {
            return false;
        }

        $log_model_data = [
            'account_name'           => $account_model->account_name,
            'request_id'             => $result['RequestId'],
            'feed_type'              => $result['FeedType'],
            'submitted_date'         => $result['SubmittedDate'],
            'feed_submission_id'     => $result['FeedSubmissionId'],
            'feed_processing_status' => $result['FeedProcessingStatus'],
        ];

        $load_result = $logModel->load($log_model_data,'');
        $save_result = $logModel->save();
        if($load_result && $save_result)
            $transaction->commit();
        else
            $transaction->rollback();

        return $load_result && $save_result;
    }
    /** 
     * @desc EBAY退票入口 
     * @param object $refund_model 售后退款记录对象
     * @param string $transaction_id 订单交易号
     * @param string $accountId 老的erp系统的account_id
     */
    protected function refundEb($refund_model, $transaction_id) 
    {   
        //参数缺失
        if (empty($refund_model) || empty($transaction_id)) {
            return [false, 'Required parameter missing'];
        }

        //通过从eb拉下来的交易信息表中找到用来提款的退票账户信息
        $transactions_model = Transactions::find()->where(['transaction_id' => $transaction_id])->one();

        //没有绑定退款账号
        if (empty($transactions_model)) {
            $receiver_business = Order::getTransactionInfo($transaction_id);

            if(empty($receiver_business))
                return [false,'No transactions info'];
        }
        else
            $receiver_business = $transactions_model->receiver_business;

        $refund_account_model = RefundAccount::find()->where(['email' => $receiver_business])->one();

        //没有找到退票账号信息
        if (empty($refund_account_model)) {
            return [false,'No find account info'];
        }

        //构造ebay退票接口的请求配置参数
        $params['refund_config'] = [
            'acct1.UserName'  => $refund_account_model->api_username,
            'acct1.Password'  => $refund_account_model->api_password,
            'acct1.Signature' => $refund_account_model->api_signature,
        ];

        $params['transaction_id'] = $transaction_id;
        $params['refund_amount']  = $refund_model->refund_amount;
        $params['currency_code']  = $refund_model->currency;
        $params['refund_type']    = "Partial"; //部分退款
       
        //全部退款
        if ($refund_model->refund_type == AfterSalesRefund::REFUND_TYPE_FULL) {
            $params['refund_type'] = "Full";
        }
        
        //调用ebay封装的退票接口
        $result = VHelper::ebayRefund($params);
        //list ($result, $message) = VHelper::ebayRefund($params);

        //返回退票结果
        return $result;
    }
    /**
     * @desc wish退款接口入口
     * @param object $refund_model 售后退款记录对象
     * @param string $accountId 老的erp系统的account_id
     * @param string $platform_order_id 平台订单号
     */
    protected function refundWish($refund_model, $old_account_id, $platform_order_id)
    {   
        //参数缺失
        if (empty($refund_model) || empty($old_account_id) || empty($platform_order_id)) {
            return [false, 'Required parameter missing'];
        } 

        //通过erp系统的old_account_id对应客服系统的账户信息
        $account_model = Account::find()->where(['old_account_id'=>$old_account_id])->one();

        //没有找到客服系统对应的账户信息
        if (empty($account_model)) {
            return [false,'No find account info'];
        }
        
        //通过客服系统的账户信息去老的erp系统拿wish的access_token
        $erp_account = Account::getAccountFromErp(Platform::PLATFORM_CODE_WISH, $account_model->account_name);
        
        //获取erp系统账户信息失败
        if (empty($erp_account) || empty($erp_account->access_token)) {
            return [false, 'Get access token fail'];
        }
        
        //订单号或者退款原因code为空
        if (empty($refund_model->reason_code)) {
            return [false, 'Reason code is empty'];
        }

        //实例化wish各种请求接口的实例并且请求退款接口
        $api = new WishApi($erp_account->access_token);
        $result = $api->refund(['id' => $platform_order_id, 'reason_code' => $refund_model->reason_code]);

        //返回退款结果
        return $result;
    }

    public function actionRefundamazon($account_id = '', $platform_code='AMAZON', $limit = 100)
    {
        if(empty($account_id))
        {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_AMAZON,Account::STATUS_VALID);
            if(!empty($accountList))
            {
                foreach ($accountList as $account)
                {
                    if(AccountTaskQueue::find()->where(['account_id'=>$account->id,'type'=>AccountTaskQueue::AMAZON_REFUND,'platform_code'=>Platform::PLATFORM_CODE_AMAZON])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::AMAZON_REFUND;
                    $accountTaskQenue->platform_code = Platform::PLATFORM_CODE_AMAZON;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code'=>Platform::PLATFORM_CODE_AMAZON,'type'=>AccountTaskQueue::AMAZON_REFUND],10);
            if (!empty($taskList))
            {
                foreach ($taskList as $accountId)
                {
                    $cmd = '/usr/local/php/bin/php /mnt/data/www/crm/yii amazonrefund/refundamazon '.$accountId;
                    exec($cmd .' > /dev/null &');
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
        else
        {
            try
            {
                //从售后退款单中拉取需要退款的申请（退款完成和退款中状态的申请不再拉取）
                $list = AfterSalesRefund::getAmazonList($platform_code, $limit, $account_id);
//                $array = array('AS1801230132','AS1801230133');
//                $list = AfterSalesRefund::find()->where(['in','after_sale_id',$array])->all();

                if(!empty($list))
                {
                    $logModel = new AmazonRefundLog();

                    //amazon退款入口
                    $result = $this->refundAmazon($account_id, $list, $logModel);
                    $message = 'operate success';
//                    echo $message;
//                    exit($message);
                }
                else
                {
                    $message = 'no datas';
//                    echo $message;
                }
                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_AMAZON,
                    AccountTaskQueue::AMAZON_REFUND);
                if (!empty($accountTask))
                {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    $cmd = '/usr/local/php/bin/php /mnt/data/www/crm/yii amazonrefund/refundamazon '.$accountId;
                    exec($cmd .' > /dev/null &');
                }
                exit($message);

            }catch (\Exception $e){
                echo 'Task Run Failed, ' . $e->getMessage();
                echo $e->getFile().'<br>';
                echo $e->getLine();
            }
        }
    }
}