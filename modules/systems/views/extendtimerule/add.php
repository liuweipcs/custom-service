<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<div class="popup-wrapper">
    <style>
        #addExtendTimeRule {
            margin: 20px auto 0 auto;
            width: 90%;
            height: auto;
            border-collapse: collapse;
        }

        #addExtendTimeRule td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #addExtendTimeRule td.col1 {
            width: 120px;
            text-align: right;
            font-weight: bold;
        }

        #addExtendTimeRule input[type='radio'], #addExtendTimeRule input[type='checkbox'] {
            display: inline-block;
            height: 1em;
            width: 1em;
            background-color: gray;
            vertical-align: text-bottom;
            margin-bottom: 2px;
        }

        #addExtendTimeRule .account_item {
            display: inline-block;
            padding: 3px 5px;
            font-size: 14px;
        }

        #addExtendTimeRule #invertSelBtn {
            margin: 5px 0;
        }
    </style>
    <?php
    $form = ActiveForm::begin([
        'id' => 'addExtendTimeRuleForm',
        'action' => Url::toRoute(['/systems/extendtimerule/add']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addExtendTimeRule">
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
                <td class="col1">账号：</td>
                <td>
                    <input type="radio" name="account_type" value="all" checked>所有账号
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="account_type" value="custom">指定账号
                    <div id="account_list">

                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">触发时间(单位小时)：</td>
                <td><input type="text" name="trigger_time" class="form-control"></td>
            </tr>
            <tr>
                <td class="col1">延长天数：</td>
                <td><input type="text" name="extend_day" class="form-control"></td>
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
                    <input type="submit" class="btn btn-primary btn-lg" value="添加">
                    <input type="reset" class="btn btn-default btn-lg" value="取消">
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
        function flushAccountList(platformCode) {
            $.get("<?php echo Url::toRoute(['/systems/extendtimerule/getaccountshortnamelist']) ?>", {
                "platformCode": platformCode
            }, function (data) {
                if (data["code"] == 1) {
                    var list = data["data"];
                    var html = "<a class='btn btn-warning' id='invertSelBtn'>反选</a><br>";

                    for (var ix in list) {
                        html += "<div class='account_item'><input type='checkbox' name='account_ids[]' value='" + list[ix]["id"] + "'>" + list[ix]["account_name"] + "</div>";
                    }

                    $("#account_list").html(html);
                } else {
                    layer.alert("获取数据失败");
                }
            }, "json");
        }

        //所有账号
        $("input[name='account_type'][value='all']").on("click", function () {
            $("#account_list").html("");
        });

        //指定账号
        $("input[name='account_type'][value='custom']").on("click", function () {
            var platformCode = $("select[name='platform_code']").val();
            if (platformCode.length == 0) {
                layer.alert("请选择平台");
                return false;
            }
            flushAccountList(platformCode);
        });

        //选择平台
        $("select[name='platform_code']").on("change", function () {
            var platformCode = $(this).val();
            var accountType = $("input[name='account_type']:checked").val();
            if (platformCode.length == 0) {
                $("#account_list").html("");
            } else {
                if (accountType == 'custom') {
                    flushAccountList(platformCode);
                }
            }
        });

        //反选按钮
        $("#account_list").on("click", "#invertSelBtn", function () {
            $("input[name^='account_ids']").each(function () {
                $(this).prop('checked', $(this).is(':checked') ? false : true);
            });
            return false;
        });
    });
</script>
