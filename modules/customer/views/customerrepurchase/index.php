<?php

use yii\helpers\Url;
use kartik\select2\Select2;
use app\modules\accounts\models\UserAccount;
use app\modules\systems\models\BasicConfig;
use kartik\datetime\DateTimePicker;

?>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/highcharts.js"></script>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/exporting.js"></script>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/series-label.js"></script>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/oldie.js"></script>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/oldie.js"></script>
<style>
    .panel-row {
        height: 52px;
        background: #f5f5f5;
        padding: 10px;
        position: relative;
        border-left: 5px solid #029be5;

    .countChart {
        display: none;
        border: 1px solid #e1e1e1;
        border-radius: 5px;
        padding: 10px;
        margin-top: 15px;
    }
</style>
<div id="page-wrapper">
    <div class="panel-row">
    <div class ="col-lg-10">
            <div class="col-lg-2">
                <?php echo Select2::widget([
                    'id' => 'platform_code',
                    'name' => 'platform_code',
                    'value' => $platform_code,
                    'data' => $platformList,
                    'options' => [
                        'placeholder' => '平台'
                    ],
                ]);
                ?>
            </div>
            <div class="col-lg-2">
                <?php echo Select2::widget([
                    'id' => 'type',
                    'name' => 'type',
                    'value' => $typeVal,
                    'data' => $typeArr,
                    'options' => [
                        'placeholder' => '统计类型/账号...',
                        'multiple' => true,
                    ]
                ]);
                ?>
            </div>
        <div id="siteAccount" class="col-lg-2" style="display: <?php echo ($platform_code == 'AMAZON') ? 'block' : 'none'; ?>">
            <?php
            echo Select2::widget([
                'id' => 'account_site',
                'name' => 'account_site',
                'data' => $accountSiteArr,
                'value' => $accountSiteVal,
                'options' => [
                    'placeholder' => '账号/站点',
                    'multiple' => true,
                    'display' => 'none'
                ],
            ]);
            ?>
        </div>
        <div class="col-lg-3">
            <?php
            echo DateTimePicker::widget([
                'name' => 'start_time',
                'id' => 'start_time',
                'options' => ['placeholder' => '按条件查询'],
                'value' => $start_time,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'yyyy-mm',
                    'todayHighlight' => true,
                    'todayBtn' => 'linked',
                ],
            ]); ?>
        </div>
        <div class="col-lg-3">
            <?php
            echo DateTimePicker::widget([
                'name' => 'end_time',
                'id' => 'end_time',
                'options' => ['placeholder' => '按条件查询'],
                'value' => $end_time,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'yyyy-mm',
                    'todayHighlight' => true,
                    'todayBtn' => 'linked',
                ],
            ]); ?>
        </div>
    </div>
    </div>

    <div class="countChart" style="margin-top: 40px;border: 1px solid #ddd;float: left;width: 100%;">
        <form class="form-inline">
            <div class="form-group" style="margin-top: 10px; margin-left: 10px;">
                <select id="countDataType" class="form-control" style="width:180px;">
                    <option value="0">全部</option>
                    <option value="1">平台客户回购次数</option>
                    <option value="2">平台客户回购率</option>
                    <option value="3">账号客户回购次数</option>
                    <option value="4">账号客户回购率</option>
                </select>
            </div>
            <div class="form-group" style="margin-top: 10px;margin-left: 10px;">
                <select id="cycle_type" class="form-control" style="width:180px;">
                    <option value="5">季度</option>
                    <option value="6">月</option>
                    <option value="7">年</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary">搜索</button>
            <span id="count_mumber">

            </span>
        </form>
        <div id="countChartCon">

        </div>
    </div>
