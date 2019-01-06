<?php
use yii\bootstrap\ActiveForm;
?>
<style type="text/css">

</style>
<div class="popup-wrapper">
    <div class="popup-body">
        <div  id="operating">
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
            <div style="height:300px;overflow-y:scroll">
                <input type="hidden" name="ids" value="<?php echo $ids;?>"/>
                <div class="popup-body">
                    <div class="row">
                        <div class="col-sm-9">
                            <div class="form-group field-ebayfeedbackresponse-response_text required">
                                <label class="control-label col-sm-3" for="ebayfeedbackresponse-response_text">内容</label>
                                <div class="col-sm-6">
                                    <textarea id="ebayfeedbackresponse-response_text" class="form-control" name="EbayFeedbackResponse[response_text]" maxlength="80" rows="7" aria-required="true"></textarea>
                                    <div class="help-block help-block-error "></div>
                                </div>

                            </div>
                            <span style="display: none;" class="count_length">(0/80)字符</span>
                        </div>

                    </div>

                </div>
                <div class="popup-footer">
                    <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
                    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
                </div>
            </div>
        </div>

    <?php
    ActiveForm::end();
    ?>
</div>

<script type="text/javascript">
$(function(){
    
    var content = "内容(0/80)字符";
    $('#ebayfeedbackresponse-response_text').on('keyup',function(){
            $(".count_length").css('display','block');
            var contentLength = $(this).val().length;
            if(contentLength < 80){
                var content = $('.count_length').text('内容('+contentLength+'/80)');
                $("label[for='ebayfeedbackresponse-response_text']").html(content);
            }else{
                var text = "内容<span style='color:red;'>(80/80)</span>"
                var content = $('.count_length').html(text);
            }
        });
})
</script>