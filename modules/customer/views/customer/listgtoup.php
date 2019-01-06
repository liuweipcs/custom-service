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
        <?php if (!empty($group['ALL'])) { ?>
            <div class="">
                <p>通用分组</p>
                <?php foreach ($group['ALL'] as $item) { ?>
                    <input name="grp[ALL][]" type="checkbox"
                           value="<?php echo $item['id']; ?>" <?php if (in_array($item['id'], $groupDetail)) {
                        echo "checked";
                    } ?>><?php echo $item['group_name']; ?>
                <?php } ?>
                <?php unset($group['ALL']); ?>
            </div>
        <?php } ?>

        <?php if (!empty($group)) { ?>
            <?php foreach ($group as $key => $grp) { ?>
                <?php if (!empty($grp)) { ?>
                    <div class="">
                        <p><?php echo $key; ?></p>
                        <?php foreach ($grp as $item) { ?>
                            <input name="grp[<?php echo $key; ?>][]" type="checkbox"
                                   value="<?php echo $item['id']; ?>" <?php if (in_array($item['id'], $groupDetail)) {
                                echo "checked";
                            } ?>><?php echo $item['group_name']; ?>
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