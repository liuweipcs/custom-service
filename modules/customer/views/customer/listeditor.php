<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use app\modules\customer\models\CustomerTagsRule;

$startIndex = 0;
?>

<div class="popup-wrapper">
    <style>
        #addMailFilterManage {
            margin: 20px auto 0 auto;
            width: 90%;
            height: auto;
            border-collapse: collapse;
        }

        #addMailFilterManage td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #addMailFilterManage td.col1 {
            width: 150px;
            text-align: right;
            font-weight: bold;
        }


        #addMailFilterManage td.col2 {
            width: 190px;
        }

        #addMailFilterRule {
            width: 100%;
        }

        #addMailFilterRule td {
            border: none;
        }

        #addMailFilterRule td.col1 {
            width: 180px;
        }

        #addMailFilterRule td span.glyphicon-remove {
            font-size: 24px;
            color: red;
            cursor: pointer;
        }

        #hideMailFilterRule {
            display: none;
        }

        .form-control_value {
            width: 10%;
            height: 35px;
        }
    </style>

    <?php
    $form = ActiveForm::begin([
        'id' => 'addMailFilterManageForm',
        'action' => Url::toRoute(['/customer/customer/listeditor']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addMailFilterManage">
            <tr>
                <td class="col1">平台：</td>
                <td colspan="2">
                    <?php if ($info['type'] == 0) { ?>
                        <input type="text" name="platform_code" class="form-control" readonly="readonly"
                               value="<?php echo $platform[$info['platform_code']]; ?>">
                    <?php } else { ?>
                        <select name="platform_code" class="form-control">
                            <option value="">请选择</option>
                            <?php if (!empty($platform)) { ?>
                                <?php foreach ($platform as $key => $value) { ?>
                                    <option value="<?php echo $key; ?>" <?php if ($key == $info['platform_code']) {
                                        echo "selected";
                                    } ?>><?php echo $value; ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td class="col1">客户ID：</td>
                <td colspan="2">
                    <input type="text" name="buyer_id" class="form-control"
                           <?php if ($info['type'] == 0) { echo 'readonly="readonly"'; } ?>
                           value="<?php echo $info['buyer_id']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">客户邮箱：</td>
                <td colspan="2">
                    <input type="text" name="buyer_email" class="form-control"
                           <?php if ($info['type'] == 0) { echo 'readonly="readonly"'; } ?>
                           value="<?php echo $info['buyer_email']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">客户姓名：</td>
                <td colspan="2">
                    <input type="text" name="buyer_name" class="form-control"
                           value="<?php echo $info['buyer_name']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">店铺：</td>
                <td colspan="2">
                    <select name="account_name" class="form-control">
                        <?php if (!empty($account)) { ?>
                            <?php foreach ($account as $key => $value) { ?>
                                <option value="<?php echo $value; ?>" <?php if ($value == $info['account_name']) {
                                    echo "selected";
                                } ?>><?php echo $value; ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="col1">付款邮箱：</td>
                <td colspan="2">
                    <input type="text" name="pay_email" class="form-control" value="<?php echo $info['pay_email']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">信用评价：</td>
                <td colspan="2">
                    <input type="text" placeholder="0-100数值" name="credit_rating" class="form-control"
                           value="<?php echo $info['credit_rating']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">电话号码：</td>
                <td colspan="2">
                    <input type="text" name="phone" class="form-control" value="<?php echo $info['phone']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">WEchat：</td>
                <td colspan="2">
                    <input type="text" name="wechat" class="form-control" value="<?php echo $info['wechat']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">Skype：</td>
                <td colspan="2">
                    <input type="text" name="skype" class="form-control" value="<?php echo $info['skype']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">Whatsapp：</td>
                <td colspan="2">
                    <input type="text" name="whatsapp" class="form-control" value="<?php echo $info['whatsapp']; ?>">
                </td>
            </tr>
            <tr>
                <td class="col1">Trademanager：</td>
                <td colspan="2">
                    <input type="text" name="trademanager" class="form-control"
                           value="<?php echo $info['trademanager']; ?>">
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan="2">
                    <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
                    <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
                    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
                </td>
            </tr>
        </table>
    </div>
    <div class="popup-footer"></div>
    <?php
    ActiveForm::end();
    ?>
</div>
