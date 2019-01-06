<div class="popup-wrapper">
    <div class="popup-body">
           <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#home">基本信息</a></li>
                <li><a data-toggle="tab" href="#menu1">产品信息</a></li>
                <li><a data-toggle="tab" href="#menu2">交易信息</a></li>
                <li><a data-toggle="tab" href="#menu3">包裹信息</a></li>
                <li><a data-toggle="tab" href="#menu4">利润信息</a></li>
                <li><a data-toggle="tab" href="#menu5">仓储物流</a></li>
                <li><a data-toggle="tab" href="#menu6">纠纷详情</a></li>
                <li><a data-toggle="tab" href="#menu7">协商方案</a></li>
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
                            <td><?php echo !empty($info['profit']['amt']) ? $info['profit']['amt'] : 0;?></td>
                            <td><?php echo !empty($info['profit']['finalvaluefee']) ? $info['profit']['finalvaluefee'] : 0;?></td>
                            <td><?php echo !empty($info['profit']['fee_amt']) ? $info['profit']['fee_amt'] : 0 ;?></td>
                            <td><?php echo !empty($info['profit']['refund_amt']) ? $info['profit']['refund_amt'] : 0 ;?></td>
                            <td><?php echo !empty($info['profit']['back_amt']) ? $info['profit']['back_amt'] : 0;?></td>
                            <td><?php echo !empty($info['profit']['product_cost']) ? $info['profit']['product_cost'] : 0;?></td>
                            <td><?php echo !empty($info['profit']['ship_cost']) ? $info['profit']['ship_cost'] : 0;?></td>
                            <td><?php echo !empty($info['profit']['final_profit']) ? $info['profit']['final_profit'] : 0;?></td>
                            <td><?php echo !empty($info['profit']['rate']) ? $info['profit']['rate'] : 0;?></td>
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
                            <td><?php echo $info['wareh_logistics']['logistics']['ship_name'];?></td>
                            </tr>
                        <?php }else{?>
                            <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                        <?php }?>
                        </tbody>
                    </table>
                </div>
                
               <div id="menu6" class="tab-pane fade">
                   <table class="table">
                       <tbody>
                       <tr class="active">
                           <td>平台纠纷ID：</td>
                           <td><?php echo $dispute_detail['platform_dispute_id'];?></td>
                           <td>平台主订单号：</td>
                           <td><?php echo $dispute_detail['platform_parent_order_id'];?></td>
                       </tr>

                       <tr class="warning">
                           <td>平台子订单号ID：</td>
                           <td><?php echo $dispute_detail['platform_order_id'];?></td>
                           <td>买家memberID：</td>
                           <td><?php echo $dispute_detail['buyer_aliid'];?></td>
                       </tr>
                       <tr class="danger">
                           <td>纠纷原因 中文：</td>
                           <td><?php echo !empty($listInfo->reason_chinese)?$listInfo->reason_chinese:'';?></td>
                           <td>纠纷原因 英文：</td>
                           <td><?php echo !empty($listInfo->reason_english)?$listInfo->reason_english:'';?></td>
                       </tr>
                       <tr class="warning">
                           <td>纠纷状态：</td>
                           <td><?php echo $dispute_detail['issue_status'];?></td>
                           <td>退款上限：</td>
                           <td><?php echo $dispute_detail['refund_money_max'];?></td>
                       </tr>

                       <tr class="active">
                           <td>退款上限本币：</td>
                           <td><?php echo $dispute_detail['refund_money_max_local'];?></td>
                           <td>产品名称：</td>
                           <td><?php echo $dispute_detail['product_name'];?></td>
                       </tr>

                       <tr class="danger">
                           <td>产品价格：</td>
                           <td><?php echo $dispute_detail['product_price'];?></td>
                           <td>买家退货物流公司：</td>
                           <td><?php echo $dispute_detail['buyer_return_logistics_company'];?></td>
                       </tr>

                       <tr class="active">
                           <td>买家退货单号：</td>
                           <td><?php echo $dispute_detail['buyer_return_no'];?></td>
                           <td>退货物流订单LP单号：</td>
                           <td><?php echo $dispute_detail['buyer_return_logistics_lp_no'];?></td>
                       </tr>
                       <tr class="success">
                           <td>创建时间：</td>
                           <td><?php echo $dispute_detail['gmt_create'];?></td>
                       </tr>
                       </tbody>
                       </tr>
                       </tbody>
                   </table>
                   <div class="panel panel-info" style="width: 45%;float: left">
                       <div class="panel-heading">
                           <h3 class="panel-title">卖家提交纠纷仲裁</h3>
                       </div>
                       <div class="panel-body">
                           <div class="form-group">
                               <label for="name">纠纷原因</label>
                               <select class="form-control" style="width: 200px" id="reason">
                                   <option value="noMatchDesc">货不对版</option>
                                   <option value="notReceived">未收到货</option>
                               </select>
                           </div>
                           <form role="form" style="height: 165px">
                               <div class="form-group">
                                   <label for="name"></label>
                                   <textarea class="form-control" rows="6" placeholder="卖家提交仲裁描述" id="arbitration_description"></textarea>
                               </div>
                           </form>
                           <div class="btn-group" style="margin-top:30px;float: left;margin-left: 10px">
                               <button type="button"  class="btn btn-info" id="arbitration">提交仲裁</button>
                           </div>
                       </div>

                   </div>
                   <div class="panel panel-info" style="width: 45%;float: left;margin-left:10%">
                       <div class="panel-heading">
                           <h3 class="panel-title">其它操作</h3>
                       </div>
                       <div class="panel-body">
                           <div class="btn-group" style="margin-top:30px;float: left;margin-left: 10px">
                               <button type="button"  class="btn btn-info" id="confirm_receipt">卖家确认收货 </button>
                           </div>
                           <div class="btn-group" style="margin-top:30px;float: left;margin-left: 10px">
                               <button type="button" class="btn btn-info" id="return_request">卖家放弃退货申请</button>
                           </div>
                       </div>

                   </div>
                </div>
                
                
                 <div id="menu7" class="tab-pane fade">
                     <div class="panel panel-primary" style="margin-top: 30px">
                         <div class="panel-body">
                             <div class="well" style="width: 400px;margin: 0 auto" >
                                 <p class="text-info">订单号：<?php echo !empty($listInfo->platform_order_id)?$listInfo->platform_order_id:'';?></p>
                                 <p class="text-info">纠纷原因：<?php echo !empty($listInfo->reason_chinese)?$listInfo->reason_chinese:'';?></p>
                                 <p class="text-info">纠纷状态：<?php echo !empty($listInfo->issue_status)?$listInfo->issue_status:'';?></p>
                             </div>
                         </div>
                     </div>
                     <div class="panel panel-primary" style="margin-top: 30px;width: 45%;float: left">
                     <?php
                     if(!empty($dispute_solution)) {
                         foreach ($dispute_solution as $value) {
                             if ($value['scheme_type'] == 'buyer' || $value['scheme_type'] == 'platform') {
                                 ?>
                                 <div class="panel panel-success" style="width: 100%;float: left;">
                                     <div class="panel-heading">
                                         <h3 class="panel-title"><?php if($value['scheme_type'] == 'buyer'){?>买家方案<?php }else{?>平台建议方案<?php }?></h3>
                                     </div>
                                     <div class="panel-body">
                                         <p class="text-primary">
                                             买家方案<?php if ($value['is_default']) { ?>(默认方案)<?php } ?></p>
                                         <p class="text-primary">方案类型：<?php echo $value['solution_type']; ?></p>
                                         <?php if((int)$value['refund_money_post']){?>
                                         <p class="text-primary">退款金额：<?php echo $value['refund_money_post']; ?>(<?php echo $value['refund_money']; ?>)</p>
                                            <?php }?>
                                         <p class="text-primary">方案状态：<?php echo $value['status']; ?></p>
                                         <div class="well"><p class="text-primary">备注：<?php echo $value['content']; ?></p></div>
                                     </div>
                                 </div>
                                 <?php
                             }
                         }
                     }
                       ?>
                     </div>
                     <div class="panel panel-primary" style="margin-left: 30px;margin-top: 30px;width: 45%;float: left">
                     <?php
                         if(!empty($dispute_solution)) {
                             foreach ($dispute_solution as $value) {
                                 if ($value['scheme_type'] == 'seller') {
                                     ?>
                                     <div class="panel panel-success" style="width: 100%;float: left;">
                                         <div class="panel-heading">
                                             <h3 class="panel-title">我的方案</h3>
                                         </div>
                                         <div class="panel-body">
                                             <p class="text-primary">
                                                 买家方案<?php if ($value['is_default']) { ?>(默认方案)<?php } ?></p>
                                             <p class="text-primary">方案类型：<?php echo $value['solution_type']; ?></p>
                                             <?php if((int)$value['refund_money_post']){?>
                                             <p class="text-primary">退款金额：<?php echo $value['refund_money_post']; ?>(<?php echo $value['refund_money']; ?>)</p>
                                             <?php }?>
                                             <p class="text-primary">方案状态：<?php echo $value['status']; ?></p>
                                             <div class="well"><p class="text-primary">备注：<?php echo $value['content']; ?></p></div>

                                         </div>
                                     </div>
                                     <?php
                                 }
                             }
                         }
                        ?>
                     </div>
                     <div class="panel panel-primary" style="margin-top: 30px;width: 100%;float: left">
                         <div class="panel-heading">
                             <h3 class="panel-title">订单留言</h3>
                         </div>
                         <div class="panel panel-success" style="margin-top: 10px;width: 45%;float: left;">
                             <div class="panel-body">
                                 <form role="form" style="height: 165px">
                                     <div class="form-group">
                                         <label for="name"></label>
                                         <textarea class="form-control" rows="6" placeholder="信息内容" id="content"></textarea>
                                     </div>
                                 </form>
                             </div>
                             <div class="btn-group" style="float: left;margin-left: 10px">
                                 <button type="button"  class="btn btn-info" id="addexpression">发送</button>
                             </div>
                         </div>
                         <div class="panel panel-success" style="margin-top: 50px;width: 45%;float: left;margin-left: 5%">
                             <div class="panel-body">
                                 <?php
                                 if(!empty($expressionList)){
                                 foreach ($expressionList as $exvalue){
                                 ?>
                                 <a href="#this" class="expression_url" data-value="<?php echo $exvalue['label'];?>"><img src="<?php echo $exvalue['expression_url'];?>" width="24" height="24"/></a>
                                 <?php
                                    }
                                 }
                                 ?>
                             </div>
                         </div>

                     </div>
                </div>

            </div>
    </div>
