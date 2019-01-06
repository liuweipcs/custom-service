<?php

use app\components\GridView;
use yii\helpers\Url;

$this->title = 'eBay纠纷-未收到物品';
?>
<style>
    .orders {
        width: 150px;
    }
    #search-form .list-inline li:nth-of-type(13) {
        width: 300px;
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
            echo GridView::widget([
                'id'           => 'grid-view',
                'dataProvider' => $dataProvider,
                'model'        => $model,
                //'tags' => $tagList,
                'pager'        => [],
                'columns'      => [
                    [
                        'field'       => '_state',
                        'type'        => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field'       => 'inquiry_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'order_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'class' => 'orders',
                        ],
                    ],
                    [
                        'field'       => 'platform_order_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'order_type',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'buyer',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'account_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'creation_date',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'update_time',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'status',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'state',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'buyer_init_expect_refund_amt',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'refund_amount',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'seller_make_it_right_by_date',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'logistics',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'ship_country',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'warehouse',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'shipped_date',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'location',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'pay_time',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type'        => 'hrefOperateButton',
                        'text'        => '处理',
                        'href'        => Url::toRoute(['/mails/ebayinquiry/handle']),
                        'buttons'     => [
//                            [
//                                'text' => '处理',
//                                'href' => Url::toRoute(['/mails/ebayinquiry/handle']),
//                                'queryParams' => '{id}',
//                                'htmlOptions' => [
//                                    'class' => 'edit-record',
//                                    '_width' => '70%',
//                                    '_height' => '70%'
//                                ],
//                            ],
                            [
                                'text'        => '更新',
                                'href'        => Url::toRoute(['/mails/ebayinquiry/refresh']),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record'
                                ],
                            ],
                            /*[
                                'text' => Yii::t('system', 'Delete'),
                                'href' => Url::toRoute('/accounts/platform/delete'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-button',
                                    'confirm' => Yii::t('system', 'Confirm Delete The Record')
                                ],
                            ]*/
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => 'min-width:120px'
                        ]
                    ]
                ],
                'toolBars'     => [
                    [
                        'href'        => Url::toRoute('/mails/ebayinquiry/batchrefresh'),
                        'text'        => Yii::t('system', '批量更新'),
                        'htmlOptions' => [
                            'class'    => 'ajax-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href'        => '#',
                        'text'        => Yii::t('system', '导出数据'),
                        'htmlOptions' => [
                            'id'       => 'export-button',
                            'data-src' => 'id',
                        ]
                    ],
                ]
            ]);
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $("#export-button").on('click', function (e) {
        var queryStr = $("#search-form").serialize();
        var dataSrc = $(this).attr('data-src');
        var checkBox = $('input[name=' + dataSrc + ']:checked');
        if (checkBox.length > 0) {
            checkBox.each(function () {
                queryStr += '&ids[]=' + $(this).val();
            });
        }
        location.href = "<?php echo Url::toRoute('/mails/ebayinquiry/toexcel'); ?>?" + queryStr;
        return false;
    });

    $('.input-group').attr('style', 'width:300px;');
</script>