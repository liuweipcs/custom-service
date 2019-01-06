<?php
use yii\grid\GridView;
use kartik\select2\Select2;
use app\modules\accounts\models\Aliexpressaccountservicescoreinfo;


/* @var $this yii\web\View */
/* @var $searchModel app\modules\orders\models\OrderReplyQuerySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '速卖通每月服务分';
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    [
                        'attribute' => '',
                        'format' => ['raw'],
                        'label' => "全/反选",
                        'headerOptions' => ['width' => '50','style'=>'cursor:pointer'],
                        'contentOptions' => ['align'=>'center'],
                        'header'=>"<b title='全选' id='all-check'>全</b>/<b title='反选' id='reverse-check'>反</b>",
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
                            'name' => 'Aliexpressaccountlevelinfo[account_id]',
                            'data' =>$accountList,
                            'options' => ['placeholder' => '请选择...'],
                            'initValueText' => $model->account_id,
                        ])
                    ],
                    [
                        'filter' => '<input type="text" name="Aliexpressaccountlevelinfo[score]" value="">--
                            <input type="text" name="Aliexpressaccountlevelinfo[scoremax]" value="">',
                        'label'=>'上月每日服务分平均值',
                        'attribute'=>'avg_score',
                        'format' => 'raw',
                        'contentOptions' => ['style'=>'color:#FF9900'],

                    ],
                    [
                        'filter' =>['2'=>'不及格','3'=>'及格','4'=>'良好','5'=>'优秀'],
                        'label'=>'当月服务等级',
                        'attribute'=>'level',
                        'format' => 'raw',
                        'value' => function($model){
                            $level = ['2'=>'<b style="color: #FF0000">不及格</b>','3'=>'<b style="color: #FF9900">及格</b>','4'=>'<b style="color: #66CC33">良好</b>','5'=>'<b style="color: #6699FF">优秀</b>'];
                            return $level[$model->level];
                        }
                    ],
                    [

                        'label'=>'考核周期',
                        'attribute'=>'appraise_period',
                        'value' => 'appraise_period',
                    ],
                    [

                        'label'=>'上月考核订单量',
                        'attribute'=>'check_m_order_count',
                        'value' => 'check_m_order_count',
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
            ]); ?>
        </div>
    </div>
</div>
