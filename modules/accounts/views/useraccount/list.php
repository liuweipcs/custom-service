<?php 
use yii\helpers\Url;
use yii\helpers\Html;
use app\components\SearchSelect;
/* @var $this \yii\web\View */
$this->registerJsFile(Url::base() . '/js/multiselect.js');
$this->title = '用户账号绑定';
?>
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
    <hr />
    <div class="row">
    	<div class="col-xs-3">
    	    <label>账号列表</label>
    		<select name="account_ids" id="account-list" class="form-control" size="18" multiple="multiple">
    		</select>
    	</div>
    	<div class="col-xs-1">
    	    <br />
    	    <br />
    	    <br />
    	    <br />
    	    <br />
    		<button type="button" id="rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>
    		<button type="button" id="rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>
    		<button type="button" id="leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>
    		<button type="button" id="leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>
    	</div>
    	
    	<div class="col-xs-3">
    	    <label>已选中的账号列表</label>
    		<select name="selected_account_ids" id="account-list-1" class="form-control" size="18" multiple="multiple">
    		</select>
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
jQuery(document).ready(function($) {
    $('#account-list').multiselect({
        right: '#account-list-1',
        rightAll: '#rightAll',
        rightSelected: '#rightSelected',
        leftSelected: '#leftSelected',
        leftAll: '#leftAll'
    });
});

function save()
{
	var form = $('#user-account');
	var postData = '';
//	var userId = $('select[name=user_id]').val();
	var userId = $('select[name=user_id_new]').val();
	$('#account-list-1 option').each(function(){
	    postData += 'account_ids[]=' + this.value + '&';
	});
	postData += 'user_id=' + userId;
	var url = '<?php echo Url::toRoute('/accounts/useraccount/save');?>';
	$.post(url, postData, function(data){
		ajaxMessageCallback(data);
	}, 'json');		
}

//查找平台账号列表
function searchAccount()
{
//	var userId = $('select[name=user_id]').val();
	var userId = $('select[name=user_id_new]').val();
	var url = '<?php echo Url::toRoute(['/accounts/useraccount/searchaccount'])?>?user_id=' + userId;
	$.get(url, function(data){
		if (typeof(data.code) != 'undefined' && data.code == 0)
		{
			ajaxMessageCallback(data);
			return false;
	    }
	    var accountList = data.accountList;

	    var selectedAccountList = data.selectedAccountList;
	    var leftHtml = '';
	    var rightHtml = '';
	    for (var i in accountList)
	    {
	        var length = accountList[i].length;
		    leftHtml += '<optgroup label="' + i + '('+length +')'+ '">' + "\n";
            for (var j in accountList[i]){

                leftHtml += '<option value="' + accountList[i][j].id + '">' + accountList[i][j].account_name + '</option>' + "\n";
		    }
            leftHtml += '</optgroup>' + "\n";
		}
	    for (var i in selectedAccountList)
	    {
            var length1 = selectedAccountList[i].length;
	    	rightHtml += '<optgroup label="' + i + '('+length1 +')'+ '">'+ "\n";
		    for (var j in selectedAccountList[i]){

                rightHtml += '<option value="' + selectedAccountList[i][j].id + '">' + selectedAccountList[i][j].account_name + '</option>' + "\n";
		    }
            rightHtml += '</optgroup>' + "\n";
		}
		$('#account-list').empty().html(leftHtml);
		$('#account-list-1').empty().html(rightHtml);

	}, 'json');
}
</script>