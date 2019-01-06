<?php
use yii\bootstrap\ActiveForm;
?>
<div class="popup-wrapper">
    <?php
        $form = ActiveForm::begin([
            'id' => 'role-form',
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
                <?php echo $form->field($model, 'role_name');?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'role_code');?>
            </div>
         </div>
         <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'parent_id')->dropdownList($roleList, ['encodeSpaces' => true]);?>
            </div>
             <div class="col-sm-6">
                 <label class="control-label col-sm-3" for="role-platform_code">平台Code</label>
                 <div class="col-sm-6">
                     <?php echo \kartik\select2\Select2::widget([
                         'name' =>'Role[platform_code]',
                         'data' =>$platform,
                         'value' => explode(',',$model->platform_code),
                         'options' =>[
                             'multiple' => true,
                             'placeholder'=>'--请输入--',
                         ],

                     ]);?>
                 </div>

             </div>
         </div>
         <div class="row">
            <div class="col-sm-6">
                <?php //echo $form->field($model, 'status')->label();?>
                <?php echo $form->field($model, 'status')->inline()->radioList($model->getStatusList());?>
            </div>
         </div>
         <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'description')->textArea();?>
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