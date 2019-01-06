<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\orders\models\OrderReplyQuerySearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="order-reply-query-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'platform_code') ?>

    <?= $form->field($model, 'order_id') ?>

    <?= $form->field($model, 'is_send') ?>

    <?= $form->field($model, 'template_id') ?>

    <?php // echo $form->field($model, 'rule_id') ?>

    <?php // echo $form->field($model, 'order_create_time') ?>

    <?php // echo $form->field($model, 'order_pay_time') ?>

    <?php // echo $form->field($model, 'order_ship_time') ?>

    <?php // echo $form->field($model, 'reply_date') ?>

    <?php // echo $form->field($model, 'error_info') ?>

    <?php // echo $form->field($model, 'fail_count') ?>

    <?php // echo $form->field($model, 'execute_id') ?>

    <?php // echo $form->field($model, 'reply_id') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
