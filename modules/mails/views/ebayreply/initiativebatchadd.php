<?php
use yii\bootstrap\ActiveForm;
use app\modules\mails\models\MailTemplate;
use app\modules\accounts\models\Platform;
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    .translation_div .col-sm-6{text-align: right;}
    .language {width:650px;float: left;}
    .language li{width:16%;float:left;}
    .language li a{font-size: 10px; text-align: left;cursor: pointer;}
</style>
<script type="application/javascript">
    $(function(){
        function iniReplayImage()
        {
            $('.replay_image').each(function(i){
                $(this).find('input').attr('name','image['+i+']');
            });
        }
        $('.replay_image_add').click(function(){
            var $this = $(this);
            var cloneObj = $this.prev('.replay_image').clone();
            cloneObj.find('input').val('');
            $this.before(cloneObj);
            iniReplayImage();
        });
        $('.popup-body').delegate('.replay_image_delete','click',function(){
            var deleteObj = $(this).parents('.replay_image');
            if(deleteObj.siblings('.replay_image').length > 0)
            {
                deleteObj.remove();
                iniReplayImage();
            }
            else
            {
                layer.msg('不能全部删除', {
                    icon: 2,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                });
            }

        });
        
        //发送邮件方法
        $('#ebay_order_replay_message_submit').click(function(){
//            if($.trim($('#ebayreply-reply_title').val()).length < 1)
//            {
//                layer.alert('主题不能为空。');
//                return;
//            }
            if($.trim($('#ebayreply-question_type').val()).length < 1)
            {
                layer.alert('问题类型必选。');
                return false;
            }
            if($.trim($('#ebayreply-reply_content').val()).length < 1)
            {
                layer.alert('回复内容不能为空。');
                return false;
            }
            var $this = $(this);
            var form = new FormData($('#ebay_order_replay_message')[0]);
            $.ajax({
                'type':'POST',
                'url':'<?=Yii::$app->request->getUrl()?>',
                'data':form,
                'processData':false,
                'contentType':false,
                'dataType':'json',
                'success':function(data){
                    switch(data.status){
                        case 'error':
                            layer.msg(data.info, {
                                icon: 2,
                                time: 2000 //2秒关闭（如果不配置，默认是3秒）
                            });
                            break;
                        case 'success':
                            layer.msg(data.info, {
                                icon: 1,
                                time: 2000 //2秒关闭（如果不配置，默认是3秒）
                            });
                            $this.siblings('.close-button').click();
                    }
                }
            });
        });

        //模板编号搜索
        $('.mail_template_title_search_btn').on('click',template_title);
        function template_title()
        {
            var templateTitle = $.trim($('.mail_template_title_search_text').val());
            if(templateTitle.length == 0)
            {
                layer.msg('搜索内容不能为空。', {
                    icon: 2,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                });
                return;
            }
            $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplatetitle']);?>',{'name':templateTitle,'platform_code':'EB'},function(data){
                if(data.code == 200)
                {
                    $('#ebayreply-reply_content_en').val(data.data);
                }
                else
                {
                    layer.msg(data.message, {
                        icon: 2,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    });
                    return;
                }
            },'json');
        }

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

    });

