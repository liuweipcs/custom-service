<?php

use yii\grid\GridView;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\accounts\models\CdiscountAccountOverview;
use kartik\datetime\DateTimePicker;

$this->title = 'CD账号表现';
?>

<style>
    .btn-excel {
        color: #fff;
        background-color: #337ab7;
        border-color: #2e6da4;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="form-group" style="float: left;">
                <select id="years" class="form-control" style="width:150px;">
                    <option value="">全部</option>
                    <option value="<?php echo $years?>"><?php echo $years;?></option>
                </select>
            </div>
            <div class="form-group" style="float: left;margin-left: 20px;">
                <select id="months" class="form-control" style="width:150px;">
                    <?php foreach ($months as $k => $item){?>
                        <option value="<?php echo $k?>"><?php echo $item;?></option>
                    <?php };?>
                </select>
            </div>
            <div class="form-group" style="float: left;margin-left: 20px;">
            <button type="button" class="btn btn-excel">导出数据</button>
            </div>
        </div>

    </div>
    <div class="row">
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'id' => 'accountOverview',
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => "{items}\n{summary}\n{pager}",
                'columns' => [
                    [
                        'class' => 'yii\grid\CheckboxColumn',
                        'name' => 'id',
                        'checkboxOptions' => function ($model) {
                            return ['value' => $model->id];
                        }
                    ],
                    [
                        'label' => '客服人员',
                        'enableSorting' => false,
                        'attribute' => 'user_name',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Select2::widget([
                            'data' => CdiscountAccountOverview::getKefuName(),
                            'options' => ['placeholder' => '请选择...'],
                            'name' => 'CdiscountAccountOverview[user_name]',
                            'value' => $searchModel->user_name,
                        ]),
                    ],
                    [
                        'label' => '账号简称',
                        'enableSorting' => false,
                        'attribute' => 'account_short_name',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Select2::widget([
                            'data' => CdiscountAccountOverview::getAccountList(),
                            'name' => 'CdiscountAccountOverview[account_id]',
                            'value' => $searchModel->account_id,
                        ]),
                    ],
                    [
                        'label' => '30天退款率',
                        'enableSorting' => false,
                        'attribute' => 'refund_rate',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'refund_rate', CdiscountAccountOverview::getReturnRate(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '30天纠纷率',
                        'enableSorting' => false,
                        'attribute' => 'claim_rate',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'claim_rate', CdiscountAccountOverview::getClaimRate(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '60天退款率',
                        'enableSorting' => false,
                        'attribute' => 'refunds_rate',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'refunds_rate', CdiscountAccountOverview::getReturnsRate(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '60天纠纷率',
                        'enableSorting' => false,
                        'attribute' => 'claims_rate',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'claims_rate', CdiscountAccountOverview::getClaimsRate(), ['class' => 'form-control']),
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {

        function addPageSize() {
            $pageSize = " 每页显示 <select name='pageSize' class='form-control' style='display:inline-block;width:80px;'>";
            $pageSize += "<option value='10' <?php if ($params['page_size'] == 10) {echo 'selected';} ?>>10</option>";
            $pageSize += "<option value='20' <?php if ($params['page_size'] == 20) {echo 'selected';} ?>>20</option>";
            $pageSize += "<option value='50' <?php if ($params['page_size'] == 50) {echo 'selected';} ?>>50</option>";
            $pageSize += "<option value='100' <?php if ($params['page_size'] == 100) {echo 'selected';} ?>>100</option>";
            $pageSize += "<option value='200' <?php if ($params['page_size'] == 200) {echo 'selected';} ?>>200</option>";
            $pageSize += "</select> 条记录";
            $("#accountOverview .summary").append($pageSize);
        }
        addPageSize();

        $("#accountOverview").on("change", "select[name='pageSize']", function () {
            var page_size = $(this).val();
            var queryStr = decodeURI(location.search);
            if (queryStr.length == 0) {
                queryStr = "?";
            }
            if (queryStr.indexOf('page_size') == -1) {
                queryStr += "&page_size=" + page_size;
            } else {
                queryStr = queryStr.replace(/(page_size=)([^&]*)/gi, "page_size=" + page_size);
            }

            location.href = "<?php echo Url::toRoute('/accounts/cdiscountaccountoverview/list'); ?>" + queryStr;
            return false;
        });

        //数据导出
        $(".btn-excel").on("click", function () {
            var years = $('#years').val();

            if(years == ''){
                alert('请选择年份');
                return;
            }
            var months = $('#months').val();

            if(months == 0){
                alert('请选择月份');
                return;
            }

            var queryStr = '&years='+years +'&months='+ months;
            location.href = "<?php echo Url::toRoute('/accounts/cdiscountaccountoverview/excel'); ?>?" + queryStr;

            return false;
        });
    });
</script>
