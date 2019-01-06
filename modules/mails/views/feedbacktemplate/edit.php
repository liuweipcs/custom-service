<?php

use yii\bootstrap\ActiveForm;
use app\modules\accounts\models\Platform;

?>
<div class="popup-wrapper">
    <?php
    $form = ActiveForm::begin([
        'id' => 'mailtemplate-form',
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
                <?php echo $form->field($model, 'platform_code')->dropDownList(Platform::getPlatformAsArray()); ?>
            </div>
            <div class="col-sm-12">
                <?php echo $form->field($model, 'template_content')->textarea(['rows' => 10, 'cols' => 60]); ?>
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
<script type="text/javascript" language="JavaScript" src="<?php echo yii\helpers\Url::base(true); ?>/js/jquery-1.9.1.min.js"></script>
