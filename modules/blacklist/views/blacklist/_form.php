<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\accounts\models\Platform;

/* @var $this yii\web\View */
/* @var $model app\modules\blacklist\models\BlackList */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="black-list-form">

    <?php $form = ActiveForm::begin(); ?>

    <?php 
        if($model->isNewRecord){
            echo $form->field($model, 'platfrom_id')->dropDownList(Platform::getAllCountList(), ['prompt'=>'请选择','style'=>'width:auto;']);
        }
    ?>
    <?= $form->field($model, 'username')->textarea(['rows' => 20]) ?>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
