<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseFour"><h4 class="panel-title">交易记录</h4></a>
    </div>
    <div id="collapseFour" class="panel-collapse collapse">
        <div class="panel-body">
            <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>交易号</th>
                        <th>付款帐号</th>
                        <th>收款帐号</th>
                        <th>交易时间</th>
                        <th>交易类型</th>
                        <th>交易状态</th>
                        <th>交易金额</th>
                        <th>手续费</th>
                    </tr>
                    </thead>
                    <tbody id="trade">
                    <?php if(!empty($info['trade'])){?>
                        <?php foreach ($info['trade'] as $value){?>
                            <tr>
                                <td><?php echo $value['transaction_id'];?></td>
                                <td><?php echo isset($value['payer_email']) ? $value['payer_email'] : '暂无信息';?></td>
                                <td><?php echo isset($value['receiver_email']) ? $value['receiver_email'] : '暂无信息';?></td>
                                <td><?php echo $value['order_pay_time'];?></td>
                                <td><?php echo $value['receive_type'];?></td>
                                <td><?php echo $value['payment_status'];?></td>
                                <td><?php echo $value['amt'];?>(<?php echo $value['currency'];?>)</td>
                                <td><?php echo $value['fee_amt'];?></td>
                            </tr>
                        <?php }?>
                    <?php }else{?>
                        <tr><td colspan="6" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
        </div>
    </div>
</div>