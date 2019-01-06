<?php
use yii\helpers\Url;
?>
<div class="popup-wrapper">
<form action="<?php echo Url::toRoute(['/orders/order/handlecancelorder',
        'platform' => $info['platform_order_id'],
        'order_id' => $info['order_id'],
        'buyerPaid' => $info['buyerPaid'],
        'payTime' => $info['payTime'],
        'account_id' => $info['account_id']
        ]);?>" method="post" role="form" class="form-horizontal">
    <div class="popup-body">
    <div class="row">
    <div class="col-sm-5">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">确认信息</h3>
            </div>
            <div class="row">
            	<div class="col-sm-9">
            		 <table id="issue-product" class="table">
            		 	<tr>
            		 		<td>系统订单号</td><td><input type="text" class="form-control" name="order_id" value="<?=$info['order_id']?>" disabled></td>
            		 	</tr>
            		 	<tr>
            		 		<td>平台订单号</td><td><input type="text" class="form-control" name="platform_order_id" value="<?=$info['platform_order_id']?>" disabled></td>
            		 	</tr>
            		 	<tr>
            		 		<td>是否付款</td><td><?php if($info['buyerPaid'] == true):?><input type="radio" name="payTime" value="<?=$info['payTime']?>" checked>是<?php else:?><input type="radio" name="payTime" value="" checked="">否<?php endif;?></td>
            		 	</tr>

            		 </table>
            	</div>
            </div>
            <div class="panel-body">

            	<label for="ship_name" class="col-sm-3 control-label required">取消原因：<span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <select class="form-control" name="cancel_reason">
                        <option value="1" selected>BUYER_ASKED_CANCEL</option>
                        <option value="2">ADDRESS_ISSUES</option>
                        <option value="0">OUT_OF_STOCK_OR_CANNOT_FULFILL</option>
                    </select>
                </div>                                    
            </div>
        </div>
    </div>
    </div>
</div>
<div class="popup-footer">
    <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
    <button class="btn btn-default close-button" type="button"><?php echo Yii::t('system', 'Close');?></button>
</div>
</form>
</div>