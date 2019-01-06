<?php

use app\modules\orders\models\Order;
use yii\helpers\Url;

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
                            <td colspan="3" align="center">没有找到信息！</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <?php if (isset($info['info']['complete_status']) && ($info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP)) { ?>
                    <div><a id="edit-warehouse-button" href="javascript:void(0);">编辑仓库物流</a></div>
                <?php } ?>
            </div>
            <div id="warehouse-edit-box" style="display:none;">
                <form action="<?php echo Url::toRoute(['/orders/order/editorderwarehouse',
                    'platform' => !empty($info['info']['platform_code']) ? $info['info']['platform_code'] : '',
                    'order_id' => !empty($info['info']['order_id']) ? $info['info']['order_id'] : '',
                ]); ?>" method="post" role="form">
                    <br/>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label for="warehouse_id" class="col-sm-3 control-label required">发货仓库<span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <select onchange="getLogistics(this)" class="form-control" name="warehouse_id">
                                        <option value="">选择仓库</option>
                                        <?php foreach ($warehouseList as $warehouseId => $warehouseName) { ?>
                                            <option value="<?php echo $warehouseId; ?>"<?php //echo $info['info']['warehouse_id'] == $warehouseId ?
                                            //' selected="selected"' : '';?>><?php echo $warehouseName; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label for="ship_code" class="col-sm-3 control-label">邮寄方式<span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="ship_code">
                                    </select>
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
    });
    $('button#warehouse-cancel-button').click(function () {
        $('div#warehouse-box').show();
        $('div#warehouse-edit-box').hide();
    });
</script>