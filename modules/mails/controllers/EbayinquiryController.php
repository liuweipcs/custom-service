<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/17 0017
 * Time: 上午 10:32
 */

namespace app\modules\mails\controllers;

use app\common\VHelper;
use app\components\Controller;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayInquiryHistory;
use app\modules\mails\models\EbayInquiryResponse;
use app\modules\orders\models\Logistic;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderEbay;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\OrderRemarkKefu;
use app\modules\orders\models\Warehouse;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\AutoCode;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Country;
use app\modules\systems\models\ErpProductApi;
use app\modules\systems\models\Transactions;
use yii\helpers\Json;
use PhpImap\Exception;
use app\modules\accounts\models\Account;
use app\modules\aftersales\models\AfterSalesProduct;
use app\modules\aftersales\models\RefundReturnReason;
use Yii;
use app\modules\systems\models\BasicConfig;
use app\modules\accounts\models\UserAccount;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\reports\models\DisputeStatistics;
use app\modules\orders\models\EbayOnlineListing;
use app\modules\users\models\UserRole;

class EbayinquiryController extends Controller
{
    public function actionIndex()
    {
        $model        = new EbayInquiry();
        $params       = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('index', [
            'model'        => $model,
            'dataProvider' => $dataProvider,
        ]);
    }


