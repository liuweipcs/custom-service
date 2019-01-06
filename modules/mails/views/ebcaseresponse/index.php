<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\mails\models\EbCaseResponseSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '纠纷升级退款结果查询列表';
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1><?= Html::encode($this->title) ?></h1>
            <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

            <p>
                <?php //echo Html::a('Create Eb Case Response', ['create'], ['class' => 'btn btn-success']) ?>
            </p>
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
//            'id',
                    [
                        'label' => '个案编号ID',
                        'attribute' => 'case_id',
                        'value' => 'case_id'
                    ],
//            'type',
//            'status',
                    // 'refund_source',
                    [
                        'label' => '退款状态',
                        'attribute' => 'refund_status',
                        'value' => 'refund_status'
                    ],
                    'content:ntext',
                    // 'error:ntext',
                    // 'lock_status',
                    // 'lock_time',
                    // 'account_id',
                    // 'create_by',
                    [
                        'label' => '退款单创建时间',
                        'attribute' => 'create_time',
                        'value' => 'create_time'
                    ],
                // 'modify_by',
                // 'modify_time',
//            ['class' => 'yii\grid\ActionColumn'],
                ],
            ]);
            ?>
        </div>
    </div>
</div>
