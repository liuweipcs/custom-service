<?php

use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
use yii\widgets\LinkPager;

$this->title = '拆单';
?>
<style type="text/css">
    .order_separate_table th,.order_separate_table td
    {
        border:1px solid #A2B8CE;
        width: 150px;
        text-align: center;
        height: 23px;
        line-height: 23px;
    }
    .order_separate_table button
    {
        width: 100%;
    }
    .add_order_sku_content
    {
        display: none;
        position:absolute;
    }
    .add_order_sku_content > li
    {
        background-color: #5CF8AA;
        height: 23px;
        width: 150px;
        text-align: center;
    }
    .add_order_sku_content > li:hover
    {
        background-color:#FF8080;
        cursor:pointer;
    }
    .order_separate_child
    {
        margin-top: 30px;
    }
    .delete_child_order
    {
        border: 1px solid #FFFFFF;
        background-color: #FFFFFF;
        color: red;
        width: 23px;
        height: 23px;
        line-height: 23px;
        float: right;
    }
    .label_colspan_4
    {
        height: inherit;
        float: left;
    }
    .order_warehouse,.label_colspan_4
    {
        margin-top: 7px;
    }
    .order_separate_store
    {
        display: none;
    }
    .delete_child_order
    {
        cursor: pointer;
    }
</style>
<script type="application/javascript">
    $(function(){
        function orderSeparateTableInit() {
            var order = $.trim($('#parent_order_id').text());
            $('.order_separate_table').each(function (i) {
                var child_order = order + '_' + (i + 1);
                var $this = $(this);
                var childOrderCode = $this.find('b.child_order_code');
                childOrderCode.children('input').val(child_order);
                childOrderCode.children('span').html(child_order);
                $this.children('tbody').children('tr').each(function (j) {
                    if($(this).children('td').length > 1)
                    {
                        var skuCell = $(this).children('td:eq(1)');
                        var sku = $.trim(skuCell.text());
                        var detailId = skuCell.children('.vc_ids').val();
                        //skuCell.children('input[name^=OrderDetail]').attr('name','OrderDetail['+child_order+']['+detailId+'][id]');
                        $(this).find('input.order_sku_quantity').attr('name','OrderDetail['+child_order+']['+detailId+'][quantity]');
                    }
                    else
                    {
                        $(this).find('select').attr('name','Order['+child_order+'][ship_code]');
                    }
                });
            });
        }
        //显示漂浮菜单
        $('.pageFormContent').delegate('.add_order_sku_area','mouseover',function(){
            $(this).children('.add_order_sku_content').show();
        });
        //隐藏漂浮菜单
        $('.pageFormContent').delegate('.add_order_sku_area','mouseout',function(){
            $(this).children('.add_order_sku_content').hide();
        });
        //漂浮菜单点击事件
        $('.pageFormContent').delegate('.add_order_sku_content>li','click',function(){
            var $this = $(this);
            var separateTableObj = $this.parents('.order_separate_table');
            var orderId = separateTableObj.find('[name="Order[order_id][]"]').val();
            var sku = $.trim($this.text());
            var detailId = $this.attr('detailid');
            var itemId = $this.attr('item_id');
            var insertTrHtml = '<tr><td><button type="button" class="delete_order_sku_btn">删除</button></td><td>'+sku+'<input type="hidden" name="id[]" value="' + detailId + '" /><input type="hidden" name="item_id[]" value="' + itemId + '" /></td><td>'+itemId+'</td><td>'+$.trim($this.attr('quantity'))+'</td><td><input type="text" sku="'+sku+'" class="order_sku_quantity textInput" name="OrderDetail['+orderId+']['+detailId+'][quantity]"></td></tr>';
            separateTableObj.children('tbody').children('tr:last').before(insertTrHtml);
            $this.remove();
        });
        //删除sku
        $('.pageFormContent').delegate('.delete_order_sku_btn','click',function () {
            var $this = $(this);
            var deleteTrObj = $this.parents('tr');
            if(deleteTrObj.siblings().length <= 1)
            {
                layer.msg('不能删除，至少保留一个产品。', {icon: 5});
                return;
            }
            if(window.confirm('确定要删除此产品？'))
            {
                var skuAndId = deleteTrObj.children('td:eq(1)');
                $this.parents('.order_separate_table').find('.add_order_sku_content').append('<li item_id='+skuAndId.children('input[name=item_id\\[\\]]').val()+' detailid="'+skuAndId.children('.vc_ids').val()+'" quantity="'+$.trim(deleteTrObj.children('td:eq(3)').text())+'">'+$.trim(skuAndId.text())+'</li>');
                deleteTrObj.remove();

            }
        });

        var k=0;
        //添加子订单
        $('.add_child_order').click(function(){
            k+=1;

            var code=$('#pla_code').val();
            var id=$('#ord_id').val();
            var clone= $('.order_separate_store>.order_separate_table').clone();
            $.post('/aftersales/domesticreturngoods/changesnum',{'id':id,'code':code},function (data) {
                if(data){
                    for (var i=0;i<=data.num;i++){
                        var msg=data.msg[i]-k;
                        if (msg>0){
                            $('#tds_1_'+i).val(msg);
                            clone.children().find('#tds_1_'+i).val('1');
                        }else {
                            $('#tds_1_'+i).val('1');
                            clone.children().find('#tds_1_'+i).val('');
                        }
                    }
                }else{

                }
            },'json')
            $(this).before($('<div class="col-lg-12 order_separate_child"></div>').append(clone));
            orderSeparateTableInit();
            $(".sb").each(function (i) {
                console.log(i+$(this).attr('name')+$(this).val());
            });
        });
        //删除子订单
        $('.pageFormContent').delegate('.delete_child_order','click',function(){
            var deleteOrderObj = $(this).parents('.order_separate_child');
            if(deleteOrderObj.siblings('.order_separate_child').length < 1)
            {
                layer.msg('不能删除此订单，至少保留一个子订单。', {icon: 5});
                return;
            }
            if(window.confirm('确定删除此订单？'))
            {
                deleteOrderObj.remove();
                orderSeparateTableInit();
            }
        });
        //发货数失去焦点
        $('.pageFormContent').delegate('.order_sku_quantity','blur',function(){
            var $this = $(this);
            var quantityV = $this.val();

            if(quantityV.length > 0)
            {
                if(quantityV == 0)
                {
                    layer.msg('不能输入0，可以删除此产品。', {icon: 5});
                    $this.val('');
                    return;
                }
                if(!(/^[1-9]\d*$/.test(quantityV)))
                {
                    layer.msg('只能录入整数', {icon: 5});
                    $this.val('');
                    return;
                }
                var totalQuantity = parseInt($this.parents('td').prev().text());
                if(quantityV > totalQuantity)
                {
                    layer.msg('发货数不能大于总数', {icon: 5});
                    $this.val('');
                    return;
                }

                var sku = $this.attr('sku');
                var itemId = $this.attr('item_id');
                var quantityObj = $('input[item_id="'+itemId+'"]');
                var addQuantity = 0;
                for( var i=0;i<quantityObj.length;i++)
                {
                    addQuantity += $(quantityObj[i]).val() - 0;
                }
                if(addQuantity > totalQuantity)
                {
                    layer.msg('此产品的发货数总数不能大于总数', {icon: 5});
                    $this.val('');
                    return;
                }
            }
        });
    });
