<?php

namespace app\modules\aftersales\controllers;

use app\components\Controller;
use app\modules\accounts\models\Account;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\OrderWalmartItem;
use yii\helpers\Json;
use app\modules\systems\models\Country;
use app\modules\orders\models\Warehouse;
use app\modules\orders\models\Logistic;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\aftersales\models\AfterSalesReturn;
use app\modules\aftersales\models\OrderReturnDetail;
use app\modules\aftersales\models\AfterSalesRedirect;
use app\modules\aftersales\models\OrderRedirectDetail;
use app\modules\aftersales\models\AfterSalesProduct;
use app\modules\aftersales\models\Domesticreturngoods;
use app\modules\aftersales\models\SkuQualityAnalysis;
use app\modules\systems\models\AutoCode;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\OrderAmazonItem;
use app\modules\systems\models\RefundAccount;
use app\modules\systems\models\BasicConfig;
use app\modules\users\models\UserRole;
use app\modules\services\modules\walmart\models\Refund;
use Yii;
use yii\helpers\Url;
use app\modules\orders\models\OrderEbayKefu;
use app\modules\orders\models\OrderAmazonKefu;
use app\modules\orders\models\OrderWishKefu;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\orders\models\OrderAliexpressKefu;
use app\modules\aftersales\models\AfterRefundCode;
class OrderController extends Controller {

    
    /**
     * @desc 列表
     * @return \yii\base\string
     */
    public function actionList() {
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('list', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc 新建售后单
     * @return \yii\base\string
     */
    public function actionAdd() {
        //添加表单重复提交验证 update by allen <2018-03-28> str
        $session = \Yii::$app->session;
        $random = mt_rand();
        $sessionRandom = $session->get('random');
        $hidenRandom = $this->request->getBodyParam('random');
        if ($hidenRandom && $sessionRandom) {
            if ($sessionRandom != $hidenRandom) {
                $this->_showMessage('请勿重复提交数据', false);
            }
        } else {
            $session->set('random', $random);
        }
        //添加表单重复提交验证 update by allen <2018-03-28> end
        //paypal账号信息
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }

        $this->isPopup = true;
        $orderId = $this->request->getQueryParam('order_id');
        $platform = $this->request->getQueryParam('platform');
        $from = $this->request->getQueryParam('from');
        $orderinfo = [];
        if (empty($platform))
            $this->_showMessage('平台CODE无效', false, null, false, null, 'layer.closeAll()');
        if (empty($orderId))
            $this->_showMessage('订单号无效', false, null, false, null, 'layer.closeAll()');
        //$orderinfo = Order::getOrderStackByOrderId($platform, '', $orderId);


        $orderinfo = OrderKefu::getOrderStackByOrderId($platform, '', $orderId);
     
        if (empty($orderinfo))
            $this->_showMessage('找不到对应订单', false, null, false, null, 'layer.closeAll()');
        if($orderinfo->info->platform_code == 'AMAZON'){
            $ship_amount = isset($orderinfo->info) && isset($orderinfo->info->ship_cost) ? $orderinfo->info->ship_cost : 0.00;
            $order_amount = isset($orderinfo->info) && isset($orderinfo->info->total_price) ? $orderinfo->info->total_price + $ship_amount : 0.00;
        }else{
            $order_amount = isset($orderinfo->info) && isset($orderinfo->info->total_price) ? $orderinfo->info->total_price : 0.00;
        }
        
        $allow_refund_amount = AfterSalesRefund::getAllowRefundAmount($orderId, $order_amount, $platform);

        $account_info = Account::find()->where(['platform_code' => $orderinfo->info->platform_code, 'old_account_id' => $orderinfo->info->account_id, 'status' => Account::STATUS_VALID])->one();
        if (empty($account_info)) {
            //如果是速卖通线下发货平台 账号信息跟速卖通是一样的
            if ($orderinfo->info->platform_code == 'ALIXX') {
                $account_info = Account::find()->where(['platform_code' => 'ALI', 'old_account_id' => $orderinfo->info->account_id, 'status' => Account::STATUS_VALID])->one();
                if (empty($account_info)) {
                    $this->_showMessage('未找到帐号信息', false, null, false, null, 'layer.closeAll()');
                }
                $account_info->platform_code = 'ALIXX';
            } else {
                $this->_showMessage('未找到帐号信息', false, null, false, null, 'layer.closeAll()');
            }
        }

        if ($this->request->getIsPost()) {
            $orderProducts = [];
            if (isset($orderinfo->product) && !empty($orderinfo->product)) {
                foreach ($orderinfo->product as $row)
                    $orderProducts[trim($row->sku)] = $row;
            }
            $dbTransaction = AfterSalesOrder::getDb()->beginTransaction();

            $account_name = $this->request->getBodyParam('account_name');
            $buyer_id = $this->request->getBodyParam('buyer_id');

            try {
                $issueProductArr = $this->request->getBodyParam('issue_product');
                $departmentId = $this->request->getBodyParam('department_id');
                $reasonId = $this->request->getBodyParam('reason_id');
                $remark = $this->request->getBodyParam('remark');
               
                $afterSalesTypeArr = $this->request->getBodyParam('after_sales_type');
                //如果部门是供应商(55)并且原因是12 产品质量问题(74) 则备注必填
                if ($departmentId == 55 && $reasonId == 74 && empty($remark)) {
                    $this->_showMessage('当前原因条件下备注必填', false);
                }
                if (empty($afterSalesTypeArr))
                    $this->_showMessage('必须选择一个售后类型', false);
                if (empty($departmentId)) {
                    $this->_showMessage('请选择责任归属部门', false);
                }
                if (empty($reasonId)) {
                    $this->_showMessage('请选择原因类型', false);
                }
                $issueProducts = [];
                if (!empty($issueProductArr)) {
                    $isSetNum = FALSE;
                    foreach ($issueProductArr as $sku => $quantity) {
                        $sku = trim($sku);
                        $quantity = (int) $quantity;
                        if ($quantity) {
                            $isSetNum = TRUE;
                        }
                        if (empty($sku))
                            throw new \Exception('无效的SKU', false);
                        if (!isset($orderProducts[$sku]))
                            throw new \Exception('SKU不存在于订单中', false);
                        if ($quantity <= 0)
                            continue;
                        $issueProducts[] = [
                            'platform_code' => $platform,
                            'order_id' => $orderId,
                            'sku' => $sku,
                            'product_title' => isset($orderProducts[$sku]->picking_name) ? $orderProducts[$sku]->picking_name : $orderProducts[$sku]->title,
                            'quantity' => $orderProducts[$sku]->quantity,
                            'linelist_cn_name' => $orderProducts[$sku]->linelist_cn_name,
                            'issue_quantity' => $quantity,
                            'reason_id' => $reasonId,
                        ];
                    }
                }
                $handle_type = 0; //处理类型
                foreach ($afterSalesTypeArr as $type) {
                    //判断如果是退款操作  订单数量必填                    
                    if (!$isSetNum) {
                        $this->_showMessage('问题产品数量必填', false);
                    }

                    $returngoodsid = $this->request->getBodyParam('id');
                    if (!in_array($type, [
                                AfterSalesOrder::ORDER_TYPE_REFUND,
                                AfterSalesOrder::ORDER_TYPE_RETURN,
                                AfterSalesOrder::ORDER_TYPE_REDIRECT,
                            ]))
                        throw new \Exception('无效的售后类型');
                    //处理退款

                    if ($type == AfterSalesOrder::ORDER_TYPE_REFUND) {
                        $handle_type = 3;
                        $refundDetail = [];
                        $refundAmount = 0.00;
                        $currencyCode = $orderinfo->info->currency;
                        $reasonCode = '';
                        $platform_order_id = '';
                        if ($platform == Platform::PLATFORM_CODE_AMAZON) {
                            $itemPriceAmounts = $this->request->getBodyParam('item_price_amount');
                            $itemPromotionPriceAmounts = $this->request->getBodyParam('promotion_discount_amount');
                            $itemShippingAmounts = $this->request->getBodyParam('item_shipping_amount');
                            $itemTaxAmounts = $this->request->getBodyParam('item_tax_amount');
                            $shippingTaxAmounts = $this->request->getBodyParam('shipping_tax_amount');
                            $reasonCodes = $this->request->getBodyParam('reason_code');
                            $platformOrderId = isset($orderinfo->info) && isset($orderinfo->info->platform_order_id) ?
                                    $orderinfo->info->platform_order_id : '';
                            if (empty($itemPriceAmounts))
                                $this->_showMessage('退款明细不能空', false);
                            foreach ($itemPriceAmounts as $itemID => $itemPriceAmount) {
                                if (empty($itemID))
                                    $this->_showMessage('退款Item ID为空', false);
                                if ($itemPriceAmount <= 0)
                                    continue;
                                $reasonCode = isset($reasonCodes[$itemID]) ? trim($reasonCodes[$itemID]) : '';
                                if (empty($reasonCode))
                                    $this->_showMessage('退款原因不能为空', false);
                                $itemPromotionPrice = $itemPromotionPriceAmounts[$itemID];//promotion金额
                                
                                $refundDetail[$itemID]['reason_code'] = $reasonCode;
                                $refundDetail[$itemID]['item_price_amount'] = (float)$itemPriceAmount + (float)$itemPromotionPrice;
                                $refundAmount += $itemPriceAmount;
                                if (isset($itemShippingAmounts[$itemID]) && $itemShippingAmounts[$itemID] > 0) {
                                    $refundDetail[$itemID]['item_shipping_amount'] = (float)$itemShippingAmounts[$itemID];
                                    $refundAmount += $refundDetail[$itemID]['item_shipping_amount'];
                                }
                                if (isset($itemTaxAmounts[$itemID]) && $itemTaxAmounts[$itemID] > 0) {
                                    $refundDetail[$itemID]['item_tax_amount'] = (float)$itemTaxAmounts[$itemID];
                                    $refundAmount += $refundDetail[$itemID]['item_tax_amount'];
                                }
                                if (isset($shippingTaxAmounts[$itemID]) && $shippingTaxAmounts[$itemID] > 0) {
                                    $refundDetail[$itemID]['shipping_tax_amount'] = (float)$shippingTaxAmounts[$itemID];
                                    $refundAmount += $refundDetail[$itemID]['shipping_tax_amount'];
                                }
                            }
                        } elseif ($platform == Platform::PLATFORM_CODE_WALMART) {
                            $refundSku = $this->request->getBodyParam('refundsku');
                            $refundComments = $this->request->getBodyParam('refundComments');
                            if (empty($refundSku)) {
                                $this->_showMessage('退款明细不能空', false);
                            }
                            $refundDetail = $redetail = [];
                            foreach ($refundSku as $key => $value) {
                                foreach ($value as $val) {
                                    if (empty($val['amount']) || $val['amount'] == '0.00') {
                                        $this->_showMessage('请输入退款相关金额', false);
                                    }
                                    $val['item_id'] = $key;
                                    $redetail[] = $val;
                                    $refundAmount += $val['amount'];
                                    if ($val['chargeType'] == 'PRODUCT') {
                                        $reasonCode = $val['refundReason'];
                                    }
                                }
                            }
                            
                            $platformOrderId = isset($orderinfo->info) && isset($orderinfo->info->platform_order_id) ? $orderinfo->info->platform_order_id : '';
                            $refundDetail = [
                                'platform_order_id' => $platformOrderId,
                                'refundComments' => $refundComments,
                                'returnDetail' => $redetail
                            ];
                        } else {
                            $reasonCode = trim($this->request->getBodyParam('reason_code'));
                            $refundAmount = floatval($this->request->getBodyParam('refund_amount'));
                        }
                        $message = trim($this->request->getBodyParam('message'));
                        $refundAmount = (float) $refundAmount;
                        $allow_refund_amount = (float) $allow_refund_amount;
                        if ($refundAmount <= 0.00)
                            $this->_showMessage('退款金额不能小于0', false);
                        if (bccomp($refundAmount, $allow_refund_amount) > 0)
                            $this->_showMessage('退款金额不能大于可退款金额', false);

                        $afterSalesOrderModel = new AfterSalesOrder();
                        $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                        $afterSalesOrderModel->order_id = $orderId;
                        $afterSalesOrderModel->transaction_id = isset($platformOrderId) ? $platformOrderId : '';
                        $afterSalesOrderModel->type = $type;
                        $afterSalesOrderModel->platform_code = $platform;
                        $afterSalesOrderModel->department_id = $departmentId;
                        $afterSalesOrderModel->reason_id = $reasonId;
                        $afterSalesOrderModel->remark = $remark;
                        $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_WATTING_AUDIT;
                        $afterSalesOrderModel->account_name = $account_name;
                        $afterSalesOrderModel->account_id = !empty($account_info) ? $account_info->id : '';
                        $afterSalesOrderModel->buyer_id = $buyer_id;
                        $flag = $afterSalesOrderModel->save(false);
                        if (!$flag)
                            throw new \Exception('保存售后单失败', false);
                        $afterSaleOrderRefund = new AfterSalesRefund();
                        $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
                        if ($refundAmount < $order_amount) {
                            $afterSaleOrderRefund->refund_type = AfterSalesRefund::REFUND_TYPE_PARTIAL;
                        }
                        $afterSaleOrderRefund->after_sale_id = $afterSalesOrderModel->after_sale_id;
                        $afterSaleOrderRefund->refund_amount = $refundAmount;
                        $afterSaleOrderRefund->refund_detail = json_encode($refundDetail);
                        $afterSaleOrderRefund->currency = $currencyCode;
                        $afterSaleOrderRefund->reason_code = $reasonCode;
                        $afterSaleOrderRefund->message = $message;
                        $afterSaleOrderRefund->platform_code = $platform;
                        $afterSaleOrderRefund->order_id = $orderId;
                        $afterSaleOrderRefund->order_amount = $orderinfo->info->total_price;
                        $afterSaleOrderRefund->platform_order_id = $orderinfo->info->platform_order_id;
                        $flag = $afterSaleOrderRefund->save();
                        if (!$flag)
                            throw new \Exception('保存退款数据失败');

                        $f_after_id = $afterSalesOrderModel->after_sale_id;
                    }
                    /*                     * *********** 退货************ */
                    //处理退货
                    if ($type == AfterSalesOrder::ORDER_TYPE_RETURN) {
                        $returnSkuArr = $this->request->getBodyParam('return_sku');
                        $returnTitleArr = $this->request->getBodyParam('return_title');
                        $returnQuantityArr = $this->request->getBodyParam('return_quantity');
                        $returnLinelistCnNameArr = $this->request->getBodyParam('return_linelist_cn_name');
                        $returnWarehouseId = $this->request->getBodyParam('return_warehouse_id');
                        $returnCarrier = trim($this->request->getBodyParam('return_carrier'));
                        $returnTrackingNo = trim($this->request->getBodyParam('return_tracking_no'));
                        $return_rma = trim($this->request->getBodyParam('return_rma'));
                        $return_remark = trim($this->request->getBodyParam('return_remark'));
                        $refund_code = $this->request->getBodyParam('refund_code');
                        $returnItems = [];
                        $returnSkuList = [];
                        if (empty($returnWarehouseId))
                            $this->_showMessage('退回仓库不能为空', false);
                        if (empty($returnSkuArr))
                            $this->_showMessage('未选择产品', false);
                        if(empty($refund_code)){
                             $this->_showMessage('请获取退货编码', false);
                        }
                        foreach ($returnSkuArr as $key => $returnSku) {
                            $returnSku = trim($returnSku);
                            if (empty($returnSku))
                                $this->_showMessage('SKU为空', false);
                            if (in_array($returnSku, $returnSkuList))
                                $this->_showMessage('SKU{' . $returnSku . '}重复', false);
                            $returnTitle = isset($returnTitleArr[$key]) ? trim($returnTitleArr[$key]) : '';
                            if (empty($returnTitle))
                                $this->_showMessage('产品标题为空', false);
                            $returnQuantity = isset($returnQuantityArr[$key]) ? (int) $returnQuantityArr[$key] : 0;
                            if ($returnQuantity <= 0)
                                $this->_showMessage('产品数量必须大于0', false);
                            $returnLinelistCnName = isset($returnLinelistCnNameArr[$key]) ? $returnLinelistCnNameArr[$key] : '';
                            $returnItems[] = [
                                'sku' => $returnSku,
                                'productTitle' => $returnTitle,
                                'quantity' => $returnQuantity,
                                'linelist_cn_name' => $returnLinelistCnName,
                            ];
                            array_push($returnSkuList, $returnSku);
                        }
                        $afterSalesOrderModel = new AfterSalesOrder();
                        $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                        $afterSalesOrderModel->refund_code = $refund_code;
                        $afterSalesOrderModel->order_id = $orderId;
                        $afterSalesOrderModel->type = $type;
                        $afterSalesOrderModel->platform_code = $platform;
                        $afterSalesOrderModel->department_id = $departmentId;
                        $afterSalesOrderModel->reason_id = $reasonId;
                        $afterSalesOrderModel->remark = $remark;
                        $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_WATTING_AUDIT;
                        $afterSalesOrderModel->account_name = $account_name;
                        $afterSalesOrderModel->account_id = !empty($account_info) ? $account_info->id : '';
                        $afterSalesOrderModel->buyer_id = $buyer_id;
                        $flag = $afterSalesOrderModel->save(false);
                        if (!$flag)
                            throw new \Exception('保存售后单失败', false);
                        $afterSaleOrderReturn = new AfterSalesReturn();
                        $afterSaleOrderReturn->after_sale_id = $afterSalesOrderModel->after_sale_id;
                        $afterSaleOrderReturn->warehouse_id = $returnWarehouseId;
                        $afterSaleOrderReturn->carrier = $returnCarrier;
                        $afterSaleOrderReturn->tracking_no = $returnTrackingNo;
                        $afterSaleOrderReturn->rma = $return_rma; //rma
                        $afterSaleOrderReturn->remark = $return_remark; //退货备注
                        $afterSaleOrderReturn->return_time = date('Y-m-d H:i:s');
                        $afterSaleOrderReturn->return_by = \Yii::$app->user->identity->login_name;
                        $afterSaleOrderReturn->create_time = date('Y-m-d H:i:s');
                        $afterSaleOrderReturn->create_by = \Yii::$app->user->identity->login_name;
                        $afterSaleOrderReturn->platform_code = $platform;
                        $afterSaleOrderReturn->order_id = $orderId;
                        $flag = $afterSaleOrderReturn->insert_overwrite();
                        if (!$flag)
                            throw new \Exception('保存退货数据失败');
                        foreach ($returnItems as $row) {
                            $orderReturnDetail = new OrderReturnDetail();
                            $orderReturnDetail->after_sale_id = $afterSalesOrderModel->after_sale_id;
                            $orderReturnDetail->sku = $row['sku'];
                            $orderReturnDetail->product_title = $row['productTitle'];
                            $orderReturnDetail->quantity = $row['quantity'];
                            $orderReturnDetail->linelist_cn_name = $row['linelist_cn_name'];
                            $flag = $orderReturnDetail->save();
                            if (!$flag)
                                throw new \Exception('保存退货详情是吧');
                        }
                        $s_after_id = $afterSalesOrderModel->after_sale_id;
                    }
                    /*                     * ************ *************** */
                    //处理重寄
                    if ($type == AfterSalesOrder::ORDER_TYPE_REDIRECT) {
                        $handle_type = 4;
                        $skuArr = $this->request->getBodyParam('sku');
                        $titleArr = $this->request->getBodyParam('product_title');
                        $quantityArr = $this->request->getBodyParam('quantity');
                        $redirectLinelistCnNameArr = $this->request->getBodyParam('redirect_linelist_cn_name');
                        $returnItemIdArr = $this->request->getBodyParam('item_id');
                        $returnTransactionIdArr = $this->request->getBodyParam('transaction_id');

                        $warehouse_name = $this->request->getBodyParam('warehouse_name');
                        $ship_code_name = $this->request->getBodyParam('ship_code_name');
                        $redirect_order_amount = $this->request->getBodyParam('order_amount');
                        $currency = $this->request->getBodyParam('add_currency');
                        $paypal_id = !empty($this->request->getBodyParam('paypal_id')) ? trim($this->request->getBodyParam('paypal_id')) : '';
                        $paypal_email = !empty($this->request->getBodyParam('paypal_email')) ? $this->request->getBodyParam('paypal_email') : '';
                        if (!empty($paypal_id) || !empty($paypal_email)) {
                            if (empty($paypal_id)) {
                                $this->_showMessage('请输入正确的交易号', false);
                            }
                            if (empty($paypal_email)) {
                                $this->_showMessage('请选择客户付款账号', false);
                            }
                            if (empty($redirect_order_amount) || $redirect_order_amount == '0' || $redirect_order_amount == '0.00') {
                                $this->_showMessage('请输入正确的加钱金额', false);
                            }
                        }
                        $paypal_email = $palPalList[$paypal_email];

                        $items = [];
                        $skuList = [];
                        if (empty($skuArr))
                            $this->_showMessage('未选择产品', false);
                        foreach ($skuArr as $key => $sku) {
                            $sku = trim($sku);
                            if (empty($sku))
                                $this->_showMessage('SKU为空', false);
                            if (in_array($sku, $skuList))
                                $this->_showMessage('SKU{' . $sku . '}重复', false);
                            $title = isset($titleArr[$key]) ? trim($titleArr[$key]) : '';
                            if (empty($title))
                                $this->_showMessage('产品标题为空', false);
                            $quantity = isset($quantityArr[$key]) ? (int) $quantityArr[$key] : 0;
                            if ($quantity <= 0)
                                $this->_showMessage('产品数量必须大于0', false);
                            $redirectLinelistCnName = isset($redirectLinelistCnNameArr[$key]) ? $redirectLinelistCnNameArr[$key] : '';
                            $returnItemId = isset($returnItemIdArr[$key]) ? $returnItemIdArr[$key] : '';
                            $returnTransactionId = isset($returnTransactionIdArr[$key]) ? $returnTransactionIdArr[$key] : '';
                            $items[] = [
                                'item_id' => $returnItemId,
                                'transaction_id' => $returnTransactionId,
                                'sku' => $sku,
                                'productTitle' => $title,
                                'quantity' => $quantity,
                                'linelist_cn_name' => $redirectLinelistCnName,
                            ];
                            array_push($skuList, $sku);
                        }

                        //发货地址信息
                        $datas['city'] = $shipCityName = trim($this->request->getBodyParam('ship_city_name'));
                        $datas['countryCode'] = $shipCountry = trim($this->request->getBodyParam('ship_country'));
                        $datas['shipName'] = $shipName = trim($this->request->getBodyParam('ship_name'));
                        $datas['phone'] = $shipPhone = trim($this->request->getBodyParam('ship_phone'));
                        $datas['province'] = $state = trim($this->request->getBodyParam('ship_stateorprovince'));
                        $datas['address1'] = $address1 = trim($this->request->getBodyParam('ship_street1'));
                        $datas['address2'] = $address2 = trim($this->request->getBodyParam('ship_street2'));
                        $datas['postCode'] = $postCode = trim($this->request->getBodyParam('ship_zip'));
                        $datas['items'] = $items;
                        if (empty($shipName))
                            $this->_showMessage('收件人不能为空', false);
                        if (empty($shipCountry))
                            $this->_showMessage('国家不能为空', false);
                        if (empty($address1))
                            $this->_showMessage('地址不能为空', false);
                        if (empty($shipCityName))
                            $this->_showMessage('城市不能为空', false);
                        if (empty($postCode))
                            $this->_showMessage('邮编不能为空', false);

                        //发货仓库和运输方式
                        $warehouseId = (int) $this->request->getBodyParam('warehouse_id');
                        $shipCode = trim($this->request->getBodyParam('ship_code'));
                        if (empty($warehouseId))
                            $this->_showMessage('发货仓库不能为空', false);
                        if ($shipCode === '' || $shipCode === null)
                            $this->_showMessage('邮寄方式不能为空', false);
                        $datas['warehouseId'] = $warehouseId;
                        $datas['shipCode'] = $shipCode;

                        $order_remark = trim($this->request->getBodyParam('order_remark'));
                        $print_remark = trim($this->request->getBodyParam('print_remark'));
                        if (strlen($print_remark) > 200)
                            $this->_showMessage('订单备货字节长度不能超过200', false);

                        $afterSalesOrderModel = new AfterSalesOrder();
                        $afterSalesOrderModel->after_sale_id = AutoCode::getCode('after_sales_order');
                        $afterSalesOrderModel->order_id = $orderId;
                        $afterSalesOrderModel->type = $type;
                        $afterSalesOrderModel->platform_code = $platform;
                        $afterSalesOrderModel->department_id = $departmentId;
                        $afterSalesOrderModel->reason_id = $reasonId;
                        $afterSalesOrderModel->remark = $remark;
                        $afterSalesOrderModel->status = AfterSalesOrder::ORDER_STATUS_WATTING_AUDIT;
                        $afterSalesOrderModel->account_name = $account_name;
                        $afterSalesOrderModel->account_id = !empty($account_info) ? $account_info->id : '';
                        $afterSalesOrderModel->buyer_id = $buyer_id;
                        $flag = $afterSalesOrderModel->save(false);

                        //平台订单模型
                        $orderQuery = null;

                        switch ($platform) {
                            case Platform::PLATFORM_CODE_EB:
                                $orderQuery = OrderEbayKefu::find();
                                break;
                            case Platform::PLATFORM_CODE_ALI:
                                $orderQuery = OrderAliexpressKefu::find();
                                break;
                            case Platform::PLATFORM_CODE_AMAZON:
                                $orderQuery = OrderAmazonKefu::find();
                                break;
                            case Platform::PLATFORM_CODE_WISH:
                                $orderQuery = OrderWishKefu::find();
                                break;
                            case Platform::PLATFORM_CODE_CDISCOUNT:
                            case Platform::PLATFORM_CODE_SHOPEE:
                            case Platform::PLATFORM_CODE_LAZADA:
                            case Platform::PLATFORM_CODE_WALMART:
                            case Platform::PLATFORM_CODE_OFFLINE:
                            case Platform::PLATFORM_CODE_MALL:
                            case Platform::PLATFORM_CODE_JOOM:
                            case Platform::PLATFORM_CODE_PF:
                            case Platform::PLATFORM_CODE_BB:
                            case Platform::PLATFORM_CODE_DDP:
                            case Platform::PLATFORM_CODE_STR:
                            case Platform::PLATFORM_CODE_JUM:
                            case Platform::PLATFORM_CODE_JET:
                            case Platform::PLATFORM_CODE_GRO:
                            case Platform::PLATFORM_CODE_DIS:
                            case Platform::PLATFORM_CODE_SPH:
                            case Platform::PLATFORM_CODE_INW:
                            case Platform::PLATFORM_CODE_JOL:
                            case Platform::PLATFORM_CODE_SOU:
                            case Platform::PLATFORM_CODE_PM:
                            case Platform::PLATFORM_CODE_WADI:
                            case Platform::PLATFORM_CODE_OBERLO:
                            case Platform::PLATFORM_CODE_WJFX:
                            case Platform::PLATFORM_CODE_ALIXX:
                            case Platform::PLATFORM_CODE_VOVA:
                                $orderQuery = OrderOtherKefu::find();
                                break;
                        }
                        //查询erp订单
                        $orderInfo = $orderQuery
                                ->andWhere(['platform_code' => $platform])
                                ->andWhere(['order_id' => $orderId])
                                ->asArray()
                                ->one();
                        if (!empty($orderInfo)) {
                            //更新平台退款订单is_aftersale is_match_rule状态
                              PlatformRefundOrder::updateAll(['is_aftersale'=>1,'is_match_rule'=>1], ['platform_code'=>$platform,'platform_order_id'=>$orderInfo['platform_order_id']]);
//                            $rlatformrefundorder = PlatformRefundOrder::find()->where(['platform_code' => $platform])->andWhere(['platform_order_id' => $orderInfo['platform_order_id']])->one();
//                            $rlatformrefundorder->is_aftersale = 1;
//                            $rlatformrefundorder->is_match_rule = 1;
//                            $rlatformrefundorder->save();
                        }




                        if (!$flag)
                            throw new \Exception('保存售后单失败', false);

                        $afterSaleOrderRedirect = new AfterSalesRedirect();
                        $afterSaleOrderRedirect->after_sale_id = $afterSalesOrderModel->after_sale_id;
                        $afterSaleOrderRedirect->ship_name = $shipName;
                        $afterSaleOrderRedirect->ship_street1 = $address1;
                        $afterSaleOrderRedirect->ship_street2 = $address2;
                        $afterSaleOrderRedirect->ship_zip = $postCode;
                        $afterSaleOrderRedirect->ship_city_name = $shipCityName;
                        $afterSaleOrderRedirect->ship_stateorprovince = $state;
                        $afterSaleOrderRedirect->ship_country = $shipCountry;
                        $afterSaleOrderRedirect->ship_phone = $shipPhone;
                        $afterSaleOrderRedirect->warehouse_id = $warehouseId;
                        $afterSaleOrderRedirect->warehouse_name = $warehouse_name;
                        $afterSaleOrderRedirect->ship_code = $shipCode;
                        $afterSaleOrderRedirect->ship_code_name = $ship_code_name;
                        $afterSaleOrderRedirect->platform_code = $platform;
                        $afterSaleOrderRedirect->order_id = $orderId;
                        $afterSaleOrderRedirect->redirect_order_id = $orderId . '-RE' . substr($afterSalesOrderModel->after_sale_id, -2);
                        $afterSaleOrderRedirect->order_remark = $order_remark;
                        $afterSaleOrderRedirect->print_remark = $print_remark;
                        $afterSaleOrderRedirect->order_amount = $redirect_order_amount;
                        $afterSaleOrderRedirect->currency = $currency;
                        $afterSaleOrderRedirect->paypal_id = $paypal_id;
                        $afterSaleOrderRedirect->paypal_email = $paypal_email;
                        $flag = $afterSaleOrderRedirect->save();
                        if (!$flag)
                            throw new \Exception('保存重寄数据失败');
                        foreach ($items as $row) {
                            $orderReturnDetail = new OrderRedirectDetail();
                            $orderReturnDetail->after_sale_id = $afterSalesOrderModel->after_sale_id;
                            $orderReturnDetail->item_id = $row['item_id'];
                            $orderReturnDetail->transaction_id = $row['transaction_id'];
                            $orderReturnDetail->sku = $row['sku'];
                            $orderReturnDetail->product_title = $row['productTitle'];
                            $orderReturnDetail->quantity = $row['quantity'];
                            $orderReturnDetail->linelist_cn_name = $row['linelist_cn_name'];
                            $flag = $orderReturnDetail->save();
                            if (!$flag)
                                throw new \Exception('保存重寄详情失败');
                        }
                        $t_after_id = $afterSalesOrderModel->after_sale_id;
                    }
                }
                $after_id = isset($t_after_id) ? $t_after_id : '';
                $after_id = isset($s_after_id) ? $s_after_id : $after_id;
                $after_id = isset($f_after_id) ? $f_after_id : $after_id;
                //保存问题产品
                if (!empty($issueProducts)) {
                    foreach ($issueProducts as $row) {
                        $afterSalesProductModel = new AfterSalesProduct();
                        $afterSalesProductModel->platform_code = $row['platform_code'];
                        $afterSalesProductModel->order_id = $row['order_id'];
                        $afterSalesProductModel->sku = $row['sku'];
                        $afterSalesProductModel->product_title = $row['product_title'];
                        $afterSalesProductModel->quantity = $row['quantity'];
                        $afterSalesProductModel->issue_quantity = $row['issue_quantity'];
                        $afterSalesProductModel->reason_id = $row['reason_id'];
                        $afterSalesProductModel->linelist_cn_name = $row['linelist_cn_name'];
                        $afterSalesProductModel->after_sale_id = $after_id;
                        $flag = $afterSalesProductModel->save();
                        if (!$flag)
                            throw new \Exception('保存问题产品失败');
                    }
                }
                //更新退件列表的操作记录
                $article = Domesticreturngoods::findOne(['order_id' => $orderId]);
                $datetime = date('Y-m-d H:i:s');
                if ($article !== null) {

                    $article->state = 3;
                    $article->handle_type = $handle_type;
                    $article->handle_user = Yii::$app->user->identity->login_name;
                    $article->handle_time = $datetime;
                    if ($handle_type == 3) {
                        $article->record = '退款';
                    } else if ($handle_type == 4) {
                        $article->record = '重寄';
                    } else {
                        $article->record = '退货';
                    }
                    $article->record .= '售后单号:' . $after_id;
                    if (!$article->save())
                        throw new \Exception('更新退件表失败');
                }
                //更新退款单表的is_aftersale字段
                $platformOrderId = isset($orderinfo->info) && isset($orderinfo->info->platform_order_id) ? $orderinfo->info->platform_order_id : '';
                $platformCode = isset($orderinfo->info) && isset($orderinfo->info->platform_code) ? $orderinfo->info->platform_code : '';
                if (!empty($platformOrderId) && !empty($platformCode)) {
                    $refundData = PlatformRefundOrder::findOne(['platform_order_id' => $platformOrderId,'platform_code'=>$platformCode]);
                    if ($refundData !== null) {
                        $refundData->is_aftersale = 1;
                        $refundData->modify_by = Yii::$app->user->identity->username;
                        $refundData->modify_time = $datetime;

                        if (!$refundData->save())
                            throw new \Exception('更新退款表失败');
                    }

                }
                $dbTransaction->commit();
                $session->remove('random'); //保存成功 清除表单提交随机数放置重复提交
                if ($from == 'inbox') {
                    //如果来自平台消息的入口，则不需要刷新页面
                    $extraJs = 'top.layer.closeAll("iframe");';
                    if (!empty($orderId)) {
                        $html = '';
                        if ($handle_type == 3) {
                            $html .= '退款:';
                            $url = Url::toRoute(['/aftersales/sales/detailrefund', 'after_sale_id' => $after_id, 'platform_code' => $platform]);
                            $html .= "<a onclick=\"layer.open({type:2,title:\'{$after_id}\',content:\'{$url}\',area:[\'100%\',\'100%\']});\">{$after_id}</a>";
                        } else if ($handle_type == 4) {
                            $html .= '重寄:';
                            $url = Url::toRoute(['/aftersales/sales/detailredirect', 'after_sale_id' => $after_id, 'platform_code' => $platform]);
                            $html .= "<a onclick=\"layer.open({type:2,title:\'{$after_id}\',content:\'{$url}\',area:[\'100%\',\'100%\']});\">{$after_id}</a>";
                        } else {
                            $html .= '退货:';
                            $url = Url::toRoute(['/aftersales/sales/detailreturn', 'after_sale_id' => $after_id, 'platform_code' => $platform]);
                            $html .= "<a onclick=\"layer.open({type:2,title:\'{$after_id}\',content:\'{$url}\',area:[\'100%\',\'100%\']});\">{$after_id}</a>";
                        }

                        $extraJs .= "top.$('#after_{$orderId}').append('{$html}');";
                    }
                    $this->_showMessage('操作成功', true, null, false, null, $extraJs);
                } else {
                    $this->_showMessage('操作成功', true, null, false, null, 'top.window.location.reload();');
                }
            } catch (\Exception $e) {
                $dbTransaction->rollBack();
                $this->_showMessage('操作失败：' . $e->getMessage(), false);
            }
        }
        $datas = ['orderId' => $orderId, 'platformCode' => $platform];
        $orderinfo = Json::decode(Json::encode($orderinfo), true);
        // $countires = Country::getCodeNamePairs();
        //获取国家信息
        $countires = Country::getCodeNamePairsList();


        //$warehouseList = Warehouse::getWarehouseList();
        //获取仓库数据
        $warehouse = Warehouse::getWarehouseListAll();
        if($platform=="AMAZON"){
           foreach($warehouse as $key=>$v){   
             if(empty(stripos($warehouse[$key], '虚拟仓'))){
              $warehouseList[$key]=$warehouse[$key];  
             }           
          }  
        }else{
           $warehouseList= $warehouse;
        }     
        $warehouseList_new = [];
        foreach ($warehouseList as $key => $value) {
            if (!in_array('请选择发货仓库', $warehouseList))
                $warehouseList_new[' '] = '请选择发货仓库';
            $warehouseList_new[$key] = $value;
        }
      
        //$logistics = Logistic::getWarehouseLogistics($orderinfo['info']['warehouse_id']);
        //获取仓库下所有的物流方式
        $logistics = Logistic::getLogistics($orderinfo['info']['warehouse_id']);

        $departmentList = BasicConfig::getParentList(52);
        $departmentList_new = [];
        foreach ($departmentList as $k => &$v) {
            $departmentList_new[$k]['depart_id'] = $k;
            $departmentList_new[$k]['depart_name'] = $v;
        }
        $reasonList = RefundReturnReason::getList('Array');


        $logistics_arr = [];
        if (!empty($logistics)) {
            foreach ($logistics as $key => $value) {
                $logistics_arr[$value->ship_code] = $value->ship_name;
            }
        }

        switch ($platform) {
            case Platform::PLATFORM_CODE_ALI:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/aliexpress_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_EB:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/ebay_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_WISH:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/wish_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/amazon_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_WALMART:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/walmart_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_SHOPEE:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/shopee_reason_code.php';
                break;
            default:
                $reasonCodeList = array();
        }

        $viewFile = 'add';
        $amazonSite = '';
        if ($platform == Platform::PLATFORM_CODE_AMAZON) {
            $viewFile = 'amazon_refund_add.php';
            $amazonSite = Account::getAmazonSite($orderinfo['info']['account_id']);
        } else if ($platform == Platform::PLATFORM_CODE_WALMART) {
//            if($orderId == 'VM180614000707'){
//                $viewFile = 'walmart_refund_add_bkb.php';
//            }else{
            $viewFile = 'walmart_refund_add.php';
//            }
        }
        $items = $orderinfo['items'];

        if (empty($items)) {
            if ($platform == Platform::PLATFORM_CODE_AMAZON)
                $items = OrderAmazonItem::getOrderItems($orderinfo['info']['platform_order_id']);
            elseif ($platform == Platform::PLATFORM_CODE_WALMART)
                $items = OrderWalmartItem::getOrderItems($orderinfo['info']['platform_order_id']);
            if (empty($items))
                $items = [];
        }

        //判断当前订单是否有退款单
        $refundOrderInfo = AfterSalesOrder::isSetAfterSalesOrder($orderId, 1);
        //判断当前订单是否有重寄单
        $redirectOrderInfo = AfterSalesOrder::isSetAfterSalesOrder($orderId, 3);
        // 重寄选择币种
        $currencys = array('USD', 'AUD', 'CAD', 'EUR', 'GBP', 'HKD');

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }
        //查询退货编码
        $refund_code=AfterRefundCode::find()->where(['order_id'=>$orderinfo['info']['order_id']])->asArray()->one();
        return $this->render($viewFile, [
                    'info' => $orderinfo,
                    'account_info' => $account_info,
                    'countries' => $countires,
                    'warehouseList' => $warehouseList,
                    'retrunWarehouseList' => $warehouseList_new,
                    'logistics' => $logistics,
                    'paypallist' => $palPalList,
                    'logistics_arr' => $logistics_arr,
                    'departmentList' => json_encode($departmentList_new),
                    'reasonList' => $reasonList,
                    'reasonCodeList' => $reasonCodeList,
                    'allow_refund_amount' => $allow_refund_amount,
                    'items' => $items,
                    'platform' => $platform,
                    //'orderAmount' => isset($orderinfo['info']['total_price']) ? $orderinfo['info']['total_price'] : 0.00,
                    'currencyCode' => isset($orderinfo['info']['currency']) ? $orderinfo['info']['currency'] : '',
                    'refundOrderInfo' => $refundOrderInfo,
                    'redirectOrderInfo' => $redirectOrderInfo,
                    'currencys' => $currencys,
                    'random' => $random,
                    'isAuthority' => $isAuthority,
                    'from' => $from,
                    'refund_code'=>$refund_code,
                    'amazonSite' => $amazonSite,
                    'warehouse'=>$warehouse
                    
        ]);
    }

