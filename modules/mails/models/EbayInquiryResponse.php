<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/19 0019
 * Time: 下午 4:16
 */

namespace app\modules\mails\models;


class EbayInquiryResponse extends MailsModel
{
    public static $escalationReasonMap = [1=>'BUYER_TROUBLE',2=>'BUYER_UNHAPPY',3=>'NO_REFUND',4=>'NOT_RECEIVED',5=>'OTHERS',6=>'SELLER_NORESPONSE',7=>'SHIPPED_ITEM',8=>'TROUBLE_COMMUNICATION'];

    public static function tableName()
    {
        return '{{%ebay_inquiry_response}}';
    }

    public function rules()
    {
        return [
            [['inquiry_id','type'],'required'],
            ['status','default','value'=>0],
            ['status','in','range'=>[0,1]],
            ['content','string'],
            [['shipping_carrier_name','tracking_number'],'default','value'=>''],
            [['shipping_carrier_name','tracking_number'],'string','max'=>100],
            ['shipping_date','checkShippingDate'],
            ['escalation_reason','checkEscalationReason'],
            ['lock_status','default','value'=>0],
            ['lock_time','safe'],
            [['error','refund_source','refund_status'],'default','value'=>'']
        ];
    }

    public function checkEscalationReason($attribute)
    {
        if(empty($this->$attribute))
            $this->$attribute = 0;
        else
        {
            if(is_numeric($this->$attribute))
            {
                if(!isset(self::$escalationReasonMap[$this->$attribute]))
                    $this->addError($attribute,'escalation_reason值错误。');
            }
            else
            {
                $key = array_search($this->$attribute,self::$escalationReasonMap);
                if($key === false)
                    $this->addError($attribute,'escalation_reason值错误。');
                else
                    $this->$attribute = $key;
            }
        }
    }

    public function checkShippingDate($attribute)
    {
        if($this->$attribute != null)
        {
            $this->$attribute = str_replace('/','-',$this->$attribute);
            if(!preg_match('/\d{4}[-]\d{2}[-]\d{2} \d{2}[:]\d{2}[:]\d{2}/',$this->$attribute))
                $this->addError($attribute,'shipment_date格式错误。');
        }
    }
}