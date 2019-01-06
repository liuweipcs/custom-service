<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/18 0018
 * Time: 下午 4:56
 */

namespace app\modules\mails\models;


class EbayCancellationsResponse extends MailsModel
{
    public static function tableName()
    {
        return '{{%ebay_cancellations_response}}';
    }

    public function rules()
    {
        return [
            [['cancel_id','type','account_id'],'required'],
            ['type','in','range'=>[1,2]],
            ['shipment_date','checkShipmentDate'],
            ['tracking_number','string','max'=>100],
            [['cancel_id','status','account_id'],'safe'],
            ['explain','string'],
            ['lock_status','default','value'=>0],
            ['lock_time','safe'],
            ['error','default','value'=>'']
        ];
    }

    public function checkShipmentDate($attribute)
    {
        if($this->$attribute != null)
        {
            $this->$attribute = str_replace('/','-',$this->$attribute);
            if(!preg_match('/\d{4}[-]\d{2}[-]\d{2} \d{2}[:]\d{2}[:]\d{2}/',$this->$attribute))
                $this->addError($attribute,'shipment_date格式错误。');
        }
    }
}