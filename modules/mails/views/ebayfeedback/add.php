<?php
use yii\bootstrap\ActiveForm;
?>
<style type="text/css">

</style>
<div class="popup-wrapper">
    <div class="popup-body">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#operating" data-toggle="tab" aria-expanded="true">操作</a></li>
            <li><a href="#order_detail" data-toggle="tab">订单详情</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade in active" id="operating">
                <?php
                $form = ActiveForm::begin([
                    'id' => 'account-form',
                    'layout' => 'horizontal',
                    'action' => Yii::$app->request->getUrl(),
                    'enableClientValidation' => false,
                    'validateOnType' => false,
                    'validateOnChange' => false,
                    'validateOnSubmit' => true,
                ]);
                ?>
                <div class="popup-body">
                    <div class="row">
                        <div class="col-sm-9">
                            <div class="form-group field-ebayfeedback-item_id">
                                <label class="control-label col-sm-3" for="ebayfeedback-item_id"><input type="checkbox" checked="checked" disabled></label>
                                <div class="col-sm-6">
                                    <p>好评</p>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-9">
                            <?php echo $form->field($model, 'item_id')->dropDownList($itemIds);?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-9">
                            <div class="form-group">
                                <label class="control-label col-sm-3" for="ebayfeedback-comment_text"></label>
                                <div class="col-sm-6">
                                    <a class="get_template_content">随机获取模板内容</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-9">
                            <?php echo $form->field($model, 'comment_text')->textarea(['rows'=>7,'maxlength'=>80]);?>
                            <span style="display: none;" class="count_length">(0/80)字符</span>
                        </div>
                    </div>
                </div>
                <div class="popup-footer">
                    <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
                    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
                </div>
            </div>
            <div class="tab-pane fade" id="order_detail">
                <iframe src="/orders/order/orderdetails?order_id=<?=$platform_order_id?>&platform=<?=$platform?>" frameborder="1" width="100%" height="100%" style="min-height:1000px;"></iframe>
            </div>
        </div>
    <?php
    ActiveForm::end();
    ?>
</div>

<script type="text/javascript">
$(function(){
    
    var content = "内容(0/80)字符";
    $('#ebayfeedback-response_text').on('keyup',function(){
            $(".count_length").css('display','block');
            var contentLength = $(this).val().length;
            if(contentLength < 80){
                var content = $('.count_length').text('内容('+contentLength+'/80)');
                $("label[for='ebayfeedback-response_text']").html(content);
            }else{
                var text = "内容<span style='color:red;'>(80/80)</span>"
                var content = $('.count_length').html(text);
            }
        });
})

$('.get_template_content').on('click',function () {
    var platform = "<?=$platform?>";
    $.post('<?= \yii\helpers\Url::toRoute(['/mails/feedbacktemplate/gettemplatename'])?>', {platform_code:platform}, function (data) {
        console.log(data);
        if (data.code == "200")
        {
            $('#ebayfeedback-comment_text').val(data.data);
        }
        else
        {
            layer.msg(data.message, {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });return;
        }

    }, 'json');
})
</script>