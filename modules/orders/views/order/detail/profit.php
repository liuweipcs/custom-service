<?php
if (!empty($info['profit'])) {
            $totalRevnue = $textracharge_price = $totalCost = $newTotalCost = 0;
            $totalRevnue += $info['profit']['product_price'] + $info['profit']['shipping_price'] + $info['profit']['adjust_amount'];

            //库存折扣成本
            $stock_price = round(0.01 * $totalRevnue, 2);
            //汇兑损失成本
            $exchange_price = round(0.02 * $totalRevnue, 2);

            $totalCost += $info['profit']['purchase_cost'] + $info['profit']['final_value_fee'] + $info['profit']['shipping_cost'] + $info['profit']['pay_cost']
                + $info['profit']['refund_amount'] + $info['profit']['redirect_cost'] + $info['profit']['packing_cost'] + $info['profit']['package_cost']
                + $info['profit']['first_carrier_cost'] + $info['profit']['duty_cost'] + $textracharge_price + $info['profit']['exceedprice'] + $info['profit']['processing'] + $stock_price + $exchange_price+$info['profit']['pack'];
            $newTotalCost += $info['profit']['purchase_cost_new1'] + $info['profit']['final_value_fee'] + $info['profit']['shipping_cost'] + $info['profit']['pay_cost']
                + $info['profit']['refund_amount'] + $info['profit']['redirect_cost'] + $info['profit']['packing_cost'] + $info['profit']['package_cost']
                + $info['profit']['first_carrier_cost'] + $info['profit']['duty_cost_new1'] + $textracharge_price + $info['profit']['exceedprice'] + $info['profit']['processing'] + $stock_price + $exchange_price;
            $final_profit = $final_profit_rate = $new_final_profit = $new_final_profit_rate = 0;
}
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseSeven"><h4 class="panel-title">利润信息</h4></a>
    </div>
    <div id="collapseSeven" class="panel-collapse collapse">
        <div class="panel-body">
            <table class="table table-striped">
                    <thead>
                    <tr>
                        <th colspan="3" style="color: green">收入</th>
                        <th colspan="17" style="color: red">成本/支出</th>
                        <th>利润</th>
                        <th>利润率</th>
                    </tr>
                    </thead>
                    <tbody id="profit_id">
                    <tr align="center">
                        <td style="color: green">产品<br />金额</td>
                        <td style="color: green">运费</td>
                        <td style="color: green">调整<br />金额</td>
                        <td style="color: red">平台<br />佣金</td>
                        <td style="color: red">交易<br />佣金</td>
                         <td style="color: red">平均<br />成本</td>
                        <td style="color: red">平均<br />采购成本</td>
                         <td style="color: red">平均<br />采购运费</td>
                         <td style="color: red">最新<br />采购价</td>
                        <td style="color: red">包装<br />成本</td>
                        <td style="color: red">包材<br />成本</td>
                        <td style="color: red">库存折扣<br />成本</td>
                        <td style="color: red">汇兑损失<br />成本</td>
                        <td style="color: red">运费<br />成本</td>
                        <td style="color: red">退款<br />金额</td>
                        <td style="color: red">重寄<br />费用</td>
                         <td style="color: red">偏远<br />附加费</td>
                         <td style="color: red">超尺寸<br />附加费</td>
                         <td style="color: red">海外仓<br />处理费</td>
                         <td style="color: red">复核<br />打包费</td>
                        <td rowspan="2">

                        </td>
                        <td rowspan="2">

                        </td>
                     </tr>
                    <?php if(!empty($info['profit'])){?>
                        <tr>
                            <td style="color: green"><?php echo $info['profit']['product_price'];?><br />(CNY)</td>
                            <td style="color: green"><?php echo $info['profit']['shipping_price'];?><br />(CNY)</td>
                            <td><?php echo $info['profit']['adjust_amount'] >= 0 ? '<font color="green">' . $info['profit']['adjust_amount'] . '<br /><br />(CNY)</font>'
                                : '<font color="red">' . $info['profit']['adjust_amount'] . '<br /><br />(CNY)</font>';?>
                            </td>
                            <td style="color: red"><?php echo $info['profit']['final_value_fee'];?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['pay_cost'];?><br />(CNY)</td>
                             <td style="color: red"><?php echo $info['profit']['purchase_cost'];?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['purchase_last_price1'];?><br />(CNY)</td>
                             <td style="color: red"><?php echo $info['profit']['purchase_ship_cost1'];?><br />(CNY)</td>
                             <td style="color: red"><?php echo $info['profit']['purchase_cost_new1'];?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['package_cost'];?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['packing_cost'];?><br />(CNY)</td>
                            <td style="color: red"><?php echo $stock_price;?><br />(CNY)</td>
                            <td style="color: red"><?php echo $exchange_price;?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['shipping_cost'];?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['refund_amount'];?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['redirect_cost'];?><br />(CNY)</td>
                            <td style="color: red"><?php echo $textracharge_price;?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['exceedprice']?$info['profit']['exceedprice']:0.00;?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['processing']?$info['profit']['processing']:0.00;?><br />(CNY)</td>
                            <td style="color: red"><?php echo $info['profit']['pack']?$info['profit']['pack']:0.00;?><br />(CNY)</td>
                         </tr>                         
                         
                         <tr>
                            <td colspan="3" align="center" style="color: green"><?php echo $totalRevnue;?>(CNY)</td>
                            <td colspan="17" align="center" style="color: red"><?php echo $newTotalCost;?>(CNY)&nbsp;备注:此处的成本/支出的平均成本为:最新采购价</td>
                            <td  align="center">
                                <?php echo $info['profit']['profit'] >= 0 ? '<font color="green">' . (($info['profit']['profit_new1']) + ($info['profit']['stock_price'])) . '(CNY)</font>'
                                        : '<font color="red">' . (($info['profit']['profit_new1']) + ($info['profit']['stock_price'])) . '(CNY)</font>';
                                ?>
                            </td>
                            <td align="center">
                                <?php 
                                if($info['profit']['total_price']){
                                    echo $info['profit']['profit'] >= 0 ? '<font color="green">' . round(((($info['profit']['profit_new1'])+($info['profit']['stock_price']))/$info['profit']['total_price']),4)*100 . '%</font>'
                                        : '<font color="red">' . round(((($info['profit']['profit_new1'])+($info['profit']['stock_price']))/$info['profit']['total_price']),4)*100 . '%</font>';
                                }else{
                                    echo '<font color="green">0.00%</font>';
                                }
                                ?>

                            </td>
                        </tr>
                         <tr>
                            <td colspan="22"><strong>汇率值：</strong><?php echo $info['profit']['currency_rate'];?>&nbsp;&nbsp;
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