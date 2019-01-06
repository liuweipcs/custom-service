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
    $this->title = 'ebay收件箱';
        echo GridView::widget([
            'id' => 'grid-view',
            'dataProvider' => $dataProvider,     
            'model' => $model,
            'tags' => $tagList,
            //'headSummary' => $headSummary,
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
                    'field' => 'message_id',
                    'type' => 'text',
                    'htmlOptions' => [ 
                    ],  
                ],
                [
                    'field' => 'subject',
                    'type' => 'text',
                    'htmlOptions' => [
                    ],
                ],
                [
                    'field' => 'item_id',
                    'type' => 'text',
                    'htmlOptions' => [
                    ],
                ],
                [
                    'field' => 'high_priority',
                    'type' => 'text',
                    'htmlOptions' => [
                    ],
                ],
                /*[
                    'field' => 'message_type',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],*/
                [
                    'field' => 'sender',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],
                [
                    'field' => 'is_replied',
                    'type' => 'text',
                    'htmlOptions' => [
                    ],
                ],
                [
                    'field' => 'is_read',
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
                    'field' => 'receive_date',
                    'type' => 'text',
                    'sortAble' => true,
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
                                'text' => '移除标签',
                                'href' => Url::toRoute('/mails/ebayinbox/removetags'),
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
                        'href' => Url::toRoute('/mails/ebayinbox/addtags'),
                        'text' => Yii::t('system', '添加标签'),
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                ], 
                [
                    'href' => Url::toRoute('/mails/ebayinbox/signreplied'),
                        'text' => Yii::t('system', '批量标记已回复'),
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
