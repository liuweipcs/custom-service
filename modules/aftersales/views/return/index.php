<?php

use app\components\GridView;
use yii\helpers\Url;

$platforms = \app\modules\accounts\models\Platform::getPlatformAsArray();
if (isset($platform_code) && isset($platforms[$platform_code])) {
    $this->title = $platforms[$platform_code];
}
$this->title .= '退件问题';
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'id'           => 'grid-view',
                'dataProvider' => $dataProvider,
                'model'        => $model,
                'pager'        => [],
                'columns'      => [
                    [
                        'field'       => 'state',
                        'type'        => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field'       => 'after_sale_id_text',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'order_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                     [
                        'field'       => 'refund_code',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'buyer_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'platform_code',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'department_text',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'reason_text',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],

                    [
                        'field'       => 'audit_info',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'remark',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'return_status_info',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'return_time',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'return_info',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'is_receive',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'carrier',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'warehouse_name',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'create_info',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
//                    [
//                        'field'       => 'modify_info',
//                        'type'        => 'text',
//                        'sortAble'    => true,
//                        'htmlOptions' => [
//                            'align' => 'center',
//                        ],
//                    ],
                    [
                        'field'       => 'edit_after_sales_order',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ]
                ],
                'toolBars'     => [
                    [
                        'href'        => Url::toRoute('/aftersales/sales/batchaudit?url=' . $url),
                        'text'        => Yii::t('system', '批量审核'),
                        'htmlOptions' => [
                            'class'    => 'delete-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href'        => Url::toRoute('/aftersales/sales/download'),
                        'text'        => Yii::t('system', '下载数据'),
                        'htmlOptions' => [
                            'id'     => 'download',
                            'target' => '_blank',
                        ]
                    ],

                ],

            ]);
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('.input-group').attr('style', 'width:300px;');
    /**
     * 下载数据相关操作
     */
    $("#download").click(function () {
        var platformCode = $("select[name='platform_code']").val();//所属平台
        var afterSaleId = $("input[name='after_sale_id']").val();//售后单号
        var orderId = $("input[name='order_id']").val();//系统订单号
        var buyerId = $("input[name='buyer_id']").val();//买家ID
        var departmentId = $("select[name='department_id']").val();//责任归属部门id
        var reasonId = $("select[name='reason_id']").val();//售后原因ID
        var type = 2; // 售后单类型
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
        var platform_code = "<?php echo $platform_code;?>";
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
            +'&return_status='+return_status+"&rma="+rma+"&tracking_no="+tracking_no;
        }
        window.open(url);
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
    //取消
    function cancelReturn(after_sales_id, return_status) {
        layer.confirm('确定要取消收货吗？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: "/aftersales/sales/cancelreturn",
                type: "GET",
                data: {"after_sales_id": after_sales_id, 'return_status': return_status},
                dataType: "json",
                success: function (data) {
                    if (data.code == 200) {
                        location.href = location.href;
                        layer.msg(data.message, {time: 3000}, {icon: 6});
                    } else {
                        layer.msg(data.message, {icon: 5});
                        return false;
                    }
                }
            });
        })
    }

    function deleteReturn(after_sales_id) {
        layer.confirm('确定要删除吗？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: "/aftersales/return/deletereturn",
                type: "GET",
                data: {"after_sales_id": after_sales_id},
                dataType: "json",
                success: function (data) {
                    if (data.code == 200) {
                        location.href = location.href;
                        layer.msg(data.message, {time: 3000}, {icon: 6});
                    } else {
                        layer.msg(data.message, {icon: 5});
                        return false;
                    }
                }
            });
        })
    }
</script>