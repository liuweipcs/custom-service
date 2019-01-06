<?php

use yii\helpers\Html;
use yii\grid\GridView;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use yii\widgets\LinkPager;
use yii\web\JsExpression;
use yii\helpers\Url;


$this->title = '客服邮件量统计';

?>

<style>
    * {
        padding: 0;
        margin: 0 auto;
    }

    li {
        list-style: none;
    }

    .box {
        text-align: center;
        text-align: left;
        padding-left: 5px;
        padding-top: 5px;
    }
    .search {
        position:absolute;
    }
    th {
        text-align: center;
        height: 35px;
        background: #f5f5f5;
    }

    td {
        text-align: center;
        height: 30px;
    }

    #btn1 {
      /*  background: #fff;*/
        color: #029be5;
    }

    #btn2 {
        background: #029be5;
        border: 1px solid #029be5;
        color: #fff;
    }

    .selDayItem {
        margin-bottom: 20px;
        top: 13px;
        left: 10px;
        height: 25px;
        line-height: 25px;
        font-size: 15px;
        float: left;
    }
    .accountSelItem {
        margin-bottom: 20px;
        top: 13px;
        left: 10px;
        height: 25px;
        line-height: 25px;
        font-size: 15px;
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

    .selDayItem > span {
        height: 25px;
        line-height: 25px;
        text-align: center;
        display: inline-block;
        color: #fff;
        border-radius: 3px;
        margin-bottom: 3px;
        cursor: pointer;
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
    .accountSelItem > a {
        float: left;
        display: block;
        width: 90px;
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
    #select {
        height: 45px;
        background: #f5f5f5;
        padding: 10px;
        position: relative;
        border-left: 5px solid #029be5;
    }
    #plate {
        margin-top: 15px;
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
    }

    #countChart {
        display: none;
        border: 1px solid #e1e1e1;
        border-radius: 5px;
        padding: 10px;
    }
    .table02 {
        border: 1px solid #ccc;
    }
    .table01 {
        border: 1px solid #e1e1e1;
    }
    .select2-selection__choice {
        clear:both;
    }
    .select2-container--krajee {
        min-width: 155px !important;
    }
    #countChartCon {
        width: 100%;
        max-height: 420px;
    }

    #countTable {
        width: 100%;
        margin-top: 15px;
    }
    .form-control{
        display: inline;
    }
    #count03 .span03 {
        color: #029be5;
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
    .pagedate tr:nth-child(2n-1){
        background: #f5f5f5;
    }
