<?php

use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use app\components\LinkPager;
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\mails\models\EbayInquiry;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\orders\models\Logistic;
use app\modules\accounts\models\Account;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonReviewMessageData;
use app\modules\mails\models\AliexpressEvaluate;
use app\modules\mails\models\AliexpressEvaluateList;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\OrderEbay;
use app\modules\orders\models\OrderRemark;
use app\modules\orders\models\Order;
use app\modules\aftersales\models\AfterSalesRefund;

$this->title = '退件跟进';
?>
<style>
    .select2-container--krajee {
        min-width: 155px !important;
    }
</style>
<style>
    #addReplyFeedback {
        margin: 20px auto 0 auto;
        width: 90%;
        height: auto;
        border-collapse: collapse;
    }

    #addReplyFeedback td {
        border: 1px solid #ccc;
        padding: 10px;
    }

    #addReplyFeedback td.col1 {
        width: 120px;
        text-align: right;
        font-weight: bold;
    }

    #addReplyFeedback .glyphicon.glyphicon-star {
        color: #ff9900;
        font-size: 20px;
    }
    .remark{cursor:pointer }
    .remark:hover {text-decoration:none;}
    p {
        margin: 0 0 0px;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-24">
            <div class="well">
                <form id="search-form" class="form-horizontal" action="<?php echo \Yii::$app->request->getUrl(); ?>"
                      method="get" role="form">
                    <input type="hidden" name="sortBy" value="">
                    <input type="hidden" name="sortOrder" value="">
                    <ul class="list-inline">
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">所属平台</label>
                                <div class="col-lg-7">
                                    <select class="form-control" id="platform_code" name="platform_code">
                                        <?php foreach ($platformList as $code => $value) { ?>
                                            <option value="<?php echo $code; ?>" <?php if ($code == $platform_code) echo 'selected="selected"'; ?>><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">账号</label>
                                <div class="col-lg-7">
                                    <?php
                                    echo Select2::widget([
                                        'name' => 'account_id',
                                        'id' => 'account_id',
                                        'data' => $ImportPeople_list,
                                        'value' => $account_id,
                                        'options' => [
                                            'placeholder' => '--请输入--',
                                        ],
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">对应订单号</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="order_id" style="width:150px"
                                           value="<?php echo $order_id; ?>">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">跟踪号</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="trackno" style="width:150px"
                                           value="<?php echo $trackno; ?>">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">买家ID</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="buyer_id" style="width:150px"
                                           value="<?php echo $buyer_id; ?>">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">退货类型</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="return_type">
                                        <option value="">全部</option>
                                        <?php foreach ($returntype as $code => $value) { ?>
                                            <option value="<?php echo $code; ?>" <?php if ($code == $return_type) echo 'selected="selected"'; ?>><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>

                        <li>
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">退货来源</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="source">
                                        <option value="">全部</option>
                                        <option <?php
                                        if ($source == 1) {
                                            echo 'selected="selected"';
                                        }
                                        ?> value="1">国内退件
                                        </option>
                                        <option <?php
                                        if ($source == 2) {
                                            echo 'selected="selected"';
                                        }
                                        ?>value="2">海外退件
                                        </option>

                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">状态</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="state">
                                        <option value="">全部</option>
                                        <?php foreach ($statestring as $code => $value) { ?>
                                            <option value="<?php echo $code; ?>" <?php if ($code == $state) echo 'selected="selected"'; ?>><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">处理类型</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="handle_type">
                                        <option value="">全部</option>
                                        <?php foreach ($handle as $code => $value) { ?>
                                            <option value="<?php echo $code; ?>" <?php if ($code == $handle_type) echo 'selected="selected"'; ?>><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>

                        <li style="margin-left: -40px;">
                            <div class="form-group" style="width:400px">
                                <label style="width:150px" class="control-label col-lg-5" for="">处理时间</label>
                                <?php
                                echo DateTimePicker::widget([
                                    'name' => 'start_date',
                                    'options' => ['placeholder' => ''],
                                    'value' => $start_date,
                                    'pluginOptions' => [
                                        'autoclose' => true,
                                        'format' => 'yyyy-mm-dd hh:ii:ss',
                                        'todayHighlight' => true,
                                        'todayBtn' => 'linked',
                                    ],
                                ]);
                                ?>
                            </div>
                        </li>
                        <li style="margin-left: -30px;">
                            <div class="form-group" style="width:300px">
                                <?php
                                echo DateTimePicker::widget([
                                    'name' => 'end_date',
                                    'options' => ['placeholder' => ''],
                                    'value' => $end_date,
                                    'pluginOptions' => [
                                        'autoclose' => true,
                                        'format' => 'yyyy-mm-dd hh:ii:ss',
                                        'todayHighlight' => true,
                                    ]
                                ]);
                                ?>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">退件单号</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="return_number" style="width:150px"
                                           value="<?php echo $return_number; ?>">
                                </div>
                            </div>
                        </li>
                    </ul>
                    <button type="submit" class="btn btn-primary">搜索</button>
                </form>
            </div>
        </div>
        <div class="bs-bars pull-left" style="padding-top: 7px;">
            共<?php
            if ($count) {
                echo $count;
            } else {
                echo 0;
            };
            ?>条数据&nbsp;
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class="btn btn-success" id="download">下载数据</a>
            </div>
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class="btn btn-default" id="temporary">暂扣订单</a>
            </div>
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class=" btn btn-default" id="perpetual">永久作废</a>
            </div>
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class="btn btn-default" id="handle">暂不处理</a>
            </div>
        </div>
        <table class="table table-striped table-bordered">

            <tr>
                <td><input type="checkbox" id="all" class="all"></td>
                <td style="width:210px">对应订单号<br/>平台\订单状态\发货状态<br/>退件单号</td>
                <td>订单备注</td>
                <!--<td>订单状态</td>-->
                <td>退款状态</td>
                <!--<td>退件单号</td>-->
                <td style="width:150px">付款时间<br/>订单总金额/币种<br/>买家ID</td>
                <!--<td>发货状态</td>-->
                <td>跟踪号<br/>发货仓库<br/>邮寄方式</td>
                <!--<td>发货仓库</td>-->
                <!--<td>邮寄方式</td>-->
                <!--<td>订单总金额/币种</td>-->
                <!--<td>平台</td>-->
                <!--<td>买家ID</td>-->
                <td>纠纷<br/>评价</td>
                <!--<td >评价</td>-->
                <td style="width:210px">售后</td>
                <td>站内信</td>
                <td style="width:90px">退货类型<br/>退货来源<br/>状态</td>
                <!--<td>退货来源</td>-->
                <td>ERP时间/备注人/内容<?php
                    if ($platform_code == 'AMAZON') {
                        echo '/联系买家';
                    }
                    ?></td>
                <!--<td>状态</td>-->
                <td>处理类型/明细</td>
                <td>操作</td>
            </tr>

            <?php if (!empty($receipts)) { ?>
                <?php
                foreach ($receipts as $item) {
                    $erp_type = json_decode($item['erp_type']);
                    $sources = array('1' => '国内退件', '2' => '海外退件');
                    $set_store_name = isset($store_name[$item['account_id']]) ? $store_name[$item['account_id']] : '';
                    ?>
                    <tr>
                        <td>
                            <input name="check" type="checkbox" value="<?= $item['id']; ?>" class="sel ">
                        </td>
                        <td>
                            <a _width="100%" _height="100%" class="edit-button platform_order_id"
                               href="/orders/order/orderdetails?platform=<?php echo $item['platform_code']; ?>&system_order_id=<?php echo $item['order_id']; ?>&rid=<?php echo $item['id']; ?>&track_number=<?php echo $item['trackno']; ?>&is_return=1"
                               title="订单信息">
                                <?php echo $set_store_name . '--' . $item['order_id']; ?></a>
                            <br/>
                            <?= $item['platform_code'] ?>\

                            <?php
                            if (isset($item['complete_status'])) {
                                echo Order::getOrderCompleteDiffStatus($item['complete_status']);
                            }
                            ?>
                            \
                            <?php echo (isset($item['shipped_date']) && !empty($item['shipped_date'])) ? '已发货' : "未发货"; ?>
                            <br/>
                            <?= $item['return_number'] ?>
                        </td>
                        <td >
                            <?php
                            //订单备注
                            if (!empty($item['order_id'])) {
                                $remarks = OrderRemark::find()
                                        ->where(['order_id' => $item['order_id']])
                                        ->asArray()
                                        ->all();

                                if (!empty($remarks)) {
                                    $str = '';
                                    foreach ($remarks as $remark) {
                                        echo '<p>';

                                        $str .= $remark['remark'] . '<br/>';
                                    }
                                    ?>  
                                    <?php
                                    if (mb_strlen($str) > 12) {
                                        $sy = mb_substr($str, 0, 12) . '...';
                                        ?>                   
                                        <a style=" cursor: pointer;"  class='remark<?php echo $item['id']; ?>' onclick="demol(this)"><?php echo $sy; ?></a>
                                        <input type="hidden" name="remark"  class="remarkl" value="<?php echo $str; ?>"/>
                                        <?php
                                    } else {
                                        echo $str;
                                    }
                                    echo '</p>';
                                }
                            }
                            ?>

                        </td>

                        <td>
                            <?php
                            if (!empty($item['order_id'])) {
                                $refunds = AfterSalesRefund::find()
                                        ->where(['order_id' => $item['order_id']])
                                        ->asArray()
                                        ->all();
                                if (!empty($refunds)) {
                                    foreach ($refunds as $refund) {
                                        echo '<p>';
                                        echo AfterSalesRefund::getRefundStatusList($refund['refund_status']);
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                                        $refund['after_sale_id'] . '&platform_code=' . $item['platform_code'] . '" >' .
                                        $refund['after_sale_id'] . '</a>';
                                        echo '</p>';
                                    }
                                }
                            }
                            ?>
                        </td>          
                        <td>
                            <?= $item['paytime'] ?>
                            <br/>
                            <?php
                            if (!empty($item['total_price'])) {
                                echo $item['total_price'] . '/' . $item['currency'];
                            } else {
                                echo '-';
                            }
                            ?>
                            <br/>
                            <?= $item['buyer_id'] ?>
                        </td>

                        <td>
                            <?php
                            echo '<a href="https://t.17track.net/en#nums=' . $item['trackno'] . '" target="_blank" title="查看物流跟踪信息">' . $item['trackno'] . '</a>';
                            ?>
                            <br/>           
                            <?php echo isset($warehouseList[$item['warehouse_id']]) ? $warehouseList[$item['warehouse_id']] : '--' ?>
                            <br/>
                            <?php echo $logistic = Logistic:: getSendGoodsWay($item['real_ship_code']); ?>
                        </td>


                        <td>

                            <?php
                            $cancel_cases = EbayCancellations::disputeLevel($item['platform_order_id']);
                            $inquiry_cases = EbayInquiry::disputeLevel($item['platform_order_id'], $item);
                            $returns_cases = EbayReturnsRequests::disputeLevel($item['platform_order_id'], $item);
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
                                $disputeHtml = '<span>无</span>';
                            }

                            echo $disputeHtml;
                            ?>
                            <br/>
                            <?php
                            if ($platform_code == 'EB') {
                                $model = OrderKefu::model('order_ebay');
                            } elseif ($platform_code == 'ALI') {
                                $model = OrderKefu::model('order_aliexpress');
                            } elseif ($platform_code == 'AMAZON') {
                                $model = OrderKefu::model('order_amazon');
                            } elseif ($platform_code == 'WISH') {
                                $model = OrderKefu::model('order_wish');
                            } else {
                                $model = OrderKefu::model('order_other');
                            }
                            $orders = $model->where(['order_id' => $list['order_id']])->one();
                            if (empty($orders)) {
                                //查copy表数据
                                if ($platform_code == 'EB') {
                                    $model = OrderKefu::model('order_ebay_copy');
                                } elseif ($platform_code == 'ALI') {
                                    $model = OrderKefu::model('order_aliexpress_copy');
                                } elseif ($platform_code == 'AMAZON') {
                                    $model = OrderKefu::model('order_amazon_copy');
                                } elseif ($platform_code == 'WISH') {
                                    $model = OrderKefu::model('order_wish_copy');
                                } else {
                                    $model = OrderKefu::model('order_other_copy');
                                }
                                $orders = $model->where(['order_id' => $list['order_id']])->one();
                            }
                            //获取平台订单号
                            //$platform_order_id = $orders->platform_order_id;
                            $platform_order_id = $item['platform_order_id'];
                            if ($item['platform_code'] == 'EB') {
                                // 查看订单评价
                                $feedbackInfos = EbayFeedback::find()->where(['order_line_item_id' => $platform_order_id, 'role' => 1])->all();
                                // 如果没有找到订单评价，通过交易ID和item_id来查评价
                                $details = \app\modules\orders\models\OrderEbayDetail::getItemIdAndTransactionId($item['order_id']);
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
                            if ($item['platform_code'] == 'AMAZON') {
                                //获取feedback  和review
                                $amazon_feedback = AmazonFeedBack::getFindOne($platform_order_id);
                                if (!empty($amazon_feedback)) {
                                    $feedbackInfo = $amazon_feedback->rating;
                                    $comment_type = '<p><span><a style="color:red;" data-rating="' . $amazon_feedback->rating . '" data-comments="' . $amazon_feedback->comments . '" class="view_feedback" >' . $feedbackInfo . '</a></span></p>';
                                    echo 'feedback:' . $comment_type . '<br>';
                                } else {
                                    echo '<span class="label label-default">无</span><br>';
                                }
                                $amazon_review = AmazonReviewMessageData::getReviewByOrderId($platform_order_id);
                                if (!empty($amazon_review)) {
                                    $siteList = Account::getSiteList('AMAZON');
                                    $domainName = isset($siteList[$amazon_review['accountId']]) ? 'https://' . $siteList[$amazon_review['accountId']] : "";
                                    $amazon_review_info = '<p><span><a style="color:red;" data-domainname="' . $domainName . '" data-reviewId="' . $amazon_review['reviewId'] . '" data-star="' . $amazon_review['star'] . '" data-asin="' . $amazon_review['asin'] . '" data-title="' . $amazon_review['title'] . '" class="view_review" >' . $amazon_review['star'] . '</a></span></p>';
                                    echo 'review:' . $amazon_review_info;
                                } else {
                                    echo '<span class="label label-default">无</span>';
                                }
                            }
                            if ($item['platform_code'] == 'ALI') {
                                $ali_evalute = AliexpressEvaluate::getFindOne($platform_order_id);
                                $id = $ali_evalute->id;
                                $account_id = $ali_evalute->account_id;
                                //只能查询到客服绑定账号的评价
                                $info = AliexpressEvaluateList::findOne($id);
                                if (!empty($info)) {
                                    $reply_evalute = '<a href="javascript:void(0)" class="edit-record ali_evalute" >' . $info['buyer_evaluation'] . '</a>';
                                    echo $reply_evalute;
                                } else {
                                    echo '<span class="label label-default">无</span>';
                                }
                            }
                            ?>
                        </td>

                        <td>
                            <?php
                            //显示退款 退货 国内退件 海外退件
                            $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder($item['platform_code'], $item['order_id']);
                            if ($aftersaleinfo) {
                                $res = AfterSalesOrder::getAfterSalesOrderByOrderId($item['order_id'], $item['platform_code']);
                                //获取售后单信息
                                if (!empty($res['refund_res'])) {
                                    $refund_res = '退款';
                                    foreach ($res['refund_res'] as $refund_re) {
                                        $refund_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                                                $refund_re['after_sale_id'] . '&platform_code=' . $item['platform_code'] . '&status=' . $aftersaleinfo->status . '" >' .
                                                $refund_re['after_sale_id'] . '</a><br>';
                                    }
                                } else {
                                    $refund_res = '';
                                }

                                if (!empty($res['return_res'])) {
                                    $return_res = '退货';
                                    foreach ($res['return_res'] as $return_re) {
                                        $return_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id=' .
                                                $return_re['after_sale_id'] . '&platform_code=' . $item['platform_code'] . '&status=' . $aftersaleinfo->status . '" >' .
                                                $return_re['after_sale_id'] . '</a><br>';
                                    }
                                } else {
                                    $return_res = '';
                                }

                                if (!empty($res['redirect_res'])) {
                                    $redirect_res = '重寄';
                                    foreach ($res['redirect_res'] as $redirect_re) {
                                        $redirect_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id=' .
                                                $redirect_re['after_sale_id'] . '&platform_code=' . $item['platform_code'] . '&status=' . $aftersaleinfo->status . '" >' .
                                                $redirect_re['after_sale_id'] . '</a><br>';
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
                                    } elseif ($res['domestic_return']['state'] == 4){
                                        $state = '驳回EPR';
                                    } else {
                                        $state = '暂不处理';
                                    }
                                    //状态：1、未处理，2、无需处理，3、已处理，4、驳回EPR
                                    $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                                            $res['domestic_return']['return_number'] . '&platform_code=' . $item['platform_code'] . '" >' .
                                            $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a><br>';
                                } else {
                                    $domestic_return = '';
                                }
                                $after_sale_text = '';
                                if (!empty($refund_res)) {
                                    $after_sale_text .= $refund_res;
                                }
                                if (!empty($return_res)) {
                                    $after_sale_text .= $return_res;
                                }
                                if (!empty($redirect_res)) {
                                    $after_sale_text .= $redirect_res;
                                }
                                if (!empty($domestic_return)) {
                                    $after_sale_text .= $domestic_return;
                                }

                                echo $after_sale_text;
                            } else {
                                $res = AfterSalesOrder::getAfterSalesOrderByOrderId($item['order_id'], $item['platform_code']);

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
                                            $res['domestic_return']['return_number'] . '&platform_code=' . $item['platform_code'] . '" >' .
                                            $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a><br>';


                                    echo $domestic_return;
                                } else {
                                    echo '<span class="label label-success">无</span>';
                                }
                            }
                            ?>
                        </td>
                        <td><?php
                            if ($item['platform_code'] == 'EB') {
                                $details = OrderEbay::getItemidArr($item['order_id']);
                                //ebay站内信
                                $item_id = [];
                                foreach ($details as $detail) {
                                    $item_id[] = $detail['item_id'];
                                }
                                $account = Account::find()->select('id')->where(['old_account_id' => $item['account_id'], 'platform_code' => 'EB'])->asArray()->one();
                                $account_id = $account['id'];
                                $isSetEbayInboxSubject = EbayInboxSubject::haveEbayInboxSubject($account_id, $item_id, $item['buyer_id']);
                                if (!empty($isSetEbayInboxSubject)) {
                                    foreach ($isSetEbayInboxSubject as $value) {
                                        echo '<a target="_blank" href="/mails/ebayinboxsubject/detail?id=' . $value['id'] . '">' . $value['item_id'] . '</a><br/>';
                                    }
                                } else {
                                    echo '<span class="label label-success">无</span>';
                                }
                            } else {
                                //其他平台显示无
                                echo '<span class="label label-success">无</span>';
                            }
                            ?>
                        </td>
                        <td><?= $erp_type->$item['return_type']->$item['return_typesmall'] ?><br/><?= $sources[$item['source']] ?><br/><span <?php
                            if ($item['state'] == 4) {
                                echo 'style="color: #FF0000"';
                            } else if ($item['state'] == 1) {
                                echo 'style="color: #FF9900"';
                            } else if ($item['state'] == 2) {
                                echo 'style="color: #666666"';
                            } else if ($item['state'] == 3) {
                                echo 'style="color: #66CC66"';
                            }
                            ?>><?= $statestring[$item['state']] ?></span>

                            <?php if ($item['state'] == 4) echo '驳回原因：' . $item['reason']; ?>
                            <?php //$item['handle_user']  ?>
                            <?php //$item['handle_time'] ?></td>

                        <td><?php echo $item['synchronization_time'] . '/' . $item['remark'] ?>
                            <?php
                            if ($platform_code == 'AMAZON') {
                                echo '<br/><a href="' . Url::toRoute(['/mails/amazonreviewdata/getsendemail', 'old_account_id' => $item['account_id'], 'toemail' => $item['email'], 'platform_order_id' => $item['platform_order_id']]) . '" target="_blank"> 联系买家</a>';
                            }
                            ?>
                        </td>

                        <td>
                            <?php
                            $str = $item['record'];
                            if (mb_strlen($str) > 12) {
                                $sy = mb_substr($str, 0, 12) . '...';
                                ?>
                                <a style=" cursor: pointer;"  class='record<?php echo $item['id']; ?>' onclick="records(this)"><?php echo $sy; ?></a>
                                <input type="hidden" name="remark"  class="recordl" value="<?php echo $str; ?>"/>
                                <?php
                            } else {
                                echo $str;
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($item['state'] == 1 || $item['state'] == 5) { ?>
                                <div class="btn-group btn-list">
                                    <button type="button"
                                            class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation'); ?></button>
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                            data-toggle="dropdown">
                                        <span class="caret"></span>
                                        <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                    </button>
                                    <ul class="dropdown-menu" rol="menu">
                                        <?php if ($item['state'] == 1) { ?>
                                            <li>
                                                <a style=" cursor: pointer;"
                                                   onclick="hangup(<?php echo $item['id']; ?>)">暂不处理</a>
                                            </li>
                                        <?php } ?>
                                        <?php if ($item['return_type'] == 1) { ?>
                                            <li>
                                                <a _width="90%" _height="75%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/orderdetails', 'platform' => $item['platform_code'], 'system_order_id' => $item['order_id'], 'track_number' => $item['trackno'], 'is_return' => 1, 'id' => $item['id']]); ?>">修改信息</a>
                                            </li>
                                        <?php } ?>
                                        <?php if ($item['return_type'] == 1 && $item['return_typesmall'] == 2) { ?>
                                            <li><a _width="90%" _height="75%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/domesticreturngoods/separate', 'platform_code' => $item['platform_code'], 'order_id' => $item['order_id'], 'id' => $item['id']]); ?>">拆单</a>
                                            </li>
                                        <?php } ?>
                                        <li><a _width="90%" _height="75%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/aftersales/order/add', 'order_id' => $item['order_id'], 'platform' => $item['platform_code'], 'id' => $item['id']]); ?>">新建售后单</a>
                                        </li>
                                        <?php if ($item['return_type'] == 1) { ?>
                                            <li><a _width="90%" _height="75%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code'], 'id' => $item['id']]); ?>">登记退款单</a>
                                            </li>
                                        <?php } ?>
                                        <?php if ($item['source'] == 1) { ?>
                                            <li><a style=" cursor: pointer;"
                                                   onclick="reject(<?php echo $item['id']; ?>)">驳回</a>
                                            </li>
                                        <?php } ?>
                                        <?php if ($item['return_type'] == 1) { ?>
                                            <li><a style=" cursor: pointer;"
                                                   onclick="withhold(<?php echo $item['id']; ?>)">暂扣订单</a>
                                            </li>
                                        <?php } ?>
                                        <?php if ($item['return_type'] == 1) { ?>
                                            <li><a style=" cursor: pointer;"
                                                   onclick="permanentcancel(<?php echo $item['id']; ?>)">永久作废</a>
                                            </li>
                                        <?php } ?>
                                        <?php if ($item['return_type'] == 1 && ($item['return_typesmall'] == 2 || $item['return_typesmall'] == 3)) { ?>
                                            <li><a _width="90%" _height="75%" class="edit-button"
                                                   href="/aftersales/domesticreturngoods/supplement?id=<?php echo $item['id']; ?>&platform=<?php echo $item['platform_code']; ?>">补款</a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            <?php } else if ($item['state'] == 2) { ?>
                                <?php
                                //有退款单的,显示了无需处理,需要有永久作废操作按钮
                                if (!empty($aftersaleinfo) && !empty($res['refund_res'])) {
                                    ?>
                                    <div class="btn-group btn-list">
                                        <button type="button"
                                                class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation'); ?></button>
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                                data-toggle="dropdown">
                                            <span class="caret"></span>
                                            <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List'); ?></span>
                                        </button>
                                        <ul class="dropdown-menu" rol="menu">
                                            <li><a style=" cursor: pointer;"
                                                   onclick="permanentcancel(<?php echo $item['id']; ?>)">永久作废</a>
                                            </li>
                                        </ul>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="21">暂无</td>
                </tr>
            <?php } ?>
        </table>
        <?php
        echo LinkPager::widget([
            'pagination' => $page,
        ]);
        ?>
    </div>
</div>

<!--驳回-->
<div class="modal fade in" id="batchsendMsgModal" tabindex="-1" role="dialog" aria-labelledby="sbatchsendMsgModalLabel"
     aria-hidden="true"
     style="top:300px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">

                <h4 class="modal-title" id="sbatchsendMsgModalLabel">驳回</h4>
            </div>
            <div class="modal-body">
                <form id="sendMsgFormBatch">
                    <div class="row">
                        <div class="col col-lg-12">
                            <textarea class="form-control" rows="5" id="reason" placeholder="请输入驳回原因"
                                      name="audit_remark"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="sendMsgBtnBatch">提交</button>
                <button type="button" class="btn btn-default" id="closeModel" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<!--挂起，暂不处理-->
<div class="modal fade in" id="hangupsendMsgModal" tabindex="-1" role="dialog" aria-labelledby="hangupsendMsgModalLabel"
     aria-hidden="true"
     style="top:300px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">

                <h4 class="modal-title" id="hangupsendMsgModalLabel">暂不处理</h4>
            </div>
            <div class="modal-body">
                <form id="hangupMsgFormBatch">
                    <div class="row">
                        <div class="col col-lg-12">
                            确定暂时不处理该订单么？
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="hangupMsgBtnBatch">是</button>
                <button type="button" class="btn btn-default" id="closesModel" data-dismiss="modal">否</button>
            </div>
        </div>
    </div>
</div>

<!--暂扣订单，接口-->
<div class="modal fade in" id="withholdsendMsgModal" tabindex="-1" role="dialog"
     aria-labelledby="withholdsendMsgModalLabel" aria-hidden="true"
     style="top:300px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">

                <h4 class="modal-title" id="withholdsendMsgModalLabel">暂扣订单</h4>
            </div>
            <div class="modal-body">
                <form id="withholdMsgFormBatch">
                    <div class="row">
                        <div class="col col-lg-12">
                            确定暂扣该订单么？
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="withholdMsgBtnBatch">是</button>
                <button type="button" class="btn btn-default" id="withholdclosesModel" data-dismiss="modal">否</button>
            </div>
        </div>
    </div>
</div>

<!--永久作废，接口-->
<div class="modal fade in" id="permanentcancelsendMsgModal" tabindex="-1" role="dialog"
     aria-labelledby="permanentcancelsendMsgModalLabel" aria-hidden="true"
     style="top:300px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">

                <h4 class="modal-title" id="permanentcancelsendMsgModalLabel">永久作废</h4>
            </div>
            <div class="modal-body">
                <form id="permanentcancelMsgFormBatch">
                    <div class="row">
                        <div class="col col-lg-12">
                            确定永久作废该订单么？
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="permanentcancelMsgBtnBatch">是</button>
                <button type="button" class="btn btn-default" id="permanentcancelclosesModel" data-dismiss="modal">否
                </button>
            </div>
        </div>
    </div>
</div>
<!--amazon feedback-->
<div id="feedback-modal" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="custom-width-modalLabel"
     aria-hidden="false" style="display: none; padding-right: 17px;">
    <div class="modal-dialog" style="width:55%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="custom-width-modalLabel">amazon feedback详情</h4>
            </div>
            <div class="col-md-12" style="margin-top:15px; margin-bottom: 15px;">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <div class="row" id="feedback">
                            <div class="col-xs-12">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>评级</th>
                                            <th>评价内容</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary waves-effect waves-light" data-dismiss="modal">Close
                </button>
            </div>
        </div>
    </div>
</div>
<div id="review-modal" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="custom-width-modalLabel"
     aria-hidden="false" style="display: none; padding-right: 17px;">
    <div class="modal-dialog" style="width:55%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="custom-width-modalLabel">amazon review详情</h4>
            </div>
            <div class="col-md-12" style="margin-top:15px; margin-bottom: 15px;">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <div class="row" id="review">
                            <div class="col-xs-12">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Asin</th>
                                            <th>评级</th>
                                            <th>评价内容</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary waves-effect waves-light" data-dismiss="modal">Close
                </button>
            </div>
        </div>
    </div>
</div>
<!--速卖通评价详情-->
<div id="evaluate-modal" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="custom-width-modalLabel"
     aria-hidden="false" style="display: none; padding-right: 17px;">
    <div class="modal-dialog" style="width:55%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="custom-width-modalLabel">速卖通评价详情</h4>
            </div>
            <div class="col-md-12" style="margin-top:15px; margin-bottom: 15px;">
                <div class="panel panel-primary">
                    <div class="popup-body">
                        <table id="addReplyFeedback">
                            <tr>
                                <td class="col1">店铺名</td>
                                <td>
                                    <?php
                                    $account = Account::findOne($info['account_id']);
                                    if (!empty($account)) {
                                        echo $account->account_name;
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="col1">itemID</td>
                                <td>
                                    <a target="_blank"
                                       href="https://www.aliexpress.com/item//<?php echo $info['platform_product_id']; ?>.html"><?php echo $info['platform_product_id']; ?></a>
                                </td>
                            </tr>
                            <tr>
                                <td class="col1">我留的评价</td>
                                <td>
                                    <?php for ($ix = 0; $ix < $info['seller_evaluation']; $ix++) { ?>
                                        <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
                                    <?php } ?>
                                    <br>
                                    <?php echo $info['seller_feedback'] ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="col1">我收到的评价</td>
                                <td>
                                    <?php for ($ix = 0; $ix < $info['buyer_evaluation']; $ix++) { ?>
                                        <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
                                    <?php } ?>
                                    <br>
                                    <span id="buyerFeedback"><?php echo $info['buyer_feedback'] ?></span>
                                    <br>
                                    <span id="translateResult" style="color:green;font-weight:bold;"></span>
                                    <br>
                                    <?php if (!empty($info['buyer_feedback'])) { ?>
                                        <a href="javascript:void(0);" id="clickTranslate">点击翻译</a>
                                    <?php } ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary waves-effect waves-light" data-dismiss="modal">Close
                </button>
            </div>
        </div>
    </div>
</div>
<script>

    //获取订单备注详情
    function demol(e) {
        var remark = $(e).next('.remarkl').val();
        var classs = $(e).attr("class");
        layer.tips(remark, '.' + classs, {
            tips: [1, '#0FA6D8'] //还可配置颜色
        });
    }
    //获取处理类型/明显
    function records(e) {
        var remark = $(e).next('.recordl').val();
        var classs = $(e).attr("class");
        layer.tips(remark, '.' + classs, {
            tips: [1, '#0FA6D8'] //还可配置颜色
        });
    }

//批量暂扣订单
    $(document).on('click', '#temporary', function () {
        var url = "<?php echo Url::toRoute(['/aftersales/domesticreturngoods/withholdall']); ?>";
        var selectIds = selectId = [];
        var ids = document.getElementsByName("check");
        for (var i = 0; i < ids.length; i++) {
            if (ids[i].checked) {
                selectId.push(ids[i].value);
            }
        }
        selectIds = selectId.join(',');
        if (selectIds == "") {
            layer.msg('请勾选数据', {icon: 0});
            return false;
        }
        layer.confirm('确定暂扣这些订单吗？', {
           'title':"提示",
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: url,
                type: "post",
                data: {'selectIds': selectIds},
                dataType: "json",
                success: function (data) {
                    if (data.state == 1) {
                        layer.msg(data.msg, {icon: 1});
                        window.location.reload();
                    } else {
                        layer.msg(data.msg, {icon: 0});
                        return false;
                    }
                },
                error: function (e) {
                    layer.msg('系统繁忙,请稍后再试', {icon: 0});
                    return false;
                }
            });
        }, function () {

        });








    });

    //amzon feedback
    $(document).on('click', '.view_feedback', function () {
        var rating = $(this).data('rating');
        var comments = $(this).data('comments');
        var html = "";
        html += '<tr>';
        html += '<td>' + rating + '</td>';
        html += '<td>' + comments + '</td>';
        html += '<tr>';
        $("#feedback tbody").html("");
        $("#feedback tbody").append(html);
        $("#feedback-modal").modal('show');

    });
    //amazon review
    $(document).on('click', '.view_review', function () {
        var asin = $(this).data('asin');
        var title = $(this).data('title');
        var star = $(this).data('star');
        var domainName = $(this).data('domainname');
        var reviewId = $(this).data('reviewId');
        var html = "";

        asin = '<a href="' + domainName + '/gp/customer-reviews/' + reviewId + '/?ie=UTF8&ASIN=' + asin + '" target="_blank">' + asin + '</a>';

        html += '<tr>';
        html += '<td>' + asin + '</td>';
        html += '<td>' + title + '</td>';
        html += '<td>' + star + '</td>';
        html += '<tr>';
        $("#review tbody").html("");
        $("#review tbody").append(html);
        $("#review-modal").modal('show');

    });

    //速卖通评价
    $(document).on('click', '.ali_evalute', function () {
        $("#evaluate-modal").modal('show');
    });

    //挂起,不处理.
    function hangup(id) {
        $("#hangupsendMsgModal").show();
        $("#hangupMsgBtnBatch").click(function () {

            $.get("<?php echo Url::toRoute(['/aftersales/domesticreturngoods/hangup']); ?>",
                    {
                        'id': id,
                    }, function (data) {
                if (data.ack) {
                    location.href = location.href;
                    layer.msg(data.message, {icon: 6});
                    $("#hangupModal").modal("hide");
                } else {
                    layer.msg(data.message, {icon: 5});
                    return;
                }
            }, "json");
            return false;
        });
    }
   //批量暂不处理
   $(document).on('click','#handle',function(){
       var url = "<?php echo Url::toRoute(['/aftersales/domesticreturngoods/hangupall']); ?>";
        var selectIds = selectId = [];
        var ids = document.getElementsByName("check");
        for (var i = 0; i < ids.length; i++) {
            if (ids[i].checked) {
                selectId.push(ids[i].value);
            }
        }
        selectIds = selectId.join(',');
        if (selectIds == "") {
            layer.msg('请勾选数据', {icon: 0});
            return false;
        }
        layer.confirm('确定暂不处理这些订单吗？', {
           'title':"提示",
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: url,
                type: "post",
                data: {'selectIds': selectIds},
                dataType: "json",
                success: function (data) {
                    if (data.state == 1) {
                        layer.msg(data.msg, {icon: 1});
                        window.location.reload();
                    } else {
                        layer.msg(data.msg, {icon: 0});
                        return false;
                    }
                },
                error: function (e) {
                    layer.msg('系统繁忙,请稍后再试', {icon: 0});
                    return false;
                }
            });
        }, function () {

        });
   
   
   
     
   
   });
   
   
   
  
    //暂扣订单.
    function withhold(id) {
        $("#withholdsendMsgModal").show();
        $("#withholdMsgBtnBatch").click(function () {

            $.get("<?php echo Url::toRoute(['/aftersales/domesticreturngoods/withhold']); ?>",
                    {
                        'id': id,
                    }, function (data) {
                if (data.ack) {
                    location.href = location.href;
                    layer.msg(data.message, {icon: 6});
                    $("#withholdModal").modal("hide");
                } else {
                    layer.msg(data.message, {icon: 5});
                    return;
                }
            }, "json");
            return false;
        });
    }

    //永久作废.
    function permanentcancel(id) {
        $("#permanentcancelsendMsgModal").show();
        $("#permanentcancelMsgBtnBatch").click(function () {

            $.get("<?php echo Url::toRoute(['/aftersales/domesticreturngoods/permanentcancel']); ?>",
                    {
                        'id': id,
                    }, function (data) {
                if (data.ack) {
                    location.href = location.href;
                    layer.msg(data.message, {icon: 6});
                    $("#permanentcancelModal").modal("hide");
                } else {
                    layer.msg(data.message, {icon: 5});
                    return;
                }
            }, "json");
            return false;
        });
    }
    //批量永久作废
    $(document).on('click', '#perpetual', function () {
        var url = "<?php echo Url::toRoute(['/aftersales/domesticreturngoods/permanentcancelall']); ?>";
        var selectIds = selectId = [];
        var ids = document.getElementsByName("check");
        for (var i = 0; i < ids.length; i++) {
            if (ids[i].checked) {
                selectId.push(ids[i].value);
            }
        }
        selectIds = selectId.join(',');
        if (selectIds == "") {
            layer.msg('请勾选数据', {icon: 0});
            return false;
        }
        layer.confirm('确定永久作废这些订单吗？', {
            'title':"提示",
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: url,
                type: "post",
                data: {'selectIds': selectIds},
                dataType: "json",
                success: function (data) {
                    if (data.state == 1) {
                        layer.msg(data.msg, {icon: 1});
                        window.location.reload();
                    } else {
                        layer.msg(data.msg, {icon: 0});
                        return false;
                    }
                },
                error: function (e) {
                    layer.msg('系统繁忙,请稍后再试', {icon: 0});
                    return false;
                }
            });
        }, function () {

        });
    });
    function reject(id) {
        $("#batchsendMsgModal").show();
        $("#sendMsgBtnBatch").click(function () {
            //获取内容
            var audit_remark = $("textarea[name=audit_remark]").val();
            if (audit_remark == '') {
                layer.msg('请输入驳回原因!', {icon: 5});
                return;
            }

            $.post("<?php echo Url::toRoute(['/aftersales/domesticreturngoods/reject']); ?>",
                    {
                        'id': id,
                        'reason': audit_remark,
                    }, function (data) {
                if (data.ack) {
                    location.href = location.href;
                    layer.msg(data.message, {icon: 6});
                    $("#batchsendMsgModal").modal("hide");
                } else {
                    layer.msg(data.message, {icon: 5});
                    return;
                }
            }, "json");
            return false;
        });
    }

    $("#closesModel").click(function () {
        $("#hangupsendMsgModal").hide();
    });

    $("#withholdclosesModel").click(function () {
        $("#withholdsendMsgModal").hide();
    });
    $("#permanentcancelclosesModel").click(function () {
        $("#permanentcancelsendMsgModal").hide();
    });
    //checkbox选择
    $(".all").bind("click",
            function () {
                $(".sel").prop("checked", $(this).prop("checked"));
            });
    $(".sel").bind("click",
            function () {
                var $sel = $(".sel");
                var b = true;
                for (var i = 0; i < $sel.length; i++) {
                    if ($sel[i].checked == false) {
                        b = false;
                        break;
                    }
                }
                $(".all").prop("checked", b);
            });
    /**
     *  根据平台切换账号
     */
    $("#platform_code").change(function () {
        $("#account_id").empty();
        $("#account_id").append("<option value=''>全部</option>");
        $.post("<?php echo Url::toRoute(['/aftersales/sales/getaccountbyplatformcode']); ?>",
                {
                    'platform_code': $("#platform_code").val(),
                }, function (data) {
            if (data.status == "success") {
                $.each(data.data, function (index, item) {
                    $("#account_id").append("<option value='" + index + "'>" + item + "</option>");
                });
            }
        }, "json");
    });
    /**
     * 下载
     */
    $("#download").click(function () {
        var url = '/aftersales/domesticreturngoods/downloadreceipt?excel=1&';
        var fromstr = $("#search-form").serialize();

        window.open(url + fromstr);
    });
    $("#closeModel").click(function () {
        //清空数据
        $("#sendMsgFormBatch textarea[name='audit_remark']").val("");
        $('#batchsendMsgModal').hide();
    });

</script>