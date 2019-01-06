<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
?>
<div class="popup-wrapper">
    <?php
    $form = ActiveForm::begin([
        'id' => 'addExecute',
        'action' => Url::toRoute(['/systems/rule/execute']),
        'method' => 'post',
    ]);
    ?>
    <table id="addExecute">
        <div style="padding-left: 20px;">
            <p style="font-size:20px;">检测到有<span style="color: red"><?php echo $mail_number;?></span>封未处理邮件，确定将这些未处理的邮件按规则分类？</p>
            <p style="color:green;font-size:17px;">处理时间大约10分钟</p>
            <input hidden="hidden" name="platfrom_code" value="<?php echo $platfrom_code;?>">
            <input hidden="hidden" name="mail_number" value="<?php echo $mail_number;?>">
            <input hidden="hidden" name="start_time" value="<?php echo $start_time;?>">
            <input hidden="hidden" name="end_time" value="<?php echo $end_time;?>">
        </div>
        <div class="form-group" style="padding-left: 20px; padding-top: 30px;">
            <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
            <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
        </div>
    </table>
    <?php
    ActiveForm::end();
    ?>

</div>
