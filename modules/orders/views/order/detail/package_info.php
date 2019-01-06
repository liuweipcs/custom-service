<?php 
use yii\helpers\Url;
?>
<div class="panel panel-default" style="margin-top: 5px;">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseThree"><h4 class="panel-title">包裹信息</h4></a>
    </div>
    <div id="collapseThree" class="panel-collapse collapse in">
        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <td>包裹号</td>
                        <td>发货仓库</td>
                        <td>运输方式</td>
                        <td>追踪号</td>
                        <td>总运费</td>
                        <td>出货时间</td>
                        <td>出货重量</td>
                        <td>数量</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if(!empty($info['orderPackage'])){
                        foreach ($info['orderPackage'] as $value) {?>
                            <tr>
                                <td><?php echo $value['package_id'];?></td>
                                <td><?php echo $value['warehouse_name'];?></td>
                                <td><?php echo $value['ship_name'];?></td>
                                <td>
                                <?php
                                    if($info['info']['paytime'] < '2018-05-20 00:00:00'){
                                        if (!empty($value['tracking_number_1'])) {
                                            echo '<a href="https://t.17track.net/en#nums='.$value['tracking_number_1'].'" target="_blank" title="查看物流跟踪信息">'.$value['tracking_number_1'].'</a>';
                                        }else{
                                            echo '<a href="https://t.17track.net/en#nums='.$value['tracking_number_2'].'" target="_blank" title="查看物流跟踪信息">'.$value['tracking_number_2'].'</a>';
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
                                <td><?php echo $value['shipping_fee'];?></td>
                                <td><?php echo $value['shipped_date'];?></td>
                                <td><?php echo $value['package_weight'];?></td>
                                <td>
                                        <?php foreach ($value['items'] as $sonvalue){?>
                                            <p>sku：<?php echo $sonvalue['sku'];?> 数量：<?php echo $sonvalue['quantity'];?></p>
                                        <?php }?>
                                    </td>
                            </tr>
                        <?php }
                    }else{?>
                    <tr><td colspan="8" style="text-align: center;">未找到包裹信息...</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>