<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/10 0010
 * Time: 下午 6:55
 */

namespace app\modules\services\modules\ebay\controllers;

use app\common\VHelper;
use app\modules\mails\models\CancelOrderHandle;
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\EbayCancellationsDetail;
use app\modules\orders\models\Order;
use app\modules\products\models\EbaySiteMapAccount;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use app\modules\systems\models\EbayAccount;
use PhpImap\Exception;
use yii\helpers\Json;
use yii\helpers\Url;
use app\modules\systems\models\EbayApiTask;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\components\Controller;
use app\modules\reports\models\DisputeStatistics;

class CancellationsearchController extends Controller {

    private $ebayAccountModel;
    private $accountId;
    private $apiTaskModel;
    private $errorCode = 0;
    private $transaction;
    private $flag = true;

    public function actionIndex() {
        if (isset($_REQUEST['account'])) {
            $account = $_REQUEST['account'];
            if (is_numeric($account) && $account > 0 && $account % 1 === 0) {
                $ebayAccountModel = EbayAccount::findOne((int) $account);
                $this->ebayAccountModel = $ebayAccountModel;
                $uncloseds = EbayCancellations::find()->where('account_id=:account_id and cancel_state<>2', [':account_id' => $account])->all(); //cancel_state不等于2
                set_time_limit(6600);
                if (!empty($uncloseds)) {
                    foreach ($uncloseds as $unclosed) {
                        $this->searchApi($ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/cancellation/search', ['cancel_id' => $unclosed->cancel_id], 'get');
                    }
                }
                $maxCreationDateRangeFrom = EbayCancellations::find()->select('max(cancel_request_date)')->distinct()->where(['account_id' => $account])->asArray()->one()['max(cancel_request_date)'];
                if (empty($maxCreationDateRangeFrom))
                    $creationDateRangeFrom = date('Y-m-d\TH:i:s', strtotime('-60 days')) . '.000Z';
                else
                    $creationDateRangeFrom = $maxCreationDateRangeFrom . '.000Z';
                $creationDateRangeTo = date('Y-m-d\TH:i:s') . '.000Z';
                if (strcasecmp($creationDateRangeTo, $creationDateRangeFrom) > 0)
                    $this->searchApi($ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/cancellation/search', ['creation_date_range_from' => $creationDateRangeFrom, 'creation_date_range_to' => $creationDateRangeTo, 'limit' => 25, 'offset' => 1], 'get');
            }
        }
        else {
            $accounts = EbaySiteMapAccount::find()->select('ebay_account_id')->distinct()->where('is_delete=0')->asArray()->all();
            if (!empty($accounts)) {
                foreach ($accounts as $accountV) {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/cancellationsearch/index', 'account' => $accountV['ebay_account_id'])));
                    sleep(2);
                }
            } else {
                exit('{{%ebay_site_map_account}}没有账号数据');
            }
        }
    }

    public function actionCancellation() {
        if (isset($_REQUEST['id'])) {
            $account = trim($_REQUEST['id']);
            if (is_numeric($account) && $account > 0 && $account % 1 === 0) {
                $this->accountId = $account;
                $accountName = Account::findById((int) $this->accountId)->account_name;
                $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
                if (empty($this->ebayAccountModel))
                    exit('无法获取账号信息。');
                if (EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_CANCELLATION, $this->accountId)) {
                    echo "account:{$this->accountId};Task Running." . PHP_EOL;
                    exit;
                }
                ignore_user_abort(true);
                set_time_limit(7200);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_CANCELLATION;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                $uncloseds = EbayCancellations::find()->where('account_id=:account_id and cancel_state<>2', [':account_id' => $account])->all(); //cancel_state不等于2
                if (!empty($uncloseds)) {
                    foreach ($uncloseds as $unclosed) {
                        $this->searchApi($this->ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/cancellation/search', ['cancel_id' => $unclosed->cancel_id], 'get');
                    }
                }
                $maxStartTime = EbayApiTask::find()->select('max(start_time)')->where(['account_id' => $this->accountId, 'task_name' => AccountTaskQueue::TASK_TYPE_CANCELLATION, 'exec_status' => 2, 'status' => [2, 3]])->asArray()->one()['max(start_time)'];
                if (empty($maxStartTime))
                    $creationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z', strtotime(' -30 days') - 32400);
                else
                    $creationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z', strtotime($maxStartTime) - 32400);
                $creationDateRangeTo = date('Y-m-d\TH:i:s') . '.000Z';
                if (strcasecmp($creationDateRangeTo, $creationDateRangeFrom) > 0)
                    $this->searchApi($this->ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/cancellation/search', ['creation_date_range_from' => $creationDateRangeFrom, 'creation_date_range_to' => $creationDateRangeTo, 'limit' => 25, 'offset' => 1], 'get');
                else {
                    $this->apiTaskModel->exec_status = 1;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。StartTime:{$creationDateRangeFrom} 不能小于 EndTime:{$creationDateRangeFrom}。]";
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                }
                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();
                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB, AccountTaskQueue::TASK_TYPE_CANCELLATION);
                if (!empty($accountTask)) {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/cancellationsearch/cancellation', ['id' => $accountId]);
                }
                exit('DONE');
            }
        } else {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB, Account::STATUS_VALID);
            if (!empty($accountList)) {
                foreach ($accountList as $account) {
                    if (AccountTaskQueue::find()->where(['account_id' => $account->id, 'type' => AccountTaskQueue::TASK_TYPE_CANCELLATION, 'platform_code' => $account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_CANCELLATION;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code' => Platform::PLATFORM_CODE_EB, 'type' => AccountTaskQueue::TASK_TYPE_CANCELLATION]);
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
//                    findClass($accountId,1);
                    VHelper::throwTheader('/services/ebay/cancellationsearch/cancellation', ['id' => $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    public function actionCancellationgetnew() {
        if (isset($_REQUEST['id'])) {
            $account = trim($_REQUEST['id']);
            if (is_numeric($account) && $account > 0 && $account % 1 === 0) {
                $this->accountId = $account;
                $accountName = Account::findById((int) $this->accountId)->account_name;
                $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
                if (empty($this->ebayAccountModel))
                    exit('无法获取账号信息。');
                if (EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_CANCELLATION, $this->accountId)) {
                    echo "account:{$this->accountId};Task Running." . PHP_EOL;
                    exit;
                }
                ignore_user_abort(true);
                set_time_limit(7200);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_CANCELLATION;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                $maxStartTime = EbayApiTask::find()->select('max(start_time)')->where(['account_id' => $this->accountId, 'task_name' => AccountTaskQueue::TASK_TYPE_CANCELLATION, 'exec_status' => 2, 'status' => [2, 3]])->asArray()->one()['max(start_time)'];
                if (empty($maxStartTime))
                    $creationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z', strtotime(' -30 days') - 29700);
                else
                    $creationDateRangeFrom = date('Y-m-d\TH:i:s.000\Z', strtotime($maxStartTime) - 29700);
                $creationDateRangeTo = date('Y-m-d\TH:i:s') . '.000Z';
                if (strcasecmp($creationDateRangeTo, $creationDateRangeFrom) > 0)
                    $this->searchApi($this->ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/cancellation/search', ['creation_date_range_from' => $creationDateRangeFrom, 'creation_date_range_to' => $creationDateRangeTo, 'limit' => 25, 'offset' => 1], 'get');
                else {
                    $this->apiTaskModel->exec_status = 1;
                    $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。StartTime:{$creationDateRangeFrom} 不能小于 EndTime:{$creationDateRangeFrom}。]";
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                }
                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();
                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB, AccountTaskQueue::TASK_TYPE_CANCELLATION);
                if (!empty($accountTask)) {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/cancellationsearch/cancellationgetnew', ['id' => $accountId]);
                }
                exit('DONE');
            }
        } else {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB, Account::STATUS_VALID);
            if (!empty($accountList)) {
                foreach ($accountList as $account) {
                    if (AccountTaskQueue::find()->where(['account_id' => $account->id, 'type' => AccountTaskQueue::TASK_TYPE_CANCELLATION, 'platform_code' => $account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_CANCELLATION;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code' => Platform::PLATFORM_CODE_EB, 'type' => AccountTaskQueue::TASK_TYPE_CANCELLATION]);
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
//                    findClass($accountId,1);
                    VHelper::throwTheader('/services/ebay/cancellationsearch/cancellationgetnew', ['id' => $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    public function actionCancellationupdate() {
        if (isset($_REQUEST['id'])) {
            $account = trim($_REQUEST['id']);
            if (is_numeric($account) && $account > 0 && $account % 1 === 0) {
                $this->accountId = $account;
                $accountName = Account::findById((int) $this->accountId)->account_name;
                $this->ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
                if (empty($this->ebayAccountModel))
                    exit('无法获取账号信息。');
                if (EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_CANCELLATION_UPDATE, $this->accountId)) {
                    echo "account:{$this->accountId};Task Running." . PHP_EOL;
                    exit;
                }
                ignore_user_abort(true);
                set_time_limit(7200);
                $this->apiTaskModel = new EbayApiTask();
                $this->apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_CANCELLATION_UPDATE;
                $this->apiTaskModel->account_id = $this->accountId;
                $this->apiTaskModel->exec_status = 1;
                $this->apiTaskModel->start_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();

                $uncloseds = EbayCancellations::find()->where('account_id=:account_id and cancel_state<>2', [':account_id' => $account])->all(); //cancel_state不等于2
                if (!empty($uncloseds)) {
                    foreach ($uncloseds as $unclosed) {
                        $this->searchApi($this->ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/cancellation/search', ['cancel_id' => $unclosed->cancel_id], 'get');
                    }
                } else {
                    $this->apiTaskModel->status = 2;
                    $this->apiTaskModel->error = 'no datas';
                }

                $this->apiTaskModel->exec_status = 2;
                $this->apiTaskModel->end_time = date('Y-m-d H:i:s');
                $this->apiTaskModel->save();
                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB, AccountTaskQueue::TASK_TYPE_CANCELLATION_UPDATE);
                if (!empty($accountTask)) {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/cancellationsearch/cancellationupdate', ['id' => $accountId]);
                }
                exit('DONE');
            }
        } else {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB, Account::STATUS_VALID);
            if (!empty($accountList)) {
                foreach ($accountList as $account) {
                    if (AccountTaskQueue::find()->where(['account_id' => $account->id, 'type' => AccountTaskQueue::TASK_TYPE_CANCELLATION_UPDATE, 'platform_code' => $account->platform_code])->exists())
                        continue;
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_CANCELLATION_UPDATE;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code' => Platform::PLATFORM_CODE_EB, 'type' => AccountTaskQueue::TASK_TYPE_CANCELLATION_UPDATE]);
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
//                    findClass($accountId,1);
                    VHelper::throwTheader('/services/ebay/cancellationsearch/cancellationupdate', ['id' => $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    private function searchApi($token, $site, $serverUrl, $params, $method) {
        /* $api = new PostOrderAPI($token,$site,$serverUrl,$method);
          $api->urlParams = $params;
          $response = (array)$api->sendHttpRequest();

          if(empty($response))
          {
          $this->apiTaskModel->status = 1;
          $this->apiTaskModel->error .= '[错误码：'.$this->errorCode.'。'.$api->getServerUrl().'拉取无数据。]';
          $this->apiTaskModel->save();
          $this->errorCode++;
          }
          else
          {
          $this->handleSearchResponse($response);
          if(isset($params['offset']) && $response['paginationOutput']->totalPages > $params['offset'])
          {
          $params['offset']++;
          $this->searchApi($token,$site,$serverUrl,$params,$method);
          }
          } */

        $transfer_ip = include \Yii::getAlias('@app') . '/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';

        $post_data = ['serverUrl' => $serverUrl, 'authorization' => $token, 'data' => '', 'method' => $method, 'responseHeader' => false, 'urlParams' => $params];
        $api = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();
        if (empty($response)) {
            $this->apiTaskModel->status = 1;
            $this->apiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $api->getServerUrl() . '中转站拉取无数据。]';
            $this->apiTaskModel->save();
            $this->errorCode++;
        } else {
            if (in_array($response->code, [200, 201, 202])) {
                $response = (array) json_decode($response->response);
                $this->handleSearchResponse($response);
                if (isset($params['offset']) && $response['paginationOutput']->totalPages > $params['offset']) {
                    $params['offset'] ++;
                    $this->searchApi($token, $site, $serverUrl, $params, $method);
                }
            } else {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->error .= '[错误码：' . $this->errorCode . '。' . $api->getServerUrl() . '拉取无数据。]';
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
        }
    }

    private function handleSearchResponse($data) {
        $currentTime = date('Y-m-d H:i:s');
        foreach ($data['cancellations'] as $cancellation) {
            $orderid = ""; //ERP订单ID
            $this->detailApi($this->ebayAccountModel->user_token, '', 'https://api.ebay.com/post-order/v2/cancellation/' . $cancellation->cancelId, 'get');
            if ($this->flag) {
                //获取新的取消纠纷插入纠纷统计表
                $disputeStatistics = DisputeStatistics::findOne(['dispute_id' => $cancellation->cancelId, 'type' => AccountTaskQueue::TASK_TYPE_CANCELLATION, 'platform_code' => Platform::PLATFORM_CODE_EB]);
                if (empty($disputeStatistics)) {
                    $disputeStatistics = new DisputeStatistics();
                    $disputeStatistics->platform_code = Platform::PLATFORM_CODE_EB;
                    $disputeStatistics->account_id = $this->accountId;
                    $disputeStatistics->status = 0;
                    $disputeStatistics->type = AccountTaskQueue::TASK_TYPE_CANCELLATION;
                    $disputeStatistics->create_time = date('Y-m-d H:i:s');
                    $disputeStatistics->dispute_id = $cancellation->cancelId;
                    $disputeStatistics->requestor_type = array_search(trim($cancellation->requestorType), EbayCancellations::$requestorTypeMap);
                    $disputeStatistics->cancel_request_date = explode('.', str_replace('T', ' ', $cancellation->cancelRequestDate->value))[0];
                    $disputeStatistics->save(false);
                }
                $model = EbayCancellations::findOne(['cancel_id' => $cancellation->cancelId]);
                if (empty($model))
                    $model = new EbayCancellations();
                $model->cancel_id = $cancellation->cancelId;
                $model->marketplace_id = $cancellation->marketplaceId;
                $model->legacy_order_id = $cancellation->legacyOrderId;
                $model->requestor_type = array_search(trim($cancellation->requestorType), EbayCancellations::$requestorTypeMap);
                $model->cancel_state = array_search(trim($cancellation->cancelState), EbayCancellations::$cancelStateMap);
                $model->cancel_status = array_search(trim($cancellation->cancelStatus), EbayCancellations::$cancelStatusMap);
                if (isset($cancellation->cancelCloseReason))
                    $model->cancel_close_reason = array_search(trim($cancellation->cancelCloseReason), EbayCancellations::$ReasonMap);
                if (isset($cancellation->sellerResponseDueDate))
                    $model->seller_response_due_date = explode('.', str_replace('T', ' ', $cancellation->sellerResponseDueDate->value))[0];
                $model->payment_status = array_search(trim($cancellation->paymentStatus), EbayCancellations::$paymentStatusMap);
                if (isset($cancellation->requestRefundAmount)) {
                    $model->request_refund_amount = $cancellation->requestRefundAmount->value;
                    $model->currency = $cancellation->requestRefundAmount->currency;
                }
                $model->cancel_request_date = explode('.', str_replace('T', ' ', $cancellation->cancelRequestDate->value))[0];
                if (isset($cancellation->cancelCloseDate))
                    $model->cancel_close_date = explode('.', str_replace('T', ' ', $cancellation->cancelCloseDate->value))[0];
                if (isset($cancellation->buyerResponseDueDate))
                    $model->buyer_response_due_date = explode('.', str_replace('T', ' ', $cancellation->buyerResponseDueDate->value))[0];
                if (isset($cancellation->cancelReason))
                    $model->cancel_reason = array_search(trim($cancellation->cancelReason), EbayCancellations::$ReasonMap);
                if (isset($cancellation->shipmentDate))
                    $model->shipment_date = explode('.', str_replace('T', ' ', $cancellation->shipmentDate->value))[0];
                //if(empty($model->buyer) && isset($cancellation->legacyOrderId))
                if (isset($cancellation->legacyOrderId)) {
                    $orderinfo = Order::getOrderStack('EB', $cancellation->legacyOrderId);
                    if (!empty($orderinfo)) {
                        $orderinfo = Json::decode(Json::encode($orderinfo), true);
                        $buyer = $orderinfo['info']['buyer_id'];
                        if ($orderinfo['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) {
                            $orderid = $orderinfo['info']['order_id'];
                        }
                    } else {
                        $buyer = "";
                    }
                    $model->buyer = $buyer;
                }
                $model->account_id = $this->accountId;
                $model->update_time = $currentTime;
                try {
                    $this->flag = $model->save();
                    if (!$this->flag)
                        $this->apiTaskModel->error .= '[错误码：' . $this->errorCode . '。cancelID:' . $cancellation->cancelId . '主表信息保存出错。' . VHelper::getModelErrors($model) . ']';
                } catch (Exception $e) {
                    $this->flag = false;
                    $this->apiTaskModel->error .= '[错误码：' . $this->errorCode . '。cancelID:' . $cancellation->cancelId . '主表信息保存出错。' . $e->getMessage() . ']';
                }
                if (!$this->flag) {
                    $this->apiTaskModel->status = 1;
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                }
            }
            
            if ($orderid && $model->isNewRecord) {
                Order::holdOrder('EB', $orderid, "客户系统检测到客户已开取消订单纠纷");
            }
            if (isset($this->transaction)) {
                if ($this->flag) {
                    $this->transaction->commit();
//                    if ($orderid) {
//                        Order::holdOrder('EB', $orderid, "客户系统检测到客户已开取消订单纠纷");
//                    }
                } else {
                    $this->transaction->rollback();
                }
                $this->transaction = null;
            }

            if (!empty($cancellation->legacyOrderId)) {
                //如果取消订单纠纷拉到时，ERP还没有把订单拉下来，则先把该订单存进cancel_order_handle
                $result = Order::getOrderStack(Platform::PLATFORM_CODE_EB, $cancellation->legacyOrderId);
                if (empty($result)) {
                    $cancelOrderHandle = CancelOrderHandle::findOne([
                                'platform_code' => Platform::PLATFORM_CODE_EB,
                                'account_id' => $this->accountId,
                                'platform_order_id' => $cancellation->legacyOrderId,
                    ]);
                    if (empty($cancelOrderHandle)) {
                        $cancelOrderHandle = new CancelOrderHandle();
                        $cancelOrderHandle->status = 0;
                        $cancelOrderHandle->pull_time = date('Y-m-d H:i:s');
                    }
                    $cancelOrderHandle->platform_code = Platform::PLATFORM_CODE_EB;
                    $cancelOrderHandle->account_id = $this->accountId;
                    $cancelOrderHandle->platform_order_id = $cancellation->legacyOrderId;
                    $cancelOrderHandle->save();
                } else {
                    //如果在ERP中找到订单，并且该订单状态为暂扣，则更新cancel_order_handle表数据

                    $result = Json::decode(Json::encode($result), true);
                    if ($result['info']['complete_status'] == Order::COMPLETE_STATUS_HOLD) {
                        $cancelOrderHandle = CancelOrderHandle::findOne([
                                    'platform_code' => Platform::PLATFORM_CODE_EB,
                                    'account_id' => $this->accountId,
                                    'platform_order_id' => $cancellation->legacyOrderId,
                                    'status' => 0,
                        ]);
                        if (!empty($cancelOrderHandle)) {
                            $cancelOrderHandle->status = 1;
                            $cancelOrderHandle->handle_time = date('Y-m-d H:i:s');
                            $cancelOrderHandle->save();
                        }
                    }
                }
            }
        }
    }

    private function detailApi($token, $site, $serverUrl, $method) {
        /* $api = new PostOrderAPI($token,$site,$serverUrl,$method);
          $response = $api->sendHttpRequest();
          if(empty($response))
          {
          $this->apiTaskModel->status = 1;
          $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}拉取无数据。]";
          $this->apiTaskModel->save();
          $this->errorCode++;
          }
          else
          $this->handleDetailResponse($response); */

        $transfer_ip = include \Yii::getAlias('@app') . '/config/transfer_ip.php';
        $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';

        $post_data = ['serverUrl' => $serverUrl, 'authorization' => $token, 'data' => '', 'method' => $method, 'responseHeader' => false, 'urlParams' => ''];
        $api = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
        $api->setData($post_data);
        $response = $api->sendHttpRequest();
        if (empty($response)) {
            $this->apiTaskModel->status = 1;
            $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}拉取中转站无数据。]";
            $this->apiTaskModel->save();
            $this->errorCode++;
        } else {
            if (in_array($response->code, [200, 201, 202])) {
                $response = json_decode($response->response);
                $this->handleDetailResponse($response);
            } else {
                $this->apiTaskModel->status = 1;
                $this->apiTaskModel->error .= "[错误码：{$this->errorCode}。{$serverUrl}拉取无数据。]";
                $this->apiTaskModel->save();
                $this->errorCode++;
            }
        }
    }

    private function handleDetailResponse($data) {
        $cancelId = $data->cancelDetail->cancelId;
        $this->transaction = EbayCancellations::getDb()->beginTransaction();
        if (!empty($data->cancelDetail->activityHistories)) {
            EbayCancellationsDetail::deleteAll(['cancel_id' => $cancelId]);
            foreach ($data->cancelDetail->activityHistories as $historie) {
                $detailModel = new EbayCancellationsDetail();
                $detailModel->cancel_id = $cancelId;
                $detailModel->activity_type = $historie->activityType;
                $detailModel->activity_party = $historie->activityParty;
                $detailModel->action_date = explode('.', str_replace('T', ' ', $historie->actionDate->value))[0];
                $detailModel->state_from = $historie->cancelStateFrom;
                $detailModel->state_to = $historie->cancelStatetateTo;
                try {
                    $this->flag = $detailModel->save();
                    if (!$this->flag)
                        $this->apiTaskModel->error .= '[错误码：' . $this->errorCode . '。cancelID:' . $cancelId . '历史信息保存出错。' . VHelper::getModelErrors($detailModel) . ']';
                } catch (Exception $e) {
                    $this->flag = false;
                    $this->apiTaskModel->error .= '[错误码：' . $this->errorCode . '。cancelID:' . $cancelId . '历史信息保存出错。' . $e->getMessage() . ']';
                }
                if (!$this->flag) {
                    $this->apiTaskModel->status = 1;
                    $this->apiTaskModel->save();
                    $this->errorCode++;
                    break;
                }
            }
        }
    }

    /**
     * http://www.customer.com/services/ebay/cancellationsearch/testget
     */
    public function actionTestget() {
        $userToken = 'AgAAAA**AQAAAA**aAAAAA**TfuzWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6ABlYepDpmDpgmdj6x9nY+seQ**sHQDAA**AAMAAA**Uvf0WNB6wQz8bv+PLR0o8XopmNN/JwBBMn4DuV3Sj0rV0bAdL5g82zTM97wraMxRUDqwjTpv8KuAFDyIEwQ4e/gpKit7educDEGxmM+24a4F44yU8E1froz1lSI0yUdh435ntgoCO+YxYdY7dEMl/Mjz6eSs5VF3RAJzf2j7GIIusnejyi/NWJvUnZFysYmKfsOQ7vZn6MXOPtcxXpir2vxvn6YpwY9VWHDIokunML1H9I0wKXIf7ijjrbT2eYx86TfHOeLDl4RH8rapva8ePWGU3bXBqtt2Q1qYJvDxM+csh1FlIyG/DkJslBZf11TOv6ILCBUfAzURu3qnQ2WVefIa6ivJw4sjQa6QB6ZL3qCboHNJniKH2ZY1aes/uCXVbS8QN/8WuoUArosjlFMR82hxSdcZpD6wmfvhyIW8R1HwLHxxrWU+Yb5AgBW1MHQs0xQ4S3Zf8DB50RkSGvy1a8uNCFcXDjQUvVXyFzMOzNbDoNVw1YjuRBMcRaRXQyKPzBjhN0slmgiayaSIY4fy9LfFG3Q8mbbn1BjAQx4bpg501+OyhHYh2Rn9sznir7WR+MucQ9O5/hAbIM4iADKo6uA/Eb5BTF+vQVez+qSOEnmNgEv4Kn2OVNX1ZRNZl5uEa3MK4eJnsymqFMca5M2pUk2+9h6ykB4prYmmlnMoOPklXkPsy3XQRpNk24Kxh/lhryjLP9fSoUMmMtw2TGX2HtcYBtX0JTWNSHblNM7KtPJtniaPmKQIJH4shTo4B82v';
        $api = new PostOrderAPI($userToken, '', 'https://api.ebay.com/post-order/v2/cancellation/5095088022', 'get');
        $response = $api->sendHttpRequest();
        findClass($response, 1);
    }

    public function actionTesthandle() {
//        echo gethostbyname('api.ebay.com');exit;

        $userToken = 'AgAAAA**AQAAAA**aAAAAA**TPa0WA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6ABlYepDpiKpg+dj6x9nY+seQ**sHQDAA**AAMAAA**0V77yjue9OsUzywc6Kk9dILbO37Jfv5MwjJAhDkZt937Ke5mwK+S2Ox8Sl3E5NHAPS3pVbD9P11T2o0lVQl0g8+vKCGMZ0arcPlOLSrWacxwu25N78Jls6Lnq0Kj9ovDlXJsMV7k8Ydma7I4ZtX8Zbf+2cHQ/aoL6AuHFViv/dHUPJMj4fur8zRN2++e1hNWpmv9YHiI6Alf0hVw0hnANyEaqDngSHrc9tpSAE7aIqNDEh4WwuaiBX/RbNDZRt4isxYwmD52+11Hgs4GamE7mvcIV7cWTs1aH6rGmmrLU7f+7Sfm3JqIbxrX36KghWR00xe9uGus/7V6PNGfXqT9PuLIfDNU9hK5100cSZUKgnLioDMoECBOEfiE9+Vyy9eJpT6A1sebeuA8Dc/un94B30EMxobOROlevmSlfva1CZk9nbK+PCrnd03GhDrrzYm2qd0Ble2p6gN+MP/dN8/2y8ghmhXFIrXdOoF92lN4J9325c/XiSC75DzBU7pYtZ9rxuBv2q2RU5oscczUm6HxXBBRUcd36H/0BkeyWJBJwy+rzEDvBIUkyIfIJpeyxS6cu5172kCATiyAwSnWWdFJEm+PReqWHOKKEEhHevrFFJXJ0GO7RIQM/85KdX112zg2IdsCIP5TrSKQ0MCWRbONNTOIaos58H/RHnuz7V49RBy4NGqg+DFxqhX14sifY6460t2LhYaBoWUfLBGMQD4vk7Lwk7ziXCvpsbHU1rCjPk//q+YqF5vT1OIDgItFQhNc';
        $api = new PostOrderAPI($userToken, '', 'https://api.ebay.com/post-order/v2/cancellation/5070861933/approve', 'post');
        $api->setData(['']);
        $api->responseHeader = true;
        $response = $api->sendHttpRequest('json');
        findClass($api->getHttpCode(), 1, 0);
        findClass($response, 1);
    }

}
