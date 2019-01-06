<?php
use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
use yii\widgets\ActiveForm;
?>
<style type="text/css">
    input.new.textInput {
        position: relative;
        left: 71px;
    }
    .up {
        border: none;
        position: relative;
        left: 80px;
        top: 1px;
    }
    input.textInput {
        position: relative;
        left: 311px;
    }
    input.date.textInput {
        position: relative;
        left: 71px;
    }
    .pageFormContent {
        display: block;
        overflow: auto;
        padding: 10px 5px;
        position: relative;
    }
    table.dataintable td {
        padding: 5px 15px 5px 5px;
        vertical-align: middle;
        border: 1px solid #AAAAAA;
        padding: 5px 15px 5px 5px;
        vertical-align: middle
    }

    input.people.textInput {
        position: relative;
        left: 18px;
    }
    a.delete{
        color: #000;
        text-decoration: none;
        line-height: 20px;
        position: relative;
        left: 45px;
        top: 6px;
    }
    #down {
        color: #fff;
        background: #029be5;
        padding: 8px 11px;
    }
    #down:hover { color:blue;}
</style>
<div class="pageContent">
    <form action="" role="form" class="form-horizontal" method="post">
    <div class="pageFormContent" layoutH="56">
        <div style="display:block;">
            <?php
            error_reporting(0);
            if(!empty($invoiceInfo)){;?>
                <span>发票随货：</span>
                <input type="radio" name="invoice_logistics" value="1" <?php if($invoiceInfo['invoice_logistics'] == 1){echo checked;};?>> 是
                <input type="radio" name="invoice_logistics" value="0" <?php if($invoiceInfo['invoice_logistics'] == 0){echo checked;};?>> 否
            <?php }else{ ;?>
                <span>发票随货：</span>
                <input type="radio" name="invoice_logistics" value="1" checked > 是
                <input type="radio" name="invoice_logistics" value="0" > 否
            <?php };?>
        </div>

        <table class="dataintable" border="0" cellspacing="1" cellpadding="3">
            <tr>
                <td style="text-align: center;font-size:25px" colspan="4" >Commercial invoice</td>
            </tr>
            <tr>
                <td width="180"><?php  echo 'International Air Waybill No.: <br>'.Yii::t('order','物流单号').'：';?></td>
                <td style="text-align: center;">
                    <?php if(!empty($invoicedetail)) {;?>
                        <?php echo $invoicedetail->invoice_data->trackingNumber;?>
                    <?php }else{ ;?>
                        <?php echo $model->track_number;?>
                    <?php };?>
                </td>
                <td width="180"><?php  echo 'Date of Exportation： <br>'.'出口日期：';?></td>
                <td style=""><?php if(!empty($invoicedetail->invoice_data->date)) {;?>
                        <div class="form-group" style="width:235px;margin: 20px;">
                            <?php
                            echo DateTimePicker::widget([
                                'name' => 'invoices[date]',
                                'options' => ['placeholder' => ''],
                                'value' => $invoicedetail->invoice_data->date,
                                'pluginOptions' => [
                                    'autoclose' => true,
                                    'format' => 'yyyy-mm-dd',
                                    'todayHighlight' => true,
                                    'todayBtn' => 'linked',
                                ],

                            ]); ?>
                        </div>
                    <?php }else{; ?>
                            <div class="form-group" style="width:235px;margin: 20px;">
                                <?php
                                echo DateTimePicker::widget([
                                    'name' => 'invoices[date]',
                                    'options' => ['placeholder' => ''],
                                    'value' => '',
                                    'pluginOptions' => [
                                        'autoclose' => true,
                                        'format' => 'yyyy-mm-dd',
                                        'todayHighlight' => true,
                                        'todayBtn' => 'linked',
                                    ],

                                ]); ?>
                            </div>
                    <?php };?>
                </td>
            </tr>
            <tr>
                <td width="180">发件人：</td>
                <td style="">
                    <?php if(!empty($invoicedetail->invoice_data->senderName)) {;?>
                        <input class="people" style="width: 180px;text-align: center;" type="text" name="invoices[senderName]" value="<?php echo $invoicedetail->invoice_data->senderName;?>" autocomplete="name">
                    <?php }else{; ?>
                        <input class="people" style="width: 180px;text-align: center;" type="text" name="invoices[senderName]" value="<?php echo '李东东';?>" autocomplete="name">
                    <?php };?>
                    <!--                    <input class="people" style="width: 180px;text-align: center;" type="text" name="sender" value="--><?php //echo '李东东';?><!--">-->
                </td>
                <td width="180">收件人：</td>
                <td style="">
                    <?php if(!empty($invoicedetail->invoice_data->receiverName)) {;?>
                        <input class="people" style="width: 180px;text-align: center;" type="text" name="invoices[receiverName]" value="<?php echo $invoicedetail->invoice_data->receiverName;?>" autocomplete="name">
                    <?php }else{; ?>
                        <input class="people" style="width: 180px;text-align: center;" type="text" name="invoices[receiverName]" value="<?php echo $model->ship_name;?>" autocomplete="name">
                    <?php };?>
                </td>
            </tr>
            <tr>
                <td width="180" class="old">发件人地址：</td>
                <td style="" class="add"><?php echo '1st Floor Junying Shopping Plaza,Ke Yuan Road No.16,'.'<br>'.'Tang sha Town,Dong guan City,Guang dong Province,China
                    523900'.'<br>'.' TEL:0086-0755-22941390';?></td>
                <td width="180" class="old">收件人地址：</td>
                <td style="">
                    <?php if(!empty($invoicedetail->invoice_data->address)) {;?>
                        <textarea style="height:100px;width:100%;font-size: 12px;"  name="invoices[address]"  type="text" value=" " class="editor" autocomplete="name"><?php echo $invoicedetail->invoice_data->address;?></textarea>
                    <?php }else{; ?>
                        <textarea style="height:100px;width:100%;font-size: 12px;"  id="shipaddress" name="invoices[address]"  type="text" value=" " class="editor" autocomplete="name">点我--进行--地址--编辑</textarea>
                    <?php };?>
                </td>
            </tr>
            <tr>
                <td width="180" class="miao" style="text-align: center;" colspan="1"><?php  echo 'Description of Goods <br> 货物描述';?></td>
                <td width="180" class="miao" style="text-align: center;">数量 (pcs)</td>
                <td width="180" class="miao" style="text-align: center;"><?php  echo 'Unit Value(USD) <br> 单价 ('.$model->currency.')';?></td>
                <td width="180" class="miao" style="text-align: center;"><?php  echo 'Total Value (USD) <br> 总价 ('.$model->currency.')';?></td>

            </tr>
            <?php if(!empty($invoicedetail)) {;?>
                <?php foreach($invoicedetail->invoice_data->goodsDescript as $key => $value){ ;?>
                    <tr>
                        <td width="180" style="text-align: center;" colspan="1"><textarea style="height:100px;width:100%;font-size: 12px;" name="invoices[goodsDescript][]"  type="text" value=""  class="textInput"><?php echo $value ?></textarea></td>
                        <td width="180" style="text-align: center;"><input class="new" style="width: 80px;text-align: center;" type="text" name="invoices[goodsDetails][QTY][]" value="<?php echo $invoicedetail->invoice_data->goodsDetails->QTY[$key] ?>"></td>
                        <td width="180" style="text-align: center;"><input class="new" style="width: 80px;text-align: center;" type="text" name="invoices[goodsDetails][cost][]" value="<?php echo $invoicedetail->invoice_data->goodsDetails->cost[$key] ?>"></td>
                        <td width="180" style="text-align: center;"><input class="new" style="width: 80px;text-align: center;" type="text" name="invoices[goodsDetails][price][]" value="<?php echo $invoicedetail->invoice_data->goodsDetails->price[$key] ?>">
                            <a  class="delete" href="javascript:;" onclick="addTr(this,'0');"><font color="blue">增加</font></a>
                            <a  class="delete" href="javascript:;" onclick="deleteTr(this,'0');"><font color="blue">删除</font></a>
                        </td>
                    </tr>
                <?php };?>
            <?php }else{; ?>
                <?php foreach($detail as $k => $v){ ;?>
                    <tr>
                        <td width="180" style="text-align: center;" colspan="1"><textarea style="height:100px;width:100%;font-size: 12px;" name="invoices[goodsDescript][]"  type="text" value=""  class="textInput"><?php echo $v["title"] ?></textarea></td>
                        <td width="180" style="text-align: center;"><input class="new" style="width: 80px;text-align: center;" type="text" name="invoices[goodsDetails][QTY][]" value="<?php echo $v["quantity"] ?>"></td>
                        <td width="180" style="text-align: center;"><input class="new" style="width: 80px;text-align: center;" type="text" name="invoices[goodsDetails][cost][]" value="<?php echo $v["sale_price"] ?>"></td>
                        <td width="180" style="text-align: center;"><input class="new" style="width: 80px;text-align: center;" type="text" name="invoices[goodsDetails][price][]" value="<?php echo $v["quantity"]*$v["sale_price"] ?>">
                            <a  class="delete" href="javascript:;" onclick="addTr(this,'0');"><font color="blue">增加</font></a>
                            <a  class="delete" href="javascript:;" onclick="deleteTr(this,'<?php echo $v["ship_price"];?>');"><font color="blue">删除</font></a>
                        </td>
                    </tr>
                <?php };?>
            <?php };?>
            <tr>
                <td width="180" style="text-align: center;" colspan="1">shipping fee (<?php echo $model->currency;?>)</td>
                <td width="180" style="text-align: center;" colspan="3">
                    <?php if(!empty($invoicedetail)) {;?>
                        <input style="width: 80px;text-align: center;" type="text" name="invoices[shippingFee]" value="<?php echo $invoicedetail->invoice_data->shippingFee;?>">
                    <?php }else{; ?>
                        <input style="width: 80px;text-align: center;" type="text" name="invoices[shippingFee]" value="<?php foreach($detail as $key => $value){static $result = 0;$result += $value['ship_price'];};?><?php echo  $result;?>">
                    <?php };?>
                </td>
            </tr>
            
            <?php if($platform=='SHOPEE'){ ?>
            <tr>
                <td width="180" style="text-align: center;" colspan="1">Voucher (<?php echo $model->currency;?>)</td>
                <td width="180" style="text-align: center;" colspan="3">
                    <?php if(!empty($invoicedetail->Voucher)) {;?>
                        <input style="width: 80px;text-align: center;"  type="text" name="invoices[Voucher]" value="<?php echo $invoicedetail->invoice_data->Voucher;?>">
                    <?php }else{; ?>
                        <input style="width: 80px;text-align: center;"  type="text" name="invoices[Voucher]" value="<?php echo $v["total_price"] ?>">
                    <?php };?>
                </td>
            </tr>
            <?php } ?>
            
            <tr>
                <td width="180" style="text-align: center;" colspan="1">Total (<?php echo $model->currency;?>)</td>
                <td width="180" style="text-align: center;" colspan="3">
                    <?php if(!empty($invoicedetail->total)) {;?>
                        <input style="width: 80px;text-align: center;"  type="text" name="invoices[total]" value="<?php echo $invoicedetail->invoice_data->total;?>">
                    <?php }else{; ?>
                        <input style="width: 80px;text-align: center;"  type="text" name="invoices[total]" value="<?php echo $v["total_price"] ?>">
                    <?php };?>
                </td>
            </tr>
            <tr>
                <td style="text-align: center;" colspan="2" >CONFIRMED AND ACCEPTED BY </td>
                <td style="text-align: center;" colspan="2" >shenzhen yibai network technology co.ltd</td>
            </tr>
            <tr>
                <td style="height: 80px;text-align: center;" colspan="2" >SIGNATURE AND COMPANY CHOP </td>
                <td style="height: 80px;" colspan="2" >
                    <div class="up">
                        <img src="http://120.24.249.36/images/qianming.jpg">
                    </div>
                </td>
            </tr>
            <input type="hidden" name="id" value="<?php echo $model->order_id;?>">
            <input type="hidden" name="invoices[trackingNumber]" value="<?php echo $model->track_number;?>">
            <input type="hidden" name="invoices[currencyType]" value="<?php echo $model->currency;?>">
            <input type="hidden" name="platform" value="<?php echo $platform;?>">
            <input type="hidden" name="do" value="do">
        </table>
    </div>
        <div class="popup-footer">
            <button class="btn btn-primary ajax-submit" id ="button" type="button"><?php echo Yii::t('system', 'Submit');?></button>
            <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
            <?php if(!empty($invoiceInfo)){;?>
                <a href="javascript:;" id="down" type="submit" style="text-decoration: none;">下载</a>

            <?php };?>
        </div>
    </form>
