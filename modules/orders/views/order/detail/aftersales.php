<?php
    use app\modules\aftersales\models\AfterSalesOrder;
    use app\modules\aftersales\models\RefundReturnReason;
    use yii\helpers\Url;
    use app\modules\systems\models\BasicConfig;
?>
<div class="panel panel-default" style="margin-top: 5px;">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapse10"><h4 class="panel-title">售后信息</h4></a>
    </div>
    <div id="collapse10" class="panel-collapse collapse">
        <div class="panel-body" style=" height: auto; max-height:350px;overflow-y:visible;">
            <table class="table table-hover">
                <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>售后单号</th>
                            <th>售后类型</th>
                            <th>退款金额/重寄加钱金额</th>
                            <th>原因</th>
                            <th>状态</th>
                            <th>创建人</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (!empty($afterSalesOrders)){
                            foreach ($afterSalesOrders as $afterSalesOrder){ ?>
                                <tr>
                                    <td><?php
                                    if($afterSalesOrder['type'] == 1){
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id='.$afterSalesOrder['after_sale_id'].'&amp;platform_code=EB&amp;status=1" title="售后单详情">'.$afterSalesOrder['after_sale_id'].'</a>';
                                    }
                                        if($afterSalesOrder['type'] == 2){
                                            echo '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id='.$afterSalesOrder['after_sale_id'].'&amp;platform_code=EB&amp;status=1" title="售后单详情">'.$afterSalesOrder['after_sale_id'].'</a>';
                                        }
                                    if($afterSalesOrder['type'] == 3){
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id='.$afterSalesOrder['after_sale_id'].'&amp;platform_code=EB&amp;status=2" title="售后单详情">'.$afterSalesOrder['after_sale_id'].'</a>';
                                    }?>
                                    </td>
                                    <td><?php if($afterSalesOrder['type']==1){echo '退款';}elseif ($afterSalesOrder['type']==2){echo '退货';}elseif ($afterSalesOrder['type']==3){echo '重寄';};?></td>
                                    <td><?php echo ($afterSalesOrder['type'] == AfterSalesOrder::ORDER_TYPE_REFUND || $afterSalesOrder['type'] == AfterSalesOrder::ORDER_TYPE_REDIRECT)? $afterSalesOrder['refund_amount'].' '.$afterSalesOrder['currency']:"-";?></td>
                                    <td><?php
                                        if($afterSalesOrder['department_id']){
                                            echo  BasicConfig::getAllConfigData()[$afterSalesOrder['reason_id']];
                                        }else{
                                            echo RefundReturnReason::getReasonContent($afterSalesOrder['reason_id']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo AfterSalesOrder::getOrderStatusList($afterSalesOrder['status']);?></td>
                                    <td><?php echo $afterSalesOrder['create_by'];?></td>
                                    <td><?php echo $afterSalesOrder['create_time'];?></td>
                                    <td>
                                        <?php if ($afterSalesOrder['status'] == AfterSalesOrder::ORDER_STATUS_WATTING_AUDIT) { ?>
                                            <div class="btn-group btn-list">
                                                <button type="button" class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation');?></button>
                                                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                    <span class="caret"></span>
                                                    <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List');?></span>
                                                </button>
                                                <ul class="dropdown-menu" rol="menu">
                                                    <?php if($afterSalesOrder['type']==2){?>
                                                        <li><a style="cursor: pointer"
                                                               onclick="cancelReturn('<?= $data->after_sale_id ?>',3)">取消退货</a>
                                                        </li>
                                                    <?php }?>
                                                    <li><a class="ajax-button" href="<?php echo Url::toRoute(['/aftersales/order/audit',
                                                            'after_sales_id' => $afterSalesOrder['after_sale_id'],
                                                            'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED]);?>">审核通过</a></li>
                                                    <li><a class="ajax-button" href="<?php echo Url::toRoute(['/aftersales/order/audit',
                                                            'after_sales_id' => $afterSalesOrder['after_sale_id'],
                                                            'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]);?>">退回修改</a></li>
                                                    <li><a style="cursor: pointer"
                                                           onclick="deleteReturn('<?= $afterSalesOrder['after_sale_id'] ?>')">删除</a></li>
                                                </ul>
                                            </div>
                                        <?php } ?>

                                        <!--待收货 -->
                                        <?php if ($afterSalesOrder['is_receive']== 1) { ?>
                                            <div class="btn-group btn-list">
                                                <button type="button"
                                                        class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation'); ?></button>
                                                <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                                        data-toggle="dropdown">
                                                    <span class="caret"></span>
                                                    <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                                </button>
                                                <ul class="dropdown-menu" rol="menu">
                                                    <li><a style="cursor: pointer"
                                                           onclick="cancelReturn('<?= $afterSalesOrder['after_sale_id'] ?>',3)">取消退货</a></li>
                                                </ul>
                                            </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }else{ ?>
                                <tr><td colspan="8" style="text-align: center;">未找到售后相关信息...</td></tr>
                        <?php } ?>        
                        </tbody>
                    </table>
            </table>
        </div>
    </div>
</div>
<script>
    //取消退货
    function cancelReturn(after_sales_id, return_status) {
        layer.confirm('确定要取消收货吗？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: "/aftersales/sales/cancelreturn",
                type: "GET",
                data: {"after_sales_id": after_sales_id, 'return_status': return_status},
                dataType: "json",
                success: function (data) {
                    if (data.code == 200) {
                        location.href = location.href;
                        layer.msg(data.message, {time: 3000}, {icon: 6});
                    } else {
                        layer.msg(data.message, {icon: 5});
                        return false;
                    }
                }
            });
        })
    }
    //删除售后单
    function deleteReturn(after_sales_id) {
        layer.confirm('确定要删除吗？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: "/aftersales/return/deletereturn",
                type: "GET",
                data: {"after_sales_id": after_sales_id},
                dataType: "json",
                success: function (data) {
                    if (data.code == 200) {
                        location.href = location.href;
                        layer.msg(data.message, {time: 3000}, {icon: 6});
                    } else {
                        layer.msg(data.message, {icon: 5});
                        return false;
                    }
                }
            });
        })
    }
</script>