    /**
     * @author alpha
     * @desc 售后单审核
     */
    public function actionAudit() {
        $afterSalesId = trim($this->request->getQueryParam('after_sales_id'));
        $status = (int) $this->request->getQueryParam('status');
        $url = $this->request->getQueryParam('url');
        $platform_code = $this->request->getQueryParam('platform_code');
        if (empty($afterSalesId))
            $this->_showMessage('无效的售后单号', false);
        $afterSalesOrderInfo = AfterSalesOrder::findById($afterSalesId);
        if (empty($afterSalesOrderInfo))
            $this->_showMessage('找不到该售后单', false);
        /* if ($afterSalesOrderInfo->status == AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED)
          $this->_showMessage('售后单已经审核过', false); */
        if (!in_array($status, [AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED, AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]))
            $this->_showMessage('无效的审核状态', false);

        $flag = $afterSalesOrderInfo->audit($status);
        
        //点开详情页面审核售后单没有平台参数修复 update by allen <2018-12-20> str
        $platform_code = empty($platform_code) ? $afterSalesOrderInfo -> platform_code : trim($platform_code);
        //点开详情页面审核售后单没有平台参数修复 update by allen <2018-12-20> end



        if (!$flag) {
            $message = empty($afterSalesOrderInfo->error_message) ? '审核失败' : $afterSalesOrderInfo->error_message;
            $this->_showMessage($message, false);
        } else {
            $redirectModel = AfterSalesRedirect::findOne(['after_sale_id' => $afterSalesId]);
            if (!empty($redirectModel)) {
                $afterSalesProductModel = AfterSalesProduct::find()->where(['after_sale_id' => $afterSalesId])->all();
                if (!empty($afterSalesProductModel)) {
                    foreach ($afterSalesProductModel as $model) {
                        //获取审核通过的重寄类型损失的金额
                        $data = AfterSalesOrder::getRefundRedirectData($model->platform_code, $redirectModel->order_id, $model->sku, $redirectModel->order_amount, $redirectModel->currency, 2, $redirectModel->redirect_order_id);
                        $model->refund_redirect_price = $data['sku_redirect_amt'];
                        $model->refund_redirect_price_rmb = $data['sku_redirect_amt_rmb'];
                        $model->save();
                    }
                }
            }

            //退款生成sku质量分析 只统计质量及破损原因 ,有返回值就审核失败
            $reason_arr = [73,74];
            $type_arr = [1,3];
            if ($afterSalesOrderInfo->status == 2 && in_array($afterSalesOrderInfo->reason_id,$reason_arr) && in_array($afterSalesOrderInfo->type,$type_arr) ) {
                $skuQualityInfo = SkuQualityAnalysis::createSkuRecord($afterSalesId,$platform_code,$afterSalesOrderInfo->order_id,$afterSalesOrderInfo->type);
                if ($skuQualityInfo) {
                    $message ='审核成功，添加产品质量破损分析信息失败';
                    $this->_showMessage($message, false);
                }
            }

        }



        if ($url) {
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute($url) . '");';
            $this->_showMessage('审核成功', true, null, false, null, $refreshUrl);
        } else {
            $this->_showMessage('审核成功', true, null, true);
        }
    }

