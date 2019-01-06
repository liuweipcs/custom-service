<?php

use app\modules\orders\models\Order;
use yii\helpers\Url;
use kartik\select2\Select2;

?>
<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseFive"><h4 class="panel-title">仓储&物流</h4></a>
    </div>
    <div id="collapseFive" class="panel-collapse collapse in">

        <div id="menu5" class="tab-pane">
            <div id="warehouse-box">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>物流单号</th>
                        <th>发货仓库:</th>
                        <th>邮寄方式</th>
                    </tr>
                    </thead>
                    <tbody id="wareh_logistics">
                    <?php if (!empty($info['wareh_logistics'])) { ?>
                        <tr>
                            <td><?php echo $info['info']['track_number'] ?></td>
                            <td><?php echo isset($info['wareh_logistics']['warehouse']['warehouse_name']) ?
                                    $info['wareh_logistics']['warehouse']['warehouse_name'] : ''; ?></td>
                            <td><?php echo isset($info['wareh_logistics']['logistics']) ?
                                    $info['wareh_logistics']['logistics']['ship_name'] : ''; ?></td>
                        </tr>
                    <?php } else { ?>
                        <tr>
                            <td colspan="2" align="center">没有找到信息！</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <?php if ($is_return == 1 || ($info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP)) { ?>
                    <div><a id="edit-warehouse-button" href="javascript:void(0);">编辑仓库物流</a></div>
                <?php } else { ?>
                    <?php if ($info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) { ?>
                        <div><a id="edit-warehouse-button" href="javascript:void(0);">编辑仓库物流</a></div>
                    <?php } ?>
                <?php } ?>
            </div>
            <div id="warehouse-edit-box" style="display:none;">
                <form action="<?php echo Url::toRoute(['/orders/order/editorderwarehouse',
                    'platform'     => $info['info']['platform_code'],
                    'order_id'     => $info['info']['order_id'],
                    'is_return'    => $is_return,
                    'returnid'     => $returnid,
                    'track_number' => $track_number
                ]); ?>" method="post" role="form">
                    <br/>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label for="warehouse_id" class="col-sm-3 control-label required">发货仓库<span
                                            class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <?php
                                        echo Select2::widget([
                                            'id' => 'warehouse_id_list',
                                            'name' => 'warehouse_id',
                                            'data' => $warehouseList,
                                            'value' => $info['info']['warehouse_id']
                                        ]);
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label for="ship_code" class="col-sm-3 control-label">邮寄方式<span
                                            class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <?php
                                        echo Select2::widget([
                                            'id' => 'ship_code_list',
                                            'name' => 'ship_code',
                                            'data' => array(),
                                            'value' => $info['wareh_logistics']['logistics']['ship_name']
                                        ]);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br/>
                    <div class="popup-footer">
                        <button class="btn btn-primary ajax-submit" type="button">保存</button>
                        <button class="btn btn-default" id="warehouse-cancel-button" type="button">取消</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $('a#edit-warehouse-button').click(function () {
        $('div#warehouse-box').hide();
        $('div#warehouse-edit-box').show();
        var warehouse_id = $("select[name=warehouse_id]").val();
        var ship_code = '<?php echo $info['wareh_logistics']['logistics']['ship_code'];?>';
        var url = '<?php echo Url::toRoute(['/orders/order/getlogistics']);?>';
        $.get(url, 'warehouse_id=' + warehouse_id, function (data) {
            var html = '';
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }
            if (typeof(data.data) != 'undefined') {
                var logistics = data.data;
                console.log(logistics);
                for (var i in logistics) {
                    $("#ship_code").val(logistics[i]);
                    break;
                }
                var sel = '';
                for (var i in logistics) {
                    if(ship_code == i){
                        sel = 'selected="selected"';
                    }else{
                        sel = '';
                    }
                    html += '<option value="' + i + '"'+sel+'>' + logistics[i] + '</option>' + "\n";

                }
            }
            $('select[name=ship_code]').empty().html(html);
        }, 'json');
    });
    $('button#warehouse-cancel-button').click(function () {
        $('div#warehouse-box').show();
        $('div#warehouse-edit-box').hide();
    });

    //根据仓库获取物流
    $("select[name='warehouse_id']").on("change", function() {
        var warehouseId = $(this).val();
        var warehouse_name = $(this).find("option:selected").text();
        $("#warehouse_name").val(warehouse_name);
        var url = '<?php echo Url::toRoute(['/orders/order/getlogistics']);?>';
        $.get(url, 'warehouse_id=' + warehouseId, function (data) {
            var html = '';
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }
            if (typeof(data.data) != 'undefined') {
                var logistics = data.data;
                for (var i in logistics) {
                    html += '<option value="' + i + '">' + logistics[i] + '</option>' + "\n";
                }
            }
            $('select[name=ship_code]').empty().html(html);
        }, 'json');
    });

    //根据仓库获取物流
    function getLogistics(obj) {
        var warehouseId = $(obj).val();
        var warehouse_name = $(obj).find("option:selected").text();
        $("#warehouse_name").val(warehouse_name);
        var url = '<?php echo Url::toRoute(['/orders/order/getlogistics']);?>';
        $.get(url, 'warehouse_id=' + warehouseId, function (data) {
            var html = '';
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }
            if (typeof(data.data) != 'undefined') {
                var logistics = data.data;
                console.log(logistics);
                for (var i in logistics) {
                    $("#ship_code").val(logistics[i]);
                    break;
                }
                for (var i in logistics) {
                    html += '<option value="' + i + '">' + logistics[i] + '</option>' + "\n";
                }
            }
            $('select[name=ship_code]').empty().html(html);
        }, 'json');
    }
</script>