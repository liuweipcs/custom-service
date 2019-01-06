<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Tag;
use app\modules\mails\models\MailTemplate;
use app\modules\systems\models\Country;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\OrderAmazonDetail;

?>
<style>
    #sendEmail {
        margin: 0 auto 0 auto;
        width: 95%;
        height: auto;
        border-collapse: collapse;
    }

    #sendEmail td, #sendEmail th {
        border: 1px solid #ccc;
        padding: 10px;
    }

    #sendEmail td.col1 {
        width: 150px;
        text-align: right;
        font-weight: bold;
    }

    .language {
        width: 650px;
        float: left;
        height: auto;
        max-height: 250px;
        overflow-y: scroll;
    }

    .language li {
        width: 16%;
        float: left;
    }

    .language li a {
        font-size: 10px;
        text-align: left;
        cursor: pointer;
    }

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

    .mailTemplateItem {
        margin-right: 5px;
        margin-bottom: 5px;
        text-decoration: underline;
    }

    .mailTemplateSearch {
        cursor: pointer;
    }

    .uploadImageLine {
        margin: 5px 0;
        line-height: 30px;
    }

    .addUploadImageLine {
        font-weight: bold;
        text-decoration: underline;
    }

    .showUploadImage {
        display: none;
        border: 1px solid #ccc;
        width: 150px;
        height: 150px;
        overflow: hidden;
    }

    .showUploadImage img {
        width: 148px;
        height: 148px;
    }
