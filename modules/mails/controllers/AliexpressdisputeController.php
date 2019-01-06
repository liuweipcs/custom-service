<?php

namespace app\modules\mails\controllers;

use app\modules\mails\models\AliexpressDisputeAttachments;
use app\modules\mails\models\AliexpressDisputeProcess;
use app\modules\mails\models\AliexpressHolidayResponseTime;
use app\modules\orders\models\OrderAliexpressKefu;
use app\modules\systems\models\AftersaleManage;
use app\modules\systems\models\BasicConfig;
use Yii;
use app\components\Controller;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressDisputeDetail;
use app\modules\mails\models\AliexpressDisputeSolution;
use app\common\VHelper;
use app\modules\orders\models\OrderKefu;
use app\modules\services\modules\aliexpress\models\OrderMessage;
use app\modules\services\modules\aliexpress\models\OrdeArbitration;
use app\modules\services\modules\aliexpress\models\GoodsReceipt;
use app\modules\services\modules\aliexpress\models\WaiverReturns;
use app\modules\accounts\models\Platform;
use yii\helpers\Json;
use app\modules\orders\models\Order;
use app\modules\mails\models\AliexpressExpression;
use app\modules\orders\models\Warehouse;
use app\modules\systems\models\Country;
use app\modules\orders\models\Transactionrecord;
use app\modules\accounts\models\Account;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\RefundAccount;
use app\modules\services\modules\aliexpress\models\AliexpressOrder;
use yii\web\UploadedFile;
use yii\helpers\Url;
use app\modules\users\models\UserRole;

class AliexpressdisputeController extends Controller
{
    const ISSUE_UPLOAD_PATH = './uploads/issue/';
    //最大纠纷图片大小2M
    const MAX_ISSUE_IMAGE_SIZE = 2097152;

