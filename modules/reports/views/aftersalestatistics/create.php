<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\reports\models\AfterSaleStatistics */

$this->title = 'Create After Sale Statistics';
$this->params['breadcrumbs'][] = ['label' => 'After Sale Statistics', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1><?= Html::encode($this->title) ?></h1>

            <?=
            $this->render('_form', [
                'model' => $model,
            ])
            ?>

        </div>
    </div>
</div>
