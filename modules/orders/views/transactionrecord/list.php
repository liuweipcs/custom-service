<?php
use app\components\GridView;
use yii\helpers\Url;
?>
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
                        'field' => 'transaction_id',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'receive_type',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'receiver_email',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'receiver_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'payer_id',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'payer_name',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'payer_email',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'payer_status',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'payment_type',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'order_time',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                   /* [
                        'field' => 'amt',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'tax_amt',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'fee_amt',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],*/
                    [
                        'field' => 'currency',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],

                    [
                        'field' => 'payment_status',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                  /*  [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'buttons' => [
                            [
                                'text' => Yii::t('system', 'Edit'),
                                'href' => Url::toRoute('/accounts/account/edit'),
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ],
                            [
                                'text' => Yii::t('system', 'Delete'),
                                'href' => Url::toRoute('/accounts/account/delete'),
                                'htmlOptions' => [
                                    'class' => 'delete-record',
                                    'confirm' => Yii::t('system', 'Confirm Delete The Record')
                                ],
                            ]
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                        ]
                    ]*/
                ],
               /* 'toolBars' => [
                    [
                        'href' => Url::toRoute('/accounts/account/add'),
                        'buttonType' => 'add',
                        'text' => Yii::t('system', 'Add'),
                        'htmlOptions' => [
                            'class' => 'add-button',
                            '_width' => '48%',
                            '_height' => '48%',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/accounts/account/batchdelete'),
                        'buttonType' => 'delete',
                        'text' => Yii::t('system', 'Delete'),
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                            'confirm' => Yii::t('system', 'Confirm Delete These Records'),
                        ]
                    ],
                ],*/
            ]);
            ?>
        </div>
    </div>
</div>

