<?php

namespace app\modules\services\modules\order\controllers;

use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonReviewMessageData;
use app\modules\orders\models\OderEbayDetail;
use Yii;
use yii\db\Query;
use app\common\VHelper;
use yii\web\Controller;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\MailOutbox;
use app\modules\mails\models\MailTemplate;
use app\modules\mails\models\MailTemplateStrReplacement;
use app\modules\mails\models\Reply;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderReplyQuery;
use app\modules\systems\models\Rule;
use app\modules\systems\models\RuleCondtion;
use app\modules\users\models\User;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\BasicConfig;
use app\modules\aftersales\models\RefundReason;
use app\modules\orders\models\OrderKefu;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\aftersales\models\AmazonFbaReturnInfo;
use app\modules\aftersales\models\AmazonFbaReturnAnalysis;
use app\modules\aftersales\models\AmazonFbaReturnDetail;
use app\modules\aftersales\models\AmazonFbaReturns;
use app\modules\mails\models\AccountTaskQueue;

class OrderController extends Controller {

    /**
     * @desc 获取订单信息，根据规则确定是否需要发送信息
     */
    public function actionGetorders() {
        // 接收订单信息
        $order_infos = file_get_contents("php://input");
        // $path = Yii::getAlias('@runtime').'/getorders.log';
        // @file_put_contents($path,$order_infos.'SUCCESS_COMPLETE\r\n',FILE_APPEND);
        // $order_infos = '[{"info":{"order_id":"AM180319007678","platform_order_id":"408-0383768-6865169","account_id":"102","log_id":"102443","order_status":"Unshipped","email":"blc47sxcyvc5gcd@marketplace.amazon.es","buyer_id":"Mario guerrero orejula","timestamp":"2018-03-19 16:51:07","created_time":"2018-03-19 15:42:26","last_update_time":"2018-03-19 08:12:28","paytime":"2018-03-19 15:42:26","ship_name":"Sindia narvaez flores","ship_street1":"Pintor fernandez briones 4A","ship_street2":"","ship_zip":"41400","ship_city_name":"Ecija","ship_stateorprovince":"Sevilla","ship_country":"ES","ship_country_name":"Spain","ship_phone":"686953227","print_remark":"","ship_cost":"0.00","subtotal_price":"8.93","total_price":"8.93","currency":"EUR","final_value_fee":"1.34","package_nums":"0","repeat_nums":"0","payment_status":"1","ship_status":"0","refund_status":"0","ship_code":"ESZXG","complete_status":"20","opration_id":"","opration_date":"2018-03-21 18:54:57","service_remark":"","is_lock":"0","abnormal":"0","abnormal_causes":"0","order_check_status":"1","is_multi_warehouse":"0","insurance_amount":null,"is_check":"0","amazon_fulfill_channel":"MFN","escrowFee":null,"warehouse_id":"1","order_profit_rate":"37.45","calculate_profit_flag":"1","parent_order_id":"0","order_type":"1","buyer_option_logistics":"Std ES Dom_5","is_upload":"1","upload_time":"2018-03-19 18:35:34","company_ship_code":"ESZXG","real_ship_code":"ESZXG","track_number":"YT1807820815600830","priority_satus":"0","is_manual_order":"0","platform_code":"AMAZON","modify_time":"2018-03-19 15:48:18","shipped_date":"2018-03-20 10:25:45"},"product":[{"picking_name":"\u65e0\u4e2d\u6587\u540d\u79f0","linelist_cn_name":"","product_weight":0}]},{"info":{"order_id":"AM180319009426","platform_order_id":"406-4786202-2811560","account_id":"102","log_id":"102521","order_status":"Unshipped","email":"8tbn0bbwj7nwxvv@marketplace.amazon.es","buyer_id":"Victoriano","timestamp":"2018-03-19 20:45:17","created_time":"2018-03-19 19:56:59","last_update_time":"2018-03-19 12:36:15","paytime":"2018-03-19 19:56:59","ship_name":"Victoriano Rodriguez Castro","ship_street1":"C\/ Jose Carbajal, n\u00ba14 Portal 2, 2\u00baizd","ship_street2":"","ship_zip":"02008","ship_city_name":"Albacete","ship_stateorprovince":"Albacete","ship_country":"ES","ship_country_name":"Spain","ship_phone":"606884045","print_remark":"","ship_cost":"0.00","subtotal_price":"19.99","total_price":"19.99","currency":"EUR","final_value_fee":"3.00","package_nums":"0","repeat_nums":"0","payment_status":"1","ship_status":"0","refund_status":"0","ship_code":"ESZXG","complete_status":"20","opration_id":"","opration_date":"2018-03-21 18:55:57","service_remark":"","is_lock":"0","abnormal":"0","abnormal_causes":"0","order_check_status":"1","is_multi_warehouse":"0","insurance_amount":null,"is_check":"0","amazon_fulfill_channel":"MFN","escrowFee":null,"warehouse_id":"1","order_profit_rate":"34.39","calculate_profit_flag":"1","parent_order_id":"0","order_type":"1","buyer_option_logistics":"Std ES Dom_5","is_upload":"1","upload_time":"2018-03-20 15:39:34","company_ship_code":"ESZXG","real_ship_code":"ESZXG","track_number":"YT1807820815600944","priority_satus":"0","is_manual_order":"0","platform_code":"AMAZON","modify_time":"2018-03-19 20:27:11","shipped_date":"2018-03-20 19:42:53"},"product":[{"picking_name":"\u65e0\u4e2d\u6587\u540d\u79f0","linelist_cn_name":"","product_weight":0}]}]';
        // $order_infos = json_decode($order_infos);
        /*         * **** test data******** */
        //$order_infos='{"1":{"info":{"order_id":"AM180815012495","platform_order_id":"026-6091040-1718759","account_id":"419","log_id":"169243","order_status":"Shipped","email":"cvvf8svqcllmb0h@marketplace.amazon.co.uk","buyer_id":"Sneha Pawar","timestamp":"2018-08-16 09:03:05","created_time":"2018-08-15 23:36:40","last_update_time":"2018-08-15 21:38:19","paytime":"2018-08-15 23:36:40","ship_name":"Sneha Pawar","ship_street1":"Apartment 144 1","ship_street2":"William Jessop Way","ship_zip":"L3 1DZ","ship_city_name":"Liverpool","ship_stateorprovince":"","ship_country":"GB","ship_country_name":"United Kingdom","ship_phone":"","print_remark":"","ship_cost":"0.00","subtotal_price":"18.99","total_price":"18.99","currency":"GBP","final_value_fee":"2.85","package_nums":"0","repeat_nums":"0","payment_status":"1","ship_status":"0","refund_status":"0","ship_code":"","complete_status":"20","opration_id":"","opration_date":"2018-08-15 23:48:08","service_remark":"","is_lock":"0","abnormal":"0","abnormal_causes":"","order_check_status":"0","is_multi_warehouse":"0","insurance_amount":null,"is_check":"0","amazon_fulfill_channel":"AFN","escrowFee":null,"warehouse_id":"323","order_profit_rate":null,"calculate_profit_flag":"0","parent_order_id":"0","order_type":"1","buyer_option_logistics":"Expedited","is_upload":"0","upload_time":null,"company_ship_code":null,"real_ship_code":null,"track_number":null,"priority_satus":"0","is_manual_order":"0","purchase_remark":"","platform_code":"AMAZON","modify_time":"2018-08-15 23:48:08","shipped_date":"2018-08-16 05:38:19","detail":{"item_id":["38362169246243"]},"sku":["BG595"]},"product":[{"picking_name":"\u6d74\u5ba4\u7f6e\u7269\u67b6-K","linelist_cn_name":"\u5bb6\u5c45\u65e5\u7528\u54c1","product_weight":"1200.00"}]}}';
        
        /****** test data*********/
//        $order_infos = '{
//    "2":{
//        "info":{
//            "order_id":"AM180822001018",
//            "platform_order_id":"205-1089482-2315566",
//            "account_id":"419",
//            "log_id":"171966",
//            "order_status":"Shipped",
//            "email":"xkj04340y0p73hq@marketplace.amazon.co.uk",
//            "buyer_id":"mej kaur",
//            "timestamp":"2018-08-22 09:51:03",
//            "created_time":"2018-08-22 01:00:26",
//            "last_update_time":"2018-08-22 01:38:14",
//            "paytime":"2018-08-22 01:00:26",
//            "ship_name":"mej kaur",
//            "ship_street1":"3 PARK DRIVE",
//            "ship_street2":"",
//            "ship_zip":"WV4 5AH",
//            "ship_city_name":"WOLVERHAMPTON",
//            "ship_stateorprovince":"W Midlands",
//            "ship_country":"GB",
//            "ship_country_name":"United Kingdom",
//            "ship_phone":"",
//            "print_remark":"",
//            "ship_cost":"0.00",
//            "subtotal_price":"18.99",
//            "total_price":"18.99",
//            "currency":"GBP",
//            "final_value_fee":"2.85",
//            "package_nums":"0",
//            "repeat_nums":"0",
//            "payment_status":"1",
//            "ship_status":"0",
//            "refund_status":"0",
//            "ship_code":"",
//            "complete_status":"20",
//            "opration_id":"",
//            "opration_date":"2018-08-22 01:21:02",
//            "service_remark":"",
//            "is_lock":"0",
//            "abnormal":"0",
//            "abnormal_causes":"",
//            "order_check_status":"0",
//            "is_multi_warehouse":"0",
//            "insurance_amount":null,
//            "is_check":"0",
//            "amazon_fulfill_channel":"AFN",
//            "escrowFee":null,
//            "warehouse_id":"323",
//            "order_profit_rate":null,
//            "calculate_profit_flag":"0",
//            "parent_order_id":"0",
//            "order_type":"1",
//            "buyer_option_logistics":"Expedited",
//            "is_upload":"0",
//            "upload_time":null,
//            "company_ship_code":null,
//            "real_ship_code":null,
//            "track_number":null,
//            "priority_satus":"0",
//            "is_manual_order":"0",
//            "purchase_remark":"",
//            "platform_code":"AMAZON",
//            "modify_time":"2018-08-22 01:21:02",
//            "shipped_date":"2018-08-22 09:38:14",
//            "detail":{
//                "item_id":[
//                    "66794091193307"
//                ]
//            },
//            "sku":[
//                "BG595"
//            ]
//        },
//        "product":[
//            {
//                "picking_name":"浴室置物架-K",
//                "linelist_cn_name":"家居日用品",
//                "product_weight":"1200.00",
//                "asinval":"B07DNWYYSY"
//            }
//        ]
//    }
//}';
        $orders = array("\r\n", "\n", "\r", "\t", "\b", "\f", '\"', "\\");
        $order_infos = str_replace($orders, ' ', $order_infos);
        // $order_infos = str_replace("'","\'",$order_infos);
        $order_infos = str_replace("<", '&lt;', $order_infos);
        $order_infos = str_replace(">", '&gt;', $order_infos);
        $order_infos = json_decode($order_infos);


        //获取平台所有规则
        $rules = Rule::find()
                ->where(['in','status',[Rule::RULE_STATUS_VALID,2], 'type' => Rule::RULE_TYPE_AUTO_ANSWER])
                ->orderBy('priority ASC')
                ->all();
        if (empty($rules)) {
            return null;
        }

        //获取rule_condition表的所有数据
        $rule_condition_data = RuleCondtion::getAllRuleConditionData();
        $execute_infos = include \Yii::getAlias("@app") . '/config/order_rule_exec_condition.php';
        $message = 'SUCCESS';
        $flag = true;

        //循环订单
        foreach ($order_infos as $order_info) {
            //订单信息为空直接跳过
            if (empty($order_info)) {
                continue;
            }

            //订单ID中包含RE直接跳过
            if (strpos($order_info->info->order_id, 'RE') !== false) {
                continue;
            }

            //获取旧的账户ID
            $old_account_id = $order_info->info->account_id;
            //账户ID
            $accont_id = 0;

            //通过平台code与账户ID查找账号信息
            $account_info = Account::find()
                    ->where(['platform_code' => $order_info->info->platform_code, 'old_account_id' => $old_account_id])
                    ->one();

            if (!empty($account_info)) {
                //账号信息不为空，则设置订单中site为账号的site_code
                $accont_id = $account_info->id;
                $order_info->info->account_id = $accont_id;
                $order_info->info->site = $account_info->site_code;
            } else {
                //账号信息为空，并且平台code等于AMAZON则直接输出
                if ($order_info->info->platform_code == Platform::PLATFORM_CODE_AMAZON) {
                    echo json_encode(['code' => 400, 'message' => '订单：' . $order_info->info->order_id . ' 在客服中没有找到帐号信息']);
                }
            }

            //匹配规则结果
            $result = [];
            foreach ($rules as $rule) {
                //比较订单中平台code与规则中平台code，如果不相等直接跳过
                if ($order_info->info->platform_code != $rule->platform_code) {
                    continue;
                }
                
                //add by allen <2018-12-07> str 如果设置的是有效期区间 并且 当前时间小于开始时间 或者 当前时间大于结束时间 则按区间过滤
                $nowTime = time();
                if($rule->status == 2 && ($nowTime < strtotime($rule->survival_str_time) || $nowTime > strtotime($rule->survival_end_time))){
                    continue;
                }
                //add by allen <2018-12-07> end 如果设置的是有效期区间 则按区间过滤
                
                if (!empty($result)) {
                    break;
                }
                //根据规则id获取相对应的条件id
                $conditionData = self::getConditionDataByRuleId($rule['id'], $rule_condition_data);
                foreach ($conditionData as $kc => $vc) {
                    //通过规则id、条件id获取rule_condtion表数据
                    $option_value_data = self::getOptionValeDataByRuleIdAndConditionId($rule['id'], $vc, $rule_condition_data);

                    //根据指定规则id和指定条件id的option_value数据进行匹配标签id
                    self::matchingOptionValue($result, $rule, $option_value_data, $order_info);
                    if (!isset($result[$rule['id']]) || empty($result[$rule['id']])) {
                        unset($result[$rule['id']]);
                        break;
                    }
                }
            }
            if (!empty($result)) {
                reset($result);

                try {
                    if (!empty($result)) {

                        //是否添加订单信息到order_reply_query_list表，默认为是
                        $is_add = true;

                        //$result 多条 $key rule_id $val temple_id
                        foreach ($result as $key => $val) {
                            //获取规则信息
                            $rule_info = Rule::find()->where(['id' => $key])->one();
                            //获取执行信息
                            $execute_info = isset($execute_infos[$rule_info->execute_id]) ? $execute_infos[$rule_info->execute_id] : '';
                            //延时时长
                            $time = 0;
                            //当前时间戳
                            $date = strtotime(date('Y-m-d H:i:s'));

                            //通过订单id和模板id查询
                            $orderReplyQueryModel = "";
                            if ($order_info->info->order_id && $val) {
                                $orderReplyQueryModel = OrderReplyQuery::find()
                                        ->where(['order_id' => $order_info->info->order_id, 'template_id' => $val])
                                        ->one();
                            }

                            //如果已经存在，则不用添加                            
                            if (!empty($orderReplyQueryModel)) {
                                $is_add = false;
                                $message = $order_info->info->order_id . '----' . $val;
                            }

                            //如果三个状态全为空，则可以添加，否则不用添加
                            if ($order_info->info->platform_code == Platform::PLATFORM_CODE_EB) {
                                //评价状态
                                $feedback = EbayFeedback::find()
                                        ->where(['order_line_item_id' => $order_info->info->platform_order_id])
                                        ->all();

                                //纠纷状态
                                $cancel = EbayCancellations::disputeLevel($order_info->info->platform_order_id);
                                $inquiry = EbayInquiry::disputeLevel($order_info->info->platform_order_id);
                                $returns = EbayReturnsRequests::disputeLevel($order_info->info->platform_order_id);

                                $info = json_decode(json_encode($order_info->info), true);
                                //订单明细
                                $info['detail'] = OderEbayDetail::getOrderDetailByOrderId($order_info->info->order_id);

                                //站内信
                                $subject = EbayInboxSubject::isSetEbayInboxSubject($info);

                                $isCancel = VHelper::arrIsVal($cancel);
                                $isInquiry = VHelper::arrIsVal($inquiry);
                                $isReturns = VHelper::arrIsVal($returns);
                                //如果订单有了纠纷，站内信，feedback等信息，则不需要发送模板
                                if (!empty($feedback) || $isCancel || $isInquiry || $isReturns || $subject['bool']) {
                                    $is_add = false;
                                    $message .= '有往来记录';
                                }
                            }

                            if ($is_add == true) {
                                $insert_flag = true;
                                //匹配新规则
                                $new_rule_flag = self::newRule($order_info->info->platform_code, $order_info->info->platform_order_id, $key, $order_info->info->buyer_id, $order_info->info->email);
                                if (!empty($rule_info->condition_by) && $new_rule_flag) {
                                    $insert_flag = false;
                                }

                                if ($insert_flag) {
                                    //匹配到规则 插入回复列表
                                    $orderReplyQueryModel = new OrderReplyQuery();
                                    $orderReplyQueryModel->platform_code = $order_info->info->platform_code;
                                    $orderReplyQueryModel->order_id = $order_info->info->order_id;
                                    $orderReplyQueryModel->is_send = 0;
                                    $orderReplyQueryModel->account_id = $accont_id;
                                    $orderReplyQueryModel->template_id = $val;
                                    $orderReplyQueryModel->rule_id = $key;
                                    $orderReplyQueryModel->order_create_time = $order_info->info->created_time;
                                    $orderReplyQueryModel->order_pay_time = $order_info->info->paytime;
                                    $orderReplyQueryModel->order_ship_time = $order_info->info->shipped_date;
                                    $orderReplyQueryModel->execute_id = $rule_info->execute_id;
                                    $orderReplyQueryModel->platform_order_id = $order_info->info->platform_order_id;
                                    $orderReplyQueryModel->create_time = date('Y-m-d H:i:s');

                                    //update by allen <2018-12-07> 新加邮件发送时间节点 str
                                    
                                    if ($execute_info) {
                                        $time_type = $execute_info['time_type'];
                                        if($rule_info->is_timed == 2){
                                            $date = strtotime(date('Y-m-d',strtotime($orderReplyQueryModel->$time_type)));
                                        }else{
                                            $date = strtotime($orderReplyQueryModel->$time_type);
                                        }
                                        $time = 3600 * 24 * $rule_info->execute_day + 3600 * $rule_info->execute_hour;
                                    }
                                    $replyDate = date('Y-m-d H:i:s', $date + $time);
                                    $orderReplyQueryModel->reply_date = $replyDate;
                                    //update by allen <2018-12-07> 新加邮件发送时间节点 end
                                    if (!$orderReplyQueryModel->save()) {
                                        $flag = FALSE;
                                        $message .= VHelper::errorToString($orderReplyQueryModel->getErrors());
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $flag = false;
                    $message = $e->getMessage();
                }
            }
        }

        if ($flag) {
            echo json_encode(['code' => 200, 'message' => $message]);
        } else {
            echo json_encode(['code' => 400, 'message' => $message]);
        }
    }

    /**
     * 判断是否过滤
     * @param $platform_code
     * @param $platform_order_id
     * @param $rule_id
     * @param $buyer_id
     * @param $receive_email
     * @return bool 为true 过滤
     */
    public static function newRule($platform_code, $platform_order_id, $rule_id, $buyer_id, $receive_email) {

        /* $rule_id = 185;
          $platform_code = Platform::PLATFORM_CODE_AMAZON;
          $platform_order_id = '408-0383768-6865169';
          $buyer_id = 'Erwin Bernhard';
          $receive_email = 'y6tssd7xp7mz0th@marketplace.amazon.de'; */
        //查询{{%rule}}
        $rule = Rule::findById($rule_id);
        $json_rule = !empty($rule->condition_by) ? $rule->condition_by : '';
        if (empty($json_rule)) {
            return false;
        }
        $condition_new = json_decode($json_rule);
        $flag = false;
        $buyer_message = '';
        switch ($platform_code) {
            case Platform::PLATFORM_CODE_EB:
                $cancel = EbayCancellations::disputeLevel($platform_order_id);
                $inquiry = EbayInquiry::disputeLevel($platform_order_id);
                $returns = EbayReturnsRequests::disputeLevel($platform_order_id);
                // feedback 中差评
                if (in_array('feedback_negative', $condition_new)) {
                    $ebay_feedback = EbayFeedback::find()
                            ->select(['feedback_id'])
                            ->where(['order_line_item_id' => $platform_order_id, 'role' => 1])
                            ->andWhere(['in', 'comment_type', [1, 2, 3]])
                            ->limit(1)
                            ->asArray()
                            ->one();
                    if (!empty($ebay_feedback)) {
                        $flag = true;
                    }
                }
                if (!$flag && in_array('feedback_positive', $condition_new)) {
                    //feedback 好评
                    $ebay_feedback = EbayFeedback::find()
                            ->select(['feedback_id'])
                            ->where(['order_line_item_id' => $platform_order_id, 'role' => 1])
                            ->andWhere(['in', 'comment_type', [4, 5]])
                            ->limit(1)
                            ->asArray()
                            ->one();
                    if (!empty($ebay_feedback)) {
                        $flag = true;
                    }
                }
                if (!$flag && in_array('dispute', $condition_new)) {
                    //dispute 中差评
                    if (!empty($cancel) || !empty($inquiry) || !empty($returns)) {
                        $flag = true;
                    }
                }

                if (!$flag && strpos($json_rule, 'buyer_message') !== false) {
                    foreach ($condition_new as $v) {
                        if (strpos($v, 'buyer_message') !== false) {
                            $buyer_message = $v;
                        }
                    }
                    $date_message = intval(explode('&', $buyer_message)[1]);
                    //buyer id
                    $eb_buyer_id = MailOutbox::find()
                            ->select(['id'])
                            ->from('{{%mail_outbox}}')
                            ->where(['buyer_id' => $buyer_id, 'platform_code' => 'EB'])
                            ->andWhere(['between', 'send_time', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', time() + $date_message * 86400)])
                            ->limit(1)
                            ->asArray()
                            ->one();
                    if (!empty($eb_buyer_id)) {
                        $flag = true;
                    }
                }
                break;

            case Platform::PLATFORM_CODE_AMAZON:
                //amazon
                if (in_array('feedback_negative', $condition_new)) {
                    $query = AmazonFeedBack::find();
                    $amazon_feedback = $query
                            ->select(['id'])
                            ->where(['order_id' => $platform_order_id])
                            ->andWhere(['in', 'rating', [1, 2, 3]])
                            ->limit(1)
                            ->asArray()
                            ->one();
//                    echo $query->createCommand()->getRawSql();die;
                    if (!empty($amazon_feedback)) {
                        $flag = true;
                    }
                }

                if (!$flag && in_array('feedback_positive', $condition_new)) {
                    $amazon_feedback_query = AmazonFeedBack::find();
                    $amazon_feedback = $amazon_feedback_query
                            ->select(['id'])
                            ->where(['order_id' => $platform_order_id])
                            ->andWhere(['in', 'rating', [4, 5]])
                            ->limit(1)
                            ->asArray()
                            ->one();
                    if (!empty($amazon_feedback)) {
                        $flag = true;
                    }
                }
                if (!$flag && in_array('review_negative', $condition_new)) {
                    $amazon_revicew = AmazonReviewMessageData::find()
                            ->alias('m')
                            ->select(['r.star'])
                            ->leftJoin('{{%amazon_review_data}} r', 'm.custId=r.customerId ')
                            ->where(['m.orderId' => $platform_order_id])
                            ->andWhere(['in', 'r.star', [1, 2, 3]])
                            ->limit(1)
                            ->asArray()
                            ->one();

                    if (!empty($amazon_revicew)) {
                        $flag = true;
                    }
                }
                if (!$flag && in_array('review_positive', $condition_new)) {
                    $amazon_revicew_query = AmazonReviewMessageData::find();
                    $amazon_revicew = $amazon_revicew_query
                            ->alias('m')
                            ->select(['r.star'])
                            ->leftJoin('{{%amazon_review_data}} r', 'm.custId=r.customerId ')
                            ->where(['m.orderId' => $platform_order_id])
                            ->andWhere(['in', 'r.star', [4, 5]])
                            ->limit(1)
                            ->asArray()
                            ->one();
                    if (!empty($amazon_revicew)) {
                        $flag = true;
                    }
                }

                if (!$flag && strpos($json_rule, 'buyer_message') !== false) {
                    foreach ($condition_new as $v) {
                        if (strpos($v, 'buyer_message') !== false) {
                            $buyer_message = $v;
                        }
                    }
                    $date_message = intval(explode('&', $buyer_message)[1]);
                    //查询receive_email
                    $amazon_receive_email_query = MailOutbox::find();
                    $amazon_receive_email = $amazon_receive_email_query
                            ->select(['id'])
                            ->from('{{%mail_outbox}}')
                            ->where(['receive_email' => $receive_email, 'platform_code' => 'AMAZON'])
                            ->andWhere(['between', 'send_time', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', time() + $date_message * 86400)])
                            ->limit(1)
                            ->asArray()
                            ->one();
//                    echo $amazon_receive_email_query->createCommand()->getRawSql();die;
                    if (!empty($amazon_receive_email)) {
                        $flag = true;
                    }
                }
                break;
        }
        return $flag;
    }

    /**
     * 根据规则id获取该规则下不重复的条件id数据
     * @param  int $rule_id 条件id
     * @param  array $rule_condition_data 一次性查出来的所有的规则条件表的数据
     * @return array
     */
    protected static function getConditionDataByRuleId($rule_id, $rule_condition_data) {
        $result = [];
        //返回指定规则id下的条件id
        foreach ($rule_condition_data as $key => $value) {
            if ($value['rule_id'] == $rule_id) {
                $result[] = $value['condtion_id'];
            }
        }

        //去重复后返回指定规则id对应的条件id
        return array_unique($result);
    }

    /**
     * 获取指定规则id和条件id下所有供匹配的option_value数据
     * @param  int $rule_id 规则id
     * @param  int $condition_id 条件id
     * @param  array $rule_condition_data 一次性查出来的所有的规则条件表的数据
     * @return array
     */
    protected static function getOptionValeDataByRuleIdAndConditionId($rule_id, $condition_id, $rule_condition_data) {
        $result = [];

        //获取指定规则id和指定条件下的option_value数据
        foreach ($rule_condition_data as $key => $value) {
            if ($value['rule_id'] == $rule_id && $value['condtion_id'] == $condition_id) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * 根据指定规则id和指定条件id的option_value数据进行匹配标签id
     * @param array $result 由已经匹配上的标签id组成的数组
     * @param array $rule_data 包含标签id的规则数据                条件id、标签id
     * @param array $option_value_data 指定的option_value_data    条件数据
     * @param object $order_model 包含跟订单有关的所有数据的对象
     * @param object $inbox_model 消息模型对象
     */
    protected static function matchingOptionValue(&$result, $rule_data, $option_value_data, $order_model) {
        //将option_value_data数据指向第一个元素
        $info = current($option_value_data);
        //指定规则和指定条件的操作符是一样的所以获取option_value_data的第一条记录代表该规则指定条件的所有的操作符
        $oprerator = $info['oprerator']; //规则优先级
        //获取要拿来跟规则数据进行匹配的字段值,有可能有多个(模型多条记录的值或者多个字段的值)
        $match_value = self::getMatchValue($option_value_data, $order_model);

//        echo '<pre>';
//        var_dump($option_value_data,$order_model,$match_value);
//        echo '</pre>';
//        echo "<br/>--------------------------<br/>";
        //对拿到的消息相关的值进行匹配,多个就循环匹配只要匹配上了就返回标签id或者模板id
        if (!empty($match_value)) {
            foreach ($match_value as $kwg => $vluematch) {
                self::match($result, $rule_data, $option_value_data, $vluematch, $oprerator);
                foreach ($result as $v) {
                    if ($v)
                        return;
                }
            }
        }
    }

    /**
     * 获取要拿来跟规则数据进行匹配的字段值,有可能有多个(模型多条记录的值或者多个字段的值)
     * @param array $option_value_data 指定规则id制定条件id下的option_value_data
     * @param object $order_model 包含跟订单有关的所有数据的对象
     * @param object $inbox_model 消息模型对象
     */
    protected static function getMatchValue($option_value_data, $order_model) {
        //指定规则下的指定条件的拿来匹配的condition_key是相同的
        $info = current($option_value_data);
        $condition_key_info = explode('.', $info['condition_key']);
        //取用来匹配的字段名称的模型名称
        //取出inbox.title.content中的inbox也就是模型标示
        $object_field = current($condition_key_info);
        //var_dump($object_field);exit;
        //匹配多个字段的情况就是inbox.title.content这种情况获取要匹配的字段组成数组
        //匹配单个字段的情况就是inbox.title这种情况获取要匹配的字段组成数组
        $fields_info = self::getMatchFieldsInfo($condition_key_info);

        //如果没有要匹配的字段则跳过该规则下指定条件的匹配
        //用来获取匹配的字段的模型要么是消息模型要么是消息模型关联的订单相关模型
        $match_value_model = $order_model->$object_field;
        //没有相对应的模型对象跳过该规则下指定条件的匹配
        $mantchValue = []; //组装用来匹配的值
        //需要获取值的模型是一个数组即有多条记录
        $match_value_model_tmp = [];
        if (is_object($match_value_model))
            $match_value_model_tmp[] = $match_value_model;
        else if (is_array($match_value_model))
            $match_value_model_tmp = $match_value_model;
        $attributeNumber = sizeof($fields_info);
        //return $mantchValue;
        if ($attributeNumber > 3)
            $attributeNumber = 3;
        //var_dump($match_value_model_tmp);
        foreach ($match_value_model_tmp as $km => $v_model) {
            if ($attributeNumber > 1)
            {
                $tmpModel = $v_model;
                for ($i=0;$i<$attributeNumber;$i++)
                {
                    if (!isset($tmpModel->$fields_info[$i])) break;
                    $tmpModel = $tmpModel->$fields_info[$i];
                }
                if (is_array($tmpModel))
                {
                    foreach ($tmpModel as $v)
                        $mantchValue[] = $v;
                }
            }
            else
            {
                if (isset($v_model->$fields_info[0]))
                    $mantchValue[] = $v_model->$fields_info[0];
            }
        }

/*         if (is_array($match_value_model)) {
            foreach ($match_value_model as $km => $v_model) {
                foreach ($fields_info as $kfied => $vfied) {
                    if (isset($v_model->$vfied)) {
                        $mantchValue[] = $v_model->$vfied;
                    }
                }
            }
        } */
        //需要获取的模型只有单个即只有单条记录
/*         if (is_object($match_value_model)) {
            foreach ($fields_info as $k_field => $v_field) {
                if (isset($match_value_model->$v_field)) {
                    $mantchValue[] = $match_value_model->$v_field;
                }
            }
        } */
        //返回需要匹配的值
        return $mantchValue;
    }

    protected static function getMatchFieldsInfo($condition_key_info) {
        $result = [];
        //匹配多个字段的情况就是inbox.title.content这种情况获取要匹配的字段组成数组
        //现在的需求是只可能是inbox模型上有多个字段
        array_shift($condition_key_info);
        foreach ($condition_key_info as $khh => $vhh) {
            $result[] = $vhh;
        }
        return $result;
    }

    /**
     * 进行匹配操作
     * @param array $result 由已经匹配上的标签id组成的数组
     * @param array $rule_data 包含标签id的规则数据
     * @param array $option_value_data 指定的option_value_data
     * @param string $vluematch 要进行匹配的值
     * @param string $oprerator 操作符(1代表大于2代表小于，3代表等于，4代表包含，5代表不包含,6代表范围（大于等于并且小于等于)
     */
    protected static function match(&$result, $rule_data, $option_value_data, $vluematch, $oprerator) {
        $ruleId = $rule_data['id'];
        //对操作符为大于的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_DAYU) {
            $option_value_data = current($option_value_data);
            if (!($vluematch > $option_value_data['option_value'])) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为小于的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_XIAOYU) {
            $option_value_data = current($option_value_data);
            if (!($vluematch < $option_value_data['option_value'])) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为等于的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_DENGYU) {
            $option_value_data = current($option_value_data);
            if (!($vluematch == $option_value_data['option_value'])) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为包含的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_BAOHAN) {
            $option_value = [];
            foreach ($option_value_data as $key => $value) {
                $option_value[] = $value['option_value'];
            }
            $flag = false;
            foreach ($option_value as $value) {
                if (strpos($vluematch, $value) !== false) {
                    $flag = true;
                    break;
                }
            }
            if (!$flag) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        // 对操作符为全等包含的情况进行匹配（目前针对帐号id，int类型数据）
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_BAOHANIN) {
            $option_value = [];
            foreach ($option_value_data as $key => $value) {
                $option_value[] = $value['option_value'];
            }

            $flag = false;
            foreach ($option_value as $value) {
                if ($vluematch == $value) {
                    $flag = true;
                    break;
                }
            }

            if (!$flag) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为不包含的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_BUBAOHAN) {
            $option_value = [];
            foreach ($option_value_data as $key => $value) {
                $option_value[] = $value['option_value'];
            }
            $flag = true;
            foreach ($option_value as $value) {
                if (strpos($vluematch, $value) !== false) {
                    $flag = false;
                    break;
                }
            }
            if (!$flag) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }

        //对操作符为范围的情况进行匹配
        if ($oprerator == RuleCondtion::RULE_CONDITION_OPRERATOR_RANGE) {
            $option_value = [];
            foreach ($option_value_data as $key => $value) {
                $option_value[] = $value['option_value'];
            }

            //要进行匹配的开始范围和结束范围
            $rang_value = self::getRangeValue($option_value);

            //对范围进行匹配
            if (!($vluematch > $rang_value['start_value'] && $vluematch <= $rang_value['end_value'])) {
                $result[$ruleId] = [];
                return;
                //$result[] = $rule_data['relation_id'];
            }
        }
        $result[$ruleId] = $rule_data['relation_id'];
    }

    /**
     * http://kefu.yibainetwork.com/services/order/order/sendmessage?platform_code=EB
     * @param type $platform_code
     * @param type $limit
     * 发件信息从等待发送列表 根据规则转移到发件箱[亚马逊]
     */
    public function actionSendmessage($platform_code, $limit = 100, $isdebug = false) {
        $now_time = date('Y-m-d H:i:s');
        $query = OrderReplyQuery::find();
        $query->where(['platform_code' => $platform_code]);
//        if($platform_code == 'AMAZON'){
//            $query->andWhere(['>=', 'order_ship_time', '2018-04-17 23:59:59']);
//        }
        if ($isdebug) {
            $query->andWhere(['order_id' => 'EB180724003263']);
        } else {
            $query->andWhere(['in', 'is_send', [OrderReplyQuery::IS_NOT_SEND, OrderReplyQuery::SEND_FAIL]]);
            $query->andWhere(['<=', 'reply_date', $now_time]);
            $query->andWhere(['<=', 'fail_count', OrderReplyQuery::MAX_FAIL_COUNT]);
        }
        $models = $query->limit($limit)->all();
//        $models = $query->andWhere(['<=', 'reply_date', $now_time])
//            ->andWhere(['<=', 'fail_count', OrderReplyQuery::MAX_FAIL_COUNT])
//            ->limit($limit)
//            ->all();
//        echo $query->createCommand()->getRawSql().'<br/>';//输出sql语句
//        die;
        foreach ($models as $model) {
            $transaction = OrderReplyQuery::getDb()->beginTransaction();
            list($result, $errorInfo, $is_send) = $this->SaveInfo($platform_code, $model, $transaction, $isdebug);
            if ($result == false && $transaction->getIsActive()) {
                $transaction->rollBack();
                $model->fail_count = $model->fail_count + 1;
            }
            $model->is_send = $is_send;
            $model->error_info = $errorInfo;
            $model->save();
            if ($result == true)
                $transaction->commit();
        }
        exit('DONE');
    }

    // 匹配模板，保存信息
    public function SaveInfo($platform_code, $model, $transaction = null, $isdebug = false) {
        $order_id = $model->order_id;
        $templateId = $model->template_id;
        $execute_id = $model->execute_id;
        $errorInfo = '';
        $order_info = Order::getOrderStackByOrderId($platform_code, '', $order_id);

        if (empty($order_info)) {
            return [false, '未查询到订单信息', -1];
        }

        if ($platform_code == Platform::PLATFORM_CODE_EB) {
            //评价状态
            $feedback = EbayFeedback::find()
                    ->where(['order_line_item_id' => $order_info->info->platform_order_id])
                    ->all();
            //纠纷状态
            $cancel = EbayCancellations::disputeLevel($order_info->info->platform_order_id);
            $inquiry = EbayInquiry::disputeLevel($order_info->info->platform_order_id);
            $returns = EbayReturnsRequests::disputeLevel($order_info->info->platform_order_id);

            $info = json_decode(json_encode($order_info->info), true);
            //订单明细
            $info['detail'] = OderEbayDetail::getOrderDetailByOrderId($order_id);

            //站内信
            $subject = EbayInboxSubject::isSetEbayInboxSubject($info);

            $isCancel = VHelper::arrIsVal($cancel);
            $isInquiry = VHelper::arrIsVal($inquiry);
            $isReturns = VHelper::arrIsVal($returns);
            //如果订单有了纠纷，站内信，feedback等信息，则不需要发送模板
            if (!empty($feedback) || $isCancel || $isInquiry || $isReturns || $subject['bool']) {
                return [true, '已有往来记录 不需要发送模板', 2];
            }
        }

        // 未付款的过滤订单状态
        if ($model->execute_id == 1) {
            if (in_array($order_info->info->complete_status, array(Order::COMPLETE_STATUS_PARTIAL_SHIP, Order::COMPLETE_STATUS_SHIPPED, Order::COMPLETE_STATUS_HOLD, Order::COMPLETE_STATUS_CANCELED)))
                return [true, '订单状态发生变化，不能发送该模板', 2];
        }

        $execute_infos = include \Yii::getAlias("@app") . '/config/order_rule_exec_condition.php';
        $execute_info = isset($execute_infos[$execute_id]) ? $execute_infos[$execute_id] : '';
        if (!empty($execute_info) && $execute_info['is_checked'] == 1) {
            $field_infos = $execute_info['field'];
            $field_infos = explode('.', $field_infos);
            $model_name = $field_infos[0];
            $field_name = $field_infos[1];
            if ($order_info->$model_name->$field_name != $execute_info['value'])
                return [true, '订单状态发生变化，不能发送该模板', 2];
        }

        $products = isset($order_info->product) || !empty($order_info->product) ? $order_info->product : '';
        if (empty($products))
            return [false, '未查询到订单详情', -1];
        $old_account_id = $order_info->info->account_id;
        $account_info = Account::find()->where(['platform_code' => $platform_code, 'old_account_id' => $old_account_id])->one();
        if (empty($account_info))
            return [false, '未找到账号' . $old_account_id . '信息', -1];
        $item_id = $products[0]->item_id;
        //匹配到了标签下面就是对模板，使用模板进行回复
        /** 1.获取模板内容 * */
        $templateInfo = MailTemplate::findById($templateId);
        if (empty($templateInfo))
            return [false, '未找到模板信息', -1];
        $subject_model = '';
        //催付款模板在已经和客户有往来信息的情况就不会再发送
        if ($platform_code == Platform::PLATFORM_CODE_EB && $model->execute_id == 1) {
            $subject_model = EbayInboxSubject::findOne(['item_id' => $item_id, 'buyer_id' => $order_info->info->buyer_id, 'account_id' => $account_info->id]);
            if ($subject_model)
                return [true, '和客户已经取得联系，不需发送催付款模板', 2];
        }

        $templateTitle = $templateInfo->template_title;
        $templateContent = $templateInfo->template_content;
        /** 2.替换模板占位符 * */
        //$matchClass->order_id = '500193669540552';
        $mailmodel = New MailTemplateStrReplacement();
        $match_arr = $mailmodel->circlematch($templateContent);
        $match_value = $mailmodel->replace_arr_value($match_arr, $platform_code, '', $item_id, $order_info);
        $content = $mailmodel->replace_content_str($match_value, $templateContent);
        $match_arr = $mailmodel->circlematch($templateTitle);
        $match_value = $mailmodel->replace_arr_value($match_arr, $platform_code, '', $item_id, $order_info);
        $title = $mailmodel->replace_content_str($match_value, $templateTitle);
        /** 3.用模板内容回复 * */
        $replyData = [
            'subject' => $title,
            'content' => $content,
            'reply_by' => User::SYSTEM_USER,
            'item_id' => $item_id,
            'sender' => $account_info->account_name,
            'recipient_id' => $order_info->info->buyer_id,
            'account_id' => $account_info->id,
            'platform_order_id' => $order_info->info->platform_order_id,
        ];
        if (empty($transaction))
            $transaction = Reply::getDb()->beginTransaction();
        $reply = Reply::addSelfReply($platform_code, $replyData);
        if (empty($reply)) {
            $transaction->rollBack();
            return [false, '保存到回复表失败', -1];
        }
        /** 4.将回复保存到发件箱* */
        $modelOutBox = new MailOutbox();
        $attributes = [
            'platform_code' => $platform_code,
            'reply_id' => $reply->id,
            'account_id' => $account_info->id,
            'content' => $content,
            'subject' => $title,
            'send_params' => $reply->getSendParams('2', $order_info, $account_info->id),
            'send_status' => MailOutbox::SEND_STATUS_WAITTING,
            'create_by' => 'system',
            'modify_by' => 'system',
            'order_id' => $order_id,
            'platform_order_id' => $model->platform_order_id,
            'rule_id' => $model->rule_id,
            'buyer_id' => $order_info->info->buyer_id,
            'receive_email' => $order_info->info->email,
        ];
        $modelOutBox->setAttributes($attributes);
        try {
            $flag = $modelOutBox->save();
            if (!$flag)
                $errorInfo = VHelper::getModelErrors($modelOutBox);
            else {
                // 回复表id加入到自动回信表
                $model->reply_id = $reply->id;
                $flag = $model->save();
                if (!$flag)
                    $errorInfo = VHelper::getModelErrors($model);
            }
        } catch (\Exception $e) {
            $flag = false;
            $errorInfo = $e->getMessage();
        }
        if (!$flag) {
            $transaction->rollBack();
            return [false, $errorInfo, -1];
        }
        if ($platform_code == Platform::PLATFORM_CODE_EB) {
            if (empty($subject_model))
                $subject_model = EbayInboxSubject::findOne(['item_id' => $item_id, 'buyer_id' => $order_info->info->buyer_id, 'account_id' => $account_info->id]);
            if (!$subject_model) {
                $subject_model = new EbayInboxSubject();
                $subject_model->first_subject = $title;
                $subject_model->is_read = 1;
                $subject_model->is_replied = 1;
            }
            $subject_model->item_id = $item_id;
            $subject_model->buyer_id = $order_info->info->buyer_id;
            $subject_model->account_id = $account_info->id;
            $subject_model->now_subject = $title;
            $subject_model->receive_date = date('Y-m-d H:i:s');

            try {
                $flag = $subject_model->save();
                if (!$flag)
                    $errorInfo = VHelper::getModelErrors($subject_model);
            } catch (Exception $e) {
                $flag = false;
                $errorInfo = $e->getMessage();
            }
        }
        if (!$flag) {
            $transaction->rollBack();
            return [false, $errorInfo, -1];
        }
        return [true, 'SUCCESS', 1];
    }

    /**
     * 根据平台 月份查询对应的退款数据
     * @param type $platform_code
     * @param type $date
     * @return type
     * @author allen <2018-04-18>
     * 测试地址: http://www.customer.com/services/order/order/getrefunddata?platform_code=ALI&date=2017-06
     * 正式地址: http://kefu.yibainetwork.com/services/order/order/getrefunddata?platform_code=EB&date=2018-03
     */
    public function actionGetrefunddata($platform_code, $date) {
        $query = AfterSalesOrder::find();
        $data = $query->select('t.after_sale_id,t.account_id,a.order_id,a.refund_amount,a.currency,t.create_time')
                ->from('{{%after_sales_order}} t')
                ->join('LEFT JOIN', '{{%after_sales_refund}} a', 't.after_sale_id = a.after_sale_id')
                ->where(['t.platform_code' => $platform_code])
                ->andWhere(['like', 'create_time', $date . '%', false])
                ->asArray()
                ->all();
        return json_encode($data);
    }

    /**
     * 获取问题产品信息
     * @param type $date
     * @return type
     * @author allen <2018-04-27>
     * 测试地址: http://www.customer.com/services/order/order/getafterproducts?date=2018-04
     * 正式地址: http://kefu.yibainetwork.com/services/order/order/getafterproducts?date=2018-04
     */
    public function actionGetafterproducts($date) {
        $query = AfterSalesOrder::find();
        $data = $query->select('p.id,t.reason_id,t.platform_code,p.sku,t.create_time')
                ->from('{{%after_sales_order}} t')
                ->join("INNER JOIN", '{{%after_sales_product}} p', 't.after_sale_id = p.after_sale_id')
                ->where(['department_id' => 55, 't.status' => 2])
                ->andWhere(['like', 't.create_time', $date . '%', false])
                ->orderBy('t.create_time')
                ->asArray()
                ->all();
        if (!empty($data)) {
            $reaseonList = BasicConfig::getParentList(55);
            foreach ($data as $key => $value) {
                $data[$key]['reason'] = $reaseonList[$value['reason_id']];
            }
        }
        return json_encode($data);
    }

    /**
     * 获取售后单退款 重寄金额信息
     * @author allen <2018-05-05>
     * 测试地址: http://www.customer.com/services/order/order/getafterproinfo?date=2018-05-05
     * 正式地址: http://kefu.yibainetwork.com/services/order/order/getafterproinfo?date=2018-05-05
     */
    public function actionGetafterproinfo($date = '') {
        if (empty($date)) {
            $date = date("Y-m-d");
        }
        $baseConfigData = BasicConfig::getAllConfigData();
        $refundData = AfterSalesOrder::find()->select('t.after_sale_id,t.order_id,t.department_id,t.reason_id,t.platform_code,f.refund_amount,f.currency,t.create_time')
                ->from('{{%after_sales_order}} t')
                ->join('INNER JOIN', '{{%after_sales_refund}} f', 't.after_sale_id = f.after_sale_id')
                ->where(['t.type' => 1, 't.status' => 2, 'refund_status' => 3])
                ->andWhere(['like', 't.create_time', $date . '%', FALSE])
                ->orderBy('t.platform_code,t.create_time')
                ->asArray()
                ->all();
        if (!empty($refundData)) {
            foreach ($refundData as $key => $value) {
                $refundData[$key]['department'] = $baseConfigData[$value['department_id']];
                $refundData[$key]['reason'] = $baseConfigData[$value['reason_id']];
            }
        }


        $redirectData = AfterSalesOrder::find()->select('t.after_sale_id,t.order_id,d.redirect_order_id,t.department_id,t.reason_id,t.platform_code,d.order_amount,d.currency,t.create_time')
                ->from('{{%after_sales_order}} t')
                ->join('INNER JOIN', '{{%after_sales_redirect}} d', 't.after_sale_id = d.after_sale_id')
                ->where(['t.type' => 3, 't.status' => 2])
                ->andWhere(['like', 't.create_time', $date . '%', FALSE])
                ->orderBy('t.platform_code,t.create_time')
                ->asArray()
                ->all();
        if (!empty($redirectData)) {
            foreach ($redirectData as $k => $val) {
                $redirectData[$k]['department'] = $baseConfigData[$val['department_id']];
                $redirectData[$k]['reason'] = $baseConfigData[$val['reason_id']];
            }
        }
//        echo $query1->createCommand()->getRawSql();die;
        //1 退款单  3重寄单
        $returnData = [1 => $refundData, 3 => $redirectData];
        return json_encode($returnData);
    }

    /**
     * 计算售后单统计数据
     * 测试地址: http://www.customer.com/services/order/order/aftersalesstatistics
     * 正式地址: http://kefu.yibainetwork.com/services/order/order/aftersalesstatistics
     * @author allen <2018-05-23>
     */
    public function actionAftersalesstatistics() {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        set_time_limit(0);
        if (isset($_REQUEST['date'])) {
            $date = $_REQUEST['date'];
            $accountList = Account::getFullAccounts();
            $beginTime = $date . ' 00:00:00';
            $endTime = $date . ' 23:59:59';
            //获取审核通过的重寄单数据 [重寄单数据]
            $query = AfterSalesOrder::find();
            $model = $query->select('t.after_sale_id,t.order_id,t.platform_code,t.department_id,t.reason_id,t.account_id,u.account_name,u.site_code,t.type,t.exchange_rate,'
                            . 't.create_by,t.create_time,r.redirect_order_id,f.order_amount')
                    ->from('{{%after_sales_order}} t')
                    ->join("LEFT JOIN", '{{%after_sales_redirect}} r', 't.after_sale_id = r.after_sale_id')
                    ->join("LEFT JOIN", '{{%after_sales_refund}} f', 't.after_sale_id = f.after_sale_id')
                    ->join("LEFT JOIN", '{{%account}} u', 't.account_id = u.id')
//                    ->where(['t.after_sale_id' => 'AS1805170340'])
                    ->where(['t.status' => 2])
                    ->andWhere(['in', 'type', [1, 3]])
                    ->andWhere(['between', 't.create_time', $beginTime, $endTime])
                    ->andWhere('department_id <> ""')
                    ->asArray()
                    ->all();
//        echo $query->createCommand()->getRawSql();die;
//            echo '<pre>';
//            var_dump($model);
//            echo '</pre>';
//            die;
            if (!empty($model)) {
                foreach ($model as $k => $value) {
                    if (!empty($value['department_id']) && !empty($value['reason_id'])) {
                        $orderInfo = OrderKefu::getOrderInfo($value['platform_code'], $value['order_id']); //订单数据
                        $proCost = OrderKefu::getProCostPrice($value['after_sale_id']); //问题产品成本价
                        $formula_id = RefundReason::getLossCalculationMethod($value['department_id'], $value['reason_id']); //计算方式
                        $model[$k]['formula_id'] = $formula_id;
//                        $model[$k]['account_name'] = isset($value['account_id']) ? $accountList[$value['account_id']] : "未找到账号"; // 账号
                        $amountInfo = OrderKefu::refundAmount($value['type'], $formula_id, $proCost, $value['order_id'], $value['redirect_order_id'], $value['order_amount'], $orderInfo['rate']);
                        $model[$k]['refund_amount'] = $amountInfo['refund_amount']; //重寄和退款按需求表对应的方式计算得到结果
                        $model[$k]['refund_amount_rmb'] = $amountInfo['refund_amount_rmb'];
                        $model[$k]['subtotal'] = $orderInfo['totalPrice'];
                        $model[$k]['subtotal_rmb'] = $orderInfo['totalPriceRmb'];
                        $model[$k]['currency'] = $orderInfo['currency'];
                        $model[$k]['exchange_rate'] = $orderInfo['rate'];
                        $model[$k]['create_by'] = $value['create_by'];
                        $model[$k]['create_time'] = $value['create_time'];
                        $model[$k]['status'] = 1;
                        $model[$k]['pro_cost_rmb'] = $proCost;
                    }
                }

                //循环数组保存处理
                if (!empty($model)) {
                    $sql = "REPLACE INTO {{%after_sale_statistics}} (`after_sale_id`,`platform_code`,`department_id`,`reason_type_id`,`formula_id`,`account_id`,`account_name`,`type`,`refund_amount`,
                    `refund_amount_rmb`,`subtotal`,`subtotal_rmb`,`currency`,`exchange_rate`,`create_by`,`create_time`,`status`,`pro_cost_rmb`,`add_time`,`site_code`) VALUES ";
                    foreach ($model as $key => $val) {
                        $after_sale_id = !empty($val['after_sale_id']) ? $val['after_sale_id'] : '';
                        $platform_code = !empty($val['platform_code']) ? $val['platform_code'] : '';
                        $department_id = !empty($val['department_id']) ? $val['department_id'] : 0;
                        $reason_id = !empty($val['reason_id']) ? $val['reason_id'] : 0;
                        $formula_id = !empty($val['formula_id']) ? $val['formula_id'] : 0;
                        $account_id = !empty($val['account_id']) ? $val['account_id'] : 0;

                        $account_name = !empty($val['account_name']) ? $val['account_name'] : '';
                        $site_code = !empty($val['site_code']) ? $val['site_code'] : '';
                        $type = !empty($val['type']) ? $val['type'] : '';
                        $refund_amount = !empty($val['refund_amount']) ? sprintf("%.4f", $val['refund_amount']) : 0.0000;
                        $refund_amount_rmb = !empty($val['refund_amount_rmb']) ? sprintf("%.4f", $val['refund_amount_rmb']) : 0.0000;
                        $subtotal = !empty($val['subtotal']) ? sprintf("%.4f", $val['subtotal']) : 0.00;
                        $subtotal_rmb = !empty($val['subtotal_rmb']) ? $val['subtotal_rmb'] : 0.00;
                        $currency = !empty($val['currency']) ? $val['currency'] : '';
                        $exchange_rate = !empty($val['exchange_rate']) ? $val['exchange_rate'] : 0.00;

                        $create_by = !empty($val['create_by']) ? $val['create_by'] : '';

                        $create_time = !empty($val['create_time']) ? $val['create_time'] : '';
                        $pro_cost_rmb = !empty($val['pro_cost_rmb']) ? $val['pro_cost_rmb'] : 0.00;

                        $sql .= "('" . $after_sale_id . "','" . $platform_code . "'," . $department_id . "," . $reason_id . "," . $formula_id . "," . $account_id . ",'" . $account_name . "',"
                                . "" . $type . "," . $refund_amount . "," . $refund_amount_rmb . "," . $subtotal . "," . $subtotal_rmb . ",'" . $currency . "'," . $exchange_rate . ",'"
                                . "" . $create_by . "','" . $create_time . "',1," . $pro_cost_rmb . ",'" . date('Y-m-d H:i:s') . "','" . $site_code . "'),";
                    }
                    $sql = rtrim($sql, ',');
//                    echo $sql;die;
                    Yii::$app->db->createCommand($sql)->execute();
                    echo $date . '数据同步完成<br/>';
                }
            }
        } else {
            for ($i = 0; $i <= 30; $i++) {
                //$date = date("Y-m-d", strtotime("-" . $i . " day"));
                $date = date("Y-m-d", (strtotime('2018-06-14') - 3600 * 24 * $i));
                echo $i . '--' . $date . '开始同步' . $date . '的数据.....<br/>';
                VHelper::throwTheader('/services/order/order/aftersalesstatistics', ['date' => $date]);
                sleep(3);
            }
        }
    }

    /**
     *
     * @param type $token
     */
    public function actionToken($token) {
        $token = Yii::$app->request->get('token');
        file_put_contents('tokens.php', $token);
    }

    /**
     * 获取按月统计的已发货订单总销售额数据
     * 测试： http://www.customer.com/services/order/order/runsaledata?platformCode=EB
     * 正式: http://kefu.yibainetwork.com/services/order/order/runsaledata?platformCode=EB
     * @author allen <2018-06-12>
     */
    public function actionRunsaledata($platformCode, $date) {
        $data = OrderKefu::getRunSaleDatas($platformCode, $date);
    }

    /**
     * 获取订单售后单
     * 测试地址: http://www.cus.cn/services/order/order/getorderafterorders?orderId=''&platformCode=''
     * 正式地址: http://kefu.yibainetwork.com/services/order/order/getorderafterorders?orderId=''&platformCode=''
     * @author zhangchu <2018-09-08>
     */
    public function actionGetorderafterorders() {
        $orderId = isset($_REQUEST['orderId']) ? trim($_REQUEST['orderId']) : null;
        $platformCode = isset($_REQUEST['platformCode']) ? trim($_REQUEST['platformCode']) : null;
        $afterSalesOrders = [];
        if (empty($orderId))
            return json_encode(['status' => 0, 'info' => '未获取到订单号！']);
        if (empty($platformCode))
            return json_encode(['status' => 0, 'info' => '未获取到平台号！']);
        $afterSalesOrderInfos = AfterSalesOrder::getByOrderIdCon($platformCode, $orderId);

        return json_encode(['status' => 200, 'info' => $afterSalesOrderInfos]);
    }

    /**
     * 获取eaby退款单信息   
     * @author clark <2018-09-14>
     * 测试地址: http://www.customer.com/services/order/order/getebrefunddata?startDate=2018-09-05&&endDate=2018-09-10
     * 正式地址: http://kefu.yibainetwork.com/services/order/order/getebrefunddata?startDate=2018-09-05&&endDate=2018-09-10
     */
    public function actionGetebrefunddata() {
        $startDate = isset($_REQUEST['startDate']) ? trim($_REQUEST['startDate']) : null;
        $endDate = isset($_REQUEST['endDate']) ? trim($_REQUEST['endDate']) : null;
        if (empty($startDate) || empty($endDate)) {
            return json_encode(['status' => 0, 'info' => '获取时间参数有缺失！']);
        }
        $platformCode = 'EB';
        $refundData = AfterSalesOrder::find()->select('r.order_id, r.platform_code,r.refund_amount, r.currency, r.refund_time')
                ->from('{{%after_sales_order}} o')
                ->leftjoin('{{%after_sales_refund}} r', 'o.after_sale_id = r.after_sale_id')
                ->where(['o.type' => 1, 'o.status' => 2, 'r.refund_status' => 3, 'o.platform_code' => 'EB'])
                ->andWhere(['between', 'r.refund_time', $startDate, $endDate])
                ->orderBy('r.refund_time')
                ->asArray()
                ->all();
        return json_encode(['status' => 200, 'info' => $refundData]);
    }

    /**
     * 测试地址: http://www.customer.com/services/order/order/getrefundcostdata?startDate=2018-09-05&&endDate=2018-09-10
     * 正式地址: http://kefu.yibainetwork.com/services/order/order/getrefundcostdata?startDate=2018-09-05&&endDate=2018-09-10
     * 获取知道时间内容eBay平台的退款单sku成本数据
     * @author allen <2018-10-09>
     */
    public function actionGetrefundcostdata() {

        $startDate = isset($_REQUEST['startDate']) ? trim($_REQUEST['startDate']) : null;
        $endDate = isset($_REQUEST['endDate']) ? trim($_REQUEST['endDate']) : null;
        if (empty($startDate) || empty($endDate)) {
            return json_encode(['status' => 0, 'info' => '获取时间参数有缺失！']);
        }

        $refundData = AfterSalesOrder::find()->select('o.after_sale_id,o.order_id,p.sku,p.refund_redirect_price_rmb,p.quantity,r.refund_time')
                ->from('{{%after_sales_order}} o')
                ->innerJoin('{{%after_sales_product}} p', 'o.after_sale_id = p.after_sale_id')
                ->leftjoin('{{%after_sales_refund}} r', 'o.after_sale_id = r.after_sale_id')
                ->where(['o.type' => 1, 'o.status' => 2, 'r.refund_status' => 3, 'o.platform_code' => 'EB'])
                ->andWhere(['between', 'r.refund_time', $startDate, $endDate])
                ->orderBy('r.refund_time')
                ->asArray()
                ->all();
        return json_encode(['status' => 200, 'info' => $refundData]);
    }

    /* * 
     * 获取平台订单号
     * 测试地址http://www.customer.com/services/order/order/getplatformorderid
     * @author harvin <2018-11-01>
     * ** */

    public function actionGetplatformorderid() {
        set_time_limit(0);
        //获取平台的code
        $refund = AfterSalesRefund::find()->select('*')->andWhere(['platform_order_id' => null])->limit(1000)->all();
        //获取erp系统对应的模板表
        $i = $j = 0;
        foreach ($refund as $v) {
            $platform = OrderKefu::getOrderModel($v['platform_code']);
            $model = OrderKefu::model($platform->ordermain)->where(['order_id' => $v['order_id']])->one();
            if (empty($model)) {
                $model = OrderKefu::model($platform->ordermaincopy)->where(['order_id' => $v['order_id']])->one();
            }
            if (!empty($model)) {
                //$serefund= AfterSalesRefund::findOne(['order_id'=>$v['order_id']]);
                $v->platform_order_id = $model->platform_order_id;
                $v->save();
                $i++;
            } else {
                echo $v['after_sale_id'] . '未找到对应订单数据<Br/>';
                $j++;
            }
        }
        echo "共" . $i . "条数据执行完成<br/>共" . $j . "条数据执行失败";
        die;
    }
    
    
    /** ================================FBA退货相关计划任务================================================== **/
    /**
     * 将api拉取的退货订单数据转成公司sku的形式保存 (过滤了映射后公司sku为空的数据)
     * @param type $beginTime
     * @param type $endTime
     * @author allen <2018-11-17>
     * SELECT t.account_id,t.seller_sku,m.sku FROM `yibai_amazon_all_returns` t
     * LEFT JOIN yibai_amazon_sku_map m ON t.account_id = m.account_id AND t.seller_sku = m.seller_sku
     * WHERE m.sku IS NOT NULL
     */
     public function actionSyncfbareturnorder($beginTime = null,$endTime = null) {
        //避免服务器拉取信息超时
        set_time_limit(0);
        $beginTime = !empty($beginTime) ? $beginTime.' 00:00:00' : '';
        $endTime = !empty($endTime) ? $endTime.' 23:59:59' : '';
        $sql = "SELECT count(t.id) as total ";
        $sql .= "FROM {{%amazon_all_returns}} as t ";
        $sql .= "LEFT JOIN {{%amazon_listing_alls}} AS a ON t.account_id = a.account_id AND t.seller_sku = a.seller_sku ";
        $sql .= "LEFT JOIN {{%amazon_sku_map}} as p ON t.account_id = p.account_id AND t.seller_sku = p.seller_sku ";
        $sql .= "LEFT JOIN {{%product_description}} AS pd ON p.sku = pd.sku AND language_code = 'Chinese' ";
        $sql .= "WHERE t.fulfillment_channel = 'fba' AND p.sku IS NOT NULL ";
        if(!empty($beginTime) && !empty($endTime)){
            $sql .= "AND t.return_date BETWEEN '".$beginTime."' AND '".$endTime."' ";
        }
        $sql .= "ORDER BY t.id desc";
        //echo $sql;die;
        $res = Yii::$app->db_product->createCommand($sql)->queryAll();
        if ($res[0]['total']) {
            $totals = $res[0]['total']; //总记录条数
            $pageSize = 1000; //页大小
            $pages = ceil($totals / $pageSize); //总页数
        } else {
            exit('无数据');
        }
        /**
         * $querySql = "SELECT t.*,a.status as available_sale,p.sku as sku,pd.title as title ";
            $querySql .= "FROM {{%amazon_all_returns}} as t ";
            $querySql .= "LEFT JOIN {{%amazon_listing_alls}} AS a ON t.account_id = a.account_id AND t.seller_sku = a.seller_sku ";
            $querySql .= "LEFT JOIN {{%amazon_sku_map}} AS p ON t.account_id = p.account_id AND t.seller_sku = p.seller_sku ";
            $querySql .= "LEFT JOIN {{%product_description}} AS pd ON p.sku = pd.sku AND language_code = 'Chinese' ";
         */
        //echo '总记录条数:'.$totals.'<br/>总页数:'.$pages;die;
        for ($i = 1; $i <= $pages; $i++) {
            $offset = ($i - 1) * $pageSize;
            $querySql = "";
            $querySql = "SELECT t.*,a.status as available_sale,p.sku as sku,pd.title as title ";
            $querySql .= "FROM {{%amazon_all_returns}} as t ";
            $querySql .= "LEFT JOIN {{%amazon_listing_alls}} AS a ON t.account_id = a.account_id AND t.seller_sku = a.seller_sku ";
            $querySql .= "LEFT JOIN {{%amazon_sku_map}} AS p ON t.account_id = p.account_id AND t.seller_sku = p.seller_sku ";
            $querySql .= "LEFT JOIN {{%product_description}} AS pd ON p.sku = pd.sku AND language_code = 'Chinese' ";
            $querySql .= "WHERE t.fulfillment_channel = 'fba' AND p.sku IS NOT NULL ";
            if(!empty($beginTime) && !empty($endTime)){
                $querySql .= "AND t.return_date BETWEEN '".$beginTime."' AND '".$endTime."' ";
            }
            $querySql .= "ORDER BY t.id desc ";
            $querySql .= "LIMIT ".$offset.",".$pageSize;
            //echo $querySql;die;
            $model = Yii::$app->db_product->createCommand($querySql)->queryAll();
            if (is_array($model) && !empty($model)) {
                $sql = "INSERT INTO {{%amazon_fba_returns}} (`platform_order_id`,`account_id`,`old_account_id`,`seller_sku`,`sku`,`asin`,`return_date`,`qty`,`fulfillment_channel`,`status`,"
                        . "`product_name`,`fulfillment_center_id`,`detailed_disposition`,`reason`,`license_plate_number`,`customer_comments`,`created_at`,`reason_type`,`is_available_sale`,`title`) VALUES ";
                foreach ($model as $value) {
                    $oldAccountId = !empty($value['account_id']) ? trim($value['account_id']) : 0; //erp账号ID
                    $accountInfo = Account::find()->where(['platform_code' => 'AMAZON', 'old_account_id' => $oldAccountId])->one();
                    $accontId = !empty($accountInfo) ? $accountInfo->id : 0; //客服账号ID                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 
                    $platformOrderId = !empty($value['order_id']) ? trim($value['order_id']) : ""; //平台订单号
                    $asin = !empty($value['asin']) ? trim($value['asin']) : ""; //asin码
                    $sellerSku = !empty($value['seller_sku']) ? trim($value['seller_sku']) : ""; //刊登在平台的销售sku
                    $sku = !empty($value['sku']) ? trim($value['sku']) : ""; //公司sku
                    $returnDate = !empty($value['return_date']) ? trim($value['return_date']) : ""; //退货时间
                    $qty = !empty($value['qty']) ? trim($value['qty']) : 0; //退货数量
                    $fulfillmentChannel = !empty($value['fulfillment_channel']) ? trim($value['fulfillment_channel']) : "";
                    $status = !empty($value['status']) ? trim($value['status']) : "";//退货产品状态
                    $productName = !empty($value['product_name']) ? trim($value['product_name']) : "";//平台的名称
                    $fulfillmentCenterId = !empty($value['fulfillment_center_id']) ? trim($value['fulfillment_center_id']) : "";
                    $detailedDisposition = !empty($value['detailed_disposition']) ? trim($value['detailed_disposition']) : "";
                    $reason = !empty($value['reason']) ? trim($value['reason']) : ""; //退货原因
                    $licensePlateNumber = !empty($value['license_plate_number']) ? trim($value['license_plate_number']) : "";
                    $customerComments = !empty($value['customer_comments']) ? trim($value['customer_comments']) : "";
                    $isAvailableSale = !empty($value['available_sale']) ? trim($value['available_sale']) : 0; //是否可售
                    $title = !empty($value['title']) ? trim($value['title']) : ""; //产品中文名
                    $createdAt = date("Y-m-d H:i:s");

                    /**
                     * 原因分类 原来类别:
                     * */
                    switch ($reason) {
                        case 'APPAREL_STYLE':
                        case 'APPAREL_TOO_LARGE':
                        case 'APPAREL_TOO_SMALL':
                        case 'EXCESSIVE_INSTALLATION':
                        case 'EXTRA_ITEM':
                        case 'FOUND_BETTER_PRICE':
                        case 'JEWELRY_TOO_LARGE':
                        case 'MISORDERED':
                        case 'NO_REASON_GIVEN':
                        case 'ORDERED_WRONG_ITEM':
                        case 'PRODUCT_NOT ITALIAN':
                        case 'PRODUCT_NOT SPANISH':
                        case 'SWITCHEROO':
                        case 'UNAUTHORIZED_PURCHASE':
                            $reason_type = 1; //客户原因
                            break;
                        case 'NOT_AS_DESCRIBED';
                            $reason_type = 2; //描述不符
                            break;
                        case 'MISSED_ESTIMATED_DELIVERY':
                        case 'DAMAGED_BY_FC':
                            $reason_type = 3; //延迟派送
                            break;
                        case 'DEFECTIVE':
                        case 'JEWELRY_LOOSE_STONE':
                        case 'NOT_COMPATIBLE':
                        case 'PART_NOT_COMPATIBLE':
                        case 'QUALITY_UNACCEPTABLE':
                            $reason_type = 4; //产品质量问题
                            break;
                        case 'DAMAGED_BY_CARRIER':
                            $reason_type = 5; //包装问题;
                            break;
                        case 'MISSING_PARTS':
                            $reason_type = 6; //6:数量短缺;
                        case 'NEVER_ARRIVED':
                        case 'UNDELIVERABLE_CARRIER_MISS_SORTED':
                        case 'UNDELIVERABLE_FAILED_DELIVERY_ATTEMPTS':
                        case 'UNDELIVERABLE_INSUFFICIENT_ADDRESS':
                        case 'UNDELIVERABLE_MISSING_LABEL':
                        case 'UNDELIVERABLE_REFUSED':
                        case 'UNDELIVERABLE_UNCLAIMED':
                            $reason_type = 7; //7:未收到
                        default :
                            $reason_type = 0;
                            break;
                    }
                    
                    //刊登sku对应多个公司sku的情况 保存多条记录
                    if(!empty($sku)){
                        if (stristr($sku, '+')) {
                            $skuArr = explode('+', $sku);
                            foreach ($skuArr as $val) {
                                $newQty = $qty;
                                if(stristr($val,'*')){
                                    $qtyArr = explode('*',$val);
                                    $val = $qtyArr[0];
                                    $newQty = $qty*$qtyArr[1];
                                }
                                $sql .= "('".$platformOrderId."',".$accontId.",".$oldAccountId.",'".$sellerSku."','".$val."','".$asin."','".$returnDate."',".$newQty.",'".$fulfillmentChannel."',"
                                        . "'".$status."','".addslashes($productName)."','".$fulfillmentCenterId."','".$detailedDisposition."','".$reason."','".$licensePlateNumber."','".addslashes($customerComments)."',"
                                        . "'".$createdAt."',".$reason_type.",".$isAvailableSale.",'".$title."'),";
                            }
                        } else {
                            $newQty = $qty;
                            //如果数量包含多个的情况
                            if(stristr($sku,'*')){
                                $qtyArr = explode('*',$sku);
                                $sku = $qtyArr[0];
                                $newQty = $qty*$qtyArr[1];
                            }
                            $sql .= "('".$platformOrderId."',".$accontId.",".$oldAccountId.",'".$sellerSku."','".$sku."','".$asin."','".$returnDate."',".$newQty.",'".$fulfillmentChannel."',"
                                        . "'".$status."','".addslashes($productName)."','".$fulfillmentCenterId."','".$detailedDisposition."','".$reason."','".$licensePlateNumber."','".addslashes($customerComments)."',"
                                         . "'".$createdAt."',".$reason_type.",".$isAvailableSale.",'".$title."'),";
                        }
                    }
                }
                $sql = rtrim($sql, ',');
//                echo $sql;die;
                Yii::$app->db->createCommand($sql)->execute();
                echo $i . '页数据执行完成<br/>';
            }
        }
    }
    
    /**
     * 从ueb_amazon_fba_returns表按账号 sku分组保存到FBA退货sku信息表
     * 获取总页数，每执行完一页抛出下一页,直到下一页超过总页数程序执行完成!
     * @param type $pages  总页数
     * @param type $nowpage  当前页
     * @author allen <2018-11-17>
     * SELECT count(1) as total FROM `ueb_amazon_fba_returns` GROUP BY account_id,sku;
     * 测试计划任务地址: http://www.customer.com/services/order/order/syncfbareturndata
     */
    public function actionSyncfbareturndata($pages = null,$nowpage = null) {
        //避免服务器拉取信息超时
        set_time_limit(0);
        
        $pageSize = 100; //页大小
        $bool = FALSE;//是否继续执行
        
        if(isset($nowpage) && $nowpage < 1){
            $nowpage = 1;
        }
        
        if(empty($pages) && empty($nowpage)){
            $sql = "SELECT count(id) as total ";
            $sql .= "FROM {{%amazon_fba_returns}}";
            //$sql .= "GROUP BY account_id,sku";
            //echo $sql;die;
            $res = Yii::$app->db->createCommand($sql)->queryAll();
            $count = $res[0]['total'];
            
            if ($count) {
                $totals = $count; //总记录条数
                $pages = ceil($totals / $pageSize); //总页数
                $nowpage = 1;
                $offset = 0;//第一页的偏移量
                $bool = TRUE;
            } else {
                exit('无数据');
            }
        }else{
            if($nowpage <= $pages){
                $bool = TRUE;
                $offset = ($nowpage - 1) * $pageSize;//第N页的偏移量
            }else{
                exit('程序执行完成!');
            }
        }
        
        //执行
        if($bool){
            $querySql = "SELECT * ";
            $querySql .= "FROM {{%amazon_fba_returns}} ";
            $querySql .= "WHERE 1 ";
            //$querySql .= "GROUP BY account_id,sku ";
            $querySql .= "ORDER BY id ";
            $querySql .= "LIMIT ".$offset.",".$pageSize;
            //echo $querySql;die;
            $model = Yii::$app->db->createCommand($querySql)->queryAll();
            
            //保存数据
            if (is_array($model) && !empty($model)) {
                $sql = "REPLACE INTO {{%amazon_fba_return_info}} (`account_id`,`old_account_id`,`platform_order_id`,`asin`,`seller_sku`,`sku`,`order_type`,`return_date`,
                        `qty`,`fulfillment_channel`,`status`,`return_reason`,`is_available_sale`,`pro_status`,`title`,`add_time`,`reason_type`,`sales_3`,`sales_7`,`sales_15`,
                        `sales_30`,`sales_60`,`sales_90`,`return_3`,`return_7`,`return_15`,`return_30`,`return_60`,`return_90`,`return_rate_3`,`return_rate_7`,`return_rate_15`,
                        `return_rate_30`,`return_rate_60`,`return_rate_90`) VALUES ";
                foreach ($model as $value) {
                    $accontId = !empty($value['account_id']) ? $value['account_id'] : 0; //客服账号ID      
                    $oldAccountId = !empty($value['old_account_id']) ? trim($value['old_account_id']) : 0; //erp账号ID                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         
                    $platformOrderId = !empty($value['platform_order_id']) ? trim($value['platform_order_id']) : ""; //平台订单号
                    $asin = !empty($value['asin']) ? trim($value['asin']) : ""; //asin码
                    $sellerSku = !empty($value['seller_sku']) ? trim($value['seller_sku']) : ""; //销售sku
                    $sku = !empty($value['sku']) ? trim($value['sku']) : ""; //公司sku
                    $orderType = 1; //1:FBA退货单
                    $returnDate = !empty($value['return_date']) ? trim($value['return_date']) : ""; //退货时间 
                    $qty = !empty($value['qty']) ? trim($value['qty']) : 0; //退货数量
                    $fulfillmentChannel = !empty($value['fulfillment_channel']) ? trim($value['fulfillment_channel']) : "";
                    $returnReason = !empty($value['reason']) ? trim($value['reason']) : ""; //退货原因
                    $isAvailableSale = !empty($value['is_available_sale']) ? trim($value['is_available_sale']) : 0; //是否可售  1：可售 2：不可售
                    $proStatus = !empty($value['status']) ? trim($value['status']) : ""; //退货产品状态
                    $title = !empty($value['title']) ? trim($value['title']) : ""; //产品中文名
                    $addTime = date("Y-m-d H:i:s");
                    $reasonType = !empty($value['reason_type']) ? $value['reason_type'] : 0;//退款原因类型
                    
                    //查询账号对应sku最近3,7,15,30,60,90天销量数据
                    $salesInfo = OrderKefu::getFbaReturnSales($sku,$oldAccountId);
                    
                    //查询账号都有sku最近3,7,15,30,60,90天退款数量
                    $returnInfo = AmazonFbaReturns::getFbaReturnQtys($sku,$oldAccountId);

                    $returnRate3 = ($returnInfo['returnQty3'] && $salesInfo['salesQty3']) ? round($returnInfo['returnQty3']/$salesInfo['salesQty3']*100,2) : 0;//最近3天退款率
                    $returnRate7 = ($returnInfo['returnQty7'] && $salesInfo['salesQty7']) ? round($returnInfo['returnQty7']/$salesInfo['salesQty7']*100,2) : 0;//最近7天退款率
                    $returnRate15 = ($returnInfo['returnQty15'] && $salesInfo['salesQty15']) ? round($returnInfo['returnQty15']/$salesInfo['salesQty15']*100,2) : 0 ;//最近15天退款率
                    $returnRate30 = ($returnInfo['returnQty30'] && $salesInfo['salesQty30']) ? round($returnInfo['returnQty30']/$salesInfo['salesQty30']*100,2) : 0;//最近30天退款率
                    $returnRate60 = ($returnInfo['returnQty60'] && $salesInfo['salesQty60']) ? round($returnInfo['returnQty60']/$salesInfo['salesQty60']*100,2) : 0;//最近60天退款率
                    $returnRate90 = ($returnInfo['returnQty90'] && $salesInfo['salesQty90']) ? round($returnInfo['returnQty90']/$salesInfo['salesQty90']*100,2) : 0;//最近90天退款率
                    
                    $sql .= "(" . $accontId . "," . $oldAccountId . ",'" . $platformOrderId . "','" . $asin . "','" . $sellerSku . "','" . $sku . "'," . $orderType . ",'" . $returnDate . "'," . $qty . ",'"
                                . "" . $fulfillmentChannel . "',0,'" . $returnReason . "'," . $isAvailableSale . ",'" . addslashes($proStatus) . "','" . addslashes($title) . "','" . $addTime . "',"
                            . "" . $reasonType . ",".$salesInfo['salesQty3'].",".$salesInfo['salesQty7'].",".$salesInfo['salesQty15'].",".$salesInfo['salesQty30'].",".$salesInfo['salesQty60'].","
                            . "".$salesInfo['salesQty90'].",".$returnInfo['returnQty3'].",".$returnInfo['returnQty7'].",".$returnInfo['returnQty15'].",".$returnInfo['returnQty30'].","
                            . "".$returnInfo['returnQty60'].",".$returnInfo['returnQty90'].",".$returnRate3.",".$returnRate7.",".$returnRate15.",".$returnRate30.",".$returnRate60.",".$returnRate90."),";
                }
                $sql = rtrim($sql, ',');
//                echo $sql;die;
                Yii::$app->db->createCommand($sql)->execute();
            }
            
            $nexPage = $nowpage + 1;            
            VHelper::throwTheader('/services/order/order/syncfbareturndata', ['pages' => $pages,'nowpage' => $nexPage]);
            exit('总共'.$totals.'条记录,共'.$pages.'页,每页'.$pageSize.'条,第一页执行成功,其余页数后台执行中....');
        }
    }
    
    
    /**
     * 同步更新fba退货sku统计数据
     * 获取总页数，每执行完一页抛出下一页,直到下一页超过总页数程序执行完成!
     * @param type $pages  总页数
     * @param type $nowpage  当前页
     * @author allen <2018-11-17>
     * SELECT count(1) as total FROM `ueb_amazon_fba_returns` GROUP BY sku;
     * 测试计划任务地址: http://www.customer.com/services/order/order/syncfbaskudata
     */
    public function actionSyncfbaskudata($pages = null,$nowpage = null) {
        //避免服务器拉取信息超时
        set_time_limit(0);
        
        $pageSize = 100; //页大小
        $bool = FALSE;//是否继续执行
        
        if(isset($nowpage) && $nowpage < 1){
            $nowpage = 1;
        }
        
        if(empty($pages) && empty($nowpage)){
            $sql = "SELECT count(id) as total ";
            $sql .= "FROM {{%amazon_fba_returns}}";
            $sql .= "GROUP BY sku";
            //echo $sql;die;
            $res = Yii::$app->db->createCommand($sql)->queryAll();
            $count = count($res);

            if ($count) {
                $totals = $count; //总记录条数
                $pages = ceil($totals / $pageSize); //总页数
                $nowpage = 1;
                $offset = 0;//第一页的偏移量
                $bool = TRUE;
            } else {
                exit('无数据');
            }
        }else{
            if($nowpage <= $pages){
                $bool = TRUE;
                $offset = ($nowpage - 1) * $pageSize;//第N页的偏移量
            }else{
                exit('程序执行完成!');
            }
        }
        
        //执行
        if($bool){
            $querySql = "SELECT * ";
            $querySql .= "FROM {{%amazon_fba_returns}} ";
            $querySql .= "WHERE 1 ";
            $querySql .= "GROUP BY sku ";
            $querySql .= "ORDER BY id ";
            $querySql .= "LIMIT ".$offset.",".$pageSize;
            //echo $querySql;die;
            $model = Yii::$app->db->createCommand($querySql)->queryAll();
            //保存数据
            if (is_array($model) && !empty($model)) {
                $j = 0;
                foreach ($model as $value) {
                    $j++;
                    $sql = "INSERT INTO {{%amazon_fba_return_analysis}} (`sku`,`seller_sku`,`title`,`return_7`,`return_15`,`return_30`,`return_60`,`return_90`,`return_rate_7`,`return_rate_15`,"
                        . "`return_rate_30`,`return_rate_60`,`return_rate_90`,`return_trend`,`sales_7`,`sales_15`,`sales_30`,`sales_60`,`sales_90`,`sales_trend`,`customer_7`,`customer_15`,"
                        . "`customer_30`,`customer_60`,`customer_90`,`description_7`,`description_15`,`description_30`,`description_60`,`description_90`,`overtime_7`,`overtime_15`,"
                        . "`overtime_30`,`overtime_60`,`overtime_90`,`quality_7`,`quality_15`,`quality_30`,`quality_60`,`quality_90`,`add_date`,`packaging_7`,`packaging_15`,`packaging_30`,"
                        . "`packaging_60`,`packaging_90`,`shortage_7`,`shortage_15`,`shortage_30`,`shortage_60`,`shortage_90`,`not_received_7`,`not_received_15`,`not_received_30`,`not_received_60`,"
                        . "`not_received_90`,`remark`) VALUES ";
                    
                    $sku = !empty($value['sku']) ? trim($value['sku']) : ""; //公司sku
                    //如果sku为空 则跳过
                    if(empty($sku)){
                        continue;
                    }
                    $sellerSku = !empty($value['seller_sku']) ? trim($value['seller_sku']) : ""; //销售sku
                    $title = !empty($value['title']) ? trim($value['title']) : ""; //产品中文名
                    
                    $AmazonFbaReturnsModel = AmazonFbaReturnAnalysis::findOne(['sku' => $sku]);
                    
                    //查询账号对应sku最近3,7,15,30,60,90天销量数据
                    $salesInfo = OrderKefu::getFbaReturnSales($sku);
                    
                    //查询账号都有sku最近3,7,15,30,60,90天退款数量
                    $returnInfo = AmazonFbaReturns::getFbaReturnQtys($sku);
                    
                    $reasonTypeInfo = AmazonFbaReturns::getReturnReasonTypeInfo($sku);
                    
                    //$returnRate3 = ($returnInfo['returnQty3'] && $salesInfo['salesQty3']) ? round($returnInfo['returnQty3']/$salesInfo['salesQty3']*100,2) : 0;//最近3天退款率
                    $returnRate7 = ($returnInfo['returnQty7'] && $salesInfo['salesQty7']) ? round($returnInfo['returnQty7']/$salesInfo['salesQty7']*100,2) : 0;//最近7天退款率
                    $returnRate15 = ($returnInfo['returnQty15'] && $salesInfo['salesQty15']) ? round($returnInfo['returnQty15']/$salesInfo['salesQty15']*100,2) : 0 ;//最近15天退款率
                    $returnRate30 = ($returnInfo['returnQty30'] && $salesInfo['salesQty30']) ? round($returnInfo['returnQty30']/$salesInfo['salesQty30']*100,2) : 0;//最近30天退款率
                    $returnRate60 = ($returnInfo['returnQty60'] && $salesInfo['salesQty60']) ? round($returnInfo['returnQty60']/$salesInfo['salesQty60']*100,2) : 0;//最近60天退款率
                    $returnRate90 = ($returnInfo['returnQty90'] && $salesInfo['salesQty90']) ? round($returnInfo['returnQty90']/$salesInfo['salesQty90']*100,2) : 0;//最近90天退款率
                    
                    //退款率趋势 [最近7天退款率 > 最近15天退款率  上升 反之 下降] 1下降 2上升 3 持平
                    $returnTrend = 3;
                    if($returnRate7 > $returnRate15){
                        $returnTrend = 2;
                    }
                    if($returnRate7 < $returnRate15){
                        $returnTrend = 1;
                    }
                    
                    //销量趋势 [最近7天销售 > 最近15天销量  上升 反之 下降] 1下降 2上升 3 持平
                    $salesTrend = 3;
                    if($salesInfo['salesQty7'] > $salesInfo['salesQty15']){
                        $salesTrend = 2;
                    }
                    if($salesInfo['salesQty7'] < $salesInfo['salesQty15']){
                        $salesTrend = 1;
                    }
                    
                    $addTime = date("Y-m-d H:i:s");
                    $sql .= "('".$sku."','".$sellerSku."','".addslashes($title)."',".$returnInfo['returnQty7'].",".$returnInfo['returnQty15'].",".$returnInfo['returnQty30'].",".$returnInfo['returnQty60'].","
                            . "".$returnInfo['returnQty90'].",".$returnRate7.",".$returnRate15.",".$returnRate30.",".$returnRate60.",".$returnRate90.",".$returnTrend.",".$salesInfo['salesQty7'].","
                            . "".$salesInfo['salesQty15'].",".$salesInfo['salesQty30'].",".$salesInfo['salesQty60'].",".$salesInfo['salesQty90'].",".$salesTrend.",".$reasonTypeInfo['customerReason7'].","
                            . "".$reasonTypeInfo['customerReason15'].",".$reasonTypeInfo['customerReason30'].",".$reasonTypeInfo['customerReason60'].",".$reasonTypeInfo['customerReason90'].","
                            . "".$reasonTypeInfo['descriptionReason7'].",".$reasonTypeInfo['descriptionReason15'].",".$reasonTypeInfo['descriptionReason30'].",".$reasonTypeInfo['descriptionReason60'].","
                            . "".$reasonTypeInfo['descriptionReason90'].",".$reasonTypeInfo['overtimeReason7'].",".$reasonTypeInfo['overtimeReason15'].",".$reasonTypeInfo['overtimeReason30'].",".$reasonTypeInfo['overtimeReason60'].","
                            . "".$reasonTypeInfo['overtimeReason90'].",".$reasonTypeInfo['qualityReason7'].",".$reasonTypeInfo['qualityReason15'].",".$reasonTypeInfo['qualityReason30'].",".$reasonTypeInfo['qualityReason60'].","
                            . "".$reasonTypeInfo['qualityReason90'].",'".$addTime."',".$reasonTypeInfo['packagingReason7'].",".$reasonTypeInfo['packagingReason15'].",".$reasonTypeInfo['packagingReason30'].","
                            . "".$reasonTypeInfo['packagingReason60'].",".$reasonTypeInfo['packagingReason90'].",".$reasonTypeInfo['shortageReason7'].",".$reasonTypeInfo['shortageReason15'].","
                            . "".$reasonTypeInfo['shortageReason30'].",".$reasonTypeInfo['shortageReason60'].",".$reasonTypeInfo['shortageReason90'].",".$reasonTypeInfo['notReceivedReason7'].","
                            . "".$reasonTypeInfo['notReceivedReason15'].",".$reasonTypeInfo['notReceivedReason30'].",".$reasonTypeInfo['notReceivedReason60'].","
                            . "".$reasonTypeInfo['notReceivedReason90'].",'".'第'.$nowpage.'页中第条'.$j.'记录新增成功 ['.date('Y-m-d H:i:s').']'."'),";
                
                    $sql = rtrim($sql, ',');
                    //echo $sql;die;
                    if(empty($AmazonFbaReturnsModel)){
                        Yii::$app->db->createCommand($sql)->execute();
                    }else{
                        //update
                        //$AmazonFbaReturnsModel -> sku = $sku;
                        //$AmazonFbaReturnsModel -> seller_sku = $sellerSku;
                        //$AmazonFbaReturnsModel -> title = addslashes($title);
                        $AmazonFbaReturnsModel -> return_7 = $returnInfo['returnQty7'];
                        $AmazonFbaReturnsModel -> return_7 = $returnInfo['returnQty7'];
                        $AmazonFbaReturnsModel -> return_15 = $returnInfo['returnQty15'];
                        $AmazonFbaReturnsModel -> return_30 = $returnInfo['returnQty30'];
                        $AmazonFbaReturnsModel -> return_60 = $returnInfo['returnQty60'];
                        $AmazonFbaReturnsModel -> return_90 = $returnInfo['returnQty90'];
                        $AmazonFbaReturnsModel -> return_rate_7 = $returnRate7;
                        $AmazonFbaReturnsModel -> return_rate_15 = $returnRate15;
                        $AmazonFbaReturnsModel -> return_rate_30 = $returnRate30;
                        $AmazonFbaReturnsModel -> return_rate_60 = $returnRate60;
                        $AmazonFbaReturnsModel -> return_rate_90 = $returnRate90;
                        $AmazonFbaReturnsModel -> return_trend = $returnTrend;
                        $AmazonFbaReturnsModel -> sales_7 = $salesInfo['salesQty7'];
                        $AmazonFbaReturnsModel -> sales_15 = $salesInfo['salesQty15'];
                        $AmazonFbaReturnsModel -> sales_30 = $salesInfo['salesQty30'];
                        $AmazonFbaReturnsModel -> sales_60 = $salesInfo['salesQty60'];
                        $AmazonFbaReturnsModel -> sales_90 = $salesInfo['salesQty90'];
                        $AmazonFbaReturnsModel -> sales_trend = $salesTrend;
                        $AmazonFbaReturnsModel -> customer_7 = $reasonTypeInfo['customerReason7'];
                        $AmazonFbaReturnsModel -> customer_15 = $reasonTypeInfo['customerReason15'];
                        $AmazonFbaReturnsModel -> customer_30 = $reasonTypeInfo['customerReason30'];
                        $AmazonFbaReturnsModel -> customer_60 = $reasonTypeInfo['customerReason60'];
                        $AmazonFbaReturnsModel -> customer_90 = $reasonTypeInfo['customerReason90'];
                        $AmazonFbaReturnsModel -> description_7 = $reasonTypeInfo['descriptionReason7'];
                        $AmazonFbaReturnsModel -> description_15 = $reasonTypeInfo['descriptionReason15'];
                        $AmazonFbaReturnsModel -> description_30 = $reasonTypeInfo['descriptionReason30'];
                        $AmazonFbaReturnsModel -> description_60 = $reasonTypeInfo['descriptionReason60'];
                        $AmazonFbaReturnsModel -> description_90 = $reasonTypeInfo['descriptionReason90'];
                        $AmazonFbaReturnsModel -> overtime_7 = $reasonTypeInfo['overtimeReason7'];
                        $AmazonFbaReturnsModel -> overtime_15 = $reasonTypeInfo['overtimeReason15'];
                        $AmazonFbaReturnsModel -> overtime_30 = $reasonTypeInfo['overtimeReason30'];
                        $AmazonFbaReturnsModel -> overtime_60 = $reasonTypeInfo['overtimeReason60'];
                        $AmazonFbaReturnsModel -> overtime_90 = $reasonTypeInfo['overtimeReason90'];
                        $AmazonFbaReturnsModel -> quality_7 = $reasonTypeInfo['qualityReason7'];
                        $AmazonFbaReturnsModel -> quality_15 = $reasonTypeInfo['qualityReason15'];
                        $AmazonFbaReturnsModel -> quality_30 = $reasonTypeInfo['qualityReason30'];
                        $AmazonFbaReturnsModel -> quality_60 = $reasonTypeInfo['qualityReason60'];
                        $AmazonFbaReturnsModel -> quality_90 = $reasonTypeInfo['qualityReason90'];
                        $AmazonFbaReturnsModel -> packaging_7 = $reasonTypeInfo['packagingReason7'];
                        $AmazonFbaReturnsModel -> packaging_15 = $reasonTypeInfo['packagingReason15'];
                        $AmazonFbaReturnsModel -> packaging_30 = $reasonTypeInfo['packagingReason30'];
                        $AmazonFbaReturnsModel -> packaging_60 = $reasonTypeInfo['packagingReason60'];
                        $AmazonFbaReturnsModel -> packaging_90 = $reasonTypeInfo['packagingReason90'];
                        $AmazonFbaReturnsModel -> shortage_7 = $reasonTypeInfo['shortageReason7'];
                        $AmazonFbaReturnsModel -> shortage_15 = $reasonTypeInfo['shortageReason15'];
                        $AmazonFbaReturnsModel -> shortage_30 = $reasonTypeInfo['shortageReason30'];
                        $AmazonFbaReturnsModel -> shortage_60 = $reasonTypeInfo['shortageReason60'];
                        $AmazonFbaReturnsModel -> shortage_90 = $reasonTypeInfo['shortageReason90'];
                        $AmazonFbaReturnsModel -> not_received_7 = $reasonTypeInfo['notReceivedReason7'];
                        $AmazonFbaReturnsModel -> not_received_15 = $reasonTypeInfo['notReceivedReason15'];
                        $AmazonFbaReturnsModel -> not_received_30 = $reasonTypeInfo['notReceivedReason30'];
                        $AmazonFbaReturnsModel -> not_received_60 = $reasonTypeInfo['notReceivedReason60'];
                        $AmazonFbaReturnsModel -> not_received_90 = $reasonTypeInfo['notReceivedReason60'];
                        $AmazonFbaReturnsModel -> return_date = date("Y-m-d H:i:s");
                        $AmazonFbaReturnsModel -> remark = '第'.$nowpage.'页中第'.$j.'条记录更新成功 ['.date('Y-m-d H:i:s').']';
                        $AmazonFbaReturnsModel -> save();
                    }
                }
            }
            
            $nexPage = $nowpage + 1;
            VHelper::throwTheader('/services/order/order/syncfbaskudata', ['pages' => $pages,'nowpage' => $nexPage]);
            exit('总共'.$totals.'条记录,共'.$pages.'页,每页'.$pageSize.'条,第一页执行成功,其余页数后台执行中....');
        }
    }
    
    
    /**
     * 导出数据库设计文档
     * @author allen <2018-11-29>
     * http://www.customer.com/services/order/order/exportdbdoc?db_name=ueb_crm
     */
    public function actionExportdbdoc($db_name){
        $sql = "select table_name,table_comment from information_schema.tables where table_schema='".$db_name."' and table_type='base table'";
        $res = Yii::$app->db->createCommand($sql)->queryAll();
        
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"  xmlns:w="urn:schemas-microsoft-com:office:word"  xmlns="http://www.w3.org/TR/REC-html40">
              <head>
                   <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                   <xml><w:WordDocument><w:View>Print</w:View></xml>
                   <script src="includes/js/ztree/js/jquery-1.4.4.min.js" type="text/javascript"></script>
            </head>';
        echo '<body>';
        echo '<EOT <style type="text/css">
                table.gridtable {
                    font-family: verdana,arial,sans-serif;
                    font-size:11px;
                    color:#333333;
                    border-width: 1px;
                    border-color: #666666;
                    border-collapse: collapse;
                    width:600px;
               }
               table.gridtable th {
                   border-width: 1px;
                   border-style: solid;
                   border-color: #666666;
                   background-color: #dedede;
               }
               table.gridtable td {
                   border-width: 1px;
                   border-style: solid;
                   border-color: #666666;
                   background-color: #ffffff;
               }
               </style>';
        
        
        //循环所有表
        if(!empty($res)){
            foreach ($res as $key => $value) {
                $tableName = $value['table_name'];//表名
                $tableComment = !empty($value['table_comment']) ? '('.$value['table_comment'].')' : "";//表备注
                
                echo ($key+1).'.'.$tableName.$tableComment.'<br/><table class="gridtable">';
                $tabSql = "SHOW FULL FIELDS FROM ".$tableName;
                $tabRes = Yii::$app->db->createCommand($tabSql)->queryAll();
                if(!empty($tabRes)){
                    echo '<tr><th style="width:15px;">编号</th><th style="width:30px;">字段名</th><th style="width:20px;">是否为空</th><th style="width:15px;">主键</th><th style="width:15px;">默认值</th><th style="width:100px;">字段说明</th></tr>';
                    foreach ($tabRes as $k => $val) {
                          echo '<tr>
                                    <td>'.($k+1).'</td><td>'.$val['Field'].'</td><td>'.$val['Null'].'</td><td>'.$val['Key'].'</td><td>'.$val['Default'].'</td><td>'.$val['Comment'].'</td>
                                </tr>';
                    }
                }
                echo '</table><br/>';
            }
        }
        
        echo '</body></html>';
        ob_start(); //打开缓冲区
        Header("Cache-Control: public");
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        if (strpos($_SERVER["HTTP_USER_AGENT"],'MSIE')) {
          header('Content-Disposition: attachment; filename=test.doc');
          }else if (strpos($_SERVER["HTTP_USER_AGENT"],'Firefox')) {
          Header('Content-Disposition: attachment; filename=test.doc');
          } else {
          header('Content-Disposition: attachment; filename=test.doc');
          }
          header("Pragma:no-cache");
          header("Expires:0");
          ob_end_flush();//输出全部内容到浏览器
        }
}
