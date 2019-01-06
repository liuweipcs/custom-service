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

    * {
        padding: 0;
        margin: 0 auto;
    }

    #btn1 {
        /*  background: #fff;*/
        color: #029be5;
    }

    #select {
        height: 45px;
        background: #f5f5f5;
        padding: 6px;
        position: relative;
        border-left: 5px solid #029be5;
    }

    .selDayItem {
        margin-bottom: 20px;
        top: 13px;
        left: 10px;
        height: 25px;
        line-height: 30px;
        font-size: 15px;
        float: left;
    }
    .selDayItem >a {
        float: left;
        color: #666;
        display: inline-block;
        padding: 0 7px;
        border-right: 1px solid #e8e8e8;
    }
    .selDayItem >a:hover {
        text-decoration: none;
        color:#029be5;
    }
    .selDayItem >a:active {
        text-decoration: none;
        color:#029be5;
    }
    .selDayItem >a:link {
        text-decoration: none;
    }
    .selDayItem >a:visited {
        text-decoration: none;
    }

    #pass{width:1660px;
        height:1000px;
        overflow:hidden;
        margin-left: -15px;
        background: #ccc;
        margin-top: 20px;
    }

    .claim_data_con,.refund_data_con,.claims_data_con,.refunds_data_con{
        width:800px;
        height:470px;float:left;
        color:#fff;
        margin-left: 20px;
        margin-top: 20px;
    }
    .claim_data_con{background:#fff;}
    .refund_data_con{background:#fff;}
    .claims_data_con{background:#fff;}
    .refunds_data_con{background:#fff;}
</style>
<div id="page-wrapper">
    <div id ="select" class="row">
        <div class="col-lg-2" style="float: left;">
            <?php echo Select2::widget([
                'id' => 'type',
                'name' => 'type',
                'value' => $account_id,
                'data' => $account,
                'options' => [
                    'placeholder' => '账号',
                    'multiple' => true,
                ]
            ]);
            ?>
        </div>
        <div class="selDayItem">
            <a name="-7" href="#" id="btn1">过去7天</a>

            <a name="-15" href="#">过去15天</a>

            <a name="-30" href="#">过去30天</a>

            <a name="-45" href="#">过去45天</a>

            <a name="-60" href="#">过去60天</a>
        </div>
        <div class="form-group" style="width:235px;float: left;margin-left: 20px;">
            <?php
            echo DateTimePicker::widget([
                'name' => 'start_time',
                'id' => 'start_time',
                'options' => ['placeholder' => '按条件查询'],
                'value' => $start_time,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'yyyy-mm-dd',
                    'todayHighlight' => true,
                    'todayBtn' => 'linked',
                ],
            ]); ?>
        </div>
        <div class="form-group" style="width:235px;float: left;margin-left: 20px;">
            <?php
            echo DateTimePicker::widget([
                'name' => 'end_time',
                'id' => 'end_time',
                'options' => ['placeholder' => '按条件查询'],
                'value' => $end_time,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'yyyy-mm-dd',
                    'todayHighlight' => true,
                    'todayBtn' => 'linked',
                ],
            ]); ?>
        </div>
        <button style="padding: 5px 10px;margin-left: 20px;" type="submit" class="btn btn-primary">搜索</button>
    </div>


    <div id="pass">
        <div id = "claim_data_con" class="claim_data_con">



        </div>
        <div id = "refund_data_con" class="refund_data_con">



        </div>
        <div id = "claims_data_con" class="claims_data_con">



        </div>
        <div id = "refunds_data_con" class="refunds_data_con">



        </div>

    </div>
</div>

<script type="text/javascript">
    $(function () {

        var name = $(".selDayItem a").siblings("#btn1").attr("name");
        var account_ids = $('#type').val();
        flushCountChart(name,account_ids);

        var claim_data = {
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

        var refund_data = {
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

        var claims_data = {
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

        var refunds_data = {
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


        function flushCountChart(name, account_ids) {
            $.post("<?php echo Url::toRoute(['/accounts/cdiscountaccountoverview/cdiscountstatistics', 'r' => 'fafafaf']); ?>", {
                "name": name,
                "account_ids": account_ids,
            }, function (data) {
                if (data["code"] == 1) {

                    claim_data.xAxis.categories = data["claim_data"]["categories"];
                    claim_data.series = data["claim_data"]["series"];
                    claim_data.title.text = data["claim_data"]['title'];
                    claim_data.yAxis.title.text = data["claim_data"]['text'];

                    refund_data.xAxis.categories = data["refund_data"]["categories"];
                    refund_data.series = data["refund_data"]["series"];
                    refund_data.title.text = data["refund_data"]['title'];
                    refund_data.yAxis.title.text = data["refund_data"]['text'];

                    claims_data.xAxis.categories = data["claims_data"]["categories"];
                    claims_data.series = data["claims_data"]["series"];
                    claims_data.title.text = data["claims_data"]['title'];
                    claims_data.yAxis.title.text = data["claims_data"]['text'];

                    refunds_data.xAxis.categories = data["refunds_data"]["categories"];
                    refunds_data.series = data["refunds_data"]["series"];
                    refunds_data.title.text = data["refunds_data"]['title'];
                    refunds_data.yAxis.title.text = data["refunds_data"]['text'];

                    Highcharts.chart('claim_data_con', claim_data);
                    Highcharts.chart('refund_data_con', refund_data);
                    Highcharts.chart('claims_data_con', claims_data);
                    Highcharts.chart('refunds_data_con', refunds_data);
                }
            }, "json");

            return false;
        }

        //选择时间
        $(".selDayItem a").on("click", function () {
            $(this).siblings().removeAttr("id");
            $(this).attr("id", "btn1");
            var name = $(".selDayItem a").siblings("#btn1").attr("name");
            var account_ids = $('#type').val();
            flushCountChart(name,account_ids);

        });

       //搜索
    $(".btn-primary").on("click", function () {
          var account_ids = $('#type').val();
          var start_time = $('#start_time').val();
          var end_time = $('#end_time').val();

          if(!start_time || !end_time){
              alert('开始/结束时间必填');
              return;
          }

        $.post("<?php echo Url::toRoute(['/accounts/cdiscountaccountoverview/cdiscountstatistics']); ?>", {
            "account_ids": account_ids,
            "start_time": start_time,
            "end_time": end_time,
        }, function (data) {
            if (data["code"] == 1) {

                claim_data.xAxis.categories = data["claim_data"]["categories"];
                claim_data.series = data["claim_data"]["series"];
                claim_data.title.text = data["claim_data"]['title'];
                claim_data.yAxis.title.text = data["claim_data"]['text'];

                refund_data.xAxis.categories = data["refund_data"]["categories"];
                refund_data.series = data["refund_data"]["series"];
                refund_data.title.text = data["refund_data"]['title'];
                refund_data.yAxis.title.text = data["refund_data"]['text'];

                claims_data.xAxis.categories = data["claims_data"]["categories"];
                claims_data.series = data["claims_data"]["series"];
                claims_data.title.text = data["claims_data"]['title'];
                claims_data.yAxis.title.text = data["claims_data"]['text'];

                refunds_data.xAxis.categories = data["refunds_data"]["categories"];
                refunds_data.series = data["refunds_data"]["series"];
                refunds_data.title.text = data["refunds_data"]['title'];
                refunds_data.yAxis.title.text = data["refunds_data"]['text'];

                Highcharts.chart('claim_data_con', claim_data);
                Highcharts.chart('refund_data_con', refund_data);
                Highcharts.chart('claims_data_con', claims_data);
                Highcharts.chart('refunds_data_con', refunds_data);
            }
        }, "json");

        return false;

    });
    });
</script>