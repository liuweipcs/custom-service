<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/6 0006
 * Time: 上午 11:43
 */

namespace app\modules\mails\models;


class EbayAddDispute extends MailsModel
{
    public static $disputeExplanationMap = [1=>'BuyerHasNotResponded',2=>'BuyerNoLongerWantsItem',3=>'BuyerNotClearedToPay',4=>'BuyerNotPaid',5=>'BuyerPaymentNotReceivedOrCleared',6=>'BuyerPurchasingMistake',7=>'BuyerRefusedToPay',8=>'BuyerReturnedItemForRefund',9=>'OtherExplanation',10=>'SellerDoesntShipToCountry',11=>'SellerRanOutOfStock',12=>'ShippingAddressNotConfirmed',13=>'UnableToResolveTerms'];
    public static $disputeReasonMap = [1=>'BuyerHasNotPaid',2=>'TransactionMutuallyCanceled'];

    public static function tableName()
    {
        return '{{%ebay_add_dispute}}';
    }
}