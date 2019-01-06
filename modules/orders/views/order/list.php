<?php

use yii\helpers\Url;
use app\modules\orders\models\Order;
use app\common\VHelper;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use app\components\LinkPager;

$this->title = '订单查询';
?>
<style>
    .select2-container--krajee {
        min-width: 155px !important;
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
                                    <select class="form-control" name="platform_code">
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
                                                <?php if ($condition_option == 'buyer_id'){ ?>selected<?php } ?>>默认查询条件
                                        </option>
                                        <option value="item_id"
                                                <?php if ($condition_option == 'item_id'){ ?>selected<?php } ?>>ItemId
                                        </option>
                                        <option value="package_id"
                                                <?php if ($condition_option == 'package_id'){ ?>selected<?php } ?>>包裹号
                                        </option>
                                        <option value="paypal_id"
                                                <?php if ($condition_option == 'paypal_id'){ ?>selected<?php } ?>>
                                            paypal交易号
                                        </option>
                                        <option value="sku" <?php if ($condition_option == 'sku'){ ?>selected<?php } ?>>
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
                                    <?php echo Select2::widget([
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
                                <label class="control-label col-lg-5" for="">发货仓库</label>
                                <div class="col-lg-7">
                                    <?php echo Select2::widget([
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
                                                <?php if ($get_date == 'order_time'){ ?>selected<?php } ?>>下单时间
                                        </option>
                                        <option value="shipped_date"
                                                <?php if ($get_date == 'shipped_date'){ ?>selected<?php } ?>>发货时间
                                        </option>
                                        <option value="paytime"
                                                <?php if ($get_date == 'paytime'){ ?>selected<?php } ?>>付款时间
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
                                ]); ?>
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

                                ]); ?>
                            </div>
                        </li>
                        <li style="margin-left: 30px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">出货方式</label>
                                <div class="col-lg-7">
                                    <div class="col-lg-7">
                                        <?php echo Select2::widget([
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
                                    <?php echo Select2::widget([
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
                                    <?php echo Select2::widget([
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
                                    <?php echo Select2::widget([
                                        'name' => 'order_status',
                                        'value' => $order_status,
                                        'data' => $order_status_list,
                                        'options' => ['placeholder' => '请选择...']
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>

                    </ul>
                    <button type="submit" class="btn btn-primary">搜索</button>
                </form>
            </div>
        </div>
        <div class="bs-bars pull-left" style="padding-top: 7px;">
            共<?php if ($count) {
                echo $count;
            } else {
                echo 0;
            }; ?>条数据&nbsp;
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

        <table class="table table-striped table-bordered">

            <tr>
                <td><input type="checkbox" id="all" class="all"></td>
                <td>订单号</td>
                <td>平台订单号</td>
                <td>订单金额</td>
                <td>订单状态</td>
                <td>订单类型</td>
                <td>邮箱</td>
                <td>发货类型</td>
                <td>买家ID</td>
                <td>付款时间</td>
                <td>发货时间</td>
                <td>下单时间</td>
                <td>产品总金额</td>
                <td>物流单号</td>
                <td>发货仓库</td>
                <td>发货方式</td>
                <td>币种</td>
                <td>成交费用</td>
                <?php if(Yii::$app->request->getQueryParam('platform_code')=='ALI'){
                    echo " <td>店铺订单状态</td>";
                }?>

                <td>操作</td>
            </tr>

            <?php if (!empty($orders)) { ?>
                <?php foreach ($orders as $item) { ?>
                    <tr>
                        <td>
                            <input name="order_id[]"
                                   data-orderid=<?php echo $item['order_id'] ?>
                                   data-platform=<?= $item['platform_code'] ?>
                                   type="checkbox" class="sel ">
                        </td>
                        <td><?php echo $item['order_id']; ?></td>
                        <td>
                            <a _width="70%" _height="70%" class="edit-button"
                               href="<?php echo Url::toRoute(['/orders/order/orderdetails',
                                   'order_id' => $item['platform_order_id'],
                                   'platform' => $item['platform_code'],
                                   'system_order_id' => $item['order_id']]); ?>" title="订单信息">
                                <?php echo $item['platform_order_id']; ?>
                            </a>
                        </td>
                        <td><?php echo $item['total_price']; ?></td>
                        <td><?php echo $item['complete_status_text']; ?></td>
                        <td><?php echo (isset($item['order_type']) && !empty($item['order_type'])) ? VHelper::getOrderType($item['order_type']) : "-"; ?></td>
                        <td><?php echo $item['email']; ?></td>
                        <td><?php echo $item['ship_code']; ?></td>
                        <td><?php echo $item['buyer_id']; ?></td>
                        <td>
                            <?php
                            if ($item['payment_status'] == 0) {
                                echo "未付款";
                            } else {
                                echo $item['paytime'];
                            }
                            ?>
                        </td>
                        <td><?=$item['shipped_date']?></td>
                        <td><?=$item['created_time']?></td>
                        <td><?php echo $item['subtotal_price']; ?></td>
                        <td><?php echo $item['track_number'] ?></td>
                        <td><?php echo $item['warehouse'] ?></td>
                        <td><?php echo $item['logistics'] ?></td>
                        <td><?php echo $item['currency']; ?></td>
                        <td>
                            <?php echo $item['final_value_fee']; ?>
                        </td>

                        <?php if(Yii::$app->request->getQueryParam('platform_code')=='ALI'){
                            $smt_order_status_text=$order_status_list[$item['order_status']];
                            echo  "<td> $smt_order_status_text</td>";
                        }?>
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
                                    <li><?php echo $item['complete_status'];?>--<?php echo $item['order_type']; ?></li>
                                    <?php if ($item['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP || $item['complete_status'] == 99) { ?>
                                        <li><a _width="30%" _height="60%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/orders/order/cancelorder',
                                                   'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">永久作废</a>
                                        </li>
                                        <li><a _width="30%" _height="60%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/orders/order/holdorder',
                                                   'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">暂时作废</a>
                                        </li>
                                    <?php } ?>
                                    
                                    <?php  if ($item['complete_status'] == Order::COMPLETE_STATUS_HOLD) { ?>
                                        <li><a confirm="确定取消暂时作废该订单？" class="ajax-button"
                                               href="<?php echo Url::toRoute(['/orders/order/cancelholdorder',
                                                   'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">取消暂时作废</a>
                                        </li>
                                        <li><a _width="30%" _height="60%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/orders/order/cancelorder',
                                                   'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">永久作废</a>
                                        </li>
                                    <?php } ?>
                                    
                                    <?php if ($item['order_type'] != Order::ORDER_TYPE_REDIRECT_ORDER) { ?>
                                        <li><a _width="100%" _height="100%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/aftersales/order/add',
                                                   'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">新建售后单</a>
                                        </li>
                                    <?php } ?>
                                    
                                    <?php if (!in_array($item['order_type'], array(Order::ORDER_TYPE_MERGE_MAIN, Order::ORDER_TYPE_SPLIT_CHILD, Order::ORDER_TYPE_REDIRECT_ORDER))){ ?>
                                        <li><a _width="80%" _height="80%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/mails/ebayreply/initiativeadd', 'order_id' => $item['order_id'], 'platform' => $platform]); ?>">发送消息</a>
                                        </li>
                                        <li><a _width="80%" _height="80%" class="edit-button"
                                               href="<?php echo Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $item['platform_order_id'], 'platform' => $platform]); ?>">回评</a>
                                        </li>
                                        <li><a _width="100%" _height="100%" class="edit-button"
                                           href="<?php echo Url::toRoute(['/aftersales/sales/register', 'order_id' => $item['order_id'], 'platform' => $item['platform_code']]); ?>">登记退款单</a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="18">暂无指定条件的订单</td>
                </tr>
            <?php } ?>
        </table>

        <?php echo LinkPager::widget([
            'pagination' => $page,
            'firstPageLabel' => '首页',
            'lastPageLabel' => '尾页',
            'nextPageLabel' => '下一页',
            'prevPageLabel' => '上一页',
        ]); ?>
    </div>
</div>
<script>
    var platform_code = $("select[name=platform_code]").val();
    var condition_option = $("select[name='condition_option']").val();
    var condition_value = $("input[name='condition_value']").val();
    var account_ids = $("select[name=account_ids]").val();
    var warehouse_id = $("select[name='warehouse_id']").val();
    var get_date=$("select[name='get_date']").val();//选择的时间
    var begin_date=$("input[name='begin_date']").val();//开始时间
    var end_date=$("input[name='end_date']").val();//结束时间
    var ship_code = $("select[name='ship_code']").val();
    var ship_country = $("select[name='ship_country']").val();
    var currency = $("select[name='currency']").val();
    var complete_status=$("select[name=complete_status]").val();
    var order_status=$("select[name=order_status]").val();
    $(function () {
        if(platform_code=='ALI'){
            $(".smt_order_status").show()
            $(".smt_currency").hide();
        }else{
            $(".smt_order_status").hide()
            $(".smt_currency").show();
        }
    });
    $('select[name=platform_code]').click(function () {
        var platform_code = $("select[name=platform_code]").val();
        if(platform_code=='ALI'){
            $(".smt_order_status").show()
            $(".smt_currency").hide();
        }else{
            $(".smt_order_status").hide()
            $(".smt_currency").show();
        }
    });
    //批量下载
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
        var selectIds = '';
        $(":checked.sel").each(function () {
            if (selectIds == '') {
                if ($(this).prop('checked') == true) {
                    selectIds = $(this).data('orderid');
                }
            }
            else {
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
                + '&get_date=' + get_date + '&end_date=' + end_date + '&begin_date=' + begin_date
                + '&paytime_end=' + paytime_end + '&ship_code=' + ship_code
                + '&ship_country=' + ship_country + '&currency=' + currency+'&complete_status'+complete_status+"&order_status"+order_status;
        }
        window.open(url);
    });

    //批量操作erpapi
    $(".batch-cancel-order,.batch-priority-distribution-warehouse,.batch-manually-distribution-warehouse,.batch-manually-push-warehouse").on('click',function () {
        var this_class = $(this).attr('class');
        if(this_class.match('batch-cancel-order')){
            var url ="<?php echo Url::toRoute(['/orders/order/ordertoinit']); ?>" ;
        } else if (this_class.match('batch-manually-push-warehouse')) {
            var url ="<?php echo Url::toRoute(['/orders/order/batchsendorde']); ?>" ;
        } else if (this_class.match('batch-priority-distribution-warehouse')) {
            var url ="<?php echo Url::toRoute(['/orders/order/setprioritystatus']); ?>" ;
        } else if (this_class.match('batch-manually-distribution-warehouse')) {
            var url ="<?php echo Url::toRoute(['/orders/order/batchallotstock']); ?>" ;
        }
        var selectIds = '';
        $(":checked.sel").each(function () {
            if (selectIds == '') {
                if ($(this).prop('checked') == true) {
                    selectIds = $(this).data('orderid');
                }
            }
            else {
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
                    'complete_status':complete_status
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