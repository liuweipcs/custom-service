<?php

use yii\bootstrap\ActiveForm;
use app\modules\orders\models\Order;
use app\modules\systems\models\BasicConfig;
use yii\helpers\Url;

?>
<style type="text/css">

</style>
<div class="popup-wrapper">
    <div class="popup-body">
        <div class="tab-content">
            <?php
            if (!empty($info) && !empty($info['info'])):?>
                <p>订单号：<?= $info['info']['order_id']; ?></p>
                <div class="panel-body col-lg-12" style="background:#F1F6FC">
                    <div class="col-lg-3">
                        <p>金额：<?= $info['info']['currency'] . $info['info']['total_price'] . '(商品：' . $info['info']['subtotal_price'] . '+运费：' . $info['info']['ship_cost']; ?></p>
                        <p>买家：<?= $info['info']['buyer_id']; ?>&nbsp;&nbsp;
                            <?php if (!empty($info['info']['email'])) {
                                echo '<a _width="95%" _height="95%" class="edit-button" href="/mails/ebayreply/initiativeadd?order_id=' . $info['info']['order_id'] . '&platform=EB">发送消息</a>';
                            } ?>
                        </p>
                        <p>发货地址：
                            <?php echo $info['info']['ship_name']; ?>
                            (tel:<?php echo $info['info']['ship_phone']; ?>)<br>
                            <?php echo $info['info']['ship_street1'] . ',' . ($info['info']['ship_street2'] == '' ? '' : $info['info']['ship_street2'] . ',') . $info['info']['ship_city_name']; ?>,
                            <?php echo $info['info']['ship_stateorprovince']; ?>,
                            <?php echo $info['info']['ship_zip']; ?>,<br/>
                            <?php echo $info['info']['ship_country_name']; ?>
                        </p>
                        <p>发货仓库：<?= isset($info['info']['warehouse_id']) && isset($warehouseList[$info['info']['warehouse_id']]) ? $warehouseList[$info['info']['warehouse_id']] : ''; ?></p>
                        <p>邮寄方式：<?php foreach ($logistics as $ship_code => $logistic) {
                                if ($ship_code == $info['info']['ship_code']) {
                                    echo $logistic;
                                    break;
                                }
                            } ?></p>
                        <p>跟 踪 号：<?php echo $info['info']['track_number']; ?></p>
                    </div>
                    <div class="col-lg-9">
                        <table class="table table-bordered">
                            <caption>订单号：<?= $info['info']['order_id']; ?></caption>
                            <thead>
                            <tr style="background:#B4D1EE;">
                                <th>订单号</th>
                                <th>item</th>
                                <th>商品</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr style="background:white;">
                                <td><?= $info['info']['order_id']; ?></td>
                                <td><?= $ebayFeedbackModel->item_id; ?></td>
                                <td><?php
                                    foreach ($info['product'] as $product) {
                                        if ($product['item_id'] == $ebayFeedbackModel->item_id) {
                                            echo '<img style="border:1px solid #ccc;padding:2px;width:60px;height:60px;" src="' . Order::getProductImageThub($product['sku']) . '" alt="' . $product['sku'] . '" />' . $product['picking_name'];
                                        }
                                    }
                                    ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($ebayFeedbackModel->comment_text) {
                $allConfig = BasicConfig::getAllConfigData();
                ?>

                <div class="col-lg-12" style="color: #3c763d;background-color: #dff0d8;border-color: #d6e9c6; float:left; margin-top: 15px; margin-bottom: 15px;">
                    <div class="col-xs-12">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>客户留评</th>
                                    <?php if (!empty($ebayFeedbackModel->department_id) && array_key_exists($ebayFeedbackModel->department_id, $allConfig)) { ?>
                                        <th>所属部门</th>
                                    <?php } ?>
                                    <th>差评原因</th>
                                    <th>跟进状态</th>
                                    <th>备注</th>
                                    <th>发送更改链接时间</th>
                                    <th>留评时间</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><?php echo $ebayFeedbackModel->comment_text; ?></td>
                                    <?php if (!empty($ebayFeedbackModel->department_id) && array_key_exists($ebayFeedbackModel->department_id, $allConfig)) { ?>
                                        <td><?php echo $allConfig[$ebayFeedbackModel->department_id]; ?></td>
                                    <?php } ?>
                                    <td>
                                        <?php
                                        if (!empty($ebayFeedbackModel->reason_id) && array_key_exists($ebayFeedbackModel->reason_id, $allConfig)) {
                                            echo '<span style="cursor:pointer;" data="' . $ebayFeedbackModel->id . '" data3="' . $ebayFeedbackModel->reason_id . '" data4="' . (!empty($ebayFeedbackModel->department_id) ? $ebayFeedbackModel->department_id : '') . '" class="not-set" data1="1" data-toggle="modal" data-target="#myModal">' . $allConfig[$ebayFeedbackModel->reason_id] . '</span>';
                                        } else {
                                            echo '<span style="cursor:pointer;" data="' . $ebayFeedbackModel->id . '" data3="' . $ebayFeedbackModel->reason_id . '" data4="' . (!empty($ebayFeedbackModel->department_id) ? $ebayFeedbackModel->department_id : '') . '" class="not-set" data1="1" data-toggle="modal" data-target="#myModal">(未设置)</span>';
                                        }
                                        ?></td>
                                    <td>
                                        <?php
                                        if (!empty($ebayFeedbackModel->step_id) && array_key_exists($ebayFeedbackModel->step_id, $allConfig)) {
                                            echo '<span style="cursor:pointer;" data="' . $ebayFeedbackModel->id . '" data1="2" data2="' . $ebayFeedbackModel->step_id . '" class="not-set" data-toggle="modal" data-target="#myModal">' . $allConfig[$ebayFeedbackModel->step_id] . '</span>';
                                        } else {
                                            echo '<span style="cursor:pointer;" data="' . $ebayFeedbackModel->id . '" data1="2" data2="' . $ebayFeedbackModel->step_id . '" class="not-set" data-toggle="modal" data-target="#myModal">(未跟进)</span>';
                                        }
                                        ?></td>
                                    <th><span id="remark_<?php echo $ebayFeedbackModel->id; ?>"><?php echo $ebayFeedbackModel->remark; ?></span></th>
                                    <th><?php echo $ebayFeedbackModel->send_link_time; ?></th>
                                    <td><?php echo $ebayFeedbackModel->comment_time; ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php } ?>


            <div class="tab-pane fade in active" id="operating">
                <?php
                $form = ActiveForm::begin([
                    'id' => 'account-form',
                    'layout' => 'horizontal',
                    'action' => Yii::$app->request->getUrl(),
                    'fieldConfig' => [
                        'template' => "<div class=\"col-lg-12\">{input}</div>",
                        'labelOptions' => ['class' => 'col-lg-1 control-label'],
                    ],
                    'enableClientValidation' => false,
                    'validateOnType' => false,
                    'validateOnChange' => false,
                    'validateOnSubmit' => true,
                ]);
                ?>
                <div class="popup-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <?php if (empty($replyModel)): ?>
                                <?php echo $form->field($model, 'response_text')->textarea(['rows' => 7, 'maxlength' => 80, 'placeholder' => "发送给客户的内容"]); ?>
                            <?php else: ?>
                                <?php echo $form->field($model, 'response_text')->textarea(['rows' => 7, 'maxlength' => 80, 'placeholder' => "发送给客户的内容", "readonly" => "readonly", "value" => $replyModel->response_text]); ?>
                            <?php endif; ?>
                            <span style="display: none;" class="count_length">(0/80)字符</span>
                        </div>

                    </div>

                </div>
                <div class="popup-footer">
                    <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
                    <button class="btn btn-default close-button" type="button"><?php echo Yii::t('system', 'Close'); ?></button>
                </div>
            </div>
        </div>
        <?php
        ActiveForm::end();
        ?>
    </div>


    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title" id="myModalLabel"></h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal">
                        <div class="div_step">
                            <div class="form-group">
                                <label for="ship_name" class="col-sm-2 control-label required">部门：<span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-control" name="department_id" id="department_id">
                                        <?php foreach ($departmentList as $key => $val) { ?>
                                            <option value="<?php echo $key; ?>"><?php echo $val; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ship_name" class="col-sm-2 control-label required">原因：<span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-control" name="reason_id" id="reason_id">

                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="div_reason" style="display:none;">
                            <div class="form-group">
                                <label for="ship_name" class="col-sm-2 control-label required for_label">状态：<span class="text-danger">*</span></label>
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
                                <div class="col-md-10"><textarea class="form-control" rows="5" id="remark_content"></textarea></div>
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
            if(department_id){
                department_id = department_id.split(',');
            }
            if(reason_id){
                reason_id = reason_id.split(',');
            }
            
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
                url: '<?php echo Url::toRoute(['/mails/ebayfeedback/setreason']); ?>',
                data: {'id': id, 'type_id': type_id, 'department_id': department_id, 'reason_id': reason_id, 'step_id': step_id, 'text': text},
                success: function (data) {
                    if (data.status) {
                        layer.msg(data.info, {icon: 1,time: 2000},
                            function () {
                               // $('#myModal').modal('hide');
                                //$('#layui-layer2').hide();
                                window.location.reload();
                                window.parent.location.reload();
                                var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                                parent.layer.close(index); //再执行关闭 
                                //parent.refreshTable("/mails/ebayfeedback/list"); 
                            }
                            );

                    } else {
                        layer.msg(data.info, {icon: 5});
                    }
                }
            });
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

        $(function () {
            var content = "内容(0/80)字符";
            $('#ebayfeedbackresponse-response_text').on('keyup', function () {
                $(".count_length").css('display', 'block');
                var contentLength = $(this).val().length;
                if (contentLength < 80) {
                    var content = $('.count_length').text('内容(' + contentLength + '/80)');
                    $("label[for='ebayfeedbackresponse-response_text']").html(content);
                } else {
                    var text = "内容<span style='color:red;'>(80/80)</span>";
                    var content = $('.count_length').html(text);
                }
            });
        });
    </script>
