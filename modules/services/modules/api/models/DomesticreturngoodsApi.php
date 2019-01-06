<?php

/**
 * @desc 订单接口
 * @author Administrator
 *
 *
 * 拆单，驳回，补款
 */

namespace app\modules\services\modules\api\models;

use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\aftersales\models\AfterSalesReturn;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressEvaluate;
use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonInboxSubject;
use app\modules\mails\models\CdiscountInboxSubject;
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\mails\models\WalmartInboxSubject;
use app\modules\aftersales\models\Domesticreturngoods;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\OrderList;
use app\modules\systems\models\ErpOrderApi;
use app\modules\aftersales\models\ReturnRuleProcessingResult;
use Yii;
use yii\helpers\Json;
use app\modules\accounts\models\Account;

class DomesticreturngoodsApi {

    public function sendResponse($code = '200', $body = null, $type = 'application/json') {
        $responseBody = '';
        switch ($code) {
            case '200':
                $header = 'HTTP/1.0 200 OK';
                $responseBody = 'OK';
                break;
            case '400':
                $header = 'HTTP/1.0 400 Not Found';
                $responseBody = 'Bad Request';
                $type = 'text/html';
                break;
            case '403':
                $header = 'HTTP/1.0 403 Forbidden';
                $responseBody = 'Token is Invalid';
                $type = 'text/html';
                break;
            case '404':
                $header = 'HTTP/1.0 404 Not Found';
                $responseBody = 'Not Found';
                $type = 'text/html';
                break;
            case '500':
                $header = 'HTTP/1.0 500 Server Internal Error';
                $responseBody = 'Server Internal Error';
                $type = 'text/html';
                break;
        }
        if (!empty($body) && $type == 'application/json') {
            $body = Json::encode($body);
        }
        if (!empty($body))
            $responseBody = $body;
        header($header);
        header('Content-type: ' . $type);
        echo $responseBody;
        exit;
    }

