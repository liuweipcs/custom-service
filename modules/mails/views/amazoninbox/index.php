<?php

use app\components\GridView;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'AMAZON邮件';
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">

           <?= GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'tags' => $tagList,
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
                        'field' => 'order_id',
                        'type' => 'text',
                        'htmlOptions' => ['style' => ['vertical-align' => 'middle']],
                    ],


                    [
                        'field' => 'account_id',
                        'type' => 'text',
                        'htmlOptions' => [],
                    ],

                    [
                        'field' => 'subject',
                        'type' => 'text',   
                        'htmlOptions' => [],
                    ],

                    [
                        'field' => 'mail_type',
                        'type' => 'text',
                        'htmlOptions' => [],
                    ],

                    [
                        'field' => 'is_read',
                        'type' => 'text',
                        'htmlOptions' => [],
                    ],

                    [
                        'field' => 'is_replied',
                        'type' => 'text',
                        'htmlOptions' => [],
                    ],

                    [
                        'field' => 'sender',
                        'type' => 'text',
                        'htmlOptions' => [],
                    ],

                    [
                        'field' => 'receiver',
                        'type' => 'text',
                        'htmlOptions' => [],
                    ],

                    [
                        'field' => 'receive_date',
                        'type' => 'text',
                        'htmlOptions' => [],
                    ],

                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'buttons' => [
                            [
                                'text' => Yii::t('system', 'Reply'),
                                'href' => Url::toRoute('/mails/amazonreply/create'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ],
                            [
                                'text' => Yii::t('system', 'Delete'),
                                'href' => Url::toRoute('/mails/amazoninbox/delete'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record',
                                    'confirm' => Yii::t('system', 'Confirm Delete The Record')
                                ],
                            ],
                            [
                                'text' => '移除标签',
                                'href' => Url::toRoute('/mails/amazoninbox/removetags'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ],
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '90px']
                        ]
                    ]

                ],
                'toolBars' => [
                    /**
                    [
                        'href' => Url::toRoute('/mails/amazoninbox/create'),
                        'buttonType' => 'add',
                        'text' => Yii::t('system', 'Add'),
                        'htmlOptions' => [
                            'class' => 'add-button',
                            '_width' => '48%',
                            '_height' => '48%',
                        ]
                    ],                       
                    */
                    [
                        'href' => Url::toRoute('/mails/amazoninbox/batchdelete'),
                        'buttonType' => 'delete',
                        'text' => Yii::t('system', 'Delete'),
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                            'confirm' => Yii::t('system', 'Confirm Delete These Records'),
                        ]
                    ],

                    [
                        'href' => Url::toRoute('/mails/amazoninbox/addtags'),
                        'text' => Yii::t('system', '添加标签'),
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
