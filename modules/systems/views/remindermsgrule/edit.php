<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<div class="popup-wrapper">
    <style>
        #addReminderMsgRule {
            margin: 20px auto 0 auto;
            width: 90%;
            height: auto;
            border-collapse: collapse;
        }

        #addReminderMsgRule td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #addReminderMsgRule td.col1 {
            width: 120px;
            text-align: right;
            font-weight: bold;
        }

        #addReminderMsgRule input[type='radio'], #addReminderMsgRule input[type='checkbox'] {
            display: inline-block;
            height: 1em;
            width: 1em;
            background-color: gray;
            vertical-align: text-bottom;
            margin-bottom: 2px;
        }

        #addReminderMsgRule .account_item {
            display: inline-block;
            padding: 3px 5px;
            font-size: 14px;
        }

        #addReminderMsgRule #invertSelBtn {
            margin: 5px 0;
        }

        #addReminderMsgRule .params {
            margin-bottom: 10px;
        }
    </style>
    <?php
    $form = ActiveForm::begin([
        'id' => 'updReminderMsgRuleForm',
        'action' => Url::toRoute(['/systems/remindermsgrule/edit']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addReminderMsgRule">
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
                                <option value="<?php echo $key; ?>" <?php if($key == $info['platform_code']) {echo "selected";} ?>><?php echo $value; ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="col1">账号：</td>
                <td>
                    <input type="radio" name="account_type" value="all" <?php if('all' == $info['account_type']) {echo "checked";} ?>>所有账号
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="account_type" value="custom" <?php if('custom' == $info['account_type']) {echo "checked";} ?>>指定账号
                    <div id="account_list">
                        <?php if (!empty($accountIdArr)) { ?>
                            <a class='btn btn-warning' id='invertSelBtn'>反选</a><br>
                            <?php foreach($shortNameList as $item) { ?>
                                <div class="account_item"><input type="checkbox" name="account_ids[]" value="<?php echo $item['id']; ?>" <?php if(in_array($item['id'], $accountIdArr)) {echo "checked";} ?>><?php echo $item['account_name']; ?></div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">触发时间(单位小时)：</td>
                <td><input type="text" name="trigger_time" class="form-control" value="<?php echo $info['trigger_time']; ?>"></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    同一个买家在
                    <input type="text" name="buyer_once_time" value="<?php echo $info['buyer_once_time']; ?>" class="form-control" style="display:inline-block;width:auto;">
                    小时内只催付一次
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    以下买家不执行催付：<span style="color:red;">(不同买家之间用","号分割, 注意是半角英文)</span>
                    <br>
                    <textarea name="not_reminder_buyer" rows="7" class="form-control"><?php echo $info['not_reminder_buyer']; ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="col1">催付内容：</td>
                <td>
                    <div class="params">
                        参数：<a class="btn btn-xs btn-primary" data-param="{$buyer_name}">买家名称</a>
                    </div>
                    <textarea name="content" rows="7" class="form-control"><?php echo $info['content']; ?></textarea>
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
<script type="text/javascript">
    (function ($) {
        $.fn.extend({
            insertAtCaret: function (myValue) {
                var $t = $(this)[0];
                if (document.selection) {
                    this.focus();
                    sel = document.selection.createRange();
                    sel.text = myValue;
                    this.focus();
                } else if ($t.selectionStart || $t.selectionStart == '0') {
                    var startPos = $t.selectionStart;
                    var endPos = $t.selectionEnd;
                    var scrollTop = $t.scrollTop;
                    $t.value = $t.value.substring(0, startPos) + myValue + $t.value.substring(endPos, $t.value.length);
                    this.focus();
                    $t.selectionStart = startPos + myValue.length;
                    $t.selectionEnd = startPos + myValue.length;
                    $t.scrollTop = scrollTop;
                } else {
                    this.value += myValue;
                    this.focus();
                }
            }
        });
    })(jQuery);

    $(function () {
        function flushAccountList(platformCode) {
            $.get("<?php echo Url::toRoute(['/systems/remindermsgrule/getaccountshortnamelist']) ?>", {
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

        //点击参数按钮
        $(".params a").on("click", function () {
            $("textarea[name='content']").insertAtCaret($(this).attr("data-param"));
        });
    });
</script>
