<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/17 0017
 * Time: 上午 11:47
 */

namespace app\modules\mails\controllers;

use app\common\VHelper;
use app\components\Controller;
use app\modules\aftersales\models\AfterSalesProduct;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\mails\models\EbayReturnsRequestsDetail;
use app\modules\mails\models\EbayReturnsRequestsResponse;
use app\modules\orders\models\Logistic;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\Warehouse;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\accounts\models\Account;
use app\modules\systems\models\ErpProductApi;
use app\modules\systems\models\Transactions;
use PhpImap\Exception;
use yii\helpers\Json;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\AutoCode;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\accounts\models\Platform;
use Yii;
use app\modules\systems\models\BasicConfig;
use app\modules\accounts\models\UserAccount;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\reports\models\DisputeStatistics;
use app\modules\orders\models\EbayOnlineListing;
use yii\web\UploadedFile;
use yii\helpers\Url;
use app\modules\users\models\UserRole;

class EbayreturnsrequestsController extends Controller {

    const XLS_UPLOAD_PATH = 'uploads/ebay_return_kefu/';
    const MAX_ISSUE_IMAGE_SIZE = 2097152;

    //public static $return_reason = array('FOUND_BETTER_PRICE', 'NO_LONGER_NEED_ITEM', 'NO_REASON', 'ORDERED_ACCIDENTALLY', 'ORDERED_WRONG_ITEM', 'WRONG_SIZE', 'BUYER_CANCEL_ORDER', 'EXPIRED_ITEM', 'OTHER', 'RETURNING_GIFT', 'BUYER_NO_SHOW', 'BUYER_NOT_SCHEDULED', 'BUYER_REFUSED_TO_PICKUP');

