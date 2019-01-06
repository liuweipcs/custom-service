<?php

use app\components\GridView;
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\datetime\DateTimePicker;

$this->title = 'shopee纠纷';
DateTimePicker::widget(['name' => 'load']);
?>
<style>
    #updateIssueInfoSpeed p.success {
        line-height: 30px;
        color: #5cb85c;
        font-size: 20px;
        font-weight: bold;
    }

    #updateIssueInfoSpeed p.error {
        line-height: 30px;
        color: #d9534f;
        font-size: 20px;
        font-weight: bold;
    }
    #search-form .list-inline li:nth-of-type(4){
        width: 250px;
    }
    /*时间样式*/
    #search-form .list-inline li:nth-of-type(10){
        width: 300px;
    }
    #search-form .list-inline li:nth-of-type(11){
        width: 300px;
        margin-left: 95px;
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
                        'field' => '_state',
                        'type' => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field' => 'returnsn',
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
                        'field' => 'ordersn',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' =>
                            [
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
                        'field' => 'reason',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'due_date',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'create_time',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'status',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'refund_amount',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'is_deal',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'hrefOperateButton',
                        'text' => '处理',
                        'href' => Url::toRoute(['/mails/shopeedispute/handle']),
                        'buttons' => [
                            [
                                'text' => '详情',
                                'href' => Url::toRoute('/mails/shopeedispute/details'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '100%',
                                    '_height' => '100%',
                                ],
                            ]
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                        ]
                    ]
                ],
                'toolBars' => [
                    [
                        'href' => '#',
                        'text' => '导出',
                        'htmlOptions' => [
                            'id' => 'export',
                            'class' => 'btn btn-danger',
                            'data-src' => 'id',
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
        //导出excel
        $("#export").on("click", function () {
            var queryStr = $("#search-form").serialize();
            var dataSrc = $(this).attr('data-src');
            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/mails/shopeedispute/export'); ?>?" + queryStr;
            return false;
        });
    });
</script>