<div class="popup-wrapper">
    <form class="form-horizontal" role="form" id="refund_form">
        <script src="<?php echo yii\helpers\Url::base(true);?>/js/star-rating.js"></script>
        <script src="<?php echo yii\helpers\Url::base(true);?>/js/star-rating.min.js"></script>
        <link href="<?php echo yii\helpers\Url::base(true);?>/css/star-rating.css" rel="stylesheet">
        <link href="<?php echo yii\helpers\Url::base(true);?>/css/star-rating.min.css" rel="stylesheet">
        <div class="panel panel-default" style="width: 99%;margin: 0 auto;">
            <div class="panel-heading">
                <h3>请留总体印象分：</h3>
                <div class="container">
                    <input id="input-21a" value="0" type="number" name="score" class="rating" min=0 max=5 step=1 data-size="xl" >
                </div>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <textarea class="form-control" rows="3" placeholder="评价内容" name="feedback_content"></textarea>
                </div>
                <p>
                    <input type="hidden" value="add" name="save"/>
                    <input type="hidden" value="<?php echo $model->platform_order_id;?>" name="order_id"/>
                    <input type="hidden" value="<?php echo $model->account_id;?>" name="account_id"/>
                    <button type="button" class="btn btn-primary btn-lg ajax-submit">评价</button>
                </p>
            </div>
        </div>
    </form>
</div>
<script>
    jQuery(document).ready(function () {
        $(".rating-kv").rating();
    });
</script>
