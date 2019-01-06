<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\bootstrap\Alert;
use app\modules\systems\models\BasicConfig;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\systems\models\BasicConfigSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Basic Configs';
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1>基础数据管理</h1>
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
            <?php  //echo $this->render('_search', ['model' => $searchModel]); ?>
            
            <p>
                <?php
                    if(\app\components\GridView::_aclcheck(Yii::$app->user->identity->id,'/systems/basicconfig/create'))
                        echo Html::a('新增数据', ['create'], ['class' => 'btn btn-success']) ?>
            </p>
            <?php
            $op = '{view}';
            if(\app\components\GridView::_aclcheck(Yii::$app->user->identity->id,'/systems/basicconfig/update'))
                $op .= ' {update}';

            echo GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
//                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'filter' => BasicConfig::getParentList(),
                        'label' => '类型',
                        'attribute' => 'parent_id',
                        'format' => 'html',
                        'value' => function ($model) {
                            $parentList = BasicConfig::getParentList();
                            return isset($parentList[$model->parent_id]) ? $parentList[$model->parent_id]:'';
                        }
                    ],
                    'level',
                    'name',
                    [
                        'filter' => [1 => '禁用',2 => '启用'],
                        'label' => '类型',
                        'attribute' => 'status',
                        'format' => 'html',
                        'value' => function ($model) {
                            $status = [1 => '禁用',2 => '启用'];
                            return $status[$model->status];
                        }
                    ],
                    'text:ntext',
                    'create_name',
                    'create_time',
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => '操作',
                        'headerOptions' => ['width' => '60'],
                        'template' => $op,
                        'buttons' => [
//                            'sync' => function ($url, $model, $key) {
//                                if ($model->platfrom_id == 31) {
//                                    return '<a href="/blacklist/blacklist/autosync" title="同步GBC数据" aria-label="同步GBC数据" data-pjax="0"><span class="glyphicon glyphicon-transfer"></span></a>';
//                                }
//                            },
                        ],
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>
