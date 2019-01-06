<?php
use yii\bootstrap\ActiveForm;
use app\modules\systems\models\RefundAccount;
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
                <?php echo $form->field($model, 'email');?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'api_username');?>
            </div>
         </div>
         <div class="row">

            <div class="col-sm-6">
                 <?php echo $form->field($model, 'api_password')->textInput(['type'=>'password']);?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'api_signature');?>
            </div>
         </div>
         <div class="row">

            <div class="col-sm-6">
                 <?php echo $form->field($model, 'client_id');?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'secret')->textInput(['type'=>'password']);?>
            </div>
         </div>
          <div class="row"> 
            <div class="col-sm-6">
                 <?php echo $form->field($model, 'status')->inline()->radioList(RefundAccount::getStatusList(), ['value' => $model->status]);?>
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