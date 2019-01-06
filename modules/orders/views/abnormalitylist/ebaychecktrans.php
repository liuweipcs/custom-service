<?php
//use app\components\GridView;
use yii\helpers\Url;
use yii\helpers\Html;
$this->title = "(售后部)ebay交易信息异常订单列表";
?>

<style type="text/css">
    .dashboard_div_index{
        width: 45%;
        border: 1px solid #e66;
        margin: 5px;
        float: right;
        min-height: 100px;
    }
    .dashboard_div {
        width: 40%;
        border: 1px solid #e66;
        margin: 5px;
        float: left;
        min-height: 100px;
    }
    .dashboard_row{
        border: 1px solid rgb(184, 208, 214);
        padding: 5px;
        margin: 5px;
        display: block;
    }

    body {
        background-color: white;
    }
</style>
<div class="popup-wrapper">
    <div class="row">
        <div class="col-lg-12">    
            <iframe src="<?php echo Yii::$app->params['erp_order_url']; ?>/site/index?from=ebaychecktrans" frameborder="0" id="iframe" name="iframe">
            </iframe>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function() {
        function setIframeWH() {
            var bw = $(window).width();
            var bh = $(window).height();
            var sw = $(".sidebar").width();
            var nh = $(".navbar").height();
 
            $("#iframe").css("float", "right");
            $("#iframe").css("width", (bw - 46) + "px");
            $("#iframe").css("height", (bh - nh) + "px");
        }
        setIframeWH();

        $(window).on("resize", function() {
         
            setIframeWH();
        });
    });
</script>