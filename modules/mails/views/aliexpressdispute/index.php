<?php

use app\components\GridView;
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\datetime\DateTimePicker;

$this->title = '速卖通纠纷';
DateTimePicker::widget(['name' => 'load']);
?>
<style>
    .select2-container--krajee {
        min-width: 120px;
    }

    #addHolidayResponseTimeLine {
        margin-bottom: 10px;
    }

    #hideHolidayResponseTimeLine {
        display: none;
    }

    #updateIssueInfoOverlay, #batchRefuseInfoOverlay {
        display: none;
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0px;
        left: 0px;
        z-index: 9999;
        filter: alpha(opacity=60);
        background-color: #333;
        opacity: 0.6;
        -moz-opacity: 0.6;
    }

    #updateIssueInfoSpeed, #batchRefuseInfoSpeed {
        position: absolute;
        width: 480px;
        height: 360px;
        top: 50%;
        left: 50%;
        margin-left: -240px;
        margin-top: -180px;
        z-index: 10000;
        overflow-y: auto;
    }

    #updateIssueInfoSpeed p.success, #batchRefuseInfoSpeed p.success {
        line-height: 30px;
        color: #5cb85c;
        font-size: 20px;
        font-weight: bold;
    }

    #updateIssueInfoSpeed p.error, #batchRefuseInfoSpeed p.error {
        line-height: 30px;
        color: #d9534f;
        font-size: 20px;
        font-weight: bold;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'pager' => [],
                'columns' => [
                    [
                        'field' => '_state',
                        'type' => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field' => 'account_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'order_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'platform_order_id',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' =>
                            [
                                'align' => 'center',
                            ],
                    ],
                    [
                        'field' => 'buyer_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'platform_dispute_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                            'class' => 'issue_id',
                        ],
                    ],
                    [
                        'field' => 'issue_status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'is_handle',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'finish_info',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                            'style' => ['min-width' => '145px']
                        ],
                    ],
                    [
                        'field' => 'gmt_create',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'reason_chinese',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'reason_english',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'issue_reponse_last_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'refuse_issue_last_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'hrefOperateButton',
                        'text' => '处理',
                        'href' => Url::toRoute(['/mails/aliexpressdispute/handle']),
                        'buttons' => [
                            [
                                'text' => '详情',
                                'href' => Url::toRoute('/mails/aliexpressdispute/details'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '100%',
                                    '_height' => '100%',
                                ],
                            ]
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                        ]
                    ]
                ],
                'toolBars' => [
                    [
                        'href' => '#',
                        'text' => '批量更新纠纷信息',
                        'htmlOptions' => [
                            'id' => 'batchUpdateIssueInfo',
                            'class' => 'btn btn-primary',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => '添加节假日响应时间',
                        'htmlOptions' => [
                            'id' => 'addHolidayResponseTime',
                            'class' => 'btn btn-primary',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => '批量拒绝买家方案',
                        'htmlOptions' => [
                            'id' => 'batchRefuseBuyerSolution',
                            'class' => 'btn btn-danger',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/mails/aliexpressdispute/batchhandle'),
                        'text' => '标记已处理',
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                            'confirm' => '确定标记为已处理吗？',
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>

<div class="modal fade in" id="addHolidayResponseTimeModal" tabindex="-1" role="dialog" aria-labelledby="addHolidayResponseTimeLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">配置节假日纠纷响应时间</h4>
            </div>
            <div class="modal-body" style="max-height:640px;overflow-y:auto;">
                <button type="button" class="btn btn-primary" id="addHolidayResponseTimeLine">新增</button>

                <table class="table table-bordered" style="table-layout:fixed;">
                    <thead>
                    <tr>
                        <th>时间</th>
                        <th>天数配置</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody id="holidayResponseTimeBody">
                    <tr id="hideHolidayResponseTimeLine">
                        <td>
                            开始时间：
                            <input type="text" class="form-control" name="start_time">
                            结束时间：
                            <input type="text" class="form-control" name="end_time">
                        </td>
                        <td>
                            纠纷响应时间：
                            <div class="input-group">
                                <input type="text" class="form-control" name="issue_reponse_day">
                                <span class="input-group-addon">天</span>
                            </div>

                            拒绝纠纷上升仲裁时间：
                            <div class="input-group">
                                <input type="text" class="form-control" name="refuse_issue_day">
                                <span class="input-group-addon">天</span>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary saveHolidayResponseTime" data-id="">保存</button>
                            <button type="button" class="btn btn-danger delHolidayResponseTime" data-id="">删除</button>
                        </td>
                    </tr>

                    <?php if (!empty($timeList)) { ?>
                        <?php foreach ($timeList as $time) { ?>
                            <tr>
                                <td>
                                    开始时间：
                                    <input type="text" class="form-control" name="start_time" value="<?php echo $time['start_time']; ?>">
                                    结束时间：
                                    <input type="text" class="form-control" name="end_time" value="<?php echo $time['end_time']; ?>">
                                </td>
                                <td>
                                    纠纷响应时间：
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="issue_reponse_day" value="<?php echo $time['issue_reponse_day'] ?>">
                                        <span class="input-group-addon">天</span>
                                    </div>

                                    拒绝纠纷上升仲裁时间：
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="refuse_issue_day" value="<?php echo $time['refuse_issue_day'] ?>">
                                        <span class="input-group-addon">天</span>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary saveHolidayResponseTime" data-id="<?php echo $time['id']; ?>">保存</button>
                                    <button type="button" class="btn btn-danger delHolidayResponseTime" data-id="<?php echo $time['id']; ?>">删除</button>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id='updateIssueInfoOverlay'>
    <div id='updateIssueInfoSpeed'></div>
</div>

<div id='batchRefuseInfoOverlay'>
    <div id='batchRefuseInfoSpeed'></div>
</div>

<div class="modal fade in" id="batchRefuseBuyerSolutionModal" tabindex="-1" role="dialog" aria-labelledby="batchRefuseBuyerSolutionModalLabel"
     style="top:300px;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">拒绝并添加协商方案</h4>
            </div>
            <div class="modal-body">
                <form id="batchRefuseBuyerSolutionForm" class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <span style="color:red">* 注意：若您只想保留一个方案，请直接修改原有方案</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">方案类型：</label>
                        <div class="col-sm-10">
                            <input type="radio" name="add_solution_type" value="refund" checked>退款
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">退款金款：</label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <span class="input-group-addon">USD</span>
                                <input type="text" class="form-control" name="refund_amount">
                                <input type="hidden" name="refund_amount_currency" value="USD">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">描述问题：</label>
                        <div class="col-sm-10">
                            <textarea name="solution_context" class="form-control" rows="6"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="batchRefuseBuyerSolutionBtn">批量拒绝</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        //处理纠纷响应剩余时间
        function flushIssueReponseLastTime() {
            $("span.issue_reponse_last_time").each(function () {
                var end_time = $(this).attr("data-endtime");
                if (end_time && end_time.length != 0) {
                    //结束时间
                    var end = new Date(end_time);
                    //当前时间(注意美国太平洋标准时间PST)
                    var now = new Date(new Date().getTime() - 15 * 3600 * 1000);
                    //结束时间减去当前时间剩余的毫秒数
                    var leftTime = end.getTime() - now.getTime();
                    //计算剩余的天数
                    var days = parseInt(leftTime / 1000 / 60 / 60 / 24, 10);
                    //计算剩余的小时
                    var hours = parseInt(leftTime / 1000 / 60 / 60 % 24, 10);
                    //计算剩余的分钟
                    var minutes = parseInt(leftTime / 1000 / 60 % 60, 10);
                    //计算剩余的秒数
                    var seconds = parseInt(leftTime / 1000 % 60, 10);

                    days = days ? days + '天' : '';
                    hours = hours ? hours + '时' : (days && (hours || minutes || seconds) ? '0时' : '');
                    minutes = minutes ? minutes + '分' : (hours && seconds ? '0分' : '');
                    seconds = seconds ? seconds + '秒' : '';
                    $(this).text(days + hours + minutes + seconds);
                }
            });
        }

        setInterval(flushIssueReponseLastTime, 1000);

        //已拒绝纠纷上升仲裁剩余时间
        function flushRefuseIssueLastTime() {
            var end_time = $(this).attr("data-endtime");
            if (end_time && end_time.length != 0) {
                //结束时间
                var end = new Date(end_time);
                //当前时间(注意美国太平洋标准时间PST)
                var now = new Date(new Date().getTime() - 15 * 3600 * 1000);
                //结束时间减去当前时间剩余的毫秒数
                var leftTime = end.getTime() - now.getTime();
                //计算剩余的天数
                var days = parseInt(leftTime / 1000 / 60 / 60 / 24, 10);
                //计算剩余的小时
                var hours = parseInt(leftTime / 1000 / 60 / 60 % 24, 10);
                //计算剩余的分钟
                var minutes = parseInt(leftTime / 1000 / 60 % 60, 10);
                //计算剩余的秒数
                var seconds = parseInt(leftTime / 1000 % 60, 10);

                days = days ? days + '天' : '';
                hours = hours ? hours + '时' : (days && (hours || minutes || seconds) ? '0时' : '');
                minutes = minutes ? minutes + '分' : (hours && seconds ? '0分' : '');
                seconds = seconds ? seconds + '秒' : '';
                $(this).text(days + hours + minutes + seconds);
            }
        }

        setInterval(flushRefuseIssueLastTime, 1000);

        $("#batchUpdateIssueInfo").on("click", function () {
            var dataSrc = $(this).attr('data-src');
            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length == 0) {
                layer.alert("请选择更新项");
                return false;
            }

            var defer = $.Deferred();
            defer.resolve($("#updateIssueInfoSpeed").html("<p class='success'>纠纷信息更新开始</p>"));
            $("#updateIssueInfoOverlay").css("display", "block");
            $("body").css("overflow", "hidden");

            checkBox.each(function () {
                //获取当前行的纠纷ID
                var issueId = $(this).parents("tr").find("td.issue_id").text();
                defer = defer.then(function () {
                    return $.ajax({
                        type: "POST",
                        url: "<?php echo Url::toRoute(['/mails/aliexpressdispute/updateissueinfo']); ?>",
                        data: {"issue_id": issueId},
                        dataType: "json",
                        global: false,
                        success: function (data) {
                            if (data["code"] == 1) {
                                $("#updateIssueInfoSpeed").append("<p class='success'>纠纷ID：" + data["data"]["issue_id"] + ",更新成功</p>");
                            } else {
                                $("#updateIssueInfoSpeed").append("<p class='error'>纠纷ID：" + data["data"]["issue_id"] + "," + data["message"] + "</p>");
                            }
                        }
                    });
                });
            });

            defer.done(function () {
                $("#updateIssueInfoSpeed").append("<p class='success'>纠纷信息更新完毕</p>");
                setTimeout(function () {
                    $("#updateIssueInfoOverlay").css("display", "none");
                    window.location.href = "<?php echo Url::toRoute(['/mails/aliexpressdispute/list']); ?>";
                }, 500);
            });
            return false;
        });

        //显示添加节假日响应时间弹窗
        $("#addHolidayResponseTime").on("click", function () {
            $("#addHolidayResponseTimeModal").modal("show");
        });

        //新增节假日纠纷响应时间
        $("#addHolidayResponseTimeLine").on("click", function () {
            var line = $("#hideHolidayResponseTimeLine").clone();
            line.removeAttr("id");
            line.find("input[name='start_time'],input[name='end_time']").datetimepicker({
                "autoclose": true,
                "format": "yyyy-mm-dd hh:ii:ss",
                "todayHighlight": true,
                "todayBtn": "linked",
                "timezone": "Asia/Shanghai"
            });
            $("#holidayResponseTimeBody").append(line);
        });

        //保存节假日纠纷响应时间
        $("#holidayResponseTimeBody").on("click", ".saveHolidayResponseTime", function () {
            var id = $(this).attr("data-id");
            var tr = $(this).parents("tr");

            var start_time = tr.find("input[name='start_time']").val();
            var end_time = tr.find("input[name='end_time']").val();
            var issue_reponse_day = tr.find("input[name='issue_reponse_day']").val();
            var refuse_issue_day = tr.find("input[name='refuse_issue_day']").val();

            if (start_time.length == 0) {
                layer.alert("开始时间不能为空");
                return false;
            }
            if (end_time.length == 0) {
                layer.alert("结束时间不能为空");
                return false;
            }
            if (issue_reponse_day.length == 0) {
                layer.alert("纠纷响应时间不能为空");
                return false;
            }
            if (refuse_issue_day.length == 0) {
                layer.alert("拒绝纠纷上升仲裁时间不能为空");
                return false;
            }

            $.post("<?php echo Url::toRoute(['/mails/aliexpressdispute/saveholidayresponsetime']) ?>", {
                "id": id,
                "start_time": start_time,
                "end_time": end_time,
                "issue_reponse_day": issue_reponse_day,
                "refuse_issue_day": refuse_issue_day,
            }, function (data) {
                if (data["code"] == 1) {
                    tr.find(".saveHolidayResponseTime").attr("data-id", data["data"]["id"]);
                    tr.find(".delHolidayResponseTime").attr("data-id", data["data"]["id"]);
                    layer.alert("保存成功");
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
            return false;
        });

        //删除节假日纠纷响应时间
        $("#holidayResponseTimeBody").on("click", ".delHolidayResponseTime", function () {
            var id = $(this).attr("data-id");
            var tr = $(this).parents("tr");

            if (id.length == 0 || id == "" || id == 0) {
                tr.remove();
            }

            $.post("<?php echo Url::toRoute(['/mails/aliexpressdispute/delholidayresponsetime']) ?>", {
                "id": id,
            }, function (data) {
                if (data["code"] == 1) {
                    tr.remove();
                    layer.alert("删除成功");
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
            return false;
        });

        //批量拒绝买家方案
        $("#batchRefuseBuyerSolution").on("click", function () {
            var checkBox = $("input[name='id']:checked");
            if (checkBox.length == 0) {
                layer.alert("请选择拒绝项");
                return false;
            }
            $("#batchRefuseBuyerSolutionModal").modal("show");
            return false;
        });

        $("#batchRefuseBuyerSolutionModal").on('hidden.bs.modal', function (e) {
            $("#batchRefuseBuyerSolutionForm")[0].reset();
        });

        $("#batchRefuseBuyerSolutionBtn").on("click", function () {
            var checkBox = $("input[name='id']:checked");
            var add_solution_type = $("input[name='add_solution_type']").val();
            var refund_amount = $("input[name='refund_amount']").val();
            var solution_context = $("textarea[name='solution_context']").val();

            if (checkBox.length == 0) {
                layer.alert("请选择拒绝项");
                return false;
            }
            if (add_solution_type.length == 0) {
                layer.alert("请选择方案类型");
                return false;
            }
            if (refund_amount.length == 0) {
                layer.alert("请填写退款金款");
                return false;
            }
            refund_amount = parseFloat(refund_amount);
            if (isNaN(refund_amount)) {
                layer.alert("请填写正确退款金款");
                return false;
            }
            if (refund_amount < 0) {
                layer.alert("退款金款不能小于0");
                return false;
            }
            if (solution_context.length == 0) {
                layer.alert("请填写描述问题");
                return false;
            }

            var params = $("#batchRefuseBuyerSolutionForm").serialize();

            var defer = $.Deferred();
            defer.resolve($("#batchRefuseInfoSpeed").html("<p class='success'>批量拒绝买家方案开始</p>"));
            $("#batchRefuseInfoOverlay").css("display", "block");
            $("body").css("overflow", "hidden");

            checkBox.each(function () {
                //获取当前行的纠纷ID
                var issueId = $(this).parents("tr").find("td.issue_id").text();
                params += "&issue_id=" + issueId;
                defer = defer.then(function () {
                    return $.ajax({
                        type: "POST",
                        url: "<?php echo Url::toRoute(['/mails/aliexpressdispute/batchrefusebuyersolution']); ?>",
                        data: params,
                        dataType: "json",
                        global: false,
                        success: function (data) {
                            if (data["code"] == 1) {
                                $("#batchRefuseInfoSpeed").append("<p class='success'>纠纷ID：" + data["data"]["issue_id"] + ",拒绝成功</p>");
                            } else {
                                $("#batchRefuseInfoSpeed").append("<p class='error'>纠纷ID：" + data["data"]["issue_id"] + "," + data["message"] + "</p>");
                            }
                        }
                    });
                });
            });

            defer.done(function () {
                $("#batchRefuseInfoSpeed").append("<p class='success'>批量拒绝买家方案完毕</p>");
                setTimeout(function () {
                    $("#batchRefuseInfoOverlay").css("display", "none");
                    window.location.href = "<?php echo Url::toRoute(['/mails/aliexpressdispute/list']); ?>";
                }, 500);
            });
            return false;
        });
    });
</script>
