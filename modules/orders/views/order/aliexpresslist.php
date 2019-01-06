<?php

use yii\helpers\Url;
use app\modules\orders\models\Order;
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\mails\models\EbayInquiry;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\MailTemplate;
use app\modules\mails\models\EbayInboxSubject;
use app\common\VHelper;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use app\components\LinkPager;
use yii\helpers\Html;
use app\modules\services\modules\aliexpress\models\AliexpressOrder;

$this->title = 'Aliexpress订单';
?>
<style>
    .select2-container--krajee {
        min-width: 155px !important;
    }
</style>
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

    .col-sm-5 {
        width: auto;
    }

    .tr_q .dropdown-menu {
        left: -136px;
    }

    .tr_h .dropdown-menu {
        left: -392px;
    }

    #updateShopOrderStatusOverlay {
        display: none;
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0px;
        left: 0px;
        z-index: 9999;
        filter: alpha(opacity=60);
        background-color: #333;
        opacity: 0.6;
        -moz-opacity: 0.6;
    }

    #updateShopOrderStatusSpeed {
        position: absolute;
        width: 480px;
        height: 360px;
        top: 50%;
        left: 50%;
        margin-left: -240px;
        margin-top: -180px;
        z-index: 10000;
        overflow-y: auto;
    }

    #updateShopOrderStatusSpeed p.success {
        line-height: 30px;
        color: #5cb85c;
        font-size: 20px;
        font-weight: bold;
    }

    #updateShopOrderStatusSpeed p.error {
        line-height: 30px;
        color: #d9534f;
        font-size: 20px;
        font-weight: bold;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="well">
                <input type="hidden" name="platform_code"
                       value="<?php echo \app\modules\accounts\models\Platform::PLATFORM_CODE_ALI; ?>">
                <form id="search-form" class="form-horizontal" action="<?php echo \Yii::$app->request->getUrl(); ?>"
                      method="get" role="form">
                    <input type="hidden" name="sortBy" value="">
                    <input type="hidden" name="sortOrder" value="">
                    <ul class="list-inline">
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">查询条件</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="condition_option"
                                            style="min-width:150px">

                                        <option value="buyer_id"
                                                <?php if ($condition_option == 'buyer_id') { ?>selected<?php } ?>>默认查询条件
                                        </option>
                                        <option value="item_id"
                                                <?php if ($condition_option == 'item_id') { ?>selected<?php } ?>>ItemId
                                        </option>
                                        <option value="package_id"
                                                <?php if ($condition_option == 'package_id') { ?>selected<?php } ?>>包裹号
                                        </option>
                                        <option value="sku" <?php if ($condition_option == 'sku') { ?>selected<?php } ?>>
                                            sku
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 15px">
                            <div class="form-group"><label class="control-label col-lg-5" for="">查询条件值</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="condition_value" style="width:150px"
                                           value="<?php echo $condition_value; ?>">
                                </div>
                            </div>
                        </li>
                        <li style="margin-left:75px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">订单状态</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="complete_status" style="width:150px">
                                        <option value="">全部</option>
                                        <?php foreach ($complete_status_list as $key => $status) { ?>
                                            <option value="<?= $key; ?>" <?php if (strlen($complete_status) > 0 && $key == $complete_status) echo 'selected="selected"' ?>><?= $status ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">选择时间</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="get_date"
                                            style="min-width:250px">
                                        <option value="order_time"
                                                <?php if ($get_date == 'order_time') { ?>selected<?php } ?>>下单时间
                                        </option>
                                        <option value="shipped_date"
                                                <?php if ($get_date == 'shipped_date') { ?>selected<?php } ?>>发货时间
                                        </option>
                                        <option value="paytime"
                                                <?php if ($get_date == 'paytime') { ?>selected<?php } ?>>付款时间
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 70px;">
                            <div class="form-group" style="width:235px">
                                <?php
                                echo DateTimePicker::widget([
                                    'name' => 'begin_date',
                                    'options' => ['placeholder' => ''],
                                    'value' => $begin_date,
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
                        <li style="margin-left: 20px;">
                            <div class="form-group" style="width:235px">
                                <?php
                                echo DateTimePicker::widget([
                                    'name' => 'end_date',
                                    'options' => ['placeholder' => ''],
                                    'value' => $end_date,
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


                        <li style="margin-left: 10px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">账号</label>
                                <div class="col-lg-7">
                                    <?php
                                    echo Select2::widget([
                                        'name' => 'account_ids',
                                        'value' => $account_ids,
                                        'data' => $ImportPeople_list,
                                        'options' => ['placeholder' => '请选择...']
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 30px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">仓库类型</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="warehouse_type"
                                            style="min-width:136px">
                                        <option value="">全部
                                        </option>  
                                        <option value="易佰东莞仓库"
                                                <?php if ($warehouse_type == '易佰东莞仓库') { ?>selected<?php } ?>>国内仓
                                        </option>
                                        <option value="12"
                                                <?php if ($warehouse_type == '12') { ?>selected<?php } ?>>海外仓
                                        </option>
                                        <option value="海外虚拟仓"
                                                <?php if ($warehouse_type == '海外虚拟仓') { ?>selected<?php } ?>>虚拟仓
                                        </option>
                                        <option value="代销"
                                                <?php if ($warehouse_type == '代销') { ?>selected<?php } ?>>代销仓
                                        </option>
                                        <option value="中转"
                                                <?php if ($warehouse_type == '中转') { ?>selected<?php } ?>>中转仓
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 30px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">发货仓库</label>
                                <div class="col-lg-7">
                                    <?php
                                    echo Select2::widget([
                                        'name' => 'warehouse_id',
                                        'value' => $warehouse_id,
                                        'data' => $warehouse_name_list,
                                        'options' => ['placeholder' => '请选择...']
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 5px">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">出货方式</label>
                                <div class="col-lg-7">
                                    <div class="col-lg-7">
                                        <?php
                                        echo Select2::widget([
                                            'name' => 'ship_code',
                                            'value' => $ship_code,
                                            'data' => $ship_code_list,
                                            'options' => ['placeholder' => '请选择...']
                                        ]);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 45px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">目的国</label>
                                <div class="col-lg-7">
                                    <?php
                                    echo Select2::widget([
                                        'name' => 'ship_country',
                                        'value' => $ship_country,
                                        'data' => $ship_country_list,
                                        'options' => ['placeholder' => '请选择...']
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 75px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">店铺订单状态</label>
                                <div class="col-lg-7">
                                    <?php
                                    echo Select2::widget([
                                        'name' => 'order_status',
                                        'value' => $order_status,
                                        'data' => $order_status_list,
                                        'options' => ['placeholder' => '请选择...']
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: -281px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">订单金额</label>
                                <div class="col-lg-7" style="width: 172px;">
                                    <input type="text" class="form-control" name="start_money" style="width:92px"
                                           value="<?php echo $start_money; ?>"  placeholder="最低金额">                                  
                                </div><span style="margin-left: -54px;">--</span>
                                <div class="col-lg-7" style="margin-left: 492px;
                                     width: 182px;
                                     margin-top: -34px;">
                                    <input type="text" class="form-control" name="end_money" style="width:92px"
                                           value="<?php echo $end_money; ?>" placeholder="最高金额">
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
                <button class="batch-reply btn btn-default" data-src="id"><span>批量回复</span></button>
            </div>
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class="btn btn-success" id="download" target="_blank" href="/orders/order/download">下载数据</a>
            </div>
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <button class="batch-cancel-order btn btn-default"
                        <span>恢复永久作废</span></button>
            </div>
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <button class="batch-priority-distribution-warehouse btn btn-default"
                        ><span>优先配库</span></button>
            </div>
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <button class="batch-manually-distribution-warehouse btn btn-default"
                        ><span>手动配库</span></button>
            </div>
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <button class="batch-manually-push-warehouse btn btn-default"><span>手动推送仓库</span></button>
            </div>
        </div>

        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <button id="batchUpdateShopOrderStatus" class="btn btn-primary"><span>更新店铺订单状态</span></button>
            </div>
        </div>

        <table class="table table-striped table-bordered">
            <tr>
                <td><input type="checkbox" id="all" class="all"></td>
                <td>订单号</td>
                <td>平台订单号</td>
                <td>订单金额</td>
                <td>订单状态</td>
                <td>订单类型</td>
                <td>发货类型</td>
                <td>买家ID</td>
                <td>付款时间</td>
                <td>发货时间</td>
                <td>下单时间</td>
                <td>物流单号</td>
                <td>发货仓库</td>
                <td>发货方式</td>
                <td>纠纷状态</td>
                <td>退款</td>
                <td>售后问题</td>
                <td>店铺订单状态</td>
                <td>操作</td>
            </tr>

            <?php if (!empty($orders)) { ?>
                <?php foreach ($orders as $item) {
                    $account_info = \app\modules\accounts\models\Account::getHistoryAccountInfo($item['account_id'], 'ALI');
                    ?>
                    <tr>
                        <?php if (!empty($item['platform_order_id']) && !empty($item['account_id']) && !empty($item['buyer_user_id'])) { ?>
                            <td>
                                <input name="order_id[]"
                                       data-orderid="<?php echo $item['platform_order_id']; ?>"
                                       data-exportorderid="<?php echo $item['order_id'] ?>"
                                       data-accountid="<?php echo $item['account_id']; ?>"
                                       data-buyeruserid="<?php echo isset($item['buyer_user_id']) ? $item['buyer_user_id'] : ""; ?>"
                                       type="checkbox" class="sel openSendMsgBtnBatch">
                            </td>

                        <?php } else { ?>
                            <td></td>

                        <?php } ?>
                        <td>
                            <?php 
                            echo isset($account_info->account_short_name) ? $account_info->account_short_name . '--' : '';
                            echo isset($item['order_id']) ? $item['order_id'] : ""; ?>
                        </td>
                        <td>
                            <a _width="100%" _height="100%" class="edit-button platform_order_id"
                               data-orderid="<?php echo $item['platform_order_id']; ?>"
                               href="<?php
                               echo Url::toRoute(['/orders/order/orderdetails',
                                   'order_id' => $item['platform_order_id'],
                                   'platform' => $platform,
                                   'system_order_id' => isset($item['order_id']) ? $item['order_id'] : ""]);
                               ?>"
                               title="订单信息">
                                   <?php echo $item['platform_order_id']; ?>
                            </a>
                        </td>
                        <td><?php echo $item['total_price'] . $item['currency']; ?></td>
                        <td><?php echo $item['complete_status_text']; ?></td>
                        <td><?php echo (isset($item['order_type']) && !empty($item['order_type'])) ? VHelper::getOrderType($item['order_type']) : "-"; ?></td>
                        <td><?php echo $item['ship_code']; ?></td>
                        <td>
                            <?php echo $item['buyer_id']; ?>

                            <?php if (!empty($item['platform_order_id']) && !empty($item['buyer_user_id']) && !empty($item['account_id'])) { ?>
                                <p><a class="btn btn-info btn-xs openSendMsgBtn"
                                      data-orderid="<?php echo $item['platform_order_id']; ?>"
                                      data-buyeruserid="<?php echo $item['buyer_user_id']; ?>"
                                      data-accountid="<?php echo $item['account_id']; ?>">发送消息</a></p>
                                <?php } ?>
                        </td>
                        <td>
                            <?php
                            if ($item['payment_status'] == 0) {
                                echo "未付款";
                            } else {
                                echo $item['paytime'];
                            }
                            ?>
                        </td>
                        <td><?= $item['shipped_date'] ?></td>
                        <td><?= $item['created_time'] ?></td>
                        <td><?php echo $item['track_number'] ?></td>
                        <td><?php echo $item['warehouse'] ?></td>
                        <td><?php echo $item['logistics'] ?></td>

                        <td>
                            <?php
                            $issueStatus = AliexpressOrder::getOrderIssueStatus($item['platform_order_id'], $item['account_id']);
                            $disputes = AliexpressDisputeList::getOrderDisputes($item['platform_order_id']);
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
                        <td>
                            <?php
                            if ($item['refund_status'] == 0)
                                echo '<span class="label label-success">无</span>';
                            else if ($item['refund_status'] == 1)
                                echo '<span class="label label-danger">部分退款</span>';
                            else
                                echo '<span class="label label-danger">全部退款</span>';
                            ?>
                        </td>
                        <td>
                            <?php
                            //显示退款 退货 国内退件 海外退件
                            $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder($platform, $item['order_id']);
                            if ($aftersaleinfo) {
                                $res = AfterSalesOrder::getAfterSalesOrderByOrderId($item['order_id'], $item['platform_code']);
                                //获取售后单信息
                                if (!empty($res['refund_res'])) {
                                    $refund_res = '退款';
                                    foreach ($res['refund_res'] as $refund_re) {
                                        $refund_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                                                $refund_re['after_sale_id'] . '&platform_code=' . $platform . '&status=' . $aftersaleinfo->status . '" >' .
                                                $refund_re['after_sale_id'] . '</a>';
                                    }
                                } else {
                                    $refund_res = '';
                                }

                                if (!empty($res['return_res'])) {
                                    $return_res = '退货';
                                    foreach ($res['return_res'] as $return_re) {
                                        $return_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id=' .
                                                $return_re['after_sale_id'] . '&platform_code=' . $platform . '&status=' . $aftersaleinfo->status . '" >' .
                                                $return_re['after_sale_id'] . '</a>';
                                    }
                                } else {
                                    $return_res = '';
                                }
                                if (!empty($res['redirect_res'])) {
                                    $redirect_res = '重寄';
                                    foreach ($res['redirect_res'] as $redirect_re) {
                                        $redirect_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id=' .
                                                $redirect_re['after_sale_id'] . '&platform_code=' . $platform . '&status=' . $aftersaleinfo->status . '" >' .
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
                                    } elseif ($res['domestic_return']['state'] == 4){
                                        $state = '驳回EPR';
                                    } else {
                                        $state = '暂不处理';
                                    }
                                    //状态：1、未处理，2、无需处理，3、已处理，4、驳回EPR
                                    $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                                            $res['domestic_return']['return_number'] . '&platform_code=' . $platform . '" >' .
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
                                echo '<span class="label label-success">无</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?= $order_status_list[$item['order_status']] ?>
                        </td>
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
                                    <!--缺货的，暂扣的，异常 初始化 正常 已备货 超期 待发货 10 25 5 0 1 13 15 17-->
                                    <?php if (in_array($item['complete_status'], [0, 1, 5, 10, 13, 15, 17, 25, 99])) { ?>
                                        <li><a _width="30%" _height="60%" class="edit-button"
                                               href="<?php
                                               echo Url::toRoute(['/orders/order/cancelorder',
                                                   'order_id' => $item['order_id'], 'platform' => $platform]);
                                               ?>">永久作废</a>
                                        </li>
                                    <?php } ?>

                                    <?php if ($item['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) { ?>

                                        <li><a _width="30%" _height="60%" class="edit-button"
                                               href="<?php
                                               echo Url::toRoute(['/orders/order/holdorder',
                                                   'order_id' => $item['order_id'], 'platform' => $platform]);
                                               ?>">暂时作废</a>
                                        </li>
                                        <?php
                                    }
                                    if ($item['complete_status'] == Order::COMPLETE_STATUS_HOLD) {
                                        ?>
                                        <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                               href="<?php
                                               echo Url::toRoute(['/orders/order/cancelholdorder',
                                                   'order_id' => $item['order_id'], 'platform' => $platform]);
                                               ?>">取消暂时作废</a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <?php if ($item['order_type'] != Order::ORDER_TYPE_REDIRECT_ORDER) { ?>
                                        <li>
                                            <a style=" cursor: pointer;"
                                               onclick="verified('<?php echo $item['order_id']; ?>')">新建售后单</a>
                                            <a style="display: none;" id="orderadd_<?php echo $item['order_id']; ?>" _width="100%" _height="100%"
                                               class="edit-button"
                                               href="<?php
                                               echo Url::toRoute(['/aftersales/order/add',
                                                   'order_id' => $item['order_id'], 'platform' => $platform]);
                                               ?>">新建售后单</a>
                                        </li>
                                    <?php } ?>
                                    <li><a _width="100%" _height="100%" class="edit-button"
                                           href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $platform]); ?>">登记退款单</a>
                                    </li>

                                    <li>
                                        <a _width="50%" _height="80%" class="edit-button"
                                           href="<?php echo Url::toRoute(['/orders/order/invoice', 'order_id' => $item['order_id'], 'platform' => $platform]); ?>">发票</a>
                                    </li>
                                    <li><a _width="100%" _height="100%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/aftersales/complaint/register', 'order_id' => $item['order_id'], 'platform' => $platform]); ?>">登记客诉单</a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="19">暂无指定条件的订单</td>
                </tr>
            <?php } ?>
        </table>
        <?php
        echo LinkPager::widget([
            'pagination' => $page,
            'firstPageLabel' => '首页',
            'lastPageLabel' => '尾页',
            'nextPageLabel' => '下一页',
            'prevPageLabel' => '上一页',
        ]);
        ?>
    </div>
</div>
<!--单个发生站内信-->
<div class="modal fade in" id="sendMsgModal" tabindex="-1" role="dialog" aria-labelledby="sendMsgModalLabel"
     style="top:300px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">发送消息</h4>
            </div>
            <div class="modal-body">
                <form id="sendMsgForm">
                    <div class="row">
                        <div class="col col-lg-12">
                            <textarea class="form-control" rows="5" name="msg"></textarea>
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
<!--批量发生站内信-->
<div class="modal fade in" id="batchsendMsgModal" tabindex="-1" role="dialog" aria-labelledby="sendMsgModalLabel"
     style="top:300px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">发送消息</h4>
            </div>
            <div class="modal-body">
                <form id="sendMsgFormBatch">
                    <div class="row">
                        <div class="col col-lg-12">
                            <textarea class="form-control" rows="5" id="msgs" name="msgs"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="sendMsgBtnBatch">发送</button>
                <button type="button" class="btn btn-default" id="closeModel" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<div id='updateShopOrderStatusOverlay'>
    <div id='updateShopOrderStatusSpeed'></div>
</div>
<script src="<?php echo yii\helpers\Url::base(true); ?>/js/currency.js"></script>
<script type="text/javascript">
                                                   $(function () {
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

                                                       //批量更新店铺订单状态
                                                       $("#batchUpdateShopOrderStatus").on("click", function () {
                                                           var checkBox = $("input[name^='order_id']:checked");
                                                           if (checkBox.length == 0) {
                                                               layer.alert("请选择更新项");
                                                               return false;
                                                           }

                                                           var defer = $.Deferred();
                                                           defer.resolve($("#updateShopOrderStatusSpeed").html("<p class='success'>店铺订单状态更新开始</p>"));
                                                           $("#updateShopOrderStatusOverlay").css("display", "block");
                                                           $("body").css("overflow", "hidden");

                                                           checkBox.each(function () {
                                                               //获取当前行的平台订单ID
                                                               var orderId = $(this).parents("tr").find("a.platform_order_id").attr("data-orderid");
                                                               defer = defer.then(function () {
                                                                   return $.ajax({
                                                                       type: "POST",
                                                                       url: "<?php echo Url::toRoute(['/orders/order/updatealishoporderstatus']); ?>",
                                                                       data: {"order_id": orderId},
                                                                       dataType: "json",
                                                                       global: false,
                                                                       success: function (data) {
                                                                           if (data["code"] == 1) {
                                                                               $("#updateShopOrderStatusSpeed").append("<p class='success'>订单ID：" + data["data"]["order_id"] + ",更新成功</p>");
                                                                           } else {
                                                                               $("#updateShopOrderStatusSpeed").append("<p class='error'>订单ID：" + data["data"]["order_id"] + "," + data["message"] + "</p>");
                                                                           }
                                                                       }
                                                                   });
                                                               });
                                                           });

                                                           defer.done(function () {
                                                               $("#updateShopOrderStatusSpeed").append("<p class='success'>店铺订单状态更新完毕</p>");
                                                               setTimeout(function () {
                                                                   $("#updateShopOrderStatusOverlay").css("display", "none");
                                                                   window.location.href = window.location.href;
                                                               }, 500);
                                                           });
                                                           return false;
                                                       });
                                                   });
                                                   var platform_code = $("input[name=platform_code]").val();
                                                   var condition_option = $("select[name='condition_option']").val();//选择的查询条件
                                                   var condition_value = $("input[name='condition_value']").val();
                                                   var get_date = $("select[name='get_date']").val();//选择的时间
                                                   var begin_date = $("input[name='begin_date']").val();//开始时间
                                                   var end_date = $("input[name='end_date']").val();//结束时间
                                                   var account_ids = $("select[name='account_ids']").val();//账号id
                                                   var ship_code = $("select[name='ship_code']").val();//运输方式
                                                   var ship_country = $("select[name='ship_country']").val();
                                                   var order_status = $("select[name='order_status']").val();//店铺订单状态
                                                   var complete_status = $("select[name=complete_status]").val();

                                                   //批量发送站内信
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

                                                   $("#download").click(function () {
                                                       //平台订单ID&账号ID&买家登陆ID 组合一个字符串
                                                       var url = $(this).attr('href');
                                                       var selectIds = '';
                                                       $(":checked.sel").each(function () {
                                                           if (selectIds == '') {
                                                               if ($(this).prop('checked') == true) {
                                                                   selectIds = $(this).data('exportorderid');
                                                               }
                                                           } else {
                                                               if ($(this).prop('checked') == true) {
                                                                   selectIds += ',' + $(this).data('exportorderid');
                                                               }
                                                           }
                                                       });
                                                       //如果选中则只下载选中数据
                                                       if (selectIds != "") {
                                                           url += '?platform_code=' + platform_code + '&json=' + selectIds;
                                                       } else {
                                                           url += '?platform_code=' + platform_code + '&condition_option=' + condition_option
                                                                   + '&condition_value=' + condition_value + '&account_ids=' + account_ids + '&warehouse_id=' + warehouse_id
                                                                   + '&get_date=' + get_date + '&end_date=' + end_date + '&begin_date=' + begin_date
                                                                   + '&ship_code=' + ship_code + '&complete_status' + complete_status
                                                                   + '&ship_country=' + ship_country + '&currency=' + currency;
                                                       }
                                                       window.open(url);
                                                   });

                                                   $(".batch-reply").bind("click", function () {
                                                       //平台订单ID&账号ID&买家登陆ID 组合一个字符串
                                                       var three_ids = '';
                                                       $(":checked.sel").each(function () {
                                                           if (three_ids == '') {
                                                               if ($(this).prop('checked') == true) {
                                                                   three_ids = $(this).data('orderid') + '&' + $(this).data('accountid') + '&' + $(this).data('buyeruserid');
                                                               }
                                                           } else {
                                                               if ($(this).prop('checked') == true) {
                                                                   three_ids += ',' + $(this).data('orderid') + '&' + $(this).data('accountid') + '&' + $(this).data('buyeruserid');
                                                               }
                                                           }
                                                       });
                                                       $("#batchsendMsgModal").modal("show");

                                                       if (three_ids == '') {
                                                           $("#sendMsgBtnBatch").click(function () {
                                                               //获取内容
                                                               var msg = $("#msgs").val();
                                                               if (msg == '') {
                                                                   layer.msg('消息内容不能为空', {icon: 5});
                                                                   return;
                                                               }
                                                               $.post("<?php echo Url::toRoute(['/mails/aliexpresssendmsg/sendmsgs']); ?>",
                                                                       {
                                                                           "condition_option": condition_option,
                                                                           'condition_value': condition_value,
                                                                           'get_date': get_date,
                                                                           'begin_date': begin_date,
                                                                           'end_date': end_date,
                                                                           'account_ids': account_ids,
                                                                           'ship_code': ship_code,
                                                                           'ship_country': ship_country,
                                                                           'order_status': order_status,
                                                                           'msg': msg
                                                                       }, function (data) {
                                                                   if (data["code"] == 1) {
                                                                       layer.alert("消息发送成功");
                                                                       $("#batchsendMsgModal").modal("hide");
                                                                   } else {
                                                                       layer.alert(data["message"]);
                                                                   }
                                                               }, "json");
                                                               return false;
                                                           });
                                                       } else {
                                                           $("#sendMsgBtnBatch").click(function () {
                                                               //获取内容
                                                               var msg = $("#msgs").val();
                                                               if (msg == '') {
                                                                   layer.msg('消息内容不能为空', {icon: 5});
                                                                   return;
                                                               }
                                                               $.post("<?php echo Url::toRoute(['/mails/aliexpresssendmsg/sendmsgs']); ?>",
                                                                       {
                                                                           "three_ids": three_ids,
                                                                           'msg': msg
                                                                       }, function (data) {
                                                                   if (data["code"] == 1) {
                                                                       layer.alert("消息发送成功");
                                                                       $("#batchsendMsgModal").modal("hide");
                                                                   } else {
                                                                       layer.alert(data["message"]);
                                                                   }
                                                               }, "json");
                                                               return false;
                                                           });
                                                       }
                                                   });

                                                   $("#closeModel").click(function () {
                                                       //清空数据
                                                       $("#sendMsgFormBatch textarea[name='msgs']").val("");
                                                   })

                                                   //批量操作erpapi
                                                   $(".batch-cancel-order,.batch-priority-distribution-warehouse,.batch-manually-distribution-warehouse,.batch-manually-push-warehouse").on('click', function () {
                                                       var this_class = $(this).attr('class');
                                                       if (this_class.match('batch-cancel-order')) {
                                                           var url = "<?php echo Url::toRoute(['/orders/order/ordertoinit']); ?>";
                                                       } else if (this_class.match('batch-manually-push-warehouse')) {
                                                           var url = "<?php echo Url::toRoute(['/orders/order/batchsendorde']); ?>";
                                                       } else if (this_class.match('batch-priority-distribution-warehouse')) {
                                                           var url = "<?php echo Url::toRoute(['/orders/order/setprioritystatus']); ?>";
                                                       } else if (this_class.match('batch-manually-distribution-warehouse')) {
                                                           var url = "<?php echo Url::toRoute(['/orders/order/batchallotstock']); ?>";
                                                       }
                                                       var selectIds = '';
                                                       $(":checked.sel").each(function () {
                                                           if (selectIds == '') {
                                                               if ($(this).prop('checked') == true) {
                                                                   selectIds = $(this).data('orderid');
                                                               }
                                                           } else {
                                                               if ($(this).prop('checked') == true) {
                                                                   selectIds += ',' + $(this).data('orderid');
                                                               }
                                                           }
                                                       });
                                                       if (selectIds == '') {
                                                           $.post(url,
                                                                   {
                                                                       "platform_code": platform_code,
                                                                       'condition_option': condition_option,
                                                                       'condition_value': condition_value,
                                                                       'account_ids': account_ids,
                                                                       'warehouse_id': warehouse_id,
                                                                       'get_date': get_date,
                                                                       'end_date': end_date,
                                                                       'begin_date': begin_date,
                                                                       'ship_code': ship_code,
                                                                       'ship_country': ship_country,
                                                                       'currency': currency,
                                                                       'complete_status': complete_status
                                                                   }, function (data) {
                                                               if (data.code == 200) {
                                                                   layer.msg(data.message, {icon: 6});
                                                                   location.reload();
                                                               } else {
                                                                   layer.msg(data.message, {icon: 5});
                                                               }
                                                           }, "json");
                                                           return false;
                                                       } else {
                                                           //选择的订单作废恢复
                                                           $.post(url,
                                                                   {
                                                                       "selectIds": selectIds,
                                                                       'platform_code': platform_code,
                                                                   }, function (data) {
                                                               if (data.code == 200) {
                                                                   layer.msg(data.message, {icon: 6});
                                                                   location.reload();
                                                               } else {
                                                                   layer.msg(data.message, {icon: 5});
                                                               }
                                                           }, "json");
                                                           return false;
                                                       }
                                                   });
</script>