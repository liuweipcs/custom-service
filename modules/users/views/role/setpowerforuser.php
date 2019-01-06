<?php 
use yii\bootstrap\ActiveForm;
?>
<style type="text/css">
ul.top-menu-list {
	overflow:hidden;
	margin-bottom:35px;
}
ul.top-menu-list li {
	margin-bottom:10px;
}
ul.second-menu-list {
     margin-bottom:0;
     overflow:hidden;
 }
ul.second-menu-list li {
    padding: 3px 25px;
    display: inline-block;
    /*height: 50px;*/
    vertical-align: top;
}
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="page-header"><strong><?php echo \Yii::t('menu', 'Set Privileges For {role}', ['role' => $model->user_name]);?></strong></div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
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
            <ul class="list-unstyled top-menu-list">
            <?php foreach ($menuList as $topMenu) { ?>
                <li>
                    <label class="checkbox-inline" for="menu_<?php echo $topMenu['id'];?>">
                        <input name="menu_ids[]" type="checkbox" id="menu_<?php echo $topMenu['id'];?>" value="<?php echo $topMenu['id'];?>"<?php echo in_array($topMenu['id'], $selectedMenuIds) ? ' checked="checked"' : '';?>>
                        <strong><?php echo $topMenu['menu_name'];?></strong>
                    </label>
                    <?php if (isset($topMenu['children']) && !empty($topMenu['children'])) { ?>
                    <ul class="list-inline second-menu-list">
                        <?php foreach ($topMenu['children'] as $secondMenu) {
                            ?>
                        <li>
                            <label class="checkbox-inline" for="menu_<?php echo $secondMenu['id'];?>">
                                <input name="menu_ids[]" type="checkbox" id="menu_<?php echo $secondMenu['id'];?>" value="<?php echo $secondMenu['id'];?>" <?php echo in_array($secondMenu['id'], $selectedMenuIds) ? ' checked="checked"' : '';?>>
                                <?php echo $secondMenu['menu_name'];?>
                            </label>
                            <?php 
                                if(!empty($secondMenu['lev3'])){ ?>
                                    <ul class="list-inline three-menu-list">
                                        <?php foreach ($secondMenu['lev3'] as $key => $value) { ?>
                                            <li>
                                                <label class="checkbox-inline" for="menu_<?php echo $value['id'];?>">
                                                <input name="menu_ids[]" type="checkbox" id="menu_<?php echo $value['id'];?>" value="<?php echo $value['id'];?>" <?php echo in_array($value['id'], $selectedMenuIds) ? ' checked="checked"' : '';?>><?php echo $value['menu_name'];?></label>
                                            </li>
                                        <?php }?>
                                    </ul>
                            <?php } ?>
                            
                            <?php if(isset($secondMenu['button']) && !empty($secondMenu['button'])){ ?>
                                <ul class="list-inline third-menu-list">
                                    <?php foreach($secondMenu['button'] as $button){ ?>
                                        <li>
                                            <label class="checkbox-inline" for="menu_<?php echo $button['id'];?>">
                                                <input name="source_ids[]" type="checkbox" id="menu_<?php echo $button['id'];?>" value="<?php echo $button['id'];?>"<?php echo in_array($button['id'], $selectedSourcesIds) ? ' checked="checked"' : '';?>>
                                                <?php echo $button['resource_description'];?>
                                            </label>
                                        </li>
                                    <?php }?>
                                </ul>
                            <?php }?>
                        </li>
                        <?php } ?>
                    </ul>
                    <?php } ?>
                </li>
            <?php } ?>    
            </ul>
            <div class="button-row">
                <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Save');?></button>
            </div>
        <?php
        ActiveForm::end();
        ?>    
        </div>
    </div>
</div>        