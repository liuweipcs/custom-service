<?php 

namespace app\modules\orders\models;

use app\common\VHelper;
use app\components\Model;
use Yii;

class PaypalInvoiceRecord extends Model {

    public static function tableName() {
        return "{{%paypal_invoice_record}}";
    }

    public function rules() {
        return [
            [['invoice_id', 'invoice_num', 'invoice_template_id', 'status', 'invoice_date', 'merchant_email', 'payer_email', 'currency', 'total_amount', 'create_time', 'create_by', 'modify_time', 'modify_by'], 'required'],
            [['merchant_email', 'payer_email'], 'email'],
        ];
    }

    public function attributeLabels() {
    	return [
    	    'invoice_id' => '开票ID',
    	    'invoice_num' => '开票编号',
    	    'invoice_template_id' => '开票模版ID',
    	    'status' => '开票状态',
    	    'invoice_date' => '开票生成时间',
    	    'merchant_email' => '商家邮箱',
    	    'payer_email' => '买家邮箱',
    	    'currency' => '货币',
    	    'total_amount' => '收款总金额',
    	    'create_time' => '创建时间',
    	    'create_by' => '创建人',
    	    'modify_time' => '修改时间',
    	    'modify_by' => '修改人',
            'note' => '留言',
    	    'order_id' => '订单号',
    	];
    }

    //通过订单查询记录
    public static function getIvoiceData($orderId, $platform_code = 'EB'){
            $order_id = $orderId;
            $InvoiceRecordRes = PaypalInvoiceRecord::find()->andWhere(['order_id' => $order_id,'status'=>'SENT'])->andWhere(['platform_code' => $platform_code])->asArray()->one();

            return $InvoiceRecordRes;
    }

    //添加记录
    public static function addInvoiceRecord($invoice, $orderId, $platform_code = 'EB') {
    	$bool = false;
    	$msg = "添加invoice成功";
    	$model = new PaypalInvoiceRecord();
        $invoice_id = $invoice->id;
    	$order_id = $orderId;
    	/*if(!empty($order_id)){
            $InvoiceRecordRes = PaypalInvoiceRecord::find()->where(['order_id' => $order_id,'status'=>'SENT'])->andWhere(['platform_code' => $platform_code])->asArray()->one();
            if($InvoiceRecordRes) {
                $bool = TRUE;
                $msg = '已经发送过收款，请不要重复发送！';
                return ['bool' => $bool, 'info' => $msg];
            }
        }*/
    	$InvoiceRecord = PaypalInvoiceRecord::find()->where(['invoice_id' => $invoice_id])->asArray()->one();
    	if(!$InvoiceRecord) {
            $model->invoice_id = $invoice->id;
    		$model->invoice_num = $invoice->number;
    		$model->invoice_template_id = $invoice->template_id;
    		$model->status = $invoice->status;
    		$model->invoice_date = $invoice->invoice_date;
    		$model->merchant_email = $invoice->merchant_info->email;
    		$model->payer_email = $invoice->billing_info[0]->email;
    		$model->note = $invoice->note;
    		$model->currency = $invoice->total_amount->currency;
    		$model->total_amount = $invoice->total_amount->value;
    		$model->create_by = Yii::$app->user->id;
    		$model->create_time = date('Y-m-d H:i:s',time());
    		$model->modify_by = Yii::$app->user->id;
    		$model->modify_time = date('Y-m-d H:i:s',time());
    		$model->order_id = $order_id;
    		$model->platform_code = $platform_code;
    		if (!$model->save()) {
    		    $bool = TRUE;
    		    $msg = VHelper::errorToString($model->getErrors());
    		}
    	}

    	return ['bool' => $bool, 'info' => $msg];
    }

    //更新记录
    public static function updateInvoiceRecord($invoiceId,$status) {    
        $bool = false;
        $msg = "更新invoice成功";
        $InvoiceRecord = PaypalInvoiceRecord::find()->where(['invoice_id' => $invoiceId])->one();
        if($InvoiceRecord) {
            $InvoiceRecord->invoice_id = $invoiceId;
            //$InvoiceRecord->status = 'SENT';
            $InvoiceRecord->status = $status;

            if (!$InvoiceRecord->save()) {
                $bool = TRUE;
                $msg = VHelper::errorToString($InvoiceRecord->getErrors());
            }
        }

        return ['bool' => $bool, 'info' => $msg];
    }    
}