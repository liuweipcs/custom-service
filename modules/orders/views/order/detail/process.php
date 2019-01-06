<?php
    use yii\helpers\Url;
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapse9"><h4 class="panel-title">延长收货期</h4></a>
    </div>
    <div id="collapse9" class="panel-collapse collapse">
        <div class="panel-body" style=" height: auto; max-height:350px;overflow-y:scroll;">
            <div class="extendAcceptGoodsTime" tabindex="-1" data-widget-cid="widget-6" style="width: 700px; z-index: 999; left: 699.5px; top: 115.5px;">
                        <div class="" data-role="content" style="max-height: none;">
                            <div class="">
                                <p id="rejectReasonErrorTip" class="">为防止货物在运输途中的突发因素，导致买家不能及时收到货物，您可以适当延长买家收货时间。</p>
                                <form name="" id="extendAcceptGoodsTimeForm" action="" method="post">
                                    <input type="hidden" name="account_name" value="<?php echo \app\modules\accounts\models\Account::getHistoryAccount($info['info']['account_id'],$info['info']['platform_code']);?>">
                                    <input type="hidden" name="platform_order_id" value="<?php echo $info['info']['platform_order_id'];?>">
                                    <input type="hidden" name="platform_code" value="<?php echo $info['info']['platform_code'];?>">
                                    <p id="rejectReasonError" class="">延长买家收货确认时间
                                        <input id="day" name="day" size="5" maxlength="10" type="text">天
                                    </p>
                                </form>
                            </div>
                        </div>
                        <div class="ui-window-btn" data-role="buttons">
                            <input type="button" value="确认" id="confirm" class="" data-role="confirm">
                            <input type="button" value="关闭" class="cancel" data-role="cancel">
                        </div>
                    </div>
        </div>
    </div>
</div>

<script>
    $("#confirm" ).click(function(){
        $.ajax({
            type: "POST",
            url : '<?php echo Url::toRoute(['/orders/order/extendacceptgoodstime']);?>',
            data:$('#extendAcceptGoodsTimeForm').serialize(),
            success: function(data) {
               var obj = eval('('+data+')');
                if(obj.ack==1){
                    layer.alert(obj.message, {
                        icon: 1
                    });
                }else{
                    layer.alert(obj.message, {
                        icon: 0
                    });
                }
            }
        });
    });
</script>