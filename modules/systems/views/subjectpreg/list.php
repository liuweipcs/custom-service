<?php 
use app\components\GridView;
use yii\helpers\Url;

$this->title = 'ebay主题过滤关键字管理';
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
                    'field' => 'preg_str',
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
                        'align' => 'center',
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
                            'text' => Yii::t('system', 'Edit'),
                            'href' => Url::toRoute('/systems/subjectpreg/edit'),
                            'queryParams' => '{id}',
                            'htmlOptions' => [
                                'class' => 'edit-record',
                                '_width' => '48%',
                                '_height' => '30%',
                            ],
                        ],
                        [
                            'text' => Yii::t('system', 'Delete'),
                            'href' => Url::toRoute('/systems/subjectpreg/delete'),
                            'queryParams' => '{id}',
                            'htmlOptions' => [
                                'class' => 'delete-record',
                                'confirm' => Yii::t('system', 'Confirm Delete The Record')
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
                    'href' => Url::toRoute('/systems/subjectpreg/add'),
                    'buttonType' => 'add',
                    'text' => Yii::t('system', 'Add'),
                    'htmlOptions' => [
                        'class' => 'add-button',
                        '_width' => '48%',
                        '_height' => '30%',
                    ]
                ],                       
                [
                    'href' => Url::toRoute('/systems/subjectpreg/batchdelete'),
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