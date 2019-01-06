<?php

use yii\grid\GridView;
use wenyuan\ueditor\Ueditor;
use app\modules\accounts\models\Account;
use app\modules\mails\models\AmazonReviewData;
use app\modules\systems\models\BasicConfig;
use yii\helpers\Url;
use kartik\daterange\DateRangePicker;
use kartik\select2\Select2;
use app\modules\accounts\models\Platform;

$this->title = 'Amazon Review Datas';
$this->params['breadcrumbs'][] = $this->title;
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
            echo '<a class="btn btn-success" target="_blank" href="/services/amazon/amazonreview/getamazonreviewsdata">手动同步review数据</a>';

            echo '  <a class="btn btn-success" id="download" href="">下载数据</a>';
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
                            <a href="<?php echo Url::toRoute(['index', 'AmazonReviewDataSearch[follow_status]' => $key]); ?>"
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
                'id' => 'review_grid',
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'class' => 'yii\grid\CheckboxColumn',
                        'checkboxOptions' => function ($model) {
                            return ['value' => $model->id];
                        }
                    ],
                    [
                        'filter' => Select2::widget([
                            'data' => $accountList,
                            'name' => "AmazonReviewDataSearch[accountId]",
                            'value' => $searchModel->accountId,
                        ]),
                        'label' => '账号',
                        'attribute' => 'accountId',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $platformList = Account::getAccount('AMAZON', 2);
                            return !empty($model->accountId) ? $platformList[$model->accountId] : '<span class="not-set">(未设置)</span>';
                        },
                        'headerOptions' => ['width' => '10%']
                    ],
                    [
                        'attribute' => 'reviewDate',
                        'headerOptions' => ['width' => '12%'],
                        'filter' => DateRangePicker::widget([
                            'name' => 'AmazonReviewDataSearch[reviewDate]',
                            'value' => isset(Yii::$app->request->get('AmazonReviewDataSearch')['reviewDate']) ? Yii::$app->request->get('AmazonReviewDataSearch')['reviewDate'] : '',
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
                        'label' => '订单/Asin/客户Email/订单类型',
                        'attribute' => 'asin',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $html = '';
                            if ($model->orderId) {
                                $html = '<a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?order_id=' . $model->orderId . '&amp;platform=AMAZON&amp;system_order_id=" title="订单信息">' . $model->orderId . '</a><br/>';
                            }

                            $siteList = Account::getSiteList('AMAZON');
                            $domainName = isset($siteList[$model->accountId]) ? 'https://' . $siteList[$model->accountId] : "";
                            if ($domainName) {
                                $html .= '<a href="' . $domainName . '/gp/customer-reviews/' . $model->reviewId . '/?ie=UTF8&ASIN=' . $model->asin . '" target="_blank">' . $model->asin . '</a>';
                            } else {
                                $html .= $model->asin;
                            }
                            if ($model->custEmail) {
                                $accountId = '';
                                $account = Account::findOne(['old_account_id' => $model->accountId, 'platform_code' => Platform::PLATFORM_CODE_AMAZON]);
                                if (!empty($account)) {
                                    $accountId = $account->id;
                                }
                                $html .= '<br/><a data = "' . $accountId . '" data1 = "' . $model->custEmail . '" data2="' . $model->orderId . '" style="color:#3A5FCD;cursor:pointer;" data-toggle="modal" class="contact_buyer">' . $model->custEmail . '</a>';
                            }
                            if ($model->amazon_fulfill_channel) {
                                $html .= '<br/><span style="color: red">' . $model->amazon_fulfill_channel . '</span>';
                            }
                            return $html;
                        }
                    ],
                    'customerId',
                    'customerName',
                    [
                        'filter' => [1 => ' 1 - 3星 ', 2 => ' 4 - 5星 '],
                        'label' => '星级',
                        'attribute' => 'star',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $starList = AmazonReviewData::startList();
                            return !empty($model->star) ? $starList[$model->star] : '<span class="not-set">(未设置)</span>';
                        },
                        'headerOptions' => ['width' => '6%']
                    ],
                    'title',
                    [
                        'label' => '产品图片',
                        'format' => [
                            'image'
                        ],
                        'value' => function ($model) {
                            return $model->imgUrl;
                        }
                    ],
                    [
                        'filter' => BasicConfig::getParentList(34),
                        'label' => '差评原因',
                        'attribute' => 'review_status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $reviewStatusList = BasicConfig::getParentList(34);
                            $html = ' - ';
                            if (in_array($model->star, array(1, 2, 3))) {
                                $html = '<span style="cursor:pointer;" data="' . $model->id . '" data3="" class="not-set" data1="1" data-toggle="modal" data-target="#myModal">(未设置)</span>';
                                if ($model->review_status) {
                                    $html = '<span style="cursor:pointer;" data="' . $model->id . '" data3="" data1="1" class="not-set" data-toggle="modal" data-target="#myModal">' . $reviewStatusList[$model->review_status] . '</span>';
                                }
                            }
                            return $html;
                        }
                    ],
                    [
                        'filter' => BasicConfig::getParentList(35),
                        'label' => '跟进状态',
                        'attribute' => 'follow_status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $followStatus = BasicConfig::getParentList(35);
                            $html = ' - ';
                            if (in_array($model->star, array(1, 2, 3))) {
                                $html = '<span style="cursor:pointer;" data="' . $model->id . '" data1="2" data2="" class="not-set" data-toggle="modal" data-target="#myModal">(未跟进)</span>';
                                if ($model->follow_status) {
                                    $html = '<span style="cursor:pointer;" data="' . $model->id . '" data1="2" data2="" class="not-set" data-toggle="modal" data-target="#myModal">' . $followStatus[$model->follow_status] . '</span>';
                                }
                            }
                            return $html;
                        }
                    ],
