<div class="panel panel-default" style="margin-top: 5px;">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseLog"><h4 class="panel-title">操作日志</h4></a>
    </div>
    <div id="collapseLog" class="panel-collapse collapse">
        <div class="panel-body" style=" height: auto; max-height:350px;overflow-y:scroll;">
            <table class="table table-hover">
                <tbody>
                    <tr>
                        <th>操作</th>
                        <th>用户</th>
                        <th>操作人</th>
                    </tr>
                    <?php if (!empty($info['logs'])) { ?>
                        <?php foreach ($info['logs'] as $log) { ?>
                            <tr>
                                <td><?php echo strip_tags($log['update_content']); ?></td>
                                <td><?php echo $log['create_time']; ?></td>
                                <td><?php echo $log['user_full_name']; ?></td>
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