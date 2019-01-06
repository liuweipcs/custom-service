<?php

use app\components\GridView;
use yii\helpers\Url;

$this->title = 'CD邮件主题';
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

    #batchCloseDiscussionOverlay {
        display: none;
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0px;
        left: 0px;
        z-index: 9999;
        filter: alpha(opacity=60);
        background-color: #333;
        opacity: 0.6;
        -moz-opacity: 0.6;
    }

    #batchCloseDiscussionSpeed {
        position: absolute;
        width: 480px;
        height: 360px;
        top: 50%;
        left: 50%;
        margin-left: -240px;
        margin-top: -180px;
        z-index: 10000;
        overflow-y: auto;
    }

    #batchCloseDiscussionSpeed p.success {
        line-height: 30px;
        color: #5cb85c;
        font-size: 20px;
        font-weight: bold;
    }

    #batchCloseDiscussionSpeed p.error {
        line-height: 30px;
        color: #d9534f;
        font-size: 20px;
        font-weight: bold;
    }

</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'tags' => $tagList,
                'is_tags' => true,
                'account_email' => $account_email,
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
                        'field' => 'platform_order_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'min-width' => '170px'
                            ],
                        ],
                    ],
                    [
                        'field' => 'subject',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'left',
                            ],
                        ],
                    ],
                    [
                        'field' => 'inbox_type',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field' => 'product_ean',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'min-width' => '170px'
                            ],
                        ],
                    ],
                    [
                        'field' => 'account_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '150px'
                            ],
                        ],
                    ],
                    [
                        'field' => 'buyer_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field' => 'status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '150px'
                            ],
                        ],
                    ],
                    [
                        'field' => 'last_updated_date',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field' => 'modify_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field' => 'is_read',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field' => 'is_reply',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field' => 'reply_by_and_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'buttons' => [
                            [
                                'text' => '移除标签',
                                'href' => Url::toRoute('/mails/cdiscountinboxsubject/removetags'),
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
                        'href' => Url::toRoute('/mails/cdiscountinboxsubject/addtags'),
                        'text' => Yii::t('system', '批量添加标签'),
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/mails/cdiscountinboxsubject/batchmark'),
                        'text' => '批量标记为已回复',
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                            'confirm' => '确定标记为已回复吗？',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => '批量关闭问题',
                        'htmlOptions' => [
                            'id' => 'batchclosediscussion',
                            'class' => 'btn btn-danger',
                            'data-src' => 'id',
                        ]
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>

<div id='batchCloseDiscussionOverlay'>
    <div id='batchCloseDiscussionSpeed'></div>
</div>

<script type="text/javascript">
    $(function() {

        //批量关闭问题
        $("#batchclosediscussion").on("click", function () {
            var dataSrc = $(this).attr('data-src');

            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length == 0) {
                layer.alert("请选择关闭项");
                return false;
            }

            layer.confirm("确定关闭问题吗？", {icon: 3}, function (index) {
                layer.close(index);

                var defer = $.Deferred();
                defer.resolve($("#batchCloseDiscussionSpeed").html("<p class='success'>关闭问题开始</p>"));
                $("#batchCloseDiscussionOverlay").css("display", "block");
                $("body").css("overflow", "hidden");

                checkBox.each(function () {
                    var id = $(this).val();
                    defer = defer.then(function () {
                        return $.ajax({
                            type: "POST",
                            url: "<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/closediscussion']); ?>",
                            data: {"id": id},
                            dataType: "json",
                            global: false,
                            success: function (data) {
                                if (data["code"] == 1) {
                                    $("#batchCloseDiscussionSpeed").append("<p class='success'>讨论ID：" + data["data"]["inbox_id"] + ",关闭成功</p>");
                                } else {
                                    $("#batchCloseDiscussionSpeed").append("<p class='error'>讨论ID：" + data["data"]["inbox_id"] + "," + data["message"] + "</p>");
                                }
                            }
                        });
                    });
                });

                defer.done(function () {
                    $("#batchCloseDiscussionSpeed").append("<p class='success'>关闭问题完毕</p>");
                    setTimeout(function () {
                        $("#batchCloseDiscussionOverlay").css("display", "none");
                        window.location.href = "<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/list']); ?>";
                    }, 500);
                });
                return false;
            });
        });
    });
</script>


