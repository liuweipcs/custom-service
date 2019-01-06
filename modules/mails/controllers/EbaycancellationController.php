<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/15 0015
 * Time: 下午 6:56
 */

namespace app\modules\mails\controllers;

use app\common\VHelper;
use app\components\Controller;
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\EbayCancellationsDetail;
use app\modules\mails\models\EbayCancellationsResponse;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderKefu;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use PhpImap\Exception;
use yii\helpers\Json;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Updatestatustask;
use Yii;
use app\modules\aftersales\models\AfterSalesProduct;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\reports\models\DisputeStatistics;
use app\modules\orders\models\EbayOnlineListing;
use app\modules\users\models\UserRole;

class EbaycancellationController extends Controller
{
    /**
     *
     * @return \yii\base\string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex()
    {
        $model        = new EbayCancellations();
        $params       = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('index', [
            'model'        => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @return string
     */
    public function actionHandle()
    {
        $this->isPopup = true;
        $id            = $this->request->get('id');
        $isout         = $this->request->get('isout');    // 如果是其他地方调用此接口，返回的时候直接刷新页面

        if (is_numeric($id) && $id > 0 && $id % 1 === 0) {
            $cancellationsModel = EbayCancellations::findOne((int)$id);
            $ebayAccountModel   = Account::findById((int)$cancellationsModel->account_id);
            $accountName        = $ebayAccountModel->account_name;
            if ($data = $this->request->post('EbayCancellationsResponse')) {
                //订单ID
                $order_id = $this->request->post('order_id');

//                $ebayAccountModel = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
                $responseModel            = new EbayCancellationsResponse();
                $responseModel->cancel_id = $cancellationsModel->cancel_id;
                $responseModel->type      = $data['type'];
                $flag                     = true;
                set_time_limit(120);
                $warnInfo = '';//提示的警告信息

                $transfer_ip = include \Yii::getAlias('@app') . '/config/transfer_ip.php';
                $transfer_ip = isset($transfer_ip['url']) ? $transfer_ip['url'] : '';

                try {
                    switch ($data['type']) {
                        case '1':
                            $responseModel->explain         = $data['explain'];
                            $responseModel->tracking_number = '';
                            $serverUrl = 'https://api.ebay.com/post-order/v2/cancellation/' . $responseModel->cancel_id . '/approve';
                            $data1     = json_encode(['']);
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => $data1, 'method' => 'post', 'responseHeader' => true, 'urlParams' => ''];
                            $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();
                            if (empty($response)) {
                                $this->_showMessage('中转服务器无响应，请稍候再试！', false);
                            } else {
                                $httpCode = $response->code;
                                $response = $response->response;
                                if (in_array($httpCode, [200, 201, 202, 204])) {
                                    $result_cancel_order = Order::cancelOrder(Platform::PLATFORM_CODE_EB, null, $cancellationsModel->legacy_order_id, $data['explain']);
                                    if ($result_cancel_order !== true) {
                                        //insert一条失败的数据
                                        Updatestatustask::insertOne(Platform::PLATFORM_CODE_EB, $cancellationsModel->legacy_order_id);
                                        $warnInfo = 'eaby接口处理成功，但是erp永久作废订单失败';
                                    }
                                }
                            }
                            break;
                        case '2':
                            $serverUrl = 'https://api.ebay.com/post-order/v2/cancellation/' . $responseModel->cancel_id . '/reject';
                            $data1     = json_encode(['shipmentDate' => ['value' => $responseModel->shipment_date . '.000Z'], 'trackingNumber' => $responseModel->tracking_number]);
                            $post_data = ['serverUrl' => $serverUrl, 'authorization' => $ebayAccountModel->user_token, 'data' => $data1, 'method' => 'post', 'responseHeader' => true, 'urlParams' => ''];
                            $api       = new PostOrderAPI('ceshi', '', $transfer_ip, 'post');
                            $api->setData($post_data);
                            $response = $api->sendHttpRequest();

                            if (empty($response))
                                $this->_showMessage('中转服务器无响应，请稍候再试！', false);
                            else {
                                $httpCode = $response->code();
                                $response = $response->response;
                            }

                            break;
                    }
                } catch (Exception $e) {
                    $flag      = false;
                    $errorInfo = $e->getMessage();
                }

                if ($flag) {
                    $responseModel->account_id  = $cancellationsModel->account_id;
                    $responseModel->status      = 1;
                    $responseModel->lock_status = 0;
                    if (is_numeric($httpCode)) {
                        if (in_array($httpCode, [200, 204])) {
                            $responseModel->status = 1;
                        } else {
                            $responseModel->status = 0;
                            $errorInfo             = "状态码：{$httpCode}";
                            $flag                  = false;
                        }
                    } else {
                        $responseModel->status = 0;
                        $errorInfo             = empty($response) ? '无返回值' : serialize($response);
                        $flag                  = false;
                    }
                    if ($flag) {
                        try {
                            $flag = $responseModel->save();
                            if (!$flag) {
                                $errorInfo = VHelper::getModelErrors($responseModel);
                            }

                            //如果点击接受，添加问题产品数据表
                            if ($flag && $data['type'] == 1) {
                                //获取订单信息
                                $orderinfo = array();
                                if (!empty($cancellationsModel->legacy_order_id)) {
                                    $orderinfo = Order::getOrderStack(Platform::PLATFORM_CODE_EB, $cancellationsModel->legacy_order_id);
                                }

                                if (!empty($orderinfo)) {
                                    $orderinfo = Json::decode(Json::encode($orderinfo), true);
                                    //循环的把问题产品信息插入数据库
                                    foreach ($orderinfo['product'] as $item) {
                                        $afterSaleProduct                   = new AfterSalesProduct();
                                        $afterSaleProduct->platform_code    = Platform::PLATFORM_CODE_EB;
                                        $afterSaleProduct->order_id         = $order_id;
                                        $afterSaleProduct->sku              = $item['sku'];
                                        $afterSaleProduct->product_title    = $item['picking_name'];
                                        $afterSaleProduct->quantity         = $item['quantity'];
                                        $afterSaleProduct->linelist_cn_name = $item['linelist_cn_name'];
                                        $afterSaleProduct->issue_quantity   = $item['quantity'];
                                        $afterSaleProduct->reason_id        = '';
                                        $afterSaleProduct->after_sale_id    = '';
                                        //添加问题产品数据
                                        if (!$afterSaleProduct->save()) {
                                            $errorInfo = VHelper::getModelErrors($afterSaleProduct);
                                        }
                                    }
                                }
                            }

                        } catch (Exception $e) {
                            $flag      = false;
                            $errorInfo = $e->getMessage();
                        }
                    }
                }

                if ($flag) {
                    $refresh = $cancellationsModel->refreshApi();
                    $flag    = $refresh['flag'];
                    if (!$refresh['flag']) {
                        $errorInfo = '更新失败';
                    }
                }

                if ($flag) {
                    //取消交易纠纷处理
                    $disputeStatistics = DisputeStatistics::findOne(['dispute_id' => $cancellationsModel->cancel_id, 'type' => AccountTaskQueue::TASK_TYPE_CANCELLATION, 'platform_code' => Platform::PLATFORM_CODE_EB]);
                    if ($disputeStatistics && $disputeStatistics->status == 0) {
                        $disputeStatistics->status = 1;
                        $disputeStatistics->reply  = Yii::$app->user->identity->user_name;
                        $disputeStatistics->save(false);
                    }
                    if ($isout)
                        $this->_showMessage(\Yii::t('system', 'Operate Successful') . $warnInfo, true);
                    $this->_showMessage(\Yii::t('system', 'Operate Successful') . $warnInfo, true, null, false, null,
                        'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebaycancellation/index') . '");', true, 'msg');
                } else {
                    $this->_showMessage(\Yii::t('system', 'Operate Failed') . '。' . $errorInfo, false);
                }
            }