    /**
     * 列表
     */
    public function actionList()
    {
        $params = \Yii::$app->request->getBodyParams();
        $model = new AliexpressDisputeList();
        $dataProvider = $model->searchList($params);

        //获取节假日纠纷响应时间
        $timeList = AliexpressHolidayResponseTime::find()
            ->where(['status' => 1])
            ->orderBy('id DESC')
            ->asArray()
            ->all();

        return $this->renderList('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'timeList' => $timeList,
        ]);
    }

    /**
     * 显示纠纷详情
     */
    public function actionShoworder()
    {
        $this->isPopup = true;

        //获取纠纷ID
        $issueId = Yii::$app->request->get('issue_id');

        if (empty($issueId)) {
            $this->_showMessage('纠纷ID不能为空', false);
        }

        //获取纠纷列表信息
        $issueList = AliexpressDisputeList::find()->where(['platform_dispute_id' => $issueId])->asArray()->one();
        if (empty($issueList)) {
            $this->_showMessage('没有找到纠纷信息', false);
        }
        //平台订单ID
        $orderId = $issueList['platform_order_id'];

        //获取纠纷详情
        $issueInfo = AliexpressDisputeDetail::find()->where(['platform_dispute_id' => $issueId])->asArray()->one();

        //获取纠纷协商方案
        $issueSolution = AliexpressDisputeSolution::find()->where(['platform_dispute_id' => $issueId])->asArray()->all();
        if (!empty($issueSolution)) {
            $tmp = [];
            foreach ($issueSolution as $solution) {
                $tmp[$solution['solution_owner']][] = $solution;
            }
            $issueSolution = $tmp;
        }

        //获取订单信息
        $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_ALI, $orderId);

        if (empty($orderinfo)) {
            //如果订单信息为空，通过父级订单ID来查询订单信息
            $orderId = $issueList['platform_parent_order_id'];
            $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_ALI, $orderId);
        }

        if (!empty($orderinfo)) {
            $orderinfo = Json::decode(Json::encode($orderinfo), true);
        } else {
            $orderinfo = [];
        }

        //如果在erp没获取到交易信息  则在客服系统重新获取一遍
        if (!empty($orderinfo['trade'])) {
            foreach ($orderinfo['trade'] as $key => $value) {
                if ($value['receiver_email'] == "" || $value['payer_email'] == "") {
                    $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->asArray()->one();
                    if (!empty($transactionRecord)) {
                        $orderinfo['trade'][$key]['receiver_email'] = $transactionRecord['receiver_email'];
                        $orderinfo['trade'][$key]['payer_email'] = $transactionRecord['payer_email'];
                        $orderinfo['trade'][$key]['payment_status'] = $transactionRecord['payment_status'];
                    }
                }
            }
        }

        //Amazon订单信息加上FBA/FBM信息
        if (!empty($orderinfo['info'])) {
            if ($orderinfo['info']['amazon_fulfill_channel'] == 'AFN') {
                $orderinfo['info']['amazon_fulfill_channel'] = 'FBA';
            }
            if ($orderinfo['info']['amazon_fulfill_channel'] == 'MFN') {
                $orderinfo['info']['amazon_fulfill_channel'] = 'FBM';
            }
            $orderinfo['info']['product_weight'] = 0;

            //如果是速卖通平台的订单，获取买家剩余收货时间
            if ($orderinfo['info']['platform_code'] == Platform::PLATFORM_CODE_ALI) {
                $platformOrderId = $orderinfo['info']['platform_order_id'];
                $platformOrderIdArr = explode('-', $platformOrderId);
                if (!empty($platformOrderIdArr) && count($platformOrderIdArr) == 2) {
                    $platformOrderId = $platformOrderIdArr[0];
                }

                $newOrderInfo = AliexpressOrder::getNewOrderInfo($platformOrderId, $orderinfo['info']['account_id']);
                if (!empty($newOrderInfo) && !empty($newOrderInfo['target']['over_time_left'])) {
                    //买家确认收货结束时间
                    $orderinfo['info']['buyer_accept_goods_end_time'] = $newOrderInfo['target']['over_time_left'];
                }
            }
        }

        //组装库存和在途数
        if (!empty($orderinfo['product'])) {
            $result = isset($orderinfo['wareh_logistics']) && isset($orderinfo['wareh_logistics']['warehouse']);
            foreach ($orderinfo['product'] as $key => $value) {

                $orderinfo['info']['product_weight'] += $value['product_weight'] * $value['quantity'];

                list($stock, $on_way_count) = [null, null];
                if ($result) {
                    $data = [];
                    $stock = isset($data['available_stock']) ? $data['available_stock'] : 0;
                    $on_way_count = isset($data['on_way_stock']) ? $data['on_way_stock'] : 0;
                }
                $orderinfo['product'][$key]['stock'] = $stock;
                $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;

                //如果有产品的asinval则组装产品详情的链接地址
                if (isset($value['asinval'])) {
                    $orderinfo['product'][$key]['detail_link_href'] = '#';
                    $orderinfo['product'][$key]['detail_link_title'] = null;
                    $site_code = Account::getSiteCode($orderinfo['info']['account_id'], Platform::PLATFORM_CODE_AMAZON);

                    if (!empty($value['asinval']) && !empty($site_code)) {
                        $link_info = VHelper::getProductDetailLinkHref($site_code, $value['asinval']);
                        $orderinfo['product'][$key]['detail_link_href'] = $link_info['href'];
                        $orderinfo['product'][$key]['detail_link_title'] = $link_info['title'];
                    }
                }
            }
        }

        //获取售后信息
        $afterSalesOrders = [];
        if (!empty($orderinfo['info']['order_id'])) {
            $afterSalesOrders = AfterSalesOrder::getByOrderId(Platform::PLATFORM_CODE_ALI, $orderinfo['info']['order_id']);
        }

        $countires = Country::getCodeNamePairsList();
        //获取仓库列表
        $warehouseList = Warehouse::getWarehouseListAll();

        //获取paypal账号信息
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }

        return $this->render('issueinfo/index', [
            'order_id' => $orderId,
            'issue_id' => $issueId,
            'issueInfo' => $issueInfo,
            'issueList' => $issueList,
            'issueSolution' => $issueSolution,
            'info' => $orderinfo,
            'paypallist' => $palPalList,
            'countries' => $countires,
            'warehouseList' => $warehouseList,
            'afterSalesOrders' => $afterSalesOrders,
            'platform' => Platform::PLATFORM_CODE_ALI,
            'isAuthority' => $isAuthority,
        ]);
    }

    /**
     * 更新纠纷信息
     */
    public function actionUpdateissueinfo()
    {
        $issueId = !empty($_REQUEST['issue_id']) ? trim($_REQUEST['issue_id']) : '';
        
        if (empty($issueId)) {
            die(json_encode([
                'code' => 0,
                'message' => '纠纷ID不能为空',
            ]));
        }

        //通过速卖通接口获取纠纷信息
        $issueInfo = AliexpressOrder::getOrderIssueInfo($issueId);
        if (empty($issueInfo)) {
            die(json_encode([
                'code' => 0,
                'message' => '获取纠纷详情失败',
                'data' => ['issue_id' => $issueId],
            ]));
        }

        //纠纷列表中的信息
        $disputeList = AliexpressDisputeList::find()->where(['platform_dispute_id' => $issueId])->one();
        //账号ID
        $accountId = !empty($disputeList['account_id']) ? $disputeList['account_id'] : '';

        if (!empty($issueInfo['issue_status']) && ($disputeList->issue_status != $issueInfo['issue_status'])) {
            $disputeList->issue_status = $issueInfo['issue_status'];
            $disputeList->modify_by = Yii::$app->user->identity->login_name;
            $disputeList->modify_time = date('Y-m-d H:i:s', time());

            if ($disputeList->save(false) === false) {
                die(json_encode([
                    'code' => 0,
                    'message' => '更新纠纷列表失败',
                    'data' => ['issue_id' => $issueId],
                ]));
            }
        }

        //纠纷完结,设置成已处理状态
        if (!empty($issueInfo['issue_status']) && $issueInfo['issue_status'] == 'finish') {
            $disputeList->is_handle = 1;
            $disputeList->save();
        }

        //纠纷详情中的信息
        $disputeDetail = AliexpressDisputeDetail::find()->where(['platform_dispute_id' => $issueId])->one();
        if (empty($disputeDetail)) {
            $disputeDetail = new AliexpressDisputeDetail();
            $disputeDetail->create_by = Yii::$app->user->identity->login_name;
            $disputeDetail->create_time = date('Y-m-d H:i:s', time());
            $disputeDetail->platform_dispute_id = $issueId;
            $disputeDetail->account_id = $accountId;
            $disputeDetail->buyer_login_id = $issueInfo['buyer_login_id'];
            $disputeDetail->platform_parent_order_id = !empty($issueInfo['parent_order_id']) ? $issueInfo['parent_order_id'] : '';
            $disputeDetail->platform_order_id = $issueInfo['order_id'];
            $disputeDetail->buyer_aliid = '';
            $disputeDetail->issue_reason_id = !empty($issueInfo['issue_reason_id']) ? $issueInfo['issue_reason_id'] : '';
            $disputeDetail->issue_reason = !empty($issueInfo['issue_reason']) ? $issueInfo['issue_reason'] : '';
            $disputeDetail->refund_money_max = $issueInfo['refund_money_max'];
            $disputeDetail->refund_money_max_local = $issueInfo['refund_money_max_local'];
            $disputeDetail->product_name = $issueInfo['product_name'];
            $disputeDetail->product_price = $issueInfo['product_price'];
            $disputeDetail->gmt_create = $issueInfo['gmt_create'];
            $disputeDetail->refund_money_max_currency = $issueInfo['refund_money_max_currency'];
            $disputeDetail->refund_money_max_local_currency = $issueInfo['refund_money_max_local_currency'];
            $disputeDetail->product_price_currency = $issueInfo['product_price_currency'];
            $disputeDetail->after_sale_warranty = !empty($issueInfo['after_sale_warranty']) ? intval($issueInfo['after_sale_warranty']) : 0;
        }
        $disputeDetail->buyer_return_logistics_company = !empty($issueInfo['buyer_return_logistics_company']) ? $issueInfo['buyer_return_logistics_company'] : '';
        $disputeDetail->buyer_return_no = !empty($issueInfo['buyer_return_no']) ? $issueInfo['buyer_return_no'] : '';
        $disputeDetail->buyer_return_logistics_lp_no = !empty($issueInfo['buyer_return_logistics_lp_no']) ? $issueInfo['buyer_return_logistics_lp_no'] : '';
        $disputeDetail->issue_status = !empty($issueInfo['issue_status']) ? $issueInfo['issue_status'] : '';
        $disputeDetail->modify_by = Yii::$app->user->identity->login_name;
        $disputeDetail->modify_time = date('Y-m-d H:i:s', time());
        if (!empty($issueInfo['platform_solution_list'])) {
             $disputeDetail->refund_money_post =  $issueInfo['platform_solution_list']['solution_api_dto'][0]['refund_money_post'];
             $disputeDetail->refund_money_post_currency =  $issueInfo['platform_solution_list']['solution_api_dto'][0]['refund_money_post_currency'];
             $disputeDetail->solution_owner = $issueInfo['platform_solution_list']['solution_api_dto'][0]['solution_owner'];
        }elseif(!empty($issueInfo['seller_solution_list'])){
             $disputeDetail->refund_money_post =  $issueInfo['seller_solution_list']['solution_api_dto'][0]['refund_money_post'];
             $disputeDetail->refund_money_post_currency =  $issueInfo['seller_solution_list']['solution_api_dto'][0]['refund_money_post_currency'];
             $disputeDetail->solution_owner = $issueInfo['seller_solution_list']['solution_api_dto'][0]['solution_owner'];
        }elseif(!empty($issueInfo['buyer_solution_list'])){
             $disputeDetail->refund_money_post =  $issueInfo['buyer_solution_list']['solution_api_dto'][0]['refund_money_post'];
             $disputeDetail->refund_money_post_currency =  $issueInfo['buyer_solution_list']['solution_api_dto'][0]['refund_money_post_currency'];
             $disputeDetail->solution_owner = 'seller';     
        }elseif($issueInfo['issue_status'] == 'canceled_issue'){
            $disputeDetail->refund_money_post = '0.00';
            $disputeDetail->refund_money_post_currency = 'USD';
            $disputeDetail->solution_owner = 'not_issue';
        }

        if ($disputeDetail->save(false) === false) {
            die(json_encode([
                'code' => 0,
                'message' => '更新纠纷详情失败',
                'data' => ['issue_id' => $issueId],
            ]));
        }

        //纠纷协商方案
        $solutionList = [];
        if (!empty($issueInfo['seller_solution_list'])) {
            $solutionList = array_merge($solutionList, $issueInfo['seller_solution_list']['solution_api_dto']);
        }
        if (!empty($issueInfo['buyer_solution_list'])) {
            $solutionList = array_merge($solutionList, $issueInfo['buyer_solution_list']['solution_api_dto']);
        }
        if (!empty($issueInfo['platform_solution_list'])) {
            $solutionList = array_merge($solutionList, $issueInfo['platform_solution_list']['solution_api_dto']);
        }
        if (!empty($solutionList)) {
            foreach ($solutionList as $solution) {
                $disputeSolution = AliexpressDisputeSolution::find()->where([
                    'platform_dispute_id' => $issueId,
                    'solution_id' => $solution['id'],
                ])->one();

                if (empty($disputeSolution)) {
                    $disputeSolution = new AliexpressDisputeSolution();
                    $disputeSolution->create_by = Yii::$app->user->identity->login_name;
                    $disputeSolution->create_time = date('Y-m-d H:i:s', time());
                }
                $disputeSolution->platform_dispute_id = $issueId;
                $disputeSolution->account_id = $accountId;
                $disputeSolution->seller_ali_id = '';
                $disputeSolution->gmt_modified = $solution['gmt_modified'];
                $disputeSolution->order_id = $solution['order_id'];
                $disputeSolution->refund_money = $solution['refund_money'];
                $disputeSolution->refund_money_currency = $solution['refund_money_currency'];
                $disputeSolution->gmt_create = $solution['gmt_create'];
                $disputeSolution->version = !empty($solution['version']) ? $solution['version'] : '';
                $disputeSolution->content = !empty($solution['content']) ? $solution['content'] : '';
                $disputeSolution->buyer_ali_id = '';
                $disputeSolution->is_default = !empty($solution['is_default']) ? $solution['is_default'] : '';
                $disputeSolution->refund_money_post = $solution['refund_money_post'];
                $disputeSolution->refund_money_post_currency = $solution['refund_money_post_currency'];
                $disputeSolution->solution_id = $solution['id'];
                $disputeSolution->solution_type = $solution['solution_type'];
                $disputeSolution->solution_owner = $solution['solution_owner'];
                $disputeSolution->status = $solution['status'];
                $disputeSolution->reached_type = !empty($solution['reached_type']) ? $solution['reached_type'] : '';
                $disputeSolution->reached_time = !empty($solution['reached_time']) ? $solution['reached_time'] : '';
                $disputeSolution->modify_by = Yii::$app->user->identity->login_name;
                $disputeSolution->modify_time = date('Y-m-d H:i:s', time());
                $disputeSolution->buyer_accept_time = !empty($solution['buyer_accept_time']) ? $solution['buyer_accept_time'] : '';
                $disputeSolution->logistics_fee_amount = !empty($solution['logistics_fee_amount']) ? $solution['logistics_fee_amount'] : '';
                $disputeSolution->logistics_fee_amount_currency = !empty($solution['logistics_fee_amount_currency']) ? $solution['logistics_fee_amount_currency'] : '';
                $disputeSolution->logistics_fee_bear_role = !empty($solution['logistics_fee_bear_role']) ? $solution['logistics_fee_bear_role'] : '';
                $disputeSolution->seller_accept_time = !empty($solution['seller_accept_time']) ? $solution['seller_accept_time'] : '';

                if ($disputeSolution->save(false) === false) {
                    die(json_encode([
                        'code' => 0,
                        'message' => '更新纠纷协商方案失败',
                        'data' => ['issue_id' => $issueId],
                    ]));
                }
            }
        }

        //纠纷操作记录
        if (!empty($issueInfo['process_dto_list'])) {
            $processList = $issueInfo['process_dto_list']['api_issue_process_dto'];

            if (!empty($processList)) {
                //删除添加的操作记录
                $processDel = AliexpressDisputeProcess::deleteAll([
                    'account_id' => $accountId,
                    'platform_dispute_id' => $issueId,
                ]);

                //删除添加的附件
                $attachmentDel = AliexpressDisputeAttachments::deleteAll([
                    'account_id' => $accountId,
                    'platform_dispute_id' => $issueId,
                ]);

                if ($processDel === false || $attachmentDel === false) {
                    die(json_encode([
                        'code' => 0,
                        'message' => '删除操作记录或附件失败',
                    ]));
                }

                foreach ($processList as $process) {
                    //重新添加操作记录
                    $disputeProcess = new AliexpressDisputeProcess();
                    $disputeProcess->platform_dispute_id = $issueId;
                    $disputeProcess->account_id = $accountId;
                    $disputeProcess->platform_dispute_process_id = !empty($process['id']) ? $process['id'] : '';
                    $disputeProcess->action_type = $process['action_type'];
                    $disputeProcess->content = !empty($process['content']) ? $process['content'] : '';
                    $disputeProcess->gmt_create = $process['gmt_create'];
                    $disputeProcess->has_buyer_video = !empty($process['has_buyer_video']) ? 1 : 0;
                    $disputeProcess->has_seller_video = !empty($process['has_seller_video']) ? 1 : 0;
                    $disputeProcess->receive_goods = !empty($process['receive_goods']) ? $process['receive_goods'] : '';
                    $disputeProcess->submit_member_type = !empty($process['submit_member_type']) ? $process['submit_member_type'] : '';
                    $disputeProcess->create_by = Yii::$app->user->identity->login_name;
                    $disputeProcess->create_time = date('Y-m-d H:i:s', time());
                    $disputeProcess->modify_by = Yii::$app->user->identity->login_name;
                    $disputeProcess->modify_time = date('Y-m-d H:i:s', time());

                    if ($disputeProcess->save()) {
                        //重新添加附件
                        if (!empty($process['attachments']) && !empty($process['attachments']['api_attachment_dto'])) {
                            $attachments = $process['attachments']['api_attachment_dto'];

                            foreach ($attachments as $attachment) {
                                $disputeAttachment = new AliexpressDisputeAttachments();
                                $disputeAttachment->platform_dispute_id = $issueId;
                                $disputeAttachment->process_id = $disputeProcess->id;
                                $disputeAttachment->platform_dispute_process_id = !empty($attachment['issue_process_id']) ? $attachment['issue_process_id'] : '';
                                $disputeAttachment->account_id = $accountId;
                                $disputeAttachment->gmt_create = $attachment['gmt_create'];
                                $disputeAttachment->file_path = $attachment['file_path'];
                                $disputeAttachment->owner = $attachment['owner'];
                                $disputeAttachment->create_by = Yii::$app->user->identity->login_name;
                                $disputeAttachment->create_time = date('Y-m-d H:i:s', time());
                                $disputeAttachment->modify_by = Yii::$app->user->identity->login_name;
                                $disputeAttachment->modify_time = date('Y-m-d H:i:s', time());
                                $disputeAttachment->save();
                            }
                        }
                    }
                }
            }
        }

        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => ['issue_id' => $issueId],
        ]));
    }

    /**
     * 处理速卖通纠纷
     */
    public function actionHandle()
    {
        $this->isPopup = true;
        $id = Yii::$app->request->get('id', 0);

        if (empty($id)) {
            $this->_showMessage('纠纷ID不能为空', false);
        }

        $issueList = AliexpressDisputeList::findOne($id);
        if (empty($issueList)) {
            $this->_showMessage('没有找到纠纷信息', false);
        }

        //纠纷ID
        $issueId = $issueList['platform_dispute_id'];
        //平台订单ID
        $orderId = $issueList['platform_order_id'];

        //获取纠纷详情
        $issueInfo = AliexpressDisputeDetail::find()->where(['platform_dispute_id' => $issueId])->asArray()->one();

        //获取纠纷协商方案
        $issueSolution = AliexpressDisputeSolution::find()->where(['platform_dispute_id' => $issueId])->asArray()->all();
        if (!empty($issueSolution)) {
            $tmp = [];
            foreach ($issueSolution as $solution) {
                $tmp[$solution['solution_owner']][] = $solution;
            }
            $issueSolution = $tmp;
        }

        //获取订单信息
        $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_ALI, $orderId);

        if (empty($orderinfo)) {
            //如果订单信息为空，通过父级订单ID来查询订单信息
            $orderId = $issueList['platform_parent_order_id'];
            $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_ALI, $orderId);
        }

        if (!empty($orderinfo)) {
            $orderinfo = Json::decode(Json::encode($orderinfo), true);
        } else {
            $orderinfo = [];
        }

        //如果在erp没获取到交易信息  则在客服系统重新获取一遍
        if (!empty($orderinfo['trade'])) {
            foreach ($orderinfo['trade'] as $key => $value) {
                if ($value['receiver_email'] == "" || $value['payer_email'] == "") {
                    $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->asArray()->one();
                    if (!empty($transactionRecord)) {
                        $orderinfo['trade'][$key]['receiver_email'] = $transactionRecord['receiver_email'];
                        $orderinfo['trade'][$key]['payer_email'] = $transactionRecord['payer_email'];
                        $orderinfo['trade'][$key]['payment_status'] = $transactionRecord['payment_status'];
                    }
                }
            }
        }

        //Amazon订单信息加上FBA/FBM信息
        if (!empty($orderinfo['info'])) {
            if ($orderinfo['info']['amazon_fulfill_channel'] == 'AFN') {
                $orderinfo['info']['amazon_fulfill_channel'] = 'FBA';
            }
            if ($orderinfo['info']['amazon_fulfill_channel'] == 'MFN') {
                $orderinfo['info']['amazon_fulfill_channel'] = 'FBM';
            }
            $orderinfo['info']['product_weight'] = 0;

            //如果是速卖通平台的订单，获取买家剩余收货时间
            if ($orderinfo['info']['platform_code'] == Platform::PLATFORM_CODE_ALI) {
                $platformOrderId = $orderinfo['info']['platform_order_id'];
                $platformOrderIdArr = explode('-', $platformOrderId);
                if (!empty($platformOrderIdArr) && count($platformOrderIdArr) == 2) {
                    $platformOrderId = $platformOrderIdArr[0];
                }

                $newOrderInfo = AliexpressOrder::getNewOrderInfo($platformOrderId, $orderinfo['info']['account_id']);
                if (!empty($newOrderInfo) && !empty($newOrderInfo['target']['over_time_left'])) {
                    //买家确认收货结束时间
                    $orderinfo['info']['buyer_accept_goods_end_time'] = $newOrderInfo['target']['over_time_left'];
                }
            }
        }

        //组装库存和在途数
        if (!empty($orderinfo['product'])) {
            $result = isset($orderinfo['wareh_logistics']) && isset($orderinfo['wareh_logistics']['warehouse']);
            foreach ($orderinfo['product'] as $key => $value) {

                $orderinfo['info']['product_weight'] += $value['product_weight'] * $value['quantity'];

                list($stock, $on_way_count) = [null, null];
                if ($result) {
                    $data = [];
                    $stock = isset($data['available_stock']) ? $data['available_stock'] : 0;
                    $on_way_count = isset($data['on_way_stock']) ? $data['on_way_stock'] : 0;
                }
                $orderinfo['product'][$key]['stock'] = $stock;
                $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;

                //如果有产品的asinval则组装产品详情的链接地址
                if (isset($value['asinval'])) {
                    $orderinfo['product'][$key]['detail_link_href'] = '#';
                    $orderinfo['product'][$key]['detail_link_title'] = null;
                    $site_code = Account::getSiteCode($orderinfo['info']['account_id'], Platform::PLATFORM_CODE_AMAZON);

                    if (!empty($value['asinval']) && !empty($site_code)) {
                        $link_info = VHelper::getProductDetailLinkHref($site_code, $value['asinval']);
                        $orderinfo['product'][$key]['detail_link_href'] = $link_info['href'];
                        $orderinfo['product'][$key]['detail_link_title'] = $link_info['title'];
                    }
                }
            }
        }

        //获取售后信息
        $afterSalesOrders = [];
        if (!empty($orderinfo['info']['order_id'])) {
            $afterSalesOrders = AfterSalesOrder::getByOrderId(Platform::PLATFORM_CODE_ALI, $orderinfo['info']['order_id']);
        }

        $countires = Country::getCodeNamePairsList();
        //获取仓库列表
        $warehouseList = Warehouse::getWarehouseListAll();

        //获取paypal账号信息
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }

        //获取卖家对应的ERP账号ID
        $account = Account::findOne($issueList['account_id']);
        $accountId = $account->old_account_id;
        //获取当前卖家的退货地址
        $refundAddress = AliexpressOrder::getSellerRefundAddress($accountId);

        return $this->render('issueinfo/handle', [
            'id' => $id,
            'order_id' => $orderId,
            'issue_id' => $issueId,
            'issueInfo' => $issueInfo,
            'issueList' => $issueList,
            'issueSolution' => $issueSolution,
            'info' => $orderinfo,
            'paypallist' => $palPalList,
            'countries' => $countires,
            'warehouseList' => $warehouseList,
            'afterSalesOrders' => $afterSalesOrders,
            'platform' => Platform::PLATFORM_CODE_ALI,
            'isAuthority' => $isAuthority,
            'refundAddress' => $refundAddress,
        ]);
    }

    /*
     * 添加订单留言
     */
    public function actionReplymsg()
    {
        $expressionModel = new AliexpressExpression();
        $data = [
            'order_id' => $this->request->getBodyParam('order_id'),
            'content' => $expressionModel->replyContentReplace($this->request->getBodyParam('content')),
            'account_id' => $this->request->getBodyParam('account_id')
        ];
        $orderMessageModel = new OrderMessage();
        $requstul = $orderMessageModel->getOrderMessage($data);
        if ($requstul) {
            $this->_showMessage('回复成功', true, null, false, null);
        } else {
            $this->_showMessage('回复失败！', false);
        }
    }

    /**
     * 纠纷详情
     */
    public function actionDetails()
    {
        $this->isPopup = true;

        //获取纠纷ID
        $id = Yii::$app->request->get('id');

        if (empty($id)) {
            $this->_showMessage('纠纷ID不能为空', false);
        }

        //获取纠纷列表信息
        $issueList = AliexpressDisputeList::find()->where(['id' => $id])->asArray()->one();
        if (empty($issueList)) {
            $this->_showMessage('没有找到纠纷信息', false);
        }
        //纠纷ID
        $issueId = $issueList['platform_dispute_id'];
        //平台订单ID
        $orderId = $issueList['platform_order_id'];

        //获取纠纷详情
        $issueInfo = AliexpressDisputeDetail::find()->where(['platform_dispute_id' => $issueId])->asArray()->one();

        //获取纠纷协商方案
        $issueSolution = AliexpressDisputeSolution::find()->where(['platform_dispute_id' => $issueId])->asArray()->all();
        if (!empty($issueSolution)) {
            $tmp = [];
            foreach ($issueSolution as $solution) {
                $tmp[$solution['solution_owner']][] = $solution;
            }
            $issueSolution = $tmp;
        }

        //获取订单信息
        $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_ALI, $orderId);

        if (empty($orderinfo)) {
            //如果订单信息为空，通过父级订单ID来查询订单信息
            $orderId = $issueList['platform_parent_order_id'];
            $orderinfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_ALI, $orderId);
        }

        if (!empty($orderinfo)) {
            $orderinfo = Json::decode(Json::encode($orderinfo), true);
        } else {
            $orderinfo = [];
        }

        //如果在erp没获取到交易信息  则在客服系统重新获取一遍
        if (!empty($orderinfo['trade'])) {
            foreach ($orderinfo['trade'] as $key => $value) {
                if ($value['receiver_email'] == "" || $value['payer_email'] == "") {
                    $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->asArray()->one();
                    if (!empty($transactionRecord)) {
                        $orderinfo['trade'][$key]['receiver_email'] = $transactionRecord['receiver_email'];
                        $orderinfo['trade'][$key]['payer_email'] = $transactionRecord['payer_email'];
                        $orderinfo['trade'][$key]['payment_status'] = $transactionRecord['payment_status'];
                    }
                }
            }
        }

        //Amazon订单信息加上FBA/FBM信息
        if (!empty($orderinfo['info'])) {
            if ($orderinfo['info']['amazon_fulfill_channel'] == 'AFN') {
                $orderinfo['info']['amazon_fulfill_channel'] = 'FBA';
            }
            if ($orderinfo['info']['amazon_fulfill_channel'] == 'MFN') {
                $orderinfo['info']['amazon_fulfill_channel'] = 'FBM';
            }
            $orderinfo['info']['product_weight'] = 0;

            //如果是速卖通平台的订单，获取买家剩余收货时间
            if ($orderinfo['info']['platform_code'] == Platform::PLATFORM_CODE_ALI) {
                $platformOrderId = $orderinfo['info']['platform_order_id'];
                $platformOrderIdArr = explode('-', $platformOrderId);
                if (!empty($platformOrderIdArr) && count($platformOrderIdArr) == 2) {
                    $platformOrderId = $platformOrderIdArr[0];
                }

                $newOrderInfo = AliexpressOrder::getNewOrderInfo($platformOrderId, $orderinfo['info']['account_id']);
                if (!empty($newOrderInfo) && !empty($newOrderInfo['target']['over_time_left'])) {
                    //买家确认收货结束时间
                    $orderinfo['info']['buyer_accept_goods_end_time'] = $newOrderInfo['target']['over_time_left'];
                }
            }
        }

        //组装库存和在途数
        if (!empty($orderinfo['product'])) {
            $result = isset($orderinfo['wareh_logistics']) && isset($orderinfo['wareh_logistics']['warehouse']);
            foreach ($orderinfo['product'] as $key => $value) {

                $orderinfo['info']['product_weight'] += $value['product_weight'] * $value['quantity'];

                list($stock, $on_way_count) = [null, null];
                if ($result) {
                    $data = [];
                    $stock = isset($data['available_stock']) ? $data['available_stock'] : 0;
                    $on_way_count = isset($data['on_way_stock']) ? $data['on_way_stock'] : 0;
                }
                $orderinfo['product'][$key]['stock'] = $stock;
                $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;

                //如果有产品的asinval则组装产品详情的链接地址
                if (isset($value['asinval'])) {
                    $orderinfo['product'][$key]['detail_link_href'] = '#';
                    $orderinfo['product'][$key]['detail_link_title'] = null;
                    $site_code = Account::getSiteCode($orderinfo['info']['account_id'], Platform::PLATFORM_CODE_AMAZON);

                    if (!empty($value['asinval']) && !empty($site_code)) {
                        $link_info = VHelper::getProductDetailLinkHref($site_code, $value['asinval']);
                        $orderinfo['product'][$key]['detail_link_href'] = $link_info['href'];
                        $orderinfo['product'][$key]['detail_link_title'] = $link_info['title'];
                    }
                }
            }
        }

        //获取售后信息
        $afterSalesOrders = [];
        if (!empty($orderinfo['info']['order_id'])) {
            $afterSalesOrders = AfterSalesOrder::getByOrderId(Platform::PLATFORM_CODE_ALI, $orderinfo['info']['order_id']);
        }

        $countires = Country::getCodeNamePairsList();
        //获取仓库列表
        $warehouseList = Warehouse::getWarehouseListAll();

        //获取paypal账号信息
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }

        return $this->render('issueinfo/index', [
            'id' => $id,
            'order_id' => $orderId,
            'issue_id' => $issueId,
            'issueInfo' => $issueInfo,
            'issueList' => $issueList,
            'issueSolution' => $issueSolution,
            'info' => $orderinfo,
            'paypallist' => $palPalList,
            'countries' => $countires,
            'warehouseList' => $warehouseList,
            'afterSalesOrders' => $afterSalesOrders,
            'platform' => Platform::PLATFORM_CODE_ALI,
            'isAuthority' => $isAuthority,
        ]);
    }

    /**
     * 添加或保存节假日纠纷响应时间
     */
    public function actionSaveholidayresponsetime()
    {
        $id = Yii::$app->request->post('id', 0);
        $start_time = Yii::$app->request->post('start_time', '');
        $end_time = Yii::$app->request->post('end_time', '');
        $issue_reponse_day = Yii::$app->request->post('issue_reponse_day', 0);
        $refuse_issue_day = Yii::$app->request->post('refuse_issue_day', 0);

        if (empty($start_time)) {
            die(json_encode([
                'code' => 0,
                'message' => '开始时间不能为空',
            ]));
        }
        if (empty($end_time)) {
            die(json_encode([
                'code' => 0,
                'message' => '结束时间不能为空',
            ]));
        }
        if (strtotime($start_time) > strtotime($end_time)) {
            die(json_encode([
                'code' => 0,
                'message' => '开始时间不能大于结束时间',
            ]));
        }
        if (empty($issue_reponse_day)) {
            die(json_encode([
                'code' => 0,
                'message' => '纠纷响应时间不能为空',
            ]));
        }
        if (empty($refuse_issue_day)) {
            die(json_encode([
                'code' => 0,
                'message' => '拒绝纠纷上升仲裁时间不能为空',
            ]));
        }

        $time = AliexpressHolidayResponseTime::findOne($id);
        if (empty($time)) {
            $time = new AliexpressHolidayResponseTime();
            $time->create_by = Yii::$app->user->identity->login_name;
            $time->create_time = date('Y-m-d H:i:s', time());
        }
        $time->start_time = $start_time;
        $time->end_time = $end_time;
        $time->issue_reponse_day = $issue_reponse_day;
        $time->refuse_issue_day = $refuse_issue_day;
        $time->update_by = Yii::$app->user->identity->login_name;
        $time->update_time = date('Y-m-d H:i:s', time());
        $time->status = 1;

        if ($time->save(false)) {
            die(json_encode([
                'code' => 1,
                'message' => '保存节假日纠纷响应时间成功',
                'data' => ['id' => $time->id],
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '保存节假日纠纷响应时间失败',
            ]));
        }
    }

    /**
     * 删除节假日纠纷响应时间
     */
    public function actionDelholidayresponsetime()
    {
        $id = Yii::$app->request->post('id', 0);

        if (empty($id)) {
            die(json_encode([
                'code' => 0,
                'message' => 'ID不能为空',
            ]));
        }

        $time = AliexpressHolidayResponseTime::findOne($id);
        if (empty($time)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到该节假日纠纷响应时间',
            ]));
        }

        if ($time->delete()) {
            die(json_encode([
                'code' => 1,
                'message' => '删除成功',
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '删除失败',
            ]));
        }
    }

    /**
     * 同意协商方案
     */
    public function actionAgreeissuesolution()
    {
        $issueId = Yii::$app->request->post('issue_id', 0);
        $solutionId = Yii::$app->request->post('solution_id', 0);

        if (empty($issueId)) {
            die(json_encode([
                'code' => 0,
                'message' => '纠纷ID不能为空',
            ]));
        }
        if (empty($solutionId)) {
            die(json_encode([
                'code' => 0,
                'message' => '协商方案ID不能为空',
            ]));
        }

        $result = AliexpressOrder::agreeIssueSolution($issueId, $solutionId);
        if ($result === true) {
            //修改纠纷为已处理
            $issue = AliexpressDisputeList::findOne(['platform_dispute_id' => $issueId]);
            if (!empty($issue)) {
                $issue->is_handle = 1;
                $issue->save();
            }

            die(json_encode([
                'code' => 1,
                'message' => '同意方案成功',
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => $result,
            ]));
        }
    }

    /**
     * 拒绝协商方案
     */
    public function actionRefuseissuesolution()
    {
        $issueId = Yii::$app->request->post('issue_id', 0);
        $solutionId = Yii::$app->request->post('solution_id', 0);

        if (empty($issueId)) {
            die(json_encode([
                'code' => 0,
                'message' => '纠纷ID不能为空',
            ]));
        }
        if (empty($solutionId)) {
            die(json_encode([
                'code' => 0,
                'message' => '协商方案ID不能为空',
            ]));
        }

        $result = AliexpressOrder::refuseIssueSolution($issueId, $solutionId);
        if ($result === true) {
            die(json_encode([
                'code' => 1,
                'message' => '拒绝方案成功',
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => $result,
            ]));
        }
    }

    /**
     * 新增或修改协商方案
     */
    public function actionSaveissuesolution()
    {
        //纠纷ID
        $issue_id = Yii::$app->request->post('issue_id', 0);
        //买家方案ID
        $buyer_solution_id = Yii::$app->request->post('buyer_solution_id', 0);
        //修改方案ID
        $modify_seller_solution_id = Yii::$app->request->post('modify_seller_solution_id', 0);
        //方案类型
        $add_solution_type = Yii::$app->request->post('add_solution_type', '');
        //退款金额
        $refund_amount = Yii::$app->request->post('refund_amount', 0);
        //理由说明
        $solution_context = Yii::$app->request->post('solution_context', '');
        //退货地址
        $return_good_address_id = Yii::$app->request->post('return_good_address_id', 0);

        if (empty($issue_id)) {
            die(json_encode([
                'code' => 0,
                'message' => '纠纷ID不能为空',
            ]));
        }

        if (empty($add_solution_type)) {
            die(json_encode([
                'code' => 0,
                'message' => '请选择方案类型',
            ]));
        }

        if (!isset($refund_amount) || $refund_amount < 0) {
            die(json_encode([
                'code' => 0,
                'message' => '请正确填写退款金额',
            ]));
        }

        if (empty($solution_context)) {
            die(json_encode([
                'code' => 0,
                'message' => '请填写理由说明',
            ]));
        }

        if ($add_solution_type == 'return_and_refund') {
            if (empty($return_good_address_id)) {
                die(json_encode([
                    'code' => 0,
                    'message' => '退货退款必须选择退货地址',
                ]));
            }
        }

        $result = AliexpressOrder::saveIssueSolution($issue_id, $modify_seller_solution_id, $add_solution_type, $refund_amount, $solution_context, $buyer_solution_id, $return_good_address_id);
        if ($result === true) {
            //修改纠纷为已处理
            $issue = AliexpressDisputeList::findOne(['platform_dispute_id' => $issue_id]);
            if (!empty($issue)) {
                $issue->is_handle = 1;
                $issue->save();
            }

            if (empty($modify_seller_solution_id)) {
                die(json_encode([
                    'code' => 1,
                    'message' => '添加方案成功',
                ]));
            } else {
                die(json_encode([
                    'code' => 1,
                    'message' => '修改方案成功',
                ]));
            }
        } else {
            if (empty($modify_seller_solution_id)) {
                die(json_encode([
                    'code' => 0,
                    'message' => '添加方案失败,' . $result,
                ]));
            } else {
                die(json_encode([
                    'code' => 0,
                    'message' => '修改方案失败,' . $result,
                ]));
            }
        }
    }

    /**
     * 批量拒绝买家方案
     */
    public function actionBatchrefusebuyersolution()
    {
        //纠纷ID
        $issue_id = Yii::$app->request->post('issue_id', 0);
        //方案类型
        $add_solution_type = Yii::$app->request->post('add_solution_type', '');
        //退款金额
        $refund_amount = Yii::$app->request->post('refund_amount', 0);
        //理由说明
        $solution_context = Yii::$app->request->post('solution_context', '');

        if (empty($issue_id)) {
            die(json_encode([
                'code' => 0,
                'message' => '纠纷ID不能为空',
            ]));
        }

        if (empty($add_solution_type)) {
            die(json_encode([
                'code' => 0,
                'message' => '请选择方案类型',
            ]));
        }

        if (!isset($refund_amount) || $refund_amount < 0) {
            die(json_encode([
                'code' => 0,
                'message' => '请正确填写退款金额',
            ]));
        }

        if (empty($solution_context)) {
            die(json_encode([
                'code' => 0,
                'message' => '请填写理由说明',
            ]));
        }

        $solution = AliexpressDisputeSolution::find()
            ->select('solution_id')
            ->andWhere(['platform_dispute_id' => $issue_id])
            ->andWhere(['solution_owner' => 'buyer'])
            ->andWhere([
                'or',
                ['status' => 'wait_seller_accept'],
                ['status' => 'wait_buyer_and_seller_accept'],
            ])
            ->asArray()
            ->one();

        if (empty($solution)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到买家方案',
            ]));
        }

        $result = AliexpressOrder::saveIssueSolution($issue_id, '', $add_solution_type, $refund_amount, $solution_context, $solution['solution_id']);
        if ($result === true) {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '失败,' . $result,
            ]));
        }
    }

    /**
     * 卖家上传纠纷证据图片
     */
    public function actionAddissueimage()
    {
        $id = Yii::$app->request->post('id', 0);
        $issue_id = Yii::$app->request->post('issue_id', 0);

        if (empty($id)) {
            $this->_showMessage('ID不能为空', false);
        }
        if (empty($issue_id)) {
            $this->_showMessage('纠纷ID不能为空', false);
        }

        $image = UploadedFile::getInstanceByName('image');
        if (empty($image)) {
            $this->_showMessage('图片上传失败', false);
        }
        //图片格式
        if (!in_array($image->extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $this->_showMessage('图片格式错误', false);
        }
        //图片大小，不超过2M
        if ($image->size > self::MAX_ISSUE_IMAGE_SIZE) {
            $this->_showMessage('图片大小不能超过2M', false);
        }

        $filePath = self::ISSUE_UPLOAD_PATH . date('Ym') . '/';
        if (!file_exists($filePath)) {
            @mkdir($filePath, 0777, true);
            @chmod($filePath, 0777);
        }
        $fileName = md5($image->baseName) . '.' . $image->extension;
        $file = $filePath . $fileName;
        if (!$image->saveAs($file)) {
            $this->_showMessage('保存图片失败', false);
        }

        $fp = fopen($file, 'rb');
        if (!$fp) {
            $this->_showMessage('打开图片失败', false);
        }
        $imageData = fread($fp, self::MAX_ISSUE_IMAGE_SIZE);
        if (!$imageData) {
            $this->_showMessage('读取图片失败', false);
        }

        $result = AliexpressOrder::addIssueImage($issue_id, $image->extension, $imageData, $image->baseName);
        @fclose($fp);
        //不管有没有上传成功，都把文件删除
        @unlink(realpath($file));
        if ($result === true) {
            VHelper::throwTheader('/mails/aliexpressdispute/updateissueinfo', ['issue_id' => $issue_id]);

            $this->_showMessage('上传凭证成功', true, Url::toRoute(['/mails/aliexpressdispute/handle', 'id' => $id]));
        } else {
            $this->_showMessage('上传凭证失败,' . $result, false, Url::toRoute(['/mails/aliexpressdispute/handle', 'id' => $id]));
        }
    }

    /**
     * 获取售后单规则
     */
    public function actionGetaftersaleorderrule()
    {
        $issueId = Yii::$app->request->post('issue_id');

        if (empty($issueId)) {
            die(json_encode([
                'code' => 0,
                'message' => '纠纷ID为空',
            ]));
        }

        $issueInfo = AliexpressDisputeList::findOne(['platform_dispute_id' => $issueId]);
        if (empty($issueInfo)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到纠纷信息',
            ]));
        }

        //获取匹配的速卖通售后单规则
        $rules = AftersaleManage::getMatchAfterSaleOrderRule(Platform::PLATFORM_CODE_ALI, $issueInfo->platform_order_id, $issueInfo->reason_chinese);

        $have = 0;
        $data = [];
        if (!empty($rules)) {
            $have = 1;
            $data = array_shift($rules);

            $allBasicConfig = BasicConfig::getAllConfigData();
            $data['department_name'] = array_key_exists($data['department_id'], $allBasicConfig) ? $allBasicConfig[$data['department_id']] : '';
            $data['reason_name'] = array_key_exists($data['reason_id'], $allBasicConfig) ? $allBasicConfig[$data['reason_id']] : '';
        }

        $data['order_id'] = '';
        $orderInfo = OrderAliexpressKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_ALI, 'platform_order_id' => $issueInfo->platform_order_id]);
        if (!empty($orderInfo)) {
            $data['order_id'] = $orderInfo->order_id;
            $data['order_amount'] = $orderInfo->total_price;
            $data['order_currency'] = $orderInfo->currency;
        } else {
            $data['order_id'] = '';
            $data['order_amount'] = 0;
            $data['order_currency'] = '';
        }

        die(json_encode([
            'code' => 1,
            'message' => '找到匹配的规则',
            'data' => $data,
            'have' => $have,
        ]));
    }

    /**
     * 创建售后单
     */
    public function actionCreateaftersaleorder()
    {
        $issueId = Yii::$app->request->post('issue_id');
        $amount = Yii::$app->request->post('amount');

        if (empty($issueId)) {
            die(json_encode([
                'code' => 0,
                'message' => '纠纷ID为空',
            ]));
        }

        if (!isset($amount) || $amount < 0) {
            die(json_encode([
                'code' => 0,
                'message' => '金额不能小于0',
            ]));
        }

        $issueInfo = AliexpressDisputeList::findOne(['platform_dispute_id' => $issueId]);
        if (empty($issueInfo)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到纠纷信息',
            ]));
        }

        $result = AftersaleManage::autoCreateAfterSaleOrder(Platform::PLATFORM_CODE_ALI, $issueInfo->platform_order_id, $issueInfo->reason_chinese, '', $amount);

        if ($result) {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '失败',
            ]));
        }
    }

    /**
     * 标记已处理
     */
    public function actionBatchhandle()
    {
        $ids = Yii::$app->request->post('ids', []);

        if (empty($ids)) {
            $this->_showMessage('请选中标记项', false);
        }

        $result = AliexpressDisputeList::updateAll(['is_handle' => 2], ['in', 'id', $ids]);
        if ($result !== false) {
            $extraJs = "$(\"input[name='id']:checked\").each(function() {
                            var tr = $(this).parent('td').parent('tr');
                            tr.find(\"span:contains('未处理'),span:contains('已处理')\").text('标记处理').removeAttr('style');
                        });";
            $this->_showMessage('标记为已处理成功', true, null, false, null, $extraJs);
        } else {
            $this->_showMessage('标记为已处理失败', false);
        }
    }

    /*
     * 卖家提交纠纷仲裁
     */
    public function actionArbitration()
    {
        $data = [
            'issueId' => $this->request->getBodyParam('dispute_id'),
            'description' => $this->request->getBodyParam('description'),
            'account_id' => $this->request->getBodyParam('account_id'),
            'reason' => $this->request->getBodyParam('reason'),
        ];
        $ordeArbitrationModel = new OrdeArbitration();
        $requstul = $ordeArbitrationModel->getOrdeArbitration($data);
        if ($requstul) {
            $this->_showMessage('回复成功', true, null, false, null);
        } else {
            $this->_showMessage('回复失败！', false);
        }
    }

    /*
     * 卖家确认收货
     */
    public function actionGoodsreceipt()
    {
        $data = [
            'issueId' => $this->request->getBodyParam('issueId'),
            'account_id' => $this->request->getBodyParam('account_id'),
        ];
        $goodsReceipt = new GoodsReceipt();
        $requstul = $goodsReceipt->getGoodsReceipt($data);
        if ($requstul) {
            $this->_showMessage('卖家确认收货成功', true, null, false, null);
        } else {
            $this->_showMessage('卖家确认收货失败！', false);
        }
    }

    /*
     * 卖家放弃退货申请
     */
    public function actionWaiverreturns()
    {
        $data = [
            'issueId' => $this->request->getBodyParam('issueId'),
            'account_id' => $this->request->getBodyParam('account_id'),
        ];
        $waiverReturns = new WaiverReturns();
        $requstul = $waiverReturns->getWaiverReturns($data);
        if ($requstul) {
            $this->_showMessage('卖家放弃退货申请成功', true, null, false, null);
        } else {
            $this->_showMessage('卖家放弃退货申请失败！', false);
        }
    }
}
