<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<style>
    #batchSetNotReminderBuyer {
        margin: 20px auto 0 auto;
        width: 90%;
        height: auto;
        border-collapse: collapse;
    }

    #batchSetNotReminderBuyer td {
        border: 1px solid #ccc;
        padding: 10px;
    }
</style>
<div class="popup-wrapper">
    <?php
    $form = ActiveForm::begin([
        'id' => 'batchSetNotReminderBuyerForm',
        'action' => Url::toRoute(['/systems/remindermsgrule/batchsetnotreminderbuyer']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="batchSetNotReminderBuyer">
            <tr>
                <td class="col1">以下买家不执行催付：<span style="color:red;">(不同买家之间用","号分割, 注意是半角英文)</span></td>
            </tr>
            <tr>
                <td>
                    <textarea rows="20" class="form-control" name="not_reminder_buyer"><?php echo $notReminderBuyer; ?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="submit" class="btn btn-primary btn-sm" value="修改">
                    <input type="button" class="btn btn-default btn-sm" onclick="top.layer.closeAll('iframe');" value="取消">
                    <input type="hidden" name="ids" value="<?php echo $ids; ?>">
                </td>
            </tr>
        </table>
    </div>
    <div class="popup-footer"></div>
    <?php
    ActiveForm::end();
    ?>
</div>
