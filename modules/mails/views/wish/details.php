<?php

use yii\helpers\Url;
use yii\helpers\Html;
use app\modules\mails\models\MailTemplate;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\orders\models\Order;
use app\modules\mails\models\WishInbox;
use app\modules\accounts\models\Account;
?>
<style>
    li {
        list-style: none;
    }

    .mb10 {
        margin-bottom: 10px;
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

    .table01 {
        border: 1px solid #e1e1e1;
        width: 100%;
        height: 170px;
        text-align: center;
    }

    .table02 {
        border: 1px solid #e1e1e1;
        width: 100%;
        height: 50px;
        text-align: center;
    }

    .td1 {
        color: #0f0f0f;
    }

    .panel-info {
        border-color: snow;
    }

    .language {
        width: 720px;
        float: left;
        height: auto;
        max-height: 250px;
        overflow-y: scroll;
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

    .panel-buyer {
        width: 70%;
        float: left;
        border: 1px solid #337ab7;
        clear: both;
        box-shadow: 6px 6px 6px #999;
    }

    .panel-buyer > .panel-heading {
        background-color: #337ab7;
        border-bottom: 1px solid #337ab7;
        color: #fff;
    }

    .panel-seller {
        width: 70%;
        float: right;
        border: 1px solid #ddd;
        clear: both;
        box-shadow: 6px 6px 6px #999;
    }

    .panel-seller > .panel-heading {
        background-color: #f5f5f5;
        border-bottom: 1px solid #ddd;
        color: #000;
    }

    .wish_reply_upload_image_display {
        float: left;
    }
</style>

<div id="page-wrapper-inbox">
    <p>
        <a href='/mails/wish/index' style='text-decoration:none;'>
            <button type="button" class="btn btn-primary btn-lg btn-block">返回列表</button>
        </a>
    </p>
    <div class="panel panel-success" style="width: 59%;float: left;height:700px;overflow-y:scroll; overflow-x:scroll;">
        <div class="panel-heading">
            <h3 class="panel-title">
                <ul class="list-inline" id="ulul">
                    <?php
                    if (!empty($tags_data)) {
                        foreach ($tags_data as $key => $value) {
                            ?>
                            <li style="margin-right: 20px;" class="btn btn-default" id="tags_value<?php echo $key; ?>">
                                <span use_data="<?php echo $key; ?>"><?php echo $value; ?></span>&nbsp;<a
                                    class="btn btn-warning" href="javascript:void(0)"
                                    onclick="removetags(this);">x</a></li>
                                <?php
                            }
                        }
                        ?>
                </ul>
            </h3>
        </div>
        <div class="panel-body">
            <div class="panel panel-info" style="float:left;width: 330px;height: 220px;margin-right: 20px;">
                <div class="panel-heading" style="height:50px">
                    <h3 class="panel-title">Ticket</h3>
                </div>
                <div style="width:100%;height:170px">
                    <table class="table01" border="1">
                        <tr>
                            <td class="td1">站内信编号</td>
                            <td><?php echo $model->platform_id ? $model->platform_id : '无'; ?></td>
                        </tr>
                        <tr>
                            <td class="td1">创建日期</td>
                            <td><?php echo $model->create_time ? $model->create_time : '无'; ?></td>
                        </tr>
                        <tr>
                            <td class="td1">更新时间</td>
                            <td><?php
                        if ($info->last_updated) {
                            $last_updated = date('Y-m-d H:i:s', strtotime($info->last_updated) + 8 * 3600); //修改utc时间
                            echo $last_updated;
                        } else {
                            echo "无";
                        }
                        ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="td1">状态</td>
                            <td><?php echo WishInbox::$status[$model->status]; ?></td>
                        </tr>
                        <tr>
                            <td class="td1">标签</td>
                            <td><?php echo $model->label ? $model->label : '无'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="panel panel-info" style="float:left; width: 330px;height: 220px; margin-right: 20px;">
                <div class="panel-heading" style="height:50px">
                    <h3 class="panel-title">交易</h3>
                </div>
                <div style="width:100%;height:170px">
                    <table class="table01" border="1">
                        <tr>
                            <td class="td1">交易ID</td>
                            <td><?php echo $info->transaction_id ? $info->transaction_id : '无'; ?></td>
                        </tr>
                        <tr>
                            <td class="td1">付款时间</td>
                            <td><?php echo $orderinfo['info']['paytime'] ? $orderinfo['info']['paytime'] : '无'; ?></td>
                        </tr>
                        <tr>
                            <td class="td1">已付款</td>
                            <td><?php
                                $time = time() - strtotime($orderinfo['info']['paytime']);
                                if ($time > 0 && $orderinfo['info']['paytime']) {
                                    if ($time >= 86400) {
                                        $days = floor($time / 86400);
                                        $secs = $time % 86400;
                                        $result = $days . ' 天';
                                        if ($secs > 0) {
                                            $result .= ' ';
                                        }
                                    }
                                    if ($secs >= 3600) {
                                        $hours = floor($secs / 3600);
                                        $secs = $time % 3600;
                                        $result .= $hours . ' 小时';
                                        if ($secs > 0) {
                                            $result .= ' ';
                                        }
                                    }
                                }
                                echo "<span>" . $result . "</span>";
                                ;
                        ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="panel panel-info" style="float:left;width: 330px;height: 220px;">
                <div class="panel-heading" style="height:50px">
                    <h3 class="panel-title">地址</h3>
                </div>
                <div style="width:100%;height:170px">
                    <table class="table01" border="1">
                        <tr>
                            <td>收货人姓名</td>
                            <td><?php echo $info->receiver_name ? $info->receiver_name : '无' ?></td>
                        </tr>
                        <tr>
                            <td>国家/城市</td>
                            <td><?php echo $info->city ? $info->city : '无'; ?></td>
                        </tr>
                        <tr>
                            <td>详细地址</td>
                            <td><?php echo $info->street_address1 ? $info->street_address1 : '无'; ?></td>
                        </tr>
                        <tr>
                            <td>邮编</td>
                            <td><?php echo $info->zipcode ? $info->zipcode : '无'; ?></td>
                        </tr>
                        <tr>
                            <td>电话号码</td>
                            <td><?php echo $info->phone_number ? $info->phone_number : '无'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="panel panel-info" style="float:left;width: 1030px;height: 100px;">
                <div class="panel-heading" style="height:50px">
                    <h3 class="panel-title">退款</h3>
                </div>
                <div style="width:100%;height:50px">
                    <table class="table02" border="1">
                        <?php
                        if (empty($salesrefund)) {
                            echo '无退款记录!';
                        } else {
                            ?>
                            <tr>
                                <td>日期</td>
                                <td>数量</td>
                                <td>成本</td>
                                <td>退款理由</td>
                                <td>退款方</td>
                            </tr>
                            <tr>
                                <td><?php echo $salesrefund[0]['refund_time'] ? $salesrefund[0]['refund_time'] : '无'; ?></td>
                                <td><?php echo count($salesrefund) > 0 ? count($salesrefund) : '无'; ?></td>
                                <td>
                                    <?php
                                    $profit = $orderinfo['profit'];
                                    echo $profit['purchase_cost'] ? $profit['purchase_cost'] : '无';
                                    ?>(<?php echo $profit['currency'] ? $profit['currency'] : '无'; ?>)
                                </td>
                                <td><?php echo $salesrefund[0]['reason_code'] ? $salesrefund[0]['reason_code'] : '无'; ?></td>
                                <td>WISH</td>
                            <?php }; ?>
                        </tr>
                    </table>
                </div>
            </div>

            <div id="replyList">
                <?php $rep_key = 1; ?>
                <?php foreach ($replyList as $value) { ?>
                    <?php
                    $type = '';
                    if ($value['type'] == 'wish support') {
                        $type = 'panel-buyer';
                    } else if ($value['type'] == 'user') {
                        $type = 'panel-buyer';
                    } else {
                        $type = 'panel-seller';
                    }
                    ?>
                    <div class="panel <?php echo $type; ?>">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <p>
                                    <?php
                                    if ($value['type'] == 'user') {
                                        echo '发送人：', $model->user_name;
                                        echo '<i class="glyphicon glyphicon-arrow-right"></i>';
                                    } else if ($value['type'] == 'wish support') {
                                        echo '发送人：', $value['type'];
                                        echo '<i class="glyphicon glyphicon-arrow-right"></i>';
                                    } else {
                                        echo '<i class="glyphicon glyphicon-arrow-left"></i>';
                                        echo '回复人：', $value['reply_by'] ? $value['reply_by'] : $value['type'];
                                    }
                                    ?>
                                </p>
                                <p>
                                    发送时间：
                                    <?php
                                    if ($value['message_time']) {
                                        $message_time = date('Y-m-d H:i:s', strtotime($value['message_time']) + 8 * 3600);
                                        echo $message_time;
                                    } else {
                                        echo $value['create_time'];
                                    }
                                    ?>
                                </p>
                            </h3>
                        </div>
                        <div class="panel-body">
                            <p class="pcontent_<?php echo $rep_key; ?>">
                                <span id="text_<?php echo $rep_key; ?>" data-content="<?php echo!empty($value['reply_content']) ? rawurlencode(strip_tags(trim($value['reply_content']))) : ''; ?>">
                                    <?php echo $value['reply_content'] ? nl2br($value['reply_content']) : ''; ?>
                                    <?php echo $value['message_translated'] ? $value['message_translated'] : '' ?>
                                    <?php echo ['message_zh'] ? $value['message_zh'] : '' ?>
                                </span>
                                <?php if (!empty($value['type']) && $value['type'] != 'merchant') { ?>
                                    <a style="cursor:pointer;" data1="<?php echo $rep_key; ?>" class="transClik">&nbsp;&nbsp;点击翻译</a>
                                <?php } ?>
                            </p>
                            <div>
                                <?php
                                if ($value['image_urls']) {
                                    $img_url = explode(',', $value['image_urls']);
                                    foreach ($img_url as $v) {
                                        echo '<a href="' . $v . '" target="_blank"><img style="width:50px;" src="' . $v . '"></a>&nbsp &nbsp';
                                    }
                                }
                                if ($value['image_url_merchant']) {
                                    $img_url_merchant = explode(',', $value['image_url_merchant']);
                                    foreach ($img_url_merchant as $v) {
                                        echo '<a href="' . $v . '" target="_blank"><img style="width:50px;" src="' . $v . '"></a>&nbsp &nbsp';
                                    }
                                }
                                $rep_key++;
                                ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <div style="margin-left:10px;width:39%;float:left;">
        <div class="panel panel-success">
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
                    <div class="panel-body mail_template_area">
                        <ul class="list-inline">
                            <?php
                            $mailTemplates = MailTemplate::getMailTemplateDataAsArray(Platform::PLATFORM_CODE_WISH);
                            foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName) {
                                echo "<li><a href='#' class='mail_template_unity' value='$mailTemplatesId'>$mailTemplateName</a></li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                <script src="<?php echo yii\helpers\Url::base(true); ?>/js/jquery.form.js"></script>
                <div class="row mb10">
                    <div class="col-sm-12">
                        <div class="btn-group">
                            <button class="btn btn-default" id="Reply">回复消息</button>
                            <button class="btn btn-default" value="1" onclick="markerReply(this.value);">标记成已处理</button>
                            <button class="btn btn-default" id="Close">关闭客户问题</button>
                            <button class="btn btn-default" id="Assist">Wish支持</button>
                            <?= Html::a('新增标签', Url::toRoute(['/mails/wish/addtags', 'ids' => $model->id, 'type' => 'detail']), ['class' => 'btn btn btn-primary add-tags-button-button']) ?>
                            <?= Html::a('移除标签', Url::toRoute(['/mails/wish/removetags', 'id' => $model->id, 'type' => 'detail']), ['class' => 'btn btn-danger add-tags-button-button']) ?>
                            <button class="btn btn-success" id="return_info">获取退货信息</button>
                        </div>
                        <input type="hidden" id="order_id" value="<?php echo $info->order_id; ?>"/>
                        <input type="hidden" id="account_id" value="<?php echo $model->account_id; ?>"/>
                        <input type="hidden" id="platform_id" value="<?php echo $model->platform_id; ?>"/>
                        <input type="hidden" id="info_id" value="<?php echo $model->info_id; ?>"/>
                        <input type="hidden" id="id" value="<?php echo $id; ?>"/>
                    </div>
                </div>
                <div class="row mb10">
                    <div class="col-sm-12">
                        <label class="control-label col-sm-3" style="text-align: right;padding-top: 3px;width:10%;margin-left: -25px;">附件</label>
                        <button class="wish_reply_upload_image" type="button">上传图片</button>
                    </div>
                </div>
                <div class="row mb10">
                    <div class="col-sm-12">
                        <div class="wish_reply_upload_image_display_area"></div>
                    </div>
                </div>
                <form>
                    <?php echo Html::hiddenInput('sl_code', "", ['id' => 'sl_code']); ?>
                    <?php echo Html::hiddenInput('tl_code', "", ['id' => 'tl_code']); ?>
                    <div class="form-group">
                        <div class="row mb10">
                            <div class="col-sm-12">
                                <textarea class="form-control" rows="10" placeholder="翻译前内容(英语)" id="reply_content"></textarea>
                            </div>
                        </div>
                        <div class="row mb10">
                            <div class="col-sm-12">
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
                                                    class="btn btn-default btn-sm dropdown-toggle" type="button"
                                                    aria-expanded="false" id="sl_btn">更多&nbsp;&nbsp;<span
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

                                <a><i class="fa fa-exchange"></i></a>

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
                                                    class="btn btn-default btn-sm dropdown-toggle" type="button"
                                                    aria-expanded="false" data="" id="tl_btn">更多&nbsp;&nbsp;<span
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

                                <button class="btn btn-sm btn-primary artificialTranslation" type="button" id="translations_btn">
                                    翻译 [ <b id="sl_name"></b> - <b id="tl_name"></b> ]
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <textarea class="form-control" rows="10" placeholder="翻译后内容(如果有翻译则发送给客户的内容)" id="reply_content_en"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6" style="width: 60%;float: left;">
        <p style="color: #FFFFFF"><strong></strong></p>
        <ul class="nav nav-tabs">
            <li class="active"><a data-toggle="tab" href="#menu1">订单相关信息</a></li>
            <li><a data-toggle="tab" href="#menu2">产品相关信息</a></li>
        </ul>

        <div class="tab-content">
            <div id="menu1" class="tab-pane fade in active">
                <table class="table">
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
                    <tbody id="basic_info">
                        <?php
                        if (!empty($Historica)) {
                            ?>
                            <?php foreach ($Historica as $hKey => $order) { ?>
                                <?php
                                $warehouseList = \app\modules\orders\models\Warehouse::getWarehouseListAll();
                                if ($hKey == 0) {
                                    //获取仓库id 第一个订单id
                                    $order_id = isset($order['orderPackage'][0]['order_id']) ? $order['orderPackage'][0]['order_id'] : '';
                                    $warehouse_id = isset($hvalue['orderPackage'][0]['warehouse_id']) ? $hvalue['orderPackage'][0]['warehouse_id'] : 0;
                                    $current_order_warehouse_name = array_key_exists($warehouse_id, $warehouseList) ?
                                            $warehouseList[$warehouse_id] : '';
                                    echo "<input type='hidden' name='current_order_warehouse_id' value='$warehouse_id'>" .
                                    "<input type='hidden' name='current_order_id' value='$order_id'>" .
                                    "<input type='hidden' name='current_order_warehouse_name' value='$current_order_warehouse_name'>";
                                }
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

                                switch ($order['comment_type']) {
                                    case 1:
                                        $comment_type = '<span>IndependentlyWithdrawn</span>';
                                        break;
                                    case 2:
                                        $comment_type = '<span"><a _width="100%" _height="100%" style="color:red;" href="' . Url::toRoute(['/mails/ebayfeedbackresponse/add', 'type' => 'Reply', 'id' => $order['feed_id']]) . '" class="edit-button" id="status">Negative</a></span>';
                                        break;
                                    case 3:
                                        $comment_type = '<span"><a _width="100%" _height="100%"style="color:orange;" href="' . Url::toRoute(['/mails/ebayfeedbackresponse/add', 'type' => 'Reply', 'id' => $order['feed_id']]) . '" class="edit-button" id="status">Neutral</a></span>';
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

                                switch ($order['order_type']) {
                                    case Order::ORDER_TYPE_MERGE_MAIN:
                                        $rela_order_name = '合并前子订单';
                                        $rela_is_arr = true;
                                        break;
                                    case Order::ORDER_TYPE_SPLIT_MAIN:
                                        $rela_order_name = '拆分后子订单';
                                        $rela_is_arr = true;
                                        break;
                                    case Order::ORDER_TYPE_MERGE_RES:
                                        $rela_order_name = '合并后父订单';
                                        $rela_is_arr = false;
                                        break;
                                    case Order::ORDER_TYPE_SPLIT_CHILD:
                                        $rela_order_name = '拆分前父订单';
                                        $rela_is_arr = false;
                                        break;
                                    default:
                                        $rela_order_name = '';
                                }

                                $order_result = '';
                                if (!empty($rela_order_name)) {
                                    if ($rela_is_arr) {
                                        foreach ($order['son_order_id'] as $son_order_id) {
                                            $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=WISH&system_order_id=' . $son_order_id . '" title="订单信息">
                                                ' . $son_order_id . '</a></p>';
                                        }
                                        if (!empty($order_result))
                                            $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                                    } else {
                                        $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=WISH&system_order_id=' . $order['parent_order_id'] . '" title="订单信息">
                                                ' . $order['parent_order_id'] . '</a></p>';
                                        $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                                    }
                                }
                                $account_info = Account::getHistoryAccountInfo($order['account_id'], WishInbox::PLATFORM_CODE);
                                //是否当前订单                   
                                $currentLabel = '';
                                if ($order['platform_order_id'] == $info->order_id) {
                                    $order_id = $info->order_id;
                                    echo "<input type='hidden' name='current_order_id' value='$order_id'>";
                                    $currentLabel = '<span class="label label-danger">当前订单</span>';
                                }

                                //是否重寄单
                                $redirectLabel = '';
                                if ($order['order_type'] == Order::ORDER_TYPE_REDIRECT_ORDER) {
                                    $redirectLabel = '<span class="label label-warning">重寄订单</span>';
                                }
                                //是否子订单
                                $splitLabel = '';
                                if ($order['order_type'] == Order::ORDER_TYPE_SPLIT_CHILD) {
                                    $splitLabel = '<span class="label label-warning">折分子订单</span>';
                                }
                                ?>
                                <tr class="active">
                                    <td>             
        <?php foreach ($order['detail'] as $deKey => $deVal): ?>

                                            <?php
                                            $arr[$deKey] = $deVal['item_id'];

                                            $hvalueArr = array_unique($arr);
                                            ?>

                                        <?php endforeach; ?>
                                        <a _width="100%" _height="100%" class="edit-button"
                                           href="<?php
                                echo Url::toRoute(['/orders/order/orderdetails',
                                    'order_id' => $order['platform_order_id'],
                                    'platform' => Platform::PLATFORM_CODE_WISH,
                                    'system_order_id' => $order['order_id']]);
                                        ?>"
                                           title="订单产品详情"><?php echo isset($account_info->account_short_name) ? $account_info->account_short_name . '--' . $order['order_id'] : $order['order_id']; ?></a>
                                           <?php echo $order_result; ?>
                                        <br>

        <?php
        if (count($order['detail'])) {
            echo '<a data-toggle="collapse" data-parent="#accordion" href="#proDetail_' . $hKey . '" aria-expanded="true" class="">查看产品详情</a><br>';
        }
        ?>

                                        <?php
                                        if (count($order['remark'])) {
                                            $remark = htmlspecialchars(json_encode($order['remark']));
                                            ?>
                                            <span style="color:red" class="have_remark" data-remark="<?php echo $remark; ?>">有备注</span>
                                        <?php } else { ?>
                                            <span>无备注</span>
                                        <?php } ?>
                                        <br>
                                        <?php echo $currentLabel; ?>
                                        <?php echo $redirectLabel; ?>
                                        <?php echo $splitLabel; ?>
                                    </td>
                                        <?php $account_info = Account::getHistoryAccountInfo($order['account_id'], Platform::PLATFORM_CODE_WISH); ?>
                                    <td>
                                    <?php
                                    echo isset($account_info->account_name) ? $account_info->account_name : '';
                                    ?>
                                        <?php echo!empty($order['ship_country']) ? '<br/>' . $order['ship_country'] : '<br/>NoCountry' ?>
                                        <?php echo '<br/>' . $order['buyer_id']; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($order['payment_status'] == 0)
                                            echo "未付款";
                                        else
                                            echo $order['paytime'];
                                        ?>
                                        <br/><span <?php if ($order['complete_status_text'] == '已取消') echo 'style="color:red;"'; ?>><?php echo $order['complete_status_text']; ?></span>
                                        <br/><span class="label label-danger">无评价</span>
                                        
                                    </td>
                                    <td><?php echo $order['total_price'] . $order['currency']; ?><br/>
                                            <?php
                                            //退款金额
                                            if (isset($order['trade']) && !empty($order['trade'])) {
                                                $after_refund_amount = 0;
                                                foreach ($order['trade'] as $after_sale_refund) {
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
                                        $profit = $order['profit']['profit'];
                                        $profit = $profit ? $profit : '-';
                                        echo $profit;
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $order_inbox_info = \wish\models\WishInboxInfo::findOne(['order_id' => $order['order_id']]);
                                        $order_inbox = WishInbox::findOne(['info_id' => $order_inbox_info->info_id]);
                                        if ($order_inbox->is_assist == 1) {
                                            ?>
                                            <span class="label label-danger">有纠纷</span><br/>
                                        <?php } else {
                                            ?>
                                            <span class="label label-danger">无纠纷</span><br/>
                                        <?php } ?>
                                         <?php 
                                         //退货编码
                                        $refundcode=\app\modules\aftersales\models\AfterRefundCode::find()->where(['order_id'=>$order['order_id']])->asArray()->one();
                                        if(empty($refundcode)){
                                             echo '<span class="label label-success">无</span>';
                                        }else{
                                            echo $refundcode['refund_code'];
                                        }
                                        ?>
                                         <br>
                                        <?php
//                                    if (AfterSalesOrder::hasAfterSalesOrder(Platform::PLATFORM_CODE_WISH, $order['order_id']))                                  
//                                        echo '<span class="label label-danger">有</span>';
//                                    else
//                                        echo '<span class="label label-success">无</span>';
                                        $res = AfterSalesOrder::getAfterSalesOrderByOrderId($order['order_id'], Platform::PLATFORM_CODE_WISH);
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
                                            $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                                                    $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_WISH . '" >' .
                                                    $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a>';
                                            echo $domestic_return;
                                        } else {
                                            echo '<span class="label label-success">无</span>';
                                        }
                                        ?>
                                        <br/>
                                        <?php
                                        $complaint = \app\modules\aftersales\models\ComplaintModel::find()->select('complaint_order,status')->where(['order_id' => $order['order_id']])->one();
                                        if (empty($complaint)) {
                                            echo '<span class="label label-success">无</span>';
                                        } else {
                                            if ($complaint->status == 6) {
                                                echo '<a _width="100%" _height="100%" class="edit-button" href=' . Url::toRoute(['/aftersales/complaint/getcompain', 'complaint_order' => $complaint->complaint_order]) . '>' . $complaint->complaint_order . '(已处理)</a>';
                                            } else {
                                                echo '<a _width="100%" _height="100%" class="edit-button" href=' . Url::toRoute(['/aftersales/complaint/getcompain', 'complaint_order' => $complaint->complaint_order]) . '>' . $complaint->complaint_order . '(未处理)</a>';
                                            }
                                        }
                                        ?>
                                        <!--用于保存售后单信息勿删-->
                                        <span id="after_<?php echo $order['order_id']; ?>"></span>
                                    </td>
        <?php
        if (count($Historica[$hKey]['orderPackage'])) {
            foreach ($Historica[$hKey]['orderPackage'] as $k => $val) {
                $trackNumber = empty($order['track_number']) ? $val['tracking_number_1'] : $order['track_number'];
                echo '<td>';
                echo $val['warehouse_name'] . '<br/>';
                echo '<span style="font-size:11px;">' . $val['shipped_date'] . '</span><br/>';
                echo $val['ship_name'] . '<br/>';
                echo!empty($trackNumber) ? '<a href="https://t.17track.net/en#nums=' . $trackNumber . '" target="_blank" title="查看物流跟踪信息">' . $trackNumber . '</a>' : '-' . '<br/>';
                echo '</td>';
            }
        } else {
            echo '<td>暂无包裹信息</td>';
        }
        ?>

                                    <td>
                                        <div class="btn-group btn-list">
                                            <button type="button"
                                                    class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation'); ?></button>
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                                    data-toggle="dropdown">
                                                <span class="caret"></span>
                                                <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                            </button>
                                            <ul class="dropdown-menu" rol="menu">

        <?php if ($order['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP || $order['complete_status'] == 99) { ?>
                                                    <li><a _width="30%" _height="60%" class="edit-button"
                                                           href="<?php
            echo Url::toRoute(['/orders/order/cancelorder',
                'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]);
            ?>">永久作废</a>
                                                    </li>
                                                    <li><a _width="30%" _height="60%" class="edit-button"
                                                           href="<?php
                                               echo Url::toRoute(['/orders/order/holdorder',
                                                   'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]);
            ?>">暂时作废</a>
                                                    </li>
                                                           <?php
                                                       }
                                                       if ($order['complete_status'] == Order::COMPLETE_STATUS_HOLD) {
                                                           ?>
                                                    <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                           href="<?php
                                        echo Url::toRoute(['/orders/order/cancelholdorder',
                                            'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]);
                                                           ?>">取消暂时作废</a>
                                                    </li>
                                                           <?php
                                                       }
                                                       ?>
                                                <?php //$order_re=stripos($order['order_id'], 'RE'); ?>    
                                                <?php //if ($order['order_type'] != Order::ORDER_TYPE_REDIRECT_ORDER || !empty($order_re)) {   ?>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php
                                        echo Url::toRoute(['/aftersales/order/add',
                                            'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH, 'from' => 'inbox']);
                                                ?>">新建售后单</a>
                                                </li>
                                                       <?php //}   ?>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]); ?>">登记退款单</a>
                                                </li>

                                                <li>
                                                    <a _width="50%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/orders/order/invoice', 'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]); ?>">发票</a>
                                                </li>
                                                <li>
                                                    <a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/complaint/register', 'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]); ?>">登记客诉单</a>
                                                </li>

                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/ebayreceipt', 'order_id' => $order['order_id'], 'platform' => $order['platform_code'], 'buyer_id' => $order['buyer_id'], 'account_id' => $order['account_id']]); ?>">登记收款单</a>
                                                </li>

                                            </ul>
                                        </div>
                                    </td>
                                </tr>
    <?php } ?>
                        <?php } elseif (empty($Historica) && !empty($orderinfo)) { ?>
                            <?php
                            $warehouse_id = isset($orderinfo['orderPackage'][0]['warehouse_id']) ? $orderinfo['orderPackage'][0]['warehouse_id'] : 0;
                            echo "<input type='hidden' id='current_order_warehouse_id' value='$warehouse_id'>";

                            //是否当前订单
                            $currentLabel = '';
                            if ($orderinfo['info']['platform_order_id'] == $info->order_id) {
                                $order_id = $info->order_id;
                                echo "<input type='hidden' name='current_order_id' value='$order_id'>";
                                $currentLabel = '<span class="label label-danger">当前订单</span>';
                            }
                            ?>
                            <tr class="active">
                                <td>
                                    <a _width="80%" _height="80%" class="edit-button"
                                       href="<?php
                        echo Url::toRoute(['/orders/order/orderdetails',
                            'order_id' => $orderinfo['info']['platform_order_id'],
                            'platform' => Platform::PLATFORM_CODE_WISH,
                            'system_order_id' => $orderinfo['info']['order_id']]);
                            ?>"
                                       title="订单信息"><?php echo $orderinfo['info']['order_id']; ?></a>
                                    <br>
                                    <a _width="80%" _height="80%" class="edit-button"
                                       href="<?php
                                   echo Url::toRoute(['/orders/order/orderdetails',
                                       'order_id' => $orderinfo['info']['platform_order_id'],
                                       'platform' => Platform::PLATFORM_CODE_WISH,
                                       'system_order_id' => $orderinfo['info']['order_id']]);
                            ?>"
                                       title="订单信息"><?php echo $orderinfo['info']['platform_order_id']; ?></a>
                                    <br>
    <?php echo $currentLabel; ?>
                                </td>
                                    <?php $account_info = Account::getHistoryAccountInfo($orderinfo['info']['account_id'], Platform::PLATFORM_CODE_WISH); ?>
                                <td>
                                <?php
                                echo isset($account_info->account_name) ? $account_info->account_name : '';
                                ?>
                                    <?php echo!empty($orderinfo['info']['ship_country']) ? '<br/>' . $orderinfo['info']['ship_country'] : '<br/>NoCountry' ?>
                                    <?php echo '<br/>' . $orderinfo['info']['buyer_id']; ?>
                                </td>
                                <td>
    <?php
    if ($orderinfo['info']['payment_status'] == 0)
        echo "未付款";
    else
        echo $orderinfo['info']['paytime'];
    ?>
                                    <br/><span <?php if ($orderinfo['info']['complete_status_text'] == '已取消') echo 'style="color:red;"'; ?>><?php echo $orderinfo['info']['complete_status_text']; ?></span>
                                </td>
                                <td><?php echo $orderinfo['product'][0]['total_price'] . $orderinfo['product'][0]['currency']; ?><br/>
    <?php
    //退款金额
    if (isset($orderinfo['trade']) && !empty($orderinfo['trade'])) {
        $after_refund_amount = 0;
        foreach ($orderinfo['trade'] as $after_sale_refund) {
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
                                    $profit = $orderinfo['profit']['profit'];
                                    $profit = $profit ? $profit : '-';
                                    echo $profit;
                                    ?>
                                </td>
                                <td>
    <?php
    $order_inbox_info = \wish\models\WishInboxInfo::findOne(['order_id' => $orderinfo['info']['order_id']]);
    $order_inbox = WishInbox::findOne(['info_id' => $order_inbox_info->info_id]);
    if ($order_inbox->is_assist == 1) {
        ?>
                                        <span class="label label-danger">有纠纷</span><br/>
                                    <?php } else {
                                        ?>
                                        <span class="label label-danger">无纠纷</span><br/>
                                    <?php } ?>
                                    <span class="label label-danger">无评价</span><br/>

    <?php
    // 售后信息 显示 退款 退货 重寄 退件
    $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder(Platform::PLATFORM_CODE_WISH, $orderinfo['info']['order_id']);
    //是否有售后订单
    if ($aftersaleinfo) {
        $res = AfterSalesOrder::getAfterSalesOrderByOrderId($orderinfo['info']['order_id'], Platform::PLATFORM_CODE_WISH);
        //获取售后单信息
        if (!empty($res['refund_res'])) {
            $refund_res = '退款';
            foreach ($res['refund_res'] as $refund_re) {
                $refund_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                        $refund_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_WISH . '&status=' . $aftersaleinfo->status . '" >' .
                        $refund_re['after_sale_id'] . '</a>';
            }
        } else {
            $refund_res = '';
        }

        if (!empty($res['return_res'])) {
            $return_res = '退货';
            foreach ($res['return_res'] as $return_re) {
                $return_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id=' .
                        $return_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_WISH . '&status=' . $aftersaleinfo->status . '" >' .
                        $return_re['after_sale_id'] . '</a>';
            }
        } else {
            $return_res = '';
        }

        if (!empty($res['redirect_res'])) {
            $redirect_res = '重寄';
            foreach ($res['redirect_res'] as $redirect_re) {
                $redirect_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id=' .
                        $redirect_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_WISH . '&status=' . $aftersaleinfo->status . '" >' .
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
            $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                    $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_WISH . '" >' .
                    $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a>';
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
        $res = AfterSalesOrder::getAfterSalesOrderByOrderId($order['order_id'], Platform::PLATFORM_CODE_WISH);
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
            $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                    $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_WISH . '" >' .
                    $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a>';
            echo $domestic_return;
        } else {
            echo '<span class="label label-success">无</span>';
        }
    }
    ?>

                                    <!--用于保存售后单信息勿删-->
                                    <span id="after_<?php echo $orderinfo['info']['order_id']; ?>"></span>
                                </td>
    <?php
    if (count($orderinfo['orderPackage'])) {
        foreach ($orderinfo['orderPackage'] as $k => $val) {
            $trackNumber = empty($order['info']['track_number']) ? $val['tracking_number_1'] : $order['info']['track_number'];
            echo '<td>';
            echo $val['warehouse_name'] . '<br/>';
            echo '<span style="font-size:11px;">' . $val['shipped_date'] . '</span><br/>';
            echo $val['ship_name'] . '<br/>';
            echo!empty($trackNumber) ? '<a href="https://t.17track.net/en#nums=' . $trackNumber . '" target="_blank" title="查看物流跟踪信息">' . $trackNumber . '</a>' : '-' . '<br/>';
            echo '</td>';
        }
    } else {
        echo '<td>暂无包裹信息</td>';
    }
    ?>

                                <td>
                                    <div class="btn-group btn-list">
                                        <button type="button"
                                                class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation'); ?></button>
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                                data-toggle="dropdown">
                                            <span class="caret"></span>
                                            <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                        </button>
                                        <ul class="dropdown-menu" rol="menu">

    <?php if ($orderinfo['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP || $orderinfo['info']['complete_status'] == 99) { ?>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
        echo Url::toRoute(['/orders/order/cancelorder',
            'order_id' => $orderinfo['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]);
        ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                               echo Url::toRoute(['/orders/order/holdorder',
                                                   'order_id' => $orderinfo['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]);
        ?>">暂时作废</a>
                                                </li>
                                                       <?php
                                                   }
                                                   if ($orderinfo['info']['complete_status'] == Order::COMPLETE_STATUS_HOLD) {
                                                       ?>
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                        echo Url::toRoute(['/orders/order/cancelholdorder',
                                            'order_id' => $orderinfo['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                       <?php
                                                   }
                                                   ?>
                                            <?php if ($orderinfo['info']['order_type'] != Order::ORDER_TYPE_REDIRECT_ORDER) { ?>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php
                                        echo Url::toRoute(['/aftersales/order/add',
                                            'order_id' => $orderinfo['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH, 'from' => 'inbox']);
                                                ?>">新建售后单</a>
                                                </li>
                                                   <?php } ?>
                                            <li><a _width="100%" _height="100%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $orderinfo['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]); ?>">登记退款单</a>
                                            </li>

                                            <li>
                                                <a _width="50%" _height="80%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/invoice', 'order_id' => $orderinfo['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_WISH]); ?>">发票</a>
                                            </li>

                                        </ul>
                                    </div>
                                </td>
                            </tr>
    <?php
} else {
    echo '<tr class="active"> <td colspan="7" align="center">没有相关订单信息！</td> </tr>';
}
?>
                    </tbody>
                </table>

            </div>
            <div id="menu2" class="tab-pane fade">
                <table class="table">
                    <thead>
                        <tr>
                            <th>产品名</th>
                            <th>产品ID</th>
                            <th>sku</th>
                            <th>总成本</th>
                            <th>数量</th>
                            <th>购买信息</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
if ($info->product_id) {
    ?>
                            <tr class="active">
                                <td><?php echo $info->goods_name; ?></td>
                                <td><a href="https://www.wish.com/product/<?php echo $info->product_id; ?>" target='_blank'><?php echo $info->product_id; ?></a></td>
                                <td><?php echo $info->sku; ?></td>
                                <td><?php echo $info->price; ?></td>
                                <td><?php echo $info->quantity; ?></td>
                                <td><?php echo 'size :' . $info->size . ' color:' . $info->color; ?></td>
                            </tr>
    <?php
} else {
    echo '<tr class="active"> <td colspan="2" align="center">没有相关产品信息！</td> </tr>';
}
?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="panel panel-default" style="margin-left:10px;width: 100%;float: left;margin-top: 20px;">
        <a href="/mails/wish/details?next=1&id=<?php echo $id; ?>" class="btn btn-primary btn-lg btn-block">下一封</a>
    </div>
</div>


<script>
    var keyboards = '<?php echo $keyboards; ?>'
    keyboards = JSON.parse(keyboards);
    var ids = '<?php echo $model->id; ?>'
    var tag_id = '';
    $(document).ready(
            function () {
                document.onkeyup = function (e) {
                    var event = window.event || e;
                    if (event.shiftKey && keyboards['shift'] != undefined && keyboards['shift'][event.keyCode] != undefined) {
                        tag_id = keyboards['shift'][event.keyCode]
                        if (tag_id != '' && tag_id != undefined) {
                            $.post('<?= Url::toRoute(['/mails/wish/addretags', 'ids' => $model->id, 'type' => 'details']) ?>', {
                                'MailTag[inbox_id]': ids,
                                'MailTag[tag_id][]': tag_id,
                                'MailTag[type]': 'details'
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
                                    $("#tags_value" + tags_id).hide(50);
                                }
                            }, 'json');
                        }
                    }
                    if (event.ctrlKey && keyboards['ctrl'] != undefined && keyboards['ctrl'][event.keyCode] != undefined) {
                        tag_id = keyboards['ctrl'][event.keyCode]
                        if (tag_id != '' && tag_id != undefined) {
                            $.post('<?= Url::toRoute(['/mails/wish/addretags', 'ids' => $model->id, 'type' => 'details']) ?>', {
                                'MailTag[inbox_id]': ids,
                                'MailTag[tag_id][]': tag_id,
                                'MailTag[type]': 'details'
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
                                    $("#tags_value" + tags_id).hide(50);
                                }
                            }, 'json');
                        }
                    }
                    if (event.altKey && keyboards['alt'] != undefined && keyboards['alt'][event.keyCode] != undefined) {
                        tag_id = keyboards['alt'][event.keyCode]
                        if (tag_id != '' && tag_id != undefined) {
                            $.post('<?= Url::toRoute(['/mails/wish/addretags', 'ids' => $model->id, 'type' => 'details']) ?>', {
                                'MailTag[inbox_id]': ids,
                                'MailTag[tag_id][]': tag_id,
                                'MailTag[type]': 'details'
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
                                    $("#tags_value" + tags_id).hide(50);
                                }
                            }, 'json');
                        }
                    }
                }
            }
    );


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

    //上传图片
    $('.wish_reply_upload_image').click(function () {
        layer.open({
            area: ['500px', '200px'],
            type: 1,
            title: '上传图片',
            content: '<form style="padding:10px 0px 0px 20px" action="<?php echo Url::toRoute('/mails/wish/uploadimage') ?>" method="post" id="wish_pop_upload_image" enctype="multipart/form-data"><input type="file" name="wish_reply_upload_image"/><p style="color:red">支持图片格式：gif、jpg、png、jpeg、tif、bmp。</p></form>',
            btn: '上传',
            yes: function (index, layero) {
                layero.find('#wish_pop_upload_image').ajaxSubmit({
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
                                $('.wish_reply_upload_image_display_area').append('<div class="wish_reply_upload_image_display"><img style="height:80px;width:80px;border:1px solid #ccc;padding:2px;" src="' + response.url + '" ><a class="wish_reply_upload_image_delete">删除</a></div>');
                                layer.close(index);
                        }
                    },
                });
            }
        });
    });
    //删除图片
    $('.wish_reply_upload_image_display_area').delegate('.wish_reply_upload_image_delete', 'click', function () {
        if (window.confirm('确定要删除？')) {
            var $this = $(this);
            var delteImageUrl = $this.siblings('img').attr('src');
            $.post('<?php echo Url::toRoute('/mails/wish/deleteimage') ?>', {'url': delteImageUrl}, function (response) {
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

    /**
     * 绑定翻译按钮 进行手动翻译操作(系统未检测到用户语言)
     * @author allen <2018-1-11>
     **/
    $('.artificialTranslation').click(function () {
        var sl = $("#sl_code").val();
        var tl = $("#tl_code").val();
        var content = $.trim($("#reply_content").val());
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
                    $("#reply_content_en").val(data);
                }
            }
        });
    });

    $(function () {
        //模板ajax
        $('.mail_template_area').delegate('.mail_template_unity', 'click', function () {
            $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>', {'num': $(this).attr('value')}, function (data) {
                switch (data.status) {
                    case 'error':
                        layer.msg(data.message);
                        return;
                    case 'success':
                        var conent = $('#reply_content').val();
                        if (conent !== "") {
                            $('#reply_content').val(conent + "\n" + data.content);
                        } else {
                            $('#reply_content').val(data.content);
                        }
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
                'platform_code': 'WISH'
            }, function (data) {
                if (data.code == 200) {
                    $('#reply_content').val(data.data);
                } else {
                    layer.msg(data.message, {
                        icon: 2,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    });
                    return;
                }
            }, 'json');
        }


        //模板搜索
        $('.mail_template_search_btn').click(function () {
            var templateName = $.trim($('.mail_template_search_text').val());
            if (templateName.length == 0) {
                layer.msg('搜索内容不能为空。', {
                    icon: 2,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                });
                return;
            }
            $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplate']); ?>', {'name': templateName, 'platform_code': 'WISH'}, function (data) {
                switch (data.status) {
                    case 'error':
                        layer.msg(data.message);
                        return;
                    case 'success':
                        var templateHtml = '';
                        for (var i in data.content) {
                            templateHtml += '<a class="mail_template_unity" value="' + i + '">' + data.content[i] + '</a>&nbsp;';
                        }
                        $('.mail_template_area').html(templateHtml);
                }
            }, 'json');
        });
    });

    function markerReply(deal_stat) {
        $.post("/mails/wish/markerreply",
                {
                    account_id: $('#account_id').val(),
                    platform_id: $('#platform_id').val(),
                    id: $('#id').val(),
                    deal_stat: deal_stat
                },
                function (result) {
                    var obj = eval('(' + result + ')');
                    layer.msg(obj.message);
                });
    }

    $(function () {
        $("#Assist").click(function () {
            $.post("/mails/wish/assist",
                    {
                        account_id: $('#account_id').val(),
                        platform_id: $('#platform_id').val(),
                    },
                    function (result) {
                        var obj = eval('(' + result + ')');
                        layer.msg(obj.message);
                    });
        });

        $("#Close").click(function () {
            $.post("/mails/wish/close",
                    {
                        account_id: $('#account_id').val(),
                        platform_id: $('#platform_id').val(),
                    },
                    function (result) {
                        var obj = eval('(' + result + ')');
                        layer.msg(obj.message);
                    });
        });
        //回复
        $("#Reply").click(function () {
            var reply_content_en = $('#reply_content_en').val();//翻译前内容
            var reply_content = $('#reply_content').val();//翻译后内容

            //确保发送给客户的内容不为空
            if (reply_content == "") {
                if (reply_content_en == "") {
                    layer.msg('请输入回复内容s!');
                    return false;
                } else {
                    reply_content = reply_content_en;
                }
            }

            if (reply_content_en == "") {
                reply_content_en = reply_content;
            }

            var sendData = {
                'content': reply_content,
                'content_en': reply_content_en,
                'account_id': $('#account_id').val(),
                'platform_id': $('#platform_id').val(),
            };
            var ebayReplyImageObj = $('.wish_reply_upload_image_display > img');//.attr('src');
            for (var i = 0; i < ebayReplyImageObj.length; i++) {
                sendData['image[' + i + ']'] = ebayReplyImageObj[i].src;
            }
            $.post("/mails/wish/reply", sendData, function (result) {
                var replyList = '';

                if (result.status == 1) {
                    var obj = result.data;
                    replyList += '<div class="panel panel-seller">';
                    replyList += '<div class="panel-heading">';
                    replyList += '<h3 class="panel-title">';
                    replyList += '<p><i class="glyphicon glyphicon-arrow-left"></i>回复人：' + obj.reply_by + '</p>';
                    replyList += '<p>发送时间：' + obj.create_time + '</p>';
                    replyList += '</h3>';
                    replyList += '</div>';
                    replyList += '<div class="panel-body">';
                    replyList += '<p>' + obj.reply_content + '</p>';
                    replyList += '</div>';
                    replyList += '</div>';
                    $('#replyList').prepend(replyList);
                    $('#Reply').attr('id', 'Reply11');
                    layer.msg(result.message, {icon: 1});
                    window.location.href = "/mails/wish/details?next=1&id=" + '<?php echo $id; ?>';
                }
            }, 'json');
        });
    });

    $('div.sidebar').hide();

    // 获取url参数
    function GetQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if (r != null)
            return unescape(r[2]);
        return null;
    }

    function removetags(obj) {
        var _id = GetQueryString('id');
        var tag_id = $(obj).siblings('span').attr('use_data');
        $.post('<?= Url::toRoute(['/mails/wish/removetags', 'id' => $model->id, 'type' => 'details']) ?>', {
            'MailTag[inbox_id]': _id,
            'MailTag[tag_id][]': tag_id,
            'MailTag[type]': 'details'
        }, function (data) {
            if (data.url && data.code == "200")
                $("#tags_value" + tag_id).hide(50);
        }, 'json');

    }


    $('#return_info').click(function () {
        var rule_warehouse_id = $('#current_order_warehouse_id').val();
        if (rule_warehouse_id == 0) {
            layer.msg("暂无仓库信息", {icon: 5});
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/refundtemplate/getrefundinfo']); ?>', {'rule_warehouse_id': rule_warehouse_id}, function (data) {
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
                    $('#reply_content').val(html);
            }
        }, 'json');
    });

    /**
     * 回复客户邮件内容点击翻译(系统检测到用户语言)
     * @author allen <2018-1-29>
     */
    $(".transClik").click(function () {
        var sl = 'auto';
        var tl = 'en';
        var tag = $(this).attr('data1');
        var message = decodeURIComponent($("#text_" + tag).attr("data-content"));
        console.log(message);
        var that = $(this);
        if (message.length == 0) {
            layer.msg('获取需要翻译的内容为空!');
            return false;
        }
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['ebayinboxsubject/translate']); ?>',
            data: {'sl': sl, 'tl': tl, 'returnLang': 1, 'content': message},
            success: function (data) {
                if (data) {
                    var htm = '<p style="color:green; font-weight:bold;">' + data.text + '</p>';
                    $(".pcontent_" + tag).after(htm);
                    $("#sl_code").val('en');
                    $("#sl_name").html('英语');
                    $("#tl_code").val(data.googleCode);
                    $("#tl_name").html(data.code);
                    that.remove();
                }
            }
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
                                    $('#reply_content').val(html + '\n' + old_content);
                                } else {
                                    $('#reply_content').val(html);
                                }
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
                            var old_content = $('#reply_content').val();
                            if (old_content !== '') {
                                $('#reply_content').val(html + '\n' + old_content);
                            } else {
                                $('#reply_content').val(html);
                            }
                    }

                }
            });

        }
    });
</script>
