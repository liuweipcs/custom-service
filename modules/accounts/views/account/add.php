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
            <div class="col-sm-12">
                <?php echo $form->field($model, 'account_name'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'account_short_name'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'platform_code')->dropDownList($platformList, ['onchange' => 'getAmazonSite(' . '$(this).val()' . ')']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'email'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php //echo $form->field($model, 'status')->label();?>
                <?php echo $form->field($model, 'status')->inline()->radioList($model->getStatusList(), ['value' => 1]); ?>
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
    function getAmazonSite(siteName) {

        var site = '<div class="col-sm-12"><?php echo str_replace("\n", '', $form->field($model, "site_code")->dropDownList($siteList));?></div>';

        if (siteName == "AMAZON")
            $('#account-platform_code').parents('.row').next().children().eq(0).before(site);
        else if ($('#account-platform_code').parents('.row').next().children().length > 1)
            $('#account-platform_code').parents('.row').next().children().eq(0).remove();

    }
</script>
