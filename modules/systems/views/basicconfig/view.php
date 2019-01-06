<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\modules\systems\models\BasicConfig */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Basic Configs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1><?= Html::encode($this->title) ?></h1>
            <?php
            if (Yii::$app->getSession()->hasFlash('success')) {
                echo Alert::widget([
                    'options' => [
                        'class' => 'alert-success', //这里是提示框的class  
                    ],
                    'body' => Yii::$app->getSession()->getFlash('success'), //消息体  
                ]);
            }
            if (Yii::$app->getSession()->hasFlash('error')) {
                echo Alert::widget([
                    'options' => [
                        'class' => 'alert-warning', //这里是提示框的class  
                    ],
                    'body' => Yii::$app->getSession()->getFlash('error'), //消息体  
                ]);
            }
            ?>
            <p>
                <?= Html::a('修改', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
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
            DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'parent_id',
                    'name',
                    'text:ntext',
                    'create_time',
                    'create_id',
                    'create_name',
                ],
            ])
            ?>
        </div>
    </div>
</div>
