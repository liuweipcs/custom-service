<?php
    use app\modules\accounts\models\Platform;
    use app\modules\accounts\models\Account;
    use app\modules\orders\models\Order;
    use app\common\VHelper;
    use yii\helpers\Url;
?>
<div class="col-md-7">
    <div class="panel panel-primary">
        <div class="panel-body">
            <h4 class="m-b-30 m-t-0">订单详情</h4>
            <div class="row">
                <div class="col-xs-12">
                    <table class="table table-hover">
                        <tbody>
                            <?php if (!empty($info['info'])) { ?>
                                <?php
                                $account_info = Account::getHistoryAccountInfo($info['info']['account_id'], $info['info']['platform_code']);
                                ?>
                                <tr>
                                    <td>订单号: <?php echo isset($account_info->account_short_name) ? $account_info->account_short_name . '-' . $info['info']['order_id'] : $info['info']['order_id']; ?></td>
                                    <td>销售平台: <?php echo $info['info']['platform_code']; ?></td>
                                </tr>
                                <tr>
                                    <td>平台订单号: <?php echo $info['info']['platform_order_id']; ?></td>
                                    <td>买家ID: <?php echo $info['info']['buyer_id']; ?></td>
                                </tr>
                                <tr>
                                    <td>下单时间: <?php echo $info['info']['created_time']; ?></td>
                                    <td>付款时间: <?php echo $info['info']['paytime']; ?></td>
                                </tr>
                                <tr>
                                    <td>运费: <?php echo $info['info']['ship_cost'] . '(' . $info['info']['currency'] . ')'; ?></td>
                                    <td>总费用: <?php echo $info['info']['total_price'] . '(' . $info['info']['currency'] . ')'; ?></td>
                                </tr>
                                <tr>
                                    <td>eBay账号: <?php echo $accountName ?></td>
                                    <td>送货地址: 
                                        <?php echo $info['info']['ship_name']; ?>
                                        (tel:<?php echo $info['info']['ship_phone']; ?>)<br>
                                        <?php echo $info['info']['ship_street1'] . ',' . ($info['info']['ship_street2'] == '' ? '' : $info['info']['ship_street2'] . ',') . $info['info']['ship_city_name']; ?>,
                                        <?php echo $info['info']['ship_stateorprovince']; ?>,
                                        <?php echo $info['info']['ship_zip']; ?>,<br/>
                                <?php echo $info['info']['ship_country_name']; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>客户email: <?php echo $info['info']['email']; ?></td>
                                    <td><a class="edit-button" href="/mails/ebayreply/initiativeadd?order_id=<?php echo $info['info']['order_id']; ?>&platform=EB">发送消息</a></td>
                                </tr>
                                <?php if (Platform::PLATFORM_CODE_EB == 'EB'): ?>
                                    <tr>
                                        <td colspan="2">客户留言:<?php if (!empty($info['note'])) echo $info['note']['note'] ?></td>
                                    </tr>
                                    <tr>
                                        <td>订单状态: 
                                            <?php
                                            $complete_status = Order::getOrderCompleteStatus();
                                            echo $complete_status[$info['info']['complete_status']];
                                            ?>
                                            
                                            <?php  echo '(订单类型: '.VHelper::getOrderType($info['info']['order_type']).')';?>
                                        </td>

                                        <td>退款状态: <?php echo VHelper::refundStatus($info['info']['refund_status']); ?></td>
                                    </tr>
                                    <tr>
                                        <td id='remarkTable' colspan="2">
                                            <?php if (!empty($info['remark'])): ?>
                                                <table style="width:100%;">
                                                <?php foreach ($info['remark'] as $key => $value): ?>
                                                        <tr>
                                                            <td style="width:60%;"><?php echo nl2br(strip_tags($value['remark'])); ?></td>
                                                            <td><?= $value['create_user'] ?></td>
                                                            <td><?= $value['create_time'] ?></td>
                                                            <td><a href="javascript:;" onclick="removeRemark(<?php echo $value['id']; ?>)">删除</a></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                                </table>
                                            <?php endif; ?>

                                        </td>

                                    </tr>
                                    <tr>
                                <input type="hidden" class="platform_code" value="<?php echo $info['info']['platform_code'] ?>">
                                <td>订单备注: <textarea style="width:200px;height:80px;" class="remark"></textarea>
                                    <button onclick=saveRemark("<?php echo $info['info']['order_id']; ?>")>添加备注</button><input class="detail_order_id" type="hidden" value="<?php echo $info['info']['order_id']; ?>"/>
                                </td>
                                <td>出货备注: <textarea style="width:200px;height:80px;" class="print_remark"><?php echo $info['info']['print_remark'] ?></textarea>
                                    <button onclick=save_print_remark("<?php echo $info['info']['order_id']; ?>")>添加发货备注</button><input class="detail_order_id" type="hidden" value="<?php echo $info['info']['order_id']; ?>"/>
                                </td>
                                </tr>
                        <?php endif; ?>


                        <?php }else { ?>
                            <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    //订单备注
    function saveRemark(orderId) {
        var remark = $('.remark').val();
        if(remark.length <= 0){
            layer.msg('请添加订单备注信息');
            return false;
        }
        var url = '<?php echo Url::toRoute(['/orders/order/addremark']); ?>';
        $.post(url, {'order_id': orderId, 'remark': remark}, function (data) {
            if (data.ack != true)
                alert(data.message);
            else
            {
                var info = data.info;
                var html = '<table style="width:100%;"><tbody>';
                for (var i in info)
                {
                    html += '<tr>' + "\n" +
                            '<td style="width:60%;">' + info[i].remark.replace(/\n/g, "<br>") + '</td>' + "\n" +
                            '<td>' + info[i].create_user + '</td>' + "\n" +
                            '<td>' + info[i].create_time + '</td>' + "\n" +
                            '<td><a href="javascript:void(0)" onclick="removeRemark(' + info[i].id + ')">删除</a></td>' + "\n" +
                            '</tr>' + "\n";
                }
                html += '</tbody></table>';
                $('#remarkTable').empty().html(html);
            }
        }, 'json');
    }

//删除订单备注
    function removeRemark(id)
    {
        console.log(id);
        var url = '<?php echo Url::toRoute(['/orders/order/removeremark']); ?>';
        $.get(url, {id: id}, function (data) {
            if (data.ack != true)
                alert(data.message);
            else
            {
                var info = data.info;
                var html = '<table style="width:100%;"><tbody>';
                for (var i in info)
                {
                    html += '<tr>' + "\n" +
                            '<td style="width:60%;">' + info[i].remark.replace(/\n/g, "<br>") + '</td>' + "\n" +
                            '<td>' + info[i].create_user + '</td>' + "\n" +
                            '<td>' + info[i].create_time + '</td>' + "\n" +
                            '<td><a href="javascript:void(0)" onclick="removeRemark(' + info[i].id + ')">删除</a></td>' + "\n" +
                            '</tr>' + "\n";
                }
                html += '</tbody></table>';
                $('#remarkTable').empty().html(html);
            }
        }, 'json');
    }

//添加出货备注
    function save_print_remark(orderId) {
        var print_remark = $('.print_remark').val();
        if(print_remark.length <=0){
            layer.msg('请添加输入备注!');
            return false;
        }
        var url = '<?php echo Url::toRoute(['/orders/order/addprintremark']); ?>';
        var platform = $('.platform_code').val();
        $.post(url, {'order_id': orderId, 'platform': platform, 'print_remark': print_remark}, function (data) {
            alert(data.info);
        }, 'json');
    }
</script>