</div>

<script>
    $(function(){
        $("#return_request").click(function(){
            $.post("/mails/aliexpressdispute/waiverreturns",
                {
                    dispute_id:<?php echo !empty($listInfo->platform_dispute_id)?$listInfo->platform_dispute_id:'';?>,
                    account_id:<?php echo !empty($listInfo->account_id )?$listInfo->account_id:'';?>,
                },
                function(result){
                    var obj = eval('('+result+')');
                    alert(obj.message);
            });
        });
        $("#confirm_receipt").click(function(){
            $.post("/mails/aliexpressdispute/goodsreceipt",
                {
                    dispute_id:<?php echo !empty($listInfo->platform_dispute_id )?$listInfo->platform_dispute_id:'';?>,
                    account_id:<?php echo !empty($listInfo->account_id )?$listInfo->account_id:'';?>,
                },
                function(result){
                    var obj = eval('('+result+')');
                    alert(obj.message);
            });
        });
        $(".expression_url").click(function () {
            var reply_content = $('#content').val();
            var expression_url = $(this).attr('data-value');
            $('#content').val(reply_content+expression_url);

        });
        $("#arbitration").click(function(){
            $.post("/mails/aliexpressdispute/arbitration",
                {
                    dispute_id:<?php echo !empty($listInfo->platform_dispute_id )?$listInfo->platform_dispute_id:'';?>,
                    account_id:<?php echo !empty($listInfo->account_id )?$listInfo->account_id:'';?>,
                    description:$('#arbitration_description').val(),
                    reason:$('#reason').val()
                },
                function(result){
                    var obj = eval('('+result+')');
                    alert(obj.message);
                });
        });
        $("#addexpression").click(function(){
            $.post("/mails/aliexpressdispute/replymsg",
                {
                    order_id:<?php echo !empty($listInfo->platform_order_id )?$listInfo->platform_order_id:'';?>,
                    account_id:<?php echo !empty($listInfo->account_id )?$listInfo->account_id:'';?>,
                    content:$('#content').val()
                },
                function(result){
                    var obj = eval('('+result+')');
                    alert(obj.message);
            });
        });
    });
</script>