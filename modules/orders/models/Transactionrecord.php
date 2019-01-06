<?php

/**
 * Created by PhpStorm.
 * User: miko
 * Date: 2017/8/2
 * Time: 10:26
 */

namespace app\modules\orders\models;

use app\common\VHelper;
use app\components\Model;

class Transactionrecord extends Model {

    public static function tableName() {
        return "{{%eb_paypal_transaction_record}}";
    }

    public function rules() {
        return [
            [['transaction_id', 'receiver_id', 'payer_id', 'payer_name', 'payer_name', 'payer_status', 'transaction_type', 'payment_type', 'order_time', 'currency', 'payment_status', 'receiver_business'], 'required'],
            [['receiver_email', 'payer_email'], 'email'],
            [['receive_type'], 'integer'],
        ];
    }

    public function attributeLabels() {
        return [
            'transaction_id' => '交易ID',
            'receive_type' => '接收类型',
            'receiver_business' => '接收业务',
            'receiver_email' => '接收邮箱',
            'receiver_id' => '接收ID',
            'payer_id' => '付款人ID',
            'payer_name' => '付款人姓名',
            'payer_email' => '付款人邮箱',
            'payer_status' => '付款人状态',
            'transaction_type' => '交易类型',
            'payment_type' => '付款类型',
            'order_time' => '付款时间',
            'amt' => '替代最低税',
            'tax_amt' => '不懂',
            'fee_amt' => '还是不懂',
            'currency' => '货币',
            'payment_status' => '付款状态',
            'note' => '备注',
            'modify_time' => '修改时间'
        ];
    }

    public function filterOptions() {
        return [
            [
                'name' => 'transaction_id',
                'type' => 'text',
                'search' => '=',
            ],
        ];
    }

    public function searchList($params = [], $sort = NULL) {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'transaction_id' => SORT_ASC
        );
        $dataProvider = parent::search(null, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        return $dataProvider;
    }

    public function addition(&$models) {
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('receive_type', self::receivetype($model->receive_type));
        }
    }

    public function receivetype($receiveType) {
        $data = [1 => '接收', 2 => '发送'];

        return $data[$receiveType];
    }

    /**
     * 获取订单的交易记录
     * @param type $data
     * @author allen <2018-1-9>
     */
    public static function getAfterSalesTransactionRecord($data) {
        $returnData = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $receiver_email = ''; //收款paypal账号
                $transaction_id = ''; //收款交易ID
                $payer_email = ''; //卖家收款时买家的付款账号
                $transactionId = explode(',', $value);
                $transactionIds = array_unique($transactionId);
                $model = self::find()->where(['in', 'transaction_id', $transactionIds])->orderBy('receive_type ASC')->asArray()->all();
                if (!empty($model)) {
                    foreach ($model as $k => $val) {
                        //收款账号
                        if ($val['receive_type'] == 1) {
                            $receiver_email = $val['receiver_email']; //收款paypal账号
                            $transaction_id = $val['transaction_id']; //收款交易ID
                            $payer_email = $val['payer_email']; //卖家收款时买家的付款账号
                        } else {
                            $returnData[] = [
                                'tag' => 2,
                                'order_id' => $key,
                                'receiverEmail' => $receiver_email, //收款payPal账号
                                'transactionId' => $transaction_id, //收款交易ID
                                'payerEmail' => $payer_email, //退款时买家收款paypal账号
                                'refundTransactionID' => $val['transaction_id'], //退款payPalID
                                'currency' => $val['currency'], //退款货币符号
                                'amt' => $val['amt'], //退款金额
                                'refund_time' => $val['order_time'], //payPal退款时间
                            ];
                        }
                    }
                }
            }
        }
        return $returnData;
    }

    /**
     * 根据交易号获取交易详情
     * @param type $transactionId
     * @return type
     * @author allen <2018-03-10>
     */
    public static function getTransactionInfo($transactionId) {
        return self::find()->where(['transaction_id' => $transactionId])->one();
    }

    /**
     * 添加交易记录数据
     * @param type $info
     * @return type
     * @author allen <2018-03-12>
     */
    public static function addTranctionRecord($info) {
        $bool = false;
        $msg = "";
        $model = new Transactionrecord();
        $transactionId = $info->PaymentTransactionDetails->PaymentInfo->TransactionID;
        $TransactionRecord = Transactionrecord::find()->where(['transaction_id' => $transactionId])->one();
        if (empty($TransactionRecord)) {
            $model->transaction_id = $transactionId;
            $model->receive_type = $info->PaymentTransactionDetails->PaymentInfo->GrossAmount->value > 0 ? 1 : 2;
            $model->receiver_business = $info->PaymentTransactionDetails->ReceiverInfo->Business;
            $model->receiver_email = $info->PaymentTransactionDetails->ReceiverInfo->Receiver;
            $model->receiver_id = $info->PaymentTransactionDetails->ReceiverInfo->ReceiverID;
            $model->payer_id = $info->PaymentTransactionDetails->PayerInfo->PayerID;
            $model->payer_name = $info->PaymentTransactionDetails->PayerInfo->PayerName->FirstName;
            $model->payer_email = $info->PaymentTransactionDetails->PayerInfo->Payer;
            $model->payer_status = $info->PaymentTransactionDetails->PayerInfo->PayerStatus;
            $model->transaction_type = $info->PaymentTransactionDetails->PaymentInfo->TransactionType;
            $model->payment_type = $info->PaymentTransactionDetails->PaymentInfo->PaymentType;
            $model->order_time = date("Y-m-d H:i:s", strtotime($info->PaymentTransactionDetails->PaymentInfo->PaymentDate));
            $model->amt = $info->PaymentTransactionDetails->PaymentInfo->GrossAmount->value;
            $model->tax_amt = isset($info->PaymentTransactionDetails->PaymentInfo->TaxAmount->value) ? $info->PaymentTransactionDetails->PaymentInfo->TaxAmount->value : 0;
            $model->fee_amt = isset($info->PaymentTransactionDetails->PaymentInfo->FeeAmount->value) ? $info->PaymentTransactionDetails->PaymentInfo->FeeAmount->value : 0;
            $model->currency = $info->PaymentTransactionDetails->PaymentInfo->GrossAmount->currencyID;
            $model->payment_status = $info->PaymentTransactionDetails->PaymentInfo->PaymentStatus;
            $model->status = 1;
            
            if (!$model->save()) {
                $bool = TRUE;
                $msg = VHelper::errorToString($model->getErrors());
            }
        }

        return ['bool' => $bool, 'info' => $msg];
    }

}
