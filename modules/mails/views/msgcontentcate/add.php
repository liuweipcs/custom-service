<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use app\modules\mails\models\MailTemplateCategory;

?>
<div class="popup-wrapper">
    <?php
    $form = ActiveForm::begin([
        'id' => 'addMailTemplateCategory',
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
                <?php echo $form->field($model, 'platform_code')->dropdownList($platformList, ['onchange' => 'getCategoryList(this)']); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'parent_id')->dropdownList([], ['id' => 'category_id']); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'category_name'); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'category_code'); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'category_description')->textarea(['rows' => 4, 'cols' => 42]); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'sort_order')->input('text', ['value' => 0]); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'status')->inline()->radioList([0 => '否', 1 => '是'], ['value' => 1]); ?>
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
    function getCategoryList(obj) {
        var platform_code = $(obj).val();
        $.post("<?php echo Url::toRoute('/mails/msgcontent/getlist'); ?>", {
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