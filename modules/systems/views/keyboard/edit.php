<?php
use yii\bootstrap\ActiveForm;
use app\modules\systems\models\Tag;
use \yii\helpers\Url;
?>
<div class="popup-wrapper">
    <?php
        $form = ActiveForm::begin([
            'id' => 'platform-form',
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

            <?php if(!empty($platformList)){?>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'platform_code')->dropdownList($platformList, ['prompt'=>'请选择平台','encodeSpaces' => true]);?>
            </div>
            <?php }?>

             <div class="col-sm-6">
                 <?php echo $form->field($model, 'tag_id')->dropdownList($tags, ['prompt'=>'请选择标签','encodeSpaces' => true]);?>
                 <input type="hidden" id="keyboard-tag_name" name="Keyboard[tag_name]" value="<?php echo $model->tag_name; ?>">
             </div>

             <input type="hidden" id="keyboard-user_id" class="form-control" name="Keyboard[user_id]" value="<?php echo \Yii::$app->user->identity->id?>">
             <input type="hidden" id="keyboard-create_by" class="form-control" name="Keyboard[create_by]" value="<?php echo \Yii::$app->user->identity->login_name?>">
         </div>
        <div class="row">
            <div class="col-sm-6">
                <?php //echo $form->field($model, 'status')->label();?>
                <?php echo $form->field($model, 'status')->inline()->radioList($model->getStatusList(), ['value' => $model->status]);?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'key_name',['inputOptions'=>['readonly'=>'readonly']]);?>
            </div>
            <input type="hidden" id="keyboard-key_code" class="form-control" name="Keyboard[key_code]" value="<?echo $model->key_code; ?>">
            <input type="hidden" id="keyboard-key_basic" class="form-control" name="Keyboard[key_basic]" value="<?echo $model->key_basic; ?>">
        </div>
          <!--
          <div class="row">
             
            <div class="col-sm-6">
                 <?php //echo $form->field($model, 'tag_type')->dropdownList($tagTypeList, ['encodeSpaces' => true]);?>
            </div>
         </div> -->
      
     </div>
     <div class="popup-footer">
        <button id="button_submit" class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
     </div>
    <?php
        ActiveForm::end();
    ?>
</div>

<script type="text/javascript">
    var arr = [16,17,18];

    $("#keyboard-key_name").on('focus',function(){
        $(this).on('keyup',function(event){
            if(event.shiftKey)
            {
                $("#keyboard-key_name").val('shift+'+event.key)
                $("#keyboard-key_basic").val('shift');
                if($.inArray(event.keyCode,arr) < 0)
                {
                    $("#keyboard-key_code").val(event.keyCode);
                }
//                console.log(16)
            }
            else if(event.ctrlKey)
            {
                $("#keyboard-key_name").val('ctrl+'+event.key)
                $("#keyboard-key_basic").val('ctrl');
                if($.inArray(event.keyCode,arr) < 0)
                {
                    $("#keyboard-key_code").val(event.keyCode);
                }
//                console.log(17)
            }
            else if(event.altKey)
            {
                $("#keyboard-key_name").val('alt+'+event.key)
                $("#keyboard-key_basic").val('alt');
                if($.inArray(event.keyCode,arr) < 0)
                {
                    $("#keyboard-key_code").val(event.keyCode);
                }
//                console.log(18)
            }

        })
//        $(document).ready(
//            function(){
//                document.onkeydown = function()
//                {
//                    var oEvent = window.event;
//                    console.log(oEvent.key);
//                    if (oEvent.keyCode == 48 && oEvent.altKey) {  //这里只能用alt，shift，ctrl等去组合其他键event.altKey、event.ctrlKey、event.shiftKey 属性
//                        console.log("你按下了alt+0");
//                    }
//                }
//            }
//        )
    })
    $("#keyboard-key_name").on('blur',function(){
        $(this).unbind('keyup');
    })

    $("#keyboard-platform_code").on('change',function(){
        var _platform_code = $(this).val();

        var url = '<?php echo Url::toRoute(['/systems/tag/gettagsbyplatformcode']);?>';
        $.get(url, {"platform_code":_platform_code}, function(data){
            if(data.code == 200)
            {
                var tags = data.data;
                var html = '';
                html = '<option value = 0>请选择标签</option>';
                for (var i in tags)
                {
                    html += '<option value="' + i + '">' + tags[i] + '</option>' + "\n";
                }
                $('#keyboard-tag_id').empty().html(html);
            }
        }, 'json');
    })

    $("#keyboard-tag_id").on('change',function(){
        var _text = $(this).find("option:selected").text();
        $("#keyboard-tag_name").val(_text);
    })

</script>