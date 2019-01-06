<?php 
use app\components\GridView;
use yii\helpers\Url;

$this->title = 'ebay升级退款管理';
?>
<div id="page-wrapper">
<!--     <div class="row">
        <div class="col-lg-12">
            <div class="page-header bold">规则标签列表</div>
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
                        'align' => 'center',
                    ],
                ],
                [
                    'field' => 'account_short_name',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],
                [
                    'field' => 'is_refund',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],
                [
                    'field' => 'claim_amount',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],
                /*[
                    'field' => 'create_by',
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
                ],*/
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
                            'href' => Url::toRoute('/accounts/ebaycaserefund/edit'),
                            'queryParams' => '{id}',
                            'htmlOptions' => [
                                'class' => 'edit-record',
                                '_width' => '48%',
                                '_height' => '30%',
                            ],
                        ],
                    ],
                    'htmlOptions' => [
                        'align' => 'center',
                    ]
                ]
            ],
            'toolBars' => [
//                [
//                    'href' => Url::toRoute('/systems/ebaycaserefund/add'),
//                    'buttonType' => 'add',
//                    'text' => Yii::t('system', 'Add'),
//                    'htmlOptions' => [
//                        'class' => 'add-button',
//                        '_width' => '48%',
//                        '_height' => '30%',
//                    ]
//                ],
                [
                    'href' => Url::toRoute('/accounts/ebaycaserefund/batchchangetorefund'),
                    'text' => Yii::t('system', '修改为自动退款'),
                    'htmlOptions' => [
                        'class' => 'delete-button',
                        'data-src' => 'id',
                        'confirm' => Yii::t('system', '确定修改为自动退款'),
                    ]
                ],
                [
                    'href' => Url::toRoute('/accounts/ebaycaserefund/batchchangetonotrefund'),
                    'text' => Yii::t('system', '修改为不自动退款'),
                    'htmlOptions' => [
                        'class' => 'delete-button',
                        'data-src' => 'id',
                        'confirm' => Yii::t('system', '确定修改为不自动退款'),
                    ]
                ],
            ],
        ]);
    ?>
        </div>
    </div>
</div>