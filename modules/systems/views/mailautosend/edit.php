<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use app\modules\mails\models\MailTemplate;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\MailTemplateCategory;
use app\modules\orders\models\Logistic;
use app\modules\systems\models\Country;
use yii\helpers\Html;
use app\modules\systems\models\MailFilterRule;
$startIndex = 0;
?>
<div class="popup-wrapper">
    <style>
        #addMailFilterManage {
            margin: 20px auto 0 auto;
            width: 90%;
            height: auto;
            border-collapse: collapse;
        }

        #addMailFilterManage td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #addMailFilterManage td.col1 {
            width: 150px;
            text-align: right;
            font-weight: bold;
        }

        #addMailFilterManage td.col2 {
            width: 190px;
        }

        #addMailFilterRule {
            width: 100%;
        }

        #addMailFilterRule td {
            border: none;
        }

        #addMailFilterRule td.col1 {
            width: 180px;
        }

        #addMailFilterRule td span.glyphicon-remove {
            font-size: 24px;
            color: red;
            cursor: pointer;
        }

        #hideMailFilterRule {
            display: none;
        }
    </style>
    <?php
    $form = ActiveForm::begin([
        'id'     => 'addMailFilterManageForm',
        'action' => Url::toRoute(['/systems/mailautosend/edit']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addMailFilterManage">
            <tr>
                <td class="col1">规则名称：</td>
                <td colspan="4">
                    <div class="col-xs-2" style="padding-right:0px;">
                        <input type="text" name="rule_name" class="form-control" value="<?php echo $info['rule_name']?>">
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">状态：</td>
                <td colspan="3">
                    <input type="radio" name="status" value="1" checked>有效
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="status" value="0">无效
                </td>
            </tr>
            <tr>
                <td class="col1">有效期：</td>
                <td colspan="4">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <?php
                        echo \kartik\datetime\DateTimePicker::widget([
                            'name'          => 'start_time',
                            'options'       => ['placeholder' => '开始时间'],
                            'value'         => $info['start_time'],
                            'pluginOptions' => [
                                'autoclose'      => true,
                                'format'         => 'yyyy-mm-dd hh:ii:ss',
                                'todayHighlight' => true,
                                'todayBtn'       => 'linked',
                            ],
                        ]); ?></div>&nbsp;&nbsp;&nbsp;
                    <div class="col-xs-4" style="padding-left:0px;">
                        <?php
                        echo \kartik\datetime\DateTimePicker::widget([
                            'name'          => 'end_time',
                            'options'       => ['placeholder' => '结束时间'],
                            'value'         => $info['end_time'],
                            'pluginOptions' => [
                                'autoclose'      => true,
                                'format'         => 'yyyy-mm-dd hh:ii:ss',
                                'todayHighlight' => true,
                                'todayBtn'       => 'linked',
                            ],
                        ]); ?>
                    </div>
                    <div class="col-xs-4" style="padding-left:0px;">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="is_permanent" value="<?php echo 2; ?>"> 永久有效
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">渠道来源：</td>
                <td colspan="3">
                    <div class="col-xs-3" style="padding-right:0px;">
                        <?php echo \kartik\select2\Select2::widget([
                            'name'    => 'platform_code',
                            'value'   => $info['platform_code'],
                            'data'    => $platformList,
                            'options' => ['placeholder' => '请选择...']
                        ]);
                        ?>
                    </div>
                    <div class="col-xs-3" style="padding-right:0px;">
                        <?php echo \kartik\select2\Select2::widget([
                            'name'    => 'account_ids',
                            'id'    => 'account_ids',
                            'value'   => $info['account_id'],
                            'data'    => $account_lists,
                            'options' => ['multiple' => true,'placeholder' => '请选择...']
                        ]);
                        ?>
                    </div>
                    <div class="col-xs-3" style="padding-right:0px;">
                        <?php echo \kartik\select2\Select2::widget([
                            'name'    => 'site_codes',
                            'id'    => 'site_codes',
                            'value'   => $info['site'],
                            'data'    => $siteLists,
                            'options' => ['multiple' => true,'placeholder' => '请选择...']
                        ]);
                        ?>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="col1">发件人</td>
                <td colspan="3">
				<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <select name="sender_type" id="" class="form-control">
                            <option value="">请选择发件人</option>
                            <option <?php if (2 == $info['sender_type']){echo "selected";} ?> value="2">不包含</option>
                            <option <?php if (1 == $info['sender_type']){echo "selected";} ?> value="1">包含</option>
                        </select>
                    </div>
                    </div>
					<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <textarea class="form-control" name="sender_content" id="" cols="20" rows="8" placeholder="支持多个，一行一个，如：
test@qq.com
AAAA@qq.com"><?php echo !empty($info['sender_content'])?$info['sender_content']:''?></textarea>
                    </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">主题</td>
                <td colspan="3">
				<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <select name="subject_type" id="" class="form-control col1">
                            <option value="">请选择</option>
                            <option <?php if (2 == $info['subject_type']){echo "selected";} ?> value="2">不包含</option>
                            <option  <?php if (1 == $info['subject_type']){echo "selected";} ?>  value="1">包含</option>
                        </select>
                    </div>
                    </div>
					<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <textarea class="form-control col1" name="subject_content" id="" cols="20" rows="8"
                                  placeholder="支持多个，一行一个，如：
要求取消订单
我想查询使用物品的信息"><?php echo !empty($info['subject_content'])?$info['subject_content']:''?></textarea>
                    </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">邮件正文</td>
                <td colspan="3">
				<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <select name="subject_body_type" id="" class="form-control">
                            <option value="">请选择</option>
                            <option <?php if (2 == $info['subject_body_type']){echo "selected";} ?> value="2">不包含</option>
                            <option <?php if (1 == $info['subject_body_type']){echo "selected";} ?> value="1">包含</option>
                        </select>
                    </div>
                    </div>
					<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <textarea name="subject_body_content" id="" class="form-control" cols="20" rows="8"
                                  placeholder="支持多个，一行一个，如：
要求取消订单
我想查询使用物品的信息"><?php echo !empty($info['subject_body_content'])?$info['subject_body_content']:''?></textarea>
                    </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">发送时间</td>
                <td colspan="4">
                    超过 <input style="width: 20%" class="form-control" type="text" name="send_time" value="<?php echo !empty($info['send_time'])?$info['send_time']:''?>">小时未回复，自动回复
                </td>
            </tr>
            <tr>
                <td class="col1">订单金额</td>
                <td colspan="4">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <input type="text" name="order_minimum_money" class="form-control" placeholder="最低金额"
                               value="<?php echo !empty($info['order_minimum_money'])?$info['order_minimum_money']:''?>">
                    </div>
                    <div class="col-xs-4" style="padding-right:0px;">
                        <input type="text" class="form-control" placeholder="最高金额"
                               name="order_highest_money"
                               value="<?php echo !empty($info['order_highest_money'])?$info['order_highest_money']:''?>">
                    </div>
                    <div class="col-xs-4" style="padding-right:0px;"> CNY</div>
                </td>
            </tr>
            <tr>
                <td class="col1">ERP SKU</td>
                <td colspan="4">
                    <div class="col-xs-2" style="padding-right:0px;">
                        <select name="erp_sku_type" id="" class="form-control">
                            <option value="">请选择</option>
                            <option <?php if (2 == $info['erp_sku_type']){echo "selected";} ?> value="2">不包含</option>
                            <option <?php if (1 == $info['erp_sku_type']){echo "selected";} ?> value="1">包含</option>
                        </select>
                    </div>
                    <div class="col-xs-6" style="padding-left:0px;">
                        <?php echo \kartik\select2\Select2::widget([
                            'name'    => 'erp_sku_content[]',
                            'value'   => $info['erp_sku_content'],
                            'data'    => $sku_lists,
                            'options' => ['multiple' => true,'placeholder' => '请选择...']
                        ]);
                        ?>
                        </select>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="col1">产品ID</td>
                <td colspan="3">
				<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <select name="product_id_type" id="" class="form-control" style="20%">
                            <option value="">请选择发件人</option>
                            <option <?php if (2 == $info['product_id_type']){echo "selected";} ?> value="2">不包含</option>
                            <option <?php if (1 == $info['product_id_type']){echo "selected";} ?> value="1">包含</option>
                        </select>
                    </div>
                    </div>
					<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <textarea class="form-control" style="80%" name="product_id_content" id="" cols="30" rows="10"
                                  placeholder="支持多个，一行一个，如：
202372158373
202372158444"><?php echo !empty($info['product_id_content'])?$info['product_id_content']:''?></textarea>
                    </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="col1">国家</td>
                <td colspan="3">
				<div class="row">
                    <div class="col-xs-2" style="padding-right:0px;">
                        <select name="country_type" id="" class="form-control">
                            <option value="">请选择</option>
                            <option  <?php if (2 == $info['country_type']){echo "selected";} ?> value="2">不包含</option>
                            <option  <?php if (1 == $info['country_type']){echo "selected";} ?> value="1">包含</option>
                        </select>
                    </div>
                    </div>
					<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <?php echo \kartik\select2\Select2::widget([
                            'name'    => 'country',
                            'value'   => (!empty($info['country_content'])?$info['country_content']:''),
                            'id'=>"selectpicker",
                            'data'    => $countryList,
                            'options' => ['multiple' => true,'placeholder' => '请选择...']
                        ]);
                        ?>
                    </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">系统订单号</td>
                <td colspan="3">
				<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <select name="order_id_type" id="" class="form-control">
                            <option value="">请选择系统订单号</option>
                            <option  <?php if (2 == $info['order_id_type']){echo "selected";} ?> value="2">不包含</option>
                            <option  <?php if (1 == $info['order_id_type']){echo "selected";} ?> value="1">包含</option>
                        </select>
                    </div>
                    </div>
					<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <textarea class="form-control" name="order_id_content" id="" cols="20" rows="8"><?php echo !empty($info['order_id_content'])?$info['order_id_content']:''?></textarea>
                    </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">平台订单号</td>
                <td colspan="3">
				<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <select name="platform_order_id_type" id="" class="form-control">
                            <option value="">请选择平台订单号</option>
                            <option <?php if (2 == $info['platform_order_id_type']){echo "selected";} ?> value="2">不包含</option>
                            <option <?php if (1 == $info['platform_order_id_type']){echo "selected";} ?> value="1">包含</option>
                        </select>
                    </div>
                    </div>
					<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <textarea class="form-control" name="platform_order_id_content" id="" cols="20"
                                  rows="8"><?php echo !empty($info['platform_order_id_content'])?$info['platform_order_id_content']:''?></textarea>
                    </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="col1">排除名单</td>
                <td colspan="2">
				<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <select name="customer_email_type" id="" class="form-control">
                            <option value="">请选择</option>
                            <option <?php if (2 == $info['customer_email_type']){echo "selected";} ?> value="2">客户邮箱不包含</option>
                            <option <?php if (1 == $info['customer_email_type']){echo "selected";} ?> value="1">客户邮箱包含</option>
                        </select>
                    </div>
                </div>
				<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <textarea name="customer_email_content" id="" cols="30" rows="8"
                                  class="form-control"><?php echo !empty($info['customer_email_content'])?$info['customer_email_content']:''?></textarea>
                    </div>
                </div>

                </td>
                <td colspan="2">
				<div class="row">
                    <div class="col-xs-4" style="padding-right:0px;">
                        <select name="buyer_id_type" id="" class="form-control">
                            <option value="">请选择</option>
                            <option  <?php if (2 == $info['buyer_id_type']){echo "selected";} ?> value="2">客户id不包含</option>
                            <option  <?php if (1 == $info['buyer_id_type']){echo "selected";} ?>  value="1">客户id包含</option>
                        </select>
                    </div>
                </div>
				<div class="row">
                    <div class="col-xs-8" style="padding-right:0px;">
                        <textarea name="buyer_id_content" id="" cols="30" rows="8" class="form-control"><?php echo !empty($info['buyer_id_content'])?$info['buyer_id_content']:''?></textarea>
                    </div>
                </div>
                </td>
            </tr>
            <tr>
                <td class="col1">邮件内容</td>
                <td colspan="3" style="color: red">
                    同时满足以上所有条件，则给客户自动回复下方配置内容：
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <div style="">
                        <div class="panel panel-default">

                            <div id="collapseThree" class="panel-collapse">
                                <div class="panel-body" style="height:auto;">
                                    <div style="margin-bottom: 10px">
                                        <form class="bs-example bs-example-form" role="form">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mail_template_search_text" name="mail_template_code" placeholder="模板编号搜索">
                                                        <span class="input-group-btn"><button class="btn btn-default btn-sm mail_template_search_btn" id="template_code" type="button">搜索</button></span>
                                                        <input type="text" class="form-control mail_template_search_text" placeholder="模板名称搜索">
                                                        <span class="input-group-btn"><button class="btn btn-default btn-sm mail_template_search_btn" id="template_name" type="button">搜索</button></span>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">

                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="panel panel-default">
                                        <div class="panel-body mail_template_area">

                                        </div>
                                    </div>
                                    <div class="btn-group">
                                        <input type="hidden" id="channel_id" value="<?php echo $model->channel_id; ?>"/>
                                        <input type="hidden" id="account_id" value="<?php echo $model->account_id; ?>"/>
                                        <input type="hidden" id="msg_sources"
                                               value="<?php echo $model->msg_sources; ?>"/>
                                        <input type="hidden" id="id" value="<?php echo $id; ?>"/>
                                        <!--在鼠标移动位置插入参数-->
                                        <div class="col-xs-12" >
                                            <?php
                                            /*  获取国家信息*/
                                            if ($order_info) {
                                                $countryList = Country::getCodeNamePairsList('en_name');

                                                if ($order_info['info']['real_ship_code']) {
                                                    $logistic = Logistic:: getSendWayEng($order_info['info']['real_ship_code']);
                                                    if (empty($logistic)) {
                                                        $logistic = Logistic:: getSendWayEng($order_info['info']['ship_code']);
                                                    }
                                                } else {
                                                    $logistic = '';
                                                }
                                                if ($order_info['info']['track_number']) {
                                                    $track        = 'http://www.17track.net/zh-cn/track?nums=' . $order_info['info']['track_number'];
                                                    $track_number = $order_info['info']['track_number'];
                                                } else {
                                                    $track        = '';
                                                    $track_number = '';
                                                }
                                                if ($order_info['info']['buyer_id']) {
                                                    $buyer_id = $order_info['info']['buyer_id'];
                                                } else {
                                                    $buyer_id = '';
                                                }
                                                if ($order_info['info']['ship_country']) {
                                                    $country      = $order_info['info']['ship_country'];
                                                    $ship_country = array_key_exists($country, $countryList) ? $countryList[$country] : '';
                                                } else {
                                                    $ship_country = '';
                                                }
                                            } else {
                                                $buyer_id     = '';
                                                $track_number = '';
                                                $logistic     = '';
                                                $track        = '';
                                                $ship_country = '';
                                            }

                                            ?>
                                            <select id="countDataType" class="form-control"
                                                    style="width:100%;height:30px;padding: 2px 5px;">
                                                <option value="all">选择绑定参数</option>
                                                <option value="{$customer_name}">客户名字</option>， ， ，  ，
                                                <option value="{$customer_address}">客户地址</option>
                                                <option value="{$platform_order}">平台订单号</option>
                                                <option value="{$asin}">ASIN</option>
                                                <option value="{$product_name}">产品名称</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!--富文本-->
                                    <div class="col-xs-12" >
                                        <textarea id ="body_info" name="sending_template" id="" cols="30" rows="8" class="form-control"><?php echo !empty($info['sending_template'])?$info['sending_template']:''?></textarea>
                                        
<!--                                        <script id="content_batch" name="sending_template" type="text/plain"></script>
                                        <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.config.js"></script>
                                        <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.all.js"></script>
                                        <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/lang/zh-cn/zh-cn.js"></script>
                                        <script type="text/javascript">
                                            UE.getEditor('content_batch', {zIndex: 6600, initialFrameHeight: 200});
                                        </script>-->
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>是否激活</td>
                <td colspan="3">
                    <input type="radio" name="active" value="1" checked>是
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="active" value="0">否
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan="3">
                    <input type="submit" class="btn btn-primary btn-sm" value="编辑">
                    <input type="reset" class="btn btn-default btn-sm" value="取消">
                    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
                </td>
            </tr>
        </table>
    </div>
    <div class="popup-footer"></div>
    <?php
    ActiveForm::end();
    ?>
</div>
<script type="text/javascript">
    $(function () {	
        $("select[name=platform_code]").change(function () {
            var platform_code = $(this).val();
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['getaccountbyplatformcode'])?>',
                data: {'platform_code': platform_code},
                success: function (data) {
                    if (data.status == 'success') {
                        $("#account_ids").empty();
                        var html = "";
                        html += '<option value="0">全部</option>';
                        $.each(data.data, function (n, value) {
                            html += '<option value="' + n + '">' + value + '</option>';
                        });
                        $("#account_ids").append(html);
                    } else {
                        layer.msg(data.message, {icon: 5, time: 10000});
                    }
                }
            });
            //ajax获取站点
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['getsitebyplatformcode'])?>',
                data: {'platform_code': platform_code},
                success: function (data) {
                    if (data.status == 'success') {
                        $("#site_codes").empty();
                        var html = "";
                        html += '<option value="0">全部</option>';
                        $.each(data.data, function (n, value) {
                            html += '<option value="' + value + '">' + value + '</option>';
                        });
                        $("#site_codes").append(html);
                    } else {
                        layer.msg(data.message, {icon: 5, time: 10000});
                        $("#site_codes").empty();
                    }
                }
            });

            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['gettemplates'])?>',
                data: {'platform_code': platform_code},
                success: function (data) {
                    if (data.status == 'success') {
                        var html = '';
                        // setmailEditor(data.data);
                        $.each(data.data,function (index, item)  {
                            html += "<a class='mail_template_unity' value='"+item.id+"'>"+item.template_name+"</a>&nbsp; &nbsp; ";
                        });
                        $(".mail_template_area").html( html );
                    } else {
                        layer.msg(data.message, {icon: 5, time: 10000});
                    }
                }
            });
        });
        
        $("#template_code").click(function () {

            var platform_code = $("select[name=platform_code]").val();
            if(platform_code == ''){
                layer.msg('请先选择平台', {icon: 5, time: 10000});
                return;
            }

            var mail_template_code = $("input[name=mail_template_code]").val();
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['gettemplates'])?>',
                data: {'platform_code': platform_code,'template_title':mail_template_code},
                success: function (data) {
                    if (data.status == 'success') {
                        //setmailEditor(data.data);
                        $(".mail_template_area").html('');
                        $(".mail_template_area").html(data.data.template_name);
                        var ue = UE.getEditor('content_batch');
                        //异步回调
                        ue.ready(function() {
                            ue.setContent(data.data.template_content );
                        });
                    } else {
                        layer.msg(data.message, {icon: 5, time: 10000});

                    }
                }
            });
        });

        //永久有效带入时间
        $("input[name=is_permanent]").click(function(){
            var myDate = new Date();
            //获取当前年
            var year=myDate.getFullYear();
            //获取当前月
            var month=myDate.getMonth()+1;
            //获取当前日
            var date=myDate.getDate();
            var h=myDate.getHours();       //获取当前小时数(0-23)
            var m=myDate.getMinutes();     //获取当前分钟数(0-59)
            var s=myDate.getSeconds();

            var now=year+'-'+p(month)+"-"+p(date)+" "+p(h)+':'+p(m)+":"+p(s);
            var twenty_year=(year + 20 )+'-'+p(month)+"-"+p(date)+" "+p(h)+':'+p(m)+":"+p(s);

            $("input[name=start_time]").val(now);
            $("input[name=end_time]").val(twenty_year);
        });
        function p(s) {
            return s < 10 ? '0' + s: s;
        }

        $("#template_name").click(function () {
            if(platform_code == ''){
                layer.msg('请先选择平台', {icon: 5, time: 10000});
                return;
            }

            var platform_code = $("select[name=platform_code]").val();
            var template_name = $("input[name=template_name]").val();
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['gettemplates'])?>',
                data: {'platform_code': platform_code,'template_name':template_name},
                success: function (data) {
                    if (data.status == 'success') {
                       // setmailEditor(data.data);
                        $("#mail_temp").html(data.data.template_name);

                        var ue = UE.getEditor('content_batch');

                        //异步回调
                        ue.ready(function() {
                            ue.setContent(data.data.template_content );
                        });
                    } else {
                        layer.msg(data.message, {icon: 5, time: 10000});

                    }
                }
            });
        });
        //模板ajax
        $('.mail_template_area').delegate('.mail_template_unity', 'click', function () {
            $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']);?>', {'num': $(this).attr('value')}, function (data) {
                switch (data.status) {
                    case 'error':
                        layer.msg(data.message, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                        return;
                    case 'success':
                        var ue = UE.getEditor('content_batch');

                        //异步回调
                        ue.ready(function() {
                            ue.setContent( data.content );
                        });

                }
            }, 'json');
        });
        
        
        

        $("#countDataType").on("change", function () {
            var data_value = $(this).val();
            if (data_value == '') {
                alert('暂无此数据');
            }
            if (data_value != 'all') {
                getValue('body_info', data_value);
            }
        });
        
        //objid：textarea的id   str：要插入的内容
        function getValue(objid, str) {
            var myField = document.getElementById("" + objid);
            //IE浏览器
            if (document.selection) {
                myField.focus();
                sel = document.selection.createRange();
                sel.text = str;
                sel.select();
            }

            else if (myField.selectionStart || myField.selectionStart == '0') {
                //得到光标前的位置
                var startPos = myField.selectionStart;
                //得到光标后的位置
                var endPos = myField.selectionEnd;
                // 在加入数据之前获得滚动条的高度
                var restoreTop = myField.scrollTop;
                myField.value = myField.value.substring(0, startPos) + str + myField.value.substring(endPos, myField.value.length);
                //如果滚动条高度大于0
                if (restoreTop > 0) {
                    // 返回
                    myField.scrollTop = restoreTop;
                }
                myField.focus();
                myField.selectionStart = startPos + str.length;
                myField.selectionEnd = startPos + str.length;
            }
            else {
                myField.value += str;
                myField.focus();
            }
        }
    });

</script>