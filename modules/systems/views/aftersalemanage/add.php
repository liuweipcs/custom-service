<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;

?>
<div class="popup-wrapper">
    <style>
        .select2-container--krajee {
            width: 120px !important;
        }

        #addAftersaleManage {
            margin: 20px auto 0 auto;
            width: 90%;
            height: auto;
            border-collapse: collapse;
        }

        #addAftersaleManage td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #addAftersaleManage td.col1 {
            width: 150px;
            text-align: right;
            font-weight: bold;
        }

        #addAftersaleRuleLine {
            margin-bottom: 10px;
        }

        #addAftersaleRule {
            width: 100%;
            border-collapse: collapse;
        }

        #addAftersaleRule td {
            text-align: center;
        }

        #addAftersaleRule td span.glyphicon-remove {
            font-size: 24px;
            color: red;
            cursor: pointer;
        }

        #hideAftersaleRule {
            display: none;
        }

        #selOrderStatus .chkItem, #selSkuStatus .chkItem {
            width: 185px;
            display: inline-block;
            margin-bottom: 5px;
        }

        .erpOrderStatusItemBtn, .skuStatusItemBtn {
            margin-bottom: 5px;
        }

        .erpOrderStatusItem button, .skuStatusItem button {
            float: left;
        }

        .erpOrderStatusItem .selItem, .skuStatusItem .selItem {
            margin-bottom: 3px;
            margin-right: 3px;
        }
    </style>
    <div style="display:none;">
        <?php echo Select2::widget(['name' => '']); ?>
    </div>
    <?php
    $form = ActiveForm::begin([
        'id' => 'addAftersaleManageForm',
        'action' => Url::toRoute(['/systems/aftersalemanage/add']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addAftersaleManage">
            <tr>
                <td class="col1">规则名称：</td>
                <td><input type="text" name="rule_name" class="form-control"></td>
            </tr>
            <tr>
                <td class="col1">平台：</td>
                <td>
                    <select name="platform_code" class="form-control">
                        <option value="">请选择</option>
                        <?php if (!empty($platformList)) { ?>
                            <?php foreach ($platformList as $key => $value) { ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="col1">售后规则：</td>
                <td>
                    <button class="btn btn-primary" id="addAftersaleRuleLine">新增</button>
                    <table id="addAftersaleRule">
                        <thead>
                        <tr>
                            <td>平台退款原因</td>
                            <td>ERP订单状态</td>
                            <td>SKU状态</td>
                            <td>订单利润</td>
                            <td>责任所属部门</td>
                            <td>原因类别</td>
                            <td>亏损计算方式</td>
                            <td>操作</td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr id="hideAftersaleRule" data-startindex="1">
                            <td>
                                <select name="platform_reason_code[0]" class="form-control" disabled>
                                    <option value="">请选择</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-primary erpOrderStatusItemBtn" data-index="0">选择ERP订单状态</button>
                                <div class="erpOrderStatusItem"></div>
                            </td>
                            <td>
                                <button class="btn btn-primary skuStatusItemBtn" data-index="0">选择SKU状态</button>
                                <div class="skuStatusItem"></div>
                            </td>
                            <td>
                                <select name="order_profit_cond[0]" class="form-control" style="width:120px;float:left;" disabled>
                                    <option value="">请选择</option>
                                    <option value="1">大于</option>
                                    <option value="2">大于等于</option>
                                    <option value="3">小于</option>
                                    <option value="4">小于等于</option>
                                </select>
                                <div class="input-group" style="width:120px;float:left;">
                                    <input type="text" name="order_profit_value[0]" class="form-control" disabled>
                                    <span class="input-group-addon">元</span>
                                </div>
                            </td>
                            <td>
                                <select class="form-control" name="department_id[0]" disabled>
                                    <?php if (!empty($departmentList)) { ?>
                                        <?php foreach ($departmentList as $key => $value) { ?>
                                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                            </td>
                            <td>
                                <select class="form-control" name="reason_id[0]" disabled>
                                    <?php if (!empty($reasonList)) { ?>
                                        <?php foreach ($reasonList as $key => $value) { ?>
                                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="formula_name[0]" value="" readonly disabled>
                                <input type="hidden" name="formula_id[0]" value="" disabled>
                            </td>
                            <td>
                                <span class="glyphicon glyphicon-remove"></span>
                            </td>
                        </tr>
                        <tr data-index="1">
                            <td>
                                <?php
                                echo Select2::widget([
                                    'name' => 'platform_reason_code[1]',
                                    'data' => ['' => '请选择'],
                                ]);
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-primary erpOrderStatusItemBtn" data-index="1">选择ERP订单状态</button>
                                <div class="erpOrderStatusItem"></div>
                            </td>
                            <td>
                                <button class="btn btn-primary skuStatusItemBtn" data-index="1">选择SKU状态</button>
                                <div class="skuStatusItem"></div>
                            </td>
                            <td>
                                <select name="order_profit_cond[1]" class="form-control" style="width:120px;float:left;">
                                    <option value="">请选择</option>
                                    <option value="1">大于</option>
                                    <option value="2">大于等于</option>
                                    <option value="3">小于</option>
                                    <option value="4">小于等于</option>
                                </select>
                                <div class="input-group" style="width:120px;float:left;">
                                    <input type="text" name="order_profit_value[1]" class="form-control">
                                    <span class="input-group-addon">元</span>
                                </div>
                            </td>
                            <td>
                                <?php
                                echo Select2::widget([
                                    'name' => 'department_id[1]',
                                    'data' => $departmentList,
                                ]);
                                ?>
                            </td>
                            <td>
                                <?php
                                echo Select2::widget([
                                    'name' => 'reason_id[1]',
                                    'data' => $reasonList,
                                ]);
                                ?>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="formula_name[1]" value="" readonly disabled>
                                <input type="hidden" name="formula_id[1]" value="">
                            </td>
                            <td>
                                <span class="glyphicon glyphicon-remove"></span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="col1">售后类型：</td>
                <td>
                    <input type="radio" name="aftersale_type" value="1" checked>退款
                </td>
            </tr>
            <tr>
                <td class="col1">是否自动审核：</td>
                <td>
                    <input type="radio" name="auto_audit" value="1">是
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="auto_audit" value="0" checked>否
                </td>
            </tr>
            <tr>
                <td class="col1">状态：</td>
                <td>
                    <input type="radio" name="status" value="1" checked>有效
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="status" value="0">无效
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <input type="submit" class="btn btn-primary btn-sm" value="添加">
                    <input type="reset" class="btn btn-default btn-sm" value="取消">
                </td>
            </tr>
        </table>
    </div>
    <div class="popup-footer"></div>
    <?php
    ActiveForm::end();
    ?>
</div>

<div id="selOrderStatus" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">选择ERP订单状态</h4>
            </div>
            <div class="modal-body">
                <button id="allSelOrderStatus" class="btn btn-primary btn-xs">全选</button>
                <br>
                <form id="selOrderStatusForm">
                    <?php if (!empty($erpOrderStatusList)) { ?>
                        <?php foreach ($erpOrderStatusList as $key => $value) { ?>
                            <p class="chkItem"><input type="checkbox" value="<?php echo $key; ?>" data-label="<?php echo $value; ?>"><?php echo $value; ?></p>
                        <?php } ?>
                    <?php } ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmSelOrderStatus">确定</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<div id="selSkuStatus" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">选择SKU状态</h4>
            </div>
            <div class="modal-body">
                <button id="allSelSkuStatus" class="btn btn-primary btn-xs">全选</button>
                <br>
                <form id="selSkuStatusForm">
                    <?php if (!empty($skuStatusList)) { ?>
                        <?php foreach ($skuStatusList as $key => $value) { ?>
                            <p class="chkItem"><input type="checkbox" name="" value="<?php echo $key; ?>" data-label="<?php echo $value; ?>"><?php echo $value; ?></p>
                        <?php } ?>
                    <?php } ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmSelSkuStatus">确定</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#allSelOrderStatus").on("click", function () {
             $("#selOrderStatusForm input").each(function () {
                 $(this).attr("checked", "checked");
                 $(this)[0].checked = true;
             });
        });

        $("#allSelSkuStatus").on("click", function() {
             $("#selSkuStatusForm input").each(function() {
                 $(this).attr("checked", "checked");
                 $(this)[0].checked = true;
             });
        });

        $("select[name='platform_code']").on("change", function() {
            var platform_code = $(this).val();

            if (platform_code.length == 0) {
                layer.alert("请选择平台");
                return false;
            }

            $.post("<?php echo Url::toRoute('/systems/aftersalemanage/getdisputereasonlist'); ?>", {
                "platform_code" : platform_code
            }, function (data) {
                var opt = "<option value=''>请选择</option>";
                if (data["code"] == 1) {
                    var data = data["data"];
                    for (var ix in data) {
                        opt += "<option value='" + data[ix]["id"] + "'>" + data[ix]["name"] + "</option>"
                    }
                }
                $("select[name^='platform_reason_code']").html(opt);
            }, "json");
        });

        $("#addAftersaleRuleLine").on("click", function () {
            var startIndex = $("#hideAftersaleRule").attr("data-startindex");
            startIndex = parseInt(startIndex) + 1;
            $("#hideAftersaleRule").attr("data-startindex", startIndex);

            var line = $("#hideAftersaleRule").clone();
            line.removeAttr("id");
            line.removeAttr("data-startindex");
            line.attr("data-index", startIndex);
            line.find("select[name^='platform_reason_code']").attr("name", "platform_reason_code[" + startIndex + "]").removeAttr("disabled");
            line.find("select[name^='order_profit_cond']").attr("name", "order_profit_cond[" + startIndex + "]").removeAttr("disabled");
            line.find("input[name^='order_profit_value']").attr("name", "order_profit_value[" + startIndex + "]").removeAttr("disabled");
            line.find("select[name^='department_id']").attr("name", "department_id[" + startIndex + "]").removeAttr("disabled");
            line.find("select[name^='reason_id']").attr("name", "reason_id[" + startIndex + "]").removeAttr("disabled");
            line.find("input[name^='formula_name']").attr("name", "formula_name[" + startIndex + "]");
            line.find("input[name^='formula_id']").attr("name", "formula_id[" + startIndex + "]").removeAttr("disabled");

            line.find("button.erpOrderStatusItemBtn").attr("data-index", startIndex);
            line.find("button.skuStatusItemBtn").attr("data-index", startIndex);

            line.find("select[name^='platform_reason_code']").select2({'theme': 'krajee'});
            line.find("select[name^='department_id']").select2({'theme': 'krajee'});
            line.find("select[name^='reason_id']").select2({'theme': 'krajee'});

            $("#addAftersaleRule > tbody").append(line);
            return false;
        });

        $("#addAftersaleRule").on("click", "span.glyphicon-remove", function () {
            $(this).parent("td").parent("tr").remove();
            return false;
        });

        $("#addAftersaleRule").on("click", "button.erpOrderStatusItemBtn", function () {
            var index = $(this).attr("data-index");
            $("#confirmSelOrderStatus").attr("data-index", index);
            $("#selOrderStatus").modal("show");
            return false;
        });

        $("#addAftersaleRule").on("click", ".erpOrderStatusItem span.glyphicon-remove", function () {
            var status = $(this).attr("data-status");
            $(this).parents("div.erpOrderStatusItem").find("input[name^='erp_order_status'][value='" + status + "']").remove();
            $(this).parent("button.selItem").remove();
            return false;
        });

        $("#confirmSelOrderStatus").on("click", function () {
            var index = $(this).attr("data-index");
            var content = $("#addAftersaleRule tr[data-index='" + index + "']").find("div.erpOrderStatusItem");
            var html = "";
            $("#selOrderStatus input[type='checkbox']:checked").each(function () {
                var label = $(this).attr("data-label");
                html += "<button type='button' class='btn btn-default btn-xs selItem'>";
                html += label + "&nbsp;&nbsp;<span class='glyphicon glyphicon-remove' style='font-size:16px;' data-status='" + $(this).val() + "'></span>";
                html += "</button>";
                html += "<input type='hidden' name='erp_order_status[" + index + "][]' value='" + $(this).val() + "'>";
            });
            content.html(html);
            $("#selOrderStatus").modal("hide");
        });

        $("#selOrderStatus").on("hide.bs.modal", function () {
            $("#selOrderStatusForm")[0].reset();
        });

        $("#addAftersaleRule").on("click", "button.skuStatusItemBtn", function () {
            var index = $(this).attr("data-index");
            $("#confirmSelSkuStatus").attr("data-index", index);
            $("#selSkuStatus").modal("show");
            return false;
        });

        $("#addAftersaleRule").on("click", ".skuStatusItem span.glyphicon-remove", function () {
            var status = $(this).attr("data-status");
            $(this).parents("div.skuStatusItem").find("input[name^='sku_status'][value='" + status + "']").remove();
            $(this).parent("button.selItem").remove();
            return false;
        });

        $("#confirmSelSkuStatus").on("click", function () {
            var index = $(this).attr("data-index");
            var content = $("#addAftersaleRule tr[data-index='" + index + "']").find("div.skuStatusItem");
            var html = "";
            $("#selSkuStatus input[type='checkbox']:checked").each(function () {
                var label = $(this).attr("data-label");
                html += "<button type='button' class='btn btn-default btn-xs selItem'>";
                html += label + "&nbsp;&nbsp;<span class='glyphicon glyphicon-remove' style='font-size:16px;' data-status='" + $(this).val() + "'></span>";
                html += "</button>";
                html += "<input type='hidden' name='sku_status[" + index + "][]' value='" + $(this).val() + "'>";
            });
            content.html(html);
            $("#selSkuStatus").modal("hide");
        });

        $("#selSkuStatus").on("hide.bs.modal", function () {
            $("#selSkuStatusForm")[0].reset();
        });

        $("#addAftersaleRule").on("change", "select[name^='department_id']", function () {
            var reason = $(this).parent("td").parent("tr").find("select[name^='reason_id']");
            var department_id = $(this).val();

            $.post("<?php echo Url::toRoute('/systems/aftersalemanage/getreasonlist'); ?>", {
                "department_id": department_id
            }, function (data) {
                if (data["code"] == 1) {
                    var data = data["data"];
                    var opt = "<option value=''>请选择</option>";
                    for (var ix in data) {
                        opt += "<option value='" + data[ix]["id"] + "'>" + data[ix]["name"] + "</option>";
                    }
                    reason.html(opt);
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
        });

        $("#addAftersaleRule").on("change", "select[name^='reason_id']", function () {
            var tr = $(this).parent("td").parent("tr");
            var department_id = $(this).parent("td").parent("tr").find("select[name^='department_id']").val();
            var reason_id = $(this).val();

            $.post("<?php echo Url::toRoute('/systems/aftersalemanage/getformula'); ?>", {
                "department_id": department_id,
                "reason_id": reason_id
            }, function (data) {
                if (data["code"] == 1) {
                    var data = data["data"];
                    tr.find("input[name^='formula_name']").val(data["name"]);
                    tr.find("input[name^='formula_id']").val(data["id"]);
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
        });

        $("#addAftersaleManageForm input[type='submit']").on("click", function () {
            var params = $("#addAftersaleManageForm").serialize();
            $.post("<?php echo Url::toRoute(['/systems/aftersalemanage/add']); ?>", params, function (data) {
                if (data["code"] == "200") {
                    layer.msg(data["message"], {icon : 1}, function() {
                        top.layer.closeAll("iframe");
                        top.refreshTable("<?php echo Url::toRoute(['/systems/aftersalemanage/list']); ?>");
                    });
                } else {
                    layer.msg(data["message"], {icon : 5});
                }
            }, "json");

            return false;
        });
    });
</script>
