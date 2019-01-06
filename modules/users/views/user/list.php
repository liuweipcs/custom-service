<?php

use app\components\GridView;
use yii\helpers\Url;

$this->title = '用户列表';
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
                        'field'       => 'user_number',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'login_name',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'user_name',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'role_name',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'user_email',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'user_telephone',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'status_text',
                        'type'        => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'create_by',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'create_time',
                        'type'        => 'text',
                        'sortAble'    => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'modify_by',
                        'type'        => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field'       => 'modify_time',
                        'type'        => 'text',
                        'sortAble'    => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field'       => 'operation',
                        'headerTitle' => \Yii::t('system', 'Operation'),
                        'type'        => 'operateButton',
                        'buttons'     => [
                            [
                                'text'        => Yii::t('system', 'Edit'),
                                'href'        => Url::toRoute('/users/user/edit'),
                                'htmlOptions' => [
                                    'class'   => 'edit-record',
                                    '_width'  => '80%',
                                    '_height' => '80%'
                                ],
                            ],
                            /* [
                                 'text' => Yii::t('system', 'Delete'),
                                 'href' => Url::toRoute('/users/user/delete'),
                                 'queryParams' => '{id}',
                                 'htmlOptions' => [
                                     'class' => 'delete-record',
                                     'confirm' => Yii::t('system', 'Confirm Delete The Record')
                                 ],
                             ],*/
                            [
                                'text'        => Yii::t('system', 'Set Privileges'),
                                'href'        => Url::toRoute('/users/role/setpowerforuser'),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class'   => 'edit-record',
                                    '_width'  => '80%',
                                    '_height' => '80%'
                                    //'confirm' => Yii::t('system', 'Confirm Delete The Record')
                                ],
                            ]

                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                        ]
                    ]
                ],
                'toolBars'     => [
                    [
                        'href'        => Url::toRoute('/users/user/add'),
                        'buttonType'  => 'add',
                        'text'        => Yii::t('system', 'Add'),
                        'htmlOptions' => [
                            'class'   => 'add-button',
                            '_width'  => '80%',
                            '_height' => '80%',
                        ]
                    ],
                    [
                        'href'        => Url::toRoute('/users/user/import'),
                        'buttonType'  => 'export',
                        'text'        => '导入excel',
                        'htmlOptions' => [
                            'class' => 'uploadExcel',
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>
<div class="popup-wrapper">
    <div class="popup-body">
        <div class="modal fade in" id="uploadExcelModal" tabindex="-1" role="dialog"
             aria-labelledby="uploadIssueImgModalLabel"
             style="top:300px;">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="myModalLabel">上传文件</h4>
                    </div>
                    <div class="modal-body">
                        <form id="uploadExcelForm" class="form-horizontal" enctype="multipart/form-data">
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <span style="color:red">* 注意：文件只能是xls,csv,xlsl格式</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <div>
                                    <input type="file" id="" name="image" style="display: inline-block; width: 80%;"/>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="uploadExcelBtn">上传</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
    </div>
</div>

<script type="text/javascript" src="/js/jquery.form.js"></script>
<script>
    //上传
    $(".uploadExcel").on("click", function () {
        $("#uploadExcelModal").modal("show");
        return false;
    });
    //上传纠纷图片
    $("#uploadExcelBtn").on("click", function () {
        var excel_file = $("#uploadExcelForm input[name='image']").val();
        if (excel_file.length == 0 || excel_file == "") {
            layer.alert("请选择上传文件");
            return false;
        }
        $("#uploadExcelForm").attr("action", "<?php echo \yii\helpers\Url::toRoute(['/users/user/import']) ?>");
        $("#uploadExcelForm").attr("method", "post");

        $('#uploadExcelForm').ajaxSubmit({
            dataType: 'json',
            beforeSubmit: function (options) {
                if (!/(csv|xls|xlsx)/ig.test(options[0].value.type)) {
                    layer.msg('文件格式错误！', {
                        icon: 2,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    });
                    return false;
                }
            },
            success: function (response) {
                switch (response.code) {
                    case 201:
                        layer.msg(response.message, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                        break;
                    case 200:
                        layer.msg(response.message, {
                            icon: 1,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                }
            },

        });
        return false;
    });
</script>