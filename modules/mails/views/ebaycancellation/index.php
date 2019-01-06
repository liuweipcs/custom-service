<?php
use app\components\GridView;
use yii\helpers\Url;

$this->title = 'eBay纠纷-取消交易';
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
                        'field' => 'state',
                        'type' => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field' => 'cancel_id',
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
                        'field' => 'legacy_order_id',
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
                        'field' => 'requestor_type',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'buyer',
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
                        'field' => 'cancel_request_date',
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
                        'field' => 'cancel_status',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'cancel_state',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'seller_response_due_date',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'hrefOperateButton',
                        'text' => '处理',
                        'href' => Url::toRoute(['/mails/ebaycancellation/handle']),
                        'buttons' => [
//                            [
//                                'text' => '处理',
//                                'href' => Url::toRoute(['/mails/ebaycancellation/handle']),
//                                'queryParams' => '{id}',
//                                'htmlOptions' => [
//                                    'class' => 'edit-record'
//                                ],
//                            ],
                            [
                                'text' => '更新',
                                'href' => Url::toRoute(['/mails/ebaycancellation/refresh']),
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
                'toolBars' => [
                    [
                        'href' => Url::toRoute('/mails/ebaycancellation/batchrefresh'),
                        'text' => Yii::t('system', '批量更新'),
                        'htmlOptions' => [
                            'class' => 'ajax-button',
                            'data-src' => 'id',
                        ]
                    ],
                ]
            ]);
            ?>
        </div>
    </div>
</div>