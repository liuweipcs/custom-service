<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\aftersales\models\RefundReason */

$this->title = 'Create Refund Reason';
$this->params['breadcrumbs'][] = ['label' => 'Refund Reasons', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
                echo $this->render('_form', [
                    'model' => $model,
                    'departmentList' => $departmentList,
                    'reasonTypeList' => [],
                    'formulaList' => $formulaList
                ])
            ?>

        </div>
    </div>
</div>