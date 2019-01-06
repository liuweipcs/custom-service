<?php
use app\modules\aftersales\models\AfterSalesOrder;
use yii\helpers\Url;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\systems\models\BasicConfig;
?>


<div class="popup-wrapper">
    <div class="popup-body">
           <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#home">退款单详情</a></li>
               <li><a data-toggle="tab" href="#product">产品信息</a></li>
           </ul>

            <div class="tab-content">
                <div id="home" class="tab-pane fade in active">
                    <table class="table table-striped">
                        <tr>
                           <td colspan="15">
                               <table  class="table table-striped">
                                   <tr>
                                       <th>责任归属部门</th>
                                       <th>退款原因</th>
                                       <th>备注</th>
                                   </tr>
                                    <tr>
                                   <?php if($model['department_id']){
                                       $departmentList = BasicConfig::getParentList(52);
                                       $allConfigData = BasicConfig::getAllConfigData();
                                       ?>
                                      
                                            <td><?php echo $departmentList[$model['department_id']];?></td>
                                            <td><?php echo $allConfigData[$model['reason_id']];?></td>
                                   <?php }else{?>
                                            <td>-</td>
                                            <td><?php echo AfterSalesOrder::getAfterSalsesData($data->after_sale_id,['reason_id']);?></td>
                                   <?php } ?>
                                    <td><?php echo AfterSalesOrder::getAfterSalsesData($data->after_sale_id,['remark']);?></td>
                                    
                                    </tr>
                               </table>
                           </td>
                       </tr>
                        
                        <tr>
                            <th>售后单号</th>
                            <th>退款金额</th>
                            <th>货币代码</th>
                            <th>买家留言</th>
                            <th>平台code</th>
                            <th>订单号</th>
                            <th>订单金额</th>
                            <th>退款类型</th>
                            <th>退款时间</th>
                            <th>退款状态</th>
                            <th>审核状态</th>
                            <th>创建人</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                        <?php if(!empty($data)){?>
                         <tr>
                           <td><?php echo $data->after_sale_id;?></td>
                           <td><?php echo $data->refund_amount;?></td>
                            <td><?php echo $data->currency;?></td>
                           <td><?php echo $data->message;?></td>
                            <td><?php echo $data->platform_code;?></td>
                           <td><?php echo $data->order_id;?></td>
                            <td><?php echo $data->order_amount;?></td>
                           <td><?php 
                               switch ($data->refund_type) {
                                   case '1':
                                       echo "部分退款";
                                   break;
                                   case "2":
                                       echo "全部退款";
                                   break;
                               }
                            ?></td>
                            <td><?php echo $data->refund_time;?></td>
                           <td>

                           <?php 
                               switch($data->refund_status){
                                   case "1":
                                       echo "待付款";
                                   break;
                                   case "2":
                                       echo '退款中';
                                   break;
                                   case "3":
                                       echo "退款完成";
                                   break;
                                   case "-1":
                                       echo "退款失败";
                                    break;
                               }
                           ?>

                           </td>

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
                        <?php }else{?>
                          <tr>
                           <td colspan="11">暂无改售后单的信息</td>
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
                        </tr>

                        <?php if(isset($order_info->product) && !empty($order_info->product)){?>

                            <?php foreach($order_info->product as $value){?>
                                <tr>

                                    <td><?php echo $value->sku;?></td>
                                    <td><?php echo $value->title;?></td>
                                    <td><?php echo $value->quantity;?></td>
                                    <td><?php echo $value->linelist_cn_name;?></td>

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