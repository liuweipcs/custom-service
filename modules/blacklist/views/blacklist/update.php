<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\blacklist\models\BlackList */

$this->title = '修改黑名单信息';
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
