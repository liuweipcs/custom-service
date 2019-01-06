<?php
/**
 * @desc 客服系统退款计划任务
 */

namespace app\commands;

use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\services\modules\cdiscount\components\cdiscountApi;
use app\modules\services\modules\walmart\models\Refund;
use app\modules\systems\models\ErpProductApi;
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
use app\modules\aftersales\models\AfterSalesProduct;
use app\modules\aftersales\models\SkuQualityDetail;

class RefundController extends Controller
{
    /**
     * @desc 获取亚马逊的退款请求的退款结果的计划任务入口
     * @param int $limit 每次拉取多少条退款请求进行获取退款结果默认一次性获取500条
     */
    public function actionGetfeedsubmissionresult($limit = 500)
    {
        try {
            //拉取退款请求列表
            $feed_submission_list = FeedSubmission::getList($limit);
            //没有需要获取结果的请求列表
            if (empty($feed_submission_list)) {
                exit('no list need deal');
            }

            //遍历所有的请求进行获取退款结果请求
            foreach ($feed_submission_list as $key => $feed_submission_model) {
                //如果请求处理状态是已被平台处理则进行获取退款结果并且更新售后退款单的退款状态
                if ($feed_submission_model->after_sale_id != 'AS1804070698') {//临时处理过滤这个订单
                    $refund_model = AfterSalesRefund::find()->where(['after_sale_id' => $feed_submission_model->after_sale_id])->one();
                    if(empty($refund_model)){
                        continue;
                    }
                    if ($refund_model->refund_status == AfterSalesRefund::REFUND_STATUS_FINISH) {
                        //更新请求的处理状态
                        $feed_submission_model->feed_processing_status = FeedSubmission::STATUS__DONE_;
                        $feed_submission_model->save();
                        continue;
                    }

                    //调用amazon获取退款请求处理状态的接口
                    $result_feed_submission = $this->getRequestResult($feed_submission_model);
                    //调用接口失败
                    if (empty($result_feed_submission) || empty($result_feed_submission['list'])) {
                        continue;
                    }

                    $result_feed_submission_list = current($result_feed_submission['list']);

                    if ($result_feed_submission_list['FeedProcessingStatus'] != FeedSubmission::STATUS__DONE_) {
                        continue;//如果请求处理状态不是已经被平台处理的请求则跳过
//                    exit();//如果请求处理状态不是已经被平台处理的请求则到此终止计划任务的执行
                    }

                    //获取退款结果
                    list ($feed_submission_result, $result_message) = $this->getFeedSubmissionResultRequest($feed_submission_model);
                    $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;

                    if ($feed_submission_result) {
                        $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                        $refund_model->refund_time   = date('Y-m-d H:i:s');//退款时间
                    }

                    if ($refund_model->refund_status == AfterSalesRefund::REFUND_STATUS_FAIL) {
                        $refund_model->fail_count  = $refund_model->fail_count + 1;
                        $refund_model->fail_reason = $result_message;
                    }

                    //修改售后退款单的退款结果
                    if ($refund_model->save()) {
                        //更新请求的处理状态
                        $feed_submission_model->feed_processing_status = $result_feed_submission_list['FeedProcessingStatus'];
                        $feed_submission_model->save();
                    }
                }
            }

        } catch (\Exception $e) {
            echo 'Task Run Failed, ' . $e->getMessage();
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
        $response   = $submission->setAccountId($feed_submission_model->old_account_id)
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

        $xml = $submission->setAccountId($feed_submission_model->old_account_id)
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
            return [true, 'refund success'];
        }

        //退款失败
        $message = $sxe->xpath('Message/ProcessingReport/Result');
        $message = current($message);
        $message = current($message->ResultDescription);

        return [false, $message];
    }