</script>
<div class="container">
    <form action="<?php echo Url::toRoute(['/aftersales/domesticreturngoods/splitorder',
    ]); ?>" method="post" role="form" class="form-horizontal">
    <div class="row pageFormContent">

        <div class="pd5">
            <div class="col-lg-12">
                <label for="OrderAliexpress_order_id">订单号</label>
                <input name="OrderAliexpress[order_id]" id="OrderAliexpress_order_id" type="hidden" value="<?php echo $model->order_id;?>">
                <div class="textInput" style="width:324px" id="parent_order_id"><?php echo $model->order_id?></div>
                <input type="hidden" name="platform_code_v" id="pla_code" value="<?php echo $model->platform_code?>">
                <input type="hidden" name="order_id_v" id="ord_id" value="<?php echo $model->order_id?>">
                <input type="hidden" name="separate_id" id="separate_id" value="<?php echo $mode->separate_id?>">
            </div>
            <div class="col-lg-12 order_separate_child">

                <?php ob_start();?>
                <table class="order_separate_table">
                    <thead>
                    <tr>
                        <th colspan="7" style="background-color: #448CCB;color:#FFFFFF">
                            <b>子订单号：</b>
                            <b class="child_order_code">
                                <?php
                                $shipname= '';//UebModel::model("Logistics")->getLogisticsName($model->ship_code);
                                $maxweight= 0;//UebModel::model("Logistics")->getmaxweightByShipCode($model->ship_code);
                                //$modelDetails = $model->detail;

                                $addOrderSkuContent = '';
                                $skuTrHtml = '';
                                $childOrder = $model->order_id.'_1';
                                $Totalweight = 0;
                                if(!empty($modelDetails)) {
                                    foreach ($modelDetails as $k => $modelDetail) {
                                        //过滤忽略发货的sku
                                        if ($modelDetail->ignore_shipment == 1) continue;

                                        $count = 'tds_' . '1_' . $k;

                                        $productWeight = 0;//round(UebModel::model('Product')->getPackWeightDBySku($modelDetail->sku),3);
                                        //$inventorydatas= 0;//Order::getOrderModel($modelDetail->platform_code)->getNumsBySku($modelDetail->sku,$warehouseModel->warehouse_code);
                                        $inventory = true;//$inventorydatas['available_stock'];
                                        if (empty($inventory)) {
                                            $inventory = 0;
                                        }

                                        $last_price = 0;//UebModel::model("Product")->getLastpriceBySku($modelDetail->sku);

                                        $Totalweight = $Totalweight + round($productWeight * $modelDetail->quantity, 3);
                                        $addOrderSkuContent .= "<li detailid='{$modelDetail->id}' quantity='{$modelDetail->quantity}'>{$modelDetail->sku}</li>";
                                        $skuTrHtml .= "<tr><td><button type=\"button\" class=\"delete_order_sku_btn\">删除</button></td><td>{$modelDetail->sku}<input type=\"hidden\" class=\"vc_ids\" name=\"id[]\" value=\"{$modelDetail->id}\" /><input type=\"hidden\" name=\"item_id[]\" value=\"{$modelDetail->item_id}\" /></td><td>{$modelDetail->item_id}</td><td>{$modelDetail->quantity}</td><td><input id='{$count}' type='text' class='order_sku_quantity' item_id='{$modelDetail->id}' name='OrderDetail[{$childOrder}][{$modelDetail->id}][quantity]' oninput='calculatedWeight(this,this.value);'/></td><td><b>{$inventory}</b></td><td><b>{$productWeight}</b>g</td></tr>";
                                    }
                                }
                                echo '<input type="hidden" class="sb" name="Order[order_id][]" value="'.$childOrder.'"/>';
                                echo '<span>'.$childOrder.'</span>';
                                ?>
                            </b>
                            <span class="delete_child_order">X</span>
                        </th>
                    </tr>
                    <tr>
                        <th>
                            <span style="position: relative;" class="add_order_sku_area">
                                    <button type="button" class="add_order_sku_btn">添加</button>
                                    <ul class="add_order_sku_content">
                                    </ul>
                            </span>
                        </th>
                        <th>SKU</th>
                        <th>Item ID</th>
                        <th>总数</th>
                        <th>发货数</th>
                        <th>可用库存</th>
                        <th>产品重量</th>

                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $skuTrHtml;?>
                    <tr>
                        <td colspan="2">发货仓库：<b><?php echo $warehouseModel[$model->warehouse_id];?></b></td>
                        <td>总重量：<b><?php echo $Totalweight;?></b>g</td>
                        <td>此渠道发货限制重量：<b><?php echo $maxweight;?></b>g</td>
                        <td colspan="2">发货渠道：<b><?php echo $shipname;?></b></td>
                        <td>总成本：<b><?php echo $last_price;?></b></td>

                    </tr>

                    </tbody>
                </table>
                <?php
                $orderSeparateTable = ob_get_contents();
                ob_end_clean();
                echo $orderSeparateTable;
                ?>
            </div>
            <button type="button" class="add_child_order">添加子订单</button>
        </div>

    </div>
    <div class="modal-footer">
        <button class="btn btn-primary ajax-submit"
                type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
        <button class="btn btn-default close-button" type="button"><?php echo Yii::t('system', 'Close'); ?></button>
    </div>

    </form>
    <div class="order_separate_store">
        <?php echo $orderSeparateTable?>
    </div>
</div>
<script type="text/javascript">
    function calculatedWeight(trsing) {
        var tbody =  $(trsing).parent().parent().parent();
        var weight = 0;
        tbody.find("tr").each(function(){
            var sontr = $(this).children();
            var number = sontr.eq(4).find("input").val();

            if(number) {
                var c = sontr.eq(6).find("b").text();
                weight =  weight + (c * number);
            }
        });
        tbody.find('tr').last('tr').children('td').eq(1).find("b").text();
        tbody.find('tr').last('tr').children('td').eq(1).find("b").text(weight);
    }
</script>