<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\aftersales\models\RefundReason */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Refund Reasons', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <p>
                <?php //echo Html::a('修改', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']); ?>
                <?php
//                    echo Html::a('删除', ['delete', 'id' => $model->id], [
//                        'class' => 'btn btn-danger',
//                        'data' => [
//                            'confirm' => 'Are you sure you want to delete this item?',
//                            'method' => 'post',
//                        ],
//                    ])
                ?>
                <?php echo Html::a('返回列表', ['index'], ['class' => 'btn btn-primary']); ?>
            </p>

            <?=
            DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'label' => '编号',
                        'value' => $model->id,
                    ],
                    [
                        'label' => '责任所属部门',
                        'value' => $departmentList[$model->department_id]
                    ],
                    [
                        'label' => '原因类别',
                        'value' => $reasonTypeList[$model->reason_type_id]
                    ],
                    [
                        'label' => '亏损计算方式',
                        'value' => $formulaList[$model->formula_id]
                    ],
                    'remark:ntext',
                    'create_by',
                    'create_time',
                    'update_by',
                    'update_time',
                ],
            ])
            ?>

        </div>
    </div>
</div>