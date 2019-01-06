<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/23 0023
 * Time: 上午 9:40
 */

namespace app\modules\mails\models;


class EbayReturnsRequestsResponse extends MailsModel
{
    public static function tableName()
    {
        return '{{%ebay_returns_requests_response}}';
    }

    public function rules()
    {
        return [
            ['return_id','required'],
            ['content','string'],
            [['refund_amount','subtotal_price','ship_cost'],'number','min'=>0,'max'=>'999999.99'],
            ['currency','string','length'=>3],
            ['type','in','range'=>[1,2,3]],
            ['status','in','range'=>[0,1]],
            ['account_id','safe'],
            [['error','refund_status'],'default','value'=>''],
            ['lock_status','default','value'=>0],
            ['lock_time','safe']
        ];
    }
}