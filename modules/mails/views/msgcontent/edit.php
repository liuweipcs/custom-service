<?php

use yii\bootstrap\ActiveForm;
use app\modules\mails\models\MailTemplate;
use yii\helpers\Url;

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
            <div class="col-sm-12" id="codeofplat">
                <?php echo $form->field($model, 'platform_code')->dropdownList($platformList, ['onchange' => 'getCategoryList(this)']); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12" id="codeofplat">
                <?php echo $form->field($model, 'template_type')->dropdownList(MailTemplate::gettemplatetypeList()); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'category_id')->dropdownList($categoryList, ['id' => 'category_id', 'encodeSpaces' => true]); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'private')->dropdownList(MailTemplate::getTemplatePrivate()); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'template_name'); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'template_title'); ?>
            </div>
        </div>

        <div class="row">

            <div class="col-sm-12">
                <?php echo $form->field($model, 'sort_order'); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'template_content')->textarea(['rows' => 4, 'cols' => 42]); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'template_description')->textarea(['rows' => 4, 'cols' => 42]); ?>
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
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/jquery-1.9.1.min.js"></script>
<script type="text/javascript">
    function getCategoryList(obj) {
        var platform_code = $(obj).val();
        $.post("<?php Url::toRoute('/mails/msgcontent/getlist') ?>", {
            "platform_code": platform_code
        }, function (data) {
            if (data["code"] == 1) {
                var data = data["data"];
                var html = "";
                for (var ix in data) {
                    var name = data[ix]["name"];
                    name = name ? name.replace(/\s/g, '&nbsp;') : "";
                    html += '<option value="' + data[ix]["id"] + '">' + name + '</option>';
                }
                $('#category_id').html(html);
            } else {
                $('#category_id').html("<option value='0'>请选择</option>");
            }
        }, "json");
    }
</script>