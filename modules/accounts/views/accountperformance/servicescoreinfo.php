<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Aliexpressaccountservicescoreinfo;
use app\modules\systems\models\Rule;
use app\modules\mails\models\MailTemplate;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\orders\models\OrderReplyQuerySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '速卖通每月服务分';
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
                    [
                        'attribute' => '',
                        'format' => ['raw'],
                        'label' => "全/反选",
                        'headerOptions' => ['width' => '50', 'style' => 'cursor:pointer'],
                        'contentOptions' => ['align' => 'center'],
                        'header' => "<b title='全选' id='all-check'>全</b>/<b title='反选' id='reverse-check'>反</b>",
                        'value' => function ($model) {
                            return "<input type='checkbox' class='i-checks' value={$model['id']}>";
                        },
                    ],
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'attribute' => '店铺名称',
                        'content' => function ($model) {
                            $platformList = Aliexpressaccountservicescoreinfo::getAccountList(1);
                            return $platformList[$model->account_id];
                        },
                        'filter' => Select2::widget([
                            'name' => 'Aliexpressaccountservicescoreinfo[account_id]',
                            'data' => Aliexpressaccountservicescoreinfo::getAccountList(1),
                            'options' => ['placeholder' => '请选择...'],
                            'initValueText' => $model->account_id,
                        ])
                    ],
                    [
                        'filter' => '<input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[total_score]" value="">--
                            <input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[total_scoremax]" value="">',
                        'label' => '今日服务分',
                        'attribute' => 'total_score',
                        'format' => 'raw',
                        'contentOptions' => function($model) {
                            if ($model->total_score < 0) {
                                return ['style' => 'color: #FF0000'];
                            }
                            if ($model->total_score > $model->average_total_score) {
                                return ['style' => 'color: #66CC99'];
                            } else {
                                return ['style' => 'color: #FF9900'];
                            }
                        },
                    ],
                    [
                        'filter' => '<input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[buyn]" value="">--
                            <input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[buynmax]" value="">',
                        'label' => '成交不卖率%',
                        'attribute' => 'buy_not_sel_rate',
                        'format' => 'raw',
                        'contentOptions' => function($model) {
                            if ($model->buy_not_sel_rate < 0) {
                                return ['style' => 'color: #FF0000'];
                            }
                            if ($model->buy_not_sel_rate > $model->average_buy_not_sel_rate) {
                                return ['style' => 'color: #66CC99'];
                            } else {
                                return ['style' => 'color: #FF9900'];
                            }
                        },
                    ],
                    [
                        'filter' => '<input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[nrd]" value="">--
                            <input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[nrdmax]" value="">',
                        'label' => '未收到货物纠纷提起率%',
                        'attribute' => 'nr_disclaimer_issue_rate',
                        'format' => 'raw',
                        'contentOptions' => function($model) {
                            if ($model->nr_disclaimer_issue_rate < 0) {
                                return ['style' => 'color: #FF0000'];
                            }
                            if ($model->nr_disclaimer_issue_rate > $model->average_nr_disclaimer_issue_rate) {
                                return ['style' => 'color: #66CC99'];
                            } else {
                                return ['style' => 'color: #FF9900'];
                            }
                        },
                    ],
                    [
                        'filter' => '<input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[snad_d]" value="">--
                            <input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[snad_dmax]" value="">',
                        'label' => '货不对版纠纷提起率%',
                        'attribute' => 'snad_disclaimer_issue_rate',
                        'format' => 'raw',
                        'contentOptions' => function($model) {
                            if ($model->snad_disclaimer_issue_rate < 0) {
                                return ['style' => 'color: #FF0000'];
                            }
                            if ($model->snad_disclaimer_issue_rate > $model->average_snad_disclaimer_issue_rate) {
                                return ['style' => 'color: #66CC99'];
                            } else {
                                return ['style' => 'color: #FF9900'];
                            }
                        },
                    ],
                    [
                        'filter' => '<input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[dsr_p]" value="">--
                            <input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[dsr_pmax]" value="">',
                        'label' => 'DSR商品描述得分',
                        'attribute' => 'dsr_prod_score',
                        'format' => 'raw',
                        'contentOptions' => function($model) {
                            if ($model->dsr_prod_score < 0) {
                                return ['style' => 'color: #FF0000'];
                            }
                            if ($model->dsr_prod_score > $model->average_dsr_prod_score) {
                                return ['style' => 'color: #66CC99'];
                            } else {
                                return ['style' => 'color: #FF9900'];
                            }
                        },
                    ],
                    [
                        'filter' => '<input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[dsr_c]" value="">--
                            <input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[dsr_cmax]" value="">',
                        'label' => 'DSR卖家服务得分',
                        'attribute' => 'dsr_communicate_score',
                        'format' => 'raw',
                        'contentOptions' => function($model) {
                            if ($model->dsr_communicate_score < 0) {
                                return ['style' => 'color: #FF0000'];
                            }
                            if ($model->dsr_communicate_score > $model->average_dsr_communicate_score) {
                                return ['style' => 'color: #66CC99'];
                            } else {
                                return ['style' => 'color: #FF9900'];
                            }
                        },
                    ],
                    [
                        'filter' => '<input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[dsr_l]" value="">--
                            <input type="text" style="width:30%; " name="Aliexpressaccountservicescoreinfo[dsr_lmax]" value="">',
                        'label' => 'DSR物流得分',
                        'attribute' => 'dsr_logis_score',
                        'format' => 'raw',
                        'contentOptions' => function($model) {
                            if ($model->dsr_logis_score < 0) {
                                return ['style' => 'color: #FF0000'];
                            }
                            if ($model->dsr_logis_score > $model->average_dsr_logis_score) {
                                return ['style' => 'color: #66CC99'];
                            } else {
                                return ['style' => 'color: #FF9900'];
                            }
                        },
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