</div>

<script>
    $("#shipaddress").click(function(){
        var str = "<?php
            $street = $model->ship_street2?$model->ship_street1.' '.$model->ship_street2:$model->ship_street1;

            $ship_address = $street.' '.$model->ship_city_name.','.$model->ship_stateorprovince.','.$model->ship_zip.'\n'.$model->ship_country_name.'\n'.'Mobile:'.$model->ship_phone;

            echo $ship_address;
            ?>";
        $(this ).val(str);
    });

    $("input[name='invoices[goodsDetails][cost][]']").blur( function () {
        var cost = $(this ).val();
        var QTY = $(this ).parent('td' ).prev('td' ).children("input[name='invoices[goodsDetails][QTY][]']" ).val();
        var price = cost * QTY;
        $(this ).parent('td' ).next('td' ).children("input[name='invoices[goodsDetails][price][]']" ).val(price);
        var totalPrices = 0;
        var result = [];
        var prices = $("input[name='invoices[goodsDetails][price][]']" );
        var shippingfee = $("input[name='invoices[shippingFee]'" ).val();
       var code="<?php echo $platform; ?>";
        if(code=="SHOPEE"){
          var Voucher=$("input[name='invoices[Voucher]'" ).val();
        }else{
          var Voucher=0;
        }
        prices.each(function(){
            result.push($(this ).val());
        });
        var length = $("input[name='invoices[goodsDetails][price][]']" ).length;
        for(i=0;i<length;i++){
            totalPrices += Number(result[i] );
        }
        totalPrices = Number(totalPrices) + Number(shippingfee)-Number(Voucher);
        totalPrices = Math.round(totalPrices*100)/100;
        $("input[name='invoices[total]']" ).val(totalPrices);
    } );

    $("input[name='invoices[shippingFee]'" ).blur(function(){
        var totalPrices = 0;
        var result = [];
        var prices = $("input[name='invoices[goodsDetails][price][]']" );
        var shippingfee = $(this).val();
        var code="<?php echo $platform; ?>";
        if(code=="SHOPEE"){
          var Voucher=$("input[name='invoices[Voucher]'" ).val();
        }else{
          var Voucher=0;
        }
        prices.each(function(){
            result.push($(this ).val());
        });
        var length = $("input[name='invoices[goodsDetails][price][]']" ).length;
        for(i=0;i<length;i++){
            totalPrices += Number(result[i] );
        }
        totalPrices = Number(totalPrices) + Number(shippingfee)-Number(Voucher);
        totalPrices = Math.round(totalPrices*100)/100;
        $("input[name='invoices[total]']" ).val(totalPrices);

    });
    $("input[name='invoices[Voucher]'" ).blur(function(){
        var totalPrices = 0;
        var result = [];
        var prices = $("input[name='invoices[goodsDetails][price][]']" );
        var Voucher = $(this).val();
        prices.each(function(){
            result.push($(this ).val());
        });
        var length = $("input[name='invoices[goodsDetails][price][]']" ).length;
        for(i=0;i<length;i++){
            totalPrices += Number(result[i] );
        }
        var shippingfee=$("input[name='invoices[shippingFee]'" ).val();
        totalPrices = Number(totalPrices)+ Number(shippingfee) - Number(Voucher);
        totalPrices = Math.round(totalPrices*100)/100;
        $("input[name='invoices[total]']" ).val(totalPrices);

    });

    //删除某行
    function deleteTr(nowTr,value){
        var yunfei = $("input[name='invoices[shippingFee]'" ).val();
        var ship;
        ship = Number(yunfei) - Number(value);
        ship = Math.round(ship*100)/100;
        $("input[name='invoices[shippingFee]'" ).val(ship)
        $(nowTr).parent().parent().remove();
        var totalPrices = 0;
        var result = [];
        var prices = $("input[name='invoices[goodsDetails][price][]']" );
        var shippingfee = $("input[name='invoices[shippingFee]'" ).val();
        var code="<?php echo $platform; ?>";
        if(code=="SHOPEE"){
          var Voucher=$("input[name='invoices[Voucher]'" ).val();
        }else{
          var Voucher=0;
        }
        prices.each(function(){
            result.push($(this ).val());
        });
        var length = $("input[name='invoices[goodsDetails][price][]']" ).length;
        if(length >0){
            for(i=0;i<length;i++){
                totalPrices += Number(result[i] );
            }
            totalPrices = Number(totalPrices) + Number(shippingfee)-Number(Voucher);
            totalPrices = Math.round(totalPrices*100)/100;
        }else{
            totalPrices = 0;
            $("input[name='invoices[shippingFee]'" ).val(0);
        }

        $("input[name='invoices[total]']" ).val(totalPrices);
    }

    //增加某行
    function addTr(nowTr,value){
        var tr = $(nowTr ).parents('tr').clone(true );
        tr.find("input[name='invoices[goodsDetails][price][]']").val('');
        tr.find("input[name='invoices[goodsDetails][cost][]']").val('');
        tr.find("input[name='invoices[goodsDetails][QTY][]']").val('');
        tr.find("textarea").html('');
        $(".dataintable tr" ).eq(4).after(tr);
        var yunfei = $("input[name='invoices[shippingFee]'" ).val();
        var ship;
        ship = Number(yunfei) + Number(value);
        ship = Math.round(ship*100)/100;
        $("input[name='invoices[shippingFee]'" ).val(ship)
        var totalPrices = 0;
        var result = [];
        var prices = $("input[name='invoices[goodsDetails][price][]']" );
        var shippingfee = $("input[name='invoices[shippingFee]'" ).val();
        var code="<?php echo $platform; ?>";
        if(code=="SHOPEE"){
          var Voucher=$("input[name='invoices[Voucher]'" ).val();
        }else{
          var Voucher=0;
        }
        prices.each(function(){
            result.push($(this ).val());
        });
        var length = $("input[name='invoices[goodsDetails][price][]']" ).length;
        for(i=0;i<length;i++){
            totalPrices += Number(result[i] );
        }
       
        totalPrices = Number(totalPrices) + Number(shippingfee)- Number(Voucher);
        totalPrices = Math.round(totalPrices*100)/100;
        $("input[name='invoices[total]']" ).val(totalPrices);
    }

    $("#down" ).click(function(){
        var data = "<?php echo $model->order_id.",".$platform;?>";
        $("#load" ).val(data);
        var action = '/orders/order/exportinvocie';
        $("#download").attr('action', action ).submit();
        $.pdialog.closeCurrent();
    })

    $(function(){
        var totalPrices = 0;
        var result = [];
        var prices = $("input[name='invoices[goodsDetails][price][]']" );
        var shippingfee = $("input[name='invoices[shippingFee]'" ).val();
        var code="<?php echo $platform; ?>";
        if(code=="SHOPEE"){
          var Voucher=$("input[name='invoices[Voucher]'" ).val();
        }else{
          var Voucher=0;
        }
        prices.each(function(){
            result.push($(this ).val());
        });
        var length = $("input[name='invoices[goodsDetails][price][]']" ).length;
        for(i=0;i<length;i++){
            totalPrices += Number(result[i] );
        }
        totalPrices = Number(totalPrices) + Number(shippingfee)-Number(Voucher);
        totalPrices = Math.round(totalPrices*100)/100;
        $("input[name='invoices[total]']" ).val(totalPrices);
    })

</script>

<form  action="" id="download" method="post" target="_blank">
    <input id="load" type="hidden" name="invoice" value="">
</form>