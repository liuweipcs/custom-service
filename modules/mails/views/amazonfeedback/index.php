<?php

use yii\grid\GridView;
use yii\helpers\Url;
use wenyuan\ueditor\Ueditor;
use app\modules\accounts\models\Account;
use app\modules\systems\models\BasicConfig;
use kartik\daterange\DateRangePicker;
use kartik\select2\Select2;
use app\modules\mails\models\AmazonFeedBack;

$this->title = 'Amazon-FeedBack';
?>

<style>
    .alert {
        padding: 5px;
        float: left;
        margin-left: 5px;
        font-size: 12px;
        line-height: 22px;
    }

    .alert-dismissable .close, .alert-dismissible .close {
        top: 0px;
        right: 0px;
    }

</style>
<div id="page-wrapper">
    <div class="row">
        <p>
            <?php
            //echo '<a class="btn btn-success" target="_blank" href="/services/amazon/amazon/getfeedback">手动同步feedback数据</a>';
            ?>
        </p>
        <div class="col-lg-12">
            <?php if (!empty($followStatusList)) { ?>
                <div class="alert alert-success alert-dismissible fade in">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                aria-hidden="true">×</span></button>
                    <a href="<?php echo Url::toRoute(['index']); ?>" class="alert-link">所有</a>
                </div>
                <?php
                foreach ($followStatusList as $key => $value) {
                    if ($key) {
                        ?>
                        <div class="alert alert-success alert-dismissible fade in">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">×</span></button>
                            <a href="<?php echo Url::toRoute(['index', 'AmazonFeedBackSearch[follow_status]' => $key]); ?>"
                               class="alert-link"><?php echo $value; ?>
                                (<?php echo isset($statisticsData[$key]) ? $statisticsData[$key] : 0; ?>)</a>
                        </div>
                        <?php
                    }
                }
            }
            ?>
        </div>
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    // ['class' => 'yii\grid\SerialColumn'],
                    [
                        'class' => 'yii\grid\CheckboxColumn',
                        'checkboxOptions' => function ($model) {
                            return ['value' => $model->id];
                        },
                        'headerOptions' => ['width' => '1%']
                    ],
                    [
                        'filter' => Select2::widget([
                            'data' => $accountList,
                            'name' => "AmazonFeedBackSearch[account_id]",
                            'value' => $searchModel->account_id,
                        ]),
                        'label' => '账号',
                        'attribute' => 'account_id',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $platformList = Account::getAccount('AMAZON', 2);
                            $oldAccount = Account::findOldAccountOne($model->account_id, 'AMAZON');
                            return !empty($oldAccount) ? $platformList[$oldAccount] : '<span class="not-set">(未设置)</span>';
                        },
                        'headerOptions' => ['width' => '3%']
                    ],
                    [
                        'label' => '订单号',
                        'attribute' => 'order_id',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $html = '';
                            $order_type = AmazonFeedBack::getOrderType($model->order_id);

                            if ($order_type) {
                                $html = '<span _width="100%" _height="100%">' . $order_type . '</span><br/>';
                            } else {
                                $html = '<span>' . $order_type . '</span><br/>';
                            }
                            if ($model->order_id) {
                                $html .= '<a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?order_id=' . $model->order_id . '&amp;platform=AMAZON&amp;system_order_id=" title="订单信息">' . $model->order_id . '</a><br/>';
                            }
                            return $html;

                        },
                        'headerOptions' => ['width' => '5%'],
                    ],
                    [
                        'label' => '电话号码',
                        'attribute' => 'ship_phone',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return !empty($model->ship_phone) ? $model->ship_phone : '<span class="not-set">(未设置)</span>';
                        },
                        'headerOptions' => ['width' => '5%'],
                    ],
                    [
                        'label' => '留评时间',
                        'attribute' => 'date',
                        'headerOptions' => ['width' => '5%'],
                        'filter' => DateRangePicker::widget([
                            'name' => 'AmazonFeedBackSearch[date]',
                            'value' => isset(Yii::$app->request->get('AmazonFeedBackSearch')['date']) ? Yii::$app->request->get('AmazonFeedBackSearch')['date'] : '',
                            'convertFormat' => true,
                            'pluginOptions' => [
                                'locale' => [
                                    'format' => 'Y-m-d',
                                    'separator' => '/',
                                ]
                            ]
                        ])
                    ],
                    [
                        'label' => '评价内容',
                        'attribute' => 'comments',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return !empty($model->comments) ? $model->comments : '';
                        },
                        'headerOptions' => ['width' => '15%'],
                    ],
                    [
                        'filter' => [0 => '全部', 1 => ' 1 - 3星 ', 2 => ' 4 - 5星 '],
                        'label' => '评级',
                        'attribute' => 'rating',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $starList = AmazonFeedBack::startList();
                            return !empty($model->rating) ? $starList[$model->rating] : '<span class="not-set">(未设置)</span>';
                        },
                        'headerOptions' => ['width' => '5%']
                    ],
                    [
                        'label' => '回复',
                        'attribute' => 'your_response',
                        'format' => 'raw',
                        'value' => function ($model) {
                            /*                                return !empty($model->your_response) ? $model->your_response : '<span style="cursor:pointer;" data="' . $model->id . '" data1="3" data2="" class="not-set" data-toggle="modal" data-target="#myModal">(未回复)</span>';*/
                            return !empty($model->your_response) ? $model->your_response : '<span  class="not-set">(未回复)</span>';
                        },
                        'headerOptions' => ['width' => '15%'],
                    ],
                    [
                        'filter' => BasicConfig::getParentList(35),
                        'label' => '跟进状态',
                        'attribute' => 'follow_status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $followStatus = BasicConfig::getParentList(35);
                            $html = ' - ';
                            if (in_array($model->rating, array(1, 2, 3))) {
                                $html = '<span style="cursor:pointer;" data="' . $model->id . '" data1="2" data2="" class="not-set" data-toggle="modal" data-target="#myModal">(未跟进)</span>';
                                if ($model->follow_status) {
                                    $html = '<span style="cursor:pointer;" data="' . $model->id . '" data1="2" data2="" class="not-set" data-toggle="modal" data-target="#myModal">' . $followStatus[$model->follow_status] . '</span>';
                                }
                            }
                            return $html;
                        },
                        'headerOptions' => ['width' => '5%'],
                    ],
                    [
                        'filter' => BasicConfig::getParentList(34),
                        'label' => '差评原因',
                        'attribute' => 'review_status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $reviewStatusList = BasicConfig::getParentList(34);
                            $html = ' - ';
                            if (in_array($model->rating, array(1, 2, 3))) {
                                $html = '<span style="cursor:pointer;" data="' . $model->id . '" data3="" class="not-set" data1="1" data-toggle="modal" data-target="#myModal">(未设置)</span>';
                                if ($model->review_status) {
                                    $html = '<span style="cursor:pointer;" data="' . $model->id . '" data3="" data1="1" class="not-set" data-toggle="modal" data-target="#myModal">' . $reviewStatusList[$model->review_status] . '</span>';
                                }
                            }
                            return $html;
                        },
                        'headerOptions' => ['width' => '5%'],
                    ],
                    [
                        'filter' => [0 => '全部', 1 => '是', 2 => '否'],
                        'label' => '是否留review',
                        'attribute' => 'is_review',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $review = AmazonFeedBack::getAmazonReview($model->id);
                            //exit;
                            if ($review) {
                                $html = $review;
                            } else {
                                $html = '暂无review';
                            }
                            return $html;
                        },
                        'headerOptions' => ['width' => '5%'],
                    ],
                    [
                        'filter' => [0 => '全部', 1 => '暂无站内信', 2 => '有站内信'],
                        'label' => '站内信',
                        'attribute' => 'is_station',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $letter = AmazonFeedBack::getStationLetter($model->id);
                            if ($letter) {
                                $html = $letter;
                            } else {
                                $html = '暂无站内信';
                            }
                            return $html;
                        },
                        'headerOptions' => ['width' => '5%'],
                    ],
                    [
                        'filter' => Select2::widget([
                            'data' => \app\modules\users\models\User::getIdNamePairs(),
                            'name' => "AmazonFeedBackSearch[modified_id]",
                            'value' => $searchModel->modified_id,
                        ]),
                        'label' => '更新人/时间',
                        'attribute' => 'modified_id',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $html = '';
                            $userList = \app\modules\users\models\User::getIdNamePairs();
                            $html = !empty($model->modified_id) ? '<span _width="100%" _height="100%">' . $userList[$model->modified_id] . '</span><br/>' : '<span class="not-set">(未设置)</span><br/>';
                            $html .= '<span _width="100%" _height="100%">' . $model->modified_time . '</span><br/>';
                            return $html;
                        },
                        'headerOptions' => ['width' => '5%'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => '操作',
                        'headerOptions' => ['width' => '5%'],
                        'template' => '{viewLog}',

                        'buttons' => [
                            'viewLog' => function ($url, $model, $key) {
                                $html = "";
                                if ($model->order_id) {
                                    $html = '<a class="contact_buyer" style="cursor:pointer;" href="' . Url::toRoute(['/mails/amazonreviewdata/getsendemail', 'account_id' => $model->account_id, 'toemail' => $model->rater_email, 'platform_order_id' => $model->order_id]) . '" target="_blank"> 联系买家</a><br/>';
                                }
                                $html .= '<a class="view_log" style="cursor:pointer;" data-toggle="modal" data1="' . $model->id . '" data2 =" " title="查看操作日志" data-pjax="0">操作日志</a>';
                                return $html;
                            },
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>

<!--回复/处理-->
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
                    <!--<div class="col-sm-10">-->
                    <div class="form-group">
                        <input type="hidden" name="hide_id" id="hide_id" value=""/>
                        <input type="hidden" name="type" id="type" value=""/>
                        <label for="ship_name" class="col-sm-2 control-label required for_label"></label>
                        <div class="col-sm-10 div_step">
                            <select class="form-control" name="reason_id" id="reason_id">
                                <?php foreach (BasicConfig::getParentList(34) as $key => $val) { ?>
                                    <option value="<?php echo $key; ?>"><?php echo $val; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-sm-10 div_reason">
                            <select class="form-control" name="step_id" id="step_id">
                                <?php foreach (BasicConfig::getParentList(35) as $key => $val) { ?>
                                    <option value="<?php echo $key; ?>"><?php echo $val; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <!--</div>-->
                </div>
                <div class="row div_reason" style="margin-top:10px;display:none;">
                    <div class="form-group">
                        <label for="ship_name" class="col-sm-2 control-label required">备注：</label>
                        <div class="col-md-10"><textarea class="form-control" rows="5" id="remark_content"></textarea>
                        </div>
                    </div>
                </div>

                <div class="row div_reply" style="margin-top:10px;display:none;">
                    <div class="form-group">
                        <label for="ship_name" class="col-sm-3 control-label required" style="margin-left: 3px;width: initial;">回复内容：<span
                                    class="text-danger">*</span></label>
                        <div class="col-md-9"><textarea class="form-control" rows="5" id="remark_content"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="text-align:center;">
                <button type="button" class="btn save btn-primary waves-effect waves-light">提交</button>
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">取消</button>
            </div>
        </div>
    </div>
</div>

<!--日志-->
<div id="log-modal" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="custom-width-modalLabel"
     aria-hidden="false" style="display: none; padding-right: 17px;">
    <div class="modal-dialog" style="width:55%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="custom-width-modalLabel">操作日志</h4>
            </div>
            <div class="col-md-12" style="margin-top:15px; margin-bottom: 15px;">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <!--<h4 class="m-b-30 m-t-0">Striped rows Table</h4>-->
                        <div class="row" id="log">
                            <div class="col-xs-12">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>动作</th>
                                        <th>备注</th>
                                        <th>时间 [操作人]</th>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-primary waves-effect waves-light" data-dismiss="modal">Close
                </button>
            </div>
        </div>
    </div>
</div>
<style>
    #myModal {
        top: 300px;
    }

</style>
<script type="text/javascript">

    //点击设置差评原因   处理状态按钮
    $(document).on('click', '.not-set', function () {
        var id = $(this).attr('data');//feedbackId
        var type = $(this).attr('data1');//类型 1:差评原因  2：处理状态 3:回复
        var statusId = $(this).attr('data2');//根据状态
        var reasonId = $(this).attr('data3');//纠纷差评原因
        $(".div_reply").hide();
        $(".div_step").hide();
        $(".div_reason").hide();
        if (type == 1) {
            $("#myModalLabel").html('Feedback差评原因 <a target="_blank" href="<?php echo Url::toRoute(['/systems/basicconfig/index']) . '?BasicConfigSearch[parent_id]=34' ?>">管理Feedback差评原因</a>');
            $(".for_label").html('原因：<span class="text-danger">*</span>');
            $("#reason_id").val(reasonId);
            $(".div_reason").hide();
            $(".div_step").show();
        } else if (type == 2) {
            $("#myModalLabel").html('Feedback处理状态 <a target="_blank" href="<?php echo Url::toRoute(['/systems/basicconfig/index']) . '?BasicConfigSearch[parent_id]=35' ?>">管理Feedback跟进状态</a>');
            $(".for_label").html('状态：<span class="text-danger">*</span>');
            $("#step_id").val(statusId);
            $(".div_reason").show();
            $(".div_step").hide();
            $("#remark_content").val($("#remark_" + id).html());
        } else if (type == 3) {
            $("#myModalLabel").html('回复Feedback');
            $(".for_label").html('');
            $(".div_reply").show();
            $(".div_step").hide();
            $(".div_reason").hide();
            $("#remark_content").val($("#remark_" + id).html());
        }
        $("#hide_id").val(id);
        $("#type").val(type);
    });


    //设置差评原因   处理状态按钮ajax请求
    $(document).on('click', '.save', function () {
        var id = $("#hide_id").val();//feedbackId
        var type_id = $("#type").val();//类型
        var reason_id = $("#reason_id").val();//差评原因
        var step_id = $("#step_id").val();//跟进状态
        var text = $("#remark_content").val();

        //如果type=1 纠纷原因则 reason_id必选    如果type=2则跟进状态必选 如果type=3则回复内容必选
        if (type_id == 1 && reason_id == 0) {
            layer.msg('请选择Feedback差评原因!');
            return false;
        }

        if (type_id == 2 && step_id == 0) {
            layer.msg('请选择Feedback处理状态!');
            return false;
        }

        if (type_id == 3 && text == 0) {
            layer.msg('请填写Feedback回复内容!');
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['process']); ?>',
            data: {'id': id, 'type_id': type_id, 'reason_id': reason_id, 'step_id': step_id, 'text': text},
            success: function (data) {
                if (data.status) {
                    layer.msg(data.info, {icon: 1});
                    $("#myModal").modal('hide');
                    window.location.reload();
                } else {
                    layer.msg(data.info, {icon: 5});
                }
            }
        });
    });

    //查看日志
    $(document).on('click', '.view_log', function () {
        var id = $(this).attr('data1');//feedback ID
        $("#custom-width-modalLabel").html(" 操作日志");

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['getlog']); ?>',
            data: {'id': id},
            success: function (data) {
                var html = "";
                if (data.status) {
                    $.each(data.info, function (n, value) {
                        html += '<tr>';
                        html += '<td>' + (n + 1) + '</td>';
                        html += '<td>' + value.action + '</td>';
                        html += '<td>' + value.remark + '</td>';
                        html += '<td>' + value.create_time + ' 【' + value.create_by + '】</td>';
                        html += '<tr>';
                    });
                } else {
                    html = '<tr><td colspan="4" style="text-align:center;">' + data.info + '</td></tr>';
                }

                $("#log tbody").html("");
                $("#log tbody").append(html);
                $("#log-modal").modal('show');
            }
        });
    });

</script>