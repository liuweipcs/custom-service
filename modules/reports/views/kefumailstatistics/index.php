<?php

use yii\helpers\Html;
use yii\grid\GridView;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use yii\widgets\LinkPager;
use yii\web\JsExpression;
use yii\helpers\Url;


$this->title = '客服邮件统计明细';

?>
<style>
    * {
        padding: 0;
        margin: 0 auto;
    }

    li {
        list-style: none;
    }

    #countChart {
        display: none;
        border: 1px solid #000;
        border-radius: 5px;
        padding: 10px;
    }

    #countChartCon {
        width: 100%;
        max-height: 420px;
    }

    #btn2 {
        background: #029be5;
        border: 1px solid #029be5;
        color: #fff;
    }
    .table02 {
        border: 1px solid #ccc;
    }

    .box {
        text-align: center;
        text-align: left;
        padding-left: 5px;
        padding-top: 5px;
    }
    #countTable {
        width: 100%;
        margin-top: 15px;
    }
    th {
        text-align: center;
        height: 35px;
        background: #f5f5f5;
    }
    .table01 {
        border: 1px solid #e1e1e1;
    }
    .table01 .orange01{
        color:#029be5;
    }
    .table01 .tr {
        text-align: right;
        border-bottom: 1px solid #e8e8e8;
        padding: 10px 7px;
        color: #666;
        border-right: 1px solid #f1f1f1;
    }
    .table01 td .span02 {
        width: 80px;
        color: #999;
        float: left;
        text-align: left;
    }
    .table01 th {
        font-weight: bold;
        padding: 10px 5px;
        background: #f5f5f5;
        text-align: center;
        font-size: 15px;
        border-right: 1px solid #e9e9e9;
        border-bottom: 1px solid #e9e9e9;
    }

    th {
        text-align: center;
        height: 35px;
    }

    td {
        text-align: center;
        height: 30px;
    }

    #btn1 {
        /*  background: #fff;*/
        color: #029be5;
    }
    .month a:hover{
        text-decoration: none;
    }
    .month a:active {
        text-decoration: none;
    }
    .month a:link {
        text-decoration: none;
    }
    .month a:visited {
        text-decoration: none;
    }

    .selCountType {
        height: 42px;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
        margin-bottom: -1px;
        position: relative;
        z-index: 2;
        border: 1px solid #e1e1e1;
        background-color: #f8f8f8;
        margin-top: 15px;
    }
    .selCountType li.selected {
        float: left;
        padding: 0 25px;
        position: relative;
        cursor: pointer;
        bottom: -1px;
        height: 41px;
        line-height: 40px;
        border-top: 2px solid #029be5;
        margin-top: -1px;
        background: #fff;
        z-index: 3;
    }
    .selCountType li {
        float: left;
        padding: 0 25px;
        height: 40px;
        line-height: 40px;
        background: #f5f5f5;
        border-right: 1px solid #e1e1e1;
        cursor: pointer;
        color: #444;
        font-size: 14px;
    }

    .accountSelItem {
        margin-bottom: 20px;
        top: 13px;
        left: 10px;
        height: 25px;
        line-height: 25px;
        font-size: 15px;
    }

    .accountSelItem > a {
        float: left;
        display: block;
        width: 110px;
        height: 30px;
        line-height: 30px;
        border: 1px solid #dedede;
        border-radius: 2px;
        background: #fff;
        font-size: 14px;
        display: inline;
        margin-right: 10px;
        margin-top: 6px;
        text-align: center;

    }
    .accountSelItem >a:hover {
        text-decoration: none;
        color:#666;
    }
    .accountSelItem >a:active {
        text-decoration: none;
        color:#029be5;
    }
    .accountSelItem >a:link {
        text-decoration: none;
    }
    .accountSelItem >a:visited {
        text-decoration: none;
    }
    .col-lg {
        margin-bottom: 20px;
        top: 13px;
        left: 10px;
        float: left;
        height: 25px;
        line-height: 25px;
        font-size: 15px;
    }
    .col-lg >a {
        float: left;
        color: #666;
        display: inline-block;
        padding: 0 7px;
        border-right: 1px solid #e8e8e8;
    }
    .col-lg >a:hover {
        text-decoration: none;
        color:#029be5;
    }
    .col-lg >a:active {
        text-decoration: none;
        color:#029be5;
    }
    .col-lg >a:link {
        text-decoration: none;
    }
    .col-lg >a:visited {
        text-decoration: none;
    }
    #select {
        height: 45px;
        background: #f5f5f5;
        padding: 10px;
        position: relative;
        border-left: 5px solid #029be5;
    }
    .glyp {
        width: 110px;
        height: 25px;
        line-height: 25px;
        text-align: center;
        display: inline-block;
        text-overflow: ellipsis;
        background-color: deepskyblue;
        color: #fff;
        border-radius: 3px;
        margin-bottom: 3px;
        cursor: pointer;
    }
    #plate {
        margin-top: 15px;
    }

    .pagedate tr:nth-child(2n-1){
        background: #f5f5f5;
    }
    .tc {
        border-bottom: 1px solid #e8e8e8;
        padding: 10px 7px;
        color: #666;
        height: 50px;
        border-right: 1px solid #f1f1f1;
        text-align: center;
        font-size: 20px;
    }
    #count03 .span03 {
        color: #029be5;
    }
