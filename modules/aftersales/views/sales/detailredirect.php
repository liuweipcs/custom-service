<?php
use app\modules\aftersales\models\AfterSalesOrder;
use yii\helpers\Url;
?>


<div class="popup-wrapper">
    <div class="popup-body">
           <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#home">重寄单详情</a></li>
                <li><a data-toggle="tab" href="#product">重寄产品信息</a></li>
           </ul>

            <div class="tab-content">
                <div id="home" class="tab-pane fade in active">
                    <table class="table table-striped">
                        <tr>
                           <td colspan="15">
                               <table  class="table table-striped">
                                   <tr>
                                       <th>重寄原因</th>
                                       <th>备注</th>
                                   </tr>
                                   <tr>
                                       <td><?php echo AfterSalesOrder::getAfterSalsesData($data->after_sale_id,['reason_id']);?></td>
                                       <td><?php echo AfterSalesOrder::getAfterSalsesData($data->after_sale_id,['remark']);?></td>
                                   </tr>
                               </table>
                           </td>
                       </tr>
                        <tr>
                            <th>重发单号</th>
                            <th>订单号</th>
                            <th>收货人姓名</th>
                            <th>收货人地址一</th>
                            <th>收货人地址二</th>
                            <th>收货人邮编</th>
                            <th>收货人城市</th>
                            <th>收货人州/省</th>
                            <th>收货人国家代码</th>
                            <th>收货人国家名称</th>
                            <th>收货人电话</th>
                            <th>发货方式</th>
                            <th>重寄亏损预算</th>
                            <th>备注</th>
                            <th>审核状态</th>
                            <th>创建人</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                         <?php if(!empty($data)){?>
                          <tr>

                            <td><?php echo '<a _width="100%" _height="100%" class="edit-button" href="/orders/order/orderdetails?order_id=&platform='.$data->platform_code.'&system_order_id='.$data->redirect_order_id.'" title="订单详情">'.$data->redirect_order_id.'</a>';?></td>
                           <td><?php echo $data->order_id;?></td>
                            <td><?php echo $data->ship_name;?></td>
                           <td><?php echo $data->ship_street1;?></td>
                            <td><?php echo $data->ship_street2;?></td>
                           <td><?php echo $data->ship_zip;?></td>
                            <td><?php echo $data->ship_city_name;?></td>
                           <td><?php echo $data->ship_stateorprovince;?></td>
                           <td><?php echo $data->ship_country;?></td>
                            <td><?php echo $data->ship_country_name;?></td>
                           <td><?php echo $data->ship_phone;?></td>
                            <td><?php echo $data->ship_code;?></td>
                              <td>
                                  <?php $orderModel =  new \app\modules\orders\models\Order;
                                  $cost = $orderModel->getRedirectCostByOrderId($data->platform_code,$data->order_id);
                                    if($cost && $cost->ack == true)
                                    {
                                        $cost = $cost->data;
                                        echo $cost;
                                    }
                                  ?>
                              </td>
                              <td></td>
                              <td><?php echo AfterSalesOrder::getOrderStatusList($model['status']);?></td>
                              <td><?php echo $model['create_by'];?></td>
                              <td><?php echo $model['create_time'];?></td>
                            <td>
                               <?php if ($model['status'] != AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED) { ?>
                               <div class="btn-group btn-list">
                                        <button type="button" class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation');?></button>
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <span class="caret"></span>
                                            <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List');?></span>                                            
                                        </button>
                                        <ul class="dropdown-menu" rol="menu">
                                            <li><a class="ajax-button" href="<?php echo Url::toRoute(['/aftersales/order/audit', 
                                                'after_sales_id' => $data->after_sale_id, 
                                                'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED]);?>">审核通过</a></li>
                                            <li><a class="ajax-button" href="<?php echo Url::toRoute(['/aftersales/order/audit', 
                                                'after_sales_id' => $data->after_sale_id,
                                                'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]);?>">退回修改</a></li>
                                        </ul>
                                    </div>
                              <?php }?>
                            </td>
                       </tr>
                       <tr>
                       <?php }else{?>
                        <tr>
                           <td colspan="13">暂无该重寄单的详情!</td>
                        </tr>
                       <?php }?>
                    </table>
                </div>

                <div id="product" class="tab-pane fade">
                    <table class="table table-striped">
                        
                        <tr>
                            <th>sku编号</th>
                            <th>产品标题</th>
                            <th>退货数量</th>
                            <th>产品线</th>
                            <th>备注</th>
                        </tr>

                       <?php if(!empty($detail_data)){?> 

                          <?php foreach($detail_data as $value){?>
                          <tr>
                        
                           <td><?php echo $value->sku;?></td>
                           <td><?php echo $value->product_title;?></td>
                            <td><?php echo $value->quantity;?></td>
                              <td><?php echo $value->linelist_cn_name;?></td>
                              <td><?php echo $value->remark;?></td>
                        
                          </tr>
                          <?php }?>
                       <?php }else{?>
                         <tr>
                            <td colspan="4">暂无产品信息!</td>
                         </tr>
                       <?php }?>
                    </table>
                </div>
            </div>
    </div>
</div>