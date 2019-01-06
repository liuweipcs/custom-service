<?php
use yii\bootstrap\ActiveForm;
use app\modules\systems\models\Tag;
use \yii\helpers\Url;
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
                <?php echo $form->field($model, 'preg_str');?>
            </div>
            <div class="col-sm-6">
                <?php //echo $form->field($model, 'status')->label();?>
                <?php echo $form->field($model, 'status')->inline()->radioList($model->getStatusList(), ['value' => \app\modules\systems\models\SubjectPreg::TAG_STATUS_VALID]);?>
            </div>
        </div>
      
     </div>
     <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
        <button class="btn btn-default close-button" type="button"><?php echo Yii::t('system', 'Close');?></button>
     </div>
    <?php
        ActiveForm::end();
    ?>
</div>