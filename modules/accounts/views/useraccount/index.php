<?php
use yii\helpers\Url;
use yii\helpers\Html;
use app\components\SearchSelect;
/* @var $this \yii\web\View */
$this->registerJsFile(Url::base() . '/js/multiselect.js');
$this->title = '用户账号绑定';
?>
<link rel="stylesheet" href="<?php echo yii\helpers\Url::base(true); ?>/css/yntree.min.css">
<style>


    *{
        margin: 0;
        padding: 0;
    }
    body{
        font-size: 14px;
        line-height: 1.42857143;
    }


</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h3 class="page-header bold">用户账号绑定</h3>
        </div>
    </div>
    <?php
    echo Html::beginForm(Url::toRoute('/accounts/useraccount/list'), 'post', [
        'role' => 'form',
        'class' => 'form-horizontal',
        'id' => 'user-account',
    ]);
    ?>
    <div class="row">
        <div class="col-xs-4">
            <label>选择用户：</label>
        </div>
    </div>
    <!--    <div class="row">-->
    <!--        <div class="col-xs-4">-->
    <!--            <div class="form-group">-->
    <!--                <div class="pull-left col-xs-8">-->
    <!--                    <select name="user_id" class="form-control col-xs-8">-->
    <!--                    --><?php //foreach ($userList as $userId => $userName) { ?>
    <!--                        <option value="--><?//=$userId;?><!--">--><?//=$userName;?><!--</option>-->
    <!--                    --><?php //} ?>
    <!--                    </select>-->
    <!--                </div>-->
    <!--                <div class="pull-right col-xs-4">-->
    <!--                    <button class="btn btn-primary" type="button" onclick="searchAccount()">确定</button>-->
    <!--                </div>-->
    <!--            </div>-->
    <!--        </div>        -->
    <!--    </div>-->
    <div class="row">
        <div class="col-xs-2">
            <?php
            echo \kartik\select2\Select2::widget([
                'name' =>'user_id_new',
                'data' =>$userList,
                'options' =>[
                    'placeholder'=>'--请输入--',
                    'style' => 'width:80px;',
                ],

            ]);
            ?>
        </div>
        <div class="pull-right col-xs-10">
            <button class="btn btn-primary" type="button" onclick="searchAccount()">确定</button>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <small class="text-danger">先选择一个用户，再点确定按钮获取用户已经分配的账号列表</small>
        </div>
    </div>
    <input class="hidden_user" hidden="hidden" />
    <hr />
    <div class="row">
        <div class="col-xs-3">
            <label>账号列表</label>
            <div name="account_ids" id="account-list" class="form-control" style="overflow:auto;height:500px;">
                <ul class="yn-tree" id="yn-tree2">

                </ul>
            </div>
        </div>

    </div>
    <br />
    <div class="row">
        <div class="col-xs-8">
            <button type="button" class="btn btn-primary" onclick="save()">保存</button>
        </div>
    </div>
    <?php echo Html::endForm();?>
</div>
<script type="text/javascript">
   /* jQuery(document).ready(function($) {
        $('#account-list').multiselect({
            right: '#account-list-1',
            rightAll: '#rightAll',
            rightSelected: '#rightSelected',
            leftSelected: '#leftSelected',
            leftAll: '#leftAll'
        });
    });*/



   $(".yn-tree").on('click','.arrow-right',function () {
       var li_a = $(this).parent().parent().attr("class");
       if(li_a == "yn-tree-li shrink"){
           $(this).parent().parent().attr("class","yn-tree-li spread");
       }else{
           $(this).parent().parent().attr("class","yn-tree-li shrink");
       }

   });

   $(".yn-tree").on('click','.yn-tree-input',function () {
       var checked = $(this).is(":checked") ? 1 : 0;
       if(checked == 1){
           $(this).parent().parent().siblings().find('li').each(function(){
               $(this).find('input').prop("checked","true");
           });

       }else if(checked == 0){
           $(this).parent().parent().siblings().find('li').each(function(){
               $(this).find('input').removeAttr("checked");
           });
       }
   });





   function save() {
       var form = $('#user-account');
       var postData = '';
//	var userId = $('select[name=user_id]').val();
       var userId = $('select[name=user_id_new]').val();
       postData = 'user_id=' + userId + '&';
       var p_li = $('#account-list>ul').children();
       $.each(p_li,function (){
          var z_li = $(this).children('ul').find('li');
           $.each(z_li,function (){
               var checked = $(this).find('input').is(":checked") ? 1 : 0;
               if(checked == 1){
                   var value = $(this).find('input').val();
                   postData += 'account_ids[]=' + value + '&';
               }

           });
       });
       var url = '<?php echo Url::toRoute('/accounts/useraccount/save');?>';
       $.post(url, postData, function (data) {
           ajaxMessageCallback(data);
       }, 'json');
   }

   //查找平台账号列表
       function searchAccount() {
//	var userId = $('select[name=user_id]').val();
           var userId = $('select[name=user_id_new]').val();
           var url = '<?php echo Url::toRoute(['/accounts/useraccount/searchaccount'])?>?user_id=' + userId;
           $.get(url, function (data) {
               if (typeof(data.code) != 'undefined' && data.code == 0) {
                   ajaxMessageCallback(data);
                   return false;
               }
               var accountList = data.accountList;
               var selectedAccountList = data.selectedAccountList;
               var leftHtml = '';
               for (var i in accountList) {
                   var length = accountList[i].length;
                   if(selectedAccountList[i] !== undefined){
                       var lengthSelect = selectedAccountList[i].length;
                   }else{
                       var lengthSelect = 0;
                   }

                   if(length == lengthSelect){
                       leftHtml += '<li class="yn-tree-li shrink" id="yn_tree_input43_li"><div class="checkbox"><span class="arrow arrow-right"></span>' +
                           '<label><input type="checkbox" class="yn-tree-input" id="yn_tree_input43" name="' + i + '" value="' + i + '" checked>' + i + '</label>('+length+')</div><ul class="yn-tree">' + "\n";
                   }else{
                       leftHtml += '<li class="yn-tree-li shrink" id="yn_tree_input43_li"><div class="checkbox"><span class="arrow arrow-right"></span>' +
                           '<label><input type="checkbox" class="yn-tree-input" id="yn_tree_input43" name="' + i + '" value="' + i + '">' + i + '</label>('+length+')</div><ul class="yn-tree">' + "\n";

                   }
                   for (var j in accountList[i]) {

                       if(accountList[i][j].check == 1){
                           leftHtml += '<li class="yn-tree-li shrink" id="yn_tree_input44_li" pid="yn_tree_input43" value="' + accountList[i][j].id + '"><div class="checkbox">' +
                               '<label><input type="checkbox" value="' + accountList[i][j].id + '" checked>' + accountList[i][j].account_name + '</label></div></li>' + "\n";
                       }else{
                           leftHtml += '<li class="yn-tree-li shrink" id="yn_tree_input44_li" pid="yn_tree_input43" value="' + accountList[i][j].id + '"><div class="checkbox">' +
                               '<label><input type="checkbox" value="' + accountList[i][j].id + '">' + accountList[i][j].account_name + '</label></div></li>' + "\n";
                       }

                   }
                   leftHtml += '</ul></li>' + "\n";
               }
               $('#account-list>ul').empty().html(leftHtml);

           }, 'json');
       }


</script>