</style>
<div id="page-wrapper">
    <div id ="select" class="row">
        <div class="col-lg">
            <a href="#" name="1" id="btn1">今日统计</a>

            <a name="-1" href="#">昨日统计</a></span>

            <a name="-7" href="#">过去7天</a>

            <a name="-15" href="#">过去15天</a>

            <a name="-30" href="#">过去30天</a>

            <a name="-45" href="#">过去45天</a>

            <a name="-60" href="#">过去60天</a>

            <a name="-90" href="#">过去90天</a>

            <a name="-180" href="#">过去半年</a>
        </div>
        <div class="form-group" style="width:235px;float: left;margin-top: -3px;margin-left: 20px;">
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
        <div class="form-group" style="width:235px;float: left;margin-top: -3px;margin-left: 20px;">
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
        <button style="padding: 5px 10px;margin-top: -3px;margin-left: 20px;" type="submit" class="btn btn-primary">搜索</button>
        <script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/highcharts.js"></script>
        <script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/exporting.js"></script>
        <script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/series-label.js"></script>
        <script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/oldie.js"></script>
        <script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/oldie.js"></script>
        <!-- /.col-lg-12 -->
        <script type="text/javascript">
            //选择指定时期内的统计
            $(function () {
                $(".col-lg a,.accountSelItem a").click(function (event) {
                    var name = $(event.target).attr('name');
                    if(name){
                        $(this).siblings().removeAttr("id");
                        $(this).attr("id", "btn1");
                        var account_id = $(".accountSelItem a").siblings('#btn2').attr("data_value");
                    }else{
                        $(this).siblings().removeAttr("id");
                        $(this).attr("id", "btn2");
                        var account_id = $(this).attr("data_value");
                        name = $(".col-lg a").siblings("#btn1").attr("name");
                    }
                    var user_name = $(".user_name").text();
                    var selCountType = $(".selCountType  li[class='selected'] > span").attr("class");
                    var platform_code = '<?php echo $platform_code;?>';
                    if (selCountType == "selCountTotal") {
                        $.ajax({
                            type: "POST",
                            dataType: "JSON",
                            url: '<?php echo Url::toRoute(['/reports/kefumailstatistics/dates']);?>',
                            data: {'name': name, 'account_id': account_id, 'user_name': user_name, 'platform_code': platform_code},
                            success: function (data) {
                                var result = data.count;
                                var _count01 = '<td class="tc"><span class="orange01">' + result.mail_all + '</span></td>' + '<td class="tc"><span class="orange01">' + result.return_all +'</span></td>' + '<td class="tc"><span class="orange01">' + result.inqurry_end_all + '</span></td>' + '<td class="tc"><span class="orange01">' + result.cancellation_all + '</span></td>'+'<td class="tc"><span class="orange01">' + result.feedback_all + '</span></td>';
                                var _count02 = '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.mail_end_all +'</span></td>';
                                _count02 += '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.return_end_all +'</span></td>';
                                _count02 += '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.inqurry_end_all +'</span></td>';
                                _count02 += '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.cancellation_end_all +'</span></td>';
                                _count02 += '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.feedback_end_all +'</span></td>';
                                var _count03 = '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.mail_not_all + '</span><span class="span03">('+result.mail_not_percent+')</span></td>';
                                _count03 += '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.return_not_all + '</span><span class="span03">('+result.return_not_percent+')</span></td>';
                                _count03 += '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.inqurry_not_all + '</span><span class="span03">('+result.inqurry_not_percent+')</span></td>';
                                _count03 += '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.cancellation_not_all + '</span><span class="span03">('+result.cancellation_not_percent+')</span></td>';
                                _count03 += '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.feedback_not_all + '</span><span class="span03">('+result.feedback_not_percent+')</span></td>';
                                var _count04 = '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.mail_wait_all +'</span></td>';
                                _count04 += '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.return_wait_all +'</span></td>';
                                _count04 += '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.inqurry_wait_all +'</span></td>';
                                _count04 += '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.cancellation_wait_all +'</span></td>';
                                _count04 += '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.feedback_wait_all +'</span></td>';
                                $('#count01').html(_count01);
                                $('#count02').html(_count02);
                                $('#count03').html(_count03);
                                $('#count04').html(_count04);
                            }
                        });
                    }else if (selCountType == "selCountChart") {
                        var dataType = $("#countDataType").val();
                        flushCountChart(name, dataType, user_name, account_id, platform_code);
                    }
                    return false;
                });

                var option = {
                    title: {
                        text: '客服流量统计明细'
                    },
                    xAxis: {
                        categories: []
                    },
                    yAxis: {
                        title: {
                            text: '数量'
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
                function flushCountChart(date, dataType, user_name, account_id, platform_code) {
                    $.post("<?php echo Url::toRoute(['/reports/kefumailstatistics/datadetail']); ?>", {
                        "date": date,
                        "dataType": dataType,
                        "user_name": user_name,
                        "account_id": account_id,
                        "platform_code": platform_code,
                    }, function (data) {
                        if (data["code"] == 1) {
                            option.xAxis.categories = data["data"]["categories"];
                            option.series = data["data"]["series"];
                            var chart = Highcharts.chart('countChartCon', option);
                        }
                    }, "json");
                }

                //搜索
                $(".btn-primary").on("click", function () {
                    var start_time = $('#start_time').val();
                    var end_time = $('#end_time').val();
                    var account_id = $(".accountSelItem a[id='btn2']").attr('data_value');
                    var user_name = $(".user_name").text();
                    var platform = '<?php echo $platform_code; ?>';
                    if(!start_time || !end_time){
                        alert('开始/结束时间都要填写');
                        return false;
                    }
                    $.ajax({
                        type: "POST",
                        dataType: "JSON",
                        url: '<?php echo Url::toRoute(['/reports/kefumailstatistics/dates']);?>',
                        data: {'account_id':account_id,'start_time': start_time, 'end_time': end_time,'user_name':user_name, 'platform': platform},
                        success: function (data) {
                            var result = data.count;
                            var _count01 = '<td class="tc"><span class="orange01">' + result.mail_all + '</span></td>' + '<td class="tc"><span class="orange01">' + result.return_all +'</span></td>' + '<td class="tc"><span class="orange01">' + result.inqurry_end_all + '</span></td>' + '<td class="tc"><span class="orange01">' + result.cancellation_all + '</span></td>'+'<td class="tc"><span class="orange01">' + result.feedback_all + '</span></td>';
                            var _count02 = '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.mail_end_all +'</span></td>';
                            _count02 += '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.return_end_all +'</span></td>';
                            _count02 += '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.inqurry_end_all +'</span></td>';
                            _count02 += '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.cancellation_end_all +'</span></td>';
                            _count02 += '<td class="tr"><span class="span02">已处理</span><span class="span03">'+ result.feedback_end_all +'</span></td>';
                            var _count03 = '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.mail_not_all + '</span><span class="span03">('+result.mail_not_percent+')</span></td>';
                            _count03 += '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.return_not_all + '</span><span class="span03">('+result.return_not_percent+')</span></td>';
                            _count03 += '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.inqurry_not_all + '</span><span class="span03">('+result.inqurry_not_percent+')</span></td>';
                            _count03 += '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.cancellation_not_all + '</span><span class="span03">('+result.cancellation_not_percent+')</span></td>';
                            _count03 += '<td class="tr"><span class="span02">未处理</span><span class="span03">' + result.feedback_not_all + '</span><span class="span03">('+result.feedback_not_percent+')</span></td>';
                            var _count04 = '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.mail_wait_all +'</span></td>';
                            _count04 += '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.return_wait_all +'</span></td>';
                            _count04 += '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.inqurry_wait_all +'</span></td>';
                            _count04 += '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.cancellation_wait_all +'</span></td>';
                            _count04 += '<td class="tr"> <span class="span02">待处理</span><span class="span03">'+ result.feedback_wait_all +'</span></td>';
                            $('#count01').html(_count01);
                            $('#count02').html(_count02);
                            $('#count03').html(_count03);
                            $('#count04').html(_count04);
                        }
                    });
                });

                //统计,趋势图切换
                $(".selCountType span").on("click", function () {
                    var id = $(this).parent("li").attr('class');
                    if(id != 'selected'){
                        $(this).parent("li").attr('class','selected');
                        $(this).parent("li").siblings("li").removeAttr("class");
                    }

                    var date = $(".col-lg > a[id='btn1']").attr("name");
                    var account_id = $(".accountSelItem a").siblings('#btn2').attr("data_value");
                    var user_name = $(".user_name").text();
                    var platform_code = '<?php echo $platform_code;?>';
                    if ($(this).attr("class") == "selCountChart") {
                        var dataType = $("#countDataType").val();
                        flushCountChart(date, dataType, user_name, account_id, platform_code);
                    }
                    if($(this).attr("class") == "selCountChart"){
                        $("#countChart").css('display','block');
                        $("#countTable").css('display','none');
                    }else{
                        $("#countChart").css('display','none');
                        $("#countTable").css('display','block');
                    }
                });

                $("#countDataType").on("change", function () {
                    var date = $(".col-lg > a[id='btn1']").attr("name");
                    var dataType = $(this).val();
                    var account_id = $(".accountSelItem a").siblings('#btn2').attr("data_value");
                    var user_name = $(".user_name").text();
                    var platform_code = '<?php echo $platform_code;?>';
                    flushCountChart(date, dataType, user_name, account_id, platform_code);
                });

            //按日期选择
                $(".glyp").click(function () {
                    var action = $(this).attr("id");
                    var months = $(".hidden").text();
                    var nowtime = new Date();
                    var year = nowtime.getFullYear();
                    var month = padleft0(nowtime.getMonth() + 1);
                    var day = padleft0(nowtime.getDate());
                    var e = year + "-" + month + "-" + day;
                    if (action == "down" && months == e) {
                        alert("下月还未到");
                        return false;
                    }
                    var user_name = $(".user_name").text();
                    var id = $(".accountSelItem > a[id = 'btn2']").attr("data_value");
                    var platform_code = '<?php echo $platform_code;?>';
                    $.ajax({
                        type: "POST",
                        dataType: "JSON",
                        url: '<?php echo Url::toRoute(['/reports/kefumailstatistics/months'])?>',
                        data: {'action': action, 'months': months, 'user_name': user_name, 'id': id, 'platform_code': platform_code},
                        success: function (data) {
                            var datenow = data.datenow;
                            var date = data.dateday;
                            var form_data = data.form_data;
                            $(".hidden").text(date);

                            var arr = new Array();
                            $.each(form_data.mail_list, function (i, v) {
                                arr.unshift('<tr>' +
                                    '<td>' + datenow + '-' + i + '</td>' +
                                    '<td>' + form_data.total[i] + '</td>' +
                                    '<td>' + form_data.completion_rate[i] + '</td>' +
                                    '<td>' + form_data.mail_list[i] + '</td>' +
                                    '<td>' + form_data.mail_end_list[i] + '</td>' +
                                    '<td>' + form_data.mail_not_list[i] + '<span>('+form_data.mail_not_percent[i]+')</span></td>' +
                                    '<td>' + form_data.return_list[i] + '</td>' +
                                    '<td>' + form_data.return_end_list[i] + '</td>' +
                                    '<td>' + form_data.return_not_list[i] + '<span>('+form_data.return_not_percent[i]+')</span></td>' +
                                    '<td>' + form_data.inqurry_list[i] + '</td>' +
                                    '<td>' + form_data.inqurry_end_list[i] + '</td>' +
                                    '<td>' + form_data.inqurry_not_list[i] + '<span>('+form_data.inqurry_not_percent[i]+')</span></td>' +
                                    '<td>' + form_data.cancellation_list[i] + '</td>' +
                                    '<td>' + form_data.cancellation_end_list[i] + '</td>' +
                                    '<td>' + form_data.cancellation_not_list[i] + '<span>('+form_data.cancellation_not_percent[i]+')</span></td>' +
                                    '<td>' + form_data.feedback_list[i] + '</td>' +
                                    '<td>' + form_data.feedback_end_list[i] + '</td>' +
                                    '<td>' + form_data.feedback_not_list[i] + '<span>('+form_data.feedback_not_percent[i]+')</span></td>' +
                                    '</tr>');
                            });
                            $('.pagedate').html(arr.join(""));
                        }
                    });
                });
            function padleft0(obj) {
                return obj.toString().replace(/^[0-9]{1}$/, "0" + obj);
            }
            });
        </script>
    </div>
    <div id ="plate" class="row">
        <div class="accountSelItem">
            <a href="#" data_value="0" id="btn2">全部</a>
            <?php foreach ($account as $k => $v) {
                ; ?>
                <a href="#" data_value=<?php echo $k; ?>><?php echo $v; ?></a>
            <?php }; ?>
        </div>
    </div>
    <div class="row">
        <div class="selCountType">
            <ul>
                <li class="selected">
                    <span class="selCountTotal">总计</span>
                </li>
                <li>
                    <span class="selCountChart">趋势图</span>
                </li>
            </ul>
        </div>
    </div>
        <div class="row countShow">
            <div id="countTable">
                <table class="table01" border="1" width="100%">
                    <thead>
                    <tr>
                        <?php if($platform_code == 'EB') {?>
                        <th style="width: 20%;">站内信</th>
                        <th style="width: 20%;">退款退货</th>
                        <th style="width: 20%;">未收到物品</th>
                        <th style="width: 20%;">取消交易</th>
                        <th style="width: 20%;">评价</th>
                        <?php }else{?>
                        <th style="width: 20%;">站内信</th>
                        <th style="width: 20%;">物流纠纷</th>
                        <th style="width: 20%;">买家原因纠纷</th>
                        <th style="width: 20%;">质量纠纷</th>
                        <th style="width: 20%;">评价</th>
                        <?php }; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <tr id="count01">
                        <td class="tc"><span class="orange01"><?php echo $mail_list[$day];?></span></td>
                        <td class="tc"><span class="orange01"><?php echo $return_list[$day]; ?></span></td>
                        <td class="tc"><span class="orange01"><?php echo $inqurry_list[$day]; ?></span></td>
                        <td class="tc"><span class="orange01"><?php echo $cancellation_list[$day]; ?></span></td>
                        <td class="tc"><span class="orange01"><?php echo $feedback_list[$day]; ?></span></td>
                    </tr>
                    <tr id="count02">
                        <td class="tr">
                            <span class="span02">已处理</span>
                            <span class="span03"><?php echo $mail_end_list[$day];?></span>
                        </td>
                        <td class="tr">
                            <span class="span02">已处理</span>
                            <span class="span03"><?php echo $return_end_list[$day]; ?></span>
                        </td>
                        <td class="tr">
                            <span class="span02">已处理</span>
                            <span class="span03"><?php echo $inqurry_end_list[$day]; ?></span>
                        </td>
                        <td class="tr">
                            <span class="span02">已处理</span>
                            <span class="span03"><?php echo $cancellation_end_list[$day]; ?></span>
                        </td>
                        <td class="tr">
                            <span class="span02">已处理</span>
                            <span class="span03"><?php echo $feedback_end_list[$day]; ?></span>
                        </td>
                    </tr>
                    <tr id="count03">
                        <td class="tr">
                            <span class="span02">未处理</span>
                            <span class="span03"><?php echo $mail_not_list[$day];?></span>
                            <span class="span03">(<?php echo $mail_not_percent[$day];?>)</span>
                        </td>
                        <td class="tr">
                            <span class="span02">未处理</span>
                            <span class="span03"><?php echo $return_not_list[$day]; ?></span>
                            <span class="span03">(<?php echo $return_not_percent[$day];?>)</span>
                        </td>
                        <td class="tr">
                            <span class="span02">未处理</span>
                            <span class="span03"><?php echo $inqurry_not_list[$day]; ?></span>
                            <span class="span03">(<?php echo $inqurry_not_percent[$day];?>)</span>
                        </td>
                        <td class="tr">
                            <span class="span02">未处理</span>
                            <span class="span03"><?php echo $cancellation_not_list[$day]; ?></span>
                            <span class="span03">(<?php echo $cancellation_not_percent[$day];?>)</span>
                        </td>
                        <td class="tr">
                            <span class="span02">未处理</span>
                            <span class="span03"><?php echo $feedback_not_list[$day]; ?></span>
                            <span class="span03">(<?php echo $feedback_not_percent[$day];?>)</span>
                        </td>
                    </tr>
                    <tr id="count04">
                        <td class="tr">
                            <span class="span02">待处理</span>
                            <span class="span03"><?php echo $mail_wait_list;?></span>
                        </td>
                        <td class="tr">
                            <span class="span02">待处理</span>
                            <span class="span03"><?php echo $return_wait_list; ?></span>
                        </td>
                        <td class="tr">
                            <span class="span02">待处理</span>
                            <span class="span03"><?php echo $inqurry_wait_list; ?></span>
                        </td>
                        <td class="tr">
                            <span class="span02">待处理</span>
                            <span class="span03"><?php echo $cancellation_wait_list; ?></span>
                        </td>
                        <td class="tr">
                            <span class="span02">待处理</span>
                            <span class="span03"><?php echo $feedback_wait_list; ?></span>
                        </td>
                    </tr>
                    </tbody>

                </table>

            </div>
        <div id="countChart">
            <select id="countDataType" class="form-control" style="width:150px;">
                <?php if($platform_code == 'EB') {?>
                <option value="inbox">站内信</option>
                <option value="return">退款退货</option>
                <option value="inquiry">未收到纠纷</option>
                <option value="cancellation">取消交易</option>
                <option value="feedback">评价</option>
                <?php }else{ ?>
                    <option value="inbox">站内信</option>
                    <option value="return">物流纠纷</option>
                    <option value="inquiry">买家原因纠纷</option>
                    <option value="cancellation">质量纠纷</option>
                    <option value="feedback">评价</option>
                <?php }; ?>
            </select>
            <div id="countChartCon">

            </div>
        </div>
    </div>
    <div style="margin:10px -15px;">
        <table class="table02" border="1" width="100%">
            <thead>
            <tr>
                <th><span class="countKefu">客服：<?php echo $user_name;?></span></th>
                <th colspan="2">总计/完成率</th>
                <th colspan="3">邮件</th>
                <?php if($platform_code == 'EB'){?>
                    <th colspan="3">退款退货</th>
                    <th colspan="3">未收到物品</th>
                    <th colspan="3">取消交易</th>
                    <th colspan="3">评价</th>
                <?php }else{?>
                    <th colspan="3">物流纠纷</th>
                    <th colspan="3">买家原因纠纷</th>
                    <th colspan="3">质量纠纷</th>
                    <th colspan="3">评价</th>
                <?php };?>
            <tr>
            <tr class="roe">
                <td height="30">日期</td>
                <td>总计</td>
                <td>完成率</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
            </tr>
            </thead>
            <tbody class="pagedate">
            <?php foreach ($mail_list as $k => $v) { ?>
                <tr>
                    <td><?php echo $monthnew . "-" . $k; ?></td>
                    <td><?php echo $total[$k]; ?></td>
                    <td><?php echo $completion_rate[$k]; ?></td>
                    <td><?php echo $mail_list[$k]; ?></td>
                    <td><?php echo $mail_end_list[$k]; ?></td>
                    <td><?php echo $mail_not_list[$k]; ?><span>(<?php echo $mail_not_percent[$k] ;?>)</span></td>
                    <td><?php echo $return_list[$k]; ?></td>
                    <td><?php echo $return_end_list[$k]; ?></td>
                    <td><?php echo $return_not_list[$k]; ?><span>(<?php echo $return_not_percent[$k] ;?>)</span></td>
                    <td><?php echo $inqurry_list[$k]; ?></td>
                    <td><?php echo $inqurry_end_list[$k]; ?></td>
                    <td><?php echo $inqurry_not_list[$k]; ?><span>(<?php echo $inqurry_not_percent[$k];?>)</span></td>
                    <td><?php echo $cancellation_list[$k]; ?></td>
                    <td><?php echo $cancellation_end_list[$k]; ?></td>
                    <td><?php echo $cancellation_not_list[$k]; ?><span>(<?php echo $cancellation_not_percent[$k];?>)</span></td>
                    <td><?php echo $feedback_list[$k]; ?></td>
                    <td><?php echo $feedback_end_list[$k]; ?></td>
                    <td><?php echo $feedback_not_list[$k]; ?><span>(<?php echo $feedback_not_percent[$k] ;?>)</span></td>
                </tr>
            <?php }; ?>
            </tbody>
        </table>
    </div>
    <div class="month">
        <span class="hidden" hidden="hidden"><?php echo $nowDate; ?></span>
        <span class="user_name" hidden="hidden"><?php echo $user_name;?></span>
        <span class="plat_code" hidden="hidden"><?php echo $platform_code;?></span>
        <a href="#" class="glyp" id="up">上个月</a>
        <a href="#" class="glyp" id="down">下个月</a>
    </div>
</div>


