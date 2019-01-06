<?php

use app\components\GridView;
use yii\helpers\Url;
use yii\data\ArrayDataProvider;

?>

<?php

echo GridView::widget([
    'id' => 'grid-view',
    'dataProvider' => isset($dataProvider) ? $dataProvider : new ArrayDataProvider([]),
    'model' => $model,
    'url' => Url::toRoute(['/aftersales/skuqualityanalysis/skudetail',
        'sku'=>$sku,
    ]),
    'layout' => '{filters}{toolBar}{jsScript}{items}',
    'enableTools' => false,
    'pager' => [],
    'columns' => [
        [
            'field' => 'order_id',
            'headerTitle' => '订单号',
            'type' => 'text',
            'htmlOptions' => [
                'style' => [
                    //'word-break' => 'normal'
                ]
            ],
        ],
        [
            'field' => 'shipped_date',
            'headerTitle' => '发货日期',
            'type' => 'text',
            'htmlOptions' => [
            ],
        ],
        [
            'field' => 'loss_rmb',
            'headerTitle' => '实际损失金额RMB',
            'type' => 'text',
            'htmlOptions' => [
            ],
        ],
        [
            'field' => 'remark',
            'headerTitle' => '备注',
            'type' => 'text',
            'htmlOptions' => [
            ],
        ],
    ],

]);
?>
<!-- <script src="http://libs.baidu.com/jquery/1.9.0/jquery.js"></script> -->
