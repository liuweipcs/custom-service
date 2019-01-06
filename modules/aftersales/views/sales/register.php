<?php
use yii\helpers\Url;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\accounts\models\Account;
use kartik\select2\Select2;
$this->registerJsFile(Url::base() . '/js/multiselect.js');
?>
<div class="popup-wrapper">
    <form action="<?php echo Url::toRoute(['/aftersales/sales/register',
        'platform' => $info['info']['platform_code'],
        'order_id' => $info['info']['order_id'],
    ]);?>" method="post" role="form" class="form-horizontal" >
        <div class="popup-body">
            <div class="row">
                <div class="col-sm-5">
                    <div class="panel panel-default">
                        <?php
                        echo $this->render('order_info',['info'=>$info,'isAuthority'=> $isAuthority,'accountName' => $accountName]);
                        echo $this->render('../order/transaction_record',['info'=>$info,'paypallist' => $paypallist]);//交易记录
                        echo $this->render('../order/package_info',['info'=>$info]);//包裹信息
                        echo $this->render('../order/logistics',['info'=>$info,'warehouseList'=>$warehouseList]);//仓储物流
                        echo $this->render('../order/aftersales',['afterSalesOrders'=>$afterSalesOrders]);//售后问题
                        echo $this->render('../order/log',['info'=>$info]);//操作日志
                        ?>
                    </div>
                </div>
                <div class="col-sm-7">
                    <div class="panel panel-default">
                            <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">问题产品</h3>
                        </div>
                        <div class="panel-body">
                            <table id="issue-product" class="table">
                                <thead>
                                <tr>
                                    <th width="50%">标题</th>
                                    <th width="20%">SKU</th>
                                    <th width="10%">数量</th>
                                    <th width="10%">产品线</th>
                                    <th width="10%">问题产品</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (isset($info['product']) && !empty($info['product'])){
                                    $count = count($info['product']);
                                    foreach ($info['product'] as $product)
                                    {
                                        ?>
                                        <tr>
                                        <td><?php echo $product['picking_name'];?></td>
                                        <td><?php echo $product['sku'];?></td>
                                        <td><?php echo $product['quantity'];?></td>
                                        <td><?php echo $product['linelist_cn_name'];?></td>
                                        <td><input class="form-control col-lg-4" type="text" size="4" name="issue_product[<?php echo $product['sku'];?>]" value="<?php echo ($count <= 1) ? $product['quantity'] : "";?>" /></td>
                                        <?php
                                    }
                                }else{ ?>
                                    <tr><td colspan="5">未找到相关数据</td></tr>
                                <?php }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                        <div class="panel-heading">
                            <h3 class="panel-title">原因</h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <div class="col-sm-3">
                                            <label for="ship_name" class=" control-label required">责任所属部门：<span class="text-danger">*</span></label>
                                            <select name="department_id" id="department_id" class="form-control" size="12" multiple="multiple">
                                            </select>
                                        </div>
                                        <div class="col-sm-9">
                                            <label for="ship_name" class="control-label required">原因类型：<span class="text-danger">*</span></label>
                                            <select name="reason_id" id="reason_id" class="form-control" size="12" multiple="multiple">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="ship_street1" class="col-sm-1 control-label">备注：</label>
                                        <div class="col-sm-11">
                                            <textarea rows="4" cols="12" name="remark" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">售后单类型</h3>
                        </div>
                        <div class="panel-body">
                            <label class="checkbox-inline">
                                <input name="after_sales_type" type="checkbox" checked="checked" onclick="return false;" value="<?php echo AfterSalesOrder::ORDER_TYPE_REFUND ;?>">退款
                            </label>
                        </div>
                    </div>

                    <?php $account_info = Account::find()->where(['platform_code'=>$info['info']['platform_code'],'old_account_id'=>$info['info']['account_id']])->one();?>
                    <input type="hidden" name="account_name" value="<?php echo isset($account_info->account_name) ? $account_info->account_name: '';?>">
                    <input type="hidden" name="buyer_id" value="<?php echo $info['info']['buyer_id']?>">
                    <div class="panel panel-default" id="refund-box">
                        <div class="panel-heading">
                            <h3 class="panel-title">退款信息</h3>
                        </div>
                        <div class="panel-body">
                            <?php if(!empty($reasonCodeList)){?>
                                <div class="row" style="display:<?php echo $platform == 'ALI' ? 'none' : 'black';?>">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label for="ship_street1" class="col-sm-2 control-label">平台退款原因：<span class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select class="form-control" name="reason_code">
                                                    <?php foreach($reasonCodeList as $code => $reason_text){?>
                                                        <option value="<?php echo $code;?>"><?php echo $reason_text;?></option>
                                                    <?php }?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                            <div class="row">
                                <div class="col-sm-10">
                                    <div class="form-group">
                                        <label for="ship_name" class="col-sm-3 control-label required">退款金额：<span class="text-danger">*</span></label>
                                        <div class="col-sm-2">
                                            <input class="form-control" type="text" name="refund_amount" value="<?php echo $allow_refund_amount;?>" />
                                        </div>
                                        <div class="col-sm-2">
                                            <input class="form-control" type="text" disabled="disabled" name="currency_code" value="<?php echo $currencyCode;?>" />
                                        </div>
                                        <div>
                                            <label class="control-label" style="color:red">可退款金额：<span><?php echo $allow_refund_amount;?></span></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-10">
                                    <div class="form-group">
                                        <a class="col-sm-3 control-label" id="figure_refund_lost">
                                            计算亏损(负数即为亏损)：
                                        </a>
                                        <div class="col-sm-9">
                                            <b style="color:red" id="refund_lost"></b>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-10">
                                    <div class="form-group">
                                        <label for="ship_street1" class="col-sm-3 control-label">订单留言：<span class="text-danger">&nbsp;</span></label>
                                        <div class="col-sm-9">
                                            <textarea rows="6" cols="12" name="message" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <?php echo $this->render('../order/profit',['info'=>$info]);//利润?>
                </div>
            </div>
        </div>
        <div class="popup-footer">
            <input class="form-control" type="hidden" id="_token_" name="_token_" value="1" />
            <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
            <button class="btn btn-default close-button" type="button"><?php echo Yii::t('system', 'Close');?></button>
        </div>
    </form>
</div>
<script type="text/javascript">
    $(document).ready(function($) {
        departmentList = <?php echo $departmentList?>;
        var rightHtml="";
        for (var i in departmentList)
        {
            rightHtml += '<option value="' + departmentList[i].depart_id + '">' + departmentList[i].depart_name + '</option>' + "\n";
        }
        $('#department_id').empty().html(rightHtml);
    });

    $("#figure_refund_lost").on('click',function(){
        var order_id = '<?php echo $info['info']['order_id'];?>'
        var platform_code = '<?php echo $info['info']['platform_code'];?>'
        var refund_amount = $("input[name=refund_amount]").val();
        var url = '<?php echo Url::toRoute(['/aftersales/order/getrefundlost']);?>';
        $.get(url, {"platform_code":platform_code,"order_id":order_id,"refund_amount":refund_amount}, function(data){
            var html = '';
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }
            else {
                $('#refund_lost').html(data.data);
            }

        }, 'json');
    });
    //根据仓库获取物流
    function getLogistics(obj)
    {
        var warehouseId = $(obj).val();
        var warehouse_name = $(obj).find("option:selected").text();
        $("#warehouse_name").val(warehouse_name);
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
                    $("#ship_code_name").val(logistics[i]);
                    break;
                }
                for (var i in logistics)
                {
                    html += '<option value="' + i + '">' + logistics[i] + '</option>' + "\n";
                }
            }
            $('select[name=ship_code]').empty().html(html);
        }, 'json');
    };

    //切换责任归属部门获取对应原因
    $(document).on("change", "#department_id", function () {
        var id = $(this).val();
        if (id) {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/aftersales/refundreason/getnetleveldata']); ?>',
                data: {'id': id},
                success: function (data) {
                    var html = "";
                    if (data) {
                        $.each(data, function (n, value) {
                            html += '<option value=' + n + '>' + value + '</option>';
                        });
                    } else {
                        html = '<option value="">---请选择---</option>';
                    }
                    $("#reason_id").empty();
                    $("#reason_id").append(html);
                }
            });
        } else {
            $("#reason_id").empty();
            $("#reason_id").append(html);
        }
    });
</script>