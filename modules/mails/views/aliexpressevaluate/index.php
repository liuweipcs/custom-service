<?php

use app\components\GridView;
use yii\helpers\Url;

$this->title = '速卖通评价';
?>
<style>
    #search-form .input-group.date {
        width: 320px;
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
                        'field' => 'platform_order_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => [
                                'min-width' => '155px',
                            ],
                        ],
                    ],
                    [
                        'field' => 'issue_status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'sku',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'product_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'product_image',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'buyer_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'gmt_order_complete',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'buyer_fb_date',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'buyer_evaluation',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'buyer_feedback',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'account_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'reply_last_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'feedback_status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'reply_status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'platform_product_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => [
                                'min-width' => '120px',
                            ],
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'buttons' => [
                            [
                                'text' => '评价',
                                'href' => Url::toRoute('/mails/aliexpressevaluate/feedback'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '80%',
                                    '_height' => '80%',
                                ],
                            ],
                            [
                                'text' => '回复评价',
                                'href' => Url::toRoute('/mails/aliexpressevaluate/replyfeedback'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '80%',
                                    '_height' => '80%',
                                ],
                            ],
                            [
                                'text' => '标记回复',
                                'href' => Url::toRoute('/mails/aliexpressevaluate/mark'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record',
                                    'confirm' => '确定标记为已回复吗？',
                                ],
                            ]
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '90px']
                        ]
                    ]
                ],
                'toolBars' => [
                    [
                        'href' => Url::toRoute('/mails/aliexpressevaluate/batchmark'),
                        'text' => '批量标记为已回复',
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                            'confirm' => '确定标记为已回复吗？',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => '导出Excel',
                        'htmlOptions' => [
                            'id' => 'export-excel',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => "全部 ({$commentTypeNum['allComment']})",
                        'htmlOptions' => [
                            'class' => 'btn-primary',
                            'id' => 'all-comment',
                            'data-type' => 'all',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => "好评 ({$commentTypeNum['positiveComment']})",
                        'htmlOptions' => [
                            'class' => 'btn-success',
                            'id' => 'positive-comment',
                            'data-type' => 'positive',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => "中评 ({$commentTypeNum['neutralComment']})",
                        'htmlOptions' => [
                            'class' => 'btn-warning',
                            'id' => 'neutral-comment',
                            'data-type' => 'neutral',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => "差评 ({$commentTypeNum['negativeComment']})",
                        'htmlOptions' => [
                            'class' => 'btn-danger',
                            'id' => 'negative-comment',
                            'data-type' => 'negative',
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        $("select[name='gmt_order_complete']").on("change", function () {
            var mode = $(this).val();

            if (mode == "today") {
                var today = new Date();
                var year = today.getFullYear();
                var month = (today.getMonth() + 1) < 10 ? ("0" + (today.getMonth() + 1)) : (today.getMonth() + 1);
                var day = today.getDate() < 10 ? ("0" + today.getDate()) : today.getDate();

                $("input[name='start_time']").val(year + "-" + month + "-" + day + " 00:00:00");
                $("input[name='end_time']").val(year + "-" + month + "-" + day + " 23:59:59");
            } else if (mode == "yesterday") {
                var yesterday = new Date((new Date()).getTime() - 24 * 60 * 60 * 1000);
                var year = yesterday.getFullYear();
                var month = (yesterday.getMonth() + 1) < 10 ? ("0" + (yesterday.getMonth() + 1)) : (yesterday.getMonth() + 1);
                var day = yesterday.getDate() < 10 ? ("0" + yesterday.getDate()) : yesterday.getDate();

                $("input[name='start_time']").val(year + "-" + month + "-" + day + " 00:00:00");
                $("input[name='end_time']").val(year + "-" + month + "-" + day + " 23:59:59");
            } else if (mode == "past30day") {
                var today = new Date();
                var todayYear = today.getFullYear();
                var todayMonth = (today.getMonth() + 1) < 10 ? ("0" + (today.getMonth() + 1)) : (today.getMonth() + 1);
                var todayDay = today.getDate() < 10 ? ("0" + today.getDate()) : today.getDate();
                var past30day = new Date((new Date()).getTime() - 30 * 24 * 60 * 60 * 1000);
                var past30dayYear = past30day.getFullYear();
                var past30dayMonth = (past30day.getMonth() + 1) < 10 ? ("0" + (past30day.getMonth() + 1)) : (past30day.getMonth() + 1);
                var past30dayDay = past30day.getDate() < 10 ? ("0" + past30day.getDate()) : past30day.getDate();

                $("input[name='start_time']").val(past30dayYear + "-" + past30dayMonth + "-" + past30dayDay + " 00:00:00");
                $("input[name='end_time']").val(todayYear + "-" + todayMonth + "-" + todayDay + " 23:59:59");
            }
        });

        $("input[name='start_time'],input[name='end_time']").on("click", function () {
            $("select[name='gmt_order_complete']").val("custom");
            $("select[name='gmt_order_complete'] option[value='custom']").attr("selected", true);
        });

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
            location.href = "<?php echo Url::toRoute('/mails/aliexpressevaluate/export'); ?>?" + queryStr;
            return false;
        });

        //评价星级筛选
        $("#all-comment,#positive-comment,#neutral-comment,#negative-comment").on("click", function () {
            $("#search-form input[name='comment_type']").remove();
            $("#search-form").append("<input type='hidden' name='comment_type' value='" + $(this).attr("data-type") + "'>");
            $("#search-form").submit();
        });

        //响应表单提交事件，刷新评价类型统计
        $("#search-form").on("submit", function () {
            var params = $(this).serialize();
            $.post("<?php echo Url::toRoute('/mails/aliexpressevaluate/flushcountcommenttypenum') ?>", params, function (data) {
                if (data["code"] == 1) {
                    $("#all-comment span").text("全部 (" + data["data"]["allComment"] + ")");
                    $("#positive-comment span").text("好评 (" + data["data"]["positiveComment"] + ")");
                    $("#neutral-comment span").text("中评 (" + data["data"]["neutralComment"] + ")");
                    $("#negative-comment span").text("差评 (" + data["data"]["negativeComment"] + ")");
                }
            }, "json");
        });

    });
</script>