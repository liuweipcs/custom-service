<?php
use yii\bootstrap\ActiveForm;
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
                <input type="hidden" value="<?php echo $audit_type;?>" name="audit_type" />
                <?php echo $form->field($model, 'suggest')->textArea(['rows' => '6']) ?>
            </div>
         </div>
     </div>
     <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
       <!-- <button class="btn btn-default close-button"><?php //echo Yii::t('system', 'Close');?></button>-->
     </div>
    <?php
        ActiveForm::end();
    ?>
</div>