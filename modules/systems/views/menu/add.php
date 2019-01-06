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
                <?php echo $form->field($model, 'menu_name');?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'parent_id')->dropdownList($menuList, ['encodeSpaces' => true]);?>
            </div>
         </div>
         <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'route');?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'menu_icon');?>
            </div>
         </div>
         <div class="row">
            <div class="col-sm-6">
                <?php //echo $form->field($model, 'status')->label();?>
                <?php echo $form->field($model, 'is_show')->inline()->radioList([
                    1 => Yii::t('system', 'Yes'),
                    0 => Yii::t('system', 'No')
                ], ['value' => 1]);?>
            </div>
            <div class="col-sm-6">
                <?php //echo $form->field($model, 'status')->label();?>
                <?php echo $form->field($model, 'is_new')->inline()->radioList([
                    0 => Yii::t('system', 'Yes'),
                    1 => Yii::t('system', 'No')
                ], ['value' => 1]);?>
            </div>
         </div>
         <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'sort_order')->textInput(['value' => 0]);?>
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