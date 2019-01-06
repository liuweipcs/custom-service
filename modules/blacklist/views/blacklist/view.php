<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\modules\accounts\models\Platform;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\modules\blacklist\models\BlackList */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Black Lists', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            if (Yii::$app->getSession()->hasFlash('success')) {
                echo Alert::widget([
                    'options' => [
                        'class' => 'alert-success', //这里是提示框的class  
                    ],
                    'body' => Yii::$app->getSession()->getFlash('success'), //消息体  
                ]);
            }
            ?>
            <p>
                <?php echo Html::a('修改', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                <?php echo Html::a('返回列表', ['index'], ['class' => 'btn btn-default']) ?>
                <?php
//                echo Html::a('Delete', ['delete', 'id' => $model->id], [
//                    'class' => 'btn btn-danger',
//                    'data' => [
//                        'confirm' => 'Are you sure you want to delete this item?',
//                        'method' => 'post',
//                    ],
//                ])
                ?>
            </p>

            <?php
            $platfromList = Platform::getAllCountList();
            echo DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'label' => '编号',
                        'value' => $model->id,
                    ],
                    [
                        'label' => '平台',
                        'value' => $platfromList[$model->platfrom_id]
                    ],
                    [
                        'label' => '黑名单用户',
                        'value' => $model->username,
                    ]
                ]
            ])
            ?>

        </div>
    </div>
</div>
