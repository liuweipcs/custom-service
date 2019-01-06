<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\systems\models\BasicConfig;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\modules\aftersales\models\RefundReason */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="refund-reason-form">

    <?php $form = ActiveForm::begin(); ?>
    <?php
    echo $form->field($model, 'department_id', ['options' => ['class' => 'col-lg-4']])->widget(Select2::classname(), [
        'data' => BasicConfig::getParentList(52),
        'options' => ['prompt' => '请选择 ...'],
    ]);
    ?>

    <?php
    echo $form->field($model, 'reason_type_id', ['options' => ['class' => 'col-lg-4']])->widget(Select2::classname(), [
        'data' => $reasonTypeList,
        'options' => ['prompt' => '请选择 ...'],
    ]);
    ?>

    <?php
    echo $form->field($model, 'formula_id', ['options' => ['class' => 'col-lg-4']])->widget(Select2::classname(), [
        'data' => BasicConfig::getParentList(108),
        'options' => ['prompt' => '请选择 ...'],
    ]);
    ?>
    
    <?php
    echo $form->field($model, 'refund_cost_id', ['options' => ['class' => 'col-lg-4']])->widget(Select2::classname(), [
        'data' => BasicConfig::getParentList(122),
        'options' => ['prompt' => '请选择 ...'],
    ]);
    ?>
    
     <?php
    echo $form->field($model, 'resend_cost_id', ['options' => ['class' => 'col-lg-4']])->widget(Select2::classname(), [
        'data' => BasicConfig::getParentList(123),
        'options' => ['prompt' => '请选择 ...'],
    ]);
    ?>

    <?php echo $form->field($model, 'remark', ['options' => ['class' => 'col-lg-12']])->textarea(['rows' => 6]); ?>

    <div class="form-group col-lg-12">
        <?php echo Html::submitButton($model->isNewRecord ? '新增' : '修改', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']); ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
    $(document).on("change", "#refundreason-department_id", function () {
        var id = $(this).val();
        if (id) {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['getnetleveldata']); ?>',
                data: {'id': id},
                success: function (data) {
                    var html = "";
                    if (data) {
                        $.each(data, function (n, value) {
                            html += '<option value=' + n + '>' + value + '</option>';
                        });
                    } else {
                        html = '<option value="">---请选择---</option>';
                    }
                    $("#refundreason-reason_type_id").empty();
                    $("#refundreason-reason_type_id").append(html);
                }
            });
        } else {
            $("#refundreason-reason_type_id").empty();
            $("#refundreason-reason_type_id").append(html);
        }
    });
</script>