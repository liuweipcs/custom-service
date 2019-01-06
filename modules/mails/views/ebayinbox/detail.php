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
</style>
<script type="application/javascript">
    $(function(){
        $('div.sidebar').hide();
        //点击卖家邮件，url变成当前的id
        $('.panel-default').dblclick(function(event){
            var id = $(this).attr('id');
            var jumpId=id.split('_')[1];
            if(jumpId != 0){
                location.href="<?php echo Url::toRoute(['ebayinbox/detail'])?>?id="+jumpId;
                $(this).children('.panel-body').attr('id','activing').css('background-color','#E6E6F2');
            }
                
            event.stopPropagation(); 
        })
        
        //回复保存、存草稿
        $('.reply_mail_save').click(function(){
            
           /* 
            if(replyContentVal.length == 0)
            {
                alert('内容不能为空。');return;
            }*/
//            var replyTitle = $.trim($('#reply_title').val());
//            if(replyTitle.length == 0)
//            {
//                alert('主题不能为空。');return;
//            }
//            var questionType = $('#question_type').val();
//            if(questionType < 1)
//            {
//                alert('请选择问题类型。');return;
//            }
            

            var replyContent = $.trim($('#reply_content').val());
            if(replyContent.length == 0)
            {
                alert('内容不能为空。');return;
            }/**/
            var isDraft = $(this).attr('reply_type') == 'draft' ? 1 : 0;
            var sendData = {
//                'reply_title': replyTitle,
//                'question_type': questionType,
                'reply_content': replyContent,
                'inbox_id': $('#inbox_id').val(),
                'id': $('#reply_id').val(),     //回复id
                'draft_id': $('#draft_id').val(),   //草稿是否存在，表存在
                'is_draft' : isDraft
            };
            var ebayReplyImageObj = $('.ebay_reply_upload_image_display > img');//.attr('src');
            for(var i=0;i<ebayReplyImageObj.length;i++)
            {
                sendData['image['+i+']'] = ebayReplyImageObj[i].src;
            }
            $.post('<?php echo Url::toRoute(['/mails/ebayreply/add'])?>',sendData,function(data){
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
        //标记已回复，下一封
        $('.reply_mail_mark').click(function(){
            var markType = $(this).attr('reply_type');
            $.post('<?php echo Url::toRoute(['/mails/ebayinbox/mark'])?>',{'inbox_id':$('#inbox_id').val(),'type':markType},function(data){
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
                        $('#reply_content').val(data.content);

                }
            },'json');
        });
        //模板搜索
        $('.mail_template_search_btn').click(function(){
            alert(1);return;
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

<div id="page-wrapper-inbox">
    <div class="panel panel-success" style="width: 49%;float: left;height:750px;overflow-y:scroll; overflow-x:scroll;">
        <div class="panel-heading">
            <h3 class="panel-title">
                <ul class="list-inline" id="ulul">
                    <?php if(!empty($tags_data)){
                        foreach($tags_data as $key => $value)
                        { ?>
                            <li style="margin-right: 20px;" class="btn btn-default" id = "tags_value<?php echo $key;?>"><span use_data="<?php echo $key;?>"><?php echo $value;?></span>&nbsp;<a class="btn btn-warning" href="javascript:void(0)" onclick="removetags(this);">x</a></li>
                        <?php }
                    }?>
                </ul>
            </h3>
        </div>
        <div class="panel-body" >
            <?php if(!empty($firstReplyModel)):?>
                <!-- <div class="well" style="background:#F8F8FF;">
                    <p style="border-bottom: 2px solid #EEEEE0"> -->
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion"
                           href="#collapseReplyItem_first">
                            <span class="mail_item_list"><span style="color:#ffcc66;padding: 0px 10px;"><?php echo $firstReplyModel->account_id?></span><span style="background-color:#ffcc66;padding: 0px 10px;"></span><?php echo $firstReplyModel->sender;?></span>
                            <span class="mail_item_list"><?php echo $firstReplyModel->attributeLabels()['create_time']?>：<?php echo $firstReplyModel->create_time;?></span>
                        </a>
                    </h4>
                </div>
                <div id="collapseReplyItem_first" class="panel-collapse collapse">
                    <div class="panel-body hear-title">
                        <div class="hear-title">
                            <span class="mail_item_list"><?php echo $firstReplyModel->attributeLabels()['sender']?>：<?php echo $firstReplyModel->sender;?></span>
                            <span class="mail_item_list"><?php echo $firstReplyModel->attributeLabels()['recipient_id']?>：<?php echo $firstReplyModel->recipient_id;?></span>
                            <span class="mail_item_list"><?php echo $firstReplyModel->attributeLabels()['is_send']?>：<?php echo $firstReplyModel::$isDraftMap[$firstReplyModel->is_send];?></span>
                        </div>
                    </div>
                </div>
                <!-- </p> -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <?php echo $firstReplyModel->reply_title?>
                        </h4>
                    </div>
                    <div id="collapseReply_first" class="panel-collapse collapse">
                        <div class="panel-body">
                            <?=$firstReplyModel->reply_content?>
                        </div>
                    </div>
                </div>
            <?php endif;?>
            <!--  -->
            <?php foreach($models as $modelKey=>$model):?>
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
                    <div class="panel-heading">
                        <h5 class="panel-title">  
                        <!--                如果是ebay发来的邮件只显示时间最近的一封 -->
                    
                            <?php if(!isset($model['inbox_id']) && $isReplied = isset($model['is_replied']) ):?>
                                <?php if($model['is_replied'] == 0):?>
                                    
                                    <?php if($model['sender'] == EbayInbox::SENDER_EBAY):?>

                                        <a class="show_content_btn" data-parent="#accordion" target="_blank" href="http://www.ebay.com/itm/<?=$model['item_id']?>">
                                        <?php echo $model['subject'];?>
                                        </a><br/>

                                    <?php else:?>

                                        <a class="show_content_btn"  style="font-weight: bold;font-family:SimHei" data-parent="#accordion" target="_blank" href="http://www.ebay.com/itm/<?=$model['item_id']?>">
                                        <?php echo $model['subject'];?>
                                        </a><br/>

                                    <?php endif;?>


                                <?php else:?>
                                    <a class="show_content_btn" data-parent="#accordion" target="_blank" href="http://www.ebay.com/itm/<?=$model['item_id']?>">
                                        <?php echo $model['subject'];?>
                                    </a><br/>
                                <?php endif;?>

                                    <span class="mail_item_list"><span style="color:#ffcc66;"><?php echo $model['sender'];?></span>
                                    <i class="fa fa-exchange"></i><span style="color:#ffcc66;"><?php echo $model['recipient_user_id'];?></span>
                                    <span class="mail_item_list" style="margin-left:20px;"><?php echo $model['receive_date']?></span>


                            <?php else:?>

                                <a class="show_content_btn" data-parent="#accordion" target="_blank" href="http://www.ebay.com/itm/<?=$model['item_id']?>">

                                    <?php echo "Re:".$model['reply_title'];?>
                                </a><br/>
                                 <span class="mail_item_list"><span style="color:#ffcc66;"><?php echo $model['sender'];?></span><i class="fa fa-exchange" style="margin-top: 2px;"></i><span style="color:#ffcc66;padding: 0px 10px;"><?php echo $model['recipient_id'];?></span>
                                    <span class="mail_item_list " style="margin-left:20px;"><?php echo isset($model['receive_date'])?$model['receive_date']:$model['create_time']?></span>

                            <?php endif;?>
                        </h5>
                    </div>
                    <div id="dialog_large_image"></div>
                        <?php if(!isset($model['inbox_id']) && $currentModel->id == $model['id']):?>

                            <div class="panel-body" style="background-color: #E6E6F2;">
                                <?php 
                                    echo !isset($model['inbox_id'])?$model['new_message']:$model['reply_content'];
                                    echo !isset($model['inbox_id'])?$model['image']:'';
                                ?>
                            </div>
                        <?php else:?>
                            <div class="panel-body">
                                <?php 
                                    echo !isset($model['inbox_id'])?nl2br($model['new_message']):nl2br($model['reply_content']);
                                    echo !isset($model['inbox_id'])?$model['image']:'';
                                ?>
                            </div>
                        <?php endif;?>
                    
                </div>
            <?php endforeach;?>
        </div></div>
    <div class="panel panel-success" style="margin-left:10px;width: 49%;float: left;height:720px;">
        <div class="panel-heading">
            <h3 class="panel-title"></h3>
        </div>
        <div class="panel-body">
            <div style="margin-bottom: 10px">
                <form class="bs-example bs-example-form" role="form">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="input-group">
                                <input type="text" class="form-control mail_template_search_text" placeholder="消息模板搜索">
                                <span class="input-group-btn">
                        <button class="btn btn-default mail_template_search_btn" type="button">搜索</button>
                    </span>
                            </div><!-- /input-group -->
                        </div><!-- /.col-lg-6 -->
                    </div><!-- /.row -->
                </form>
            </div>
            <div class="panel panel-default">
                <div class="mail_template_area panel-body">
                    <?php
                        
                        $mailTemplates = MailTemplate::AccordingToAccountShow(Platform::PLATFORM_CODE_EB);
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
            </div>
            <div class="btn-group">
                <button type="button" reply_type="reply" class="reply_mail_save btn btn-default">回复消息</button>
                <button type="button" reply_type="draft" class="reply_mail_save btn btn-default">存草稿</button>
                <button type="button" reply_type="replied" class="reply_mail_mark btn btn-default">标记已回复</button>
                <button type="button" reply_type="next" class="reply_mail_mark btn btn-default">下一封</button>
                <?= Html::a('新增标签', Url::toRoute(['/mails/ebayinbox/addtags', 'ids' => $currentModel->id,'type'=>'detail']), ['class' => 'btn btn btn-primary add-tags-button-button']) ?>

                <?= Html::a('移除标签', Url::toRoute(['/mails/ebayinbox/removetags', 'id' => $currentModel->id,'type'=>'detail']), ['class' => 'btn btn-danger add-tags-button-button']) ?>


            </div>
            <script src="<?php echo yii\helpers\Url::base(true);?>/js/jquery.form.js"></script>
            <form role="form" style="height: 265px">
                <div class="form-group">
                    <?php echo Html::hiddenInput('inbox_id',$currentModel->id,['id'=>'inbox_id']);?>
                    <?php echo Html::hiddenInput('reply_id',empty($replyModel->id)?'':$replyModel->id,['id'=>'reply_id']);?>
                    <?php echo Html::hiddenInput('draft_id',empty($draftModel->id)?'':$draftModel->id,['id'=>'draft_id']);?>
                    <label for="name"><?php echo $replyModel->attributeLabels()['reply_content']?></label>  <!---->
                    

                   <!--  <script id="editor" type="text/plain" style="width:900px;height:350px;"></script>   -->


                     <textarea id="reply_content" class="form-control" rows="3" placeholder="输入回复内容" style="height: 300px"><?php echo !isset($draftModel->reply_content)?"":$draftModel->reply_content?></textarea> <!---->



                    <button class="ebay_reply_upload_image" type="button">上传图片</button>
                    <div class="ebay_reply_upload_image_display_area">
                        <?php
                        if(!empty($replyModel->id))
                        {
                            $replyPictureModel = \app\modules\mails\models\EbayReplyPicture::find()->where(['reply_table_id'=>$replyModel->id])->all();
                            if(!empty($replyPictureModel))
                            {
                                foreach($replyPictureModel as $replyPictureModelV)
                                    echo '<div class="ebay_reply_upload_image_display"><img style="height:50px;width:50px;" src="',$replyPictureModelV->picture_url,'" ><a class="ebay_reply_upload_image_delete">删除</a></div>';
                            }
                        }
                        ?>
                    </div>

                    <script type="application/javascript">
                        $(function(){
                            //上传图片
                            $('.ebay_reply_upload_image').click(function(){
                                layer.open({
                                    area:['500px','200px'],
                                    type: 1,
                                    title:'上传图片',
                                    content: '<form style="padding:10px 0px 0px 20px" action="<?php echo Url::toRoute('/mails/ebayreply/uploadimage')?>" method="post" id="ebay_pop_upload_image" enctype="multipart/form-data"><input type="file" name="ebay_reply_upload_image"/><p style="color:red">支持图片格式：gif、jpg、png、jpeg、tif、bmp。</p></form>',
                                    btn:'上传',
                                    yes:function(index,layero){
                                        layero.find('#ebay_pop_upload_image').ajaxSubmit({
                                            dataType:'json',
                                            beforeSubmit:function(options){
                                                if(!/(gif|jpg|png|jpeg|tif|bmp)/ig.test(options[0].value.type))
                                                {
                                                    layer.msg('图片格式错误！', {
                                                        icon: 2,
                                                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                    });
                                                    return false;
                                                }
                                            },
                                            success:function(response){
                                                switch(response.status)
                                                {
                                                    case 'error':
                                                        layer.msg(response.info, {
                                                            icon: 2,
                                                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                        });
                                                        break;
                                                    case 'success':
                                                        $('.ebay_reply_upload_image_display_area').append('<div class="ebay_reply_upload_image_display"><img style="height:50px;width:50px;" src="'+response.url+'" ><a class="ebay_reply_upload_image_delete">删除</a></div>');
                                                        layer.close(index);
                                                }
                                            },
                                        });
                                    }
                                });
                            });
                            //删除图片
                            $('.ebay_reply_upload_image_display_area').delegate('.ebay_reply_upload_image_delete','click',function(){
                                if(window.confirm('确定要删除？'))
                                {
                                    var $this = $(this);
                                    var delteImageUrl = $this.siblings('img').attr('src');
                                    $.post('<?php echo Url::toRoute('/mails/ebayreply/deleteimage')?>',{'url':delteImageUrl},function(response){
                                        switch(response.status)
                                        {
                                            case 'error':
                                                layer.msg(response.info,{icon:2,time:2000});
                                                break;
                                            case 'success':
                                                layer.msg('删除成功',{icon:1,time:2000});
                                                $this.parent().remove();
                                        }
                                    },'json');
                                }
                            });
                        })
                    </script>
                </div>
            </form>
        </div>
        <div style="width: 100%;margin-top: 187px">
            <p style="color: #FFFFFF"><strong></strong>----------------------------------------------------------------------------------------------</p>
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#menu1">历史订单</a></li>
            </ul>
            <div class="tab-content">
                <div id="menu1" class="tab-pane fade in active">
                    <br/>
                    <?php
                    if(!empty($Historica)):?>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>订单号</th>
                                <th>帐号</th>
                                <th>国家</th>
                                <th>订单金额</th>
                                <th>订单状态</th>
                                <th>纠纷状态</th>
                                <th>买家ID</th>
                                <th>售后问题</th>
                                <th>付款时间</th>
                                <th>评价</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($Historica as $hKey => $hvalue):?>
                                <?php

                                $transactionIds = array_column($hvalue['detail'],'transaction_id');
                                $cancel_level = EbayCancellations::disputeLevel($hvalue['platform_order_id']);
                                $inquiry_level = EbayInquiry::disputeLevel($hvalue['detail'][0]['transaction_id']);
                                $refund_level = EbayReturnsRequests::disputeLevel($hvalue['detail'][0]['transaction_id']);

                                $final_level = max($cancel_level,$inquiry_level,$refund_level);

                                switch($final_level)
                                {
                                    case 1:
                                        $disputeHtml = '<span style="color:lightgreen">已关闭</span>';
                                        break;
                                    case 2:
                                        $disputeHtml = '<span style="color:lightblue">已解决</span>';
                                        break;
                                    case 3:
                                        $disputeHtml = '<span style="color:red">有</span>';
                                        break;
                                    case 4:
                                        $disputeHtml = '<span style="color:orange">已升级</span>';
                                        break;
                                    default:
                                        $disputeHtml = '<span>无</span>';
                                }

                                if($currentModel->transaction_id != '' && $currentModel->transaction_id != null && in_array($currentModel->transaction_id,$transactionIds))
                                    $currentTrClass = 'class="active"';
                                else
                                    $currentTrClass = '';

                                switch($hvalue['detail'][0]['comment_type']){
                                    case 1:
                                        $comment_type = '<span>IndependentlyWithdrawn</span>';
                                        break;
                                    case 2:
                                        $comment_type = '<span style="color:red">Negative</span>';
                                        break;
                                    case 3:
                                        $comment_type = '<span style="color:orange">Neutral</span>';
                                        break;
                                    case 4:
                                        $comment_type = '<span style="color:green">Positive</span>';
                                        break;
                                    case 5:
                                        $comment_type = '<span>Withdrawn</span>';
                                        break;
                                    default:
                                        $comment_type = '<span">暂无</span>';
                                }

                                ?>
                                <tr <?=$currentTrClass?>>

                                    <td>

                                    <?php foreach($hvalue['detail'] as $deKey => $deVal):?>
                                    
                                        <?php $arr[$deKey] =$deVal['item_id'];

                                            $itemArr = array_unique($arr);
                                        ?>

                                    <?php endforeach;?>

                                    <?php if(in_array($currentModel->item_id, $itemArr)): ?>
                                            
                                            <i class="fa fa-caret-right" style='color:#5cb85c'></i>

                                    <?php endif;?>

                                        <a _width="70%" _height="70%" class="edit-button" href="<?php echo Url::toRoute(['/orders/order/orderdetails',
                                            'order_id' => $hvalue['platform_order_id'],
                                            'platform' => Platform::PLATFORM_CODE_EB,
                                            'system_order_id' => $hvalue['order_id']]);?>" title="订单信息"><?php echo $hvalue['order_id'];?></a>

                                    </td>
                                    <td>
                                        <?php
                                            echo Account::getHistoryAccount($hvalue['account_id'],Ebayinbox::PLATFORM_CODE);
                                        ?>
                                    </td>
                                    <td><?php echo !empty($hvalue['ship_country'])?$hvalue['ship_country']:'NoCountry'?></td>
                                    <td><?php echo $hvalue['total_price'] . $hvalue['currency'];?></td>
                                    <td><?php echo $hvalue['complete_status_text'];?></td>
                                    <td><?php echo $disputeHtml;?></td>
                                    <td><?php echo $hvalue['buyer_id'];?></td>
                                    <td>
                                        <?php
                                        // 售后信息 显示 退款 退货 重寄 退件
                                        $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder(Platform::PLATFORM_CODE_EB,  $hvalue['order_id']);
                                        //是否有售后订单
                                        if ($aftersaleinfo) {
                                            $res = AfterSalesOrder::getAfterSalesOrderByOrderId( $hvalue['order_id'], Platform::PLATFORM_CODE_EB);
                                            //获取售后单信息
                                            if (!empty($res['refund_res'])) {
                                                $refund_res = '退款';
                                                foreach ($res['refund_res'] as $refund_re) {
                                                    $refund_res .=
                                                        '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                                                        $refund_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_EB . '&status=' . $aftersaleinfo->status . '" >' .
                                                        $refund_re['after_sale_id'] . '</a>';
                                                }
                                            } else {
                                                $refund_res = '';
                                            }

                                            if (!empty($res['return_res'])) {
                                                $return_res = '退货';
                                                foreach ($res['return_res'] as $return_re) {
                                                    $return_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id=' .
                                                        $return_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_EB . '&status=' . $aftersaleinfo->status . '" >' .
                                                        $return_re['after_sale_id'] . '</a>';
                                                }
                                            } else {
                                                $return_res = '';
                                            }

                                            if (!empty($res['redirect_res'])) {
                                                $redirect_res = '重寄';
                                                foreach ($res['redirect_res'] as $redirect_re) {
                                                    $redirect_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id=' .
                                                        $redirect_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_EB . '&status=' . $aftersaleinfo->status . '" >' .
                                                        $redirect_re['after_sale_id'] . '</a>';
                                                }
                                            } else {
                                                $redirect_res = '';
                                            }
                                            if (!empty($res['domestic_return'])) {
                                                $domestic_return = '退货跟进';
                                                if ($res['domestic_return']['state'] == 1) {
                                                    $state = '未处理';
                                                } elseif ($res['domestic_return']['state'] == 2) {
                                                    $state = '无需处理';
                                                } elseif ($res['domestic_return']['state'] == 3) {
                                                    $state = '已处理';
                                                } else {
                                                    $state = '驳回EPR';
                                                }
                                                //状态：1、未处理，2、无需处理，3、已处理，4、驳回EPR
                                                $domestic_return.= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                                                    $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_EB . '" >' .
                                                    $res['domestic_return']['return_number'] . '('.$state .')'. '</a>';
                                            } else {
                                                $domestic_return = '';
                                            }
                                            $after_sale_text = '';
                                            if (!empty($refund_res)) {
                                                $after_sale_text .= $refund_res . '<br>';
                                            }
                                            if (!empty($return_res)) {
                                                $after_sale_text .= $return_res . '<br>';
                                            }
                                            if (!empty($redirect_res)) {
                                                $after_sale_text .= $redirect_res . '<br>';
                                            }
                                            if (!empty($domestic_return)) {
                                                $after_sale_text .= $domestic_return;
                                            }
                                            echo $after_sale_text;
                                        } else {
                                            echo '<span class="label label-success">无</span>';
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        if($hvalue['payment_status'] == 0)

                                            echo "未付款";
                                        else
                                            echo $hvalue['paytime'];
                                        ?>
                                    </td>
                                    <td>
                                        <?=$comment_type;?>
                                    </td>
                                    
                                    <td>
                                        <div class="btn-group btn-list">
                                            <button type="button" class="btn btn-default btn-sm"><?php echo \Yii::t('system', 'Operation');?></button>
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                <span class="caret"></span>
                                                <span class="sr-only"><?php echo \Yii::t('system', 'Toggle Dropdown List');?></span>
                                            </button>
                                            
                                            <ul class="dropdown-menu" rol="menu">
                                                <?php foreach($hvalue['detail'] as $value){

                                                    $transaction_id[] = $value['transaction_id']?>

                                                <?php };?>
                                                <li>
                                                    <a _width="30%" _height="60%" class="edit-button" href="<?php echo Url::toRoute(['/orders/order/canceltransaction',
                                                        'orderid'=>$hvalue['order_id'],
                                                          'account_id'=>$hvalue['account_id'],
                                                          'payment_status'=>$hvalue['payment_status'],
                                                          'paytime'=>$hvalue['paytime'],'platform_order_id'=>$hvalue['platform_order_id'],
                                                          'transaction_id'=>$transaction_id
                                                          ]);?>">取消订单</a>
                                                </li>
                                                <?php if ($hvalue['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP || $hvalue['complete_status'] == 99) { ?>
                                                    <li><a _width="30%" _height="60%" class="edit-button" href="<?php echo Url::toRoute(['/orders/order/cancelorder',
                                                            'order_id' => $hvalue['order_id'], 'platform' => 'EB']);?>">永久作废</a></li>
                                                    <li><a _width="30%" _height="60%" class="edit-button" href="<?php echo Url::toRoute(['/orders/order/holdorder',
                                                            'order_id' => $hvalue['order_id'], 'platform' => 'EB']);?>">暂时作废</a></li>
                                                    <?php
                                                }
                                                if ($hvalue['complete_status'] == Order::COMPLETE_STATUS_HOLD)
                                                {
                                                    ?>
                                                    <li><a confirm="确定取消暂时作废该订单？" class="ajax-button" href="<?php echo Url::toRoute(['/orders/order/cancelholdorder',
                                                            'order_id' => $hvalue['order_id'], 'platform' => 'EB']);?>">取消暂时作废</a></li>
                                                    <?php
                                                }
                                                ?>
                                                <?php if ($hvalue['order_type'] != Order::ORDER_TYPE_REDIRECT_ORDER) { ?>
                                                    <li><a _width="80%" _height="80%" class="edit-button" href="<?php echo Url::toRoute(['/aftersales/order/add',
                                                            'order_id' => $hvalue['order_id'], 'platform' => 'EB']);?>">新建售后单</a></li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;?>
                            </tbody>
                        </table>
                    <?php endif;?>
                </div>
            </div>
        </div>
    </div>
    <div style="display: none">
        <form id="ebay_inbox_detail_jump" action="" method="post">
            <input id="ebay_inbox_exclude" name="exclude" value=""/>
        </form>
    </div>
