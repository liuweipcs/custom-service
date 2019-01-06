<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
?>
<div class="popup-wrapper">
    <style>
        #addFeedbackAccountRule {
            margin: 20px auto 0 auto;
            width: 90%;
            height: auto;
            border-collapse: collapse;
        }

        #addFeedbackAccountRule td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #addFeedbackAccountRule td.col1 {
            width: 120px;
            text-align: right;
            font-weight: bold;
        }

        #addFeedbackAccountRule input[type='radio'], #addFeedbackAccountRule input[type='checkbox'] {
            display: inline-block;
            height: 1em;
            width: 1em;
            background-color: gray;
            vertical-align: text-bottom;
            margin-bottom: 2px;
        }

        #addFeedbackAccountRule .account_item {
            display: inline-block;
            padding: 3px 5px;
            font-size: 14px;
        }

        #addFeedbackAccountRule #invertSelBtn {
            margin: 5px 0;
        }
    </style>
    <?php
    $form = ActiveForm::begin([
                'id' => 'addFeedbackAccountRuleForm',
                'action' => Url::toRoute(['/systems/feedbackaccountrule/add']),
                'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addFeedbackAccountRule">
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
                <td class="col1">留评内容：</td>   
                <td>
                    <input type="radio" name="msg_type" value="rand_centent" checked >随机发送留评模板
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="msg_type" value="centent">指定内容
                    <div class="centent" style="display: none;" >
                        <select name="feedback_template_id" class="form-control">
                            <option value="">选择回评模板</option>           
                        </select>
                        <textarea class="form-control" id="centent" style="height:119px;" name="centent"></textarea>
                    </div>
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
            $.get("<?php echo Url::toRoute(['/systems/feedbackaccountrule/getaccountshortnamelist']) ?>", {
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
        //获取随机回复模板
        $("input[name='msg_type'][value='rand_centent']").on("click", function () {
            $('.centent').hide();
        });
        //获取指定内容
        $("input[name='msg_type'][value='centent']").on("click", function () {
            var platformCode = $("select[name='platform_code']").val();
            if (platformCode.length == 0) {
                layer.alert("请选择平台");
                return false;
            }
            $('.centent').show();

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
            var cententType = $("input[name='msg_type']:checked").val();
            if (platformCode.length == 0) {
                $("#account_list").html("");
            } else {
                if (accountType == 'custom') {
                    flushAccountList(platformCode);
                }
            }
            if (cententType != "") {
                feedbacktemplate(platformCode);
            }
        });
        function feedbacktemplate(platformCode) {

            $.ajax({
                url: "<?php echo Url::toRoute(['/mails/feedbacktemplate/getfeedbacktemplateall']) ?>",
                type: "post",
                data: {'platformCode': platformCode},
                dataType: "json",
                success: function (data) {
                    console.log(data.data);
                    var html = "";
                    html = '<option value="">---选择回评模板---</option>';
                    $.each(data.data, function (n, value) {
                        html += '<option value=' + n + '>' + value + '</option>';
                    });
                    $("select[name='feedback_template_id']").html(html);

                },
                error: function (e) {
                    layer.alert('系统繁忙,请稍后再试');
                }
            });
        }
        //选择指定模板
        $("select[name='feedback_template_id']").on('change', function () {
            var feedbacktemplateid = $("select[name='feedback_template_id']").val();
            $.ajax({
                url: "<?php echo Url::toRoute(['/mails/feedbacktemplate/getfeedbacktemplateinfo']) ?>",
                type: "post",
                data: {'feedbacktemplateid': feedbacktemplateid},
                dataType: "json",
                success: function (data) {
                    $("#centent").val(data.data);
                },
                error: function (e) {
                    layer.alert('系统繁忙,请稍后再试');
                }
            });


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
