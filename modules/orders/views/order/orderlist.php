<?php

use yii\helpers\Url;
use app\modules\orders\models\Order;
use app\common\VHelper;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use app\components\LinkPager;
use app\modules\orders\models\Tansaction;
use app\modules\orders\models\PaypalInvoiceRecord;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\MailTemplate;

$this->title = '订单查询';
?>
<style>
    .select2-container--krajee {
        min-width: 155px !important;
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
        text-decoration: underline;
    }
    .created_time{
        cursor: pointer;
    }
    .paytime{
        cursor: pointer;
    }
    .shipped{
        cursor: pointer;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="well">
                <form id="search-form" class="form-horizontal" action="<?php echo \Yii::$app->request->getUrl(); ?>"
                      method="get" role="form">
                    <input type="hidden" name="sortBy" value="">
                    <input type="hidden" name="sortOrder" value="">
                    <ul class="list-inline">
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">所属平台</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="platform_codes">
                                        <?php foreach ($platformList as $code => $value) { ?>
                                            <option value="<?php echo $code; ?>" <?php if ($code == $platformCode) echo 'selected="selected"'; ?>><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">查询条件</label>
                                <div class="col-lg-7"><select class="form-control" name="condition_option"
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
                                        <option value="paypal_id"
                                                <?php if ($condition_option == 'paypal_id') { ?>selected<?php } ?>>
                                            paypal交易号
                                        </option>
                                        <option value="sku" <?php if ($condition_option == 'sku') { ?>selected<?php } ?>>
                                            sku
                                        </option>

                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">查询条件值</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="condition_value" style="width:150px;"
                                           value="<?php echo $condition_value; ?>">
                                </div>
                            </div>
                        </li>
                        <li style="margin-left:45px;">
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
                        <li style="margin-left: 30px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">发货方式</label>
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
                        <li style="margin-left: 1px;">
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
                        <li style="margin-left: 1px;" class="smt_currency">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">货币类型</label>
                                <div class="col-lg-7">
                                    <?php
                                    echo Select2::widget([
                                        'name' => 'currency',
                                        'value' => $currency,
                                        'data' => $currency_list,
                                        'options' => ['placeholder' => '请选择...']
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>

                        <li style="margin-left: 75px;" class="smt_order_status">
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
                        <li>
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">订单类型</label>
                                <div class="col-lg-7">
                                    <?php
                                    echo Select2::widget([
                                        'name' => 'order_type',
                                        'value' => $order_type,
                                        'data' => $order_type_lists,
                                        'options' => ['placeholder' => '请选择...']
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>
                        <!--                        订单备注-->
                        <li>
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">订单备注</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="remark" style="width:150px;"
                                           value="<?php echo $remark ?>">
                                </div>
                            </div>
                        </li>
                        <!--                        添加item location-->
                        <li style="margin-left: 20px" class="item_location">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">Item Location</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="item_location" style="width:150px;"
                                           value="<?php echo $item_location ?>">
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
        <?php
        if (Yii::$app->request->getQueryParam('platform_codes') == 'ALI') {
            echo " <div class=\"bs-bars pull-left\">
            <div id=\"\" class=\"btn-group\">
                <button class=\"batch-reply btn btn-default\" data-src=\"id\"><span>批量回复</span></button>
            </div>
        </div>";
        }
        ?>

        <?php
        if (Yii::$app->request->getQueryParam('platform_codes') == 'EB') {
            echo "<div class=\"bs-bars pull-left\">
            <div id=\"\" class=\"btn-group\">
                <button class=\"batch-reply_ebay btn btn-default\" data-src=\"id\"><span>批量回复</span></button>
            </div>
        </div>";
        }
        ?>

        <?php
        if (Yii::$app->request->getQueryParam('platform_codes') == 'AMAZON') {
            echo "<div class=\"bs-bars pull-left\">
            <div id=\"\" class=\"btn-group\">
                <button class=\"batch-reply_amazon btn btn-default\" data-src=\"id\"><span>批量回复</span></button>
            </div>
        </div>";
        }
        ?>

        <?php
        if (Yii::$app->request->getQueryParam('platform_codes') == 'CDISCOUNT') {
            echo "<div class=\"bs-bars pull-left\">
            <div id=\"\" class=\"btn-group\">
                <button class=\"batch-reply_cd btn btn-default\" data-src=\"id\"><span>批量回复</span></button>
            </div>
        </div>";
        }
        ?>

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
        <div class="bs-bars pull-left" id="showBatchUpdateShopOrderStatus" style="display:none;">
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
                <td>目的国</td>
                <td>买家ID</td>
                <td class="paytime"><input type="hidden" name="paytime" id="paytime" value="<?php echo $paytime_state; ?>">付款时间</td>            
                  <td class="shipped"><input type="hidden" name="shipped" id="shipped" value="<?php echo $shipped_state; ?>">发货时间</td>
                <td class="created_time"><input type="hidden" name="created_time" id="created_time" value="<?php echo $created_state; ?>">下单时间</td>
                <td>产品总金额</td>
                <td>物流单号</td>
                <td>发货仓库</td>
                <td>发货方式</td>
                <td>币种</td>
                <td>成交费用</td>
                <td>纠纷状态</td>
                <td>退款</td>
                <td>售后问题</td>
                <?php
                if (Yii::$app->request->getQueryParam('platform_codes') == 'ALI') {
                    echo " <td>店铺订单状态</td>";
                }
                ?>
                <?php
                if (Yii::$app->request->getQueryParam('platform_codes') == 'EB') {

                    echo "<td>评价状态</td><td>站内信</td><td>Item Location</td>";
                }
                ?>
                <?php
                if (Yii::$app->request->getQueryParam('platform_codes') == 'AMAZON') {
                    echo " <td>联系买家</td>";
                }
                ?>
                <td>订单备注</td>
                <td>操作</td>
            </tr>
            <?php if (!empty($orders)) { ?>
                <?php
                foreach ($orders as $item) {
                    $account_info = Account::getHistoryAccountInfo($item['account_id'], $item['platform_code']);
                    ?>
                    <tr>
                        <td>
                            <input name="order_id[]"
                                   data-platformorderid="<?php echo $item['platform_order_id']; ?>"
                                   data-accountid="<?php echo $item['account_id']; ?>"
                                   data-buyeruserid="<?php echo isset($item['buyer_user_id']) ? $item['buyer_user_id'] : ""; ?>"
                                   data-orderid="<?php echo $item['order_id'] ?>"
                                   data-site="<?php echo isset($item['site_code']) ? $item['site_code'] : '' ?>"
                                   data-custEmail="<?php echo $item['email'] ?>"
                                   data-platform="<?php echo $item['platform_code'] ?>"
                                   type="checkbox" class="sel ">
                        </td>
                        <td><?php
                            echo isset($account_info->account_short_name) ? $account_info->account_short_name . '--' : '';
                            echo $item['order_id'];
                            ?></td>
                        <td>
                            <a _width="100%" _height="100%" class="edit-button platform_order_id"
                               data-orderid="<?php echo $item['platform_order_id']; ?>"
                               href="<?php
                               echo Url::toRoute(['/orders/order/orderdetails',
                                   'order_id' => $item['platform_order_id'],
                                   'platform' => $item['platform_code'],
                                   'system_order_id' => $item['order_id']]);
                               ?>" title="订单信息">
                                   <?php echo $item['platform_order_id']; ?>
                            </a>
                        </td>
                        <td><?php echo $item['total_price']; ?></td>
                        <td><?php echo isset($item['complete_status_text']) ? $item['complete_status_text'] : ''; ?></td>
                        <td><?php echo (isset($item['order_type']) && !empty($item['order_type'])) ? VHelper::getOrderType($item['order_type']) : "-"; ?></td>
                        <td><?= (array_key_exists($item['ship_country'], $countryList) ? '(' . $countryList[$item['ship_country']] . ')' : '') . $item['ship_country']
                                   ?></td>
                        <td><?php echo $item['buyer_id']; ?>
                            <?php if (Yii::$app->request->getQueryParam('platform_codes') == 'ALI') { ?>
                                <?php if (!empty($item['platform_order_id']) && !empty($item['buyer_user_id']) && !empty($item['account_id'])) { ?>
                                    <p><a class="btn btn-info btn-xs openSendMsgBtn"
                                          data-orderid="<?php echo $item['platform_order_id']; ?>"
                                          data-buyeruserid="<?php echo $item['buyer_user_id']; ?>"
                                          data-accountid="<?php echo $item['account_id']; ?>">发送消息</a></p>
                                    <?php } ?>

                            <?php } ?>

                            <?php if (!empty($item['platform_order_id']) && !empty($item['email']) && !empty($account_info)) { ?>
                                <?php
                                if ($item['platform_code'] == "WALMART") {
                                    echo '<br/><a href="' . Url::toRoute(['/mails/walmartreply/getsendemail', 'account_id' => $account_info->id, 'toemail' => $item['email'], 'platform_order_id' => $item['platform_order_id']]) . '" target="_blank"> 联系买家</a>';
                                }
                                ?>
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
                        <td><?php
                            echo strtotime($item['shipped_date']) ? $item['shipped_date'] : '-';
                            ;
                            ?>
                        </td>
                        <td><?= $item['created_time'] ?></td>
                        <td><?php echo $item['subtotal_price']; ?></td>
                        <td><?php echo $item['track_number'] ?></td>
                        <td><?php echo isset($item['warehouse']) ? $item['warehouse'] : '' ?></td>
                        <td><?php echo isset($item['logistics']) ? $item['logistics'] : '' ?></td>
                        <td><?php echo $item['currency']; ?></td>
                        <td>
                            <?php echo $item['final_value_fee']; ?>
                        </td>

                        <?php if (Yii::$app->request->getQueryParam('platform_codes') == 'EB') { ?>
                            <td>
                                <?php
                                $cancel_cases = \app\modules\mails\models\EbayCancellations::disputeLevel($item['platform_order_id']);
                                $inquiry_cases = \app\modules\mails\models\EbayInquiry::disputeLevel($item['platform_order_id']);
                                $returns_cases = \app\modules\mails\models\EbayReturnsRequests::disputeLevel($item['platform_order_id'], $item);
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
                            </td>
                        <?php } ?>

                        <?php if (Yii::$app->request->getQueryParam('platform_codes') == 'ALI') { ?>
                            <td>
                                <?php
                                $issueStatus = \app\modules\services\modules\aliexpress\models\AliexpressOrder::getOrderIssueStatus($item['platform_order_id'], $item['account_id']);
                                $disputes = \app\modules\mails\models\AliexpressDisputeList::getOrderDisputes($item['platform_order_id']);
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

                        <?php if (!in_array(Yii::$app->request->getQueryParam('platform_codes'), ['EB', 'ALI'])) { ?>
                            <td>
                                <span class="label label-success">无纠纷</span>
                            </td>
                        <?php } ?>

                        <td>
                            <?php
                            $refund_amount = isset($item['refund_amount']) ? $item['refund_amount'] : 0.00;
                            if ($item['refund_status'] == 0)
                                echo '<span class="label label-success">无</span>';
                            else if ($item['refund_status'] == 1)
                                echo '<span class="label label-danger">部分退款</span><br>' . '(' . $refund_amount . ')';
                            else
                                echo '<span class="label label-danger">全部退款</span><br>' . '(' . $refund_amount . ')';
                            ?>
                        </td>

                        <td>
                            <?php
                            $aftersaleinfo = \app\modules\aftersales\models\AfterSalesOrder::hasAfterSalesOrder(Yii::$app->request->getQueryParam('platform_codes'), $item['order_id']);
                            if ($aftersaleinfo) {
                                $res = \app\modules\aftersales\models\AfterSalesOrder::getAfterSalesOrderByOrderId($item['order_id'], Yii::$app->request->getQueryParam('platform_codes'));
                                //获取售后单信息
                                if (!empty($res['refund_res'])) {
                                    $refund_res = '退款';
                                    foreach ($res['refund_res'] as $refund_re) {
                                        $refund_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                                                $refund_re['after_sale_id'] . '&platform_code=' . Yii::$app->request->getQueryParam('platform_codes') . '&status=' . $aftersaleinfo->status . '" >' .
                                                $refund_re['after_sale_id'] . '</a><br>';
                                    }
                                } else {
                                    $refund_res = '';
                                }

                                if (!empty($res['return_res'])) {
                                    $return_res = '退货';
                                    foreach ($res['return_res'] as $return_re) {
                                        $return_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id=' .
                                                $return_re['after_sale_id'] . '&platform_code=' . Yii::$app->request->getQueryParam('platform_codes') . '&status=' . $aftersaleinfo->status . '" >' .
                                                $return_re['after_sale_id'] . '</a><br>';
                                    }
                                } else {
                                    $return_res = '';
                                }

                                if (!empty($res['redirect_res'])) {
                                    $redirect_res = '重寄';
                                    foreach ($res['redirect_res'] as $redirect_re) {
                                        $redirect_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id=' .
                                                $redirect_re['after_sale_id'] . '&platform_code=' . Yii::$app->request->getQueryParam('platform_codes') . '&status=' . $aftersaleinfo->status . '" >' .
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
                                    } else {
                                        $state = '驳回EPR';
                                    }
                                    //状态：1、未处理，2、无需处理，3、已处理，4、驳回EPR
                                    $domestic_return .= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                                            $res['domestic_return']['return_number'] . '&platform_code=' . Yii::$app->request->getQueryParam('platform_codes') . '" >' .
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
                                $res = \app\modules\aftersales\models\AfterSalesOrder::getAfterSalesOrderByOrderId($item['order_id'], Yii::$app->request->getQueryParam('platform_codes'));
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
                                            $res['domestic_return']['return_number'] . '&platform_code=' . Yii::$app->request->getQueryParam('platform_codes') . '" >' .
                                            $res['domestic_return']['return_number'] . '(' . $state . ')' . '</a>';
                                    echo $domestic_return;
                                } else {
                                    echo '<span class="label label-success">无</span>';
                                }
                            }
                            ?>
                        </td>

                        <?php
                        if (Yii::$app->request->getQueryParam('platform_codes') == 'ALI') {
                            $smt_order_status_text = $order_status_list[$item['order_status']];
                            echo "<td> $smt_order_status_text</td>";
                        }
                        ?>

                        <?php if (Yii::$app->request->getQueryParam('platform_codes') == 'EB') { ?>

                            <?php
                            // 查看订单评价
                            $feedbackInfos = \app\modules\mails\models\EbayFeedback::find()->where(['order_line_item_id' => $item['platform_order_id'], 'role' => 1])->all();
                            // 如果没有找到订单评价，通过交易ID和item_id来查评价
                            if (empty($feedbackInfos) && !empty($item['detail'])) {
                                $max_comment_type = 6;
                                foreach ($item['detail'] as $detail) {
                                    $feedbackInfo = \app\modules\mails\models\EbayFeedback::getCommentByTransactionID($detail['transaction_id'], $detail['item_id']);
                                    if (!empty($feedbackInfo->comment_type) && ($feedbackInfo->comment_type < $max_comment_type)) {
                                        $feedbackInfos[] = $feedbackInfo;
                                    }
                                }
                            }
                            $comment_type = '';

                            if (!empty($feedbackInfos)) {
                                foreach ($feedbackInfos as $feedbackInfo) {
                                    switch ($feedbackInfo->comment_type) {
                                        case 1:
                                            $comment_type .= '<p><span>IndependentlyWithdrawn</span></p>';
                                            break;
                                        case 2:
                                            $comment_type .= '<p><span><a style="color:red;" href="' . Url::toRoute(['/mails/ebayfeedbackresponse/add', 'type' => 'Reply', 'id' => $feedbackInfo->id]) . '" class="edit-button" id="status">Negative</a></span></p>';
                                            break;
                                        case 3:
                                            $comment_type .= '<p><span><a style="color:orange;" href="' . Url::toRoute(['/mails/ebayfeedbackresponse/add', 'type' => 'Reply', 'id' => $feedbackInfo->id]) . '" class="edit-button" id="status">Neutral</a></span></p>';
                                            break;
                                        case 4:
                                            $comment_type .= '<p><span style="color:green">Positive</span></p>';
                                            break;
                                        case 5:
                                            $comment_type .= '<p><span>Withdrawn</span></p>';
                                            break;
                                    }
                                }
                                echo '<td>' . $comment_type . '</td>';
                            } else {
                                echo '<td><span class="label label-default">无</span></td>';
                            }
                            ?>
                            <?php
                            //站内信
                            $isSetEbayInboxSubject = \app\modules\mails\models\EbayInboxSubject::isSetEbayInboxSubject($item);
                            if (isset($isSetEbayInboxSubject['bool']) && !empty($isSetEbayInboxSubject['bool'])) {
                                foreach ($isSetEbayInboxSubject['info'] as $value) {
                                    echo '<td><a target="_blank" href="/mails/ebayinboxsubject/detail?id=' . $value['id'] . '">' . $value['item_id'] . '</a><br/></td>';
                                }
                            } else {
                                echo '<td><span class="label label-success">无</span></td>';
                            }
                            ?>
                            <td><?php echo isset($item['location']) ? $item['location'] : ''; ?></td>
                        <?php } ?>

                        <?php if (Yii::$app->request->getQueryParam('platform_codes') == 'AMAZON') { ?>
                            <?php
                            $account = Account::findOne(['old_account_id' => $item['account_id'], 'platform_code' => Platform::PLATFORM_CODE_AMAZON]);
                            if (!empty($account) && !empty($item['email'])) {
                                echo '<td><a href="' . Url::toRoute(['/mails/amazonreviewdata/getsendemail', 'account_id' => $account->id, 'toemail' => $item['email'], 'platform_order_id' => $item['platform_order_id']]) . '" target="_blank"> 联系买家</a></td>';
                            } else {
                                echo '<td>无</td>';
                            }
                            ?>
                        <?php } ?>
                        <td>
                            <?php
                            if (!empty($item['remark'])) {
                                $remark_text = '';
                                foreach ($item['remark'] as $v) {
                                    $remark_text .= $v . '<br>';
                                }
                                echo $remark_text;
                            }
                            ?>
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

                                    <!-- ebay操作-->
                                    <?php if (Yii::$app->request->getQueryParam('platform_codes') == 'EB') { ?>
                                        <?php
                                        $transaction_id[] = isset($item['trade']) ? $item['trade']['transaction_id'] : [];
                                        $invoiceInfo = PaypalInvoiceRecord::getIvoiceData($item['order_id'], $item['platform_code']);

                                        $transactionId = Tansaction::getOrderTransactionIdEbayByOrderId($item['order_id'], $item['platform_code']);
                                        ?>
                                        <?php if ($item['order_type'] == 1) { ?>
                                            <?php if (in_array($item['complete_status'], [19, 20]) || (isset($item['warehouse_type']) && in_array($item['warehouse_type'], [2, 3, 5]) && $item['complete_status'] == 13)) { ?>

                                                <!--新建售后单 发送消息 取消订单 回评 登记退款单 登记收款单 -->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayreply/initiativeadd', 'order_id' => $item['order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">发送消息</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/canceltransaction',
                                                           'orderid' => $item['order_id'],
                                                           'platform' => Yii::$app->request->getQueryParam('platform_codes'), 'account_id' => $item['account_id'],
                                                           'payment_status' => $item['payment_status'],
                                                           'paytime' => $item['paytime'], 'platform_order_id' => $item['platform_order_id'],
                                                           'transaction_id' => $transaction_id])
                                                       ?>">取消订单</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $item['platform_order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">回评</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/ebayreceipt', 'order_id' => $item['order_id'], 'platform' => $item['platform_code'], 'buyer_id' => $item['buyer_id'], 'account_id' => $item['account_id']]); ?>">登记收款单</a>
                                                </li>

                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $item['order_id'], 'platform_order_id' => $item['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $item['platform_code']]); ?>">收款</a>
                                                </li>

                                            <?php } ?>

                                            <?php if (in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25,119]) || (isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13)) { ?>

                                                <!---  永久作废 暂时作废 发送消息 取消订单 回评-->
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/canceltransaction',
                                                           'orderid' => $item['order_id'],
                                                           'platform' => Yii::$app->request->getQueryParam('platform_codes'), 'account_id' => $item['account_id'],
                                                           'payment_status' => $item['payment_status'],
                                                           'paytime' => $item['paytime'], 'platform_order_id' => $item['platform_order_id'],
                                                           'transaction_id' => $transaction_id])
                                                       ?>">取消订单</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayreply/initiativeadd', 'order_id' => $item['order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">发送消息</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $item['platform_order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">回评</a>
                                                </li>

                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $item['order_id'], 'platform_order_id' => $item['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $item['platform_code']]); ?>">收款</a>
                                                </li>

                                            <?php } ?>

                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!--- 取消暂时作废 永久作废 发送消息 取消订单（会自动永久作废订单）  回评 --->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <!--                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                                                                       href="<?php
                                                //echo Url::toRoute(['/orders/order/cancelorder',
                                                //   'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                ?>">永久作废</a>
                                                                                                </li>-->

                                                <!--                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                                                                       href="<?php
//                                                       echo Url::toRoute(['/orders/order/canceltransaction',
//                                                           'orderid' => $item['order_id'],
//                                                           'platform' => Yii::$app->request->getQueryParam('platform_codes'), 'account_id' => $item['account_id'],
//                                                           'payment_status' => $item['payment_status'],
//                                                           'paytime' => $item['paytime'], 'platform_order_id' => $item['platform_order_id'],
//                                                           'transaction_id' => $transaction_id])
                                                ?>">取消订单</a>
                                                                                                </li>-->
                                                <!--                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/mails/ebayreply/initiativeadd', 'order_id' => $item['order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]);     ?>">发送消息</a>
                                                                                                </li>-->
                                                <!--                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $item['platform_order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]);     ?>">回评</a>
                                                                                                </li>-->


                                            <?php } ?>

                                            <?php if ($item['complete_status'] == 40) { ?>
                                                <!------新建售后单（只能建退款单，不能建重寄单） 发送消息 取消订单 回评 登记退款单------>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayreply/initiativeadd', 'order_id' => $item['order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">发送消息</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/canceltransaction',
                                                           'orderid' => $item['order_id'],
                                                           'platform' => Yii::$app->request->getQueryParam('platform_codes'), 'account_id' => $item['account_id'],
                                                           'payment_status' => $item['payment_status'],
                                                           'paytime' => $item['paytime'], 'platform_order_id' => $item['platform_order_id'],
                                                           'transaction_id' => $transaction_id])
                                                       ?>">取消订单</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $item['platform_order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">回评</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>

                                                <?php if (isset($invoiceInfo)) { ?>
                                                    <li><a href="javascript:void(0)" class="cancelEbayPaypalInvoice"
                                                           data-orderid="<?php echo $item['order_id']; ?>"
                                                           data-invoiceid="<?php echo $invoiceInfo['invoice_id']; ?>"
                                                           data-invoiceemail="<?php echo $invoiceInfo['merchant_email']; ?>">取消收款</a>
                                                    </li>
                                                <?php } else { ?>
                                                    <li><a _width="80%" _height="80%" class="edit-button"
                                                           href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $item['order_id'], 'platform_order_id' => $item['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $item['platform_code']]); ?>">收款</a>
                                                    </li>
                                                <?php }; ?>
                                            <?php } ?>

                                        <?php } ?>
                                        <?php if ($item['order_type'] == 7) { ?>
                                            <?php if ((isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13) || in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25])) { ?>
                                                <!--永久作废 暂时作废-->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                            <?php } ?>

                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!--取消暂时作废 永久作废-->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>

                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>


                                        <?php if ($item['order_type'] == 3) { ?>
                                            <?php if ($item['complete_status'] == 40) { ?>
                                                <!--新建售后单（只能建退款单，不能建重寄单） 发送消息 取消订单 回评 登记退款单-->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>

                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/canceltransaction',
                                                           'orderid' => $item['order_id'],
                                                           'platform' => Yii::$app->request->getQueryParam('platform_codes'), 'account_id' => $item['account_id'],
                                                           'payment_status' => $item['payment_status'],
                                                           'paytime' => $item['paytime'], 'platform_order_id' => $item['platform_order_id'],
                                                           'transaction_id' => $transaction_id])
                                                       ?>">取消订单</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayreply/initiativeadd', 'order_id' => $item['order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">发送消息</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $item['platform_order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">回评</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>
                                                <?php if (isset($invoiceInfo)) { ?>
                                                    <li><a href="javascript:void(0)" class="cancelEbayPaypalInvoice"
                                                           data-orderid="<?php echo $item['order_id']; ?>"
                                                           data-invoiceid="<?php echo $invoiceInfo['invoice_id']; ?>"
                                                           data-invoiceemail="<?php echo $invoiceInfo['merchant_email']; ?>">取消收款</a>
                                                    </li>
                                                <?php } else { ?>
                                                    <li><a _width="80%" _height="80%" class="edit-button"
                                                           href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $item['order_id'], 'platform_order_id' => $item['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $item['platform_code']]); ?>">收款</a>
                                                    </li>
                                                <?php }; ?>
                                            <?php } ?>
                                        <?php } ?>

                                        <?php if ($item['order_type'] == 2) { ?>
                                            <?php if (in_array($item['complete_status'], [19, 20]) || ($item['complete_status'] == 13 && isset($item['warehouse_type']) && in_array($item['warehouse_type'], [2, 3, 5]))) { ?>
                                                <!--新建售后单（只能建重寄单，不能建退款单） 登记收款单-->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li>
                                                    <a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/ebayreceipt', 'order_id' => $item['order_id'], 'platform' => $item['platform_code'], 'buyer_id' => $item['buyer_id'], 'account_id' => $item['account_id']]); ?>">登记收款单</a>
                                                </li>
                                            <?php } ?>
                                            <?php if ((isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13) || in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25])) { ?>
                                                <!--永久作废 暂时作废-->
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                            <?php } ?>
                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!--取消暂时作废 永久作废-->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>

                                        <?php if ($item['order_type'] == 4) { ?>
                                            <?php if ($item['complete_status'] == 40) { ?>
                                                <!--新建售后单（只能建退款单，不能建重寄单） 发送消息 取消订单 回评 登记退款单-->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>

                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/canceltransaction',
                                                           'orderid' => $item['order_id'],
                                                           'platform' => Yii::$app->request->getQueryParam('platform_codes'), 'account_id' => $item['account_id'],
                                                           'payment_status' => $item['payment_status'],
                                                           'paytime' => $item['paytime'], 'platform_order_id' => $item['platform_order_id'],
                                                           'transaction_id' => $transaction_id])
                                                       ?>">取消订单</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayreply/initiativeadd', 'order_id' => $item['order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">发送消息</a>
                                                </li>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $item['platform_order_id'], 'platform' => Yii::$app->request->getQueryParam('platform_codes')]); ?>">回评</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>

                                                <?php if (isset($invoiceInfo)) { ?>
                                                    <li><a href="javascript:void(0)" class="cancelEbayPaypalInvoice"
                                                           data-orderid="<?php echo $item['order_id']; ?>"
                                                           data-invoiceid="<?php echo $invoiceInfo['invoice_id']; ?>"
                                                           data-invoiceemail="<?php echo $invoiceInfo['merchant_email']; ?>">取消收款</a>
                                                    </li>
                                                <?php } else { ?>
                                                    <li><a _width="80%" _height="80%" class="edit-button"
                                                           href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $item['order_id'], 'platform_order_id' => $item['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $item['platform_code']]); ?>">收款</a>
                                                    </li>
                                                <?php }; ?>
                                            <?php } ?>
                                        <?php } ?>

                                        <?php if ($item['order_type'] == 5) { ?>
                                            <?php if (in_array($item['complete_status'], [19, 20]) || (isset($item['warehouse_type']) && in_array($item['warehouse_type'], [2, 3, 4]) && $item['complete_status'] == 13)) { ?>
                                                <!--新建售后单（只能建重寄单，不能建退款单） 登记收款单-->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li>
                                                    <a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/ebayreceipt', 'order_id' => $item['order_id'], 'platform' => $item['platform_code'], 'buyer_id' => $item['buyer_id'], 'account_id' => $item['account_id']]); ?>">登记收款单</a>
                                                </li>
                                            <?php } ?>
                                            <?php if (in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25]) || (isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13)) { ?>
                                                <!-- 永久作废 暂时作废-->
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                            <?php } ?>
                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!-- 取消暂时作废 永久作废-->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>

                                        <?php if ($item['order_type'] == 6) { ?>
                                            <?php if (isset($invoiceInfo)) { ?>
                                                <li><a href="javascript:void(0)" class="cancelEbayPaypalInvoice"
                                                       data-orderid="<?php echo $item['order_id']; ?>"
                                                       data-invoiceid="<?php echo $invoiceInfo['invoice_id']; ?>"
                                                       data-invoiceemail="<?php echo $invoiceInfo['merchant_email']; ?>">取消收款</a>
                                                </li>
                                            <?php } else { ?>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $item['order_id'], 'platform_order_id' => $item['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $item['platform_code']]); ?>">收款</a>
                                                </li>
                                            <?php }; ?>
                                        <?php } ?>
                                        <?php if ($item['order_type'] == 8) { ?>
                                            <?php if (isset($invoiceInfo)) { ?>
                                                <li><a href="javascript:void(0)" class="cancelEbayPaypalInvoice"
                                                       data-orderid="<?php echo $item['order_id']; ?>"
                                                       data-invoiceid="<?php echo $invoiceInfo['invoice_id']; ?>"
                                                       data-invoiceemail="<?php echo $invoiceInfo['merchant_email']; ?>">取消收款</a>
                                                </li>
                                            <?php } else { ?>
                                                <li><a _width="80%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $item['order_id'], 'platform_order_id' => $item['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $item['platform_code']]); ?>">收款</a>
                                                </li>
                                            <?php }; ?>
                                        <?php } ?>
                                    <?php } ?>

                                    <!---------- 速卖通操作--------->
                                    <?php if (Yii::$app->request->getQueryParam('platform_codes') == 'ALI') { ?>

                                        <?php if ($item['order_type'] == 1) { ?>
                                            <?php if (in_array($item['complete_status'], [19, 20]) || (isset($item['warehouse_type']) && in_array($item['warehouse_type'], [2, 3, 4]) && $item['complete_status'] == 13)) { ?>

                                                <!--新建售后单 发送消息   登记退款单  -->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li>
                                                    <a class="openSendMsgBtn" style="cursor: pointer"
                                                       data-orderid="<?php echo isset($item['platform_order_id']) ? $item['platform_order_id'] : null; ?>"
                                                       data-buyeruserid="<?php echo isset($item['buyer_user_id']) ? $item['buyer_user_id'] : null; ?>"
                                                       data-accountid="<?php echo isset($item['account_id']) ? $item['account_id'] : null; ?>">发送消息</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>
                                            <?php } ?>

                                            <?php if (in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25]) || (isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13)) { ?>

                                                <!---  永久作废 暂时作废 发送消息  -->
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>

                                                <li>
                                                    <a class="openSendMsgBtn" style="cursor: pointer"
                                                       data-orderid="<?php echo isset($item['platform_order_id']) ? $item['platform_order_id'] : null; ?>"
                                                       data-buyeruserid="<?php echo isset($item['buyer_user_id']) ? $item['buyer_user_id'] : null; ?>"
                                                       data-accountid="<?php echo isset($item['account_id']) ? $item['account_id'] : null; ?>">发送消息</a>
                                                </li>
                                            <?php } ?>

                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!--- 取消暂时作废 永久作废 发送消息 （会自动永久作废订单）   --->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li>
                                                    <a class="openSendMsgBtn" style="cursor: pointer"
                                                       data-orderid="<?php echo isset($item['platform_order_id']) ? $item['platform_order_id'] : null; ?>"
                                                       data-buyeruserid="<?php echo isset($item['buyer_user_id']) ? $item['buyer_user_id'] : null; ?>"
                                                       data-accountid="<?php echo isset($item['account_id']) ? $item['account_id'] : null; ?>">发送消息</a>
                                                </li>
                                            <?php } ?>

                                            <?php if ($item['complete_status'] == 40) { ?>
                                                <!------新建售后单（只能建退款单，不能建重寄单） 发送消息   登记退款单------>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li>
                                                    <a class="openSendMsgBtn" style="cursor: pointer"
                                                       data-orderid="<?php echo isset($item['platform_order_id']) ? $item['platform_order_id'] : null; ?>"
                                                       data-buyeruserid="<?php echo isset($item['buyer_user_id']) ? $item['buyer_user_id'] : null; ?>"
                                                       data-accountid="<?php echo isset($item['account_id']) ? $item['account_id'] : null; ?>">发送消息</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>
                                            <?php } ?>

                                        <?php } ?>

                                        <?php if ($item['order_type'] == 7) { ?>
                                            <?php if ((isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13) || in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25])) { ?>
                                                <!--永久作废 暂时作废-->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>

                                            <?php } ?>
                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!--取消暂时作废 永久作废-->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>

                                            <?php } ?>

                                        <?php } ?>

                                        <?php if ($item['order_type'] == 3) { ?>
                                            <?php if ($item['complete_status'] == 40) { ?>
                                                <!--新建售后单（只能建退款单，不能建重寄单） 发送消息   登记退款单-->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li>
                                                    <a class="openSendMsgBtn" style="cursor: pointer"
                                                       data-orderid="<?php echo isset($item['platform_order_id']) ? $item['platform_order_id'] : null; ?>"
                                                       data-buyeruserid="<?php echo isset($item['buyer_user_id']) ? $item['buyer_user_id'] : null; ?>"
                                                       data-accountid="<?php echo isset($item['account_id']) ? $item['account_id'] : null; ?>">发送消息</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($item['order_type'] == 2) { ?>
                                            <?php if (in_array($item['complete_status'], [19, 20]) || ($item['complete_status'] == 13 && isset($item['warehouse_type']) && in_array($item['warehouse_type'], [2, 3, 5]))) { ?>
                                                <!--新建售后单（只能建重寄单，不能建退款单） -->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                            <?php } ?>
                                            <?php if ((isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13) || in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25])) { ?>
                                                <!--永久作废 暂时作废-->
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                            <?php } ?>
                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!--取消暂时作废 永久作废-->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($item['order_type'] == 4) { ?>
                                            <?php if ($item['complete_status'] == 40) { ?>
                                                <!--新建售后单（只能建退款单，不能建重寄单） 发送消息   登记退款单-->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li>
                                                    <a class="openSendMsgBtn" style="cursor: pointer"
                                                       data-orderid="<?php echo isset($item['platform_order_id']) ? $item['platform_order_id'] : null; ?>"
                                                       data-buyeruserid="<?php echo isset($item['buyer_user_id']) ? $item['buyer_user_id'] : null; ?>"
                                                       data-accountid="<?php echo isset($item['account_id']) ? $item['account_id'] : null; ?>">发送消息</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($item['order_type'] == 5) { ?>
                                            <?php if (in_array($item['complete_status'], [19, 20]) || (isset($item['warehouse_type']) && in_array($item['warehouse_type'], [2, 3, 4]) && $item['complete_status'] == 13)) { ?>
                                                <!--新建售后单（只能建重寄单，不能建退款单） -->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>

                                            <?php } ?>
                                            <?php if (in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25]) || (isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13)) { ?>
                                                <!-- 永久作废 暂时作废-->
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                            <?php } ?>
                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!-- 取消暂时作废 永久作废-->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } ?>

                                    <!-- cdiscount平台 -->
                                    <?php if (Yii::$app->request->getQueryParam('platform_codes') == 'CDISCOUNT') { ?>
                                        <?php $account = Account::findOne(['old_account_id' => $item['account_id'], 'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT]); ?>
                                        <li><a _width="100%" _height="100%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/getsendemail', 'account_id' => $account->id, 'toemail' => $item['email'], 'platform_order_id' => $item['platform_order_id']]); ?>" target="_blank">联系买家</a>
                                        </li>
                                        <?php
                                        $invoiceInfo = PaypalInvoiceRecord::getIvoiceData($item['order_id'], $item['platform_code']);
                                        $transactionId = Tansaction::getOrderTransactionIdEbayByOrderId($item['order_id'], $item['platform_code']);
                                        ?>
                                        <?php if (isset($invoiceInfo)) { ?>
                                            <li><a href="javascript:void(0)" class="cancelEbayPaypalInvoice" data-orderid="<?php echo $item['order_id']; ?>" data-invoiceid="<?php echo $invoiceInfo['invoice_id']; ?>" data-invoiceemail="<?php echo $invoiceInfo['merchant_email']; ?>">取消收款</a>
                                            </li>
                                        <?php } else { ?>
                                            <li><a _width="80%" _height="80%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/orders/order/ebaypaypalinvoice', 'order_id' => $item['order_id'], 'platform_order_id' => $item['platform_order_id'], 'transaction_id' => $transactionId['transaction_id'], 'platform' => $item['platform_code']]); ?>">收款</a>
                                            </li>
                                        <?php }; ?>
                                    <?php } ?>

                                    <!------------ wish amazon other-------->
                                    <?php if (!in_array(Yii::$app->request->getQueryParam('platform_codes'), ['ALI', 'EB'])) { ?>
                                        <!--新建售后单  登记退款单  -->
                                        <li><a _width="100%" _height="100%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/aftersales/order/add', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">新建售后单</a>
                                        </li>
                                        <li><a _width="100%" _height="100%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                        </li>
                                        <?php if ($item['order_type'] == 1) { ?>
                                            <?php if (in_array($item['complete_status'], [19, 20]) || (isset($item['warehouse_type']) && in_array($item['warehouse_type'], [2, 3, 4]) && $item['complete_status'] == 13)) { ?>


                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <!--                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);               ?>">登记退款单</a>
                                                                                                </li>-->
                                            <?php } ?>

                                            <?php if (in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25]) || (isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13)) { ?>

                                                <!---  永久作废 暂时作废   -->
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                            <?php } ?>

                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!--- 取消暂时作废 永久作废  （会自动永久作废订单）   --->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>

                                            <?php if ($item['complete_status'] == 40) { ?>
                                                <!------新建售后单（只能建退款单，不能建重寄单）    登记退款单------>
                                                <!--                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/aftersales/order/add','order_id' => $item['order_id'], 'platform' => $item['platform_code']]);              ?>">新建售后单</a>
                                                                                                </li>-->
                                                <!--                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);               ?>">登记退款单</a>
                                                                                                </li>-->
                                            <?php } ?>

                                        <?php } ?>

                                        <?php if ($item['order_type'] == 7) { ?>
                                            <?php if ((isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13) || in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25])) { ?>
                                                <!--永久作废 暂时作废-->
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/aftersales/order/add',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">新建售后单</a>
                                                </li>
                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>

                                            <?php } ?>
                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!--取消暂时作废 永久作废-->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($item['order_type'] == 3) { ?>
                                            <?php if ($item['complete_status'] == 40) { ?>
                                                <!--新建售后单（只能建退款单，不能建重寄单） 发送消息   登记退款单-->
                                                <!--                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/aftersales/order/add','order_id' => $item['order_id'], 'platform' => $item['platform_code']]);               ?>">新建售后单</a>
                                                                                                </li>-->
                                                <!--                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);               ?>">登记退款单</a>
                                                                                                </li>-->
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($item['order_type'] == 2) { ?>
                                            <?php if (in_array($item['complete_status'], [19, 20]) || ($item['complete_status'] == 13 && isset($item['warehouse_type']) && in_array($item['warehouse_type'], [2, 3, 5]))) { ?>
                                                <!--新建售后单（只能建重寄单，不能建退款单） -->
                                                <!--                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/aftersales/order/add','order_id' => $item['order_id'], 'platform' => $item['platform_code']]);              ?>">新建售后单</a>
                                                                                                </li>-->
                                            <?php } ?>
                                            <?php if ((isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13) || in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25])) { ?>
                                                <!--永久作废 暂时作废-->
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                            <?php } ?>
                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!--取消暂时作废 永久作废-->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($item['order_type'] == 4) { ?>
                                            <?php if ($item['complete_status'] == 40) { ?>
                                                <!--                                                新建售后单（只能建退款单，不能建重寄单） 登记退款单
                                                                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/aftersales/order/add', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);               ?>">新建售后单</a>
                                                                                                </li>-->
                                                <!--                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);              ?>">登记退款单</a>
                                                                                                </li>-->
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($item['order_type'] == 5) { ?>
                                            <?php if (in_array($item['complete_status'], [19, 20]) || (isset($item['warehouse_type']) && in_array($item['warehouse_type'], [2, 3, 4]) && $item['complete_status'] == 13)) { ?>
                                                <!--新建售后单（只能建重寄单，不能建退款单） -->
                                                <!--                                                <li><a _width="100%" _height="100%" class="edit-button"
                                                                                                       href="<?php //echo Url::toRoute(['/aftersales/order/add','order_id' => $item['order_id'], 'platform' => $item['platform_code']]);              ?>">新建售后单</a>
                                                                                                </li>-->

                                            <?php } ?>
                                            <?php if (in_array($item['complete_status'], [0, 1, 5, 10, 15, 99, 25]) || (isset($item['warehouse_type']) && $item['warehouse_type'] == 1 && $item['complete_status'] == 13)) { ?>
                                                <!-- 永久作废 暂时作废-->
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/holdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">暂时作废</a>
                                                </li>
                                            <?php } ?>
                                            <?php if ($item['complete_status'] == 25) { ?>
                                                <!-- 取消暂时作废 永久作废-->
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelholdorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">取消暂时作废</a>
                                                </li>
                                                <li><a _width="30%" _height="60%" class="edit-button"
                                                       href="<?php
                                                       echo Url::toRoute(['/orders/order/cancelorder',
                                                           'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                       ?>">永久作废</a>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } ?>
                                    <?php if (Yii::$app->request->getQueryParam('platform_codes') == 'WISH') { ?>
                                        <?php if ($item['order_type'] == 7) { ?>     
                                            <li><a _width="100%" _height="100%" class="edit-button"
                                                   href="<?php
                                                   echo Url::toRoute(['/aftersales/order/add',
                                                       'order_id' => $item['order_id'], 'platform' => $item['platform_code']]);
                                                   ?>">新建售后单</a>
                                            </li>
                                            <li><a _width="100%" _height="100%" class="edit-button"
                                                   href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                            </li>
                                        <?php } ?>
                                    <?php } ?>
                                    <!-----------wish cd LAZADA SHOPEE ----------->
                                    <?php if (in_array(Yii::$app->request->getQueryParam('platform_codes'), ['WISH', 'CDISCOUNT', 'LAZADA', 'SHOPEE'])) { ?>
                                        <li><a _width="80%" _height="80%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/aftersales/sales/ebayreceipt', 'order_id' => $item['order_id'], 'platform' => $item['platform_code'], 'buyer_id' => $item['buyer_id'], 'account_id' => $item['account_id']]); ?>">登记收款单</a>
                                        </li>
                                    <?php } ?>
                                    <li>
                                        <a _width="50%" _height="80%" class="edit-button"
                                           href="<?php echo Url::toRoute(['/orders/order/invoice', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">发票</a>
                                    </li>
                                    <li>
                                       <a _width="100%" _height="100%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/aftersales/complaint/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记客诉单</a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="21">暂无指定条件的订单</td>
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

<!--速卖通单个发生站内信-->
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
<!--速卖通批量发生站内信-->
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

<!--amazon批量发送-->
<div id="contact_buyer_batch" class="modal fade in" tabindex="-1" role="dialog"
     aria-labelledby="custom-width-modalLabel" aria-hidden="false" style="display: none; padding-right: 17px;">
    <div class="modal-dialog" style="width:55%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="custom-width-modalLabel">批量发送邮件</h4>
            </div>
            <div class="col-md-12" style="margin-top:15px; margin-bottom: 15px;">
                <input type="hidden" name="_csrf" id='csrf' value="<?php echo Yii::$app->request->csrfToken ?>">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">发件人</label>
                                        <div class="col-md-11">
                                            <input id="sender_email_batch" readonly type="text" class="form-control"
                                                   value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">收件人</label>
                                        <div class="col-md-11">
                                            <input id="recipient_email_batch" readonly type="text" class="form-control"
                                                   value="">
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="all_value" value="">
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">主题</label>
                                        <div class="col-md-11">
                                            <input id="title_batch" type="text" class="form-control" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">内容</label>
                                        <div class="col-md-11">
                                            <script id="content_batch" name="content_batch" type="text/plain"></script>
                                            <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.config.js"></script>
                                            <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/ueditor.all.js"></script>
                                            <script src="<?php echo yii\helpers\Url::base(true); ?>/js/UEditor/lang/zh-cn/zh-cn.js"></script>
                                            <script type="text/javascript">
                                                UE.getEditor('content_batch', {zIndex: 6600, initialFrameHeight: 200});
                                            </script>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">取消</button>
                <button type="button" class="btn send_batch btn-primary waves-effect waves-light">发送</button>
            </div>
        </div>
    </div>
</div>

<!--CDISCOUNT 批量发送-->
<div id="contact_cd_batch" class="modal fade in" tabindex="-1" role="dialog"
     aria-labelledby="custom-width-modalLabel" aria-hidden="false" style="display: none; padding-right: 17px;">
    <div class="modal-dialog" style="width:55%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="custom-width-modalLabel">cdiscount批量发送邮件</h4>
            </div>
            <div class="col-md-12" style="margin-top:15px; margin-bottom: 15px;">
                <input type="hidden" name="_csrf" id='csrf' value="<?php echo Yii::$app->request->csrfToken ?>">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">发件人</label>
                                        <div class="col-md-11">
                                            <input id="sender_emails_batch" readonly type="text" class="form-control"
                                                   value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">收件人</label>
                                        <div class="col-md-11">
                                            <input id="recipient_emails_batch" readonly type="text" class="form-control"
                                                   value="">
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="cd_all_value" value="">
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">主题</label>
                                        <div class="col-md-11">
                                            <input id="cd_title_batch" type="text" class="form-control" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">消息模板</label>
                                        <div class="col-md-11 input-group" style="padding:5px 15px;">
                                            <input type="text" id='cd_search' class="form-control" placeholder="消息模板搜索">
                                            <div class="input-group-addon mailTemplateSearch" style="cursor: pointer;">搜索</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">消息模板</label>
                                        <div class="col-md-11">
                                            <div id="mailTemplateArea" style="margin-top: -25px">
                                                <?php
                                                $templates = MailTemplate::getMyMailTemplate(Platform::PLATFORM_CODE_CDISCOUNT);
                                                if (!empty($templates)) {
                                                    foreach ($templates as $template) {
                                                        if (!empty($template[0])) {
                                                            echo '<fieldset>';
                                                            echo '<legend>' . ($template[0]['category_name'] ? $template[0]['category_name'] : '无分类名称') . '</legend>';
                                                        }

                                                        if (!empty($template) && is_array($template)) {
                                                            foreach ($template as $item) {
                                                                echo "<a href='javascript:void(0);' class='mailTemplateItem' data-id='{$item['id']}'>{$item['template_name']}</a>";
                                                            }
                                                        }
                                                        if (!empty($template[0])) {
                                                            echo '</fieldset>';
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">内容—英语</label>
                                        <div class="col-md-11">
                                            <textarea name="reply_content_en" id="reply_content_en" rows="5" cols="20" class="form-control" placeholder="输入回复内容(注意：此输入回复内容为英语)"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label"></label>
                                        <div class="col-md-11">
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
                                                <?php $googleLangCode = VHelper::googleLangCode(); ?>
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
                                </div>
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label class="col-md-1 control-label">内容—翻译</label>
                                        <div class="col-md-11">
                                            <textarea name="reply_content" id="reply_content" rows="5" cols="20" class="form-control" placeholder="输入回复内容"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="sl_code" value="">
                <input type="hidden" id="tl_code" value="">
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">取消</button>
                <button type="button" class="btn cd_send_batch btn-primary waves-effect">发送</button>
            </div>
        </div>
    </div>
</div>

<div id='updateShopOrderStatusOverlay'>
    <div id='updateShopOrderStatusSpeed'></div>
</div>

<script>

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

        $("select[name='platform_codes']").on("change", function () {
            var platform_code = $(this).val();
            if (platform_code == "ALI") {
                $("#showBatchUpdateShopOrderStatus").css("display", "block");
            } else {
                $("#showBatchUpdateShopOrderStatus").css("display", "none");
            }
        });

        function showBatchUpdateShopOrderStatus() {
            var platform_code = $("select[name='platform_codes']").val();
            if (platform_code == "ALI") {
                $("#showBatchUpdateShopOrderStatus").css("display", "block");
            } else {
                $("#showBatchUpdateShopOrderStatus").css("display", "none");
            }
        }

        showBatchUpdateShopOrderStatus();

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


    var platform_code = $("select[name=platform_codes]").val();
    var condition_option = $("select[name='condition_option']").val();
    var condition_value = $("input[name='condition_value']").val();
    var account_ids = $("select[name=account_ids]").val();
    var warehouse_id = $("select[name='warehouse_id']").val();
    var get_date = $("select[name='get_date']").val();//选择的时间
    var begin_date = $("input[name='begin_date']").val();//开始时间
    var end_date = $("input[name='end_date']").val();//结束时间
    var ship_code = $("select[name='ship_code']").val();
    var ship_country = $("select[name='ship_country']").val();
    var currency = $("select[name='currency']").val();
    var complete_status = $("select[name=complete_status]").val();
    var order_status = $("select[name=order_status]").val();
    //点击下单时间排序
    $(".created_time").click(function () {
        var created_state = $("#created_time").val();
        var url = window.location.href;
        if (url.search('paytime_state') != -1 && url.search('shipped_state') != -1) {
            url = url.substring(0, url.length - 16);
        } else {
            if (url.search('paytime_state') != -1 || url.search('shipped_state') != -1) {
                url = url.substring(0, url.length - 16);
            }
        }
        if (created_state == "") {
            created_state = 1;
        } else if (created_state == 1) {
            url = url.substring(0, url.length - 16);
            created_state = 2;
        } else if (created_state == 2) {
            url = url.substring(0, url.length - 16);
            created_state = 1;
        }
        window.location.href = url + "&created_state=" + created_state;
    });
    //点击付款时间排序
    $(".paytime").click(function () {
        var paytime_state = $("#paytime").val();
        var url = window.location.href;
        if (url.search('created_state') != -1 && url.search('shipped_state') != -1) {
            url = url.substring(0, url.length - 16);
        } else {
            if (url.search('created_state') != -1 || url.search('shipped_state') != -1) {
                url = url.substring(0, url.length - 16);
            }
        }
        if (paytime_state == "") {
            paytime_state = 1;
        } else if (paytime_state == 1) {
            url = url.substring(0, url.length - 16);
            paytime_state = 2;
        } else if (paytime_state == 2) {
            url = url.substring(0, url.length - 16);
            paytime_state = 1;
        }
        window.location.href = url + "&paytime_state=" + paytime_state;
    });
    //发货时间排序
    $(".shipped").click(function () {
        var shipped_state = $("#shipped").val();
        var url = window.location.href;
        if (url.search('created_state') != -1 && url.search('paytime_state') != -1) {
            url = url.substring(0, url.length - 16);
        } else {
            if (url.search('created_state') != -1 || url.search('paytime_state') != -1) {
                url = url.substring(0, url.length - 16);
            }
        }
        if (shipped_state == "") {
            shipped_state = 1;
        } else if (shipped_state == 1) {
            url = url.substring(0, url.length - 16);
            shipped_state = 2;
        } else if (shipped_state == 2) {
            url = url.substring(0, url.length - 16);
            shipped_state = 1;
        }
        window.location.href = url + "&shipped_state=" + shipped_state;

    });
    /**
     * 速卖通发送站内信
     */
    $(".batch-reply").bind("click", function () {
        //平台订单ID&账号ID&买家登陆ID 组合一个字符串
        var three_ids = '';
        $(":checked.sel").each(function () {
            if (three_ids == '') {
                if ($(this).prop('checked') == true) {
                    three_ids = $(this).data('plarformorderid') + '&' + $(this).data('accountid') + '&' + $(this).data('buyeruserid');
                }
            } else {
                if ($(this).prop('checked') == true) {
                    three_ids += ',' + $(this).data('plarformorderid') + '&' + $(this).data('accountid') + '&' + $(this).data('buyeruserid');
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
    });
    //ebay 批量恢复消息
    $(".batch-reply_ebay").bind("click", function () {
        var orderids = '';
        $(":checked.sel").each(function () {
            if (orderids == '') {
                if ($(this).prop('checked') == true) {
                    orderids = $(this).data('orderid');
                }

            } else {
                if ($(this).prop('checked') == true) {
                    orderids += ',' + $(this).data('orderid')
                }
            }
        });
        if (orderids == '') {
            layer.msg('未选择订单', {icon: 2});
            return;
        } else {
            layer.open({
                type: 2,
                skin: 'layui-layer-rim', //加上边框
                area: ['90%', '90%'], //宽高
                content: "http://<?= $_SERVER['HTTP_HOST']; ?>/mails/ebayreply/initiativebatchadd?orderids=" + orderids + "&platform=EB",
            });
        }

    });
    //amazon 发送邮件
    $(".batch-reply_amazon").bind("click", function () {
        //账号ID&站点&客户邮件&平台订单id 组合一个字符串
        var four_ids = '';
        var custEmails = '';
        var all_value = "";
        $(":checked.sel").each(function () {
            if (four_ids == '') {
                if ($(this).prop('checked') == true) {
                    four_ids = $(this).data('accountid') + '&' + $(this).data('site') + '&' + $(this).data('custemail') + '&' + $(this).data('platformorderid');
                    custEmails = $(this).data('custemail');
                    all_value = $(this).data('email') + '&' + $(this).data('accountid') + '&' + $(this).data('site') + '&' + $(this).data('custemail') + '&' + $(this).data('platformorderid');
                }
            } else {
                if ($(this).prop('checked') == true) {
                    four_ids += ',' + $(this).data('accountid') + '&' + $(this).data('site') + '&' + $(this).data('custemail') + '&' + $(this).data('platformorderid');
                    custEmails += ';' + $(this).data('custemail');
                    all_value += ',' + $(this).data('email') + '&' + $(this).data('accountid') + '&' + $(this).data('site') + '&' + $(this).data('custemail') + '&' + $(this).data('platformorderid');
                }
            }
        });
        if (four_ids == '') {
            layer.msg('请选择订单', {icon: 5});
            return;
        } else {
            //获取发件邮箱
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/orders/order/getsendemails']) ?>',
                data: {'four_ids': four_ids},
                success: function (data) {
                    $("#sender_email_batch").val(data.emails);
                    $("#all_value").val(data.four_arr);
                }
            });
            $("#recipient_email_batch").val(custEmails);
            $("#contact_buyer_batch").modal("show");
        }
    });

    //cdiscount 发送邮件
    $(".batch-reply_cd").bind("click", function () {
        //账号ID&站点&客户邮件&平台订单id 组合一个字符串
        var four_ids = '';
        var account_ids = '';
        var custEmails = '';
        var platformorderid = "";
        $(":checked.sel").each(function () {
            if (four_ids == '') {
                if ($(this).prop('checked') == true) {
                    four_ids = $(this).data('accountid') + '&' + $(this).data('custemail') + '&' + $(this).data('platformorderid');
                    account_ids = $(this).data('accountid');
                    custEmails = $(this).data('custemail');
                    platformorder_ids = $(this).data('platformorderid');
                }
            } else {
                if ($(this).prop('checked') == true) {
                    four_ids += ',' + $(this).data('accountid') + '&' + $(this).data('custemail') + '&' + $(this).data('platformorderid');
                    account_ids += ';' + $(this).data('accountid');
                    custEmails += ';' + $(this).data('custemail');
                    platformorder_ids += ';' + $(this).data('platformorderid');
                }
            }
        });
        if (four_ids == '') {
            layer.msg('请选择订单', {icon: 5});
            return;
        } else {
            //获取发件邮箱
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/getsendemails']) ?>',
                data: {'account_id': account_ids, 'toemail': custEmails, 'platform_order_id': platformorder_ids, },
                success: function (data) {
                    $("#sender_emails_batch").val(data);
                }
            });
            $("#cd_all_value").val(four_ids);
            $("#recipient_emails_batch").val(custEmails);
            cdClearn();
            $("#contact_cd_batch").modal("show");
        }
    });

    //cdiscount批量发送邮件
    $('#contact_cd_batch').on('click', '.cd_send_batch', function () {
        var send_email = $("#sender_emails_batch").val();
        var recipient_email = $("#recipient_emails_batch").val();
        var cd_all_value = $("#cd_all_value").val();
        var title = $("#cd_title_batch").val();
        var content_en = $('#reply_content_en').val();
        var content = $('#reply_content').val();

        if (send_email == "") {
            layer.msg('发件人未设置!');
            return false;
        }

        if (recipient_email == "") {
            layer.msg('收件人未设置');
            return false;
        }

        if (title == "") {
            layer.msg('主题未设置');
            return false;
        }

        if (content_en == "") {
            layer.msg('回复内容(英文)不能为空');
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/sendemails']) ?>',
            data: {'cd_all_value': cd_all_value, 'subject': title, 'reply_content_en': content_en, 'reply_content': content, },
            success: function (data) {
                if (data.bool) {
                    layer.msg(data.msg, {icon: 6, time: 1000});
                    $("#contact_cd_batch").modal('hide');
                    cdClearn();
                } else {
                    layer.msg(data.msg, {icon: 5, time: 10000});
                }
            }
        });
    });

    //cd清除批量发信数据
    function cdClearn() {
        $('#cd_title_batch').val('');
        $('#reply_content_en').val('');
        $('#reply_content').val('');
        $('#cd_search').val('');
    }

    //消息模板
    $("#contact_cd_batch").on("click", ".mailTemplateItem", function () {
        var id = $(this).attr("data-id");
        $.post("<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>", {
            "num": id
        }, function (data) {
            switch (data.status) {
                case "error":
                    layer.msg(data.message, {icon: 5});
                case "success":
                    var refund_content = $("#reply_content_en").val();
                    if (refund_content !== '') {
                        $("#reply_content_en").val(refund_content + "\n" + data.content);
                    } else {
                        $("#reply_content_en").val(data.content);
                    }
            }
        }, "json");
        return false;
    });

    //消息模板搜索
    $('.mailTemplateSearch').click(function () {
        var name = $(this).prev("input[type='text']").val();
        if (name.length == 0) {
            layer.msg('搜索名称不能为空', {icon: 5});
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/cdiscountinboxsubject/searchmailtemplate']); ?>', {
            "name": name
        }, function (data) {
            if (data["code"] == 1) {
                var data = data["data"];
                if (data) {
                    var html = "";
                    for (var ix in data) {
                        if (data[ix][0]) {
                            html += "<fieldset>"
                            html += "<legend>" + (data[ix][0]["category_name"] ? data[ix][0]["category_name"] : "无分类名称") + "</legend>";
                        }
                        var item = data[ix];
                        for (var index in item) {
                            html += "<a href='#' class='mailTemplateItem' data-id='" + item[index]["id"] + "'>" + item[index]["template_name"] + "</a>";
                        }
                        if (data[ix][0]) {
                            html += "</fieldset>";
                        }
                    }
                    $("#mailTemplateArea").html(html);
                }
            } else {
                layer.msg(data["message"], {icon: 5});
                $("#mailTemplateArea").html("");
            }
        }, 'json');
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
                    //$("#reply_content").css('display', 'block');
                }
            }
        });
        return false;
    });

    //单个发送 contact_buyer
    $(document).on('click', '.contact_buyer', function () {
        var accountId = $(this).data('accountid');//账号ID
        var site = $(this).data('site');//站点ID
        var custEmail = $(this).data('custemail');//收件人邮箱
        if (accountId == "" || site == "" || custEmail == "") {
            layer.alert('数据不全');
            return false;
        }
        //获取发件邮箱
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['/orders/order/getsendemail']) ?>',
            data: {'account_id': accountId, 'site': site},
            success: function (data) {
                $("#sender_email").val(data);
            }
        });
        $("#recipient_email").val(custEmail);
        $("#contact_buyer").modal('show');
    });
    //发送邮件
    $(document).on('click', '.send', function () {
        var send_email = $("#sender_email_batch").val();
        var recipient_email = $("#recipient_email_batch").val();
        var title = $("#title_batch").val();//主题
        var ue = UE.getEditor('content1');
        var content = ue.getContent();//邮件内容
        if (send_email == "") {
            layer.msg('发件人未设置!');
            return false;
        }

        if (recipient_email == "") {
            layer.msg('收件人未设置');
            return false;
        }

        if (title == "") {
            layer.msg('主题未设置');
            return false;
        }

        if (content == "") {
            layer.msg('邮件内容为空');
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['sendemail']) ?>',
            data: {'send_email': send_email, 'recipient_email': recipient_email, 'title': title, 'content': content},
            success: function (data) {
                if (data.bool) {
                    layer.msg(data.msg, {icon: 6, time: 1000});
                    $("#contact_buyer").modal('hide');
                } else {
                    layer.msg(data.msg, {icon: 5, time: 10000});
                }
            }
        });

    });

    //批量发送邮件
    $(document).on('click', '.send_batch', function () {
        var send_email = $("#sender_email_batch").val();
        var recipient_email = $("#recipient_email_batch").val();
        var all_value = $("#all_value").val();//
        var title = $("#title_batch").val();//主题
        var ue = UE.getEditor('content_batch');
        var content = ue.getContent();//邮件内容
        var csrf = $("#csrf").val();

        if (send_email == "") {
            layer.msg('发件人未设置!');
            return false;
        }

        if (recipient_email == "") {
            layer.msg('收件人未设置');
            return false;
        }

        if (title == "") {
            layer.msg('主题未设置');
            return false;
        }

        if (content == "") {
            layer.msg('邮件内容为空');
            return false;
        }

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['sendemails']) ?>',
            data: {'all_value': all_value, 'title': title, 'content': content, "csrf": csrf},
            success: function (data) {
                if (data.bool) {
                    layer.msg(data.msg, {icon: 6, time: 1000});
                    $("#contact_buyer_batch").modal('hide');
                } else {
                    layer.msg(data.msg, {icon: 5, time: 10000});
                }
            }
        });
    });
    //
    $(function () {
        if (platform_code == 'ALI') {
            $(".smt_order_status").show()
            $(".smt_currency").hide();
        } else {
            $(".smt_order_status").hide()
            $(".smt_currency").show();
        }
        if (platform_code == 'EB') {
            $(".item_location").show();
        } else {
            $(".item_location").hide();
        }
    });
    $('select[name=platform_code]').click(function () {
        var platform_code = $("select[name=platform_code]").val();
        if (platform_code == 'ALI') {
            $(".smt_order_status").show()
            $(".smt_currency").hide();
        } else {
            $(".smt_order_status").hide()
            $(".smt_currency").show();
        }
        if (platform_code == 'EB') {
            $(".item_location").show();
        } else {
            $(".item_location").hide();
        }
    });
    //批量选择
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
     * 下载订单
     */
    $("#download").click(function () {
        //平台订单ID&账号ID&买家登陆ID 组合一个字符串
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

        var url = $(this).attr('href');
        //如果选中则只下载选中数据
        if (selectIds != "") {
            url += '?platform_code=' + platform_code + '&json=' + selectIds;
        } else {
            url += '?platform_code=' + platform_code + '&condition_option=' + condition_option
                    + '&condition_value=' + condition_value + '&account_ids=' + account_ids + '&warehouse_id=' + warehouse_id
                    + '&get_date=' + get_date + '&end_date=' + end_date + '&begin_date=' + begin_date + '&ship_code=' + ship_code
                    + '&ship_country=' + ship_country + '&currency=' + currency + '&complete_status' + complete_status + "&order_status" + order_status;
        }
        window.open(url);
    });

    /**
     *  批量操作erpapi
     */
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

    $("select[name=platform_code]").change(function () {
        var platform_code = $(this).val();
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['getaccountbyplatformcode']) ?>',
            data: {'platform_code': platform_code},
            success: function (data) {
                if (data.status == 'success') {
                    $("select[name=account_ids]").empty();
                    var html = "";
                    html += '<option value="0">全部</option>';
                    $.each(data.data, function (n, value) {
                        html += '<option value="' + n + '">' + value + '</option>';
                    });
                    $("select[name=account_ids]").append(html);
                } else {
                    layer.msg(data.message, {icon: 5, time: 10000});
                }
            }
        });
    });

</script>