</style>
<div id="page-wrapper">
    <?php
    $form = ActiveForm::begin([
        'id' => 'sendEmailForm',
        'action' => Url::toRoute(['/mails/amazonfeedback/sendemail']),
        'method' => 'post',
        'options' => ['enctype' => 'multipart/form-data'],
    ]);
    ?>
    <table id="sendEmail">
        <tr>
            <td class="col1">ASIN：</td>
            <td>
                <?php
                echo "<select name='asin' class='form-control'>";
                echo "<option value='all'>全部</option>";
                if (!empty($platformOrderId)) {
                    $order_info = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_AMAZON, $platformOrderId);
                    if (!empty($order_info)) {
                        $asins = OrderAmazonDetail::find()
                            ->select('asinval')
                            ->where(['order_id' => $order_info['info']['order_id']])
                            ->column();
                        if (!empty($asins)) {
                            foreach ($asins as $asin) {
                                echo "<option value='{$asin}'>{$asin}</option>";
                            }
                        }
                    }
                }
                echo "</select>";
                ?>
            </td>
        </tr>
        <tr>
            <td class="col1">收件人：</td>
            <td>
                <input type="text" name="receive_email" class="form-control" value="<?php echo $toEmail; ?>">
            </td>
        </tr>
        <tr>
            <td class="col1">发件人：</td>
            <td>
                <input type="text" name="sender_email" class="form-control" value="<?php echo $fromEmail; ?>">
            </td>
        </tr>
        <tr>
            <td class="col1">主题：</td>
            <td>
                <input type="text" name="subject" class="form-control">
            </td>
        </tr>
        <tr>
            <td class="col1">选择标签：</td>
            <td>
                <?php
                $tagList = Tag::find()
                    ->select('id, tag_name as name')
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_AMAZON, 'status' => 1])
                    ->orderBy('sort_order ASC')
                    ->asArray()
                    ->all();
                if (!empty($tagList)) {
                    foreach ($tagList as $tag) {
                        echo "<input type='checkbox' name='tag[]' value='{$tag['id']}'>{$tag['name']}&nbsp;&nbsp;";
                    }
                }
                ?>
            </td>
        </tr>
        <tr>
            <td class="col1"></td>
            <td>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="消息模板搜索">
                    <div class="input-group-addon mailTemplateSearch">搜索</div>
                </div>
            </td>
        </tr>
        <tr>
            <td class="col1">消息模板：</td>
            <td>
                <div id="mailTemplateArea">
                    <?php
                    $templates = MailTemplate::getMyMailTemplate(Platform::PLATFORM_CODE_AMAZON);
                    if (!empty($templates)) {
                        foreach ($templates as $template) {
                            if (!empty($template[0])) {
                                echo '<fieldset>';
                                echo '<legend>' . ($template[0]['category_name'] ? $template[0]['category_name'] : '无分类名称') . '</legend>';
                            }

                            if (!empty($template) && is_array($template)) {
                                foreach ($template as $item) {
                                    echo "<a href='javascript:void(0);' class='mailTemplateItem' data-id='{$item['id']}'>{$item['template_name']}</a>";
                                }
                            }
                            if (!empty($template[0])) {
                                echo '</fieldset>';
                            }
                        }
                    }
                    ?>
                </div>
            </td>
        </tr>
        <tr>
            <td class="col1"></td>
            <td>
                <div class="col col-md-6" id="uploadImageArea">
                    <div class="row uploadImageLine">
                        <div class="col col-md-2">附件:</div>
                        <div class="col col-md-8">
                            <input type="file" name="uploadImage[]">
                            <div class="showUploadImage"></div>
                        </div>
                        <div class="col col-md-2">
                            <a href="javascript:void(0);" class="addUploadImageLine">添加</a>
                        </div>
                    </div>
                </div>
                <div class="col col-md-6">
                    <?php
                    $buyer_id = '';
                    $track_number = '';
                    $track = '';
                    $sku_str = '';
                    $pruduct_str = '';
                    $asin_str = '';

                    if (!empty($order_info)) {
                        $countryList = Country::getCodeNamePairsList('en_name');
                        if (!empty($order_info['info']['track_number'])) {
                            $track = 'http://www.17track.net/zh-cn/track?nums=' . $order_info['info']['track_number'];
                            $track_number = $order_info['info']['track_number'];
                        }
                        if (!empty($order_info['info']['buyer_id'])) {
                            $buyer_id = $order_info['info']['buyer_id'];
                        }
                        if (!empty($order_info['product'])) {
                            foreach ($order_info['product'] as $v) {
                                $sku_str .= ',' . $v['sku'];
                                $pruduct_str .= ',' . $v['title'];
                            }
                        }
                        if (!empty($asins)) {
                            foreach ($asins as $asin) {
                                $asin_str .= ',' . $asin;
                            }
                        }
                    }
                    ?>
                    <select id="countDataType" class="form-control" style="width:200px;">
                        <option value="all">选择绑定参数</option>
                        <option value="<?php echo $buyer_id; ?>">客户ID</option>
                        <option value="<?php echo $track_number; ?>">跟踪号</option>
                        <option value="<?php echo $track; ?>">查询网址</option>
                        <option value="<?php echo rtrim($pruduct_str, ','); ?>">产品标题</option>
                        <option value="<?php echo rtrim($sku_str, ','); ?>">产品sku</option>
                        <option value="<?php echo rtrim($asin_str, ','); ?>">ASIN</option>
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td class="col1">回复内容(英文)：</td>
            <td>
                <textarea name="reply_content_en" id="reply_content_en" rows="10" class="form-control" placeholder="输入回复内容(注意：此输入回复内容为英语)"></textarea>
            </td>
        </tr>
        <tr>
            <td class="col1"></td>
            <td>
                <div class="btn-group btn-group-sm">
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
            </td>
        </tr>
        <tr>
            <td class="col1">回复内容：</td>
            <td>
                <textarea name="reply_content" id="reply_content" rows="10" class="form-control" placeholder="发送给客户的内容"></textarea>
            </td>
        </tr>
        <tr>
            <td class="col1"></td>
            <td>
                <input type="submit" value="发送" id="sendEmailBtn" class="btn btn-primary">
                <input type="hidden" id="sl_code" value="">
                <input type="hidden" id="tl_code" value="">
                <input type="hidden" name="account_id" value="<?php echo !empty($accouontId) ? $accouontId : ''; ?>">
                <input type="hidden" name="platform_order_id" value="<?php echo !empty($platformOrderId) ? $platformOrderId : ''; ?>">
            </td>
        </tr>
    </table>
    <?php
    ActiveForm::end();
    ?>
