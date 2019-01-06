<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<style>
    #addFeedback {
        margin: 20px auto 0 auto;
        width: 90%;
        height: auto;
        border-collapse: collapse;
    }

    #addFeedback td {
        border: 1px solid #ccc;
        padding: 10px;
    }

    #addFeedback td.col1 {
        width: 120px;
        text-align: right;
        font-weight: bold;
    }
</style>
<script src="<?php echo yii\helpers\Url::base(true); ?>/js/star-rating.js"></script>
<script src="<?php echo yii\helpers\Url::base(true); ?>/js/star-rating.min.js"></script>
<link href="<?php echo yii\helpers\Url::base(true); ?>/css/star-rating.css" rel="stylesheet">
<link href="<?php echo yii\helpers\Url::base(true); ?>/css/star-rating.min.css" rel="stylesheet">
<div class="popup-wrapper">
    <?php
    $form = ActiveForm::begin([
        'id' => 'addFeedbackForm',
        'action' => Url::toRoute(['/mails/aliexpressevaluate/feedback']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addFeedback">
            <tr>
                <td class="col1">评价星级</td>
                <td>
                    <input value="<?php echo !empty($info['seller_evaluation']) ? $info['seller_evaluation'] : 0; ?>" type="number" name="seller_evaluation" class="rating" min="0" max="5" step="1" data-size="xl">
                </td>
            </tr>
            <tr>
                <td class="col1">评价内容</td>
                <td>
                    <?php if (empty($info['seller_evaluation']) && empty($info['seller_feedback'])) { ?>
                        <textarea name="seller_feedback" rows="10" class="form-control"></textarea>
                    <?php } else { ?>
                        <textarea name="seller_feedback" rows="10" class="form-control" disabled readonly><?php echo !empty($info['seller_feedback']) ? $info['seller_feedback'] : ''; ?></textarea>
                    <?php } ?>
                </td>
            </tr>
            <?php if (empty($info['seller_evaluation']) && empty($info['seller_feedback'])) { ?>
                <tr>
                    <td></td>
                    <td>
                        <input type="submit" class="btn btn-primary" value="提交">
                        <input type="reset" class="btn btn-default" value="取消">
                        <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <div class="popup-footer"></div>
    <?php
    ActiveForm::end();
    ?>
</div>
