<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\orders\models\OrderReplyQuery */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="order-reply-query-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id')->textInput() ?>

    <?= $form->field($model, 'platform_code')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'order_id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'is_send')->textInput() ?>

    <?= $form->field($model, 'template_id')->textInput() ?>

    <?= $form->field($model, 'rule_id')->textInput() ?>

    <?= $form->field($model, 'order_create_time')->textInput() ?>

    <?= $form->field($model, 'order_pay_time')->textInput() ?>

    <?= $form->field($model, 'order_ship_time')->textInput() ?>

    <?= $form->field($model, 'reply_date')->textInput() ?>

    <?= $form->field($model, 'error_info')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'fail_count')->textInput() ?>

    <?= $form->field($model, 'execute_id')->textInput() ?>

    <?= $form->field($model, 'reply_id')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
