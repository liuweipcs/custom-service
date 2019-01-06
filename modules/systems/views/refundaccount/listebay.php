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
                            'text' => '绑定退票账号',
                            'href' => Url::toRoute('/systems/refundaccount/punitive'),
                            'htmlOptions' => [
                                'class' => 'edit-record',
                                '_height' => '26%',
                            ],
                        ],
                    ],
                    'htmlOptions' => [
                        'align' => 'center',
                    ]
                ]
            ],
        ]);
    ?>
        </div>
    </div>
</div>