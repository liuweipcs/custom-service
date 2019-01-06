<?php

use app\components\GridView;
use yii\helpers\Url;

?>
<style>
    #search-form .input-group.date {
        width: 320px;
    }
    .form-horizontal .control-label{
        text-align: center;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            $this->title = '速卖通消息';
            echo GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'tags' => $tagList,
                'is_tags' => true,
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
                        'field' => 'msg_sources',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'other_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'last_message_content',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'account_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'read_stat',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'is_replied',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'deal_stat',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'receive_date',
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
                                'text' => '标记为已处理',
                                'href' => Url::toRoute('/mails/aliexpress/batchprocessing'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record'
                                ],
                            ],
                            [
                                'text' => '移除标签',
                                'href' => Url::toRoute('/mails/aliexpress/removetags'),
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
                    [
                        'href' => Url::toRoute('/mails/aliexpress/batchprocessing'),
                        'text' => '批量标记成已处理',
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                            'confirm' => '确定标记成已处理吗？',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/mails/aliexpress/addtags'),
                        'text' => '批量添加标签',
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/mails/aliexpress/batchreply'),
                        'text' => '批量回复邮件',
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                            '_width' => '80%',
                            '_height' => '80%'
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/mails/aliexpress/signreplied'),
                        'text' => Yii::t('system', '标记回复'),
                        'htmlOptions' => [
                            'class' => 'ajax-button',
                            'data-src' => 'id',
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>