<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\blacklist\models\BlackListSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="black-list-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'platfrom_id') ?>

    <?= $form->field($model, 'platfrom_code') ?>

    <?= $form->field($model, 'username') ?>

    <?= $form->field($model, 'create_time') ?>

    <?php // echo $form->field($model, 'modify_time') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