    /**
     * 对售后退款单进行退款操作的计划任务接口
     * @param string $platform_code 平台code
     * @param int $limit 每次拉取多少退款单,默认500个退款单
     */
    public function actionRefund($platform_code, $limit = 500)
    {
        if (!empty($platform_code)) {
            //从售后退款单中拉取需要退款的申请（退款完成和退款中状态的申请不再拉取）
            $list = AfterSalesRefund::getListByPlatformCode($platform_code, $limit);
            foreach ($list as $key => $refund_model) {
                try {
                     echo $refund_model->after_sale_id . '<br/>';
                    // 由于审核通过之后，审核的状态和退款状态可以修改。所以在退款开始之前必须再次确认审核以及退款状态
                    $query = AfterSalesOrder::find();
                    //$after_model = AfterSalesOrder::find()
                    $after_model = $query->select('t.*,t1.refund_status,t1.refund_amount,t1.currency')
                        ->from(AfterSalesOrder::tableName() . ' as t')
                        ->innerJoin(AfterSalesRefund::tableName() . ' as t1', 't1.after_sale_id = t.after_sale_id')
                        ->where(['t.after_sale_id' => $refund_model->after_sale_id])->one();
                    //                      echo $refund_model->after_sale_id.'<br/>';
                    //                    echo $query->createCommand()->getRawSql();die;//输出sql语句


                    if (empty($after_model))
                        continue;

                    if ($after_model->status != AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED || !in_array($after_model->refund_status, array(AfterSalesRefund::REFUND_STATUS_WAIT, AfterSalesRefund::REFUND_STATUS_FAIL)))
                        continue;

                    //通过订单号和平台code获取交易号和erp系统的old_account_id以及平台sellingId
                    list ($transaction_id, $old_account_id, $item_id, $platform_order_id) = Order::getTransactionId(
                        $refund_model->platform_code,
                        $refund_model->order_id
                    );

                    if ($refund_model->platform_code != Platform::PLATFORM_CODE_AMAZON)
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

                    //这里是wish和ebay平台修改退款结果amzaon另开计划任务去修改退款结果数据
                    if ($refund_model->platform_code != Platform::PLATFORM_CODE_AMAZON) {
                        //退款成功状态
                        if ($result) {
                            $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                            $refund_model->refund_time   = date('Y-m-d H:i:s');//退款时间
                            $refund_model->fail_reason   = '';
                            $afterSalesProductModel = AfterSalesProduct::find()->where(['after_sale_id' => $refund_model->after_sale_id])->all();
                            if(!empty($afterSalesProductModel)){
                                foreach ($afterSalesProductModel as $productModel){
                                    //保存退款损失金额
                                    $data = AfterSalesOrder::getRefundRedirectData($productModel->platform_code, $productModel->order_id, $productModel->sku, $after_model->refund_amount, $after_model->currency, 1);
                                    $productModel->refund_redirect_price = $data['sku_refund_amt'];
                                    $productModel->refund_redirect_price_rmb = $data['sku_refund_amt_rmb'];
                                    $productModel->save();
                                    $reason_arr = [73,74];
                                    if (in_array($productModel->reason_id,$reason_arr)) {
                                        //更新sku_qlty_detail loss_rmb finish_time字段
                                        $skuQualityDetail = SkuQualityDetail::find()->where(['after_sale_id' => $productModel->after_sale_id,'sku'=>$productModel->sku])->one();
                                        if (!empty($skuQualityDetail)) {
                                            $skuQualityDetail->loss_rmb = $data['sku_refund_amt_rmb'];
                                            $skuQualityDetail->finish_time = date('Y-m-d H:i:s'); //退款完成时间
                                            $skuQualityDetail->save();
                                        }
                                    }

                                }
                            }

                        } else {
                            //退款失败 则更新失败的次数以及失败原因
                            $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                            $refund_model->fail_count    = $refund_model->fail_count + 1;
                            $refund_model->fail_reason   = $message;
                        }

                        //更新退款结果
                        $refund_model->save();
                    }

                } catch (\Exception $e) {
                    echo 'Task Run Failed, ' . $e->getMessage();
                    echo $e->getFile() . '<br>';
                    echo $e->getLine();
                }
            }

        }
}

