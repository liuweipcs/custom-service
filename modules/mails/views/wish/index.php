<?php
use app\components\GridView;
use yii\helpers\Url;
?>
<style>
    #search-form .input-group.date {
        width: 320px;
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
            $this->title = 'wish消息管理';
            echo GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'tags' => $tagList,
                'is_tags' => true,
                'account_email' => $account_email,
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
                        'field' => 'platform_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'label',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
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
                        'field' => 'order_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
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
                        'field' => 'user_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'last_updated',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'remain_replay_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'read_stat',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'is_replied',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'modify_by_time',
                        'type' => 'text',
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
                                'href' => Url::toRoute('/mails/wish/removetags'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ],

                           /* [
                                'text' => '标记为已处理',
                                'href' => Url::toRoute('/mails/aliexpress/batchprocessing'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record'
                                ],
                            ],*/
//                            [
//                                'text' => Yii::t('system', 'Delete'),
//                                'href' => Url::toRoute('/accounts/platform/delete'),
//                                'queryParams' => '{id}',
//                                'htmlOptions' => [
//                                    'class' => 'delete-button',
//                                    'confirm' => Yii::t('system', 'Confirm Delete The Record')
//                                ],
//                            ],
//                            [
//                                'text' => '查看',
//                                'href' => Url::toRoute('/mails/aliexpress/details?id={id}'),
//                                'queryParams' => '{id}',
//                                'htmlOptions' => [
//                                    'class' => 'delete-button',
//                                    'confirm' => Yii::t('system', 'Confirm Delete The Record')
//                                ],
//                    ]
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '90px']
                        ]
                    ]
                ],
                'toolBars' => [
                    [
                        'href' => Url::toRoute('/mails/wish/batchmark'),
                        'text' => '批量标记为已处理',
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                            'confirm' => '确定标记成已处理吗？',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/mails/wish/addtags'),
                        'text' => '批量添加标签',
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => '导出数据',
                        'htmlOptions' => [
                            'id' => 'export-mail-content',
                            'data-src' => 'id',
                        ]
                    ],
                 /*   [
                        'href' => Url::toRoute('/mails/wish/toexcel'),
                        'text' => Yii::t('system', '导出数据'),
                        'htmlOptions' => [
                            'id' => 'to_excel',
                            'target' => '_blank',
                        ]
                    ],*/
                ],
            ]);
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        //导出邮件内容
        $("#export-mail-content").on("click", function () {
            var queryStr = $("#search-form").serialize();
            var dataSrc = $(this).attr('data-src');
            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/mails/wish/export'); ?>?" + queryStr;
            return false;
        });

        //剩余收货时间
        function flushAcceptGoodsLastTime() {
            $("span.issue_reponse_last_time").each(function () {
                var end_time = $(this).attr("data-endtime");
                if (end_time && end_time.length != 0) {
                    //结束时间
                    var end = new Date(end_time);
                    //当前时间
                    var now = new Date();
                    var start_time = now.getTime();
                    //结束时间减去当前时间剩余的毫秒数
                    var leftTime = end.getTime() - start_time;
                    //计算剩余的天数
                    var days = parseInt(leftTime / 1000 / 60 / 60 / 24, 10);
                    //计算剩余的小时
                    var hours = parseInt(leftTime / 1000 / 60 / 60 % 24, 10);
                    //计算剩余的分钟
                    var minutes = parseInt(leftTime / 1000 / 60 % 60, 10);
                    //计算剩余的秒数
                    var seconds = parseInt(leftTime / 1000 % 60, 10);

                    days = days ? days + '天' : '';
                    hours = hours ? hours + '时' : (days && (hours || minutes || seconds) ? '0时' : '');
                    minutes = minutes ? minutes + '分' : (hours && seconds ? '0分' : '');
                    seconds = seconds ? seconds + '秒' : '';
                    $(this).text(days + hours + minutes + seconds);
                }
            });
        }

        setInterval(flushAcceptGoodsLastTime, 1000);
    });
</script>