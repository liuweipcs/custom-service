<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\orders\models\OrderReplyQuery */

$this->title = 'Create Order Reply Query';
$this->params['breadcrumbs'][] = ['label' => 'Order Reply Queries', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-reply-query-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
