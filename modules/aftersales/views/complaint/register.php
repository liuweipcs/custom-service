<?php

use yii\helpers\Url;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\accounts\models\Account;
use kartik\select2\Select2;
use app\modules\aftersales\models\ComplaintModel;
use yii\helpers\Html;
//use yii\widgets\ActiveForm;
use yii\bootstrap\ActiveForm;

//$this->registerJsFile(Url::base() . '/js/multiselect.js');
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
    <!--    <form action="<?php //echo Url::toRoute(['/aftersales/complaint/register','platform' => $info['info']['platform_code'],'order_id' => $info['info']['order_id']]);     ?>" method="post" role="form" class="form-horizontal" >-->
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
            <?php $complain = ComplaintModel::find()->where(['order_id' => $info['info']['order_id']])->andWhere(['platform_order_id' => $info['info']['platform_order_id']])->all();
            ?>
            <div class="col-sm-7">
                <div class="panel panel-default">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">仓库客诉信息</h3>
                        </div> 
                        <input type="hidden" name="platform_order_id" value="<?php echo $info['info']['platform_order_id']; ?>" id="platform_order_id">
                        <input type="hidden" name="order_id" value="<?php echo $info['info']['order_id']; ?>" id="order_id">
                        <input type="hidden" name="buyer_id" value="<?php echo $info['info']['buyer_id']; ?>" id="buyer_id">
                        <input type="hidden" name="shipped_date" value="<?php echo $info['info']['shipped_date']; ?>" id="shipped_date">
                        <input type="hidden" name="platform_code" value="<?php echo $info['info']['platform_code']; ?>" id="platform_code">
                         <input type="hidden" name="warehouse_id" value="<?php echo $info['info']['warehouse_id']; ?>" id="warehouse_id">
                          <input type="hidden" name="account_id" value="<?php echo $info['info']['account_id']; ?>" id="account_id">
                        <table class="table" >    
                            <tbody>
                                <?php if (!empty($complain)) { ?>
                                    <tr>
                                        <th style="width: 100px;text-align: -webkit-center">已登记客诉</th>
                                        <td>
                                            <?php foreach ($complain as $key => $vo) { ?>   
                                                <a _width="100%" _height="100%" class="edit-button" href="<?php echo Url::toRoute(['getcompain', 'complaint_order' => $vo->complaint_order]); ?>" ><?php echo $vo->complaint_order ?></a><?php
                                                if ($key != (count($complain) - 1)) {
                                                    echo ",";
                                                }
                                                ?>
                                            <?php } ?>  
                                        </td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <th style="width: 100px;text-align: -webkit-center">状态</th>
                                    <td>待审核</td>
                                </tr>
                                <tr>
                                    <th style="width: 100px;text-align: -webkit-center">加急</th>
                                    <td>
                                        <input name="is_expedited" type="radio" class="redirect-input" value="0" checked>不加急 &nbsp;&nbsp;&nbsp;<input name="is_expedited" type="radio" class="redirect-input" value="1">加急
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
                                                <option value="<?php echo $vo->name; ?>"><?php echo $vo->name; ?></option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="width: 100px;text-align: -webkit-center">详情描述</th>
                                    <td>    
                                        <textarea rows="4" cols="12" name="description" style="    width: 502px;
                                                  height: 125px;" id="description"></textarea>    
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

                            <?php foreach ($info['product'] as $vo) { ?>
                                <tr>
                                    <td><input name="id[]"  value="<?php echo $vo['id']; ?>" type="checkbox" class="sel"></td>
                                    <td>
                                        <span style="color:#a96a6a">名称:</span><?php echo $vo['picking_name']; ?><input type="hidden" name="title[<?php echo $vo['id']; ?>][]" value="<?php echo $vo['picking_name']; ?>" class="picking_name"/><br/>
                                        <span style="color:#a96a6a">SKU:</span><?php echo $vo['sku']; ?><input type="hidden" name="sku[<?php echo $vo['id']; ?>][]" value="<?php echo $vo['sku']; ?>" class="sku"/><br/>
                                        <span style="color:#a96a6a">产品线:</span><?php echo $vo['linelist_cn_name']; ?><input type="hidden" name="product_line[<?php echo $vo['id']; ?>][]" value="<?php echo $vo['linelist_cn_name']; ?>" class="linelist_cn_name"/>
                                    </td>
                                    <td><input type="text" style="width:30px" name="qty[<?php echo $vo['id']; ?>][]" value="<?php echo $vo['quantity']; ?>" class="qty" id="onebu_<?php echo $vo['id']; ?>"/></td>
                                    <td>
                                        <?php
                                        echo $form->field($model, 'img_url', ['labelOptions' => ['class' => 't-r-pd5'], 'options' => ['class' => '']])->widget('manks\FileInput', [
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
<script>
      <?php foreach ($info['product'] as $vo) { ?>
          $("#onebu_<?php echo $vo['id']; ?>").blur(function(){
              var quantity='<?php echo $vo['quantity']; ?>';
              var  qty= $("#onebu_<?php echo $vo['id']; ?>").val();
               if(qty>quantity){
                  layer.tips('数量要小于等于实际购买数量', '#onebu_<?php echo $vo['id']; ?>', {
                  tips: [1, '#3595CC'],
                  time: 4000
                })
                $("#onebu_<?php echo $vo['id']; ?>").val(quantity);
               }       
          });
     <?php } ?>
    
    function Submits() {
        var buyer_id = $("#buyer_id").val();
        var order_id = $("#order_id").val();
        var account_id=$('#account_id').val();
        var warehouse_id=$("#warehouse_id").val();
        var platform_order_id = $("#platform_order_id").val();
        var shipped_date = $("#shipped_date").val();
        var platform_code = $("#platform_code").val();
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
        var url = '<?php echo Url::toRoute(['/aftersales/complaint/getsave']); ?>'
        $.ajax({
            url: url,
            type: 'post',
            data: {'data': data,
                'is_expedited': is_expedited,
                'description': description,
                'type': type,
                'buyer_id': buyer_id,
                'order_id': order_id,
                'platform_order_id': platform_order_id,
                'shipped_date': shipped_date,
                'platform_code': platform_code,
                'warehouse_id':warehouse_id,
                'account_id':account_id
            },
            dataType: "json",
            success: function (data) {
                if (data.state == 1) {
                    layer.msg(data.msg, {icon: 1});
                    window.location.reload();
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