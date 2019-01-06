<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseSeven"><h4 class="panel-title">利润信息</h4></a>
    </div>
    <div id="collapseSeven" class="panel-collapse collapse in">
        <div class="panel-body">
            <table class="table table-striped">
                    <thead>
                    <tr>
                        <th colspan="3">收入</th>
                        <th colspan="8">成本/支出</th>
                        <th >利润</th>
                        <th>利润率</th>
                    </tr>
                    </thead>
                    <tbody id="profit_id">
                    <tr>
                        <td style="color: green;">产品金额</td>
                        <td style="color: green;">运费</td>
                        <td style="color: green;">调整金额</td>
                        <td style="color: red;">平台佣金</td>
                        <td style="color: red;">交易佣金</td>
                        <td style="color: red;">货物成本</td>
                        <td style="color: red;">包装成本</td>
                        <td style="color: red;">包材成本</td>
                        <td style="color: red;">运费成本</td>
                        <td style="color: red;">退款金额</td>
                        <td style="color: red;">重寄费用</td>
                        <?php if(!empty($info['profit'])){?>
                            <td rowspan="3">
                                <?php echo $info['profit']['profit'] >= 0 ? '<font color="green">' . $info['profit']['profit']. '(CNY)</font>'
                                    : '<font color="red">' . $info['profit']['profit'] . '(CNY)</font>';?>
                            </td>
                            <td rowspan="3">
                                <?php echo $info['profit']['profit_rate'] >= 0 ? '<font color="green">' . $info['profit']['profit_rate'] . '%</font>'
                                    : '<font color="red">' . $info['profit']['profit_rate'] . '%</font>';?>
                            </td>
                        <?php }; ?>
                    </tr>
                    <?php if(!empty($info['profit'])){?>
                        <tr>
                            <td><?php echo $info['profit']['product_price'];?>(CNY)</td>
                            <td><?php echo $info['profit']['shipping_price'];?>(CNY)</td>
                            <td><?php echo $info['profit']['adjust_amount'];?>(CNY)</td>
                            <td><?php echo $info['profit']['final_value_fee'];?>(CNY)</td>
                            <td><?php echo $info['profit']['pay_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['purchase_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['package_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['packing_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['shipping_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['refund_amount'];?>(CNY)</td>
                            <td><?php echo $info['profit']['redirect_cost'];?>(CNY)</td>
                        </tr>
                        <?php
                        $totalRevnue = 0;
                        $totalCost = 0;
                        if (!empty($info['profit']))
                        {
                            $totalRevnue += $info['profit']['product_price'] + $info['profit']['shipping_price'] + $info['profit']['adjust_amount'];
                            $totalCost += $info['profit']['purchase_cost'] + $info['profit']['final_value_fee'] + $info['profit']['shipping_cost'] + $info['profit']['pay_cost']
                                + $info['profit']['refund_amount'] + $info['profit']['redirect_cost'] + $info['profit']['packing_cost'] + $info['profit']['package_cost'];
                        }
                        ?>
                        <tr>
                            <td colspan="3" align="center" style="color: green"><?php echo $totalRevnue;?>(CNY)</td>
                            <td colspan="8" align="center" style="color: red"><?php echo $totalCost;?>(CNY)</td>
                        </tr>
                        <tr>
                            <td colspan="13"><strong>汇率值：</strong><?php echo $info['profit']['currency_rate'];?>&nbsp;&nbsp;
                                (<?php echo substr($info['profit']['create_time'], 0, 10);?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $info['profit']['currency'];?>
                                ->CNY)&nbsp;&nbsp;<strong>利润计算公式：</strong>（收入-成本/支出）-退款-重寄费用。
                            </td>
                        </tr>
                    <?php }else{?>
                        <tr><td colspan="13" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
        </div>
    </div>
</div>