<?php

use yii\helpers\Url;
use app\modules\orders\models\PaypalInvoiceRecord;
use app\modules\orders\models\Tansaction;
use app\modules\accounts\models\Platform;
?>
<style>
    .stepInfo {position: relative;margin: 40px auto 0 auto;width: auto;}
    ul, ol {list-style: none;margin-bottom:-3px;}
    .stepInfo li {width: auto;height: 0.15em;background: #bbb;}
    .stepIco {background: #bbb none repeat;border-radius: 1em;color: #fff;float: left;height: 1.4em;line-height: 1.5em;margin-left: 60px;margin-top: -10px;
              padding: 0.03em;text-align: center;width: 1.4em;z-index: 999;}
    .stepText {color: #666;margin-top: 0.2em;width: 6em;text-align: center;margin-left: -2.2em;}
    .step{background: rgb(79, 182, 64) none repeat scroll 0px 0px;}
    .step_text{color: rgb(79, 182, 64);}
    .border{border:none}
    .button{
        margin-top:5px;
        border: 2px solid #1809f1;
        padding: 7px;
        width: 74px;
        border-radius: 13%;
        cursor: pointer;
        margin-left: 1px;
        float: left;
    }
</style>
<div class="panel panel-primary border">
    <div class="container" style="height:42px;width:100%;">
        <div>
            <?php
            $cancel = '';
            //ebay未付款订单 并且无纠纷
            if ($info['info']['payment_status'] == 0 && empty($info['info']['dispute'])) {
                $cancel = 1;
            }
            //ebay 与付款订单 并且付款时间小于30天 无纠纷的
            if ($info['info']['payment_status'] == 1) {
                //获取付款时间戳
                $paytime = strtotime($info['info']['paytime']);
                //当前时间戳
                $time = time();
                if ((($time - $paytime) <= 2592000) && empty($info['info']['dispute'])) {
                    $cancel = 1;
                }
            }
            ?>
            <?php if ($platform == "EB") { ?>
                <?php if ($cancel == 1) { ?>
                    <div class="button">
                        <a _width="30%" _height="60%" class="edit-button"
                           href="<?php
            echo Url::toRoute(['/orders/order/canceltransaction',
                'orderid' => $info['info']['order_id'],
                'platform' => $platform, 'account_id' => $info['info']['account_id'],
                'payment_status' => $info['info']['payment_status'],
                'paytime' => $info['info']['paytime'], 'platform_order_id' => $info['info']['platform_order_id'],
            ])
                    ?>">取消订单</a>
                    </div>
                <?php } ?>
            <?php } ?>
            
            <?php if($info['info']['complete_status'] == 40){ ?>
            
            
            <div class="button" style="width:102px">
                <a confirm="确定取恢复永久作废订单？" class="ajax-button"
                   href="<?php
            echo Url::toRoute(['/orders/order/ordertoinitlist',
                'orderid' => $info['info']['order_id'],
                'platform' => $platform, 
            ])
            ?>">恢复永久作废</a>
            </div>
            <?php } ?>

            <?php if ($info['info']['complete_status'] < 19 || $info['info']['complete_status'] == 99) { ?>
                <div class="button">
                    <a _width="30%" _height="60%" class="edit-button"
                       href="<?php
                echo Url::toRoute(['/orders/order/holdorder',
                    'order_id' => $info['info']['order_id'], 'platform' => $platform]);
                ?>">暂时作废</a>
                </div>
                <div class="button">
                    <a _width="30%" _height="60%" class="edit-button"
                       href="<?php echo Url::toRoute(['/orders/order/cancelorder', 'order_id' => $info['info']['order_id'], 'platform' => $platform]); ?>">永久作废</a>
                </div>
            <?php } ?>
            <?php if ($info['info']['complete_status'] == 25) { ?>
                <div class="button" style="width:102px">
                    <a confirm="确定取消暂时作废该订单？" class="ajax-button"
                       href="<?php
            echo Url::toRoute(['/orders/order/cancelholdorder',
                'order_id' => $info['info']['order_id'], 'platform' => $platform]);
                ?>">取消暂时作废</a>

                </div>
            <?php } ?>
            <div class="button" style="width:49px">
                <a _width="50%" _height="80%" class="edit-button"
                   href="<?php echo Url::toRoute(['/orders/order/invoice', 'order_id' => $info['info']['order_id'], 'platform' => $platform]); ?>">发票</a>

            </div>
            <?php if (!in_array($info['info']['order_type'], [2, 5]) && $info['info']['repeat_nums'] == 0) { ?>
                <div class="button" style="width:85px">
                    <a _width="100%" _height="100%" class="edit-button"
                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $info['info']['order_id'], 'platform' => $platform]); ?>">登记退款单</a>
                </div>
            <?php } ?>
            <?php if (!in_array($info['info']['order_type'], [2, 5]) && $info['info']['repeat_nums'] == 0) { ?>
                <div class="button" style="width:85px">
                    <a  _width="100%" _height="100%" class="edit-button"
                        href="<?php
            echo Url::toRoute(['/aftersales/order/add',
                'order_id' => $info['info']['order_id'], 'platform' => $platform]);
                ?>">新建售后单</a>
                </div>
            <?php } ?>


            <?php if ($platform == "EB" || $platform == "CDISCOUNT") { ?>
                <?php
                $invoiceInfo = PaypalInvoiceRecord::getIvoiceData($info['info']['order_id'], $platform);
                $transactionId = Tansaction::getOrderTransactionIdEbayByOrderId($info['info']['order_id'], $platform);
                ?>
                <?php if (!isset($invoiceInfo)) { ?>
                    <div class="button">
                        <a href="javascript:void(0)" class="cancelEbayPaypalInvoice" data-orderid="<?php echo $info['info']['order_id']; ?>" data-invoiceid="<?php echo $invoiceInfo['invoice_id']; ?>" data-invoiceemail="<?php echo $invoiceInfo['merchant_email']; ?>">取消收款</a>
                    </div>
                <?php } elseif(!in_array($info['info']['order_type'], [2, 5]) && $info['info']['repeat_nums'] == 0) { ?>
                    <div class="button" style="width:49px">
                        <a _width="80%" _height="80%" class="edit-button"
                           href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $info['info']['order_id'], 'platform_order_id' => $info['info']['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $platform]); ?>">收款</a>
                    </div>
                <?php }; ?>
            <?php }; ?>

            <?php if (in_array($platform, [Platform::PLATFORM_CODE_EB, Platform::PLATFORM_CODE_CDISCOUNT, Platform::PLATFORM_CODE_WISH, Platform::PLATFORM_CODE_LAZADA, Platform::PLATFORM_CODE_SHOPEE])) { ?>
                <div class="button" style="width:85px">
                    <a _width="80%" _height="80%" class="edit-button"
                       href="<?php echo Url::toRoute(['/aftersales/sales/ebayreceipt', 'order_id' => $info['info']['order_id'], 'platform' => $platform, 'buyer_id' => $info['info']['buyer_id'], 'account_id' => $info['info']['account_id']]); ?>">登记收款单</a>
                </div>
            <?php } ?>
            <div class="button" style="width:85px">
                    <a _width="100%" _height="100%" class="edit-button"
                       href="<?php echo Url::toRoute(['/aftersales/complaint/register', 'order_id' => $info['info']['order_id'], 'platform' => $platform,]); ?>">登记客诉单</a>
                </div>

        </div>

    </div>
</div>
<script src="<?php echo yii\helpers\Url::base(true); ?>/js/currency.js"></script>
<script>
    //取消收款
    $(".cancelEbayPaypalInvoice").on("click", function () {
        var order_id = $(this).attr("data-orderid");
        var invoice_id = $(this).attr("data-invoiceid");
        var invoice_email = $(this).attr("data-invoiceemail");

        $.get("<?php echo Url::toRoute(['/orders/order/cancelebaypaypalinvoice']); ?>", {
            "order_id": order_id, "invoice_id": invoice_id, "invoice_email": invoice_email
        }, function (data) {
            if (!data.bool) {
                layer.msg(data.msg, {icon: 1, time: 3000});
                window.location.reload();
            } else {
                layer.msg(data.msg, {icon: 5});
            }
        }, "json");

        return false;
    });





</script>