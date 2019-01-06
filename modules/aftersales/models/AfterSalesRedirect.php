<?php

namespace app\modules\aftersales\models;

use app\modules\systems\models\Country;
use app\modules\systems\models\ErpOrderApi;
use app\modules\orders\models\Order;
use app\common\VHelper;
use app\modules\systems\models\BasicConfig;
use app\modules\aftersales\models\AfterSalesRedirectDown;

class AfterSalesRedirect extends AfterSalesModel
{

    public $error_message = '';

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%after_sales_redirect}}';
    }

    public function attributes()
    {
        $attributes      = parent::attributes();
        $extraAttributes = ['quantity', 'account_id', 'order_type', 'payer_email', 'receiver_email', 'type',
            'department_id', 'account_name', 'paytime', 'buyer_id', 'order_status', 'shipped_date', 'sku',
            'pro_name', 'sum_quantity', 'line_cn_name', 'account_name',
            'reSku', 'rePname', 'reSumqty', 'resendId', 'reOrderStatus', 'reWarehouse', 'reShipName',
            'reShippedDate', 'reason_id', 'reason', 'createBy', 'remark', 'status', 'amazon_fulfill_channel',
            'order_number', 'platform_order_id', 'createTime', 'approve_time', 'total_price','sku_total_price',
            'sku_redirect_amt', 'sku_redirect_amt_rmb', 'is_fbc'];

        return array_merge($attributes, $extraAttributes);
    }

    /**
     * @desc 根据售后单号和平台code获取售后重寄信息
     */
    public static function getList($after_sale_id, $platform_code)
    {
        return self::find()->where(['after_sale_id' => $after_sale_id, 'platform_code' => $platform_code])->one();
    }

    public function audit(AfterSalesOrder $model)
    {
        $afterSalesRedirectInfo = $this->findOne(['after_sale_id' => $model->after_sale_id]);

        if (empty($afterSalesRedirectInfo))
            return false;
        $afterSalesRedirectDetail = OrderRedirectDetail::getByAfterSalesId($model->after_sale_id);

        $orderId                  = $model->order_id;
        $platformCode             = $model->platform_code;
        $product                  = [];
        foreach ($afterSalesRedirectDetail as $row) {
            $items[] = [
                'sku'            => $row->sku,
                'item_id'        => $row->item_id,
                'transaction_id' => $row->transaction_id,
                'productTitle'   => $row->product_title,
                'quantity'       => $row->quantity,
            ];
        }
        $datas = [];
        //推送数据到订单系统
        $orderId                    = $model->order_id;
        $platformCode               = $model->platform_code;
        $datas['orderId']           = $orderId;
        $datas['platformCode']      = $platformCode;
        $datas['city']              = $afterSalesRedirectInfo->ship_city_name;
        $datas['countryCode']       = $afterSalesRedirectInfo->ship_country;
        $datas['shipName']          = $afterSalesRedirectInfo->ship_name;
        $datas['phone']             = $afterSalesRedirectInfo->ship_phone;
        $datas['province']          = $afterSalesRedirectInfo->ship_stateorprovince;
        $datas['address1']          = $afterSalesRedirectInfo->ship_street1;
        $datas['address2']          = $afterSalesRedirectInfo->ship_street2;
        $datas['postCode']          = $afterSalesRedirectInfo->ship_zip;
        $datas['afterSalesOrderId'] = $model->after_sale_id;
        $datas['redirectOrderId']   = $afterSalesRedirectInfo->redirect_order_id;
        $datas['items']             = $items;
        $datas['warehouseId']       = $afterSalesRedirectInfo->warehouse_id;
        $datas['shipCode']          = $afterSalesRedirectInfo->ship_code;
        $datas['items']             = $items;
        $datas['order_remark']      = $afterSalesRedirectInfo->order_remark;
        $datas['print_remark']      = $afterSalesRedirectInfo->print_remark;
        $datas['order_amount']      = $afterSalesRedirectInfo->order_amount;
        $datas['currency']          = $afterSalesRedirectInfo->currency;
        $datas['paypal_id']         = $afterSalesRedirectInfo->paypal_id;
        $datas['paypal_email']      = $afterSalesRedirectInfo->paypal_email;
        $datas['createBy']          = $model->create_by;
        $erpOrderApi                = new ErpOrderApi();
        $flag                       = $erpOrderApi->redirectOrder($datas);
        if (!$flag) {
            $this->error_message = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return true;
    }

    public function getAfterSalesOrderDetails($afterSalesId)
    {
        return OrderRedirectDetail::getByAfterSalesId($afterSalesId);
    }

    /**
     * 获取重寄订单下载需要的数据
     * 1.根据查询条件获取需要下载的重寄售后单
     * 2.获取重寄订单的订单号和原订单号保存为数组
     * 3.重寄订单号数组传给erp接口获取原订单信息已经重寄订单发货等信息
     * 4.循环整合信息
     * @author allen <2018-1-9>
     * @param $condition
     * @param $where
     * @param $andWhere
     * @return array|\yii\db\ActiveRecord[]
     * @throws \yii\db\Exception
     */
    public static function getRedirectData($condition, $where, $andWhere)
    {
        $query = self::find();//获取对象
        //查询字段
        $query->select("t3.account_name as account_name,t.account_id,"
            . "t.platform_code,"
            . "t.after_sale_id,"
            . "t.department_id,"
            . "t.order_id,"
            . "t.type,"
            . "t.create_time as createTime,"
            . "t.approve_time,"
            . "t1.redirect_order_id as `resendId`,"
            . "t1.order_amount,"
            . "t1.currency,"
            . "t1.paypal_id,"
            . "t4.payer_email,"
            . "t4.receiver_email,"
            . "group_concat(`t2`.`sku` separator ',') as `reSku`,"
            . "group_concat(`t2`.`quantity` separator ',') as `quantity`,"
            . "group_concat(`t2`.`product_title` separator ',') as `rePname`,"
            . "sum(`t2`.`quantity`) as `reSumqty`,"
            . "t.reason_id,"
            . "t.create_by as createBy,"
            . "t.remark,"
            . "t.status,"
            . "t1.ship_country,"
            . "group_concat(`t2`.`linelist_cn_name` separator ',') as `line_cn_name`");
        //关联相关表
        $query->from('{{%after_sales_order}} `t`')
            ->join('LEFT JOIN', '{{%after_sales_redirect}} `t1`', '`t`.`after_sale_id` = `t1`.`after_sale_id`')
            ->join('LEFT JOIN', '{{%order_redirect_detail}} `t2`', '`t`.`after_sale_id` = `t2`.`after_sale_id`')
            ->join('LEFT JOIN', '{{%account}} t3', 't.account_id = t3.id')
            ->join('LEFT JOIN', '{{%eb_paypal_transaction_record}} t4', 't.transaction_id = t4.transaction_id');
        if ($condition) {
            $query->where(['in', 't.after_sale_id', $condition]);
        } else {
            $query->where($where);
            if (!empty($andWhere)) {
                foreach ($andWhere as $value) {
                    $query->andWhere($value);
                }
            }
        }
        //获取结果集
        $model = $query->groupBy('t.after_sale_id')->orderBy('t.platform_code,t.account_id,t.after_sale_id')->all();

        //获取旧退款原因
        $refundReasonList = RefundReturnReason::getList('Array');
        //获取新退款原因
        $getAllConfigList = BasicConfig::getAllConfigData();
        //售后单审核状态
        $auditStatus = VHelper::afterSalesStatusList();
        //ERP订单完成状态
        $orderStats = VHelper::orderStatusList();
        // 获取国家简码数据
        $countires = Country::getCodeNamePairs('cn_name');
        if (!empty($model)) {
            $order = [];
            foreach ($model as $value) {
                if (isset($value->ship_country) && !empty($value->ship_country) && isset($countires[$value->ship_country])) {
                    $value->setAttribute('ship_country_name', $countires[$value->ship_country]);
                }
                if (in_array($value->platform_code, ['ALI', 'AMAZON', 'EB', 'WISH'])) {
                    $order[$value->platform_code]['order_id'][]    = $value->order_id;
                    $order[$value->platform_code]['re_order_id'][] = $value->resendId;
                } else {
                    $order['Other']['order_id'][]    = $value->order_id;
                    $order['Other']['re_order_id'][] = $value->resendId;
                }
            }
            //erp订单相关重寄数据
            //$response = Order::getResendDataInfo($order);

            //从库相关重寄订单
            $response = AfterSalesRedirectDown::getResendDataInfo($order);

            if (empty($response)) {
                foreach ($model as $k => $v) {
                    if ($v->department_id) {
                        $v->department_id = isset($getAllConfigList[$v->department_id]) ? $getAllConfigList[$v->department_id] : '';
                        $v->reason_id     = isset($getAllConfigList[$v->reason_id]) ? $getAllConfigList[$v->reason_id] : '';
                    } else {
                        $v->reason_id = isset($refundReasonList[$v->reason_id]) ? $refundReasonList[$v->reason_id] : '';
                    }
                    $v->status     = $auditStatus[$v->status];
                    $v->order_type = '';

                }
            }
            if (!empty($response)) {
                foreach ($model as $k => $val) {
                    foreach ($response as $resp_k => $resp_val) {
                        if ($val->order_id == $resp_val['order_id']) {
                            $val->order_type        = isset($resp_val['order_type']) ? $resp_val['order_type'] : "";
                            $val->platform_order_id = isset($resp_val['platform_order_id']) ? $resp_val['platform_order_id'] : "";
                            $val->order_status      = isset($resp_val['order_status']) ? $orderStats[$resp_val['order_status']] : "";
                            $val->reOrderStatus     = isset($resp_val['reOrderStatus']) ? $orderStats[$resp_val['reOrderStatus']] : "";
                            $val->sku               = $resp_val['sku'];
                            $val->pro_name          = $resp_val['pro_name'];
                            $val->sum_quantity      = $resp_val['sum_quantity'];
                            $val->shipped_date      = $resp_val['shipped_date'];
                            $val->warehouse_name    = $resp_val['warehouse_name'];
                            $val->ship_name         = $resp_val['ship_name'];
                            $val->reWarehouse       = isset($resp_val['reWarehouse']) ? $resp_val['reWarehouse'] : "";
                            $val->reShipName        = isset($resp_val['reShipName']) ? $resp_val['reShipName'] : "";
                            $val->reShippedDate     = isset($resp_val['reShippedDate']) ? $resp_val['reShippedDate'] : "";
                            $val->total_price       = $resp_val['total_price'];//添加订单总金额
                        }
                    }
                    if ($val->department_id) {
                        $val->department_id = isset($getAllConfigList[$val->department_id]) ? $getAllConfigList[$val->department_id] : '';
                        $val->reason_id     = isset($getAllConfigList[$val->reason_id]) ? $getAllConfigList[$val->reason_id] : '';
                    } else {
                        $val->reason_id = isset($refundReasonList[$val->reason_id]) ? $refundReasonList[$val->reason_id] : '';
                    }
                    $val->status = $auditStatus[$val->status];
                }
            }
        }
        return $model;
    }

    /**
     * 获取单个退款信息
     */
    public static function findByAfterSaleId($after_sale_id)
    {
        return self::find()->where(['after_sale_id' => $after_sale_id])->asArray()->one();
    }
}