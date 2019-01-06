<?php

use yii\bootstrap\ActiveForm;
use app\modules\systems\models\Gbc;
use app\modules\systems\models\Condition;
use app\modules\systems\models\ConditionGroup;

$this->title = '修改Gbc信息';
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
        <?php if ($model->type == '1') { ?>
            <div class="row">
                <div class="col-sm-6">
                    <?php $model->type = '1'; ?>
                    <?php echo $form->field($model, 'type')->inline()->radioList(['1' => '账号']); ?>
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
            <div class="row">
                <div class="col-sm-6">
                    <?php echo $form->field($model, 'ebay_id')->textarea(['rows' => 15, 'cols' => 20]); ?>
                </div>
            </div>
        <?php } elseif ($model->type == '2') { ?>
            <div class="row">
                <div class="col-sm-6">
                    <?php $model->type = '2'; ?>
                    <?php echo $form->field($model, 'type')->inline()->radioList(['2' => '付款邮箱']); ?>
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
            <div class="row">
                <div class="col-sm-6">
                    <?php echo $form->field($model, 'payment_email')->textarea(['rows' => 15, 'cols' => 20]); ?>
                </div>
            </div>
        <?php } else { ?>
            <div class="row">
                <div class="col-sm-6">
                    <?php $model->type = '3'; ?>
                    <?php echo $form->field($model, 'type')->inline()->radioList(['3' => '地址']); ?>
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
            <div class="row">
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
            <div class="row">
                <div class="col-sm-6">
                    <?php echo $form->field($model, 'address'); ?>
                </div>
                <div class="col-sm-3">
                    <?php echo $form->field($model, 'postal_code'); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <?php echo $form->field($model, 'recipients'); ?>
                </div>
            </div>
        <?php } ?>
    </div>
    <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
    </div>
    <?php
    ActiveForm::end();
    ?>
</div>