//                    'is_reply',
//                    'is_station',
                    [
                        'label' => '站内信',
                        'attribute' => 'is_station',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $letter = AmazonReviewData::getStationLetter($model->id);
                            if ($letter) {
                                $html = $letter;
                            } else {
                                $html = '暂无站内信';
                            }
                            return $html;
                        }
                    ],
                    [
                        'label' => '联系买家',
                        'attribute' => 'contact_buyer',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $html = "";
                            if ($model->orderId) {
                                $accountId = '';
                                $account = Account::findOne(['old_account_id' => $model->accountId, 'platform_code' => Platform::PLATFORM_CODE_AMAZON]);
                                if (!empty($account)) {
                                    $accountId = $account->id;
                                }
                                $html = '<a href="' . Url::toRoute(['/mails/amazonreviewdata/getsendemail', 'account_id' => $accountId, 'toemail' => $model->custEmail, 'platform_order_id' => $model->orderId]) . '" target="_blank"> 联系买家</a>';
                            }
                            return $html;
                        }
                    ],
                    [
                        'filter' => Select2::widget([
                            'data' => \app\modules\users\models\User::getIdNamePairs(),
                            'name' => "AmazonReviewDataSearch[modified_id]",
                            'value' => $searchModel->modified_id,
                        ]),
                        'label' => '更新人',
                        'attribute' => 'modified_id',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $userList = \app\modules\users\models\User::getIdNamePairs();
                            return !empty($model->modified_id) ? $userList[$model->modified_id] : '<span class="not-set">(未设置)</span>';
                        },
                        'headerOptions' => ['width' => '10%']
                    ],
                    [
                        'attribute' => 'addTime',
                        'label' => '系统下载时间',
                        'value' => function ($model) {
                            return date('Y-m-d', strtotime($model->addTime));   //主要通过此种方式实现
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => '操作',
                        'headerOptions' => ['width' => '80'],
                        'template' => '{viewLog}',
                        'buttons' => [
                            'viewLog' => function ($url, $model, $key) {
                                return '<a class="view_log" style="cursor:pointer;" data-toggle="modal" data1="' . $model->asin . '" data2 ="' . $model->id . '" title="查看操作日志" aria-label="查看操作日志" data-pjax="0"><span class="fa fa-binoculars"></span></a>';
                            },
                        ],
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>

<!--处理-->
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
                        <label for="ship_name" class="col-sm-2 control-label required">备注：<span
                                    class="text-danger">*</span></label>
                        <div class="col-md-10"><textarea class="form-control" rows="5" id="remark_content"></textarea>
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

<!--联系买家-->
<div id="contact_buyer" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="custom-width-modalLabel"
     aria-hidden="false" style="display: none; padding-right: 17px;">
    <div class="modal-dialog" style="width:55%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="custom-width-modalLabel">发送邮件</h4>
            </div>
            <div class="col-md-12" style="margin-top:15px; margin-bottom: 15px;">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-12">
                                <!--<form class="form-horizontal" action="#" novalidate="">-->
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">发件人</label>
                                        <div class="col-md-11">
                                            <input id="sender_email" type="text" class="form-control" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">收件人</label>
                                        <div class="col-md-11">
                                            <input id="recipient_email" type="text" class="form-control" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">主题</label>
                                        <div class="col-md-11">
                                            <input id="title" type="text" class="form-control" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">内容</label>
                                        <div class="col-md-11">
                                            <script id="content" name="content" type="text/plain"></script>
                                            <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.config.js"></script>
                                            <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.all.js"></script>
                                            <script type="text/javascript">
                                                var ue = UE.getEditor('content', {
                                                    zIndex: 6600,
                                                    initialFrameHeight: 200
                                                });
                                            </script>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <input type="hidden" id="account_id" value="">
                                    <input type="hidden" id="platform_order_id" value="">
                                </div>
                                <!--</form>-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">取消</button>
                <button type="button" class="btn send btn-primary waves-effect waves-light">发送</button>
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

    #contact_buyer {
        top: 200px;
    }
</style>
<script>
    //点击设置差评原因   处理状态按钮
    $(document).on('click', '.not-set', function () {
        var id = $(this).attr('data');//feedbackId
        var type = $(this).attr('data1');//类型 1:差评原因  2：处理状态
        var statusId = $(this).attr('data2');//根据状态
        var reasonId = $(this).attr('data3');//纠纷差评原因
        if (type == 1) {
            $("#myModalLabel").html('Review差评原因 <a target="_blank" href="<?php echo Url::toRoute(['/systems/basicconfig/index']) . '?BasicConfigSearch[parent_id]=34' ?>">管理Reviw差评原因</a>');
            $(".for_label").html('原因：<span class="text-danger">*</span>');
            $("#reason_id").val(reasonId);
            $(".div_reason").hide();
            $(".div_step").show();
        } else {
            $("#myModalLabel").html('Review处理状态 <a target="_blank" href="<?php echo Url::toRoute(['/systems/basicconfig/index']) . '?BasicConfigSearch[parent_id]=35' ?>">管理Revies跟进状态</a>');
            $(".for_label").html('状态：<span class="text-danger">*</span>');
            $("#step_id").val(statusId);
            $(".div_reason").show();
            $(".div_step").hide();
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

        //如果type=1 纠纷原因则 reason_id必选    如果type=2则跟进状态必选
        if (type_id == 1 && reason_id == 0) {
            layer.msg('请选择Review差评原因!');
            return false;
        }

        if (type_id == 2 && step_id == 0) {
            layer.msg('请选择Review处理状态!');
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
                    //                    window.refreshTable("/mails/amazonreviewdata/index");
                    window.location.reload();
                } else {
                    layer.msg(data.info, {icon: 5});
                }
            }
        });
    });

    //联系买家  contact_buyer
    $(document).on('click', '.contact_buyer', function () {
        var accountId = $(this).attr('data');//账号ID
        var custEmail = $(this).attr('data1');//收件人邮箱
        var platformOrderId = $(this).attr('data2');

        if (accountId == "" || custEmail == "") {
            layer.alert('数据不全');
            return false;
        }

        //获取发件邮箱
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['/mails/amazonreviewdata/getsendemail']) ?>',
            data: {'account_id': accountId},
            success: function (data) {
                $("#sender_email").val(data);
            }
        });

        $("#recipient_email").val(custEmail);
        $("#account_id").val(accountId);
        $("#platform_order_id").val(platformOrderId);

        $("#contact_buyer").modal('show');
    });

    //发送邮件
    $(document).on('click', '.send', function () {
        var sender_email = $("#sender_email").val();
        var receive_email = $("#recipient_email").val();
        var account_id = $("#account_id").val();
        var platform_order_id = $("#platform_order_id").val();
        var subject = $("#title").val();
        var ue = UE.getEditor('content');
        var reply_content_en = ue.getContent();

        if (sender_email == "") {
            layer.msg('发件人未设置!');
            return false;
        }

        if (receive_email == "") {
            layer.msg('收件人未设置');
            return false;
        }

        if (subject == "") {
            layer.msg('主题未设置');
            return false;
        }

        if (reply_content_en == "") {
            layer.msg('邮件内容为空');
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['/mails/amazonreviewdata/sendemail']) ?>',
            data: {
                'sender_email': sender_email,
                'receive_email': receive_email,
                'subject': subject,
                'reply_content_en': reply_content_en,
                'account_id': account_id,
                'platform_order_id': platform_order_id
            },
            success: function (data) {
                if (data.bool) {
                    layer.msg(data.msg, {icon: 6, time: 1000});
                    $("#contact_buyer").modal('hide');
                } else {
                    console.log(data.msg);
                    layer.msg(data.msg, {icon: 5, time: 10000});
                }
            }
        });

    });

    //查看日志
    $(document).on('click', '.view_log', function () {
        var id = $(this).attr('data2');//reviewData ID
        var asin = $(this).attr('data1');//asin
        $("#custom-width-modalLabel").html(asin + " 操作日志");

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

    /**
     * 下载数据相关操作
     */
    $("#download").click(function () {
        var account_id = $("select[name='AmazonReviewDataSearch[accountId]']").val();
        var reviewDate = $("input[name='AmazonReviewDataSearch[reviewDate]']").val();
        var asin = $("input[name='AmazonReviewDataSearch[asin]']").val();
        var customerName = $("input[name='AmazonReviewDataSearch[customerName]']").val();
        var star = $("select[name='AmazonReviewDataSearch[star]']").val();
        var title = $("input[name='AmazonReviewDataSearch[title]']").val();
        var review_status = $("select[name='AmazonReviewDataSearch[review_status]']").val();
        var follow_status = $("select[name='AmazonReviewDataSearch[follow_status]']").val();
        var is_station = $("input[name='AmazonReviewDataSearch[is_station]']").val();
        var url = '<?php echo Url::toRoute(['download'])?>';
        var selectIds = $("#review_grid").yiiGridView("getSelectedRows");
        //如果选中则只下载选中数据
        if (selectIds != "") {
            url += '?selectIds=' + selectIds;
        } else {
            url += '?account_id=' + account_id + '&reviewDate=' + reviewDate
                + '&asin=' + asin + '&customerName=' + customerName + '&star=' + star
                + '&title=' + title + '&review_status=' + review_status + '&follow_status=' + follow_status
                + '&is_station=' + is_station;
        }
        window.open(url);
    });


</script>