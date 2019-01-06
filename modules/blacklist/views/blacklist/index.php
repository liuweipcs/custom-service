<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\modules\accounts\models\Platform;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\blacklist\models\BlackListSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '黑名单列表';
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">

            <h1><?= Html::encode($this->title) ?></h1>

            <p>
            <?php
                if(\app\components\GridView::_aclcheck(Yii::$app->user->identity->id,'/blacklist/blacklist/create'))
                    echo Html::a('新增', ['create'], ['class' => 'btn btn-success'])
            ?>
            </p>
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
            <?php

            $op = '{view}';
            if(\app\components\GridView::_aclcheck(Yii::$app->user->identity->id,'/blacklist/blacklist/update'))
                $op .= ' {update}';
            if(\app\components\GridView::_aclcheck(Yii::$app->user->identity->id,'/blacklist/blacklist/autosync'))
                $op .= ' {sync}';

            echo GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'filter' => Platform::getAllCountList(),
                        'label' => '平台',
                        'attribute' => 'platfrom_id',
                        'format' => 'html',
                        'value' => function ($model) {
                            $platformList = Platform::getAllCountList();
                            return !empty($model->platfrom_id) ? $platformList[$model->platfrom_id] : '<span class="not-set">(未设置)</span>';
                        }
                    ],
                    [
                        'label' => 'GBC黑名单信息',
                        'attribute' => 'username',
                        'value' => 'username',
                        'headerOptions' => ['width' => '40%']
                    ],
                    [
                        'label' => '黑名单信息(自己平台)',
                        'attribute' => 'myself_username',
                        'value' => 'myself_username',
                        'headerOptions' => ['width' => '20%']
                    ],        
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => '操作',
                        'headerOptions' => ['width' => '80'],
                        'template' => $op,
                        'buttons' => [
                            'sync' => function ($url, $model, $key) {
                                if ($model->platfrom_id == 31) {
                                    return '<a href="/blacklist/blacklist/autosync" title="同步GBC数据" aria-label="同步GBC数据" data-pjax="0"><span class="glyphicon glyphicon-transfer"></span></a>';
                                }
                            },
                        ],
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>
