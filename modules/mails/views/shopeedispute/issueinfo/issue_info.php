<?php

use yii\helpers\Url;
use app\modules\accounts\models\Platform;

?>
<link href="<?php echo yii\helpers\Url::base(true); ?>/css/timeline.css" rel="stylesheet">
<style>
    .issueAttachment {
        border: 1px solid #ccc;
        padding: 2px;
        width: 60px;
        height: 60px;
        cursor: pointer;
    }
</style>

<div>
    <button type="button" disabled id="updateIssueInfo" class="btn btn-primary"
            data-issueid="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
        更新纠纷
    </button>
    <span class="label label-danger">如果下方没有纠纷信息，可点击左边按钮进行更新</span>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#issue" href="#issue_basicinfo"><h4 class="panel-title">基本信息</h4></a>
    </div>
    <div id="issue_basicinfo" class="panel-collapse collapse in">
        <div class="panel-body">
            <table class="table table-bordered">
                <?php if (!empty($issue_list)) { ?>
                    <tr>
                        <td>取消原因:</td>
                        <td colspan="3">
                            <?php echo !empty($issue_list['reason']) ? $issue_list['reason'] : ''; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>纠纷ID：</td>
                        <td><?php echo !empty($issue_list['returnsn']) ? $issue_list['returnsn'] : ''; ?></td>
                        <td>状态：</td>
                        <td>
                            <?php
                            echo !empty($issue_list['status']) ? $issue_list['status'] : '';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>纠纷开始时间：</td>
                        <td><?php echo !empty($issue_list['create_time']) ? date('Y-m-d H:i:s', $issue_list['create_time']) : ''; ?></td>
                        <td>回复截止时间：</td>
                        <td><?php echo date('Y-m-d H:i:s', $issue_list['update_time'] + 3600 * 72); ?></td>
                    </tr>
                    <tr>
                        <td>售后单号：</td>
                        <td colspan="1">
                            <?php
                            if (!empty($afterSalesOrders)) {
                                foreach ($afterSalesOrders as $afterSalesOrder) {
                                    if ($afterSalesOrder['type'] == 1) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/aftersales/sales/detailrefund', 'after_sale_id' => $afterSalesOrder['after_sale_id'], 'platform_code' => Platform::PLATFORM_CODE_SHOPEE]) . '">' . $afterSalesOrder['after_sale_id'] . '</a>';
                                    } else if ($afterSalesOrder['type'] == 2) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/aftersales/sales/detailreturn', 'after_sale_id' => $afterSalesOrder['after_sale_id'], 'platform_code' => Platform::PLATFORM_CODE_SHOPEE]) . '">' . $afterSalesOrder['after_sale_id'] . '</a>';
                                    } else if ($afterSalesOrder['type'] == 3) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/aftersales/sales/detailredirect', 'after_sale_id' => $afterSalesOrder['after_sale_id'], 'platform_code' => Platform::PLATFORM_CODE_SHOPEE]) . '">' . $afterSalesOrder['after_sale_id'] . '</a>';
                                    }
                                }
                            } else {
                                echo '没有售后单号';
                            }
                            ?>
                        </td>
                        <td>退款金额</td>
                        <td> <?php echo $issue_list['amount_before_discount'] . '(' . $issue_list['currency'] . ')' ?></td>
                    </tr>
                    <tr>
                        <td>买家纠纷描述</td>
                        <td colspan="3">
                            <?php $img_list = json_decode($issue_list['images']);
                            if (!empty($img_list)) {
                                foreach ($img_list as $v) {
                                    ?>
                                    <img src="<?php echo $v; ?>" class="issueAttachment">
                                    <?php
                                }
                            }
                            ?>
                            <br>
                            <?php echo $issue_list['text_reason'] ?>
                            <div id="translate"></div>
                            <a style="cursor: pointer;"
                               data='<?php echo !empty($issue_list['text_reason'])?$issue_list['text_reason']:""; ?>' class="transClik">点击翻译</a>
                        </td>
                    </tr>
                    <tr>
                        <td>卖家回复</td>
                        <td colspan="3">
                            <?php echo date('Y-m-d H:i:s', $issue_list['due_date']); ?>
                            <br>
                            <?php $image_lists = \app\modules\mails\models\ShopeeAttachment::find()->where(['returnsn' => $issue_list['returnsn']])->asArray()->one();
                            if (!empty($image_lists)) {
                                $image_urls = json_decode($image_lists['shopee_image_url'], true);
                                if (!empty($image_urls)) {
                                    foreach ($image_urls as $image_list) {
                                        echo "<img src='$image_list' class='issueAttachment'>";
                                    }
                                }
                            }
                            ?>
                            <br>
                            <?php echo json_decode($issue_list['dispute_text_reason'])[0] ?>
                        </td>
                    </tr>

                <?php } else { ?>
                    <tr>
                        <td>没有找到纠纷信息</td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</div>
<div class="modal fade in" id="seeIssueAttachmentModal" tabindex="-1" role="dialog"
     aria-labelledby="seeIssueAttachmentLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">查看凭证</h4>
            </div>
            <div class="modal-body">
                <img src="" style="width:100%;height:auto;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        //todo
        $(".transClik").click(function () {
            var sl = 'auto';
            var tl = 'en';
            var message = $(this).attr('data');
            var that = $(this);
            if (message.length == 0) {
                layer.msg('获取需要翻译的内容有错!');
                return false;
            }
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['ebayinboxsubject/translate']);?>',
                data: {'sl': sl, 'tl': tl, 'returnLang': 1, 'content': message},
                success: function (data) {
                    if (data) {
                        var htm = '<b style="color:green;">' + data.text + '</b>';
                        $("#translate").append(htm);
                        that.remove();
                    }
                }
            });
        });

        //查看凭证
        $(".issueAttachment").on("click", function () {
            var url = $(this).attr("src");
            $("#seeIssueAttachmentModal img").attr("src", url);
            $("#seeIssueAttachmentModal").modal("show");
            return false;
        });
        $("#seeIssueAttachmentModal").on('hidden.bs.modal', function (e) {
            $("#seeIssueAttachmentModal img").attr("src", "");
        });

        $(".transClik").click(function(){
            var sl = 'auto';
            var tl = 'en';
            var message = $(this).attr('data');
            var that = $(this);
            if(message.length == 0)
            {
                layer.msg('获取需要翻译的内容有错!');
                return false;
            }
            $.ajax({
                type:"POST",
                dataType:"JSON",
                url:'<?php echo Url::toRoute(['ebayinboxsubject/translate']);?>',
                data:{'sl':sl,'tl':tl,'returnLang':1,'content':message},
                success:function(data){
                    if(data){
                        var htm = '<b style="color:green;">'+data.text+'</b>';
                        $("#translate").append(htm);
                        that.remove();
                    }
                }
            });
        });
    });
</script>