    public function actionIndex() {
        $model = new EbayReturnsRequests();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('index', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     *
     * @return string
     * @throws \yii\base\ExitException
     * @throws \yii\db\Exception
     */
    public function actionHandle() {
        $this->isPopup = true;
        $id = $this->request->get('id'); 
        $isout = $this->request->get('isout');  // 如果是其他地方调用此接口，返回的时候直接刷新页面
        $afterSalesOrderId = "";
        if (is_numeric($id) && $id > 0 && $id % 1 === 0) {
            //获取退货请求信息
            $returnModel = EbayReturnsRequests::findOne((int) $id);

            //根据交易id查询inbox信息
            $inbox_info = EbayInbox::findOne(['transaction_id' => $returnModel->transaction_id]);

            //获取退款原因
            $returnReason = RefundReturnReason::getList('Array');

            //获取账号信息
            $AccountModel = Account::findById((int) $returnModel->account_id);
            if (empty($AccountModel)) {
                echo '未查到ebay帐号信息，无法处理。';
                \Yii::$app->end();
            }
            //账号名称
            $accountName = $AccountModel->account_name;
            if ($data = $this->request->post('EbayReturnsRequestsResponse')) {
                //获取sku信息
                $sku_info = $this->request->post('EbayReturnsRequestsResponse')['sku_info'];

                /*  $flag     = true;
                  //自动退款
                  $autoRefund = $this->request->post('EbayReturnsRequests')['auto_refund']; */

                //订单ID
                $order_id = $this->request->post('order_id'); //当前订单号
                if (!isset($data['type'])) {
                    $flag = false;
                    $errorInfo = '请选择任一种处理方式。';
                }

                //如果是全额退款或部分退款  全额退款2 部分退款3
                if (in_array($data['type'], array(2, 3))) {
                    //设置退款原因code
                    $data['reason_code'] = isset($data['reason_code'][$data['type']]) ? $data['reason_code'][$data['type']] : '';
                    //设置责任归属部门ID
                    $data['department_id'] = isset($data['department_id'][$data['type']]) ? $data['department_id'][$data['type']] : '';
                    //原因备注
                    $data['remark'] = isset($data['remark'][$data['type']]) ? $data['remark'][$data['type']] : '';
                    if ($data['department_id'] == 55 && $data['reason_code'] == 74 && empty($data['remark'])) {
                        $this->_showMessage('当前原因条件下原因备注必填', false);
                    }

                    if (empty($sku_info)) {
                        $this->_showMessage('请选择需要退款的相关SKU信息', false);
                    }
                }
                //判断是否是标记退款4
                if ($data['type'] == 4) {
                    //给客户留言
                    $content = isset($data['content'][$data['type']]) ? $data['content'][$data['type']] : '';
                    if (!$content) {
                        $this->_showMessage('留言内容不能为空!', false);
                    }
                }



                //全额退款或部分退款，责任归属部门和退款原因必填
                if (in_array($data['type'], array(2, 3))) {
                    if (empty($data['department_id'])) {
                        $this->_showMessage('请选择责任归属部门', false);
                    }

                    if (empty($data['reason_code'])) {
                        $this->_showMessage('请选择退款原因', false);
                    }
                }

                /*        if ($flag) {
                  if (in_array($autoRefund, array(0, 1, 2))) {
                  if ($autoRefund != 2 && $returnModel->auto_refund != 2) {
                  $returnModel->auto_refund = $autoRefund;
                  }
                  } else {
                  $flag      = false;
                  $errorInfo = '自动退款设置错误。';
                  }
                  } */

                //发送留言，处理翻译
                if ($data['type'] == 1) {
                    $message = $data['content'][1];
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
                //退货请求答复
                $ebayAccountModel = $AccountModel;
                $responseModel = new EbayReturnsRequestsResponse();
                $responseModel->return_id = $returnModel->return_id;
                $responseModel->content = $content;
                $responseModel->refund_amount = $data['type'] == 1 ? 0 : $data['refund_amount'][$data['type']];
                $responseModel->ship_cost = $data['ship_cost'];
                $responseModel->subtotal_price = $data['subtotal_price'];
                $responseModel->currency = $data['currency'];
                $responseModel->type = $data['type'];
                $responseModel->account_id = $returnModel->account_id;
                $transfer_ip = include \Yii::getAlias('@app') . '/config/transfer_ip.php';
                $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
                if (empty($transfer_ip)) {
                    $this->_showMessage('中转站配置错误', false);
                }
                $flag = true;
                set_time_limit(120);       
                try {
                    switch ($responseModel->type) {
                        case '1':
                            $serverUrl = 'https://api.ebay.com/post-order/v2/return/' . $responseModel->return_id . '/send_message';
                            $data1 = json_encode(['message' => ['content' => $responseModel->content]]);
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => $data1, 'method' => 'post', 'responseHeader' => true, 'urlParams' => ''];
                            $api = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();
                            if (empty($response)) {
                                $flag = false;
                                $responseModel->status = 0;
                                $responseModel->error = '香港服务器无返回值';
                            } else {
                                if (in_array($response->code, [200, 201, 202, 204])) {
                                    $responseModel->status = 1;
                                } else {
                                    $flag = false;
                                    $responseModel->status = 0;
                                    $responseModel->error = $response->response;
                                }
                            }
                            break;
                        //标记退款
                        case '4':   
                            $serverUrl = 'https://api.ebay.com/post-order/v2/return/' . $responseModel->return_id . '/mark_refund_sent';
                            $data1 = [
                                'refundDetail' => ['itemizedRefundDetail' => [['refundAmount' => ['currency' => $responseModel->currency,'value' => $responseModel->subtotal_price], 'refundFeeType' => 'PURCHASE_PRICE','restockingFeePercentage'=>'0'],['refundAmount' => ['currency' => $responseModel->currency,'value' => $responseModel->ship_cost], 'refundFeeType' => 'ORIGINAL_SHIPPING','restockingFeePercentage'=>'0']], 'totalAmount' => ['currency' => $responseModel->currency, 'value' => $responseModel->refund_amount]
                                    ]]; 
                            $data1 = json_encode($data1); 
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => $data1, 'method' => 'post', 'responseHeader' => false, 'urlParams' => ''];
                            $api = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();   
                            if (empty($response)) {
                                $flag = false;
                                $responseModel->status = 0;
                                $responseModel->error = '香港服务器无返回值';
                            } else {
                                if (in_array($response->code, [200, 201, 202, 204])) {
                                    $responseModel->status = 1;
                                    $ebayrequest = EbayReturnsRequests::find()->where(['return_id' => $responseModel->return_id])->one();
                                    $ebayrequest->is_deal = 1;
                                    $ebayrequest->save();
                                } else {
                                    $flag = false;
                                    $responseModel->status = 0;
                                    $responseModel->error = $response->response;
                                }
                            }
                            break;
                        case '2':
                            $serverUrl = 'https://api.ebay.com/post-order/v2/return/' . $responseModel->return_id . '/issue_refund';
                            if ($responseModel->ship_cost > 0 && bccomp($responseModel->refund_amount, ($responseModel->subtotal_price + $responseModel->ship_cost)) == 0) {
                                $data1 = ['comments' => ['content' => $responseModel->content], 'refundDetail' => ['itemizedRefundDetail' => [['refundAmount' => ['value' => $responseModel->ship_cost, 'currency' => $responseModel->currency], 'refundFeeType' => 'ORIGINAL_SHIPPING'], ['refundAmount' => ['value' => $responseModel->subtotal_price, 'currency' => $responseModel->currency], 'refundFeeType' => 'PURCHASE_PRICE']], 'totalAmount' => ['currency' => $responseModel->currency, 'value' => $responseModel->refund_amount]]];
                            } else {
                                $data1 = ['comments' => ['content' => $responseModel->content], 'refundDetail' => ['itemizedRefundDetail' => [['refundAmount' => ['value' => $responseModel->refund_amount, 'currency' => $responseModel->currency], 'refundFeeType' => 'PURCHASE_PRICE']], 'totalAmount' => ['currency' => $responseModel->currency, 'value' => $responseModel->refund_amount]]];
                            }
                            $data1 = json_encode($data1);
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => $data1, 'method' => 'post', 'responseHeader' => false, 'urlParams' => ''];
                            $api = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();
                            if (empty($response)) {
                                $flag = false;
                                $responseModel->status = 0;
                                $responseModel->error = '香港服务器无返回值';
                            } else {
                                if (in_array($response->code, [200, 201, 202, 204])) {
                                    //退货请求答复状态
                                    $response = json_decode($response->response);
                                    $responseModel->refund_status = isset($response->refundStatus) ? $response->refundStatus : '';
                                    $responseModel->status = 1;
                                    //售后单信息
                                    $afterSalesOrderModel = new AfterSalesOrder();
                                    $afterSalesOrderModel->buyer_id = $returnModel->buyer_login_name;
                                    $afterSalesOrderModel->account_name = $accountName;
                                    $afterSalesOrderModel->account_id = $returnModel->account_id;
                                    $afterSalesOrderModel->approver = \Yii::$app->user->identity->login_name;
                                    $afterSalesOrderModel->approve_time = date('Y-m-d H:i:s');
                                    $afterSalesOrderModel->department_id = $data['department_id'];
                                    $afterSalesOrderModel->reason_id = $data['reason_code'];
                                    $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                                    $afterSalesOrderModel->transaction_id = $returnModel->transaction_id;
                                    $afterSalesOrderModel->order_id = $order_id;
                                    $afterSalesOrderModel->type = AfterSalesOrder::ORDER_TYPE_REFUND;
                                    if ($data['remark']) {
                                        $afterSalesOrderModel->remark = $data['remark'];
                                    } else {
                                        if ($responseModel->refund_status == 'PENDING' || $responseModel->refund_status == 'OTHER') {
                                            $afterSalesOrderModel->remark = $responseModel->refund_status;
                                        }
                                    }
                                    $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                                    $afterSalesOrderModel->platform_code = Platform::PLATFORM_CODE_EB;

                                    //售后退款信息
                                    $afterSaleOrderRefund = new AfterSalesRefund();
                                    $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
                                    $afterSaleOrderRefund->refund_amount = $responseModel->refund_amount;
                                    $afterSaleOrderRefund->currency = $responseModel->currency;
                                    $afterSaleOrderRefund->message = $responseModel->content;
                                    $afterSaleOrderRefund->transaction_id = $returnModel->transaction_id;
                                    $afterSaleOrderRefund->order_id = $order_id;
                                    $afterSaleOrderRefund->platform_code = Platform::PLATFORM_CODE_EB;
                                    $afterSaleOrderRefund->order_amount = $responseModel->refund_amount;
                                    $afterSaleOrderRefund->reason_code = $data['reason_code'];
                                    $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                                    switch ($responseModel->refund_status) {
                                        case 'SUCCESS':
                                            $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                                            break;
                                        default:
                                            $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_WAIT_RECEIVE;
                                    }
                                    $afterSaleOrderRefund->fail_count = 88;
                                } else {
                                    $responseModel->status = 0;
                                    $responseModel->error = $response->response;
                                    $flag = false;
                                    die(json_encode([
                                        'message' => $response->response,
                                    ]));
                                }
                            }
                            break;
                        case '3':
                            if ($responseModel->refund_amount > $returnModel->buyer_estimated_refund_amount) {
                                $this->_showMessage(\Yii::t('system', 'Operate Failed') . '。退款金额不能超过纠纷要求退款金额', false);
                            }
                            $sendData = [
                                'decision' => 'OFFER_PARTIAL_REFUND',
                                'partialRefundAmount' => [
                                    'currency' => $responseModel->currency, 'value' => $responseModel->refund_amount
                                ]
                            ];
                            if (!empty($responseModel->content)) {
                                $sendData['comments'] = ['content' => $responseModel->content];
                            }
                            $serverUrl = 'https://api.ebay.com/post-order/v2/return/' . $responseModel->return_id . '/decide';
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => json_encode($sendData), 'method' => 'post', 'responseHeader' => false, 'urlParams' => ''];
                            $api = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();
                            if (empty($response)) {
                                $flag = false;
                                $responseModel->status = 0;
                                $responseModel->error = $response;
                            } else {
                                if (in_array($response->code, [200, 201, 202, 204])) {
                                    $response = json_decode($response->response);
                                    $responseModel->refund_status = isset($response->refundStatus) ? $response->refundStatus : '';
                                    $responseModel->status = 1;
                                    //建立退款售后处理单
                                    $afterSalesOrderModel = new AfterSalesOrder();
                                    $afterSalesOrderModel->buyer_id = $returnModel->buyer_login_name;
                                    $afterSalesOrderModel->account_name = $accountName;
                                    $afterSalesOrderModel->account_id = $returnModel->account_id;
                                    $afterSalesOrderModel->approver = \Yii::$app->user->identity->login_name;
                                    $afterSalesOrderModel->approve_time = date('Y-m-d H:i:s');
                                    $afterSalesOrderModel->department_id = $data['department_id'];
                                    $afterSalesOrderModel->reason_id = $data['reason_code'];
                                    $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                                    $afterSalesOrderModel->transaction_id = $returnModel->transaction_id;
                                    $afterSalesOrderModel->order_id = $order_id;
                                    $afterSalesOrderModel->type = AfterSalesOrder::ORDER_TYPE_REFUND;
                                    if ($data['remark']) {
                                        $afterSalesOrderModel->remark = $data['remark'];
                                    } else {
                                        if ($responseModel->refund_status == 'PENDING' || $responseModel->refund_status == 'OTHER') {
                                            $afterSalesOrderModel->remark = $responseModel->refund_status;
                                        }
                                    }
                                    $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                                    $afterSalesOrderModel->platform_code = Platform::PLATFORM_CODE_EB;

                                    $afterSaleOrderRefund = new AfterSalesRefund();
                                    $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_PARTIAL;
                                    $afterSaleOrderRefund->refund_amount = $responseModel->refund_amount;
                                    $afterSaleOrderRefund->currency = $responseModel->currency;
                                    $afterSaleOrderRefund->message = $responseModel->content;
                                    $afterSaleOrderRefund->transaction_id = $returnModel->transaction_id;
                                    $afterSaleOrderRefund->order_id = $order_id;
                                    $afterSaleOrderRefund->reason_code = $data['reason_code'];
                                    $afterSaleOrderRefund->platform_code = Platform::PLATFORM_CODE_EB;
                                    //调订单接口查询总金额
                                    $orderinfo = '';
                                    if ($afterSaleOrderRefund->transaction_id) {
                                        $orderinfo = Order::getOrderStackByTransactionId('EB', $afterSaleOrderRefund->transaction_id);
                                    } elseif ($afterSaleOrderRefund->item_id) {
                                        $orderinfo = Order::getOrderStackByOrderId('EB', $returnModel->item_id . '-0');
                                    }
                                    if (!empty($orderinfo)) {
                                        $orderinfo = Json::decode(Json::encode($orderinfo), true);
                                        $afterSaleOrderRefund->order_amount = $orderinfo['info']['total_price'];
                                    }
                                    $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FAIL;
                                    switch ($responseModel->refund_status) {
                                        case 'SUCCESS':
                                            $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                                            break;
                                        default:
                                            $afterSaleOrderRefund->refund_status = AfterSalesRefund::REFUND_STATUS_WAIT_RECEIVE;
                                    }
                                    $afterSaleOrderRefund->fail_count = 88;
                                } else {
                                    $flag = false;
                                    $responseModel->status = 0;
                                    $responseModel->error = $response->response;
                                }
                            }
                    }
                } catch (Exception $e) {
                    $flag = false;
                    $errorInfo = $e->getMessage();
                }

                if ($flag) {
                    $transaction = EbayReturnsRequestsResponse::getDb()->beginTransaction();
                    try {
                        //添加退货请求答复信息
                        $flag = $responseModel->save();


                        if (!$flag) {
                            $errorInfo = VHelper::getModelErrors($responseModel);
                        } elseif (isset($afterSalesOrderModel)) {
                            //添加售后单信息
                            $flag = $afterSalesOrderModel->save();
                            if (!$flag) {
                                $errorInfo = VHelper::getModelErrors($afterSalesOrderModel);
                            } else {
                                $afterSalesOrderId = $afterSalesOrderModel->after_sale_id;
                            }

                            //添加售后退款信息
                            if ($flag && isset($afterSaleOrderRefund)) {
                                //设置售后退款的after_sale_id为售后单的after_sale_id
                                $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                                $flag = $afterSaleOrderRefund->save();
                                //添加售后退款信息
                                if (!$flag) {
                                    $errorInfo = VHelper::getModelErrors($afterSaleOrderRefund);
                                } else {
                                    $afterSaleOrderRefund->audit($afterSalesOrderModel); // 成功推送erp修改订单退款状态
                                }
                            }
                        }

                        if ($flag && ($data['type'] == 2 || $data['type'] == 3)) {
                            if (strpos($sku_info, ';') !== false) {
                                $sku_info = ltrim($sku_info, ';');
                                //拆分数组
                                $sku_info_arr = explode(';', $sku_info);
                                foreach ($sku_info_arr as &$v) {
                                    $sku = explode('&', $v)[0];
                                    $product_title = explode('&', $v)[1];
                                    $linelist_cn_name = explode('&', $v)[2];
                                    $issue_quantity = intval(explode('&', $v)[3]);
                                    $afterSaleProduct = new AfterSalesProduct();
                                    $afterSaleProduct->platform_code = Platform::PLATFORM_CODE_EB;
                                    $afterSaleProduct->order_id = $order_id;
                                    $afterSaleProduct->sku = $sku;
                                    $afterSaleProduct->product_title = $product_title;
                                    $afterSaleProduct->quantity = $issue_quantity;
//                                    $afterSaleProduct->currency=$data['currency'];
                                    $afterSaleProduct->linelist_cn_name = $linelist_cn_name;
                                    $afterSaleProduct->issue_quantity = $issue_quantity;
                                    $afterSaleProduct->reason_id = $data['reason_code'];
                                    $afterSaleProduct->after_sale_id = $afterSalesOrderId;
                                    //添加问题产品数据
                                    if (!$afterSaleProduct->save()) {
                                        $errorInfo = VHelper::getModelErrors($afterSaleProduct);
                                    }
                                }
                            } else {
                                //单个
                                $sku = explode('&', $sku_info)[0];
                                $product_title = explode('&', $sku_info)[1];
                                $linelist_cn_name = explode('&', $sku_info)[2];
                                $issue_quantity = intval(explode('&', $sku_info)[3]);
                                $afterSaleProduct = new AfterSalesProduct();
                                $afterSaleProduct->platform_code = Platform::PLATFORM_CODE_EB;
                                $afterSaleProduct->order_id = $order_id;
                                $afterSaleProduct->sku = $sku;
                                $afterSaleProduct->product_title = $product_title;
                                $afterSaleProduct->quantity = $issue_quantity;
                                $afterSaleProduct->linelist_cn_name = $linelist_cn_name;
//                                $afterSaleProduct->currency=$data['currency'];
                                $afterSaleProduct->issue_quantity = $issue_quantity;
                                $afterSaleProduct->reason_id = $data['reason_code'];
                                $afterSaleProduct->after_sale_id = $afterSalesOrderId;
                                //添加问题产品数据
                                if (!$afterSaleProduct->save()) {
                                    $errorInfo = VHelper::getModelErrors($afterSaleProduct);
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $flag = false;
                        $errorInfo = $e->getMessage();
                    }

                    if ($flag) {
                        $transaction->commit();
                    } else {
                        $transaction->rollback();
                    }
                }


                if ($flag) {

                    $returnModel->is_transition = 0;
                    if (!$returnModel->save())
                        $this->_showMessage('修改纠纷状态出错。', false);

                    VHelper::throwTheader('/services/ebay/returnsearch/refresh', ['id' => $id]);
                    //纠纷处理完 修改状态
                    $disputeStatistics = DisputeStatistics::findAll(['dispute_id' => $returnModel->return_id, 'type' => AccountTaskQueue::TASK_TYPE_RETURN, 'platform_code' => Platform::PLATFORM_CODE_EB]);
                    if ($disputeStatistics) {
                        foreach ($disputeStatistics as $statistics) {
                            if ($statistics->status == 0) {
                                $statistics->status = 1;
                                $statistics->reply = Yii::$app->user->identity->user_name;
                                $statistics->save(false);
                            }
                        }
                    }
                    if ($isout) {
                        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true);
                    } else {

                        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayreturnsrequests/index') . '");', true, 'msg');
                    }
                } else {
                    $this->_showMessage(\Yii::t('system', 'Operate Failed') . '。' . $errorInfo, false);
                }
            }
                                        
            //获取订单信息
            if (!empty($returnModel->platform_order_id)) {
                $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_EB, $returnModel->platform_order_id, "", 3);
              
            } else {
                $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_EB, $returnModel->item_id . '-' . $returnModel->transaction_id, "", 3);
            }
            if (empty($orderinfo) && !empty($returnModel->transaction_id)) {
                $orderinfo = OrderKefu::getOrderStackByTransactionId(Platform::PLATFORM_CODE_EB, $returnModel->transaction_id);
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
                            $data = [];
                            $stock = isset($data['available_stock']) ? $data['available_stock'] : 0;
                            $on_way_count = isset($data['on_way_stock']) ? $data['on_way_stock'] : 0;
                        }
                        $orderinfo['product'][$key]['stock'] = $stock;
                        $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;
                    }
                    // 查询订单评价
                    $transactionId = !empty($value['transaction_id']) ? $value['transaction_id'] : 0;
                    $order_line_item_id = $value['item_id'] . '-' . $transactionId;
                    $feedbackInfo = EbayFeedback::find()->select('id,feedback_id,comment_type')->where(['role' => 1, 'order_line_item_id' => $order_line_item_id])->asArray()->one();
                    if (!empty($feedbackInfo)) {
                        $orderinfo['product'][$key]['feed_table_id'] = $feedbackInfo['id'];
                        $orderinfo['product'][$key]['feedback_id'] = $feedbackInfo['feedback_id'];
                        $orderinfo['product'][$key]['comment_type'] = $feedbackInfo['comment_type'];
                    }
                }
            } else {
                echo '未查到订单信息，不能处理。';
                \Yii::$app->end();
            }
            $detailModel = EbayReturnsRequestsDetail::find()->where(['return_id' => $returnModel->return_id])->orderBy('creation_date_value DESC')->all();

            $googleLangCode = VHelper::googleLangCode();
            @$afterSalesOrders = AfterSalesOrder::getByOrderId('EB', $orderinfo['info']['order_id']);
            //加黑名单解决方案  部门主管及以上
            $isAuthority = false;
            if (UserRole::checkManage(Yii::$app->user->identity->id)) {
                $isAuthority = true;
            }

            $author = false;
            if (in_array(Yii::$app->user->identity->login_name, ['何贞'])) {
                $author = true;
            }
            $departmentList = BasicConfig::getParentList(52);
            $departmentList_new = [];

            foreach ($departmentList as $k => &$v) {
                $departmentList_new[$k]['depart_id'] = $k;
                $departmentList_new[$k]['depart_name'] = $v;
            }

            return $this->render('handle/index', [
                        'order_id' => $returnModel->transaction_id,
                        'info' => $orderinfo, //$orderinfo,
                        'model' => $returnModel,
                        'detailModel' => $detailModel,
                        'accountName' => $accountName,
                        'reasonCode' => $returnReason,
                        'inbox_info' => $inbox_info,
                        'googleLangCode' => $googleLangCode,
                        'afterSalesOrders' => $afterSalesOrders,
                        'isAuthority' => $isAuthority,
                        'departmentList' => json_encode($departmentList_new),
                        'author' => $author,
            ]);
        }
    }

    /**
     * 上传凭证
     */
    public function actionUploadimages() {
        if (\Yii::$app->request->isPost) {
            $id = Yii::$app->request->post('id');
            $return_id = Yii::$app->request->post('return_id');
            $imageUrl = Yii::$app->request->post('image');
            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }
            if (empty($return_id)) {
                $this->_showMessage('纠纷ID不能为空', false);
            }
            if (empty($imageUrl)) {
                $this->_showMessage('请上传凭证', false);
            }

            //获取退货请求信息
            $returnModel = EbayReturnsRequests::findOne((int) $id);
            //获取账号信息
            $AccountModel = Account::findById((int) $returnModel->account_id);
            if (empty($AccountModel)) {
                echo '未查到ebay帐号信息，无法处理。';
                \Yii::$app->end();
            }
            $name = basename($imageUrl);
            $info = pathinfo($name);
            $fp = fopen($imageUrl, 'rb');
            if (!$fp) {
                $this->_showMessage('打开图片失败', false);
            }
            $imageData = fread($fp, self::MAX_ISSUE_IMAGE_SIZE);
            if (!$imageData) {
                $this->_showMessage('读取图片失败', false);
            }
            $transfer_ip = include \Yii::getAlias('@app') . '/config/transfer_ip.php';
            $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';
            $imageData = base64_encode(base64_encode($imageData));
            $serverUrl = 'https://api.ebay.com/post-order/v2/return/' . $returnModel->return_id . '/file/upload';
            $sendData = [
                "fileName" => $name,
                "data" => $imageData,
                "filePurpose" => "ITEM_RELATED",
            ];
            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $AccountModel->user_token, 'data' => json_encode($sendData), 'method' => 'post', 'responseHeader' => true, 'urlParams' => ''];
            $api = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
            $api->setData($post_data);
            $response = $api->sendHttpRequest();
            if (empty($response)) {
                return (json_encode([
                            'status' => 'error',
                            'info' => '香港服务器无返回值',
                ]));
            } else {
                if (in_array($response->code, [200, 201, 202])) {
                    $serverUrls = 'https://api.ebay.com/post-order/v2/return/' . $returnModel->return_id . '/file/submit';
                    $sendData = ["filePurpose" => "ITEM_RELATED",];
                    $post_data = ['serverUrl' => $serverUrls, 'authorization' => $AccountModel->user_token, 'data' => json_encode($sendData), 'method' => 'post', 'responseHeader' => true, 'urlParams' => ''];
                    $api = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                    $api->setData($post_data);
                    $result = $api->sendHttpRequest();
                    if (Yii::$app->user->identity->login_name == '吴峰') {
                        var_dump(json_decode($result));
                        exit;
                    }
                    if (in_array($result->code, [200, 201, 202])) {
                        return (json_encode([
                                    'status' => 'success',
                                    'info' => '上传成功',
                        ]));
                    } else {
                        return (json_encode([
                                    'status' => 'error',
                                    'info' => '保存图片失败',
                        ]));
                    }
                } else {
                    return (json_encode([
                                'status' => 'error',
                                'info' => '上传失败',
                    ]));
                }
            }
        }
    }

    /**
     * 上传图片
     */
    public function actionUploadimage() {
        if (\Yii::$app->request->isPost) {
            $uploadFile = UploadedFile::getInstanceByName('upload_file');

            if (empty($uploadFile)) {
                die(json_encode([
                    'status' => 'error',
                    'info' => '上传失败',
                ]));
            }
            if ($uploadFile->size > self::MAX_ISSUE_IMAGE_SIZE) {
                die(json_encode([
                    'status' => 'error',
                    'info' => '图片大小超过限制',
                ]));
            }
            $filePath = self::XLS_UPLOAD_PATH . date('Ymd') . '/';
            if (!file_exists($filePath)) {
                @mkdir($filePath, 0777, true);
                @chmod($filePath, 0777);
            }
            $fileName = md5($uploadFile->baseName) . '.' . $uploadFile->extension;
            $file = $filePath . $fileName;

            if ($uploadFile->saveAs($file)) {
                die(json_encode([
                    'status' => 'success',
                    'info' => '上传成功',
                    'url' => $this->request->hostInfo . '/' . $file,
                    'file_name' => $file,
                ]));
            } else {
                die(json_encode([
                    'status' => 'error',
                    'info' => '上传失败',
                ]));
            }
        }
    }

    /**
     * 删除图片
     */
    public function actionDeleteimage() {
        $url = trim($this->request->post('url'));
        $host = $this->request->hostInfo;
        if (strpos($url, $host) === false) {
            $response = ['status' => 'error', 'info' => '参数错误。'];
        } else {
            $url = str_replace($host . '/', '', $url);
            if (file_exists($url)) {
                unlink($url);
                $response = ['status' => 'success'];
            } else {
                $response = ['status' => 'error', 'info' => '图片不存在。'];
            }
        }
        echo json_encode($response);
        \Yii::$app->end();
    }

    /**
     * 单个更新
     */
    public function actionRefresh() {
        $id = $this->request->get('id');
        if (is_numeric($id) && $id > 0 && $id % 1 === 0) {
            $result = EbayReturnsRequests::findOne((int) $id)->refreshApi();
            if ($result['flag']) {
                $this->_showMessage('更新成功。', true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayreturnsrequests/index') . '");', true, 'msg');
            } else {
                $this->_showMessage('更新失败。' . $result['info'], false);
            }
        }
    }

    /**
     * 批量更新
     */
    public function actionBatchrefresh() {
        $ids = $this->request->post('ids');
        $returnids = '';
        foreach ($ids as $key => $id) {
            $result = EbayReturnsRequests::findOne((int) $id)->refreshApi();
            if ($result['flag']) {
                continue;
            } else {
                $returnids .= EbayReturnsRequests::getReturnIDByID($id) . ',';
            }
        }
        if ($returnids) {
            $returnids = trim($returnids, ',');
            $this->_showMessage('部分数据更新失败，return_id如下所示：' . $returnids, true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayreturnsrequests/index') . '");', true, 'msg');
        } else {
            $this->_showMessage('更新成功。', true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayreturnsrequests/index') . '");');
        }
    }

    /**
     * 自动退款
     */
    public function actionChangeautorefund() {
        $id = $this->request->post('id');
        $auto_refund = $this->request->post('auto_refund');

        $ebayReturnsRequests = EbayReturnsRequests::find()->where(['id' => $id])->one();
        if (empty($ebayReturnsRequests)) {
            echo json_encode(['status' => 'error', 'message' => '未找到该纠纷']);
        }
        $ebayReturnsRequests->auto_refund = $auto_refund;

        if ($ebayReturnsRequests->save())
            echo json_encode(['status' => 'success', 'message' => '操作成功']);
        else
            echo json_encode(['status' => 'error', 'message' => '操作失败']);
    }

    /**
     * 导出excel
     */
    public function actionToexcel() {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        //获取get参数
        $get = YII::$app->request->get();
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        $data = [];

        //只能查询到客服绑定账号的
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

        if (is_array($ids) && !empty($ids)) {
            //取出选中的数据
            $data = EbayReturnsRequests::find()
                    ->select('*')
                    ->andWhere(['in', 'id', $ids])
                    ->andWhere(['in', 'account_id', $accountIds])
                    ->asArray()
                    ->all();
        } else {
            //取出筛选的评价数据
            $query = EbayReturnsRequests::find()
                    ->select('*')
                    ->andWhere(['in', 'account_id', $accountIds]);

            //添加表单的筛选条件
            if (!empty($get['return_id'])) {
                $query->andWhere(['return_id' => $get['return_id']]);
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
            if (!empty($get['buyer_login_name'])) {
                $query->andWhere(['buyer_login_name' => $get['buyer_login_name']]);
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
            if (!empty($get['state'])) {
                $query->andWhere(['state' => $get['state']]);
            }
            if (isset($get['status']) && $get['status'] != '') {
                switch ($get['status']) {
                    case 'wait_seller':
                        $query->andWhere('status in ("PARTIAL_REFUND_REQUESTED","REPLACEMENT_LABEL_REQUESTED","REPLACEMENT_REQUESTED","REPLACEMENT_WAITING_FOR_RMA","RETURN_LABEL_REQUESTED","RETURN_REQUESTED","RETURN_REQUESTED_TIMEOUT","WAITING_FOR_RETURN_LABEL","WAITING_FOR_RMA")');
                        break;
                    case 'closed':
                        $query->andWhere('status in ("CLOSED","REPLACEMENT_CLOSED","REPLACEMENT_DELIVERED","RETURN_REJECTED")');
                        break;
                    case 'other':
                        $query->andWhere('status in ("ESCALATED","ITEM_DELIVERED","ITEM_SHIPPED","LESS_THAN_A_FULL_REFUND_ISSUED","PARTIAL_REFUND_DECLINED","PARTIAL_REFUND_INITIATED","READY_FOR_SHIPPING","REPLACED","REPLACEMENT_SHIPPED","REPLACEMENT_STARTED","UNKNOWN")');
                        break;
                }
            }
            if (isset($get['is_deal']) && $get['is_deal'] != '') {
                $query->andWhere(['is_deal' => $get['is_deal']]);
            }
            if (isset($get['is_transition']) && $get['is_transition'] != '') {
                $query->andWhere(['is_transition' => $get['is_transition']]);
            }
            if (!empty($get['start_time']) && !empty($get['end_time'])) {
                $query->andWhere(['between', 'return_creation_date', $get['start_time'], $get['end_time']]);
            } else if (!empty($get['start_time'])) {
                $query->andWhere(['>=', 'return_creation_date', $get['start_time']]);
            } else if (!empty($get['end_time'])) {
                $query->andWhere(['<=', 'return_creation_date', $get['end_time']]);
            }

            $data = $query->asArray()->all();
        }

        if (empty($data)) {
            $this->_showMessage('数据为空', false);
        }

        $itemIds = [];
        $transactionIds = [];
        $platformOrderIds = [];

        if (!empty($data)) {
            foreach ($data as $item) {
                $itemIds[] = $item['item_id'];
                $transactionIds[] = $item['transaction_id'];
                $platformOrderIds[] = $item['platform_order_id'];
            }

            $itemIds = array_unique($itemIds);
            $transactionIds = array_unique($transactionIds);
            $platformOrderIds = array_unique($platformOrderIds);
        }

        //获取订单信息
        $result = Order::getEbayOrderInfos([
                    'platformCode' => Platform::PLATFORM_CODE_EB,
                    'platformOrderIds' => implode(',', $platformOrderIds),
                    'transactionIds' => implode(',', $transactionIds),
                    'itemIds' => implode(',', $itemIds),
        ]);

        $orders = !empty($result['order']) ? $result['order'] : [];
        $trans = !empty($result['trans']) ? $result['trans'] : [];

        //获取paypal收款地址
        $receiverModel = new ErpProductApi();
        $paypalAddrs = $receiverModel->getProductPaypals(['itemIds' => implode(',', $itemIds)]);
        $paypalAddrs = !empty($paypalAddrs) ? json_decode(json_encode($paypalAddrs->datas), true) : array();

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
        $logistics = json_decode(json_encode(Logistic::getAllStatusLogistics()), true);

        foreach ($data as $key => $model) {
            $data[$key]['account_name'] = isset($accountNames[$model['account_id']]) ? $accountNames[$model['account_id']] : '';
            $accountShortName = isset($accountShortNames[$model['account_id']]) ? $accountShortNames[$model['account_id']] : '';
            if (!empty($model['order_id'])) {
                $data[$key]['orientation_order_id'] = isset($accountShortName) ? $accountShortName . '--' . $model['order_id'] : $model['order_id'];
            }

            //$rmb = VHelper::getTargetCurrencyAmt($model['currency'],  'CNY', $model['buyer_estimated_refund_amount']);
            $rmb = VHelper::getTargetCurrencyAmtKefu($model['currency'], 'CNY', $model['buyer_estimated_refund_amount']);
            $data[$key]['buyer_estimated_refund_amount_rmb'] = $rmb ? $rmb : '';

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
                    $tmp = $order_info->info;
                    $tmp->trade = $order_info->trade;
                    if (!empty($order_info->product)) {
                        foreach ($order_info->product as $product) {
                            if ($product->transaction_id == $model['transaction_id']) {
                                $tmp->sku = $product->sku;
                                $tmp->quantity = $product->quantity;
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
                $data[$key]['paytime'] = $order_info['paytime'];
                $data[$key]['ship_country_name'] = $order_info['ship_country_name'];
                $data[$key]['complete_status'] = array_key_exists($order_info['complete_status'], $order_status_map) ? $order_status_map[$order_info['complete_status']] : '';
                $data[$key]['complete_status'] = strip_tags($data[$key]['complete_status']);
                $data[$key]['shipped_date'] = $order_info['shipped_date'];
                $data[$key]['warehouse_name'] = array_key_exists($order_info['warehouse_id'], $warehouseList) ? $warehouseList[$order_info['warehouse_id']] : '';
                $data[$key]['ship_code_name'] = array_key_exists($order_info['ship_code'], $logistics) ? $logistics[$order_info['ship_code']] : '';
                $data[$key]['sku'] = $order_info['sku'];
                $data[$key]['quantity'] = $order_info['quantity'];
                $data[$key]['picking_name'] = $order_info['picking_name'];

                $data[$key]['paypal_trans_id'] = '';
                $data[$key]['trans_currency'] = '';
                $data[$key]['amt'] = '';
                $data[$key]['paypal'] = '';

                if (!empty($order_info['trade'])) {
                    $paypal_trans_id = '';
                    foreach ($order_info['trade'] as $trade) {
                        if ($trade['amt'] > 0) {
                            $paypal_trans_id = $trade['transaction_id'];
                            $data[$key]['paypal_trans_id'] = $paypal_trans_id;
                            $data[$key]['trans_currency'] = $trade['currency'];
                            $data[$key]['amt'] = $trade['amt'];
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
                $data[$key]['paytime'] = '';
                $data[$key]['ship_country_name'] = '';
                $data[$key]['complete_status'] = '';
                $data[$key]['shipped_date'] = '';
                $data[$key]['warehouse_name'] = '';
                $data[$key]['ship_code_name'] = '';
                $data[$key]['paypal'] = '';
                $data[$key]['paypal_trans_id'] = '';
                $data[$key]['trans_currency'] = '';
                $data[$key]['amt'] = '';
                $data[$key]['sku'] = '';
                $data[$key]['quantity'] = '';
                $data[$key]['picking_name'] = '';
            }
            $detailInfo = EbayReturnsRequestsDetail::getReturnNotes($model['return_id']);
            $data[$key]['notes'] = !empty($detailInfo) ? $detailInfo['notes'] : "";
        }
        $data = json_decode(json_encode($data));

        $columns = ['account_name', 'orientation_order_id', 'paytime', 'buyer_login_name', 'ship_country_name', 'complete_status', 'shipped_date', 'warehouse_name', 'ship_code_name', 'paypal', 'paypal_trans_id', 'trans_currency', 'amt', 'item_id', 'sku', 'quantity', 'picking_name', 'return_id', 'return_reason', 'return_creation_date', 'state', 'status', 'return_quantity', 'buyer_estimated_refund_amount', 'buyer_estimated_refund_amount_rmb', 'actual_refund_amount', 'notes'];
        $headers = ['account_name' => '账号名', 'orientation_order_id' => '定位订单号', 'paytime' => '付款时间', 'buyer_login_name' => '买家ID', 'ship_country_name' => '收件人国家',
            'complete_status' => '订单状态', 'shipped_date' => '发货时间', 'warehouse_name' => '发货仓库', 'ship_code_name' => '邮寄方式', 'paypal' => '收款PayPal帐号',
            'paypal_trans_id' => '收款PayPal交易号', 'trans_currency' => '收款币种', 'amt' => '收款金额', 'item_id' => 'ItemId', 'sku' => 'SKU', 'quantity' => '数量', 'picking_name' => '中文名',
            'return_id' => 'Return Id', 'return_reason' => '退款原因', 'return_creation_date' => 'Return创建时间', 'state' => '状况', 'status' => '状态', 'return_quantity' => '退货数量',
            'buyer_estimated_refund_amount' => '买家预计退款金额', 'buyer_estimated_refund_amount_rmb' => '买家预计退款金额(RMB)', 'actual_refund_amount' => '实际退款金额', 'notes' => '投诉内容'];

        $fileName = 'return_' . date('Y-m-d');

        \moonland\phpexcel\Excel::widget([
            'fileName' => $fileName,
            'models' => $data,
            'mode' => 'export', //default value as 'export'
            'columns' => $columns, //without header working, because the header will be get label from attribute label.
            'headers' => $headers,
        ]);
    }

}
