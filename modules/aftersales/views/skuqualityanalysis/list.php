<?php

use app\modules\systems\models\BasicConfig;
use app\components\GridView;
use yii\helpers\Url;
use yii\bootstrap\Modal;

$this->title .= '产品质量破损分析';
?>
<style type="text/css">
	
	#search-form .input-group.date {
	      width: 320px;
	  }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'pager' => [],
                'columns' => [
                    [
                        'field' => 'state',
                        'type' => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field' => 'sku',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'picking_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                            'style' => ['width' => '230px']
                        ],
                    ],
                    [
                        'field' => 'category_cn_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'qualityer',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'developer',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'abnormal_num',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'total_loss_rmb',
                        'type' => 'text',
						'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'reason_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'remark',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'modify_by',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                ],
                'toolBars' => [
                    [
                        'href' => Url::toRoute('/aftersales/skuqualityanalysis/download'),
                        'text' => Yii::t('system', '下载数据'),
                        'htmlOptions' => [
                            'id' => 'download',
                            'target' => '_blank',
                        ]
                    ],
                ],    
       
            ]);
            ?>
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel">备注</h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal">
                    <div class="div_reason" style="display:block;">
                        <div class="form-group">
                            <label for="remark" class="col-sm-3 control-label required" style="width:18%">备注内容：<span class="text-danger">*</span></label>
                            <div class="col-md-9"><textarea class="form-control" rows="6" id="remark_content"></textarea></div>
                            <div class="count_length"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="hide_id" id="hide_id" value=""/>
                <button type="button" class="btn btn-default waves-effect" data-dismiss="modal">Close</button>
                <button type="button" class="btn save btn-primary waves-effect waves-light">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
	
	/**
	 * 下载数据相关操作
	 */
	$("#download").click(function () {
	    var url = $(this).attr('href');

	    var selectIds = selectId = [];
	    var selection = $("#grid-list").bootstrapTable('getAllSelections');

	    for (var i = 0; i < selection.length; i++) {
	        selectId.push(selection[i].id);
	    }
	    selectIds = selectId.join(',');

	    //如果选中则只下载选中数据
	    if (selectIds != "") {
	        url += '?ids=' + selectIds;
	    } else {
	        url = url;
	    }
	    window.open(url);
	});

	//获取所选id
	$(document).on('click', '.remark', function () {
	    var id = $(this).attr('data');
	    $("#hide_id").val(id);
	});

	//点击设置备注
	$(document).on('click', '.save', function () {
	    var id = $("#hide_id").val();
	    var text = $("#remark_content").val();

	    if (!text) {
	    	layer.msg('请填写备注内容!',{icon: 7,time: 1000});
	    	return false;
	    }
	    $.ajax({
	        type: "POST",
	        dataType: "JSON",
	        url: '<?php echo Url::toRoute(['/aftersales/skuqualityanalysis/setreason']); ?>',
	        data: {'id': id, 'text': text},
	        success: function (data) {
	            if (data.status) {
	                layer.msg(data.info, {icon: 1,time: 2000},
	                    function () {
	                        window.location.reload();
	                        window.parent.location.reload();
	                        var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
	                        parent.layer.close(index); //再执行关闭 
	                    }
	                    );

	            } else {
	                layer.msg(data.info, {icon: 5});
	            }
	        }
	    });
	});

	$(function(){
	    var content = "内容(0/180)字符";
	    $('#remark_content').on('keyup',function(){
	            $(".count_length").css('display','block');
	            var contentLength = $(this).val().length;
	            if(contentLength < 180){
	                var content = $('.count_length').text('备注内容('+contentLength+'/180)');
	                $("label[for='remark']").html(content);
	            }else{
	                var text = "备注内容<span style='color:red;'>(180/180)</span>"
	                var content = $('.count_length').html(text);
	            }
	        });
	})



</script>


<?php
Modal::begin([
    'id' => 'created-modal',
    'header' => '<h4 class="modal-title">查看SKU异常明细</h4>',
    'footer' => '<a href="#" class="btn btn-primary"  data-dismiss="modal">关闭</a>',
    'closeButton' =>false,
    'size'=>'modal-lg',
    'options'=>[
        'z-index' =>'-1',
        // 'data-backdrop'=>'static',//点击空白处不关闭弹窗
    ],
]);
Modal::end();

$js = <<<JS
	$(document).on('click', '.anbor-detail', function () {
	    $.get($(this).attr('data-href'), {},
	        function (data) {
	           $('#created-modal').find('.modal-body').html(data);
	           $('#created-modal').modal('show');
	        }
	    );
	    return false;
	});
	
    $(document).on('click', '.remark', function () {
	    $('#myModal').modal('show');
	    return false;
	});
JS;
$this->registerJs($js);
?>


