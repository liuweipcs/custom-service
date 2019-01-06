<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use app\modules\customer\models\CustomerTagsRule;

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
        .form-control_value{
            width: 10%;
            height: 35px;
        }
    </style>

    <?php
    $form = ActiveForm::begin([
        'id' => 'addMailFilterManageForm',
        'action' => Url::toRoute(['/customer/customer/editor']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addMailFilterManage">
            <tr>
                <td class="col1">标签名称：</td>
                <td colspan="2">
                    <input type="text" name="tag_name" class="form-control" value="<?php echo $info['tag_name']; ?>">
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
                <td class="col1">状态：</td>
                <td colspan="2">
                    <input type="radio" name="status" value="1" <?php if (1 == $info['status']) {
                        echo "checked";
                    } ?>>有效
                    <input type="radio" name="status" value="0" <?php if (0 == $info['status']) {
                        echo "checked";
                    } ?>>无效
                </td>
            </tr>
            <tr>
                <td class="col1">自动添加规则：</td>
                <td colspan="2">
                    <input type="radio" name="cond_type" value="1" <?php if (1 == $info['cond_type']) {
                        echo "checked";
                    } ?>>满足以下所有条件
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="cond_type" value="2" <?php if (2 == $info['cond_type']) {
                        echo "checked";
                    } ?>>满足以下任一条件

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
                                <input type="text" name="rule_value[0]" class="form-control_value" disabled>～
                                <input type="text" name="end_value[0]" class="form-control_value" disabled>
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
                                        <input type="text" name="rule_value[<?php echo $startIndex; ?>]" class="form-control_value" value="<?php echo $ruleList['value']; ?>">
                                        <input type="text" name="end_value[<?php echo $startIndex; ?>]" class="form-control_value" value="<?php echo $ruleList['value1']; ?>">
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
                <td>&nbsp;</td>
                <td colspan="2">
                    <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
                    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
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
        $("#addMailFilterRuleBtn").on("click", function () {
            var startIndex = $("#hideMailFilterRule").attr("data-startindex");
            startIndex = parseInt(startIndex) + 1;
            $("#hideMailFilterRule").attr("data-startindex", startIndex);
            var line = $("#hideMailFilterRule").clone();
            line.removeAttr("id");
            line.removeAttr("data-startindex");
            line.find("select[name^='rule_type']").attr("name", "rule_type[" + startIndex + "]").removeAttr("disabled");
            line.find("input[name^='rule_value']").attr("name", "rule_value[" + startIndex + "]").removeAttr("disabled");
            line.find("input[name^='end_value']").attr("name", "end_value[" + startIndex + "]").removeAttr("disabled");
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

        $("#addMailFilterRule").delegate(".form-control_value","keyup",function(){
            $(this).val($(this).val().replace(/[^0-9.]/g,'')).bind("paste",function(){  //CTR+V事件处理
                $(this).val($(this).val().replace(/[^0-9.]/g,''));
            }).css("ime-mode", "disabled"); //CSS设置输入法不可用    ;
        })
    });

</script>
