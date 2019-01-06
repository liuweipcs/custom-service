<?php

use yii\helpers\Url;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AliexpressDisputeAttachments;

?>

<style>
    #updateIssueInfo {
        margin-bottom: 5px;
    }

</style>
<input type="hidden" name="ordersn" value="<?php echo !empty($cancellationList['ordersn'])?$cancellationList['ordersn']:''?>">
<input type="hidden" name="order_id" value="<?php echo !empty($info['order_id'])?$info['order_id']:''?>">

<div>
    <button type="button" id="updateIssueInfo" class="btn btn-primary"
            data-ordersn="<?php echo !empty($cancellationList['ordersn']) ? $cancellationList['ordersn'] : ''; ?>">
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
                <?php if (!empty($cancellationList)) { ?>
                    <tr>
                        <td>取消原因:</td>
                        <td colspan="3">
                            <?php echo !empty($cancellationList['cancel_reason']) ? $cancellationList['cancel_reason'] : ''; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>状态：</td>
                        <td colspan="3">
                            <?php
                            echo !empty($cancellationList['order_status']) ? $cancellationList['order_status'] : '';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>纠纷开始时间：</td>
                        <td><?php echo !empty($cancellationList['update_time']) ? date('Y-m-d H:i:s', $cancellationList['update_time']) : ''; ?></td>
                        <td>回复截止时间：</td>
                        <td><?php echo date('Y-m-d H:i:s', $cancellationList['update_time'] + 3600 * 48); ?></td>
                    </tr>
                    <tr>
                        <td>售后单号：</td>
                        <td colspan="3">
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
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td>没有找到交易信息</td>
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
<button type="button" id="" class="btn btn-primary agreeSolutionBtn" <?php if($cancellationList['is_deal']==2){ echo 'disabled';}?>>
    同意
</button>

<button type="button" id="" class="btn btn-primary refuseAndAddSolutionBtn" <?php if($cancellationList['is_deal']==2){ echo 'disabled';}?>>
    拒绝
</button>
<button type="button" id="mark_deal" class="btn btn-primary" <?php if($cancellationList['is_deal']==2){ echo 'disabled';}?>
        data-ordersn="<?php echo !empty($cancellationList['ordersn']) ? $cancellationList['ordersn'] : ''; ?>">
    标记成已处理
</button>

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
                <p>若同意买家取消订单的申请，此订单将立即被取消，并将退款退回给买家，同时，不要安排商品出货。若你已将商品出货，请拒绝此买家的申请。确认同意此申请？</p>
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
                <input type="hidden" name="ordersn" value="<?php echo !empty($cancellationList['ordersn']) ? $cancellationList['ordersn'] : ''; ?>">
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
                <p>若同意买家取消订单的申请，此订单将立即被取消，并将退款退回给买家，同时，不要安排商品出货。若你已将商品出货，请拒绝此买家的申请。确认同意此申请？</p>
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
                <input type="hidden" name="ordersn" value="<?php echo !empty($cancellationList['ordersn']) ? $cancellationList['ordersn'] : ''; ?>">
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
                <p>若同意买家取消订单的申请，此订单将立即被取消，并将退款退回给买家，同时，不要安排商品出货。若你已将商品出货，请拒绝此买家的申请。确认同意此申请？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="remindAgreeSolutionBtn">确认</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <input type="hidden" name="account_id"
                       value="<?php echo !empty($account_id) ? $account_id : ''; ?>">
                <input type="hidden" name="ordersn" value="<?php echo !empty($cancellationList['ordersn']) ? $cancellationList['ordersn'] : ''; ?>">
            </div>
        </div>
    </div>
</div>


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
                            <span style="color:red">* 如果拒绝此申请，订单将维持原本状态。如果还未出货，请在拒绝此申请后安排出货。确认拒绝此申请？</span>
                        </div>
                    </div>
                    <input type="hidden" name="ordersn" value="<?php echo !empty($cancellationList['ordersn']) ? $cancellationList['ordersn'] : ''; ?>">
                    <input type="hidden" name="account_id" value="<?php echo !empty($account_id) ? $account_id : ''; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="addSolutionBtn">保存</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">

    $(function () {
        //同意方案
        $(".agreeSolutionBtn").on("click", function () {
            var ordersn = $("input[name=ordersn]").val();
            if (ordersn.length == 0) {
                layer.alert("平台订单号不能为空");
                return false;
            }
            if ($("input[type='checkbox']").is(':checked') == true) {
                $.post("<?php echo Url::toRoute(['/mails/shopeecancellation/judgerule']); ?>", {
                    'ordersn': ordersn, 'dispute_reason': '客户要求取消',
                }, function (data) {
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
        $("#agreeSolutionModal").on('hidden.bs.modal', function (e) {
            $("#addSolutionForm")[0].reset();
        });

        //最终同意纠纷
        $("#yesRuleAgreeSolutionBtn,#noRuleAgreeSolutionBtn,#remindAgreeSolutionBtn").on("click", function () {
            var _this = $(this);
            var ordersn = $(this).siblings("input[name='ordersn']").val();
            var account_id = $(this).siblings("input[name='account_id']").val();
            if (!ordersn) {
                layer.alert('未找到订单编号');
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

            $.post("<?php echo Url::toRoute(['/mails/shopeecancellation/agreeissuesolution']); ?>", {
                'ordersn': ordersn,
                'account_id': account_id,
            }, function (data) {
                if (data["code"] == 1) {
                    layer.alert("同意方案成功");
                    if (_this.attr("id") == "yesRuleAgreeSolutionBtn") {
                        $.ajax({
                            url: "<?php echo Url::toRoute(['/mails/shopeecancellation/createaftersaleorder']); ?>",
                            async: false,
                            type: "POST",
                            data: {"ordersn" : ordersn, "amount" : amount},
                            dataType: "json",
                            success: function (data) {
                            }
                        });
                    }
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
            return false;
        });


        //拒绝
        $("#addSolutionBtn").on("click", function () {
            var ordersn = $("input[name=ordersn]").val();
            var account_id = $("input[name=account_id]").val();
            if (!ordersn) {
                layer.alert('未找到平台订单号',{icon:2});
                return false;
            }
            if (!account_id) {
                layer.alert('无账号id',{icon:2});
                return false;
            }
            $.post("<?php echo Url::toRoute(['/mails/shopeecancellation/refuseissuesolution']) ?>", { 'ordersn': ordersn,
                'account_id': account_id}, function (data) {
                if (data["code"] == 1) {
                    layer.alert(data["message"]);
                    $("#addSolutionModal").modal("hide");
                    window.location.replace("<?php echo Url::toRoute(['/mails/shopeecancellation/handle', 'id' => $id]); ?>");

                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
            return false;
        });


        //更新纠纷信息
        $("#updateIssueInfo").on("click", function () {
            var ordersn = $(this).attr("data-ordersn");
            if (ordersn.length == 0) {
                layer.alert("平台订单号不能为空");
                return false;
            }
            $.post("<?php echo Url::toRoute(['/mails/shopeecancellation/updateissueinfo']) ?>", {
                "ordersn": ordersn,
            }, function (data) {
                if (data["code"] == 1) {
                    layer.alert("更新成功");
                    window.location.replace("<?php echo Url::toRoute(['/mails/shopeecancellation/handle', 'id' => $id]); ?>");
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
        });
    });
    //标记处理
    $("#mark_deal").on("click", function () {
        var ordersn = $(this).attr("data-ordersn");
        if (ordersn.length == 0) {
            layer.alert("平台订单号不能为空");
            return false;
        }
        $.post("<?php echo Url::toRoute(['/mails/shopeecancellation/markdeal']) ?>", {
            "ordersn": ordersn,
        }, function (data) {
            if (data["code"] == 1) {
                layer.alert("标记成功");
                window.location.replace("<?php echo Url::toRoute(['/mails/shopeecancellation/handle', 'id' => $id]); ?>");
            } else {
                layer.alert(data["message"]);
            }
        }, "json");
    });
</script>