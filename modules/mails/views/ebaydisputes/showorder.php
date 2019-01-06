<style>
    li{list-style: none;}
    .hear-title,.search-box ul{overflow: hidden;}
    .hear-title p:nth-child(1) span:nth-child(1),.hear-title p:nth-child(2) span:nth-child(1){display: inline-block;width: 30%}
    .item-list li{border-bottom: 1px solid #ddd;padding: 5px 10px}
    .item-list li span{display: inline-block;width: 25%}
    .search-box ul li{float: left;padding:0 10px 10px 0}
    .search-box textarea{display: block;margin-top: 10px;width: 100%}
    .info-box .det-info{width: 100%;height: 200px;border: 2px solid #ddd;}
    /*.well span{padding: 6%}*/
    .well p{text-align:left}
    .mail_item_list
    {
        margin-right: 30px;
        display: inline-block;
    }
    .mail_template_unity
    {
        cursor:pointer;
    }
</style>


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
                <li><a data-toggle="tab" href="#menu7">处理未收到货纠纷</a></li>
                <li><a data-toggle="tab" href="#menu8">处理描述不符纠纷</a></li>
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
                            <td><?php echo $info['wareh_logistics']['logistics']['ship_name'];?></td>
                            </tr>
                        <?php }else{?>
                            <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                        <?php }?>
                        </tbody>
                    </table>
                </div>
                
                
          <div id="menu6" class="tab-pane fade">
                    <table class="table table-striped" border="1">
                                              
                        <tbody id="negotiationdetail">
              <?php 
              foreach($dispute_negotiate as $key=>$value){
                  if($value['solutionOwner']=='buyer'){
               ?>       
                      <tr><td align='left' width='50%'>
                      <?php 

                            echo 'Status: '.$value['status'];
                            echo '<br>';
                            echo '<br>';                      
                            echo 'Role: '.$value['solutionOwner'].'<br>';
                            echo 'Expected: '.$value['solutionType'].'<br>';
                            echo 'Amount: '.$value['amount'].' '.$value['currencyCode'].'<br>';
                            echo 'Content: '.$value['content'].'<br>';
    
                            $y = substr($value['gmtCreate'],0,4);
                            $m = substr($value['gmtCreate'],4,2);
                            $d = substr($value['gmtCreate'],6,2);
                            $h = substr($value['gmtCreate'],8,2);
                            $i = substr($value['gmtCreate'],10,2);
                            echo 'Create at: '.$y.'-'.$m.'-'.$d.' '.$h.':'.$i.'<br>';;
                            echo 'Download at: '.$value['createDate'];

                    
                      ?>
                                        
                      </td><td  width='50%'></td></tr>
               <?php
                  }elseif($value['solutionOwner']=='seller'){
                ?>      
                       <tr><td  width='50%'></td><td align='left' width='50%'>
                       <?php 

                            echo 'Status: '.$value['status'];
                            echo '<br>';
                            echo '<br>';
                            echo 'Role: '.$value['solutionOwner'].'<br>';
                            echo 'Offered: '.$value['solutionType'].'<br>';
                            echo 'Amount: '.$value['amount'].' '.$value['currencyCode'].'<br>';
                            echo 'Content: '.$value['content'].'<br>';
    
                            $y = substr($value['gmtCreate'],0,4);
                            $m = substr($value['gmtCreate'],4,2);
                            $d = substr($value['gmtCreate'],6,2);
                            $h = substr($value['gmtCreate'],8,2);
                            $i = substr($value['gmtCreate'],10,2);
                            echo 'Create at: '.$y.'-'.$m.'-'.$d.' '.$h.':'.$i.'<br>';;
                            echo 'Download at: '.$value['createDate'];

                       
                       
                       
                       ?></td></tr>
                <?php    
                  }elseif($value['solutionOwner']=='platform'){
                ?>      
                    <tr><td colspan='2' align='center'>
                    
                    
                    <?php 
                    
                    echo 'Status: '.$value['status'].'<br>';
                    echo '<br>';
                    echo '<br>';
                    echo 'Role: '.$value['solutionOwner'].'<br>';
                    echo 'Solution Type: '.$value['solutionType'].'<br>';
                    echo 'Amount: '.$value['amount'].' '.$value['currencyCode'].'<br>';
                    $y = substr($value['gmtCreate'],0,4);
                    $m = substr($value['gmtCreate'],4,2);
                    $d = substr($value['gmtCreate'],6,2);
                    $h = substr($value['gmtCreate'],8,2);
                    $i = substr($value['gmtCreate'],10,2);
                    echo 'Create at: '.$y.'-'.$m.'-'.$d.' '.$h.':'.$i.'<br>';;
                    $r_y = substr($value['reachedTime'],0,4);
                    $r_m = substr($value['reachedTime'],4,2);
                    $r_d = substr($value['reachedTime'],6,2);
                    $r_h = substr($value['reachedTime'],8,2);
                    $r_i = substr($value['reachedTime'],10,2);
                    echo 'Reached at '.$r_y.'-'.$r_m.'-'.$r_d.' '.$r_h.':'.$r_i.'<br>';
                    echo 'Download at: '.$value['createDate'];
                    
                    ?>
                   
                    </td></tr>
              <?php  
                  }else{}
              }
              
              
              ?>
                  
                        </tbody>
                    </table>
            </div>
                
                
           <div id="menu7" class="tab-pane fade">
               <div>

                         
                            <input type="radio" checked="checked" name="operation" value="1" id="aaa"/> <label for="aaa">发送留言</label> <br />
                            
                            <input type="radio" name="operation" value="2" id="rrr"/> <label for="rrr"> 全额退款</label> <br />
                            
                            <input type="radio" name="operation" value="3" id="ddd"/>
                            <a data-toggle="collapse" data-parent="#accordion" href="#shipping_info">提供发货信息 </a><br />
                            
                            <div id="shipping_info" class="panel-collapse collapse">
    			                <div class="panel-body">				                    
        				     <input type="radio" checked="checked" name="shipping_choice" value="1" /> 
                           <a data-toggle="collapse" data-parent="#accordion" href="#no_tracking"> 无跟踪号</a> <br />
     				            
                        		<div id="no_tracking" class="panel-collapse collapse">
                        			<div class="panel-body">
                            					<input type="text" class="form-control" id="ship_company_name_no_track"  placeholder="运输商名称">
                            					<input type="text" class="form-control" id="ship_date"  placeholder="发货日期 exam: 2017-06-14">
                            					
                        			</div>
                        		</div>
      				            
                              <input type="radio" name="shipping_choice" value="2" />
                              <a data-toggle="collapse" data-parent="#accordion" href="#with_tracking"> 有跟踪号</a> <br />
                              
                                    <div id="with_tracking" class="panel-collapse collapse">
                        			<div class="panel-body">
                            					<input type="text" class="form-control" id="ship_company_name_with_track"  placeholder="运输商名称">
                            					<input type="text" class="form-control" id="tracking_num"  placeholder="exam: RC123456789HK">
                            					
                        			</div>
                        		</div>
                              
                              
    			                </div>
		                         </div>
                   
                            <input type="radio" name="operation" value="4" id="bbb"/>  <label for="bbb">升级</label> <br />
                            <span>
                            <textarea id="reply_message" class="form-control" rows="3" placeholder="发送留言内容" style="height: 100px"></textarea>
                                                                 
                            </span>

               </div>

               <p>
                 <button type="button" class="btn btn-default" onclick="sendmsg()">提交</button>
                 <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
               </p>
               

<div class="panel-group" id="accordion">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 class="panel-title">
				<a data-toggle="collapse" data-parent="#accordion" 
				   href="#collapseOne">
					点击我进行展开，再次点击我进行折叠。第 1 部分
				</a>
			</h4>
		</div>
		<div id="collapseOne" class="panel-collapse collapse in">
			<div class="panel-body">
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson 
				cred nesciunt sapiente ea proident. Ad vegan excepteur butcher 
				vice lomo.
			</div>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 class="panel-title">
				<a data-toggle="collapse" data-parent="#accordion" 
				   href="#collapseTwo">
					点击我进行展开，再次点击我进行折叠。第 2 部分
				</a>
			</h4>
		</div>
		<div id="collapseTwo" class="panel-collapse collapse">
			<div class="panel-body">
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson 
				cred nesciunt sapiente ea proident. Ad vegan excepteur butcher 
				vice lomo.
			</div>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 class="panel-title">
				<a data-toggle="collapse" data-parent="#accordion" 
				   href="#collapseThree">
					点击我进行展开，再次点击我进行折叠。第 3 部分
				</a>
			</h4>
		</div>
		<div id="collapseThree" class="panel-collapse collapse">
			<div class="panel-body">
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson 
				cred nesciunt sapiente ea proident. Ad vegan excepteur butcher 
				vice lomo.
			</div>
		</div>
	</div>
</div>
               
 </div>
                
          <div id="menu8" class="tab-pane fade">
               <div>

                         
                            <input type="radio" checked="checked" name="snadsendmsg" value="1" id="snadmsg"/> <label for="snadmsg">发送留言</label> <br />
                            
                            <input type="radio" name="snadsendmsg" value="2" id="snadrefund"/> <label for="snadrefund"> 全额退款</label> <br />
                            
                            <input type="radio" name="snadsendmsg" value="5" id="snadpartialrefund"/>                            
                            
                            <a data-toggle="collapse" data-parent="#accordion" href="#partial_refund"> 部分退款</a> <br />
                            
                               
                           <div id="partial_refund" class="panel-collapse collapse">
                        	   <div class="panel-body">
                            		<input type="text" class="form-control" id="partial_refund_amount"  placeholder="部分退款金额(USD)">                            					                            					
                        	   </div>
                           </div>                                                                                 
                   
                            <input type="radio" name="snadsendmsg" value="4" id="snadescalate"/>  <label for="snadescalate">升级</label> <br />
                            <span>
                            <textarea id="snad_reply_message" class="form-control" rows="3" placeholder="发送留言内容" style="height: 100px"></textarea>
                                                                 
                            </span>

               </div>

               <p>
                 <button type="button" class="btn btn-default" onclick="sendsnadmsg()">提交</button>
                 <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
               </p>
                              
        </div>
  
               
        </div>
    </div>
</div>


<script>
function sendsnadmsg(){
    var sandoperation  = "";
    var partial_refund = "";
    var msgcontent = "";
    var rSendMsg = document.all.snadsendmsg;
    for(i=0;i<rSendMsg.length;i++)
    {
         if(rSendMsg[i].checked)
        	 sandoperation=rSendMsg[i].value;
    }


    if(sandoperation==1){
   	  
      	 if($('#snad_reply_message').val().length<2){
          	 alert('仅发送订单留言时，此处留言长度要大于2个字符');
          	 return;
          	 }
     }

    msgcontent = $('#snad_reply_message').val();

    if(sandoperation==5){
        
    partial_refund = $('#partial_refund_amount').val();
    
    }else{
        
   	 $('#partial_refund_amount').val('');
   	 
        }
    
    $.post("/mails/ebaydisputes/savesnadhandle", {operation:sandoperation, msgcontent:msgcontent, partialrefund:partial_refund}, function(data,status){

        alert(data);
        return;

   	 if(status=='success'){
   	   	 alert('回复成功');
//   	  	 document.getElementById("reply_content").value = "";
      	  $('#partial_refund_amount').val('');
          $('#snad_reply_message').val('');      	  
   	   	 }else{   	   	   	 
      	 alert('回复失败');    	      
   	   	   	 }
 
        });  

}
</script>

<script>
function sendmsg()
{
           var operation="";
           var rPort = document.all.operation;
           for(i=0;i<rPort.length;i++)
           {
                if(rPort[i].checked)
                	operation=rPort[i].value;
           }

	       
           if(operation==1){
       	  
        	 if($('#reply_message').val().length<2){
            	 alert('仅发送订单留言时，此处留言长度要大于2个字符');
            	 return;
            	 }
            }
         
	       msgcontent=$('#reply_message').val();

	       var shipping = '';
	       var ship_company_name_no_track = '';
	       var ship_company_name_with_track = '';
	       var ship_date = '';
	       var tracking_num = '';
//	       alert(operation);
	       var rshipping = document.all.shipping_choice;

	       if(operation==3){
//	    	   alert('gohereher');

	    	   for(i=0;i<rshipping.length;i++){
	    	        
		    	   if(rshipping[i].checked)
		               	shipping=rshipping[i].value;
		    	   }

//	    	   alert(shipping);

	    	   if(shipping==1){
	    		   var ship_company_name_no_track = $('#ship_company_name_no_track').val();
	    		   var ship_date         =$('#ship_date').val();
	    		   
	    		   if(ship_company_name_no_track.length<4){
		    		   
	    			   alert('运输公司必填');
		              	 return;
		    		   }
	    		   if(ship_date.length<4){
		    		   
	    			   alert('运输日期必填');
		              	 return;
		    		   }  		    	  
	    	   }

	    	   if(shipping==2){

	    		  var ship_company_name_with_track = $('#ship_company_name_with_track').val();
	    		  var tracking_num                 = $('#tracking_num').val();

		    	   }

	    	   
		       }
	     	       

	            $.post("/mails/ebaydisputes/savehandle", {operation:operation, msgcontent:msgcontent, ship_company_name_no_track:ship_company_name_no_track, ship_date:ship_date, ship_company_name_with_track:ship_company_name_with_track, tracking_num:tracking_num, shipping:shipping}, function(data,status){
	        
	           	 if(status=='success'){
	           	   	 alert('回复成功');
	           	  	 document.getElementById("reply_content").value = "";
	           	   	 }
	         
	                });  

}

</script>
		    	   
	

		    	   
	
	
	
	
		    	   
