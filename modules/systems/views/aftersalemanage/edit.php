<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;
use app\modules\systems\models\BasicConfig;

?>
<div class="popup-wrapper">
    <style>
        .select2-container--krajee {
            width: 120px !important;
        }

        #updAftersaleManage {
            margin: 20px auto 0 auto;
            width: 90%;
            height: auto;
            border-collapse: collapse;
        }

        #updAftersaleManage td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #updAftersaleManage td.col1 {
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
        'id' => 'updAftersaleManageForm',
        'action' => Url::toRoute(['/systems/aftersalemanage/edit']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="updAftersaleManage">
            <tr>
                <td class="col1">规则名称：</td>
                <td><input type="text" name="rule_name" class="form-control" value="<?php echo $info['rule_name']; ?>"></td>
            </tr>
            <tr>
                <td class="col1">平台：</td>
                <td>
                    <select name="platform_code" class="form-control">
                        <option value="">请选择</option>
                        <?php if (!empty($platformList)) { ?>
                            <?php foreach ($platformList as $key => $value) { ?>
                                <option value="<?php echo $key; ?>" <?php if ($key == $info['platform_code']) {echo 'selected="selected"';} ?>><?php echo $value; ?></option>
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
                        <tr id="hideAftersaleRule" data-startindex="<?php echo !empty($rules) ? count($rules) : 1; ?>">
                            <td>
                                <select name="platform_reason_code[0]" class="form-control" disabled>
                                    <?php if (!empty($platformReasonList)) { ?>
                                        <?php foreach ($platformReasonList as $key => $value) { ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                        <?php } ?>
                                    <?php } ?>
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
                        <?php if (!empty($rules)) { ?>
                            <?php foreach ($rules as $key => $rule) { ?>
                                <tr data-index="<?php echo ($key + 1); ?>">
                                    <td>
                                        <?php
                                        echo Select2::widget([
                                            'name' => 'platform_reason_code[' . ($key + 1) . ']',
                                            'data' => $platformReasonList,
                                            'value' => $rule['platform_reason_code'],
                                        ]);
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary erpOrderStatusItemBtn" data-index="<?php echo ($key + 1); ?>">选择ERP订单状态</button>
                                        <div class="erpOrderStatusItem">
                                            <?php
                                            if (!empty($rule['erp_order_status'])) {
                                                $erpOrderStatusIds = explode(',', $rule['erp_order_status']);
                                                if (!empty($erpOrderStatusIds)) {
                                                    foreach ($erpOrderStatusIds as $id) {
                                                        echo '<button type="button" class="btn btn-default btn-xs selItem">';
                                                        echo (array_key_exists($id, $erpOrderStatusList) ? $erpOrderStatusList[$id] : '') . '&nbsp;&nbsp;<span class="glyphicon glyphicon-remove" style="font-size:16px;" data-status="' . $id . '"></span>';
                                                        echo '</button>';
                                                        echo '<input type="hidden" name="erp_order_status[' . ($key + 1) . '][]" value="' . $id . '">';
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary skuStatusItemBtn" data-index="<?php echo ($key + 1); ?>">选择SKU状态</button>
                                        <div class="skuStatusItem">
                                            <?php
                                            if (!empty($rule['sku_status'])) {
                                                $skuStatusIds = explode(',', $rule['sku_status']);
                                                if (!empty($skuStatusIds)) {
                                                    foreach ($skuStatusIds as $id) {
                                                        echo '<button type="button" class="btn btn-default btn-xs selItem">';
                                                        echo (array_key_exists($id, $skuStatusList) ? $skuStatusList[$id] : '') . '&nbsp;&nbsp;<span class="glyphicon glyphicon-remove" style="font-size:16px;" data-status="' . $id . '"></span>';
                                                        echo '</button>';
                                                        echo '<input type="hidden" name="sku_status[' . ($key + 1) . '][]" value="' . $id . '">';
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <select name="order_profit_cond[<?php echo ($key + 1); ?>]" class="form-control" style="width:120px;float:left;">
                                            <option value="">请选择</option>
                                            <option value="1" <?php if (1 == $rule['order_profit_cond']) {echo 'selected="selected"';} ?>>大于</option>
                                            <option value="2" <?php if (2 == $rule['order_profit_cond']) {echo 'selected="selected"';} ?>>大于等于</option>
                                            <option value="3" <?php if (3 == $rule['order_profit_cond']) {echo 'selected="selected"';} ?>>小于</option>
                                            <option value="4" <?php if (4 == $rule['order_profit_cond']) {echo 'selected="selected"';} ?>>小于等于</option>
                                        </select>
                                        <div class="input-group" style="width:120px;float:left;">
                                            <input type="text" name="order_profit_value[<?php echo ($key + 1); ?>]" class="form-control" value="<?php echo $rule['order_profit_value']; ?>">
                                            <span class="input-group-addon">元</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        echo Select2::widget([
                                            'name' => 'department_id[' . ($key + 1) . ']',
                                            'data' => $departmentList,
                                            'value' => $rule['department_id'],
                                        ]);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $reasonList = BasicConfig::find()
                                            ->select('id, name')
                                            ->where(['parent_id' => $rule['department_id'], 'status' => 2])
                                            ->asArray()
                                            ->all();

                                        if (!empty($reasonList)) {
                                            $tmp = ['' => '请选择'];
                                            foreach ($reasonList as $item) {
                                                $tmp[$item['id']] = $item['name'];
                                            }
                                            $reasonList = $tmp;
                                        }

                                        echo Select2::widget([
                                            'name' => 'reason_id[' . ($key + 1) . ']',
                                            'data' => $reasonList,
                                            'value' => $rule['reason_id'],
                                        ]);
                                        ?>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="formula_name[<?php echo ($key + 1); ?>]" value="<?php echo array_key_exists($rule['formula_id'], $allBasicConfig) ? $allBasicConfig[$rule['formula_id']] : ''; ?>" readonly disabled>
                                        <input type="hidden" name="formula_id[<?php echo ($key + 1); ?>]" value="<?php echo $rule['formula_id']; ?>">
                                    </td>
                                    <td>
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
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
                    <input type="radio" name="auto_audit" value="1" <?php if(1 == $info['auto_audit']) {echo "checked";} ?>>是
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="auto_audit" value="0" <?php if(0 == $info['auto_audit']) {echo "checked";} ?>>否
                </td>
            </tr>
            <tr>
                <td class="col1">状态：</td>
                <td>
                    <input type="radio" name="status" value="1" <?php if(1 == $info['status']) {echo "checked";} ?>>有效
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="status" value="0" <?php if(0 == $info['status']) {echo "checked";} ?>>无效
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <input type="submit" class="btn btn-primary btn-sm" value="修改">
                    <input type="reset" class="btn btn-default btn-sm" value="取消">
                    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
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

        $("select[name='platform_code']").on("change", function () {
            var platform_code = $(this).val();

            if (platform_code.length == 0) {
                layer.alert("请选择平台");
                return false;
            }

            $.post("<?php echo Url::toRoute('/systems/aftersalemanage/getdisputereasonlist'); ?>", {
                "platform_code": platform_code
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

        $("#updAftersaleManageForm input[type='submit']").on("click", function () {
            var params = $("#updAftersaleManageForm").serialize();
            $.post("<?php echo Url::toRoute(['/systems/aftersalemanage/edit']); ?>", params, function (data) {
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