    /**
     * @desc 获取订单售后单
     */
    public function afferentReturnOrder() {
        $params = file_get_contents("php://input");
//        $path = Yii::getAlias('@runtime').'/getorderss.log';
//        @file_put_contents($path,$params,FILE_APPEND);
//        die;
        //$params = '{"type":{"1":{"1":"\u5b89\u68c0","2":"\u5c3a\u5bf8\u8d85\u957f,\u4f53\u79ef\u8d85\u91cd","3":"\u504f\u8fdc","4":"\u5730\u5740\u95ee\u9898","5":"\u8ddf\u8e2a\u53f7\u5931\u8d25","6":"\u9000\u4ef6\u91cd\u53f7"},"2":{"1":"\u6d3e\u4ef6\u4e0d\u6210\u529f"}},"orders":{"SP180620001409":{"order_id":"SP180620001409","platform_code":"SHOPEE","remark":"\u4e0d\u5408\u683c","return_number":"33956","remarks_user":"\u8d75\u5927\u4f1f","trackno":"G00389324359","state":2,"source":1,"type":"1","type_small":"1","create_time":"2018-07-26 09:58:34","ruleid":"5","new_rule":{"is_case":"1","is_return":"0","is_send":"0","is_message":"0","is_feedback":"0"}}}}';
//        $params = '{"type":{"1":{"1":"\u5b89\u68c0","2":"\u5c3a\u5bf8\u8d85\u957f,\u4f53\u79ef\u8d85\u91cd","3":"\u504f\u8fdc","4":"\u5730\u5740\u95ee\u9898","5":"\u8ddf\u8e2a\u53f7\u5931\u8d25","6":"\u9000\u4ef6\u91cd\u53f7"},"2":{"1":"\u6d3e\u4ef6\u4e0d\u6210\u529f"}},"orders":{"AL180814004794":{"order_id":"AL180814004794","platform_code":"ALI","remark":"\u8be5\u6e20\u9053\u8be5\u56fd\u5bb6\u4e0d\u63a5\u53d7\u5185\u7f6e\u7535\u6c60\uff0c\u6362\u6e20\u9053\u8865\u53d1\uff0c4px-s\u5c0f\u5305\u6302\u53f7+\u5e26\u7535","return_number":"44280","remarks_user":"\u8d75\u5927\u4f1f","trackno":"LZ700739095CN","state":1,"source":1,"type":"1","type_small":"1","create_time":"2018-08-27 08:16:54"}}}';
//        $in_case_flag = $is_return_flag = $is_send_flag = $is_message_flag = $is_feedback_flag = false;
        $paramsarray = json_decode($params, true);
        $returndata = array();
        if (empty($paramsarray)) {
            $response = new \stdClass();
            $response->ack = false;
            $response->return_number = json_encode($returndata);
            $response->afterSalesOrders = '参数错误';
            $this->sendResponse('300', $response);
            exit;
        }
        foreach ($paramsarray['orders'] as $list) {
            $returnResult = new ReturnRuleProcessingResult();

//            var_dump($list);die;
            //退款单号不能为空
            if ($list['source'] == 1) {
                if (empty($list['trackno'])) {
                    $returndata[] = $list['order_id'] . '国内退件无跟踪号';
                    continue;
                }
            }
            if (empty($list['source'])) {

                $returndata[] = $list['order_id'] . '退货来源不能为空';
                continue;
            }

            if (empty($list['platform_code']) || empty($list['order_id']))
                continue;


            if (!empty($list['platform_code']) && empty($list['buyer_id'])) {
                //查询买家id
                if (!in_array($list['platform_code'], ['EB', 'ALI', 'AMAZON', 'WISH'])) {
                    $platform_code = 'OTHER';
                } else {
                    $platform_code = $list['platform_code'];
                }
                $orders = OrderList::getOrderone($platform_code, $list['order_id']);
                if (empty($orders)) {
                    $buyer_id = $list['buyer_id'];
                } else {
                    $buyer_id = $orders->buyer_id;
                }
            }
            //查询该订单的卖家账号
            $platform_code = $list['platform_code'];
            switch ($platform_code) {
                case 'EB':
                    $model = OrderKefu::model('order_ebay');
                    break;
                case 'ALI':
                    $model = OrderKefu::model('order_aliexpress');
                    break;
                case 'AMAZON':
                    $model = OrderKefu::model('order_amazon');
                    break;
                case 'WISH':
                    $model = OrderKefu::model('order_wish');
                    break;
                default:
                    $model = OrderKefu::model('order_other');
                    break;
            }


            $orders = $model->where(['order_id' => $list['order_id']])->one();
            if (empty($orders)) {
                //查询copy表数据
                switch ($platform_code) {
                    case 'EB':
                        $model = OrderKefu::model('order_ebay_copy');
                        break;
                    case 'ALI':
                        $model = OrderKefu::model('order_aliexpress_copy');
                        break;
                    case 'AMAZON':
                        $model = OrderKefu::model('order_amazon_copy');
                        break;
                    case 'WISH':
                        $model = OrderKefu::model('order_wish_copy');
                        break;
                    default:
                        $model = OrderKefu::model('order_other_copy');
                        break;
                }
                $orders = $model->where(['order_id' => $list['order_id']])->one();
            }
            $account_old_id = isset($orders->account_id) ? $orders->account_id : 0;
            $account = Account::findAccountId($account_old_id, $platform_code);
            //account[0] 返回的是客服系统的主键id
            $platform_order_id = $orders->platform_order_id;
            /**
             * $state 客服状态:1:待客服处理 2:无需处理 3:已处理(已处理过售后单)
             * $type  erp处理状态: 1:待处理 2:发货  3:不发货 4:标记已发货(已处理过重发单无需重新发货)
             * 当erp状态 $type = 1时无需通知erp 不需要掉接口
             * 判断推送数据state 无需处理的
             */
            $returnResult->platform_code = $platform_code;
            $returnResult->order_id = $list['order_id'];
            $after_sale_orders = AfterSalesOrder::getAfterSalesOrderByOrderId($list['order_id'], $platform_code);
            
            $notifyErp = FALSE;//默认不同步erp
            if ($list['state'] == 2) {
                $returnResult->erp_rule = 1;
                $returnResult->is_run_kfrule = 1;
                $state = 1; //待处理
                $type = 1; //待处理
                $isCase = FALSE;
                $isReturn = FALSE;
                $isResend = FALSE;
                $isMessage = FALSE;
                $isFeedback = FALSE;
                //判断规则
//                if (!empty($list['new_rule'])) {
                //是否有纠纷
//                    if (isset($list['new_rule']['is_case'])) {
                if ($platform_code == Platform::PLATFORM_CODE_EB) {
                    $cancel = EbayCancellations::disputeLevel($platform_order_id);
                    $inquiry = EbayInquiry::disputeLevel($platform_order_id);
                    $returns = EbayReturnsRequests::disputeLevel($platform_order_id);
                    if (!empty($cancel) || !empty($inquiry) || !empty($returns)) {
                        $isCase = TRUE;
                        $returnResult->is_case = 1;
                    } else {
                        $returnResult->is_case = 0;
                    }
                } elseif ($platform_code == Platform::PLATFORM_CODE_ALI) {
                    //速卖通纠纷
                    $smt_dispute = AliexpressDisputeList::whetherExist($platform_order_id);
                    if (!empty($smt_dispute)) {
                        $isCase = TRUE;
                        $returnResult->is_case = 1;
                    } else {
                        $returnResult->is_case = 0;
                    }
                }
//                    }
                //是否有退货
//                    if (isset($list['new_rule']['is_return'])) {
                //有退款
                if (!empty($after_sale_orders['refund_res'])) {
                    $isReturn = TRUE;
                    $returnResult->is_return = 1;
                } else {
                    $returnResult->is_return = 0;
                }
//                    }
                //是否重寄
//                    if (isset($list['new_rule']['is_send'])) {
                //有重寄
                if (!empty($after_sale_orders['redirect_res'])) {
                    $isResend = true;
                    $returnResult->is_resend = 1;
                } else {
                    $returnResult->is_resend = 0;
                }
//                    }
                //是否有站内信
//                    if (isset($list['new_rule']['is_message'])) {
                switch ($platform_code) {
                    case Platform::PLATFORM_CODE_EB:
                        //item_id ebay 根据item_id 关联
                        $item_ids = $list['item_ids'];
                        $ebay_inbox_subject = '';
                        if (!empty($item_ids)) {
                            //查询
                            $ebay_inbox_subject = EbayInboxSubject::haveEbayInboxSubject($account[0], $item_ids, $buyer_id);
                        }

                        if (!empty($ebay_inbox_subject)) {
                            $isMessage = true;
                            $returnResult->is_message = 1;
                        } else {
                            $returnResult->is_message = 0;
                        }
                        break;

                    case Platform::PLATFORM_CODE_WALMART:
                        $walmart_inbox_subject = WalmartInboxSubject::findOne(['order_id' => $list['order_id'], 'buyer_id' => $buyer_id, 'account_id' => $account[0]]);
                        if (!empty($walmart_inbox_subject)) {
                            $isMessage = true;
                            $returnResult->is_message = 1;
                        } else {
                            $returnResult->is_message = 0;
                        }
                        break;
                    case Platform::PLATFORM_CODE_AMAZON:
                        $amazon_inbox_subject = AmazonInboxSubject::findOne(['order_id' => $list['order_id'], 'buyer_id' => $buyer_id, 'account_id' => $account[0]]);
                        if (!empty($amazon_inbox_subject)) {
                            $isMessage = true;
                            $returnResult->is_message = 1;
                        } else {
                            $returnResult->is_message = 0;
                        }
                        break;
                    case Platform::PLATFORM_CODE_CDISCOUNT:
                        $cdiscount_inbox_subject = CdiscountInboxSubject::findOne(['platform_order_id' => $platform_order_id, 'account_id' => $account[0]]);
                        if (!empty($cdiscount_inbox_subject)) {
                            $isMessage = true;
                            $returnResult->is_message = 1;
                        } else {
                            $returnResult->is_message = 0;
                        }
                        break;
                }
//                    }
                //是否有差评
//                    if (isset($list['new_rule']['is_feedback'])) {
                switch ($platform_code) {
                    case Platform::PLATFORM_CODE_EB:
                        //
                        $ebay_feedback = EbayFeedback::find()
                                ->select(['feedback_id'])
                                ->where(['order_line_item_id' => $platform_order_id, 'role' => 1])
                                ->andWhere(['in', 'comment_type', [1, 2, 3]])
                                ->limit(1)
                                ->asArray()
                                ->one();
                        if (!empty($ebay_feedback)) {
                            $isFeedback = true;
                            $returnResult->is_feedback = 1;
                        } else {
                            $returnResult->is_feedback = 0;
                        }
                        break;
                    case Platform::PLATFORM_CODE_ALI:
                        $aliexpress_evaluate = AliexpressEvaluate::getFindOne($platform_order_id);
                        if (!empty($aliexpress_evaluate)) {
                            $isFeedback = true;
                            $returnResult->is_feedback = 1;
                        } else {
                            $returnResult->is_feedback = 0;
                        }
                        break;

                    case Platform::PLATFORM_CODE_AMAZON:
                        $amazon_feedback = AmazonFeedBack::find()
                                ->select(['id'])
                                ->where(['order_id' => $platform_order_id])
                                ->andWhere(['in', 'rating', [1, 2, 3]])
                                ->limit(1)
                                ->asArray()
                                ->one();

                        if (!empty($amazon_feedback)) {
                            $isFeedback = true;
                            $returnResult->is_feedback = 1;
                        } else {
                            $returnResult->is_feedback = 0;
                        }
                        break;
                }
//                    }
                //符合规则 erp状态: 发货  客服状态: 无需处理
                if (!$isCase && !$isReturn && !$isResend && !$isMessage && !$isFeedback) {
                    $notifyErp = TRUE; //需要通知erp
                    $state = 2; //客服系统无需处理
                    $type = 2; //erp发货
                    $returnResult->result = '两边规则都符合【客服:无需处理 erp:重发】';
                } else {
                    //符合erp规则 不符合客服规则 客服有退款单 客服状态：已处理，erp不发货
                    if (!empty($after_sale_orders['refund_res'])) {
                        $notifyErp = TRUE; //需要通知erp
                        $state = 3; //已处理
                        $type = 3; //通知erp不发货
//                        $returnResult->is_return = 1;
                        $result = 'ERP规则符合客服已创建退款单【客服:已处理(已建退款单) erp:不发货】';
                    }else if (!empty($after_sale_orders['redirect_res'])) {
                        $notifyErp = TRUE; //需要通知erp
                        $state = 3; //已处理
                        $type = 4; //通知erp有重复单但无需发货
//                        $returnResult->is_resend = 1;
                        $result = 'ERP规则符合客服规则不符合已建重寄单【客服:已处理(已建重寄单) erp:标记发货单不需要重新发货】';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             
                    }
                }

//                }
            } else {
                $returnResult->erp_rule = 0;
                $returnResult->is_run_kfrule = 0;
                $returnResult->is_case = 0;
                $returnResult->is_message = 0;
                $returnResult->is_feedback = 0;
                $result = 'ERP规则不符合【客服:待处理 erp:待处理】';
                //erp未匹配到规则的订单
                $state = 1; //待处理
                //判断订单是否已创建过退款单
                if (!empty($after_sale_orders['refund_res'])) {
                    $notifyErp = TRUE; //需要通知erp
                    $state = 3; //已处理
                    $type = 3; //通知erp不发货
                    $returnResult->is_return = 1;
                    $result = 'ERP规则不符合【客服:已处理(已建退款单) erp:不发货】';
                } else {
                    $returnResult->is_return = 0;
                }

                //判断订单是否已创建过重寄单
                if (!empty($after_sale_orders['redirect_res'])) {
                    $notifyErp = TRUE; //需要通知erp
                    $state = 3; //已处理
                    $type = 4; //通知erp有重复单但无需发货
                    $returnResult->is_resend = 1;
                    $result = 'ERP规则不符合【客服:已处理(已建重寄单) erp:已创建重寄单】';
                } else {
                    $returnResult->is_resend = 0;
                }

                $returnResult->result = $result;
            }

            $syncErp = TRUE;
            if ($notifyErp) {
                $orderModel = new ErpOrderApi();
                $ship_data = array(
                    "order_id" => $list['order_id'],
                    "platform_code" => $platform_code,
                    "track_number" => $list['trackno'],
                    "type" => $type, //2发货,3不发货,4通知erp有重复单但无需发货
                    'create_user' => Yii::$app->user->identity->login_name, //修改人
                    'create_time' => date('Y-m-d H:i:s'), //修改时间
                );
                $res_send = $orderModel->Whethership($ship_data);
                if ($res_send->statusCode != 200) {
                    $syncErp = FALSE;
                }
            }

            if ($syncErp) {
                $domesticreturngoods = Domesticreturngoods::findOne(['platform_code' => $list['platform_code'], 'order_id' => $list['order_id']]);
                if ($domesticreturngoods === null) {
                    $domesticreturngoods = new Domesticreturngoods;
                    $domesticreturngoods->return_number = $list['return_number'];
                    $domesticreturngoods->order_id = $list['order_id'];
                    $domesticreturngoods->create_time = $list['create_time'];
                    $domesticreturngoods->trackno = $list['trackno'];
                    $domesticreturngoods->buyer_id = $buyer_id;
                    $domesticreturngoods->account_id = isset($account[0]) ? $account[0] : 0;
                }
                $domesticreturngoods->platform_code = $list['platform_code'];
                $domesticreturngoods->return_type = $list['type'];
                $domesticreturngoods->return_typesmall = $list['type_small'];
                $domesticreturngoods->remark = $list['remark'];
                $domesticreturngoods->source = $list['source'];
                $domesticreturngoods->state = $state;
                $domesticreturngoods->creator = 'system';
                $domesticreturngoods->synchronization_time = date('Y-m-d H:i:s');
                $domesticreturngoods->update_time = date('Y-m-d H:i:s');
                $domesticreturngoods->erp_type = json_encode($paramsarray['type']);

                if ($domesticreturngoods->save()) {
                    $returndata[] = $list['order_id'];
                }
                //保存日志信息
                $returnResult->processing_date = date('Y-m-d H:i:s');
                $returnResult->save();
            }
        }
        if (empty($returndata)) {
            $response = new \stdClass();
            $response->ack = false;
            $response->return_number = $returndata;
            $response->afterSalesOrders = '数据同步失败 ' . $res_send->message;
            $this->sendResponse('300', $response);
        } else {
            $response = new \stdClass();
            $response->ack = true;
            $response->return_number = $returndata;
            $response->afterSalesOrders = '数据同步成功';
            $this->sendResponse('200', $response);
        }
    }

