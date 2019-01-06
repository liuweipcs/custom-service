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
    <div class="popup-body" style="margin-bottom:50px">     
        <div class="row">
            <label for="name">选择退票账号</label>
            <select class="form-control" name="refund_account_id">
            <?php foreach($refund_account_data as $key => $value){?>
            <?php if($relation_refund_account_id == $value['id']){?>
            <option value="<?php echo $value['id'];?>" selected><?php echo $value['email'];?></option>
            <?php }else{?>
             <option value="<?php echo $value['id'];?>"><?php echo $value['email'];?></option>
             <?php }?>
            <?php }?>
            </select>
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