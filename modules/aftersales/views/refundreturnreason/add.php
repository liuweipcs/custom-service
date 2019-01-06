<?php
use yii\bootstrap\ActiveForm;
use app\modules\systems\models\Tag;
?>
<div class="popup-wrapper">
    <?php
        $form = ActiveForm::begin([
            'id' => 'platform-form',
            'layout' => 'horizontal',
            'action' => Yii::$app->request->getUrl(),
            'enableClientValidation' => false,
            'validateOnType' => false,
            'validateOnChange' => false,
            'validateOnSubmit' => true,
        ]);
     ?>
    <div class="popup-body">     
        <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'content')->textArea(['rows' => '6']) ?>
            </div>
            <div class="col-sm-6">
                 <?php echo $form->field($model, 'mark')->textArea(['rows' => '6']) ?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'sort_order')->textArea(['rows' => '1']) ?>
            </div>
         </div>
     </div>
     <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
     </div>
    <?php
        ActiveForm::end();
    ?>
</div>