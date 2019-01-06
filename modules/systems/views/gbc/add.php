<?php

use yii\bootstrap\ActiveForm;
use app\modules\systems\models\Gbc;
use app\modules\systems\models\Condition;
use app\modules\systems\models\ConditionGroup;

?>
<style>
    .email, .addr {
        display: none;
    }
</style>
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
                <?php echo $form->field($model, 'type')->inline()->radioList(Gbc::getStatusList()); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'account_type')->inline()->radioList(Gbc::getAccountTypeList()); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'platform_code')->dropDownList(Gbc::platformDropdown()); ?>
            </div>
        </div>
        <div class="row account">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'ebay_id')->textarea(['placeholder' => '支持填写多个账号，不同账号用英文逗号“,”分开', 'rows' => 6]); ?>
            </div>
        </div>
        <div class="row email">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'payment_email')->textarea(['placeholder' => '支持填写多个邮箱，不同账号用英文逗号“,”分开', 'rows' => 6]); ?>
            </div>
        </div>
        <div class="row addr">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'country'); ?>
            </div>
            <div class="col-sm-3">
                <?php echo $form->field($model, 'state'); ?>
            </div>
            <div class="col-sm-3">
                <?php echo $form->field($model, 'city'); ?>
            </div>
        </div>
        <div class="row addr">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'address'); ?>
            </div>
            <div class="col-sm-3">
                <?php echo $form->field($model, 'postal_code'); ?>
            </div>
        </div>
        <div class="row addr">
            <div class="col-sm-6">
                <?php echo $form->field($model, 'recipients'); ?>
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

<script type="text/javascript">
    $(function () {
        $("input[name='Gbc[type]']").on("click", function () {
            var type = $(this).val();

            if ($(this).is(":checked")) {
                if (type == "1") {
                    $(".email,.addr").css("display", "none");
                    $(".account").css("display", "block");
                } else if (type == "2") {
                    $(".account,.addr").css("display", "none");
                    $(".email").css("display", "block");
                } else if (type == "3") {
                    $(".account,.email").css("display", "none");
                    $(".addr").css("display", "block");
                }
            }
        });
    });
</script>
