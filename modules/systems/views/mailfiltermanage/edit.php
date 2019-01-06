<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use app\modules\systems\models\MailFilterRule;

$startIndex = 0;
?>
<div class="popup-wrapper">
    <style>
        #addMailFilterManage {
            margin: 20px auto 0 auto;
            width: 90%;
            height: auto;
            border-collapse: collapse;
        }

        #addMailFilterManage td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #addMailFilterManage td.col1 {
            width: 150px;
            text-align: right;
            font-weight: bold;
        }

        #addMailFilterManage td.col2 {
            width: 190px;
        }

        #addMailFilterRule {
            width: 100%;
        }

        #addMailFilterRule td {
            border: none;
        }

        #addMailFilterRule td.col1 {
            width: 180px;
        }

        #addMailFilterRule td span.glyphicon-remove {
            font-size: 24px;
            color: red;
            cursor: pointer;
        }

        #hideMailFilterRule {
            display: none;
        }
    </style>
    <?php
    $form = ActiveForm::begin([
        'id' => 'addMailFilterManageForm',
        'action' => Url::toRoute(['/systems/mailfiltermanage/edit']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addMailFilterManage">
            <tr>
                <td class="col1">过滤器名称：</td>
                <td colspan="2">
                    <input type="text" name="filter_name" class="form-control" value="<?php echo $info['filter_name']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">状态：</td>
                <td colspan="2">
                    <input type="radio" name="status" value="1" <?php if (1 == $info['status']) {
                        echo "checked";
                    } ?>>有效
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="status" value="0" <?php if (0 == $info['status']) {
                        echo "checked";
                    } ?>>无效
                </td>
            </tr>
            <tr>
                <td class="col1">平台：</td>
                <td colspan="2">
                    <select name="platform_code" class="form-control">
                        <option value="">请选择</option>
                        <?php if (!empty($platformList)) { ?>
                            <?php foreach ($platformList as $key => $value) { ?>
                                <option value="<?php echo $key; ?>" <?php if ($key == $info['platform_code']) {
                                    echo "selected";
                                } ?>><?php echo $value; ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="col1">当邮件到达时：</td>
                <td colspan="2">
                    <input type="radio" name="cond_type" value="1" <?php if (1 == $info['cond_type']) {
                        echo "checked";
                    } ?>>满足以下所有条件
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="cond_type" value="2" <?php if (2 == $info['cond_type']) {
                        echo "checked";
                    } ?>>满足以下任一条件
                </td>
            </tr>
            <tr>
                <td class="col1">&nbsp;</td>
                <td colspan="2">
                    <table id="addMailFilterRule">
                        <thead>
                        <tr>
                            <td colspan="3">
                                <a href="#" class="btn btn-primary" id="addMailFilterRuleBtn">新增</a>
                            </td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr id="hideMailFilterRule" data-startindex="<?php echo !empty($manageRuleList) ? count($manageRuleList) : 0; ?>">
                            <td class="col1">
                                <?php if (!empty($ruleTypeList)) { ?>
                                    <select name="rule_type[0]" class="form-control" disabled>
                                        <?php foreach ($ruleTypeList as $key => $value) { ?>
                                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                <?php } ?>
                            </td>
                            <td>
                                <input type="text" name="rule_value[0]" class="form-control" disabled>
                            </td>
                            <td>
                                <span class="glyphicon glyphicon-remove"></span>
                            </td>
                        </tr>

                        <?php if (!empty($manageRuleList)) { ?>
                            <?php foreach ($manageRuleList as $ruleList) { ?>
                                <tr>
                                    <td class="col1">
                                        <?php if (!empty($ruleTypeList)) { ?>
                                            <?php $startIndex++; ?>
                                            <select name="rule_type[<?php echo $startIndex; ?>]" class="form-control">
                                                <?php foreach ($ruleTypeList as $key => $value) { ?>
                                                    <option value="<?php echo $key; ?>" <?php if ($key == $ruleList['type']) {
                                                        echo "selected";
                                                    } ?>><?php echo $value; ?></option>
                                                <?php } ?>
                                            </select>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <input type="text" name="rule_value[<?php echo $startIndex; ?>]" class="form-control" value="<?php echo $ruleList['value']; ?>">
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
                <td class="col1">符合条件则执行：</td>
                <td class="col2">
                    <input type="checkbox" id="isMoveSite" <?php if (!empty($info['move_site_ids'])) {
                        echo "checked";
                    } ?>> 邮件移动到站点
                </td>
                <td id="siteList">
                    <?php if (!empty($info['move_site_ids']) && !empty($mailSiteList)) { ?>
                        <?php foreach ($mailSiteList as $value) { ?>
                            <input type="checkbox" name="move_site_ids[]" value="<?php echo $value['id']; ?>" <?php if (in_array($value['id'], $info['move_site_ids'])) {
                                echo "checked";
                            } ?>> <?php echo $value['name']; ?>
                        <?php } ?>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td class="col1">&nbsp;</td>
                <td class="col2">
                    <input type="checkbox" id="isTypeMark" <?php if (!empty($info['type_mark'])) {
                        echo "checked";
                    } ?>> 邮件类型标记为
                </td>
                <td id="typeList">
                    <?php if (!empty($info['type_mark']) && !empty($mailTypeList)) { ?>
                        <select name="type_mark" class="form-control" style="width:150px;">
                            <option value="">请选择</option>
                            <?php foreach ($mailTypeList as $key => $value) { ?>
                                <option value="<?php echo $key; ?>" <?php if ($key == $info['type_mark']) {
                                    echo "selected";
                                } ?>><?php echo $value; ?></option>
                            <?php } ?>
                        </select>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td class="col1">&nbsp;</td>
                <td colspan="2">
                    <input type="checkbox" id="isMarkRead" name="mark_read" value="1" <?php if (!empty($info['mark_read'])) {
                        echo "checked";
                    } ?>> 邮件标记为已读
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan="2">
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
<script type="text/javascript">
    $(function () {
        $("#isMoveSite").on("click", function () {
            if ($(this).is(":checked")) {
                var platform_code = $("select[name='platform_code']").val();
                if (platform_code.length == 0) {
                    layer.alert("请选择平台");
                    return false;
                }

                flushSiteList(platform_code);
            } else {
                $("#siteList > input[type='checkbox']").addClass("disabled");
                $("#siteList > input[type='checkbox']").attr("disabled", "disabled");
            }
        });

        //刷新站点列表
        function flushSiteList(platform_code) {
            $.post("<?php echo Url::toRoute('/systems/mailfiltermanage/getsitelist'); ?>", {
                "platform_code": platform_code
            }, function (data) {
                var html = "";
                if (data["code"] == 1) {
                    var data = data["data"];
                    for (var ix in data) {
                        html += "<input type='checkbox' name='move_site_ids[]' value='" + data[ix]["id"] + "'>&nbsp;" + data[ix]["name"] + "&nbsp;&nbsp;";
                    }
                } else {
                    html += "<span style='color:red;'>" + data["message"] + "</span>";
                }
                $("#siteList").html(html);
            }, "json");
        }

        $("#isTypeMark").on("click", function () {
            if ($(this).is(":checked")) {
                var platform_code = $("select[name='platform_code']").val();
                if (platform_code.length == 0) {
                    layer.alert("请选择平台");
                    return false;
                }

                flushMailTypeList(platform_code);
            } else {
                $("#typeList > select").addClass("disabled");
                $("#typeList > select").attr("disabled", "disabled");
            }
        });

        //刷新邮件类型列表
        function flushMailTypeList(platform_code) {
            $.post("<?php echo Url::toRoute('/systems/mailfiltermanage/getmailtypelist'); ?>", {
                "platform_code": platform_code
            }, function (data) {
                var html = "";
                if (data["code"]) {
                    var data = data["data"];
                    html += "<select name='type_mark' class='form-control' style='width:150px;'>";
                    html += "<option value=''>请选择</option>";
                    for (var ix in data) {
                        html += "<option value='" + ix + "'>" + data[ix] + "</option>";
                    }
                    html += "</select>";
                } else {
                    html += "<span style='color:red;'>" + data["message"] + "</span>";
                }
                $("#typeList").html(html);
            }, "json");
        }

        $("select[name='platform_code']").on("change", function () {
            var platform_code = $(this).val();

            if ($("#isTypeMark").is(":checked")) {
                flushMailTypeList(platform_code);
            }
            if ($("#isMoveSite").is(":checked")) {
                flushSiteList(platform_code);
            }
        });

        $("#addMailFilterRuleBtn").on("click", function () {
            var startIndex = $("#hideMailFilterRule").attr("data-startindex");
            startIndex = parseInt(startIndex) + 1;
            $("#hideMailFilterRule").attr("data-startindex", startIndex);
            var line = $("#hideMailFilterRule").clone();
            line.removeAttr("id");
            line.removeAttr("data-startindex");
            line.find("select[name^='rule_type']").attr("name", "rule_type[" + startIndex + "]").removeAttr("disabled");
            line.find("input[name^='rule_value']").attr("name", "rule_value[" + startIndex + "]").removeAttr("disabled");
            line.find("span.glyphicon-remove").on("click", function () {
                $(this).parent("td").parent("tr").remove();
            });
            $("#addMailFilterRule > tbody").append(line);
            return false;
        });

        $("#addMailFilterRule span.glyphicon-remove").on("click", function () {
            $(this).parent("td").parent("tr").remove();
            return false;
        });
    });
</script>