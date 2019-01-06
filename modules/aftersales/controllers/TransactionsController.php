<?php

namespace app\modules\aftersales\controllers;

use app\common\VHelper;
use app\modules\systems\models\RefundAccount;
use app\modules\orders\models\Transactionrecord;
use app\modules\systems\models\TransactionAddress;
use Yii;
use yii\helpers\Json;
use app\components\Controller;

/**
 * RefundreturnreasonController implements the CRUD actions for RefundReturnReason model.
 */
class TransactionsController extends Controller
{
    public function actionGetinfobyid()
    {
        $bool = FALSE;
        //payPal收款账号
        $payPalAccountId = $this->request->getQueryParam('account_id');
        // 交易id
        $transactionId = $this->request->getQueryParam('transaction_id');

        if (empty($payPalAccountId) || empty($transactionId)) {
            $this->_showMessage('payPal账号/交易号 为必填数据', false);
        }

        $transactionInfo = Transactionrecord::getTransactionInfo($transactionId);
        // 如果tranasction_info不存在，直接使用接口获取数据
        if (empty($transactionInfo)) {
            // 根据帐号获取退款帐号信息
            $account_info = RefundAccount::findOne($payPalAccountId);
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

                $response   = VHelper::ebTransactionDeail($params);
                $payPalInfo = $response[0];
                if ($payPalInfo) {
                    //开启事物
                    $transaction = Yii::$app->db->beginTransaction();
                    //添加交易记录信息
                    $transactionInserResult = Transactionrecord::addTranctionRecord($payPalInfo);
                    if ($transactionInserResult['bool']) {
                        $bool    = TRUE;
                        $message = $transactionInserResult['info'];
                    }

                    //添加交易地址信息
                    if (!$bool) {
                        $addressResult = TransactionAddress::insertAddressData($payPalInfo->PaymentTransactionDetails->PayerInfo->Address, $transactionId);
                        if (!$addressResult[0]) {
                            $bool    = TRUE;
                            $message = $addressResult[1];
                        }
                    }

                    //提交事物保存数据
                    if (!$bool) {
                        $transaction->commit();
                        $transactionInfo = Transactionrecord::getTransactionInfo($transactionId);
                    } else {
                        $transaction->rollBack();
                    }
                } else {
                    $this->_showMessage($response[1], false);
                }
            }
        }
        $transactionInfo = json_decode(Json::encode($transactionInfo), true);
        $this->_showMessage('', true, null, false, $transactionInfo, null, false);
    }

    /**
     * 收款单获取paypal信息
     */
    public function actionGetinfobyid_()
    {
        $bool                    = FALSE;
        $payPalInfo              = new \stdClass();
        $transactionInfo_address = new \stdClass();
        //payPal收款账号
        $payPalAccountId = $this->request->getQueryParam('account_id');
        // 交易id
        $transactionId = $this->request->getQueryParam('transaction_id');
        if (empty($payPalAccountId) || empty($transactionId)) {
            $this->_showMessage('payPal账号/交易号 为必填数据', false);
        }
        $transactionInfo = Transactionrecord::getTransactionInfo($transactionId);
        // 如果tranasction_info不存在，直接使用接口获取数据
        // 根据帐号获取退款帐号信息
        $account_info = RefundAccount::findOne($payPalAccountId);
        if (empty($account_info)) {
            $bool = TRUE;
            $this->_showMessage('payPal账号获取失败!', false);
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
            if ($response[0] == false) {
                $this->_showMessage('查询paypal信息为空!', false);
            }
            $payPalInfo = $response[0];
        }
        if (empty($transactionInfo)) {
            if (!empty($payPalInfo)) {
                //transaction_id order_time payment_status amt fee_amt currency payer_email receiver_email
                $transactionInfo['transaction_id'] = $transactionId;
                $transactionInfo['receiver_email'] = $payPalInfo->PaymentTransactionDetails->ReceiverInfo->Receiver;
                $transactionInfo['payer_email']    = $payPalInfo->PaymentTransactionDetails->PayerInfo->Payer;
                $transactionInfo['order_time']     = date("Y-m-d H:i:s", strtotime($payPalInfo->PaymentTransactionDetails->PaymentInfo->PaymentDate));
                $transactionInfo['amt']            = $payPalInfo->PaymentTransactionDetails->PaymentInfo->GrossAmount->value;
                $transactionInfo['fee_amt']        = isset($info->PaymentTransactionDetails->PaymentInfo->FeeAmount->value) ? $payPalInfo->PaymentTransactionDetails->PaymentInfo->FeeAmount->value : 0;
                $transactionInfo['currency']       = $payPalInfo->PaymentTransactionDetails->PaymentInfo->GrossAmount->currencyID;
                $transactionInfo['payment_status'] = $payPalInfo->PaymentTransactionDetails->PaymentInfo->PaymentStatus;
                $transactionInfo_address           = $payPalInfo->PaymentTransactionDetails->PayerInfo->Address;
            } else {
                $this->_showMessage('查询信息为空!', false);
            }
        }
        $data['transactionInfo']    = $transactionInfo;
        $data['Transactionrecord']  = $payPalInfo;
        $data['TransactionAddress'] = $transactionInfo_address;
        $return_data                = json_decode(Json::encode($data), true);
        $this->_showMessage('', true, null, false, $return_data, null, false);
    }

}
