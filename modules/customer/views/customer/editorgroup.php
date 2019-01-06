<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

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
        .form-control_value{
            width: 10%;
            height: 35px;
        }
    </style>

    <?php
    $form = ActiveForm::begin([
        'id' => 'addMailFilterManageForm',
        'action' => Url::toRoute(['/customer/customer/addgroup']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addMailFilterManage">
            <tr>
                <td class="col1">分组名称：</td>
                <td colspan="2">
                    <input type="text" name="group_name" class="form-control" value="<?php echo $info['group_name']; ?>">
                </td>
            </tr>

            <tr>
                <td class="col1">平台：</td>
                <td colspan="2">
                    <select name="platform_code" class="form-control">
                        <option value="">请选择</option>
                        <?php if (!empty($platformList)) { ?>
                            <?php foreach ($platformList as $key => $value) { ?>
                                <option value="<?php echo $key; ?>" <?php if ($key == $info['platform_code']) {
                                    echo "selected";
                                } ?>><?php echo $value; ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="col1">说明：</td>
                <td colspan="2">
                    <textarea name="instruction" class="form-control" style="width: 100%;height:200px;"><?php echo $info['instruction']; ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="col1">状态：</td>
                <td colspan="2">
                    <input type="radio" name="status" value="1" <?php if (1 == $info['status']) {
                        echo "checked";
                    } ?>>有效
                    <input type="radio" name="status" value="0" <?php if (0 == $info['status']) {
                        echo "checked";
                    } ?>>无效
                </td>
            </tr>
            <tr>
                <td> </td>
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