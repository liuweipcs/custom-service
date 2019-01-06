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
                        'field' => 'reply_title',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'question_type',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'recipient_id',
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
                        'field' => 'create_by',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'create_time',
                        'type' => 'text',
                        'htmlOptions' => [
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
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'buttons' => [
                            [
                                'text' => '编辑',
                                'href' => Url::toRoute(['/mails/ebayreply/edit']),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ],
//                            [
//                                'text' => '更新',
//                                'href' => Url::toRoute(['/mails/ebayinquiry/refresh']),
//                                'queryParams' => '{id}',
//                                'htmlOptions' => [
//                                    'class' => 'delete-record'
//                                ],
//                            ],
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
                        'href' => Url::toRoute('/mails/ebayreply/initiativeadd'),
                        'text' => Yii::t('system', '发送新邮件'),
                        'htmlOptions' => [
                            'class' => 'edit-record',
//                            'data-src' => 'id',
                        ]
                    ],

                ],
                /*'toolBars' => [
                    [
                        'href' => Url::toRoute('/accounts/platform/add'),
                        'buttonType' => 'add',
                        'text' => Yii::t('system', 'Add'),
                        'htmlOptions' => [
                            'class' => 'add-button',
                            '_width' => '48%',
                            '_height' => '48%',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/accounts/platform/batchdelete'),
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