    public function actionHandle()
    {
        $this->isPopup = true;
        $id            = $this->request->get('id');
        $isout         = $this->request->get('isout');  // 如果是其他地方调用此接口，返回的时候直接刷新页面
//        findClass(Order::getOrderStackByTransactionId('EB', 1444056224010),1);
        if (is_numeric($id) && $id > 0 && $id % 1 === 0) {
            $inquireModel = EbayInquiry::findOne((int)$id);

            $ebayAccountModel = Account::findById((int)$inquireModel->account_id);
            $accountName      = $ebayAccountModel->account_name;

            //获取退款原因
            $returnReason = RefundReturnReason::getList('Array');

            if ($data = $this->request->post('EbayInquiryResponse')) {
                $flag       = true;
                $autoRefund = $this->request->post('EbayInquiry')['auto_refund'];
                $order_id   = $this->request->post('order_id');
                if (!isset($data['type'])) {
                    $flag      = false;
                    $errorInfo = '请选择任一种处理方式。';
                }
                if ($flag) {
                    if (in_array($autoRefund, array(0, 1, 2))) {
                        if ($autoRefund != 2 && $inquireModel->auto_refund != 2) {
                            $inquireModel->auto_refund = $autoRefund;
                        }
                    } else {
                        $flag      = false;
                        $errorInfo = '自动退款设置错误。';
                    }
                }

//                $ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
                $inquireModel->ebayAccountModel = $ebayAccountModel;
                if ($data['type'] == 2) {
                    if (empty($data['department_id'])) {
                        $this->_showMessage('请选择责任归属部门!', false);
                    }
                    if (empty($data['reason_code'])) {
                        $this->_showMessage('请选择退款原因!', false);
                    }
                }

                //处理翻译 add by allen <2018-1-11> str
                if ($data['type'] == 1) {
                    $message    = $data['content'][1];
                    $message_en = $data['content']['1_en'];
                    if ($message == "" && $message_en == "") {
                        $this->_showMessage('留言内容不能为空!', false);
                    } else {
                        if ($message_en) {
                            $content = $message_en;
                        } else {
                            $content = $message;
                        }
                    }
                } else {
                    $content = $data['content'][$data['type']];

                }
                //处理翻译 add by allen <2018-1-11> end

                $responseModel             = new EbayInquiryResponse();
                $responseModel->inquiry_id = $inquireModel->inquiry_id;
                $responseModel->type       = $data['type'];
                $responseModel->content    = $content;
                $responseModel->account_id = $inquireModel->account_id;

                $transfer_ip = include Yii::getAlias('@app') . '/config/transfer_ip.php';
                $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
                if (empty($transfer_ip)) {
                    $this->_showMessage('中转站配置错误', false);
                }
                set_time_limit(120);
                try {
                    switch ($responseModel->type) {
                        case '1':
                            $serverUrl = 'https://api.ebay.com/post-order/v2/inquiry/' . $responseModel->inquiry_id . '/send_message';
                            $data1     = json_encode(['message' => ['content' => $responseModel->content]]);
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => $data1, 'method' => 'post', 'responseHeader' => true, 'urlParams' => ''];
                            $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();
//                            $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$responseModel->inquiry_id.'/send_message','post');
//                            if(!empty($responseModel->content))
//                                $api->setData(['message'=>['content'=>$responseModel->content]]);
//                            $api->responseHeader = true;
//                            $response = $api->sendHttpRequest('json');
                            if (empty($response)) {
                                $responseModel->status = 0;
                                $responseModel->error  = '无返回值';
                            } else {
//                                if(in_array($api->getHttpCode(),[200,201,202]))
                                if (in_array($response->code, [200, 201, 202])) {
                                    $responseModel->status = 1;
                                } else {
                                    $responseModel->status = 0;
                                    $responseModel->error  = $response->response;
                                }
                            }
                            break;
                        case '2':
                            if (empty($inquireModel->platform_order_id)) {
                                $flag      = false;
                                $errorInfo = '无平台订单号，不能退款。';
                                break;
                            }

                            $serverUrl = 'https://api.ebay.com/post-order/v2/inquiry/' . $responseModel->inquiry_id . '/issue_refund';
                            $data1     = json_encode(['comments' => ['content' => $responseModel->content]]);
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => $data1, 'method' => 'post', 'responseHeader' => false, 'urlParams' => ''];
                            $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();

//                            $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$responseModel->inquiry_id.'/issue_refund','post');
////                            if(!empty($responseModel->content))
//                            $api->setData(['comments'=>['content'=>$responseModel->content]]);
//                            $response = $api->sendHttpRequest();
                            if (empty($response)) {
                                $responseModel->status = 0;
                                $responseModel->error  = '无返回值';
                            } else {
//                                if(in_array($api->getHttpCode(),[200,201,202]))
                                if (in_array($response->code, [200, 201, 202])) {
                                    $response                     = json_decode($response->response);
                                    $responseModel->status        = 1;
                                    $responseModel->error         = '';
                                    $responseModel->refund_source = $response->refundResult->refundSource;
                                    $responseModel->refund_status = $response->refundResult->refundStatus;
//                                    $inquireModel->auto_refund = 2;

                                    //update by allen <2018-03-17> 退款成功后创建售后单
                                    if ($response->refundResult->refundStatus == 'SUCCESS') {
                                        $afterSalesOrderModel                 = new AfterSalesOrder();
                                        $afterSalesOrderModel->buyer_id       = $inquireModel->buyer;
                                        $afterSalesOrderModel->account_name   = $accountName;
                                        $afterSalesOrderModel->approver       = \Yii::$app->user->identity->login_name;
                                        $afterSalesOrderModel->approve_time   = date('Y-m-d H:i:s');
                                        $afterSalesOrderModel->department_id  = $data['department_id'];
                                        $afterSalesOrderModel->reason_id      = $data['reason_code'];
                                        $afterSalesOrderModel->after_sale_id  = AutoCode::getCode('after_sales_order');
                                        $afterSalesOrderModel->transaction_id = $inquireModel->transaction_id;
                                        $afterSalesOrderModel->order_id       = $order_id;
                                        $afterSalesOrderModel->type           = AfterSalesOrder::ORDER_TYPE_REFUND;
                                        if ($responseModel->refund_status == 'PENDING' || $responseModel->refund_status == 'OTHER') {
                                            $afterSalesOrderModel->remark = $responseModel->refund_status;
                                        }
                                        $afterSalesOrderModel->status        = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                                        $afterSalesOrderModel->platform_code = Platform::PLATFORM_CODE_EB;
                                        $afterSalesOrderModel->account_id    = $inquireModel->account_id;

                                        $afterSaleOrderRefund                 = new AfterSalesRefund();
                                        $afterSaleOrderRefund->refund_type    = AfterSalesRefund::REFUND_TYPE_FULL;
                                        $afterSaleOrderRefund->refund_amount  = $inquireModel->total_amount;
                                        $afterSaleOrderRefund->currency       = $inquireModel->currency;
                                        $afterSaleOrderRefund->message        = $responseModel->content;
                                        $afterSaleOrderRefund->transaction_id = $inquireModel->transaction_id;
                                        $afterSaleOrderRefund->order_id       = $order_id;
                                        $afterSaleOrderRefund->platform_code  = Platform::PLATFORM_CODE_EB;
                                        $afterSaleOrderRefund->order_amount   = $inquireModel->total_amount;

                                        $afterSaleOrderRefund->reason_code   = $data['reason_code'];
                                        $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                                        switch ($responseModel->refund_status) {
                                            case 'FAILED':
                                                $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                                                break;
                                            case 'PENDING':
                                                $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_ING;
                                                break;
                                            case 'SUCCESS':
                                                $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                                                break;
                                        }
                                        $afterSaleOrderRefund->fail_count = 88;
                                    } else {
                                        $responseModel->status = 0;
                                        $responseModel->error  = $response->refundResult->refundStatus;
                                    }
                                } else {
                                    $responseModel->status = 0;
                                    $responseModel->error  = $response->response;
                                }
                            }
                            break;
                        case '3':
                            $responseModel->shipping_carrier_name = $data['shipping_carrier_name'];
                            $responseModel->shipping_date         = $data['shipping_date'];
                            $responseModel->tracking_number       = $data['tracking_number'];
//                            $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$responseModel->inquiry_id.'/provide_shipment_info','post');
                            $data_new['sellerComments']      = ['content' => $responseModel->content];
                            $data_new['shippingCarrierName'] = $responseModel->shipping_carrier_name;
//                            $data_new['shippingDate'] = ['value'=>$responseModel->shipping_date];
                            $data_new['trackingNumber'] = $responseModel->tracking_number;

                            $time_value               = str_replace('+00:00', 'Z', gmdate('c', strtotime($data['shipping_date'])));
                            $data_new['shippingDate'] = ['value' => $time_value];


                            $serverUrl = 'https://api.ebay.com/post-order/v2/inquiry/' . $responseModel->inquiry_id . '/provide_shipment_info';
                            $data1     = json_encode($data_new);
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => $data1, 'method' => 'post', 'responseHeader' => true, 'urlParams' => ''];
                            $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();

//                            $api->setData($data_new);
//                            $api->responseHeader = true;
//                            $response = $api->sendHttpRequest();
                            // 本接口无response返回，只有状态
//                            if(empty($response))
//                            {
//                                $responseModel->status = 0;
//                                $responseModel->error = '无返回值';
//                            }
//                            else
//                            {
                            if (in_array($response->code, [200, 201, 202])) {
                                $responseModel->status = 1;
                            } else {
                                $responseModel->status = 0;
                                $responseModel->error  = $response->response;

                            }
//                            }
                            break;
                        case '4':
                            $responseModel->escalation_reason = $data['escalation_reason'];
//                            $api = new PostOrderAPI($ebayAccountModel->user_token,'','https://api.ebay.com/post-order/v2/inquiry/'.$responseModel->inquiry_id.'/escalate','post');
                            $data1['comments']         = ['content' => $responseModel->content];
                            $data1['escalationReason'] = EbayInquiryResponse::$escalationReasonMap[$responseModel->escalation_reason];

                            $serverUrl = 'https://api.ebay.com/post-order/v2/inquiry/' . $responseModel->inquiry_id . '/escalate';
                            $data1     = json_encode($data1);
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => $data1, 'method' => 'post', 'responseHeader' => true, 'urlParams' => ''];
                            $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();
//                            $api->setData($data);
//                            $api->responseHeader = true;
//                            $response = $api->sendHttpRequest();
                            if (empty($response)) {
                                $responseModel->status = 0;
                                $responseModel->error  = '无返回值';
                            } else {
                                if (in_array($response->code, [200, 201, 202])) {
                                    $responseModel->status = 1;
                                } else {
                                    $responseModel->status = 0;
                                    $responseModel->error  = $response->response;

                                }
                            }
                            break;
                        default:
                            $flag      = false;
                            $errorInfo = '请选择任一种处理方式。';
                    }
                } catch (Exception $e) {
                    $flag      = false;
                    $errorInfo = $e->getMessage();
                }
                if ($flag) {
                    $transaction = EbayInquiryResponse::getDb()->beginTransaction();
                    try {
                        $flag = $responseModel->save();
                        if (!$flag) {
                            $errorInfo = VHelper::getModelErrors($responseModel);
                        } elseif (isset($afterSalesOrderModel)) {
                            //先关闭纠纷退款自动创建售后单
                            $flag = $afterSalesOrderModel->save();
                            if (!$flag) {
                                $errorInfo = VHelper::getModelErrors($afterSalesOrderModel);
                            }
                            if ($flag && isset($afterSaleOrderRefund)) {
                                $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                                $flag                                = $afterSaleOrderRefund->save();
                                if (!$flag) {
                                    $errorInfo = VHelper::getModelErrors($afterSaleOrderRefund);
                                } else {
                                    // 成功 推送erp修改订单退款状态
                                    $afterSaleOrderRefund->audit($afterSalesOrderModel);
                                }
                            }
                        }

                        //如果是全额退款，需要添加问题产品数据表
                        if ($flag && $data['type'] == 2) {
                            //获取订单信息
                            $orderinfo = array();
                            if (!empty($inquireModel->platform_order_id)) {
                                $orderinfo = Order::getOrderStack(Platform::PLATFORM_CODE_EB, $inquireModel->platform_order_id);
                            } else {
                                $orderinfo = Order::getOrderStack(Platform::PLATFORM_CODE_EB, $inquireModel->item_id . '-' . $inquireModel->transaction_id);
                            }

                            if (!empty($orderinfo) && isset($afterSalesOrderModel)) {
                                $orderinfo = Json::decode(Json::encode($orderinfo), true);
                                //循环的把问题产品信息插入数据库
                                foreach ($orderinfo['product'] as $item) {
                                    //针对未收到的item_id保存问题产品
                                    if ($item['item_id'] == $inquireModel->item_id) {
                                        $afterSaleProduct                   = new AfterSalesProduct();
                                        $afterSaleProduct->platform_code    = Platform::PLATFORM_CODE_EB;
                                        $afterSaleProduct->order_id         = $order_id;
                                        $afterSaleProduct->sku              = $item['sku'];
                                        $afterSaleProduct->product_title    = $item['picking_name'];
                                        $afterSaleProduct->quantity         = $item['quantity'];
                                        $afterSaleProduct->linelist_cn_name = $item['linelist_cn_name'];
                                        $afterSaleProduct->issue_quantity   = $item['quantity'];
                                        $afterSaleProduct->reason_id        = $data['reason_code'];
                                        $afterSaleProduct->after_sale_id    = $afterSalesOrderModel->after_sale_id;
                                        //添加问题产品数据
                                        if (!$afterSaleProduct->save()) {
                                            $errorInfo = VHelper::getModelErrors($afterSaleProduct);
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $flag      = false;
                        $errorInfo = $e->getMessage();
                    }
                    if ($flag) {
                        $transaction->commit();
                    } else {
                        $transaction->rollBack();
                    }
                }

                //如果发送失败，而$flag为true时，修改$flag的布尔值
                if ($flag) {
                    $flag      = (bool)$responseModel->status;
                    $errorInfo = $responseModel->error;
                }

                if ($flag) {
                    $inquireModel->is_deal = 0;
                    if (!$inquireModel->save())
                        $this->_showMessage('修改纠纷状态出错。', false);

                    //更新此条inquire信息。
                    VHelper::throwTheader('/services/ebay/inquiry/refresh', ['id' => $id]);

                    //处理未收到物品纠纷
                    $disputeStatistics = DisputeStatistics::findAll(['dispute_id' => $inquireModel->inquiry_id, 'type' => AccountTaskQueue::TASK_TYPE_INQUIRY, 'platform_code' => Platform::PLATFORM_CODE_EB]);
                    if ($disputeStatistics) {
                        foreach ($disputeStatistics as $statistics) {
                            if ($statistics->status == 0) {
                                $statistics->status = 1;
                                $statistics->reply  = Yii::$app->user->identity->user_name;
                                $statistics->save(false);
                            }
                        }
                    }
                    if ($isout) {
                        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true);
                    } else {
                        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                            'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayinquiry/index') . '");', true, 'msg');
                    }
                } else {
                    $this->_showMessage(\Yii::t('system', 'Operate Failed') . '。' . $errorInfo, false);
                }
            }

            //$orderinfo = [];
            //if (!empty($inquireModel->transaction_id)) {
            //    $orderinfo = Order::getOrderStackByTransactionId('EB', $inquireModel->transaction_id);
            //} else {
            //    $old_account_id = Account::findOne($inquireModel->account_id)->old_account_id;
            //    $orderinfo = Order::getEbayOrderStack($old_account_id, $inquireModel->buyer, $inquireModel->item_id, $inquireModel->transaction_id);
            //}

            //获取订单信息
            $orderinfo = [];
            if (!empty($inquireModel->platform_order_id)) {
                $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_EB, $inquireModel->platform_order_id);
            } else {
                $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_EB, $inquireModel->item_id . '-' . $inquireModel->transaction_id);
            }
            if (empty($orderinfo) && !empty($inquireModel->transaction_id)) {
                $orderinfo = OrderKefu::getOrderStackByTransactionId(Platform::PLATFORM_CODE_EB, $inquireModel->transaction_id);
            }

            if (!empty($orderinfo)) {
                $orderinfo = Json::decode(Json::encode($orderinfo), true);
            } else {
                $orderinfo = [];
            }

            if (empty($orderinfo)) {
                $this->_showMessage('订单信息不存在，请稍候再试！', false);
            }

            //组装库存和在途数
            if (!empty($orderinfo)) {
                if (!empty($orderinfo['product'])) {
                    $result = isset($orderinfo['wareh_logistics']) && isset($orderinfo['wareh_logistics']['warehouse']);
                    foreach ($orderinfo['product'] as $key => $value) {
                        //获取item location
                        $orderinfo['product'][$key]['location'] = EbayOnlineListing::getItemLocation($value['item_id']);
                        list($stock, $on_way_count) = [null, null];
                        if ($result) {
//                            $data = VHelper::getProductStockAndOnCount($value['sku'], $orderinfo['wareh_logistics']['warehouse']['warehouse_code']);
                            $data         = [];
                            $stock        = isset($data['available_stock']) ? $data['available_stock'] : 0;
                            $on_way_count = isset($data['on_way_stock']) ? $data['on_way_stock'] : 0;
                        }
                        $orderinfo['product'][$key]['stock']        = $stock;
                        $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;

                        // 查询订单评价
                        $transactionId      = !empty($value['transaction_id']) ? $value['transaction_id'] : 0;
                        $order_line_item_id = $value['item_id'] . '-' . $transactionId;
                        $feedbackInfo       = EbayFeedback::find()->select('id,feedback_id,comment_type')->where(['role' => 1, 'order_line_item_id' => $order_line_item_id])->asArray()->one();
                        if (!empty($feedbackInfo)) {
                            $orderinfo['product'][$key]['feed_table_id'] = $feedbackInfo['id'];
                            $orderinfo['product'][$key]['feedback_id']   = $feedbackInfo['feedback_id'];
                            $orderinfo['product'][$key]['comment_type']  = $feedbackInfo['comment_type'];
                        }
                    }
                }
                //付款帐号与收款帐号
                /*if(!empty($orderinfo['trade'])){
                    foreach ($orderinfo['trade'] as $key => $value) {
                        $transactionId =$value['transaction_id'];
                        $PayMessage =VHelper::getTransactionAccount($transactionId);
                        //var_dump($PayMessage);exit;
                        if(!empty($PayMessage) && isset($PayMessage[0])){
                            $orderinfo['trade'][$key]['receiver_business'] = $PayMessage[0]['receiver_business'];
                            $orderinfo['trade'][$key]['payer_email'] =$PayMessage[0]['payer_email'];
                            $orderinfo['trade'][$key]['fee_amt'] =$PayMessage[0]['fee_amt'];
                            $orderinfo['trade'][$key]['amt'] =$PayMessage[0]['amt'];
                            $orderinfo['trade'][$key]['payment_status'] =$PayMessage[0]['payment_status'];
                            $orderinfo['trade'][$key]['order_pay_time'] =$PayMessage[0]['order_time'];
                            $orderinfo['trade'][$key]['currency'] =$PayMessage[0]['currency'];
                        }else{
                            $orderinfo['trade'][$key]['receiver_business'] = "-";
                            $orderinfo['trade'][$key]['payer_email'] = "-";
                            $orderinfo['trade'][$key]['fee_amt'] = "-";
                            $orderinfo['trade'][$key]['amt'] = "-";
                            $orderinfo['trade'][$key]['payment_status'] = "-";
                            $orderinfo['trade'][$key]['order_pay_time'] = "-";
                        }
                    }
                }*/
            }
            $detailModel    = EbayInquiryHistory::find()->where(['inquiry_id' => $inquireModel->inquiry_id])->orderBy('date DESC')->all();
            $googleLangCode = VHelper::googleLangCode();
            @$afterSalesOrders = AfterSalesOrder::getByOrderId('EB', $orderinfo['info']['order_id']);

