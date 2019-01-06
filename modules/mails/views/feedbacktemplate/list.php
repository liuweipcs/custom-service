<?php

use app\components\GridView;
use yii\helpers\Url;

$this->title = '回评模板';
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
                            'align' => 'center',
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
                        'field' => 'template_content',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                            'style' => [
                                'max-width' => '480px',
                            ],
                        ],
                    ],
                    [
                        'field' => 'create_by',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'create_time',
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
                                'text' => Yii::t('system', 'Edit'),
                                'href' => Url::toRoute('/mails/feedbacktemplate/edit'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ],
                            [
                                'text' => Yii::t('system', 'Delete'),
                                'href' => Url::toRoute('/mails/feedbacktemplate/delete'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record',
                                    'confirm' => Yii::t('system', 'Confirm Delete The Record')
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
                        'href' => Url::toRoute('/mails/feedbacktemplate/add'),
                        'buttonType' => 'add',
                        'text' => Yii::t('system', 'Add'),
                        'htmlOptions' => [
                            'class' => 'add-button',
                            '_width' => '48%',
                            '_height' => '48%',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/mails/feedbacktemplate/batchdelete'),
                        'buttonType' => 'delete',
                        'text' => Yii::t('system', 'Delete'),
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                            'confirm' => Yii::t('system', 'Confirm Delete These Records'),
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>