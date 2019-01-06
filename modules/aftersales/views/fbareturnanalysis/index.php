<?php

use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use app\components\LinkPager;

$this->title = 'FBA退货分析列表';
?>
<style>
    .select2-container--krajee {
        min-width: 155px !important;
    }
</style>
<style>
    #addReplyFeedback {
        margin: 20px auto 0 auto;
        width: 90%;
        height: auto;
        border-collapse: collapse;
    }

    #addReplyFeedback td {
        border: 1px solid #ccc;
        padding: 10px;
    }

    #addReplyFeedback td.col1 {
        width: 120px;
        text-align: right;
        font-weight: bold;
    }

    #addReplyFeedback .glyphicon.glyphicon-star {
        color: #ff9900;
        font-size: 20px;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-24">
            <div class="well">
                <form id="search-form" class="form-horizontal" action="<?php echo \Yii::$app->request->getUrl(); ?>"
                      method="get" role="form">
                    <input type="hidden" name="sortBy" value="">
                    <input type="hidden" name="sortOrder" value="">
                    <div class="col-lg-12">
                        <div class="col-lg-3">
                            <div class="form-group"><label class="control-label col-lg-3" for="">sku</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" placeholder="支持亚马逊刊登sku、公司sku搜索" name="sku" style="width:280px" value="<?php echo $datas['sku']; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group"><label class="control-label col-lg-5" for="">品控姓名</label>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control" name="last_quality_control_user" style="width:150px" value="<?php echo $datas['lastQualityControlUser']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="form-group"><label class="control-label col-lg-7" for="">总退货趋势</label>
                                <div class="col-lg-5">
                                    <select class="form-control" name="return_trend">
                                        <option value="">全部</option>
                                        <option value="1" <?php if ($datas['returnTrend'] == 1) echo 'selected="selected"'; ?>>下降</option>
                                        <option value="2" <?php if ($datas['returnTrend'] == 2) echo 'selected="selected"'; ?>>上升</option>
                                        <option value="3" <?php if ($datas['returnTrend'] == 3) echo 'selected="selected"'; ?>>持平</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="form-group"><label class="control-label col-lg-7" for="">销量趋势</label>
                                <div class="col-lg-5">
                                    <select class="form-control" name="sales_trend">
                                        <option value="">全部</option>
                                        <option value="1" <?php if ($datas['salesTrend'] == 1) echo 'selected="selected"'; ?>>下降</option>
                                        <option value="2" <?php if ($datas['salesTrend'] == 2) echo 'selected="selected"'; ?>>上升</option>
                                        <option value="3" <?php if ($datas['salesTrend'] == 3) echo 'selected="selected"'; ?>>持平</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="col-lg-3">
                            <div class="col-lg-3">
                                <div class="" style="width:350px">
                                    <label class="control-label col-lg-3" for=""  style="width:100px" >总退货率</label>
                                    <select id="return_rate" class="form-control" name="return_rate" style="width:80px;float:left;">
                                        <option value="0"></option>
                                        <option value="3" <?php echo $datas['refundRate'] == 3 ? 'selected' : ""; ?>>3天</option>
                                        <option value="7" <?php echo $datas['refundRate'] == 7 ? 'selected' : ""; ?>>7天</option>
                                        <option value="15" <?php echo $datas['refundRate'] == 15 ? 'selected' : ""; ?>>15天</option>
                                        <option value="30" <?php echo $datas['refundRate'] == 30 ? 'selected' : ""; ?>>30天</option>
                                        <option value="60" <?php echo $datas['refundRate'] == 60 ? 'selected' : ""; ?>>60天</option>
                                        <option value="90" <?php echo $datas['refundRate'] == 90 ? 'selected' : ""; ?>>90天</option>
                                    </select>
                                    <!--<div>-->
                                    <input class="form-control return_rate_str" name="return_rate_str" style="width:70px;float: left;display: inline;"  type="text" value="<?php echo $datas['refundRateStr']; ?>"/>
                                    <label style="width:20px;float: left;text-align: center;line-height: 35px;" >～</label>
                                    <input class="form-control return_rate_end" name="return_rate_end" style="width:70px;float: left;display: inline;"  type="text" value="<?php echo $datas['refundRateStr']; ?>"/>
                                    <!--</div>-->
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="col-lg-3">
                                <div class="" style="width:320px">
                                    <label class="control-label col-lg-3" for=""  style="width:80px" >销售</label>
                                    <select id="sales" class="form-control" name="sales" style="width:80px;float:left;">
                                        <option value="0"></option>
                                        <option value="3" <?php echo $datas['sales'] == 3 ? 'selected' : ""; ?>>3天</option>
                                        <option value="7" <?php echo $datas['sales'] == 7 ? 'selected' : ""; ?>>7天</option>
                                        <option value="15" <?php echo $datas['sales'] == 15 ? 'selected' : ""; ?>>15天</option>
                                        <option value="30" <?php echo $datas['sales'] == 30 ? 'selected' : ""; ?>>30天</option>
                                        <option value="60" <?php echo $datas['sales'] == 60 ? 'selected' : ""; ?>>60天</option>
                                        <option value="90" <?php echo $datas['sales'] == 90 ? 'selected' : ""; ?>>90天</option>
                                    </select>
                                    <!--<div>-->
                                    <input class="form-control sales_str" name="sales_str" style="width:70px;float: left;display: inline;"  type="text" value="<?php echo $datas['salesStr']; ?>"/>
                                    <label style="width:20px;float: left;text-align: center;line-height: 35px;" >～</label>
                                    <input class="form-control sales_end" name="sales_end" style="width:70px;float: left;display: inline;"  type="text" value="<?php echo $datas['salesEnd']; ?>"/>
                                    <!--</div>-->
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-left:20px;">搜索</button>

                </form>
            </div>
        </div>
        <div class="bs-bars pull-left" style="padding-top: 7px;">
            共<?php
            if ($count) {
                echo $count;
            } else {
                echo 0;
            };
            ?>条数据&nbsp;
        </div>
        <table class="table table-striped table-bordered">

            <tr>
                <th>产品信息</th>
                <th>公司SKU状态</th>
                <th style="width:200px;">退货率</th>
                <th style="width:80px;">退货趋势</th>
                <th>销量</td>
                <th>销量趋势</th>
                <th>客户原因</th>
                <th>描述不符</th>
                <th>延迟派送</th>
                <th>产品质量问题</th>
                <th>包装问题</th>
                <th>数量短缺</th>
                <th>未收到</th>
                <th style="width:200px;">品控问题</th>
            </tr>
            <?php if (!empty($receipts)) { ?>
                <?php foreach ($receipts as $item) { ?>
                    <tr>
                        <td><?php echo $item['sku']; ?></td>
                        <td><?php echo $item['pro_status'];?></td>
                        <td><?php echo $item['refund_rate']; ?></td>
                        <td><?php echo $item['return_trend']; ?></td>
                        <td><?php echo $item['sales']; ?></td>
                        <td><?php echo $item['sales_trend']; ?></td>
                        <td><?php echo $item['customer']; ?></td>
                        <td><?php echo $item['description']; ?></td>
                        <td><?php echo $item['overtime']; ?></td>
                        <td><?php echo $item['quality']; ?></td>
                        <td><?php echo $item['packaging']; ?></td>
                        <td><?php echo $item['shortage']; ?></td>
                        <td><?php echo $item['not_received']; ?></td>
                        <td><?php echo $item['quality_control']; ?></td>
                    </tr>
                <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="11" style="text-align: center;">暂无数据</td>
                </tr>
        <?php } ?>
        </table>
<?php echo LinkPager::widget(['pagination' => $page,]); ?>
    </div>
</div>

<style>
    #myModal,#historyModal{top: 20%;}
    .table tr td{font-size:12px;}
    .table tr th{font-size:14px;font-weight: bold;text-align: center;}
</style>

<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal">
                    <div class="div_reason">
                        <div class="form-group">
                            <label for="ship_name" class="col-sm-2 control-label required">添加品控措施：<span class="text-danger">*</span></label>
                            <div class="col-md-10"><textarea class="form-control" rows="5" id="quality_control"></textarea></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="hide_id" id="hide_id" value=""/>
                <input type="hidden" name="type" id="type" value=""/>
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">取消</button>
                <button type="button" class="btn save btn-primary waves-effect waves-light">提交</button>
            </div>
        </div>
    </div>
</div>

<!--查看历史-->
<div id="historyModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
<!--            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>-->
            <div class="modal-body">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 style="margin-top:-10px; font-weight: bold;font-size: 14px; color: #000003;">查看品控历史</h4>
                            <div class="row">
                                <div class="col-12">
                                    <table class="table history-table table-striped">
                                        <thead>
                                            <tr>
                                                <th style="width: 80px;">操作人</th>
                                                <th style="width: 150px;">操作时间</th>
                                                <th>内容</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function () {
        $('#sales').change(function () {
            if ($(this).val() == 0) {
                $(".sales_str").val("");
                $(".sales_end").val("");
            }
        });

        $('#return_rate').change(function () {
            if ($(this).val() == 0) {
                $(".return_rate_str").val("");
                $(".return_rate_end").val("");
            }
        });
    });

    //第一次添加品控措施
    $(document).on('click', '.not-set', function () {
        var id = $(this).attr('data');//id
        $("#hide_id").val(id);
    });

    //新增品控措施
    $(document).on('click', '.add', function () {
        var id = $(this).attr('data');//id
        $("#hide_id").val(id);
    });
    
    //保存品控措施
    $(document).on('click', '.save', function () {
        var id = $("#hide_id").val();//Id
        var text = $("#quality_control").val();
        if (text == "") {
            layer.msg('请输入品控问题!', {icon: 5});
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['savequalitycontrol']); ?>',
            data: {
                'id': id,
                'text': text
            },
            success: function (data) {
                if (data.status) {
                    layer.msg(data.info, {icon: 1});
                    $("#myModal").modal('hide');
                    window.refreshTable("/aftersales/fbareturnanalysis/index");
                } else {
                    layer.msg(data.info, {icon: 5});
                }
            }
        });
        return false;
    });

    //获取品控信息详情
    function demol(e) {
        var remark = $(e).next('.remarkl').val();
        var classs = $(e).attr("class");
        layer.tips(remark, '.' + classs, {
            tips: [1, '#0FA6D8'] //还可配置颜色
        });
    }
    
    //查看品控历史
    $(document).on('click', '.view', function () {
        var id = $(this).attr('data');//id
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['viwhistory']); ?>',
            data: {'id': id},
            success: function (data) {
                $(".history-table tbody").empty("");
                var html = "";
                if (data) {
                    $.each(data,function(name,value) {
                        html += '<tr><td>'+value.user+'</td><td>'+value.time+'</td><td>'+value.text+'</td></tr>';
                    });
                } else {
                    html += '<tr><td colspan="3" style="text-align:center;">暂无历史记录</td></tr>';
                }
                $(".history-table tbody").append(html);
            }
        });
    });
    
    //查看品公司sku状态
    $(document).on('click', '.sku-status', function () {
        var id = $(this).attr('data');//id
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['skustatus']); ?>',
            data: {'id': id},
            success: function (data) {
                layer.tips(data, '.cla_'+id, {
                    tips: [4, '#78BA32']
                });
            }
        });
    });
   
</script>