</style>
<div id="page-wrapper">
    <div id ="select" class="row">
        <div class="selDayItem">
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
        <div class="form-group" style="float:left;margin-top: -3px;margin-left: 20px;">
            <div class="col-lg-7">
                <?php echo Select2::widget([
                    'id' => 'user_name_kefu',
                    'name' => 'user_name_kefu',
                    'value' => $user_name,
                    'data' => $userList,
                    'options' => ['placeholder' => '客服搜索...']
                ]);
                ?>
            </div>
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
    </div>

    <div id ="plate" class="row">
        <div class="accountSelItem">
            <?php foreach ($platfrom as $v) { ?>
                <?php if($v == $plat_code) {?>
                    <a href="#" id="btn2" data_value=<?php echo $v; ?>><?php echo $v; ?></a>
                <?php }else{ ?>
                    <a href="#" data_value=<?php echo $v; ?>><?php echo $v; ?></a>
                <?php }; ?>
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
                <tr id="zong_head_one">
                    <?php if($plat_code == 'EB') {?>
                    <th style="width: 20%;">站内信</th>
                    <th style="width: 20%;">退款退货</th>
                    <th style="width: 20%;">未收到物品</th>
                    <th style="width: 20%;">取消交易</th>
                    <th style="width: 20%;">评价</th>
                    <?php }else{ ?>
                        <th style="width: 20%;">站内信</th>
                        <th style="width: 20%;">物流纠纷</th>
                        <th style="width: 20%;">买家原因纠纷</th>
                        <th style="width: 20%;">质量纠纷</th>
                        <th style="width: 20%;">评价</th>
                    <?php }; ?>
                </tr>
                <tr id="zong_head_two">

                </tr>
                </thead>
                <tbody>
                <tr id="count01">
                    <td class="tc"><span class="orange01"><?php echo $mail_list;?></span></td>
                    <td class="tc"><span class="orange01"><?php echo $return_list; ?></span></td>
                    <td class="tc"><span class="orange01"><?php echo $inqurry_list; ?></span></td>
                    <td class="tc"><span class="orange01"><?php echo $cancellation_list; ?></span></td>
                    <td class="tc"><span class="orange01"><?php echo $feedback_list; ?></span></td>
                </tr>
                <tr id="count02">
                    <td class="tr">
                        <span class="span02">已处理</span>
                        <span class="span03"><?php echo $mail_end_list;?></span>
                    </td>
                    <td class="tr">
                        <span class="span02">已处理</span>
                        <span class="span03"><?php echo $return_end_list; ?></span>
                    </td>
                    <td class="tr">
                        <span class="span02">已处理</span>
                        <span class="span03"><?php echo $inqurry_end_list; ?></span>
                    </td>
                    <td class="tr">
                        <span class="span02">已处理</span>
                        <span class="span03"><?php echo $cancellation_end_list; ?></span>
                    </td>
                    <td class="tr">
                        <span class="span02">已处理</span>
                        <span class="span03"><?php echo $feedback_end_list; ?></span>
                    </td>
                </tr>
                <tr id="count03">
                    <td class="tr">
                        <span class="span02">未处理</span>
                        <span class="span03"><?php echo $mail_not_list;?></span>
                        <span class="span03">(<?php echo $mail_not_percent;?>)</span>
                    </td>
                    <td class="tr">
                        <span class="span02">未处理</span>
                        <span class="span03"><?php echo $return_not_list; ?></span>
                        <span class="span03">(<?php echo $return_not_percent;?>)</span>
                    </td>
                    <td class="tr">
                        <span class="span02">未处理</span>
                        <span class="span03"><?php echo $inqurry_not_list; ?></span>
                        <span class="span03">(<?php echo $inqurry_not_percent;?>)</span>
                    </td>
                    <td class="tr">
                        <span class="span02">未处理</span>
                        <span class="span03"><?php echo $cancellation_not_list; ?></span>
                        <span class="span03">(<?php echo $cancellation_not_percent;?>)</span>
                    </td>
                    <td class="tr">
                        <span class="span02">未处理</span>
                        <span class="span03"><?php echo $feedback_not_list; ?></span>
                        <span class="span03">(<?php echo $feedback_not_percent;?>)</span>
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
            <form class="form-inline">
                <div class="form-group" id="chart1">
                    <label>统计类别</label>
                    <select id="countDataType" class="form-control" style="width:150px;">
                            <option value="all">全部</option>
                            <option value="inbox">站内信</option>
                            <option value="return">退款退货</option>
                            <option value="inquiry">未收到纠纷</option>
                            <option value="cancellation">取消交易</option>
                            <option value="feedback">评价</option>
                    </select>
                </div>
                <div class="form-group" id="chart2">

                </div>
                <div class="form-group">
                    <div class="col-lg-2" style="margin-left: 10px;">
                        <?php echo Select2::widget([
                            'id' => 'countCostum',
                            'name' => 'countCostum',
                            'value' => $user_name,
                            'data' => $userList,
                            'options' => ['placeholder' => '客服人员...', 'multiple' => true, 'display' => 'block']
                        ]);
                        ?>
                    </div>
                </div>
                <span type="submit" class="btn search btn-info m-b-5">搜 索</span>
            </form>
            <div id="countChartCon">

            </div>
        </div>
    </div>
    <div style="margin:10px -15px;">
        <table class="table02" border="1" width="100%">
            <thead>
            <tr id="head_one">
                <th></th>
                <th colspan="2">总计/完成率</th>
                <?php if($plat_code == 'EB'){ ;?>
                    <th colspan="4">邮件</th>
                    <th colspan="4">退款退货</th>
                    <th colspan="4">未收到物品</th>
                    <th colspan="4">取消交易</th>
                    <th colspan="4">评价</th>
                <?php }else{ ?>
                    <th colspan="4">邮件</th>
                    <th colspan="4">物流纠纷</th>
                    <th colspan="4">买家原因纠纷</th>
                    <th colspan="4">质量纠纷</th>
                    <th colspan="4">评价</th>
                <?php }; ?>
            <tr>
            <tr id="head_two">

            </tr>
            <tr class="roe">
                <td height="30">客服</td>
                <td>总计</td>
                <td>完成率</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
                <td>待处理</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
                <td>待处理</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
                <td>待处理</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
                <td>待处理</td>
                <td>总数</td>
                <td>已处理</td>
                <td>未处理</td>
                <td>待处理</td>
            </tr>
            </thead>
            <tbody class="pagedate">
            <?php foreach ($list as $k => $v) { ?>
                <tr>
                    <td name="<?php echo $k; ?>"><a id="detail" style="color:#029be5;" target="_blank" href="<?php echo Url::toRoute(['/reports/kefumailstatistics/index',
                            'user_name' => $k]); ?>"><?php echo $k; ?></a></td>
                    <td><?php echo $v['total']; ?></td>
                    <td><?php echo $v['completion_rate']; ?></td>
                    <td><?php echo $v['mail_list']; ?></td>
                    <td><?php echo $v['mail_end_list']; ?></td>
                    <td><?php echo $v['mail_not_list']; ?><span>(<?php echo $v['mail_not_percent']; ?>)</span></td>
                    <td><?php echo $v['mail_wait_list']; ?></td>
                    <td><?php echo $v['return_list']; ?></td>
                    <td><?php echo $v['return_end_list']; ?></td>
                    <td><?php echo $v['return_not_list']; ?><span>(<?php echo $v['return_not_percent']; ?>)</span></td>
                    <td><?php echo $v['return_wait_list']; ?></td>
                    <td><?php echo $v['inqurry_list']; ?></td>
                    <td><?php echo $v['inqurry_end_list']; ?></td>
                    <td><?php echo $v['inqurry_not_list']; ?><span>(<?php echo $v['inqurry_not_percent']; ?>)</span></td>
                    <td><?php echo $v['inqurry_wait_list']; ?></td>
                    <td><?php echo $v['cancellation_list']; ?></td>
                    <td><?php echo $v['cancellation_end_list']; ?></td>
                    <td><?php echo $v['cancellation_not_list']; ?><span>(<?php echo $v['cancellation_not_percent']; ?>)</span></td>
                    <td><?php echo $v['cancellation_wait_list']; ?></td>
                    <td><?php echo $v['feedback_list']; ?></td>
                    <td><?php echo $v['feedback_end_list']; ?></td>
                    <td><?php echo $v['feedback_not_list']; ?><span>(<?php echo $v['feedback_not_percent']; ?>)</span></td>
                    <td><?php echo $v['feedback_wait_list']; ?></td>
                </tr>
            <?php }; ?>
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/highcharts.js"></script>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/exporting.js"></script>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/series-label.js"></script>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/oldie.js"></script>
<script type="text/javascript" src="<?php echo yii\helpers\Url::base(true); ?>/js/highcharts/code/modules/oldie.js"></script>
<script type="text/javascript">
    $(function () {
        //选择指定时期内的统计
        $(".selDayItem >a").click(function () {
            $(this).siblings().removeAttr("id");
            $(this).attr("id", "btn1");
            var name = $(this).attr("name");
            var platform_code = $('#btn2').attr("data_value");
            var selCountType = $(".selCountType li[class='selected'] > span").attr("class");
            if (selCountType == "selCountTotal") {
                $.ajax({
                    type: "POST",
                    dataType: "JSON",
                    url: '<?php echo Url::toRoute(['/reports/kefumailstatistics/data']);?>',
                    data: {'name': name, 'platform_code': platform_code},
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
                        var list = data.list;
                        var html = '';
                        $.each(list, function (i, v) {
                            html += '<tr>'+
                                '<td name="'+i+'"><a id="detail" style="color:#029be5;" target="_blank" href="/reports/kefumailstatistics/index?user_name='+i+'">'+i+'</a></td>'+
                                '<td>'+v['total']+'</td>'+
                                '<td>'+v['completion_rate']+'</td>'+
                                '<td>'+v['mail_list']+'</td>'+
                                '<td>'+v['mail_end_list']+'</td>'+
                                '<td>'+v['mail_not_list']+'<span>('+v['mail_not_percent']+')</span></td>'+
                                '<td>'+v['mail_wait_list']+'</td>'+
                                '<td>'+v['return_list']+'</td>'+
                                '<td>'+v['return_end_list']+'</td>'+
                                '<td>'+v['return_not_list']+'<span>('+v['return_not_percent']+')</span></td>'+
                                '<td>'+v['return_wait_list']+'</td>'+
                                '<td>'+v['inqurry_list']+'</td>'+
                                '<td>'+v['inqurry_end_list']+'</td>'+
                                '<td>'+v['inqurry_not_list']+'<span>('+v['inqurry_not_percent']+')</span></td>'+
                                '<td>'+v['inqurry_wait_list']+'</td>'+
                                '<td>'+v['cancellation_list']+'</td>'+
                                '<td>'+v['cancellation_end_list']+'</td>'+
                                '<td>'+v['cancellation_not_list']+'<span>('+v['cancellation_not_percent']+')</span></td>'+
                                '<td>'+v['cancellation_wait_list']+'</td>'+
                                '<td>'+v['feedback_list']+'</td>'+
                                '<td>'+v['feedback_end_list']+'</td>'+
                                '<td>'+v['feedback_not_list']+'<span>('+v['feedback_not_percent']+')</span></td>'+
                                '<td>'+v['feedback_wait_list']+'</td>'+
                                '</tr>';
                        });
                        $('.pagedate').html(html);
                    }
                });
            } else if (selCountType == "selCountChart") {
                var dataType = $("#countDataType").val();
                var countCostum = $("#countCostum").val();
                flushCountChart(name, dataType, countCostum, platform_code);
            }
            return false;
        });

        var option = {
            title: {
                text: '客服流量统计'
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

        //按平台统计
        $(".accountSelItem >a").click(function (){
            var platform_code = $(this).attr("data_value");
            if(platform_code != 'EB' && platform_code != 'ALI'){
                alert('该平台暂无统计');
                return false;
            }
            $(this).siblings().removeAttr("id");
            $(this).attr("id", "btn2");
            var name = $('#btn1').attr("name");
            var selCountType = $(".selCountType li[class='selected'] > span").attr("class");
            if (selCountType == "selCountTotal") {
                $.ajax({
                    type: "POST",
                    dataType: "JSON",
                    url: '<?php echo Url::toRoute(['/reports/kefumailstatistics/data']);?>',
                    data: {'name': name, 'platform_code': platform_code},
                    success: function (data) {
                        var user_list = data.user_list;
                        var htm = '';
                        $.each(user_list, function (n, value) {
                            htm += '<option value=' + n + '>' + value + '</option>';
                        });
                        $("#user_name_kefu").empty();
                        $("#user_name_kefu").append(htm);
                        $("#countCostum").empty();
                        $("#countCostum").append(htm);
                        var zong_head = '';
                        var head = '';
                        if(data.platform_code == 'EB'){
                            zong_head += '<th style="width: 20%;">站内信</th>'
                                +'<th style="width: 20%;">退款退货</th>'
                                +'<th style="width: 20%;">未收到物品</th>'
                                +'<th style="width: 20%;">取消交易</th>'
                                +'<th style="width: 20%;">评价</th>';
                            head += '<th></th>'
                                +'<th colspan="2">总计/完成率</th>'
                                +'<th colspan="4">邮件</th>'
                                +'<th colspan="4">退款退货</th>'
                                +'<th colspan="4">未收到物品</th>'
                                +'<th colspan="4">取消交易</th>'
                                +'<th colspan="4">评价</th>';

                        }else{
                            zong_head += '<th style="width: 20%;">站内信</th>'
                                +'<th style="width: 20%;">物流纠纷</th>'
                                +'<th style="width: 20%;">买家原因纠纷</th>'
                                +'<th style="width: 20%;">质量纠纷</th>'
                                +'<th style="width: 20%;">评价</th>';

                            head += '<th></th>'
                                +'<th colspan="2">总计/完成率</th>'
                                +'<th colspan="4">邮件</th>'
                                +'<th colspan="4">物流纠纷</th>'
                                +'<th colspan="4">买家原因纠纷</th>'
                                +'<th colspan="4">质量纠纷</th>'
                                +'<th colspan="4">评价</th>';
                        }

                        $("#zong_head_one").empty();
                        $("#zong_head_two").html(zong_head);
                        $("#head_one").empty();
                        $("#head_two").html(head);

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
                        var list = data.list;
                        var html = '';
                        $.each(list, function (i, v) {
                            html += '<tr>'+
                                '<td name="'+i+'"><a id="detail" style="color:#029be5;" target="_blank" href="/reports/kefumailstatistics/index?user_name='+i+'">'+i+'</a></td>'+
                                '<td>'+v['total']+'</td>'+
                                '<td>'+v['completion_rate']+'</td>'+
                                '<td>'+v['mail_list']+'</td>'+
                                '<td>'+v['mail_end_list']+'</td>'+
                                '<td>'+v['mail_not_list']+'<span>('+v['mail_not_percent']+')</span></td>'+
                                '<td>'+v['mail_wait_list']+'</td>'+
                                '<td>'+v['return_list']+'</td>'+
                                '<td>'+v['return_end_list']+'</td>'+
                                '<td>'+v['return_not_list']+'<span>('+v['return_not_percent']+')</span></td>'+
                                '<td>'+v['return_wait_list']+'</td>'+
                                '<td>'+v['inqurry_list']+'</td>'+
                                '<td>'+v['inqurry_end_list']+'</td>'+
                                '<td>'+v['inqurry_not_list']+'<span>('+v['inqurry_not_percent']+')</span></td>'+
                                '<td>'+v['inqurry_wait_list']+'</td>'+
                                '<td>'+v['cancellation_list']+'</td>'+
                                '<td>'+v['cancellation_end_list']+'</td>'+
                                '<td>'+v['cancellation_not_list']+'<span>('+v['cancellation_not_percent']+')</span></td>'+
                                '<td>'+v['cancellation_wait_list']+'</td>'+
                                '<td>'+v['feedback_list']+'</td>'+
                                '<td>'+v['feedback_end_list']+'</td>'+
                                '<td>'+v['feedback_not_list']+'<span>('+v['feedback_not_percent']+')</span></td>'+
                                '<td>'+v['feedback_wait_list']+'</td>'+
                                '</tr>';
                        });
                        $('.pagedate').html(html);
                    }
                });
            } else if (selCountType == "selCountChart") {
                var chart2 = '';
                var head = '';
                if(platform_code == 'EB'){
                    chart2 += '<label>统计类别</label>'
                        +'<select id="countDataType" class="form-control" style="width:150px;">'
                        +'<option value="all">全部</option>'
                        +'<option value="inbox">站内信</option>'
                        +'<option value="return">退款退货</option>'
                        +'<option value="inquiry">未收到纠纷</option>'
                        +'<option value="cancellation">取消交易</option>'
                        +'<option value="feedback">评价</option>'
                        +'</select>';
                    head += '<th></th>'
                        +'<th colspan="2">总计/完成率</th>'
                        +'<th colspan="4">邮件</th>'
                        +'<th colspan="4">退款退货</th>'
                        +'<th colspan="4">未收到物品</th>'
                        +'<th colspan="4">取消交易</th>'
                        +'<th colspan="4">评价</th>';

                }else{
                    chart2 += '<label>统计类别</label>'
                        +'<select id="countDataType" class="form-control" style="width:150px;">'
                        +'<option value="all">全部</option>'
                        +'<option value="inbox">站内信</option>'
                        +'<option value="return">物流纠纷</option>'
                        +'<option value="inquiry">买家原因纠纷</option>'
                        +'<option value="cancellation">质量纠纷</option>'
                        +'<option value="feedback">评价</option>'
                        +'</select>';

                    head += '<th></th>'
                        +'<th colspan="2">总计/完成率</th>'
                        +'<th colspan="4">邮件</th>'
                        +'<th colspan="4">物流纠纷</th>'
                        +'<th colspan="4">买家原因纠纷</th>'
                        +'<th colspan="4">质量纠纷</th>'
                        +'<th colspan="4">评价</th>';
                }

                $("#chart1").empty();
                $("#chart2").html(chart2);
                $("#head_one").empty();
                $("#head_two").html(head);
                var dataType = $("#countDataType").val();
                var countCostum = $("#countCostum").val();
                flushCountChart(name, dataType, countCostum,platform_code);
            }
            return false;

        });
        //搜索
        $(".btn-primary").on("click", function () {
            var start_time = $('#start_time').val();
            var end_time = $('#end_time').val();
            var platform_code = $('#btn2').attr("data_value");
            if(!start_time || !end_time){
                alert('开始/结束时间都要填写');
                return false;
            }
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/reports/kefumailstatistics/data']);?>',
                data: {'start_time': start_time, 'end_time': end_time, 'platform_code': platform_code},
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
                   //搜索统计每个客服
                    var list = data.list;
                    var html = '';
                    $.each(list, function (i, v) {
                        html += '<tr>'+
                            '<td name="'+i+'"><a id="detail" style="color:#029be5;" target="_blank" href="/reports/kefumailstatistics/index?user_name='+i+'">'+i+'</a></td>'+
                            '<td>'+v['total']+'</td>'+
                            '<td>'+v['completion_rate']+'</td>'+
                            '<td>'+v['mail_list']+'</td>'+
                            '<td>'+v['mail_end_list']+'</td>'+
                            '<td>'+v['mail_not_list']+'<span>('+v['mail_not_percent']+')</span></td>'+
                            '<td>'+v['mail_wait_list']+'</td>'+
                            '<td>'+v['return_list']+'</td>'+
                            '<td>'+v['return_end_list']+'</td>'+
                            '<td>'+v['return_not_list']+'<span>('+v['return_not_percent']+')</span></td>'+
                            '<td>'+v['return_wait_list']+'</td>'+
                            '<td>'+v['inqurry_list']+'</td>'+
                            '<td>'+v['inqurry_end_list']+'</td>'+
                            '<td>'+v['inqurry_not_list']+'<span>('+v['inqurry_not_percent']+')</span></td>'+
                            '<td>'+v['inqurry_wait_list']+'</td>'+
                            '<td>'+v['cancellation_list']+'</td>'+
                            '<td>'+v['cancellation_end_list']+'</td>'+
                            '<td>'+v['cancellation_not_list']+'<span>('+v['cancellation_not_percent']+')</span></td>'+
                            '<td>'+v['cancellation_wait_list']+'</td>'+
                            '<td>'+v['feedback_list']+'</td>'+
                            '<td>'+v['feedback_end_list']+'</td>'+
                            '<td>'+v['feedback_not_list']+'<span>('+v['feedback_not_percent']+')</span></td>'+
                            '<td>'+v['feedback_wait_list']+'</td>'+
                            '</tr>';
                    });
                    $('.pagedate').html(html);
                }
            });
        });
        function flushCountChart(date, dataType, countCostum, platform_code) {
            $.post("<?php echo Url::toRoute(['/reports/kefumailstatistics/chart']); ?>", {
                "date": date,
                "dataType": dataType,
                "countCostum": countCostum,
                "platform_code": platform_code,
            }, function (data) {
                if (data["code"] == 1) {
                    var user_list = data['userList'];
                    var htm = '';
                    $.each(user_list, function (n, value) {
                        htm += '<option value=' + n + '>' + value + '</option>';
                    });
                    $("#user_name_kefu").empty();
                    $("#user_name_kefu").append(htm);
                    $("#countCostum").empty();
                    $("#countCostum").append(htm);
                    option.xAxis.categories = data["data"]["categories"];
                    option.series = data["data"]["series"];
                    var chart = Highcharts.chart('countChartCon', option);
                }
            }, "json");
        }
         //趋势图、总计切换
        $(".selCountType span").on("click", function () {
            var id = $(this).parent("li").attr('class');
            if(id != 'selected'){
                $(this).parent("li").attr('class','selected');
                $(this).parent("li").siblings("li").removeAttr("class");
            }

            var date = $(".selDayItem > a[id='btn1']").attr("name");
            var platform_code = $('#btn2').attr("data_value");

            var chart2 = '';
            if(platform_code == 'EB'){
                chart2 += '<label>统计类别</label>'
                    +'<select id="countDataType" class="form-control" style="width:150px;">'
                    +'<option value="all">全部</option>'
                    +'<option value="inbox">站内信</option>'
                    +'<option value="return">退款退货</option>'
                    +'<option value="inquiry">未收到纠纷</option>'
                    +'<option value="cancellation">取消交易</option>'
                    +'<option value="feedback">评价</option>'
                    +'</select>';
            }else{
                chart2 += '<label>统计类别</label>'
                    +'<select id="countDataType" class="form-control" style="width:150px;">'
                    +'<option value="all">全部</option>'
                    +'<option value="inbox">站内信</option>'
                    +'<option value="return">物流纠纷</option>'
                    +'<option value="inquiry">买家原因纠纷</option>'
                    +'<option value="cancellation">质量纠纷</option>'
                    +'<option value="feedback">评价</option>'
                    +'</select>';
            }

            $("#chart1").empty();
            $("#chart2").html(chart2);
            var dataType = $("#countDataType").val();
            var countCostum = $("#countCostum").val();
            flushCountChart(date, dataType, countCostum,platform_code);

            if($(this).attr("class") == "selCountChart"){
                $("#countChart").css('display','block');
                $("#countTable").css('display','none');
            }else{
                $("#countChart").css('display','none');
                $("#countTable").css('display','block');
            }

        });

      /*  $("#countDataType").on("change", function () {
            var date = $(".selDayItem > span[id='btn1']").attr("name");
            var dataType = $(this).val();
            var countCostum = $("#countCostum").val();
            flushCountChart(date, dataType, countCostum);
        });*/
        $(".search").on("click",function(){
            var date = $(".selDayItem > a[id='btn1']").attr("name");
            var dataType = $("#countDataType").val();
            var countCostum = $("#countCostum").val();
            var platform_code = $('#btn2').attr("data_value");
            flushCountChart(date, dataType, countCostum, platform_code);
        });
        
        $("#user_name_kefu").on("change",function(){
            var user_name = $(this).val();
            var url = '/reports/kefumailstatistics/index?user_name='+user_name;
            window.open(url);
        });
    });
</script>