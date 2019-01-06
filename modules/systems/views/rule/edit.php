<?php

use yii\bootstrap\ActiveForm;
use app\modules\systems\models\Condition;
use app\modules\systems\models\ConditionOption;
use app\modules\systems\models\Rule;
use app\modules\systems\models\RuleCondtion;
use kartik\select2\Select2;
use kartik\datetime\DateTimePicker;

?>
<style type="text/css">
    .selItem {
        width: 130px;
        height: 32px;
        line-height: 32px;
        overflow: hidden;
        text-align: center;
        margin-bottom: 5px;
        margin-left: 3px;
        margin-top: 3px;
        padding: 0 10px;
        position: relative;
        text-overflow: ellipsis;
    }

    .selItem span.glyphicon-remove {
        float: right;
        top: 7px;
        right: 5px;
        position: absolute;
    }
</style>


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

<?php
$condition_by = $model->condition_by;
if (!empty($condition_by)) {
    $condition_arr = json_decode($condition_by);
} else {
    $condition_arr = [];
}
?>
<div style="width:100%;height:830px;margin-top: 10px;">
    <div style="width:72%;height:780px;border-radius: 6px;float:right;border:1px solid #333;margin-right:3%;margin-bottom:30px">
        <div style="width:100%;height:30px;line-height:30px;border-bottom:1px solid #333;padding-left:15px">
            已选条件
        </div>
        <div style="width:100%;height:400px;overflow-y:scroll;padding-top:10px">
            <div id="new_condition">
                <?php foreach ($condition_arr as $v) {
                    if ($v == 'feedback_negative') {
                        $condition_id = 1; ?>
                        <div id="container1">
                            <input type="hidden" value="<?php echo $v ?>" name="Rule[new_rule_condition][<?php echo $condition_id ?>][condition_name]">
                        </div>
                    <?php }
                    if ($v == 'feedback_positive') {
                        $condition_id = 2; ?>
                        <div id="container2">
                            <input type="hidden" value="<?php echo $v ?>" name="Rule[new_rule_condition][<?php echo $condition_id ?>][condition_name]">
                        </div>
                    <?php }
                    if ($v == 'review_negative') {
                        $condition_id = 3; ?>
                        <div id="container3">
                            <input type="hidden" value="<?php echo $v ?>" name="Rule[new_rule_condition][<?php echo $condition_id ?>][condition_name]">
                        </div>
                    <?php }
                    if ($v == 'review_positive') {
                        $condition_id = 4; ?>
                        <div id="container4">
                            <input type="hidden" value="<?php echo $v ?>" name="Rule[new_rule_condition][<?php echo $condition_id ?>][condition_name]">
                        </div>
                    <?php }
                    if ($v == 'dispute') {
                        $condition_id = 5; ?>
                        <div id="container5">
                            <input type="hidden" value="<?php echo $v ?>" name="Rule[new_rule_condition][<?php echo $condition_id ?>][condition_name]">
                        </div>
                    <?php }
                    if (strpos($v, 'buyer_message') !== false) {
                        $condition_id = 6;
                        $date_message = intval(explode('&', $v)[1]); ?>
                        <div id="container6">
                            <input type="hidden" value="<?php echo $v ?>" name="Rule[new_rule_condition][<?php echo $condition_id ?>][condition_name]">
                            <input type="hidden" value="<?= $date_message ?>" name="Rule[new_rule_condition][<?php echo $condition_id ?>][buyer_message_day]">
                        </div>
                    <?php }
                } ?>

            </div>
            <div id="condition_option_container">
                <?php foreach ($rule_condition_data['condition_data'] as $key => $value) { ?>

                <?php if ($value['input_type'] == Condition::CONDITION_INPUT_TYPE_INPUT) { ?>
                    <div id="<?php echo 'container' . $value['condition_id']; ?>" style='margin:10px 5px'>
                        <div style='width:160px;float:left;height:34px;line-height:34px;text-align:left;'><?php echo $value['condition_name']; ?>
                            :　
                        </div>
                        <input type='hidden' value="<?php echo $value['input_type']; ?>"
                               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][input_type]"/>
                        <input type='hidden' value="<?php echo $value['condition_name']; ?>"
                               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_name]"/>
                        <input type='hidden' value="<?php echo $value['condition_key']; ?>"
                               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_key]"/>

                        <div style='float:left'>
                            <select onchange="baohan_bubaohan(this,<?php echo $value['condition_id']; ?>)" class='form-control' style='width:80px' name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][oprerator]">
                                <?php foreach ($oprerator_data as $k => $v) { ?>
                                    <?php if ($k == $value['oprerator']) { ?>
                                        <option value="<?php echo $k; ?>" selected><?php echo $v; ?></option>
                                    <?php } else { ?>
                                        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>

                        <div id="<?php echo 'input_baohan_bubaohan_' . $value['condition_id']; ?>"
                             style='width:315px;float:left;padding-left:20px;'>

                            <!-- 如果对应规则对应条件下的option_value是数组或者不是数组的情况-->
                            <?php if (!is_array($value['option_value'])) { ?>
                                <input style='width:315px' type='text' class='form-control'
                                       name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][value]"
                                       placeholder='请输入选项值' value="<?php echo $value['option_value'] ?>">

                            <?php } else { ?>
                                <div id='input_input_input_input_input' style='width:100%'>
                                    <div style='display: inline-block;white-space: nowrap;'>
                                        <input style='width:60px;height:34px;font-style: normal;font-weight: normal;'
                                               type='text'
                                               name="Rule[rule_condtion][<?php echo $value['condition_id'] ?>][value][]"
                                               placeholder='您的值'
                                               value="<?php echo current($value['option_value']); ?>"/>
                                        <label onclick="add_input(this,<?php echo $value['condition_id']; ?>)">
                                            <font size='4' color='blue'>&nbsp;+&nbsp;</font>
                                        </label>
                                    </div>
                                </div>
                                <?php
                                array_shift($value['option_value']);
                                ?>
                                <?php foreach ($value['option_value'] as $k_op => $v_op) { ?>
                                    <?php
                                    $element = md5(time() . mt_rand(1, 1000000));
                                    ?>
                                    <div id="<?php echo 'input_input_' . $element; ?>" style='width:100%'>
                                        <div style='display: inline-block;white-space: nowrap;'>
                                            <input style='font-style: normal;font-weight: normal;width:60px;height:34px'
                                                   type='text'
                                                   name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][value][]"
                                                   placeholder='您的值' value="<?php echo $v_op; ?>">
                                            <label data-element="<?php echo 'input_input_' . $element; ?>" onclick='deal_input(this)'>
                                                <font size='4' color='red'>&nbsp;-&nbsp;</font>
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                            <!-- 如果对应规则对应条件下的option_value是数组或者不是数组的情况结束-->
                        </div>

                        <div style='clear:both'></div>
                    </div>

                <?php } elseif ($value['input_type'] == Condition::CONDITION_INPUT_TYPE_RADIO) { ?>

                    <div id="<?php echo 'container' . $value['condition_id']; ?>" style='margin:10px 5px'>
                        <div style='width:160px;float:left;height:34px;line-height:34px;text-align:left;'><?php echo $value['condition_name']; ?>
                            :　
                        </div>
                        <input type='hidden' value="<?php echo $value['input_type']; ?>"
                               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][input_type]"/>
                        <input type='hidden' value="<?php echo $value['condition_name']; ?>"
                               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_name]"/>
                        <input type='hidden' value="<?php echo $value['condition_key']; ?>"
                               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_key]"/>
                        　　
                        <div style='float:left'>
                            <select class='form-control' style='width:80px' name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][oprerator]">
                                <?php foreach ($oprerator_data as $k => $v) { ?>
                                    <?php if ($k == $value['oprerator']) { ?>
                                        <option value="<?php echo $k; ?>" selected><?php echo $v; ?></option>
                                    <?php } else { ?>
                                        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>

                        <div style='width:315px;float:left;padding-left:20px;'>
                            <?php foreach (ConditionOption::getOptionDataByConditionId($value['condition_id'], $value['condition_key'], $model->platform_code, $model->type) as $wk => $wv) { ?>
                                <div id='input_input_input' style='width:100%'>
                                    <div style='display: inline-block;white-space: nowrap;'>
                                        <?php if ($wv['option_value'] == $value['option_value']) { ?>
                                            <input style='font-style: normal;font-weight: normal;' type='radio'
                                                   name='Rule[rule_condtion][<?php echo $value['condition_id']; ?>][value]'
                                                   value="<?php echo $wv['option_value']; ?>" checked/>
                                        <?php } else { ?>
                                            <input style='font-style: normal;font-weight: normal;' type='radio'
                                                   name='Rule[rule_condtion][<?php echo $value['condition_id']; ?>][value]'
                                                   value="<?php echo $wv['option_value']; ?>"/>
                                        <?php } ?>
                                        <label><?php echo $wv['option_name']; ?></label>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div style='clear:both'></div>
                    </div>

                <?php } else if ($value['input_type'] == Condition::CONDITION_INPUT_TYPE_SELECT) { ?>
                <div id="<?php echo 'container' . $value['condition_id']; ?>" style='margin:10px 5px'>
                    <div style='width:160px;float:left;height:34px;line-height:34px;text-align:left;'><?php echo $value['condition_name']; ?>
                        :　
                    </div>
                    <input type='hidden' value="<?php echo $value['input_type']; ?>"
                           name='Rule[rule_condtion][<?php echo $value['condition_id']; ?>][input_type]'/>
                    <input type='hidden' value="<?php echo $value['condition_name']; ?>"
                           name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_name]"/>
                    <input type='hidden' value="<?php echo $value['condition_key']; ?>"
                           name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_key]"/>

                    <div style='float:left'>
                        <select class='form-control' style='width:80px' name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][oprerator]">
                            <?php foreach ($oprerator_data as $k => $v) { ?>
                                <?php if ($k == $value['oprerator']) { ?>
                                    <option value="<?php echo $k; ?>" selected><?php echo $v; ?></option>
                                <?php } else { ?>
                                    <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>

                    <div style='float:left;padding-left:20px'>
                        <?php if ($value['condition_key'] == Condition::CONDITION_KEY_BYUER_OPTION_LOGISTICS) { ?>
                            <a href="#" style="width:160px;float:left;height:34px;line-height:34px;text-align:left" id="container35" data-toggle="modal" data-target="selLogisticsModal">买家选择运输方式:</a>
                            </div>
                            <div style='clear:both'></div>

                            <div id="selLogisticsItem">
                                <?php if (is_array($value['option_value'])) { ?>
                                    <?php foreach ($value['option_value'] as $key => $val) { ?>
                                        <button type="button" class="btn btn-default click_remove selItem"><?php echo $val; ?>
                                            <span class="glyphicon glyphicon-remove"></span>
                                            <input type="hidden" name="Rule[rule_condtion][35][value][]" value="<?php echo $val; ?>"></button>
                                        </button>
                                    <?php } ?>
                                <?php } else { ?>
                                    <button type="button" class="btn btn-default click_remove">
                                        <?php echo $value['option_value']; ?>
                                        <span class="glyphicon glyphicon-remove"></span>
                                        <input type="hidden" name="Rule[rule_condtion][35][value][]" value="<?php echo $value['option_value']; ?>">
                                    </button>
                                <?php } ?>
                            </div>

                        <?php } else if ($value['condition_key'] == (Condition::CONDITION_KEY_WAREHOUSE_ID)){ ?>
                            <a href="#" style="width:160px;float:left;height:34px;line-height:34px;text-align:left"
                               id="container36" data-toggle="modal" data-target="selWarehouseModal">指定仓库:</a>
                            </div>
                            <div style='clear:both'></div>

                            <div id="selWarehouseItem">
                                <?php foreach (ConditionOption::getOptionDataByConditionId($value['condition_id'], $value['condition_key'], $model->platform_code, $model->type) as $gk => $gv) { ?>
                                    <?php if (is_array($value['option_value'])) { ?>
                                        <?php if (in_array($gk, $value['option_value'])) { ?>
                                            <button type="button" class="btn btn-default click_remove selItem"><?php echo $gv; ?>
                                                <span class="glyphicon glyphicon-remove"></span>
                                                <input type="hidden" name="Rule[rule_condtion][36][value][]"
                                                       value="<?php echo $gk; ?>"></button>
                                            </button>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if ($gk == $value['option_value']) { ?>
                                            <button type="button" class="btn btn-default click_remove"><?php echo $gv; ?><span
                                                        class="glyphicon glyphicon-remove"></span>
                                                <input type="hidden" name="Rule[rule_condtion][36][value][]"
                                                       value="<?php echo $gk; ?>"></button>
                                            </button>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>
                            </div>

                        <?php } else if (($value['condition_key'] == Condition::CONDITION_KEY_INFO_SITE)) { ?>
                            <a href="#" style="width:160px;float:left;height:34px;line-height:34px;text-align:left" id="container44"
                               data-toggle="modal" data-target="selSiteInfoModal">站点:</a>
                            </div>
                            <div style='clear:both'></div>
                            <div id="selSiteInfoItem">
                                <?php foreach (ConditionOption::getOptionDataByConditionId($value['condition_id'], $value['condition_key'], $model->platform_code, $model->type) as $gk => $gv) { ?>
                                    <?php if (is_array($value['option_value'])) { ?>
                                        <?php if (in_array($gk, $value['option_value'])) { ?>
                                            <button type="button" class="btn btn-default click_remove selItem"><?php echo $gv; ?><span
                                                        class="glyphicon glyphicon-remove"></span>
                                                <input type="hidden" name="Rule[rule_condtion][43][value][]" value="<?php echo $gk; ?>">
                                            </button>
                                            </button>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if ($gk == $value['option_value']) { ?>
                                            <button type="button" class="btn btn-default click_remove"><?php echo $gv; ?><span
                                                        class="glyphicon glyphicon-remove"></span>
                                                <input type="hidden" name="Rule[rule_condtion][43][value][]" value="<?php echo $gk; ?>">
                                            </button>
                                            </button>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>
                            </div>

                        <?php } else if ($value['condition_key'] == Condition::CONDITION_KEY_SHIP_CODE) { ?>
                            <a href="#" style="width:160px;float:left;height:34px;line-height:34px;text-align:left" id="container37" data-toggle="modal" data-target="selShipCodeModal">指定邮寄方式:</a>
                            </div>
                            <div style='clear:both'></div>
                            <div id="selShipCodeItem">
                                <?php foreach (ConditionOption::getOptionDataByConditionId($value['condition_id'], $value['condition_key'], $model->platform_code, $model->type) as $gk => $gv) { ?>
                                    <?php if (is_array($value['option_value'])) { ?>
                                        <?php if (in_array($gk, $value['option_value'])) { ?>
                                            <button type="button" class="btn btn-default click_remove selItem"><?php echo $gv; ?><span
                                                        class="glyphicon glyphicon-remove"></span>
                                                <input type="hidden" name="Rule[rule_condtion][37][value][]" value="<?php echo $gk; ?>">
                                            </button>
                                            </button>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if ($gk == $value['option_value']) { ?>
                                            <button type="button" class="btn btn-default click_remove"><?php echo $gv; ?><span
                                                        class="glyphicon glyphicon-remove"></span>
                                                <input type="hidden" name="Rule[rule_condtion][37][value][]" value="<?php echo $gk; ?>">
                                            </button>
                                            </button>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>
                            </div>

                        <?php } else if ($value['condition_key'] == Condition::CONDITION_KEY_SHIP_COUNTRY) { ?>
                            <a href="#" style="width:160px;float:left;height:34px;line-height:34px;text-align:left" id="container40" data-toggle="modal" data-target="selCityAreaModal">指定国家或区域:</a>
                            </div>
                            <div style='clear:both'></div>
                            <div id="selCityAreaItem">
                                <?php foreach (ConditionOption::getOptionDataByConditionId($value['condition_id'], $value['condition_key'], $model->platform_code, $model->type) as $gk => $gv) { ?>
                                    <?php if (is_array($value['option_value'])) { ?>
                                        <?php if (in_array($gk, $value['option_value'])) { ?>
                                            <button type="button" class="btn btn-default click_remove selItem"><?php echo $gv; ?><span
                                                        class="glyphicon glyphicon-remove"></span>
                                                <input type="hidden" name="Rule[rule_condtion][40][value][]" value="<?php echo $gk;; ?>">
                                            </button>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if ($gk == $value['option_value']) { ?>
                                            <button type="button" class="btn btn-default click_remove"><?php echo $gv; ?><span
                                                        class="glyphicon glyphicon-remove"></span>
                                                <input type="hidden" name="Rule[rule_condtion][40][value][]" value="<?php echo $gk; ?>">
                                            </button>
                                            </button>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>
                            </div>

                        <?php } else if ($value['condition_key'] == Condition::CONDITION_KEY_ACCOUNT || $value['condition_key'] == Condition::CONDITION_KEY_ORDER_ACCOUNT){ ?>
                            <a href="#" style="width:160px;float:left;height:34px;line-height:34px;text-align:left" id="container32"
                               data-toggle="modal" data-target="selAccountIdModal">所属账号:</a>
                            </div>
                            <div style='clear:both'></div>
                            <div id="selAccountIdItem">
                                <?php foreach (ConditionOption::getOptionDataByConditionId($value['condition_id'], $value['condition_key'], $model->platform_code, $model->type) as $gk => $gv) { ?>
                                    <?php if (is_array($value['option_value'])) { ?>
                                        <?php if (in_array($gv['id'], $value['option_value'])) { ?>
                                            <button type="button"
                                                    class="btn btn-default click_remove selItem"><?php echo $gv['account_name']; ?><span
                                                    class="glyphicon glyphicon-remove"></span>
                                            <?php if ($value['condition_key'] == Condition::CONDITION_KEY_ORDER_ACCOUNT) { ?>
                                                <input type="hidden" name="Rule[rule_condtion][32][value][]"
                                                       value="<?php echo $gv['id']; ?>"></button>
                                            <?php } else { ?>
                                                <input type="hidden" name="Rule[rule_condtion][31][value][]"
                                                       value="<?php echo $gv['id']; ?>"></button>
                                            <?php }; ?>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if ($gv['id'] == $value['option_value']) { ?>
                                            <button type="button" class="btn btn-default click_remove"><?php echo $gv['account_name']; ?><span
                                                    class="glyphicon glyphicon-remove"></span>
                                            <?php if ($value['condition_key'] == Condition::CONDITION_KEY_ORDER_ACCOUNT) { ?>
                                                <input type="hidden" name="Rule[rule_condtion][32][value][]"
                                                       value="<?php echo $gv['id']; ?>">
                                            <?php } else { ?>
                                                <input type="hidden" name="Rule[rule_condtion][31][value][]"
                                                       value="<?php echo $gv['id']; ?>">
                                            <?php }; ?>
                                            </button>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>
                            </div>

                        <?php } else if ($value['condition_key'] == Condition::CONDITION_KEY_CUSTOMER_COUNTRY) { ?>
                            </div>
                            <div style="float:left;padding-left:20px;width:400px;">
                            <?php echo Select2::widget([
                                'name' => "Rule[rule_condtion][{$value['condition_id']}][value][]",
                                'value' => $value['option_value'],
                                'data' => $countryList,
                                'options' => ['placeholder' => '请选择...', 'multiple' => true]
                            ]);
                            ?>
                            </div>
                            <div style='clear:both'></div>
                        <?php } else if ($value['condition_key'] == Condition::CONDITION_KEY_LOGISTICS_MODE) { ?>
                            </div>
                            <div style="float:left;padding-left:20px;width:400px;">
                            <?php echo Select2::widget([
                                'name' => "Rule[rule_condtion][{$value['condition_id']}][value][]",
                                'value' => $value['option_value'],
                                'data' => $logisticsList,
                                'options' => ['placeholder' => '请选择...', 'multiple' => true]
                            ]);
                            ?>
                            </div>
                            <div style='clear:both'></div>
                        <?php } ?>

                    </div>
                    <div style='clear:both'></div>

<?php } elseif ($value['input_type'] == Condition::CONDITION_INPUT_TYPE_CHECKBOX) { ?>


    <div id="<?php echo 'container' . $value['condition_id']; ?>" style='margin:10px 5px'>
        <div style='width:160px;float:left;height:34px;line-height:34px;text-align:left'><?php echo $value['condition_name']; ?>
            :　
        </div>
        <input type='hidden' value="<?php echo $value['input_type']; ?>"
               name='Rule[rule_condtion][<?php echo $value['condition_id']; ?>][input_type]'/>
        <input type='hidden' value="<?php echo $value['condition_name']; ?>"
               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_name]"/>
        <input type='hidden' value="<?php echo $value['condition_key']; ?>"
               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_key]"/>
        <div style='float:left'>
            <select class='form-control' style='width:80px'
                    name='Rule[rule_condtion][<?php echo $value['condition_id']; ?>][oprerator]'>
                <?php foreach ($oprerator_data as $k => $v) { ?>
                    <?php if ($k == $value['oprerator']) { ?>
                        <option value="<?php echo $k; ?>" selected><?php echo $v; ?></option>
                    <?php } else { ?>
                        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                    <?php } ?>
                <?php } ?>

            </select></div>

        <div style='width:315px;float:left;padding-left:20px;'>

            <?php foreach (ConditionOption::getOptionDataByConditionId($value['condition_id'], $value['condition_key'], $model->platform_code, $model->type) as $hk => $hv) { ?>
                <div id="input_input_input" style='width:100%'>
                    <div style='display: inline-block;white-space: nowrap;'>
                        <?php if (in_array($hv['option_value'], $value['option_value'])) { ?>
                            <input style='font-style: normal;font-weight: normal;' type='checkbox'
                                   name='Rule[rule_condtion][<?php echo $value['condition_id']; ?>][value][]'
                                   value="<?php echo $hv['option_value']; ?>" checked>

                        <?php } else { ?>
                            <input style='font-style: normal;font-weight: normal;' type='checkbox'
                                   name='Rule[rule_condtion][<?php echo $value['condition_id']; ?>][value][]'
                                   value="<?php echo $hv['option_value']; ?>">
                        <?php } ?>
                        <label><?php echo $hv['option_name']; ?></label></div>
                </div>
                </label>
            <?php } ?>

        </div>
        <div style='clear:both'></div>
    </div>


    <!-- 当input_type为范围的情况-->
<?php } elseif ($value['input_type'] == Condition::CONDITION_INPUT_TYPE_RANGE) { ?>
    <div id="<?php echo 'container' . $value['condition_id']; ?>" style='margin:10px 5px'>
        <div style='width:160px;float:left;height:34px;line-height:34px;text-align:left'><?php echo $value['condition_name']; ?>
            :　
        </div>
        <input type='hidden' value="<?php echo $value['input_type']; ?>"
               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][input_type]"/>
        <input type='hidden' value="<?php echo $value['condition_name']; ?>"
               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_name]"/>
        <input type='hidden' value="<?php echo $value['condition_key']; ?>"
               name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][condition_key]"/>

        <div style='float:left'>
            <select onchange="baohan_bubaohan(this,<?php echo $value['condition_id']; ?>)" class='form-control'
                    style='width:80px' name="Rule[rule_condtion][<?php echo $value['condition_id']; ?>][oprerator]">
                <?php foreach (RuleCondtion::getOpreratorAsArray(Condition::CONDITION_INPUT_TYPE_RANGE) as $k => $v) { ?>
                    <?php if ($k == $value['oprerator']) { ?>
                        <option value="<?php echo $k; ?>" selected><?php echo $v; ?></option>
                    <?php } else { ?>
                        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                    <?php } ?>
                <?php } ?>
            </select>
        </div>
        <div id="<?php echo 'input_baohan_bubaohan_' . $value['condition_id']; ?>"
             style='float:left;padding-left:20px;'>
            <?php
            $start_rang_value = current($value['option_value']);
            next($value['option_value']);
            $end_rang_value = current($value['option_value']);
            ?>
            <input style='width:150px;float:left;margin-right:15px' type='text' class='form-control'
                   name="Rule[rule_condtion][<?php echo $value['condition_id'] ?>][value][]" placeholder='请输入开始范围'
                   value="<?php echo $start_rang_value; ?>">
            <input style='width:150px;float:left' type='text' class='form-control'
                   name="Rule[rule_condtion][<?php echo $value['condition_id'] ?>][value][]" placeholder='请输入结束范围'
                   value="<?php echo $end_rang_value; ?>">
            <div style='clear:both'></div>
        </div>
        <div style='clear:both'></div>
    </div>


<?php } ?>

<?php } ?>
</div>
</div>
<div style="with:100%;height:30px;padding-left:15px;line-height:30px;border-bottom:1px solid #333;border-top:1px solid #333;">
    规则信息
</div>
<div>
    <div style="width:100%;margin-top:10px">
        <div style="width:30%;float:left">
            <?php echo $form->field($model, 'rule_name'); ?>
            <input type="hidden" value="<?php echo $model->type; ?>" name="Rule[type]" id="rule_type"/>
        </div>
        <div style="width:70%;float:left">
            <?php echo $form->field($model, 'relation_id')->dropdownList($tagList, ['encodeSpaces' => true]); ?>
        </div>
        <div style="clear:both"></div>
    </div>

    <div style="width:100%">
        <div style="width:30%;float:left">
            <?php echo $form->field($model, 'sort_order')->textInput(['value' => $model->sort_order]); ?>
        </div>
        <div style="width:70%;float:left">
            <?php echo $form->field($model, 'priority')->textInput(['value' => $model->priority]); ?>
        </div>
        <div style="clear:both"></div>
    </div>

    <div style="width:100%">
        <div style="width:30%;float:left">
            <?php echo $form->field($model, 'mail_notify')->inline()->radioList($model->getMailNotifyList(), ['value' => $model->mail_notify]); ?>
        </div>
        <div style="width:70%;float:left">
            <?php echo $form->field($model, 'platform_code')->dropdownList($platformList, ['encodeSpaces' => true, 'onchange' => 'getAccountInfoByChangePlatformCode(this)']); ?>
        </div>
        <?php if ($model->type == Rule::RULE_TYPE_AUTO_ANSWER): ?>
        <div style="width:30%;float:left">
                <label class="control-label col-sm-3" for="rule-execute_id">触发条件</label>
                <div class="col-sm-6">
                    <select id="rule-execute_id" class="form-control" name="Rule[execute_id]" aria-required="true">
                        <option value="">请选择</option>
                        <?php foreach ($execute_infos as $key => $execute_info) {
                            if ($execute_info['status'] == 1) {
                                if ($model->execute_id == $key)
                                    echo "<option value='{$key}' selected='selected'>{$execute_info['name']}</option>";
                                else
                                    echo "<option value='{$key}'>{$execute_info['name']}</option>";
                            }
                        } ?>
                    </select>
                </div>
            </div>
            <div style="width:70%;float:left;">
                <div style="margin-left: 160px;width:40%;float:left">
                        <?php 
                            echo $form->field($model, 'is_timed')->dropdownList([1 => '按时间累加到点发送',2 => '指定时间点发送']);
                        ?>
                        </div>
                <div style="float:left;margin-left: -100px;margin-top: -7px;">
                <label class="control-label col-sm-12">触发后<input class="form-control" style="width: 50px;display: inline;"
                                                                onkeyup="this.value=this.value.replace(/\D/g,'')"
                                                                onafterpaste="this.value=this.value.replace(/\D/g,'')"
                                                                ng-pattern="/[^a-zA-Z]/" name="Rule[execute_day]"
                                                                value="<?php echo $model->execute_day; ?>">天<input
                            style="width: 50px;display: inline;" class="form-control" onkeyup="value=value.replace(/[^\d]/g,'') " ng-pattern="/[^a-zA-Z]/"
                            name="Rule[execute_hour]" value="<?php echo $model->execute_hour; ?>">时发送</label>
                </div>
            </div>
        <?php endif; ?>
        <div style="clear:both"></div>
    </div>

    <div style="width:100%">        
        <div style="width:30%;float:left">
            <?php echo $form->field($model, 'status')->dropdownList($model->getStatusList()); ?>
        </div>
        <div style="clear:both"></div>
    </div>
    <div style="width:100%">
        <div id="survival" style="display: <?php echo ($model->status == 2) ? 'block' : 'none';?>">
            <div style="width:50%;float:left">
                <?php echo $form->field($model, 'survival_str_time')->widget(DateTimePicker::classname(), [ 
                            'options' => ['placeholder' => ''], 
                            'pluginOptions' => [
                                'autoclose' => true, 
                                'todayHighlight' => true, 
                                'startDate' =>date('Y-m-d'), //设置今天之前的日期不能选择 
                        ]]); ?>
            </div>
            <div style="width:50%;float:left">
                <?php echo $form->field($model, 'survival_end_time')->widget(DateTimePicker::classname(), [ 
                            'options' => ['placeholder' => ''], 
                            'pluginOptions' => [
                                'autoclose' => true, 
                                'todayHighlight' => true, 
                                'startDate' =>date('Y-m-d'), //设置今天之前的日期不能选择 
                        ]]); ?>
            </div>
        </div>
        <div style="clear:both"></div>
    </div>
</div>
</div>
<div style="width:23%;height:780px;float:left;border:1px solid #333;overflow-y:scroll;border-radius: 6px;">
    <div style="width:100%;height:30px;line-height:30px;border-bottom:1px solid #333;padding-left:15px;">可选条件</div>
    <div>
        <?php foreach ($conditionData as $key => $value) { ?>
            <div style="height:30px;line-height:30px;margin-left:15px">
                <?php echo $value['group_name']; ?>
            </div>

            <div style="border:1px solid #333;margin:0px 15px 10px 15px;padding-left:10px">
                <?php if (count($value['condition'])) { ?>
                    <?php foreach ($value['condition'] as $ck => $cv) { ?>
                        <div class="checkbox">
                            <label>
                                <?php if (in_array($cv['id'], $rule_condition_data['condition_ids'])) { ?>
                                    <input type="checkbox" data-condition-key="<?php echo $cv['condition_key']; ?>"
                                           data-condition-name="<?php echo $cv['condition_name']; ?>"
                                           class="inlineCheckbox1" data-input-type="<?php echo $cv['input_type']; ?>"
                                           value="<?php echo $cv['id']; ?>" checked='checked'>
                                <?php } else { ?>
                                    <input type="checkbox" data-condition-key="<?php echo $cv['condition_key']; ?>"
                                           data-condition-name="<?php echo $cv['condition_name']; ?>"
                                           class="inlineCheckbox1" data-input-type="<?php echo $cv['input_type']; ?>"
                                           value="<?php echo $cv['id']; ?>">
                                <?php } ?>
                                <?php echo $cv['condition_name']; ?>
                            </label>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div style='height:50px;font-size:12px'>暂无条件！</div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    
    <div style="clear:both"></div>
            <div style="height:30px;line-height:30px;margin-left:15px">排除条件</div>
            <div style="border:1px solid #333;margin:0px 15px 10px 15px;padding-left:10px">
        <div class="checkbox">
            <?php $condition_by = $model->condition_by;
            if (!empty($condition_by)) {
                $condition_arr = json_decode($condition_by);
            }
            ?>
            <label>
                <input type="checkbox" class="inlineCheckbox_new" value="1" <?php if (in_array('feedback_negative', $condition_arr)) {
                    echo 'checked';
                } ?> data-condition-name="feedback_negative">
                排除已经留过中差评feedback订单
            </label>
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" class="inlineCheckbox_new" value="2" <?php if (in_array('feedback_positive', $condition_arr)) {
                    echo 'checked';
                } ?> data-condition-name="feedback_positive">
                排除已经留过好评feedback订单
            </label>
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" class="inlineCheckbox_new" value="3" <?php if (in_array('review_negative', $condition_arr)) {
                    echo 'checked';
                } ?> data-condition-name="review_negative">
                排除已经留过中差评Review订单
            </label>
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" class="inlineCheckbox_new" value="4" <?php if (in_array('review_positive', $condition_arr)) {
                    echo 'checked';
                } ?> data-condition-name="review_positive">
                排除已经留过好评Review订单
            </label>
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" class="inlineCheckbox_new" value="5" <?php if (in_array('dispute', $condition_arr)) {
                    echo 'checked';
                } ?> data-condition-name="dispute">
                排除已有纠纷订单
            </label>
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" class="inlineCheckbox_new" value="6" <?php if (strpos($condition_by, 'buyer_message') !== false) {
                    echo 'checked';
                } ?> data-condition-name="buyer_message">
                排除有往来邮件(Buyer message)的订单
            </label>
            <?php foreach ($condition_arr as $v) {
                if (strpos($v, 'buyer_message') !== false) {
                    $buyer_message = $v;
                }
            }
            $date_message = intval(explode('&', $buyer_message)[1]);
            ?>
            <label for=""><input type="text" name="buyer_message_day" onkeyup="validationNumber(this,30)" maxlength="90" value="<?php echo $date_message ?>">天内同一个买家只发送一封邮件</label>
        </div>
    </div>
</div>

<div style="clear:both"></div>
<div class="popup-footer">
    <button class="btn btn-primary ajax-submit" type="button">提交</button>
    <button class="btn btn-default close-button">关闭</button>
</div>
<?php
ActiveForm::end();
?>

<div class="modal fade" id="selLogisticsModal" tabindex="-1" role="dialog" aria-labelledby="selLogisticsModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel">买家选择运输方式</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col col-sm-12">
                        <label for="checkall"><font color="blue">全选</font><input type="checkbox" id="checkall1"></label>
                        <label for="checkrev"><font color="blue">反选</font><input type="checkbox" id="checkrev1"></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col col-sm-12 selLogisticsItem">

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" id="buyer_logistics" class="btn btn-primary">提交</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="selSiteInfoModal" tabindex="-1" role="dialog" aria-labelledby="selSiteInfoModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel">站点：</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col col-sm-12">
                        <label for="checkall"><font color="blue">全选</font><input type="checkbox" id="checkall2"></label>
                        <label for="checkrev"><font color="blue">反选</font><input type="checkbox" id="checkrev2"></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col col-sm-12 selSiteInfoItem">

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" id="site_info" class="btn btn-primary">提交</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="selWarehouseModal" tabindex="-1" role="dialog" aria-labelledby="selWarehouseModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel">指定仓库</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col col-sm-12">
                        <label for="checkall"><font color="blue">全选</font><input type="checkbox" id="checkall3"></label>
                        <label for="checkrev"><font color="blue">反选</font><input type="checkbox" id="checkrev3"></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col col-sm-12 selWarehouseItem">

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" id="warehouse_id" class="btn btn-primary">提交</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="selShipCodeModal" tabindex="-1" role="dialog" aria-labelledby="selShipCodeModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel">指定邮寄方式</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col col-sm-12">
                        <label for="checkall"><font color="blue">全选</font><input type="checkbox" id="checkall4"></label>
                        <label for="checkrev"><font color="blue">反选</font><input type="checkbox" id="checkrev4"></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col col-sm-12 selShipCodeItem">

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" id="ship_code" class="btn btn-primary">提交</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="selCityAreaModal" tabindex="-1" role="dialog" aria-labelledby="selCityAreaModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel">指定国家或区域</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col col-sm-12">
                        <label for="checkall"><font color="blue">全选</font><input type="checkbox" id="checkall5"></label>
                        <label for="checkrev"><font color="blue">反选</font><input type="checkbox" id="checkrev5"></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col col-sm-12 selCityAreaItem">

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" id="ship_country" class="btn btn-primary">提交</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="selAccountIdModal" tabindex="-1" role="dialog" aria-labelledby="selAccountIdModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel">所属账号</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col col-sm-12">
                        <label for="checkall"><font color="blue">全选</font><input type="checkbox" id="checkall6"></label>
                        <label for="checkrev"><font color="blue">反选</font><input type="checkbox" id="checkrev6"></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col col-sm-12 selAccountIdItem">

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" id="account_id" class="btn btn-primary">提交</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    function validationNumber(e, num) {
        var regu = /^[0-9]+\.?[0-9]*$/;
        if (e.value != "") {
            if (!regu.test(e.value)) {
                layer.msg('请输入正确的数字', {icon: 5});
                e.value = e.value.substring(0, e.value.length - 1);
                e.focus();
            } else {
                if (e.value > 90) {
                    layer.msg('日期在1~99之内', {icon: 5});
                    e.value = e.value.substring(0, e.value.length - 1);
                    e.focus();
                }
                if (num == 0) {
                    if (e.value.indexOf('.') > -1) {
                        e.value = e.value.substring(0, e.value.length - 1);
                        e.focus();
                    }
                }
                if (e.value.indexOf('.') > -1) {
                    if (e.value.split('.')[1].length > num) {
                        e.value = e.value.substring(0, e.value.length - 1);
                        e.focus();
                    }
                }
            }
        }
    }

    $(function () {
        $(".inlineCheckbox_new").click(function () {
            var element_id = "#container" + $(this).val();
            if (!$(this).prop('checked')) {
                $(element_id).remove();
                return;
            }
            var condition_id = $(this).val();
            var condition_name = $(this).attr('data-condition-name');
            if (condition_id == 6) {
                var buyer_message_day = $('input[name=buyer_message_day]').val();
            } else {
                var buyer_message_day = '';
            }
            append_new_condition_option_detail(condition_id, condition_name, buyer_message_day);
        });

        function append_new_condition_option_detail(condition_id, condition_name, buyer_message_day) {
            var html = "<div id='container" + condition_id + "' style='margin:10px 0px'>";
            var html = html + "<div style='display:;width:160px;float:left;height:34px;line-height:34px;text-align:left'>" + condition_name + ":　</div>";
            var html = html + "<input type='hidden' value='" + condition_name + "' name='Rule[new_rule_condition][" + condition_id + "][condition_name]'/>";
            var html = html + "<input type='hidden' value='" + buyer_message_day + "' name='Rule[new_rule_condition][" + condition_id + "][buyer_message_day]'/>";
            var html = html + "</div><div style='clear:both'></div></div>";
            $("#new_condition").append(html);
        }

        $("#checkall1").click(function () {
            var obj = $("input[name^='logistics']");
            if ($(this).is(":checked")) {
                /*obj.each(function() {
                    $(this).attr("checked", "checked");
                    $(this)[0].checked = true;
                });*/
                obj.prop("checked", "checked")
            } else {
                /* obj.each(function() {
                     $(this).removeAttr("checked");
                     $(this)[0].checked = false;
                 });*/
                obj.prop("checked", "")
            }
        });

        $("input[name^='logistics']").each(function () {
            if (!$(this).is(":checked")) {
                /* $(this).removeAttr("checked");
                 $(this)[0].checked = false;*/
                obj.prop("checked", "")
            }
        });
        $("input[name^='site_info']").each(function () {
            if (!$(this).is(":checked")) {
                obj.prop("checked", "")
            }
        });
        $("input[name^='warehouse']").each(function () {
            if (!$(this).is(":checked")) {
                obj.prop("checked", "")
            }
        });
        $("input[name^='ship_code']").each(function () {
            if (!$(this).is(":checked")) {
                obj.prop("checked", "")
            }
        });
        $("input[name^='city_area']").each(function () {
            if (!$(this).is(":checked")) {
                obj.prop("checked", "")
            }
        });
        $("input[name^='account_id']").each(function () {
            if (!$(this).is(":checked")) {
                obj.prop("checked", "")
            }
        });
        $("#checkall2").click(function () {
            var obj = $("input[name^='site_info']");
            if ($(this).is(":checked")) {
                obj.each(function () {
                    $(this).attr("checked", "checked");
                    $(this)[0].checked = true;
                });
            } else {
                obj.each(function () {
                    $(this).removeAttr("checked");
                    $(this)[0].checked = false;
                });
            }
        });
        $("#checkall3").click(function () {
            var obj = $("input[name^='warehouse']");
            if ($(this).is(":checked")) {
                obj.each(function () {
                    $(this).attr("checked", "checked");
                    $(this)[0].checked = true;
                });
            } else {
                obj.each(function () {
                    $(this).removeAttr("checked");
                    $(this)[0].checked = false;
                });
            }
        });
        $("#checkall4").click(function () {
            var obj = $("input[name^='ship_code']");
            if ($(this).is(":checked")) {
                obj.each(function () {
                    $(this).attr("checked", "checked");
                    $(this)[0].checked = true;
                });
            } else {
                obj.each(function () {
                    $(this).removeAttr("checked");
                    $(this)[0].checked = false;
                });
            }
        });
        $("#checkall5").click(function () {
            var obj = $("input[name^='city_area']");
            if ($(this).is(":checked")) {
                obj.each(function () {
                    $(this).attr("checked", "checked");
                    $(this)[0].checked = true;
                });
            } else {
                obj.each(function () {
                    $(this).removeAttr("checked");
                    $(this)[0].checked = false;
                });
            }
        });
        $("#checkall6").click(function () {
            var obj = $("input[name^='account_id']");
            if ($(this).is(":checked")) {
                obj.each(function () {
                    $(this).attr("checked", "checked");
                    $(this)[0].checked = true;
                });
            } else {
                obj.each(function () {
                    $(this).removeAttr("checked");
                    $(this)[0].checked = false;
                });
            }
        });

        $("#checkrev1").click(function () {
            //实现反选功能
            $("input[name^='logistics']").each(function () {
                this.checked = !this.checked;
            });
            return false;
        });
        $("#checkrev2").click(function () {
            $("input[name^='site_info']").each(function () {
                this.checked = !this.checked;
            });
            return false;
        });
        $("#checkrev3").click(function () {
            $("input[name^='warehouse']").each(function () {
                this.checked = !this.checked;
            });
            return false;
        });
        $("#checkrev4").click(function () {
            $("input[name^='ship_code']").each(function () {
                this.checked = !this.checked;
            });
            return false;
        });
        $("#checkrev5").click(function () {
            $("input[name^='city_area']").each(function () {
                this.checked = !this.checked;
            });
            return false;
        });
        $("#checkrev6").click(function () {
            $("input[name^='account_id']").each(function () {
                this.checked = !this.checked;
            });
            return false;
        });
        //勾选条件处理函数
        $(".inlineCheckbox1").click(function () {
            var element_id = "#container" + $(this).val();
            var element_ids = "#containers" + $(this).val();
            if (!$(this).prop('checked')) {
                $(element_id).remove();
                $(element_ids).remove();
                return;
            }
            var input_type = $(this).attr('data-input-type');
            var condition_id = $(this).val();
            var condition_name = $(this).attr('data-condition-name');
            var condition_key = $(this).attr('data-condition-key');
            var rule_platform_code = $("#rule-platform_code").val();
            append_condition_option_detail(input_type, condition_id, condition_name, condition_key, rule_platform_code);
        });

        //添加所选项
        $('#ship_country').on('click', function () {
            var insertHtml = "";
            var checkedVal = $('.inlineCheckbox1[data-condition-name="指定国家或区域"]:checked').val();
            //console.log($("input[name^='city_area']:not(:checked)").length);
            if (!($("input[name^='city_area']:not(:checked)").length)) {
                insertHtml += "<button type='button' class='btn btn-default click_remove'>全部国家或区域<span class='glyphicon glyphicon-remove'></span></button>";
                $("input[name^='city_area']:checked").each(function () {


                    insertHtml += "<input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >";

                });
            } else {
                $("input[name^='city_area']:checked").each(function () {
                    insertHtml += "<button type='button' class='btn btn-default click_remove selItem'> \
                            " + $(this).parent().text() + "<span class='glyphicon glyphicon-remove'></span> \
                              <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >\
                            </button>";
                });
            }
            $('#selCityAreaModal').modal('hide')
            $("#selCityAreaItem").html(insertHtml);

            return false;
        });

        $('#buyer_logistics').on('click', function () {
            var insertHtml = "";
            var checkedVal = $('.inlineCheckbox1[data-condition-name="买家选择运输方式"]:checked').val();
            if (!($("input[name^='logistics']:not(:checked)").length)) {
                insertHtml += "<button type='button' class='btn btn-default click_remove'>全部运输方式<span class='glyphicon glyphicon-remove'></span></button>";
                $("input[name^='logistics']:checked").each(function () {


                    insertHtml += "<input type='hidden' name='Rule[rule_condtion][[" + checkedVal + "][value][]' value='" + $(this).val() + "' >";

                });
            } else {
                $("input[name^='logistics']:checked").each(function () {
                    insertHtml += "<button type='button' class='btn btn-default click_remove selItem'> \
                            " + $(this).val() + "<span class='glyphicon glyphicon-remove'></span> \
                                <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >\
                            </button>";
                });
            }


            $('#selLogisticsModal').modal('hide')
            $("#selLogisticsItem").html(insertHtml);

            return false;
        });

        $('#site_info').on('click', function () {
            var insertHtml = "";
            var checkedVal = $('.inlineCheckbox1[data-condition-name="站点"]:checked').val();
            if (!($("input[name^='site_info']:not(:checked)").length)) {
                insertHtml += "<button type='button' class='btn btn-default click_remove'>全部站点<span class='glyphicon glyphicon-remove'></span></button>";
                $("input[name^='site_info']:checked").each(function () {
                    insertHtml += " <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >";
                });
            } else {
                $("input[name^='site_info']:checked").each(function () {
                    insertHtml += "<button type='button' class='btn btn-default click_remove selItem'> \
                            " + $(this).parent().text() + "<span class='glyphicon glyphicon-remove'></span> \
                              <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >\
                            </button>";
                });
            }
            $('#selSiteInfoModal').modal('hide')
            $("#selSiteInfoItem").html(insertHtml);

            return false;
        });

        $('#warehouse_id').on('click', function () {
            var insertHtml = "";
            var checkedVal = $('.inlineCheckbox1[data-condition-name="指定仓库"]:checked').val();
            if (!($("input[name^='warehouse']:not(:checked)").length)) {
                insertHtml += "<button type='button' class='btn btn-default click_remove'>全部仓库<span class='glyphicon glyphicon-remove'></span></button>";
                $("input[name^='warehouse']:checked").each(function () {
                    insertHtml += " <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >";
                });
            } else {
                $("input[name^='warehouse']:checked").each(function () {
                    insertHtml += "<button type='button' class='btn btn-default click_remove selItem'> \
                            " + $(this).parent().text() + "<span class='glyphicon glyphicon-remove'></span> \
                              <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >\
                            </button>";
                });
            }
            $('#selWarehouseModal').modal('hide')
            $("#selWarehouseItem").html(insertHtml);

            return false;
        });

        $('#ship_code').on('click', function () {
            var insertHtml = "";
            var checkedVal = $('.inlineCheckbox1[data-condition-name="指定邮寄方式"]:checked').val();
            if (!($("input[name^='ship_code']:not(:checked)").length)) {
                insertHtml += "<button type='button' class='btn btn-default click_remove'>全部邮寄方式<span class='glyphicon glyphicon-remove'></span></button>";
                $("input[name^='ship_code']:checked").each(function () {
                    insertHtml += " <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >";
                });
            } else {
                $("input[name^='ship_code']:checked").each(function () {
                    insertHtml += "<button type='button' class='btn btn-default click_remove selItem'> \
                            " + $(this).parent().text() + "<span class='glyphicon glyphicon-remove'></span> \
                              <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >\
                            </button>";
                });
            }
            $('#selShipCodeModal').modal('hide')
            $("#selShipCodeItem").html(insertHtml);

            return false;
        });

        $('#account_id').on('click', function () {
            var insertHtml = "";
            var checkedVal = $('.inlineCheckbox1[data-condition-name="所属账号"]:checked').val();
            if (!($("input[name^='account_id']:not(:checked)").length)) {
                insertHtml += "<button type='button' class='btn btn-default click_remove'>全部所属账号<span class='glyphicon glyphicon-remove'></span></button>";
                $("input[name^='account_id']:checked").each(function () {
                    insertHtml += " <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >";
                });
            } else {
                $("input[name^='account_id']:checked").each(function () {
                    insertHtml += "<button type='button' class='btn btn-default click_remove selItem'> \
                            " + $(this).parent().text() + "<span class='glyphicon glyphicon-remove'></span> \
                              <input type='hidden' name='Rule[rule_condtion][" + checkedVal + "][value][]' value='" + $(this).val() + "' >\
                            </button>";

                });
            }
            $('#selAccountIdModal').modal('hide')
            $("#selAccountIdItem").html(insertHtml);

            return false;
        });


        $("#platform-form").on("click", "span.glyphicon-remove", function () {
            $(this).parent().remove();
            return false;
        });


        $("a[data-target='selLogisticsModal']").on("click", function () {
            var condition_key = $('.inlineCheckbox1[data-condition-name="买家选择运输方式"]:checked').attr('data-condition-key');
            var rule_platform_code = $("#rule-platform_code").val();
            var checkedVal = $('.inlineCheckbox1[data-condition-name="买家选择运输方式"]:checked').val();
            var checkeds = new Array;
            $("input[name^='Rule[rule_condtion][" + checkedVal + "][value]']").each(function (i) {
                checkeds[i] = $(this).val();
            });
            $.ajax({
                url: "<?php echo \yii\helpers\Url::toRoute('/systems/rule/getopreratordata');?>",
                async: false,
                dataType: 'json',
                type: 'get',
                data: {
                    condition_id: checkedVal,
                    input_type: '3',
                    condition_key: condition_key,
                    rule_platform_code: rule_platform_code,
                    type: "<?echo $model->type;?>"
                },
                success: function (datas) {
                    var selLogisticsItem = '';
                    var datas = datas.option_data;

                    for (var n in datas) {
                        var value = datas[n];
                        selLogisticsItem += "<label><input type='checkbox' name='logistics[]' value='" + value + "'>" + value + "</label>";
                    }

                    selLogisticsItem = $(selLogisticsItem);

                    for (var ix in checkeds) {
                        var obj = selLogisticsItem.find("input[name^='logistics'][value='" + checkeds[ix] + "']");
                        //obj[0].checked = true;
                        //obj.attr("checked", "checked");
                        obj.prop("checked", true);
                    }
                    selLogisticsItem.find("input[name^='logistics']").on("click", function () {
                        if (!$(this).is(":checked")) {
                            $("#checkall1").removeAttr("checked");
                            $("#checkall1")[0].checked = false;
                        }
                    });
                    $('.selLogisticsItem').html(selLogisticsItem);
                    $("#selLogisticsModal").modal("show");
                }
            });
        });

        $("a[data-target='selSiteInfoModal']").on("click", function () {
            //alert("<?php echo Condition::CONDITION_KEY_INFO_SITE;?>");
            var condition_key = $('.inlineCheckbox1[data-condition-name="站点"]:checked').attr('data-condition-key');
            var rule_platform_code = $("#rule-platform_code").val();
            var checkedVal = $('.inlineCheckbox1[data-condition-name="站点"]:checked').val();
            var checkeds = new Array;
            $("input[name^='Rule[rule_condtion][" + checkedVal + "][value]']").each(function (i) {
                checkeds[i] = $(this).val();
            });


            $.ajax({
                url: "<?php echo \yii\helpers\Url::toRoute('/systems/rule/getopreratordata');?>",
                async: false,
                dataType: 'json',
                type: 'get',
                data: {
                    condition_id: checkedVal,
                    input_type: '3',
                    condition_key: condition_key,
                    rule_platform_code: rule_platform_code,
                    type: "<?echo $model->type;?>"
                },
                success: function (datas) {
                    var selSiteInfoItem = '';
                    var datas = datas.option_data;
                    for (var n in datas) {
                        var value = datas[n];
                        selSiteInfoItem += "<label><input type='checkbox' name='site_info[]' value='" + n + "'>" + value + "</label>";
                    }
                    selSiteInfoItem = $(selSiteInfoItem);
                    for (var ix in checkeds) {
                        var obj = selSiteInfoItem.find("input[name^='site_info'][value='" + checkeds[ix] + "']");
                        //console.log(obj);
                        //obj[0].checked = true;
                        //obj.attr("checked", "checked");
                        obj.prop("checked", true);
                    }
                    selSiteInfoItem.find("input[name^='site_info']").on("click", function () {
                        if (!$(this).is(":checked")) {
                            $("#checkall2").removeAttr("checked");
                            $("#checkall2")[0].checked = false;
                        }
                    });
                    $('.selSiteInfoItem').html(selSiteInfoItem);
                    $("#selSiteInfoModal").modal("show");
                }
            });
        });

        $("a[data-target='selWarehouseModal']").on("click", function () {
            var condition_key = $('.inlineCheckbox1[data-condition-name="指定仓库"]:checked').attr('data-condition-key');
            var rule_platform_code = $("#rule-platform_code").val();
            var checkedVal = $('.inlineCheckbox1[data-condition-name="指定仓库"]:checked').val();
            var checkeds = new Array;
            $("input[name^='Rule[rule_condtion][" + checkedVal + "][value]']").each(function (i) {
                checkeds[i] = $(this).val();
            });
            $.ajax({
                url: "<?php echo \yii\helpers\Url::toRoute('/systems/rule/getopreratordata');?>",
                async: false,
                dataType: 'json',
                type: 'get',
                data: {
                    condition_id: checkedVal,
                    input_type: '3',
                    condition_key: condition_key,
                    rule_platform_code: rule_platform_code,
                    type: "<?echo $model->type;?>"
                },
                success: function (datas) {
                    var selWarehouseItem = '';
                    var datas = datas.option_data;
                    for (var n in datas) {
                        var value = datas[n];
                        selWarehouseItem += "<label><input type='checkbox' name='warehouse[]' value='" + n + "'>" + value + "</label>";
                    }
                    selWarehouseItem = $(selWarehouseItem);
                    for (var ix in checkeds) {
                        var obj = selWarehouseItem.find("input[name^='warehouse'][value='" + checkeds[ix] + "']");
                        //console.log(obj);
                        //obj[0].checked = true;
                        //obj.attr("checked", "checked");
                        obj.prop("checked", true);
                    }
                    selWarehouseItem.find("input[name^='warehouse']").on("click", function () {
                        if (!$(this).is(":checked")) {
                            $("#checkall3").removeAttr("checked");
                            $("#checkall3")[0].checked = false;
                        }
                    });
                    $('.selWarehouseItem').html(selWarehouseItem);
                    $("#selWarehouseModal").modal("show");
                }
            });
        });

        $("a[data-target='selShipCodeModal']").on("click", function () {
            var condition_key = $('.inlineCheckbox1[data-condition-name="指定邮寄方式"]:checked').attr('data-condition-key');
            var rule_platform_code = $("#rule-platform_code").val();
            var checkedVal = $('.inlineCheckbox1[data-condition-name="指定邮寄方式"]:checked').val();
            var checkeds = new Array;
            $("input[name^='Rule[rule_condtion][" + checkedVal + "][value]']").each(function (i) {
                checkeds[i] = $(this).val();
            });

            $.ajax({
                url: "<?php echo \yii\helpers\Url::toRoute('/systems/rule/getopreratordata');?>",
                async: false,
                dataType: 'json',
                type: 'get',
                data: {
                    condition_id: checkedVal,
                    input_type: '3',
                    condition_key: condition_key,
                    rule_platform_code: rule_platform_code,
                    type: "<?echo $model->type;?>"
                },
                success: function (datas) {
                    var selShipCodeItem = '';
                    var datas = datas.option_data;
                    for (var n in datas) {
                        var value = datas[n];
                        selShipCodeItem += "<label><input type='checkbox' name='ship_code[]' value='" + n + "'>" + value + "</label>";
                    }

                    selShipCodeItem = $(selShipCodeItem);
                    for (var ix in checkeds) {
                        var obj = selShipCodeItem.find("input[name^='ship_code'][value='" + checkeds[ix] + "']");
                        //console.log(obj);
                        //obj[0].checked = true;
                        //obj.attr("checked", "checked");
                        obj.prop("checked", true);
                    }
                    selShipCodeItem.find("input[name^='ship_code']").on("click", function () {
                        if (!$(this).is(":checked")) {
                            $("#checkall4").removeAttr("checked");
                            $("#checkall4")[0].checked = false;
                        }
                    });
                    $('.selShipCodeItem').html(selShipCodeItem);
                    $("#selShipCodeModal").modal("show");
                }
            });
        });

        $("a[data-target='selCityAreaModal']").on("click", function () {
            var condition_key = $('.inlineCheckbox1[data-condition-name="指定国家或区域"]:checked').attr('data-condition-key');
            var rule_platform_code = $("#rule-platform_code").val();
            var checkedVal = $('.inlineCheckbox1[data-condition-name="指定国家或区域"]:checked').val();
            var checkeds = new Array;
            $("input[name^='Rule[rule_condtion][" + checkedVal + "][value]']").each(function (i) {
                checkeds[i] = $(this).val();
            });
            $.ajax({
                url: "<?php echo \yii\helpers\Url::toRoute('/systems/rule/getopreratordata');?>",
                async: false,
                dataType: 'json',
                type: 'get',
                data: {
                    condition_id: checkedVal,
                    input_type: '3',
                    condition_key: condition_key,
                    rule_platform_code: rule_platform_code,
                    type: "<?echo $model->type;?>"
                },
                success: function (datas) {
                    var selCityAreaItem = '';
                    var datas = datas.option_data;
                    for (var n in datas) {
                        var value = datas[n];
                        selCityAreaItem += "<label><input type='checkbox' name='city_area[]' value='" + n + "'>" + value + "</label>";
                    }
                    selCityAreaItem = $(selCityAreaItem);

                    for (var ix in checkeds) {
                        var obj = selCityAreaItem.find("input[name^='city_area'][value='" + checkeds[ix] + "']");
                        //console.log(obj);
                        //obj[0].checked = true;
                        //obj.attr("checked", "checked");
                        obj.prop("checked", true);
                    }
                    selCityAreaItem.find("input[name^='city_area']").on("click", function () {
                        if (!$(this).is(":checked")) {
                            $("#checkall5").removeAttr("checked");
                            $("#checkall5")[0].checked = false;
                        }
                    });
                    $('.selCityAreaItem').html(selCityAreaItem);
                    $("#selCityAreaModal").modal("show");
                }
            });
        });

        $("a[data-target='selAccountIdModal']").on("click", function () {
            var condition_key = $('.inlineCheckbox1[data-condition-name="所属账号"]:checked').attr('data-condition-key');
            var rule_platform_code = $("#rule-platform_code").val();
            var checkedVal = $('.inlineCheckbox1[data-condition-name="所属账号"]:checked').val();
            var checkeds = new Array;
            $("input[name^='Rule[rule_condtion][" + checkedVal + "][value]']").each(function (i) {
                checkeds[i] = $(this).val();
            });

            $.ajax({
                url: "<?php echo \yii\helpers\Url::toRoute('/systems/rule/getopreratordata');?>",
                async: false,
                dataType: 'json',
                type: 'get',
                data: {
                    condition_id: checkedVal,
                    input_type: '3',
                    condition_key: condition_key,
                    rule_platform_code: rule_platform_code,
                    type: "<?echo $model->type;?>"
                },
                success: function (datas) {
                    var selAccountIdItem = '';
                    var datas = datas.option_data;
                    for (var n in datas) {
                        var value = datas[n].id;
                        selAccountIdItem += "<label><input type='checkbox' name='account_id[]' value='" + value + "'>" + datas[n].account_name + "</label>";
                    }
                    selAccountIdItem = $(selAccountIdItem);

                    for (var ix in checkeds) {
                        var obj = selAccountIdItem.find("input[name^='account_id'][value='" + checkeds[ix] + "']");
                        //console.log(obj);
                        //obj[0].checked = true;
                        //obj.attr("checked", "checked");
                        obj.prop("checked", true);
                    }
                    selAccountIdItem.find("input[name^='account_id']").on("click", function () {
                        if (!$(this).is(":checked")) {
                            $("#checkall6").removeAttr("checked");
                            $("#checkall6")[0].checked = false;
                        }
                    });
                    $('.selAccountIdItem').html(selAccountIdItem);
                    $("#selAccountIdModal").modal("show");
                }
            });
        });

    });

    function append_condition_option_detail(input_type, condition_id, condition_name, condition_key, rule_platform_code) {
        $.ajax({
            url: "<?php echo \yii\helpers\Url::toRoute('/systems/rule/getopreratordata');?>",
            async: false,
            dataType: 'json',
            type: 'get',
            data: {
                condition_id: condition_id,
                input_type: input_type,
                condition_key: condition_key,
                rule_platform_code: rule_platform_code,
                type: "<?echo $model->type;?>"
            },
            success: function (result) {
                switch (result.input_type) {
                    case '1':
                        append_condition_option_input(result, condition_id, condition_name, condition_key);
                        break;
                    case '2':
                        append_condition_option_radio(result, condition_id, condition_name, condition_key);
                        break;
                    case '3':
                        //append_condition_option_select(result,condition_id,condition_name,condition_key);
                        append_condition_option_a(result, condition_id, condition_name, condition_key);
                        break;
                    case '4':
                        append_condition_option_checkbox(result, condition_id, condition_name, condition_key);
                        break;
                    case '6':
                        append_condition_option_a(result, condition_id, condition_name, condition_key);
                        break;
                    case '5':
                        append_condition_option_range(result, condition_id, condition_name, condition_key);
                        break;
                }
            },
            error: function (xhr, status, error) {
                alert(error);
            }
        });
    }

    //构造a 元素
    function append_condition_option_a(data, condition_id, condition_name, condition_key) {
        //console.log(data,condition_id,condition_name,condition_key);
        var html = "<div id='containers" + condition_id + "' style='margin:10px 5px'>";
        var html = html + "<div style='width:160px;float:left;height:34px;line-height:34px;text-align:left'>" + condition_name + ":　</div>";
        var html = html + "<input type='hidden' value='" + data.input_type + "' name='Rule[rule_condtion][" + condition_id + "][input_type]'/>";
        var html = html + "<input type='hidden' value='" + condition_name + "' name='Rule[rule_condtion][" + condition_id + "][condition_name]'/>";
        var html = html + "<input type='hidden' value='" + condition_key + "' name='Rule[rule_condtion][" + condition_id + "][condition_key]'/>";
        var html = html + "<div style='float:left'><select class='form-control' style='width:80px' name='Rule[rule_condtion][" + condition_id + "][oprerator]'>";
        var obj = data.oprerator_data;
        for (var n in obj) {
            var html = html + "<option value='" + n + "'>" + obj[n] + "</option>";
        }
        var html = html + "</select></div><div style='float:left;padding-left:20px'>";

        if (condition_key == "<?php echo Condition::CONDITION_KEY_BYUER_OPTION_LOGISTICS;?>") {
            html += "<a href='#' style='width:160px;float:left;height:34px;line-height:34px;text-align:left' id='container" + condition_id + "' data-toggle='modal' data-target='selLogisticsModal' >买家选择运输方式:</a>";
            html += "</div><div style='clear:both'></div><div id='selLogisticsItem'></div><div>";


            var selLogisticsItem = '';
            var datas = data.option_data;
            for (var n in datas) {
                var value = datas[n];
                selLogisticsItem += "<label><input type='checkbox' name='logistics[]' value='" + value + "'>" + value + "</label>";
            }
            selLogisticsItem = $(selLogisticsItem);
            selLogisticsItem.find("input[name^='logistics']").on("click", function () {
                if (!$(this).is(":checked")) {
                    $("#checkall1").removeAttr("checked");
                    $("#checkall1")[0].checked = false;
                }
            });
            $('.selLogisticsItem').html(selLogisticsItem);

            var obj = $(html);
            obj.find("a[data-target='selLogisticsModal']").on("click", function () {
                $("#selLogisticsModal").modal("show");
            });
        } else if (condition_key == "<?php echo Condition::CONDITION_KEY_PRODUCT_SITE;?>" || condition_key == "<?php echo Condition::CONDITION_KEY_INFO_SITE;?>") {
            html += "<a href='#' style='width:160px;float:left;height:34px;line-height:34px;text-align:left' id='container" + condition_id + "' data-toggle='modal' data-target='selSiteInfoModal' >站点:</a>";
            html += "</div><div style='clear:both'></div><div id='selSiteInfoItem'></div><div>";

            var selSiteInfoItem = '';
            var obj = data.option_data;
            for (var n in obj) {
                var value = obj[n];
                selSiteInfoItem += "<label><input type='checkbox' name='site_info[]' value='" + n + "'>" + value + "</label>";
            }
            selSiteInfoItem = $(selSiteInfoItem);
            selSiteInfoItem.find("input[name^='site_info']").on("click", function () {
                if (!$(this).is(":checked")) {
                    $("#checkall2").removeAttr("checked");
                    $("#checkall2")[0].checked = false;
                }
            });
            $('.selSiteInfoItem').html(selSiteInfoItem);

            var obj = $(html);
            obj.find("a[data-target='selSiteInfoModal']").on("click", function () {
                $("#selSiteInfoModal").modal("show");
            });
        } else if (condition_key == "<?php echo Condition::CONDITION_KEY_WAREHOUSE_ID;?>") {
            html += "<a href='#' style='width:160px;float:left;height:34px;line-height:34px;text-align:left' id='container" + condition_id + "' data-toggle='modal' data-target='selWarehouseModal' >指定仓库:</a>";
            html += "</div><div style='clear:both'></div><div id='selWarehouseItem'></div><div>";

            var selWarehouseItem = '';
            var datas = data.option_data;
            for (var n in datas) {
                var value = datas[n];
                selWarehouseItem += "<label><input type='checkbox' name='warehouse[]' value='" + n + "'>" + value + "</label>";
            }
            selWarehouseItem = $(selWarehouseItem);
            selWarehouseItem.find("input[name^='warehouse']").on("click", function () {
                if (!$(this).is(":checked")) {
                    $("#checkall3").removeAttr("checked");
                    $("#checkall3")[0].checked = false;
                }
            });
            $('.selWarehouseItem').html(selWarehouseItem);

            var obj = $(html);
            obj.find("a[data-target='selWarehouseModal']").on("click", function () {
                $("#selWarehouseModal").modal("show");
            });
        } else if (condition_key == "<?php echo Condition::CONDITION_KEY_SHIP_CODE;?>") {
            html += "<a href='#' style='width:160px;float:left;height:34px;line-height:34px;text-align:left' id='container" + condition_id + "' data-toggle='modal' data-target='selShipCodeModal' >指定邮寄方式:</a>";
            html += "</div><div style='clear:both'></div><div id='selShipCodeItem'></div><div>";

            var selShipCodeItem = '';
            var datas = data.option_data;
            for (var n in datas) {
                var value = datas[n];
                selShipCodeItem += "<label><input type='checkbox' name='ship_code[]' value='" + n + "'>" + value + "</label>";
            }
            selShipCodeItem = $(selShipCodeItem);
            selShipCodeItem.find("input[name^='ship_code']").on("click", function () {
                if (!$(this).is(":checked")) {
                    $("#checkall4").removeAttr("checked");
                    $("#checkall4")[0].checked = false;
                }
            });
            $('.selShipCodeItem').html(selShipCodeItem);

            var obj = $(html);
            obj.find("a[data-target='selShipCodeModal']").on("click", function () {
                $("#selShipCodeModal").modal("show");
            });
        } else if (condition_key == "<?php echo Condition::CONDITION_KEY_SHIP_COUNTRY;?>") {
            html += "<a href='#' style='width:160px;float:left;height:34px;line-height:34px;text-align:left' id='container" + condition_id + "' data-toggle='modal' data-target='selCityAreaModal' >指定国家或区域:</a>";
            html += "</div><div style='clear:both'></div><div id='selCityAreaItem'></div><div>";

            var selCityAreaItem = '';
            var datas = data.option_data;
            for (var n in datas) {
                var value = datas[n];
                selCityAreaItem += "<label><input type='checkbox' name='city_area[]' value='" + n + "'>" + value + "</label>";
            }
            selCityAreaItem = $(selCityAreaItem);
            selCityAreaItem.find("input[name^='city_area']").on("click", function () {
                if (!$(this).is(":checked")) {
                    $("#checkall5").removeAttr("checked");
                    $("#checkall5")[0].checked = false;
                }
            });
            $('.selCityAreaItem').html(selCityAreaItem);

            var obj = $(html);
            obj.find("a[data-target='selCityAreaModal']").on("click", function () {
                $("#selCityAreaModal").modal("show");
            });
        } else if (condition_key == "<?php echo Condition::CONDITION_KEY_ACCOUNT;?>" || condition_key == "<?php echo Condition::CONDITION_KEY_ORDER_ACCOUNT?>") {
            html += "<a href='#' style='width:160px;float:left;height:34px;line-height:34px;text-align:left' id='container" + condition_id + "' data-toggle='modal' data-target='selAccountIdModal' >所属账号:</a>";
            html += "</div><div style='clear:both'></div><div id='selAccountIdItem'></div><div>";

            var selAccountIdItem = '';
            var obj = data.option_data;
            for (var n in obj) {
                var value = obj[n].id;
                selAccountIdItem += "<label><input type='checkbox' name='account_id[]' value='" + obj[n].account_name + "'>" + obj[n].account_name + "</label>";
            }
            selAccountIdItem = $(selAccountIdItem);
            selAccountIdItem.find("input[name^='account_id']").on("click", function () {
                if (!$(this).is(":checked")) {
                    $("#checkall6").removeAttr("checked");
                    $("#checkall6")[0].checked = false;
                }
            });
            $('.selAccountIdItem').html(selAccountIdItem);

            var obj = $(html);
            obj.find("a[data-target='selAccountIdModal']").on("click", function () {
                $("#selAccountIdModal").modal("show");
            });

        }

        $("#condition_option_container").append(obj);
    }

    //构造input元素
    function append_condition_option_input(data, condition_id, condition_name, condition_key) {

        var html = "<div id='container" + condition_id + "' style='margin:10px 5px'>";
        var html = html + "<div style='width:160px;float:left;height:34px;line-height:34px;text-align:left'>" + condition_name + ":　</div>";
        var html = html + "<input type='hidden' value='" + data.input_type + "' name='Rule[rule_condtion][" + condition_id + "][input_type]'/>";
        var html = html + "<input type='hidden' value='" + condition_name + "' name='Rule[rule_condtion][" + condition_id + "][condition_name]'/>";
        var html = html + "<input type='hidden' value='" + condition_key + "' name='Rule[rule_condtion][" + condition_id + "][condition_key]'/>";
        var html = html + "<div style='float:left'><select onchange='baohan_bubaohan(this," + condition_id + ")' class='form-control' style='width:80px' name='Rule[rule_condtion][" + condition_id + "][oprerator]'>";
        var obj = data.oprerator_data;
        for (var n in obj) {
            var html = html + "<option value='" + n + "'>" + obj[n] + "</option>";
        }
        var html = html + "</select></div><div id='input_baohan_bubaohan_" + condition_id + "' style='width:315px;float:left;padding-left:20px;'>";
        var html = html + "<input style='width:315px' type='text' class='form-control' name='Rule[rule_condtion][" + condition_id + "][value]' placeholder='请输入选项值'>";
        var html = html + "</div><div style='clear:both'></div>";

        $("#condition_option_container").append(html);
    }

    //构造radio元素
    function append_condition_option_radio(data, condition_id, condition_name, condition_key) {
        var html = "<div id='container" + condition_id + "' style='margin:10px 5px'>";
        var html = html + "<div style='width:160px;float:left;height:34px;line-height:34px;text-align:left'>" + condition_name + ":　</div>";
        var html = html + "<input type='hidden' value='" + data.input_type + "' name='Rule[rule_condtion][" + condition_id + "][input_type]'/>";
        var html = html + "<input type='hidden' value='" + condition_name + "' name='Rule[rule_condtion][" + condition_id + "][condition_name]'/>";
        var html = html + "<input type='hidden' value='" + condition_key + "' name='Rule[rule_condtion][" + condition_id + "][condition_key]'/>";
        var html = html + "<div style='float:left'><select class='form-control' style='width:80px' name='Rule[rule_condtion][" + condition_id + "][oprerator]'>";
        var obj = data.oprerator_data;
        for (var n in obj) {
            var html = html + "<option value='" + n + "'>" + obj[n] + "</option>";
        }
        var html = html + "</select></div><div style='width:315px;float:left;padding-left:20px;'>";

        var obj = data.option_data;
        for (var n in obj) {
            var value = obj[n].option_value;
            var html = html + "<div id='input_input_input' style='width:100%'><div style='display: inline-block;white-space: nowrap;'><input style='font-style: normal;font-weight: normal;' type='radio' name='Rule[rule_condtion][" + condition_id + "][value]' value='" + value + "'  checked /><label>";
            var html = html + obj[n].option_name + "</label></div></div>";
        }

        var html = html + "</div><div style='clear:both'></div></div>";

        $("#condition_option_container").append(html);
    }

    //构造select元素
    function append_condition_option_select(data, condition_id, condition_name, condition_key) {
        var html = "<div id='container" + condition_id + "' style='margin:10px 5px'>";
        var html = html + "<div style='width:160px;float:left;height:34px;line-height:34px;text-align:left'>" + condition_name + ":　</div>";
        var html = html + "<input type='hidden' value='" + data.input_type + "' name='Rule[rule_condtion][" + condition_id + "][input_type]'/>";
        var html = html + "<input type='hidden' value='" + condition_name + "' name='Rule[rule_condtion][" + condition_id + "][condition_name]'/>";
        var html = html + "<input type='hidden' value='" + condition_key + "' name='Rule[rule_condtion][" + condition_id + "][condition_key]'/>";
        var html = html + "<div style='float:left'><select class='form-control' style='width:80px' name='Rule[rule_condtion][" + condition_id + "][oprerator]'>";
        var obj = data.oprerator_data;
        for (var n in obj) {
            var html = html + "<option value='" + n + "'>" + obj[n] + "</option>";
        }
        var html = html + "</select></div><div style='float:left;padding-left:20px'>";

        if (condition_key == "<?php echo Condition::CONDITION_KEY_ACCOUNT;?>" || condition_key == "<?php echo Condition::CONDITION_KEY_ORDER_ACCOUNT?>") {
            var html = html + "<div id='account_info_select'>";
            var html = html + "<select multiple='multiple' name='Rule[rule_condtion][" + condition_id + "][value][]' class='form-control' style='width:315px'>";

            var obj = data.option_data;
            for (var n in obj) {
                var value = obj[n].id;
                var html = html + "<option value='" + value + "'>" + obj[n].account_name + "</option>";
            }

        } else if (condition_key == "<?php echo Condition::CONDITION_KEY_BYUER_OPTION_LOGISTICS;?>") {
            var html = html + "<div id='buyer_option_logistics_select'>";
            var html = html + "<select multiple='multiple' name='Rule[rule_condtion][" + condition_id + "][value][]' class='form-control' style='width:315px'>";

            var obj = data.option_data;
            for (var n in obj) {
                var value = obj[n];
                var html = html + "<option value='" + value + "'>" + value + "</option>";
            }
        } else if (condition_key == "<?php echo Condition::CONDITION_KEY_WAREHOUSE_ID;?>") {
            var html = html + "<div id='warehouse_select'>";
            var html = html + "<select multiple='multiple' name='Rule[rule_condtion][" + condition_id + "][value][]' class='form-control' style='width:315px'>";

            var obj = data.option_data;
            for (var n in obj) {
                var value = obj[n];
                var html = html + "<option value='" + n + "'>" + value + "</option>";
            }
        }
        else if (condition_key == "<?php echo Condition::CONDITION_KEY_SHIP_CODE;?>") {
            var html = html + "<div id='ship_code_select'>";
            var html = html + "<select multiple='multiple' name='Rule[rule_condtion][" + condition_id + "][value][]' class='form-control' style='width:315px'>";

            var obj = data.option_data;
            for (var n in obj) {
                var value = obj[n];
                var html = html + "<option value='" + n + "'>" + value + "</option>";
            }
        }
        else if (condition_key == "<?php echo Condition::CONDITION_KEY_SHIP_COUNTRY;?>") {
            var html = html + "<div id='ship_code_select'>";
            var html = html + "<select multiple='multiple' name='Rule[rule_condtion][" + condition_id + "][value][]' class='form-control' style='width:315px'>";

            var obj = data.option_data;
            for (var n in obj) {
                var value = obj[n];
                var html = html + "<option value='" + n + "'>" + value + "</option>";
            }
        }
        else if (condition_key == "<?php echo Condition::CONDITION_KEY_PRODUCT_SITE;?>" || condition_key == "<?php echo Condition::CONDITION_KEY_INFO_SITE?>") {
            var html = html + "<div id='product_site_select'>";
            var html = html + "<select multiple='multiple' name='Rule[rule_condtion][" + condition_id + "][value][]' class='form-control' style='width:315px'>";

            var obj = data.option_data;
            for (var n in obj) {
                var value = obj[n];
                var html = html + "<option value='" + n + "'>" + value + "</option>";
            }
        }
        else {
            var html = html + "<div>";
            var html = html + "<select multiple='multiple' name='Rule[rule_condtion][" + condition_id + "][value][]' class='form-control' style='width:315px'>";

            var obj = data.option_data;
            for (var n in obj) {
                var value = obj[n].option_value;
                var html = html + "<option value='" + value + "'>" + obj[n].option_name + "</option>";
            }
        }


        var html = html + "</select></div></div><div style='clear:both'></div></div>";
        $("#condition_option_container").append(html);
    }

    //构造checkbox元素
    function append_condition_option_checkbox(data, condition_id, condition_name, condition_key) {
        var html = "<div id='container" + condition_id + "' style='margin:10px 5px'>";
        var html = html + "<div style='width:160px;float:left;height:34px;line-height:34px;text-align:left'>" + condition_name + ":　</div>";
        var html = html + "<input type='hidden' value='" + data.input_type + "' name='Rule[rule_condtion][" + condition_id + "][input_type]'/>";
        var html = html + "<input type='hidden' value='" + condition_name + "' name='Rule[rule_condtion][" + condition_id + "][condition_name]'/>";
        var html = html + "<input type='hidden' value='" + condition_key + "' name='Rule[rule_condtion][" + condition_id + "][condition_key]'/>";
        var html = html + "<div style='float:left'><select class='form-control' style='width:80px' name='Rule[rule_condtion][" + condition_id + "][oprerator]'>";
        var obj = data.oprerator_data;
        for (var n in obj) {
            var html = html + "<option value='" + n + "'>" + obj[n] + "</option>";
        }

        var html = html + "</select></div><div style='width:315px;float:left;padding-left:20px;'>";
        var obj = data.option_data;
        for (var n in obj) {
            var value = obj[n].option_value;
            var html = html + "<div id='input_input_input' style='width:100%'><div style='display: inline-block;white-space: nowrap;'><input style='font-style: normal;font-weight: normal;' type='checkbox' name='Rule[rule_condtion][" + condition_id + "][value][]' value='" + value + "'  checked /><label>";
            var html = html + obj[n].option_name + "</label></div></div>";
        }
        var html = html + "</div><div style='clear:both'></div></div>";

        $("#condition_option_container").append(html);
    }

    //构造input_type为范围的dom元素
    function append_condition_option_range(data, condition_id, condition_name, condition_key) {
        var html = "<div id='container" + condition_id + "' style='margin:10px 5px'>";
        var html = html + "<div style='width:160px;float:left;height:34px;line-height:34px;text-align:left'>" + condition_name + ":　</div>";
        var html = html + "<input type='hidden' value='" + data.input_type + "' name='Rule[rule_condtion][" + condition_id + "][input_type]'/>";
        var html = html + "<input type='hidden' value='" + condition_name + "' name='Rule[rule_condtion][" + condition_id + "][condition_name]'/>";
        var html = html + "<input type='hidden' value='" + condition_key + "' name='Rule[rule_condtion][" + condition_id + "][condition_key]'/>";
        var html = html + "<div style='float:left'><select onchange='baohan_bubaohan(this," + condition_id + ")' class='form-control' style='width:80px' name='Rule[rule_condtion][" + condition_id + "][oprerator]'>";
        var obj = data.oprerator_data;
        for (var n in obj) {
            var html = html + "<option value='" + n + "'>" + obj[n] + "</option>";
        }
        var html = html + "</select></div><div id='input_baohan_bubaohan_" + condition_id + "' style='float:left;padding-left:20px;'>";
        var html = html + "<input style='width:150px;float:left;margin-right:15px' type='text' class='form-control' name='Rule[rule_condtion][" + condition_id + "][value][]' placeholder='请输入开始范围'><input style='width:150px;float:left' type='text' class='form-control' name='Rule[rule_condtion][" + condition_id + "][value][]' placeholder='请输入结束范围'><div style='clear:both'></div>";
        var html = html + "</div><div style='clear:both'></div></div>";

        $("#condition_option_container").append(html);
    }

    function baohan_bubaohan(obj, condition_id) {
        var oprerator_baohan = "<?php echo RuleCondtion::RULE_CONDITION_OPRERATOR_BAOHAN;?>";
        var oprerator_bubaohan = "<?php echo RuleCondtion::RULE_CONDITION_OPRERATOR_BUBAOHAN;?>";
        if (obj.value == oprerator_baohan || obj.value == oprerator_bubaohan) {
            var html = "<label id='input_input_input'><input style='font-style: normal;font-weight: normal;width:60px;height:34px;' type='text'  name='Rule[rule_condtion][" + condition_id + "][value][]' placeholder='您的值'>";
            var html = html + "<label onclick='add_input(this," + condition_id + ")'><font size='4' color='blue'>&nbsp;+&nbsp;</font></label>" + "</label>";

        } else {
            var html = "<input style='width:315px' type='text' class='form-control' name='Rule[rule_condtion][" + condition_id + "][value]' placeholder='请输入选项值'>";
        }
        $("#input_baohan_bubaohan_" + condition_id).html(html);
    }

    function add_input(obj, condition_id) {
        var element = "input_input_" + new Date().getTime();
        var html = "<label id='" + element + "'><input style='font-style: normal;font-weight: normal;width:60px;height:34px' type='text'  name='Rule[rule_condtion][" + condition_id + "][value][]' placeholder='您的值'>";
        var html = html + "<label data-element='" + element + "' onclick='deal_input(this)'><font size='4' color='red'>&nbsp;-&nbsp;</font></label></label>";
        $("#input_baohan_bubaohan_" + condition_id).append(html);
    }

    function deal_input(obj, condition_id) {
        //移除新增的dom元素
        $("#" + obj.getAttribute('data-element')).remove();
    }

    //改变平台code去更新账号数据
    function getAccountInfoByChangePlatformCode(obj) {
        $.ajax({
            url: "<?php echo \yii\helpers\Url::toRoute('/accounts/account/getaccount');?>",
            async: false,
            dataType: 'json',
            type: 'get',
            data: {platform_code: obj.value, type: $("#rule_type").val()},
            success: function (result) {
                var select_name = $("#account_info_select").find('select').attr('name');
                if (select_name) {
                    changeAccountSelectData(result.account_info, select_name);
                }
                var select_name = $("#buyer_option_logistics_select").find('select').attr('name');
                if (select_name) {
                    changeBuyerOptionLogistics(result.buyer_option_logistics, select_name);
                }
                changeRelationSelectData(result.relation_data);
            },
            error: function (xhr, status, error) {
                alert(error);
            }
        });
    }

    //通过改变平台code去更新该平台下面的账号信息
    function changeAccountSelectData(data, select_name) {
        if (data.length) {
            var html = "<select multiple='multiple' name='" + select_name + "' class='form-control' style='width:315px'>";
            for (var n in data) {
                var value = data[n].id;
                var html = html + "<option value='" + value + "'>" + data[n].account_name + "</option>";
            }
            var html = html + "</select>";
            $("#account_info_select").html(html);
        }
    }

    //通过改变平台code去更新该平台下面的买家选择运输方式信息
    function changeBuyerOptionLogistics(data, select_name) {
        if (data.length) {
            var html = "<select multiple='multiple' name='" + select_name + "' class='form-control' style='width:315px'>";
            for (var n in data) {
                var value = data[n];
                var html = html + "<option value='" + value + "'>" + value + "</option>";
            }
            var html = html + "</select>";
            $("#buyer_option_logistics_select").html(html);
        }
    }

    //通过改变平台code去更新该平台下的标签或者模板数据
    function changeRelationSelectData(data) {
        var html = '';
        for (var n in data) {
            var html = html + "<option value='" + n + "'>" + data[n] + "</option>";
        }
        $("#rule-relation_id").html(html);
    }
    
    //切换状态 有效期时间是否隐藏
    $('#rule-status').on('change',function(){
        if($(this).val() == 2){
            $("#survival").css('display','block');
        }else{
            $("#rule-survival_str_time").val("");
            $("#rule-survival_end_time").val("");
            $("#survival").css('display','none');
        }
    });

</script>