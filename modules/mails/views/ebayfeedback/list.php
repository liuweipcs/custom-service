<?php

use app\components\GridView;
use yii\helpers\Url;
use kartik\select2\Select2;
use app\modules\systems\models\BasicConfig;

$this->title = 'eBay评价';
?>
<div id="page-wrapper">
    <!--     <div class="row">
            <div class="col-lg-12">
                <div class="page-header bold">平台列表</div>
            </div>
        </div> -->
    <div class="row">
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'id'           => 'grid-view',
                'dataProvider' => $dataProvider,
                'model'        => $model,
                //'tags' => $tagList,
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
                        'field'       => 'feedback_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'eb_order_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'order_line_item_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'commenting_user',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'commenting_user_score',
                        'type'        => 'text',
                        'htmlOptions' => [
                             'align' => 'left',
                             'style' => ['min-width' => '230px']
                        ]    
                    ],
                    [
                        'field'       => 'department_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'step_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'send_link_time',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '100px']
                        ],
                    ],
                    [
                        'field'       => 'item_price',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],

                    [

                        'field'       => 'status',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'item_id',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'time_list',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                            'style' => ['min-width' => '190px']
                        ],
                    ],
                    [
                        'field'       => 'message',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',

                        ],
                    ],
                    [
                        'field'       => 'logistics',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                            'style' => ['min-width' => '150px']
                        ],
                    ],
                    [
                        'field'       => 'ship_country',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                            'style' => ['min-width' => '70px']
                        ],
                    ],
                    [
                        'field'       => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type'        => 'operateButton',
                        'buttons'     => [
                            [
                                'text'        => '跟进',
                                'href'        => Url::toRoute(['/mails/ebayfeedbackresponse/add', 'type' => 'Reply']),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class'   => 'edit-record',
                                    'id'      => 'status',
                                    '_width'  => '70%',
                                    '_height' => '70%',
                                ],
                            ],

//                        [
//                            'text' => '回评',
//                            'href' => Url::toRoute(['/mails/ebayfeedback/replyback','type'=>'FollowUp']),
//                            'queryParams' => '{id}',
//                            'htmlOptions' => [
//                                'class' => 'edit-record'
//                            ],
//                        ],
                            [
                                'text'        => '标记已回复',
                                'href'        => Url::toRoute('/mails/ebayfeedback/mark'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class'   => 'delete-record',
                                    'confirm' => '确定标记为已回复吗?',
                                ],
                            ],
//                        [
//                            'text' => '设置差评原因',
//                            'href' => 'javascript(0)',
//                            'queryParams' => '{id}',
//                            'htmlOptions' => [
//                                'class' => 'mark_reason'
//                            ],
//                        ],
                            /*[
                                'text' => Yii::t('system', 'Delete'),
                                'href' => Url::toRoute('/accounts/platform/delete'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-button',
                                    'confirm' => Yii::t('system', 'Confirm Delete The Record')
                                ],
                            ]*/
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => 'min-width:120px'
                        ]
                    ]
                ],
                'toolBars'     => [
                    /*  [
                          'href' => Url::toRoute('/accounts/platform/add'),
                          'buttonType' => 'add',
                          'text' => Yii::t('system', 'Add'),
                          'htmlOptions' => [
                              'class' => 'add-button',
                              '_width' => '48%',
                              '_height' => '48%',
                          ]
                      ], */
                    [
                        'href'        => Url::toRoute('/mails/ebayfeedbackresponse/addlist'),
                        'text'        => Yii::t('system', '批量跟进'),
                        'queryParams' => '{id}',
                        'htmlOptions' => [
                            'class'    => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href'        => Url::toRoute('/mails/ebayfeedback/mark'),
                        'text'        => '批量标记为已回复',
                        'htmlOptions' => [
                            'class'    => 'delete-button',
                            'data-src' => 'id',
                            'confirm'  => '确定选中项标记为已回复吗?',
                        ]
                    ],
                    [
                        'href'        => "#",
                        'text'        => '导出excel',
                        'htmlOptions' => [
                            'id'       => 'export-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href'        => "#",
                        'text'        => '超过30天无法修改',
                        'htmlOptions' => [
                            'id'    => 'more_30',
                            'class' => 'btn-danger waves-effect waves-light'
                        ]
                    ],
                    [
                        'href'        => "#",
                        'text'        => '链接已发送5天未修改',
                        'htmlOptions' => [
                            'id'    => 'send_5',
                            'class' => 'btn-warning waves-effect waves-light',
                        ]
                    ],
                    [
                        'href'        => "#",
                        'text'        => '链接已发送8天未修改',
                        'htmlOptions' => [
                            'id'    => 'send_8',
                            'class' => 'btn-warning waves-effect waves-light',
                        ]
                    ],
                    [
                        'href'        => "#",
                        'text'        => '超期未修改',
                        'htmlOptions' => [
                            'id'    => 'expired',
                            'class' => 'btn-danger waves-effect waves-light'
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>
<style>
    #myModal {
        top: 300px;
    }
</style>
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false"
     style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal">
                    <div class="div_step">
                        <div class="form-group">
                            <div class="col-sm-3">
                                <label for="ship_name" class=" control-label required">责任所属部门：<span class="text-danger">*</span></label>
                                <select class="form-control" name="department_id" id="department_id" size="12"
                                        multiple="multiple">
                                    <?php foreach ($departmentList as $key => $val) { ?>
                                        <option value="<?php echo $key; ?>"><?php echo $val; ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-sm-9">
                                <label for="ship_name" class="control-label required">原因：<span
                                            class="text-danger">*</span></label>
                                <select class="form-control" name="reason_id" id="reason_id" size="12"
                                        multiple="multiple">

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="div_reason" style="display:none;">
                        <div class="form-group">
                            <label for="ship_name" class="col-sm-2 control-label required for_label">状态：<span
                                        class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <select class="form-control" name="step_id" id="step_id">
                                    <!--<option value="">--请选择跟进状态--</option>-->
                                    <?php foreach (BasicConfig::getParentList(5) as $key => $val) { ?>
                                        <option value="<?php echo $key; ?>"><?php echo $val; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ship_name" class="col-sm-2 control-label required">备注：<span class="text-danger">*</span></label>
                            <div class="col-md-10"><textarea class="form-control" rows="5"
                                                             id="remark_content"></textarea></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="hide_id" id="hide_id" value=""/>
                <input type="hidden" name="type" id="type" value=""/>
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">Close</button>
                <button type="button" class="btn save btn-primary waves-effect waves-light">Save changes</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function getrepliedmessage(feedback_id) {

        $.get("<?php echo Url::toRoute(['/mails/ebayfeedback/getrepliedmsg'])?>", {'feedback_id': feedback_id}, function (data) {
            var msg = "回复内容:&nbsp&nbsp" + data;
            layer.alert(msg);
        });
    }

    $('.input-group').attr('style', 'width:300px;');


    //点击设置差评原因   处理状态按钮
    $(document).on('click', '.not-set', function () {
        var id = $(this).attr('data');//feedbackId
        var type = $(this).attr('data1');//类型 1:差评原因  2：处理状态
        var statusId = $(this).attr('data2');//根据状态
        var reasonId = $(this).attr('data3');//纠纷差评原因
        var departmentId = $(this).attr('data4') //部门ID

        if (type == 1) {
            $('#department_id').val(departmentId);
            $('#department_id').trigger('change', [reasonId]);
            $('#reason_id').val(reasonId);

            $("#myModalLabel").html('纠纷差评原因 <a target="_blank" href="<?php echo Url::toRoute(['/systems/basicconfig/index']) . '?BasicConfigSearch[parent_id]=1'?>">管理纠纷原因</a>');
            $(".div_reason").hide();
            $(".div_step").show();
        } else {
            $("#step_id").val(statusId);
            $("#remark_content").val($("#remark_" + id).html());

            $("#myModalLabel").html('纠纷处理状态 <a target="_blank" href="<?php echo Url::toRoute(['/systems/basicconfig/index']) . '?BasicConfigSearch[parent_id]=5'?>">管理跟进状态</a>');
            $(".div_reason").show();
            $(".div_step").hide();
        }
        $("#hide_id").val(id);
        $("#type").val(type);
    });

    //设置差评原因   处理状态按钮ajax请求
    $(document).on('click', '.save', function () {
        var id = $("#hide_id").val();//feedbackId
        var type_id = $("#type").val();//类型
        var department_id = $("#department_id").val();  //部门ID
        var reason_id = $("#reason_id").val();//差评原因
        var step_id = $("#step_id").val();//跟进状态
        var text = $("#remark_content").val();

        //如果type=1 纠纷原因则 department_id和reason_id必选
        if (type_id == 1 && (department_id == 0 || reason_id == 0)) {
            layer.msg('请选择责任所属部门和原因类型!');
            return false;
        }

        //如果type=2则跟进状态必选
        if (type_id == 2 && step_id == 0) {
            layer.msg('请选择处理状态!');
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['setreason']); ?>',
            data: {
                'id': id,
                'type_id': type_id,
                'department_id': department_id,
                'reason_id': reason_id,
                'step_id': step_id,
                'text': text
            },
            success: function (data) {
                if (data.status) {
                    layer.msg(data.info, {icon: 1});
                    $("#myModal").modal('hide');
                    window.refreshTable("/mails/ebayfeedback/list");
                } else {
                    layer.msg(data.info, {icon: 5});
                }
            }
        });
        return false;
    });

    //获取责任归属部门对应原因
    $(document).on("change", "#department_id", function (event, reasonId) {
        var id = $(this).val();
        var html = '<option value="0">---请选择---</option>';
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
                            if (reasonId && reasonId == n) {
                                html += '<option value=' + n + ' selected="selected">' + value + '</option>';
                            } else {
                                html += '<option value=' + n + '>' + value + '</option>';
                            }
                        });
                    }
                    $("#reason_id").html(html);
                }
            });
        } else {
            $("#reason_id").html(html);
        }
    });

    //搜索表单，获取责任归属部门对应原因
    $(document).on("change", "#search-form select[name='department_id']", function () {
        var id = $(this).val();
        var html = '<option value="0">---请选择---</option>';
        if (id == "0" || id == "") {
            id = 1;
        }
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
                }
                $("#search-form select[name='reason_id']").html(html);
            }
        });
    });

    $(function () {
        $("#more_30").on("click", function () {
            var html = "<input type='hidden' name='hidden_val' id = 'hidden_val' value = '1'/>";
            $("#search-form").append(html);
            window.refreshTable("/mails/ebayfeedback/list");
            $("#hidden_val").remove();
        });

        $("#send_5").on("click", function () {
            var html = "<input type='hidden' name='hidden_val' id = 'hidden_val' value = '2'/>";
            $("#search-form").append(html);
            window.refreshTable("/mails/ebayfeedback/list");
            $("#hidden_val").remove();
        });

        $("#send_8").on("click", function () {
            var html = "<input type='hidden' name='hidden_val' id = 'hidden_val' value = '3'/>";
            $("#search-form").append(html);
            window.refreshTable("/mails/ebayfeedback/list");
            $("#hidden_val").remove();
        });

        $("#expired").on("click", function () {
            var html = "<input type='hidden' name='hidden_val' id = 'hidden_val' value = '4'/>";
            $("#search-form").append(html);
            window.refreshTable("/mails/ebayfeedback/list");
            $("#hidden_val").remove();
        });

        $("#export-button").on("click", function () {
            var queryStr = $("#search-form").serialize();
            var dataSrc = $(this).attr('data-src');
            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/mails/ebayfeedback/export'); ?>?" + queryStr;
            return false;
        });
    });
</script>