    public function actionGetrefundlost() {
        $order_id = $this->request->getQueryParam('order_id');
        $platform_code = $this->request->getQueryParam('platform_code');
        $refund_amount = $this->request->getQueryParam('refund_amount');
        if (!is_numeric($refund_amount))
            $this->_showMessage('退款数据有误', false);

        $order_profit = new Order();
        $order_profit = $order_profit->getOrderProfitByOrderId($order_id);

        if ($order_profit && $order_profit->ack == true) {
            $refund_amount = $order_profit->data->profit - $refund_amount * $order_profit->data->currency_rate;
            $this->_showMessage('', true, null, false, $refund_amount . '&nbsp;CNY');
        } else {
            $this->_showMessage('未获取到订单利润信息', false);
        }
    }

    public function actionGetredirectlost() {
        $platform_code = $this->request->getQueryParam('platform_code');
        $order_id = $this->request->getQueryParam('order_id');
        $sku_arr = $this->request->getQueryParam('sku_arr');
        $quantity_arr = $this->request->getQueryParam('quantity_arr');
        $ship_code = $this->request->getQueryParam('ship_code');
        $ship_country = $this->request->getQueryParam('ship_country');
        $ship_country_name = $this->request->getQueryParam('ship_country_name');
        $order_amount = $this->request->getQueryParam('order_amount');
        $currency = $this->request->getQueryParam('currency');
        $cost = 0.00;
        $cost = new Order;
        $cost = $cost->getPreRedirectCost($platform_code, $order_id, $sku_arr, $quantity_arr, $ship_code, $ship_country, $ship_country_name, $order_amount, $currency);
        if ($cost && $cost->ack == true) {
            $cost = $cost->data;
            $this->_showMessage('', true, null, false, $cost . '&nbsp;CNY');
        } else {
            $this->_showMessage('数据异常', false);
        }
    }

}
