<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Rule;
use app\modules\mails\models\MailTemplate;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\orders\models\OrderReplyQuerySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '自动发信列表';
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'label' => '平台',
                        'attribute' => 'platform_code',
                        'filter' => Select2::widget([
                            'name' => 'OrderReplyQuerySearch[platform_code]',
                            'data' => Platform::getPlatformAsArray(),
                            'options' => ['placeholder' => '请选择平台名称...'],
                            'initValueText' => $model->platform_code,
                        ]),
                        'value' => function ($model) {
                            $platformList = Platform::getPlatformAsArray();
                            return $platformList[$model->platform_code];
                        },
                    ],
                    [
                        'label' => '规则',
                        'attribute' => 'rule_id',
                        'filter' => Select2::widget([
                            'name' => 'OrderReplyQuerySearch[rule_id]',
                            'data' => Rule::getRuleList(),
                            'options' => ['placeholder' => '请选择规则名称...'],
                            'initValueText' => $model->rule_id,
                        ]),
                        'value' => function ($model) {
                            return Rule::getRuleList($model->rule_id);
                        }
                    ],
                    [
                        'label' => '模板',
                        'attribute' => 'template_id',
                        'filter' => Select2::widget([
                            'name' => 'OrderReplyQuerySearch[template_id]',
                            'data' => MailTemplate::getTemplateName(),
                            'options' => ['placeholder' => '请选择模板名称...'],
                            'initValueText' => $model->template_id,
                        ]),
                        'value' => function ($model) {
                            return MailTemplate::getTemplateName($model->template_id);
                        }
                    ],
                    [
                        'label' => '订单ID',
                        'attribute' => 'order_id',
                        'value' => 'order_id'
                    ],
                    [
                        'filter' => [0 => '未处理', 1 => '已处理', -1 => '处理失败', 2 => '无需处理', 4 => '暂停发送'],
                        'label' => '是否发送',
                        'attribute' => 'is_send',
                        'format' => 'html',
                        'value' => function ($model) {
                            $status = [0 => '未处理', 1 => '已处理', -1 => '处理失败', 2 => '无需处理', 4 => '暂停发送'];
                            return $status[$model->is_send];
                        }
                    ],
                    [
                        'label' => '付款时间',
                        'attribute' => 'order_pay_time',
                        'value' => 'order_pay_time'
                    ],
                    [
                        'label' => '发货时间',
                        'attribute' => 'order_ship_time',
                        'value' => 'order_ship_time'
                    ],
                    [
                        'label' => '计划发送时间',
                        'attribute' => 'reply_date',
                        'value' => 'reply_date'
                    ],
                    [
                        'label' => '错误信息',
                        'attribute' => 'error_info',
                        'value' => 'error_info'
                    ],
//            ['class' => 'yii\grid\ActionColumn'],
//            ['class' => 'yii\grid\ActionColumn','header' => '操作',],
//            [
//                'label'=>'更多操作',
//                'format'=>'raw',
//                'value' => function($data){
//                    $url = "http://www.baidu.com";
//                    return Html::a('添加权限组', $url, ['title' => '审核']); 
//                }
//            ] 
                ],
            ]);
            ?>
        </div>
    </div>
</div>
