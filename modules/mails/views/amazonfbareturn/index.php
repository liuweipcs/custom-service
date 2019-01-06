<?php

use app\components\GridView;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
$this->title = 'Amazon-Return';
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">

            <?= GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'columns' => [
                    [
                        'field' => 'state',
                        'type' => 'checkbox',
                        'htmlOption' => [
                            'style' => [
                                'style' => [
                                    'vertical-align' => 'middle'
                                ],
                            ]
                        ],
                    ],

                    [
                        'field' => 'account_id',
                        'type' => 'text',
                        'htmlOption' => ['style' => []]
                    ],

                    [
                        'field' => 'return_date',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    [
                        'field' => 'order_id',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    [
                        'field' => 'sku',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    [
                        'field' => 'asin',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    /*[
                        'field' => 'fnsku',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],*/

                    [
                        'field' => 'product_name',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    [
                        'field' => 'quantity',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    [
                        'field' => 'fulfillment_center_id',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    [
                        'field' => 'detailed_disposition',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    [
                        'field' => 'reason',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    [
                        'field' => 'status',
                        'type' => 'text',
                        'htmlOption' => [],
                    ],

                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '90px']
                        ],
                        'buttons' => [
                            [
                                'text' => '联系买家',
                                'href' => Url::toRoute('/mails/amazonsendmail/create/?type=FbaReturn'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record'
                                ],
                            ]
                        ]
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
