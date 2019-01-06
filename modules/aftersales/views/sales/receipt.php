<?php

use yii\helpers\Url;

$this->registerJsFile(Url::base() . '/js/multiselect.js');
?>
<div class="popup-wrapper">
    <form action="<?php echo Url::toRoute(['/aftersales/sales/ebayreceipt',
    ]); ?>" method="post" role="form" class="form-horizontal">
        <div class="popup-body">
            <?php $after_sale_receipt_id = Yii::$app->getRequest()->getQueryParam('after_sale_receipt_id'); ?>
            <input type="hidden" name="after_sale_receipt_id" value="<?php if (isset($after_sale_receipt_id)) {
                echo $after_sale_receipt_id;
            } ?>">
            <div class="row">
                <div class="col-sm-14">
                    <div class="panel panel-default">
                        <div class="panel-body" border="1">
                            <table class="table" border="1">
                                <tbody>
                                <tr>
                                    <td style="text-align: left;width: 10%">订单号:</td>
                                    <td>
                                        <?php if (!empty($afterSaleReceipt)) {
                                            echo $afterSaleReceipt->order_id;
                                        } else {
                                            echo $order_id;
                                        } ?>
                                        <input type="hidden" name="order_id"
                                               value="<?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                                   echo $afterSaleReceipt->order_id;
                                               } else {
                                                   echo $order_id;
                                               }
                                               ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left;width: 10%">平台:</td>
                                    <td>
                                        <?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                            echo $afterSaleReceipt->platform_code;
                                        } else {
                                            echo $platform;
                                        } ?>
                                        <input type="hidden" name="platform_code"
                                               value="<?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                                   echo $afterSaleReceipt->platform_code;
                                               } else {
                                                   echo $platform;
                                               } ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left;width: 10%">平台账号:</td>
                                    <td>
                                        <?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                            echo \app\modules\accounts\models\Account::getAccountNameByOldAccountId($afterSaleReceipt->account_id,$afterSaleReceipt->platform_code);
                                        } else {
                                            echo \app\modules\accounts\models\Account::getAccountNameByOldAccountId($account_id,$platform);
                                        }; ?>
                                        <input type="hidden" name="account_id"
                                               value="<?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                                   echo $afterSaleReceipt->account_id;
                                               } else {
                                                   echo $account_id;
                                               }; ?>">
                                    </td>
                                </tr>

                                <tr>
                                    <td style="text-align: left;width: 10%">客户id:</td>
                                    <td>
                                        <?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                            echo $afterSaleReceipt->buyer_id;
                                        } else {
                                            echo $buyer_id;
                                        }; ?>
                                        <input type="hidden" name="buyer_id"
                                               value="<?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                                   echo $afterSaleReceipt->buyer_id;
                                               } else {
                                                   echo $buyer_id;
                                               }; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left;width: 10%">收款记录查询:</td>
                                    <td>
                                        <table style="width: 100%">
                                            <tr>
                                                <td>
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <label for="paypal_email"
                                                                   class="col-sm-3 control-label">收款方式</label>
                                                            <input name="receipt_type" class="receipt_type_radio1"
                                                                   value="1" type="radio"
                                                                <?php if (isset($afterSaleReceipt) && $afterSaleReceipt->receipt_type == 1) {
                                                                    echo 'checked="checked"';
                                                                } ?> />paypal收款
                                                            <input name="receipt_type"
                                                                   class="receipt_type_radio2" <?php if (isset($afterSaleReceipt) && $afterSaleReceipt->receipt_type == 2) {
                                                                echo 'checked="checked"';
                                                            } ?> value="2" type="radio"/> 线下收款
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="col-lg-12 receipt_type_1">
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
                                                                                        'value' => isset($afterSaleReceipt) && !empty($afterSaleReceipt) ? $afterSaleReceipt->paypal_account_id : ""
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
                                                                                           value="<?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                                                                               echo $afterSaleReceipt->transaction_id;} ?>">
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

                                                    <div class="col-lg-12 receipt_type_2">
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
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: left;width: 10%">原因:</td>
                                    <td>
                                        <div class="panel-body">
                                            <div class="row">
                                                <select class="form-control" name="receipt_reason_type" id=""
                                                        style="width: 15%;float: left;">
                                                    <option value="">请选择</option>
                                                    <?php foreach ($receipt_reason_types as $k => &$receipt_reason_type) { ?>
                                                        <?php if (isset($afterSaleReceipt) && $k == $afterSaleReceipt->receipt_reason_type) {
                                                            echo '<option selected="selected" value="' . $k . '">' . $receipt_reason_type . '</option>';
                                                        } else {
                                                            echo '<option  value="' . $k . '">' . $receipt_reason_type . '</option>';
                                                        } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="row">
                                                <textarea style="display: none" id="" name="receipt_reason_remark"
                                                          cols="60" rows="10"
                                                          placeholder="输入收款原因"><?php if (isset($afterSaleReceipt) && !empty($afterSaleReceipt)) {
                                                        echo $afterSaleReceipt->receipt_reason_remark;
                                                    } ?></textarea>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="popup-footer">
            <input class="form-control" type="hidden" id="_token_" name="_token_" value="1"/>
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
        } else if (receipt_type == 1) {
            $(".receipt_type_2").hide();
            $(".receipt_type_1").show();
        } else {
            $(".receipt_type_radio1").attr('checked', true);//默认paypal
            $(".receipt_type_1").show();
            $(".receipt_type_2").hide();
        }
        if ($("select[name=receipt_reason_type]").val() == 4) {
            $("textarea[name=receipt_reason_remark]").show()
        } else {
            $("textarea[name=receipt_reason_remark]").hide()
        }
        //编辑初始化
        var paypal_id = $("input[name=transaction_id_2]").val();
        var paypal_account_id = $("#paypal_account_id").val();
        if(paypal_id&&paypal_account_id){
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
                        '                    </tr>\n' +
                        '                    </thead>\n' +
                        '                    <tbody>';
                    html += '<td ><p>' + paypal_info.transaction_id + '</p><p>' + paypal_info.order_time + '</p></td>';
                    html += '<td ><p>' + paypal_info.amt + '</p><p>' + paypal_info.payment_status + '</p></td>';
                    html += '<td >' + paypal_info.fee_amt + '</td>';
                    html += '<td >' + paypal_info.currency + '</td>';
                    html += '<td >' + paypal_info.payer_email + '</td>';
                    html += '<td >' + paypal_info.receiver_email + '</td>';
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
        }
    });

    $("input[name=receipt_type]").click(function () {
        if($(this).val()==1){
            //线下收款
            $(".receipt_type_2").hide();
            $(".receipt_type_1").show();
        }else{
            $(".receipt_type_2").show();
            $(".receipt_type_1").hide();
        }
    })

    //原因类型选择
    $("select[name=receipt_reason_type]").on('click', function () {
        var receipt_reason_type = $("select[name=receipt_reason_type]").val();
        if (receipt_reason_type == 4) {
            $("textarea[name=receipt_reason_remark]").show();
        } else {
            $("textarea[name=receipt_reason_remark]").hide();
        }
    });
    //查询
    $("#search_payapl_record").on('click', function () {
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
                    '                    </tr>\n' +
                    '                    </thead>\n' +
                    '                    <tbody>';
                html += '<td ><p>' + paypal_info.transaction_id + '</p><p>' + paypal_info.order_time + '</p></td>';
                html += '<td ><p>' + paypal_info.amt + '</p><p>' + paypal_info.payment_status + '</p></td>';
                html += '<td >' + paypal_info.fee_amt + '</td>';
                html += '<td >' + paypal_info.currency + '</td>';
                html += '<td >' + paypal_info.payer_email + '</td>';
                html += '<td >' + paypal_info.receiver_email + '</td>';
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