<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\systems\models\BasicConfig */

$this->title = 'Update Basic Config: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Basic Configs', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <p>
                <?php Html::a('修改', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
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
