<?php
header("Content-type:application/vnd.ms-excel");
header("Content-Disposition:attachment;filename=$filename.xls");
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

    table.dataintable td {
        padding: 5px 15px 5px 5px;
        vertical-align: middle;
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
</style>
<table class="dataintable" border="2" cellspacing="1" cellpadding="3">
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
        <td style="text-align: center;">
            <?php echo $invoicedetail->invoice_data->date;?>
        </td>
    </tr>
    <tr>
        <td width="180">发件人：</td>
        <td style="text-align: center;"><?php echo $invoicedetail->invoice_data->senderName;?></td>
        <td width="180">收件人：</td>
        <td style="text-align: center;"><?php echo $invoicedetail->invoice_data->receiverName;?></td>
    </tr>
    <tr>
        <td width="180" class="old">发件人地址：</td>
        <td style="" class="add"><?php echo '1st Floor Junying Shopping Plaza,Ke Yuan Road No.16,'.'<br>'.'Tang sha Town,Dong guan City,Guang dong Province,China
            523900'.'<br>'.' TEL:0086-0755-22941390';?></td>
        <td width="180" class="old">收件人地址：</td>
        <td style="">
            <?php echo $invoicedetail->invoice_data->address;?>
        </td>
    </tr>
    <tr>
        <td width="180" class="miao" style="text-align: center;" colspan="1"><?php  echo 'Description of Goods <br> 货物描述';?></td>
        <td width="180" class="miao" style="text-align: center;">数量 (pcs)</td>
        <td width="180" class="miao" style="text-align: center;"><?php  echo 'Unit Value(USD) including shipping fee <br> 单价 ('.$model->currency.')';?></td>
        <td width="180" class="miao" style="text-align: center;"><?php  echo 'Total Value (USD) <br> 总价 ('.$model->currency.')';?></td>

    </tr>
    <?php foreach($invoicedetail->invoice_data->goodsDescript as $key => $value){ ;?>
        <tr>
            <td width="180" style="text-align: center;" colspan="1"><?php echo $value ?></td>
            <td width="180" style="text-align: center;"><?php echo $invoicedetail->invoice_data->goodsDetails->QTY[$key] ?></td>
            <td width="180" style="text-align: center;"><?php echo $invoicedetail->invoice_data->goodsDetails->cost[$key] ?></td>
            <td width="180" style="text-align: center;"><?php echo $invoicedetail->invoice_data->goodsDetails->price[$key] ?></td>
        </tr>

    <?php };?>
    <tr>
        <td width="180" style="text-align: center;" colspan="1">shipping fee (<?php echo $model->currency;?>)</td>
        <td width="180" style="text-align: center;" colspan="3">
            <?php echo $invoicedetail->invoice_data->shippingFee;?>
        </td>
    </tr>
    <tr>
        <td width="180" style="text-align: center;" colspan="1">Total (<?php echo $model->currency;?>)</td>
        <td width="180" style="text-align: center;" colspan="3">
            <?php echo $invoicedetail->invoice_data->total;?>
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
                <img src="http://120.24.249.36/images/qianming.jpg" width="275" height="60">
            </div>
        </td>
    </tr>
</table>


