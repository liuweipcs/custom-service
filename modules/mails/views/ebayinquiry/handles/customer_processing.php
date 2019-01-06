<?php

use app\modules\mails\models\EbayInquiryResponse;
use app\modules\mails\models\MailTemplate;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\accounts\models\Platform;
use yii\helpers\Url;
use app\modules\orders\models\Order;
use yii\helpers\Html;
use kartik\select2\Select2;

?>
<style>
    p {
        margin: 0px 0px 5px;
        font-size: 13px;
    }

    .list-group {
        margin-bottom: 0px;
    }

    .list-group-item {
        padding: 5px 5px;
        font-size: 13px;
    }

    .table {
        margin-bottom: 10px;
    }

    .btn-sm {
        line-height: 1;
    }

    .mail_template_area a {
        cursor: pointer;
    }

    .col-sm-5 {
        width: auto;
    }

    .tr_q .dropdown-menu {
        left: -136px;
    }

    .tr_h .dropdown-menu {
        left: -392px;
    }

    #wrapper .popup-body {
        padding-top: 0px;
    }
</style>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">纠纷详情&处理</h4>
    </div>
    <div id="collapseThree" class="panel-collapse">
        <div class="panel-body">
            <div class="col-xs-12">
                <p>纠纷编号:<?php echo $model->inquiry_id; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;状态:<?php echo $model->status; ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;创建时间:<?php echo $model->creation_date; ?></p>
                <p>售后单号:
                    <?php
                    if (isset($info['info']) && !empty($info['info'])) {
                        $afterSalesOrders = AfterSalesOrder::find()->select('after_sale_id')->where(['order_id' => $info['info']['order_id']])->asArray()->all();
                        if (empty($afterSalesOrders)) {
                            echo '<span>无售后处理单</span>';
                        } else {
                            echo '<span>' . implode(',', array_column($afterSalesOrders, 'after_sale_id')) . '</span>';
                        }
                    } else {
                        echo '<span>无售后处理单</span>';
                    }

                    if (!empty($info))
                        echo '<a style="margin-left:10px" _width="90%" _height="90%" class="edit-button" href="' . Url::toRoute(['/aftersales/order/add', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">新建售后单</a>';

                    if (!empty($info) && $info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) {
                        echo '<a style="margin-left:10px" _width="30%" _height="60%" class="edit-button" href="' . Url::toRoute(['/orders/order/cancelorder', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">永久作废</a>';
                        echo '&nbsp;&nbsp;<a _width="30%" _height="60%" class="edit-button" href="' . Url::toRoute(['/orders/order/holdorder', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">暂时作废</a>';
                    } else if (!empty($info) && $info['info']['complete_status'] == Order::COMPLETE_STATUS_HOLD) {
                        echo '<a style="margin-left:10px" _width="30%" _height="60%" class="edit-button" href="' . Url::toRoute(['/orders/order/cancelorder', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">永久作废</a>';
                    }

                    if (!empty($info) && $info['info']['complete_status'] == Order::COMPLETE_STATUS_HOLD) {
                        echo '<a confirm="确定取消暂时作废该订单？" class="ajax-button" href="' . Url::toRoute(['/orders/order/cancelholdorder', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">取消暂时作废</a>';
                    }
                    ?>
                </p>
                <p>买家期望:<?php echo $model->buyer_initial_expected_resolution; ?></p>
                <p>无需自动退款
                    <?php
                    switch ($model->auto_refund) {
                        case 0:
                            $auto_refund_after_case_attribute = '';
                            break;
                        case 1:
                            $auto_refund_after_case_attribute = 'checked="checked"';
                            break;
                        case 2:
                            $auto_refund_after_case_attribute = 'checked="checked"  disabled="disabled"';
                    }
                    ?>
                    <?php if ($model->state == 'CLOSED' || in_array($model->status, ['CLOSED', 'CLOSED_WITH_ESCALATION', 'CS_CLOSED'])) $auto_refund_after_case_attribute .= ' disabled="disabled"'; ?>
                    <input <?php echo $auto_refund_after_case_attribute; ?> type="checkbox"
                                                                            class="auto_refund_after_case"/></p>
                <script type="application/javascript">
                    <?php if ($model->auto_refund != 2) { ?>
                    $(function () {
                        var id = '<?php echo $model->id;?>';
                        $('.auto_refund_after_case').click(function (data) {
                            var auto_refund = $('.auto_refund_after_case_actual').val();
                            $.post('<?php echo Url::toRoute(['/mails/ebayinquiry/changeautorefund']);?>', {
                                'id': id,
                                'auto_refund': auto_refund
                            }, function (data) {
                                switch (data.status) {
                                    case 'error':
                                        layer.msg(data.message, {
                                            icon: 2,
                                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                        });
                                        return;
                                    case 'success':
                                        if (auto_refund == 1) {
                                            $('.auto_refund_after_case_actual').val(0);
                                        }
                                        else {
                                            $('.auto_refund_after_case_actual').val(1);
                                        }
                                }
                            }, 'json');
//                                        $('.auto_refund_after_case_actual').val(Number(this.checked));
                        });
                    });
                    <?php } ?>
                </script>
            </div>

            <div class="col-xs-12">
                <?php
                $item_id = $model->item_id;
                $account_id = $model->account_id;
                $buyer_id = $model->buyer;

                $subject_model = \app\modules\mails\models\EbayInboxSubject::findOne(['buyer_id' => $buyer_id, 'item_id' => $item_id, 'account_id' => $account_id]);
                ?>
                <h5 class="m-b-30 m-t-0">
                    互动记录
                    <?php if ($subject_model) {
                        echo '&nbsp;&nbsp;<a href="/mails/ebayinboxsubject/detail?id=' . $subject_model->id . '" target="_blank">查看站内信记录</a>';
                    } ?>
                </h5>

                <?php if (!empty($detailModel)) { ?>
                    <ul class="list-group" style="height: auto; max-height:250px;overflow-y:scroll;">
                        <?php foreach ($detailModel as $key => $detail) { ?>
                            <li class="list-group-item" <?php if ($key + 1 == count($detailModel)) {
                                echo "id='section-6'";
                            } ?>>
                                <?php echo isset($detail->date) ? date('Y-m-d H:i:s', strtotime($detail->date) + 28800) : '', '&nbsp;&nbsp;&nbsp;&nbsp;', '<span style="color:#FF7F00">', $detail::$actorMap[$detail->actor], '</span>', '&nbsp;&nbsp;&nbsp;&nbsp;', $detail->action; ?>
                                <?php if (!empty($detail->description)) { ?>
                                    <table class="table-bordered table_div_<?php echo $key; ?>">
                                        <tbody>
                                        <tr class="ebay_dispute_message_board">
                                            <td style="width:100px;text-align: center;">留言</td>
                                            <td><?php echo !empty($detail->description) ? $detail->description . '<a style="cursor: pointer;" data1 = "div_' . $key . '" data="' . $detail->description . '" class="transClik">&nbsp;&nbsp;点击翻译</a>' : ""; ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <h5>请在<?php echo '<b style="color:red;">' . $model->seller_make_it_right_by_date . '</b>'; ?>前回复</h5>
                <!--处理操作-->
                <?php if ($model->state != 'CLOSED' && !in_array($model->status, ['CLOSED', 'CLOSED_WITH_ESCALATION', 'CS_CLOSED'])): ?>
                    <div class="popup-wrapper">
                        <?php
                        $responseModel = new EbayInquiryResponse();
                        $form = yii\bootstrap\ActiveForm::begin([
                            'id' => 'account-form',
                            'layout' => 'horizontal',
                            'action' => Yii::$app->request->getUrl(),
                            'enableClientValidation' => false,
                            'validateOnType' => false,
                            'validateOnChange' => false,
                            'validateOnSubmit' => true,
                        ]);
                        ?>
                        <link href="<?php echo yii\helpers\Url::base(true); ?>/laydate/need/laydate.css"
                              rel="stylesheet">
                        <link href="<?php echo yii\helpers\Url::base(true); ?>/laydate/skins/default/laydate.css"
                              rel="stylesheet">
                        <script src="<?php echo yii\helpers\Url::base(true); ?>/laydate/laydate.js"></script>
                        <?php if (count($detailModel) == 0) echo '<b style="color:red;">无互动记录，可能为return case，如果操作失败，请到后台处理，case关闭之后请联系技术人员修改纠纷状态！</b>' ?>
                        <div class="popup-body">
                            <div class="row">
                                <input class="auto_refund_after_case_actual" type="hidden"
                                       name="EbayInquiry[auto_refund]" value="<?= $model->auto_refund ?>"/>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="2">全额退款
                                    <div class="type_map_params">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <div class="col-sm-3">
                                                    <label for="ship_name" class=" control-label required">责任所属部门：<span
                                                                class="text-danger">*</span></label>
                                                    <select name="EbayInquiryResponse[department_id]" id="department_id" class="form-control"
                                                            size="12" multiple="multiple">
                                                    </select>
                                                </div>
                                                <div class="col-sm-9">
                                                    <label for="ship_name" class="control-label required">原因类型：<span
                                                                class="text-danger">*</span></label>
                                                    <select name="EbayInquiryResponse[reason_code]" id="reason_id" class="form-control"
                                                            size="12" multiple="multiple">
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="order_id" value="<?php if (!empty($info['info'])) echo $info['info']['order_id']; ?>">

                                        <div class="row" style="margin-top: 5px;">
                                            <label for="ship_name" class="col-sm-2 control-label">备注：</label>
                                            <div class="col-sm-9">
                                                <textarea class="form-control" name="EbayInquiryResponse[content][2]" rows="5" cols="6"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="3">提供发货信息
                                    <div class="type_map_params">
                                        承运人：<input type="text" name="EbayInquiryResponse[shipping_carrier_name]"/>
                                        发货时间：<input class="laydate-icon" id="ebay_inquiry_history_shipping_date"
                                                    value="" name="EbayInquiryResponse[shipping_date]"/>
                                        跟踪号：<input type="text" name="EbayInquiryResponse[tracking_number]">
                                        <br/>
                                        <textarea name="EbayInquiryResponse[content][3]" rows="5" cols="50"></textarea>
                                    </div>
                                </div>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="4">升级
                                    <div class="type_map_params">
                                        原因：<select class="form-control" name="EbayInquiryResponse[escalation_reason]">
                                            <?php foreach (EbayInquiryResponse::$escalationReasonMap as $escalationReasonK => $escalationReasonV): ?>
                                                <option value="<?= $escalationReasonK ?>"><?= $escalationReasonV ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <textarea name="EbayInquiryResponse[content][4]" rows="5" cols="50"></textarea>
                                    </div>
                                </div>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="1">发送留言
                                    <div class="type_map_params">
                                        <div style="margin-bottom: 10px">
                                            <div class="row">
                                                <div class="col-lg-5">
                                                    <div class="input-group">
                                                        <input type="text" class="mail_template_title_search_text"
                                                               placeholder="模板编号搜索">
                                                        <!--<span class="input-group-btn">-->
                                                        <button class="btn-sm btn btn-default mail_template_title_search_btn"
                                                                type="button">Go!
                                                        </button>
                                                        <!--</span>-->
                                                    </div>
                                                </div>
                                                <?php
                                                $warehouseList = \app\modules\orders\models\Warehouse::getWarehouseListAll();
                                                $order_id = isset($info['orderPackage'][0]['order_id']) ? $info['orderPackage'][0]['order_id'] : '';
                                                $warehouse_id = isset($info['orderPackage'][0]['warehouse_id']) ? $info['orderPackage'][0]['warehouse_id'] : 0;

                                                $current_order_warehouse_name = array_key_exists($warehouse_id, $warehouseList) ?
                                                    $warehouseList[$warehouse_id] : '';

                                                echo "<input type='hidden' name='current_order_warehouse_id' value='$warehouse_id'>";
                                                echo  "<input type='hidden' name='current_order_id' value='$order_id'>";
                                                echo "<input type='hidden' name='current_order_warehouse_name' value='$current_order_warehouse_name'>";
                                                ?>
                                                <div class="col-lg-5">
                                                    <div class="input-group">
                                                        <input type="text" class="mail_template_search_text"
                                                               placeholder="消息模板搜索">
                                                        <!--<span class="input-group-btn">-->
                                                        <a class="btn btn-sm btn-default mail_template_search_btn">搜索</a>
                                                        <!--</span>-->
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success" id="return_info">获取退货信息</button>
                                            </div>
                                        </div>
                                        <!--<div class="panel panel-default">-->
                                        <div class="mail_template_area">
                                            <?php
                                            $mailTemplates = MailTemplate::getMailTemplateDataAsArrayByUserId(Platform::PLATFORM_CODE_EB);
                                            foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName) {
                                                echo '<a class="mail_template_unity" value="' . $mailTemplatesId . '">' . $mailTemplateName . '</a> ';
                                            }
                                            ?>
                                        </div>
                                        <!--</div>-->

                                        <?php echo Html::hiddenInput('sl_code', "", ['id' => 'sl_code']); ?>
                                        <?php echo Html::hiddenInput('tl_code', "", ['id' => 'tl_code']); ?>
                                        <div><textarea id='leave_message' name="EbayInquiryResponse[content][1]"
                                                       rows="4" cols="98"></textarea></div>
                                        <div class="row"
                                             style="text-align: center;font-size: 13px;font-weight: bold;margin-top: 10px;margin-bottom: 10px;">
                                            <div class="col-sm-5 tr_q">
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-default" type="button"
                                                            onclick="changeCode(3, 'en', '', $(this))">英语
                                                    </button>
                                                    <button class="btn btn-sm btn-default" type="button"
                                                            onclick="changeCode(3, 'fr', '', $(this))">法语
                                                    </button>
                                                    <button class="btn btn-sm btn-default" type="button"
                                                            onclick="changeCode(3, 'de', '', $(this))">德语
                                                    </button>
                                                    <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                                        <div class="btn-group">
                                                            <button data-toggle="dropdown"
                                                                    class="btn btn-default btn-sm dropdown-toggle"
                                                                    type="button" aria-expanded="false" id="sl_btn">更多&nbsp;&nbsp;<span
                                                                        class="caret"></span></button>
                                                            <ul class="dropdown-menu language">
                                                                <?php foreach ($googleLangCode as $key => $value) { ?>
                                                                    <li>
                                                                        <a onclick="changeCode(1, '<?php echo $key; ?>', '<?php echo $value; ?>', $(this))"><?php echo $value; ?></a>
                                                                    </li>
                                                                <?php } ?>
                                                            </ul>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="fa-hover col-sm-1" style="width:0px;line-height: 30px;"><a><i
                                                            class="fa fa-exchange"></i></a></div>
                                            <div class="col-sm-5 tr_h">
                                                <div class="btn-group">
                                                    <button class="btn-sm btn btn-default" type="button"
                                                            onclick="changeCode(4, 'en', '', $(this))">英语
                                                    </button>
                                                    <button class="btn btn-sm btn-default" type="button"
                                                            onclick="changeCode(4, 'fr', '', $(this))">法语
                                                    </button>
                                                    <button class="btn btn-sm btn-default" type="button"
                                                            onclick="changeCode(4, 'de', '', $(this))">德语
                                                    </button>
                                                    <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                                        <div class="btn-group">
                                                            <button data-toggle="dropdown"
                                                                    class="btn btn-default btn-sm dropdown-toggle"
                                                                    type="button" aria-expanded="false" data=""
                                                                    id="tl_btn">更多&nbsp;&nbsp;<span
                                                                        class="caret"></span></button>
                                                            <ul class="dropdown-menu language">
                                                                <?php foreach ($googleLangCode as $key => $value) { ?>
                                                                    <li>
                                                                        <a onclick="changeCode(2, '<?php echo $key; ?>', '<?php echo $value; ?>', $(this))"><?php echo $value; ?></a>
                                                                    </li>
                                                                <?php } ?>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="col-sm-1">
                                                <button class="btn btn-sm btn-primary artificialTranslation"
                                                        type="button" id="translations_btn">翻译 [ <b id="sl_name"></b> -
                                                    <b id="tl_name"></b> ]
                                                </button>
                                            </div>
                                        </div>
                                        <div><textarea id='leave_message_en' name="EbayInquiryResponse[content][1_en]"
                                                       rows="4" cols="98"></textarea></div>
                                    </div>
                                </div>
                                <script>
                                    void function () {
                                        laydate({
                                            elem: '#ebay_inquiry_history_shipping_date',
                                            format: 'YYYY/MM/DD hh:mm:ss',
                                        })
                                        $(function () {
                                            $('[name="EbayInquiryResponse[type]"]').click(function () {
                                                $('.type_map_params').hide();
                                                $(this).siblings('.type_map_params').show();
                                            });
                                        });
                                    }();
                                </script>
                            </div>
                        </div>
                        <div class="popup-footer">
                            <button class="btn btn-primary ajax-submit"
                                    type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
                            <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
                        </div>
                        <?php
                        yii\bootstrap\ActiveForm::end();
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>

    $(document).ready(function ($) {
        departmentList = <?php echo $departmentList?>;
        var rightHtml = "";
        for (var i in departmentList) {
            rightHtml += '<option value="' + departmentList[i].depart_id + '">' + departmentList[i].depart_name + '</option>' + "\n";
        }
        $('#department_id').empty().html(rightHtml);
    });

    //模板ajax
    $('.mail_template_area').delegate('.mail_template_unity', 'click', function () {
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>', {'num': $(this).attr('value')}, function (data) {
            switch (data.status) {
                case 'error':
                    layer.msg(data.message, {
                        icon: 2,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    });
                    return;
                case 'success':
                    $('#leave_message').val(data.content);
//                        UE.getEditor('editor').setContent(data.content);
            }
        }, 'json');
    });

    //模板搜索
    $('.mail_template_search_btn').click(function () {
        var templateName = $.trim($('.mail_template_search_text').val());
        if (templateName.length == 0) {
            layer.msg('搜索名称不能为空。', {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplate']);?>', {'name': templateName}, function (data) {
            switch (data.status) {
                case 'error':
                    layer.msg(data.message, {
                        icon: 2,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    });
                    return;
                case 'success':
                    var templateHtml = '';
                    for (var i in data.content) {
                        templateHtml += '<a class="mail_template_unity" value="' + i + '">' + data.content[i] + '</a>';
                    }
                    $('.mail_template_area').html(templateHtml);
            }
        }, 'json');
    });


    //模板编号搜索
    $('.mail_template_title_search_btn').on('click', template_title);
    $('.mail_template_title_search_text').bind('keypress', function () {
        if (event.keyCode == "13") {
            template_title();
        }
    });

    function template_title() {
        var templateTitle = $.trim($('.mail_template_title_search_text').val());
        if (templateTitle.length == 0) {
            layer.msg('搜索内容不能为空。', {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplatetitle']); ?>', {
            'name': templateTitle,
            'platform_code': 'EB'
        }, function (data) {
            if (data.code == 200) {
                $('#leave_message').val(data.data);
            } else {
                layer.msg(data.message, {
                    icon: 2,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                });
                return;
            }
        }, 'json');
    }


    /**
     * 点击选择语言将选中语言赋值给对应控件
     * @param {type} type 类型
     * @param {type} code 语言code
     * @param {type} name 语言名称
     * @param {type} that 当前对象
     * @author allen <2018-1-11>
     * */
    function changeCode(type, code, name = "", that = "") {
        if (type == 1) {
            $("#sl_code").val(code);
            $("#sl_btn").html(name + '&nbsp;&nbsp;<span class="caret"></span>');
            that.css('font-weight', 'bold');
            $("#sl_name").html(name);
        } else if (type == 2) {
            $("#tl_code").val(code);
            $("#tl_btn").html(name + '&nbsp;&nbsp;<span class="caret"></span>');
            $("#tl_name").html(name);
            that.css('font-weight', 'bold');
        } else if (type == 3) {
            var name = that.html();
            $("#sl_code").val(code);
            $("#sl_name").html(name);
        } else {
            var name = that.html();
            $("#tl_code").val(code);
            $("#tl_name").html(name);
        }
    }

    /**
     * 绑定翻译按钮 进行手动翻译操作(系统未检测到用户语言)
     * @author allen <2018-1-11>
     **/
    $('.artificialTranslation').click(function () {
        var sl = $("#sl_code").val();
        var tl = $("#tl_code").val();
        var content = $.trim($("#leave_message").val());
        if (sl == "") {
            layer.msg('请选择需要翻译的语言类型');
            return false;
        }

        if (tl == "") {
            layer.msg('请选择翻译目标的语言类型');
            return false;
        }

        if (content.length <= 0) {
            layer.msg('请输入需要翻译的内容!');
            return false;
        }
        //ajax请求
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['ebayinboxsubject/translate']); ?>',
            data: {'sl': sl, 'tl': tl, 'content': content},
            success: function (data) {
                if (data) {
                    $("#leave_message_en").val(data);
                }
            }
        });
    });

    /**
     * 回复客户邮件内容点击翻译(系统检测到用户语言)
     * @author allen <2018-1-11>
     */
    $(".transClik").click(function () {
        var sl = 'auto';
        var tl = 'en';
        var message = $(this).attr('data');
        var tag = $(this).attr('data1');
        var that = $(this);
        if (message.length == 0) {
            layer.msg('获取需要翻译的内容有错!');
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['ebayinboxsubject/translate']); ?>',
            data: {'sl': sl, 'tl': tl, 'returnLang': 1, 'content': message},
            success: function (data) {
                if (data) {
                    var htm = '<tr class="ebay_dispute_message_board ' + tag + '"><td style="text-align: center;"><b style="color:red;">' + data.code + '</b></td><td><b style="color:green;">' + data.text + '</b></td></tr>';
                    $(".table_" + tag).append(htm);
                    $("#sl_code").val('en');
                    $("#sl_name").html('英语');
                    $("#tl_code").val(data.googleCode);
                    $("#tl_name").html(data.code);
                    that.remove();
                }
            }
        });
    });

    $(document).on("change", "#department_id", function () {
        var id = $(this).val();
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
                    $("#reason_id").empty();
                    $("#reason_id").append(html);
                }
            });
        } else {
            $("#reason_id").empty();
            $("#reason_id").append(html);
        }
    });

    //点击获取退货信息
    $('#return_info').click(function () {
        var current_order_id = $("input[name='current_order_id']").val();
        var rule_warehouse_id = $("input[name='current_order_warehouse_id']").val();
        var current_order_warehouse_name = $("input[name='current_order_warehouse_name']").val();

        var warehouse_1 = '递四方';
        var warehouse_2 = '谷仓';
        var warehouse_3 = '万邑通';
        var warehouse_4 = '旺集';

        if (!rule_warehouse_id) {
            layer.msg("暂无仓库信息", {icon: 5});
            return;
        }
        if (!current_order_id) {
            layer.msg("暂无订单信息", {icon: 5});
            return;
        }
        if (current_order_warehouse_name.match(warehouse_1)
            || current_order_warehouse_name.match(warehouse_2)
            || current_order_warehouse_name.match(warehouse_3)
            || current_order_warehouse_name.match(warehouse_4)) {

            //弹出框输入追踪号
            layer.prompt({title: '追踪号', value: '', formType: 0}, function (tracking_no, index) {
                $.ajax({
                    type: "POST",
                    dataType: "JSON",
                    url: '<?php echo Url::toRoute(['/mails/refundtemplate/getrefundinfo']); ?>',
                    data: {
                        'rule_warehouse_id': rule_warehouse_id,
                        'order_id': current_order_id,
                        'tracking_no': tracking_no
                    },
                    success: function (data) {
                        switch (data.status) {
                            case 'error':
                                layer.msg(data.message, {icon: 5});
                                return;
                            case 'success':
                                var html = "";
                                html += 'rma:' + data.content.is_get_rma;
                                html += "\n",
                                    html += 'consignee:' + data.content.refund_name;
                                html += "\n",
                                    html += 'address:' + data.content.refund_address;
                                var old_content = $('#leave_message').val();
                                if (old_content !== '') {
                                    $('#leave_message').val(html + '\n' + old_content);
                                } else {
                                    $('#leave_message').val(html + '\n' + old_content);
                                }
                                $(this).attr('disabled', true);
                        }

                    }
                });

                layer.close(index);
            });

        } else {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/mails/refundtemplate/getrefundinfo']); ?>',
                data: {
                    'rule_warehouse_id': rule_warehouse_id,
                    'order_id': current_order_id,
                },
                success: function (data) {
                    switch (data.status) {
                        case 'error':
                            layer.msg(data.message, {icon: 5});
                            return;
                        case 'success':
                            var html = "";

                            html += 'rma:' + data.content.is_get_rma;
                            html += "\n",
                                html += 'consignee:' + data.content.refund_name;
                            html += "\n",
                                html += 'address:' + data.content.refund_address;
                            var old_content = $('#leave_message_en').val();
                            if (old_content !== '') {
                                $('#leave_message_en').val(html + '\n' + old_content);
                            } else {
                                $('#leave_message_en').val(html + '\n' + old_content);
                            }
                            $(this).attr('disabled', true);
                    }

                }
            });

        }
    });
</script>