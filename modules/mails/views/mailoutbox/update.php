<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<div class="popup-wrapper">
    <style>
        #updateMailoutbox {
            margin: 5px auto 0 auto;
            width: 95%;
            height: auto;
            border-collapse: collapse;
        }

        #updateMailoutbox td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #updateMailoutbox td.col1 {
            width: 180px;
            text-align: right;
            font-weight: bold;
        }
    </style>
    <?php
    $form = ActiveForm::begin([
        'id' => 'updateMailoutboxForm',
        'action' => Url::toRoute(['/mails/mailoutbox/update']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="updateMailoutbox">
            <tr>
                <td class="col1">当前主题标题：</td>
                <td>
                    <input type="text" name="subject" class="form-control" value="<?php echo $info->subject; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">当前主题内容：</td>
                <td>
                    <textarea class="form-control" rows="8" id="cur_content" readonly><?php echo $info->content; ?></textarea>

                    <a href="javascript:void(0);" class="translate">点击翻译</a>
                </td>
            </tr>
            <tr>
                <td class="col1">修改后的主题：</td>
                <td>
                    <textarea name="content_en" id="content_en" rows="8" class="form-control" placeholder="此回复内容为英语"><?php echo $info->content; ?></textarea>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="btn-group btn-group-sm" style="margin:5px 0;">
                                <button class="btn btn-default" type="button" onclick="changeCode(3,'en','',$(this))">
                                    英语
                                </button>
                                <button class="btn btn-default" type="button" onclick="changeCode(3,'fr','',$(this))">
                                    法语
                                </button>
                                <button class="btn btn-default" type="button" onclick="changeCode(3,'de','',$(this))">
                                    德语
                                </button>
                                <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                    <div class="btn-group">
                                        <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" id="sl_btn">
                                            更多<span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu language">
                                            <?php foreach ($googleLangCode as $key => $value) { ?>
                                                <li>
                                                    <a onclick="changeCode(1,'<?php echo $key; ?>','<?php echo $value; ?>',$(this))"><?php echo $value; ?></a>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                            <a><i class="fa fa-exchange"></i></a>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default" type="button" onclick="changeCode(4,'en','',$(this))">
                                    英语
                                </button>
                                <button class="btn btn-default" type="button" onclick="changeCode(4,'fr','',$(this))">
                                    法语
                                </button>
                                <button class="btn btn-default" type="button" onclick="changeCode(4,'de','',$(this))">
                                    德语
                                </button>
                                <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                    <div class="btn-group">
                                        <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" id="tl_btn">
                                            更多<span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu language">
                                            <?php foreach ($googleLangCode as $key => $value) { ?>
                                                <li>
                                                    <a onclick="changeCode(2,'<?php echo $key; ?>','<?php echo $value; ?>',$(this))"><?php echo $value; ?></a>
                                                </li>
                                            <?php } ?>
                                            </li>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                            <button class="btn btn-sm btn-primary artificialTranslation" type="button" id="translations_btn">
                                翻译 [ <b id="sl_name"></b> - <b id="tl_name"></b> ]
                            </button>
                            <input type="hidden" id="sl_code" value="">
                            <input type="hidden" id="tl_code" value="">
                        </div>
                    </div>

                    <textarea name="content" id="content" rows="8" class="form-control" placeholder="发送给客户的内容"></textarea>
                </td>
            </tr>
            <tr>
                <td class="col1"></td>
                <td>
                    <input type="submit" class="btn btn-primary btn-sm" value="提交">
                    <input type="reset" class="btn btn-default btn-sm" value="取消">
                    <input type="hidden" name="id" value="<?php echo $info->id; ?>">
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
    /**
     * 点击选择语言将选中语言赋值给对应控件
     * @param {type} type 类型
     * @param {type} code 语言code
     * @param {type} name 语言名称
     * @param {type} that 当前对象
     * @author allen <2018-1-11>
     */
    function changeCode(type, code, name = "", that = "") {
        if (type == 1) {
            $("#sl_code").val(code);
            $("#sl_btn").html(name + '&nbsp;&nbsp;<span class="caret"></span>');
            that.css('font-weight', 'bold');
            $("#sl_name").html(name);
        } else if (type == 2) {
            $("#tl_code").val(code);
            $("#tl_btn").html(name + '&nbsp;&nbsp;<span class="caret"></span>');
            $("#tl_name").html(name);
            that.css('font-weight', 'bold');
        } else if (type == 3) {
            var name = that.html();
            $("#sl_code").val(code);
            $("#sl_name").html(name);
        } else {
            var name = that.html();
            $("#tl_code").val(code);
            $("#tl_name").html(name);
        }
    }

    /**
     * 绑定翻译按钮 进行手动翻译操作(系统未检测到用户语言)
     * @author allen <2018-1-11>
     */
    $('.artificialTranslation').click(function () {
        var sl = $("#sl_code").val();
        var tl = $("#tl_code").val();
        var content = $.trim($("#content_en").val());
        if (sl == "") {
            layer.msg('请选择需要翻译的语言类型');
            return false;
        }

        if (tl == "") {
            layer.msg('请选择翻译目标的语言类型');
            return false;
        }

        if (content.length <= 0) {
            layer.msg('请输入需要翻译的内容!');
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['/mails/ebayinboxsubject/translate']); ?>',
            data: {'sl': sl, 'tl': tl, 'content': content},
            success: function (data) {
                if (data) {
                    $("#content").val(data);
                }
            }
        });
        return false;
    });

    //点击翻译
    $(".translate").on("click", function() {
        var sl = 'auto';
        var tl = 'en';
        var content = $("#cur_content").text();

        $.post("<?php echo Url::toRoute(['/mails/ebayinboxsubject/translate']); ?>", {
            "sl": sl,
            "tl": tl,
            "returnLang": 1,
            "content": content
        }, function (data) {
            if (data) {
                $("#tl_code").val(data.googleCode);
                $("#tl_name").html(data.code);
                $("#sl_code").val('en');
                $("#sl_name").html('英语');
                $("#content_en").html(data.text);
            }
        }, "json");
        return false;
    });

    //取消按钮
    $("input[type='reset']").on("click", function() {
        top.layer.closeAll();
        return false;
    });
</script>