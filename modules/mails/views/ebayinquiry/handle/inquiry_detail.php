<?php
    use app\modules\aftersales\models\AfterSalesOrder;
    use app\modules\accounts\models\Platform;
    use yii\helpers\Url;
    use app\modules\orders\models\Order;
?>
<div class="col-md-7">
        <div class="panel panel-primary">
            <div class="panel-body">
                <h4 class="m-b-30 m-t-0">纠纷详情</h4>
                <div class="row">
                    <div class="col-xs-12">
                        <table class="table table-hover">
                            <tbody>
                                <tr>
                                    <td style="text-align: right;">纠纷编号：</td>
                                    <td style="text-align: left;"><?php echo $model->inquiry_id; ?></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;">状态：</td>
                                    <td style="text-align: left;"><?php echo $model->status; ?></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;">买家期望：</td>
                                    <td style="text-align: left;"><?php echo $model->buyer_initial_expected_resolution; ?></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;">创建时间：</td>
                                    <td style="text-align: left;"><?php echo $model->creation_date; ?></td>
                                </tr>
                                <tr>
                                    <td  style="text-align: right;">售后单号：</td>
                                    <td>
                                        <?php
                                        if(isset($info['info']) && !empty($info['info'])){
                                        $afterSalesOrders = AfterSalesOrder::find()->select('after_sale_id')->where(['order_id'=>$info['info']['order_id']])->asArray()->all();
                                            if(empty($afterSalesOrders)){
                                                echo '<span>无售后处理单</span>';
                                            }else{
                                                echo '<span>'.implode(',',array_column($afterSalesOrders,'after_sale_id')).'</span>';
                                            }
                                        }else{
                                            echo '<span>无售后处理单</span>';
                                        }
                                        if (!empty($info))
                                            echo '<a style="margin-left:10px" _width="90%" _height="90%" class="edit-button" href="' . Url::toRoute(['/aftersales/order/add', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">新建售后单</a>';

                                        if (!empty($info) && $info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) {
                                            echo '<a style="margin-left:10px" _width="30%" _height="60%" class="edit-button" href="' . Url::toRoute(['/orders/order/cancelorder', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">永久作废</a>';
                                            echo '&nbsp;&nbsp;<a _width="30%" _height="60%" class="edit-button" href="'.Url::toRoute(['/orders/order/holdorder', 'order_id' => $info['info']['order_id'], 'platform' =>  Platform::PLATFORM_CODE_EB]).'">暂时作废</a>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php if ($model->state != 'CLOSED' && !in_array($model->status, ['CLOSED', 'CLOSED_WITH_ESCALATION', 'CS_CLOSED'])) { ?>
                                    <tr>
                                        <td>无需自动退款</td>
                                        <?php
                                        switch ($model->auto_refund) {
                                            case 0:
                                                $auto_refund_after_case_attribute = '';
                                                break;
                                            case 1:
                                                $auto_refund_after_case_attribute = 'checked="checked"';
                                                break;
                                            case 2:
                                                $auto_refund_after_case_attribute = 'checked="checked"  disabled="disabled"';
                                        }
                                        ?>
                                        <td><input <?php echo $auto_refund_after_case_attribute; ?> type="checkbox" class="auto_refund_after_case"/></td>
                                    </tr>
                                <script type="application/javascript">
                                <?php if ($model->auto_refund != 2) { ?>
                                        $(function(){
                                        $('.auto_refund_after_case').click(function(){
                                        $('.auto_refund_after_case_actual').val(Number(this.checked));
                                        });
                                        });
                                <?php } ?>
                                </script>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="col-xs-12">
                        <h4 class="m-b-30 m-t-0">互动记录</h4>

                <?php if (!empty($detailModel)) { ?>
                    <ul class="list-group" style="height: auto; max-height:550px;overflow-y:scroll; overflow-x:scroll;">
                        <?php foreach ($detailModel as $key => $detail) { ?>
                            <li class="list-group-item" <?php if ($key + 1 == count($detailModel)) {
                                echo "id='section-6'";
                            } ?>>
                    <?php echo isset($detail->date) ? date('Y-m-d H:i:s', strtotime($detail->date) + 28800) : '', '&nbsp;&nbsp;&nbsp;&nbsp;', '<span style="color:#FF7F00">', $detail::$actorMap[$detail->actor], '</span>', '&nbsp;&nbsp;&nbsp;&nbsp;', $detail->action; ?>
                                <?php if(!empty($detail->description)){?>
                                <table class="table table-bordered table_div_<?php echo $key; ?>">
                                    <tbody>
                                        <tr class="ebay_dispute_message_board">
                                            <td style="width:100px;text-align: center;">留言</td>
                                            <td><?php echo !empty($detail->description) ? $detail->description . '<a style="cursor: pointer;" data1 = "div_' . $key . '" data="' . $detail->description . '" class="transClik">&nbsp;&nbsp;点击翻译</a>' : ""; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <?php } ?>
                            </li>
                    <?php } ?>
                    </ul>
                <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>