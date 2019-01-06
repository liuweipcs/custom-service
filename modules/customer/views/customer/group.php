<?php
use app\components\GridView;
use yii\helpers\Url;
?>
<style>
    #search-form .input-group.date {
        width: 320px;
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
            $this->title = '客户分组';
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
                        'field' => 'group_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
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
                        'field' => 'instruction',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'customer_number',
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
                        'field' => 'modify_by',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],

                    [
                        'field' => 'modify_time',
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
                                'href' => Url::toRoute('/customer/customer/editorgroup'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '80%',
                                    '_height' => '80%',
                                ],
                            ],
                            [
                                'text' => Yii::t('system', 'Delete'),
                                'href' => Url::toRoute('/customer/customer/deletegroup'),
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
                        'href' => Url::toRoute('/customer/customer/addgroup'),
                        'buttonType' => 'add',
                        'text' => Yii::t('system', 'Add'),
                        'htmlOptions' => [
                            'class' => 'add-button',
                            '_width' => '80%',
                            '_height' => '80%',
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>