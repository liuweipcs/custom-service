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
                <?php echo $form->field($model, 'login_name');?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'user_name');?>
            </div>
         </div>
         <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'user_email');?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'user_telephone');?>
            </div>
         </div>
         <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'login_password')->passwordInput();?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'confirm_password')->passwordInput();?>
            </div>
         </div>         
         <div class="row">
            <div class="col-sm-6">
                <?php //echo $form->field($model, 'status')->label();?>
                <?php echo $form->field($model, 'status')->inline()->radioList($model->getStatusList(), ['value' => 1]);?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'role_ids')->dropdownList($roleList, ['encodeSpaces' => true,
                    'multiple' => true,
                    'size' => 22,
                ]);?>
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