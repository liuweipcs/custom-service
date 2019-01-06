<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Aliexpressaccountdisputeproductlist;
use app\modules\systems\models\Rule;
use app\modules\mails\models\MailTemplate;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\orders\models\OrderReplyQuerySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '速卖通低服务分商品';
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
                            $platformList = Aliexpressaccountdisputeproductlist::getAccountList(1);
                            return $platformList[$model->account_id];
                        },
                        'filter' => Select2::widget([
                            'name' => 'Aliexpressaccountdisputeproductlist[account_id]',
                            'data' => Aliexpressaccountdisputeproductlist::getAccountList(1),
                            'options' => ['placeholder' => '请选择...'],
                            'initValueText' => $model->account_id,
                        ])
                    ],
                    [
                        'label' => '商品名称',
                        'attribute' => 'product_name',
                        'value' => 'product_name',
                    ],
                    [
                        'label' => '商品ID',
                        'attribute' => 'product_id',
                        'value' => 'product_id',
                    ],
                    [
                        'filter' => '<input type="text" name="Aliexpressaccountdisputeproductlist[score]" size="4" value="">--
                            <input type="text" name="Aliexpressaccountdisputeproductlist[scoremax]" size="4" value="">',
                        'label' => '得分',
                        'attribute' => 'score',
                        'format' => 'raw',
                        'contentOptions' => ['style' => 'color:red'],
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
