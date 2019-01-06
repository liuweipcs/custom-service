<?php

use app\components\LinkPager;

$this->title = '日志表';
?>
<style>
    .row{
        margin-right: 0;
    margin-left: 0;
    }
/*    .pagination-detail{
        display: none;
    }*/
</style>
<div class="page-wrapper" style="margin: 10px;">
    <div class="row"> 
        <table class="table table-striped table-bordered">
            <tr>
                <td>平台</td>
                <td>类型</td>
                <td>账号类型</td>
                <td>修改认</td>
                <td>修改内容</td>
                <td>修改时间</td>
            </tr>
          <?php foreach ($log as $v) { ?>
                <tr>
                    <td><?php echo $v['platform_code']; ?></td>
                    <td><?php 
                    if($v['type']==1){
                          echo 'ebay账号'; 
                    }elseif($v['type']==2){
                        echo "付款邮箱";
                    }elseif($v['type']==3){
                        echo "地址";
                    }
                    ?></td>
                    <td><?php 
                    if($v['account_type']==1){
                        echo 'GBC';
                    }elseif($v['account_type']==2){
                        echo '公司';
                    }
                    ?></td>
                    <td><?php echo $v['update_user']; ?></td>
                    <td><?php echo $v['content']; ?></td>
                    <td><?php echo $v['update_time']; ?></td>    
                </tr>

          <?php } ?>
        </table>
        <?php
        echo LinkPager::widget([
            'pagination' => $page,
            'firstPageLabel' => '首页',
            'lastPageLabel' => '尾页',
            'nextPageLabel' => '下一页',
            'prevPageLabel' => '上一页',
        ]);
        ?>
    </div>
</div>

