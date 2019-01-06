<?php

use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
use yii\widgets\LinkPager;

$this->title = '补收款列表';
?>
<style>
    .select2-container--krajee {
        min-width: 155px !important;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="well">
                <form id="search-form" class="form-horizontal" action="<?php echo \Yii::$app->request->getUrl(); ?>"
                      method="get" role="form">
                    <input type="hidden" name="sortBy" value="">
                    <input type="hidden" name="sortOrder" value="">
                    <ul class="list-inline">
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">所属平台</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="platform_code">
                                        <?php foreach ($platformList as $code => $value) { ?>
                                            <option value="<?php echo $code; ?>" <?php if ($code == $platformCode) echo 'selected="selected"'; ?>><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">平台账号</label>
                                <div class="col-lg-7">
                                    <?php echo \kartik\select2\Select2::widget([
                                        'name'    => 'account_id',
                                        'value'   => $account_id,
                                        'data'    => $ImportPeople_list,
                                        'options' => ['placeholder' => '请选择...']
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">订单号</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="order_id" style="width:150px"
                                           value="<?php echo $order_id; ?>">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">客户id</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="buyer_id" style="width:150px"
                                           value="<?php echo $buyer_id; ?>">
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: -40px;">
                            <div class="form-group" style="width:400px">
                                <label style="width:150px" class="control-label col-lg-5" for="">申请时间</label>
                                <?php
                                echo DateTimePicker::widget([
                                    'name'          => 'begin_date',
                                    'options'       => ['placeholder' => ''],
                                    'value'         => $begin_date,
                                    'pluginOptions' => [
                                        'autoclose'      => true,
                                        'format'         => 'yyyy-mm-dd hh:ii:ss',
                                        'todayHighlight' => true,
                                        'todayBtn'       => 'linked',
                                    ],

                                ]); ?>
                            </div>
                        </li>
                        <li style="margin-left: -30px;">
                            <div class="form-group" style="width:400px">
                                <label style="width:150px" class="control-label col-lg-5" for="">结束时间</label>
                                <?php
                                echo DateTimePicker::widget([
                                    'name'          => 'end_date',
                                    'options'       => ['placeholder' => ''],
                                    'value'         => $end_date,
                                    'pluginOptions' => [
                                        'autoclose'      => true,
                                        'format'         => 'yyyy-mm-dd hh:ii:ss',
                                        'todayHighlight' => true,
                                    ]
                                ]); ?>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">申请人</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="creater" style="width:150px"
                                           value="<?php echo $creater; ?>">
                                </div>
                            </div>
                        </li>
                    </ul>
                    <button type="submit" class="btn btn-primary">搜索</button>
                </form>
            </div>
        </div>
        <div class="bs-bars pull-left" style="padding-top: 7px;">
            共<?php if ($count) {
                echo $count;
            } else {
                echo 0;
            }; ?>条数据&nbsp;
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <button class="batch-reply btn btn-default" id="batchDelete" data-src="id"><span>批量删除</span></button>
            </div>
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class="btn btn-success" id="download" target="_blank">下载数据</a>
            </div>
        </div>
        <!--        <div class="bs-bars pull-left">-->
        <!--            <div id="" class="btn-group">-->
        <!--                <button class="batchAudit btn btn-default" id="batchAudit" data-src="id"><span>审核</span></button>-->
        <!--            </div>-->
        <!--        </div>-->
        <table class="table table-striped table-bordered">

            <tr>
                <td><input type="checkbox" id="all" class="all"></td>
                <td>平台</td>
                <td>申请人/申请日期</td>
                <td>更新人/更新日期</td>
                <td>平台账号</td>
                <td>买家ID</td>
                <td>订单号</td>
                <!--                <td>paypal账号id</td>-->
                <td>paypal账号</td>
                <td>交易号</td>
                <td>补收款币种</td>
                <td>补收款金额</td>
                <!--                <td>审核人/审核日期</td>-->
                <!--                <td>审核状态</td>-->
                <!--                <td>审核备注</td>-->
                <td>收款方式</td>
                <td>收款原因</td>
                <td>操作</td>
            </tr>

            <?php if (!empty($receipts)) { ?>
                <?php foreach ($receipts as $item) { ?>
                    <tr>
                        <td>
                            <input name="" type="checkbox" value="<?= $item['after_sale_receipt_id']; ?>" class="sel ">
                        </td>
                        <td><?php echo $item['platform_code']; ?></td>
                        <td>
                            <?= $item['creater'] ?><br>
                            <?= $item['created_time'] ?>
                        </td>
                        <td>
                            <?= $item['modifier'] ?><br>
                            <?= $item['modified_time'] ?>
                        </td>
                        <td><?= \app\modules\accounts\models\Account::getAccountNameByOldAccountId($item['account_id'], $item['platform_code']) ?></td>
                        <td><?= $item['buyer_id'] ?></td>
                        <td><?= $item['order_id'] ?></td>
                        <!--                        <td>--><? //= $item['paypal_account_id'] ?><!--</td>-->
                        <td><?= $item['paypal_account'] ?></td>
                        <td><?= $item['transaction_id'] ?></td>
                        <td><?= $item['receipt_currency'] ?></td>
                        <td><?= $item['receipt_money'] ?></td>
                        <!--                        <td>-->
                        <!--                            --><? //= $item['auditer'] ?><!--<br>-->
                        <!--                            --><? //= $item['audit_time'] ?>
                        <!--                        </td>-->
                        <!--                        <td>--><?php //if ($item['audit_status'] == 1) {
                        //                                echo '未审核';
                        //                            } elseif ($item['audit_status'] == 2) {
                        //                                echo '审核通过';
                        //                            } else {
                        //                                echo '审核失败';
                        //                            } ?><!--</td>-->
                        <!--                        <td>--><? //= $item['audit_remark'] ?><!--</td>-->
                        <td><?php if ($item['receipt_type'] == 1) {
                                echo 'paypal收款';
                            } elseif ($item['receipt_type'] == 2) {
                                echo '线下收款';
                            } ?></td>
                        <td>
                            <?php if ($item['receipt_reason_type'] == 1) {
                                echo '收到退回';
                            } elseif ($item['receipt_reason_type'] == 2) {
                                echo '加钱重寄';
                            } elseif ($item['receipt_reason_type'] == 3) {
                                echo '假重寄';
                            } else {
                                echo '其他';
                            } ?>

                        </td>
                        <td>
                            <div class="btn-group btn-list">
                                <button type="button"
                                        class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation'); ?></button>
                                <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                        data-toggle="dropdown">
                                    <span class="caret"></span>
                                    <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                </button>
                                <ul class="dropdown-menu" rol="menu">
                                    <li>
                                        <a _width="90%" _height="75%" class="edit-button"
                                           href="<?php echo Url::toRoute(['/aftersales/sales/editreceipt', 'after_sale_receipt_id' => $item['after_sale_receipt_id']]); ?>">编辑</a>
                                    </li>
                                    <li><a style=" cursor: pointer;"
                                           onclick="del(<?php echo $item['after_sale_receipt_id']; ?>)">删除</a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="14">暂无</td>
                </tr>
            <?php } ?>
        </table>
        <?php echo LinkPager::widget([
            'pagination' => $page,
        ]); ?>
    </div>
</div>

<!--批量审核-->
<div class="modal fade in" id="batchsendMsgModal" tabindex="-1" role="dialog" aria-labelledby="sendMsgModalLabel"
     style="top:300px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">审核收款单</h4>
            </div>
            <div class="modal-body">
                <form id="sendMsgFormBatch">
                    <div class="row">
                        <div class="col col-lg-12">
                            <select class="form-control" name="audit_type">
                                <option value="">请选择</option>
                                <option value="2">审核通过</option>
                                <option value="3">审核未通过</option>
                            </select>
                        </div>
                        <div class="col col-lg-12">
                            <textarea class="form-control" rows="5" id="audit_remark" placeholder="审核备注"
                                      name="audit_remark"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="sendMsgBtnBatch">审核</button>
                <button type="button" class="btn btn-default" id="closeModel" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<script>
    //checkbox选择
    $(".all").bind("click",
        function () {
            $(".sel").prop("checked", $(this).prop("checked"));
        });
    $(".sel").bind("click",
        function () {
            var $sel = $(".sel");
            var b = true;
            for (var i = 0; i < $sel.length; i++) {
                if ($sel[i].checked == false) {
                    b = false;
                    break;
                }
            }
            $(".all").prop("checked", b);
        });
    //删除
    $("#batchDelete").bind("click", function () {
        //账号ID&站点&客户邮件&平台订单id 组合一个字符串
        var ids = '';
        $(":checked.sel").each(function () {
            if (ids == '') {
                if ($(this).prop('checked') == true) {
                    ids = $(this).val();
                }
            }
            else {
                if ($(this).prop('checked') == true) {
                    ids += ',' + $(this).val();
                }
            }
        });

        if (ids == '') {
            layer.msg('请选择收款单!', {icon: 5});
            return;
        } else {

            layer.confirm('确定要批量删除数据吗？', {
                btn: ['确定', '取消'] //按钮
            }, function () {
                $.ajax({
                    url: "/aftersales/sales/deletereceipt",
                    type: "GET",
                    data: {"after_sale_receipt_id": ids},
                    dataType: "json",
                    success: function (data) {
                        if (data.code == 200) {
                            location.href = location.href;
                            layer.msg(data.message, {icon: 6});
                        } else {
                            layer.msg(data.msg, {icon: 5});
                            return;
                        }
                    }
                });
            })
        }
    });

    /**
     * 下载
     */
    $("#download").click(function () {
        //平台订单ID&账号ID&买家登陆ID 组合一个字符串
        var selectIds = '';
        $(":checked.sel").each(function () {
            if (selectIds == '') {
                if ($(this).prop('checked') == true) {
                    selectIds = $(this).val();
                }
            }
            else {
                if ($(this).prop('checked') == true) {
                    selectIds += ',' + $(this).val();
                }
            }
        });
        var platform_code = $('select[name=platform_code]').val();
        var account_id = $("select[name=account_id]").val();
        var order_id = $("input[name=order_id]").val();
        var buyer_id = $("input[name=buyer_id]").val();
        var begin_date = $("input[name='begin_date']").val();
        var end_date = $("input[name='end_date']").val();
        var url = '/aftersales/sales/downloadreceipt';
        //如果选中则只下载选中数据
        if (selectIds != "") {
            url += '?json=' + selectIds;
        } else {
            url += '?platform_code=' + platform_code + '&account_id=' + account_id
                + '&order_id=' + order_id + '&buyer_id=' + buyer_id + '&begin_date=' + begin_date + '&end_date=' + end_date;
        }
        window.open(url);
    });

    $(".batchAudit ").bind("click", function () {
        //平台订单ID&账号ID&买家登陆ID 组合一个字符串
        var ids = '';
        $(":checked.sel").each(function () {
            if (ids == '') {
                if ($(this).prop('checked') == true) {
                    ids = $(this).val()
                }
            }
            else {
                if ($(this).prop('checked') == true) {
                    ids += ',' + $(this).val()
                }
            }
        });

        if (ids == '') {
            layer.msg('选择审核的收款单', {icon: 5});
            return;
        } else {
            $("#batchsendMsgModal").modal("show");
            $("#sendMsgBtnBatch").click(function () {
                var audit_status = $("select[name='audit_type']").val();//选择的查询条件
                if (audit_status == '') {
                    layer.msg('请选择审核状态!', {icon: 5});
                    return;
                }
                if (audit_status == 3) {
                    //获取内容
                    var audit_remark = $("textarea[name=audit_remark]").val();
                    if (audit_remark == '') {
                        layer.msg('请输入审核未通过备注!', {icon: 5});
                        return;
                    }
                }
                $.post("<?php echo Url::toRoute(['/aftersales/sales/auditreceipt']); ?>",
                    {
                        'after_sale_receipt_id': ids,
                        "audit_status": audit_status,
                        'audit_remark': audit_remark
                    }, function (data) {
                        if (data.code == 200) {
                            location.href = location.href;
                            layer.msg(data.message, {icon: 6});
                            $("#batchsendMsgModal").modal("hide");
                        } else {
                            layer.msg(data.message, {icon: 5});
                            return;
                        }
                    }, "json");
                return false;
            });
        }
    });

    $("#closeModel").click(function () {
        //清空数据
        $("#sendMsgFormBatch textarea[name='audit_remark']").val("");
    });

    //单个删除
    function del(id) {
        layer.confirm('您确定要删除这条数据吗？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: "/aftersales/sales/deletereceipt",
                type: "GET",
                data: {"after_sale_receipt_id": id},
                dataType: "json",
                success: function (data) {
                    if (data.code == 200) {
                        location.href = location.href;
                        layer.msg(data.message, {time: 3000}, {icon: 6});
                    } else {
                        layer.msg(data.msg, {icon: 5});
                        return;
                    }
                }
            });
        })
    }


    $("select[name=platform_code]").change(function () {
        var platform_code = $(this).val();
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['getaccountbyplatformcode'])?>',
            data: {'platform_code': platform_code},
            success: function (data) {
                if (data.status == 'success') {
                    $("select[name=account_id]").empty();
                    var html = "";
                    html += '<option value="0">全部</option>';
                    $.each(data.data, function (n, value) {
                        html += '<option value="' + n + '">' + value + '</option>';
                    });
                    $("select[name=account_id]").append(html);
                } else {
                    layer.msg(data.message, {icon: 5, time: 10000});
                }
            }
        });
    });
</script>