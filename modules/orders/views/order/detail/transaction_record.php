<?php

use kartik\select2\Select2;
use yii\helpers\Url;
?>
<div class="panel panel-default" style="margin-top: 5px;">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseFour"><h4 class="panel-title">交易记录</h4></a>
    </div>
    <div id="collapseFour" class="panel-collapse collapse in">
        <div class="panel-body">
            <table class="table table-striped transactionRecord">
                <tbody>
                    <tr>
                        <th>交易号</th>
                        <th>付款帐号</th>
                        <th>收款帐号</th>
                        <th>交易时间</th>
                        <th>交易类型</th>
                        <th>交易状态</th>
                        <th>交易金额</th>
                        <th>手续费</th>
                    </tr>
                    <?php 
                    if (!empty($info['trade'])) { ?>
                        <?php foreach ($info['trade'] as $value) { ?>
                            <tr>
                                <td><?php echo $value['transaction_id']; ?></td>
                                <td><?php echo isset($value['payer_email']) ? $value['payer_email'] : '暂无信息'; ?></td>
                                <td><?php echo isset($value['receiver_email']) ? $value['receiver_email'] : '暂无信息'; ?></td>
                                <td><?php echo $value['order_pay_time']; ?></td>
                                <td><?php echo $value['receive_type']; ?></td>
                                <td><?php echo $value['payment_status']; ?></td>
                                <td><?php echo $value['amt']; ?>(<?php echo $value['currency']; ?>)</td>
                                <td><?php echo $value['fee_amt']; ?>(<?php echo $value['currency']; ?>)</td>
                            </tr>
                        <?php }
                        if($info['info']['payment_status'] != 1){ ?>
                            <tr>
                                <td colspan="6"></td>
                                <td colspan="2" align="right">
                                    <button type="button" id="bindpayPalTransactionBtn" class="btn btn-sm btn-info waves-effect waves-light" data-toggle="modal" data-target="#myModal">关联payPal交易</button>
                                    <!--<button type="button" class="btn btn-sm btn-info waves-effect waves-light">新增交易记录</button>-->
                                </td>
                            </tr>
                        <?php }
                        } else { ?>
                        <tr><td colspan="8" align="center">没有找到信息！</td></tr>
                        
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="2" align="right">
                                <button type="button" id="bindpayPalTransactionBtn" class="btn btn-sm btn-info waves-effect waves-light" data-toggle="modal" data-target="#myModal">关联payPal交易</button>
                                <!--<button type="button" class="btn btn-sm btn-info waves-effect waves-light">新增交易记录</button>-->
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="margin-top: 10%;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    &times;
                </button>
                <h4 class="modal-title" id="myModalLabel">订单关联payPal交易记录操作</h4>
            </div>
            <div class="modal-body" style="height:160px;">
                <div class="col-sm-10">
                    <div class="form-group">
                        <label for="ship_name" class="col-sm-3 control-label required" style="margin-top: 10px;">收款账号：<span class="text-danger">*</span></label>
                        <div class="col-sm-9" style="padding: 0px;">
                            <?php
                            if (!empty($paypallist)) {
                                echo Select2::widget([
                                    'id' => 'paypal_account',
                                    'name' => 'paypal_account',
                                    'data' => $paypallist,
                                    'value' => '',
//                                    'options' => [
//                                        'placeholder' => '',
//                                    ],
                                ]);
                            }
                            ?>                           
                        </div>                                    
                    </div>
                </div>
                <div class="col-sm-10">    
                    <div class="form-group" style="margin-top:10px;">
                        <label for="ship_name" class="col-sm-3 control-label required" style="margin-top: 10px;">交 &nbsp;易 &nbsp;号：<span class="text-danger">*</span></label>
                        <div class="col-sm-9" style="padding: 0px;">
                            <input type="text" class="form-control" name="transaction_number" id = "transaction_number"/>                        
                        </div>                                    
                    </div>
                </div>

                <div class="col-sm-10">    
                    <div class="form-group" style="margin-top:10px;">
                        <label for="ship_name" class="col-sm-3 control-label required" style="margin-top: 10px;">金 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 额：<span class="text-danger">*</span></label>
                        <div class="col-sm-9" style="padding: 0px;">
                            <div class="input-group margin">
                                <select class="form-control" id="currency" name="currency" style="width:80px; float:left;">
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                        <option value="GBP">GBP</option>
                                        <option value="AUD">AUD</option>
                                        <option value="CAD">CAD</option>
                                    </select>
                                <!-- /btn-group -->
                                <input type="text" class="form-control" name="amount" id="amount" onkeyup="if (isNaN(value))
                                        execCommand('undo')" onafterpaste="if(isNaN(value))execCommand('undo')" style="width:100px; float:left;">
                            </div>                     
                        </div>                                    
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary save">保存</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal -->
</div>

<script>
    $(function () {
        $(".save").on('click', function () {
            var account = $("#paypal_account").val();
            var transactionNumber = $("#transaction_number").val();
            var amount = $("#amount").val();
            var currency = $("#currency").val();

            if (account == "" || transactionNumber == "" || amount == "" || currency == "") {
                layer.msg('相关数据不能为空');
                return false;
            }

            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['orderbindtransaction']) ?>',
                data: {'account': account, 'transactionId': transactionNumber,'currency':currency, 'amount': amount, 'order_id': '<?php echo $info['info']['order_id']; ?>'},
                success: function (data) {
                    if(!data.bool){
                        layer.msg(data.msg, {icon: 1});
                        if(data.info != ""){
                            var receiveType = data.info.receive_type ? '接收' : '付款';
                            var htm = "";
//                            htm = '<tr>';
                            htm += '<td>'+data.info.transaction_id+'</td>';
                            htm += '<td>'+data.info.payer_email+'</td>';
                            htm += '<td>'+data.info.receiver_email+'</td>';
                            htm += '<td>'+data.info.order_time+'</td>';
                            htm += '<td>'+receiveType+'</td>';
                            htm += '<td>'+data.info.payment_status+'</td>';
                            htm += '<td>'+data.info.amt+'('+data.info.currency+')'+'</td>';
                            htm += '<td>'+data.info.fee_amt+'('+data.info.currency+')'+'</td>';
//                            htm += '</tr>';
                            $(".transactionRecord tr:eq(1)").html(htm);//加载新绑定数据
                            $("#bindpayPalTransactionBtn").remove();//移除添加按钮
                            $("#myModal").modal('hide');//隐藏弹窗层
                        }
                    }else{
                        layer.msg(data.msg, {icon: 5});
                    }
                }
            });
        });
    });
</script>