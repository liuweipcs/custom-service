<?php

use app\components\GridView;
use yii\helpers\Url;

$platforms = \app\modules\accounts\models\Platform::getPlatformAsArray();
if (isset($platform_code) && isset($platforms[$platform_code])) {
    $this->title = $platforms[$platform_code];
}
$this->title .= '售后问题';
?>
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
                        'field' => 'state',
                        'type' => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field' => 'after_sale_id_text',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'order_id',
                        'type' => 'text',
                         'sortAble' => true,
                        'htmlOptions' => [
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
                        'field' => 'type_text',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'platform_code',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'department_text',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'reason_text',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'reason',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'audit_info',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'remark',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'refund_status_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'refund_amount_info',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],  
                    [
                        'field' => 'fail_reason',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],   
                    ],
                    [
                        'field' => 'create_info',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'edit_after_sales_order',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ]
                ],
                'toolBars' => [
                    [
                        'href' => Url::toRoute('/aftersales/sales/batchaudit?url=' . $url),
                        'text' => Yii::t('system', '批量审核'),
                        'htmlOptions' => [
                            'class' => 'delete-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/aftersales/sales/download'),
                        'text' => Yii::t('system', '下载数据'),
                        'htmlOptions' => [
                            'id' => 'download',
                            'target' => '_blank',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/aftersales/sales/refund'),
                        'text' => Yii::t('system', '标记退款完成'),
                        'htmlOptions' => [
                            'id' => 'refund',
                            'target' => '_blank',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/aftersales/sales/deleteall'),
                        'text' => Yii::t('system', '删除'),
                        'htmlOptions' => [
                            'id' => 'deleteall',
                            'target' => '_blank',
                        ]
                    ],
//                    [
//                        'href' => Url::toRoute('/aftersales/sales/Reversion'),
//                        'text' => Yii::t('system', '退回修改'),
//                        'htmlOptions' => [
//                            'id' => 'reversion',
//                            'target' => '_blank',
//                        ]
//                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('.input-group').attr('style', 'width:300px;');
    $("#reversion").click(function () {
        var statusText = $("select[name='status_text']").val();// 售后单审核状态
        var url = $(this).attr('href');
        if (statusText == "") {
            layer.msg('请选择售后单审核状态', {icon: 0});
            return false;
        }
        if (statusText != "1" || statusText != "3") {
            layer.msg('状态必须是待审核或是退回修改', {icon: 0});
            return false;
        }
        var selectIds = selectId = [];
        var selection = $("#grid-list").bootstrapTable('getAllSelections');
        for (var i = 0; i < selection.length; i++) {
            selectId.push(selection[i].id);
        }
        selectIds = selectId.join(',');
        if (selectIds == "") {
            layer.msg('请勾选数据', {icon: 0});
            return false;
        }
        layer.confirm('确定将数据删除？？', {
            title: "提示",
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: url,
                type: "get",
                data: {'statusText': statusText, "selectIds": selectIds},
                dataType: "json",
                success: function (e) {
                    if (e.state == 0) {
                        layer.msg(e.msg, {icon: 0});
                        return false;
                    } else {
                        layer.msg(e.msg, {icon: 1});
                        window.location.reload();
                    }
                },
                error: function () {
                    layer.msg('系统繁忙请稍后再试！！', {icon: 0});
                    return false;
                }
            });
        }, function () {

        });


        return false;


    });


    /***
     * 批量删除
     **/
    $("#deleteall").click(function () {
        var type = $("select[name='type']").val(); // 售后单类型 
        var time_type = $("select[name='time_type']").val();//时间类型
        var startTime = $("input[name='start_time']").val(); //开始时间
        var endTime = $("input[name='end_time']").val();//结束时间
        var url = $(this).attr('href');
        if (type == "") {
            layer.msg('请选择售后类型', {icon: 0});
            return false;
        }
        if (time_type == "") {
            layer.msg('请选择时间类型', {icon: 0});
            return false;
        }
        var myDate = new Date();
        var month = myDate.getMonth() + 1; //获取当前月份(0-11,0代表1月)
        var d = new Date(startTime);//开始时间
        var startTimed = d.getMonth() + 1;
        var ed = new Date(endTime);//结束时间
        var endTimeed = d.getMonth() + 1;

        if (type == '1') {
            if (time_type == '3') {
                if (month !== startTimed || month !== endTimeed) {
                    layer.msg('退款完成时间过了当月的数据不能删除', {icon: 0});
                    return false;
                }
            }
        }
        if (type == '3') {
            if (time_type == '2') {
                if (month !== startTimed || month !== endTimeed) {
                    layer.msg('审核通过时间过了当月数据不能删除', {icon: 0});
                    return false;
                }
            }
        }


        var selectIds = selectId = [];
        var selection = $("#grid-list").bootstrapTable('getAllSelections');
        for (var i = 0; i < selection.length; i++) {
            selectId.push(selection[i].id);
        }
        selectIds = selectId.join(',');
        if (selectIds == "") {
            layer.msg('请勾选数据', {icon: 0});
            return false;
        }
        layer.confirm('确定将数据删除？？', {
            title: "提示",
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: url,
                type: "get",
                data: {'type': type, 'time_type': time_type, "selectIds": selectIds, 'startTime': startTime, 'endTime': endTime},
                dataType: "json",
                success: function (e) {
                    if (e.state == 0) {
                        layer.msg(e.msg, {icon: 0});
                        return false;
                    } else {
                        layer.msg(e.msg, {icon: 1});
                        window.location.reload();
                    }
                },
                error: function () {
                    layer.msg('系统繁忙请稍后再试！！', {icon: 0});
                    return false;
                }
            });
        }, function () {

        });






        return false;
    });



    /**
     * 标记退款完成
     */
    $("#refund").click(function () {
        var type = $("select[name='type']").val(); // 售后单类型 1
        var refundStatus = $("select[name='refund_status']").val();//售后单退款状态，只有类型为退款失败的情况才有用 4
        var url = $(this).attr('href');
        if (type == "") {
            layer.msg('请选择售后类型', {icon: 0});
            return false;
        }
        if (type != '1') {
            layer.msg('请选择退款售后单', {icon: 0});
            return false;
        }
        if (refundStatus != '4') {
            layer.msg('只有退款失败的退款售后单才能标记', {icon: 0});
            return false;
        }
        var selectIds = selectId = [];
        var selection = $("#grid-list").bootstrapTable('getAllSelections');
        for (var i = 0; i < selection.length; i++) {
            selectId.push(selection[i].id);
        }
        selectIds = selectId.join(',');
        if (selectIds == "") {
            layer.msg('请勾选数据', {icon: 0});
            return false;
        }
        // url+='&json='+selectIds+"&type="+type+"&refundStatus="+refundStatus;
        layer.confirm('确定将数据标记退款完成？', {
            title: "提示",
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: url,
                type: "get",
                data: {'type': type, 'refundStatus': refundStatus, "selectIds": selectIds},
                dataType: "json",
                success: function (e) {
                    if (e.state == 0) {
                        layer.msg(e.msg, {icon: 0});
                        return false;
                    } else {
                        layer.msg(e.msg, {icon: 1});
                        window.location.reload();
                    }
                },
                error: function () {
                    layer.msg('系统繁忙请稍后再试！！', {icon: 0});
                    return false;
                }
            });
        }, function () {

        });





        return false;



    });



    /**
     * 下载数据相关操作
     */
    $("#download").click(function () {
        var json_data = "";
        var platformCode = $("select[name='platform_code']").val();//所属平台
        var afterSaleId = $("input[name='after_sale_id']").val();//售后单号
        var orderId = $("input[name='order_id']").val();//系统订单号
        var buyerId = $("input[name='buyer_id']").val();//买家ID
        var departmentId = $("select[name='department_id']").val();//责任归属部门id
        var reasonId = $("select[name='reason_id']").val();//售后原因ID
        var type = $("select[name='type']").val(); // 售后单类型
        var statusText = $("select[name='status_text']").val();// 售后单审核状态
        var refundStatus = $("select[name='refund_status']").val();//售后单退款状态，只有类型为退款的情况才有用
        var createBy = $("input[name='create_by']").val();//售后单创建人
        var time_type = $("select[name='time_type']").val();//时间类型
        var startTime = $("input[name='start_time']").val(); //开始时间
        var endTime = $("input[name='end_time']").val();//结束时间
        var sku = $("input[name=sku]").val();
        var return_status = $("input[name=return_status]").val();
        var rma = $("input[name=rma]").val();
        var tracking_no = $("input[name=tracking_no]").val();
        var url = $(this).attr('href');
        if (type == "") {
            layer.msg('请选择售后类型');
            return false;
        }
        if (startTime == "" || endTime == "") {
            layer.msg('开始时间/结束时间不能为空');
            return false;
        }
        var platform_code = "<?php echo $platform_code; ?>";
        url += '?platform_code=' + platform_code + '&time_type=' + time_type + '&type=' + type + '&startTime=' + startTime + '&endTime=' + endTime;

        var selectIds = selectId = [];
        var selection = $("#grid-list").bootstrapTable('getAllSelections');
        for (var i = 0; i < selection.length; i++) {
            selectId.push(selection[i].id);
        }
        selectIds = selectId.join(',');
        //如果选中则只下载选中数据
        if (selectIds != "") {
            url += '&json=' + selectIds;
        } else {
            url += '&platformCode=' + platformCode + '&afterSaleId=' + afterSaleId + '&orderId=' + orderId
                    + '&buyerId=' + buyerId + '&departmentId=' + departmentId + '&reasonId=' + reasonId
                    + '&statusText=' + statusText + '&refundStatus=' + refundStatus + '&createBy=' + createBy + '&sku=' + sku
                    + '&return_status=' + return_status + "&rma=" + rma + "&tracking_no=" + tracking_no;
        }
        window.open(url);
    });

    $(function () {
        var type = $("select[name=type]").val();
        if (type != 1) {
            $("select[name=refund_status]").attr('disabled', 'disabled');

        }
        if (type != 2) {
            $("select[name=return_status]").attr('disabled', 'disabled');
        }

        var platform_code = "<?php echo $platform_code; ?>";

        if (platform_code == undefined || platform_code == 'all') {
            $("input[name=sku]").attr('readonly', 'readonly');
        }
        //
        var time_type = $("select[name=time_type] option");
        //时间类型删除全部选项 默认
        if (type == '') {
            $("select[name=time_type] option:selected").remove();
            time_type.each(function (i, el) {
                if ($(el).val() == 1) {
                    $(this).attr("selected", true);
                }
            })
        }

    });

    $("select[name=type]").on('click', function () {
        var type = $("select[name=type]").val();
        if (type != 1) {
            $("select[name=refund_status]").attr('disabled', 'disabled');
            $("select[name=refund_status]").val('');
        } else {
            $("select[name=refund_status]").removeAttr('disabled');

        }
        if (type != 2) {
            $("select[name=return_status]").attr('disabled', 'disabled');
            $("select[name=return_status]").val('');
        } else {
            $("select[name=return_status]").removeAttr('disabled');

        }

        var time_type = $("select[name=time_type] option");
        //重寄单 3 隐藏 退款时间 审核时间选中
        if (type == 3) {
            //
            time_type.each(function (i, el) {
                if ($(el).val() == 3 || $(el).val() == 4) {
                    $(this).hide();
                    $(this).siblings().show();
                } else {
                    $(this).show();
                }
                if ($(el).val() == 2) {
                    $(this).attr("selected", true);
                    $(this).siblings().attr('selected', false);
                }
            })
        }
        if (type == 2) {
            time_type.each(function (i, el) {
                if ($(el).val() == 4) {
                    $(this).attr("selected", true);
                    $(this).siblings().attr('selected', false);
                }
                if ($(el).val() == 3) {
                    $(this).hide();
                    $(this).siblings().show();
                } else {
                    $(this).show();
                }
            })
        }
        if (type == 1) {
            //退款单 退款时间选中
            time_type.each(function (i, el) {
                if ($(el).val() == 3) {
                    $(this).attr("selected", true);
                    $(this).siblings().attr('selected', false);
                }
                if ($(el).val() == 4) {
                    $(this).hide();
                    $(this).siblings().show();
                } else {
                    $(this).show();
                }
            })
        }
        if (type == '') {
            time_type.each(function (i, el) {
                if ($(el).val() == 1) {
                    $(this).attr("selected", true);
                    $(this).siblings().attr('selected', false);
                }
                if ($(el).val() == 3) {
                    $(this).show();
                }
            })
        }

    });


    $("select[name=platform_code]").on('change', function () {
        var platform_code = $("select[name=platform_code]").val();
        if (platform_code == '' || platform_code == undefined) {
            $("input[name=sku]").val('');
            $("input[name=sku]").attr('readonly', 'readonly');
        } else {
            $("input[name=sku]").removeAttr('readonly');
        }
       $.ajax({
           url:'<?php echo Url::toRoute(['/aftersales/sales/getaccountid']); ?>',
           dataType:"json",
           data:{'platform_code':platform_code},
           type:"post",
           success:function(e){
               console.log(e.data);
               var html="";
               if(e.data){
                    html = '<option value="">---请选择---</option>';
                     $.each(e.data, function (n, value) {
                            html+= '<option value=' + n + '>' + value + '</option>';
                        });
               }else{
                    html = '<option value="">---请选择---</option>';
               }
                 $("select[name='account_id']").html(html);
           }
       });
        
        
        
        
    })
    $("input[name=sku]").on('click', function () {
        var platform_code = $("select[name=platform_code]").val();
        if ($(this).attr('readonly') == 'readonly') {
            if (platform_code == undefined || platform_code == '')
                layer.msg('请先选择平台');
        }
    });


    //切换责任归属部门获取对应原因
    $("select[name='department_id']").change(function () {
        var id = $("select[name='department_id']").val();
        var html = "";
        if (id) {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/aftersales/refundreason/getnetleveldata']); ?>',
                data: {'id': id},
                success: function (data) {
                    var html = "";
                    if (data) {
                        $.each(data, function (n, value) {
                            html += '<option value=' + n + '>' + value + '</option>';
                        });
                    } else {
                        html = '<option value="">---请选择---</option>';
                    }
                    $("select[name='reason_id']").empty();
                    $("select[name='reason_id']").append(html);
                }
            });
        } else {
            $("select[name='reason_id']").empty();
            $("select[name='reason_id']").append(html);
        }
    });
</script>