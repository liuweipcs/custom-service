<?php

namespace app\modules\aftersales\models;

use app\modules\accounts\models\Platform;
use app\modules\orders\models\OrderOtherSearch;
use app\modules\systems\models\ErpOrderApi;
use Yii;

class AfterSalesRefund extends AfterSalesModel {

    public $error_message = '';

    const REFUND_STATUS_WAIT = 1; //待退款
    const REFUND_STATUS_ING = 2;  //退款中
    const REFUND_STATUS_FINISH = 3; //退款完成
    const REFUND_STATUS_FAIL = 4; //退款失败
    const REFUND_STATUS_WAIT_RECEIVE = 5; //等待接受退款中
    const REFUND_TYPE_FULL = 2; //全部退款
    const REFUND_TYPE_PARTIAL = 1; //部分退款  
    const REFUND_FAIL_COUNT_MAX = 5; //退款失败5五次后将不再执行计划任务进行退款操作

    public $account_id;

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName() {
        return '{{%after_sales_refund}}';
    }

    public function attributes() {
        $attributes = parent::attributes();
        $extraAttributes = ['status']; //状态

        return array_merge($attributes, $extraAttributes);
    }

    /**
     * 退款状态列表
     */
    public static function getRefundStatusList($key = null)
    {
        $data = [
            self::REFUND_STATUS_WAIT => '待退款',
            self::REFUND_STATUS_ING => '退款中',
            self::REFUND_STATUS_FINISH => '退款完成',
            self::REFUND_STATUS_FAIL => '退款失败',
            self::REFUND_STATUS_WAIT_RECEIVE => '等待接受退款中',
        ];

        if (isset($key)) {
            return array_key_exists($key, $data) ? $data[$key] : '';
        } else {
            return $data;
        }
    }

