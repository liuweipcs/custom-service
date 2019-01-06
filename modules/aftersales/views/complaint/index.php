<?php

use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use app\modules\aftersales\models\ComplaintModel;
use app\components\LinkPager;
use app\modules\aftersales\models\WarehouseprocessingModel;

$this->title = '仓库客诉表';
?>
<style>
    .select2-container--krajee {
        min-width: 155px !important;
    }
    .created_time{
        cursor: pointer;
    }
    .paytime{
        cursor: pointer;
    }
    .shipped{
        cursor: pointer;
    }
    .col-sm-6 {
        width: 138%;
    }
    #search-form1{
        display: none;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="well">
                <input type="hidden" name="platform_code" value="<?php echo \app\modules\accounts\models\Platform::PLATFORM_CODE_EB; ?>">
                <form id="search-form" class="form-horizontal" action="<?php echo \Yii::$app->request->getUrl(); ?>"
                      method="get" role="form">
                    <input type="hidden" name="sortBy" value="">
                    <input type="hidden" name="sortOrder" value="">
                    <ul class="list-inline">
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">所属平台</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="platform_code">
                                        <?php foreach ($platformList as $code => $value) { ?>
                                            <option value="<?php echo $code; ?>" <?php if ($code == $platformCode) echo 'selected="selected"'; ?>><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">账号</label>
                                <div class="col-lg-7">
                                    <?php
                                    echo Select2::widget([
                                        'name' => 'account_id',
                                        'value' => $account_id,
                                        'data' => $ImportPeople_list,
                                        'options' => ['placeholder' => '请选择...']
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 30px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">客诉类型</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="type" style="min-width:136px">
                                        <option value="">全部 </option>  
                                        <?php foreach ($basic as $key => $val) { ?>
                                            <option value="<?php echo $val->name; ?>" <?php if ($type == $val->name) { ?>selected<?php } ?>> <?php echo $val->name ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">关键字查询</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="key" style="width:300px;"
                                           value="<?php echo $keys; ?>" placeholder="支持买家ID/订单号/平台订单号">
                                </div>
                            </div>
                        </li>
                        <li style="margin-left:45px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">状态</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="status" style="width:150px">
                                        <option value="">全部</option>
                                        <option value="-1" <?php if ($status == -1) { ?>selected<?php } ?>>审核不通过</option>
                                        <option value="0" <?php if ($status === '0') { ?>selected<?php } ?>>待审核</option>
                                        <option value="1" <?php if ($status == 1) { ?>selected<?php } ?>>推送成功待仓库处理</option>
                                        <option value="2" <?php if ($status == 2) { ?>selected<?php } ?>>推送失败</option>
                                        <option value="3" <?php if ($status == 3) { ?>selected<?php } ?>>仓库处理完成待确认</option>
                                        <option value="4" <?php if ($status == 4) { ?>selected<?php } ?>>重新推送失败待重新推送</option>
                                        <option value="5" <?php if ($status == 5) { ?>selected<?php } ?>>重新推送成功待仓库处理</option>
                                        <option value="6" <?php if ($status == 6) { ?>selected<?php } ?>>已完成</option>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">选择时间</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="get_date"
                                            style="min-width:150px">  
                                        <option value="confirm_time"
                                                <?php if ($get_date == 'confirm_time') { ?>selected<?php } ?>>处理时间
                                        </option>
                                        <option value="shipping_date"
                                                <?php if ($get_date == 'shipping_date') { ?>selected<?php } ?>>发货时间
                                        </option>
                                        <option value="audit_time"
                                                <?php if ($get_date == 'audit_time') { ?>selected<?php } ?>>审核时间
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 70px;">
                            <div class="form-group" style="width:235px">
                                <?php
                                echo DateTimePicker::widget([
                                    'name' => 'begin_date',
                                    'options' => ['placeholder' => ''],
                                    'value' => $begin_date,
                                    'pluginOptions' => [
                                        'autoclose' => true,
                                        'format' => 'yyyy-mm-dd hh:ii:ss',
                                        'todayHighlight' => true,
                                        'todayBtn' => 'linked',
                                    ],
                                ]);
                                ?>
                            </div>
                        </li>
                        <li style="margin-left: 20px;">
                            <div class="form-group" style="width:235px">
                                <?php
                                echo DateTimePicker::widget([
                                    'name' => 'end_date',
                                    'options' => ['placeholder' => ''],
                                    'value' => $end_date,
                                    'pluginOptions' => [
                                        'autoclose' => true,
                                        'format' => 'yyyy-mm-dd hh:ii:ss',
                                        'todayHighlight' => true,
                                        'todayBtn' => 'linked',
                                    ],
                                ]);
                                ?>
                            </div>
                        </li>
                        <li style="margin-left:45px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">是否加急</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="is_expedited" style="width:150px">
                                        <option value="">全部</option>
                                        <option value="0" <?php if ($is_expedited === '0') { ?>selected<?php } ?>>不加急</option>
                                        <option value="1" <?php if ($is_expedited == 1) { ?>selected<?php } ?>>加急</option>

                                    </select>
                                </div>
                            </div>
                        </li>
                        <li style="margin-left:45px;">
                            <div class="form-group">
                                <label class="control-label col-lg-5" for="">是否超时</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="is_overtime" style="width:150px">
                                        <option value="">全部</option>
                                        <option value="0" <?php if ($is_overtime === '0') { ?>selected<?php } ?>>否</option>
                                        <option value="1" <?php if ($is_overtime == 1) { ?>selected<?php } ?>>是</option>
                                    </select>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <button type="submit" class="btn btn-primary">搜索</button>

                </form>
            </div>

        </div>
        <div class="bs-bars pull-left" style="padding-top: 7px;">
            共<?php
            if ($count) {
                echo $count;
            } else {
                echo 0;
            };
            ?>条数据&nbsp;
        </div>
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class="batch-reply btn btn-default" id="examine"  href="javascript:void(0);">审核</a>
            </div>
        </div>

<!--        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class="batch-reply btn btn-default" id="confirm"  href="javascript:void(0);">确认</a>
            </div>
        </div>-->
        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class="batch-reply btn btn-default" id="delall"  href="javascript:void(0);">删除</a>
            </div>
        </div>
        <!--        <div class="bs-bars pull-left">
                    <div id="" class="btn-group">
                        <a class="batch-reply btn btn-default" id="download" target="_blank" href="/orders/order/download">加急</a>
                    </div>
                </div>-->

        <table class="table table-striped table-bordered">
            <tr>
                <th><input type="checkbox" id="all" class="all"></th>
                <td>买家ID</td>
                <td>客诉单号</td>
                <td style="width:200px">产品信息</td>
                <td>订单信息</td>
                <td style="width:200px">客诉信息</td>
                <td style="width:240px">仓库处理信息</td>
                <td>状态</td>
                <td>操作人/时间</td>
                <td>操作</td> 
            </tr>
            <?php if ($count != 0) { ?>
                <?php foreach ($result as $key => $item) { ?>           
                    <tr>
                        <td>
                            <?php if ($item['status'] == 0 || $item['status'] == '-1') { ?>
                                <input name="id[]"  data-orderid=<?php echo $item['id'] ?> value="<?= $item['id']; ?>" type="checkbox" class="sel">
                            <?php } ?>
                            <?php if ($item['status'] == 3) { ?>
                                <input name="id[]"  data-orderid=<?php echo $item['id'] ?> value="<?= $item['id']; ?>" type="checkbox" class="sel">
                            <?php } ?>  
                        </td>
                        <td><?php echo $item['buyer_id']; ?></td>
                        <td><a _width="100%" _height="100%" class="edit-button" href="<?php echo Url::toRoute(['getcompain', 'complaint_order' => $item['complaint_order']]); ?>" ><?php echo $item['complaint_order']; ?></a><br/><?php if ($item['is_expedited'] == 1) { ?><span style="color: red">【加急】</span><?php } ?></td>
                        <td><?php
                            foreach ($item['complian'] as $key => $v) {  ?>
                            <a href="http://120.24.249.36/product/index/sku/<?php $v['sku']; ?>" style="color:blue" target="_blank"><?php echo $v['sku']; ?></a>(<?php echo $v['title'] ?>)<br/>
                           
                         <?php  }  ?></td>
                   
                        <td>
                            订单号：<?php echo $item['order_id']; ?><br/>
                            平台单号: <a _width="100%" _height="100%" class="edit-button platform_order_id"
                               data-orderid="<?php echo $item['platform_order_id']; ?>"
                               href="<?php
                               echo Url::toRoute(['/orders/order/orderdetails',
                                   'order_id' => $item['platform_order_id'],
                                   'platform' => $item['platform_code'],
                                   'system_order_id' => $item['order_id']]);
                               ?>" title="订单信息">
                                  <?php echo $item['platform_order_id']; ?>
                            </a><br/>
                            发货时间:<?php echo $item['shipping_date']; ?>
                        </td>
                        <td>
                            客诉类型:<?php echo $item['type']; ?><br/>
                            详细描述:<?php echo $item['description']; ?>
                        </td>
                        <td>
                            处理次数:<?php echo $item['processing_times']; ?><br/>
                            时效:<span style="color: red">
                                <?php
                                //第一次推送时间
                                $starttime = strtotime($item['audit_time']);
                                //$starttime = strtotime("2018-12-05 18:32:30");
                                //已完结更新时间
                                $endtime = strtotime($item['confirm_time']);
                                // $endtime = strtotime("2018-12-07 15:45:10");
                                echo ComplaintModel::getRemainderTime($starttime, $endtime);
                                ?>
                                </sapn><br/>
                                <?php
                                $res = WarehouseprocessingModel::find()->where(['complaint_order_id' => $item['id']])->orderBy('processing_time desc')->asArray()->one();
                                ?>
                                <h5>最新处理信息：</h5>
                                处理人：<?php echo $res['processing_user']; ?><br/>
                                处理时间：<?php echo $res['processing_time']; ?><br/>
                                处理类型：<?php echo $res['processing_type']; ?><br/>
                                详细描述：<?php echo $res['description']; ?>
                        </td>
                        <td>
                            <?php echo ComplaintModel::getstatus($item['status']) ?>  
                        </td>
                        <td>
                            最晚处理：<?php echo $item['last_processing_time']; ?><br/>
                            <!--                            更新：方超 /2018-10-24 15:02:40<br/>-->
                            审核：<?php echo $item['auditer']; ?> /<?php echo $item['audit_time'] ?><br/>

                            确认：<?php echo $res['confirm_user'] ?> /<?php echo $res['confirm_time']; ?>
                        </td>
                        <td>
                            <a _width="100%" _height="100%" class="edit-button" href="<?php echo Url::toRoute(['getcompain', 'complaint_order' => $item['complaint_order']]); ?>" >处理</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="22">暂无指定条件客诉单</td>
                </tr>
            <?php } ?>
        </table>
        <?php
        echo LinkPager::widget([
            'pagination' => $page,
            'firstPageLabel' => '首页',
            'lastPageLabel' => '尾页',
            'nextPageLabel' => '下一页',
            'prevPageLabel' => '上一页',
        ]);
        ?>
    </div>

    <div class="modal fade in" id="batchsendMsgModal" tabindex="-1" role="dialog" aria-labelledby="sbatchsendMsgModalLabel"
         aria-hidden="true"
         style="top:300px;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="sbatchsendMsgModalLabel">审核</h4>
                </div>
                <div class="modal-body">
                    <div class="popup-body">
                        <div class="row">
                            <div class="col-sm-9">
                                <div class="form-group field-subject-tag">
                                    <label class="control-label col-sm-3" for="ebayreply-reply_title">结果:</label>
                                    <div class="col-sm-9">
                                        <label class="checkbox-inline">
                                            <input type="radio" name="status" value="1" >审核通过
                                        </label>
                                        <label class="checkbox-inline">
                                            <input type="radio" name="status" value="-1">审核不通过
                                        </label>
                                        <input type="hidden" name="selectIds" value="" id="selectIds">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-9">
                                <div class="form-group field-ebayfeedback-comment_text">
                                    <label class="control-label col-sm-3" for="ebayfeedback-comment_text">原因：</label>
                                    <div class="col-sm-6">
                                        <textarea id="ebayfeedback-comment_text" class="form-control" name="remark" maxlength="80" rows="7" placeholder="请输入原因" id="remark"></textarea>
                                        <div class="help-block help-block-error "></div>
                                    </div>
                                </div>                          
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="sendMsgBtnBatch">提交</button>
                    <button type="button" class="btn btn-default" id="closeModel" data-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade in" id="confirms" tabindex="-1" role="dialog" aria-labelledby="sbatchsendMsgModalLabel"
         aria-hidden="true"
         style="top:300px;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="confirms">确认</h4>
                </div>
                <div class="modal-body">
                    <div class="popup-body">    
                        <div class="popup-body">
                            <div class="row">
                                <div class="col-sm-9">
                                    <div class="form-group field-subject-tag">
                                        <label class="control-label col-sm-3" for="ebayreply-reply_title">结果:</label>
                                        <div class="col-sm-9">
                                            <label class="checkbox-inline">
                                                <input type="radio" name="status" value="6">完结
                                            </label>
                                            <label class="checkbox-inline">
                                                <input type="radio" name="status" value="5">重新推仓库处理
                                            </label>
                                             <input type="hidden" name="ids" value="" id="Ids">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-9">
                                    <div class="form-group field-ebayfeedback-comment_text">
                                        <label class="control-label col-sm-3" for="ebayfeedback-comment_text">原因：</label>
                                        <div class="col-sm-6">
                                            <textarea id="ebayfeedback-comment_text" class="form-control" name="remark" maxlength="80" rows="7" placeholder="请输入原因"></textarea>
                                            <div class="help-block help-block-error "></div>
                                        </div>
                                    </div>                          
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="sendMsg">提交</button>
                    <button type="button" class="btn btn-default" id="closeconfrim" data-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo yii\helpers\Url::base(true); ?>/js/currency.js"></script>
<script type="text/javascript">
    $("#all").click(function(){
        var ids = $(".sel").length;
        var che=this.checked;
         for(var i=0;i<ids;i++){
             $(".sel")[i].checked=che;
            } 
       })
    $("#closeModel").click(function () {
        $('#batchsendMsgModal').hide();

    })
    $('#closeconfrim').click(function () {
        $('#confirms').hide();
    });
    $("select[name='platform_code']").on("change", function () {
        var platform_code = $(this).val();
        $.post("<?php echo Url::toRoute(['/orders/refund/reason']) ?>", {
            "platform_code": platform_code
        }, function (data) {
            // console.log(data);
            var html = "<option value=' '>全部</option>";
            var htmla = "<option value=' '>全部</option>";
            if (data["code"] == 1) {
                var account = data["account"];
                var data = data["data"];
                // console.log(account);
                for (var ix in data) {
                    html += "<option value='" + ix + "'>" + data[ix] + "</option>"
                }
                for (var id in account) {
                    htmla += "<option value='" + id + "'>" + account[id] + "</option>"
                }
            }

            $("select[name='account_id']").html(htmla);
            $("select[name='reason']").html(html);
        }, "json");
    });
    $("#examine").click(function () {
        //平台订单ID&账号ID&买家登陆ID 组合一个字符串

        var selectIds = '';
        $(":checked.sel").each(function () {
            if (selectIds == '') {
                if ($(this).prop('checked') == true) {
                    selectIds = $(this).data('orderid');
                }
            } else {
                if ($(this).prop('checked') == true) {
                    selectIds += ',' + $(this).data('orderid');
                }
            }
        });
        if (selectIds == "") {
            layer.msg('请勾选数据', {icon: 2});
            return false;
        }
        $('#selectIds').val(selectIds);
        $("#batchsendMsgModal").show();

    });
    $('#sendMsgBtnBatch').click(function () {
        var selectIds = $('#selectIds').val();

        var url = '/aftersales/complaint/getexamineall';
        var status = $('input[name="status"]:checked').val();
        var remark = $('#remark').val();
        if (status == "") {
            layer.msg('请选择审核状态', {icon: 2});
            return false;
        }
        if (status == "-1") {
            if (remark == '') {
                layer.msg('请说明原因', {icon: 2});
                return false;
            }
        }
        $.ajax({
            url: url,
            type: "post",
            data: {'id': selectIds, 'status': status, 'remark': remark},
            dataType: "json",
            success: function (data) {
                if (data.state == 0) {
                    layer.msg(data.msg, {icon: 2});
                    $("#batchsendMsgModal").hide();
                } else {
                    layer.msg(data.msg, {icon: 1});
                    $("#batchsendMsgModal").hide();
                    window.location.reload();
                }
            }
        });



    });
    //批量删除
    $('#delall').click(function () {
        var url = "/aftersales/complaint/delall";
        //平台订单ID&账号ID&买家登陆ID 组合一个字符串
        var selectIds = '';
        $(":checked.sel").each(function () {
            if (selectIds == '') {
                if ($(this).prop('checked') == true) {
                    selectIds = $(this).data('orderid');
                }
            } else {
                if ($(this).prop('checked') == true) {
                    selectIds += ',' + $(this).data('orderid');
                }
            }
        });
        if (selectIds == "") {
            layer.msg('请勾选数据', {icon: 2});
            return false;
        }
        layer.confirm('您确定要删除这些数据吗？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: url,
                type: 'post',
                data: {'id': selectIds},
                dataType: "json",
                success: function (e) {
                    if (e.state == 1) {
                        layer.msg(e.msg, {icon: 1});
                        window.location.reload();
                    } else {
                        layer.msg(e.msg, {icon: 2});
                    }
                }
            })
        }, function () {

        });
    });
    //确认
    $('#confirm').click(function () {
       var selectIds = '';
        $(":checked.sel").each(function () {
            if (selectIds == '') {
                if ($(this).prop('checked') == true) {
                    selectIds = $(this).data('orderid');
                }
            } else {
                if ($(this).prop('checked') == true) {
                    selectIds += ',' + $(this).data('orderid');
                }
            }
        });
        if (selectIds == "") {
            layer.msg('请勾选数据', {icon: 2});
            return false;
        }
        $('#Ids').val(selectIds);
        $('#confirms').show();
    });
    $('#sendMsg').click(function(){
          var url = '/aftersales/complaint/getconfirmall';
        var ids=$('#Ids').val();
       var status = $('input[name="status"]:checked').val();
       var remark = $('#remark').val(); 
      $.ajax({
          url:url,
          type:'post',
          data:{'ids':ids,'status':status,'remark':remark},
          dataType:'json',
          success:function(e){
              
          },
          
          
          
      });
        
    });
    
    

</script>