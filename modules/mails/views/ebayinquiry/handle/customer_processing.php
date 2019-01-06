<?php
    use app\modules\mails\models\EbayInquiryResponse;
    use app\modules\mails\models\MailTemplate;
    use app\modules\accounts\models\Platform;
    use yii\helpers\Html;
    use yii\helpers\Url;
?>
<style>
    .col-sm-5{width:auto;}
    .mail_template_area a{cursor: pointer;}
    .tr_q .dropdown-menu{left:-136px;}
    .tr_h .dropdown-menu {left:-397px;}
</style>
<div class="col-md-5">
        <div class="panel panel-primary">
            <div class="panel-body">
                <ul class="list-group">
                            <?php
                                if(!empty($model->seller_address))
                                {
                                    $sellerAddress = unserialize($model->seller_address);
                                    if(!empty($sellerAddress))
                                    {
                                        $addressLine = isset($sellerAddress->address->addressLine1) ? $sellerAddress->address->addressLine1:$sellerAddress->address->addressLine2;
                                        echo '<li class="list-group-item"><div style="color:#912CEE">卖家地址：</div><div><div>',$sellerAddress->name,'</div><div>',$addressLine,'</div><div>',$sellerAddress->address->city,'&nbsp;&nbsp;&nbsp;&nbsp;',$sellerAddress->address->postalCode,'</div></div></li>';
                                    }
                                }
                            ?>
                </ul>
                
                <?php
                $item_id = $model->item_id;
                $account_id = $model->account_id;
                $buyer_id = $model->buyer;

                $subject_model = \app\modules\mails\models\EbayInboxSubject::findOne(['buyer_id' => $buyer_id, 'item_id' => $item_id, 'account_id' => $account_id]);
                ?>

                <dl class="dl-horizontal">
                    <dt style="width:100px;">ebay message</dt>
                    <?php
                    if ($subject_model) {
                        echo '<dd><a href="/mails/ebayinboxsubject/detail?id=' . $subject_model->id . '" target="_blank">' . $subject_model->first_subject . '</a></dd>';
                    } else {
                        echo '<dd style="width:70px;">无</dd>';
                    }
                    ?>
                </dl>

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
                        <link href="<?php echo yii\helpers\Url::base(true); ?>/laydate/need/laydate.css" rel="stylesheet">
                        <link href="<?php echo yii\helpers\Url::base(true); ?>/laydate/skins/default/laydate.css" rel="stylesheet">
                        <script src="<?php echo yii\helpers\Url::base(true); ?>/laydate/laydate.js"></script>
                        <div class="popup-body">
                            <div class="row">
                                <input class="auto_refund_after_case_actual" type="hidden" name="EbayInquiry[auto_refund]" value="<?= $model->auto_refund ?>"/>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="2" >全额退款
                                    <div class="type_map_params">
                                        <input type="hidden" name="order_id" value="<?php if (!empty($info['info'])) echo $info['info']['order_id']; ?>">
                                        退款原因：
                                        <select name="EbayInquiryResponse[reason_code]">
                                            <?php foreach ($reasonCode as $key => $value) { ?>
                                                <option value="<?= $value->id ?>"><?= $value->content ?></option>
                                            <?php } ?>
                                        </select>
                                        <br/>
                                        <textarea name="EbayInquiryResponse[content][2]" rows="5" cols="50"></textarea>
                                    </div>
                                </div>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="3" >提供发货信息
                                    <div class="type_map_params">
                                        承运人：<input type="text" name="EbayInquiryResponse[shipping_carrier_name]"/>
                                        发货时间：<input class="laydate-icon" id="ebay_inquiry_history_shipping_date" value="" name="EbayInquiryResponse[shipping_date]"/>
                                        跟踪号：<input type="text" name="EbayInquiryResponse[tracking_number]">
                                        <br/>
                                        <textarea name="EbayInquiryResponse[content][3]" rows="5" cols="50"></textarea>
                                    </div>
                                </div>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="4" >升级
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
                                    <input type="radio" name="EbayInquiryResponse[type]" value="1" >发送留言
                                    <div class="type_map_params">
                                        <div style="margin-bottom: 10px">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mail_template_title_search_text" placeholder="模板编号搜索">
                                                        <span class="input-group-btn">
                                                            <button class="btn btn-default mail_template_title_search_btn" type="button">Go!</button>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mail_template_search_text" placeholder="消息模板搜索">
                                                        <span class="input-group-btn">
                                                            <a class="btn btn-default mail_template_search_btn" >搜索</a>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="mail_template_area panel-body">
                                        <?php
                                        $mailTemplates = MailTemplate::getMailTemplateDataAsArrayByUserId(Platform::PLATFORM_CODE_EB);
                                        foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName) {
                                            echo '<a class="mail_template_unity" value="' . $mailTemplatesId . '">' . $mailTemplateName . '</a> ';
                                        }
                                        ?>
                                            </div>
                                        </div>

                                        <?php echo Html::hiddenInput('sl_code', "", ['id' => 'sl_code']); ?>
                                        <?php echo Html::hiddenInput('tl_code', "", ['id' => 'tl_code']); ?>
                                        <div><textarea id='leave_message' name="EbayInquiryResponse[content][1]" rows="6" cols="90"></textarea></div>
                                        <div class="row" style="text-align: center;font-size: 13px;font-weight: bold;margin-top: 10px;margin-bottom: 10px;">
                                            <div class="col-sm-5 tr_q">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-default" type="button" onclick="changeCode(3, 'en', '', $(this))">英语</button>
                                                    <button class="btn btn-default" type="button" onclick="changeCode(3, 'fr', '', $(this))">法语</button>
                                                    <button class="btn btn-default" type="button" onclick="changeCode(3, 'de', '', $(this))">德语</button>
                                                    <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                                        <div class="btn-group">
                                                            <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" id="sl_btn">更多&nbsp;&nbsp;<span class="caret"></span> </button>
                                                            <ul class="dropdown-menu language">
                                                                <?php foreach ($googleLangCode as $key => $value) { ?>
                                                                    <li><a onclick="changeCode(1, '<?php echo $key; ?>', '<?php echo $value; ?>', $(this))"><?php echo $value; ?></a></li>        
                                                                <?php } ?>
                                                            </ul>
                                                        </div>
                                                            <?php } ?>
                                                </div>
                                            </div>
                                            <div class="fa-hover col-sm-1" style="width:0px;line-height: 30px;"><a><i class="fa fa-exchange"></i></a></div>
                                            <div class="col-sm-5 tr_h">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-default" type="button" onclick="changeCode(4, 'en', '', $(this))">英语</button>
                                                    <button class="btn btn-default" type="button" onclick="changeCode(4, 'fr', '', $(this))">法语s</button>
                                                    <button class="btn btn-default" type="button" onclick="changeCode(4, 'de', '', $(this))">德语</button>
                                                        <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                                        <div class="btn-group">
                                                            <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" data="" id="tl_btn">更多&nbsp;&nbsp;<span class="caret"></span> </button>
                                                            <ul class="dropdown-menu language">
                                                                <?php foreach ($googleLangCode as $key => $value) { ?>
                                                                    <li><a onclick="changeCode(2, '<?php echo $key; ?>', '<?php echo $value; ?>', $(this))"><?php echo $value; ?></a></li>              
                                                                <?php } ?>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <?php } ?>
                                                </div>
                                            </div>
                                            <div class="col-sm-1"><button class="btn btn-sm btn-primary artificialTranslation" type="button" id="translations_btn">翻译 [ <b id="sl_name"></b> - <b id="tl_name"></b> ] </button></div>
                                        </div>    
                                        <div><textarea id='leave_message_en' name="EbayInquiryResponse[content][1_en]" rows="6" cols="90"></textarea></div>
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
                            <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
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
<script>
    //模板ajax
    $('.mail_template_area').delegate('.mail_template_unity', 'click', function () {
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>', {'num': $(this).attr('value')}, function (data) {
            switch (data.status)
            {
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
    $('.mail_template_search_btn').click(function(){
        var templateName = $.trim($('.mail_template_search_text').val());
        if(templateName.length == 0)
        {
            layer.msg('搜索名称不能为空。', {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplate']);?>',{'name':templateName},function(data){
            switch(data.status)
            {
                case 'error':
                    layer.msg(data.message, {
                        icon: 2,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    });
                    return;
                case 'success':
                    var templateHtml = '';
                    for(var i in data.content)
                    {
                        templateHtml += '<a class="mail_template_unity" value="'+i+'">'+data.content[i]+'</a>';
                    }
                    $('.mail_template_area').html(templateHtml);
            }
        },'json');
    });
    

//模板编号搜索
    $('.mail_template_title_search_btn').on('click', template_title);
    $('.mail_template_title_search_text').bind('keypress', function () {
        if (event.keyCode == "13")
        {
            template_title();
        }
    });

    function template_title()
    {
        var templateTitle = $.trim($('.mail_template_title_search_text').val());
        if (templateTitle.length == 0)
        {
            layer.msg('搜索内容不能为空。', {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplatetitle']); ?>', {'name': templateTitle, 'platform_code': 'EB'}, function (data) {
            if (data.code == 200)
            {
                $('#leave_message').val(data.data);
            } else
            {
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
        if (message.length == 0)
        {
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
                    that.remove();
                }
            }
        });
    });
</script>