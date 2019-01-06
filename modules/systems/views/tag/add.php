<?php

use yii\bootstrap\ActiveForm;
use app\modules\systems\models\Tag;

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
                <?php echo $form->field($model, 'tag_name'); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'tag_en_name'); ?>
            </div>
        </div>

        <?php if (!empty($platformList)) { ?>
            <div class="row">
                <div class="col-sm-12">
                    <?php echo $form->field($model, 'platform_code')->dropdownList($platformList, ['encodeSpaces' => true]); ?>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'status')->inline()->radioList($model->getStatusList(), ['value' => Tag::TAG_STATUS_VALID]); ?>
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