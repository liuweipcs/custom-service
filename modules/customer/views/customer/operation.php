<?php

use yii\helpers\Url;
?>
<div class="form-group" style="margin-top: 5px;">

    <div id="collapseSix" class="panel-collapse collapse in">
        <div class="panel-body">
            <table class="table table-hover">
                <tbody>
                <tr>
                    <th>#</th>
                    <th>动作</th>
                    <th>备注</th>
                    <th>时间</th>
                    <th>操作人</th>
                </tr>
                <?php if (!empty($list)) { ?>
                    <?php foreach ($list as $k => $log) { ?>
                        <tr>
                            <td><?php echo $k+1; ?></td>
                            <td><?php echo $log['action']; ?></td>
                            <td>[<?php echo $log['mark']; ?>]</td>
                            <td><?php echo $log['create_time']; ?></td>
                            <td><?php echo $log['create_by']; ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="3" align="center">没有找到信息...</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>