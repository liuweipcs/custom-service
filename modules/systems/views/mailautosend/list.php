<?php

use app\components\GridView;
use yii\helpers\Url;

$this->title = '邮件自动发信规则管理';
?>
<style>
    p.filterRuleDetailWhen {
        margin: 0;
        line-height: 30px;
        background-color: #e6f7ff;
        padding: 5px;
        text-align: left;
    }

    p.filterRuleDetailBe {
        margin: 0;
        line-height: 30px;
        background-color: #fff0f6;
        padding: 5px;
        text-align: left;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'pager' => [],
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
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
                        'field' => 'rule_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'auto_rule_detail',
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
                        'field' => 'status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'sendmail',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'active_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
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
                                'href' => Url::toRoute('/systems/mailautosend/edit'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '100%',
                                    '_height' => '100%',
                                ],
                            ],
                            [
                                'text' => Yii::t('system', 'Delete'),
                                'href' => Url::toRoute('/systems/mailautosend/delete'),
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
                        'href' => Url::toRoute('/systems/mailautosend/add'),
                        'buttonType' => 'add',
                        'text' => Yii::t('system', 'Add'),
                        'htmlOptions' => [
                            'class' => 'add-button',
                            '_width' => '100%',
                            '_height' => '100%',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/systems/mailautosend/batchdelete'),
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
