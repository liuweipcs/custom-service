<?php
use yii\bootstrap\ActiveForm;
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
        <div style="height:300px;overflow-y:auto;">
            <input type="hidden" name="MailTag[inbox_id]" value="<?php echo $subject_ids; ?>"/>
            <input type="hidden" name="MailTag[type]" value="<?php echo $type; ?>"/>
            <?php if (!empty($tags_data)) { ?>
                <?php foreach ($tags_data as $tag_id => $tag_name) { ?>
                    <label class="checkbox-inline">

                        <?php if (in_array($tag_id, $exist_data)) { ?>
                            <input type="checkbox" name="MailTag[tag_id][]" value="<?php echo $tag_id; ?>" checked> <?php echo $tag_name; ?>
                        <?php } else { ?>
                            <input type="checkbox" name="MailTag[tag_id][]" value="<?php echo $tag_id; ?>"> <?php echo $tag_name; ?>
                        <?php } ?>
                    </label>
                <?php } ?>
            <?php } else { ?>
                该平台或者消息暂无任何标签数据
            <?php } ?>
        </div>
    </div>
    <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
    </div>
    <?php
    ActiveForm::end();
    ?>
</div>