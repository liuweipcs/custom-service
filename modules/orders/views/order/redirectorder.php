<?php
use yii\helpers\Url;
?>
<div class="popup-wrapper">
    <form action="<?php echo Url::toRoute(['/orders/order/redirectorder',
        'platform' => $info['info']['platform_code'],
        'order_id' => $info['info']['order_id'],
        ]);?>" method="post" role="form" class="form-horizontal" method="post">
    <div class="popup-body">
        <div class="panel-group" id="accordion">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion"href="#collapseOne">订单产品信息</a>
                    </h4>
                </div>
                <div id="collapseOne" class="panel-collapse collapse in">
                    <div class="panel-body">
                        <table id="product-table" class="table">
                            <thead>
                                <tr>
                                    <th>标题</th>
                                    <th>SKU</th>
                                    <th>数量</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($info['product'] as $row) { ?>
                                <tr>
                                    <td><input class="form-control" type="text" name="product_title[]" value="<?=$row['title'];?>" /></td>
                                    <td><input class="form-control" type="text" name="sku[]" size="12" value="<?=$row['sku'];?>" /></td>
                                    <td><input class="form-control" type="text" name="quantity[]" size="6" value="<?=$row['quantity'];?>" /></td>
                                    <td><a href="javascript:void(0)" id="delete-row-button">删除</a></td><td>
                                </tr>
                            <?php } ?>                          
                            </tbody>
                        </table>
                        <div><a href="javascript:void(0)" id="add-product-row">添加产品</a></div>
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">发货地址信息</a>
                    </h4>
                </div>
                <div id="collapseTwo" class="panel-collapse collapse">
                    <div class="panel-body">
                        <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="ship_name" class="col-sm-3 control-label required">收件人<span class="text-danger">*</span></label>
                                                <div class="col-sm-9">
                                                  <input type="text" name="ship_name" value="<?php echo $info['info']['ship_name'];?>" class="form-control" id="ship_name">
                                                </div>                                    
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="ship_street1" class="col-sm-3 control-label">地址1<span class="text-danger">*</span></label>
                                                <div class="col-sm-9">
                                                  <input type="text" name="ship_street1" value="<?php echo $info['info']['ship_name'];?>" class="form-control" id="ship_street1">
                                                </div>                                    
                                            </div>                                        
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="ship_street2" class="col-sm-3 control-label required">地址2</label>
                                                <div class="col-sm-9">
                                                  <input type="text" value="<?php echo $info['info']['ship_street2'];?>" name="ship_street2" class="form-control" id="ship_street2">
                                                </div>                                    
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="ship_city_name" class="col-sm-3 control-label">城市<span class="text-danger">*</span></label>
                                                <div class="col-sm-9">
                                                  <input type="text" value="<?php echo $info['info']['ship_city_name'];?>" name="ship_city_name" class="form-control" id="ship_city_name">
                                                </div>                                    
                                            </div>                                        
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="ship_stateorprovince" class="col-sm-3 control-label">省/州</label>
                                                <div class="col-sm-9">
                                                  <input type="text" value="<?php echo $info['info']['ship_stateorprovince'];?>" name="ship_stateorprovince" class="form-control" id="ship_stateorprovince">
                                                </div>                                    
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="ship_country" class="col-sm-3 control-label">国家<span class="text-danger">*</span></label>
                                                <div class="col-sm-9">
                                                    <select name="ship_country" id="ship_country" class="form-control">
                                                        <option value="">选择国家</option>
                                                        <?php foreach ($countries as $code => $name) { ?>
                                                        <option<?php echo $info['info']['ship_country'] == $code ? ' selected="selected"' : '';?> value="<?php echo $code;?>"><?php echo $name;?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>                                    
                                            </div>                                        
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="ship_zip" class="col-sm-3 control-label">邮编<span class="text-danger">*</span></label>
                                                <div class="col-sm-9">
                                                  <input type="text" value="<?php echo $info['info']['ship_zip'];?>" name="ship_zip" class="form-control" id="ship_zip">
                                                </div>                                    
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="ship_phone" class="col-sm-3 control-label">电话</label>
                                                <div class="col-sm-9">
                                                  <input type="text" value="<?php echo $info['info']['ship_phone'];?>" name="ship_phone" class="form-control" id="ship_phone">
                                                </div>                                    
                                            </div>                                        
                                        </div>
                                    </div>                    
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapseThr">仓库物流信息</a>
                    </h4>
                </div>
                <div id="collapseThr" class="panel-collapse collapse">
                    <div class="panel-body">
                         <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="warehouse_id" class="col-sm-3 control-label required">发货仓库<span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                    <select onchange="getLogistics(this)" class="form-control" name="warehouse_id">

                                    <?php foreach ($warehouseList as $warehouseId => $warehouseName) { ?>
                                        <option value="<?php echo $warehouseId;?>"<?php echo $info['info']['warehouse_id'] == $warehouseId ?
                                             ' selected="selected"' : '';?>><?php echo $warehouseName;?></option>
                                    <?php } ?>
                                    </select>
                                    </div>                                    
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="ship_code" class="col-sm-3 control-label">邮寄方式<span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <select class="form-control" name="ship_code">
                                        <?php foreach ($logistics as $logistic) { ?>
                                            <option value="<?php echo $warehouseId;?>"<?php echo $info['info']['ship_code'] == $logistic->ship_code ?
                                                ' selected="selected"' : '';?>><?php echo $logistic->ship_name;?></option>
                                        <?php } ?>                                   
                                        </select>
                                    </div>                                    
                                </div>                                        
                            </div>
                        </div>                   
                    </div>
                </div>
            </div>            
        </div>
    </div>
    <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
     </div>
    </form>     
</div>
<script type="text/javascript">
$('a#add-product-row').click(function(){
	var html = '<tr>' + "\n" +
    '<td><input class="form-control" type="text" name="product_title[]" value="" /></td>' + "\n" +
    '<td><input class="form-control" type="text" name="sku[]" size="12" value="" /></td>' + "\n" +
    '<td><input class="form-control" type="text" name="quantity[]" size="6" value="1" /></td>' + "\n" +
    '<td><a href="javascript:void(0)" id="delete-row-button">删除</a></td>' + "\n" +
    '<tr>' + "\n";
    $('table#product-table tbody').append(html);
    $('a#delete-row-button').click(function(){
        $(this).parents('tr').remove();
    });
});
$('a#delete-row-button').click(function(){
    $(this).parents('tr').remove();
});
//根据仓库获取物流
function getLogistics(obj)
{
    var warehouseId = $(obj).val();
    var url = '<?php echo Url::toRoute(['/orders/order/getlogistics']);?>';
    $.get(url, 'warehouse_id=' + warehouseId, function(data){
        var html = '';
        if (data.code != '200')
        	if (data.code != '200') {
        		layer.alert(data.message, {
        			icon: 5
        		});
        		return;
        	}
    	if (typeof(data.data) != 'undefined')
    	{
        	var logistics = data.data;
    	    for (var i in logistics)
    	    {
 	    	   html += '<option value="' + i + '">' + logistics[i] + '</option>' + "\n";
            }
        }
        $('select[name=ship_code]').empty().html(html);
    }, 'json');
}
</script>