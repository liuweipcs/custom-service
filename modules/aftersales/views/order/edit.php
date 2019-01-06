<?php
use yii\bootstrap\ActiveForm;
?>
<div class="popup-wrapper">
    <?php
        $form = ActiveForm::begin([
            'id' => 'account-form',
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
                <?php echo $form->field($model, 'account_name');?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'account_short_name');?>
            </div>
         </div>
        <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'platform_code')->dropDownList($platformList);?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'email');?>
            </div>
         </div>         
         <div class="row">
            <div class="col-sm-6">
                <?php //echo $form->field($model, 'status')->label();?>
                <?php echo $form->field($model, 'status')->inline()->radioList($model->getStatusList());?>
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