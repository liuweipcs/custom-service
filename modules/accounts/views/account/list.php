<?php 
use app\components\GridView;
use yii\helpers\Url;

$this->title = '帐号管理';
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
                    'field' => 'account_name',
                    'type' => 'text',
                    'htmlOptions' => [ 
                    ],  
                ],
                [
                    'field' => 'account_short_name',
                    'type' => 'text',
                    'htmlOptions' => [
                    ],
                ],
                [
                    'field' => 'account_discussion_name',
                    'type' => 'text',
                    'htmlOptions' => [
                    ],
                ],
                [
                    'field' => 'platform_code',
                    'type' => 'text',
                    'htmlOptions' => [
                    ],
                ],
                [
                    'field' => 'email',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],
                [
                    'field' => 'site',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],
                [
                    'field' => 'site_code',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],
                [
                    'field' => 'seller_id',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],
                [
                    'field' => 'status_text',
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
                ]
            ],
            'toolBars' => [
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
            ],
        ]);
    ?>
        </div>
    </div>
</div>