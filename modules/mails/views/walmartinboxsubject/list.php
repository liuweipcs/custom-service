<?php

use app\components\GridView;
use yii\helpers\Url;

$this->title = '沃尔玛邮件主题';
?>
<style>
    .select2-container--krajee {
        width: 150px !important;
    }

    a.tag-label, a.site-label, a.account-label {
        text-decoration: none;
    }

    a.label-on {
        text-decoration: underline;
        font-weight: bold;
    }

    #search-form .input-group.date {
        width: 300px;
    }
</style>
<style>
    .mail-notify-list {
        display: none;
    }

    .mail-notify {
        width: 400px;
        height: 180px;
        border: 1px solid #ccc;
        background-color: #fff;
        overflow: hidden;
        margin: 0 auto 10px auto;
    }

    .mail-notify-title {
        height: 45px;
        line-height: 45px;
        padding: 0 15px;
        font-size: 20px;
        font-weight: bold;
        border-bottom: 1px solid #ccc;
    }

    .mail-notify-title .glyphicon-remove {
        float: right;
        font-size: 18px;
        margin-top: 12px;
        cursor: pointer;
    }

    .mail-notify-content {
        padding: 15px;
    }

    .mail-notify-content p a {
        color: #169BD5;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'id'            => 'grid-view',
                'dataProvider'  => $dataProvider,
                'model'         => $model,
                'tags'          => $tagList,
                'is_tags'       => true,
                'sites'         => $siteList,
                'is_sites'      => true,
                'account_email' => $account_email,
                'columns'       => [
                    [
                        'field'       => 'state',
                        'type'        => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field'       => 'order_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'min-width' => '170px'
                            ],
                        ],
                    ],
                    [
                        'field'       => 'now_subject',
                        'type'        => 'text',
                        'htmlOptions' => [],
                    ],
                    [
                        'field'       => 'buyer_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'min-width' => '150px'
                            ],
                        ],
                    ],
                    [
                        'field'       => 'sender_email',
                        'type'        => 'text',
                        'htmlOptions' => [],
                    ],
                    [
                        'field'       => 'is_read',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field'       => 'is_replied',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field'       => 'account_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'min-width' => '150px'
                            ],
                        ],
                    ],
                    [
                        'field'       => 'receive_email',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'min-width' => '160px'
                            ],
                        ],
                    ],
                    [
                        'field'       => 'receive_date',
                        'type'        => 'text',
                        'sortAble'    => true,
                        'htmlOptions' => [
                            'style' => [
                                'min-width' => '160px'
                            ],
                        ],
                    ],
                    [
                        'field'       => 'reply_by_and_time',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'min-width'  => '160px',
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field'       => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type'        => 'operateButton',
                        'buttons'     => [
                            [
                                'text'        => '回复',
                                'href'        => Url::toRoute('/mails/walmartreply/createsubject'),
                                'queryParams' => 'id',
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ],
                            [
                                'text'        => '移除标签',
                                'href'        => Url::toRoute('/mails/walmartinboxsubject/removetags'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ],
                            [
                                'text' => '移至客户来信',
                                'href' => Url::toRoute('/mails/walmartinboxsubject/moveclientletter'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record',
                                    'confirm' => '确定要移至客户来信吗？',
                                ],
                            ],
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '90px']
                        ]
                    ]
                ],
                'toolBars'      => [
                    [
                        'href'        => Url::toRoute('/mails/walmartinboxsubject/batchmark'),
                        'text'        => '批量标记为已回复',
                        'htmlOptions' => [
                            'class'    => 'delete-button',
                            'data-src' => 'id',
                            'confirm'  => '确定标记为已回复吗？',
                        ]
                    ],
                    [
                        'href'        => Url::toRoute('/mails/walmartinboxsubject/addtags'),
                        'text'        => Yii::t('system', '批量添加标签'),
                        'htmlOptions' => [
                            'class'    => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href'        => '#',
                        'text'        => '导出Excel',
                        'htmlOptions' => [
                            'id'       => 'export-excel',
                            'data-src' => 'id',
                        ]
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/jquery.cookie.js"></script>
<script type="text/javascript">
    var keyboards = '<?php echo $keyboards; ?>'
    var keyboards = JSON.parse(keyboards);

    $(document).ready(
        function () {
            document.onkeyup = function () {
                var tag_id = '';
                var ids = '';

                if (event.shiftKey && keyboards['shift'] != 'undefined' && keyboards['shift'][event.keyCode] != 'undefined') {
                    $('[name="id"]:checked').each(function () {
                        if (ids == '') {
                            ids = $(this).val();
                        }
                        else {
                            ids += ',' + $(this).val();
                        }
                    })
                    tag_id = keyboards['shift'][event.keyCode]
                }
                if (event.ctrlKey && keyboards['ctrl'] != 'undefined' && keyboards['ctrl'][event.keyCode] != 'undefined') {
                    $('[name="id"]:checked').each(function () {
                        if (ids == '') {
                            ids = $(this).val();
                        }
                        else {
                            ids += ',' + $(this).val();
                        }
                    })
                    tag_id = keyboards['ctrl'][event.keyCode]
                }
                if (event.altKey && keyboards['alt'] != 'undefined' && keyboards['alt'][event.keyCode] != 'undefined') {
                    $('[name="id"]:checked').each(function () {
                        if (ids == '') {
                            ids = $(this).val();
                        }
                        else {
                            ids += ',' + $(this).val();
                        }
                    })
                    tag_id = keyboards['alt'][event.keyCode]
                }

                if (tag_id != '' && tag_id != 'undefined') {
                    if (ids == '') {
                        alert('没有选中数据');
                        return false;
                    }
                    $.post('<?= Url::toRoute(['/mails/walmartinboxsubject/addtags', 'type' => 'list'])?>', {
                        'MailTag[inbox_id]': ids,
                        'MailTag[tag_id][]': tag_id,
                        'MailTag[type]': 'list'
                    }, function (data) {
                        if (data.code == "200")
                            window.location.href = data.url;
                    }, 'json');
                }

            }
        }
    );

    $(function () {

        //walmart邮件提醒
        function walmartInboxMailNotify() {
            var walmart_mail_notify = $.cookie("walmart_mail_notify");
            if (walmart_mail_notify == "1") {
                return false;
            }

            var params = $("#search-form").serialize();
            $.ajax({
                type: "POST",
                url: "<?php echo Url::toRoute(['/mails/walmartinboxsubject/getwalmartmailnotify']); ?>",
                data: params,
                dataType: "json",
                global: false,
                success: function (data) {
                    if (data["code"] == 1) {
                        var data = data["data"];
                        var content = "";

                        for (var ix in data) {
                            content += "<div class='mail-notify'>";
                            content += "    <div class='mail-notify-title'>";
                            content += "    <span class='glyphicon glyphicon-remove'></span>";
                            content += "    提醒";
                            content += "    </div>";
                            content += "    <div class='mail-notify-content'>";
                            content += "    <p><?php echo Yii::$app->user->identity->login_name; ?>，你好，有一封新的 \"" + data[ix]["type_mark"] + "\" 邮件，请尽快处理</p>";
                            content += "    <p>主题：<a target='_blank' href='/mails/walmartinboxsubject/view?id=" + data[ix]["id"] + "'>" + data[ix]["now_subject"] + "</a></p>";
                            content += "    </div>";
                            content += "</div>";
                        }

                        if (content.length != 0) {
                            layer.closeAll();
                            layer.open({
                                type: 1,
                                closeBtn: 1,
                                title: "walmart邮件提醒",
                                anim: 6,
                                area: ["430px", "225px"],
                                offset: "r",
                                shade: false,
                                shadeClose: true,
                                content: content,
                                success: function (layero, index) {
                                    layer.style(index, {
                                        marginLeft: -10
                                    });
                                    //添加删除事件
                                    $(layero).find(".mail-notify-title > span.glyphicon-remove").on("click", function () {
                                        $(this).parents(".mail-notify").remove();
                                        return false;
                                    });
                                },
                                cancel: function (index, layero) {
                                    layer.confirm('是否今天之内不再弹出提醒窗口？', {
                                        btn: ['是', '否']
                                    }, function (index) {
                                        var d = new Date();
                                        var leftTime = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1, 0, 0, 0);
                                        if (navigator.userAgent.indexOf("Chrome") > -1) {
                                            leftTime.setTime(leftTime.getTime() + (8 * 60 * 60 * 1000));
                                        }
                                        $.cookie("walmart_mail_notify", "1", {expires: leftTime, path: '/'});
                                        layer.close(index);
                                    }, function () {
                                    });
                                }
                            });
                        }
                    }
                }
            });
        }

        setTimeout(walmartInboxMailNotify, 3000);
        setInterval(walmartInboxMailNotify, 30000);

        //导出excel
        $("#export-excel").on("click", function () {
            var queryStr = $("#search-form").serialize();
            var dataSrc = $(this).attr('data-src');
            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/mails/walmartinboxsubject/export'); ?>?" + queryStr;
            return false;
        });
    });
</script>


