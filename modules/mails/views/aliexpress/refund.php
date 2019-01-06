<div class="popup-wrapper">
    <div class="popup-body">
        <form class="form-horizontal" role="form" id="refund_form">
            <div class="form-group">
                <label class="col-sm-2 control-label"><span style="color: red">*</span>类别：</label>
                <div class="col-sm-10">
                        <select class="form-control" style="width: 300px;" name="type" onchange="returnedType(this.value);">
                            <option value="1">选择类别</option>
                            <option value="1">退货</option>
                            <option value="2">退款</option>
                            <option value="3">退货/退款</option>
                        </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label"><span style="color: red">*</span>货币：</label>
                <div class="col-sm-10">
                    <select class="form-control" style="width: 300px;" name="currency">
                            <option value="AUD">AUD</option>
                            <option value="CAD">CAD</option>
                            <option value="CHF">CHF</option>
                            <option value="CNY">CNY</option>
                            <option value="DKK">DKK</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="HKD">HKD</option>
                            <option value="IDR">IDR</option>
                            <option value="JPY">JPY</option>
                            <option value="MOP">MOP</option>
                            <option value="MYR">MYR</option>
                            <option value="NOK">NOK</option>
                            <option value="NZD">NZD</option>
                            <option value="PHP">PHP</option>
                            <option value="SEK">SEK</option>
                            <option value="SGD">SGD</option>
                            <option value="THB">THB</option>
                            <option value="USD">USD</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label"><span style="color: red">*</span>是否退回：</label>
                <div class="col-sm-10">
                    <select class="form-control" style="width: 300px;" name="is_returned">
                        <option value="1" selected="selected">未退回</option>
                        <option value="2">已退回</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">运单号：</label>
                <div class="col-sm-10">
                    <input class="form-control" id="focusedInput" type="text"  style="width: 300px;" name="express_num">
                </div>
            </div>
            <div class="form-group product_sum">
                <label class="col-sm-2 control-label"><span style="color: red">*</span>退款金额：</label>
                <div class="col-sm-10">
                    <input class="form-control" id="focusedInput" type="text"  style="width: 300px;" name="refund_sum" >
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">原因：</label>
                <div class="col-sm-10">
                    <textarea class="form-control" id="focusedInput"  style="width: 300px;" name="reason" ></textarea>
                </div>
            </div>
            <div class="form-group" >
                <div class="col-sm-offset-2 col-sm-10" >
                    <button type="button" class="btn btn-default" onclick="undodelete();">撤销删除</button>
                    <button type="button" class="btn btn-default" onclick="Addproduct();">添加产品</button>
                    <table class="table" id="tatletab" style="display: none">
                        <thead>
                        <tr>
                            <th>SKU</th>
                            <th>产品名称</th>
                            <th>数量</th>
                            <th class="product_sum">产品金额</th>
                            <th class="product_sum">货币</th>
                            <th>原因</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody id="product">
                        <?php if(!empty($info['product'])){?>
                            <?php foreach ($info['product'] as $key=>$value){?>
                            <tr class="active" id="tr<?php echo $value['sku'];?>">
                            <td><input type="hidden" class="deleteInput" value="0" id="delete<?php echo $value['sku'];?>" name="product[<?php echo $key;?>][delete]"><input type="hidden" value="<?php echo $value['sku'];?>" name="product[<?php echo $key;?>][sku]" ><?php echo $value['sku'];?></td>
                            <td title="<?php echo $value['title'];?>"><input type="text" value="<?php echo $value['title'];?>" name="product[<?php echo $key;?>][title]" ></td>
                            <td><input type="text" value="<?php echo $value['quantity'];?>" name="product[<?php echo $key;?>][return_quantity]" style="width: 30px;"></td>
                            <td class="product_sum"><input type="text" value="<?php echo $value['total_price'];?>" name="product[<?php echo $key;?>][product_sum]" style="width: 50px;"></td>
                            <td class="product_sum"><input type="text" value="<?php echo $value['currency'];?>" name="product[<?php echo $key;?>][currency]" style="width: 40px;"></td>
                            <td><textarea class="form-control" rows="1" cols="1" name="product[<?php echo $key;?>][reason]" ></textarea></td>
                            <td><button type="button" class="btn btn-default" onclick="deletesku('<?php echo $value['sku'];?>');">删除</button></td>
                            </tr>
                            <?php }?>
                        <?php }else{?>
                            <tr><td colspan="2" align="center">没有找到产品信息！</td></tr>
                        <?php }?>
                        </tbody>
                    </table>

                </div>

            </div>

            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <input type="hidden" value="add" name="save"/>
                    <input type="hidden" value="<?php echo $order_id;?>" name="order_id"/>
                    <input type="hidden" value="<?php echo $platform;?>" name="platform_code"/>
                    <button type="button" class="btn btn-primary ajax-submit">保存</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
//    $(window).load(function() {
//        $.get("/mails/aliexpress/orderinfo",
//            {
//                order_id:'79774017137549',
//                platform:'ALI'
//            },
//            function(result){
//                var obj = eval('('+result+')');
//                if(obj.product){
//                    /*产品信息*/
//                    var product = '';
//                    if(obj.product){
//                        $.each(obj.product,function(m,value) {
//                            product += '<tr class="active">' +
//                                '<td><input type="hidden" value="'+value.sku+'" name="product['+m+'][sku]" >'+value.sku+'</td>' +
//                                '<td title="'+value.title+'"><input type="text" value="'+value.title+'" name="product['+m+'][title]" ></td>' +
//                                '<td><input type="text" value="'+value.quantity+'" name="product['+m+'][return_quantity]" style="width: 30px;"></td>' +
//                                '<td class="product_sum"><input type="text" value="'+value.total_price+'" name="product['+m+'][product_sum]" style="width: 50px;"></td>' +
//                                '<td class="product_sum"><input type="text" value="'+value.currency+'" name="product['+m+'][currency]" style="width: 40px;"></td>' +
//                                '<td><textarea class="form-control" rows="1" cols="1" name="product['+m+'][reason]" ></textarea></td>' +
//                                '<td><button type="button" class="btn btn-primary" onclick="deleteCustomAttributes(this);">删除</button></td>' +
//                                '</tr>';
//                        });
//                    }else {
//                        product +='<tr><td colspan="6" align="center">没有找到信息！</td></tr>';
//                    }
//                    $('#product').text('');
//                    $('#product').append(product);
//                }
//            });
//    });
    function Addproduct() {
        $('#tatletab').removeAttr('style');
        $('.deleteInput').val(0);
        $('.active').removeAttr('style');
    }
    function returnedType(type) {
          if(type == 1){
              $('.product_sum').css('display','none');
              $('#tatletab').css('display','none');
              $('.deleteInput').val(1);
              $('.active').removeAttr('style');
          }else {
              $('.product_sum').removeAttr('style');
              $('#tatletab').css('display','none');
              $('.deleteInput').val(1);
              $('.active').removeAttr('style');
          }
    }
//    function deleteCustomAttributes(nowTr){
//            $(nowTr).parent().parent().remove();
//    }
    function deletesku(sku){
        $('#tr'+sku).css('display','none');
        $('#delete'+sku).val(1);
    }
    function undodelete(sku){
        $('.deleteInput').val(0);
        $('.active').removeAttr('style');
    }
</script>