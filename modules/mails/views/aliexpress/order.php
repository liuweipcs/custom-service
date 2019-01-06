<div class="popup-wrapper">
    <div class="popup-body">
           <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#home">基本信息</a></li>
                <li><a data-toggle="tab" href="#menu1">产品信息</a></li>
                <li><a data-toggle="tab" href="#menu2">交易信息</a></li>
                <li><a data-toggle="tab" href="#menu3">包裹信息</a></li>
                <li><a data-toggle="tab" href="#menu4">利润信息</a></li>
                <li><a data-toggle="tab" href="#menu5">仓储物流</a></li>
           </ul>

            <div class="tab-content">
                <div id="home" class="tab-pane fade in active">
                    <table class="table">
                        </thead>
                        <tbody id="basic_info">
                        <?php if(!empty($info['info'])){?>
                            <tr><td>订单号</td><td><?php echo $info['info']['order_id'];?></td><td>销售平台</td><td><?php echo $info['info']['platform_code'];?></td></tr>
                            <tr><td>平台订单号</td><td><?php echo $info['info']['platform_order_id'];?></td><td>买家ID</td><td><?php echo $info['info']['buyer_id'];?></td></tr>
                            <tr><td>下单时间</td><td><?php echo $info['info']['created_time'];?></td><td>付款时间</td><td><?php echo $info['info']['paytime'];?></td></tr>
                            <tr><td>运费</td><td><?php echo $info['info']['ship_cost'] + $info['info']['currency'];?></td><td>总费用</td><td><?php echo $info['info']['total_price'];?></td></tr>
                            <tr><td>送货地址</td><td colspan="3" >
                                        <?php echo $info['info']['ship_name'];?>
                                        (tel:<?php echo $info['info']['ship_phone'];?>)<br>
                                        <?php echo $info['info']['ship_street1'] + $info['info']['ship_street2'] + $info['info']['ship_city_name'];?>,
                                        <?php echo $info['info']['ship_stateorprovince'];?>,
                                        <?php echo $info['info']['ship_zip'];?>,<br/>
                                        <?php echo $info['info']['ship_country_name'];?>
                                    </td>
                            </tr>
                        <?php }else{?>
                            <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                        <?php }?>
                        </tbody>
                    </table>
                </div>
                <div id="menu1" class="tab-pane fade">
                    <table class="table table-striped">
                        <thead>
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
                        </tr>
                        </thead>
                        <tbody id="product">
                        <?php if(!empty($info['product'])){?>
                            <?php foreach ($info['product'] as $value){?>
                                <td style="width: 50%"><?php echo $value['title'];?></td>
                                <td><?php echo $value['sku'];?></td>
                                <td><?php echo $value['quantity'];?></td>
                                <td><?php echo $value['sale_price'];?></td>
                                <td><?php echo $value['ship_price'];?></td>
                                <td><?php echo $value['qs'];?></td>
                                <td><?php echo $value['stock'];?></td>
                                <td><?php echo $value['on_way_stock'];?></td>
                                <td><?php echo $value['total_price'];?></td>
                                </tr>
                            <?php }?>
                        <?php }else{?>
                            <tr><td colspan="6" align="center">没有找到信息！</td></tr>
                        <?php }?>
                        </tbody>
                    </table>
                </div>
                <div id="menu2" class="tab-pane fade">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>交易号</th>
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
                <div id="menu3" class="tab-pane fade">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>包裹号</th>
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
                <div id="menu4" class="tab-pane fade">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>收款</th>
                            <th>手续费</th>
                            <th>成交费</th>
                            <th>退款</th>
                            <th>退款返还</th>
                            <th>产品成本</th>
                            <th>运费成本</th>
                            <th>利润(RMB)</th>
                            <th>汇率</th>
                        </tr>
                        </thead>
                        <tbody id="profit_id">
                        <?php if(!empty($info['profit'])){?>
                            <tr>
                            <td><?php echo $info['profit']['amt'];?></td>
                            <td><?php echo $info['profit']['finalvaluefee'];?></td>
                            <td><?php echo $info['profit']['fee_amt'];?></td>
                            <td><?php echo $info['profit']['refund_amt'];?></td>
                            <td><?php echo $info['profit']['back_amt'];?></td>
                            <td><?php echo $info['profit']['product_cost'];?></td>
                            <td><?php echo $info['profit']['ship_cost'];?></td>
                            <td><?php echo $info['profit']['final_profit'];?></td>
                            <td><?php echo $info['profit']['rate'];?></td>
                            </tr>
                        <?php }else{?>
                            <tr><td colspan="9" align="center">没有找到信息！</td></tr>
                        <?php }?>
                        </tbody>
                    </table>
                </div>
                <div id="menu5" class="tab-pane fade">
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