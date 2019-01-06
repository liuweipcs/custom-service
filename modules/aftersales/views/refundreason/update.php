<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\aftersales\models\RefundReason */

$this->title = 'Update Refund Reason: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Refund Reasons', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
                echo $this->render('_form', [
                    'model' => $model,
                    'reasonTypeList' => $reasonTypeList
                ])
            ?>

        </div>
    </div>
</div>