    /**
     * @author alpha
     * @desc 海外仓接受erp返回的rma
     */
    public function getRma() {
        $params = file_get_contents("php://input");
        $paramsarray = json_decode($params, true);
        $returndata = [];
        if (empty($paramsarray)) {
            $response = new \stdClass();
            $response->ack = false;
            $response->return_number = json_encode($returndata);
            $response->afterSalesOrders = '参数错误';
            $this->sendResponse('200', $response);
        }
        foreach ($paramsarray as $k => $list) {
            if (empty($list['order_id'])) {
                continue;
            }
            if (empty($list['rma'])) {
                continue;
            }
            $yb = AfterSalesReturn::findOne_ByOrderId(['order_id' => $list['order_id']]);
            if (empty($yb)) {
                continue;
            }
            $yb->rma = $list['rma'];
            $yb->receiving_time = date('Y-m-d H:i:s');
            if ($yb->save()) {
                $returndata[$k]['id'] = $list['id'];
                $returndata[$k]['order_id'] = $list['order_id'];
            }
        }
        if (empty($returndata)) {
            $response = new \stdClass();
            $response->ack = false;
            $response->msg = '接受rma失败';
            $this->sendResponse('200', $response);
        } else {
            $response = new \stdClass();
            $response->ack = true;
            $response->msg = '接受rma成功,订单号;' . json_encode($returndata);
            $response->return_number = $returndata;
            $this->sendResponse('200', $response);
        }
    }

