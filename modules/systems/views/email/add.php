<?php

use yii\bootstrap\ActiveForm;
use app\modules\systems\models\Email;
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
            <div class="col-sm-12">
                <?php echo $form->field($model, 'platform_code')->dropDownList(Email::platformDropdown()); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'emailaddress'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'imap_server'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'smtp_server'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'imap_protocol'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'smtp_protocol'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'imap_port'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'smtp_port'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'is_amazon_send')->inline()->radioList(['1' => '是', '0' => '否 【此项设置仅对沃尔玛平台设置有效】'], ['value' => 0]); ?>
            </div>
        </div>
        <div class="row"> 
            <div class="col-sm-12">
                <?php echo $form->field($model, 'is_encrypt')->inline()->radioList(['1' => '是', '0' => '否'], ['value' => 0]); ?>
            </div>
        </div>

        <div class="row"> 
            <div class="col-sm-12">
                <?php echo $form->field($model, 'encryption')->inline()->radioList(['ssl' => 'ssl', 'tls' => 'tls']); ?>
            </div>
        </div>
        <div class="row"> 
            <div class="col-sm-12">
               <?php echo $form->field($model, 'password')->passwordInput() ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'accesskey'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'filter_option')->textarea(['rows' => 6]); ?>
            </div>
        </div>


    </div>
    <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
    </div>
    <?php
    ActiveForm::end();
    ?>
</div>

<script>
//    $(function () {
//        $('input[name="Email[is_encrypt]"]').change(function () {
//            var val = $('input:radio[name="Email[is_encrypt]"]:checked').val();
//            if (val == 1) {
//                $(".encrypted").show();
//            }else{
//                $(".encrypted").hide();
//            }
//        });
//    });
</script>