<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\modules\systems\models\BasicConfig;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\aftersales\models\RefundReasonSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Refund Reasons';
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <!--<h1><?= Html::encode($this->title) ?></h1>-->
            <?php // echo $this->render('_search', ['model' => $searchModel]);  ?>

            <p>
                <?= Html::a('新增数据', ['create'], ['class' => 'btn btn-success']) ?>
            </p>
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
//                    'id',
                    [
                        'filter' => BasicConfig::getParentList(52),
                        'label' => '责任所属部门',
                        'attribute' => 'department_id',
                        'format' => 'html',
                        'value' => function ($model) {
                            $parentList = BasicConfig::getParentList(52);
                            return isset($parentList[$model->department_id]) ? $parentList[$model->department_id]:'-';
                        }
                    ],
                    [
                        'filter' => $searchModel->getReasonType(),
                        'label' => '原因类别',
                        'attribute' => 'reason_type_id',
                        'format' => 'html',
                        'value' => function ($model) {
                            $parentList = BasicConfig::getParentList($model->department_id);
                            return isset($parentList[$model->reason_type_id]) ? $parentList[$model->reason_type_id] : '-';
                        }
                    ],
//                    'reason_type_id',
//                    'formula_id',
                    [
                        'filter' => BasicConfig::getParentList(108),
                        'label' => '亏损计算方式',
                        'attribute' => 'formula_id',
                        'format' => 'html',
                        'value' => function ($model) {
                            $parentList = BasicConfig::getParentList(108);
                            return isset($parentList[$model->formula_id]) ? $parentList[$model->formula_id]:'-';
                        }
                    ],
                    [
                        'filter' => BasicConfig::getParentList(122),
                        'label' => '退款成本计算方式',
                        'attribute' => 'refund_cost_id',
                        'format' => 'html',
                        'value' => function ($model) {
                            $parentList = BasicConfig::getParentList(122);
                            return isset($parentList[$model->refund_cost_id]) ? $parentList[$model->refund_cost_id]:'-';
                        }
                    ],
                    [
                        'filter' => BasicConfig::getParentList(123),
                        'label' => '重寄成本计算方式',
                        'attribute' => 'resend_cost_id',
                        'format' => 'html',
                        'value' => function ($model) {
                            $parentList = BasicConfig::getParentList(123);
                            return isset($parentList[$model->resend_cost_id]) ? $parentList[$model->resend_cost_id]:'-';
                        }
                    ],
                    'remark:ntext',
//                     'create_by_id',
                    'create_by',
                    'create_time',
//                     'update_by_id',
                    'update_by',
                    'update_time',
//                    ['class' => 'yii\grid\ActionColumn'],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => '操作',
//                        'headerOptions' => ['width' => '80'],
                        'template' => '{view}',
                    ],
                            
                ],
            ]);
            ?>
        </div>
    </div>
</div>
