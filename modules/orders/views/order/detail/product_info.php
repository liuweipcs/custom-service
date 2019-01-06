<div class="col-md-12" style="margin-top:10px;">
    <div class="panel panel-primary">
        <div class="panel-body">
            <div class="row">
                <div class="col-xs-12">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>序号</th>
                                <th>产品中文名称</th>
                                <th>绑定的SKU</th>
                                <th>数量</th>
                                <th>发货SKU</th>
                                <th>发货数量</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($info['product'])) {
                                foreach ($info['product'] as $k => $val) {
                                ?>
                                    <tr>
                                        <td><?php echo $k+1;?></td>
                                        <td><?php echo isset($val['picking_name']) ? $val['picking_name'] : "-";?></td>
                                        <td><?php echo isset($val['sku_old']) ? $val['sku_old'] : "-";?></td>
                                        <td><?php echo isset($val['quantity_old']) ? $val['quantity_old'] : 0;?></td>
                                        <td><?php echo isset($val['sku']) ? $val['sku'] : "-";?></td>
                                        <td><?php echo isset($val['quantity']) ? $val['quantity'] : 0;?></td>
                                    </tr>
                            <?php }
                                } else { ?>
                                <tr><td colspan="6" style="text-align: center;">暂无数据...</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>