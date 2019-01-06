<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\orders\models\OrderReplyQuery */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Order Reply Queries', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-reply-query-view">

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
            'platform_code',
            'order_id',
            'is_send',
            'template_id',
            'rule_id',
            'order_create_time',
            'order_pay_time',
            'order_ship_time',
            'reply_date',
            'error_info',
            'fail_count',
            'execute_id',
            'reply_id',
        ],
    ]) ?>

</div>
