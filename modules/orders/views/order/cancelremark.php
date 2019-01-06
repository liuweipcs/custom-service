<?php
use yii\helpers\Url;
?>
<div class="popup-wrapper">
<form action="#" method="post" role="form" class="form-horizontal">
    <div class="popup-body">
    <div class="row">
    <div class="col-sm-5">
        <div class="panel panel-default">
            <!-- <div class="panel-heading">
                <h3 class="panel-title">确认信息</h3>
            </div> -->
            <div class="row">
            	<div class="col-sm-9">
            		<input type="hidden" name="order_id" value="<?=$info['orderId']?>">
                    <input type="hidden" name="platformCode" value="<?=$info['platformCode']?>">
            	</div>
            </div>
            <div class="panel-body">

            	<label for="ship_name" class="col-sm-3 control-label required">永久作废备注：<span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <textarea class="form-control" name="remark" row='3' placeholder="请输入备注" style="height: 200px;"></textarea>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<div class="popup-footer">
    <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
</div>
</form>
</div>