</div>
<script type="text/javascript" src="/js/jquery.form.js"></script>
<script type="text/javascript">

    //发送邮件
    $("#sendEmailBtn").on("click", function () {
        $("#sendEmailForm").ajaxSubmit({
            dataType: 'json',
            success: function (data) {
                if (data["bool"] == 1) {
                    layer.msg("发送成功", {icon: 1});
                } else {
                    layer.msg(data["msg"], {icon: 5});
                }
            }
        });
        return false;
    });

    //消息模板
    $("#page-wrapper").on("click", ".mailTemplateItem", function () {
        var id = $(this).attr("data-id");
        $.post("<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>", {
            "num": id
        }, function (data) {
            switch (data.status) {
                case "error":
                    layer.msg(data.message, {icon: 5});
                case "success":
                    var refund_content = $("#reply_content_en").val();
                    if (refund_content !== '') {
                        $("#reply_content_en").val(refund_content + "\n" + data.content);
                    } else {
                        $("#reply_content_en").val(data.content);
                    }
            }
        }, "json");
        return false;
    });

    //消息模板搜索
    $('.mailTemplateSearch').click(function () {
        var name = $(this).prev("input[type='text']").val();
        if (name.length == 0) {
            layer.msg('搜索名称不能为空', {icon: 5});
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/amazonreviewdata/searchmailtemplate']); ?>', {
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
                            html += "<a href='#' class='mailTemplateItem' data-id='" + item[index]["id"] + "'>" + item[index]["template_name"] + "</a>";
                        }
                        if (data[ix][0]) {
                            html += "</fieldset>";
                        }
                    }
                    $("#mailTemplateArea").html(html);
                }
            } else {
                layer.msg(data["message"], {icon: 5});
                $("#mailTemplateArea").html("");
            }
        }, 'json');
        return false;
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
        var content = $.trim($("#reply_content_en").val());
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
            url: '<?php echo Url::toRoute(['/mails/ebayinboxsubject/translate']); ?>',
            data: {'sl': sl, 'tl': tl, 'content': content},
            success: function (data) {
                if (data) {
                    $("#reply_content").val(data);
                    $("#reply_content").css('display', 'block');
                }
            }
        });
        return false;
    });

    //文件上传添加
    $("#uploadImageArea").on("click", ".addUploadImageLine", function () {
        var html = "<div class='row uploadImageLine'>";
        html += "<div class='col col-md-2'>附件: </div>";
        html += "<div class='col col-md-8'>";
        html += "<input type='file' name='uploadImage[]'>";
        html += "<div class='showUploadImage'></div>";
        html += "</div>";
        html += "<div class='col col-md-2'>";
        html += "<a href='javascript:void(0);' class='delUploadImageLine'>删除</a>";
        html += "</div>";
        html += "</div>";
        $("#uploadImageArea").append(html);
    });

    //文件上传预览
    $("#uploadImageArea").on("change", "input[type='file']", function () {
        var file = this.files[0];
        if (/(.jpg|.png|.gif|.ps|.jpeg)$/.test(file["name"])) {
            //创建一个img标签
            var img = document.createElement("img");
            //通过file对象创建对象URL
            img.src = window.URL.createObjectURL(file);
            img.onload = function () {
                //释放对象URL
                window.URL.revokeObjectURL(this.src);
            };
            $(this).next(".showUploadImage").css("display", "block").html($(img));
        }
    });

    //文件上传删除
    $("#uploadImageArea").on("click", ".delUploadImageLine", function () {
        $(this).parents(".uploadImageLine").remove();
    });

    //鼠标定位添加订单信息
    $("#countDataType").on("change", function () {
        var data_value = $(this).val();
        if (data_value == '') {
            layer.msg('暂无此数据', {icon: 2});
            return false;
        }
        if (data_value != 'all') {
            getValue('reply_content_en', data_value);
        }
    });

    function getValue(objid, str) {
        var myField = document.getElementById("" + objid);
        //IE浏览器
        if (document.selection) {
            myField.focus();
            sel = document.selection.createRange();
            sel.text = str;
            sel.select();
        } else if (myField.selectionStart || myField.selectionStart == '0') {
            //得到光标前的位置
            var startPos = myField.selectionStart;
            //得到光标后的位置
            var endPos = myField.selectionEnd;
            // 在加入数据之前获得滚动条的高度
            var restoreTop = myField.scrollTop;
            myField.value = myField.value.substring(0, startPos) + str + myField.value.substring(endPos, myField.value.length);
            //如果滚动条高度大于0
            if (restoreTop > 0) {
                // 返回
                myField.scrollTop = restoreTop;
            }
            myField.focus();
            myField.selectionStart = startPos + str.length;
            myField.selectionEnd = startPos + str.length;
        } else {
            myField.value += str;
            myField.focus();
        }
    }
</script>