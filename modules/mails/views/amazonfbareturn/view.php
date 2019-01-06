<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\mails\models\AmazonFBAReturn */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Amazon Fbareturns', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="amazon-fbareturn-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'hash',
            'account_id',
            'return_date',
            'order_id',
            'sku',
            'asin',
            'fnsku',
            'product_name',
            'quantity',
            'fulfillment_center_id',
            'detailed_disposition',
            'reason',
            'status',
            'update_date',
        ],
    ]) ?>

</div>
