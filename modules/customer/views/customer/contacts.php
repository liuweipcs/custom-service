<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use app\modules\customer\models\CustomerTagsRule;
use app\modules\mails\models\MailTemplate;
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

        .form-control_value {
            width: 10%;
            height: 35px;
        }
    </style>

    <?php
    $form = ActiveForm::begin([
        'id' => 'addMailFilterManageForm',
        'action' => Url::toRoute(['/customer/customer/contacts']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addMailFilterManage">
            <tr>
                <td class="col1">平台：</td>
                <td colspan="2">
                    <input type="text" id="plat_f" name="platform_code" class="form-control" readonly="readonly" value="<?php echo $info[0]['platform_code']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">联系方式：</td>
                <td colspan="2">
                    <input class="CheBox1" type="checkbox" name="inbox" checked="checked"/> 站内信联系
                    <input class="CheBox2" type="checkbox" name="email" checked="checked"/> 邮件联系
                </td>
            </tr>
            <tr class="b1">
                <td class="col1">店铺：</td>
                <td colspan="2">
                    <input type="text" name="account_name" class="form-control" readonly="readonly" value="<?php echo $account_name; ?>">
                </td>
            </tr>
            <tr class="b1">
                <td class="col1">客户ID：</td>
                <td colspan="2">
                    <input type="text" name="buyer_id" class="form-control" readonly="readonly" value="<?php echo $buyer_id; ?>">
                </td>
            </tr>
            <tr class="a1">
                <td class="col1">发件邮箱：</td>
                <td colspan="2">
                    <input type="text" name="to_email" class="form-control" readonly="readonly" value="<?php echo $email; ?>">
                </td>
            </tr>
            <tr class="a1">
                <td class="col1">收件人邮箱：</td>
                <td colspan="2">
                    <input type="text" name="buyer_email" class="form-control" readonly="readonly" value="<?php echo $buyer_email; ?>">
                </td>
            </tr>
            <tr class="a1">
                <td class="col1">邮箱主题：</td>
                <td colspan="2">
                    <input type="text" name="email_title" class="form-control" value="">
                </td>
            </tr>
            <tr>
                <td class="col1">客户跟进模板：</td>
                <td colspan="2">
                    <div class="input-group">
                        <input type="text" class="form-control mail_template_search_text" style="display: table-cell;" placeholder="消息模板搜索">
                        <span class="input-group-btn">
                                    <button class="btn btn-default mail_template_search_btn" type="button">搜索</button>
                                </span>
                    </div>
                    <div class="panel-body mail_template_area">
                        <ul class="list-inline">
                            <?php
                            $mailTemplates = MailTemplate::getCustomerDataAsArray($info[0]['platform_code']);
                            foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName) {
                                echo "<li><a href='#' class='mail_template_unity' value='$mailTemplatesId'>$mailTemplateName</a></li>";
                            }
                            ?>
                        </ul>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">发送内容：</td>
                <td colspan="2">
                    <div id="content" name="content" class="col-md-11">
                        <script id="content" name="content" type="text/plain"></script>
                        <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.config.js"></script>
                        <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.all.js"></script>
                        <script type="text/javascript">
                            var ue = UE.getEditor('content', {zIndex: 6600, initialFrameHeight: 400});
                        </script>
                    </div>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan="2">
                    <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
                    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                </td>
            </tr>
        </table>
    </div>
    <div class="popup-footer"></div>
    <?php
    ActiveForm::end();
    ?>
</div>

<script type="text/javascript" language="javascript">

    $(function(){
        $(".CheBox1").click(function () {
            var $cr = $(".CheBox1");
            if ($cr.is(":checked")) {
                $(".b1").show();
            }
            else {
                $(".b1").hide();
            }
        });
        $(".CheBox2").click(function () {
            var $cr = $(".CheBox2");
            if ($cr.is(":checked")) {
                $(".a1").show();
            }
            else {
                $(".a1").hide();
            }
        });

        //模板ajax
        $('.mail_template_area').delegate('.mail_template_unity', 'click', function () {
            $.post('<?php echo Url::toRoute(['/customer/customer/gettemplate']);?>', {'num': $(this).attr('value')}, function (data) {
                switch (data.status) {
                    case 'error':
                        layer.msg(data.message, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                        return;
                    case 'success':

                        var ue = UE.getEditor('content');
                        var refund_content = ue.getContent();//邮件内容
                        if (refund_content !== '') {
                            ue.setContent(refund_content + '\n' + data.content);
                        } else {
                            ue.setContent(data.content);
                        }
                }
            }, 'json');
        });

        //模板搜索
        $('.mail_template_search_btn').click(function () {
            var templateName = $.trim($('.mail_template_search_text').val());
            var platform_code = $('#plat_f').val();
            if (templateName.length == 0) {
                layer.msg('搜索名称不能为空。', {
                    icon: 2,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                });
                return;
            }
            $.post('<?php echo Url::toRoute(['/customer/customer/searchtemplate']);?>', {
                'name': templateName,
                'platform_code': platform_code,
            }, function (data) {
                switch (data.status) {
                    case 'error':
                        layer.msg(data.message, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                        return;
                    case 'success':
                        var templateHtml = '';
                        for (var i in data.content) {
                            templateHtml += '<a class="mail_template_unity" value="' + i + '">' + data.content[i] + '</a>';
                        }
                        $('.mail_template_area').html(templateHtml);
                }
            }, 'json');
        });

    });

</script>