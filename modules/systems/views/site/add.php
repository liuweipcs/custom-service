<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<div class="popup-wrapper">
    <style>
        #addSite {
            margin: 20px auto 0 auto;
            width: 90%;
            height: auto;
            border-collapse: collapse;
        }

        #addSite td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        #addSite td.col1 {
            width: 120px;
            text-align: right;
            font-weight: bold;
        }
    </style>
    <?php
    $form = ActiveForm::begin([
        'id' => 'addSiteForm',
        'action' => Url::toRoute(['/systems/site/add']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addSite">
            <tr>
                <td class="col1">站点名称：</td>
                <td><input type="text" name="site_name" class="form-control"></td>
            </tr>
            <tr>
                <td class="col1">平台：</td>
                <td>
                    <select name="platform_code" class="form-control">
                        <option value="">请选择</option>
                        <?php if (!empty($platformList)) { ?>
                            <?php foreach ($platformList as $key => $value) { ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="col1">状态：</td>
                <td>
                    <input type="radio" name="status" value="1" checked>有效
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="status" value="0">无效
                </td>
            </tr>
            <tr>
                <td class="col1">排序：</td>
                <td>
                    <input type="text" class="form-control" name="sort" value="0">
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <input type="submit" class="btn btn-primary btn-sm" value="添加">
                    <input type="reset" class="btn btn-default btn-sm" value="取消">
                </td>
            </tr>
        </table>
    </div>
    <div class="popup-footer"></div>
    <?php
    ActiveForm::end();
    ?>
</div>
