<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\MailTemplate;
use app\modules\orders\models\Warehouse;
use app\modules\accounts\models\Account;
use app\modules\orders\models\Order;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\services\modules\cdiscount\components\cdiscountApi;
use kartik\select2\Select2;
use app\modules\aftersales\models\AfterSalesRedirect;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\Tansaction;
use app\modules\orders\models\PaypalInvoiceRecord;
use app\modules\mails\models\CdiscountInbox;
use app\modules\mails\models\CdiscountInboxReply;

$this->title = $subject->subject;
?>
<style>
    .mb10 {
        margin-bottom: 10px;
    }

    .table td {
        vertical-align: middle !important;
        text-align: center;
    }

    .language {
        width: 650px;
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

    .subjectTag {
        margin-right: 5px;
        margin-bottom: 5px;
    }

    .subjectTag i {
        font-size: 18px;
        font-style: normal;
        color: #337ab7;
        cursor: pointer;
    }

    .remarkTag i {
        font-size: 18px;
        font-style: normal;
        color: #337ab7;
        cursor: pointer;
    }

    .pannel-boday{
        width: 100%;
        float: left;
        clear: both;
        box-shadow: 6px 6px 6px #999;
        border: 1px solid #337ab7;
    }

    .panel-buyer {
        width: 70%;
        float: left;
        border: 1px solid #337ab7;
        clear: both;
        box-shadow: 6px 6px 6px #999;
        background: #b3f3616e;
    }

    .panel-heading {
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

    .translate {
        text-decoration: underline;
        font-weight: bold;
    }

    .translate-result {
        color: green;
        font-weight: bold;
    }

    .addRemark {
        cursor: pointer;
        margin-left: 10px;
    }

    fieldset {
        padding: .35em .625em .75em;
        margin: 0 2px;
        border: 1px solid silver;
    }

    legend {
        padding: .5em;
        border: 0;
        width: auto;
        margin-bottom: 0;
        font-size: 16px;
    }

    .mailTemplateItem {
        margin-right: 5px;
        margin-bottom: 5px;
    }

    .mailTemplateSearch {
        cursor: pointer;
    }

    .uploadImageLine {
        margin: 5px 0;
        line-height: 30px;
    }

    .showUploadImage {
        display: none;
        border: 1px solid #ccc;
        width: 150px;
        height: 150px;
        overflow: hidden;
    }

    .showUploadImage img {
        width: 148px;
        height: 148px;
    }
</style>
<script type="text/javascript" src="/js/jquery.form.js"></script>


<div id="page-wrapper-inbox" class="row">
    <div class="col col-md-12" style="margin-bottom:20px;">
        <a href="<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/list']); ?>" class="btn btn-primary" style="width:100%;">返回列表</a>
    </div>
    <div class="col col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <p><i class="fa fa-pencil">查看邮件</i> (主题: <?php echo $subject->subject; ?>)</p>
                    <p>账号：<?php echo!empty($account->account_name) ? $account->account_name : ''; ?></p>
                    <p>
                        <?php if (!empty($subject->tags)) { ?>
                            <?php foreach ($subject->tags as $tag) { ?>
                                <span class="label label-info subjectTag">
                                    <?php echo $tag['name']; ?>
                                    <i data-inboxid="<?php echo $subject->id; ?>" data-tagid="<?php echo $tag['id']; ?>">x</i>
                                </span>
                            <?php } ?>
                        <?php } ?>
                    </p>
                    <?php if (!empty($subject->product_seller_reference)) { ?>
                        <p>SKU：<?php echo $subject->product_seller_reference; ?></p>
                    <?php } ?>
                    <?php if (!empty($orderInfo['product'])) { ?>
                        <?php foreach ($orderInfo['product'] as $product) { ?>
                            <p>
                                产品名称：
                                <a href="https://www.cdiscount.com/f-1650601-<?php echo $product['item_id']; ?>.html" target="blank"><?php echo $product['picking_name']; ?></a>
                            </p>
                        <?php } ?>
                    <?php } ?>
                </h3>
            </div>
            <div class="panel-body" style="height:720px;overflow-y:scroll;">
                <?php if (empty($account->account_discussion_name)) { ?>
                    <div class="alert alert-danger" role="alert">
                        如果发现下面对话框没有左右分开买家与卖家对话，请到 平台账号管理->账号管理 里设置账号的讨论名称
                    </div>
                <?php } ?>

                <?php if (!empty($list_d)) {
                    ?>
                    <?php foreach ($list_d as $key => $item) { ?>
                        <div class="panel pannel-boday">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <p>
                                        <?php echo!empty($subject_reply_ids[$key]['sender']) ? $subject_reply_ids[$key]['sender'] : '无'; ?>
                                        <i class="glyphicon glyphicon-arrow-right"></i>
                                        <span style="float:right;">
                                            主题ID：
                                            <?php echo $subject_reply_ids[$key]['inbox_subject_id']; ?>
                                        </span>
                                    </p>

                                    <p>
                                        最新发送时间：<?php echo date('Y-m-d H:i:s', strtotime($subject_reply_ids[$key]['timestamp']) + (6 * 3600)); ?>

                                        <?php if (!empty($subject_reply_ids[$key]['remark'])) { ?>
                                            <span class="label label-info remarkTag">
                                                <?php echo $subject_reply_ids[$key]['remark']; ?>
                                                <i data-id="<?php echo $subject_reply_ids[$key]['id']; ?>">x</i>
                                            </span>
                                        <?php } else { ?>
                                            <i class="fa fa-pencil addRemark" data-id="<?php echo $subject_reply_ids[$key]['id']; ?>"></i>
                                        <?php } ?>

                                        <span style="float:right;">
                                            回复状态：
                                            <?php
                                            if (empty($subject_reply_ids[$key]['is_reply'])) {
                                                echo '<b style="color:yellow;">未回复</b>';
                                            } else {
                                                echo '已回复';
                                            }
                                            ?>
                                        </span>
                                    </p>
                                </h3>
                            </div>
                            <?php $timestamp = date('Y-m-d H:i:s', strtotime($subject_reply_ids[$key]['timestamp']) + (6 * 3600));?>
                            <?php if (!empty($reply_d[$key][0]) && $reply_d[$key][0]['reply_time'] > $timestamp) { ?>
                                <?php $reply = $reply_d[$key][0]; ?>
                                <div class="panel panel-seller">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">
                                            <p>
                                                <?php echo $account->account_discussion_name; ?>
                                                <i class="glyphicon glyphicon-arrow-right"></i>
                                                <span style="float:right;">
                                                    回复人:
                                                    <b style="background-color:#dff0d8;"><?php echo $reply['reply_by']; ?></b>
                                                </span>
                                            </p>
                                            <p>
                                                发送时间：<?php echo $reply['reply_time']; ?>
                                            </p>
                                        </h3>
                                    </div>
                                    <?php if (!empty($reply['attachments'])) { ?>
                                        <div class="">
                                            <div class="dropdown">
                                                <button class="btn btn-primary dropdown-toggle" type="button" id="reply_attachments_<?php echo $reply['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                    附件：<span class="caret"></span>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="reply_attachments_<?php echo $reply['id']; ?>">
                                                    <?php foreach ($reply['attachments'] as $attachment) { ?>
                                                        <li><a href="<?php echo Yii::$app->request->getHostInfo() . '/' . ltrim($attachment['file_path'], '/'); ?>" target="_blank"><?php echo $attachment['name']; ?></a></li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="panel-body">
                                        <p id="content_<?php echo $reply['id']; ?>"><?php echo!empty($reply['reply_content_en']) ? $reply['reply_content_en'] : $reply['reply_content']; ?></p>
                                        <p class="translate-result" id="translate_<?php echo $reply['id']; ?>"></p>
                                        <p><a href="javascript:void(0);" class="translate" data-id="<?php echo $reply['id']; ?>">点击翻译</a></p>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php foreach ($item as $k => $inbox) { ?>
                                <?php if ($inbox['float'] == 'left') { ?>
                                    <div class="panel panel-buyer">
                                        <?php if (!empty($inbox['attachments'])) { ?>
                                            <div class="">
                                                <div class="dropdown">
                                                    <button class="btn btn-primary dropdown-toggle" type="button" id="inbox_attachments_<?php echo $inbox['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                        附件：<span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="inbox_attachments_<?php echo $inbox['id']; ?>">
                                                        <?php foreach ($inbox['attachments'] as $attachment) { ?>
                                                            <li><a href="<?php echo Yii::$app->request->getHostInfo() . '/' . ltrim($attachment['file_path'], '/'); ?>" target="_blank"><?php echo $attachment['name']; ?></a></li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <div class="panel-body">
                                            <p id="content_<?php echo $inbox['id']; ?>"><?php echo $inbox['content']; ?></p>
                                            <p class="translate-result" id="translate_<?php echo $inbox['id']; ?>"></p>
                                            <p><a href="javascript:void(0);" class="translate" data-id="<?php echo $inbox['id']; ?>">点击翻译</a>
                                                <span style="float: right">
                                                    <?php echo date('Y-m-d H:i:s', strtotime($inbox['timestamp']) + (6 * 3600)); ?>
                                                </span>
                                            </p>

                                        </div>
                                    </div>

                                <?php } else if ($inbox['float'] == 'right') { ?>
                                    <div class="panel panel-seller">
                                        <div class="panel-heading">
                                            <h3 class="panel-title">
                                                <p>
                                                    <?php echo !empty($inbox['sender']) ? $inbox['sender'] : '无'; ?>
                                                    <i class="glyphicon glyphicon-arrow-right"></i>

                                                    <?php if (!empty($inbox['reply_by'])) { ?>
                                                        <span style="float:right;">
                                                            回复人:
                                                            <b style="background-color:#dff0d8;"><?php echo $inbox['reply_by']; ?></b>
                                                        </span>
                                                    <?php } ?>
                                                </p>
                                                <p>
                                                    发送时间：<?php echo date('Y-m-d H:i:s', strtotime($inbox['timestamp']) + (6 * 3600)); ?>

                                                    <?php if (!empty($inbox['remark'])) { ?>
                                                        <span class="label label-info remarkTag">
                                                            <?php echo $inbox['remark']; ?>
                                                            <i data-id="<?php echo $inbox['id']; ?>">x</i>
                                                        </span>
                                                    <?php } else { ?>
                                                        <i class="fa fa-pencil addRemark" data-id="<?php echo $inbox['id']; ?>"></i>
                                                    <?php } ?>
                                                </p>
                                            </h3>
                                        </div>
                                        <?php if (!empty($inbox['attachments'])) { ?>
                                            <div class="">
                                                <div class="dropdown">
                                                    <button class="btn btn-primary dropdown-toggle" type="button" id="inbox_attachments_<?php echo $inbox['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                        附件：<span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="inbox_attachments_<?php echo $inbox['id']; ?>">
                                                        <?php foreach ($inbox['attachments'] as $attachment) { ?>
                                                            <li><a href="<?php echo Yii::$app->request->getHostInfo() . '/' . ltrim($attachment['file_path'], '/'); ?>" target="_blank"><?php echo $attachment['name']; ?></a></li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <div class="panel-body">
                                            <p id="content_<?php echo $inbox['id']; ?>"><?php echo!empty($inbox['content_en']) ? $inbox['content_en'] : $inbox['content']; ?></p>
                                            <p class="translate-result" id="translate_<?php echo $inbox['id']; ?>"></p>
                                            <p><a href="javascript:void(0);" class="translate" data-id="<?php echo $inbox['id']; ?>">点击翻译</a></p>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-info-circle">历史订单</i></h3>
            </div>
            <div class="panel-body">
                <table class="table table-striped table-bordered">
                    <tr>
                        <td>订单号</td>
                        <td>
                            账号<br>
                            国家<br>
                            买家ID
                        </td>
                        <td>
                            下单时间<br>
                            付款时间<br>
                            最迟发货时间<br>
                            规定运输时间<br>
                            订单状态
                        </td>
                        <td>
                            订单金额<br>
                            退款金额<br>
                            利润<br>
                            评价
                        </td>
                        <td>
                            纠纷状态<br>
                            退货编码<br>
                            售后<br/>
                            仓库客诉
                        </td>
                        <td>
                            包裹信息
                        </td>
                        <td>
                            操作
                        </td>
                    </tr>

                    <?php if (!empty($historyOrders)) { ?> 
                 
                        <?php foreach ($historyOrders as $order) { ?>
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
                                        $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=CDISCOUNT&system_order_id=' . $son_order_id . '" title="订单信息">
                                                ' . $son_order_id . '</a></p>';
                                    }
                                    if (!empty($order_result))
                                        $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                                } else {
                                    $order_result .= '<p><a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=CDISCOUNT&system_order_id=' . $order['parent_order_id'] . '" title="订单信息">
                                                ' . $order['parent_order_id'] . '</a></p>';
                                    $order_result = '<p>' . $rela_order_name . ':</p>' . $order_result;
                                }
                            }
                            $account_info = Account::getHistoryAccountInfo($order['account_id'], Platform::PLATFORM_CODE_CDISCOUNT);


                            //是否当前订单
                            $currentLabel = '';
                            if ($order['platform_order_id'] == $subject->platform_order_id) {
                                $warehouseList = Warehouse::getWarehouseListAll();
                                $warehouse_id = !empty($order['orderPackage'][0]['warehouse_id']) ? $order['orderPackage'][0]['warehouse_id'] : 0;
                                $warehouse_name = array_key_exists($warehouse_id, $warehouseList) ? $warehouseList[$warehouse_id] : '';

                                echo "<input type='hidden' name='current_order_warehouse_id' value='{$warehouse_id}'>";
                                echo "<input type='hidden' name='current_order_id' value='{$order['order_id']}'>";
                                echo "<input type='hidden' name='current_order_warehouse_name' value='{$warehouse_name}'>";
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
                            <tr>
                                <td>
                                    <?php if(!empty($order['detail'])){?>
                                    <?php foreach ($order['detail'] as $deKey => $deVal): ?>

                                        <?php
                                        $arr[$deKey] = $deVal['item_id'];

                                        $hvalueArr = array_unique($arr);
                                        ?>

                                    <?php endforeach; ?>
                                <?php } ?>
                                    <span><?php echo $order['platform_order_id']; ?></span>
                                    <br>
                                    <span>-------------------------</span>
                                    <br>
                                    <a _width="100%" _height="100%" class="edit-button"
                                       href="<?php
                                       echo Url::toRoute(['/orders/order/orderdetails',
                                           'order_id' => $order['platform_order_id'],
                                           'platform' => Platform::PLATFORM_CODE_CDISCOUNT,
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
                                <td>
                                    <?php
                                    //账号
                                    $account = Account::findOne(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT, 'old_account_id' => $order['account_id']]);
                                    if (!empty($account)) {
                                        echo $account->account_name, '<br>';
                                    } else {
                                        echo '无<br>';
                                    }
                                    //国家
                                    if (!empty($order['ship_country'])) {
                                        echo $order['ship_country'], '<br>';
                                    } else {
                                        echo '无<br>';
                                    }
                                    //买家ID
                                    if (!empty($order['buyer_id'])) {
                                        echo $order['buyer_id'], '<br>';
                                    } else {
                                        echo '无<br>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    //下单时间
                                    $creationDate = '无<br>';
                                    //规定运输时间
                                    $deliveryDateMax = '无<br>';
                                    //最迟发货时间
                                    $shippingDateMax = '无<br>';

                                    if (!empty($order['platform_order_id'])) {
                                        //$cdApi = new cdiscountApi($account->refresh_token);
                                        //$apiOrderInfo = $cdApi->getOrderInfo($order['platform_order_id']);

                                        if (!empty($apiOrderInfo) && !empty($apiOrderInfo['GetOrderListResponse']['GetOrderListResult']['OrderList'])) {
                                            $apiOrderInfo = $apiOrderInfo['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'];

                                            if (!empty($apiOrderInfo['CreationDate'])) {
                                                $creationDate = date('Y-m-d H:i:s', strtotime($apiOrderInfo['CreationDate'])) . '<br>';
                                            }

                                            if (!empty($apiOrderInfo['OrderLineList']) && !empty($apiOrderInfo['OrderLineList']['OrderLine'])) {
                                                $orderLine = $apiOrderInfo['OrderLineList']['OrderLine'];
                                                if (!empty($orderLine[0]['DeliveryDateMax'])) {
                                                    $deliveryDateMax = date('Y-m-d H:i:s', strtotime($orderLine[0]['DeliveryDateMax'])) . '<br>';
                                                }
                                            }

                                            if (!empty($apiOrderInfo['ShippingDateMax'])) {
                                                $shippingDateMax = date('Y-m-d H:i:s', strtotime($apiOrderInfo['ShippingDateMax'])) . '<br>';
                                            }
                                        }
                                    }

                                    //下单时间
                                    echo $order['created_time'] . '<br>';

                                    if (!empty($order['payment_status'])) {
                                        echo '已付款<br>';
                                        echo $order['paytime'], '<br>';
                                    } else {
                                        echo '未付款<br>';
                                    }
                                    //最迟发货时间
                                    echo $shippingDateMax;
                                    //规定运输时间
                                    echo $deliveryDateMax;

                                    if ($order['complete_status_text'] == '已取消') {
                                        echo '<span style="color:red">' . $order['complete_status_text'] . '</span>';
                                    } else {
                                        echo $order['complete_status_text'];
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    //订单金额
                                    if (!empty($order['trade'])) {
                                        $total_price = $order['total_price'];
                                        foreach ($order['trade'] as $trade) {
                                            if ($trade['receive_type'] == '发起') {
                                                $total_price -= $trade['amt'];
                                            }
                                        }
                                        if (empty($total_price)) {
                                            $total_price = '0.00';
                                        } else {
                                            $total_price = number_format($total_price, 2, '.', '');
                                        }
                                        echo '<b style="color:green">' . $total_price . $order['currency'] . '</b><br/>';
                                    } else {
                                        echo '<b style="color:green">' . $order['total_price'] . $order['currency'] . '</b><br/>';
                                    }

                                    //退款金额
                                    if (!empty($order['trade'])) {
                                        $refund_amount = 0;
                                        foreach ($order['trade'] as $trade) {
                                            if ($trade['amt'] < 0) {
                                                $refund_amount += $trade['amt'];
                                            }
                                        }
                                        if (!empty($refund_amount)) {
                                            echo $refund_amount . '<br>';
                                        } else {
                                            echo '无<br>';
                                        }
                                    } else {
                                        echo '无<br>';
                                    }

                                    //利润
                                    if (!empty($order['profit'])) {
                                        $profit = 0;
                                        if (!empty($refund_amount)) {
                                            $profit = -$refund_amount;
                                        }
                                        if (!empty($order['after_sale_redirect'])) {
                                            foreach ($order['after_sale_redirect'] as $after_sale_redirect) {
                                                $cost = new Order();
                                                $cost = $cost->getRedirectCostByOrderId(Platform::PLATFORM_CODE_CDISCOUNT, $order['order_id']);
                                                if ($cost && $cost->ack == true) {
                                                    $cost = $cost->data;
                                                    $profit += $cost;
                                                }
                                            }
                                        }
                                        $profit = $order['profit']['profit'] - $profit;
                                        echo $profit;
                                    } else {
                                        echo '无<br>';
                                    }
                                    ?>
                                    <?php echo '无<br>'; ?>
                                </td>
                                <td>
                                    <?php
                                    echo '无<br>';                                     
                                    //退货编码
                                    $refundcode=\app\modules\aftersales\models\AfterRefundCode::find()->where(['order_id'=>$order['order_id']])->asArray()->one();
                                    if(empty($refundcode)){
                                         echo '<span class="label label-success">无</span>';
                                    }else{
                                        echo $refundcode['refund_code'];
                                    }
                                    echo "<br>";
                                    // 售后信息 显示 退款 退货 重寄 退件
                                    $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder(Platform::PLATFORM_CODE_CDISCOUNT, $order['order_id']);
                                    //是否有售后订单
                                    if ($aftersaleinfo) {
                                        $res = AfterSalesOrder::getAfterSalesOrderByOrderId($order['order_id'], Platform::PLATFORM_CODE_CDISCOUNT);
                                        //获取售后单信息
                                        if (!empty($res['refund_res'])) {
                                            $refund_res = '退款';
                                            foreach ($res['refund_res'] as $refund_re) {
                                                $refund_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                                                        $refund_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_CDISCOUNT . '&status=' . $aftersaleinfo->status . '" >' .
                                                        $refund_re['after_sale_id'] . '</a>';
                                            }
                                        } else {
                                            $refund_res = '';
                                        }

                                        if (!empty($res['return_res'])) {
                                            $return_res = '退货';
                                            foreach ($res['return_res'] as $return_re) {
                                                $return_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id=' .
                                                        $return_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_CDISCOUNT . '&status=' . $aftersaleinfo->status . '" >' .
                                                        $return_re['after_sale_id'] . '</a>';
                                            }
                                        } else {
                                            $return_res = '';
                                        }

                                        if (!empty($res['redirect_res'])) {
                                            $redirect_res = '重寄';
                                            foreach ($res['redirect_res'] as $redirect_re) {
                                                $redirect_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id=' .
                                                        $redirect_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_CDISCOUNT . '&status=' . $aftersaleinfo->status . '" >' .
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
                                                    $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_CDISCOUNT . '" >' .
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
                                        $res = AfterSalesOrder::getAfterSalesOrderByOrderId($order['order_id'], Platform::PLATFORM_CODE_WALMART);
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
                                                    $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_CDISCOUNT . '" >' .
                                                    $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a>';
                                            echo $domestic_return;
                                        } else {
                                            echo '<span class="label label-success">无</span>';
                                        }
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
                                <td>
                                    <?php
                                    //包裹信息
                                    if (!empty($order['orderPackage'])) {
                                        foreach ($order['orderPackage'] as $package) {
                                            echo $package['warehouse_name'], '<br>';
                                            echo $package['shipped_date'], '<br>';
                                            echo $package['ship_name'], '<br>';
                                            if ($order['paytime'] < '2018-05-20 00:00:00') {
                                                echo!empty($package['tracking_number_1']) ? '<a href="https://t.17track.net/en#nums=' . $package['tracking_number_1'] . '" target="_blank" title="查看物流跟踪信息">' . $package['tracking_number_1'] . '</a>' : '-' . '<br/>';
                                            } else {
                                                echo!empty($package['tracking_number_1']) ? '<a href="' . Url::toRoute(['/orders/order/gettracknumber', 'track_number' => $package['tracking_number_1']]) . '" title="查看物流跟踪信息">' . $package['tracking_number_1'] . '</a>' : '-' . '<br/>';
                                            }
                                        }
                                    } else {
                                        echo '暂无包裹信息<br>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-list">
                                        <button type="button" class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation'); ?></button>
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <span class="caret"></span>
                                            <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                        </button>
                                        <ul class="dropdown-menu" rol="menu">
                                            <?php if ($order['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP || $order['complete_status'] == 99) { ?>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $order['order_id'], 'platform' => $order['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $order['order_id'], 'platform' => $order['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                            <?php } ?>
                                            <?php if ($order['complete_status'] == Order::COMPLETE_STATUS_HOLD) { ?>
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $order['order_id'], 'platform' => $order['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $order['order_id'], 'platform' => $order['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>
                                            <?php $order_re = stripos($order['order_id'], 'RE'); ?>      
                                            <?php //if ($order['order_type'] != Order::ORDER_TYPE_REDIRECT_ORDER && !empty($order_re)) {  ?>
                                            <li>
                                                <a style=" cursor: pointer;" onclick="verified('<?php echo $order['order_id']; ?>')">新建售后单</a>
                                                <a style="display: none;" id="orderadd_<?php echo $order['order_id']; ?>" _width="100%" _height="100%" class="edit-button"
                                                   href="<?php
                                                   echo Url::toRoute(['/aftersales/order/add',
                                                       'order_id' => $order['order_id'], 'platform' => $order['platform_code'], 'from' => 'inbox']);
                                                   ?>">新建售后单</a>
                                            </li>
                                            <?php //}  ?>
                                            <li><a _width="100%" _height="100%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $order['order_id'], 'platform' => $order['platform_code']]); ?>">登记退款单</a>
                                            </li>
                                            <li>
                                                <a _width="50%" _height="80%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/invoice', 'order_id' => $order['order_id'], 'platform' => $order['platform_code']]); ?>">发票</a>
                                            </li>
                                            <?php
                                            $invoiceInfo = PaypalInvoiceRecord::getIvoiceData($order['order_id'], $order['platform_code']);
                                            $transactionId = Tansaction::getOrderTransactionIdEbayByOrderId($order['order_id'], $order['platform_code']);
                                            ?>
                                            <?php if (isset($invoiceInfo)) { ?>
                                                <li><a href="javascript:void(0)" class="cancelEbayPaypalInvoice" data-orderid="<?php echo $order['order_id']; ?>" data-invoiceid="<?php echo $invoiceInfo['invoice_id']; ?>" data-invoiceemail="<?php echo $invoiceInfo['merchant_email']; ?>">取消收款</a>
                                                </li>
                                            <?php } else { ?>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $order['order_id'], 'platform_order_id' => $order['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $order['platform_code']]); ?>">收款</a>
                                                </li>
                                            <?php }; ?>
                                            <li><a _width="80%" _height="80%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/sales/ebayreceipt', 'order_id' => $order['order_id'], 'platform' => $order['platform_code'], 'buyer_id' => $order['buyer_id'], 'account_id' => $order['account_id']]); ?>">登记收款单</a>
                                            </li>
                                            <li><a _width="100%" _height="100%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/complaint/register', 'order_id' => $order['order_id'], 'platform' => $order['platform_code']]); ?>">登记客诉单</a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7">没有历史订单信息</td>
                        </tr>
                    <?php } ?>

                </table>
            </div>
        </div>
    </div>

    <div class="col col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-envelope-o">回复邮件</i></h3>
            </div>
            <?php
            $form = ActiveForm::begin([
                        'id' => 'createReplyForm',
                        'action' => Url::toRoute(['/mails/cdiscountinboxsubject/createreply']),
                        'method' => 'post',
                        'options' => ['enctype' => 'multipart/form-data'],
            ]);
            ?>

            <div class="panel-body">
                <div class="row">
                    <input type="hidden" id="now_subject_id" name="subject_id" value="<?php echo $subject->id; ?>">
                    <input type="hidden" name="account_id" value="<?php echo $subject->account_id; ?>">
                    <input type="hidden" id="sl_code" value="">
                    <input type="hidden" id="tl_code" value="">
                </div>
                <div class="row mb10">
                    <div class="col col-md-12">
                        <p style="color:red;">注意：选择未回复的邮件选项，会针对所有未回复的发送邮件！！！请妥善选择。</p>
                        <?php
                        echo Select2::widget([
                            'id' => 'inbox_ids',
                            'name' => 'inbox_ids',
                            'value' => $noReplyId,
                            'data' => $noReplyInboxs,
                            'options' => ['placeholder' => '', 'multiple' => true, 'display' => 'block']
                        ]);
                        ?>
                    </div>
                </div>
                <div class="row mb10">
                    <div class="col-lg-6">
                        <div class="input-group">
                            <input type="text" class="form-control mail_template_title_search_text"
                                   placeholder="模板编号搜索">
                            <span class="input-group-btn">
                                <button class="btn btn-default mail_template_title_search_btn"
                                        type="button">Go!</button>
                            </span>
                        </div><!-- /input-group -->
                    </div>
                    <div class="col-lg-6">
                        <div class="input-group">
                            <input type="text" class="form-control mail_template_search_text" placeholder="消息模板搜索">
                            <span class="input-group-btn">
                                <div class="btn btn-default mailTemplateSearch">搜索</div>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="mail_template_area panel-body">
                        <?php
                        $mailTemplates = MailTemplate::getMailTemplateDataAsArrayByUserId(Platform::PLATFORM_CODE_CDISCOUNT);
                        foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName) {
                            echo "<a class='mail_template_unity' value='$mailTemplatesId'>$mailTemplateName</a> ";
                        }
                        ?>
                    </div>
                </div>
                <div class="row mb10">
                    <div class="col col-md-6" id="uploadImageArea">
                        <div class="row uploadImageLine">
                            <div class="col col-md-2">附件:</div>
                            <div class="col col-md-7">
                                <input type="file" name="uploadImage[]">
                                <div class="showUploadImage"></div>
                            </div>
                            <div class="col col-md-3">
                                <a href="javascript:void(0);" class="addUploadImageLine">添加</a>
                                <a href="javascript:void(0);" class="clearUploadImage">清空</a>
                            </div>
                        </div>
                    </div>
                    <div class="col col-md-6">
                        <a class="btn btn-success" id="return_info">获取退货信息</a>
                    </div>
                </div>
                <div class="row mb10">
                    <div class="col col-md-12">
                        <textarea name="reply_content_en" id="reply_content_en" rows="10" class="form-control" placeholder="输入回复内容(注意：此输入回复内容为英语)"></textarea>
                    </div>
                </div>

                <div class="row mb10">
                    <div class="col-sm-12">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-default" type="button" onclick="changeCode(3, 'en', '', $(this))">
                                英语
                            </button>
                            <button class="btn btn-default" type="button" onclick="changeCode(3, 'fr', '', $(this))">
                                法语
                            </button>
                            <button class="btn btn-default" type="button" onclick="changeCode(3, 'de', '', $(this))">
                                德语
                            </button>
                            <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                <div class="btn-group">
                                    <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" id="sl_btn">
                                        更多<span class="caret"></span>
                                    </button>
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
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-default" type="button" onclick="changeCode(4, 'en', '', $(this))">
                                英语
                            </button>
                            <button class="btn btn-default" type="button" onclick="changeCode(4, 'fr', '', $(this))">
                                法语
                            </button>
                            <button class="btn btn-default" type="button" onclick="changeCode(4, 'de', '', $(this))">
                                德语
                            </button>
                            <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                <div class="btn-group">
                                    <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" id="tl_btn">
                                        更多<span class="caret"></span>
                                    </button>
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

                <div class="row mb10">
                    <div class="col col-md-12">
                        <textarea name="reply_content" id="reply_content" rows="10" class="form-control" placeholder="发送给客户的内容"></textarea>
                    </div>
                </div>

                <div class="row mb10">
                    <div class="col col-md-10">
                        <a href="javascript:void(0);" class="btn btn-default closeDiscussion" data-id="<?php echo $subject->id; ?>">关闭问题</a>
                        <a href="javascript:void(0);" class="btn btn-default markReply" data-id="<?php echo $subject->id; ?>">标记已回复</a>
                        <a href="javascript:void(0);" class="btn btn-default jumpInboxSubject" data-type="prev">上一个</a>
                        <a href="javascript:void(0);" class="btn btn-default jumpInboxSubject" data-type="next">下一个</a>
                        <a href="<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/addtags', 'ids' => $subject->id, 'type' => 'detail']) ?>" class="btn btn-primary add-tags-button-button">新增标签</a>
                        <a href="<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/removetags', 'id' => $subject->id, 'type' => 'detail']) ?>" class="btn btn-danger add-tags-button-button">移除标签</a>
                    </div>
                    <div class="col col-md-2">
                        <a class="btn btn-success" id="createReplyBtn">回复消息</a>
                    </div>
                </div>
            </div>
            <?php
            ActiveForm::end();
            ?>
        </div>
    </div>
</div>

<div class="modal fade in" id="addRemarkModal" tabindex="-1" role="dialog" aria-labelledby="addRemarkModalLabel"
     style="top:300px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">添加备注</h4>
            </div>
            <div class="modal-body">
                <form id="addRemarkForm">
                    <div class="row">
                        <div class="col col-lg-12">
                            <textarea class="form-control" rows="5" name="remark"></textarea>
                            <input type="hidden" name="id" value="">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="addRemarkBtn">保存</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('div.sidebar').hide();

    //关闭问题
    $(".closeDiscussion").on("click", function () {
        var _this = $(this);
        var id = $(this).attr("data-id");

        layer.confirm("确定关闭问题吗？", {icon: 3}, function (index) {
            $.post("<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/closediscussion']); ?>", {
                "id": id
            }, function (data) {
                if (data["code"] == 1) {
                    layer.msg("关闭问题成功", {icon: 1});
                    //按钮不可再次点击
                    _this.attr("disabled", "disabled");
                    _this.off("click");
                } else {
                    layer.msg(data["message"], {icon: 5});
                }
            }, "json");
            layer.close(index);
        });
        return false;
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
            'platform_code': 'CDISCOUNT'
        }, function (data) {
            if (data.code == 200) {
                $('#reply_content_en').val(data.data);
                reply();
            } else {
                layer.msg(data.message, {
                    icon: 2,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                });
                return;
            }
        }, 'json');
        return false;
    }

    //回复消息
    $("#createReplyBtn").on("click", function () {
        reply();
        return false;
    });

    function reply() {
        $("#createReplyForm").ajaxSubmit({
            dataType: 'json',
            success: function (data) {
                if (data["code"] == "200" && data["url"] == 'next') {
                    layer.msg("发送成功", {icon: 1}, function () {
                        window.location.href = "<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/view', 'id' => $subject->id, 'next' => 1]); ?>";
                    });
                } else if (data["code"] == "200" && data["url"] == 'no') {
                    layer.msg("发送成功", {icon: 1}, function () {
                        window.location.href = "<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/view', 'id' => $subject->id]); ?>";
                    });
                } else {
                    layer.msg(data["message"], {icon: 5});
                }
            }
        });
    }

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
                    var refund_content = $('#reply_content_en').val();
                    if (refund_content !== '') {
                        $('#reply_content_en').val(refund_content + '\n' + data.content);
                    } else {
                        $('#reply_content_en').val(data.content);
                    }
            }
        }, 'json');

        return false;
    });


    //消息模板搜索
    $('.mailTemplateSearch').click(function () {
        var templateName = $.trim($('.mail_template_search_text').val());
        var platform_code = 'CDISCOUNT';
        if (templateName.length == 0) {
            layer.msg('搜索名称不能为空', {icon: 5});
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplate']); ?>', {
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

        return false;
    });


    //标记已回复
    $(".markReply").on("click", function () {
        var _this = $(this);
        var id = $(this).attr("data-id");
        if (id.length == 0) {
            layer.msg("ID不能为空", {icon: 5});
            return false;
        }
        $.post("<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/batchmark']); ?>", {
            "ids": id
        }, function (data) {
            if (data["code"] == "200") {
                layer.msg(data["message"], {icon: 1});
                //按钮不可再次点击
                _this.attr("disabled", "disabled");
                _this.off("click");
            } else {
                layer.msg(data["message"], {icon: 5});
            }
        }, "json");
        return false;
    });

    //上一个和下一个
    $(".jumpInboxSubject").on("click", function () {
        var type = $(this).attr("data-type");
        if (type == "prev") {
            //window.location.href = "<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/view']); ?>"+"?id="+subjectId+"&prev=1";
            window.location.href = "<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/view', 'id' => $subject->id, 'prev' => 1]); ?>";
        } else if (type == "next") {
            window.location.href = "<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/view', 'id' => $subject->id, 'next' => 1]); ?>";
            //window.location.href = "<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/view']); ?>" + "?id="+subjectId+'&next=1';
        }
        return false;
    });

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
            url: '<?php echo Url::toRoute(['/mails/ebayinboxsubject/translate']); ?>',
            data: {'sl': sl, 'tl': tl, 'content': content},
            success: function (data) {
                if (data) {
                    $("#reply_content").val(data);
                    $("#reply_content").css('display', 'block');
                }
            }
        });
        return false;
    });

    //点击获取退货信息
    $('#return_info').click(function () {
        var current_order_id = $("input[name='current_order_id']").val();
        var rule_warehouse_id = $("input[name='current_order_warehouse_id']").val();
        var current_order_warehouse_name = $("input[name='current_order_warehouse_name']").val();
        var warehouse_1 = '递四方';
        var warehouse_2 = '谷仓';
        if (!rule_warehouse_id) {
            layer.msg("暂无仓库信息", {icon: 5});
            return;
        }
        if (!current_order_id) {
            layer.msg("暂无订单信息", {icon: 5});
            return;
        }
        if (current_order_warehouse_name.match(warehouse_1) || current_order_warehouse_name.match(warehouse_2)) {

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
                                html += "\n";
                                html += 'consignee:' + data.content.refund_name;
                                html += "\n";
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
                            html += "\n";
                            html += 'consignee:' + data.content.refund_name;
                            html += "\n";
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
        return false;
    });

    //文件上传添加
    $("#uploadImageArea").on("click", ".addUploadImageLine", function () {
        var html = "<div class='row uploadImageLine'>";
        html += "<div class='col col-md-2'>附件: </div>";
        html += "<div class='col col-md-7'>";
        html += "<input type='file' name='uploadImage[]'>";
        html += "<div class='showUploadImage'></div>";
        html += "</div>";
        html += "<div class='col col-md-3'>";
        html += "<a href='javascript:void(0);' class='delUploadImageLine'>删除</a>&nbsp;";
        html += "<a href='javascript:void(0);' class='clearUploadImage'>清空</a>";
        html += "</div>";
        html += "</div>";
        $("#uploadImageArea").append(html);
    });

    //清空
    $("#uploadImageArea").on("click", ".clearUploadImage", function () {
        $(this).parents(".uploadImageLine").find("input[type='file']").val("");
        $(this).parents(".uploadImageLine").find(".showUploadImage").css("display", "none").html("");
    });

    //文件上传预览
    $("#uploadImageArea").on("change", "input[type='file']", function () {
        var file = this.files[0];
        if (/(.jpg|.png|.gif|.ps|.jpeg)$/.test(file["name"])) {
            //创建一个img标签
            var img = document.createElement("img");
            //通过file对象创建对象URL
            img.src = window.URL.createObjectURL(file);
            img.onload = function () {
                //释放对象URL
                window.URL.revokeObjectURL(this.src);
            };
            $(this).next(".showUploadImage").css("display", "block").html($(img));
        }
    });

    //文件上传删除
    $("#uploadImageArea").on("click", ".delUploadImageLine", function () {
        $(this).parents(".uploadImageLine").remove();
    });

    //选择邮件模板
    $("#mailTemplateArea").on("click", ".mail_template", function () {
        var id = $(this).attr("data-id");

        $.post("<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>", {
            "num": id
        }, function (data) {
            switch (data.status) {
                case 'error':
                    layer.msg(data.message, {icon: 5});
                    return;
                case 'success':
                    var refund_content = $('#reply_content_en').val();
                    if (refund_content != '') {
                        $("#reply_content_en").val(refund_content + "\n" + data.content);
                    } else {
                        $("#reply_content_en").val(data.content);
                    }
            }
        }, 'json');
        return false;
    });

    //删除主题标签
    $("#page-wrapper-inbox").on("click", ".subjectTag i", function () {
        var parent = $(this).parent();
        var inbox_id = $(this).attr("data-inboxid");
        var tag_id = $(this).attr("data-tagid");

        $.post("<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/removetags']) ?>" + "?id=" + inbox_id + "&type=detail", {
            'MailTag[inbox_id]': inbox_id,
            'MailTag[tag_id][]': tag_id,
            'MailTag[type]': "detail"
        }, function (data) {
            if (data.code == "200") {
                layer.msg("标签移除成功", {icon: 1});
                parent.remove();
            }
        }, "json");
        return false;
    });

    //删除邮件备注
    $("#page-wrapper-inbox").on("click", ".remarkTag i", function () {
        var parent = $(this).parent();
        var id = $(this).attr("data-id");

        $.post("<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/setremark']); ?>", {
            "id": id,
            "remark": ""
        }, function (data) {
            if (data["code"] == 1) {
                var html = "<i class='fa fa-pencil addRemark' data-id='" + id + "'></i>";
                parent.after(html);
                parent.remove();
            } else {
                layer.msg(data["message"], {icon: 5});
            }
        }, "json");
        return false;
    });

    //添加邮件备注
    $("#page-wrapper-inbox").on("click", "i.addRemark", function () {
        var id = $(this).attr("data-id");
        $("#addRemarkForm input[name='id']").val(id);
        $("#addRemarkModal").modal("show");
        return false;
    });

    //弹窗关闭时清空数据
    $("#addRemarkModal").on('hidden.bs.modal', function (e) {
        $("#addRemarkForm input[name='id']").val("");
        $("#addRemarkForm textarea[name='remark']").val("");
    });

    //添加邮件备注
    $("#addRemarkBtn").on("click", function () {
        var id = $("#addRemarkForm input[name='id']").val();
        var remark = $("#addRemarkForm textarea[name='remark']").val();
        var params = $("#addRemarkForm").serialize();

        $.post("<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/setremark']); ?>", params, function (data) {
            if (data["code"] == 1) {
                $("#addRemarkModal").modal("hide");
                var html = "<span class='label label-info remarkTag'>";
                html += remark;
                html += "<i data-id='" + id + "'>x</i>";
                html += "</span>";
                $(".addRemark[data-id='" + id + "']").after(html);
                $(".addRemark[data-id='" + id + "']").remove();
            } else {
                layer.msg(data["message"], {icon: 5});
            }
        }, "json");
        return false;
    });

    //点击翻译功能
    $("#page-wrapper-inbox").on("click", "a.translate", function () {
        var sl = 'auto';
        var tl = 'en';
        var id = $(this).attr("data-id");
        var content = $("#content_" + id).text();

        if (content.length == 0) {
            layer.msg("翻译内容不能为空", {icon: 5});
            return false;
        }

        $.post("<?php echo Url::toRoute(['/mails/ebayinboxsubject/translate']); ?>", {
            "sl": sl,
            "tl": tl,
            "returnLang": 1,
            "content": content
        }, function (data) {
            if (data) {
                $("#tl_code").val(data.googleCode);
                $("#tl_name").html(data.code);
                $("#sl_code").val('en');
                $("#sl_name").html('英语');
                $("#translate_" + id).html(data.text);
            }
        }, "json");
        return false;
    });

    function verified(id) {
        $.ajax({
            url: "<?php echo Url::toRoute(['/aftersales/domesticreturngoods/getreturngoods']); ?>",
            type: "GET",
            data: {'order_id': id},
            dataType: "json",
            success: function (data) {
                if (data.ack) {
                    layer.confirm('包裹已退件，请确认是否需要修改订单信息发出而不是建立重寄。', {
                        btn: ['是', '否'] //按钮
                    }, function (index) {
                        $("#orderadd_" + id).click();
                        layer.close(index);
                    })
                } else {
                    $("#orderadd_" + id).click();
                }
            }
        });
    }
</script>
