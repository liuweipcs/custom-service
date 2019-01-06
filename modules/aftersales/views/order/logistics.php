<?php
use app\modules\orders\models\Order;
use yii\helpers\Url;
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseLogis"><h4 class="panel-title">仓储&物流</h4></a>
    </div>
    <div id="collapseLogis" class="panel-collapse collapse ">

        <div id="menu5" class="tab-pane">
            <div id="warehouse-box">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>物流单号</th>
                        <th>发货仓库:</th>
                        <th>邮寄方式</th>
                    </tr>
                    </thead>
                    <tbody id="wareh_logistics">
                    <?php if(!empty($info['wareh_logistics'])){?>
                        <tr>
                            <td><?php echo $info['info']['track_number']?></td>
                            <td><?php echo isset($info['wareh_logistics']['warehouse']['warehouse_name']) ?
                                    $info['wareh_logistics']['warehouse']['warehouse_name'] : '';?></td>
                            <td><?php echo isset($info['wareh_logistics']['logistics']) ?
                                    $info['wareh_logistics']['logistics']['ship_name'] : '';?></td>
                        </tr>
                    <?php }else{?>
                        <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
