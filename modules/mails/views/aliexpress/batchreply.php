<?php

use yii\helpers\Url;
use app\modules\mails\models\MailTemplate;
use app\modules\accounts\models\Platform;
use yii\helpers\Html;
use app\modules\mails\models\MailTemplateCategory;

?>
<style>
    fieldset {
        padding: .35em .625em .75em;
        margin: 0 2px;
        border: 1px solid silver;
    }

    legend {
        padding: .5em;
        border: 0;
        width: auto;
        margin-bottom: 0;
        font-size: 16px;
    }

    .mail_template_area a {
        display: inline-block;
        margin: 2px 5px;
    }
</style>
<div class="popup-wrapper">
    <div class="popup-body">
        <div class="panel panel-default">
            <div class="panel-body ">
                <ul class="list-inline">
                    <?php
                    foreach ($buyer_names as $k => $v) {
                        $account_id = $v['account_id'];
                        $buyer_name = $v['buyer_name'];
                        echo "<li><a href='#' class='' value='$account_id'>$buyer_name</a></li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
        <input type="hidden" name="ids" value="<?php echo $ids; ?>">
        <div style="margin-bottom: 10px">
            <form class="bs-example bs-example-form" role="form">
                <div class="row">
                    <div class="col-lg-3">
                        <div class="input-group">
                            <input type="text" class="form-control mail_template_search_text" placeholder="消息模板搜索">
                            <span class="input-group-btn">
                                <button class="btn btn-default btn-sm mail_template_search_btn" type="button">搜索</button>
                            </span>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <?php
                        $templateCateList = MailTemplateCategory::getCategoryList(Platform::PLATFORM_CODE_ALI, 0, 'list');
                        if (!empty($templateCateList)) {
                            echo '<select id="selMailTemplateCate" class="form-control" style="width:200px;">';
                            foreach ($templateCateList as $key => $templateCate) {
                                $templateCate = str_replace(' ', '&nbsp;', $templateCate);
                                echo "<option value='{$key}'>{$templateCate}</option>";
                            }
                            echo '</select>';
                        }
                        ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="panel panel-default">
            <div class="panel-body mail_template_area">
                <?php
                $templates = MailTemplate::getMyMailTemplate(Platform::PLATFORM_CODE_ALI);
                if (!empty($templates)) {
                    foreach ($templates as $template) {
                        if (!empty($template[0])) {
                            echo '<fieldset>';
                            echo '<legend>' . ($template[0]['category_name'] ? $template[0]['category_name'] : '无分类名称') . '</legend>';
                        }

                        if (!empty($template) && is_array($template)) {
                            foreach ($template as $item) {
                                echo "<a href='#' class='mail_template_unity' value='{$item['id']}'>{$item['template_name']}</a>";
                            }
                        }
                        if (!empty($template[0])) {
                            echo '</fieldset>';
                        }
                    }
                }
                ?>
            </div>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-primary" id="Reply">回复消息</button>
            <button type="button" class="btn btn-sm btn-info" id="addexpression">添加表情</button>
            <div class="form_data" style="float: left;font-size: 12px;">
                <select id="countDataType" class="form-control" style="width:100%;height:30px;padding: 2px 5px;">
                    <option value="all">选择绑定参数</option>
                    <option value="{buyer_id}">客户ID</option>
                    <option value="{track_number}">跟踪号</option>
                    <option value="{logistic}">发货方式</option>
                    <option value="{track}">查询网址</option>
                    <option value="{ship_country}">国家</option>
                </select>
            </div>
        </div>
        <div class="well" style="width: 100%;margin-top: 5px;display: none" id="expression"><?php
            if (!empty($expressionList)) {
                foreach ($expressionList as $exvalue) {
                    ?>
                    <a href="#this" class="expression_url" data-value="<?php echo $exvalue['label']; ?>"><img src="<?php echo $exvalue['expression_url']; ?>" width="24" height="24"/></a>
                    <?php
                }
            }
            ?>
        </div>
        <div class="form-group" style="margin-top: 10px;min-height:30px;">
            <label class="sr-only" for="inputfile">文件输入</label>
            <input type="file" id="inputfile">
            <div id="updateimage">

            </div>
        </div>
        <form role="form">
            <?php echo Html::hiddenInput('sl_code', "", ['id' => 'sl_code']); ?>
            <?php echo Html::hiddenInput('tl_code', "", ['id' => 'tl_code']); ?>
            <div class="form-group">
                <label for="name"></label>
                <textarea class="form-control" rows="6" placeholder="翻译前内容(英语)" id="reply_content"></textarea>

                <div class="row" style="text-align: center;font-size: 13px;font-weight: bold;margin-top: 10px;margin-bottom: 10px;">
                    <div class="col-sm-5 tr_q">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-default" type="button" onclick="changeCode(3, 'en', '', $(this))">英语</button>
                            <button class="btn btn-sm btn-default" type="button" onclick="changeCode(3, 'fr', '', $(this))">法语</button>
                            <button class="btn btn-sm btn-default" type="button" onclick="changeCode(3, 'de', '', $(this))">德语</button>
                            <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                <div class="btn-group">
                                    <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" id="sl_btn">更多&nbsp;&nbsp;<span class="caret"></span></button>
                                    <ul class="dropdown-menu language">
                                        <?php foreach ($googleLangCode as $key => $value) { ?>
                                            <li><a onclick="changeCode(1, '<?php echo $key; ?>', '<?php echo $value; ?>', $(this))"><?php echo $value; ?></a></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="fa-hover col-sm-1" style="width:0px;line-height: 30px;"><a><i class="fa fa-exchange"></i></a></div>
                    <div class="col-sm-5 tr_h">
                        <div class="btn-group">
                            <button class="btn-sm btn btn-default" type="button" onclick="changeCode(4, 'en', '', $(this))">英语</button>
                            <button class="btn btn-sm btn-default" type="button" onclick="changeCode(4, 'fr', '', $(this))">法语</button>
                            <button class="btn btn-sm btn-default" type="button" onclick="changeCode(4, 'de', '', $(this))">德语</button>
                            <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                <div class="btn-group">
                                    <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" data="" id="tl_btn">更多&nbsp;&nbsp;<span class="caret"></span></button>
                                    <ul class="dropdown-menu language">
                                        <?php foreach ($googleLangCode as $key => $value) { ?>
                                            <li><a onclick="changeCode(2, '<?php echo $key; ?>', '<?php echo $value; ?>', $(this))"><?php echo $value; ?></a></li>
                                        <?php } ?>
                                        </li>
                                    </ul>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <button class="btn btn-sm btn-primary artificialTranslation" type="button" id="translations_btn">翻译 [ <b id="sl_name"></b> - <b id="tl_name"></b> ]</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <textarea class="form-control" rows="6" placeholder="翻译后内容(如果有翻译则发送给客户的内容)" id="reply_content_en"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    $(function () {
        //模板ajax
        $('.mail_template_area').delegate('.mail_template_unity', 'click', function () {
            $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>', {'num': $(this).attr('value')}, function (data) {
                switch (data.status) {
                    case 'error':
                        alert(data.message);
                        return;
                    case 'success':
                        $('#reply_content').val(data.content);
                }
            }, 'json');
        });

        //邮件模板搜索
        $('.mail_template_search_btn').click(function () {
            var name = $.trim($('.mail_template_search_text').val());
            if (name.length == 0) {
                layer.msg('搜索名称不能为空。', {icon: 5});
                return;
            }
            $.post('<?php echo Url::toRoute(['/mails/aliexpress/searchmailtemplate']); ?>', {
                "name": name
            }, function (data) {
                if (data["code"] == 1) {
                    var data = data["data"];
                    if (data) {
                        var html = "";
                        for (var ix in data) {
                            if (data[ix][0]) {
                                html += "<fieldset>"
                                html += "<legend>" + (data[ix][0]["category_name"] ? data[ix][0]["category_name"] : "无分类名称") + "</legend>";
                            }
                            var item = data[ix];
                            for (var index in item) {
                                html += "<a href='#' class='mail_template_unity' value='" + item[index]["id"] + "'>" + item[index]["template_name"] + "</a>";
                            }
                            if (data[ix][0]) {
                                html += "</fieldset>";
                            }
                        }
                        $(".mail_template_area").html(html);
                    }
                } else {
                    layer.alert(data["message"]);
                    $(".mail_template_area").html("");
                }
            }, 'json');
            return false;
        });

        //选择邮件模板分类
        $("#selMailTemplateCate").on("change", function () {
            var category_id = $(this).val();
            $.post("<?php echo Url::toRoute('/mails/aliexpress/getmailtemplatelist'); ?>", {
                "category_id": category_id
            }, function (data) {
                if (data["code"] == 1) {
                    var data = data["data"];
                    if (data) {
                        var html = "";
                        for (var ix in data) {
                            if (data[ix][0]) {
                                html += "<fieldset>"
                                html += "<legend>" + (data[ix][0]["category_name"] ? data[ix][0]["category_name"] : "无分类名称") + "</legend>";
                            }
                            var item = data[ix];
                            for (var index in item) {
                                html += "<a href='#' class='mail_template_unity' value='" + item[index]["id"] + "'>" + item[index]["template_name"] + "</a>";
                            }
                            if (data[ix][0]) {
                                html += "</fieldset>";
                            }
                        }
                        $(".mail_template_area").html(html);
                    }
                } else {
                    layer.alert(data["message"]);
                    $(".mail_template_area").html("");
                }
            }, "json");
            return false;
        });

        //表情
        $('#addexpression').click(function () {
            $("#expression").toggle();
        });
        $(".expression_url").click(function () {
            var reply_content = $('#reply_content').val();
            var expression_url = $(this).attr('data-value');
            $('#reply_content').val(reply_content + expression_url);

        });
        //鼠标定位添加订单信息
        $("#countDataType").on("change", function (){
            var data_value = $(this).val();
            var reply_content = $('#reply_content').val();
            if(data_value != 'all'){
                $('#reply_content').val(reply_content + data_value);
            }
        })
        //上传图片
        $("#inputUpload").click(function () {
            $.ajax({
                type: "POST",
                url: "/mails/aliexpress/uploadpicture",
                data: {
                    file: $('#inputfile').val(),
                    account_ids: '<?php echo $account_ids?>',
                }, // 要提交的表单
                enctype: 'multipart/form-data',
                success: function (msg) {
                    var obj = eval('(' + msg + ')');
                    if (obj.status == 1) {
                        $('#form2').css("display", "none");
                    } else {
                        alertMsg.info(obj.message);
                    }
                }, error: function (error) {
                    alert(error);
                }
            });

        });
        $("#inputfile").change(function () {
            //创建FormData对象
            var data = new FormData();
            //为FormData对象添加数据
            $.each($('#inputfile')[0].files, function (i, file) {
                data.append('upload_file', file);
            });
            $.ajax({
                url: '/mails/aliexpress/uploadpicture?account_ids=<?php echo $account_ids; ?>',
                type: 'POST',
                data: data,
                cache: false,
                contentType: false, //不可缺
                processData: false, //不可缺
                success: function (msg) {
                    var obj = eval('(' + msg + ')');
                    if (obj.code == 200) {
                        $('#updateimage').text('');
                        $('#updateimage').append('<img src="' + obj.data + '" class="img-rounded imgPath" width="140" /><input type="hidden" id="imgPath_url" name="imgPath" value="' + obj.data + '" class="imgPath"><br><a onclick="imgPathRemove();" class="imgPath">删除</a>');
                    } else {
                        alert(obj.message);
                    }
                }
            });
        });
    });

    function imgPathRemove() {
        $(".imgPath").remove();
    }

    //回复
    $("#Reply").click(function () {
        var reply_content_en = $('#reply_content_en').val();//翻译后内容
        var reply_content = $('#reply_content').val();//翻译前内容
        var ids = $("input[name='ids']").val();
        if (!reply_content) {
            layer.msg('你还没有填写回复内容');
            return false;
        }
        //判断

        $.post("/mails/aliexpress/batchreply",
            {
                "ids": ids,
                content: reply_content,
                content_en: reply_content_en,
                imgPath: $('#imgPath_url').val()
            },
            function (data) {
                if (data.code != '200') {
                    layer.alert(data.message, {
                        icon: 5
                    });
                } else {
                    var reply = data.data;
                    var replyList = '';
                    var imgPath = '';
                    if (reply.imgPath != '') {
                        imgPath += '<br/><img src="' + reply.imgPath + '"/>';
                    }
                    replyList += '<div class="well" style="background:#90EE90;"> ' +
                        '<p style="border-bottom: 2px solid #EEEEE0"> <span>发送人：' + reply.create_by +
                        '</span><span>日期：' + reply.create_time + '</span></p> ' +
                        '<p> ' + reply.reply_content + '</p>' + imgPath + ' </div>';
                    $('#replyList').prepend(replyList);
                    $('#Reply').attr('id', 'Reply11');
                    layer.alert(data.message, {icon: 1});
                    location.href = "/mails/aliexpress/index";
                }
            }, 'json');
    });
    $('div.sidebar').hide();

    /**
     * 回复客户邮件内容点击翻译(系统检测到用户语言)
     * @author allen <2018-1-29>
     */
    $(".transClik").click(function () {
        var sl = 'auto';
        var tl = 'en';
        var tag = $(this).attr('data1');
        var message = $("#text_" + tag).html();
        var that = $(this);
        if (message.length == 0) {
            layer.msg('获取需要翻译的内容有错!');
            return false;
        }
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['ebayinboxsubject/translate']); ?>',
            data: {'sl': sl, 'tl': tl, 'returnLang': 1, 'content': message},
            success: function (data) {
                if (data) {
                    var htm = '<p style="color:green; font-weight:bold;">' + data.text + '</p>';
                    $(".pcontent_" + tag).after(htm);
                    $("#sl_code").val('en');
                    $("#sl_name").html('英语');
                    $("#tl_code").val(data.googleCode);
                    $("#tl_name").html(data.code);
                    that.remove();
                }
            }
        });
    });

    /**
     * 点击选择语言将选中语言赋值给对应控件
     * @param {type} type 类型
     * @param {type} code 语言code
     * @param {type} name 语言名称
     * @param {type} that 当前对象
     * @author allen <2018-1-11>
     * */
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
     **/
    $('.artificialTranslation').click(function () {
        var sl = $("#sl_code").val();
        var tl = $("#tl_code").val();
        var content = $.trim($("#reply_content").val());
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
        //ajax请求
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['ebayinboxsubject/translate']); ?>',
            data: {'sl': sl, 'tl': tl, 'content': content},
            success: function (data) {
                if (data) {
                    $("#reply_content_en").val(data);
                }
            }
        });
    });

    /**
     * 添加 或者修改站内信备注功能
     * @author huwenjun <2018-05-22>
     */
    $(document).on('click', '.remark', function () {
        var id = $(this).attr('data');
        var remark = $(this).attr('data1');//默认备注
        if (remark == '') {
            remark = $(this).text();
        }
        layer.prompt({title: '站内信备注', value: remark, formType: 2}, function (text, index) {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['operationremark']); ?>',
                data: {'id': id, 'remark': text},
                success: function (data) {
                    if (data.status) {
                        layer.msg(data.info, {icon: 1});
                        var htm = '<li class="tag label btn-info md ion-close-circled"><span style="cursor: pointer;" class="remark" data="' + id + '" data1="">' + text + '</span>&nbsp;&nbsp;<a href="javascript:void(0)" class="removetags" data="' + id + '">x</a></li>';
                        $("#remark_" + id).html(htm);
                    } else {
                        layer.msg(data.info, {icon: 5});
                    }
                }
            });
            layer.close(index);
        });
    });

    /**
     * 删除站内信备注功能
     * @author huwenjun <2018-05-22>
     */

    $(document).on('click', '.removetags', function () {
        var id = $(this).attr('data');
        layer.confirm('您确定要删除么？', {
            btn: ['确定', '再考虑一下'] //按钮
        }, function () {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['operationremark']); ?>',
                data: {'id': id, 'remark': ''},
                success: function (data) {
                    if (data.status) {
                        layer.msg(data.info, {icon: 1});
                        var htm = '<i class="fa remark fa-pencil remark" style="cursor: pointer;" data="' + id + '" data1=""></i>';
                        $("#remark_" + id).html(htm);
                    } else {
                        layer.msg(data.info, {icon: 5});
                    }
                }
            });
        }, function () {

        });
    });
</script>