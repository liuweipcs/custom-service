<?php
use app\components\GridView;
use yii\helpers\Url;
use app\modules\systems\models\BasicConfig;
?>
<style>
    #search-form .input-group.date {
        width: 320px;
    }
    .form-horizontal .control-label{
        text-align: center;
    }
    .col-lg-5 {
        width: 35%
    }
    #myModal {
        top: 300px;
    }
</style>
<div id="page-wrapper">
    <!--     <div class="row">
            <div class="col-lg-12">
                <div class="page-header bold">平台列表</div>
            </div>
        </div> -->
    <div class="row">
        <div class="col-lg-12">
            <?php
            $this->title = '客户列表';
            echo GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'tags' => $follows,
                'is_tags' => true,
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
                        'field' => 'create_by_time',
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
                        'field' => 'buyer_id_email',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'buyer_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'type',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'account_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],

                    [
                        'field' => 'pay_email',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'phone',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'other_contacts',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'purchase_times',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'turnover',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'disputes_number',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'credit_rating',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'tag_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'follow_status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '130px']
                        ],
                    ],
                    [
                        'field' => 'modify_by',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'buttons' => [
                            [
                                'text' => Yii::t('system', 'Edit'),
                                'href' => Url::toRoute('/customer/customer/listeditor'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '80%',
                                    '_height' => '80%',
                                ],
                            ],
                            [
                                'text' => Yii::t('system', '联系客户'),
                                'href' => Url::toRoute('/customer/customer/contact'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '80%',
                                    '_height' => '80%',
                                ],
                            ],
                            [
                                'text' => Yii::t('system', '操作日志'),
                                'href' => Url::toRoute('/customer/customer/operation'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '80%',
                                    '_height' => '80%',
                                ],
                            ]
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '90px']
                        ]
                    ]
                ],
                'toolBars' => [
                    [
                        'href' => Url::toRoute('/customer/customer/comeexcel'),
                        'text' => Yii::t('system', '导入客户'),
                        'htmlOptions' => [
                            'class' => 'add-button',
                            '_width' => '30%',
                            '_height' => '30%',
                        ]
                    ],
                    [
                        'href' => '#',
                        'text' => Yii::t('system', '导出客户'),
                        'htmlOptions' => [
                            'id' => 'export-mail-content',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/customer/customer/contacts'),
                        'text' => Yii::t('system', '联系客户'),
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                            '_width' => '80%',
                            '_height' => '80%',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/customer/customer/listtags'),
                        'text' => Yii::t('system', '添加客户标签'),
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/customer/customer/listgroup'),
                        'text' => Yii::t('system', '加入分组'),
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false"
     style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel"></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group">
                        <input type="hidden" name="hide_id" id="hide_id" value=""/>
                        <input type="hidden" name="type" id="type" value=""/>
                        <label for="ship_name" class="col-sm-2 control-label required for_label"></label>
                        <div class="col-sm-10 div_reason">
                            <select class="form-control" name="step_id" id="step_id">
                                <?php foreach (BasicConfig::getParentList(35) as $key => $val) { ?>
                                    <option value="<?php echo $key; ?>"><?php echo $val; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                   
                </div>
                <div class="row div_reason" style="margin-top:10px;display:none;">
                    <div class="form-group">
                        <label for="ship_name" class="col-sm-2 control-label required">备注：</label>
                        <div class="col-md-10"><textarea class="form-control" rows="5" id="remark_content"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">关闭</button>
                <button type="button" class="btn save btn-primary waves-effect waves-light">提交</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        //导出客户内容
        $("#export-mail-content").on("click", function () {

            var queryStr = $("#search-form").serialize();
            var dataSrc = $(this).attr('data-src');
            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length > 0) {
                checkBox.each(function () {
                    queryStr += '&ids[]=' + $(this).val();
                });
            }
            location.href = "<?php echo Url::toRoute('/customer/customer/export'); ?>?" + queryStr;
            return false;
        });
    });

    //点击设置跟进状态
    $(document).on('click', '.not-set', function () {
        var id = $(this).attr('data');//buyer_id
        var type = $(this).attr('data1');//类型 1:跟进状态 
        var statusId = $(this).attr('data2');//根据状态
        if (type == 1) {
            $("#myModalLabel").html('维护客户跟进状态 <a target="_blank" href="<?php echo Url::toRoute(['/systems/basicconfig/index']) . '?BasicConfigSearch[parent_id]=35' ?>">管理客户跟进状态</a>');
            $(".for_label").html('原因：<span class="text-danger">*</span>');
            $(".div_reason").show(); 
            $("#step_id").val(statusId);
            $("#remark_content").val($("#remark_" + id).html());
        }
        $("#hide_id").val(id);
        $("#type").val(type);
    });

    //设置差评原因   处理状态按钮ajax请求
    $(document).on('click', '.save', function () {
        var buyer_id = $("#hide_id").val();//buyer_id
        var type_id = $("#type").val();//类型
        var step_id = $("#step_id").val();//跟进状态
        var text = $("#remark_content").val();
        //type=1则跟进状态必选 
        if (type_id == 1 && step_id == 0) {
            layer.msg('请选择跟进处理状态!');
            return false;
        }
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['followstatus']); ?>',
            data: {'buyer_id': buyer_id, 'type_id': type_id, 'step_id': step_id, 'text': text},
            success: function (data) {
                if (data.status) {
                    layer.msg(data.msg, {icon: 1});
                    $("#myModal").modal('hide');
                    window.location.reload();
                } else {
                    layer.msg(data.msg, {icon: 5});
                }
            }
        });
    });
</script>
