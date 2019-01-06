<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\reports\models\AfterSaleStatisticsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            $form = ActiveForm::begin([
                        'action' => ['index'],
                        'method' => 'get',
            ]);
            ?>

            <?= $form->field($model, 'id') ?>

            <?= $form->field($model, 'after_sale_id') ?>

            <?= $form->field($model, 'platform_code') ?>

            <?= $form->field($model, 'department_id') ?>

            <?= $form->field($model, 'reason_type_id') ?>

            <?php // echo $form->field($model, 'formula_id') ?>

            <?php // echo $form->field($model, 'account_id') ?>

            <?php // echo $form->field($model, 'account_name') ?>

            <?php // echo $form->field($model, 'type') ?>

            <?php // echo $form->field($model, 'refund_amount') ?>

            <?php // echo $form->field($model, 'refund_amount_rmb') ?>

            <?php // echo $form->field($model, 'subtotal') ?>

            <?php // echo $form->field($model, 'subtotal_rmb') ?>

            <?php // echo $form->field($model, 'currency') ?>

            <?php // echo $form->field($model, 'exchange_rate') ?>

            <?php // echo $form->field($model, 'create_by') ?>

            <?php // echo $form->field($model, 'create_time') ?>

            <?php // echo $form->field($model, 'status') ?>

            <?php // echo $form->field($model, 'pro_cost_rmb') ?>

                <?php // echo $form->field($model, 'add_time')  ?>

            <div class="form-group">
<?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
            <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
            </div>

<?php ActiveForm::end(); ?>

        </div>
    </div>

</div>