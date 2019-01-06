<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\reports\models\AfterSaleStatistics */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'After Sale Statistics', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1><?= Html::encode($this->title) ?></h1>

            <p>
                <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                <?=
                Html::a('Delete', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Are you sure you want to delete this item?',
                        'method' => 'post',
                    ],
                ])
                ?>
            </p>

            <?=
            DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'after_sale_id',
                    'platform_code',
                    'department_id',
                    'reason_type_id',
                    'formula_id',
                    'account_id',
                    'account_name',
                    'type',
                    'refund_amount',
                    'refund_amount_rmb',
                    'subtotal',
                    'subtotal_rmb',
                    'currency',
                    'exchange_rate',
                    'create_by',
                    'create_time',
                    'status',
                    'pro_cost_rmb',
                    'add_time',
                ],
            ])
            ?>

        </div>
    </div>
</div>