    /**
     * @author alpha
     * @desc erp 接受是否收到货
     */
    public function isReceive() {
        $params = file_get_contents("php://input");
//        $params='[{"order_id":"EB180625002556","receipt":2,"id":"51"},{"order_id":"EB180717010213","receipt":1,"id":"38"}]';
        $paramsarray = json_decode($params, true);
        $returndata = [];
        if (empty($paramsarray)) {
            $response = new \stdClass();
            $response->ack = false;
            $response->return_number = json_encode($returndata);
            $response->afterSalesOrders = '参数错误';
            $this->sendResponse('200', $response);
        }
        foreach ($paramsarray as $k => $list) {
            if (empty($list['order_id'])) {
                continue;
            }
            if (empty($list['receipt'])) {
                continue;
            }
            $yb = AfterSalesReturn::findOne_ByOrderId(['order_id' => $list['order_id']]);
            if (empty($yb)) {
                continue;
            }
            $yb->is_receive = $list['receipt'];
            $yb->receiving_time = date('Y-m-d H:i:s');
            if ($yb->save()) {
                $returndata[$k]['id'] = $list['id'];
                $returndata[$k]['order_id'] = $list['order_id'];
            }
        }
        if (empty($returndata)) {
            $response = new \stdClass();
            $response->ack = false;
            $response->msg = '接受erp收货状态失败';
            $this->sendResponse('200', $response);
        } else {
            $response = new \stdClass();
            $response->ack = true;
            $response->msg = '接受erp收货状态成功,订单号;' . json_encode($returndata);
            $response->return_number = $returndata;
            $this->sendResponse('200', $response);
        }
    }

}
