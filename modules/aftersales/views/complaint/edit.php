<?php

use yii\helpers\Url;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\accounts\models\Account;
use kartik\select2\Select2;
use app\modules\aftersales\models\ComplaintModel;
use yii\bootstrap\ActiveForm;
?>
<style>
    .t-r-pd5{
        display: none;
    }
    .form-control{
        display: none;
    }
</style>
<div class="popup-wrapper">
<!--    <form action="<?php //echo Url::toRoute(['/aftersales/complaint/register','order_id' => $info['info']['order_id'], ]);   ?>" method="post" role="form" class="form-horizontal" >-->
    <?php
    $form = ActiveForm::begin([
                'id' => 'platform-form',
                //'layout' => 'horizontal',
                'method' => 'post',
//            'action' => Url::toRoute(['/aftersales/complaint/register',
//                    'platform' => $info['info']['platform_code'],
//                    'order_id' => $info['info']['order_id'],
//                ]),
                'enableClientValidation' => false,
                'validateOnType' => false,
                'validateOnChange' => false,
                'validateOnSubmit' => true,
    ]);
    ?>


    <div class="popup-body">
        <div class="row">
            <div class="col-sm-5">
                <div class="panel panel-default">
                    <?php
                    echo $this->render('order_info', ['info' => $info, 'isAuthority' => $isAuthority, 'accountName' => $accountName]);
                    echo $this->render('../order/transaction_record', ['info' => $info, 'paypallist' => $paypallist]); //交易记录
                    echo $this->render('../order/package_info', ['info' => $info]); //包裹信息
                    echo $this->render('../order/logistics', ['info' => $info, 'warehouseList' => $warehouseList]); //仓储物流
                    echo $this->render('../order/aftersales', ['afterSalesOrders' => $afterSalesOrders]); //售后问题
                    echo $this->render('../order/log', ['info' => $info]); //操作日志
                    ?>
                </div>
            </div>  
            <div class="col-sm-7">
                <div class="panel panel-default">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">仓库客诉信息</h3>
                        </div>
                        <input type="hidden" name="complaint_order" value="<?php echo $data->complaint_order; ?>" id="complaint_order">
                        <table class="table">    
                            <tbody>

                                <tr>
                                    <th style="width: 100px;text-align: -webkit-center">客诉单号</th>
                                    <td>
                                        <?php echo $data->complaint_order; ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th style="width: 100px;text-align: -webkit-center">状态</th>
                                    <td><?php echo ComplaintModel::getstatus($data['status']); ?></td>
                                </tr>
                                <tr>
                                    <th style="width: 100px;text-align: -webkit-center">加急</th>
                                    <td>
                                        <input name="is_expedited" type="radio" class="redirect-input" value="0" <?php if ($data->is_expedited == 0) { ?> checked <?php } ?>>不加急 &nbsp;&nbsp;&nbsp;<input name="is_expedited" type="radio" class="redirect-input" value="1" <?php if ($data->is_expedited == 1) { ?> checked <?php } ?>>加急
                                    </td>
                                </tr>
                                <tr>
                                    <th style="width: 100px;text-align: -webkit-center;line-height: 41px;">客诉类型</th>
                                    <td><select  name="type"  style="width: 354px;
                                                 text-align: -webkit-center;
                                                 line-height: 41px;
                                                 height: 41px;" id="type">
                                            <option value="">请选择...</option>
                                            <?php foreach ($basic as $vo) { ?>
                                                <option value="<?php echo $vo->name; ?>" <?php if ($data->type == $vo->name) { ?>selected<?php } ?>><?php echo $vo->name; ?></option>
                                            <?php } ?>
                                        </select></td>
                                </tr>
                                <tr>
                                    <th style="width: 100px;text-align: -webkit-center">详情描述</th>
                                    <td>    
                                        <textarea rows="4" cols="12" name="description" style="    width: 502px;
                                                  height: 125px;" id="description"><?php echo $data->description; ?></textarea>    
                                    </td>
                                </tr> 
                            </tbody>
                        </table>

                        <table class="table table-striped table-bordered" id="history_income_list">
                            <tr>
                                <th style="width: 80px">是否登记</th>
                                <th style="width: 240px">产品信息</th>
                                <th style="width: 50px">数量</th>
                                <th>图片</th>    
                            </tr>

                            <?php foreach ($data->complian as $vo) { ?>
                                <tr>
                                    <td><input name="id[]"  value="<?php echo $vo->id; ?>" type="checkbox" class="sel"></td>
                                    <td>
                                        <span style="color:#a96a6a">名称:</span><?php echo $vo->title; ?><input type="hidden" name="title[<?php echo $vo['id']; ?>][]" value="<?php echo $vo->title; ?>" class="picking_name"/><br/>
                                        <span style="color:#a96a6a">SKU:</span><?php echo $vo->sku; ?><input type="hidden" name="sku[<?php echo $vo->id; ?>][]" value="<?php echo $vo->sku; ?>"class="sku"/><br/>
                                        <span style="color:#a96a6a">产品线:</span><?php echo $vo->product_line; ?><input type="hidden" name="product_line[<?php echo $vo->id; ?>][]" value="<?php echo $vo->product_line; ?>" class="linelist_cn_name"/>
                                    </td>
                                    <td><input type="text" style="width:30px" name="qty[<?php echo $vo->id; ?>][]" value="<?php echo $vo->qty; ?>" class="qty" id="onebu_<?php echo $vo->id; ?>"/></td>
                                    <td>
                                        <?php
                                        echo $form->field($vo, 'img_url', ['labelOptions' => ['class' => 't-r-pd5'], 'options' => ['class' => '']])->widget('manks\FileInput', [
                                            'clientOptions' => [
                                                'pick' => [
                                                    'multiple' => true,
                                                ],
                                                'server' => Url::to('getupload'),
                                            ],
                                        ]);
                                        ?>

                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="popup-footer">
            <!--<input class="form-control" type="hidden" id="_token_" name="_token_" value="1" />-->
            <span class="btn btn-primary ajax-submit" type="" onclick="Submits()"><?php echo Yii::t('system', 'Submit'); ?></span>
            <button class="btn btn-default close-button" type="button"><?php echo Yii::t('system', 'Close'); ?></button>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
<script type="text/javascript">
      <?php foreach ($data->complian as $vo) { ?>
         $("#onebu_<?php echo $vo->id; ?>").blur(function(){
              var quantity='<?php echo $vo->qty; ?>';
              var  qty= $("#onebu_<?php echo $vo->id; ?>").val();
               if(qty>quantity){
                  layer.tips('数量要小于等于实际购买数量', '#onebu_<?php echo  $vo->id; ?>', {
                  tips: [1, '#3595CC'],
                  time: 4000
                })
                $("#onebu_<?php echo $vo->id; ?>").val(quantity);
               }       
          });
     <?php } ?>
    
    
    function Submits() {

        var complaint_order = $("#complaint_order").val();
        var trList = $("#history_income_list tbody").children("tr")
        var data = new Array();
        var tdimg = new Array();
        var id = "";
        var is_expedited = $('input:radio[name="is_expedited"]:checked').val();
        var type = $("#type").val();
        var description = $('#description').val();
        if (type == '') {
            layer.msg('请选择客诉类型', {icon: 2});
            return false;
        }
        for (var i = 1; i < trList.length; i++) {
            var tdArr = trList.eq(i).find("td");
            if (tdArr.eq(0).find('input').prop('checked')) {
                id = tdArr.eq(0).find('input').val();//
                var picking_name = tdArr.eq(1).find('.picking_name').val();//名称
                var sku = tdArr.eq(1).find('.sku').val();//sku
                var linelist_cn_name = tdArr.eq(1).find('.linelist_cn_name').val();//产品线
                var qty = tdArr.eq(2).find('.qty').val();//数量
                var img = tdArr.eq(3).find('.multi-item');
                if (img.length > 5) {
                    layer.msg('每个sku图片不能超出5张以上', {icon: 2});
                    return false;
                }
                for (var j = 0; j < img.length; j++) {
                    tdimg[j] = img.eq(j).find('input').val();

                }
                console.log(tdimg);
                data[i - 1] = [id, [picking_name, sku, linelist_cn_name], qty, tdimg]
                console.log(data);
                 tdimg=[];
            }
        }
        if (id == "") {
            layer.msg('请选择要客诉数据', {icon: 2});
            return false;
        }
        var kurl = "<?php echo $_SERVER['HTTP_REFERER']; ?>"
        var url = '<?php echo Url::toRoute(['/aftersales/complaint/geteditsave']); ?>'
        $.ajax({
            url: url,
            type: 'post',
            data: {'data': data,
                'is_expedited': is_expedited,
                'description': description,
                'type': type,
                'complaint_order': complaint_order,
            },
            dataType: "json",
            success: function (data) {
                if (data.state == 1) {
                    layer.msg(data.msg, {icon: 1});
                    window.location.href = kurl;
                } else {
                    layer.msg(data.msg, {icon: 2});
                }
            },
            error: function (e) {
                layer.msg('系统繁忙请稍后再试！', {icon: 2});
            }
        });
        return false;
    }

</script>