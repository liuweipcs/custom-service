<?php 
use yii\helpers\Url;
?>
<div class="panel panel-default" style="margin-top: 5px;">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseThree"><h4 class="panel-title">包裹信息</h4></a>
    </div>
    <div id="collapseThree" class="panel-collapse collapse">
        <div class="panel-body">
            <table class="table table-hover">
                            <tbody>
                                <?php if(!empty($info['orderPackage'])){?>
                                <?php foreach ($info['orderPackage'] as $value){?>
                                <tr>
                                    <td style="text-align: right;">包裹号</td>
                                    <td style="text-align: left;"><?php echo $value['package_id'];?></td>
                                    <td style="text-align: right;">发货仓库</td>
                                    <td style="text-align: left;"><?php echo $value['warehouse_name'];?></td>
                                </tr>
                                
                                <tr>
                                    <td style="text-align: right;">运输方式</td>
                                    <td style="text-align: left;"><?php echo $value['ship_name'];?></td>
                                    <td style="text-align: right;">追踪号</td>
                                    <td style="text-align: left;">
                                        <?php
                                            if($info['info']['paytime'] < '2018-05-20 00:00:00'){
                                                if (!empty($value['tracking_number_1'])) {
                                                    echo "<a target=\"_blank\" href='http://www.17track.net/zh-cn/track?nums=" . $value['tracking_number_1'] . "' title='物流商实际追踪号'>".$value['tracking_number_1'] ."</a>";
                                                }else{
                                                    echo "<a target=\"_blank\" href='http://www.17track.net/zh-cn/track?nums=" . $value['tracking_number_2' ] . "' title='代理商追踪号'>".$value['tracking_number_2'] ."</a>";
                                                }
                                            }else{
                                                if (!empty($value['tracking_number_1'])) {
                                                    echo '<a target="_blank" href="'.Url::toRoute(['/orders/order/gettracknumber','track_number' => $value['tracking_number_1']]).'" title="查看物流跟踪信息">'.$value['tracking_number_1'].'</a>';
                                                }else{
                                                    echo '<a target="_blank" href="'.Url::toRoute(['/orders/order/gettracknumber','track_number' => $value['tracking_number_2']]).'" title="查看物流跟踪信息">'.$value['tracking_number_2'].'</a>';
                                                }
                                            }
                                        ?>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td style="text-align: right;">总运费</td>
                                    <td style="text-align: left;"><?php echo $value['shipping_fee'];?></td>
                                    <td style="text-align: right;">出货时间</td>
                                    <td style="text-align: left;"><?php echo $value['shipped_date'];?></td>
                                </tr>
                                
                                <tr>
                                    <td style="text-align: right;">重量</td>
                                    <td style="text-align: left;"><?php echo $value['package_weight'];?></td>
                                    <td style="text-align: right;">产品</td>
                                    <td style="text-align: left;">
                                        <?php foreach ($value['items'] as $sonvalue){?>
                                                <p>sku：<?php echo $sonvalue['sku'];?> 数量：<?php echo $sonvalue['quantity'];?></p>
                                            <?php }?>
                                    </td>
                                </tr>
                                <?php }?>
                                <?php }else{?>
                                    <tr><td colspan="8" align="center">没有找到信息！</td></tr>
                                <?php }?>
                            </tbody>
                        </table>
        </div>
    </div>
</div>