<?php
use app\components\GridView;
use yii\helpers\Url;

$this->title = 'eBay纠纷-退款退货';
?>
<style>
    .orders{
        width:150px;
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
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                //'tags' => $tagList,
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
                        'field' => 'return_id',
                        'type' => 'text',
                        'htmlOptions' => [

                        ],
                    ],
                    [
                        'field' => 'order_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'class' => 'orders',
                        ],
                    ],
                    [
                        'field' => 'platform_order_id',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'order_type',
                        'type' => 'text',
                        'htmlOptions' => [

                        ],
                    ],
                    [
                        'field' => 'buyer_login_name',
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
                        'field' => 'return_reason',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'current_type',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'return_creation_date',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'update_time',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'status',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'state',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'return_quantity',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'buyer_estimated_refund_amount',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'actual_refund_amount',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'seller_response_date',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'hrefOperateButton',
                        'text' => '处理',
                        'href' => Url::toRoute(['/mails/ebayreturnsrequests/handle']),
                        'buttons' => [
//                            [
//                                'text' => '处理',
//                                'href' => Url::toRoute(['/mails/ebayreturnsrequests/handle']),
//                                'queryParams' => '{id}',
//                                'htmlOptions' => [
//                                    'class' => 'edit-record',
//                                    '_width' => '70%',
//                                    '_height' => '70%'
//                                ],
//                            ],
                            [
                                'text' => '更新',
                                'href' => Url::toRoute(['/mails/ebayreturnsrequests/refresh']),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record',

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
                'toolBars' => [
                    [
                        'href' => Url::toRoute('/mails/ebayreturnsrequests/batchrefresh'),
                        'text' => Yii::t('system', '批量更新'),
                        'htmlOptions' => [
                            'class' => 'ajax-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => Yii::t('system', '导出数据'),
                        'htmlOptions' => [
                            'id' => 'export-button',
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
    $("#export-button").on('click',function(e){
        var queryStr = $("#search-form").serialize();
        var dataSrc = $(this).attr('data-src');
        var checkBox = $('input[name=' + dataSrc + ']:checked');
        if (checkBox.length > 0) {
            checkBox.each(function () {
                queryStr += '&ids[]=' + $(this).val();
            });
        }
        location.href = "<?php echo Url::toRoute('/mails/ebayreturnsrequests/toexcel'); ?>?" + queryStr;
        return false;
    });

    $('.input-group').attr('style','width:300px;');
</script>