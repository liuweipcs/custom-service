<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
/* @var $this yii\web\View */
/* @var $model app\modules\mails\models\AmazonInbox */
/* @var $form yii\widgets\ActiveForm */
?>

<?php 
    $form = ActiveForm::begin([
        'id'                     => 'amazoninbox-form',
        'layout'                 => 'horizontal',
        'action'                 => Yii::$app->request->getUrl(),
        'enableClientValidation' => false,
        'validateOnType'         => false,
        'validateOnChange'       => false,
        'validateOnSubmit'       => true,
    ]);
?>

<div class="popup-body"> 

<?= $form->field($model, 'sender')->textInput(['maxlength' => true]) ?>

<?= $form->field($model, 'sender_email')->textInput(['maxlength' => true]) ?>

<?= $form->field($model, 'subject')->textInput(['maxlength' => true]) ?>

<?= $form->field($model, 'body')->textarea(['rows' => 6]) ?>



</div>

<div class="popup-footer">
    <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn ajax-submit btn-success' : 'btn ajax-submit btn-primary']) ?>

    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
</div>

<?php ActiveForm::end(); ?>
