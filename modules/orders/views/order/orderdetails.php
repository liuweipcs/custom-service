<?php
use yii\helpers\Url;
use app\modules\orders\models\Order;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\accounts\models\Platform;

?>
<style>
    li{list-style: none;}
    .hear-title,.search-box ul{overflow: hidden;}
    .hear-title p:nth-child(1) span:nth-child(1),.hear-title p:nth-child(2) span:nth-child(1){display: inline-block;width: 30%}
    .item-list li{border-bottom: 1px solid #ddd;padding: 5px 10px}
    .item-list li span{display: inline-block;width: 25%}
    .search-box ul li{float: left;padding:0 10px 10px 0}
    .search-box textarea{display: block;margin-top: 10px;width: 100%}
    .info-box .det-info{width: 100%;height: 200px;border: 2px solid #ddd;}
    .well span{padding: 6%}
    .well p{text-align:left}
    #remarkTable tr td{width: 250px;}

</style>

<div class="popup-wrapper">
    <div class="popup-body">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion"
                       href="#collapseOne">
                        基本信息
                    </a>
                </h4>
            </div>
            <div id="collapseOne" class="panel-collapse collapse in">
                <div id="home" class="tab-pane in active">
                    <table class="table">
                        </thead>
                        <tbody id="basic_info">
                        <?php if(!empty($info['info'])){?>
                            <?php
//                            if($info['info']['platform_code'] == Platform::PLATFORM_CODE_EB)
                            $account_info = \app\modules\accounts\models\Account::getHistoryAccountInfo($info['info']['account_id'],$info['info']['platform_code']);
                            ?>
                            <tr><td>订单号</td><td><?php echo isset($account_info->account_short_name) ? $account_info->account_short_name.'--'.$info['info']['order_id'] : $info['info']['order_id'];?></td><td>销售平台</td><td><?php echo $info['info']['platform_code'];?></td></tr>
                            <tr><td>平台订单号</td><td><?php echo $info['info']['platform_order_id'];?></td><td>买家ID</td><td><?php echo $info['info']['buyer_id'];?></td></tr>
                            <tr><td>下单时间</td><td><?php echo $info['info']['created_time'];?></td><td>付款时间</td><td><?php echo $info['info']['paytime'];?></td></tr>
                            <tr><td>运费</td><td><?php echo $info['info']['ship_cost'] + $info['info']['currency'];?></td><td>总费用</td><td><?php echo $info['info']['total_price'].'('.$info['info']['currency'].')';?></td></tr>
                            <tr><td>送货地址</td><td>
                                    <?php echo $info['info']['ship_name'];?>
                                    (tel:<?php echo $info['info']['ship_phone'];?>)<br>
                                    <?php echo $info['info']['ship_street1'] . ',' . ($info['info']['ship_street2'] == '' ? '' : $info['info']['ship_street2'] . ',') . $info['info']['ship_city_name'];?>,
                                    <?php echo $info['info']['ship_stateorprovince'];?>,
                                    <?php echo $info['info']['ship_zip'];?>,<br/>
                                    <?php echo $info['info']['ship_country_name'];?>
                                    <?php if ($info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) { ?>
                                        <br />
                                        <a href="javascript:void(0)" id="address-edit-button">编辑发货地址</a>
                                    <?php } ?>
                                </td>
                                <td>发货类型</td><td><?php echo $info['info']['amazon_fulfill_channel'];?></td>
                            </tr>
                            <tr><td>客户email</td><td><?php echo $info['info']['email'];?></td>
                                <?php if($info['info']['platform_code'] == "EB") echo '<td>操作</td><td><a class="edit-button" href="/mails/ebayreply/initiativeadd?order_id='.$info['info']['order_id'].'&platform=EB">发送消息</a>&nbsp; <a _class="edit-button" href="'.Url::toRoute(['/mails/ebayfeedback/replyback', 'order_id' => $info['info']['platform_order_id'], 'platform' => 'EB']).'">回评</a></td>';?>
                            </tr>
                            <tr><td>产品估重</td><td><?php echo $info['info']['product_weight'].'(g)';?></td><td>店铺名称</td><td><?php echo \app\modules\accounts\models\Account::getHistoryAccount($info['info']['account_id'],$info['info']['platform_code']);?></td></tr>
                            <?php if(Platform::PLATFORM_CODE_EB == 'EB'):?>
                                <tr><td>客户留言</td><td colspan="3"><?php if(!empty($info['note']))echo $info['note']['note']?></td>
                                </tr>
                                <tr><td>订单状态</td><td colspan="3">
                                        <?php
                                        $complete_status = Order::getOrderCompleteStatus();
                                        echo $complete_status[$info['info']['complete_status']];
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td id='remarkTable' colspan="4">
                                        <?php if(!empty($info['remark'])):?>
                                            <table>
                                                <?php foreach ($info['remark'] as $key => $value):?>
                                                    <tr>
                                                        <td><?=$value['remark']?></td>
                                                        <td><?=$value['create_user']?></td>
                                                        <td><?=$value['create_time']?></td>
                                                        <td><a href="javascript:;" onclick="removeRemark(<?php echo $value['id'];?>)">删除</a></td>
                                                    </tr>
                                                <?php endforeach;?>
                                            </table>
                                        <?php endif;?>

                                    </td>

                                </tr>
                                <tr>
                                  <input type="hidden" class="platform_code" value="<?php echo $info['info']['platform_code']?>">
                                    <td>订单备注</td>
                                    <td><textarea style="width:360px;height:80px;" class="remark"></textarea>
                                        <button onclick=saveRemark("<?php echo $info['info']['order_id'];?>")>添加备注</button><input class="detail_order_id" type="hidden" value="<?php echo $info['info']['order_id'];?>"/>
                                    </td>
                                    <td>出货备注</td>
                                    <td><textarea style="width:360px;height:80px;" class="print_remark"><?php echo $info['info']['print_remark']?></textarea>
                                        <button onclick=save_print_remark("<?php echo $info['info']['order_id'];?>")>添加发货备注</button><input class="detail_order_id" type="hidden" value="<?php echo $info['info']['order_id'];?>"/>
                                    </td>
                                </tr>
                            <?php endif;?>
                            <tr id="address_form_row" style="display:none;">
                                <td colspan="4">
                                    <form class="form-horizontal" action="<?php echo Url::toRoute(['/orders/order/editaddress',
                                        'platform' => $info['info']['platform_code'],
                                        'order_id' => $info['info']['order_id'],
                                    ]);?>" role="form" action="">
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="ship_name" class="col-sm-3 control-label required">收件人<span class="text-danger">*</span></label>
                                                    <div class="col-sm-9">
                                                        <input type="text" name="ship_name" value="<?php echo $info['info']['ship_name'];?>" class="form-control" id="ship_name">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="ship_street1" class="col-sm-3 control-label">地址1<span class="text-danger">*</span></label>
                                                    <div class="col-sm-9">
                                                        <input type="text" name="ship_street1" value="<?php echo $info['info']['ship_street1'];?>" class="form-control" id="ship_street1">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="ship_street2" class="col-sm-3 control-label required">地址2</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" value="<?php echo $info['info']['ship_street2'];?>" name="ship_street2" class="form-control" id="ship_street2">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="ship_city_name" class="col-sm-3 control-label">城市<span class="text-danger">*</span></label>
                                                    <div class="col-sm-9">
                                                        <input type="text" value="<?php echo $info['info']['ship_city_name'];?>" name="ship_city_name" class="form-control" id="ship_city_name">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="ship_stateorprovince" class="col-sm-3 control-label">省/州</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" value="<?php echo $info['info']['ship_stateorprovince'];?>" name="ship_stateorprovince" class="form-control" id="ship_stateorprovince">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="ship_country" class="col-sm-3 control-label">国家<span class="text-danger">*</span></label>
                                                    <div class="col-sm-9">
                                                        <select name="ship_country" id="ship_country" class="form-control">
                                                            <option value="">选择国家</option>
                                                            <?php foreach ($countries as $code => $name) { ?>
                                                                <option<?php echo $info['info']['ship_country'] == $code ? ' selected="selected"' : '';?> value="<?php echo $code;?>"><?php echo $name;?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="ship_zip" class="col-sm-3 control-label">邮编<span class="text-danger">*</span></label>
                                                    <div class="col-sm-9">
                                                        <input type="text" value="<?php echo $info['info']['ship_zip'];?>" name="ship_zip" class="form-control" id="ship_zip">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label for="ship_phone" class="col-sm-3 control-label">电话</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" value="<?php echo $info['info']['ship_phone'];?>" name="ship_phone" class="form-control" id="ship_phone">
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
                        <?php }else{?>
                            <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                        <?php }?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">产品信息</a>
                </h4>
            </div>
            <div id="collapseTwo" class="panel-collapse collapse">
                <div id="menu1" class="tab-pane">
                    <form action="<?php echo Url::toRoute(['/orders/order/editorderproduct',
                        'platform' => $info['info']['platform_code'],
                        'order_id' => $info['info']['order_id'],
                    ]);?>" method="post" role="form">
                        <div id="product-box">
                            <table id="product-table" class="table table-striped">
                                <thead>
                                <tr>
                                    <th>标题</th>
                                    <!--<th>产品中文</th>-->
                                    <th>ASIN</th>
                                    <th>绑定产品sku</th>
                                    <th>数量</th>
                                    <th>产品sku</th>
                                    <th>数量</th>
                                    <th>平台卖价</th>
                                    <th>总运费</th>
                                    <th>欠货数量</th>
                                    <th>库存</th>
                                    <th>在途数</th>
                                    <td>缩略图</td>
                                    <th>总计</th>
                                </tr>
                                </thead>
                                <tbody id="product">
                                <?php if(!empty($info['product'])){?>
                                    <?php foreach ($info['product'] as $value){?>
                                        <tr>
                                            <td value="<?php echo $value['title'];?>" class="p-title" style="width: 30%">
                                                <?php
                                                switch($platform)
                                                {
                                                    case 'EB':
                                                        $mallLink = 'http://www.ebay.com/itm/'.$value['item_id'];
                                                        $endTag = '';
                                                        break;
                                                    case 'AMAZON':
                                                        $mallLink = $value['detail_link_href'];
                                                        $endTag = '';
                                                        break;
                                                    default :
                                                        $mallLink = 'https://www.aliexpress.com/item//'.$value['item_id'];
                                                        $endTag = '.html';
                                                }
                                                ?>
                                                <a href="<?php echo $mallLink,$endTag;?>" target="_blank"><?php echo $value['title'];?>&nbsp;(item_number:<?php echo $value['item_id'];?>)</a>
                                                <?php if(isset($value['asinval'])){?>
                                                    <br>
                                                    <!--                                      <a target="_blank" href="<?php /*echo $value['detail_link_href'];*/?>" title="<?php /*echo $value['detail_link_title'];*/?>"><?php /*echo $value['asinval'];*/?></a>-->
                                                <?php }?>
                                            </td>
                                            <td rowspan="2">
                                                <?php if(isset($value['asinval'])) echo $value['asinval']; else echo '-'; ?>
                                            </td>
                                            <td rowspan="2" value="<?php echo $value['sku_old'];?>" class="p-sku_old"><?php echo $value['sku_old'];?></td>
                                            <td rowspan="2" value="<?php echo $value['quantity_old'];?>" class="p-quantity_old"><?php echo $value['quantity_old'];?></td>
                                            <td rowspan="2" value="<?php echo $value['sku'];?>" class="p-sku"><?php echo $value['sku'];?></td>
                                            <td rowspan="2" value="<?php echo $value['quantity'];?>" class="p-quantity"><?php echo $value['quantity'];?></td>
                                            <td rowspan="2" value="<?php echo $value['sale_price'];?>" class="p-sale-price"><?php echo $value['sale_price'];?></td>
                                            <td rowspan="2" value="<?php echo $value['ship_price'];?>" class="p-ship-price"><?php echo $value['ship_price'];?></td>
                                            <td rowspan="2" value="<?php echo $value['qs'];?>" class="p-qs"><?php echo $value['qs'];?></td>
                                            <td rowspan="2" value="<?php echo $value['stock'];?>" class="p-stock"><?php echo $value['stock'];?></td>
                                            <td rowspan="2" value="<?php echo $value['on_way_stock'];?>" class="p-on-way-stock"><?php echo $value['on_way_stock'];?></td>
                                            <td rowspan="2" ><img style="border:1px solid #ccc;padding:2px;width:60px;height:60px;" src="<?php echo Order::getProductImageThub($value['sku']);?>" alt="<?php echo $value['sku']?>" /></td>
                                            <td rowspan="2" value="<?php echo $value['total_price'];?>" class="p-total-price"><?php echo $value['total_price'];?></td>
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
                            <?php if ($info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) { ?>
                                <!--<div><a id="edit-product-button" href="javascript:void(0);">编辑产品</a></div>-->
                            <?php } ?>
                        </div>
                        <div id="product-edit-box" style="display: none;">
                            <table class="table table-striped">
                                <tbody>
                                <tr>
                                    <th>标题</th>
                                    <th>产品sku</th>
                                    <th>数量</th>
                                    <th>平台卖价</th>
                                    <th>总运费</th>
                                    <th>欠货数量</th>
                                    <th>库存</th>
                                    <th>在途数</th>
                                    <th>总计</th>
                                    <th>操作</th>
                                </tr>
                                </tbody>
                            </table>
                            <div><a href="javascript:void(0);" id="add-row-button">添加产品</a></div>
                            <div class="popup-footer">
                                <button class="btn btn-primary ajax-submit" type="button">保存</button>
                                <button class="btn btn-default" id="product-cancel-button" type="button">取消</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion"
                       href="#collapseThree">
                        交易信息
                    </a>
                </h4>
            </div>
            <div id="collapseThree" class="panel-collapse collapse">
                <div id="menu2" class="tab-pane">
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
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion"
                       href="#collapseFour">
                        包裹信息
                    </a>
                </h4>
            </div>
            <div id="collapseFour" class="panel-collapse collapse">
                <div id="menu3" class="tab-pane">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>包裹号</th>
                            <th>追踪号</th>
                            <th>发货仓库</th>
                            <th>运输方式</th>
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
                                    <td><?php
                                        if (!empty($value['tracking_number_1'])) {
                                            echo "<a target=\"_blank\" href='http://www.17track.net/zh-cn/track?nums=" . $value['tracking_number_1'] . "' title='物流商实际追踪号'>".$value['tracking_number_1'] ."</a>";
                                        }else{
                                            echo "<a target=\"_blank\" href='http://www.17track.net/zh-cn/track?nums=" . $value['tracking_number_2' ] . "' title='代理商追踪号'>".$value['tracking_number_2'] ."</a>";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $value['warehouse_name'];?></td>
                                    <td><?php echo $value['ship_name'];?></td>
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
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion"
                       href="#collapseFive">
                        利润信息
                    </a>
                </h4>
            </div>
            <div id="collapseFive" class="panel-collapse collapse">
                <div id="menu4" class="tab-pane">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th colspan="3">收入</th>
                            <th colspan="8">成本/支出</th>
                            <th >利润</th>
                            <th>利润率</th>
                        </tr>
                        </thead>
                        <tbody id="profit_id">
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
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion"
                       href="#collapseSix">
                        仓储物流
                    </a>
                </h4>
            </div>
            <div id="collapseSix" class="panel-collapse collapse">
                <div id="menu5" class="tab-pane">
                    <div id="warehouse-box">
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
                                    <td><?php echo isset($info['wareh_logistics']['logistics']) ?
                                            $info['wareh_logistics']['logistics']['ship_name'] : '';?></td>
                                </tr>
                            <?php }else{?>
                                <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                            <?php }?>
                            </tbody>
                        </table>
                        <?php if ($info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) { ?>
                            <div><a id="edit-warehouse-button" href="javascript:void(0);">编辑仓库物流</a></div>
                        <?php } ?>
                    </div>
                    <div id="warehouse-edit-box" style="display:none;">
                        <form action="<?php echo Url::toRoute(['/orders/order/editorderwarehouse',
                            'platform' => $info['info']['platform_code'],
                            'order_id' => $info['info']['order_id'],
                        ]);?>" method="post" role="form">
                            <br />
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="warehouse_id" class="col-sm-3 control-label required">发货仓库<span class="text-danger">*</span></label>
                                        <div class="col-sm-9">
                                            <select onchange="getLogistics(this)" class="form-control" name="warehouse_id">
                                                <option value="">选择仓库</option>
                                                <?php foreach ($warehouseList as $warehouseId => $warehouseName) { ?>
                                                    <option value="<?php echo $warehouseId;?>"<?php //echo $info['info']['warehouse_id'] == $warehouseId ?
                                                    //' selected="selected"' : '';?>><?php echo $warehouseName;?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="ship_code" class="col-sm-3 control-label">邮寄方式<span class="text-danger">*</span></label>
                                        <div class="col-sm-9">
                                            <select class="form-control" name="ship_code">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br />
                            <div class="popup-footer">
                                <button class="btn btn-primary ajax-submit" type="button">保存</button>
                                <button class="btn btn-default" id="warehouse-cancel-button" type="button">取消</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion"
                       href="#collapseSeven">
                        售后问题
                    </a>
                </h4>
            </div>
            <div id="collapseSeven" class="panel-collapse collapse">
                <div id="menu6" class="tab-pane">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>售后单号</th>
                            <th>售后类型</th>
                            <th>退款金额</th>
                            <th>原因</th>
                            <th>状态</th>
                            <th>创建人</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (!empty($afterSalesOrders)){
                            foreach ($afterSalesOrders as $afterSalesOrder){ ?>
                                <tr>
                                    <td><?php echo $afterSalesOrder['after_sale_id'];?></td>
                                    <td><?php echo AfterSalesOrder::getOrderTypeList($afterSalesOrder['type']);?></td>
                                    <td><?php echo ($afterSalesOrder['type'] == 1)? $afterSalesOrder['refund_amount'].' '.$afterSalesOrder['currency']:"-";?></td>
                                    <td><?php echo RefundReturnReason::getReasonContent($afterSalesOrder['reason_id']);?></td>
                                    <td><?php echo AfterSalesOrder::getOrderStatusList($afterSalesOrder['status']);?></td>
                                    <td><?php echo $afterSalesOrder['create_by'];?></td>
                                    <td><?php echo $afterSalesOrder['create_time'];?></td>
                                    <td>
                                        <?php if ($afterSalesOrder['status'] == AfterSalesOrder::ORDER_STATUS_WATTING_AUDIT) { ?>
                                            <div class="btn-group btn-list">
                                                <button type="button" class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation');?></button>
                                                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                    <span class="caret"></span>
                                                    <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List');?></span>
                                                </button>
                                                <ul class="dropdown-menu" rol="menu">
                                                    <li><a class="ajax-button" href="<?php echo Url::toRoute(['/aftersales/order/audit',
                                                            'after_sales_id' => $afterSalesOrder['after_sale_id'],
                                                            'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED]);?>">审核通过</a></li>
                                                    <li><a class="ajax-button" href="<?php echo Url::toRoute(['/aftersales/order/audit',
                                                            'after_sales_id' => $afterSalesOrder['after_sale_id'],
                                                            'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]);?>">审核不通过</a></li>
                                                </ul>
                                            </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion"
                       href="#collapseEight">
                        延长收货期
                    </a>
                </h4>
            </div>
            <div id="collapseEight" class="panel-collapse collapse">
                <div id="menu7" class="tab-pane">
                    <div class="extendAcceptGoodsTime" tabindex="-1" data-widget-cid="widget-6" style="width: 700px; z-index: 999; left: 699.5px; top: 115.5px;">
                        <div class="" data-role="content" style="max-height: none;">
                            <div class="">
                                <p id="rejectReasonErrorTip" class="">为防止货物在运输途中的突发因素，导致买家不能及时收到货物，您可以适当延长买家收货时间。</p>
                                <form name="" id="extendAcceptGoodsTimeForm" action="" method="post">
                                    <input type="hidden" name="account_name" value="<?php echo \app\modules\accounts\models\Account::getHistoryAccount($info['info']['account_id'],$info['info']['platform_code']);?>">
                                    <input type="hidden" name="platform_order_id" value="<?php echo $info['info']['platform_order_id'];?>">
                                    <input type="hidden" name="platform_code" value="<?php echo $info['info']['platform_code'];?>">
                                    <p id="rejectReasonError" class="">延长买家收货确认时间
                                        <input id="day" name="day" size="5" maxlength="10" type="text">天
                                    </p>
                                </form>
                            </div>
                        </div>
                        <div class="ui-window-btn" data-role="buttons">
                            <input type="button" value="确认" id="confirm" class="" data-role="confirm">
                            <input type="button" value="关闭" class="cancel" data-role="cancel">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$(function(){
    $(".return_refund_id").click(function(){
        var element = "#" + $(this).attr('data-class');
        var display = $(element).css('display');
        var display_value = display == 'none' ? 'table-cell' : 'none';
        $(element).css('display',display_value);
    });
    $('a#address-edit-button').click(function(){
        $('tr#address_form_row').show();
    });
    $('button#address-cancel-button').click(function(){
    	$('tr#address_form_row').hide();
    });
    $('a#edit-warehouse-button').click(function(){
        $('div#warehouse-box').hide();
        $('div#warehouse-edit-box').show();
    });
    $('button#warehouse-cancel-button').click(function(){
        $('div#warehouse-box').show();
        $('div#warehouse-edit-box').hide();
    });
    $('a#edit-product-button').click(function(){
        var html = '<tr><th>标题</th>' + "\n" +
            '<th>产品sku</th>' + "\n" +
            '<th>数量</th>' + "\n" +
            '<th>平台卖价</th>' + "\n" +
            '<th>总运费</th>' + "\n" +
            '<th>欠货数量</th>' + "\n" +
            '<th>库存</th>' + "\n" +
            '<th>在途数</th>' + "\n" +
            '<th>总计</th>' + "\n" +
            '<th>操作</th></tr>' + "\n";
        $('tbody#product').find('tr').each(function(){
            html += '<tr>' + "\n" +
            '<td class="picking_name"><input class="form-control" type="text" name="product_title[]" value="' + $(this).find('td.p-title').attr('value') + '" /></td>' + "\n" +
            '<td><input class="form-control" type="text" name="sku[]" value="' + $(this).find('td.p-sku').attr('value') + '" onblur="get_sku(this)" /></td>' + "\n" +
            '<td><input class="form-control" type="text" name="quantity[]" value="' + $(this).find('td.p-quantity').attr('value') + '" /></td>' + "\n" +
            '<td><input class="form-control" type="text" name="sale_price[]" value="' + $(this).find('td.p-sale-price').attr('value') + '" /></td>' + "\n" +
            '<td><input class="form-control" type="text" name="ship_price[]" value="' + $(this).find('td.p-ship-price').attr('value') + '" /></td>' + "\n" +
            '<td>' + $(this).find('td.p-qs').attr('value') + '</td>' + "\n" +
            '<td>' + $(this).find('td.p-stock').attr('value') + '</td>' + "\n" +
            '<td>' + $(this).find('td.p-on-way-stock').attr('value') + '</td>' + "\n" +
            '<td>' + $(this).find('td.p-total-price').attr('value') + '</td>' + "\n" +
            '<td><a href="javascript:void(0)" id="delete-row-button">删除</a></td>' + "\n" +
            '</tr>';
        });
        $('div#product-edit-box').show();
        $('div#product-edit-box tbody').empty().append(html);
        $('div#product-box').hide();
        $('button#product-cancel-button').click(function(){
            $('div#product-box').show();
            $('div#product-edit-box').hide();
        });
        $('a#delete-row-button').click(function(){
            $(this).parents('tr').remove();
        });
        $('a#add-row-button').unbind('click').click(function(){
        var html = '<tr>' +
        '<td class="picking_name"><input class="form-control" type="text" name="product_title[]" value="" /></td>' + "\n" +
        '<td><input class="form-control" type="text" name="sku[]" value="" onblur="get_sku(this)" /></td>' + "\n" +
        '<td><input class="form-control" type="text" name="quantity[]" value="1" /></td>' + "\n" +
        '<td><input class="form-control" type="text" name="sale_price[]" value="0" /></td>' + "\n" +
        '<td><input class="form-control" type="text" name="ship_price[]" value="0" /></td>' + "\n" +
        '<td></td>' + "\n" +
        '<td></td>' + "\n" +
        '<td></td>' + "\n" +
        '<td></td>' + "\n" +
        '<td><a href="javascript:void(0)" id="delete-row-button">删除</a></td>' + "\n" +
        '</tr>';
        $('div#product-edit-box table tbody').append(html);
        $('a#delete-row-button').click(function(){
            $(this).parents('tr').remove();
        });
    });
    });

    $("#confirm" ).click(function(){
        $.ajax({
            type: "POST",
            url : '<?php echo Url::toRoute(['/orders/order/extendacceptgoodstime']);?>',
            data:$('#extendAcceptGoodsTimeForm').serialize(),
            success: function(data) {
               var obj = eval('('+data+')');
                if(obj.ack==1){
                    layer.alert(obj.message, {
                        icon: 1
                    });
                }else{
                    layer.alert(obj.message, {
                        icon: 0
                    });
                }
            }
        });
    })
})

//根据仓库获取物流
function getLogistics(obj)
{
    var warehouseId = $(obj).val();
    var url = '<?php echo Url::toRoute(['/orders/order/getlogistics']);?>';
    $.get(url, 'warehouse_id=' + warehouseId, function(data){
        var html = '';
        if (data.code != '200')
        	if (data.code != '200') {
        		layer.alert(data.message, {
        			icon: 5
        		});
        		return;
        	}
    	if (typeof(data.data) != 'undefined')
    	{
        	var logistics = data.data;
    	    for (var i in logistics)
    	    {
 	    	   html += '<option value="' + i + '">' + logistics[i] + '</option>' + "\n";
            }
        }
        $('select[name=ship_code]').empty().html(html);
    }, 'json');
}

//订单备注
function saveRemark(orderId){

    var url = '<?php echo Url::toRoute(['/orders/order/addremark']);?>';
    $.post(url,{'order_id':orderId,'remark':$('.remark').val()},function(data){
          if (data.ack != true)
              alert(data.message);
          else
          {
              var info = data.info;
              var html = '';
              for (var i in info)
              {
                  html += '<tr>' + "\n"+
                    '<td>' + info[i].remark + '</td>' + "\n" +
                    '<td>' + info[i].create_user + '</td>' + "\n" +
                    '<td>' + info[i].create_time + '</td>' + "\n" +
                    '<td><a href="javascript:void(0)" onclick="removeRemark(' + info[i].id + ')">删除</a></td>' + "\n" +
                    '</tr>' + "\n";
              }
              $('#remarkTable').empty().html(html);
          }
    },'json');
}

//删除订单备注
function removeRemark(id)
{   
    console.log(id);
    var url ='<?php echo Url::toRoute(['/orders/order/removeremark']);?>';
    $.get(url,{id:id},function(data){
          if (data.ack != true)
              alert(data.message);
          else
          {
              var info = data.info;
              var html = '';
              for (var i in info)
              {
                  html += '<tr>' + "\n"+
                        
                        '<td>' + info[i].remark + '</td>' + "\n" +
                        '<td>' + info[i].create_user + '</td>' + "\n" +
                        '<td>' + info[i].create_time + '</td>' + "\n" +
                        '<td><a href="javascript:void(0)" onclick="removeRemark(' + info[i].id + ')">删除</a></td>' + "\n" +
                        '</tr>' + "\n";
              }
              $('#remarkTable').empty().html(html);
          }
    },'json');
}

//添加出货备注
function save_print_remark(orderId){
    var url = '<?php echo Url::toRoute(['/orders/order/addprintremark']);?>';
    var platform = $('.platform_code').val();
    $.post(url,{'order_id':orderId,'platform':platform,'print_remark':$('.print_remark').val()},function(data){
          alert(data.info);
    },'json');
}

// 根据sku匹配产品信息
function get_sku(obj) {
    var url = '<?php echo Url::toRoute(['/products/product/getproduct']);?>';
    obj = $(obj);
    $.get(url, {"sku":obj.val()}, function(data){
        var returns = data.data;
        if (data.code != '200') {
            layer.alert(data.message, {
                icon: 5
            });
            return;
        }
        else {
            console.log(obj.parent().siblings(".picking_name").children("input").val());
            obj.parent().siblings(".picking_name").children("input").val(returns.title);
        }

    }, 'json');
}
</script>