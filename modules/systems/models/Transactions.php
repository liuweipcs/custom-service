<?php

	namespace app\modules\systems\models;
	use yii\db\ActiveRecord;
	use Yii;
	/**
	* 
	*/
	class Transactions extends ActiveRecord
	{
		
		public static function tableName(){

			return "{{%eb_paypal_transaction_record}}";
		}

		public function rules(){

			return[
				[['transaction_id','receive_type','receiver_business','receiver_email','receiver_id','payer_id','payer_name','payer_email','payer_status','transaction_type','payment_type','order_time','amt','tax_amt','fee_amt','currency','payment_status','note','modify_time','status'],'safe'],
			];
		}

		public function attributeLabels(){
			return [
				'transaction_id' => '交易ID',
				'receive_type' => '接收类型',
				'receiver_business' =>'接收业务',
				'receiver_email' =>'接收邮箱',
				'receiver_id' =>'接收ID',
				'payer_id' =>'付款人ID',
				'payer_name' =>'付款人姓名',
				'payer_email'=>'付款人邮箱',
				'payer_status' =>'付款人状态',
				'transaction_type' =>'交易类型',
				'payment_type' => '付款类型',
				'order_time' =>'付款时间',
				'amt'=> '替代最低税',
                'tax_amt' => '',
                'fee_amt' => '',
                'currency' => '',
                'payment_status' => '付款状态'

			];
		}

		/*@desc 交易信息存入数据库*/
		public static function insertTransactionData($result,$detail_info)
		{
            $reciver_type = $result->PaymentInfo->GrossAmount->value > 0 ? 1 : 2;
            $data = [
                 'transaction_id' => $result->PaymentInfo->TransactionID,
                 'receive_type' => $reciver_type,
                 'receiver_business' => $result->ReceiverInfo->Business,
                 'receiver_email' => $result->ReceiverInfo->Receiver,
                 'receiver_id' => $result->ReceiverInfo->ReceiverID,
                 'payer_id' => $result->PayerInfo->PayerID,
                 'payer_name' => $result->PayerInfo->PayerName->FirstName,
                 'payer_email' => $result->PayerInfo->Payer,
                 'payer_status' => $result->PayerInfo->PayerStatus,
                 'transaction_type' => $result->PaymentInfo->TransactionType,
                 'payment_type' => $result->PaymentInfo->PaymentType,
                 'order_time' => date("Y-m-d H:i:s",strtotime($result->PaymentInfo->PaymentDate)),
                 'amt' => $detail_info->GrossAmount->value,
				'tax_amt' => isset($result->PaymentInfo->TaxAmount->value)?$result->PaymentInfo->TaxAmount->value:0,
                 'fee_amt' => $detail_info->FeeAmount->value,
                 'currency' => $result->PaymentInfo->GrossAmount->currencyID,
                 'payment_status' => $result->PaymentInfo->PaymentStatus,
            ];
            
            //存取数据
            $model = self::findOne(['transaction_id'=>$result->PaymentInfo->TransactionID]);
            if(empty($model))
            $model = new self();
            
			if ($model->load($data, '') && $model->save(false)) {
               return [true , 'save success'];
			}
			
			return [false, current(current($model->getErrors()))];
		}

        /*@desc 只有交易详情时候，交易信息存入数据库（客户系统在获取交易信息时使用）*/
        public static function insertDataWithoutList($result)
        {
            $reciver_type = $result->PaymentInfo->GrossAmount->value > 0 ? 1 : 2;
            $gross_amount = isset($result->GrossAmout->value) ? $result->GrossAmout->value : 0;
            $fee_amt = isset($result->FeeAmount->value) ? $result->FeeAmount->value : 0;
            if($reciver_type == 1)
                $fee_amt = $fee_amt/-1;
            $data = [
                'transaction_id' => $result->PaymentInfo->TransactionID,
                'receive_type' => $reciver_type,
                'receiver_business' => $result->ReceiverInfo->Business,
                'receiver_email' => $result->ReceiverInfo->Receiver,
                'receiver_id' => $result->ReceiverInfo->ReceiverID,
                'payer_id' => $result->PayerInfo->PayerID,
                'payer_name' => $result->PayerInfo->PayerName->FirstName,
                'payer_email' => $result->PayerInfo->Payer,
                'payer_status' => $result->PayerInfo->PayerStatus,
                'transaction_type' => $result->PaymentInfo->TransactionType,
                'payment_type' => $result->PaymentInfo->PaymentType,
                'order_time' => date("Y-m-d H:i:s",strtotime($result->PaymentInfo->PaymentDate)),
                'amt' => $gross_amount,
                'tax_amt' => isset($result->PaymentInfo->TaxAmount->value)?$result->PaymentInfo->TaxAmount->value:0,
                'fee_amt' => $fee_amt,
                'currency' => $result->PaymentInfo->GrossAmount->currencyID,
                'payment_status' => $result->PaymentInfo->PaymentStatus,
            ];

            //存取数据
            $model = new self();
            if ($model->load($data, '') && $model->save(false)) {
                return [true , 'save success'];
            }

            return [false, current(current($model->getErrors()))];
        }

		/*@desc 异常交易信息存入数据库*/
		public static function insertPartTransactionData($detail_info,$receiver_business)
		{
            $receive_type = $detail_info->GrossAmount->value > 0 ? 1 : 2;
            $data = [
				'transaction_id' => $detail_info->TransactionID,
                'receive_type' => $receive_type,
                'payer_email' => isset($detail_info->Payer) ? $detail_info->Payer : '',
                'receiver_email'=>$receiver_business,
                'order_time' => date("Y-m-d H:i:s",strtotime($detail_info->Timestamp)),
				'amt' => $detail_info->GrossAmount->value,
				'fee_amt' => $detail_info->FeeAmount->value,
				'currency' => $detail_info->GrossAmount->currencyID,
				'payment_status' => $detail_info->Status,
				'status' => 0,
			];

			//存取数据
            $model = self::findOne(['transaction_id'=>$detail_info->TransactionID]);
            if(empty($model))
			    $model = new self();
			if ($model->load($data, '') && $model->save(false)) {
				return [true , 'save success'];
			}

			return [false, current(current($model->getErrors()))];
		}

		/*@desc 查询该交易号是否已经存入数据库*/
		public static function isExisted($transaction_id)
		{
			$info = self::find()->where(['transaction_id'=>$transaction_id])->one();

			return $info ? true : false;
		}

/*		public static function insertPartTransactionData($result)
		{
			$reciver_type = $result->GrossAmount->value > 0 ? 1 : 2;
			$data = [
				'transaction_id' => $result->TransactionID,
				'receive_type' => $reciver_type,
				'fee_amt' => $result->FeeAmount->value,
				'currency' => $result->GrossAmount->currencyID,
				'payment_status' => $result->Type,
			];

			//存取数据
			$model = new self();
			$model->setAttributes($data);
			if ($model->save(false)) {
				return [true , 'save success'];
			}

			return [false, current(current($model->getErrors()))];
		}	*/


	}