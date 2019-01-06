<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\orders\models\Order;
use app\modules\accounts\models\Account;
use app\modules\mails\models\WalmartInbox;
use app\modules\mails\models\MailTemplate;
use app\modules\orders\models\Logistic;
use app\modules\systems\models\Country;

$this->title = $subject_model->now_subject;
$this->params['breadcrumbs'][] = ['label' => 'Walmart Inboxes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$first_subject = !empty($models[0]->subject) ? str_replace('"', "'", $models[0]->subject) : '';
$first_subject = preg_replace('/[\f\n\r\t\v]*/', '', $first_subject);
?>
<style>
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

    .panel-default {
        padding-left: 0;
        padding-right: 0;
        border: 1px solid #ddd;
    }

    .col-sm-5 {
        width: auto;
    }

    .tr_q .dropdown-menu {
        left: -136px;
    }

    .tr_h .dropdown-menu {
        left: -392px;
    }

    .clearRemarkBtn {
        font-size: 16px;
    }

    iframe {
        width: 100%;
        max-height: 720px;
        min-height: 480px;
        border: none;
    }

    .mail_template_unity {
        margin-right: 10px;
        cursor: pointer;
    }
</style>
<script type="text/javascript" src="/js/jquery.form.js"></script>
<div id="page-wrapper-inbox" class="row">

    <div class="col col-md-6">
        <div class="panel panel-default cancel-inbox-id">
            <div class="panel-heading">
                <h3 class='panel-title'>
                    <p><i class="fa fa-pencil"></i>查看邮件（<?= $subject_model->now_subject; ?>）</p>
                    <p>买家邮箱：<?php echo $subject_model->sender_email; ?></p>
                    <span id="remark_<?php echo $subject_model->id; ?>">
                        <?php if (empty($subject_model->remark)) { ?>
                            <i class="fa subject_remark fa-pencil " style="cursor: pointer;"
                               data="<?php echo $subject_model->id; ?>" data1=""></i>
                           <?php } else { ?>
                            <li class=" tag label btn-info md ion-close-circled">
                                <span style="cursor: pointer;word-break:normal;white-space:pre-wrap;"
                                      class="subject_remark" data-id="<?php echo $subject_model->id; ?>"
                                      data="<?php echo $subject_model->id; ?>"
                                      data1=""><?php echo app\common\VHelper::break_string($subject_model->remark, 40); ?></span>
                                <a href="javascript:void(0)" class="remove_subject_remark"
                                   data-id="<?php echo $subject_model->id; ?>">x</a>
                            </li>
                        <?php } ?>
                    </span>
                </h3>
            </div>
            <div class="panel-heading">
                <h3 class='panel-title'>
                    <ul class="list-inline" id="ulul">
                        <?php
                        if (!empty($tags_data)) {
                            foreach ($tags_data as $key => $value) {
                                ?>
                                <li style="margin-right: 20px;" class="btn btn-default"
                                    id="tags_value<?php echo $key; ?>"><span
                                        use_data="<?php echo $key; ?>"><?php echo $value; ?></span>&nbsp;<a
                                        class="btn btn-warning" href="javascript:void(0)"
                                        onclick="removetags(this);">x</a></li>
                                    <?php
                                }
                            }
                            ?>
                    </ul>
                </h3>
            </div>
            <div class="panel-body" style="height: auto; max-height:700px;overflow-y:scroll; overflow-x:scroll;">
                <div>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab-body">
                            <?php
                            $rep_key = 1;
                            foreach ($models as $modelKey => $model):
                                ?>
                                <div id="collapseItem<?= $modelKey ?>" class="panel-collapse collapse">
                                    <div class="panel-body hear-title">
                                        <div class="hear-title">
                                            <span class="mail_item_list"><?php echo isset($model['flagged']) ? $model['flagged'] : "" ?></span>
                                            <span class="mail_item_list"><?php echo isset($model['high_priority']) ? $model['high_priority'] : "" ?></span>
                                            <span class="mail_item_list"><?php echo isset($model['expiration_date']) ? $model['expiration_date'] : "" ?></span>
                                            <span class="mail_item_list"><?php echo isset($model['message_type']) ? $model['message_type'] : "" ?></span>
                                            <span class="mail_item_list"><?php echo isset($model['is_read']) ? $model['is_read'] : "" ?></span>
                                            <span class="mail_item_list"><?php echo isset($model['is_replied']) ? $model['is_replied'] : "" ?></span>
                                            <span class="mail_item_list"><?php echo isset($model['response_enabled']) ? $model['response_enabled'] : "" ?></span>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($model->history)) { ?>
                                    <?php foreach ($model->history as $item) { ?>
                                        <div class="panel panel-default" style="overflow:hidden;width:80%;float:right;">
                                            <div class="panel-heading">
                                                <h5 class="panel-title">
                                                    <?php echo 'RE:' . $model['subject']; ?>
                                                    <br>
                                                    <span class="mail_item_list">
                                                        <span style="color:#ffcc66;"><?php echo $model['account_name']; ?></span>
                                                        <i class="glyphicon glyphicon-arrow-right"
                                                           style="margin-top: 2px;"></i>
                                                        <span style="color:#ffcc66;padding:0px 10px;"><?php echo $model['sender']; ?></span>
                                                        <span class="mail_item_list"
                                                              style="margin-left:20px;"><?php echo $item['create_time']; ?></span>
                                                        <span class="mail_item_list bg-success"
                                                              style="margin-left:20px;"><?php echo $item['create_by']; ?></span>
                                                    </span>
                                                </h5>
                                            </div>
                                            <div style="margin:1px 0;">
                                                <div class="dropdown" style="display: inline-block;">
                                                    <button class="btn btn-primary dropdown-toggle" type="button"
                                                            id="dropdownMenu1" data-toggle="dropdown"
                                                            aria-haspopup="true" aria-expanded="true">
                                                        附件<span class="caret"></span>
                                                    </button>
                                                    <?php if (!empty($item->attachments)): ?>
                                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                                            <?php foreach ($item->attachments as $attachment) : ?>
                                                                <li><?= Html::a($attachment->name, str_replace(\Yii::$app->basePath . DIRECTORY_SEPARATOR . 'web', '', $attachment->file_path), ['target' => '_blank']) ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">暂无附件
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="panel-body"
                                                 style="background-color:#E6E6F2;width:100%;word-wrap:break-word;word-break:normal;font-size:16px;">
                                                回复内容(英文): <?php echo nl2br(strip_tags($item->reply_content_en)); ?>

                                                <?php if (!empty($item->reply_content)) { ?>
                                                    <hr style="border-top:1px solid #ccc;">
                                                    回复内容: <?php echo nl2br(strip_tags($item->reply_content)); ?>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } ?>

                                <?php if ($model['mail_type'] == 3) { ?>
                                    <div class="panel panel-default" style="overflow:hidden;width:80%;float:right;">
                                        <div class="panel-heading">
                                            <h5 class="panel-title">
                                                <?php echo $model['subject']; ?>
                                                <br>
                                                <span class="mail_item_list">
                                                    <span style="color:#ffcc66;"><?php echo $model['sender']; ?></span>
                                                    <i class="glyphicon glyphicon-arrow-right"
                                                       style="margin-top: 2px;"></i>
                                                    <span style="color:#ffcc66;padding:0px 10px;"><?php echo $model['receiver']; ?></span>
                                                    <span class="mail_item_list"
                                                          style="margin-left:20px;"><?php echo $model['create_time']; ?></span>
                                                    <span class="mail_item_list bg-success"
                                                          style="margin-left:20px;"><?php echo $model['create_by']; ?></span>
                                                </span>
                                            </h5>
                                        </div>
                                        <div style="margin:1px 0;">
                                            <div class="dropdown" style="display: inline-block;">
                                                <button class="btn btn-primary dropdown-toggle" type="button"
                                                        id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true"
                                                        aria-expanded="true">
                                                    附件<span class="caret"></span>
                                                </button>
                                                <?php if (!empty($model->attachments)): ?>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                                        <?php foreach ($model->attachments as $attachment) : ?>
                                                            <li><?= Html::a($attachment->name, str_replace(\Yii::$app->basePath . DIRECTORY_SEPARATOR . 'web', '', $attachment->file_path), ['target' => '_blank']) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">暂无附件</ul>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="panel-body"
                                             style="background-color:#E6E6F2;width:100%;word-wrap:break-word;word-break:normal;font-size:16px;">
                                                 <?php echo nl2br(strip_tags($model['body'], '<br>')); ?>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <div class="panel panel-primary reply_tit" id="content_<?php echo $model['id']; ?>"
                                         style="overflow:hidden;width:80%;float:left;">
                                        <div class="panel-heading" data-id="<?php echo $model['id']; ?>">
                                            <h5 class="panel-title">
                                                <span><?php echo $model['subject']; ?></span>
                                                <br/>
                                                <span class="mail_item_list">
                                                    <span style="color:#ffcc66;"><?php echo $model['sender']; ?></span>
                                                    <i class="glyphicon glyphicon-arrow-right"></i>
                                                    <span style="color:#ffcc66;"><?php echo $model['account_name']; ?></span>
                                                    <span class="mail_item_list"
                                                          style="margin-left:20px;"><?php echo $model['receive_date'] ?></span>

                                                    <span id="remark_<?php echo $model['id']; ?>">
                                                        <?php if (empty($model['remark'])) { ?>
                                                            <i class="fa remark fa-pencil showAddRemark"
                                                               data-id="<?php echo $model->id; ?>"
                                                               style="cursor: pointer;"></i>
                                                           <?php } else { ?>
                                                            <li class="tag label btn-info md ion-close-circled">
                                                                <span style="cursor: pointer;" class="remark showAddRemark"
                                                                      data-id="<?php echo $model['id']; ?>"
                                                                      data1=""><?php echo \app\common\VHelper::break_string($model['remark'], 35); ?></span>
                                                                &nbsp;&nbsp;
                                                                <a href="javascript:void(0)" class="clearRemarkBtn"
                                                                   data-id="<?php echo $model->id; ?>">x</a>
                                                            </li>
                                                        <?php } ?>
                                                    </span>
                                                </span>
                                            </h5>
                                        </div>
                                        <div style="margin:1px 0;">
                                            <div class="dropdown" style="display: inline-block;">
                                                <button class="btn btn-primary dropdown-toggle" type="button"
                                                        id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true"
                                                        aria-expanded="true">
                                                    附件<span class="caret"></span>
                                                </button>
                                                <?php if (!empty($model->attachments)): ?>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                                        <?php foreach ($model->attachments as $attachment) : ?>
                                                            <li><?= Html::a($attachment->name, str_replace(\Yii::$app->basePath . DIRECTORY_SEPARATOR . 'web', '', $attachment->file_path), ['target' => '_blank']) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">暂无附件</ul>
                                                <?php endif; ?>
                                            </div>
                                            <div class="dropdown" style="display:inline-block;">
                                                <button class="btn btn-primary dropdown-toggle" type="button"
                                                        id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true"
                                                        aria-expanded="true">
                                                    标记为...
                                                    <span class="caret"></span>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                                    <li>
                                                        <a href="javascript:markEmailStatus(<?= $model->id ?>, 1);">已读</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:markEmailStatus(<?= $model->id ?>, 2);">已回复</a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <?php echo WalmartInbox::wherethrAttch($model->id, 0) ?>
                                            <?php $replied = WalmartInbox::getReplied($model['id']); ?>
                                            <span style="float:right;margin:10px 15px 0 0">回复状态：<font
                                                    color="red"><?php echo!$replied ? '未回复' : '已回复'; ?>  </font>
                                            </span>
                                        </div>
                                        <div id="dialog_large_image"></div>
                                        <?php if (!empty($model->body)) { ?>
                                            <button type="button" class="btn btn-sm btn-success"
                                                    style="cursor: pointer;margin-left: -8px;"
                                                    onclick="clikTrans($(this),<?php echo $model->id; ?>)">点击翻译
                                            </button>
                                            <div style="padding:5px 10px;background-color:#dcdcdc;"
                                                 class="trans_<?php echo $model->id; ?>"></div>
                                            <div class="panel-body"
                                                 style="background-color:#D1EFAF;width:100%;word-wrap:break-word;word-break:normal;font-size:16px;">
                                                <iframe src="<?php echo Yii::$app->request->getHostInfo() . Url::toRoute(['/mails/walmartinboxsubject/getinboxbody', 'id' => $model->id]); ?>"></iframe>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                                <?php $rep_key++; ?>
                            <?php endforeach; ?>
                            <div style="clear:both;"></div>
                        </div>
                    </div>
                </div>
                <hr>
            </div>
        </div>
        <div class="panel panel-default" style="margin-top: 10px;">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-info-circle"></i> 历史订单 </h3>
            </div>
            <div class="panel-body">
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-info">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>订单号</th>
                                    <th>帐号<br/>国家<br/>买家ID</th>
                                    <th>付款时间<br/>订单状态</th>
                                    <th>订单金额<br/>退款金额<br/>利润</th>
                                    <th>退货编码<br>售后<br/>仓库客诉</th>
                                    <th>包裹信息</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="basic_info">
                                <?php
                                if (!empty($historica)) {

                                    $warehouseList = \app\modules\orders\models\Warehouse::getWarehouseListAll();
                                    foreach ($historica as $hKey => $order) {
                                        $current = '';
                                        $redirectLabel = '';
                                        /* 判断是否为当前订单ID */
                                        if ($orderType) {
                                            $current = '<span class="label label-danger">当前订单</span>';
                                        } else {
                                            if ($order['platform_order_id'] == $subject_model->order_id) {
                                                $order_id = $subject_model->order_id;
                                                $warehouse_id = isset($order['orderPackage'][0]['warehouse_id']) ? $order['orderPackage'][0]['warehouse_id'] : 0;
                                                $current_order_warehouse_name = array_key_exists($warehouse_id, $warehouseList) ?
                                                        $warehouseList[$warehouse_id] : '';
                                                echo "<input type='hidden' name='current_order_warehouse_id' value='$warehouse_id'>" .
                                                "<input type='hidden' name='current_order_id' value='$order_id'>" .
                                                "<input type='hidden' name='current_order_warehouse_name' value='$current_order_warehouse_name'>";

                                                $current = '<span class="label label-danger">当前订单</span>';
                                            }
                                        }

                                        if ($order['order_type'] == Order::ORDER_TYPE_REDIRECT_ORDER)
                                            $redirectLabel = '<span class="label label-warning">重寄订单</span>';
                                        ?>
                                        <tr class="active">
                                            <td>
                                                <a _width="70%" _height="70%" class="edit-button" href="<?php
                                                echo Url::toRoute(['/orders/order/orderdetails',
                                                    'order_id' => $order['platform_order_id'],
                                                    'platform' => Platform::PLATFORM_CODE_WALMART,
                                                    'system_order_id' => $order['order_id']]);
                                                ?>"
                                                   title="订单信息"><?php echo $order['order_id']; ?><?php echo $current . $redirectLabel; ?></a>
                                                <br>
                                                <?php
                                                if (count($order['detail'])) {
                                                    echo '<a data-toggle="collapse" data-parent="#accordion" href="#proDetail_' . $hKey . '" aria-expanded="true" class="">查看产品详情</a>';
                                                }
                                                ?>
                                            </td>
                                            <?php $account_info = Account::getHistoryAccountInfo($order['account_id'], WalmartInbox::PLATFORM_CODE); ?>
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
                                                <br/>
                                                <span <?php if ($order['complete_status_text'] == '已取消') echo 'style="color:red;"'; ?>><?php echo $order['complete_status_text']; ?></span>
                                            </td>

                                            <td>
                                                <?php
                                                if (isset($order['trade']) && !empty($order['trade'])) {
                                                    $f_total_price = $order['total_price'];
                                                    foreach ($order['trade'] as $v_price) {
                                                        if ($v_price['receive_type'] == '发起')
                                                            $f_total_price -= $v_price['amt'];
                                                    }
                                                    if (number_format($f_total_price, 2, '.', '') == 0)
                                                        $f_total_price = 0.00;
                                                    echo '<b style="color:green">' . $f_total_price . $order['currency'] . '</b><br/>';
                                                } else {
                                                    echo '<b style="color:green">' . $order['total_price'] . $order['currency'] . '</b><br/>';
                                                }
                                                ?>
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
                                                //利润
                                                $refundlost = 0;
                                                if (!empty($order['profit'])) {
                                                    if (!empty($after_refund_amount))
                                                        $refundlost = -$after_refund_amount;
                                                    if (!empty($order['after_sale_redirect'])) {
                                                        foreach ($order['after_sale_redirect'] as $after_sale_redirect) {
                                                            $cost = new Order;
                                                            $cost = $cost->getRedirectCostByOrderId(Platform::PLATFORM_CODE_WALMART, $order['order_id']);
                                                            if ($cost && $cost->ack == true) {
                                                                $cost = $cost->data;
                                                                $refundlost += $cost;
                                                            }
                                                        }
                                                    }

                                                    $refundlost = $order['profit']['profit'] - $refundlost;
                                                    echo $refundlost;
                                                }
                                                ?>
                                            </td>

                                            <td>
                                            <?php 
                                              //退货编码
                                              $refundcode=\app\modules\aftersales\models\AfterRefundCode::find()->where(['order_id'=>$order['order_id']])->asArray()->one();
                                              if(empty($refundcode)){
                                                   echo '<span class="label label-success">无</span>';
                                              }else{
                                                  echo $refundcode['refund_code'];
                                              }

                                              ?>
                                                <br/>
                                                <?php
                                                // 售后信息 显示 退款 退货 重寄 退件
                                                $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder(Platform::PLATFORM_CODE_WALMART, $order['order_id']);
                                                //是否有售后订单
                                                if ($aftersaleinfo) {
                                                    $res = AfterSalesOrder::getAfterSalesOrderByOrderId($order['order_id'], Platform::PLATFORM_CODE_WALMART);
                                                    //获取售后单信息
                                                    if (!empty($res['refund_res'])) {
                                                        $refund_res = '退款';
                                                        foreach ($res['refund_res'] as $refund_re) {
                                                            $refund_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                                                                    $refund_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_WALMART . '&status=' . $aftersaleinfo->status . '" >' .
                                                                    $refund_re['after_sale_id'] . '</a>';
                                                        }
                                                    } else {
                                                        $refund_res = '';
                                                    }

                                                    if (!empty($res['return_res'])) {
                                                        $return_res = '退货';
                                                        foreach ($res['return_res'] as $return_re) {
                                                            $return_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id=' .
                                                                    $return_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_WALMART . '&status=' . $aftersaleinfo->status . '" >' .
                                                                    $return_re['after_sale_id'] . '</a>';
                                                        }
                                                    } else {
                                                        $return_res = '';
                                                    }

                                                    if (!empty($res['redirect_res'])) {
                                                        $redirect_res = '重寄';
                                                        foreach ($res['redirect_res'] as $redirect_re) {
                                                            $redirect_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id=' .
                                                                    $redirect_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_WALMART . '&status=' . $aftersaleinfo->status . '" >' .
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
                                                                $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_WALMART . '" >' .
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
                                                    $res = AfterSalesOrder::getAfterSalesOrderByOrderId($hvalue['order_id'], Platform::PLATFORM_CODE_EB);
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
                                                                $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_WALMART . '" >' .
                                                                $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a>';
                                                        echo $domestic_return;
                                                    } else {
                                                        echo '<span class="label label-success">无</span>';
                                                    }
                                                }
                                                ?>
                                                <br/>
                                                <?php
                                                $complaint = \app\modules\aftersales\models\ComplaintModel::find()->select('complaint_order,status')->where(['order_id' => $hvalue['order_id']])->one();
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
                                            if (!empty($historica)) {
                                                if (count($historica[$hKey]['orderPackage'])) {
                                                    foreach ($historica[$hKey]['orderPackage'] as $k => $val) {
                                                        $trackNumber = empty($order['track_number']) ? $val['tracking_number_1'] : $order['track_number'];
                                                        echo '<td>';
                                                        echo $val['warehouse_name'] . '<br/>';
                                                        echo '<span style="font-size:11px;">' . $val['shipped_date'] . '</span><br/>';
                                                        echo $val['ship_name'] . '<br/>';
                                                        if ($order['paytime'] < '2018-05-20 00:00:00') {
                                                            echo!empty($trackNumber) ? '<a href="https://t.17track.net/en#nums=' . $trackNumber . '" target="_blank" title="查看物流跟踪信息">' . $trackNumber . '</a>' : '-' . '<br/>';
                                                        } else {
                                                            echo!empty($trackNumber) ? '<a href="' . Url::toRoute(['/orders/order/gettracknumber', 'track_number' => $trackNumber]) . '" title="查看物流跟踪信息">' . $trackNumber . '</a>' : '-' . '<br/>';
                                                        }
                                                        echo '</td>';
                                                    }
                                                } else {
                                                    echo '<td>暂无包裹信息</td>';
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
                                                                       'order_id' => $order['order_id'], 'platform' => $order['platform_code']]);
                                                                   ?>">永久作废</a>
                                                            </li>
                                                            <li><a _width="30%" _height="60%" class="edit-button"
                                                                   href="<?php
                                                                   echo Url::toRoute(['/orders/order/holdorder',
                                                                       'order_id' => $order['order_id'], 'platform' => $order['platform_code']]);
                                                                   ?>">暂时作废</a>
                                                            </li>
                                                        <?php } if ($order['complete_status'] == Order::COMPLETE_STATUS_HOLD) { ?>
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
                                                        <?php //if ($order['order_type'] != Order::ORDER_TYPE_REDIRECT_ORDER ||!empty($order_re)) { ?>
                                                        <li>
                                                            <a style=" cursor: pointer;"
                                                               onclick="verified('<?php echo $order['order_id']; ?>')">新建售后单</a>
                                                            <a style="display: none;"
                                                               id="orderadd_<?php echo $order['order_id']; ?>"
                                                               _width="100%" _height="100%" class="edit-button"
                                                               href="<?php
                                                               echo Url::toRoute(['/aftersales/order/add',
                                                                   'order_id' => $order['order_id'], 'platform' => $order['platform_code'], 'from' => 'inbox']);
                                                               ?>">新建售后单</a>
                                                        </li>
                                                        <?php //} ?>
                                                        <li><a _width="100%" _height="100%" class="edit-button"
                                                               href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $order['order_id'], 'platform' => $order['platform_code']]); ?>">登记退款单</a>
                                                        </li>

                                                        <li>
                                                            <a _width="50%" _height="80%" class="edit-button"
                                                               href="<?php echo Url::toRoute(['/orders/order/invoice', 'order_id' => $order['order_id'], 'platform' => $order['platform_code']]); ?>">发票</a>
                                                        </li>
                                                        <li>
                                                            <a _width="100%" _height="100%" class="edit-button"
                                                               href="<?php echo Url::toRoute(['/aftersales/complaint/register', 'order_id' => $order['order_id'], 'platform' => $order['platform_code']]); ?>">登记客诉单</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>

                                        <?php if (count($order['detail'])) { ?>
                                            <tr id="proDetail_<?php echo $hKey; ?>" class="panel-collapse collapse"
                                                aria-expanded="true">
                                                <td colspan="7">
                                                    <table class="table table-hover"
                                                           style="font-size:11px;background-color: #f5f5f5;">
                                                        <thead>
                                                            <tr style="font-size:9px;">
                                                                <th>编号</th>
                                                                <th>产品中名</th>
                                                                <th>绑定SKU</th>
                                                                <th>绑定的sku数量</th>
                                                                <th>发货SKU</th>
                                                                <th>发货的sku数量</th>
                                                            </tr>
                                                            <?php foreach ($order['detail'] as $k => $pdetail) { ?>
                                                                <tr>
                                                                    <td><?php echo $k + 1; ?></td>
                                                                    <td><?php echo $pdetail['titleCn']; ?></td>
                                                                    <td><?php echo $pdetail['sku_old']; ?></td>
                                                                    <td><?php echo $pdetail['quantity_old']; ?></td>
                                                                    <td><?php echo $pdetail['sku']; ?></td>
                                                                    <td><?php echo $pdetail['quantity']; ?></td>
                                                                </tr>
                                                            <?php }
                                                            ?>
                                                        </thead>
                                                    </table>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                        <?php
                                    }
                                } else {
                                    echo '<tr class="active"> <td colspan="8" align="center">没有相关订单信息！</td> </tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
                        'id' => 'walmartinbox-form',
                        'action' => Url::toRoute(['/mails/walmartreply/createsubject', 'id' => $subject_model->id, 'next' => 1]),
                        'enableClientValidation' => false,
                        'validateOnType' => false,
                        'validateOnChange' => false,
                        'validateOnSubmit' => true,
                        'options' => ['enctype' => 'multipart/form-data'],
            ]);
            ?>
            <div class="panel-body">

                <div class="panel panel-default form-group">
                    <select onchange="getSubject()" id="subject" class="form-control" name="inbox_id"
                            aria-required="true">
                                <?php if (!$subject_model->is_replied) { ?>
                            <option value="all">未回复的邮件</option>
                        <?php } ?>

                        <?php
                        foreach ($models as $val) {
                            if (!$val->is_replied) {
                                echo "<option value='{$val->id}' style='font-weight:bold;color:blue'>{$val->subject}</option>";
                            } else {
                                echo "<option value='{$val->id}'>{$val->subject}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group" style="margin:-5px 0 15px 0;">
                    　 <input type="text" class="reply-sub form-control" name="reply_title" value="<?php echo $first_subject; ?>">
                </div>

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
                                    <input type="text" class="form-control mail_template_search_text"
                                           placeholder="消息模板搜索">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default mail_template_search_btn"
                                                type="button">搜索</button>
                                    </span>
                                </div><!-- /input-group -->
                            </div>
                        </div><!-- /.row -->
                    </form>
                </div>

                <div class="panel panel-default" style="margin-bottom:15px;">
                    <div class="mail_template_area panel-body">
                        <?php
                        $mailTemplates = MailTemplate::getMailTemplateDataAsArrayByUserId(Platform::PLATFORM_CODE_WALMART);
                        foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName) {
                            echo "<a class='mail_template_unity' value='$mailTemplatesId'>$mailTemplateName</a> ";
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <?php echo Html::hiddenInput('sl_code', "", ['id' => 'sl_code']); ?>
                    <?php echo Html::hiddenInput('tl_code', "", ['id' => 'tl_code']); ?>
                    <label class="control-label col-sm-3" style="width: auto;">附件</label>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <input type="file" id="" name="WalmartReply[file][]"
                                   style="display: inline-block; width: 80%;"/>
                            <a href="javascript:void(0);" onclick="doaddfile(this);">添加</a>
                            <a href="javascript:void(0);" onclick="deletefile(this);">删除</a>
                        </div>
                    </div>

                </div>
                <div style="float: right;margin-right: 235px;margin-bottom: 10px;">
                    <button type="button" class="btn btn-sm btn-success" id="return_info">获取退货信息</button>
                </div>
                <!--在鼠标移动位置插入参数-->
                <div class="form_data" style="float: left;margin-bottom: 10px;">
                    <?php
                    if ($historica) {
                        $order_info = $historica[0];
                        if ($order_info) {
                            $countryList = Country::getCodeNamePairsList('en_name');
                            if ($order_info['track_number']) {
                                $track = 'http://www.17track.net/zh-cn/track?nums=' . $order_info['track_number'];
                                $track_number = $order_info['track_number'];
                            } else {
                                $track = '';
                                $track_number = '';
                            }
                            if ($order_info['buyer_id']) {
                                $buyer_id = $order_info['buyer_id'];
                            } else {
                                $buyer_id = '';
                            }

                            if (!empty($order_info['detail'])) {
                                foreach ($order_info['detail'] as $v) {
                                    $sku_str .= ',' . $v['sku'];
                                    $pruduct_str .= ',' . $v['title'];
                                }
                            } else {
                                $sku_str = '';
                                $pruduct_str = '';
                            }
                        } else {
                            $buyer_id = '';
                            $track_number = '';
                            $track = '';
                            $sku_str = '';
                            $pruduct_str = '';
                        }
                    }
                    ?>
                    <select id="countDataType" class="form-control" style="width:100%;height:30px;padding: 2px 5px;">
                        <option value="all">选择绑定参数</option>
                        <option value="<?php echo $buyer_id; ?>">客户ID</option>
                        <option value="<?php echo $track_number; ?>">跟踪号</option>
                        <option value="<?php echo $track; ?>">查询网址</option>
                        <option value="<?php echo rtrim($pruduct_str, ',') ?>">产品标题</option>
                        <option value="<?php echo rtrim($sku_str, ',') ?>">产品sku</option>
                    </select>
                </div>
                <?= $form->field($reply, 'reply_content_en')->textarea(['rows' => 8, 'id' => 'amz-reply', 'class' => 'form-control', 'name' => 'reply_content_en', 'placeholder' => "输入回复内容(注意: 此输回复内容为英语)"])->label(false) ?>
                <div class="row"
                     style="text-align: center;font-size: 13px;font-weight: bold;margin-top: 20px;margin-bottom: 20px;">
                    <div class="col-sm-4">
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
                                    <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle"
                                            type="button" aria-expanded="false" id="sl_btn">更多&nbsp;&nbsp;<span
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
                    </div>
                    <div class="fa-hover col-sm-1" style="width:0px;line-height: 30px;"><a><i
                                class="fa fa-exchange"></i></a></div>
                    <div class="col-sm-4">
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
                                    <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle"
                                            type="button" aria-expanded="false" data="" id="tl_btn">
                                        更多&nbsp;&nbsp;<span class="caret"></span></button>
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
                    </div>
                    <div class="col-sm-1">
                        <button class="btn btn-sm btn-primary artificialTranslation" type="button"
                                id="translations_btn">翻译 [ <b id="sl_name"></b> - <b id="tl_name"></b> ]
                        </button>
                    </div>
                </div>
                <?= $form->field($reply, 'reply_content')->textarea(['rows' => 8, 'id' => 'amz-reply-en', 'name' => 'reply_content', "placeholder" => "发送给客户的内容", "value" => ""])->label(false) ?>

                <div class="col-md-12 panel-body" style="padding-bottom: 100px;margin-left: -13px;">
                    <button type="button" reply_type="replied" class="reply_mail_mark btn-sm btn btn-default">标记已回复
                    </button>
                    <button type="button" reply_type="last" class="reply_mail_mark btn btn-sm btn-default">上一个</button>
                    <button type="button" reply_type="next" class="reply_mail_mark btn btn-sm btn-default">下一个</button>
                    <?= Html::a('新增标签', Url::toRoute(['/mails/walmartinboxsubject/addtags', 'ids' => $subject_model->id, 'type' => 'detail']), ['class' => 'btn btn-sm btn-primary add-tags-button-button']) ?>

                    <?= Html::a('移除标签', Url::toRoute(['/mails/walmartinboxsubject/removetags', 'id' => $subject_model->id, 'type' => 'detail']), ['class' => 'btn btn-sm btn-danger add-tags-button-button']) ?>
                    <button type="button" reply_type="reply" class="reply_mail_save btn btn-sm btn-success"
                            style="float:right;" onclick="dosubmit()">回复消息
                    </button>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
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
    //鼠标定位添加订单信息
    $("#countDataType").on("change", function () {
        var data_value = $(this).val();
        if (data_value == '') {
            layer.msg('暂无此数据', {icon: 2});
            return false;
        }
        if (data_value != 'all') {
            getValue('amz-reply', data_value);
        }
    });

    //objid：textarea的id   str：要插入的内容
    function getValue(objid, str) {
        var myField = document.getElementById("" + objid);
        //IE浏览器
        if (document.selection) {
            myField.focus();
            sel = document.selection.createRange();
            sel.text = str;
            sel.select();
        } else if (myField.selectionStart || myField.selectionStart == '0') {
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
        } else {
            myField.value += str;
            myField.focus();
        }
    }

    //点击邮件内容获取回复邮件主题
    $('.reply_tit').on('click', function () {
        var reply_tit = $(this).find('h5.panel-title>span:first').text();
        $('.reply-sub').val("RE:<?php echo $first_subject; ?>");
    });


    // 获取选中的邮件主题
    function getSubject() {
        var subject_val = $("#subject option:selected").text();
        if (subject_val == '未回复的邮件') {
            $('.reply-sub').val("RE:<?php echo $first_subject; ?>");
        } else {
            $('.reply-sub').val("RE:" + subject_val);
        }
    }

    //模板编号搜索
    $('.mail_template_title_search_btn').on('click', template_title);
    $('.mail_template_title_search_text').on('keypress', function () {
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
            'platform_code': 'WALMART'
        }, function (data) {
            if (data.code == 200) {
                $('#amz-reply-en').val(data.data);
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
        var platform_code = 'WALMART';
        if (templateName.length == 0) {
            layer.msg('搜索名称不能为空。', {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });
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
    });

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

                    var refund_content = $('#amz-reply').val();
                    if (refund_content !== '') {
                        $('#amz-reply').val(refund_content + '\n' + data.content);
                    } else {
                        $('#amz-reply').val(data.content);
                    }
            }
        }, 'json');
    });

    //标记已回复，上一个，下一个
    $('.reply_mail_mark').click(function () {
        var markType = $(this).attr('reply_type');
        //上一个 下一个
        if (markType == 'last') {
            window.location.href = "<?php echo Url::toRoute(['/mails/walmartinboxsubject/view?last=1']) ?>";
        } else if (markType == 'next') {
            window.location.href = "<?php echo Url::toRoute(['/mails/walmartinboxsubject/view?next=1']) ?>"
        } else if (markType == 'replied') {
            markSubjectStatus(<?= $subject_model->id ?>, 2)
        }
    });

    var markSubjectStatus = function (id, stat) {
        $.get('<?= Url::toRoute("/mails/walmartinboxsubject/mark") ?>', {id: id, stat: stat}, function (data) {
            var $data = $.parseJSON(data);
            if ($data.url && $data.code == "200") {
                window.location.href = $data.url;
            }
        })
    }

    var markEmailStatus = function (id, stat) {
        $.get('<?= Url::toRoute("/mails/walmartinboxsubject/markemail") ?>', {id: id, stat: stat}, function (data) {
            var $data = $.parseJSON(data);
            if ($data.url && $data.code == "200") {
                window.location.href = $data.url;
            }
        })
    }

    var showMores = function (obj) {
        if ($(obj).attr('vi') == '1') {
            $(obj).attr('vi', '2').text('<<<收起').siblings('a.hd').css('display', 'inline-block');
        } else {
            $(obj).attr('vi', '1').text('更多>>>').siblings('a.hd').css('display', 'none');
        }
    }

    var choosesTemplate = function (id) {
        $.post('<?= Url::toRoute("/mails/msgcontent/gettemplate") ?>', {'num': id}, function (data) {
            if (data.status == 'success') {
                var refund_content = $('#amz-reply').val();
            }
            if (refund_content !== '') {
                $('#amz-reply').val(refund_content + '\n' + data.content);
            } else {
                $('#amz-reply').val(data.content);
            }
        }, 'json');
    }

    var expand = function (obj) {
        if ($(obj).attr('v') == 1) {
            $(obj).attr('v', '2').find('div').css('display', 'block');
        } else {
            $(obj).attr('v', '1').find('div').css('display', 'none');
        }
    }

    var go = function (obj) {
        var j = 1,
                s = '',
                search = $('#t-search').val();
        if (search.length == 0) {
            layer.msg('搜索名称不能为空。', {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });
            return;
        }
        $.post('<?= Url::toRoute("/mails/msgcontent/searchtemplate") ?>', {
            'name': search,
            'platform_code': '<?php echo Platform::PLATFORM_CODE_WALMART; ?>'
        }, function (data) {
            if (data.status) {
                for (var i in data.content) {
                    if (j <= 12) {
                        s += '<a href="javascript:void(0);" onclick="choosesTemplate(' + i + ')" style="text-align:center;color:black;"> [ <span class="bg-success">' + data.content[i] + '</span> ]</a>';
                    } else {
                        s += '<a href="javascript:void(0);" onclick="choosesTemplate(' + i + ')" style="text-align:center;color:black;display:none" class="hd"> [ <span class="bg-success">' + data.content[i] + '</span>]</a>';
                    }
                    j++;
                }
                s += '&nbsp;<a href="javascript:void(0);" onclick="showMores(this)" vi="1">更多&gt;&gt;&gt;</a>';
                $('.t-zone').html(s);

            }
        }, 'json');
    }

    var dosubmit = function () {
        if (!$('#amz-reply').val()) {
            layer.alert('请填写回复内容!', {icon: 5});
            return false;
        } else {
            $("#walmartinbox-form").submit();
        }
        return true;
    }

    function doaddfile(obj) {
        var str = '<div>' +
                '<input type="file" id="" name="WalmartReply[file][]" style="display: inline-block; width: 80%;" /> <a href="javascript:void(0);" onclick="doremovefile(this);">删除</a>' +
                '</div>';

        $(obj).parent('div').after(str);
    }

    function deletefile(obj) {
        $(obj).siblings('input').val('');
    }

    function doremovefile(obj) {
        $(obj).parent('div').remove();
    }

    $('div.sidebar').hide();

    // 获取url参数
    function GetQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) {
            return unescape(r[2]);
        }
        return null;
    }

    function removetags(obj) {
        var _id = GetQueryString('id');
        var tag_id = $(obj).siblings('span').attr('use_data');
        $.post('<?= Url::toRoute(['/mails/walmartinboxsubject/removetags', 'id' => $subject_model->id, 'type' => 'detail']) ?>', {
            'MailTag[inbox_id]': _id,
            'MailTag[tag_id][]': tag_id,
            'MailTag[type]': 'detail'
        }, function (data) {
            if (data.url && data.code == "200")
                $("#tags_value" + tag_id).hide(50);
        }, 'json');
    }

    $('.get-data-id').on('click', function () {
        $this = $(this);
        var inbox_id = $this.attr('data-id');
        $('.cancel-inbox-id').removeClass('panel-success').addClass('panel-default');
        $(this).parent().siblings().removeClass('panel-success').addClass('panel-default');
        $(this).parent().removeClass('panel-default').addClass('panel-success')
        $('#walmartreply-inbox_id').val(inbox_id);
    });

    $('.cancel-inbox-id').on('click', function () {
        $(this).removeClass('panel-default').addClass('panel-success');
        $('.get-data-id').parent().removeClass('panel-success').addClass('panel-default');
        $('.get-data-id').parent().siblings().removeClass('panel-success').addClass('panel-default');
        $('#walmartreply-inbox_id').val('');
    });

    //快捷键设置标签
    var keyboards = '<?php echo $keyboards; ?>'
    keyboards = JSON.parse(keyboards);
    var ids = '<?php echo $subject_model->id; ?>'
    var tag_id = '';
    $(document).ready(
            function () {
                document.onkeyup = function (e) {
                    var event = window.event || e;
                    if (event.shiftKey && keyboards['shift'] != undefined && keyboards['shift'][event.keyCode] != undefined) {
                        tag_id = keyboards['shift'][event.keyCode]
                        if (tag_id != '' && tag_id != undefined) {
                            $.post('<?= Url::toRoute(['/mails/walmartinboxsubject/addretags', 'ids' => $subject_model->id, 'type' => 'detail']) ?>', {
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
                                    $("#tags_value" + tags_id).hide(50);
                                }
                            }, 'json');
                        }
                    }
                    if (event.ctrlKey && keyboards['ctrl'] != undefined && keyboards['ctrl'][event.keyCode] != undefined) {
                        tag_id = keyboards['ctrl'][event.keyCode]
                        if (tag_id != '' && tag_id != undefined) {
                            $.post('<?= Url::toRoute(['/mails/walmartinboxsubject/addretags', 'ids' => $subject_model->id, 'type' => 'detail']) ?>', {
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
                                    $("#tags_value" + tags_id).hide(50);
                                }
                            }, 'json');
                        }
                    }
                    if (event.altKey && keyboards['alt'] != undefined && keyboards['alt'][event.keyCode] != undefined) {
                        tag_id = keyboards['alt'][event.keyCode]
                        if (tag_id != '' && tag_id != undefined) {
                            $.post('<?= Url::toRoute(['/mails/walmartinboxsubject/addretags', 'ids' => $subject_model->id, 'type' => 'detail']) ?>', {
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
                                    $("#tags_value" + tags_id).hide(50);
                                }
                            }, 'json');
                        }
                    }
                }
            }
    );

    /**
     * 绑定翻译按钮 进行手动翻译操作
     * @author allen <2018-1-5>
     **/
    function clikTrans(that, k) {
        var sl = 'auto';
        var tl = 'en';

        $.get("<?php echo Url::toRoute(['/mails/walmartinboxsubject/getinboxtransbody']) ?>", {
            "id": k
        }, function (data) {
            if (data["code"] == 1) {
                var content = data["data"];
                if (content.length == 0) {
                    layer.msg('翻译的内容为空!', {icon: 5});
                    return false;
                }
                $.ajax({
                    type: "POST",
                    dataType: "JSON",
                    url: '<?php echo Url::toRoute(['/mails/ebayinboxsubject/translate']); ?>',
                    data: {'sl': sl, 'tl': tl, 'returnLang': 1, 'content': content},
                    success: function (data) {
                        if (data) {
                            $("#sl_code").val('en');
                            $("#sl_name").html('英语');
                            $("#tl_code").val(data.googleCode);
                            $("#tl_name").html(data.code);
                            $(".trans_" + k).html('<b style="color:green;">' + data.text + '</b>');
                            that.remove();
                        }
                    }
                });
            } else {
                layer.msg(data["message"], {icon: 5});
            }
        }, "json");
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
        var content = $.trim($("#amz-reply").val());
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
                    $("#amz-reply-en").val(data);
                    $("#amz-reply-en").css('display', 'block');
                }
            }
        });
    });

    //显示添加备注弹窗
    $("#page-wrapper-inbox").on("click", ".showAddRemark", function () {
        $("#addRemarkForm input[name='id']").val($(this).attr("data-id"));
        $("#addRemarkForm textarea[name='remark']").val($(this).attr("data-remark"));
        $("#addRemarkModal").modal("show");
        return false;
    });
    //弹窗关闭时清空数据
    $("#addRemarkModal").on('hidden.bs.modal', function (e) {
        $("#addRemarkForm input[name='id']").val("");
        $("#addRemarkForm textarea[name='remark']").val("");
    });
    //添加或保存备注
    $("#addRemarkBtn").on("click", function () {
        var params = $("#addRemarkForm").serialize();
        console.log(params);
        $.post("<?php echo Url::toRoute(['/mails/walmartinboxsubject/addremark']) ?>", params, function (data) {
            if (data["code"] == 1) {
                var data = data["data"];
                var htm = '<li class="tag label btn-info md ion-close-circled"><span style="cursor: pointer;" class="remark showAddRemark" data-id="' + data["id"] + '">' + data["remark"] + '</span>&nbsp;&nbsp;<a href="javascript:void(0)" class="clearRemarkBtn" data-id="' + data["id"] + '">x</a></li>';

                $("#remark_" + data["id"]).html(htm);

                $("#addRemarkModal").modal("hide");
            } else {
                layer.alert(data["message"]);
            }
        }, "json");
        return false;
    });

    //清空备注
    $("#page-wrapper-inbox").on("click", "a.clearRemarkBtn", function (e) {
        var id = $(this).attr("data-id");
        var parent = $(this).parent();
        $.post("<?php echo Url::toRoute(['/mails/walmartinboxsubject/clearremark']) ?>", {
            "id": id
        }, function (data) {
            if (data["code"] == 1) {
                var htm = '<i class="fa remark fa-pencil showAddRemark" style="cursor: pointer;" data-id="' + id + '"></i>';
                $("#remark_" + id).html(htm);
            } else {
                layer.alert(data["message"]);
            }
        }, "json");
        e.stopPropagation();
        return false;
    });

    //显示feedback详情
    $(".feedback").on("click", function () {
        var rating = $("input[name='rating']").val();
        var comments = $("input[name='comments']").val();
        $("#feedbackModal").modal("show");

        $(".rating").html(rating);
        $(".comments").html(comments);

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
        }
    });

    function verified(id) {
        $.ajax({
            url: "/aftersales/domesticreturngoods/getreturngoods",
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

    /**
     * 添加 或者修改备注功能
     * @author huwenjun
     */
    $(document).on('click', '.subject_remark', function () {
        var id = $(this).attr('data');
        var remark = $(this).attr('data1');//默认备注
        if (remark == '') {
            remark = $(this).text();
        }
        if (id == '') {
            layer.msg('参数缺失，请检查后再提交！', {icon: 5});
        }
        layer.prompt({title: 'walmart邮件主题备注', value: remark, formType: 2}, function (text, index) {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['operationremark']); ?>',
                data: {'id': id, 'remark': text},
                success: function (data) {
                    if (data.status) {
                        layer.msg(data.info, {icon: 1});
                        var htm = '<li class="tag label btn-info md ion-close-circled"><span style="cursor: pointer;word-break:normal;white-space:pre-wrap;" class="subject_remark" data="' + id + '" data1="">' + text + '</span>&nbsp;' +
                                '<a href="javascript:void(0)" class="remove_subject_remark" data-id="' + id + '">x</a></li>';
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
     * 删除邮件备注功能
     * @author huwenjun
     */

    $(document).on('click', '.remove_subject_remark', function () {
        var id = $(this).data('id');
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
                        var htm = '<i class="fa subject_remark fa-pencil" style="cursor: pointer;" data="' + id + '" data1=""></i>';
                        $("#remark_" + id).html(htm);
                    } else {
                        layer.msg(data.info, {icon: 5});
                    }
                }
            });
        }, function () {

        });
    });
</script>
