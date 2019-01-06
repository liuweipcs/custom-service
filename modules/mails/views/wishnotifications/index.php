<?php
use app\components\GridView;
use yii\helpers\Url;

$this->title = 'Wish通知列表';
?>
<style>
    .select2-container--krajee {
        min-width: 120px;
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
                        'field' => 'account_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => [
                                'min-width' => '120px',
                            ],
                        ],
                    ],
                    [
                        'field' => 'noti_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => [
                                'min-width' => '220px',
                            ],
                        ],
                    ],
                    [
                        'field' => 'title',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'message',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'perma_link',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'is_view',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'view_by',
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
                            'style' => [
                                'min-width' => '180px',
                            ],
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'buttons' => [
                            [
                                'text' => '确认查看',
                                'href' => Url::toRoute('/mails/wishnotifications/check'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record',
                                	'confirm' => '确定已经查看?'
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
                    /*[
                        'href' => Url::toRoute('/mails/aliexpress/batchprocessing'),
                        'text' => '批量标记成已处理',
                        'htmlOptions' => [
                            'class' => 'delete-record',
                            'data-src' => 'id',
                            'confirm' => '确定标记成已处理吗？',
                        ]
                    ],*/
                ],
            ]);
            ?>
        </div>
    </div>
</div>