    /**
     * 根据平台code获取退款申请列表
     * @param string $platform_code 平台code
     * @param int $limit 一次性拉取多少条数据
     */
    public static function getListByPlatformCode($platform_code, $limit, $account_id = null) {
        //查找指定平台的而且已经审核通过的售后退款单号
//        $after_sale_ids = AfterSalesOrder::getAfterSaleIdByOrderId(['platform_code' => $platform_code, 'limit' => $limit]);
//
//        if (empty($after_sale_ids)) {
//            return [];
//        }
        if (!in_array($platform_code, array(Platform::PLATFORM_CODE_AMAZON, Platform::PLATFORM_CODE_WALMART))) {
            $query = self::find()->from(self::tableName() . ' as t1')->select('t2.account_id,t1.*');
            $query->where('t1.platform_code=:platform_code', [':platform_code' => $platform_code]);

            $query->innerJoin(AfterSalesOrder::tableName() . ' as t2', 't2.after_sale_id = t1.after_sale_id');
            //必须是已经审核通过的退款单
//        $query->andWhere(['in', 'after_sale_id', $after_sale_ids]);
            if (!empty($account_id))
                $query->andWhere(['t2.account_id' => $account_id]);

            $query->andWhere('t2.status = :status and t2.type = :type', [':status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED, ':type' => AfterSalesOrder::ORDER_TYPE_REFUND]);

            //退款完成和退款中的状态将不再进行计划任务退款
            $query->andWhere(['in', 't1.refund_status', [self::REFUND_STATUS_WAIT, self::REFUND_STATUS_FAIL]]);

            //退款失败次数超过最大限制将不再进行计划任务退款
            $query->andWhere(['<=', 't1.fail_count', self::REFUND_FAIL_COUNT_MAX]);

            $query->limit($limit);

            $res = $query->all();
            return $res;
        } else {
            $query = self::find()->from(self::tableName() . ' as t1')->select('t2.account_id,t1.*');
            $query->where('t1.platform_code=:platform_code', [':platform_code' => $platform_code]);

            $query->innerJoin(AfterSalesOrder::tableName() . ' as t2', 't2.after_sale_id = t1.after_sale_id');
            //必须是已经审核通过的退款单
//        $query->andWhere(['in', 'after_sale_id', $after_sale_ids]);
            if (!empty($account_id))
                $query->andWhere(['t2.account_id' => $account_id]);

            $query->andWhere('t2.status = :status and t2.type = :type', [':status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED, ':type' => AfterSalesOrder::ORDER_TYPE_REFUND]);

            //退款完成和退款中的状态将不再进行计划任务退款
            $query->andWhere(['in', 't1.refund_status', [self::REFUND_STATUS_WAIT, self::REFUND_STATUS_FAIL]]);

            //退款失败次数超过最大限制将不再进行计划任务退款
            $query->andWhere(['<=', 't1.fail_count', self::REFUND_FAIL_COUNT_MAX]);
            $query->andWhere('t1.refund_detail is not null');

            $query->limit($limit);
            $res = $query->all();

            return $res;
        }
    }

    public static function getAmazonList($platform_code = 'AMAZON', $limit, $account_id = null) {

        $query = self::find()->from(self::tableName() . ' as t1')->select('t1.*');
        $query->where('t1.platform_code=:platform_code', [':platform_code' => $platform_code]);

        $query->innerJoin(AfterSalesOrder::tableName() . ' as t2', 't2.after_sale_id = t1.after_sale_id');
        //必须是已经审核通过的退款单
//        $query->andWhere(['in', 'after_sale_id', $after_sale_ids]);
        if (!empty($account_id))
            $query->andWhere(['t2.account_id' => $account_id]);

        $query->andWhere('t2.status = :status and t2.type = :type', [':status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED, ':type' => AfterSalesOrder::ORDER_TYPE_REFUND]);

        //退款完成和退款中的状态将不再进行计划任务退款
        $query->andWhere(['in', 't1.refund_status', [self::REFUND_STATUS_WAIT, self::REFUND_STATUS_FAIL]]);

        //退款失败次数超过最大限制将不再进行计划任务退款
        $query->andWhere(['<=', 't1.fail_count', self::REFUND_FAIL_COUNT_MAX]);
        $query->andWhere('t1.refund_detail is not null');

        $query->limit($limit);

        return $query->all();
    }

    /**
     * @desc 获取指定订单号的可退款金额(订单总金额-已经审核通过的退款单的退款金额之和)
     * @param string $order_id 订单号
     * @param string $order_amount 订单金额
     * @param string $platform_code 平台code
     */
    public static function getAllowRefundAmount($order_id, $order_amount, $platform_code) {
        //要返回的可退款金额,已经审核通过的退款单的退款金额之和
        $allow_refund_amount = 0.00;
        $audit_refund_amount = 0.00;

        if (empty($order_id) || empty($order_amount) || empty($platform_code)) {
            return $allow_refund_amount;
        }

        //指定订单号的而且已经审核通过的退款单类型的售后单号
        $audit_after_sales_ids = AfterSalesOrder::getAfterSaleIdByOrderId(['order_id' => $order_id, 'platform_code' => $platform_code]);

        //该订单还没有已经审核通过的售后单
        if (empty($audit_after_sales_ids)) {
            $allow_refund_amount = $order_amount - $audit_refund_amount;
            return sprintf("%.2f", $allow_refund_amount);
        }

        $query = (new \yii\db\Query())->from(self::tableName())->where(['in', 'after_sale_id', $audit_after_sales_ids]);
        $audit_refund_amount = $query->sum('refund_amount');

        return sprintf("%.2f", $order_amount - $audit_refund_amount);
    }

    /**
     * @desc 根据售后单号获取退款信息
     */
    public static function getList($after_sale_id, $platform_code) {
        return self::find()->where(['after_sale_id' => $after_sale_id, 'platform_code' => $platform_code])->one();
    }

    /**
     * @desc 退款审核
     * @param AfterSalesOrder $model
     * @param type 1:新建售后单  2：登记退款单
     * @return bool
     */
    public function audit(AfterSalesOrder $model, $type = 1) {
        $afterSalesRefundInfo = $this->findOne(['after_sale_id' => $model->after_sale_id]);
        if (!$afterSalesRefundInfo) {
            $this->error_message = '售后单: ' . $model->after_sale_id . ' 未找到!';
            return false;
        }
        //根据订单去对应平台退款
        $orderId = $model->order_id;
        $platformCode = $model->platform_code;
        $account_id = $model->account_id;
        $platform_order_id = $afterSalesRefundInfo->platform_order_id;

        //添加shopee审核
        if ($platformCode == Platform::PLATFORM_CODE_SHOPEE && $type == 1) {
            //调用接口
            if (empty($platform_order_id)) {
                return false;
            }
            if (empty($account_id)) {
                return false;
            }
            $res= \app\modules\mails\models\ShopeeDisputeList::findOne(['ordersn'=>$platform_order_id]);
            //纠纷 同意退款
            $result = OrderOtherSearch::ConfirmReturn($res->returnsn, intval($account_id));
            return $result;
        }

        $total_refund_amount = $afterSalesRefundInfo->refund_amount;
        // 查询该订单的退款总数
        if ($afterSalesRefundInfo->refund_type == self::REFUND_TYPE_PARTIAL) {
            $afterSaleModels = self::find()->where(['order_id' => $afterSalesRefundInfo->order_id, 'refund_status' => self::REFUND_STATUS_FINISH])->andWhere(['<>', 'after_sale_id', $afterSalesRefundInfo->after_sale_id])->asArray()->all();
            // 已经退款的金额(包括此次退款)
            foreach ($afterSaleModels as $afterSaleModel) {
                $total_refund_amount += $afterSaleModel['refund_amount'];
            }
        }

        //修改订单系统订单退款状态
        $datas = [
            'order_id' => $model->order_id,
            'return_refund_id' => $model->after_sale_id,
            'refund_sum' => $afterSalesRefundInfo->refund_amount,
            'refund_type' => $afterSalesRefundInfo->refund_type,
            'real_refund_sum' => $afterSalesRefundInfo->refund_amount,
            'currency' => $afterSalesRefundInfo->currency,
            'reason' => $model->reason_id,
            'verify_date' => $model->approve_time,
            'refund_date' => $model->approve_time,
            'status' => $model->status,
            'total_refund_amount' => $total_refund_amount,
        ];
        $erpOrderApi = new ErpOrderApi();

        if (!$erpOrderApi->setRefund($model->platform_code, $datas)) {
            $this->error_message = $erpOrderApi->getExcptionMessage();
            return false;
        }
        return true;
    }

    /**
     * 获取单个退款信息
     */
    public static function findByAfterSaleId($after_sale_id) {
        return self::find()->where(['after_sale_id' => $after_sale_id])->asArray()->one();
    }

}
