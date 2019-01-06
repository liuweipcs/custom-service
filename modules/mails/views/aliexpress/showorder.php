<div class="popup-wrapper">
    <div class="popup-body">
           <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#home">纠纷信息</a></li>
                <li><a data-toggle="tab" href="#menu1">纠纷谈判信息</a></li>
           </ul>

            <div class="tab-content">
               <div id="home" class="tab-pane fade in active">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>店铺名称</th>
                            <th>账号编号</th>
                            <th>纠纷ID</th>
                            <th>纠纷金额</th>
                            <th>纠纷币种</th>
                            <th>纠纷原因</th>
                            <th>纠纷状态</th>
                            <th>纠纷创建时间</th>
                            <th>产品标题</th>
                        </tr>
                        </thead>
                        
                        <tbody id="disputedetail">
              
                        <?php if(!empty($dispute_detail)){?>

                            <tr>
                            <td><?php echo $dispute_detail['store_name'];?></td>
                            <td><?php echo $dispute_detail['accountid'];?></td>
                            <td><?php echo $dispute_detail['dispute_id'];?></td>
                            <td><?php echo $dispute_detail['amount'];?></td>
                            <td><?php echo $dispute_detail['currencyCode'];?></td>
                            <td><?php echo $dispute_detail['issueReason'];?></td>
                            <td><?php echo $dispute_detail['issueStatus'];?></td>                            
                            <td>
                            <?php 
                                                        
                            $d_y = substr($dispute_detail['gmtCreate'],0,4);
                            $d_m = substr($dispute_detail['gmtCreate'],4,2);
                            $d_d = substr($dispute_detail['gmtCreate'],6,2);
                            $d_h = substr($dispute_detail['gmtCreate'],8,2);
                            $d_i = substr($dispute_detail['gmtCreate'],10,2);
                            echo $d_y.'-'.$d_m.'-'.$d_d.' '.$d_h.':'.$d_i;
                           
                            ?>
                            
                            
                            </td>
                            <td><?php echo $dispute_detail['productName'];?></td>                            
                        <?php }else{?>
                            <tr><td colspan="7" align="center">无纠纷信息！</td></tr>
                        <?php }?>
                        
                    <tr>                       
                        </tbody>
                    </table>
                    <div>
                           <form id="myform" name="myform" method="post" >
                                <table>  
                                    <tr>  
                                        <td>订单留言回复:</td>  
                                        <td> <textarea id="reply_content" class="form-control" rows="3" placeholder="输入回复内容" style="height: 100px"></textarea></td>  
                                    </tr>
                                </table>  
                            </form>  
                    </div>
                                   <p>
                 <button type="button" class="btn btn-default" onclick="sendmsg()">提交</button>
                 <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
               </p>    
                </div>
                
                
                 <div id="menu1" class="tab-pane fade">
                    <table class="table table-striped" border="1">
                                              
                        <tbody id="negotiationdetail">
              <?php
              if(!empty($dispute_negotiate)) {
                  foreach ($dispute_negotiate as $key => $value) {
                      if ($value['solutionOwner'] == 'buyer') {
                          ?>
                          <tr>
                              <td align='left' width='50%'>
                                  <?php

                                  echo 'Status: ' . $value['status'];
                                  echo '<br>';
                                  echo '<br>';
                                  echo 'Role: ' . $value['solutionOwner'] . '<br>';
                                  echo 'Expected: ' . $value['solutionType'] . '<br>';
                                  echo 'Amount: ' . $value['amount'] . ' ' . $value['currencyCode'] . '<br>';
                                  echo 'Content: ' . $value['content'] . '<br>';

                                  $y = substr($value['gmtCreate'], 0, 4);
                                  $m = substr($value['gmtCreate'], 4, 2);
                                  $d = substr($value['gmtCreate'], 6, 2);
                                  $h = substr($value['gmtCreate'], 8, 2);
                                  $i = substr($value['gmtCreate'], 10, 2);
                                  echo 'Create at: ' . $y . '-' . $m . '-' . $d . ' ' . $h . ':' . $i . '<br>';;
                                  echo 'Download at: ' . $value['createDate'];


                                  ?>

                              </td>
                              <td width='50%'></td>
                          </tr>
                          <?php
                      } elseif ($value['solutionOwner'] == 'seller') {
                          ?>
                          <tr>
                              <td width='50%'></td>
                              <td align='left' width='50%'>
                                  <?php

                                  echo 'Status: ' . $value['status'];
                                  echo '<br>';
                                  echo '<br>';
                                  echo 'Role: ' . $value['solutionOwner'] . '<br>';
                                  echo 'Offered: ' . $value['solutionType'] . '<br>';
                                  echo 'Amount: ' . $value['amount'] . ' ' . $value['currencyCode'] . '<br>';
                                  echo 'Content: ' . $value['content'] . '<br>';

                                  $y = substr($value['gmtCreate'], 0, 4);
                                  $m = substr($value['gmtCreate'], 4, 2);
                                  $d = substr($value['gmtCreate'], 6, 2);
                                  $h = substr($value['gmtCreate'], 8, 2);
                                  $i = substr($value['gmtCreate'], 10, 2);
                                  echo 'Create at: ' . $y . '-' . $m . '-' . $d . ' ' . $h . ':' . $i . '<br>';;
                                  echo 'Download at: ' . $value['createDate'];


                                  ?></td>
                          </tr>
                          <?php
                      } elseif ($value['solutionOwner'] == 'platform') {
                          ?>
                          <tr>
                              <td colspan='2' align='center'>


                                  <?php

                                  echo 'Status: ' . $value['status'] . '<br>';
                                  echo '<br>';
                                  echo '<br>';
                                  echo 'Role: ' . $value['solutionOwner'] . '<br>';
                                  echo 'Solution Type: ' . $value['solutionType'] . '<br>';
                                  echo 'Amount: ' . $value['amount'] . ' ' . $value['currencyCode'] . '<br>';
                                  $y = substr($value['gmtCreate'], 0, 4);
                                  $m = substr($value['gmtCreate'], 4, 2);
                                  $d = substr($value['gmtCreate'], 6, 2);
                                  $h = substr($value['gmtCreate'], 8, 2);
                                  $i = substr($value['gmtCreate'], 10, 2);
                                  echo 'Create at: ' . $y . '-' . $m . '-' . $d . ' ' . $h . ':' . $i . '<br>';;
                                  $r_y = substr($value['reachedTime'], 0, 4);
                                  $r_m = substr($value['reachedTime'], 4, 2);
                                  $r_d = substr($value['reachedTime'], 6, 2);
                                  $r_h = substr($value['reachedTime'], 8, 2);
                                  $r_i = substr($value['reachedTime'], 10, 2);
                                  echo 'Reached at ' . $r_y . '-' . $r_m . '-' . $r_d . ' ' . $r_h . ':' . $r_i . '<br>';
                                  echo 'Download at: ' . $value['createDate'];

                                  ?>

                              </td>
                          </tr>
                          <?php
                      }
                  }
              }else{
                  echo '<tr><td align="center">无纠纷谈判信息！</td></tr>';
              }
              ?>

                        </tbody>
                    </table>
                </div>
               
            </div>
    </div>
</div>

<script type="text/javascript">
function sendmsg()
{
	var content = $('#reply_content').val();
	var orderid = <?php echo $order_id;?>;
	var accountid =<?php echo $dispute_detail['accountid']; ?>;

	if(content.length<2){
	    alert("订单留言长度要大于2");
	    return;
		}
    $.post("/mails/aliexpressdispute/replymsg", {orderid:orderid, content:content, accountid:accountid}, function(data,status){
   	 if(status=='success'){
   	   	 alert('回复成功');
   	  	 document.getElementById("reply_content").value = "";
   	   	 }
    });
}

</script>
