<div class="row" id="section-1" style="margin-top: 10px;">
<div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-body">
                <h4 class="m-b-30 m-t-0">交易记录</h4>
                <div class="row">
                    <div class="col-xs-12">
                        <table class="table table-striped">
                            <tbody>
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
                                <?php if (!empty($info['trade'])) { ?>
                                    <?php foreach ($info['trade'] as $value) { ?>
                                        <tr>
                                            <td><?php echo $value['transaction_id']; ?></td>
                                            <td><?php echo $value['payer_email']; ?></td>
                                            <td><?php echo $value['receiver_business']; ?></td>
                                            <td><?php echo $value['order_pay_time']; ?></td>
                                            <td><?php echo $value['receive_type']; ?></td>
                                            <td><?php echo $value['payment_status']; ?></td>
                                            <td><?php echo $value['amt']; ?>(<?php echo $value['currency']; ?>)</td>
                                            <td><?php echo $value['fee_amt']; ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr><td colspan="8" align="center">没有找到信息！</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>