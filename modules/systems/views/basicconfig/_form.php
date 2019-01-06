<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\systems\models\BasicConfig;

/* @var $this yii\web\View */
/* @var $model app\modules\systems\models\BasicConfig */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="basic-config-form">

    <?php
    $form = ActiveForm::begin([
                'fieldConfig' => ['template' => "{label}{input}{error}"],
                'options' => ['class' => 'form-horizontal', 'enctype' => "multipart/form-data"]
    ]);
    ?>

    <?php //echo $form->field($model, 'parent_id')->dropDownList(BasicConfig::getParentList(), ['prompt'=>'请选择','style'=>'width:auto;']);?>


    <?php
    echo $form->field($model, 'parent_id')->dropDownList(BasicConfig::getParentList(), [
                    'prompt' => '--请选分类--',
                    'onchange' => '
                    $.post("' . yii::$app->urlManager->createUrl('/systems/basicconfig/getnextlevel') . '?parentId="+$(this).val(),function(data){
                        if(data){
                            $("select#basicconfig-level_two").html(data);
                            $(".form-group.field-basicconfig-level_two").show();
                        }else{
                            $("select#basicconfig-level_two").html("");
                            $(".form-group.field-basicconfig-level_two").hide();    
                        }
                    });',
                    'style' => 'width:auto;'
    ]);
    ?>
    <?= $form->field($model, 'level_two')->dropDownList([], ['prompt' => '--请选择区--', 'style' => 'width:auto;']) ?>
    <?php echo $form->field($model, 'status')->radioList(['2' => '启用', '1' => '禁用']); ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'text')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? '新增' : '保存修改', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<script>
    $(document).ready(function () {
        $(".form-group.field-basicconfig-level_two").hide();
    });
</script>