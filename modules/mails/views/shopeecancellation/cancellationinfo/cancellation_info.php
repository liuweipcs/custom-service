<?php

use yii\helpers\Url;
use app\modules\accounts\models\Platform;

?>
<link href="<?php echo yii\helpers\Url::base(true); ?>/css/timeline.css" rel="stylesheet">

<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#issue" href="#issue_basicinfo"><h4 class="panel-title">基本信息</h4></a>
    </div>
    <div id="issue_basicinfo" class="panel-collapse collapse in">
        <div class="panel-body">
            <table class="table table-bordered">
                <?php if (!empty($cancellationList)) { ?>
                    <tr>
                        <td>取消原因:</td>
                        <td colspan="3">
                            <?php echo !empty($cancellationList['cancel_reason']) ? $cancellationList['cancel_reason'] : ''; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>状态：</td>
                        <td colspan="3">
                            <?php
                            echo !empty($cancellationList['order_status']) ? $cancellationList['order_status'] : '';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>纠纷开始时间：</td>
                        <td><?php echo !empty($cancellationList['update_time']) ? date('Y-m-d H:i:s', $cancellationList['update_time']) : ''; ?></td>
                        <td>回复截止时间：</td>
                        <td><?php echo date('Y-m-d H:i:s', $cancellationList['update_time'] + 3600 * 48); ?></td>
                    </tr>
                    <tr>
                        <td>售后单号：</td>
                        <td colspan="3">
                            <?php
                            if (!empty($afterSalesOrders)) {
                                foreach ($afterSalesOrders as $afterSalesOrder) {
                                    if ($afterSalesOrder['type'] == 1) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/aftersales/sales/detailrefund', 'after_sale_id' => $afterSalesOrder['after_sale_id'], 'platform_code' => Platform::PLATFORM_CODE_SHOPEE]) . '">' . $afterSalesOrder['after_sale_id'] . '</a>';
                                    } else if ($afterSalesOrder['type'] == 2) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/aftersales/sales/detailreturn', 'after_sale_id' => $afterSalesOrder['after_sale_id'], 'platform_code' => Platform::PLATFORM_CODE_SHOPEE]) . '">' . $afterSalesOrder['after_sale_id'] . '</a>';
                                    } else if ($afterSalesOrder['type'] == 3) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/aftersales/sales/detailredirect', 'after_sale_id' => $afterSalesOrder['after_sale_id'], 'platform_code' => Platform::PLATFORM_CODE_SHOPEE]) . '">' . $afterSalesOrder['after_sale_id'] . '</a>';
                                    }
                                }
                            } else {
                                echo '没有售后单号';
                            }
                            ?>
                        </td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td>没有找到交易信息</td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        //todo

    });
</script>