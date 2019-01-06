<?php

use yii\helpers\Url;

$this->registerJsFile(Url::base() . '/js/multiselect.js');
?>
<div class="popup-wrapper">
    <form action="<?php echo Url::toRoute(['/aftersales/domesticreturngoods/relation',
    ]); ?>" method="post" role="form" class="form-horizontal">
        <div class="popup-body">
            <input type="hidden" name="id" value="<?php echo $id;?>">
            <input type="hidden" name="platform" value="<?php echo $platform;?>">
            <div class="row">
                <div class="col-sm-14">
                    <div class="panel panel-default">
                        <div class="panel-body" border="1">
                                        <table style="width: 100%">
                                            <tr>
                                                <td>
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <label for="paypal_email"
                                                                   class="col-sm-3 control-label">收款方式</label>
                                                            <input name="receipt_type" id="receipt_type_radio1" class="receipt_type_radio1"
                                                                   value="1" type="radio" checked="checked" />
                                                            <label for="receipt_type_radio1">paypal收款</label>
                                                            <input name="receipt_type" id="receipt_type_radio2"
                                                                   class="receipt_type_radio2" value="2" type="radio"/>
                                                            <label for="receipt_type_radio2">线下收款</label>
                                                            <input name="receipt_type" id="receipt_type_radio3"
                                                                   class="receipt_type_radio3" value="3" type="radio"/>
                                                            <label for="receipt_type_radio3">关联补款单</label>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="col-lg-12 receipt_type_1" style="display:none;">
                                                        <div class="panel panel-default">

                                                            <div class="panel-body">
                                                                <div class="row ">
                                                                    <div class="col-sm-6">
                                                                        <div class="form-group">
                                                                            <label for="paypal_email"
                                                                                   class="col-sm-3 control-label">paypal帐号</label>
                                                                            <div class="col-sm-9">
                                                                                <?php
                                                                                if (!empty($paypallist)) {
                                                                                    echo \kartik\select2\Select2::widget([
                                                                                        'id' => 'paypal_account_id',
                                                                                        'name' => 'paypal_account_id',
                                                                                        'data' => $paypallist,
                                                                                        'value' => ""
                                                                                    ]);
                                                                                }
                                                                                ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6">
                                                                        <div class="form-group">
                                                                            <label for="warehouse_id"
                                                                                   class="col-sm-3 control-label required">paypal交易号
                                                                            </label>
                                                                            <div class="col-sm-9">
                                                                                <div class="col-sm-6">
                                                                                    <input type="text"
                                                                                           name="transaction_id_2"
                                                                                           class="form-control"
                                                                                           value="">
                                                                                </div>
                                                                                <div class="col-sm-6">
                                                                                    <button id="search_payapl_record"
                                                                                            class="btn btn-default"
                                                                                            type="button">搜索
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div id="paypal_table">
                                                                        <!--                                                    记录-->
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-12 receipt_type_2" style="display:none;">
                                                        <div class="panel panel-default">
                                                            <div class="panel-body">
                                                                <div class="row ">
                                                                    <div class="col-sm-12">
                                                                        <label for="receipt_currency_"
                                                                               class="col-sm-3 control-label">收款币种/金额</label>
                                                                        <div class="col-sm-9" style="margin-left:-23px">
                                                                            <div class="col-sm-2">
                                                                                <select class="form-control"
                                                                                        name="receipt_currency" id="">
                                                                                    <?php foreach ($currencys as $currency):
                                                                                        if (isset($afterSaleReceipt) && $currency == $afterSaleReceipt->receipt_currency) {
                                                                                            echo '<option selected="selected" value="' . $currency . '">' . $currency . '</option>';
                                                                                        } else
                                                                                            echo '<option value="' . $currency . '">' . $currency . '</option>';
                                                                                    endforeach;
                                                                                    ?>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-sm-3">
                                                                                <input class="form-control " type="text"
                                                                                       name="receipt_money" onkeyup="if (isNaN(value))
                                        execCommand('undo')" onafterpaste="if(isNaN(value))execCommand('undo')"
                                                                                       value="<?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                                                                           echo $afterSaleReceipt->receipt_money;
                                                                                       } ?>"
                                                                                       placeholder="请输入补收款金额">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-12" style="margin-top: 8px;">
                                                                        <div class="form-group">
                                                                            <label
                                                                                class="col-sm-3 control-label">交易流水号</label>
                                                                            <div class="col-sm-2">
                                                                                <input class="form-control" type="text"
                                                                                       name="transaction_id"
                                                                                       value="<?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                                                                           echo $afterSaleReceipt->transaction_id;
                                                                                       } ?>"
                                                                                       placeholder="请输入交易流水号">
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-sm-12" style="margin-top: 8px;">
                                                                        <div class="form-group">
                                                                            <label
                                                                                class="col-sm-3 control-label">收款银行</label>
                                                                            <div class="col-sm-2">
                                                                                <select class="form-control"
                                                                                        name="receipt_bank" id="">

                                                                                    <?php foreach ($receipt_banks as $k=> $receipt_bank):
                                                                                        if (isset($afterSaleReceipt) && trim($receipt_bank) == trim($afterSaleReceipt->receipt_bank)) {
                                                                                            echo '<option selected="selected" value="' . $k . '">' . $receipt_bank . '</option>';
                                                                                        } else
                                                                                            echo '<option value="' . $k . '">' . $receipt_bank . '</option>';
                                                                                    endforeach;
                                                                                    ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-12 receipt_type_3" style="display:none;">
                                                        <div class="panel panel-default">

                                                            <div class="panel-body">
                                                                <div class="row ">
                                                                    <div class="col-sm-6">
                                                                        <div class="form-group">
                                                                            <label for="paypal_email"
                                                                                   class="col-sm-3 control-label">平台订单号</label>
                                                                            <div class="col-sm-9">
                                                                                <input type="text"
                                                                                       name="vc_platform_order_id"
                                                                                       class="form-control"
                                                                                       value="">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6">
                                                                        <div class="form-group">
                                                                            <label for="warehouse_id"
                                                                                   class="col-sm-3 control-label required">订单号
                                                                            </label>
                                                                            <div class="col-sm-9">
                                                                                <div class="col-sm-6">
                                                                                    <input type="text"
                                                                                           name="vc_order_id"
                                                                                           class="form-control"
                                                                                           value="">
                                                                                </div>
                                                                                <div class="col-sm-6">
                                                                                    <button id="search_payapl_record_ol"
                                                                                            class="btn btn-default"
                                                                                            type="button">搜索
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div id="paypal_table_vc">
                                                                        <!--                                                    记录-->
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </td>
                                            </tr>
                                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="popup-footer">

            <button class="btn btn-primary ajax-submit"
                    type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
            <button class="btn btn-default close-button" type="button"><?php echo Yii::t('system', 'Close'); ?></button>
        </div>
    </form>
