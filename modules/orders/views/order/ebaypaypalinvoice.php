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
        <ul class="nav nav-tabs">
            <li class="active"><a href="#operating" data-toggle="tab" aria-expanded="true">操作</a></li>
            <li><a href="#order_detail" data-toggle="tab">订单详情</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade in active" id="operating">

                <div class="popup-body">
                    <div class="row">
                        <div class="col-sm-9 form-group">                                                         
                            <label class="col-sm-2 control-label required" for="ship_name">我司paypal账号<span class="text-danger">*</span></label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="paypal_account" id = "paypal_account" 
                                <?php if ($trade['receiver_email']) {?>
                                value="<?=$trade['receiver_email']?>" 
                                <?php } else {?>
                                placeholder="请输入收款payPal账号" 
                                <?php }?>
                                >
                            </div> 
                                <input type="hidden" name="order_id" id="order_id" value="<?=$order_id;?>">
                        </div>
                    </div>        
                    <div class="row">
                        <div class="col-sm-9 form-group"> 
                            <label class="col-sm-2 control-label required" for="ship_name" >客户paypal账号<span class="text-danger">*</span></label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="payer_email" id = "payer_email"  
                                <?php if ($trade['payer_email']) {?>
                                value="<?php echo $trade['payer_email'];?>" 
                                <?php } else {?>
                                placeholder="请输入客户payPal账号" 
                                <?php }?>
                                >
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
                                <input type="text" class="form-control" name="product_name" id = "product_name" value="<?=$productName;?>" >
                                <input type="hidden" class="form-control" name="platform" id = "platform" value="<?=$platform;?>" >
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
                    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
                </div>
            </div>
            <div class="tab-pane fade" id="order_detail">
                <iframe src="/orders/order/orderdetails?order_id=<?=$platform_order_id?>&platform=<?=$platform?>" frameborder="1" width="100%" height="100%" style="min-height:1000px;"></iframe>
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
        var orderId = $('#order_id').val(); //订单号
        var platform_code = $('#platform').val(); //平台

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
        if ((!reg.test(paypalAccount)) || (!reg.test(payerEmail))) {
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
            url: '<?php echo Url::toRoute(['sendpaypalinvoice']) ?>',
            data: {'platform_code':platform_code, 'paypalAccount': paypalAccount, 'payerEmail': payerEmail,'currency':currency, 'amount': amount, 'productName': productName ,'note': note,'orderId':orderId},
            success: function (data) {
                //console.log(data);
                if(!data.bool){
                    layer.alert(data.msg, {icon: 1}, function() {
                        if(data.info!='') {
                            top.layer.closeAll("iframe"); 
                        }
                        parent.location.reload(); 
                    });
                }else{
                    layer.alert(data.msg, {icon: 5});
                }
            }
        });
    });    
});

/*function getMoney(obj){
    //修复第一个字符是小数点 的情况.  
    if(obj.value !=''&& obj.value.substr(0,1) == '.'){  
        obj.value="";  
    }  
    obj.value = obj.value.replace(/^0*(0\.|[1-9])/, '$1');//解决 粘贴不生效  
    obj.value = obj.value.replace(/[^\d.]/g,"");  //清除“数字”和“.”以外的字符  
    obj.value = obj.value.replace(/\.{2,}/g,"."); //只保留第一个. 清除多余的       
    obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");      
    obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3');//只能输入两个小数       
    if(obj.value.indexOf(".")< 0 && obj.value !=""){//以上已经过滤，此处控制的是如果没有小数点，首位不能为类似于 01、02的金额  
        if(obj.value.substr(0,1) == '0' && obj.value.length == 2){  
            obj.value= obj.value.substr(1,obj.value.length);      
        }  
    }
}*/

</script>