<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\mails\models\AmazonReply */
/* @var $form yii\widgets\ActiveForm */
?>


<?php 
    $form = ActiveForm::begin([
        'id'                     => 'amazoninbox-form',
        'layout'                 => 'horizontal',
        'action'                 => Url::toRoute(['/mails/amazonreply/createsubject', 'id' => $subject_id]),
        'enableClientValidation' => false,
        'validateOnType'         => false,
        'validateOnChange'       => false,
        'validateOnSubmit'       => true,        
    ]);

?>

<div class="popup-body">
    <?= Html::tag(
        'div',
        Html::tag('label', '收件人', ['class' => 'control-label col-sm-3']) .
        Html::tag('div', Html::textInput('', $receiver, ['class' => 'form-control']), ['class' => 'col-sm-6']),
        [
         'class' => 'form-group'
        ]
    )?>

    <?= $form->field($model, 'reply_title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'reply_content')->textarea(['rows' => 6]) ?>

</div>

<div class="popup-footer">
    <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>

    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>


</div>

<?php ActiveForm::end(); ?>

