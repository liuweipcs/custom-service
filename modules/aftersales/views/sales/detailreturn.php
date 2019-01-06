<?php

use app\modules\aftersales\models\AfterSalesOrder;
use yii\helpers\Url;

?>

<div class="popup-wrapper">
    <div class="popup-body">
        <ul class="nav nav-tabs">
            <li class="active"><a data-toggle="tab" href="#home">退货单详情</a></li>
            <li><a data-toggle="tab" href="#product">退货产品信息</a></li>
        </ul>

        <div class="tab-content">
            <div id="home" class="tab-pane fade in active">
                <table class="table table-striped">
                    <tr>
                        <td colspan="15">
                            <table class="table table-striped">
                                <tr>
                                    <th>责任归属部门</th>
                                    <th>退款原因</th>
                                    <th>备注</th>
                                </tr>
                                <tr>
                                    <?php if ($model['department_id']) {
                                        $departmentList = \app\modules\systems\models\BasicConfig::getParentList(52);
                                        $allConfigData  = \app\modules\systems\models\BasicConfig::getAllConfigData();
                                        ?>

                                        <td><?php echo $departmentList[$model['department_id']]; ?></td>
                                        <td><?php echo $allConfigData[$model['reason_id']]; ?></td>
                                    <?php } else { ?>
                                        <td>-</td>
                                        <td><?php echo AfterSalesOrder::getAfterSalsesData($data->after_sale_id, ['reason_id']); ?></td>
                                    <?php } ?>
                                    <td><?php echo AfterSalesOrder::getAfterSalsesData($data->after_sale_id, ['remark']); ?></td>

                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <th>售后单号</th>
                        <th>平台code</th>
                        <th>订单号</th>
                        <th>订单金额</th>
                        <th>备注</th>
                        <th>退货状态</th>
                        <th>退货时间</th>
                        <th>接受退货仓库</th>
                        <th>买家退货使用的carrier</th>
                        <th>买家退货跟踪单号</th>
                        <th>RMA</th>
                        <th>审核状态</th>
                        <th>创建人</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                    <?php if (!empty($data)) { ?>
                        <tr>
                            <td><?php echo $data->after_sale_id; ?></td>
                            <td><?php echo $data->platform_code; ?></td>
                            <td><?php echo $data->order_id; ?></td>
                            <td><?php echo $model['order_amount'] ?></td>
                            <td><?php echo $data->remark ?></td>
                            <td><?php echo AfterSalesOrder::getReturnStatusText($data->return_status) ?></td>
                            <td><?php echo $data->return_time ?></td>
                            <td><?php echo \app\modules\orders\models\Warehouse::getSendWarehouse($data->warehouse_id) ?></td>
                            <td><?php echo $data->carrier; ?></td>
                            <?php $tracking_url = '<a href="https://t.17track.net/en#nums=' . $data->tracking_no . '" target="_blank" title="查看物流跟踪信息">' . $data->tracking_no . '</a>'; ?>
                            <td><?php echo $tracking_url; ?></td>
                            <td><?php echo $data->rma; ?></td>
                            <td><?php echo AfterSalesOrder::getOrderStatusListText($model['status']) ?></td>
                            <td><?php echo $data->create_time; ?></td>
                            <td><?php echo $data->create_by; ?></td>
                            <td>  
                                <?php if ($model['status'] != AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED) { ?> 
                                        <div class="btn-group btn-list">
                                            <button type="button"
                                                    class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation'); ?></button>
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                                    data-toggle="dropdown">
                                                <span class="caret"></span>
                                                <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                            </button>
                                            <ul class="dropdown-menu" rol="menu">
                                                <li><a class="ajax-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/order/audit',
                                                           'after_sales_id' => $data->after_sale_id,
                                                           'status'         => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED]); ?>">审核通过</a>
                                                </li>
                                                <li><a class="ajax-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/order/audit',
                                                           'after_sales_id' => $data->after_sale_id,
                                                           'platform_code'  => $data->platform_code,
                                                           'status'         => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]); ?>">退回修改</a>
                                                </li>
                                                <li><a style="cursor: pointer"
                                                       onclick="cancelReturn('<?= $data->after_sale_id ?>',3)">取消退货</a>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php 
                                } ?>
                                <!--待收货  -->
                                <?php //if ($data->is_receive == 1) { ?>
<!--                                    <div class="btn-group btn-list">
                                        <button type="button"
                                                class="btn btn-default btn-sm"><?php //echo Yii::t('system', 'Operation'); ?></button>
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                                data-toggle="dropdown">
                                            <span class="caret"></span>
                                            <span class="sr-only"><?php //echo Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                        </button>
                                        <ul class="dropdown-menu" rol="menu">
                                            <li><a style="cursor: pointer"
                                                   onclick="cancelReturn('<?= $data->after_sale_id ?>',3)">取消退货</a></li>
                                        </ul>-->
<!--                                    </div>-->
                                <?php //} ?>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <tr>
                            <td colspan="14">暂无该退货单单的详情</td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <div id="product" class="tab-pane fade">
                <table class="table table-striped">

                    <tr>
                        <th>sku编号</th>
                        <th>产品标题</th>
                        <th>退货数量</th>
                        <th>产品线</th>
                        <th>备注</th>
                    </tr>
                    <?php if (!empty($detail_data)) { ?>
                        <?php foreach ($detail_data as $value) { ?>
                            <tr>
                                <td><?php echo $value->sku; ?></td>
                                <td><?php echo $value->product_title; ?></td>
                                <td><?php echo $value->quantity; ?></td>
                                <td><?php echo $value->linelist_cn_name; ?></td>
                                <td><?php echo $value->remark; ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5">暂无产品信息</td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
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
</script>