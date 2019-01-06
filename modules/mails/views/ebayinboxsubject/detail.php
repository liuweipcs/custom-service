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
use app\modules\systems\models\Country;
use app\modules\orders\models\Transactionrecord;
use app\modules\systems\models\PaypalAccount;
use app\modules\orders\models\PaypalInvoiceRecord;
use app\modules\orders\models\Tansaction;

$this->title = 'Ebay邮件主题详情';
?>
<style>
    .ebay_reply_upload_image_delete {
        cursor: pointer;
    }

    .ebay_reply_upload_image_display {
        margin: 2px 2px 0px 0px;
        float: left;
    }

    .ebay_reply_upload_image_delete {
        line-height: 0px;
    }

    .mail_template_unity {
        margin-right: 10px;
    }

    .show_content_btn {
        text-decoration: underline;
        font-size: 13px;
    }

    #dialog_large_image {
        margin-left: 30%;
        position: absolute;
        z-index: 9999;
    }

    .language {
        width: 550px;
        float: left;
    }

    .language li {
        width: 16%;
        float: left;
    }

    .language li a {
        font-size: 10px;
        text-align: left;
        cursor: pointer;
    }

    .col-sm-4 {
        width: auto;
    }

    .alert-warning {
        color: #ffffff;
        background-color: rgba(243, 156, 18, .88);
        border-color: rgba(243, 156, 18, .88);
    }

    .alert {
        margin-top: 5px;
    }
</style>
<script type="application/javascript">
    $(function () {
        $('div.sidebar').hide();
        //回复保存、存草稿
        $('.reply_mail_save').click(function () {

//            var replyContentVal = $.trim($('#ueditor_0').contents().find('body p').text());
//            var replyContent = $.trim($('#ueditor_0').contents().find('body').html())
            var hide_last_language_code = $.trim($("#hide_last_language_code").val());
            var replyContentVal = $.trim($("#reply_content").val());
            var replyContent = $.trim($("#reply_content").val());//回复给客户翻译后的消息
            var replyContentEn = $.trim($("#reply_content_en").val());//客服回复的消息
//            var sl = $("#sl_code").val();

            //确保发送给客户的内容不为空
            if (replyContent == "") {
                if (replyContentEn == "") {
                    layer.msg('请输入回复内容s!');
                    return false;
                } else {
                    replyContent = replyContentEn;
                }
            }

            //如果翻译内容为空 则直接获取发送给客户的内容
            if (replyContentEn == "") {
                replyContentEn = replyContent;
            }

            var isDraft = $(this).attr('reply_type') == 'draft' ? 1 : 0;
            var sendData = {
//                'reply_title': replyTitle,
//                'question_type': questionType,
//                'subject_id':$('#subject_id').val(),
                'reply_content': replyContent,
                'reply_content_en': replyContentEn,
                'inbox_id': $('#inbox_id').val(),
                'id': $('#reply_id').val(),     //回复id
                'is_draft': isDraft
            };
            var ebayReplyImageObj = $('.ebay_reply_upload_image_display > img');//.attr('src');
            for (var i = 0; i < ebayReplyImageObj.length; i++) {
                sendData['image[' + i + ']'] = ebayReplyImageObj[i].src;
            }
            $.post('<?php echo Url::toRoute(['/mails/ebayreply/addsubject'])?>', sendData, function (data) {
                // console.log(data);return;
                switch (data.status) {
                    case 'error':
                        layer.msg(data.message, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                        return;
                    case 'success':
                        if (typeof data.url == 'string') {
                            $('#ebay_inbox_detail_jump').attr('action', data.url).submit();
                        }
                }
            }, 'json');
        });
        //标记已回复，上一个，下一个
        $('.reply_mail_mark').click(function () {
            var markType = $(this).attr('reply_type');
            $.post('<?php echo Url::toRoute(['/mails/ebayinboxsubject/mark'])?>', {
                'subject_id':<?= $currentModel->id; ?>,
                'type': markType
            }, function (data) {
                switch (data.status) {
                    case 'success':
                        $('#ebay_inbox_detail_jump').attr('action', data.url).submit();
                        break;
                    case 'error':
                        layer.msg(data.info, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                        });
                }
            }, 'json');
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

                        var refund_content = $('#reply_content_en').val();
                        if (refund_content !== '') {
                            $('#reply_content_en').val(refund_content + '\n' + data.content);
                        } else {
                            $('#reply_content_en').val(data.content);
                        }
                }
            }, 'json');
        });
        //模板搜索
        $('.mail_template_search_btn').click(function () {
            var templateName = $.trim($('.mail_template_search_text').val());
            var platform_code = 'EB';
            if (templateName.length == 0) {
                layer.msg('搜索名称不能为空。', {
                    icon: 2,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                });
                return;
            }
            $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplate']);?>', {
                'name': templateName,
                'platform_code': platform_code
            }, function (data) {
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
            $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplatetitle']);?>', {
                'name': templateTitle,
                'platform_code': 'EB'
            }, function (data) {
                if (data.code == 200) {
                    $('#reply_content_en').val(data.data);
                }
                else {
                    layer.msg(data.message, {
                        icon: 2,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    });
                    return;
                }
            }, 'json');
        }
    });

</script>
<style>
    li {
        list-style: none;
    }

    .hear-title, .search-box ul {
        overflow: hidden;
    }

    .hear-title p:nth-child(1) span:nth-child(1), .hear-title p:nth-child(2) span:nth-child(1) {
        display: inline-block;
        width: 30%
    }

    .item-list li {
        border-bottom: 1px solid #ddd;
        padding: 5px 10px
    }

    .item-list li span {
        display: inline-block;
        width: 25%
    }

    .search-box ul li {
        float: left;
        padding: 0 10px 10px 0
    }

    .search-box textarea {
        display: block;
        margin-top: 10px;
        width: 100%
    }

    .info-box .det-info {
        width: 100%;
        height: 200px;
        border: 2px solid #ddd;
    }

    /*.well span{padding: 6%}*/
    .well p {
        text-align: left
    }

    .mail_item_list {
        margin-right: 30px;
        display: inline-block;
    }

    .mail_template_unity {
        cursor: pointer;
    }
