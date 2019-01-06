<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\blacklist\models\BlackList */

$this->title = '新建黑名单';
$this->params['breadcrumbs'][] = ['label' => 'Black Lists', 'url' => ['index']];
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
