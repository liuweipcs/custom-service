<?php

use app\components\GridView;
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\datetime\DateTimePicker;

$this->title = 'shopee交易';
DateTimePicker::widget(['name' => 'load']);
?>
<style>
    #search-form .list-inline li:nth-of-type(3){
        width: 250px;
    }
    #search-form .list-inline li:nth-of-type(9){
        width: 300px;
    }
    #search-form .list-inline li:nth-of-type(10){
        width: 300px;
        margin-left: 95px;
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
                        'field' => '_state',
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
                        ],
                    ],
                    [
                        'field' => 'order_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'ordersn',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' =>
                            [
                                'align' => 'center',
                            ],
                    ],
                    [
                        'field' => 'buyer_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'cancel_reason',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'update_time',
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
                        'field' => 'order_status',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'is_deal',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'hrefOperateButton',
                        'text' => '处理',
                        'href' => Url::toRoute(['/mails/shopeecancellation/handle']),
                        'buttons' => [
                            [
                                'text' => '详情',
                                'href' => Url::toRoute('/mails/shopeecancellation/details'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '100%',
                                    '_height' => '100%',
                                ],
                            ]
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                        ]
                    ]
                ],
               /* 'toolBars' => [
                    [
                        'href' => '#',
                        'text' => '导出',
                        'htmlOptions' => [
                            'id' => 'export',
                            'class' => 'btn btn-danger',
                            'data-src' => 'id',
                        ]
                    ],
                ],*/
            ]);
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        //TODO
    });
</script>