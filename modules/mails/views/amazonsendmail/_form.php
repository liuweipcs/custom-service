<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\mails\models\AmazonSendMail */
/* @var $form yii\widgets\ActiveForm */
?>

<?php $form = ActiveForm::begin([
        'id'                     => 'amazonsendmail-form',
        'layout'                 => 'horizontal',
        'action'                 => Url::toRoute(['/mails/amazonsendmail/create', 'id' => $id, 'type' => $type]),
        'enableClientValidation' => false,
        'validateOnType'         => false,
        'validateOnChange'       => false,
        'validateOnSubmit'       => true,        
    ]); ?>

<div class="popup-body">

    <?= $form->field($model, 'from')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'to')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'subject')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <label class="control-label col-sm-3" for="">附件</label>
        <div class="col-sm-6">
            <input type="file" name="AmazonSendMail[file]" />
        </div>
    </div>

    <?= $form->field($model, 'body')->textarea(['rows' => 6]) ?>


</div>

<div class="popup-footer">
    <?= Html::button('发送', ['class' => 'btn btn-primary', 'onclick' => 'dosubmit()']) ?>

    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>


</div>

<?php ActiveForm::end(); ?>
<script type="text/javascript">
    var dosubmit = function()  {
        $('#amazonsendmail-form').ajaxSubmit({success : function (data) {
            var $data = $.parseJSON(data);
            layer.alert($data.message);
        }});
    }

</script>