</script>
<div class="popup-wrapper">
    <?php
    $form = ActiveForm::begin([
        'id' => 'ebay_order_replay_message',
        'layout' => 'horizontal',
        'action' => Yii::$app->request->getUrl(),
        'enableClientValidation' => false,
        'validateOnType' => false,
        'validateOnChange' => false,
        'validateOnSubmit' => true,
        'options' => ['enctype'=>'multipart/form-data','role' => 'form'],
    ]);
    ?>
    <div class="popup-body">

        <?php echo Html::hiddenInput('tl_code',"",['id'=>'tl_code']);?>
        <?php echo Html::hiddenInput('EbayReply[order_ids]',$order_ids,['id'=>'order_ids']);?>
        <div class="row" >
            <div class="col-sm-9">
                <?php echo $form->field($model, 'reply_title')->textInput();?>
            </div>
        </div>
        <div class="row" style="display: none;">
            <div class="col-sm-9">
                <?php echo $form->field($model, 'question_type')->dropDownList($model::$questionTypeMap,['value'=>2]);?>
            </div>
        </div>
        <div class="row">
            <!--<div class="col-sm-9">
                <div class="form-group field-ebayreply-image">
                    <label class="control-label col-sm-3" >图片</label>
                    <div class="col-sm-9">
                        <div class="replay_image"><input style="display: inline" type="file" name="image[0]"/><button type="button" style="display: inline;" class="replay_image_delete">删除</button></div>
                        <button type="button" class="replay_image_add">添加图片</button>
                    </div>
                </div>
            </div>-->
        </div>
        <div class="row">
            <div class="col-sm-9">
                <div class="form-group field-ebayreply-image">
                    <label class="control-label col-sm-3" ></label>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <input type="text" class="form-control mail_template_title_search_text" placeholder="模板编号搜索">
                            <span class="input-group-btn">
                                <button class="btn btn-default mail_template_title_search_btn" type="button">Go!</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-9">
                <div class="form-group field-ebayreply-image">
                    <label class="control-label col-sm-3" ></label>
                    <div class="col-lg-6">
                        <div class="input-group">
                            <input type="text" class="form-control mail_template_search_text" placeholder="消息模板搜索">
                            <span class="input-group-btn">
                                <button class="btn btn-default mail_template_search_btn" type="button">搜索</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-9">
                <div class="form-group">
                    <label class="control-label col-sm-3" >模板</label>
                    <div class="col-sm-9">
                        <div  class="mail_template_area" style="padding-top:6px;float:left;">
                            <?php

                            $mailTemplates = MailTemplate::getMailTemplateDataAsArrayByUserId(Platform::PLATFORM_CODE_EB);
                            foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName)
                            {
                                echo "<a class='mail_template_unity' value='$mailTemplatesId'>$mailTemplateName</a>";
                            }
                            // $mailTemplates = MailTemplate::AccordingToAccountShow($this->params['identity']->user_name);
                            /*$mailTemplates = MailTemplate::getMailTemplateDataAsArray(Platform::PLATFORM_CODE_EB);
                            foreach ($mailTemplates as $mailTemplatesKey => $mailTemplateVal)
                            {
                                //$mailTemplatesId = $mailTemplateVal['id'];
                                //$mailTemplateName = $mailTemplateVal['template_name'];

                                $mailTmp = MailTemplate::AccordingToAccountShow($mailTemplateVal['private']);




                            }*/
                            /*echo "<a class='mail_template_unity' value='$mailTemplatesId'>$mailTemplateName</a>";*/
                            ?>
                        </div>
                        <!--在鼠标移动位置插入参数-->
                        <div class="form_data" style="float: left;font-size: 12px;margin-left: 50px;">
                            <select id="countDataType" class="form-control"
                                    style="width:100%;height:30px;padding: 2px 5px;">
                                <option value="all">选择绑定参数</option>
                                <option value="{1track_number1}">跟踪号</option>
                                <option value="{1track1}">查询网址</option>
                                <option value="{1buyer_id1}">客户姓名</option>
                                <option value="{1ship_name1}">客户地址</option>
                                <option value="{1payer_email1}">付款账号</option>
                                <option value="{1receiver_email1}">收款账号</option>
                                <option value="{1transaction_id1}">退款交易号</option>
                                <option value="{1email1}">客户email</option>
                                <option value="{1paytime1}">付款时间</option>
                                <option value="{1item_id1}">item ID</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="row translation_div">
            <div class="col-sm-12">
                <?php echo $form->field($model, 'reply_content_en')->textarea(['rows'=>6]);?>
            </div>
            
            <div class="col-sm-6">
                                <div class="btn-group btn-group-sm">
                                 <button class="btn btn-default" type="button" onclick="changeCode(4,'en','',$(this))">英语</button>
                                 <button class="btn btn-default" type="button" onclick="changeCode(4,'fr','',$(this))">法语</button>
                                 <button class="btn btn-default" type="button" onclick="changeCode(4,'de','',$(this))">德语</button>
                                 <?php if(is_array($googleLangCode) && !empty($googleLangCode)){?>
                                 <div class="btn-group">
                                   <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" data="" id="tl_btn">更多&nbsp;&nbsp;<span class="caret"></span> </button>
                                   <ul class="dropdown-menu language">
                                     <?php foreach ($googleLangCode as $key => $value) { ?>
                                       <li><a onclick="changeCode(2,'<?php echo $key;?>','<?php echo $value;?>',$(this))"><?php echo $value;?></a></li>              
                                     <?php } ?>
                                    </li>
                                  </ul>
                                 </div>
                                 <?php } ?>
                               </div>
                             </div>
            <div class="col-sm-3">
            <button type="button" class="btn btn-primary translation" style="text-align: center;font-size: 13px;font-weight: bold;margin-bottom: 20px;" id="translation_btn"> 翻译 </button>
            </div>
            <div class="col-sm-12">
                <?php echo $form->field($model, 'reply_content')->textarea(['rows'=>6]);?>
            </div>
        </div>
    </div>
    <div class="popup-footer">
        <button id="ebay_order_replay_message_submit" class="btn btn-primary" type="button"><?php echo Yii::t('system', 'Submit');?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
    </div>
    <?php
    ActiveForm::end();
    ?>
