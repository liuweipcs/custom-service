<?php
use \app\modules\orders\models\Order;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\accounts\models\Platform;
use yii\helpers\Url;
use app\modules\blacklist\models\BlackList;
?>
<div class="popup-wrapper">
    <div class="popup-body">
        <ul class="nav nav-tabs">
            <li class="active"><a data-toggle="tab" href="#home">纠纷处理</a></li>
            <li><a data-toggle="tab" href="#menu1">基本信息</a></li>
            <li><a data-toggle="tab" href="#menu2">产品信息</a></li>
            <li><a data-toggle="tab" href="#menu3">交易信息</a></li>
            <li><a data-toggle="tab" href="#menu4">包裹信息</a></li>
            <li><a data-toggle="tab" href="#menu5">利润信息</a></li>
            <li><a data-toggle="tab" href="#menu6">仓储物流</a></li>
        </ul>

        <div class="tab-content">
            <div id="home" class="tab-pane fade in active">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Cancel Id</th>
                        <th><?=$model->cancel_id?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>状况</td>
                        <td><?php echo $model->cancel_state == 0 ? '' : $model::$cancelStateMap[$model->cancel_state];?></td>
                    </tr>
                    <tr>
                        <td>原因</td>
                        <td><?php echo $model->cancel_reason == 0 ? '' : $model::$ReasonMap[$model->cancel_reason];?></td>
                    </tr>
                    <tr>
                        <td>发起时间</td>
                        <td><?=$model->cancel_request_date?></td>
                    </tr>
                    <tr>
                        <td>售后单号</td>
                        <td>
                            <?php
                            $order_id = $info['info']['order_id'];
                            $afterSalesOrders = AfterSalesOrder::find()->select('after_sale_id')->where(['order_id'=>$order_id])->asArray()->all();
                            if(empty($afterSalesOrders))
                                echo '<span>无售后处理单</span>';
                            else
                                echo '<span>'.implode(',',array_column($afterSalesOrders,'after_sale_id')).'</span>';

                            if(!empty($info))
                                echo '<a style="margin-left:10px" _width="90%" _height="90%" class="edit-button" href="'.Url::toRoute(['/aftersales/order/add','order_id'=>$info['info']['order_id'],'platform'=>Platform::PLATFORM_CODE_EB]).'">新建售后单</a>';

                            if(!empty($info) && $info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP)
                            {
                                echo '<a style="margin-left:10px" _width="30%" _height="60%" class="edit-button" href="'.Url::toRoute(['/orders/order/cancelorder','order_id'=>$info['info']['order_id'],'platform'=>Platform::PLATFORM_CODE_EB]).'">永久作废</a>';
                                echo '<a style="margin-left:10px" confirm="确定暂时作废该订单？" class="ajax-button" href="'.Url::toRoute(['/orders/order/holdorder','order_id'=>$info['info']['order_id'],'platform'=>Platform::PLATFORM_CODE_EB]).'">暂时作废</a>';
                            }
                            ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php if(!empty($detailModel)):?>
                    处理过程
                <ul class="list-group">
                    <?php foreach($detailModel as $detail):?>
                        <li class="list-group-item"><?php echo isset($detail->action_date) ? date('Y-m-d H:i:s',strtotime($detail->action_date)+28800):'','&nbsp;&nbsp;&nbsp;&nbsp;','<span style="color:#FF7F00">',$detail->activity_party,'</span>','&nbsp;&nbsp;&nbsp;&nbsp;',$detail->activity_type;?></li>
                    <?php endforeach;?>
                </ul>
                <?php endif;?>

                <?php
                $item_id = $model->legacy_order_id;
                $item_id = substr($item_id,0,strpos($item_id,'-'));
                $account_id = $model->account_id;
                $buyer_id = $model->buyer;

                $subject_model = \app\modules\mails\models\EbayInboxSubject::findOne(['buyer_id'=>$buyer_id,'item_id'=>$item_id,'account_id'=>$account_id]);
                ?>

                <dl class="dl-horizontal">
                    <dt style="width:100px;">ebay message</dt>
                    <?php
                    if($subject_model)
                    {
                        echo '<dd><a href="/mails/ebayinboxsubject/detail?id='.$subject_model->id.'" target="_blank">'.$subject_model->first_subject.'</a></dd>';
                    }
                    else
                    {
                        echo '<dd style="width:70px;">无</dd>';
                    }
                    ?>
                </dl>

                <?php if($model->cancel_state != 2 && $model->cancel_status != 5):?>
                    <div class="popup-wrapper">
                        <?php
                        $responseModel = new \app\modules\mails\models\EbayCancellationsResponse();
                        $form = yii\bootstrap\ActiveForm::begin([
                            'id' => 'account-form',
                            'layout' => 'horizontal',
                            'action' => Yii::$app->request->getUrl(),
                            'enableClientValidation' => false,
                            'validateOnType' => false,
                            'validateOnChange' => false,
                            'validateOnSubmit' => true,
                        ]);
                        ?>
                        <div class="popup-body">
                            <div class="row">
                                <input type="radio" name="EbayCancellationsResponse[type]" value="1" checked>接受
                                <input type="radio" name="EbayCancellationsResponse[type]" value="2">拒绝
                                <div class="ebay_cancellations_response_explain">
                                    <textarea cols="100" rows="10" name="EbayCancellationsResponse[explain]"></textarea>
                                </div>
                                <link href="<?php echo yii\helpers\Url::base(true);?>/laydate/need/laydate.css" rel="stylesheet">
                                <link href="<?php echo yii\helpers\Url::base(true);?>/laydate/skins/default/laydate.css" rel="stylesheet">
                                <script src="<?php echo yii\helpers\Url::base(true);?>/laydate/laydate.js"></script>
                                <div class="ebay_cancellations_refuse_params">
                                    发货时间：<input class="laydate-icon" id="demo" value="" name="EbayCancellationsResponse[shipment_date]"/>
                                    跟踪号：<input type="text" name="EbayCancellationsResponse[tracking_number]"/>
                                </div>
                                <script>
                                    void function(){
                                        laydate({
                                            elem: '#demo',
                                            format: 'YYYY/MM/DD hh:mm:ss',
                                        })
                                    }();
                                    $(function(){
                                        $('.ebay_cancellations_refuse_params').hide();
                                        $('input[name="EbayCancellationsResponse[type]"]').click(function(){
                                            switch($(this).val())
                                            {
                                                case '1' :
                                                    $('.ebay_cancellations_response_explain').show();
                                                    $('.ebay_cancellations_refuse_params').hide();
                                                    break;
                                                case '2' :
                                                    $('.ebay_cancellations_response_explain').hide();
                                                    $('.ebay_cancellations_refuse_params').show();
                                            }
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="popup-footer">
                            <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
                            <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
                        </div>
                        <?php
                        yii\bootstrap\ActiveForm::end();
                        ?>
                    </div>
                <?php endif;?>
            </div>
            <div id="menu1" class="tab-pane fade">
                <table class="table">
                    </thead>
                    <tbody id="basic_info">
                    <?php if(!empty($info['info'])){?>
                        <?php
                        $account_info = \app\modules\accounts\models\Account::getHistoryAccountInfo($info['info']['account_id'],$info['info']['platform_code']);
                        ?>
                        <tr><td>订单号</td><td><?php echo isset($account_info->account_short_name) ? $account_info->account_short_name.'-'.$info['info']['order_id'] : $info['info']['order_id'];?></td><td>销售平台</td><td><?php echo $info['info']['platform_code'];?></td></tr>
                        <tr><td>平台订单号</td><td><?php echo $info['info']['platform_order_id'];?></td><td>买家ID</td>
                            <td>
                                <?php echo $info['info']['buyer_id'];?>&nbsp;&nbsp;
                                <!--添加黑名单  取消黑名单操作 add by allen <2018-2-8> str-->
                                <span id="blackinfo">
                                    <?php if (Gbc::checkInBlackList($info['info']['buyer_id'], $info['info']['platform_code'])) { ?>
                                        <a class="cancelBlackList" href="javascript:void(0);" style="color:blue;" data-buyerid="<?php echo $info['info']['buyer_id']; ?>" data-platformcode="<?php echo $info['info']['platform_code']; ?>">取消黑名单</a>
                                    <?php } else { ?>
                                        <a class="addBlackList" href="javascript:void(0);" style="color:blue;" data-buyerid="<?php echo $info['info']['buyer_id']; ?>" data-platformcode="<?php echo $info['info']['platform_code']; ?>">加入黑名单</a>
                                    <?php } ?>
                                </span>
                                <!--添加黑名单  取消黑名单操作 add by allen <2018-2-8> end-->
                            </td></tr>
                        <tr><td>下单时间</td><td><?php echo $info['info']['created_time'];?></td><td>付款时间</td><td><?php echo $info['info']['paytime'];?></td></tr>
                        <tr><td>运费</td><td><?php echo $info['info']['ship_cost'] .'('. $info['info']['currency'].')';?></td><td>总费用</td><td><?php echo $info['info']['total_price'] .'('. $info['info']['currency'].')';?></td></tr>
                        <tr><td>eBay账号</td><td><?php echo $accountName?></td><td>送货地址</td><td colspan="3" >
                                <?php echo $info['info']['ship_name'];?>
                                (tel:<?php echo $info['info']['ship_phone'];?>)<br>
                                <?php echo $info['info']['ship_street1'] + $info['info']['ship_street2'] + $info['info']['ship_city_name'];?>,
                                <?php echo $info['info']['ship_stateorprovince'];?>,
                                <?php echo $info['info']['ship_zip'];?>,<br/>
                                <?php echo $info['info']['ship_country_name'];?>
                            </td>
                        </tr>
                        <tr><td>客户email</td><td><?php echo $info['info']['email'];?></td><td><a _width="100%" _height ="100%" class="edit-button" href="/mails/ebayreply/initiativeadd?order_id=<?php echo $info['info']['order_id'];?>&platform=EB">发送消息</a></td></tr>
                        <tr><td>订单状态</td><td colspan="3">
                                <?php
                                $complete_status = Order::getOrderCompleteStatus();
                                echo $complete_status[$info['info']['complete_status']];
                                ?>
                            </td>
                        </tr>
                    <?php }else{?>
                        <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu2" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>标题</th>
                        <th>绑定产品sku</th>
                        <th>数量</th>
                        <th>产品sku</th>
                        <th>数量</th>
                        <th>平台卖价</th>
                        <th>总运费</th>
                        <th>欠货数量</th>
                        <th>库存</th>
                        <th>在途数</th>
                        <th>缩略图</th>
                        <th>总计</th>
                    </tr>
                    </thead>
                    <tbody id="product">
                    <?php if(!empty($info['product'])){?>
                        <?php foreach ($info['product'] as $value){?>
                            <tr>
                                <td style="width: 50%">
                                    <a href="<?php echo 'http://www.ebay.com/itm/'.$value['item_id'];?>" target="_blank"><?php echo $value['title'];?>&nbsp;(item_number:<?php echo $value['item_id'];?>)</a></td>
                                <td rowspan="2"><?php echo $value['sku_old'];?></td>
                                <td rowspan="2"><?php echo $value['quantity_old'];?></td>
                                <td rowspan="2"><?php echo $value['sku'];?></td>
                                <td rowspan="2"><?php echo $value['quantity'];?></td>
                                <td rowspan="2"><?php echo $value['sale_price'];?></td>
                                <td rowspan="2"><?php echo $value['ship_price'];?></td>
                                <td rowspan="2"><?php echo $value['qs'];?></td>
                                <td rowspan="2"><?php echo $value['stock'];?></td>
                                <td rowspan="2"><?php echo $value['on_way_stock'];?></td>
                                <td rowspan="2" ><img style="border:1px solid #ccc;padding:2px;width:60px;height:60px;" src="<?php echo Order::getProductImageThub($value['sku']);?>" alt="<?php echo $value['sku']?>" /></td>
                                <td rowspan="2"><?php echo $value['total_price'];?></td>
                            </tr>
                            <tr>
                                <td bgcolor="#F8F8F8" valign="<?php echo $value['picking_name'];?>" class="p-picking-name"><?php echo $value['picking_name']?>&nbsp;(sku:<?php echo $value['sku'];?>)</td>
                            </tr>
                        <?php }?>
                    <?php }else{?>
                        <tr><td colspan="6" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu3" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>交易号</th>
                        <th>付款帐号</th>
                        <th>收款帐号</th>
                        <th>交易时间</th>
                        <th>交易类型</th>
                        <th>交易状态</th>
                        <th>交易金额</th>
                        <th>手续费</th>
                    </tr>
                    </thead>
                    <tbody id="trade">
                    <?php if(!empty($info['trade'])){?>
                        <?php foreach ($info['trade'] as $value){?>
                            <tr>
                                <td><?php echo $value['transaction_id'];?></td>
                                <td><?php echo $value['payer_email'];?></td>
                                <td><?php echo $value['receiver_business'];?></td>
                                <td><?php echo $value['order_pay_time'];?></td>
                                <td><?php echo $value['receive_type'];?></td>
                                <td><?php echo $value['payment_status'];?></td>
                                <td><?php echo $value['amt'];?>(<?php echo $value['currency'];?>)</td>
                                <td><?php echo $value['fee_amt'];?></td>
                            </tr>
                        <?php }?>
                    <?php }else{?>
                        <tr><td colspan="6" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu4" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>包裹号</th>
                        <th>发货仓库</th>
                        <th>运输方式</th>
                        <th>追踪号</th>
                        <th>总运费</th>
                        <th>出货时间</th>
                        <th>重量</th>
                        <th>产品</th>
                    </tr>
                    </thead>
                    <tbody id="package">
                    <?php if(!empty($info['orderPackage'])){?>
                        <?php foreach ($info['orderPackage'] as $value){?>
                            <tr>
                                <td><?php echo $value['package_id'];?></td>
                                <td><?php echo $value['warehouse_name'];?></td>
                                <td><?php echo $value['ship_name'];?></td>
                                <td><?php
                                    if (!empty($value['tracking_number_1'])) {
                                        echo "<a _width='100%' _height='120%' class='edit-button' href='http://www.17track.net/zh-cn/track?nums=" . $value['tracking_number_1'] . "' title='物流商实际追踪号'>".$value['tracking_number_1'] ."</a>";
                                    }else{
                                        echo "<a _width='100%' _height='120%' class='edit-button' href='http://www.17track.net/zh-cn/track?nums=" . $value['tracking_number_2' ] . "' title='代理商追踪号'>".$value['tracking_number_2'] ."</a>";
                                    }
                                    ?>
                                </td>
                                <td><?php echo $value['shipping_fee'];?></td>
                                <td><?php echo $value['shipped_date'];?></td>
                                <td><?php echo $value['package_weight'];?></td>
                                <td>
                                    <?php foreach ($value['items'] as $sonvalue){?>
                                        <p>sku：<?php echo $sonvalue['sku'];?> 数量：<?php echo $sonvalue['quantity'];?></p>
                                    <?php }?>
                                </td>
                            </tr>
                        <?php }?>
                    <?php }else{?>
                        <tr><td colspan="7" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu5" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th colspan="3">收入</th>
                        <th colspan="8">成本/支出</th>
                        <th >利润</th>
                        <th>利润率</th>
                    </tr>
                    </thead>
                    <tr>
                        <td style="color: green;">产品金额</td>
                        <td style="color: green;">运费</td>
                        <td style="color: green;">调整金额</td>
                        <td style="color: red;">平台佣金</td>
                        <td style="color: red;">交易佣金</td>
                        <td style="color: red;">货物成本</td>
                        <td style="color: red;">包装成本</td>
                        <td style="color: red;">包材成本</td>
                        <td style="color: red;">运费成本</td>
                        <td style="color: red;">退款金额</td>
                        <td style="color: red;">重寄费用</td>
                        <?php if(!empty($info['profit'])){?>
                            <td rowspan="3">
                                <?php echo $info['profit']['profit'] >= 0 ? '<font color="green">' . $info['profit']['profit']. '(CNY)</font>'
                                    : '<font color="red">' . $info['profit']['profit'] . '(CNY)</font>';?>
                            </td>
                            <td rowspan="3">
                                <?php echo $info['profit']['profit_rate'] >= 0 ? '<font color="green">' . $info['profit']['profit_rate'] . '%</font>'
                                    : '<font color="red">' . $info['profit']['profit_rate'] . '%</font>';?>
                            </td>
                        <?php }; ?>
                    </tr>
                    <tbody id="profit_id">
                    <?php if(!empty($info['profit'])){?>
                        <tr>
                            <td><?php echo $info['profit']['product_price'];?>(CNY)</td>
                            <td><?php echo $info['profit']['shipping_price'];?>(CNY)</td>
                            <td><?php echo $info['profit']['adjust_amount'];?>(CNY)</td>
                            <td><?php echo $info['profit']['final_value_fee'];?>(CNY)</td>
                            <td><?php echo $info['profit']['pay_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['purchase_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['package_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['packing_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['shipping_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['refund_amount'];?>(CNY)</td>
                            <td><?php echo $info['profit']['redirect_cost'];?>(CNY)</td>
                        </tr>
                        <?php
                        $totalRevnue = 0;
                        $totalCost = 0;
                        if (!empty($info['profit']))
                        {
                            $totalRevnue += $info['profit']['product_price'] + $info['profit']['shipping_price'] + $info['profit']['adjust_amount'];
                            $totalCost += $info['profit']['purchase_cost'] + $info['profit']['final_value_fee'] + $info['profit']['shipping_cost'] + $info['profit']['pay_cost']
                                + $info['profit']['refund_amount'] + $info['profit']['redirect_cost'] + $info['profit']['packing_cost'] + $info['profit']['package_cost'];
                        }
                        ?>
                        <tr>
                            <td colspan="3" align="center" style="color: green"><?php echo $totalRevnue;?>(CNY)</td>
                            <td colspan="8" align="center" style="color: red"><?php echo $totalCost;?>(CNY)</td>
                        </tr>
                        <tr>
                            <td colspan="13"><strong>汇率值：</strong><?php echo $info['profit']['currency_rate'];?>&nbsp;&nbsp;
                                (<?php echo substr($info['profit']['create_time'], 0, 10);?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $info['profit']['currency'];?>
                                ->CNY)&nbsp;&nbsp;<strong>利润计算公式：</strong>（收入-成本/支出）-退款-重寄费用。
                            </td>
                        </tr>
                    <?php }else{?>
                        <tr><td colspan="9" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu6" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>发货仓库:</th>
                        <th>邮寄方式</th>
                    </tr>
                    </thead>
                    <tbody id="wareh_logistics">
                    <?php if(!empty($info['wareh_logistics'])){?>
                        <tr>
                            <td><?php echo $info['wareh_logistics']['warehouse']['warehouse_name'];?></td>
                            <td><?php //echo $info['wareh_logistics']['logistics']['ship_name'];?></td>
                        </tr>
                    <?php }else{?>
                        <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
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
<!--<script type="text/javascript">-->
<!--    $(window).load(function() {-->
<!--        $.get("/mails/aliexpress/order",-->
<!--            {-->
<!--                order_id:'79774017137549'-->
<!--            },-->
<!--            function(result){-->
<!--                var obj = eval('('+result+')');-->
<!--                if(obj){-->
<!--                    /*基本信息*/-->
<!--                    var basic_info = '';-->
<!--                    if(obj.info){-->
<!--                        basic_info += '<tr><td>订单号</td><td>'+obj.info.order_id+'</td><td>销售平台</td><td>'+obj.info.platform_code+'</td></tr>';-->
<!--                        basic_info += '<tr><td>平台订单号</td><td>'+obj.info.platform_order_id+'</td><td>买家ID</td><td>'+obj.info.buyer_id+'</td></tr>';-->
<!--                        basic_info += '<tr><td>下单时间</td><td>'+obj.info.created_time+'</td><td>付款时间</td><td>'+obj.info.paytime+'</td></tr>';-->
<!--                        basic_info += '<tr><td>运费</td><td>'+obj.info.ship_cost+obj.info.currency+'</td><td>总费用</td><td>'+obj.info.total_price+'</td></tr>';-->
<!--                        basic_info += '<tr><td>送货地址</td><td colspan="3" >'+obj.info.ship_name+'(tel:'+obj.info.ship_phone+')<br>'+obj.info.ship_street1+obj.info.ship_street2+obj.info.ship_city_name+','+obj.info.ship_stateorprovince+','+obj.info.ship_zip+',<br/>'+obj.info.ship_country_name+'</td></tr>';-->
<!--                    }else {-->
<!--                        basic_info +='<tr><td colspan="4" align="center">没有找到信息！</td></tr>';-->
<!--                    }-->
<!--                    $('#basic_info').text('');-->
<!--                    $('#basic_info').append(basic_info);-->
<!--                    /*产品信息*/-->
<!--                    var product = '';-->
<!--                    if(obj.product){-->
<!--                        $.each(obj.product,function(m,value) {-->
<!--                            product += '<tr>' +-->
<!--                                '<td style="width: 50%">'+value.title+'</td>' +-->
<!--                                '<td>'+value.sku+'</td>' +-->
<!--                                '<td>'+value.quantity+'</td>' +-->
<!--                                '<td>'+value.sale_price+'</td>' +-->
<!--                                '<td>'+value.ship_price+'</td>' +-->
<!--                                '<td>'+value.qs+'</td>' +-->
<!--                                '<td>'+value.total_price+'</td>' +-->
<!--                                '</tr>';-->
<!--                               });-->
<!--                    }else {-->
<!--                        product +='<tr><td colspan="7" align="center">没有找到信息！</td></tr>';-->
<!--                    }-->
<!--                    $('#product').text('');-->
<!--                    $('#product').append(product);-->
<!--                    /*交易信息*/-->
<!--                    var trade = '';-->
<!--                    if(obj.trade){-->
<!--                        $.each(obj.trade,function(m,value) {-->
<!--                            trade += '<tr>' +-->
<!--                                '<td>'+value.transaction_id+'</td>' +-->
<!--                                '<td>'+value.order_pay_time+'</td>' +-->
<!--                                '<td>'+value.receive_type+'</td>' +-->
<!--                                '<td>'+value.payment_status+'</td>' +-->
<!--                                '<td>'+value.amt+'('+value.currency+')</td>' +-->
<!--                                '<td>'+value.fee_amt+'</td>' +-->
<!--                                '</tr>';-->
<!--                        });-->
<!--                    }else {-->
<!--                        trade +='<tr><td colspan="7" align="center">没有找到信息！</td></tr>';-->
<!--                    }-->
<!--                    $('#trade').text('');-->
<!--                    $('#trade').append(trade);-->
<!---->
<!--                    /*包裹信息*/-->
<!--                    var package = '';-->
<!--                    var items = '';-->
<!--                    if(obj.orderPackage){-->
<!--                        $.each(obj.orderPackage,function(m,value) {-->
<!--                            items = '';-->
<!--                            $.each(value.items,function(k,sonvalue) {-->
<!--                                items +='<p>sku：'+sonvalue.sku+' 数量：'+sonvalue.quantity+'</p>';-->
<!--                            });-->
<!--                            package += '<tr>' +-->
<!--                                '<td>'+value.package_id+'</td>' +-->
<!--                                '<td>'+value.warehouse_name+'</td>' +-->
<!--                                '<td>'+value.ship_name+'</td>' +-->
<!--                                '<td>'+value.shipping_fee+'</td>' +-->
<!--                                '<td>'+value.shipped_date+'</td>' +-->
<!--                                '<td>'+value.package_weight+'</td>' +-->
<!--                                '<td>'+items+'</td>' +-->
<!--                                '</tr>';-->
<!--                        });-->
<!--                    }else {-->
<!--                        package +='<tr><td colspan="7" align="center">没有找到信息！</td></tr>';-->
<!--                    }-->
<!--                    $('#package').text('');-->
<!--                    $('#package').append(package);-->
<!--                    /*利润信息*/-->
<!--                    var profit_str = '';-->
<!--                    if(obj.profit){-->
<!--                            profit_str += '<tr>' +-->
<!--                                '<td>'+obj.profit.amt+'</td>' +-->
<!--                                '<td>'+obj.profit.finalvaluefee+'</td>' +-->
<!--                                '<td>'+obj.profit.fee_amt+'</td>' +-->
<!--                                '<td>'+obj.profit.refund_amt+'</td>' +-->
<!--                                '<td>'+obj.profit.back_amt+'</td>' +-->
<!--                                '<td>'+obj.profit.product_cost+'</td>' +-->
<!--                                '<td>'+obj.profit.ship_cost+'</td>' +-->
<!--                                '<td>'+obj.profit.final_profit+'</td>' +-->
<!--                                '<td>'+obj.profit.rate+'</td>' +-->
<!--                                '</tr>';-->
<!--                    }else {-->
<!--                        profit_str +='<tr><td colspan="9" align="center">没有找到信息！</td></tr>';-->
<!--                    }-->
<!--                    $('#profit_id').text('');-->
<!--                    $('#profit_id').append(profit_str);-->
<!--                    /*仓储物流*/-->
<!--                    var wareh_logistics = '';-->
<!--                    if(obj.wareh_logistics){-->
<!--                        wareh_logistics += '<tr>' +-->
<!--                            '<td>'+obj.wareh_logistics.warehouse.warehouse_name+'</td>' +-->
<!--                            '<td>'+obj.wareh_logistics.logistics.ship_name+'</td>' +-->
<!--                            '</tr>';-->
<!--                    }else {-->
<!--                        wareh_logistics +='<tr><td colspan="9" align="center">没有找到信息！</td></tr>';-->
<!--                    }-->
<!--                    $('#wareh_logistics').text('');-->
<!--                    $('#wareh_logistics').append(wareh_logistics);-->
<!--                }-->
<!--            });-->
<!--    });-->
<!--</script>-->