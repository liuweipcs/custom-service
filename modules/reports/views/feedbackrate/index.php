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
    }

    .btn-excel {
        color: #fff;
        background-color: #337ab7;
        border-color: #2e6da4;
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
                    'value' => $account,
                    'data' => $accountArr,
                    'options' => [
                        'placeholder' => '账号',
                        'multiple' => true,
                    ]
                ]);
                ?>
            </div>
            <div class="col-lg-2" id="cuscomer">
                <?php
                echo Select2::widget([
                    'id' => 'user_id',
                    'name' => 'user_id',
                    'data' => $userIdList,
                    'value' => $users,
                    'options' => [
                        'placeholder' => '客服人员',
                        'multiple' => true,
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
        <button type="button" class="btn btn-excel">导出数据</button>
    </div>

    <div class="countChart" style="margin-top: 40px;border: 1px solid #ddd;float: left;width: 100%;">
        <form class="form-inline">
            <div class="form-group" style="margin-top: 10px; margin-left: 10px;">
                <select id="countDataType" class="form-control" style="width:180px;">
                    <option value="0">全部</option>
                    <option value="1">按平台统计</option>
                    <option value="2">按账号统计</option>
                    <option value="3">按客服统计</option>
                </select>
            </div>
            <div class="form-group" style="margin-top: 10px;margin-left: 10px;">
                <select id="cycle_type" class="form-control" style="width:180px;">
                    <option value="5">月</option>
                    <option value="6">季度</option>
                    <option value="7">年</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary">搜索</button>
        </form>
        <div class="row">
            <div class="col-lg-6">
                <ul class="feedback" id="positive_feedback">

                </ul>
            </div>
            <div id = "positive_con" class="col-lg-12">



            </div>

        </div>
        <div class="row">
            <div class="col-lg-6">
                <ul class="feedback" id="negative_feedback">

                </ul>
            </div>
            <div id = "negative_con" class="col-lg-12">


            </div>

        </div>

    </div>
</div>

<script type="text/javascript">
    $(function () {
        var positive = {
            title: {
                text: ''
            },
            xAxis: {
                categories: []
            },
            yAxis: {
                title: {
                    text: ''
                },
                labels: {
                    formatter: function () {
                        //y轴加上%
                        return this.value + '%';
                    }
                }
            },
            tooltip: {
                shared: true,
                crosshairs: true,
                valueSuffix: '%'
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

        var negative = {
            title: {
                text: ''
            },
            xAxis: {
                categories: []
            },
            yAxis: {
                title: {
                    text: ''
                },
                labels: {
                    formatter: function () {
                        //y轴加上%
                        return this.value + '%';
                    }
                }
            },
            tooltip: {
                shared: true,
                crosshairs: true,
                valueSuffix: '%'
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

        function flushCountChart(start_time, end_time, platform_code, plat_type, cycle_type, account, user_name) {
            $.post("<?php echo Url::toRoute(['/reports/feedbackrate/feedbackstatistics', 'r' => 'fafafaf']); ?>", {
                "start_time": start_time,
                "end_time": end_time,
                "platform_code": platform_code,
                "plat_type": plat_type,
                "cycle_type": cycle_type,
                "account": account,
                "user_name": user_name,
            }, function (data) {
                if (data["code"] == 1) {

                    var positive_feedback =  '<li>评价数 : <span style="font-weight:bold;color: #029BE5;" id="zong">'+data["data"]["feedback"]+'</span></li>'+
                        '<li>好评数 : <span style="font-weight:bold;color: #029BE5;" id="hao">'+data["data"]["zong_positive"]+'</span></li>'+
                        '<li>好评率 : <span style="font-weight:bold;color: #029BE5;" id="hao_rate">'+data["data"]["zong_positive_rate"]+'</span></li>';

                    var negative_feedback = '<li>评价数 : <span style="font-weight:bold;color: #029BE5;" id="zong">'+data["data1"]["feedback"]+'</span></li>'+
                        '<li>差评数 : <span style="font-weight:bold;color:#029BE5;" id="hao">'+data["data1"]["zong_negative"]+'</span></li>'+
                        '<li>差评率 : <span style="font-weight:bold;color: #029BE5;" id="hao_rate">'+data["data1"]["zong_negative_rate"]+'</span></li>';

                    $("#positive_feedback").html(positive_feedback);
                    $("#negative_feedback").html(negative_feedback);

                    positive.xAxis.categories = data["data"]["categories"];
                    positive.series = data["data"]["series"];
                    positive.title.text = data["data"]['title'];
                    positive.yAxis.title.text = data["data"]['text'];

                    negative.xAxis.categories = data["data1"]["categories"];
                    negative.series = data["data1"]["series"];
                    negative.title.text = data["data1"]['title'];
                    negative.yAxis.title.text = data["data1"]['text'];

                    Highcharts.chart('positive_con', positive);
                    Highcharts.chart('negative_con', negative);
                }
            }, "json");

            return false;
        }

        //切换搜索平台获取对应的账号
        $(document).on("change", "#platform_code", function () {
            var platform_code = $(this).val();
            var html = "";

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


        //数据导出
        $(".btn-excel").on("click", function () {
            var start_time = $('#start_time').val();
            var end_time = $('#end_time').val();
            var platform_code = $('#platform_code').val();
            var user_name = $('#user_id').val();

            if(platform_code == 0 || platform_code == null){
                alert('请选择一个平台');
                return false;
            }

            if (!start_time || !end_time) {
                alert('开始/结束时间都要填写');
                return false;
            }
            if (start_time >= end_time) {
                alert('开始时间必须小于结束时间');
                return false;
            }

           var queryStr = '&start_time='+start_time +'&end_time='+ end_time + '&platform_code='+ platform_code +'&user_name='+ user_name;
            location.href = "<?php echo Url::toRoute('/reports/feedbackrate/excel'); ?>?" + queryStr;

            return false;
        });

        //搜索
        $(".btn-primary").on("click", function () {

            var start_time = $('#start_time').val();
            var end_time = $('#end_time').val();
            var platform_code = $('#platform_code').val();
            var account = $('#type').val();
            var user_name = $('#user_id').val();

            var plat_type = $('#countDataType').val();
            if (plat_type == 0) {
                alert('请选择统计类型');
                return false;
            }
            if(plat_type == 2){
                if(platform_code == 0 || platform_code == null){
                    alert('账号统计必须选择一个平台');
                    return false;
                }
            }
            if(plat_type == 3){
                if(platform_code == 0 || platform_code == null){
                    alert('客服统计必须选择一个平台');
                    return false;
                }
            }
            var cycle_type = $("#cycle_type").val();
            if (!cycle_type) {
                alert('请选择统计周期');
                return false;
            }
            if (!start_time || !end_time) {
                alert('开始/结束时间都要填写');
                return false;
            }
            if (start_time >= end_time) {
                alert('开始时间必须小于结束时间');
                return false;
            }

            flushCountChart(start_time, end_time, platform_code, plat_type, cycle_type, account, user_name);

        });


    });
</script>
