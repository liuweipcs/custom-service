<?php
use app\components\GridView;
use yii\helpers\Url;
use app\modules\mails\models\EbayReply;
use yii\helpers\Html;
use app\modules\products\models\EbayListing;
use app\modules\mails\models\MailTemplate;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\Order;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayReturnsRequests;
use yii\helpers\Json;
use app\modules\accounts\models\Account;
use app\modules\mails\models\EbayInbox;
$this->title = 'Ebay邮件主题详情';
?>
<style>
    .ebay_reply_upload_image_delete
    {
        cursor:pointer;
    }
    .ebay_reply_upload_image_display
    {
        margin:2px 2px 0px 0px;
        float: left;
    }
    .ebay_reply_upload_image_delete
    {
        line-height:0px;
    }
    .mail_template_unity
    {
        margin-right: 10px;
    }
    .show_content_btn{text-decoration:underline;font-size: 13px;}
    #dialog_large_image{margin-left: 30%;position: absolute;z-index: 9999;}
    .language {width:550px;float: left;}
    .language li{width:16%;float:left;}
    .language li a{font-size: 10px; text-align: left;cursor: pointer;}
    .col-sm-4{width:auto;}
    .alert-warning {color: #ffffff;background-color: rgba(243,156,18,.88);border-color: rgba(243,156,18,.88);}
    .alert{margin-top:5px;}
</style>
<script type="application/javascript">
    $(function(){
        $('div.sidebar').hide();

        //回复保存、存草稿
        $('.reply_mail_save').click(function(){
           
//            var replyContentVal = $.trim($('#ueditor_0').contents().find('body p').text());
//            var replyContent = $.trim($('#ueditor_0').contents().find('body').html())
            var hide_last_language_code = $.trim($("#hide_last_language_code").val());
            var replyContentVal = $.trim($("#reply_content").val());
            var replyContent = $.trim($("#reply_content").val());//回复给客户翻译后的消息
            var replyContentEn = $.trim($("#reply_content_en").val());//客服回复的消息
//            var sl = $("#sl_code").val();
            
            //确保发送给客户的内容不为空
            if(replyContent == ""){
                if(replyContentEn == ""){
                     layer.msg('请输入回复内容s!');
                    return false;
                }else{
                    replyContent = replyContentEn;
                }
            }
            
            //如果翻译内容为空 则直接获取发送给客户的内容
            if(replyContentEn == ""){
                replyContentEn = replyContent;
            }
            
            var isDraft = $(this).attr('reply_type') == 'draft' ? 1 : 0;
            var sendData = {
//                'reply_title': replyTitle,
//                'question_type': questionType,
//                'subject_id':$('#subject_id').val(),
                'reply_content': replyContent,
                'reply_content_en' :replyContentEn,
                'inbox_id': $('#inbox_id').val(),
                'id': $('#reply_id').val(),     //回复id
                'is_draft' : isDraft
            };
            var ebayReplyImageObj = $('.ebay_reply_upload_image_display > img');//.attr('src');
            for(var i=0;i<ebayReplyImageObj.length;i++)
            {
                sendData['image['+i+']'] = ebayReplyImageObj[i].src;
            }
            $.post('<?php echo Url::toRoute(['/mails/ebayreply/addsubject'])?>',sendData,function(data){
                // console.log(data);return;
                switch(data.status)
                {
                    case 'error':
                        layer.msg(data.message, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });return;
                    case 'success':
                        if(typeof data.url == 'string')
                        {
                            $('#ebay_inbox_detail_jump').attr('action',data.url).submit();
                        }
                }
            },'json');
        });
        //标记已回复，上一个，下一个
        $('.reply_mail_mark').click(function(){
            var markType = $(this).attr('reply_type');
            $.post('<?php echo Url::toRoute(['/mails/ebayinboxsubject/mark'])?>',{'subject_id':<?= $currentModel->id; ?>,'type':markType},function(data){
                switch(data.status)
                {
                    case 'success':
                        $('#ebay_inbox_detail_jump').attr('action',data.url).submit();
                        break;
                    case 'error':
                        layer.msg(data.info, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                }
            },'json');
        });
        //模板ajax
        $('.mail_template_area').delegate('.mail_template_unity','click',function(){
            $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']);?>',{'num':$(this).attr('value')},function(data){
                switch(data.status)
                {
                    case 'error':
                        layer.msg(data.message, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                        return;
                    case 'success':
                        $('#reply_content_en').val(data.content);
//                        UE.getEditor('editor').setContent(data.content);
                }
            },'json');
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
        $('.mail_template_title_search_btn').on('click',template_title);
        $('.mail_template_title_search_text').bind('keypress',function(){
            if(event.keyCode == "13")
            {
                template_title();
            }
        });

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
                    $('#reply_content_en').val(data.data);
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
    });

</script>
<style>
    li{list-style: none;}
    .hear-title,.search-box ul{overflow: hidden;}
    .hear-title p:nth-child(1) span:nth-child(1),.hear-title p:nth-child(2) span:nth-child(1){display: inline-block;width: 30%}
    .item-list li{border-bottom: 1px solid #ddd;padding: 5px 10px}
    .item-list li span{display: inline-block;width: 25%}
    .search-box ul li{float: left;padding:0 10px 10px 0}
    .search-box textarea{display: block;margin-top: 10px;width: 100%}
    .info-box .det-info{width: 100%;height: 200px;border: 2px solid #ddd;}
    /*.well span{padding: 6%}*/
    .well p{text-align:left}
    .mail_item_list
    {
        margin-right: 30px;
        display: inline-block;
    }
    .mail_template_unity
    {
        cursor:pointer;
    }
</style>
<div class="col-md-12">
    <div id="page-wrapper-inbox" class="col-md-12">
        <div class="panel panel-default" >
            <div class="panel-heading cancel-inbox-id">
                <h3 class='panel-title'>
                    <i class="fa fa-pencil"></i>邮件
                    <ul style = "display: inline-block;" id="ulul">
                    <?php if(!empty($tags_data)){
                            foreach($tags_data as $key => $value)
                            { ?>
                                <li style="margin-right: 20px;" class="tag label btn-info md ion-close-circled" id = "tags_value<?php echo $key;?>"><span use_data="<?php echo $key;?>"><?php echo $value;?></span>&nbsp;<a href="javascript:void(0)" onclick="removetags(this);">x</a></li>
                    <?php }
                    }?>
                    </ul>
                </h3> <br/>               
                <?php if($sku){ ?>
                <?php if(is_array($sku)){?>    
                <span>相关sku：
                <?php foreach ($sku as $val) { ?>
                    <a href="http://120.24.249.36/product/index/sku/<?php echo $val; ?>"
                       style="color:red" target='_blank'><?php echo $val; ?> ——</a>
                <?php } ?>
                </span>
                <?php }else{?>
                <span>相关sku：<font color="red"><?php echo $sku;?></font></span>    
                <?php }?>
                <?php }?>
            </div>
            <div class="panel-body">
                <?php
                $rep_key = 1;
                foreach($models as $modelKey=>$model):?>
                    <div id="collapseItem<?=$modelKey?>" class="panel-collapse collapse">
                        <div class="panel-body hear-title">
                            <div class="hear-title">
                                <span class="mail_item_list"><?php echo isset($model['flagged'])?$model['flagged']:""?></span>
                                <span class="mail_item_list"><?php echo isset($model['high_priority'])?$model['high_priority']:""?></span>
                                <span class="mail_item_list"><?php echo isset($model['item_id'])?$model['item_id']:""?></span>
                                <span class="mail_item_list"><?php echo isset($model['expiration_date'])?$model['expiration_date']:""?></span>
                                <span class="mail_item_list"><?php echo isset($model['message_type'])?$model['message_type']:""?></span>
                                <span class="mail_item_list"><?php echo isset($model['is_read'])?$model['is_read']:""?></span>
                                <span class="mail_item_list"><?php echo isset($model['is_replied'])?$model['is_replied']:""?></span>
                                <span class="mail_item_list"><?php echo isset($model['response_enabled'])?$model['response_enabled']:""?></span>
                            </div>
                        </div>
                    </div>

                    <!-- </p>  -->
                    <div class="<?php if(!isset($model['inbox_id'])):echo 'panel panel-primary';?><?php else:echo 'panel panel-default'; endif;?>" id="content_<?=!isset($model['inbox_id'])?$model['id']:0?>" style="width:80%;<?php if(isset($model['inbox_id']))echo 'margin-left:20%;';?>">
                        <div <?php if(!isset($model['inbox_id'])): ?> class="panel-heading get-data-id" data-id="<?php echo $model['id'];?>"<?php else :?> class="panel-heading" <?php endif; ?>>
                            <h5 class="panel-title">
                                <a class="show_content_btn" data-parent="#accordion" target="_blank" href="http://www.ebay.com/itm/<?=$model['item_id']?>"<?php if(isset($model['is_replied']) && $model['is_replied'] == 0): ?> style="font-weight: bold;font-family:SimHei" <?php endif;?>>
                                    <?php echo !isset($model['inbox_id'])?$model['subject']:"Re:".$model['reply_title']?>
                                </a><br/>
                                <?php if(isset($model['inbox_id'])){?>
                                    <span class="mail_item_list"><span style="color:#ffcc66;"><?php echo $model['sender'];?></span><i class="	glyphicon glyphicon-arrow-right" style="margin-top: 2px;"></i><span style="color:#ffcc66;padding: 0px 10px;"><?php echo $model['recipient_id'];?></span>
                                        <span class="mail_item_list " style="margin-left:20px;"><?php echo $model['create_time'];?></span>
                                        <span class="mail_item_list bg-success" style="margin-left:20px;"><?php echo $model['create_by'];?></span>
                                <?php }else{?>
                                    <span class="mail_item_list"><span style="color:#ffcc66;"><?php echo $model['sender'];?></span>
                                    <i class="glyphicon glyphicon-arrow-right"></i><span style="color:#ffcc66;"><?php echo $model['account_name'];?></span>
                                    <span class="mail_item_list" style="margin-left:20px;"><?php echo $model['receive_date']?></span>
                                <?php }?>
                            </h5>
                        </div>
                        <div id="dialog_large_image"></div>
                            <?php 
                            if(!isset($model['inbox_id']) && $currentModel->id == $model['id']){?>
                                <div class="panel-body" style="background-color: #E6E6F2;">
                                    <?php
                                        echo !isset($model['inbox_id'])?$model['new_message']:nl2br($model['reply_content']);
                                        echo !isset($model['inbox_id'])?$model['image']:'';
                                    ?>

                                </div>
                            <?php }else{?>
                                <div class="panel-body" <?php if(!isset($model['inbox_id'])) echo 'style="background-color:#D1EFAF;"';?>>
                                    <?php
    //                                    echo !isset($model['inbox_id'])?'<div class="message">'.$model['new_message'].'</div>'.'<a type="button" onclick="show_translate($(this))" class="btn btn-sm waves-effect waves-light" data-toggle="modal" data-target="#myModal">点击翻译</a>':nl2br($model['reply_content']).'<a type="button" class="btn btn-sm waves-effect waves-light" data-toggle="modal" data-target="#myModal">点击翻译</a>';
                                        if(!isset($model['inbox_id'])){
                                            echo '<span id="message_'.$rep_key.'">'.$model['new_message'].'</span>  <a onclick="clikTrans($(this),'.$rep_key.')" style="cursor: pointer;">点击翻译</a>';
                                            echo '<span id="trans_message'.$rep_key.'"></span>';
                                        }else{
                                            echo '<span id="message_'.$rep_key.'">'.nl2br($model['reply_content_en']).'</span>';
                                            echo '<span id="trans_message'.$rep_key.'"></span>';
                                        }
                                        echo !isset($model['inbox_id'])?$model['image']:'';
                                    ?>
                                </div>
                            <?php }?>
                            <?php if(isset($model['pictures']) && !empty($model['pictures']))
                            {
                                echo '<hr>';
                                echo '图片:';
                                foreach ($model['pictures'] as $picture)
                                {
                                    echo '<a href="'.$picture['picture_url'].'" target="_blank";><img width="100px" height="100px" src="'.$picture['picture_url'].'"></img></a>&nbsp;';
                                }
                            }
                            ?>
                    </div>
                <?php 
                $rep_key++;
                endforeach;?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var $new_inbox_id = '<?= $new_inbox_id?>'//$('#inbox_id').val();

    // 获取url参数
    function GetQueryString(name)
    {
        var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if(r!=null)return  unescape(r[2]); return null;
    }

    function removetags(obj) {
        var _id = GetQueryString('id');
        var tag_id = $(obj).siblings('span').attr('use_data');
        $.post('<?= Url::toRoute(['/mails/ebayinboxsubject/removetags','id' => $currentModel->id,'type'=>'detail'])?>', {'MailTag[inbox_id]' : _id, 'MailTag[tag_id][]' : tag_id, 'MailTag[type]' : 'detail'}, function (data) {
            if (data.code == "200")
                $("#tags_value"+tag_id).hide(50);
        }, 'json');

    }

    //实例化编辑器
    $(function(){
        $draft = "<?php echo !isset($draftModel->reply_content)?"":$draftModel->reply_content?>";
        var ue = UE.getEditor('editor');
        ue.addListener('ready',function(){
            ue.setContent($draft);
        })
    })
    
    
    
    //图片处理
    var docHeight = $(document).height(); //获取窗口高度  
    var orgial = new Array();
    orgial.push("<?php echo !empty($orgialImage)?$orgialImage:''?>");
    var org = orgial[0].split(',');
    var j = new Array();
    for (var i = 0; i <org.length; i++) {

         var o = org[i].lastIndexOf("\/");
         j.push(org[i].substring(0,o));
    }
    $('#imagePreviewHtml').each(function(){
        $(this).find('.fourColumnsTd').click(function(){
//            $('body').append('<div id="overlay"></div>');
//            $('#overlay')
//            .height(docHeight)
//            .css({'opacity': .5,'position': 'relative','top': 0,'left': 0,'background-color': 'black','width': '100%','z-index': 999});
            var thumb = $(this).find('img').attr('src');
            var index = thumb .lastIndexOf("\/");  
            thumb = thumb .substring(0, index);
            var result = $.inArray(thumb,j);

            if(result > -1)
            {
                var large_image = '<img src= ' + org[result] + '></img>';
                window.open(org[result],"","toolbar=no,scrollbars=no,menubar=no");    // 打开一个新的窗口，在新的窗口显示图片的本来大小

//                var large_image = '<img src= ' + org[result] + '></img>';
//                var div = $('#dialog_large_image').html($(large_image).animate({ height: '100%', width: '100%' }, 500));
            }else{
                alert('原图片资源不存在')
//                var noexists = '<img src=/img/noexists.jpg>'
//                $("#dialog_large_image").html($(noexists).animate({height: '100%', width: '100%'},500));
            }
        })
    })
//    $("#dialog_large_image").click(function(){$(this).html('');$('body').find('#overlay').remove();})

//    $('.get-data-id').on('click', function(){
//        $this = $(this);
//        var inbox_id = $this.attr('data-id');
//        $('.cancel-inbox-id').parent().removeClass('panel-success').addClass('panel-default');
//        $(this).parent().siblings().removeClass('panel-success').addClass('panel-default');
//        $(this).parent().removeClass('panel-default').addClass('panel-success');
//        $('#inbox_id').val(inbox_id);
//    })
//
//    $('.cancel-inbox-id').on('click', function(){
//        $(this).parent().removeClass('panel-default').addClass('panel-success');
//        $('.get-data-id').parent().removeClass('panel-success').addClass('panel-default');
//        $('.get-data-id').parent().siblings().removeClass('panel-success').addClass('panel-default');
//        $('#inbox_id').val($new_inbox_id);
//    })

    var keyboards = '<?php echo $keyboards; ?>'
    keyboards = JSON.parse(keyboards);
    var ids = '<?php echo $currentModel->id; ?>'
    var tag_id = '';
    $(document).ready(
        function(){
            document.onkeyup = function(e)
            {
                var event = window.event || e;
                if(event.shiftKey && keyboards['shift'] != undefined && keyboards['shift'][event.keyCode] != undefined)
                {
                    tag_id = keyboards['shift'][event.keyCode]
                    if (tag_id != '' && tag_id != undefined) {
                        $.post('<?= Url::toRoute(['/mails/ebayinboxsubject/addretags', 'ids' => $currentModel->id, 'type' => 'detail'])?>', {
                            'MailTag[inbox_id]': ids,
                            'MailTag[tag_id][]': tag_id,
                            'MailTag[type]': 'detail'
                        }, function (data) {
                            if (data.code == "200" && data.url == 'add') {
                                /*  window.location.href = data.url;*/
                                var html = "";
                                var result = data.data;
                                $.each(result, function (i, v) {
                                    html += '<li style="margin-right: 20px;" class="tag label btn-info md ion-close-circled" id = "tags_value' + i + '"><span use_data="' + i + '">' + v + '</span>&nbsp;<a href="javascript:void(0)" onclick="removetags(this);">x</a></li>';
                                })
                                $("#ulul").html(html);
                            } else if (data.code == "200" && data.url == 'del') {
                                var tags_id = data.js;
                                $("#tags_value"+tags_id).hide(50);
                            }
                        }, 'json');
                    }
                }
                if(event.ctrlKey && keyboards['ctrl'] != undefined && keyboards['ctrl'][event.keyCode] != undefined)
                {
                    tag_id = keyboards['ctrl'][event.keyCode]
                    if (tag_id != '' && tag_id != undefined) {
                        $.post('<?= Url::toRoute(['/mails/ebayinboxsubject/addretags', 'ids' => $currentModel->id, 'type' => 'detail'])?>', {
                            'MailTag[inbox_id]': ids,
                            'MailTag[tag_id][]': tag_id,
                            'MailTag[type]': 'detail'
                        }, function (data) {
                            if (data.code == "200" && data.url == 'add') {
                                /*  window.location.href = data.url;*/
                                var html = "";
                                var result = data.data;
                                $.each(result, function (i, v) {
                                    html += '<li style="margin-right: 20px;" class="tag label btn-info md ion-close-circled" id = "tags_value' + i + '"><span use_data="' + i + '">' + v + '</span>&nbsp;<a href="javascript:void(0)" onclick="removetags(this);">x</a></li>';
                                })
                                $("#ulul").html(html);
                            } else if (data.code == "200" && data.url == 'del') {
                                var tags_id = data.js;
                                $("#tags_value"+tags_id).hide(50);
                            }
                        }, 'json');
                    }
                }
                if(event.altKey && keyboards['alt'] != undefined && keyboards['alt'][event.keyCode] != undefined) {
                    tag_id = keyboards['alt'][event.keyCode]
                    if (tag_id != '' && tag_id != undefined) {
                        $.post('<?= Url::toRoute(['/mails/ebayinboxsubject/addretags', 'ids' => $currentModel->id, 'type' => 'detail'])?>', {
                            'MailTag[inbox_id]': ids,
                            'MailTag[tag_id][]': tag_id,
                            'MailTag[type]': 'detail'
                        }, function (data) {
                            if (data.code == "200" && data.url == 'add') {
                                /*  window.location.href = data.url;*/
                                var html = "";
                                var result = data.data;
                                $.each(result, function (i, v) {
                                    html += '<li style="margin-right: 20px;" class="tag label btn-info md ion-close-circled" id = "tags_value' + i + '"><span use_data="' + i + '">' + v + '</span>&nbsp;<a href="javascript:void(0)" onclick="removetags(this);">x</a></li>';
                                })
                                $("#ulul").html(html);
                            } else if (data.code == "200" && data.url == 'del') {
                                var tags_id = data.js;
                                $("#tags_value"+tags_id).hide(50);
                            }
                        }, 'json');
                    }
                }
            }
        }
    );
    
    
    /**
     * 回复客户邮件内容点击翻译
     * @author allen <2018-1-4>
     */
    $(".translation").click(function(){
        var sl = 'en';//自己填的默认是英语
        var tl = $(this).attr('data');
        var content =  $.trim($("#reply_content_en").val());
        if(content.length == 0)
        {
           layer.msg('请输入需要翻译的内容!');
           return false;
        }
        
        if(sl == ""){
           layer.msg('系统未识别到客户翻译的语言!');
           $("#myModal").show();
           return false;
        }
        
        $.ajax({
            type:"POST",
            dataType:"JSON",
            url:'<?php echo Url::toRoute(['translate']);?>',
            data:{'sl':sl,'tl':tl,'content':content},
            success:function(data){
                if(data){
                    $("#reply_content").val(data);
                    $("#reply_content").css('display','block');
                }
            }
        });
    });
    
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
            $("#sl_btn").html(name+'&nbsp;&nbsp;<span class="caret"></span>');
            that.css('font-weight','bold');
            $("#sl_name").html(name);
        }else if(type == 2){
            $("#tl_code").val(code);
            $("#tl_btn").html(name+'&nbsp;&nbsp;<span class="caret"></span>');
            $("#tl_name").html(name);
            that.css('font-weight','bold');
        }else if(type == 3){
            var name = that.html();
            $("#sl_code").val(code);
            $("#sl_name").html(name);
        }else{
            var name = that.html();
            $("#tl_code").val(code);
            $("#tl_name").html(name);
        }
    }
    
    /**
     * 绑定翻译按钮 进行手动翻译操作 
     * @author allen <2018-1-5>
     **/
     function clikTrans(that,k){
        var sl = 'auto';
        var tl = 'en';
        
        var message = $("#message_"+k).html();
        var tag = $(this).attr('data1');
        if(message.length == 0)
        {
           layer.msg('获取需要翻译的内容有错!');
           return false;
        }
        
        $.ajax({
            type:"POST",
            dataType:"JSON",
            url:'<?php echo Url::toRoute(['translate']);?>',
            data:{'sl':sl,'tl':tl,'returnLang':1,'content':message},
            success:function(data){
                if(data){
//                    var htm = '<tr class="ebay_dispute_message_board '+tag+'"><td style="text-align: center;"><b style="color:red;">'+data.code+'</b></td><td><b style="color:green;">'+data.text+'</b></td></tr>';
                    $("#sl_code").val('en');
                    $("#sl_name").html('英语');
                    $("#tl_code").val(data.googleCode);
                    $("#tl_name").html(data.code);
                    $("#trans_message"+k).html('<br/><br/><b style="color:green;">'+data.text+'</b>');
                    that.remove();
                }
            }
        });
    }
     
     
     
    $('.artificialTranslation').click(function(){
        var sl = $("#sl_code").val();
        var tl = $("#tl_code").val();
        var content = $.trim($("#reply_content_en").val());
        if(sl == ""){
            layer.msg('请选择需要翻译的语言类型');
            return false;
        }
        
        if(tl == ""){
            layer.msg('请选择翻译目标的语言类型');
            return false;
        }
        
        if(content.length <= 0){
           layer.msg('请输入需要翻译的内容!');
           return false;
        }
        
        //ajax请求
        $.ajax({
            type:"POST",
            dataType:"JSON",
            url:'<?php echo Url::toRoute(['translate']);?>',
            data:{'sl':sl,'tl':tl,'content':content},
            success:function(data){
                if(data){
                    $("#reply_content").val(data);
                    $("#reply_content").css('display','block');
                }
            }
        });
    });
</script>