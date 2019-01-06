<?php

use app\modules\orders\models\Order;
use app\modules\accounts\models\Platform;
use yii\helpers\Url;
use app\common\VHelper;
use app\modules\blacklist\models\BlackList;
use app\modules\mails\models\AmazonReviewData;
use app\modules\accounts\models\Account;
use app\modules\systems\models\Gbc;
use app\modules\orders\models\OrderKefu;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonReviewMessageData;
use app\modules\mails\models\AliexpressEvaluate;
use app\modules\mails\models\AliexpressEvaluateList;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\orders\models\OrderEbay;
use app\modules\aftersales\models\AfterSalesOrder;

?>
<style>
    .cik{
    width: 63px;
    height: 22px;
    line-height: 22px;
    display: inherit;
    text-align: center;
    background: #00adff;
    color: #fff;
    cursor: pointer;
    border-radius: 12%;  
    }
</style>
<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne"><h4 class="panel-title">订单信息</h4></a>
    </div>
    <div id="collapseOne" class="panel-collapse collapse in">
        <div class="panel-body">
            <?php if (!empty($info['abnormals'])): ?>
                <?php
                $ablist = [
                    1  => '留言待处理异常(请联系客服部门)',
                    2  => '地址错误异常(请联系订单组)',
                    3  => '商品未知异常(请联系销售部门)',
                    4  => '无法分配仓库异常(请联系订单组)',
                    5  => '邮寄方式错误异常(速卖通平台请联系销售部门,其它平台联系订单组)',
                    6  => '一买家多订单异常(请联系订单组)',
                    9  => '需人工审核异常(请联系销售部门)',
                    11 => '付款金额错误异常(请联系客服部门)',
                    12 => '仓库发货异常(请联系订单组)',
                ];
                ?>
                <div>
                    <strong style="color:red">异常：</strong>
                    <?php foreach ($info['abnormals'] as $row):
                        if ($row['abnormity_superclass'] == 9 && !empty($row['check_order_rule'])) {
                            if ($row['flag_state'] == 1) {
                                $disabled  = "disabled=disabled";
                                $signtitle = "已经审核(" . $row['auditor_username'] . ":" . $row['auditor_date'] . ")";
                                $color     = "green";
                            } else {
                                $signtitle = "未审核";
                                $disabled  = '';
                                $color     = "red";
                            }
                            echo '<p><strong style="color:red">' . $ablist[$row['abnormity_superclass']] . ': </strong><span>' . $row['reason'] . '</span>
    <span style="color:' . $color . '">' . $signtitle . '</span>
</p>';
                        } else {
                            echo '<p><strong style="color:red">' . $row['abnormity_superclass'] . ': </strong><span>' . $row['reason'] . '</span>
</p>';
                        }
                    endforeach;
                    ?>
                </div>
            <?php endif; ?>
            <table class="table">
                <tbody>
                <?php if (!empty($info['info'])) { ?>
                    <?php
                    $account_info = \app\modules\accounts\models\Account::getHistoryAccountInfo($info['info']['account_id'], $info['info']['platform_code']);
                    $eb_site      = \app\modules\orders\models\OrderEbay::getSiteByPlatformId($info['info']['platform_order_id']);
                    ?>
                    <tr>
                        <td style="text-align: right;">销售平台</td>
                        <td><?php echo $info['info']['platform_code']; ?></td>
                        <td style="text-align: right;">平台订单号</td>
                        <td><?php echo $info['info']['platform_order_id']; ?></td>
                        <td style="text-align: right;">订单号</td>
                        <td><?php echo isset($account_info->account_short_name) ? $account_info->account_short_name . '--' . $info['info']['order_id'] : $info['info']['order_id']; ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;">买家ID</td>
                        <td>
                            <?php echo $info['info']['buyer_id']; ?>&nbsp;&nbsp;
                            <!--添加黑名单  取消黑名单操作 add by allen <2018-2-8> str-->
                            <?php if ($isAuthority) { ?>
                                <span id="blackinfo">
                                    <?php if (Gbc::checkInBlackList($info['info']['buyer_id'], $info['info']['platform_code'])) { ?>
                                        <a class="cancelBlackList" href="javascript:void(0);" style="color:blue;" data-buyerid="<?php echo $info['info']['buyer_id']; ?>" data-platformcode="<?php echo $info['info']['platform_code']; ?>">取消黑名单</a>
                                    <?php } else { ?>
                                        <a class="addBlackList" href="javascript:void(0);" style="color:blue;" data-buyerid="<?php echo $info['info']['buyer_id']; ?>" data-platformcode="<?php echo $info['info']['platform_code']; ?>">加入黑名单</a>
                                    <?php } ?>
                                </span>
                            <?php } ?>
                            <!--添加黑名单  取消黑名单操作 add by allen <2018-2-8> end-->
                        </td>
                        <td style="text-align: right;">店铺名称</td>
                        <td><?php echo \app\modules\accounts\models\Account::getHistoryAccount($info['info']['account_id'], $info['info']['platform_code']); ?></td>
                        <td style="text-align: right;">订单状态</td>
                        <td>
                            <?php
                            $complete_status = Order::getOrderCompleteStatus();
                            echo isset($complete_status[$info['info']['complete_status']]) ? $complete_status[$info['info']['complete_status']] : "-";
                            echo '(订单类型: ' . VHelper::getOrderType($info['info']['order_type']) . ')'; ?>
                            退款状态: <?php echo VHelper::refundStatus($info['info']['refund_status']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right;">下单时间</td>
                        <td><?php echo $info['info']['created_time']; ?></td>
                        <td style="text-align: right;">付款时间</td>
                        <td><?php echo $info['info']['paytime']; ?></td>
                        <?php switch ($info['info']['order_type']) {
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
                        } ?>

                        <?php if (!empty($rela_order_name)) { ?>
                            <td style="text-align: right;"><?= $rela_order_name; ?></td>
                            <td><?php if ($rela_is_arr && isset($info['info']['son_order_id'])) {
                                    foreach ($info['info']['son_order_id'] as $son_order_id) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=' . $info['info']['platform_code'] . '&system_order_id=' . $son_order_id . '" title="订单信息">
                                                ' . $son_order_id . '</a>';
                                    }
                                } else {
                                    echo '<a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=' . $info['info']['platform_code'] . '&system_order_id=' . $info['info']['parent_order_id'] . '" title="订单信息">
                                                ' . $info['info']['parent_order_id'] . '</a>';
                                }
                                ?></td>
                        <?php } else { ?>
                            <td colspan="2"></td>
                        <?php } ?>


                    </tr>
                    <tr>
                        <td style="text-align: right;">产品估重</td>
                        <td><?php echo $info['info']['product_weight'] . '(g)'; ?></td>
                        <td style="text-align: right;">运费</td>
                        <td><?php echo $info['info']['ship_cost'] + $info['info']['currency']; ?></td>
                        <td style="text-align: right;">总费用</td>
                        <td><?php echo $info['info']['total_price'] . '(' . $info['info']['currency'] . ')'; ?></td>
                    </tr>
                    <tr style="color: #00a2d4">
                        <td style="text-align: right;">是否同步
                        <td><?php echo \app\modules\orders\models\OrderEbay::getIsUpload($info['info']['is_upload']); ?></td>
                        <?php if($info['info']['platform_code'] == 'WALMART'){ ?>
                        <td>orderNumber</td>
                        <td><?php echo $info['info']['order_number'];?></td>
                        <td colspan="2"></td>
                        <?php }else{?>
                        <td colspan="4"></td>
                        <?php } ?>
                        
                    </tr>
                    <tr>
                        <!--纠纷-->
                        <td style="text-align: right;">纠纷</td>
                        <?php if ($info['info']['platform_code'] == 'EB') { ?>
                            <td>
                                <?php
                                $cancel_cases = \app\modules\mails\models\EbayCancellations::disputeLevel($info['info']['platform_order_id']);
                                $inquiry_cases = \app\modules\mails\models\EbayInquiry::disputeLevel($info['info']['platform_order_id'],$info['info']);
                                $returns_cases = \app\modules\mails\models\EbayReturnsRequests::disputeLevel($info['info']['platform_order_id'], $info['info']);
                                $disputeHtml = '';
                                $text_data_map = array(0 => array('data' => '无', 'color' => ''), 1 => array('data' => '已关闭', 'color' => 'lightgreen'), 2 => array('data' => '已解决', 'color' => 'lightblue'), 3 => array('data' => '有', 'color' => 'red'), 4 => array('data' => '已升级', 'color' => 'orange'));
                                $case_key = array(1, 2, 3, 4);
                                if (!empty($cancel_cases)) {
                                    foreach ($cancel_cases as $cancel_case) {
                                        if (in_array($cancel_case[0], $case_key)) {
                                            $disputeHtml .= '<p><a _width="100%" _height="100%" class="edit-button" style="color:' . $text_data_map[$cancel_case[0]]['color'] . '" href="' . Url::toRoute(['/mails/ebaycancellation/handle', 'id' => $cancel_case[1], 'isout' => 1]) . '">' . $cancel_case[2] . $text_data_map[$cancel_case[0]]['data'] . '</a>&nbsp;<p>';
                                        }
                                    }
                                }

                                if (!empty($inquiry_cases)) {
                                    foreach ($inquiry_cases as $inquiry_case) {
                                        if (in_array($inquiry_case[0], $case_key)) {
                                            $disputeHtml .= '<p><a _width="100%" _height="100%"class="edit-button" style="color:' . $text_data_map[$inquiry_case[0]]['color'] . '" href="' . Url::toRoute(['/mails/ebayinquiry/handle', 'id' => $inquiry_case[1], 'isout' => 1]) . '">' . $inquiry_case[2] . $text_data_map[$inquiry_case[0]]['data'] . '</a>&nbsp;<p>';
                                        }
                                    }
                                }

                                if (!empty($returns_cases)) {
                                    foreach ($returns_cases as $returns_case) {
                                        if (in_array($returns_case[0], $case_key)) {
                                            $disputeHtml .= '<p><a _width="100%" _height="100%" class="edit-button" style="color:' . $text_data_map[$returns_case[0]]['color'] . '" href="' . Url::toRoute(['/mails/ebayreturnsrequests/handle', 'id' => $returns_case[1], 'isout' => 1]) . '">' . $returns_case[2] . $text_data_map[$returns_case[0]]['data'] . '</a>&nbsp;<p>';
                                        }
                                    }
                                }
                                if (empty($disputeHtml)) {
                                    $disputeHtml = '<span class="label label-success">无</span>';
                                }

                                echo $disputeHtml;
                                ?>
                            </td>
                        <?php } ?>

                        <?php if ($info['info']['platform_code'] == 'ALI') { ?>
                            <td>
                                <?php
                                $issueStatus = \app\modules\services\modules\aliexpress\models\AliexpressOrder::getOrderIssueStatus($info['info']['platform_order_id'], $info['info']['account_id']);
                                $disputes = \app\modules\mails\models\AliexpressDisputeList::getOrderDisputes($info['info']['platform_order_id']);
                                if ($issueStatus == 'IN_ISSUE') {
                                    ?>
                                    <p><span class="label label-danger">纠纷订单</span></p>
                                <?php } else if ($issueStatus == 'NO_ISSUE') { ?>
                                    <p><span class="label label-default">没有纠纷</span></p>
                                <?php } else if ($issueStatus == 'END_ISSUE') { ?>
                                    <p><span class="label label-success">纠纷结束</span></p>
                                <?php } else { ?>
                                    <p><span class="label label-success">无</span></p>
                                <?php } ?>

                                <?php if (!empty($disputes)) { ?>
                                    <?php foreach ($disputes as $dispute) { ?>
                                        <p><a class="edit-button" _width="100%" _height="100%"
                                              href="<?php echo Url::toRoute(['/mails/aliexpressdispute/showorder', 'issue_id' => $dispute['platform_dispute_id']]); ?>">
                                                <span class="label label-danger">纠纷ID:<?php echo $dispute['platform_dispute_id']; ?></span>
                                            </a></p>
                                    <?php } ?>
                                <?php } ?>
                            </td>
                        <?php } ?>

                        <?php if (!in_array($info['info']['platform_code'], ['EB', 'ALI'])) { ?>
                            <td>
                                <span class="label label-success">无纠纷</span>
                            </td>
                        <?php } ?>
                        <!--邮件-->
                        <td style="text-align: right;">邮件</td>
                        <td>
                            <?php
                            if ($info['info']['platform_code'] == 'EB') {
                                $details = OrderEbay::getItemidArr($info['info']['order_id']);
                                //ebay站内信
                                $item_id = [];
                                foreach ($details as $detail) {
                                    $item_id[] = $detail['item_id'];
                                }
                                $account = Account::find()->select('id')->where(['old_account_id' => $info['info']['account_id'], 'platform_code' => 'EB'])->asArray()->one();
                                $account_id = $account['id'];
                                $isSetEbayInboxSubject = EbayInboxSubject::haveEbayInboxSubject($account_id, $item_id, $info['info']['buyer_id']);
                                if (!empty($isSetEbayInboxSubject)) {
                                    foreach ($isSetEbayInboxSubject as $value) {
                                        echo '<a target="_blank" href="/mails/ebayinboxsubject/detail?id=' . $value['id'] . '">' . $value['item_id'] . '</a><br/>';
                                    }
                                } else {
                                    echo '<span class="label label-success">无</span>';
                                }
                            } elseif ($info['info']['platform_code'] == 'ALI'){
                                $account_id = Account::find()->select('id')->where(['old_account_id' => $info['info']['account_id'], 'platform_code' => 'ALI'])->asArray()->one()['id'];
                                $inbox = \app\modules\mails\models\AliexpressInbox::findOne(['channel_id' => $info['info']['platform_order_id'],'account_id' => $account_id]);
                                if(!empty($inbox)){
                                    echo '<a target="_blank" href="/mails/aliexpress/details?id=' . $inbox['id'] . '">' . $inbox['channel_id'] . '</a><br/>';
                                }else{
                                    $inbox_replys = \app\modules\mails\models\AliexpressReply::findAll(['type_id' => $info['info']['order_id'],'account_id' => $account_id]);
                                    $channel_id = [];
                                    foreach ($inbox_replys as $inbox_reply) {
                                        $channel_id[] = $inbox_reply['channel_id'];
                                    }
                                    $isSetAliSubject = \app\modules\mails\models\AliexpressInbox::find()
                                        ->select('id,channel_id')
                                        ->where(['account_id' => $account_id])
                                        ->andWhere(['in','channel_id',$channel_id])
                                        ->all();
                                    if(!empty($isSetAliSubject)){
                                        foreach ($isSetAliSubject as $value) {
                                            echo '<a target="_blank" href="/mails/aliexpress/details?id=' . $value['id'] . '">' . $value['channel_id'] . '</a><br/>';
                                        }
                                    }else {
                                        echo '<span class="label label-success">无</span>';
                                    }
                                }
                            }elseif ($info['info']['platform_code'] == 'AMAZON'){
                                $account_id = Account::find()->select('id')->where(['old_account_id' => $info['info']['account_id'], 'platform_code' => 'AMAZON'])->asArray()->one()['id'];
                                $isSetAmazonSubject = \app\modules\mails\models\AmazonInboxSubject::findAll(['order_id' => $info['info']['platform_order_id'],'account_id' => $account_id]);
                                if(!empty($isSetAmazonSubject)){
                                    foreach ($isSetAmazonSubject as $value) {
                                        echo '<a target="_blank" href="/mails/amazoninboxsubject/view?id=' . $value['id'] . '">' . $value['order_id'] . '</a><br/>';
                                    }
                                }else {
                                    echo '<span class="label label-success">无</span>';
                                }
                            }elseif ($info['info']['platform_code'] == 'WISH'){
                                $account_id = Account::find()->select('id')->where(['old_account_id' => $info['info']['account_id'], 'platform_code' => 'WISH'])->asArray()->one()['id'];
                                $wish_info = \wish\models\WishInboxInfo::findOne(['order_id'=>$info['info']['platform_order_id']]);
                                $isSetWishSubject = \app\modules\mails\models\WishInbox::findAll(['info_id' => $wish_info['info_id'],'account_id' => $account_id]);
                                if(!empty($isSetWishSubject)){
                                    foreach ($isSetWishSubject as $value) {
                                        echo '<a target="_blank" href="/mails/wish/details?id=' . $value['id'] . '">' . $value['platform_id'] . '</a><br/>';
                                    }
                                }else {
                                    echo '<span class="label label-success">无</span>';
                                }
                            }elseif ($info['info']['platform_code'] == 'WALMART'){
                                $account_id = Account::find()->select('id')->where(['old_account_id' => $info['info']['account_id'], 'platform_code' => 'WALMART'])->asArray()->one()['id'];
                                $isSetWalSubject = \app\modules\mails\models\WalmartInboxSubject::findAll(['order_id' => $info['info']['platform_order_id'], 'account_id' => $account_id]);
                                if(!empty($isSetWalSubject)){
                                    foreach ($isSetWalSubject as $value) {
                                        echo '<a target="_blank" href="/mails/walmartinboxsubject/view?id=' . $value['id'] . '">' . $value['order_id'] . '</a><br/>';
                                    }
                                }else {
                                    echo '<span class="label label-success">无</span>';
                                }

                            }elseif ($info['info']['platform_code'] == 'CDISCOUNT'){
                                $account_id = Account::find()->select('id')->where(['old_account_id' => $info['info']['account_id'], 'platform_code' => 'CDISCOUNT'])->asArray()->one()['id'];
                                $isSetCdSubject = \app\modules\mails\models\CdiscountInboxSubject::findAll(['platform_order_id' => $info['info']['platform_order_id'], 'account_id' => $account_id]);
                                if(!empty($isSetCdSubject)){
                                    foreach ($isSetCdSubject as $value) {
                                        echo '<a target="_blank" href="/mails/cdiscountinboxsubject/view?id=' . $value['id'] . '">' . $value['inbox_id'] . '</a><br/>';
                                    }
                                }else {
                                    echo '<span class="label label-success">无</span>';
                                }
                            }else {
                                echo '<span class="label label-success">无</span>';
                            }
                            ?>
                        </td>
                        <!--评价-->
                        <td style="text-align: right">评价</td>
                        <td>
                            <?php
                            if ($info['info']['platform_code'] == 'EB') {
                                $model = OrderKefu::model('order_ebay');
                            } elseif ($info['info']['platform_code'] == 'ALI') {
                                $model = OrderKefu::model('order_aliexpress');
                            } elseif ($info['info']['platform_code'] == 'AMAZON') {
                                $model = OrderKefu::model('order_amazon');
                            } elseif ($info['info']['platform_code'] == 'WISH') {
                                $model = OrderKefu::model('order_wish');
                            } else {
                                $model = OrderKefu::model('order_other');
                            }
                            $orders = $model->where(['order_id' => $info['info']['order_id']])->one();
                            if (empty($orders)) {
                                //查copy表数据
                                if ($info['info'] == 'EB') {
                                    $model = OrderKefu::model('order_ebay_copy');
                                } elseif ($info['info'] == 'ALI') {
                                    $model = OrderKefu::model('order_aliexpress_copy');
                                } elseif ($info['info'] == 'AMAZON') {
                                    $model = OrderKefu::model('order_amazon_copy');
                                } elseif ($info['info'] == 'WISH') {
                                    $model = OrderKefu::model('order_wish_copy');
                                } else {
                                    $model = OrderKefu::model('order_other_copy');
                                }
                                $orders = $model->where(['order_id' => $info['info']['order_id']])->one();
                            }
                            //获取平台订单号
                            $platform_order_id = $info['info']['platform_order_id'];
                            if ($info['info']['platform_code'] == 'EB') {
                                // 查看订单评价
                                $feedbackInfos = EbayFeedback::find()->where(['order_line_item_id' => $platform_order_id, 'role' => 1])->all();
                                // 如果没有找到订单评价，通过交易ID和item_id来查评价
                                $details = \app\modules\orders\models\OrderEbayDetail::getItemIdAndTransactionId($info['info']['order_id']);
                                if (empty($feedbackInfos) && !empty($details)) {
                                    $max_comment_type = 6;

                                    foreach ($details as $detail) {
//                                        var_dump($detail);die;
                                        $feedbackInfo = EbayFeedback::getCommentByTransactionID($detail['transaction_id'], $detail['item_id']);
                                        if (!empty($feedbackInfo->comment_type) && ($feedbackInfo->comment_type < $max_comment_type)) {
                                            $feedbackInfos[] = $feedbackInfo;
                                        }
                                    }
                                }

                                if (!empty($feedbackInfos)) {
                                    foreach ($feedbackInfos as $feedbackInfo) {
                                        switch ($feedbackInfo->comment_type) {
                                            case 1:
                                                $comment_type = '<p><span>IndependentlyWithdrawn</span></p>';
                                                break;
                                            case 2:
                                                $comment_type = '<p><span><a style="color:red;" href="' . Url::toRoute(['/mails/ebayfeedbackresponse/add', 'type' => 'Reply', 'id' => $feedbackInfo->id]) . '" class="edit-button" id="status">Negative</a></span></p>';
                                                break;
                                            case 3:
                                                $comment_type = '<p><span><a style="color:orange;" href="' . Url::toRoute(['/mails/ebayfeedbackresponse/add', 'type' => 'Reply', 'id' => $feedbackInfo->id]) . '" class="edit-button" id="status">Neutral</a></span></p>';
                                                break;
                                            case 4:
                                                $comment_type = '<p><span style="color:green">Positive</span></p>';
                                                break;
                                            case 5:
                                                $comment_type = '<p><span>Withdrawn</span></p>';
                                                break;
                                        }
                                        echo $comment_type;
                                    }
                                } else {
                                    echo '<span class="label label-default">无</span>';
                                }
                            }
                            if ($info['info']['platform_code'] == 'AMAZON') {
                                //获取feedback  和review
                                $amazon_feedback = AmazonFeedBack::getFindOne($info['info']['platform_order_id']);
                                if (!empty($amazon_feedback)) {
                                    $feedbackInfo = $amazon_feedback->rating;
                                    $comment_type = '<p><span><a style="color:red;" data-rating="' . $amazon_feedback->rating . '" data-comments="' . $amazon_feedback->comments . '" class="view_feedback" >' . $feedbackInfo . '</a></span></p>';
                                    echo 'feedback:' . $comment_type . '<br>';
                                }
                                $amazon_review = AmazonReviewMessageData::getReviewByOrderId($info['info']['platform_order_id']);
                                if (!empty($amazon_review)) {
                                    $siteList = Account::getSiteList('AMAZON');
                                    $domainName = isset($siteList[$amazon_review['accountId']]) ? 'https://' . $siteList[$amazon_review['accountId']] : "";
                                    $amazon_review_info = '<p><span><a style="color:red;" data-domainname="' . $domainName . '" data-reviewId="' . $amazon_review['reviewId'] . '" data-star="' . $amazon_review['star'] . '" data-asin="' . $amazon_review['asin'] . '" data-title="' . $amazon_review['title'] . '" class="view_review" >' . $amazon_review['star'] . '</a></span></p>';
                                    echo 'review:' . $amazon_review_info;
                                } else {
                                    echo '<span class="label label-default">无</span>';
                                }
                            }
                            if ($info['info']['platform_code'] == 'ALI') {
                                $ali_evalute = AliexpressEvaluate::getFindOne($info['info']['platform_order_id']);
                                $id = $ali_evalute->id;
                                $account_id = $ali_evalute->account_id;
                                //只能查询到客服绑定账号的评价
                                $info_one = AliexpressEvaluateList::findOne($id);
                                if (!empty($info_one)) {
                                    $reply_evalute = '<a href="javascript:void(0)" class="edit-record ali_evalute" >' . $info_one['buyer_evaluation'] . '</a>';
                                    echo $reply_evalute;
                                } else {
                                    echo '<span class="label label-default">无</span>';
                                }
                            }
                            if(!in_array($info['info']['platform_code'], ['EB', 'ALI','AMAZON'])){
                                echo '<span class="label label-default">无</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right;">客户email</td>
                        <td colspan="0"><?php echo $info['info']['email']; ?>
                            <?php if ($info['info']['platform_code'] == "EB" && !empty($info['info']['email']))
                                echo '<a _width="95%" _height="95%" class="edit-button" href="/mails/ebayreply/initiativeadd?order_id=' . $info['info']['order_id'] . '&platform=EB">发送消息</a>&nbsp; <a _class="edit-button" href="' . Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $info['info']['platform_order_id'], 'platform' => 'EB']) . '">回评</a>'; ?>

                            <?php if ($info['info']['platform_code'] == "AMAZON") {
                                echo '<a href="' . Url::toRoute(['/mails/amazonreviewdata/getsendemail', 'account_id' => $account_info->id, 'toemail' => $info['info']['email'], 'platform_order_id' => $info['info']['platform_order_id']]) . '" target="_blank"> 联系买家</a>';
                            } ?>
                            <?php if ($info['info']['platform_code'] == "WALMART") {
                                echo '<a href="' . Url::toRoute(['/mails/walmartreply/getsendemail', 'account_id' => $account_info->id, 'toemail' => $info['info']['email'], 'platform_order_id' => $info['info']['platform_order_id']]) . '" target="_blank"> 联系买家</a>';
                            } ?>
                            <?php if ($info['info']['platform_code'] == "CDISCOUNT") {
                                echo '<a href="' . Url::toRoute(['/mails/cdiscountinboxsubject/getsendemail', 'account_id' => $account_info->id, 'toemail' => $info['info']['email'], 'platform_order_id' => $info['info']['platform_order_id']]) . '" target="_blank"> 联系买家</a>';
                            } ?>

                            <?php if ($info['info']['platform_code'] == "ALI") {
                                $evaluate_id = \app\modules\mails\models\AliexpressEvaluateList::getCurrentEvaluateIdByPlatformOrderId($info['info']['platform_order_id']);
                                ?>
                                <a class="btn btn-info btn-xs openSendMsgBtn"
                                   data-orderid="<?php echo $info['info']['platform_order_id']; ?>"
                                   data-buyeruserid="<?php echo $info['info']['buyer_user_id']; ?>"
                                   data-accountid="<?php echo $info['info']['account_id']; ?>">发送消息</a>&nbsp;&nbsp;
                                <a _width="100%" _height="100%" class="edit-button btn btn-info btn-xs "
                                   href="/mails/aliexpressevaluate/replyfeedback?id=<?php echo $evaluate_id; ?>">回复评价</a>
                            <?php } ?>
                        </td>

                        <?php if ($info['info']['platform_code'] == 'EB') { ?>
                            <td style="text-align: right;">站点</td>
                            <td><?= $eb_site ?></td>
                        <?php } ?>

                        <?php if ($info['info']['platform_code'] == "AMAZON") { ?>
                            <td style="text-align: right;">站点</td>
                            <td><?= $account_info['site_code'] ?></td>
                        <?php } ?>



                        <?php if (!empty($info['info']['buyer_accept_goods_end_time'])) { ?>
                            <td style="text-align:right;">
                                剩余收货时间
                            </td>
                            <td>
                                <?php
                                //注意这里的时区问题，接口返回时间是美国时间，这里临时将时区设为美国洛杉矶
                                $tz = date_default_timezone_get();
                                date_default_timezone_set('America/Los_Angeles');
                                $buyer_accept_goods_last_time    = strtotime($info['info']['buyer_accept_goods_end_time']) - time();
                                $buyer_accept_goods_end_time_str = strtotime($info['info']['buyer_accept_goods_end_time']) * 1000;
                                date_default_timezone_set($tz);
                                //获取订单的发货时间，如果为空，则获取订单创建时间
                                $shippedDate           = (!empty($info['info']['shipped_date']) && $info['info']['shipped_date'] != '0000-00-00 00:00:00') ? $info['info']['shipped_date'] : $info['info']['created_time'];
                                $accept_goods_last_day = floor((strtotime("+120 day", strtotime($shippedDate)) - time()) / (3600 * 24));
                                $accept_goods_last_day = ($accept_goods_last_day > 0) ? $accept_goods_last_day : 0;
                                ?>
                                <span class="glyphicon glyphicon-time" style="color:#f17838;font-weight:bold;"></span>
                                <?php if ($buyer_accept_goods_last_time > 0) { ?>
                                    <span class="accept_goods_last_time" style="color:#f17838;font-weight:bold;"
                                          data-endtime="<?php echo !empty($buyer_accept_goods_end_time_str) ? $buyer_accept_goods_end_time_str : ''; ?>">
                                                <?php echo VHelper::sec2string($buyer_accept_goods_last_time); ?>
                                            </span>
                                    <a href="#" id="showExtendAcceptGoodsTime"
                                       data-lastday="<?php echo !empty($accept_goods_last_day) ? $accept_goods_last_day : 0; ?>">延长收货时间</a>
                                <?php } else { ?>
                                    <span style="color:#f17838;font-weight:bold;">0 天</span>
                                <?php } ?>
                            </td>
                        <?php } ?>

                        <td style="text-align: right;">退货跟进</td>
                        <td>
                            <?php
                            //显示退款 退货 国内退件 海外退件
                            $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder($info['info']['platform_code'], $info['info']['order_id']);
                            if ($aftersaleinfo) {
                                $res = AfterSalesOrder::getAfterSalesOrderByOrderId($info['info']['order_id'], $info['info']['platform_code']);
                                if (!empty($res['domestic_return'])) {
                                    $domestic_return = '退货跟进';
                                    if ($res['domestic_return']['state'] == 1) {
                                        $state = '未处理';
                                    } elseif ($res['domestic_return']['state'] == 2) {
                                        $state = '无需处理';
                                    } elseif ($res['domestic_return']['state'] == 3) {
                                        $state = '已处理';
                                    } elseif ($res['domestic_return']['state'] == 4){
                                        $state = '驳回EPR';
                                    } else {
                                        $state = '暂不处理';
                                    }
                                    //状态：1、未处理，2、无需处理，3、已处理，4、驳回EPR
                                    $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                                        $res['domestic_return']['return_number'] . '&platform_code=' . $info['info']['platform_code'] . '" >' .
                                        $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a><br>';
                                } else {
                                    $domestic_return = '<span class="label label-default">无</span>';
                                }
                                $after_sale_text = '';
                                if (!empty($domestic_return)) {
                                    $after_sale_text .= $domestic_return;
                                }

                                echo $after_sale_text;
                            } else {
                                $res = AfterSalesOrder::getAfterSalesOrderByOrderId($info['info']['order_id'], $info['info']['platform_code']);

                                if (!empty($res['domestic_return'])) {
                                    $domestic_return = '退货跟进';
                                    if ($res['domestic_return']['state'] == 1) {
                                        $state = '未处理';
                                    } elseif ($res['domestic_return']['state'] == 2) {
                                        $state = '无需处理';
                                    } elseif ($res['domestic_return']['state'] == 3) {
                                        $state = '已处理';
                                    } elseif ($res['domestic_return']['state'] == 4){
                                        $state = '驳回EPR';
                                    } else {
                                        $state = '暂不处理';
                                    }

                                    //状态：1、未处理，2、无需处理，3、已处理，4、驳回EPR
                                    $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                                        $res['domestic_return']['return_number'] . '&platform_code=' . $info['info']['platform_code'] . '" >' .
                                        $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a><br>';


                                    echo $domestic_return;
                                } else {
                                    echo '<span class="label label-success">无</span>';
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right;">发货类型</td>
                        <td><?php echo $info['info']['amazon_fulfill_channel']; ?></td>
                        <td style="text-align: right;">送货地址</td>
                        <td colspan="1">
                            <?php echo $info['info']['ship_name']; ?>
                            (tel:<?php echo $info['info']['ship_phone']; ?>)<br>
                            <?php echo $info['info']['ship_street1'] . ',' . ($info['info']['ship_street2'] == '' ? '' : $info['info']['ship_street2'] . ',') . $info['info']['ship_city_name']; ?>
                            ,
                            <?php echo $info['info']['ship_stateorprovince']; ?>,
                            <?php echo $info['info']['ship_zip']; ?>,<br/>
                            <?php echo $info['info']['ship_country_name']; ?>
                            <?php if ($is_return == 1) { ?>
                                <br/>
                                <a href="javascript:void(0)" id="address-edit-button">编辑发货地址</a>
                            <?php } else { ?>
                                <?php if (($info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) || ($info['info']['complete_status'] == 25)) { ?>
                                    <br/>
                                    <a href="javascript:void(0)" id="address-edit-button">编辑发货地址</a>
                                <?php } ?>
                            <?php } ?>

                        </td>
                         <td style="text-align: right;">仓库客诉单</td>
                         <td>  
                             
                          <?php   
                          $compaint=\app\modules\aftersales\models\ComplaintModel::find()->select('complaint_order,status')->where(['order_id'=>$info['info']['order_id']])->all();
                          if(!empty($compaint)){
                          foreach ($compaint as $key => $vo) {
                              if($vo->status==6){
                            echo  '<a _width="100%" _height="100%" class="edit-button" href='.Url::toRoute(['/aftersales/complaint/getcompain', 'complaint_order' => $vo->complaint_order]).' >'.$vo->complaint_order.'(已处理)</a>';        
                              }else{
                               echo  '<a _width="100%" _height="100%" class="edit-button" href='.Url::toRoute(['/aftersales/complaint/getcompain', 'complaint_order' => $vo->complaint_order]).' >'.$vo->complaint_order.'(未处理)</a>';     
                              }              
                          }               
                          }else{
                               echo '<span class="label label-success">无</span>';
                          }                    
                            ?>  
                         </td>

                    </tr>
                    <?php if (Platform::PLATFORM_CODE_EB == 'EB'): ?>
                        <tr>
                            <td>客户留言</td>
                            <td colspan="3">
                                <?php if(!empty($info['note'])) {
                                    echo $info['note']['note'];
                                    if($info['note']['status'] == 1){
                                        echo '<span class="label label-success">留言已读</span>';
                                    }else{ ?>
                                        <a id="markBtn" style="color:red" href="javascript:void(0)" onclick="markProcessed('<?php echo $info['info']['platform_code'];?>','<?php echo $info['info']['order_id'];?>','<?php echo $info['info']['system_order_id'];?>')">标记为已处理</a>
                                    <?php } ?>
                                <?php }?>
                            </td>
                            <td style="float: right;">退货编码</td>
                            <td>
                                <?php  
                                $code=\app\modules\aftersales\models\AfterRefundCode::find()->where(['order_id'=>$info['info']['order_id']])->asArray()->one();
                                if(!empty($code)){ ?>
                                <span style="color: #0087ff;"><?php echo $code['refund_code']; ?></span>
                               <?php }else{ ?>
                                <span class="cik">点击获取</span>
                                <span class="code" style="color: #0087ff;"></span>
                               <?php } ?>
                            </td>
                        </tr>

                        <tr>
                            <td id='remarkTable' colspan="6">
                                <?php if (!empty($info['remark'])): ?>
                                    <table class="table table-striped" style="width:100%;">
                                        <?php foreach ($info['remark'] as $key => $value): ?>
                                            <tr style="color:#FF6347;">
                                                <td style="width:60%;"><?php echo nl2br(strip_tags($value['remark'])); ?></td>
                                                <td><?= $value['create_user'] ?></td>
                                                <td><?= $value['create_time'] ?></td>
                                                <td><a href="javascript:;"
                                                       onclick="removeRemark(<?php echo $value['id']; ?>)">删除</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                <?php endif; ?>

                            </td>

                        </tr>
                        <tr>
                            <input type="hidden" class="platform_code"
                                   value="<?php echo $info['info']['platform_code'] ?>">
                            <td>订单备注</td>
                            <td><textarea style="width:360px;height:80px;" class="remark"></textarea>
                                <button onclick=saveRemark("<?php echo $info['info']['order_id']; ?>")>添加备注</button>
                                <input class="detail_order_id" type="hidden"
                                       value="<?php echo $info['info']['order_id']; ?>"/>
                            </td>
                            <td>出货备注</td>
                            <td><textarea style="width:360px;height:80px;"
                                          class="print_remark"><?php echo $info['info']['print_remark'] ?></textarea>
                                <button onclick=save_print_remark("<?php echo $info['info']['order_id']; ?>")>添加发货备注
                                </button>
                                <input class="detail_order_id" type="hidden"
                                       value="<?php echo $info['info']['order_id']; ?>"/>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr id="address_form_row" style="display:none;">
                        <td colspan="6">
                            <form class="form-horizontal" action="<?php echo Url::toRoute(['/orders/order/editaddress',
                                'platform'     => $info['info']['platform_code'],
                                'order_id'     => $info['info']['order_id'],
                                'is_return'    => $is_return,
                                'returnid'     => $returnid,
                                'track_number' => $track_number
                            ]); ?>" role="form" action="">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="buyer_id" class="col-sm-3 control-label required">买家id<span
                                                        class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" name="buyer_id"
                                                       value="<?php echo $info['info']['buyer_id']; ?>"
                                                       class="form-control" id="buyer_id">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="email" class="col-sm-3 control-label">email<span
                                                        class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" name="email"
                                                       value="<?php echo $info['info']['email']; ?>"
                                                       class="form-control" id="email">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="ship_name" class="col-sm-3 control-label required">收件人<span
                                                        class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" name="ship_name"
                                                       value="<?php echo $info['info']['ship_name']; ?>"
                                                       class="form-control" id="ship_name">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="ship_street1" class="col-sm-3 control-label">地址1<span
                                                        class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" name="ship_street1"
                                                       value="<?php echo $info['info']['ship_street1']; ?>"
                                                       class="form-control" id="ship_street1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="ship_street2"
                                                   class="col-sm-3 control-label required">地址2</label>
                                            <div class="col-sm-9">
                                                <input type="text" value="<?php echo $info['info']['ship_street2']; ?>"
                                                       name="ship_street2" class="form-control" id="ship_street2">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="ship_city_name" class="col-sm-3 control-label">城市<span
                                                        class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text"
                                                       value="<?php echo $info['info']['ship_city_name']; ?>"
                                                       name="ship_city_name" class="form-control" id="ship_city_name">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="ship_stateorprovince" class="col-sm-3 control-label">省/州</label>
                                            <div class="col-sm-9">
                                                <input type="text"
                                                       value="<?php echo $info['info']['ship_stateorprovince']; ?>"
                                                       name="ship_stateorprovince" class="form-control"
                                                       id="ship_stateorprovince">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="ship_country" class="col-sm-3 control-label">国家<span
                                                        class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="ship_country" id="ship_country" class="form-control">
                                                    <option value="">选择国家</option>
                                                    <?php
                                                    if (is_array($countries) && !empty($countries)) {
                                                        foreach ($countries as $code => $name) { ?>
                                                            <option<?php echo trim($info['info']['ship_country_name']) == trim($name) || trim($info['info']['ship_country_name']) == trim($code) ? ' selected="selected"' : ''; ?>
                                                                    value="<?php echo $code . '&' . $name; ?>"><?php echo $name; ?></option>
                                                        <?php }
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="ship_zip" class="col-sm-3 control-label">邮编<span
                                                        class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" value="<?php echo $info['info']['ship_zip']; ?>"
                                                       name="ship_zip" class="form-control" id="ship_zip">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="ship_phone" class="col-sm-3 control-label">电话</label>
                                            <div class="col-sm-9">
                                                <input type="text" value="<?php echo $info['info']['ship_phone']; ?>"
                                                       name="ship_phone" class="form-control" id="ship_phone">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="popup-footer">
                                    <button class="btn btn-primary ajax-submit" type="button">保存</button>
                                    <button class="btn btn-default" id="address-cancel-button" type="button">取消</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td colspan="2" align="center">没有找到信息！</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade in" id="extendAcceptGoodsTimeModal" tabindex="-1" role="dialog"
     aria-labelledby="extendAcceptGoodsTimeModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">延长收货时间</h4>
            </div>
            <div class="modal-body">
                <form id="extendAcceptGoodsTimeForm" class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <span>为防止货物在运输途中的突发因素，导致买家不能及时收到货物，您可以适当延长买家收货时间。</span>
                        </div>
                    </div>
                    <div class="form-group" style="color:red;">
                        <label class="col-sm-5 control-label">剩余可延长时间(仅供参考)：</label>
                        <div class="col-sm-7">
                            <p class="form-control-static" id="last_extend_day_label"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-5 control-label">延长买家收货确认时间：</label>
                        <div class="col-sm-7">
                            <div class="input-group">
                                <input type="text" class="form-control" name="day">
                                <div class="input-group-addon">天</div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="last_extend_day" value="0">
                    <input type="hidden" name="platform_order_id"
                           value="<?php echo !empty($info['info']['platform_order_id']) ? $info['info']['platform_order_id'] : ''; ?>">
                    <input type="hidden" name="platform_code"
                           value="<?php echo !empty($info['info']['platform_code']) ? $info['info']['platform_code'] : ''; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="extendAcceptGoodsTimeBtn">确认</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!--联系买家-->
<div id="contact_buyer" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="custom-width-modalLabel"
     aria-hidden="false" style="display: none; padding-right: 17px;">
    <div class="modal-dialog" style="width:55%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="custom-width-modalLabel">发送邮件</h4>
            </div>
            <div class="col-md-12" style="margin-top:15px; margin-bottom: 15px;">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-12">
                                <!--<form class="form-horizontal" action="#" novalidate="">-->
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">发件人</label>
                                        <div class="col-md-11">
                                            <input id="sender_email" type="text" class="form-control" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">收件人</label>
                                        <div class="col-md-11">
                                            <input id="recipient_email" type="text" class="form-control" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">主题</label>
                                        <div class="col-md-11">
                                            <input id="title" type="text" class="form-control" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">内容</label>
                                        <div class="col-md-11">
                                            <script id="content" name="content" type="text/plain"></script>
                                            <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.config.js"></script>
                                            <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.all.js"></script>
                                            <script type="text/javascript">
                                                var ue = UE.getEditor('content', {
                                                    zIndex: 6600,
                                                    initialFrameHeight: 200
                                                });
                                            </script>
                                        </div>
                                    </div>
                                </div>
                                <!--</form>-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">取消</button>
                <button type="button" class="btn send btn-primary waves-effect waves-light">发送</button>
            </div>
        </div>
    </div>
</div>
                <?php /*  获取国家信息*/
                if ($info['info']) {
                    $countryList = \app\modules\systems\models\Country::getCodeNamePairsList('en_name');

                    if ($info['info']['real_ship_code']) {
                        $logistic = \app\modules\orders\models\Logistic:: getSendWayEng($info['info']['real_ship_code']);
                        if (empty($logistic)) {
                            $logistic = \app\modules\orders\models\Logistic:: getSendWayEng($info['info']['ship_code']);
                        }
                    } else {
                        $logistic = '';
                    }
                    if ($info['info']['track_number']) {
                        $track        = 'http://www.17track.net/zh-cn/track?nums=' . $info['info']['track_number'];
                        $track_number = $info['info']['track_number'];
                    } else {
                        $track        = '';
                        $track_number = '';
                    }
                    if ($info['info']['buyer_id']) {
                        $buyer_id = $info['info']['buyer_id'];
                    } else {
                        $buyer_id = '';
                    }
                    if ($info['info']['ship_country']) {
                        $country      = $info['info']['ship_country'];
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
                } ?>
<!--速卖通单个发生站内信-->
<div class="modal fade in" id="sendMsgModal" tabindex="-1" role="dialog" aria-labelledby="sendMsgModalLabel"
     style="top:200px;">
    <div class="modal-dialog" role="document" style="width:35%;height: 55%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">发送消息</h4>
            </div>
            <div class="modal-body">
                <form id="sendMsgForm">
                    <div>
                        <select id="countDataType" class="form-control"
                                style="width:100%;height:30px;padding: 2px 5px;">
                            <option value="all">选择绑定参数</option>
                            <option value="<?php echo $buyer_id; ?>">客户ID</option>
                            <option value="<?php echo $track_number; ?>">跟踪号</option>
                            <option value="<?php echo $logistic; ?>">发货方式</option>
                            <option value="<?php echo $track; ?>">查询网址</option>
                            <option value="<?php echo $ship_country ?>">国家</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col col-lg-12">
                            <textarea class="form-control" rows="5" id="smt_msg" name="msg"></textarea>
                            <input type="hidden" name="orderId" value="">
                            <input type="hidden" name="buyerUserId" value="">
                            <input type="hidden" name="accountId" value="">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="sendMsgBtn">发送</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<script>
    $('a#address-edit-button').click(function () {
        $('tr#address_form_row').show();
    });
    $('button#address-cancel-button').click(function () {
        $('tr#address_form_row').hide();
    });

   $(".cik").click(function(){
   var order_id='<?php echo $info['info']['order_id'];  ?>';
   var url='<?php echo Url::toRoute(['/aftersales/return/refundcode']); ?>'; 
                           
         $.ajax({
             url:url,
             type:"post",
             data:{'order_id':order_id},
             dataType:"json",
             success:function(data){
               if(data.state==1){
                   layer.msg(data.msg, {icon: 0});
                   $('.code').html(data.code);
                   $('.cik').hide();
               }  
             },
             error:function(e){
                layer.msg("系统繁忙,请稍后再试", {icon: 0});
             }
         }); 
   });
    function markProcessed(platformCode, orderId, systemOrderId){

        var url = '<?php echo Url::toRoute(['/orders/order/markprocessed']); ?>';
        $.get(url, {'order_id': orderId, 'platform_code': platformCode}, function(data){
            if (data.status != '200'){
                layer.msg("留言标记失败", {icon: 5});
            }
            else
            {
                layer.msg("留言标记成功", {icon: 1}, function () {
                    document.location.reload();//刷新当前页面
                });
            }
        },'json');
    }

    //订单备注
    function saveRemark(orderId) {
        var remark = $('.remark').val();
        if (remark.length <= 0) {
            layer.msg('请添加订单备注信息');
            return false;
        }
        var url = '<?php echo Url::toRoute(['/orders/order/addremark']); ?>';
        $.post(url, {'order_id': orderId, 'remark': remark}, function (data) {
            if (data.ack != true)
                alert(data.message);
            else {
                var info = data.info;
                var html = '<table class="table table-striped" style="width:100%;"><tbody>';
                for (var i in info) {
                    html += '<tr style="color:#FF6347;">' + "\n" +
                        '<td style="width:60%;">' + info[i].remark.replace(/\n/g, "<br>") + '</td>' + "\n" +
                        '<td>' + info[i].create_user + '</td>' + "\n" +
                        '<td>' + info[i].create_time + '</td>' + "\n" +
                        '<td><a href="javascript:void(0)" onclick="removeRemark(' + info[i].id + ')">删除</a></td>' + "\n" +
                        '</tr>' + "\n";
                }
                html += '</tbody></table>';
                $('#remarkTable').empty().html(html);
            }
        }, 'json');
    }

    //删除订单备注
    function removeRemark(id) {
        var url = '<?php echo Url::toRoute(['/orders/order/removeremark']); ?>';
        $.get(url, {id: id}, function (data) {
            if (data.ack != true)
                alert(data.message);
            else {
                var info = data.info;
                var html = '<table class="table table-striped" style="width:100%;"><tbody>';
                for (var i in info) {
                    html += '<tr style="color:#FF6347;">' + "\n" +
                        '<td style="width:60%;">' + info[i].remark.replace(/\n/g, "<br>") + '</td>' + "\n" +
                        '<td>' + info[i].create_user + '</td>' + "\n" +
                        '<td>' + info[i].create_time + '</td>' + "\n" +
                        '<td><a href="javascript:void(0)" onclick="removeRemark(' + info[i].id + ')">删除</a></td>' + "\n" +
                        '</tr>' + "\n";
                }
                html += '</tbody></table>';
                $('#remarkTable').empty().html(html);
            }
        }, 'json');
    }

    //添加出货备注
    function save_print_remark(orderId) {
        var url = '<?php echo Url::toRoute(['/orders/order/addprintremark']);?>';
        var platform = $('.platform_code').val();
        var print_remark = $('.print_remark').val();
        if (print_remark.length <= 0) {
            layer.msg('请输入出货备注!');
            return false;
        }
        $.post(url, {'order_id': orderId, 'platform': platform, 'print_remark': print_remark}, function (data) {
            alert(data.info);
        }, 'json');
    }

    //添加黑名单操作
    $(document).on("click", ".addBlackList", function () {
        var _this = $(this);
        layer.confirm('您确定要将当前用户加入黑名单？', {
            btn: ['确定', '暂且放他一马']
        }, function (index) {
            var buyer_id = _this.attr("data-buyerid");
            var platform_code = _this.attr("data-platformcode");

            if (buyer_id.length == 0) {
                layer.msg("买家ID不能为空");
                return false;
            }

            $.post("<?php echo Url::toRoute(['/systems/gbc/addblacklist']) ?>", {
                "buyer_id" : buyer_id,
                "platform_code" : platform_code,
                "type" : 1,
                "account_type": 2
            }, function (data) {
                if (data["code"] == 1) {
                    layer.msg("添加黑名单成功", {icon: 1});
                    $("#blackinfo").html("<a class='cancelBlackList' href='javascript:void(0);' style='color:blue;' data-buyerid='" + buyer_id + "' data-platformcode='" + platform_code + "'>取消黑名单</a>");
                } else {
                    layer.msg("添加黑名单失败", {icon: 5});
                }
            }, "json");

            layer.close(index);
        }, function () {

        });
        return false;
    });

    //取消黑名单操作
    $(document).on("click", ".cancelBlackList", function() {
        var buyer_id = $(this).attr("data-buyerid");
        var platform_code = $(this).attr("data-platformcode");

        if (buyer_id.length == 0) {
            layer.msg("买家ID不能为空");
            return false;
        }

        $.post("<?php echo Url::toRoute(['/systems/gbc/cancelblacklist']) ?>", {
            "buyer_id" : buyer_id,
            "platform_code" : platform_code,
            "type" : 1,
            "account_type": 2
        }, function (data) {
            if (data["code"] == 1) {
                layer.msg("取消黑名单成功", {icon: 1});
                $("#blackinfo").html("<a class='addBlackList' href='javascript:void(0);' style='color:blue;' data-buyerid='" + buyer_id + "' data-platformcode='" + platform_code + "'>添加黑名单</a>");
            } else {
                layer.msg("取消黑名单失败", {icon: 5});
            }
        }, "json");
        return false;
    });

    $(function () {
        $("#showExtendAcceptGoodsTime").on("click", function () {
            var lastday = $(this).attr("data-lastday");
            $("#last_extend_day_label").text(lastday + " 天");
            $("#extendAcceptGoodsTimeForm input[name='last_extend_day']").val(lastday);
            $("#extendAcceptGoodsTimeModal").modal("show");
            return false;
        });

        $("#extendAcceptGoodsTimeBtn").on("click", function () {
            var lastday = parseInt($("#extendAcceptGoodsTimeForm input[name='last_extend_day']").val());
            var day = parseInt($("#extendAcceptGoodsTimeForm input[name='day']").val());

            if (day > lastday) {
                layer.alert("延长天数不能大于剩余可延长天数");
                return false;
            }

            var params = $("#extendAcceptGoodsTimeForm").serialize();
            $.post("<?php echo Url::toRoute('/orders/order/extendacceptgoodstime') ?>", params, function (data) {
                if (data["code"] == 1) {
                    layer.alert("延长收货时间成功");
                    $("#extendAcceptGoodsTimeModal").modal("hide");
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
            return false;
        });

        $("#extendAcceptGoodsTimeModal").on('hidden.bs.modal', function (e) {
            $("#extendAcceptGoodsTimeForm")[0].reset();
            $("#extendAcceptGoodsTimeForm input[name='last_extend_day']").val(0);
        });

        //剩余收货时间
        function flushAcceptGoodsLastTime() {
            $("span.accept_goods_last_time").each(function () {
                var end_time = $(this).attr("data-endtime");
                if (end_time && end_time.length != 0) {
                    //结束时间
                    var end = new Date();
                    end.setTime(end_time);
                    //当前时间
                    var now = new Date();
                    //结束时间减去当前时间剩余的毫秒数
                    var leftTime = end.getTime() - now.getTime();
                    //计算剩余的天数
                    var days = parseInt(leftTime / 1000 / 60 / 60 / 24, 10);
                    //计算剩余的小时
                    var hours = parseInt(leftTime / 1000 / 60 / 60 % 24, 10);
                    //计算剩余的分钟
                    var minutes = parseInt(leftTime / 1000 / 60 % 60, 10);
                    //计算剩余的秒数
                    var seconds = parseInt(leftTime / 1000 % 60, 10);

                    days = days ? days + '天' : '';
                    hours = hours ? hours + '时' : (days && (hours || minutes || seconds) ? '0时' : '');
                    minutes = minutes ? minutes + '分' : (hours && seconds ? '0分' : '');
                    seconds = seconds ? seconds + '秒' : '';
                    $(this).text(days + hours + minutes + seconds);
                }
            });
        }

        setInterval(flushAcceptGoodsLastTime, 1000);
    });

    //打开发送消息弹窗
    $(".openSendMsgBtn").on("click", function () {
        $("#sendMsgForm input[name='orderId']").val($(this).attr("data-orderid"));
        $("#sendMsgForm input[name='buyerUserId']").val($(this).attr("data-buyeruserid"));
        $("#sendMsgForm input[name='accountId']").val($(this).attr("data-accountid"));
        $("#sendMsgModal").modal("show");
        return false;
    });
    //发送消息
    $("#sendMsgBtn").on("click", function () {
        var params = $("#sendMsgForm").serialize();
        $.post("<?php echo Url::toRoute(['/mails/aliexpresssendmsg/sendmsg']); ?>", params, function (data) {
            if (data["code"] == 1) {
                layer.alert("消息发送成功");
                $("#sendMsgModal").modal("hide");
            } else {
                layer.alert(data["message"]);
            }
        }, "json");
        return false;
    });
    //弹窗关闭时清空数据
    $('#sendMsgModal').on('hidden.bs.modal', function (e) {
        $("#sendMsgForm textarea[name='msg']").val("");
        $("#sendMsgForm input[name='orderId']").val("");
        $("#sendMsgForm input[name='buyerUserId']").val("");
        $("#sendMsgForm input[name='accountId']").val("");
    });

    //鼠标定位添加订单信息
    $("#countDataType").on("change", function () {
        var data_value = $(this).val();
        if (data_value == '') {
            layer.msg("暂无此信息", {icon: 2});
            return false;
        }
        if (data_value != 'all') {
            getValue('smt_msg', data_value);
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
</script>    