</div>
<script>

    var option = {
        title: {
            text: ''
        },
        xAxis: {
            categories: []
        },
        yAxis: {
            title: {
                text: ''
            }
        },
        tooltip: {
            shared: true,
            crosshairs: true
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'middle'
        },
        plotOptions: {
            line: {
                dataLabels: {
                    // 开启数据标签
                    enabled: true
                },
                // 关闭鼠标跟踪，对应的提示框、点击事件会失效
                enableMouseTracking: true
            },
            series: {
                label: {
                    connectorAllowed: false
                }
            }
        },
        series: [],
        responsive: {
            rules: [{
                condition: {
                    maxWidth: 960
                },
                chartOptions: {
                    legend: {
                        layout: 'horizontal',
                        align: 'center',
                        verticalAlign: 'bottom'
                    }
                }
            }]
        }
    };

    //搜索
    $(".btn-primary").on("click", function () {

        var start_time = $('#start_time').val();
        var end_time = $('#end_time').val();
        var platform_code = $('#platform_code').val();
        if(platform_code == 'AMAZON'){
            var type = $('#type').val();
            var accountSiteVal = $('#account_site').val();
        }else{
            var type = $('#type').val();
            var accountSiteVal = '';
        }

        var plat_type = $('#countDataType').val();
        if(plat_type == 0){
            alert('请选择统计类型');
            return false;
        }
        var cycle_type = $("#cycle_type").val();
        if(!cycle_type){
            alert('请选择统计周期');
            return false;
        }
        if (!start_time || !end_time) {
            alert('开始/结束时间都要填写');
            return false;
        }
        if(start_time >= end_time){
            alert('开始时间必须小于结束时间');
            return false;
        }

        flushCountChart(start_time, end_time, platform_code, plat_type, cycle_type, type, accountSiteVal);

    });

    function flushCountChart(start_time, end_time, platform_code, plat_type, cycle_type, type, accountSiteVal){
        $.post("<?php echo Url::toRoute(['/customer/customerrepurchase/getstatistics']); ?>", {
            "start_time": start_time,
            "end_time": end_time,
            "platform_code": platform_code,
            "plat_type": plat_type,
            "cycle_type": cycle_type,
            "type": type,
            "accountSiteVal": accountSiteVal,
        }, function (data) {
            if (data["code"] == 1) {
                option.xAxis.categories = data["data"]["categories"];
                option.series = data["data"]["series"];
                option.title.text = data["data"]['title'];
                option.yAxis.title.text = data["data"]['text'];
                var chart = Highcharts.chart('countChartCon', option);
            }
        }, "json");

        return false;
    }

    $(document).on("change","#countDataType",function(){
        var plat_type = $(this).val();

        var platform_code = $('#platform_code').val();

        if(platform_code == 'AMAZON'){
            var type = $('#type').val();
            var accountSiteVal = $('#account_site').val();
        }else{
            var type = $('#type').val();
            var accountSiteVal = '';
        }
        var start_time = $('#start_time').val();
        var end_time = $('#end_time').val();

        var html = "";
        if(plat_type != 0){
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/customer/customerrepurchase/getplatrate']) ?>',
                data: {'plat_type': plat_type,'platform_code': platform_code,'type':type,'accountSiteVal':accountSiteVal,'start_time':start_time,'end_time':end_time},
                success: function (data) {
                    if (data) {
                        $("#count_mumber").text(data.result);
                    } else {
                        $("#count_mumber").empty();
                    }
                }
            });
        }else{
            $("#count_mumber").empty();
        }
     return false;
    });
    //切换搜索平台获取对应的账号
    $(document).on("change", "#platform_code", function () {
        var platform_code = $(this).val();
        var html = "";

        if (platform_code == 'AMAZON') {
            $("#type").attr('multiple', false);
            $("#siteAccount").css('display', 'block');
        } else {
            $("#type").attr('multiple', true);
            $("#siteAccount").css('display', 'none');
        }
        if (platform_code) {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/accounts/account/getaccoutorsite']) ?>',
                data: {'platform_code': platform_code},
                success: function (data) {
                    if (data) {
                        $.each(data, function (n, value) {
                            html += '<option value=' + n + '>' + value + '</option>';
                        });
                    } else {
                        html = '<option value="">---请选择---</option>';
                    }
                    $("#type").empty();
                    $("#type").append(html);
                }
            });
        } else {
            $("#type").empty();
            $("#type").append(html);
        }
        return false;
    });

    //切换类型 获取对应的账号 或者站点数据
    $(document).on("change", "#type", function () {
        var platform_code = $("#platform_code").val();//平台
        var account = $(this).val();
        var type;
        var html = "";
        if (account) {
            if (account == 'account') {
                type = 1;
            }
            if (account == 'site') {
                type = 2;
            }
//           alert(type);
            //ajax请求数据
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/accounts/account/getaccoutorsite']) ?>',
                data: {'platform_code': platform_code, 'type': type},
                success: function (data) {

                    if (data) {
                        $.each(data, function (n, value) {
                            html += '<option value=' + n + '>' + value + '</option>';
                        });
                    } else {
                        html = '<option value="">---请选择---</option>';
                    }
                    $("#account_site").empty();
                    $("#account_site").append(html);
                }
            });

        } else {
            $("#account_site").empty();
            $("#account_site").append(html);
        }
        return false;
    });
</script>