<?php

use app\components\GridView;
use yii\helpers\Url;

?>
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
    <!--     <div class="row">
            <div class="col-lg-12">
                <div class="page-header bold">平台列表</div>
            </div>
        </div> -->
    <div class="row">
        <div class="col-lg-12">
            <?php
            $this->title = 'Ebay邮件主题';
            echo GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'tags' => $tagList,
                'is_tags' => true,
                'account_email' => $account_email,
//'headSummary' => $headSummary,
                'pager' => [],
                'columns' => [
                    [
                        'field' => 'state',
                        'type' => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],

                    [
                        'field' => 'now_subject',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'item_id',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'buyer_id',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],

                    /*[
                        'field' => 'message_type',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],*/

                    [
                        'field' => 'is_replied',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'is_read',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'account_id',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'receive_date',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
//                [
//                    'field' => 'create_by',
//                    'type' => 'text',
//                    'htmlOptions' => [
//                    ],
//                ],
//                [
//                    'field' => 'create_time',
//                    'type' => 'text',
//                    'sortAble' => true,
//                    'htmlOptions' => [
//                        'align' => 'center',
//                    ],
//                ],
                    [
                        'field' => 'modify_by',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'modify_time',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],

                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'buttons' => [
                            [
                                'text' => '移除标签',
                                'href' => Url::toRoute('/mails/ebayinboxsubject/removetags'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ],
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '90px']
                        ]
                    ]


                ],
                'toolBars' => [

                    [
                        'href' => Url::toRoute('/mails/ebayinboxsubject/addtags'),
                        'text' => Yii::t('system', '添加标签'),
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                    ],

                    [
                        'href' => Url::toRoute('/mails/ebayinboxsubject/signreplied'),
                        'text' => Yii::t('system', '标记回复'),
                        'htmlOptions' => [
                            'class' => 'ajax-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/mails/ebayinboxsubject/toexcel'),
                        'text' => Yii::t('system', '导出数据'),
                        'htmlOptions' => [
                            'id' => 'to_excel',
                            'target' => '_blank',
//                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => '导出ebay邮件内容',
                        'htmlOptions' => [
                            'id' => 'export-mail-content',
                            'data-src' => 'id',
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/jquery.cookie.js"></script>
<script type="text/javascript">
    $('.input-group').attr('style', 'width:300px;');

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
                    $.post('<?= Url::toRoute(['/mails/ebayinboxsubject/addtags', 'type' => 'list'])?>', {'MailTag[inbox_id]': ids, 'MailTag[tag_id][]': tag_id, 'MailTag[type]': 'list'}, function (data) {
                        if (data.code == "200")
                            window.location.href = data.url;
                    }, 'json');
                }

            }
        }
    )

    $('#to_excel').on('click', function (e) {
        _href = $(this).attr('href');
        _start_time = $("input[name='start_time']").val();
        _end_time = $("input[name='end_time']").val()
        _account_id = $("input[name='account_id']").val()
        console.log(_account_id);

        if (_start_time == '') {
            alert('请选择导出数据的开始时间');
            return false;
        }

        _url = _href + '?account_id=' + _account_id + '&start_time=' + _start_time + '&end_time=' + _end_time

        _platform_code = $("select[name='platform_code']").val()

        if (_platform_code != "" && _platform_code != undefined) {
            _url += '&platform_code=' + _platform_code;
        }

        window.open(_url);
//        $(this).attr('href',_url);

    });


    $(function () {
        //ebay邮件提醒
        function ebayInboxMailNotify() {
            var ebay_mail_notify = $.cookie("ebay_mail_notify");
            if (ebay_mail_notify == "1") {
                return false;
            }

            var params = $("#search-form").serialize();
            $.ajax({
                type: "POST",
                url: "<?php echo Url::toRoute(['/mails/ebayinboxsubject/getebaymailnotify']); ?>",
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
                            content += "    <p><?php echo Yii::$app->user->identity->login_name; ?>，你好，有一封新的 \"" + data[ix]["tag_name"] + "\" 邮件，请尽快处理</p>";
                            content += "    <p>主题：<a target='_blank' href='/mails/ebayinboxsubject/detail?id=" + data[ix]["id"] + "'>" + data[ix]["now_subject"] + "</a></p>";
                            content += "    </div>";
                            content += "</div>";
                        }

                        if (content.length != 0) {
                            layer.closeAll();
                            layer.open({
                                type: 1,
                                closeBtn: 1,
                                title: "ebay邮件提醒",
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
                                        $.cookie("ebay_mail_notify", "1", {expires: leftTime, path: '/'});
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

        setTimeout(ebayInboxMailNotify, 3000);
        setInterval(ebayInboxMailNotify, 30000);


        //导出ebay邮件内容
        $("#export-mail-content").on("click", function () {
            var queryStr = $("#search-form").serialize();
            var dataSrc = $(this).attr('data-src');
            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/mails/ebayinboxsubject/exportmailcontent'); ?>?" + queryStr;
            return false;
        });
    });
</script>