</div>

<script type="text/javascript">
    $('.mail_template_area').delegate('.mail_template_unity','click',function(){

        $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']);?>',{'num':$(this).attr('value')},function(data){
            console.log(data);
            switch(data.status)
            {
                case 'error':
                    layer.msg(data.message, {
                        icon: 2,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    });
                    return;
                case 'success':
                    $('#ebayreply-reply_content_en').val(data.content);

            }
        },'json');
    });

    //鼠标定位添加订单信息
    $("#countDataType").on("change", function (){
        var data_value = $(this).val();
        var reply_content = $('#ebayreply-reply_content_en').val();
        if(data_value != 'all'){
            $('#ebayreply-reply_content_en').val(reply_content + data_value);
        }
    })
     /**
    * 点击选择语言将选中语言赋值给对应控件
     * @param {type} type 类型
     * @param {type} code 语言code
     * @param {type} name 语言名称
     * @param {type} that 当前对象
     * @author allen <2018-1-5>
     * */
    function changeCode(type,code,name = "",that = ""){
        if(type == 1){
            $("#sl_code").val(code);
            $("#sl_btn").html(name+'&nbsp;&nbsp;<span class="caret">[ 英语 -'+name+']</span>');
            that.css('font-weight','bold');
            $("#sl_name").html(name);
        }else if(type == 2){
            $("#tl_code").val(code);
            $("#translation_btn").html('翻译 [ 英语 -'+name+' ]');
            that.css('font-weight','bold');
        }else if(type == 3){
            var name = that.html();
            $("#sl_code").val(code);
            $("#sl_name").html(name);
        }else{
            var name = that.html();
            $("#tl_code").val(code);
            $("#translation_btn").html('翻译 [ 英语 -'+name+' ]');
            $("#tl_name").html(name);
        }
    }

    /**
     * 回复客户邮件内容点击翻译
     * @author allen <2018-1-11>
     */
    $(".translation").click(function(){
        var sl = 'en';//自己填的默认是英语
        var tl = $("#tl_code").val();
        if(tl == ""){
          tl = 'en';   
        }
        var content =  $.trim($("#ebayreply-reply_content_en").val());
        if(content.length == 0)
        {
           layer.msg('请输入需要翻译的内容!');
           return false;
        }        
        $.ajax({
            type:"POST",
            dataType:"JSON",
            url:'<?php echo Url::toRoute(['ebayinboxsubject/translate']);?>',
            data:{'sl':sl,'tl':tl,'content':content},
            success:function(data){
                if(data){
                    $("#ebayreply-reply_content").val(data);
//                    $("#reply_content").css('display','block');
                }
            }
        });
    });
</script>