<?php

use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\orders\models\Order;
use app\common\VHelper;
use yii\helpers\Url;
use app\modules\systems\models\Gbc;
use app\modules\blacklist\models\BlackList;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\orders\models\OrderEbay;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\orders\models\OrderKefu;
use app\modules\mails\models\EbayFeedback;
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne"><h4 class="panel-title">订单信息</h4></a>
    </div>
    <div id="collapseOne" class="panel-collapse collapse in">
        <div class="panel-body">
            <table class="table">
                <tbody>
                    <?php if (!empty($info['info'])) { ?>
                        <?php
                        $account_info = \app\modules\accounts\models\Account::getHistoryAccountInfo($info['info']['account_id'], $info['info']['platform_code']);                  
                        if ($info['info']['platform_code'] == "EB") {
                            $eb_site = \app\modules\orders\models\OrderEbay::getSiteByPlatformId($info['info']['platform_order_id']);
                        } elseif ($info['info']['platform_code'] == 'AMAZON') {
                            $eb_site = \app\modules\orders\models\OrderAmazonKefu::getSiteByPlatformId($info['info']['platform_order_id']);
                        } elseif ($info['info']['platform_code'] == 'ALI') {
                            $eb_site = \app\modules\orders\models\OrderAliexpressKefu::getSiteByPlatformId($info['info']['platform_order_id']);
                        } elseif ($info['info']['platform_code'] == 'WISH') {
                            $eb_site = \app\modules\orders\models\OrderAliexpressKefu::getSiteByPlatformId($info['info']['platform_order_id']);
                        } else {
                            $eb_site = \app\modules\orders\models\OrderOtherKefu::getSiteByPlatformId($info['info']['platform_order_id']);
                        }
                        ?>
                        <tr>
                            <td style="text-align: right;">平台</td>
                            <td><?php echo $info['info']['platform_code']; ?></td>
                            <td style="text-align: right;">订单号</td>
                            <td><?php echo isset($account_info->account_short_name) ? $account_info->account_short_name . '-' . $info['info']['order_id'] : $info['info']['order_id']; ?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;">平台订单号</td>
                            <td><?php echo $info['info']['platform_order_id']; ?></td>
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
                        </tr>
                        <tr>
                            <td style="text-align: right;">下单时间</td>
                            <td><?php echo $info['info']['created_time']; ?></td>
                            <td style="text-align: right;">付款时间</td>
                            <td><?php echo $info['info']['paytime']; ?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;">运费</td>
                            <td><?php echo $info['info']['ship_cost'] . '(' . $info['info']['currency'] . ')'; ?></td>
                            <td style="text-align: right;">总费用</td>
                            <td><?php echo $info['info']['total_price'] . '(' . $info['info']['currency'] . ')'; ?></td>
                        </tr>
                        <tr>
                            <!--纠纷-->
                            <td style="text-align: right;">纠纷</td>
                            <td>
                                <?php
                                $cancel_cases = \app\modules\mails\models\EbayCancellations::disputeLevel($info['info']['platform_order_id']);
                                $inquiry_cases = \app\modules\mails\models\EbayInquiry::disputeLevel($info['info']['platform_order_id'], $info['info']);
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
                            <!--邮件-->
                            <td style="text-align: right;">邮件</td>
                            <td>
                                <?php
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
                                ?>
                            </td>
                            <!--评价-->
                            <td style="text-align: right;width: 46px;">评价</td>
                            <td>
                                <?php
                                $model = OrderKefu::model('order_ebay');

                                $orders = $model->where(['order_id' => $info['info']['order_id']])->one();
                                if (empty($orders)) {
                                    //查copy表数据
                                    $model = OrderKefu::model('order_ebay_copy');

                                    $orders = $model->where(['order_id' => $info['info']['order_id']])->one();
                                }
                                //获取平台订单号
                                $platform_order_id = $info['info']['platform_order_id'];
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
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right;">账号</td>
                            <td><?php echo $account_info->account_name; ?></td>
                            <td style="text-align: right;">送货地址</td>
                            <td><?php echo $info['info']['ship_name']; ?>
                                (tel:<?php echo $info['info']['ship_phone']; ?>)<br>
                                <?php echo $info['info']['ship_street1'] . ',' . ($info['info']['ship_street2'] == '' ? '' : $info['info']['ship_street2'] . ',') . $info['info']['ship_city_name']; ?>
                                ,
                                <?php echo $info['info']['ship_stateorprovince']; ?>,
                                <?php echo $info['info']['ship_zip']; ?>,<br/>
                                <?php echo $info['info']['ship_country_name']; ?>
                            </td>
                        </tr>
                        <?php if (Platform::PLATFORM_CODE_EB == 'EB'): ?>
                            <tr>
                                <td style="text-align: right;">订单状态:</td>
                                <td>
                                    <?php
                                    $complete_status = Order::getOrderCompleteStatus();
                                    echo $complete_status[$info['info']['complete_status']];
                                    echo '(订单类型: ' . VHelper::getOrderType($info['info']['order_type']) . ')';
                                    echo '退款状态:' . VHelper::refundStatus($info['info']['refund_status']);
                                    ?>
                                </td>
                                <td style="text-align: right;">客户email</td>
                                <td>
                                    <?php echo $info['info']['email']; ?>&nbsp;&nbsp;<a _width="100%" _height="100%"
                                                   class="edit-button"
                                                   href="/mails/ebayreply/initiativeadd?order_id=<?php echo $info['info']['order_id']; ?>&platform=EB">发送消息</a>
                                                   <?php
                                                   if (!empty($info['product'])) {
                                                       $feed_result = '';
                                                       foreach ($info['product'] as $product) {
                                                           if (isset($product['feed_table_id']))
                                                               $feed_result .= '&nbsp;&nbsp;<a href="/mails/ebayfeedbackresponse/add?type=Reply&id=' . $product['feed_table_id'] . '" class="edit-button" id="status" _width="90%" _height="90%">' . \app\modules\mails\models\EbayFeedback::$commentTypeMap[$product['comment_type']] . '</a>';
                                                       }
                                                       if (!empty($feed_result))
                                                           echo '&nbsp;&nbsp;评价状态：' . $feed_result;
                                                   }
                                                   ?>
                                </td>

                                            <!--                                <td>退款状态: <?php echo VHelper::refundStatus($info['info']['refund_status']); ?></td>-->
                            </tr>
                            <tr>
                                <td style="text-align: right;">站点</td>
                                <td ><?= $eb_site ?></td>
                                <td style="text-align: right;width: 74px">退货跟进</td>
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
                                            } else {
                                                $state = '驳回EPR';
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
                                            } else {
                                                $state = '驳回EPR';
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
                                <?php
                                switch ($info['info']['order_type']) {
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
                                ?>

                                <?php if (!empty($rela_order_name)) { ?>
                                    <td style="text-align: right;"><?= $rela_order_name; ?></td>
                                    <td><?php
                                        if ($rela_is_arr && isset($info['info']['son_order_id'])) {
                                            foreach ($info['info']['son_order_id'] as $son_order_id) {
                                                echo '<a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=EB&system_order_id=' . $son_order_id . '" title="订单信息">
                                                ' . $son_order_id . '</a>';
                                            }
                                        } else {
                                            echo '<a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?platform=EB&system_order_id=' . $info['info']['parent_order_id'] . '" title="订单信息">
                                                ' . $info['info']['parent_order_id'] . '</a>';
                                        }
                                        ?></td>
                                <?php } else { ?>
                                    <td colspan="2"></td>
                                <?php } ?>
                                <td style="text-align: right;">客户留言</td>
                                <td colspan="3" style="width: 100px"><?php
                                    if (!empty($info['note'])) {
                                        echo $info['note']['note'];
                                    }
                                    ?></td>
                            </tr>
                            <tr>
                                <td id='remarkTable' colspan="4">
                                    <?php if (!empty($info['remark'])) { ?>
                                        <table style="width:100%;">
                                            <?php foreach ($info['remark'] as $key => $value) { ?>
                                                <tr style="color:#FF6347;">
                                                    <td style="width:80%;"><?php echo nl2br(strip_tags($value['remark'])); ?></td>
                                                    <td><?= $value['create_user'] ?></td>
                                                    <td><?= $value['create_time'] ?></td>
                                                    <td><a href="javascript:;"
                                                           onclick="removeRemark(<?php echo $value['id']; ?>)">删除</a></td>
                                                </tr>
                                            <?php } ?>
                                        </table>
                                    <?php } ?>

                                </td>

                            </tr>
                            <tr>
                        <input type="hidden" class="platform_code"
                               value="<?php echo $info['info']['platform_code'] ?>">
                        <td style="text-align: right;">订单备注</td>
                        <td><textarea style="width:45%;height:60px;" class="remark"></textarea>
                            <span class="btn btn-default" onclick=saveRemark("<?php echo $info['info']['order_id']; ?>")>添加备注</span>
                            <input class="detail_order_id" type="hidden"
                                   value="<?php echo $info['info']['order_id']; ?>"/>
                        </td>
                        <td style="text-align: right;">出货备注</td>
                        <td><textarea style="width:45%;height:60px;"
                                      class="print_remark"><?php echo $info['info']['print_remark'] ?></textarea>
                            <span class="btn btn-default" onclick=save_print_remark("<?php echo $info['info']['order_id']; ?>")>添加发货备注
                            </span>
                            <input class="detail_order_id" type="hidden"
                                   value="<?php echo $info['info']['order_id']; ?>"/>
                        </td>
                        </tr>
                    <?php endif; ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4" align="center">没有找到信息！</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
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
    var html = '<table style="width:100%;"><tbody>';
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
    console.log(id);
    var url = '<?php echo Url::toRoute(['/orders/order/removeremark']); ?>';
    $.get(url, {id: id}, function (data) {
    if (data.ack != true)
            alert(data.message);
    else {
    var info = data.info;
    var html = '<table style="width:100%;"><tbody>';
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
    var print_remark = $('.print_remark').val();
    if (print_remark.length <= 0) {
    layer.msg('请添加输入备注!');
    return false;
    }
    var url = '<?php echo Url::toRoute(['/orders/order/addprintremark']); ?>';
    var platform = $('.platform_code').val();
    $.post(url, {'order_id': orderId, 'platform': platform, 'print_remark': print_remark}, function (data) {
    alert(data.info);
    }, 'json');
    }


    //设置黑名单操作
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
</script>