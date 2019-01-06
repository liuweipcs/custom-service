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
                        'field' => 'platform_code',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'subject',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'content',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'send_status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'send_failure_reason',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'send_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'create_by',
                        'type' => 'text',
                        'htmlOptions' => [
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
                                'text' => \Yii::t('mail_outbox', 'Re-Send'),
                                'href' => Url::toRoute('/mails/mailoutbox/resend'),
                                'htmlOptions' => [
                                    'class' => 'delete-record'
                                ],
                            ],
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
/*                 'toolBars' => [
                    [
                        'href' => Url::toRoute('/mails/aliexpress/batchprocessing'),
                        'text' => '批量标记成已处理',
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                            'confirm' => '确定标记成已处理吗？',
                        ]
                    ],
                ], */
            ]);
            ?>
        </div>
    </div>
</div>