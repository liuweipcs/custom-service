<?php

namespace app\modules\aftersales\models;

use app\common\VHelper;
use app\modules\orders\models\Order;
use app\modules\orders\models\Transactionrecord;
use app\modules\systems\models\RefundAccount;
use yii\helpers\Json;

class AfterSalesReceipt extends AfterSalesModel
{

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%after_sales_receipt}}';
    }

    /**
     * 查询收款单
     * @param $platform_code
     * @param $account_id
     * @param $order_id
     * @param $buyer_id
     * @param $begin_date
     * @param $end_date
     * @param int $pageCur
     * @param int $pageSize
     * @return array|null
     */
    public static function getReceiptList($platform_code, $account_id, $order_id, $buyer_id, $begin_date, $end_date, $pageCur = 0, $pageSize = 0, $creater)
    {
        $query = self::find();
        $query->select(['t.*']);
        $query->from(self::tableName() . ' t');

        if (isset($platform_code) && !empty($platform_code)) {

            $query->andWhere(['t.platform_code' => $platform_code]);
        }
        if (isset($account_id) && !empty($account_id)) {

            $query->andWhere(['t.account_id' => $account_id]);
        }
        if (isset($order_id) && !empty($order_id)) {

            $query->andWhere(['t.order_id' => $order_id]);
        }
        if (isset($buyer_id) && !empty($buyer_id)) {

            $query->andWhere(['t.buyer_id' => $buyer_id]);
        }
        if (isset($creater) && !empty($creater)) {

            $query->andWhere(['t.creater' => $creater]);
        }

        //发货时间
        if ($begin_date && $end_date) {
            $query->andWhere(['between', 't.created_time', $begin_date, $end_date]);
        } else if (!empty($begin_date)) {
            $query->andWhere(['>=', 't.created_time', $begin_date]);
        } else if (!empty($end_date)) {
            $query->andWhere(['<=', 't.created_time', $end_date]);
        }
        $count     = $query->count();
        $pageCur   = $pageCur ? $pageCur : 1;
        $pageSize  = $pageSize ? $pageSize : \Yii::$app->params['defaultPageSize'];
        $offset    = ($pageCur - 1) * $pageSize;
        $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.created_time' => SORT_DESC])->asArray()->all();
        if (!empty($data_list)) {
            return [
                'count'     => $count,
                'data_list' => $data_list,
            ];
        }
        return null;

    }

    /**
     * 下载收款单
     * @param $json_arr
     * @param $platform_code
     * @param $account_id
     * @param $order_id
     * @param $buyer_id
     * @param $begin_date
     * @param $end_date
     * @return array|null
     */
    public static function getDownloadList($json_arr, $platform_code, $account_id, $order_id, $buyer_id, $begin_date, $end_date)
    {
        $query = self::find();
        $query->select(['t.*']);
        $query->from(self::tableName() . ' t');
        if ($json_arr) {
            $query->where(['in', 't.after_sale_receipt_id', $json_arr]);
        } else {
            if (isset($platform_code) && !empty($platform_code)) {

                $query->andWhere(['t.platform_code' => $platform_code]);
            }
            if (isset($account_id) && !empty($account_id)) {

                $query->andWhere(['t.account_id' => $account_id]);
            }
            if (isset($order_id) && !empty($order_id)) {

                $query->andWhere(['t.order_id' => $order_id]);
            }
            if (isset($buyer_id) && !empty($buyer_id)) {

                $query->andWhere(['t.buyer_id' => $buyer_id]);
            }
            //申请时间
            if ($begin_date && $end_date) {
                $query->andWhere(['between', 't.created_time', $begin_date, $end_date]);
            } else if (!empty($begin_date)) {
                $query->andWhere(['>=', 't.created_time', $begin_date]);
            } else if (!empty($end_date)) {
                $query->andWhere(['<=', 't.created_time', $end_date]);
            }
        }
        $data_list = $query->orderBy(['t.created_time' => SORT_DESC])->asArray()->all();
        if (!empty($data_list)) {
            return [
                'data_list' => $data_list,
            ];
        }
        return null;

    }

    /**
     * 获取审核状态
     * @param $key
     * @return string
     */
    public static function getReceiptAuditStatus($key)
    {
        switch ($key) {
            case 1:
                return "未审核";
                break;
            case 2:
                return "审核通过";
                break;
            case 3:
                return "审核未通过";
                break;
        }
    }

    /**
     * 获取收款类型
     * @param $key
     * @return string
     */
    public static function getReceiptType($key)
    {
        switch ($key) {
            case 1:
                return "paypal收款";
                break;
            case 2:
                return "线下收款";
                break;

        }
    }

    /**
     * 获取收款原因类型
     * @param $key
     * @return string
     */
    public static function getReceiptReasonType($key)
    {
        switch ($key) {
            case 1:
                return "收到退回";
                break;
            case 2:
                return "加钱重寄";
                break;
            case 3:
                return "假重寄";
                break;
            case 4:
                return "其他";
                break;

        }
    }

    /**
     * * 订单绑定payPal收款信息
     * 1.本地paypal交易记录获取数据 如果能获取到则直接获取
     * 2.获取不到就调用paypal Api获取
     * @param $orderId
     * @param $account
     * @param $transactionId
     */
    public static function Orderbindtransaction($orderId, $account, $transactionId)
    {
        $bool    = FALSE;
        $message = '';
        //先从本地paypal交易记录获取数据 如果能获取到则直接获取  获取不到就调用paypal Api获取
        $info = Transactionrecord::getTransactionInfo($transactionId);
        if (empty($info)) {
            //通过payPal Api获取交易详情
            $account_info = RefundAccount::findOne($account);
            if (empty($account_info)) {
                $bool    = TRUE;
                $message = "payPal账号获取失败!";
            }
            if (!$bool) {
                //组请求数据
                $params['detail_config'] = [
                    'acct1.UserName'  => $account_info['api_username'],
                    'acct1.Password'  => $account_info['api_password'],
                    'acct1.Signature' => $account_info['api_signature'],
                ];
                $params['transID']       = $transactionId;

                $response = VHelper::ebTransactionDeail($params);
                $info     = $response[0];

            }
        }
        //同步ERP
        if (!$bool) {
            $payPalInfo = json_decode(Json::encode($info), TRUE);
            $apiDatas   = [
                'receipt'       => 'receipt',
                'orderId'       => $orderId,
                'transactionId' => $transactionId,
                'payPalInfo'    => $payPalInfo,
            ];
            $apiRes     = Order::orderbindtransaction($apiDatas);
            if ($apiRes['bool']) {
                $bool    = TRUE;
                $message = $apiRes['info'];
            } else {
                $message .= $apiRes['info'];
            }
        }
        return json_encode(['bool' => $bool, 'msg' => $message, 'info' => $info, 'code' => 200]);
    }

    /** 解除绑定
     * @param $orderId
     * @param $account
     * @param $transactionId
     */
    public static function Orderunbindtransaction($orderId, $account, $transactionId)
    {
        $bool    = FALSE;
        $message = '';
        //先从本地paypal交易记录获取数据 如果能获取到则直接获取  获取不到就调用paypal Api获取
        $info = Transactionrecord::getTransactionInfo($transactionId);
        if (empty($info)) {
            //通过payPal Api获取交易详情
            $account_info = RefundAccount::findOne($account);
            if (empty($account_info)) {
                $bool    = TRUE;
                $message = "payPal账号获取失败!";
            }
            if (!$bool) {
                //组请求数据
                $params['detail_config'] = [
                    'acct1.UserName'  => $account_info['api_username'],
                    'acct1.Password'  => $account_info['api_password'],
                    'acct1.Signature' => $account_info['api_signature'],
                ];
                $params['transID']       = $transactionId;
                $response                = VHelper::ebTransactionDeail($params);
                $info                    = $response[0];
            }
        }
        //同步ERP
        if (!$bool) {
            $payPalInfo = json_decode(Json::encode($info), TRUE);
            $apiDatas   = [
                'receipt'       => 'receipt',
                'orderId'       => $orderId,
                'transactionId' => $transactionId,
                'payPalInfo'    => $payPalInfo,
            ];
            $apiRes     = Order::orderunbindtransaction($apiDatas);
            if ($apiRes['bool']) {
                $bool    = TRUE;
                $message = $apiRes['info'];
            } else {
                $message .= $apiRes['info'];
            }
        }
        return json_encode(['bool' => $bool, 'msg' => $message, 'info' => $info, 'code' => 200]);
    }
}