</style>
<div class="col-md-12">
    <div id="page-wrapper-inbox" class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading cancel-inbox-id">
                <h3 class='panel-title'>
                    <i class="fa fa-pencil"></i>邮件
                    <ul style="display: inline-block;" id="ulul">
                        <?php if (!empty($tags_data)) {
                            foreach ($tags_data as $key => $value) { ?>
                                <li style="margin-right: 20px;" class="tag label btn-info md ion-close-circled"
                                    id="tags_value<?php echo $key; ?>"><span
                                            use_data="<?php echo $key; ?>"><?php echo $value; ?></span>&nbsp;<a
                                            href="javascript:void(0)" onclick="removetags(this);">x</a></li>
                            <?php }
                        } ?>
                    </ul>
                </h3><br/>
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
            <div class="panel-body" style="height: auto; max-height:700px;overflow-y:scroll; overflow-x:scroll;">
                <?php
                $rep_key = 1;
                foreach ($models as $modelKey => $model):?>
                    <div id="collapseItem<?= $modelKey ?>" class="panel-collapse collapse">
                        <div class="panel-body hear-title">
                            <div class="hear-title">
                                <span class="mail_item_list"><?php echo isset($model['flagged']) ? $model['flagged'] : "" ?></span>
                                <span class="mail_item_list"><?php echo isset($model['high_priority']) ? $model['high_priority'] : "" ?></span>
                                <span class="mail_item_list"><?php echo isset($model['item_id']) ? $model['item_id'] : "" ?></span>
                                <span class="mail_item_list"><?php echo isset($model['expiration_date']) ? $model['expiration_date'] : "" ?></span>
                                <span class="mail_item_list"><?php echo isset($model['message_type']) ? $model['message_type'] : "" ?></span>
                                <span class="mail_item_list"><?php echo isset($model['is_read']) ? $model['is_read'] : "" ?></span>
                                <span class="mail_item_list"><?php echo isset($model['is_replied']) ? $model['is_replied'] : "" ?></span>
                                <span class="mail_item_list"><?php echo isset($model['response_enabled']) ? $model['response_enabled'] : "" ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- </p>  -->
                    <div class="<?php if (!isset($model['inbox_id'])):echo 'panel panel-primary'; ?><?php else:echo 'panel panel-default'; endif; ?>"
                         id="content_<?= !isset($model['inbox_id']) ? $model['id'] : 0 ?>"
                         style="width:80%;<?php if (isset($model['inbox_id'])) echo 'margin-left:20%;'; ?>">
                        <div <?php if (!isset($model['inbox_id'])): ?> class="panel-heading get-data-id" data-id="<?php echo $model['id']; ?>"<?php else : ?> class="panel-heading" <?php endif; ?>>
                            <h5 class="panel-title">
                                <a class="show_content_btn" data-parent="#accordion" target="_blank"
                                   href="http://www.ebay.com/itm/<?= $model['item_id'] ?>"<?php if (isset($model['is_replied']) && $model['is_replied'] == 0): ?> style="font-weight: bold;font-family:SimHei" <?php endif; ?>>
                                    <?php echo !isset($model['inbox_id']) ? $model['subject'] : "Re:" . $model['reply_title'] ?>
                                </a><br/>
                                <?php if (isset($model['inbox_id'])){ ?>
                                <span class="mail_item_list"><span
                                            style="color:#ffcc66;"><?php echo $model['sender']; ?></span><i
                                            class="	glyphicon glyphicon-arrow-right"
                                            style="margin-top: 2px;"></i><span
                                            style="color:#ffcc66;padding: 0px 10px;"><?php echo $model['recipient_id']; ?></span>
                                    <span class="mail_item_list "
                                          style="margin-left:20px;"><?php echo $model['create_time']; ?></span>
                                    <span class="mail_item_list bg-success"
                                          style="margin-left:20px;"><?php echo $model['create_by']; ?></span>
                                    <?php }else{ ?>
                                    <span class="mail_item_list"><span
                                                style="color:#ffcc66;"><?php echo $model['sender']; ?></span>
                                <i class="glyphicon glyphicon-arrow-right"></i><span
                                                style="color:#ffcc66;"><?php echo $model['account_name']; ?></span>
                                <span class="mail_item_list"
                                      style="margin-left:20px;"><?php echo $model['receive_date'] ?></span>

                                <span id="remark_<?php echo $model['id']; ?>">
                                <?php if (empty($model['remark'])) { ?>
                                    <i class="fa remark fa-pencil remark" style="cursor: pointer;"
                                       data="<?php echo $model['id']; ?>" data1=""></i>
                                <?php } else { ?>
                                    <li class="tag label btn-info md ion-close-circled"><span style="cursor: pointer;"
                                                                                              class="remark"
                                                                                              data="<?php echo $model['id']; ?>"
                                                                                              data1=""><?php echo $model['remark']; ?></span>&nbsp;&nbsp;<a
                                                href="javascript:void(0)"
                                                onclick="removetags(<?php echo $model['id']; ?>);">x</a></li>
                                    <!--<span class="remark" style="cursor: pointer;" data="<?php echo $model['id']; ?>" data1=""><?php echo $model['remark']; ?></span>-->
                                <?php } ?>
                                </span>
                                        <?php } ?>
                            </h5>
                        </div>
                        <div id="dialog_large_image"></div>
                        <?php
                        if (!isset($model['inbox_id']) && $currentModel->id == $model['id']) {
                            ?>
                            <div class="panel-body" style="background-color: #E6E6F2;">
                                <?php
                                echo !isset($model['inbox_id']) ? $model['new_message'] : nl2br($model['reply_content']);
                                echo !isset($model['inbox_id']) ? $model['image'] : '';
                                ?>

                            </div>
                        <?php } else {
                            ?>
                            <div class="panel-body" <?php if (!isset($model['inbox_id'])) echo 'style="background-color:#D1EFAF;"'; ?>>
                                <?php
                                //                                    echo !isset($model['inbox_id'])?'<div class="message">'.$model['new_message'].'</div>'.'<a type="button" onclick="show_translate($(this))" class="btn btn-sm waves-effect waves-light" data-toggle="modal" data-target="#myModal">点击翻译</a>':nl2br($model['reply_content']).'<a type="button" class="btn btn-sm waves-effect waves-light" data-toggle="modal" data-target="#myModal">点击翻译</a>';
                                if (!isset($model['inbox_id'])) {
                                    echo '<span id="message_' . $rep_key . '">' . $model['new_message'] . '</span>  <a onclick="clikTrans($(this),' . $rep_key . ')" style="cursor: pointer;">点击翻译</a>';
                                    echo '<span id="trans_message' . $rep_key . '"></span>';
                                } else {
                                    echo '<span id="message_' . $rep_key . '">' . nl2br(strip_tags($model['reply_content_en'])) . '</span>';
                                    echo '<span id="trans_message' . $rep_key . '"></span>';
                                }
                                if (!isset($model['inbox_id'])) {
                                    echo str_replace('id="imagePreviewHtml"', 'class="imagePreviewHtml"', $model['image']);
                                }
                                ?>
                            </div>
                        <?php } ?>
                        <?php if (isset($model['pictures']) && !empty($model['pictures'])) {
                            echo '<hr>';
                            echo '图片:';
                            foreach ($model['pictures'] as $picture) {
                                echo '<a href="' . $picture['picture_url'] . '" target="_blank";><img width="100px" height="100px" src="' . $picture['picture_url'] . '"></img></a>&nbsp;';
                            }
                        }
                        ?>
                    </div>
                    <?php
                    $rep_key++;
                endforeach; ?>
            </div>
        </div>
        <div class="col-lg-6" style="width: 100%;">
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#menu1">历史订单</a></li>
            </ul>

            <?php
            if (!empty($Historica)):?>
                <table class="table table-hover" style="font-size:11px;">
                    <thead>
                    <tr>
                        <th>订单号</th>
                        <th>帐号<br/>国家<br/>买家ID</th>
                        <th>付款时间<br/>订单状态<br/>评价</th>
                        <th>订单金额<br/>退款金额<br/>利润</th>
                        <th>纠纷状态<br/>退货编码<br/>售后<br/>仓库客诉</th>
                        <th>包裹信息</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $text_data_map = array(0 => array('data' => '无', 'color' => ''), 1 => array('data' => '已关闭', 'color' => 'lightgreen'), 2 => array('data' => '已解决', 'color' => 'lightblue'), 3 => array('data' => '有', 'color' => 'red'), 4 => array('data' => '已升级', 'color' => 'orange'));
                    $case_key      = array(1, 2, 3, 4);

                    ?>
                    <?php $warehouseList = \app\modules\orders\models\Warehouse::getWarehouseListAll(); ?>
                    <?php foreach ($Historica as $hKey => $hvalue): ?>
                        <?php
                        if ($hKey == 0) {
                            //获取仓库id 第一个订单id
                            $order_id                     = isset($hvalue['orderPackage'][0]['order_id']) ? $hvalue['orderPackage'][0]['order_id'] : '';
                            $warehouse_id                 = isset($hvalue['orderPackage'][0]['warehouse_id']) ? $hvalue['orderPackage'][0]['warehouse_id'] : 0;
                            $current_order_warehouse_name = array_key_exists($warehouse_id, $warehouseList) ?
                                $warehouseList[$warehouse_id] : '';
                            echo "<input type='hidden' name='current_order_warehouse_id' value='$warehouse_id'>" .
                                "<input type='hidden' name='current_order_id' value='$order_id'>" .
                                "<input type='hidden' name='current_order_warehouse_name' value='$current_order_warehouse_name'>";
                        }

                        $cancel_cases  = EbayCancellations::disputeLevel($hvalue['platform_order_id']);
                        $inquiry_cases = EbayInquiry::disputeLevel($hvalue['platform_order_id']);
                        $returns_cases = EbayReturnsRequests::disputeLevel($hvalue['platform_order_id']);

                        $disputeHtml = '';

                        if (!empty($cancel_cases)) {
                            foreach ($cancel_cases as $cancel_case) {
                                if (in_array($cancel_case[0], $case_key)) {
                                    $disputeHtml .= '<p style="margin:0 0 0px;"><a _width="100%" _height="100%" class="edit-button" style="color:' . $text_data_map[$cancel_case[0]]['color'] . '" href="' . Url::toRoute(['/mails/ebaycancellation/handle', 'id' => $cancel_case[1], 'isout' => 1]) . '">' . $cancel_case[2] . $text_data_map[$cancel_case[0]]['data'] . '</a>&nbsp;</p>';
                                }
                            }
                        }

                        if (!empty($inquiry_cases)) {
                            foreach ($inquiry_cases as $inquiry_case) {
                                if (in_array($inquiry_case[0], $case_key)) {
                                    $disputeHtml .= '<p style="margin:0 0 0px;"><a _width="100%" _height="100%" class="edit-button" style="color:' . $text_data_map[$inquiry_case[0]]['color'] . '" href="' . Url::toRoute(['/mails/ebayinquiry/handle', 'id' => $inquiry_case[1], 'isout' => 1]) . '">' . $inquiry_case[2] . $text_data_map[$inquiry_case[0]]['data'] . '</a>&nbsp;</p>';
                                }
                            }
                        }

                        if (!empty($returns_cases)) {
                            foreach ($returns_cases as $returns_case) {
                                if (in_array($returns_case[0], $case_key)) {
                                    $disputeHtml .= '<p style="margin:0 0 0px;"><a _width="100%" _height="100%" class="edit-button" style="color:' . $text_data_map[$returns_case[0]]['color'] . '" href="' . Url::toRoute(['/mails/ebayreturnsrequests/handle', 'id' => $returns_case[1], 'isout' => 1]) . '">' . $returns_case[2] . $text_data_map[$returns_case[0]]['data'] . '</a>&nbsp;</p>';
                                }
                            }
                        }

                        if (empty($disputeHtml)) {
                            $disputeHtml = '<p style="margin:0 0 0px;">无</p>';
                        }
                        $currentTrClass = '';

                        switch ($hvalue['comment_type']) {
                            case 1:
                                $comment_type = '<span>IndependentlyWithdrawn</span>';
                                break;
                            case 2:
                                $comment_type = '<span"><a _width="100%" _height="100%" style="color:red;" href="' . Url::toRoute(['/mails/ebayfeedbackresponse/add', 'type' => 'Reply', 'id' => $hvalue['feed_id']]) . '" class="edit-button" id="status">Negative</a></span>';
                                break;
                            case 3:
                                $comment_type = '<span"><a _width="100%" _height="100%"style="color:orange;" href="' . Url::toRoute(['/mails/ebayfeedbackresponse/add', 'type' => 'Reply', 'id' => $hvalue['feed_id']]) . '" class="edit-button" id="status">Neutral</a></span>';
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

                        switch ($hvalue['order_type']) {
                            case Order::ORDER_TYPE_MERGE_MAIN:
                                $rela_order_name = '合并前子订单';
                                $rela_is_arr     = true;
                                break;
                            case Order::ORDER_TYPE_SPLIT_MAIN:
                                $rela_order_name = '拆分后子订单';
                                $rela_is_arr     = true;
                                break;
                            case Order::ORDER_TYPE_MERGE_RES:
                                $rela_order_name = '合并后父订单';
                                $rela_is_arr     = false;
                                break;
                            case Order::ORDER_TYPE_SPLIT_CHILD:
                                $rela_order_name = '拆分前父订单';
                                $rela_is_arr     = false;
                                break;
                            default:
                                $rela_order_name = '';
                        }

                        $order_result = '';
                        if (!empty($rela_order_name)) {
                            if ($rela_is_arr) {
                                foreach ($hvalue['son_order_id'] as $son_order_id) {
                                    $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=EB&system_order_id=' . $son_order_id . '" title="订单信息">
                                                ' . $son_order_id . '</a></p>';
                                }
                                if (!empty($order_result))
                                    $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                            } else {
                                $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=EB&system_order_id=' . $hvalue['parent_order_id'] . '" title="订单信息">
                                                ' . $hvalue['parent_order_id'] . '</a></p>';
                                $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                            }
                        }

                        ?>
                        <?php $account_info = Account::getHistoryAccountInfo($hvalue['account_id'], Ebayinbox::PLATFORM_CODE); ?>
                        <tr <?= $currentTrClass ?>>

                            <td>

                                <?php foreach ($hvalue['detail'] as $deKey => $deVal): ?>

                                    <?php $arr[$deKey] = $deVal['item_id'];

                                    $hvalueArr = array_unique($arr);
                                    ?>

                                <?php endforeach; ?>

                                <?php if (in_array($currentModel->item_id, $hvalueArr) && empty($hvalue['parent_order_id'])): ?>

                                    <i class="fa fa-caret-right" style='color:#5cb85c'></i>

                                <?php endif; ?>

                                <a _width="100%" _height="100%" class="edit-button"
                                   href="<?php echo Url::toRoute(['/orders/order/orderdetails',
                                       'order_id'        => $hvalue['platform_order_id'],
                                       'platform'        => Platform::PLATFORM_CODE_EB,
                                       'system_order_id' => $hvalue['order_id']]); ?>"
                                   title="订单产品详情"><?php echo isset($account_info->account_short_name) ? $account_info->account_short_name . '--' . $hvalue['order_id'] : $hvalue['order_id']; ?></a>
                                <?php echo $order_result; ?>
                                <br>

                                <?php if (count($hvalue['detail'])) {
                                    echo '<a data-toggle="collapse" data-parent="#accordion" href="#proDetail_' . $hKey . '" aria-expanded="true" class="">查看产品详情</a><br>';
                                } ?>

                                <?php if (count($hvalue['remark'])) {
                                    $remark = htmlspecialchars(json_encode($hvalue['remark'])); ?>
                                    <span style="color:red" class="have_remark" data-remark="<?php echo $remark; ?>">有备注</span>
                                <?php } else { ?>
                                   <span>无备注</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php
                                echo isset($account_info->account_name) ? $account_info->account_name : '';
                                ?>
                                <?php echo !empty($hvalue['ship_country']) ? '<br/>' . $hvalue['ship_country'] : '<br/>NoCountry' ?>
                                <?php echo '<br/>' . $hvalue['buyer_id']; ?>
                            </td>
                            <td>
                                <?php
                                if ($hvalue['payment_status'] == 0)
                                    echo "未付款";
                                else
                                    echo $hvalue['paytime'];
                                ?>
                                <br/><span <?php if ($hvalue['complete_status_text'] == '已取消') echo 'style="color:red;"'; ?>><?php echo $hvalue['complete_status_text']; ?></span>
                            <?php echo $comment_type; ?>
                            </td>
                            <td>
                                <?php
                                if (isset($hvalue['trade']) && !empty($hvalue['trade'])) {
                                    $f_total_price = $hvalue['total_price'];
                                    foreach ($hvalue['trade'] as $v_price) {
                                        if ($v_price['receive_type'] == '发起')
                                            $f_total_price -= $v_price['amt'];
                                    }
                                    if (number_format($f_total_price, 2, '.', '') == 0)
                                        $f_total_price = 0.00;
                                    echo '<b style="color:green">' . $f_total_price . $hvalue['currency'] . '</b><br/>';

                                } else {
                                    echo '<b style="color:green">' . $hvalue['total_price'] . $hvalue['currency'] . '</b><br/>';
                                }
                                ?>

                                <?php
                                //退款金额
                                /*if(!empty($hvalue['profit']))
                                    echo $hvalue['profit']['refund_amount'];*/
                                if (isset($hvalue['trade']) && !empty($hvalue['trade'])) {
                                    $after_refund_amount = 0;
                                    foreach ($hvalue['trade'] as $after_sale_refund) {
                                        if ($after_sale_refund['amt'] < 0)
                                            $after_refund_amount += $after_sale_refund['amt'];
                                    }
                                    if (!empty($after_refund_amount)) {
                                        echo $after_refund_amount . '<br/>';
                                    } else {
                                        echo '-<br/>';
                                    }
                                } else {
                                    echo '-<br/>';
                                }
                                ?>


                                <?php
                                //利润
                                $refundlost = 0;
                                if (!empty($hvalue['profit'])) {
                                    if (!empty($after_refund_amount))
                                        $refundlost = -$after_refund_amount;
                                    if (!empty($hvalue['after_sale_redirect'])) {
                                        foreach ($hvalue['after_sale_redirect'] as $after_sale_redirect) {
                                            $cost = new Order;
                                            $cost = $cost->getRedirectCostByOrderId(Platform::PLATFORM_CODE_EB, $hvalue['order_id']);
                                            if ($cost && $cost->ack == true) {
                                                $cost       = $cost->data;
                                                $refundlost += $cost;
                                            }
                                        }
                                    }

                                    $refundlost = $hvalue['profit']['profit'] - $refundlost;
                                    echo $refundlost;
                                }
                                ?>
                            </td>
                            <td>
                              
                                <?php echo $disputeHtml; ?>
                                 <?php 
                                 //退货编码
                                 $refundcode=\app\modules\aftersales\models\AfterRefundCode::find()->where(['order_id'=>$hvalue['order_id']])->asArray()->one();
                                 if(empty($refundcode)){
                                      echo '<span class="label label-success">无</span>';
                                 }else{
                                     echo $refundcode['refund_code'];
                                 }
                                 
                                 ?>
                                <?php
                                echo '<br/>';
                                // 售后信息 显示 退款 退货 重寄 退件
                             //  $hvalue['order_id']= 'EB180502007751'; 
                                $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder(Platform::PLATFORM_CODE_EB,  $hvalue['order_id']); 
                             //   echo "<pre>";
                            //    print_r($res);
                            //    die;
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
                                     $res = AfterSalesOrder::getAfterSalesOrderByOrderId( $hvalue['order_id'], Platform::PLATFORM_CODE_EB);
                                     
                                     if(!empty($res['domestic_return'])){
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
                                   
                            
                                       echo $domestic_return;
                                     }else{
                                          echo '<span class="label label-success">无</span>';
                                     }
                                  
                                }
                                ?>
                              <br/>
                             <?php 
                             $complaint=\app\modules\aftersales\models\ComplaintModel::find()->select('complaint_order,status')->where(['order_id'=>$hvalue['order_id']])->one();
                            if(empty($complaint)) {
                               echo '<span class="label label-success">无</span>'; 
                            }else{
                               if($complaint->status==6){
                                  echo '<a _width="100%" _height="100%" class="edit-button" href='.Url::toRoute(['/aftersales/complaint/getcompain', 'complaint_order' => $complaint->complaint_order]).'>'.$complaint->complaint_order.'(已处理)</a>';
                               }else{
                                     echo '<a _width="100%" _height="100%" class="edit-button" href='.Url::toRoute(['/aftersales/complaint/getcompain', 'complaint_order' => $complaint->complaint_order]).'>'.$complaint->complaint_order.'(未处理)</a>';
                               }   
                            }
                             
                             
                             ?>
                              
                                <!--用于保存售后单信息勿删-->
                                <span id="after_<?php echo $hvalue['order_id']; ?>"></span>    
                            </td>
                            <?php
                            if (count($Historica[$hKey]['orderPackage'])) {
                                foreach ($Historica[$hKey]['orderPackage'] as $k => $val) {
                                    $trackNumber = empty($hvalue['track_number']) ? $val['tracking_number_1'] : $hvalue['track_number'];
                                    echo '<td>';
                                    echo $val['warehouse_name'] . '<br/>';
                                    echo '<span style="font-size:11px;">' . $val['shipped_date'] . '</span><br/>';
                                    echo $val['ship_name'] . '<br/>';
                                    if ($hvalue['paytime'] < '2018-05-20 00:00:00') {
                                        echo !empty($trackNumber) ? '<a href="https://t.17track.net/en#nums=' . $trackNumber . '" target="_blank" title="查看物流跟踪信息">' . $trackNumber . '</a>' : '-' . '<br/>';
                                    } else {
                                    echo !empty($trackNumber) ? '<a target="_blank" href="' . Url::toRoute(['/orders/order/gettracknumber', 'track_number' => $trackNumber]) . '" title="查看物流跟踪信息">' . $trackNumber . '</a>' : '-' . '<br/>';
                                        //echo '<a target="_blank" href="http://kefu.yibainetwork.com/orders/order/gettracknumber?track_number='.$hvalue['track_number'].'" title="查看物流跟踪信息">'.$hvalue['track_number'].'</a>';
                                    }
                                    echo '</td>';
                                }
                            } else {
                                echo '<td>暂无包裹信息</td>';
                            } ?>
                            <td>
                                <div class="btn-group btn-list">
                                    <button type="button"
                                            class="btn btn-default btn-sm"><?php echo \Yii::t('system', 'Operation'); ?></button>
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                            data-toggle="dropdown" aria-expanded="false">
                                        <span class="caret"></span>
                                        <span class="sr-only"><?php echo \Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                    </button>

                                    <ul class="dropdown-menu" rol="menu">
                                        <?php foreach ($hvalue['detail'] as $value) {
                                            $transaction_id[] = $value['transaction_id'];
                                        } ?>

                                        <!-------- 状态判断------>
                                        <!--新建售后单-->
                                        <?php //$order_re=stripos($hvalue['order_id'], 'RE'); ?>
                                        <?php //if ($hvalue['order_type'] != 7 || !empty($order_re)) { ?>
                                            <li>
                                                <a _width="100%" _height="100%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/order/add', 'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code'], 'from' => 'inbox']); ?>">新建售后单</a>
                                            </li>
                                        <?php //} ?>

                                        <!--取消订单 回评 登记退款单 登记收款单-->
                                        <?php if (!in_array($hvalue['order_type'], array(Order::ORDER_TYPE_MERGE_MAIN, Order::ORDER_TYPE_SPLIT_CHILD, Order::ORDER_TYPE_REDIRECT_ORDER))) { ?>
                                            <li><a _width="80%" _height="80%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/mails/ebayreply/initiativeadd', 'order_id' => $hvalue['order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_code')]); ?>">发送消息</a>
                                            </li>
                                            <li><a _width="30%" _height="60%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/canceltransaction',
                                                       'orderid'        => $hvalue['order_id'],
                                                       'platform'       => Yii::$app->request->getQueryParam('platform_code'), 'account_id' => $hvalue['account_id'],
                                                       'payment_status' => $hvalue['payment_status'],
                                                       'paytime'        => $hvalue['paytime'], 'platform_order_id' => $hvalue['platform_order_id'],
                                                       'transaction_id' => $transaction_id]) ?>">取消订单</a>
                                            </li>
                                            <li><a _width="80%" _height="80%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $hvalue['platform_order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_code')]); ?>">回评</a>
                                            </li>
                                            <li><a _width="100%" _height="100%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code']]); ?>">登记退款单</a>
                                            </li>
                                            <li><a _width="80%" _height="80%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/sales/ebayreceipt', 'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code'], 'buyer_id' => $hvalue['buyer_id'], 'account_id' => $hvalue['account_id']]); ?>">登记收款单</a>
                                            </li>

                                            <?php
                                            $invoiceInfo = PaypalInvoiceRecord::getIvoiceData($hvalue['order_id']);
                                            $transactionId = Tansaction::getOrderTransactionIdEbayByOrderId($hvalue['order_id'],$hvalue['platform_code']);
                                            ?>

                                            <li><a _width="80%" _height="80%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $hvalue['order_id'], 'platform_order_id' => $hvalue['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'],'platform' => $hvalue['platform_code']]); ?>">收款</a>
                                            </li>

                                        <?php }elseif(!empty($order_re)){  ?>
                                            <li><a _width="100%" _height="100%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code']]); ?>">登记退款单</a>
                                            </li>
                                       <?php  } ?>

                                        <!--永久作废  临时作废-->
                                        <?php if ($hvalue['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP || $hvalue['complete_status'] == 99 ||$hvalue['complete_status'] == 119) { ?>
                                            <li><a _width="30%" _height="60%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/cancelorder',
                                                       'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code']]); ?>">永久作废</a>
                                            </li>
                                            <li><a _width="30%" _height="60%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/holdorder',
                                                       'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code']]); ?>">暂时作废</a>
                                            </li>
                                        <?php } ?>


                                        <?php if ($hvalue['complete_status'] == Order::COMPLETE_STATUS_HOLD) { ?>
                                            <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/cancelholdorder',
                                                       'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code']]); ?>">取消暂时作废</a>
                                            </li>
                                            <li><a _width="30%" _height="60%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/cancelorder',
                                                       'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code']]); ?>">永久作废</a>
                                            </li>
                                        <?php } ?>

                                        <li>
                                            <a _width="50%" _height="80%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/orders/order/invoice', 'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code']]); ?>">发票</a>
                                        </li>
                                        <li>
                                            <a _width="100%" _height="100%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/aftersales/complaint/register', 'order_id' => $hvalue['order_id'], 'platform' => $hvalue['platform_code']]); ?>">登记客诉单</a>
                                        </li>

                                    </ul>
                                </div>
                            </td>
                        </tr>

                        <?php if (count($hvalue['detail'])) { ?>
                            <tr id="proDetail_<?php echo $hKey; ?>" class="panel-collapse collapse"
                                aria-expanded="true">
                                <td colspan="7">
                                    <table class="table table-hover" style="font-size:11px;background-color: #f5f5f5;">
                                        <thead>
                                        <tr style="font-size:9px;">
                                            <th>编号</th>
                                            <th>产品中名</th>
                                            <th>绑定SKU</th>
                                            <th>绑定的sku数量</th>
                                            <th>发货SKU</th>
                                            <th>发货的sku数量</th>
                                        </tr>
                                        <?php
                                        foreach ($hvalue['detail'] as $k => $pdetail) { ?>
                                            <tr>
                                                <td><?php echo $k + 1; ?></td>
                                                <td><?php echo $pdetail['titleCn']; ?></td>
                                                <td><a href="http://120.24.249.36/product/index/sku/<?php echo $pdetail['sku']; ?>"
                                                       style="color:blue" target='_blank'><?php echo $pdetail['sku_old']; ?></a></td>
                                                <td><?php echo $pdetail['quantity_old']; ?></td>
                                                <td><a href="http://120.24.249.36/product/index/sku/<?php echo $pdetail['sku']; ?>"
                                                       style="color:blue" target='_blank'><?php echo $pdetail['sku']; ?></a></td>
                                                <td><?php echo $pdetail['quantity']; ?></td>
                                            </tr>
                                        <?php }
                                        ?>
                                        </thead>
                                    </table>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (count($hvalue['remark'])) { ?>
                            <tr id="proRemark_<?php echo $hKey; ?>" class="panel-collapse collapse"
                                aria-expanded="true">
                                <td colspan="7">
                                    <table class="table table-hover" style="font-size:11px;background-color: #f5f5f5;">
                                        <thead>
                                        <tr style="font-size:9px;">
                                            <th>订单备注</th>
                                        </tr>
                                        <?php
                                        foreach ($hvalue['remark'] as $k => $pdetail) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($pdetail); ?></td>
                                            </tr>
                                        <?php }
                                        ?>
                                        </thead>
                                    </table>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <div id="page-wrapper-inbox" class="panel panel-primary col-md-6">
        <div class="panel-body">
            <div style="margin-bottom: 10px">
                <form class="bs-example bs-example-form" role="form">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="input-group">
                                <input type="text" class="form-control mail_template_title_search_text"
                                       placeholder="模板编号搜索">
                                <span class="input-group-btn">
                                    <button class="btn btn-default mail_template_title_search_btn"
                                            type="button">Go!</button>
                                </span>
                            </div><!-- /input-group -->
                        </div><!-- /.col-lg-6 -->
                        <div class="col-lg-6">
                            <div class="input-group">
                                <input type="text" class="form-control mail_template_search_text" placeholder="消息模板搜索">
                                <span class="input-group-btn">
                                    <button class="btn btn-default mail_template_search_btn" type="button">搜索</button>
                                </span>
                            </div><!-- /input-group -->
                        </div>
                    </div><!-- /.row -->
                </form>
            </div>
            <div class="panel panel-default">
                <div class="mail_template_area panel-body">
                    <?php
                    $mailTemplates = MailTemplate::getMailTemplateDataAsArrayByUserId(Platform::PLATFORM_CODE_EB);
                    foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName) {
                        echo "<a class='mail_template_unity' value='$mailTemplatesId'>$mailTemplateName</a> ";
                    }
                    ?>
                </div>
            </div>
            <script src="<?php echo yii\helpers\Url::base(true); ?>/js/jquery.form.js"></script>
            <form role="form">
                <div class="form-group">
                    <?php echo Html::hiddenInput('inbox_id', $new_inbox_id, ['id' => 'inbox_id']); ?>
                    <?php echo Html::hiddenInput('reply_id', empty($replyModel->id) ? '' : $replyModel->id, ['id' => 'reply_id']); ?>
                    <?php echo Html::hiddenInput('sl_code', "", ['id' => 'sl_code']); ?>
                    <?php echo Html::hiddenInput('tl_code', "", ['id' => 'tl_code']); ?>
                    <!-- <label for="name"><?php echo $replyModel->attributeLabels()['reply_content'] ?></label> -->
                    附件:
                    <button class="ebay_reply_upload_image" type="button">上传图片</button> &nbsp;&nbsp;&nbsp;&nbsp;
                    <button type="button" class="btn btn-sm btn-success" id="return_info">获取退货信息</button>
                    <!--在鼠标移动位置插入参数-->
                    <div class="form_data" style="float: right;font-size: 12px;margin-right: 530px;">
                        <?php
                        $track_number   = '';
                        $track          = '';
                        $buyer_id       = '';
                        $ship_name      = '';
                        $payer_email    = '';
                        $receiver_email = '';
                        $transaction_id = '';
                        $email          = '';
                        $paytime        = '';
                        $item_id        = '';
                        if ($Historica) {
                            $countryList = Country::getCodeNamePairsList('en_name');
                            $order_info  = $Historica[0];
                            if ($order_info) {
                                $detail       = $order_info['detail'][0];
                                $track_number = $order_info['track_number'] ? $order_info['track_number'] : '';
                                $track        = $order_info['track_number'] ? 'http://www.17track.net/zh-cn/track?nums=' . $order_info['track_number'] : '';
                                $buyer_id     = $order_info['ship_name'] ? $order_info['ship_name'] : '';
                                $ship_name    = $order_info['ship_name'] ? $order_info['ship_name'] : '';
                                $ship_name    .= "(tel:" . $order_info['ship_phone'] . ")";
                                $ship_name    .= $order_info['ship_street1'] . ',' . ($order_info['ship_street2'] == '' ? '' : $order_info['ship_street2'] . ',') . $order_info['ship_city_name'] . ',';
                                $ship_name    .= $order_info['ship_stateorprovince'] . ',';
                                $ship_name    .= $order_info['ship_zip'] . ',';
                                $ship_name    .= $order_info['ship_country_name'];
                                $email        = $order_info['email'] ? $order_info['email'] : '';
                                $paytime      = $order_info['paytime'] ? $order_info['paytime'] : '';
                                $item_id      = $detail['item_id'] ? $detail['item_id'] : '';

                                //如果在erp没获取到交易信息  则在客服系统重新获取一遍
                                //获取所以paypal账号信息
                                $paypal         = PaypalAccount::getPaypleEmail();
                                $receiver_email = '';
                                $payer_email    = '';
                                $transaction_id = '';
                                if (!empty($order_info['trade'])) {
                                    foreach ($order_info['trade'] as $key => $value) {
                                        $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->andwhere(['in', 'payer_email', $paypal])->asArray()->one();
                                        if (!empty($transactionRecord)) {
                                            $transaction_id = $transactionRecord['transaction_id'];
                                            $receiver_email = $transactionRecord['receiver_email'];
                                            $payer_email    = $transactionRecord['payer_email'];
                                        } else {
                                            $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->asArray()->one();
                                            if (!empty($transactionRecord)) {
                                                $receiver_email = $transactionRecord['receiver_email'];
                                                $payer_email    = $transactionRecord['payer_email'];
                                            }
                                        }

                                    }
                                }
                            }

                        }
                        ?>
                        <select id="countDataType" class="form-control"
                                style="width:100%;height:30px;padding: 2px 5px;">
                            <option value="all">选择绑定参数</option>
                            <option value="<?php echo $track_number; ?>">跟踪号</option>
                            <option value="<?php echo $track; ?>">查询网址</option>
                            <option value="<?php echo $buyer_id; ?>">客户姓名</option>
                            <option value="<?php echo $ship_name; ?>">客户地址</option>
                            <option value="<?php echo $payer_email; ?>">付款账号</option>
                            <option value="<?php echo $receiver_email; ?>">收款账号</option>
                            <option value="<?php echo $transaction_id; ?>">退款交易号</option>
                            <option value="<?php echo $email; ?>">客户email</option>
                            <option value="<?php echo $paytime; ?>">付款时间</option>
                            <option value="<?php echo $item_id; ?>">item ID</option>
                        </select>
                    </div>
                    <textarea id="reply_content_en" class="form-control" rows="3" placeholder="输入回复内容(注意: 此输回复内容为英语)"
                              style="width:100%;height: 180px;margin-top:15px;"><?php echo $replyModel->is_draft ? $replyModel->reply_content : ''; ?></textarea>

                    <div class="row" style="text-align: center;font-size: 13px;font-weight: bold;margin-top: 20px;">
                        <div class="col-sm-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default" type="button" onclick="changeCode(3,'en','',$(this))">
                                    英语
                                </button>
                                <button class="btn btn-default" type="button" onclick="changeCode(3,'fr','',$(this))">
                                    法语
                                </button>
                                <button class="btn btn-default" type="button" onclick="changeCode(3,'de','',$(this))">
                                    德语
                                </button>
                                <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                    <div class="btn-group">
                                        <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle"
                                                type="button" aria-expanded="false" id="sl_btn">更多&nbsp;&nbsp;<span
                                                    class="caret"></span></button>
                                        <ul class="dropdown-menu language">
                                            <?php foreach ($googleLangCode as $key => $value) { ?>
                                                <li>
                                                    <a onclick="changeCode(1,'<?php echo $key; ?>','<?php echo $value; ?>',$(this))"><?php echo $value; ?></a>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="fa-hover col-sm-1" style="width:0px;line-height: 30px;"><a><i
                                        class="fa fa-exchange"></i></a></div>
                        <div class="col-sm-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default" type="button" onclick="changeCode(4,'en','',$(this))">
                                    英语
                                </button>
                                <button class="btn btn-default" type="button" onclick="changeCode(4,'fr','',$(this))">
                                    法语
                                </button>
                                <button class="btn btn-default" type="button" onclick="changeCode(4,'de','',$(this))">
                                    德语
                                </button>
                                <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                    <div class="btn-group">
                                        <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle"
                                                type="button" aria-expanded="false" data="" id="tl_btn">
                                            更多&nbsp;&nbsp;<span class="caret"></span></button>
                                        <ul class="dropdown-menu language">
                                            <?php foreach ($googleLangCode as $key => $value) { ?>
                                                <li>
                                                    <a onclick="changeCode(2,'<?php echo $key; ?>','<?php echo $value; ?>',$(this))"><?php echo $value; ?></a>
                                                </li>
                                            <?php } ?>
                                            </li>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col-sm-1">
                            <button class="btn btn-sm btn-primary artificialTranslation" type="button"
                                    id="translations_btn">翻译 [ <b id="sl_name"></b> - <b id="tl_name"></b> ]
                            </button>
                        </div>
                    </div>


                    <textarea id="reply_content" class="form-control" rows="3" placeholder="发送给客户的内容"
                              style="display: block; width:100%;height: 180px;margin-top:15px;"><?php echo $replyModel->is_draft ? $replyModel->reply_content_en : ''; ?></textarea>


                    <div class="ebay_reply_upload_image_display_area">
                        <?php
                        if (!empty($replyModel->id)) {
                            $replyPictureModel = \app\modules\mails\models\EbayReplyPicture::find()->where(['reply_table_id' => $replyModel->id])->all();
                            if (!empty($replyPictureModel)) {
                                foreach ($replyPictureModel as $replyPictureModelV)
                                    echo '<div class="ebay_reply_upload_image_display"><img style="height:50px;width:50px;" src="', $replyPictureModelV->picture_url, '" ><a class="ebay_reply_upload_image_delete">删除</a></div>';
                            }
                        }
                        ?>
                    </div>

                    <script type="application/javascript">
                        $(function () {
                            //上传图片
                            $('.ebay_reply_upload_image').click(function () {
                                layer.open({
                                    area: ['500px', '200px'],
                                    type: 1,
                                    title: '上传图片',
                                    content: '<form style="padding:10px 0px 0px 20px" action="<?php echo Url::toRoute('/mails/ebayreply/uploadimage')?>" method="post" id="ebay_pop_upload_image" enctype="multipart/form-data"><input type="file" name="ebay_reply_upload_image"/><p style="color:red">支持图片格式：gif、jpg、png、jpeg、tif、bmp。</p></form>',
                                    btn: '上传',
                                    yes: function (index, layero) {
                                        layero.find('#ebay_pop_upload_image').ajaxSubmit({
                                            dataType: 'json',
                                            beforeSubmit: function (options) {
                                                if (!/(gif|jpg|png|jpeg|tif|bmp)/ig.test(options[0].value.type)) {
                                                    layer.msg('图片格式错误！', {
                                                        icon: 2,
                                                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                    });
                                                    return false;
                                                }
                                            },
                                            success: function (response) {
                                                switch (response.status) {
                                                    case 'error':
                                                        layer.msg(response.info, {
                                                            icon: 2,
                                                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                        });
                                                        break;
                                                    case 'success':
                                                        $('.ebay_reply_upload_image_display_area').append('<div class="ebay_reply_upload_image_display"><img style="height:50px;width:50px;" src="' + response.url + '" ><a class="ebay_reply_upload_image_delete">删除</a></div>');
                                                        layer.close(index);
                                                }
                                            },
                                        });
                                    }
                                });
                            });
                            //删除图片
                            $('.ebay_reply_upload_image_display_area').delegate('.ebay_reply_upload_image_delete', 'click', function () {
                                if (window.confirm('确定要删除？')) {
                                    var $this = $(this);
                                    var delteImageUrl = $this.siblings('img').attr('src');
                                    $.post('<?php echo Url::toRoute('/mails/ebayreply/deleteimage')?>', {'url': delteImageUrl}, function (response) {
                                        switch (response.status) {
                                            case 'error':
                                                layer.msg(response.info, {icon: 2, time: 2000});
                                                break;
                                            case 'success':
                                                layer.msg('删除成功', {icon: 1, time: 2000});
                                                $this.parent().remove();
                                        }
                                    }, 'json');
                                }
                            });
                        })
                    </script>
                </div>
            </form>
        </div>
        <div class="col-md-12 panel-body">
            <button type="button" reply_type="draft" class="reply_mail_save btn btn-sm btn-default">存草稿</button>
            <button type="button" reply_type="replied" class="reply_mail_mark btn-sm btn btn-default">标记已回复</button>
            <button type="button" reply_type="last" class="reply_mail_mark btn btn-sm btn-default">上一个</button>
            <button type="button" reply_type="next" class="reply_mail_mark btn btn-sm btn-default">下一个</button>
            <?= Html::a('新增标签', Url::toRoute(['/mails/ebayinboxsubject/addtags', 'ids' => $currentModel->id, 'type' => 'detail']), ['class' => 'btn btn-sm btn-primary add-tags-button-button']) ?>

            <?= Html::a('移除标签', Url::toRoute(['/mails/ebayinboxsubject/removetags', 'id' => $currentModel->id, 'type' => 'detail']), ['class' => 'btn btn-sm btn-danger add-tags-button-button']) ?>
            <button type="button" reply_type="reply" class="reply_mail_save btn btn-sm btn-success"
                    style="float:right;">回复消息
            </button>

        </div>
        <div class="clear"></div>
    </div>

    <div style="display: none">
        <form id="ebay_inbox_detail_jump" action="" method="post">
            <input id="ebay_inbox_exclude" name="exclude" value=""/>
        </form>
    </div>
</div>

<script type="text/javascript">
    var $new_inbox_id = '<?= $new_inbox_id?>'//$('#inbox_id').val();
    // 获取url参数
    function GetQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]);
        return null;
    }

    function removetags(obj) {
        var _id = GetQueryString('id');
        var tag_id = $(obj).siblings('span').attr('use_data');
        $.post('<?= Url::toRoute(['/mails/ebayinboxsubject/removetags', 'id' => $currentModel->id, 'type' => 'detail'])?>', {
            'MailTag[inbox_id]': _id,
            'MailTag[tag_id][]': tag_id,
            'MailTag[type]': 'detail'
        }, function (data) {
            if (data.url && data.code == "200")
                $("#tags_value" + tag_id).hide(50);
        }, 'json');

    }

    //实例化编辑器
    $(function () {
        $draft = "<?php echo !isset($draftModel->reply_content) ? "" : $draftModel->reply_content?>";
        var ue = UE.getEditor('editor');
        ue.addListener('ready', function () {
            ue.setContent($draft);
        })
    })

    //鼠标定位添加订单信息
    $("#countDataType").on("change", function () {
        var data_value = $(this).val();
        if (data_value == '') {
            alert('暂无此数据');
        }
        if (data_value != 'all') {
            getValue('reply_content_en', data_value);
        }
    })

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


    //图片处理
    var docHeight = $(document).height(); //获取窗口高度
    var orgial = new Array();
    orgial.push("<?php echo !empty($orgialImage) ? $orgialImage : ''?>");
    var org = orgial[0].split(',');
    var j = new Array();
    for (var i = 0; i < org.length; i++) {

        var o = org[i].lastIndexOf("\/");
        j.push(org[i].substring(0, o));
    }
    $('.imagePreviewHtml').each(function () {
        $(this).find('.fourColumnsTd').click(function () {
            var thumb = $(this).find('img').attr('src');
            var index = thumb.lastIndexOf("\/");
            thumb = thumb.substring(0, index);
            var result = $.inArray(thumb, j);

            if (result > -1) {
                var large_image = '<img src= ' + org[result] + '></img>';
                window.open(org[result], "", "toolbar=no,scrollbars=no,menubar=no");    // 打开一个新的窗口，在新的窗口显示图片的本来大小
            } else {
                alert('原图片资源不存在')
            }
        })
    })

    var keyboards = '<?php echo $keyboards; ?>'
    keyboards = JSON.parse(keyboards);
    var ids = '<?php echo $currentModel->id; ?>'
    var tag_id = '';
    $(document).ready(
        function () {
            document.onkeyup = function (e) {
                var event = window.event || e;
                if (event.shiftKey && keyboards['shift'] != undefined && keyboards['shift'][event.keyCode] != undefined) {
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
                                $("#tags_value" + tags_id).hide(50);
                            }
                        }, 'json');
                    }
                }
                if (event.ctrlKey && keyboards['ctrl'] != undefined && keyboards['ctrl'][event.keyCode] != undefined) {
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
                                $("#tags_value" + tags_id).hide(50);
                            }
                        }, 'json');
                    }
                }
                if (event.altKey && keyboards['alt'] != undefined && keyboards['alt'][event.keyCode] != undefined) {
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
                                $("#tags_value" + tags_id).hide(50);
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
    $(".translation").click(function () {
        var sl = 'en';//自己填的默认是英语
        var tl = $(this).attr('data');
        var content = $.trim($("#reply_content_en").val());
        if (content.length == 0) {
            layer.msg('请输入需要翻译的内容!');
            return false;
        }

        if (sl == "") {
            layer.msg('系统未识别到客户翻译的语言!');
            $("#myModal").show();
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['translate']);?>',
            data: {'sl': sl, 'tl': tl, 'content': content},
            success: function (data) {
                if (data) {
                    $("#reply_content").val(data);
                    $("#reply_content").css('display', 'block');
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
     * 绑定翻译按钮 进行手动翻译操作
     * @author allen <2018-1-5>
     **/
    function clikTrans(that, k) {
        var sl = 'auto';
        var tl = 'en';

        var message = $("#message_" + k).html();
        var tag = $(this).attr('data1');
        if (message.length == 0) {
            layer.msg('获取需要翻译的内容有错!');
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['translate']);?>',
            data: {'sl': sl, 'tl': tl, 'returnLang': 1, 'content': message},
            success: function (data) {
                if (data) {
                    $("#sl_code").val('en');
                    $("#sl_name").html('英语');
                    $("#tl_code").val(data.googleCode);
                    $("#tl_name").html(data.code);
                    $("#trans_message" + k).html('<br/><br/><b style="color:green;">' + data.text + '</b>');
                    that.remove();
                }
            }
        });
    }


    $('.artificialTranslation').click(function () {
        var sl = $("#sl_code").val();
        var tl = $("#tl_code").val();
        var content = $.trim($("#reply_content_en").val());
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
            url: '<?php echo Url::toRoute(['translate']);?>',
            data: {'sl': sl, 'tl': tl, 'content': content},
            success: function (data) {
                if (data) {
                    $("#reply_content").val(data);
                    $("#reply_content").css('display', 'block');
                }
            }
        });
    });

    /**
     * 添加 或者修改站内信备注功能
     * @author allen <2018-02-10>
     */
    $(document).on('click', '.remark', function () {
        var id = $(this).attr('data');
        var remark = $(this).attr('data1');//默认备注
        if (remark == '') {
            remark = $(this).text();
        }
        layer.prompt({title: '站内信备注', value: remark, formType: 2}, function (text, index) {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['operationremark']); ?>',
                data: {'id': id, 'remark': text},
                success: function (data) {
                    if (data.status) {
                        layer.msg(data.info, {icon: 1});
                        var htm = '<li class="tag label btn-info md ion-close-circled"><span style="cursor: pointer;" class="remark" data="' + id + '" data1="">' + text + '</span>&nbsp;&nbsp;<a href="javascript:void(0)" class="removetags" data="' + id + '">x</a></li>';
                        $("#remark_" + id).html(htm);
                    } else {
                        layer.msg(data.info, {icon: 5});
                    }
                }
            });
            layer.close(index);
        });
    });

    /**
     * 删除站内信备注功能
     * @author allen <2018-02-10>
     */

    $(document).on('click', '.removetags', function () {
        var id = $(this).attr('data');
        layer.confirm('您确定要删除么？', {
            btn: ['确定', '再考虑一下'] //按钮
        }, function () {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['operationremark']); ?>',
                data: {'id': id, 'remark': ''},
                success: function (data) {
                    if (data.status) {
                        layer.msg(data.info, {icon: 1});
                        var htm = '<i class="fa remark fa-pencil remark" style="cursor: pointer;" data="' + id + '" data1=""></i>';
                        $("#remark_" + id).html(htm);
                    } else {
                        layer.msg(data.info, {icon: 5});
                    }
                }
            });
        }, function () {

        });
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
                                var old_content = $('#reply_content').val();
                                if (old_content !== '') {
                                    $('#amz-reply').val(html + '\n' + old_content);
                                } else {
                                    $('#amz-reply').val(html + '\n' + old_content);
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
                            var old_content = $('#reply_content_en').val();
                            if (old_content !== '') {
                                $('#reply_content_en').val(html + '\n' + old_content);
                            } else {
                                $('#reply_content_en').val(html + '\n' + old_content);
                            }
                            $(this).attr('disabled', true);
                    }

                }
            });

        }
    });

    $('.have_remark').on('click', function () {
        var remark = $(this).data('remark');
        var remark_ = '';
        for (var i = 0; i < remark.length; i++) {
            remark_ += remark[i] + '<br>';
        }
        layer.tips(remark_, '.have_remark', {
            tips: [2, '#337ab7'], //设置tips方向和颜色 类型：Number/Array，默认：2 tips层的私有参数。支持上右下左四个方向，通过1-4进行方向设定。如tips: 3则表示在元素的下面出现。有时你还可能会定义一些颜色，可以设定tips: [1, '#c00']
            tipsMore: false, //是否允许多个tips 类型：Boolean，默认：false 允许多个意味着不会销毁之前的tips层。通过tipsMore: true开启
            time: 5000  //2秒后销毁
        });
    });

</script>