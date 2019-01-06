<?php

use yii\helpers\Url;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AliexpressDisputeAttachments;

?>
<script type="text/javascript" src="/js/jquery.form.js"></script>
<style>
    #updateIssueInfo {
        margin-bottom: 5px;
    }

    .issueAttachment {
        border: 1px solid #ccc;
        padding: 2px;
        width: 60px;
        height: 60px;
        cursor: pointer;
    }
</style>
<input type="hidden" name="order_id" value="<?php echo !empty($info['order_id']) ? $info['order_id'] : '' ?>">
<input type="hidden" name="ordersn" value="<?php echo !empty($issue_list['ordersn']) ? $issue_list['ordersn'] : '' ?>">
<input type="hidden" name="dispute_reason"
       value="<?php echo !empty($issue_list['dispute_reason']) ? json_decode($issue_list['dispute_reason'])[0] : '' ?>">
<input type="hidden" name="reason"
       value="<?php echo !empty($issue_list['reason']) ?$issue_list['reason'] : '' ?>">
<div>
    <button type="button" id="updateIssueInfo" class="btn btn-primary"
            data-returnsn="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
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

<div>
    <label class="checkbox-inline">
        <input name="after_sales_type[]" type="checkbox" id="refund-input" <?php if(!empty($afterSalesOrders)){ echo 'checked';}?>
               value="<?php echo \app\modules\aftersales\models\AfterSalesOrder::ORDER_TYPE_REFUND ?>">同意纠纷，自动建立退款售后单
    </label>
</div>
<button type="button" id="" class="btn btn-primary agreeSolutionBtn"  <?php if($issue_list['is_deal']==2){ echo 'disabled';}?>
        data-issueid="<?php echo !empty($returnsn) ? $returnsn : ''; ?>" >
    同意
</button>

<button type="button" id="" class="btn btn-primary refuseAndAddSolutionBtn" <?php if($issue_list['is_deal']==2){ echo 'disabled';}?>
        data-issueid="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
    拒绝
</button>
<button type="button" id="mark_deal" class="btn btn-primary" <?php if($issue_list['is_deal']==2){ echo 'disabled';}?>
        data-returnsn="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
    标记成已处理
