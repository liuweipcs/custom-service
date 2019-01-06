<?php

use yii\grid\GridView;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\accounts\models\EbayAccountOverview;
use kartik\datetime\DateTimePicker;

$this->title = 'ebay账号表现';
?>
<style>
    .table {
        border-collapse: collapse;
    }

    .table > tbody > tr > td {
        padding: 0;
        text-align: center;
        vertical-align: middle;
        line-height: 35px;
    }

    .table > thead > tr > td.seller {
        background: #fffb8f;
        text-align: center;
        font-size: 20px;
        font-weight: bold;
    }

    .table > thead > tr > td.buyer {
        background: #b7eb8f;
        text-align: center;
        font-size: 20px;
        font-weight: bold;
    }

    .input-rate {
        width: 65px;
        height: 34px;
        line-height: 34px;
        border: 1px solid #ccc;
        border-radius: 4px;
        display: inline-block;
    }

    .nest-table {
        width: 100%;
        height: 100%;
        border-collapse: collapse;
    }

    .nest-table > tbody > tr > td {
        border-bottom: 1px solid #ddd;
        text-align: center;
        line-height: 35px;
    }

    .nest-table > tbody > tr:last-child > td {
        border-bottom: none;
    }

    a.status {
        color: #666;
        cursor: pointer;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12" style="margin-bottom:10px;">
            <button class="btn btn-primary" id="exportAccountOverview">导出账号表现</button>
            <button class="btn btn-primary" id="exportAccountOverviewDetails">导出账号表现明细表</button>
            <button class="btn btn-primary" id="exportQclist">导出待处理刊登</button>

            <div style="display:inline-block;width:280px;vertical-align:middle;">
                <?php
                echo DateTimePicker::widget([
                    'name' => 'filter_date',
                    'id' => 'filter_date',
                    'options' => ['placeholder' => '按日期查询'],
                    'value' => $params['filter_date'],
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                        'todayHighlight' => true,
                        'todayBtn' => 'linked',
                    ],
                ]);
                ?>
            </div>
            <button class="btn btn-primary" id="condSearch">搜索</button>
            <button class="btn btn-primary" id="list_download">导出SpeedPAK物流管理方案数据</button>
            <button class="btn btn-primary" id="misuse_download">导出买家选择SpeedPAK物流选项数据</button>
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
                        'label' => '账号简称',
                        'enableSorting' => false,
                        'attribute' => 'account_name',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Select2::widget([
                            'data' => EbayAccountOverview::getAccountList(),
                            'name' => 'EbayAccountOverview[account_id]',
                            'value' => $searchModel->account_id,
                        ]),
                    ],
                    [
                        'label' => '站点',
                        'enableSorting' => false,
                        'attribute' => 'program',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'program_status', EbayAccountOverview::getSiteList(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '当前账户等级',
                        'enableSorting' => false,
                        'attribute' => 'current_level',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'current_level_status', EbayAccountOverview::getAccountLevel(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '预测账户等级',
                        'enableSorting' => false,
                        'attribute' => 'projected_level',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '120px',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'projected_level_status', EbayAccountOverview::getAccountLevel(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '不良交易率',
                        'enableSorting' => false,
                        'attribute' => 'bad_trade_rate',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '170px',
                            ],
                        ],
                        'filter' => "<input type='text' name='EbayAccountOverview[bad_trade_rate_status][low]' value='{$searchModel->bad_trade_rate_status['low']}' placeholder='最低分值' class='input-rate'>
                                        ~
                                     <input type='text' name='EbayAccountOverview[bad_trade_rate_status][high]' value='{$searchModel->bad_trade_rate_status['high']}' placeholder='最高分值' class='input-rate'>",
                    ],
                    [
                        'label' => '未解决纠纷率',
                        'enableSorting' => false,
                        'attribute' => 'unresolve_dispute_rate',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '170px',
                            ],
                        ],
                        'filter' => "<input type='text' name='EbayAccountOverview[unresolve_dispute_rate_status][low]' value='{$searchModel->unresolve_dispute_rate_status['low']}' placeholder='最低分值' class='input-rate'>
                                        ~
                                     <input type='text' name='EbayAccountOverview[unresolve_dispute_rate_status][high]' value='{$searchModel->unresolve_dispute_rate_status['high']}' placeholder='最高分值' class='input-rate'>",
                    ],
                    [
                        'label' => '运送延迟率',
                        'enableSorting' => false,
                        'attribute' => 'transport_delay_rate',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                                'min-width' => '170px',
                            ],
                        ],
                        'filter' => "<input type='text' name='EbayAccountOverview[transport_delay_rate_status][low]' value='{$searchModel->transport_delay_rate_status['low']}' placeholder='最低分值' class='input-rate'>
                                        ~
                                     <input type='text' name='EbayAccountOverview[transport_delay_rate_status][high]' value='{$searchModel->transport_delay_rate_status['high']}' placeholder='最高分值' class='input-rate'>",
                    ],
                    [
                        'label' => '综合表现',
                        'enableSorting' => false,
                        'attribute' => 'ltnp',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'ltnp_status', EbayAccountOverview::getLtnpStatus(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '货运',
                        'enableSorting' => false,
                        'attribute' => 'ship',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'ship_status', EbayAccountOverview::getShippingStatus(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '非货运',
                        'enableSorting' => false,
                        'attribute' => 'tci',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'tci_status', EbayAccountOverview::getNonShippingStatus(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '物流标准',
                        'enableSorting' => false,
                        'attribute' => 'shipping_policy',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'shipping_policy_status', EbayAccountOverview::getEdshippingStatus(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '海外仓标准',
                        'enableSorting' => false,
                        'attribute' => 'sd_warehouse',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'sd_warehouse_status', EbayAccountOverview::getWareHouseStatus(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '商业计划追踪',
                        'enableSorting' => false,
                        'attribute' => 'pgc_tracking',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'pgc_tracking_status', EbayAccountOverview::getPgcTrackingStatus(), ['class' => 'form-control']),
                    ],
                    [
                        'label' => '待处理刊登',
                        'enableSorting' => false,
                        'attribute' => 'qclist',
                        'format' => 'raw',
                        'headerOptions' => [
                            'style' => [
                                'text-align' => 'center',
                            ],
                        ],
                        'filter' => Html::activeDropDownList($searchModel, 'qclist_status', EbayAccountOverview::getQcListingStatus(), ['class' => 'form-control']),
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

            location.href = "<?php echo Url::toRoute('/accounts/ebayaccountoverview/list'); ?>" + queryStr;
            return false;
        });

        $("#condSearch").on("click", function () {
            var filter_date = $("#filter_date").val();
            var queryStr = decodeURI(location.search);
            if (queryStr.length == 0) {
                queryStr = "?";
            }
            if (queryStr.indexOf('filter_date') == -1) {
                queryStr += "&filter_date=" + filter_date;
            } else {
                queryStr = queryStr.replace(/(filter_date=)([^&]*)/gi, "filter_date=" + filter_date);
            }
            location.href = "<?php echo Url::toRoute('/accounts/ebayaccountoverview/list'); ?>" + queryStr;
            return false;
        });

        function addStatusColor() {
            $("a.status").each(function () {
                var status = $(this).attr("data-status");
                if (status) {
                    if (status.indexOf("正常") != -1) {
                        $(this).css("color", "#52c41a");
                    } else if (status.indexOf("超标") != -1) {
                        $(this).css("color", "#fa541c");
                    } else if (status.indexOf("警告") != -1) {
                        $(this).css("color", "#faad14");
                    } else if (status.indexOf("限制") != -1) {
                        $(this).css("color", "#eb2f96");
                    } else if (status.indexOf("不考核") != -1) {
                        $(this).css("color", "#1890ff");
                    }

                    if (status.indexOf("最高评级") != -1) {
                        $(this).css("color", "#52c41a");
                    } else if (status.indexOf("低于标准") != -1) {
                        $(this).css("color", "#fa541c");
                    }
                }
            });
        }

        addStatusColor();

        function addTableHeader() {
            $(".table > thead").prepend("<tr><td></td><td></td><td></td><td colspan='5' class='seller'>卖家成绩表</td><td colspan='7' class='buyer'>买家体验报告</td></tr>");
        }

        addTableHeader();

        //显示账号表现详情
        $("a.status").on("click", function () {
            var width = "50%";
            var height = "50%";
            var title = $(this).attr("title");
            var type = $(this).attr("data-type");
            if (!type || type.length == 0) {
                return false;
            }
            var accountId = $(this).attr("data-accountid");
            if (!accountId || accountId.length == 0) {
                return false;
            }
            //单独设置买家体验报告的货运弹窗高度
            if (type == "ship") {
                height = "80%";
            }
            //单独设置物流标准弹窗高度
            if (type == "eds_shipping_policy") {
                height = "85%";
            }
            var id = $(this).attr("data-id");
            if (!id) {
                id = 0;
            }
            layer.open({
                type: 2,
                title: title,
                content: "<?php echo Url::toRoute(['/accounts/ebayaccountoverview/overviewdetails']) ?>" + "?type=" + type + "&account_id=" + accountId + "&id=" + id,
                area: [width, height]
            });
            return false;
        });

        //导出账号表现
        $("#exportAccountOverview").on("click", function () {
            var queryStr = decodeURI(location.search);
            var checkBox = $("input[name^='id']:checked");
            if (queryStr.length == 0) {
                queryStr = "?";
            }
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/accounts/ebayaccountoverview/exportaccountoverview'); ?>" + queryStr;
            return false;
        });

        //导出账号表现明细表
        $("#exportAccountOverviewDetails").on("click", function () {
            var queryStr = decodeURI(location.search);
            var checkBox = $("input[name^='id']:checked");
            if (queryStr.length == 0) {
                queryStr = "?";
            }
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/accounts/ebayaccountoverview/exportaccountoverviewdetails'); ?>" + queryStr;
            return false;
        });

        //导出待处理刊登
        $("#exportQclist").on("click", function () {
            var queryStr = decodeURI(location.search);
            var checkBox = $("input[name^='id']:checked");
            if (queryStr.length == 0) {
                queryStr = "?";
            }
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/accounts/ebayaccountoverview/exportqclist'); ?>" + queryStr;
            return false;
        });
        //导出SpeedPAK物流管理方案数据
        $('#list_download').on("click",function(){
             var queryStr = decodeURI(location.search);
            var checkBox = $("input[name^='id']:checked");
            if (queryStr.length == 0) {
                queryStr = "?";
            }
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/accounts/ebayaccountoverview/listdownload'); ?>" + queryStr;
            return false;
        });
        //导出买家选择SpeedPAK物流选项数据
        $('#misuse_download').on("click",function(){
             var queryStr = decodeURI(location.search);
            var checkBox = $("input[name^='id']:checked");
            if (queryStr.length == 0) {
                queryStr = "?";
            }
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/accounts/ebayaccountoverview/misusedownload'); ?>" + queryStr;
            return false;
        });
        
        
        
    });
</script>