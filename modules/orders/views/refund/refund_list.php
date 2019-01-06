<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;
?>

<div class="popup-wrapper">
    <?php
    $form = ActiveForm::begin([
        'id' => 'refundList',
        'action' => Url::toRoute(['/orders/refund/getrefund']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="refundList">
            <span style="color:red;">目前只支持MALL平台</span>
            <tr>
                <td class="col1">订单号：</td>
                <td colspan="2">
                    <input type="text" name="order_id" class="form-control">
                </td>
            </tr>

            <tr>
                <td class="col1">账号：</td>
                <td colspan="2">
                    <div class="form-group" style="margin-top: 10px;margin-left: -15px;">
                    <div class="col-lg-7">
                        <?php echo Select2::widget([
                            'id' => 'account_id',
                            'name' => 'account_id',
                            'value' => $account_id,
                            'data' => $account,
                            'options' => ['placeholder' => '请选择...']
                        ]);
                        ?>
                    </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan="2">
                    <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
                    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
                </td>
            </tr>
        </table>
    </div>
    <div class="popup-footer"></div>
    <?php
    ActiveForm::end();
    ?>
</div>