    /**
     * 对walmart售后退款单进行退款操作的计划任务接口
     * @param string $platform_code 平台code
     * @param int $limit 每次拉取多少退款单,默认500个退款单
     */
    public function actionRefundwalmart($limit = 500)
    {
        $platform_code = Platform::PLATFORM_CODE_WALMART;

        try {
            //从售后退款单中拉取需要退款的申请（退款完成和退款中状态的申请不再拉取）
            $list = AfterSalesRefund::getListByPlatformCode($platform_code, $limit);
            foreach ($list as $key => $refund_model) {
                // 由于审核通过之后，审核的状态和退款状态可以修改。所以在退款开始之前必须再次确认审核以及退款状态
                $query = AfterSalesOrder::find();
                //$after_model = AfterSalesOrder::find()
                $after_model = $query->select('t.*,t1.refund_status')
                    ->from(AfterSalesOrder::tableName() . ' as t')
                    ->innerJoin(AfterSalesRefund::tableName() . ' as t1', 't1.after_sale_id = t.after_sale_id')
                    ->where(['t.after_sale_id' => $refund_model->after_sale_id])->one();
                //echo $query->createCommand()->getRawSql();die;//输出sql语句
                if (empty($after_model))
                    continue;

                if ($after_model->status != AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED || !in_array($after_model->refund_status, array(AfterSalesRefund::REFUND_STATUS_WAIT, AfterSalesRefund::REFUND_STATUS_FAIL)))
                    continue;

                $accountId         = $after_model->account_id;
                $platform_order_id = $refund_model->platform_order_id;
                if (empty($accountId) || empty($platform_order_id)) {
                    $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                    $refund_model->fail_reason   = '缺少帐号或者平台订单数据';
                    $refund_model->fail_count    = $refund_model->fail_count + 1;
                    $refund_model->save();
                    continue;
                }
                $accountName = Account::getAccountName($accountId, $platform_code);
                if (empty($accountName)) {
                    $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                    $refund_model->fail_reason   = '客服系统未找到帐号数据，请先添加相应帐号';
                    $refund_model->fail_count    = $refund_model->fail_count + 1;
                    $refund_model->save();
                    continue;
                }

                $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_ING;
                //修改退款状态为退款中
                if (!$refund_model->save()) {
                    continue;
                }

                //根据平台code调用各个平台的退款接口

                list ($result, $message) = $this->RefundWalmart($refund_model, $accountName);

                $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;

                //退款成功状态
                if ($result) {
                    $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                }


                if ($refund_model->refund_status == AfterSalesRefund::REFUND_STATUS_FAIL) {
                    $refund_model->fail_count  = $refund_model->fail_count + 1;
                    $refund_model->fail_reason = $message;
                }
                //更新退款结果
                $refund_model->save();

            }
        } catch (\Exception $e) {
            echo 'Task Run Failed, ' . $e->getMessage();
            echo $e->getFile() . '<br>';
            echo $e->getLine();
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
    protected function getRefundResult($refund_model, $transaction_id, $old_account_id, $item_id, $platform_order_id, $order_id = '')
    {
        list ($result, $message) = [false, 'unknow error'];

        //amazon退款入口
        if ($refund_model->platform_code == Platform::PLATFORM_CODE_AMAZON) {
            $result  = $this->refundAmazon($refund_model, $old_account_id, $item_id, $platform_order_id, $order_id);
            $message = 'operate success';
        }

        //EB退款入口
        if ($refund_model->platform_code == Platform::PLATFORM_CODE_EB) {
            list ($result, $message) = $this->refundEb($refund_model, $transaction_id, $item_id);
        }

        //wish退款入口
        if ($refund_model->platform_code == Platform::PLATFORM_CODE_WISH) {
            list ($result, $message) = $this->refundWish($refund_model, $old_account_id, $platform_order_id);
        }

        //沃尔玛退款入口  add by allen <2018-06-23>
        if ($refund_model->platform_code == Platform::PLATFORM_CODE_WALMART) {
            list ($result, $message) = $this->refundWalmart($refund_model);
        }

        //cd退款入口
        if ($refund_model->platform_code == Platform::PLATFORM_CODE_CDISCOUNT) {
            list ($result, $message) = $this->refundCdiscount($refund_model);
        }

        return [$result, $message];
    }

    /**
     * @desc amazon 退款接口入口
     * @param object $refund_model 售后退款记录对象
     * @param string $item_id 平台sellingId
     */
    protected function refundAmazon($refund_model, $old_account_id, $item_id, $platform_order_id, $order_id = '')
    {
        //参数缺失
        if (empty($refund_model) || empty($item_id) || empty($old_account_id) || empty($platform_order_id)) {
            return false;
        }
        //通过erp系统的old_account_id对应客服系统的账户信息
        $account_model = Account::find()->where(['old_account_id' => $old_account_id, 'platform_code' => 'AMAZON'])->one();
        //没有找到对应的账户信息
        if (empty($account_model)) {
            return false;
        }
        //构造请求amazon退款申请的接口请求参数,目前暂定位全部退款和部分退款都通过本金类型类退款
        $req['orderid']     = $platform_order_id;
        $req['currency']    = $refund_model->currency;
        $req['itemid']      = $item_id;
        $req['action_type'] = 'Refund';
        $req['slitemid']    = ''; //itemid和slitemid不能同时为空
        $req['reason']      = $refund_model->reason_code;
        $req['co']          = json_decode($refund_model->refund_detail, true);
//        $req['co'][]    = array(
//             'type'     => 'Principal', //暂时全部通过本金类型类退款
//             'amount'   => $refund_model->refund_amount,
//             'currency' => $refund_model->currency,
//        );
        //实例化封装的amazon接口请求类,并且获取请求结果
        $feed   = new SubmitFeedRequest();
        $resp   = $feed->setAccountId($account_model->old_account_id)
            ->setServiceUrl()
            ->setConfig()
            ->setFeedType('_POST_PAYMENT_ADJUSTMENT_DATA_')
            ->setBusinessType(SubmitFeedRequest::REFUND)
            ->setReqArrList(array($req))
            ->setRequest()
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

        $feed_submission_data = [
            'after_sale_id'          => $refund_model->after_sale_id,
            'account_name'           => $account_model->account_name,
            'old_account_id'         => $account_model->old_account_id,
            'request_id'             => $result['RequestId'],
            'feed_type'              => $result['FeedType'],
            'submitted_date'         => $result['SubmittedDate'],
            'feed_submission_id'     => $result['FeedSubmissionId'],
            'feed_processing_status' => $result['FeedProcessingStatus'],
        ];

        //将请求的数据保存进FeedSubmission用于开启计划任务去获取amazon的最终退款接口
        $feed_submission_model = new FeedSubmission();
        $load_result           = $feed_submission_model->load($feed_submission_data, '');
        $save_result           = $feed_submission_model->save();
        if ($save_result && $save_result) {
            $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_ING;
            $refund_model->save();
        }
        return $load_result && $save_result;
    }

    /**
     * @desc EBAY退票入口
     * @param object $refund_model 售后退款记录对象
     * @param string $transaction_id 订单交易号
     * @param string $accountId 老的erp系统的account_id
     */
    protected function refundEb($refund_model, $transaction_id, $item_id = '')
    {
        //参数缺失
        if (empty($refund_model) || empty($transaction_id)) {
            return [false, 'Required parameter missing'];
        }

        //通过从eb拉下来的交易信息表中找到用来提款的退票账户信息
        $transactions_model = Transactions::find()->where(['transaction_id' => $transaction_id])->one();

        //没有绑定退款账号
        if (empty($transactions_model)) {
//            $receiver_business = Order::getTransactionInfo($transaction_id);
            $receiverModel = new ErpProductApi();
            $result        = $receiverModel->getProductPaypal(['itemId' => $item_id]);

            if ($result->ack == true && !empty($result->datas)) {
                $receiver_business = $result->datas;
            } else {
                $receiver_business = '';
            }

            if (empty($receiver_business))
                return [false, 'No transactions info'];
        } else
            $receiver_business = $transactions_model->receiver_email;

        $refund_account_model = RefundAccount::find()->where(['email' => $receiver_business])->one();

        //没有找到退票账号信息
        if (empty($refund_account_model)) {
            return [false, 'No find account info'];
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
        if (empty($refund_model)) {
            return [false, '退款信息缺失'];
        }

        if (empty($refund_model->account_id)) {
            return [false, '账号ID缺失'];
        }
        //通过erp系统的old_account_id对应客服系统的账户信息
        $account_model = Account::find()->where(['id' => $refund_model->account_id])->one();

        //没有找到客服系统对应的账户信息
        if (empty($account_model->account_name)) {
            return [false, '客服系统未设置对应的账号'];
        }



        //订单号或者退款原因code为空
        if (empty($refund_model->reason_code)) {
            return [false, 'Reason code is empty'];
        }

        //实例化wish各种请求接口的实例并且请求退款接口
        $api    = new WishApi($account_model->user_token);
        $result = $api->refund(['id' => $platform_order_id, 'reason_code' => $refund_model->reason_code]);

        //返回退款结果
        return $result;
    }

    /**
     * @desc walmart 退款接口入口
     * @param object $refund_model 售后退款记录对象
     * @param string $old_account_id 老的erp系统的account_id
     * @param string $platform_order_id 平台订单号
     */
    protected function refundWalmart($refund_model)
    {
        //参数缺失
        if (empty($refund_model)) {
            return [false, '退款信息缺失'];
        }

        if (empty($refund_model->account_id)) {
            return [false, '账号ID缺失'];
        }
        //通过erp系统的old_account_id对应客服系统的账户信息
        $account_model = Account::find()->where(['id' => $refund_model->account_id])->one();

        //没有找到客服系统对应的账户信息
        if (empty($account_model->account_name)) {
            return [false, '客服系统未设置对应的账号'];
        }

        $refund       = new Refund($account_model->account_name);
        $refund_model = json_decode($refund_model->refund_detail, true);
        $result       = $refund->handleResponse($refund_model);
        return $result;
    }

    /**
     * cd退款入口
     * @param $refund_model 售后退款记录对象
     * @param $platform_order_id 平台订单ID
     */
    protected function refundCdiscount($refund_model)
    {
        if (empty($refund_model)) {
            return [false, '退款信息为空'];
        }
        if (empty($refund_model->account_id)) {
            return [false, '账号ID为空'];
        }
        $account = Account::findOne($refund_model->account_id);
        if (empty($account)) {
            return [false, '未找到账号信息'];
        }
        if (empty($refund_model->platform_order_id)) {
            return [false, '平台订单ID为空'];
        }
        if ($refund_model->refund_status == AfterSalesRefund::REFUND_STATUS_FINISH) {
            return [false, '已经退款完成的,不能再次退款'];
        }
        try {
            $cdapi = new cdiscountApi($account->refresh_token);
            $result = $cdapi->CreateRefund($refund_model->platform_order_id, $refund_model->refund_amount);
            if (empty($result)) {
                return [false, '接口返回错误'];
            }

            $result = $result['CreateRefundVoucherResponse']['CreateRefundVoucherResult'];
            if (empty($result['CommercialGestureList'])) {
                return [false, json_encode($result['ErrorMessage'])];
            }
            $result = $result['CommercialGestureList']['RefundInformationMessage'];
            if (!empty($result['OperationSuccess']['_']) && $result['OperationSuccess']['_'] == 'true') {
                return [true, '退款成功'];
            } else {
                return [false, json_encode($result['ErrorMessage'])];
            }

        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }
    }

    public function actionRefundcc()
    {

        try {
            $after_sale_id = 'AS1712130007';
            $refund_model  = AfterSalesRefund::findOne(['after_sale_id' => $after_sale_id]);


            //通过订单号和平台code获取交易号和erp系统的old_account_id以及平台sellingId
            list ($transaction_id, $old_account_id, $item_id, $platform_order_id) = Order::getTransactionId(
                $refund_model->platform_code,
                $refund_model->order_id
            );

            $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_ING;

            //根据平台code调用各个平台的退款接口
            list ($result, $message) = $this->getRefundResult($refund_model, $transaction_id, $old_account_id,
                $item_id,
                $platform_order_id,
                $refund_model->order_id
            );

            $refund_model->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;

            //退款成功状态
            if ($result) {
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

        } catch (\Exception $e) {
            echo $e->getFile();
            echo $e->getLine();
            echo 'Task Run Failed, ' . $e->getMessage();
        }
    }


}
