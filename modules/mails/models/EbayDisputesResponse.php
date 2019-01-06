<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/5 0005
 * Time: 下午 7:09
 */

namespace app\modules\mails\models;


class EbayDisputesResponse extends MailsModel
{
    public static $disputeActivityMap = [1=>'CameToAgreementNeedFVFCredit',2=>'MutualAgreementOrNoBuyerResponse',3=>'SellerAddInformation',4=>'SellerComment',5=>'SellerCompletedTransaction',6=>'SellerEndCommunication',7=>'SellerOffersRefund',8=>'SellerPaymentNotReceived',9=>'SellerShippedItem'];

    public static function tableName()
    {
        return '{{%ebay_disputes_response}}';
    }
}