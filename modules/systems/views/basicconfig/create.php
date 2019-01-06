<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\systems\models\BasicConfig */

$this->title = '新增基础数据';
$this->params['breadcrumbs'][] = ['label' => 'Basic Configs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1><?= Html::encode($this->title) ?></h1>
            <p>
                <?php
                Html::a('删除', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Are you sure you want to delete this item?',
                        'method' => 'post',
                    ],
                ])
                ?>
                <?= Html::a('返回主列表', ['index', 'id' => $model->id], ['class' => 'btn btn-default']) ?>
            </p>
            <?=
            $this->render('_form', [
                'model' => $model,
            ])
            ?>
        </div>
    </div>
</div>