            //获取订单信息
            $orderinfo = [];
            if (!empty($cancellationsModel->legacy_order_id)) {
                $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_EB, $cancellationsModel->legacy_order_id);
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
                            $data         = [];
                            $stock        = isset($data['available_stock']) ? $data['available_stock'] : 0;
                            $on_way_count = isset($data['on_way_stock']) ? $data['on_way_stock'] : 0;

//                            $data = VHelper::getProductStockAndOnCount($value['sku'], $orderinfo['wareh_logistics']['warehouse']['warehouse_code']);
//                            $stock = $data['available_stock'];
//                            $on_way_count = $data['on_way_stock'];
                        }
                        $orderinfo['product'][$key]['stock']        = $stock;
                        $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;
                    }
                }
                //付款帐号与收款帐号
                if (!empty($orderinfo['trade'])) {
                    foreach ($orderinfo['trade'] as $key => $value) {
                        $transactionId = $value['transaction_id'];
                        $PayMessage    = VHelper::getTransactionAccount($transactionId);
                        //var_dump($PayMessage);exit;
                        if (!empty($PayMessage) && isset($PayMessage[$key])) {
                            // var_dump($PayMessage);exit;
                            $orderinfo['trade'][$key]['receiver_business'] = $PayMessage[$key]['receiver_business'];
                            $orderinfo['trade'][$key]['payer_email']       = $PayMessage[$key]['payer_email'];
                        } else {
                            $orderinfo['trade'][$key]['receiver_business'] = "暂无信息";
                            $orderinfo['trade'][$key]['payer_email']       = "暂无信息";
                        }
                    }
                }
            }
            $detailModel = EbayCancellationsDetail::find()->where(['cancel_id' => $cancellationsModel->cancel_id])->orderBy('action_date DESC')->all();

            //加黑名单解决方案  部门主管及以上
            $isAuthority = false;
            if (UserRole::checkManage(Yii::$app->user->identity->id)) {
                $isAuthority = true;
            }

            return $this->render('handles/index', [
                'order_id'    => $cancellationsModel->legacy_order_id,
                'info'        => $orderinfo,
                'model'       => $cancellationsModel,
                'detailModel' => $detailModel,
                'accountName' => $accountName,
                'isAuthority' => $isAuthority
            ]);
        }
    }

    public function actionRefresh()
    {
        $id = $this->request->get('id');
        if (is_numeric($id) && $id > 0 && $id % 1 === 0) {
            try {
                $result = EbayCancellations::findOne((int)$id)->refreshApi();
            } catch (\Exception $e) {
                $result['flag'] = false;
                $result['info'] = $e->getMessage() . '。文件：' . $e->getFile() . ',行号：' . $e->getLine() . '。';
            }
            if ($result['flag']) {
                $this->_showMessage('更新成功。', true, null, false, null,
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebaycancellation/index') . '");', true, 'msg');
            } else {
                $this->_showMessage('更新失败。' . $result['info'], false);
            }
        }
    }

    public function actionBatchrefresh()
    {
        $ids       = $this->request->post('ids');
        $cancelids = '';
        foreach ($ids as $key => $id) {
            $result = EbayCancellations::findOne((int)$id)->refreshApi();
            if ($result['flag']) {
                continue;
            } else {
                $cancelids .= EbayCancellations::getCancelIDByID($id) . ',';
            }
        }
        if ($cancelids) {
            $cancelids = trim($cancelids, ',');
            $this->_showMessage('部分数据更新失败，cancel_id如下所示：' . $cancelids, true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebaycancellation/index') . '");', true, 'msg');
        } else {
            $this->_showMessage('更新成功。', true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebaycancellation/index') . '");');
        }

    }
}