<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
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
        <div class="row">
            <div class="col-xs-6">
                <div>
                    <label>资源选择列表</label>
                    <span class="pull-right"><a class="ajax-button small" href="<?php echo Url::toRoute('/systems/resource/refresh');?>"><?php echo Yii::t('system', 'Refresh Resource');?></a></span>
                </div>
                <select name="resource_ids[]" multiple class="form-control col-md-2" size="16">
                <?php 
                foreach ($resourceList as $row) 
                { 
                ?>
                    <optgroup label="<?php echo $row['resource_description']?>" value="<?php echo $row['id']?>"><?php echo $row['resource_description']?></optgroup>
                <?php
                    if (isset($row['children']) && !empty($row['children']))
                    {
                        foreach ($row['children'] as $children)
                        {
                ?>
                    <option value="<?php echo $children['id'];?>"<?php echo in_array($children['id'], $selectedResourceIds) ? ' selected="selected"' : '';?>>
                    <?php echo $children['resource_description'];?>
                    </option>
                <?php
                        }
                    }      
                ?>
                <?php 
                }
                ?>
                </select>
            </div>
        </div>
    </div>
    <div class="popup-footer">
        <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
        <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
    </div>
    <?php
        ActiveForm::end();
    ?>
</div>        