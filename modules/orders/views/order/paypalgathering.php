<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use kartik\select2\Select2;
?>
<style type="text/css">
    .row{margin-bottom: 15px;}
    label{margin-top: 10px;margin-left:35px;margin-right: -50px;}
</style>
<div class="popup-wrapper">
    <div class="popup-body">
        <div class="tab-content">
            <div class="tab-pane fade in active" id="operating">
                <div class="popup-body">
                    <div class="row">
                        <div class="col-sm-9 form-group">
                            <label class="col-sm-2 control-label required" for="ship_name">我司paypal账号<span class="text-danger">*</span></label>
                            <div>
                                <div class="form-group">
                                    <div class="col-lg-7">
                                        <?php echo Select2::widget([
                                            'id' => 'paypal_account',
                                            'name' => 'paypal_account',
                                            'value' => $paypal_account,
                                            'data' => $paypal_email,
                                            'options' => ['placeholder' => 'payple号搜索...']
                                        ]);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-9 form-group">
                            <label class="col-sm-2 control-label required" for="ship_name" >客户paypal账号<span class="text-danger">*</span></label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="payer_email" id = "payer_email" placeholder="请输入客户payPal账号">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-9 form-group">
                            <label class="col-xs-2 control-label required" for="ship_name">收款币种/金额<span class="text-danger">*</span></label>
                            <div class="col-xs-2" style="padding-right:0px;">
                                <select class="form-control" id="currency" name="currency">
                                    <option value="USD">USD</option>
                                    <option value="CAD">CAD</option>
                                    <option value="AUD">AUD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                </select>
                            </div>
                            <div class="col-xs-4" style="padding-left:0px;">
                                <input type="text" class="form-control" name="amount" id="amount" onkeyup="if (isNaN(value))
                                        execCommand('undo')" onafterpaste="if(isNaN(value))execCommand('undo')" placeholder="请输入收款金额(数字格式)">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-9 form-group">
                            <label class="col-sm-2 control-label required" for="ship_name" >产品名称<span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="product_name" id = "product_name" placeholder="请输入产品名称" >
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-9">
                            <label class="col-sm-2 control-label" for="form-group">留言</label>
                            <div class="col-sm-10">
                                <textarea id="note" rows="3" cols='143' maxlength="80" placeholder="输入给客户的留言"></textarea>
                                <span style="display: none;" class="count_length">(0/80)字符</span></td>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="popup-footer">
                    <button class="btn btn-primary save" type="button"><?php echo Yii::t('system', 'Submit');?></button>
                </div>
            </div>
        </div>

    </div>

    <script type="text/javascript">
        $(function(){

            var content = "内容(0/80)字符";
            $('#note').on('keyup',function(){
                $(".count_length").css('display','block');
                var contentLength = $(this).val().length;
                if(contentLength < 80){
                    var content = $('.count_length').text('内容('+contentLength+'/80)');
                    $("label[for='form-group']").html(content);
                }else{
                    var text = "内容<span style='color:red;'>(80/80)</span>"
                    var content = $('.count_length').html(text);
                }
            });
            //发送收款请求
            $(".save").on('click', function () {
                var paypalAccount = $.trim($("#paypal_account").val()); //卖家邮件账号
                var payerEmail = $.trim($("#payer_email").val()); //买家邮件账号
                var currency = $("#currency").val();    //货币
                var amount = $("#amount").val();    //金额
                var productName = $("#product_name").val(); //产品名称
                var note = $("#note").val();    //留言

                if (paypalAccount == "" || payerEmail == "" || amount == "" || currency == "" || productName == "") {
                    layer.msg('相关数据不能为空，请填写完全！',{
                        icon: 7,
                        time: 3000
                    });
                    return false;
                }

                //过滤账号格式为邮箱格式

                var reg = /^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/;//正则表达式
                //var reg = /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
                if (!reg.test(payerEmail)) {
                    layer.msg('请填写正确邮件格式及paypal账号！',{
                        icon: 7,
                        time: 3000
                    });
                    return false;
                }
                //发送请求
                $.ajax({
                    type: "POST",
                    dataType: "JSON",
                    url: '<?php echo Url::toRoute(['gathering']) ?>',
                    data: {'paypalAccount': paypalAccount, 'payerEmail': payerEmail,'currency':currency, 'amount': amount, 'productName': productName ,'note': note},
                    success: function (data) {
                        //console.log(data);
                        if(!data.bool){
                            layer.alert(data.msg, {icon: 1}, function() {
                                parent.location.reload();
                            });
                        }else{
                            layer.alert(data.msg, {icon: 5});
                        }
                    }
                });
            });
        });
    </script>