</div>
<script type="text/javascript">
    $(document).ready(function ($) {
        //默认显示paypal付款
        var receipt_type = $("input[name='receipt_type']:checked").val();
        if (receipt_type == 2) {
            $(".receipt_type_2").show();
            $(".receipt_type_1").hide();
            $(".receipt_type_3").hide();
        } else if (receipt_type == 1) {
            $(".receipt_type_2").hide();
            $(".receipt_type_1").show();
            $(".receipt_type_3").hide();
        } else if (receipt_type == 3) {
            $(".receipt_type_2").hide();
            $(".receipt_type_3").show();
            $(".receipt_type_1").hide();
        }else{
            $(".receipt_type_radio1").attr('checked', true);//默认paypal
            $(".receipt_type_1").show();
            $(".receipt_type_2").hide();
            $(".receipt_type_3").hide();
         }

        if ($("select[name=receipt_reason_type]").val() == 4) {
            $("textarea[name=receipt_reason_remark]").show()
        } else {
            $("textarea[name=receipt_reason_remark]").hide()
        }

    });

    $("input[name=receipt_type]").click(function () {
        if($(this).val()==1){
            //线下收款
            $(".receipt_type_3").hide();
            $(".receipt_type_2").hide();
            $(".receipt_type_1").show();
        }else  if($(this).val()==2){
            $(".receipt_type_3").hide();
            $(".receipt_type_1").hide();
            $(".receipt_type_2").show();
        }else{
            $(".receipt_type_3").show();
            $(".receipt_type_1").hide();
            $(".receipt_type_2").hide();
        }
    });

    //原因类型选择
    $("select[name=receipt_reason_type]").on('click', function () {
        var receipt_reason_type = $("select[name=receipt_reason_type]").val();
        if (receipt_reason_type == 4) {
            $("textarea[name=receipt_reason_remark]").show();
        } else {
            $("textarea[name=receipt_reason_remark]").hide();
        }
    });
    //查询关联补款单
    $("#search_payapl_record_ol").on('click',function () {
        $("#paypal_table_vc").html('');
        var platform_order_id = $("input[name=vc_platform_order_id]").val();
        var order_id = $("input[name=vc_order_id]").val();
        var url = '<?php echo Url::toRoute(['/aftersales/domesticreturngoods/aliexpresslist']);?>';
        $.get(url, {"platform_order_id": platform_order_id, "order_id": order_id}, function (data) {
            var html = '';
            var data = eval('(' + data + ')');
            console.log(data);
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }else {
                html += '<div class="panel-body">\n' +
                    '                    <table id="return-product" class="table">\n' +
                    '                    <thead>\n' +
                    '                    <tr>\n' +
                    '                    <th style="height:74px;line-height:37px;">平台订单号</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">订单号</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">买家ID</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">订单金额</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">付款时间</th>\n' +
                    '                    </tr>\n' +
                    '                    </thead>\n' +
                    '                    <tbody>';
                html += '<td ><p>' + data.data.platform_order_id + '</p></td>';
                html += '<td ><p>' + data.data.order_id + '</p></td>';
                html += '<td >' + data.data.buyer_id + '</td>';
                html += '<td >' + data.data.total_price + data.data.currency+'</td>';
                html += '<td >' + data.data.paytime + '</td>';
                html += '</tbody>\n' + '</table>';
                html += '<input type="hidden" name="vc_totprice" value="' + data.data.total_price + '"/>';
                html += '<input type="hidden" name="vc_currency" value="' + data.data.currency + '"/>';
                html += '<input type="hidden" name="vc_order_id" value="' + data.data.order_id + '"/>';
                html += '</div>';
                $("#paypal_table_vc").html(html);
            }
    });
    });
    //查询
    $("#search_payapl_record").on('click', function () {
        $("#paypal_table").html('');
        var paypal_id = $("input[name=transaction_id_2]").val();
        var paypal_account_id = $("#paypal_account_id").val(); // 订单account_id(erp系统account_id)
        var url = '<?php echo Url::toRoute(['/aftersales/transactions/getinfobyid_']);?>';
        $.get(url, {"transaction_id": paypal_id, "account_id": paypal_account_id}, function (data) {
            var html = '';
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }
            else {
                var paypal_info = data.data.transactionInfo;
                var Transactionrecord = data.data.Transactionrecord;
                var TransactionAddress = data.data.TransactionAddress;
                var html = '';
                if (Transactionrecord.PaymentTransactionDetails.PaymentInfo.TaxAmount == null) {
                    TaxAmount = Transactionrecord.PaymentTransactionDetails.PaymentInfo.TaxAmount;
                } else {
                    TaxAmount = 0;
                }
                if (Transactionrecord.PaymentTransactionDetails.PaymentInfo.FeeAmount == null) {
                    FeeAmount = 0;
                } else {
                    FeeAmount = Transactionrecord.PaymentTransactionDetails.PaymentInfo.FeeAmount;
                }
                html += '<div class="panel-body">\n' +
                    '                    <table id="return-product" class="table">\n' +
                    '                    <thead>\n' +
                    '                    <tr>\n' +
                    '                    <th style="height:74px;line-height:37px;">paypal交易号/交易时间</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">金额/状态</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">佣金</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">货币类型</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">付款帐号</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">收款帐号</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">收款状态</th>\n' +
                    '                    </tr>\n' +
                    '                    </thead>\n' +
                    '                    <tbody>';
                html += '<td ><p>' + paypal_info.transaction_id + '</p><p>' + paypal_info.order_time + '</p></td>';
                html += '<td ><p>' + paypal_info.amt + '</p><p>' + paypal_info.payment_status + '</p></td>';
                html += '<td >' + paypal_info.fee_amt + '</td>';
                html += '<td >' + paypal_info.currency + '</td>';
                html += '<td >' + paypal_info.payer_email + '</td>';
                html += '<td >' + paypal_info.receiver_email + '</td>';
                html += '<td >' + Transactionrecord.PaymentTransactionDetails.PayerInfo.PayerStatus + '</td>';
                html += '</tbody>\n' + '</table>';
                html += '<input type="hidden" name="record[transaction_id]" value="' + paypal_info.transaction_id + '"/>';
                html += '<input type="hidden" name="record[receive_type]" value="' + Transactionrecord.PaymentTransactionDetails.PaymentInfo.GrossAmount.value + '"/>';
                html += '<input type="hidden" name="record[receiver_business]" value="' + Transactionrecord.PaymentTransactionDetails.ReceiverInfo.Business + '"/>';
                html += '<input type="hidden" name="record[receiver_email]" value="' + Transactionrecord.PaymentTransactionDetails.ReceiverInfo.Receiver + '"/>';
                html += '<input type="hidden" name="record[receiver_id]" value="' + Transactionrecord.PaymentTransactionDetails.ReceiverInfo.ReceiverID + '"/>';
                html += '<input type="hidden" name="record[payer_id]" value="' + Transactionrecord.PaymentTransactionDetails.PayerInfo.PayerID + '"/>';
                html += '<input type="hidden" name="record[payer_name]" value="' + Transactionrecord.PaymentTransactionDetails.PayerInfo.PayerName.FirstName + '"/>';
                html += '<input type="hidden" name="record[payer_email]" value="' + Transactionrecord.PaymentTransactionDetails.PayerInfo.Payer + '"/>';
                html += '<input type="hidden" name="record[payer_status]" value="' + Transactionrecord.PaymentTransactionDetails.PayerInfo.PayerStatus + '"/>';
                html += '<input type="hidden" name="record[transaction_type]" value="' + Transactionrecord.PaymentTransactionDetails.PaymentInfo.TransactionType + '"/>';
                html += '<input type="hidden" name="record[payment_type]" value="' + Transactionrecord.PaymentTransactionDetails.PaymentInfo.PaymentType + '"/>';
                html += '<input type="hidden" name="record[order_time]" value="' + Transactionrecord.PaymentTransactionDetails.PaymentInfo.PaymentDate + '"/>';
                html += '<input type="hidden" name="record[amt]" value="' + Transactionrecord.PaymentTransactionDetails.PaymentInfo.GrossAmount.value + '"/>';
                html += '<input type="hidden" name="record[tax_amt]" value="' + TaxAmount + '"/>';
                html += '<input type="hidden" name="record[fee_amt]" value="' + FeeAmount + '"/>';
                html += '<input type="hidden" name="record[currency]" value="' + Transactionrecord.PaymentTransactionDetails.PaymentInfo.GrossAmount.currencyID + '"/>';
                html += '<input type="hidden" name="record[payment_status]" value="' + Transactionrecord.PaymentTransactionDetails.PaymentInfo.PaymentStatus + '"/>';
                html += '<input type="hidden" name="record[status]" value="' + 1 + '"/>';
                //地址
                html += '<input type="hidden" name="address[transaction_id]" value="' + paypal_info.transaction_id + '"/>';
                html += '<input type="hidden" name="address[name]" value="' + TransactionAddress.Name + '"/>';
                html += '<input type="hidden" name="address[street1]" value="' + TransactionAddress.Street1 + '"/>';
                html += '<input type="hidden" name="address[street2]" value="' + TransactionAddress.Street2 + '"/>';
                html += '<input type="hidden" name="address[city_name]" value="' + TransactionAddress.CityName + '"/>';
                html += '<input type="hidden" name="address[state_or_province]" value="' + TransactionAddress.StateOrProvince + '"/>';
                html += '<input type="hidden" name="address[country]" value="' + TransactionAddress.Country + '"/>';
                html += '<input type="hidden" name="address[country_name]" value="' + TransactionAddress.CountryName + '"/>';
                html += '<input type="hidden" name="address[phone]" value="' + TransactionAddress.Phone + '"/>';
                html += '<input type="hidden" name="address[postal_code]" value="' + TransactionAddress.PostalCode + '"/>';
                html += '</div>';
                $("#paypal_table").html(html);
                $("#paypal_email").val(paypal_info.receiver_email);
                $("#order_amount").val(paypal_info.amt)
                $("#currency").val(paypal_info.currency);
            }

        }, 'json');
    })
</script>