</button>
<!--拒绝纠纷-->
<div class="modal fade in" id="addSolutionModal" tabindex="-1" role="dialog" aria-labelledby="addSolutionModalLabel"
     style="top:300px;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">拒绝纠纷</h4>
            </div>
            <div class="modal-body">
                <form id="addSolutionForm" class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <span style="color:red">* 注意：如果拒绝申请，订单将维持原本状态</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">选择原因：</label>
                        <div class="col-sm-10">
                            <select name="dispute_reason" class="form-control">
                                <option value="">请选择</option>
                                <?php if (!empty($ReturnDisputeReasonList)) {
                                    ; ?>
                                    <?php foreach ($ReturnDisputeReasonList as $v) { ?>
                                        <option value="<?php echo $v ?>">
                                            <?php
                                            echo !empty($v) ? $v : '';
                                            ?>
                                        </option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="dispute_text_reason">
                        <label class="col-sm-2">原因：</label>
                        <div class="col-sm-10">
                            <textarea class="form-control" name="dispute_text_reason" id="" cols="10"
                                      rows="5"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">Email信箱：</label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <input type="text" class="form-control" name="email">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">争议证明：</label>
                        <div class="col-sm-10 ebay_reply_upload_image_display_area">
                            <?php $image_lists = \app\modules\mails\models\ShopeeAttachment::find()->where(['returnsn' => $issue_list['returnsn']])->asArray()->one();
                            if (!empty($image_lists)) {
                                $image_urls = json_decode($image_lists['image_url'], true);
                                if (!empty($image_urls)) {
                                    foreach ($image_urls as &$image_list) {
                                        $image_list_arr[] = $image_list;
                                        echo '<div class="ebay_reply_upload_image_display"><img class="issueAttachment" style="height:50px;width:50px;" src="', $image_list, '" ><a class="ebay_reply_upload_image_delete">删除</a></div>';
                                    }
                                }
                            }
                            ?>
                            <input type="hidden" value='<?php echo !empty($image_list_arr)?htmlspecialchars(json_encode($image_list_arr)):"" ?>'
                                   name="images">
                            <button type="button" id="uploadIssueImg" class="btn btn-primary"
                                    data-issueid="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
                                上传凭证
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="returnsn" value="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
                    <input type="hidden" name="account_id"
                           value="<?php echo !empty($account_id) ? $account_id : ''; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="addSolutionBtn">保存</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<!--更新-->
<div class="modal fade in" id="uploadIssueImgModal" tabindex="-1" role="dialog"
     aria-labelledby="uploadIssueImgModalLabel"
     style="top:300px;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">上传凭证</h4>
            </div>
            <div class="modal-body">
                <form id="uploadIssueImgForm" class="form-horizontal" enctype="multipart/form-data">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <span style="color:red">* 注意：图片只能是JPG,JPEG,PNG格式,2M大小</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <input type="file" id="" name="image[]" style="display: inline-block; width: 80%;"/>
                            <a href="javascript:void(0);" onclick="doaddfile(this);">添加</a>
                            <a href="javascript:void(0);" onclick="deletefile(this);">删除</a>
                        </div>
                    </div>
                    <input type="hidden" name="issue_id" value="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
                    <input type="hidden" name="id" value="<?php echo !empty($id) ? $id : 0; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="uploadIssueImgBtn">上传</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<!--查看-->
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
<!--同意纠纷有售后单-->
<div class="modal fade in" id="agreeIssueYesRule" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">同意纠纷</h4>
            </div>
            <div class="modal-body">
                <p>确认退款吗?一旦你接受了，付款将被退还给买方。</p>
                <br>
                <p>根据售后单规则配置，同意纠纷时创建的售后单信息如下：</p>
                <table class="table">
                    <tr>
                        <td>责任部门</td>
                        <td>售后原因</td>
                        <td>金额/币种</td>
                    </tr>
                    <tr>
                        <td id="afterSaleRuleDepartmentId"></td>
                        <td id="afterSaleRuleReasonId"></td>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control" id="afterSaleRuleAmount">
                                <div class="input-group-addon" id="afterSaleRuleCurrency">USD</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="yesRuleAgreeSolutionBtn">确认</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <input type="hidden" name="account_id"
                       value="<?php echo !empty($account_id) ? $account_id : ''; ?>">
                <input type="hidden" name="returnsn" value="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
            </div>
        </div>
    </div>
</div>
<!--同意纠纷未找到售后单规则-->
<div class="modal fade in" id="agreeIssueNoRule" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">同意纠纷</h4>
            </div>
            <div class="modal-body">
                <p>确认退款吗?一旦你接受了，付款将被退还给买方。</p>
                <br>
                <p>根据售后单规则配置，没有匹配到规则，您可以：</p>
                <a href="<?php echo Url::toRoute(['/systems/aftersalemanage/add']); ?>" target="_blank" style="color:#169BD5;">添加售后单规则</a>
                <a href="#" id="manualRegAfterSaleOrder" target="_blank" style="color:#169BD5;">手动登记售后单</a>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="noRuleAgreeSolutionBtn">确认</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <input type="hidden" name="account_id"
                       value="<?php echo !empty($account_id) ? $account_id : ''; ?>">
                <input type="hidden" name="returnsn" value="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
            </div>
        </div>
    </div>
</div>
<!--同意纠纷未选择售后单规则-->
<div class="modal fade in" id="agreeIssueRemind" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">同意纠纷</h4>
            </div>
            <div class="modal-body">
                <p>确认退款吗?一旦你接受了，付款将被退还给买方</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="remindAgreeSolutionBtn">确认</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <input type="hidden" name="account_id"
                       value="<?php echo !empty($account_id) ? $account_id : ''; ?>">
                <input type="hidden" name="returnsn" value="<?php echo !empty($returnsn) ? $returnsn : ''; ?>">
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        //同意方案
        $(".agreeSolutionBtn").on("click", function () {
            var ordersn = $("input[name=ordersn]").val();
            var reason = $("input[name=reason]").val();
            if (ordersn.length == 0) {
                layer.alert("平台订单号不能为空");
                return false;
            }
            if (reason.length == 0) {
                layer.alert("纠纷原因不能为空");
                return false;
            }
            if ($("input[type='checkbox']").is(':checked') == true) {
                $.post("<?php echo Url::toRoute(['/mails/shopeedispute/judgerule']); ?>", {
                    'ordersn': ordersn, 'dispute_reason': reason,
                }, function (data) {
                    console.log(data);
                    if (data["code"] == 1) {

                        if (data["have"] == 1) {
                            var data = data["data"];
                            $("#afterSaleRuleDepartmentId").text(data["department_name"]);
                            $("#afterSaleRuleReasonId").text(data["reason_name"]);
                            $("#afterSaleRuleAmount").val(data["order_amount"]);
                            $("#afterSaleRuleCurrency").text(data["order_currency"]);
                            $("#agreeIssueYesRule").modal("show");
                        } else {
                            var data = data["data"];
                            $("#manualRegAfterSaleOrder").attr("href", "/aftersales/order/add?platform=SHOPEE&order_id=" + (data["order_id"] ? data["order_id"] : ""));
                            $("#agreeIssueNoRule").modal("show");
                        }
                    } else {
                        layer.alert(data["message"]);
                    }
                }, "json");
            }else{
                $("#agreeIssueRemind").modal("show");
            }
            return false;
        });


        //新增方案//拒绝
        $(".refuseAndAddSolutionBtn").on("click", function () {
            $("#addSolutionModal").modal("show");
            return false;
        });

        //弹窗关闭时清空数据
        $("#addSolutionModal").on('hidden.bs.modal', function (e) {
            $("#addSolutionForm")[0].reset();
        });

        //最终同意纠纷
        $("#yesRuleAgreeSolutionBtn,#noRuleAgreeSolutionBtn,#remindAgreeSolutionBtn").on("click", function () {
            var _this = $(this);
            var returnsn = $(this).siblings("input[name='returnsn']").val();
            var account_id = $(this).siblings("input[name='account_id']").val();

            if (!returnsn) {
                layer.alert('未找到退款编号');
                return false;
            }
            if (!account_id) {
                layer.alert('无账号id');
                return false;
            }

            if (_this.attr("id") == "yesRuleAgreeSolutionBtn") {
                var amount = parseFloat($("#afterSaleRuleAmount").val());
                if (isNaN(amount)) {
                    layer.alert("请填写正确的金额");
                    return false;
                }
                if (amount < 0) {
                    layer.alert("金额不能小于0");
                    return false;
                }
            }

            $.post("<?php echo Url::toRoute(['/mails/shopeedispute/confirmreturn']); ?>", {
                'returnsn': returnsn,
                'account_id': account_id,
            }, function (data) {
                if (data["code"] == 1) {
                    layer.alert("同意方案成功");
                    if (_this.attr("id") == "yesRuleAgreeSolutionBtn") {
                        $.ajax({
                            url: "<?php echo Url::toRoute(['/mails/shopeedispute/createaftersaleorder']); ?>",
                            async: false,
                            type: "POST",
                            data: {"returnsn" : returnsn, "amount" : amount},
                            dataType: "json",
                            success: function (data) {
                            }
                        });
                    }
                } else {
                    layer.alert(data["message"]);
                }
                window.location.replace("<?php echo Url::toRoute(['/mails/shopeedispute/handle', 'id' => $id]); ?>");

            }, "json");
            return false;
        });
        //添加或修改方案
        $("#addSolutionBtn").on("click", function () {
            var returnsn = $("input[name='returnsn']").val();
            var dispute_reason = $("select[name=dispute_reason]").val();
            var dispute_text_reason = $("textarea[name=dispute_text_reason]").val();
            var email = $("input[name=email]").val();
            var images = $("input[name=images]").val();
            var account_id = $("input[name=account_id]").val();
            if (!returnsn) {
                layer.alert("没有编号");
                return false;
            }
            if (!account_id) {
                layer.alert("没有账号id");
                return false;
            }
            if (!dispute_reason) {
                layer.alert("请选择原因");
                return false;
            }
            if (!dispute_text_reason) {
                layer.alert("请输入原因描述");
                return false;
            }
            if (!email) {
                layer.alert("请输入卖家邮箱地址");
                return false;
            }
            if (!images) {
                layer.alert("请上传图片");
                return false;
            }
            var params = $("#addSolutionForm").serialize();
            $.post("<?php echo Url::toRoute(['/mails/shopeedispute/disputereturn']) ?>", params, function (data) {
                if (data.code == 200) {
                    layer.alert(data.msg);
                    $("#addSolutionModal").modal("hide");
                } else {
                    layer.alert(data.msg);
                }
                window.location.replace("<?php echo Url::toRoute(['/mails/shopeedispute/handle', 'id' => $id]); ?>");
            }, "json");
            return false;
        });


        //上传纠纷图片
        $("#uploadIssueImg").on("click", function () {
            $("#uploadIssueImgModal").modal("show");
            return false;
        });
        //弹窗关闭时清空数据
        $("#uploadIssueImgModal").on('hidden.bs.modal', function (e) {
            $("#uploadIssueImgForm")[0].reset();
        });


        //删除图片
        $('.ebay_reply_upload_image_display_area').delegate('.ebay_reply_upload_image_delete', 'click', function () {
            var $this = $(this);
            var delteImageUrl = $this.siblings('img').attr('src');
            var returnsn = $("input[name=returnsn]").val();
            layer.confirm('您确定要删除么？', {
                btn: ['确定', '再考虑一下'] //按钮
            }, function () {
                $.ajax({
                    type: "POST",
                    dataType: "JSON",
                    url: '<?php echo Url::toRoute(['/mails/shopeedispute/deleteimage']); ?>',
                    data: {'url': delteImageUrl, "returnsn": returnsn},
                    success: function (data) {
                        switch (data.status) {
                            case 'error':
                                layer.msg(data.info, {icon: 2, time: 2000});
                                break;
                            case 'success':
                                layer.msg('删除成功', {icon: 1, time: 2000});
                                $this.parent().remove();
                                $('input[name=images]').val(data.image_url);
                                // location.reload();
                        }
                    }
                });
            }, function () {

            });
        });

        //上传纠纷图片
        $("#uploadIssueImgBtn").on("click", function () {
            var image = $("#uploadIssueImgForm input[name='image[]']").val();
            if (image.length == 0 || image == "") {
                layer.alert("请选择上传图片");
                return false;
            }
            $("#uploadIssueImgForm").attr("action", "<?php echo Url::toRoute(['/mails/shopeedispute/addissueimage']) ?>");
            $("#uploadIssueImgForm").attr("method", "post");

            $('#uploadIssueImgForm').ajaxSubmit({
                dataType: 'json',
                beforeSubmit: function (options) {
                    if (!/(gif|jpg|png|jpeg|tif|bmp)/ig.test(options[0].value.type)) {
                        layer.msg('图片格式错误！', {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                        return false;
                    }
                },
                success: function (response) {
                    switch (response.code) {
                        case 201:
                            layer.msg(response.message, {
                                icon: 2,
                                time: 2000 //2秒关闭（如果不配置，默认是3秒）
                            });
                            break;
                        case 200:
                            var templateHtml = '';
                            var json_img = JSON.parse(response.image_url);
                            for (var i = 0; i < json_img.length; i++) {
                                templateHtml += '<div class="ebay_reply_upload_image_display"><img class="issueAttachment" style="height:50px;width:50px;" src="' + json_img[i] + '" alt=""><a class="ebay_reply_upload_image_delete" >删除</a></div>';
                            }
                            $('#uploadIssueImgModal').modal('hide');
                            $('input[name=images]').val(response.image_url);
                            $('.ebay_reply_upload_image_display_area').append(templateHtml);
                    }
                },

            });
            return false;
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

        /**
         * 回复客户邮件内容点击翻译(系统检测到用户语言)
         */
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
    });

    function doaddfile(obj) {
        var str = '<div>' +
            '<input type="file" id="" name="image[]" style="display: inline-block; width: 80%;" /> <a href="javascript:void(0);" onclick="doremovefile(this);">删除</a>' +
            '</div>';

        $(obj).parent('div').after(str);
    }

    function deletefile(obj) {
        $(obj).siblings('input').val('');
    }

    function doremovefile(obj) {
        $(obj).parent('div').remove();
    }

    //更新纠纷信息
    $("#updateIssueInfo").on("click", function () {
        var returnsn = $(this).attr("data-returnsn");

        if (returnsn.length == 0) {
            layer.alert("退款退货编号不能为空");
            return false;
        }

        $.post("<?php echo Url::toRoute(['/mails/shopeedispute/updateissueinfo']) ?>", {
            "returnsn": returnsn,
        }, function (data) {
            if (data["code"] == 1) {
                layer.alert("更新成功");
                window.location.replace("<?php echo Url::toRoute(['/mails/shopeedispute/handle', 'id' => $id]); ?>");
            } else {
                layer.alert(data["message"]);
            }
        }, "json");
    });
    //标记处理
    $("#mark_deal").on("click", function () {
        var returnsn = $(this).attr("data-returnsn");
        if (returnsn.length == 0) {
            layer.alert("退款退货编号不能为空");
            return false;
        }
        $.post("<?php echo Url::toRoute(['/mails/shopeedispute/markdeal']) ?>", {
            "returnsn": returnsn,
        }, function (data) {
            if (data["code"] == 1) {
                layer.alert("标记成功");
                window.location.replace("<?php echo Url::toRoute(['/mails/shopeedispute/handle', 'id' => $id]); ?>");
            } else {
                layer.alert(data["message"]);
            }
        }, "json");
    });

</script>