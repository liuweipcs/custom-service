<?php
use yii\helpers\Url;
use app\components\GridView;

?>
<style type="text/css">
    
    #search-form .btn-primary {
      /*  float: right;
        margin-top: -60px;
        margin-right: 396px;*/
      }
</style>
<div class="abn_detail" style="height: 450px; overflow-y: scroll; margin: 10px 0">
    <?php 
     // echo $this->render('@app/modules/aftersales/views/skuqualityanalysis/skudetail', [
     //     'model' => $model,
     //    // 'dataProvider'=>$dataProvider,
     //     'sku'=>$sku,
     // ]);

     ?>

     <?php if(!empty($model)){ ?>
     <h4 class="modal-title"></h4>
         <table class="table table-bordered">
             <thead>
             <tr>
                 <th>订单号</th>
                 <th>发货日期</th>
                 <th>实际损失金额RMB</th>
                 <th>备注</th>
             </tr>
             </thead>
         <?php foreach($model as $value){ ?>
             <tr class="table-module-b1">
                 <td><?=$value->order_id?></td>
                 <td><?=$value->shipped_date?></td>
                 <td><?=$value->loss_rmb?></td>
                 <td><?=$value->remark?></td>
             </tr>
         <?php } ?>
         </table>

     <?php }else{ ?>
         <div style="height: 200px; text-align: center">暂无数据</div>
     <?php } ?>       
</div>
                
