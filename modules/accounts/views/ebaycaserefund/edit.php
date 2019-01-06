<?php
use yii\bootstrap\ActiveForm;
use \app\modules\accounts\models\EbayCaseRefund;
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
                <div class="form-group field-ebaycaserefund-account_id">
                    <label class="control-label col-sm-3" for="ebaycaserefund-account_id">帐号</label>
                    <div class="col-sm-6">
                        <p class="form-control" disabled="disabled"><?php echo $accountInfo->account_name;?></p>
                        <div class="help-block help-block-error "></div>
                    </div>

                </div>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'is_refund')->inline()->radioList(EbayCaseRefund::getStatusList(), ['value' => $model->is_refund]);?>
            </div>
        </div>
        <div class="row">

            <div class="col-sm-6">
                <?php echo $form->field($model, 'currency',['inputOptions'=>['disabled'=>'disabled']]);?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'claim_amount');?>
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