<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\mails\models\AmazonFeedBack */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Amazon Feed Backs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="amazon-feed-back-view">

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
            'date',
            'rating',
            'comments:ntext',
            'your_response:ntext',
            'arrived_on_time',
            'item_as_described',
            'customer_service',
            'order_id',
            'rater_email:email',
            'rater_role',
            'update_date',
        ],
    ]) ?>

</div>
