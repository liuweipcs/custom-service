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
        <?php if (!empty($tags['ALL'])) { ?>
            <div class="">
                <p>通用标签</p>
                <?php foreach ($tags['ALL'] as $item) { ?>
                    <input name="tag[ALL][]" type="checkbox"
                           value="<?php echo $item['id']; ?>" <?php if (in_array($item['id'], $tagsDetail)) {
                        echo "checked";
                    } ?>><?php echo $item['tag_name']; ?>
                <?php } ?>
                <?php unset($tags['ALL']); ?>
            </div>
        <?php } ?>

        <?php if (!empty($tags)) { ?>
            <?php foreach ($tags as $key => $tag) { ?>
                <?php if (!empty($tag)) { ?>
                    <div class="">
                        <p><?php echo $key; ?></p>
                        <?php foreach ($tag as $item) { ?>
                            <input name="tag[<?php echo $key; ?>][]" type="checkbox"
                                   value="<?php echo $item['id']; ?>" <?php if (in_array($item['id'], $tagsDetail)) {
                                echo "checked";
                            } ?>><?php echo $item['tag_name']; ?>
                        <?php } ?>
                    </div>
                <?php } ?>
            <?php } ?>
        <?php } ?>
    </div>
    <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
    </div>
    <?php
    ActiveForm::end();
    ?>
</div>