</div>

<script type="text/javascript">
 //标签快捷键设置
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
                        $.post('<?= Url::toRoute(['/mails/ebayinbox/addretags', 'ids' => $currentModel->id, 'type' => 'detail'])?>', {
                            'MailTag[inbox_id]': ids,
                            'MailTag[tag_id][]': tag_id,
                            'MailTag[type]': 'detail'
                        }, function (data) {
                            if (data.code == "200" && data.url == 'add') {
                                /*  window.location.href = data.url;*/
                                var html = "";
                                var result = data.data;
                                $.each(result, function (i, v) {
                                    html += '<li style="margin-right: 20px;" class="btn btn-default" id = "tags_value' + i + '"><span use_data="' + i + '">' + v + '</span>&nbsp;<a class="btn btn-warning" href="javascript:void(0)" onclick="removetags(this);">x</a></li>';
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
                        $.post('<?= Url::toRoute(['/mails/ebayinbox/addretags', 'ids' => $currentModel->id, 'type' => 'detail'])?>', {
                            'MailTag[inbox_id]': ids,
                            'MailTag[tag_id][]': tag_id,
                            'MailTag[type]': 'detail'
                        }, function (data) {
                            if (data.code == "200" && data.url == 'add') {
                                /*  window.location.href = data.url;*/
                                var html = "";
                                var result = data.data;
                                $.each(result, function (i, v) {
                                    html += '<li style="margin-right: 20px;" class="btn btn-default" id = "tags_value' + i + '"><span use_data="' + i + '">' + v + '</span>&nbsp;<a class="btn btn-warning" href="javascript:void(0)" onclick="removetags(this);">x</a></li>';
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
                        $.post('<?= Url::toRoute(['/mails/ebayinbox/addretags', 'ids' => $currentModel->id, 'type' => 'detail'])?>', {
                            'MailTag[inbox_id]': ids,
                            'MailTag[tag_id][]': tag_id,
                            'MailTag[type]': 'detail'
                        }, function (data) {
                            if (data.code == "200" && data.url == 'add') {
                                /*  window.location.href = data.url;*/
                                var html = "";
                                var result = data.data;
                                $.each(result, function (i, v) {
                                    html += '<li style="margin-right: 20px;" class="btn btn-default" id = "tags_value' + i + '"><span use_data="' + i + '">' + v + '</span>&nbsp;<a class="btn btn-warning" href="javascript:void(0)" onclick="removetags(this);">x</a></li>';
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
        $.post('<?= Url::toRoute(['/mails/ebayinbox/removetags','id' => $currentModel->id,'type'=>'detail'])?>', {'MailTag[inbox_id]' : _id, 'MailTag[tag_id][]' : tag_id, 'MailTag[type]' : 'detail'}, function (data) {
            if (data.url && data.code == "200")
                $("#tags_value"+tag_id).hide(50);
        }, 'json');

    }

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
            $('body').append('<div id="overlay"></div>');  
            $('#overlay')  
            .height(docHeight)  
            .css({'opacity': .5,'position': 'relative','top': 0,'left': 0,'background-color': 'black','width': '100%','z-index': 999});
            var thumb = $(this).find('img').attr('src');
            var index = thumb .lastIndexOf("\/");  
            thumb = thumb .substring(0, index);
            var result = $.inArray(thumb,j);

            if(result > -1)
            {   
                var large_image = '<img src= ' + org[result] + '></img>';
                var div = $('#dialog_large_image').html($(large_image).animate({ height: '100%', width: '100%' }, 500));
            }else{
                var noexists = '<img src=/img/noexists.jpg>'
                $("#dialog_large_image").html($(noexists).animate({height: '100%', width: '100%'},500));
            }
        })
    })
    $("#dialog_large_image").click(function(){$(this).html('');$('body').find('#overlay').remove();})

</script>