//            if(in_array(Yii::$app->user->identity->login_name,['何贞','胡丽玲','汪成荣','卢乐斯','方超'])){
            //加黑名单解决方案  部门主管及以上
            $isAuthority = false;
            if (UserRole::checkManage(Yii::$app->user->identity->id)) {
                $isAuthority = true;
            }

            $departmentList     = BasicConfig::getParentList(52);
            $departmentList_new = [];

            foreach ($departmentList as $k => &$v) {
                $departmentList_new[$k]['depart_id']   = $k;
                $departmentList_new[$k]['depart_name'] = $v;
            }
            return $this->render('handles/index', [
                'order_id'         => $inquireModel->transaction_id,
                'info'             => $orderinfo,
                'model'            => $inquireModel,
                'detailModel'      => $detailModel,
                'accountName'      => $accountName,
                'reasonCode'       => $returnReason,
                'googleLangCode'   => $googleLangCode,
                'afterSalesOrders' => $afterSalesOrders,
                'isAuthority'      => $isAuthority,
                'departmentList'   => json_encode($departmentList_new)//$departmentList
            ]);
//            }else{
//                return $this->render('handle/index', [
//                    'order_id' => $inquireModel->transaction_id,
//                    'info'=>$orderinfo,
//                    'model'=>$inquireModel,
//                    'detailModel' => $detailModel,
//                    'accountName' => $accountName,
//                    'reasonCode' =>$returnReason,
//                    'googleLangCode'=>$googleLangCode,
//                ]);
//            }
        }
    }


    public function actionRefresh()
    {
        $id = $this->request->get('id');
        if (is_numeric($id) && $id > 0 && $id % 1 === 0) {
            $result = EbayInquiry::findOne((int)$id)->refreshApi();
            if ($result['flag']) {
                $this->_showMessage('更新成功。', true, null, false, null,
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayinquiry/index') . '");', true, 'msg');
            } else {
                $this->_showMessage('更新失败。' . $result['info'], false);
            }
        }
    }

    public function actionBatchrefresh()
    {
        $ids        = $this->request->post('ids');
        $inquiryids = '';
        foreach ($ids as $key => $id) {
            $result = EbayInquiry::findOne((int)$id)->refreshApi();
            if ($result['flag']) {
                continue;
            } else {
                $inquiryids .= EbayInquiry::getInqueryByID($id) . ',';
            }
        }
        if ($inquiryids) {
            $inquiryids = trim($inquiryids, ',');
            $this->_showMessage('部分数据更新失败，inquiry_id如下所示：' . $inquiryids, true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayinquiry/index') . '");', true, 'msg');
        } else {
            $this->_showMessage('更新成功。', true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayinquiry/index') . '");');
        }

    }

    /**
     * @desc 导出
     */
    public function actionToexcel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        //获取get参数
        $get  = YII::$app->request->get();
        $ids  = !empty($get['ids']) ? $get['ids'] : [];
        $data = [];

        //只能查询到客服绑定账号的
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

        if (is_array($ids) && !empty($ids)) {
            //取出选中的数据
            $data = EbayInquiry::find()
                ->select('*')
                ->andWhere(['in', 'id', $ids])
                ->andWhere(['in', 'account_id', $accountIds])
                ->asArray()
                ->all();
        } else {
            //取出筛选的评价数据
            $query = EbayInquiry::find()
                ->select('*')
                ->andWhere(['in', 'account_id', $accountIds]);

            //添加表单的筛选条件
            if (!empty($get['inquiry_id'])) {
                $query->andWhere(['inquiry_id' => $get['inquiry_id']]);
            }
            if (!empty($get['platform_order_id'])) {
                $query->andWhere(['platform_order_id' => $get['platform_order_id']]);
            }
            if (!empty($get['item_id'])) {
                $query->andWhere(['item_id' => $get['item_id']]);
            }
            if (!empty($get['account_id'])) {
                $query->andWhere(['account_id' => $get['account_id']]);
            }
            if (!empty($get['buyer'])) {
                $query->andWhere(['buyer' => $get['buyer']]);
            }
            if (!empty($get['sku'])) {
                //通过sku查询item_id
                $itemIds = Order::getEbayFeedBackItemIdBySku([
                    'sku' => $get['sku'],
                ]);

                if (!empty($itemIds)) {
                    $query->andWhere(['in', 'item_id', $itemIds]);
                }
            }
            if (isset($get['status']) && $get['status'] != '') {
                switch ($get['status']) {
                    case 'wait_seller':
                        $query->where('status in ("OPEN","PENDING","WAITING_SELLER_RESPONSE")');
                        break;
                    case 'closed':
                        $query->where('status in ("CLOSED","CLOSED_WITH_ESCALATION","CS_CLOSED")');
                        break;
                    case 'other':
                        $query->where('status in ("OTHER","WAITING_BUYER_RESPONSE")');
                        break;
                }
            }
            if (!empty($get['state'])) {
                $query->andWhere(['state' => $get['state']]);
            }
            if (isset($get['is_deal']) && $get['is_deal'] != '') {
                $query->andWhere(['is_deal' => $get['is_deal']]);
            }
            if (!empty($get['start_time']) && !empty($get['end_time'])) {
                $query->andWhere(['between', 'creation_date', $get['start_time'], $get['end_time']]);
            } else if (!empty($get['start_time'])) {
                $query->andWhere(['>=', 'creation_date', $get['start_time']]);
            } else if (!empty($get['end_time'])) {
                $query->andWhere(['<=', 'creation_date', $get['end_time']]);
            }

            $data = $query->asArray()->all();
        }

        if (empty($data)) {
            $this->_showMessage('数据为空', false);
        }

        $itemIds          = [];
        $transactionIds   = [];
        $platformOrderIds = [];

        if (!empty($data)) {
            foreach ($data as $item) {
                $itemIds[]          = $item['item_id'];
                $transactionIds[]   = $item['transaction_id'];
                $platformOrderIds[] = $item['platform_order_id'];
            }

            $itemIds          = array_unique($itemIds);
            $transactionIds   = array_unique($transactionIds);
            $platformOrderIds = array_unique($platformOrderIds);
        }

        //获取订单信息
        $result = Order::getEbayOrderInfos([
            'platformCode'     => Platform::PLATFORM_CODE_EB,
            'platformOrderIds' => implode(',', $platformOrderIds),
            'transactionIds'   => implode(',', $transactionIds),
            'itemIds'          => implode(',', $itemIds),
        ]);

        $orders = !empty($result['order']) ? $result['order'] : [];
        $trans  = !empty($result['trans']) ? $result['trans'] : [];

        //获取paypal收款地址
        $receiverModel = new ErpProductApi();
        $paypalAddrs   = $receiverModel->getProductPaypals(['itemIds' => implode(',', $itemIds)]);
        $paypalAddrs   = !empty($paypalAddrs) ? json_decode(json_encode($paypalAddrs->datas), true) : array();

        //获取订单完成状态
        $order_status_map = Order::getOrderCompleteStatus();
        //获取账号信息
        $accountInfos = Account::find()->select('*')->where(['platform_code' => Platform::PLATFORM_CODE_EB])->asArray()->all();
        //获取账号名称
        $accountNames = array_column($accountInfos, 'account_name', 'id');
        //获取账号短名称
        $accountShortNames = array_column($accountInfos, 'account_short_name', 'id');
        //获取所有发货仓库信息
        $warehouseList = Warehouse::getWarehouseList();
        //获取所有邮寄方式信息
        $logistics             = json_decode(json_encode(Logistic::getAllStatusLogistics()), true);
        $platform_order_id_arr = [];
        foreach ($data as $key => $model) {
            $platform_order_id_arr[]    = $model['platform_order_id'];
            $data[$key]['account_name'] = isset($accountNames[$model['account_id']]) ? $accountNames[$model['account_id']] : '';
            $accountShortName           = isset($accountShortNames[$model['account_id']]) ? $accountShortNames[$model['account_id']] : '';
            if (!empty($model['order_id'])) {
                $data[$key]['orientation_order_id'] = isset($accountShortName) ? $accountShortName . '--' . $model['order_id'] : $model['order_id'];
            }

            $order_info = [];
            if (array_key_exists($model['platform_order_id'], $orders)) {
                $order_info = $orders[$model['platform_order_id']];
            } else if (array_key_exists($model['transaction_id'], $trans)) {
                $order_info = $trans[$model['transaction_id']];
            } else if (array_key_exists($model['item_id'] . '-' . $model['transaction_id'], $orders)) {
                $order_info = $orders[$model['item_id'] . '-' . $model['transaction_id']];
            }

            //如果$order_info还是为空，则通过ERP接口获取
            if (empty($order_info)) {
                if (!empty($model['transaction_id'])) {
                    $order_info = Order::getOrderStackByTransactionId(Platform::PLATFORM_CODE_EB, $model['transaction_id']);
                } else if (!empty($model['order_id'])) {
                    $order_info = Order::getOrderStackByOrderId(Platform::PLATFORM_CODE_EB, '', $model['order_id']);
                }
                if (!empty($order_info)) {
                    $tmp        = $order_info->info;
                    $tmp->trade = $order_info->trade;
                    if (!empty($order_info->product)) {
                        foreach ($order_info->product as $product) {
                            if ($product->transaction_id == $model['transaction_id']) {
                                $tmp->sku          = $product->sku;
                                $tmp->quantity     = $product->quantity;
                                $tmp->picking_name = $product->picking_name;
                            }
                        }
                    }
                    //这里转成数组，从ERP接口获取的是对象
                    $order_info = json_decode(json_encode($tmp), true);
                }
            }

            if (!empty($order_info)) {
                if (empty($data[$key]['orientation_order_id'])) {
                    $data[$key]['orientation_order_id'] = isset($accountShortName) ? $accountShortName . '--' . $order_info['order_id'] : $order_info['order_id'];
                }
                $data[$key]['paytime']           = $order_info['paytime'];
                $data[$key]['ship_country_name'] = $order_info['ship_country_name'];
                $data[$key]['complete_status']   = array_key_exists($order_info['complete_status'], $order_status_map) ? $order_status_map[$order_info['complete_status']] : '';
                $data[$key]['complete_status']   = strip_tags($data[$key]['complete_status']);
                $data[$key]['shipped_date']      = $order_info['shipped_date'];
                $data[$key]['warehouse_name']    = array_key_exists($order_info['warehouse_id'], $warehouseList) ? $warehouseList[$order_info['warehouse_id']] : '';
                $data[$key]['ship_code_name']    = array_key_exists($order_info['ship_code'], $logistics) ? $logistics[$order_info['ship_code']] : '';
                $data[$key]['sku']               = $order_info['sku'];
                $data[$key]['quantity']          = $order_info['quantity'];
                $data[$key]['picking_name']      = $order_info['picking_name'];

                $data[$key]['paypal_trans_id'] = '';
                $data[$key]['trans_currency']  = '';
                $data[$key]['amt']             = '';
                $data[$key]['paypal']          = '';

                if (!empty($order_info['trade'])) {
                    $paypal_trans_id = '';
                    foreach ($order_info['trade'] as $trade) {
                        if ($trade['amt'] > 0) {
                            $paypal_trans_id               = $trade['transaction_id'];
                            $data[$key]['paypal_trans_id'] = $paypal_trans_id;
                            $data[$key]['currency']        = $trade['currency'];
                            $data[$key]['amt']             = $trade['amt'];
                        }
                    }
                    $transactionModel = Transactions::findOne('transaction_id = "' . $paypal_trans_id . '"');
                    if (!empty($transactionModel)) {
                        $data[$key]['paypal'] = $transactionModel->receive_email;
                    } else {
                        if (array_key_exists($model['item_id'], $paypalAddrs)) {
                            $data[$key]['paypal'] = $paypalAddrs[$model['item_id']];
                        }
                    }
                }
            } else {
                $data[$key]['paytime']           = '';
                $data[$key]['ship_country_name'] = '';
                $data[$key]['complete_status']   = '';
                $data[$key]['shipped_date']      = '';
                $data[$key]['warehouse_name']    = '';
                $data[$key]['ship_code_name']    = '';
                $data[$key]['paypal']            = '';
                $data[$key]['paypal_trans_id']   = '';
                $data[$key]['currency']          = '';
                $data[$key]['amt']               = '';
                $data[$key]['sku']               = '';
                $data[$key]['quantity']          = '';
                $data[$key]['picking_name']      = '';
            }
        }
        $extra_info    = OrderEbay::getExtraInfo($platform_order_id_arr);
        $warehouseList = Warehouse::getAllWarehouseList(true);
        $countryList   = Country::getCodeNamePairs('cn_name');
        $order_id_arr=[];
        //
        foreach ($data as $v1){
            $order_id_arr[]=$v1['order_id'];
        }
        $remarks='';
        $order_remark_arr = OrderRemarkKefu::getOrderRemarksByArray($order_id_arr);
        foreach ($data as &$v) {
            foreach ($order_remark_arr as $v2){
                if($v2['order_id']==$v['order_id']){
                    $remarks .=$v2['remark']."-";
                }
            }
            $v['remark']=rtrim($remarks,'-');

            foreach ($extra_info as $value) {
                if ($v['platform_order_id'] == $value['platform_order_id']) {
                    $v['warehouse_id'] = $value['warehouse_id'];
                    $v['ship_code']    = $value['ship_code'];
                    $v['ship_country'] = $value['ship_country'];
                    $v['shipped_date'] = $value['shipped_date'];
                    $v['location']     = $value['location'];
                    $v['warehouse']    = isset($v['warehouse_id']) && (int)$v['warehouse_id'] > 0 ? $warehouseList[$v['warehouse_id']] : null;  //发货仓库
                    $v['logistics']    = isset($v['ship_code']) ? Logistic::getSendGoodsWay($v['ship_code']) : null; //发货方式
                    $v['ship_country'] = $v['ship_country'] . (array_key_exists($v['ship_country'], $countryList)
                            ? '(' . $countryList[$v['ship_country']] . ')' : '');
                    $v['pay_time']     = $value['paytime'];

                }
            }

            if (empty($v['orientation_order_id'])) {
                $v['orientation_order_id'] = '';
            }
            if (empty($v['warehouse'])) {
                $v['warehouse'] = '';
            }
            if (empty($v['logistics'])) {
                $v['logistics'] = '';
            }
            if (empty($v['ship_country'])) {
                $v['ship_country'] = '';
            }
            if (empty($v['location'])) {
                $v['location'] = '';
            }
            if (empty($v['pay_time'])) {
                $v['pay_time'] = '';
            }
        }

        $data = json_decode(json_encode($data));

        $columns = ['account_name', 'orientation_order_id', 'paytime', 'buyer', 'ship_country_name', 'complete_status',
            'shipped_date', 'warehouse_name', 'ship_code_name', 'paypal', 'paypal_trans_id', 'currency', 'amt', 'sku',
            'quantity', 'picking_name', 'inquiry_id', 'creation_date', 'state', 'status', 'claim_amount', 'warehouse',
            'logistics', 'ship_country', 'location', 'pay_time','remark'];
        $headers = ['account_name' => '账号名', 'orientation_order_id' => '定位订单号', 'paytime' => '付款时间',
                    'buyer'        => '买家ID', 'ship_country_name' => '收件人国家', 'complete_status' => '订单状态',
                    'shipped_date' => '发货时间', 'warehouse_name' => '发货仓库', 'ship_code_name' => '邮寄方式',
                    'paypal'       => '收款PayPal帐号', 'paypal_trans_id' => '收款PayPal交易号', 'currency' => '收款币种',
                    'amt'          => '收款金额', 'sku' => 'SKU', 'quantity' => '数量', 'picking_name' => '中文名',
                    'inquiry_id'   => 'Inquiry Id', 'creation_date' => 'Inquiry创建时间', 'state' => '状况',
                    'status'       => '状态', 'claim_amount' => '涉及金额', 'warehouse' => '发货仓库', 'logistics' => '发货方式',
                    'ship_country' => '目的国', 'location' => 'Item Location', 'pay_time' => '付款时间','remark'=>'订单备注'];

        $fileName = 'inquiry_' . date('Y-m-d');

        \moonland\phpexcel\Excel::widget([
            'fileName' => $fileName,
            'models'   => $data,
            'mode'     => 'export', //default value as 'export'
            'columns'  => $columns, //without header working, because the header will be get label from attribute label.
            'headers'  => $headers,
        ]);
    }

    public function actionChangeautorefund()
    {
        $id          = $this->request->post('id');
        $auto_refund = $this->request->post('auto_refund');

        $ebayInquiry = EbayInquiry::find()->where(['id' => $id])->one();
        if (empty($ebayInquiry))
            echo json_encode(['status' => 'error', 'message' => '未找到该纠纷']);

        switch ($auto_refund) {
            case 1:
                $ebayInquiry->auto_refund = 0;
                break;
            case 0:
                $ebayInquiry->auto_refund = 1;
        }
        if ($ebayInquiry->save())
            echo json_encode(['status' => 'success', 'message' => '操作成功']);
        else
            echo json_encode(['status' => 'error', 'message' => '操作失败']);

    }
}