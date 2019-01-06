<?php

use yii\helpers\Url;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\accounts\models\Account;
use kartik\select2\Select2;
use app\modules\aftersales\models\ComplaintModel;
use app\modules\aftersales\models\WarehouseprocessingModel;
$this->registerCssFile(Url::base() . '/css/viewer.min.css');
$this->registerJsFile(Url::base() . '/js/multiselect.js');
?>
<!--<link rel="stylesheet" href="http://www.dowebok.com/demo/192/css/viewer.min.css">-->
<style>
    .button{
        margin-top: -3px;
        border: 2px solid #1809f1;
        padding: 7px;
        width: 51px;
        border-radius: 13%;
        cursor: pointer;
        margin-left: 16px;
        float: left;
    }
</style>
<div class="popup-wrapper">
    <form action="<?php
    echo Url::toRoute(['/aftersales/complaint/register',
        'platform' => $info['info']['platform_code'],
        'order_id' => $info['info']['order_id'],
    ]);
    ?>" method="post" role="form" class="form-horizontal" >
        <div class="popup-body">
            <div class="row">
                <div class="col-sm-5">
                    <div class="panel panel-default">
                        <?php
                        echo $this->render('order_info', ['info' => $info, 'isAuthority' => $isAuthority, 'accountName' => $accountName]);
                        echo $this->render('../order/transaction_record', ['info' => $info, 'paypallist' => $paypallist]); //交易记录
                        echo $this->render('../order/package_info', ['info' => $info]); //包裹信息
                        echo $this->render('../order/logistics', ['info' => $info, 'warehouseList' => $warehouseList]); //仓储物流
                        echo $this->render('../order/aftersales', ['afterSalesOrders' => $afterSalesOrders]); //售后问题
                        echo $this->render('../order/log', ['info' => $info]); //操作日志
                        ?>
                    </div>
                </div>  
                <div>
                    <?php if ($complain['status'] == '0' || $complain['status'] == '-1' || $complain['status'] == '2') { ?>
                        <div class="button">
                            <a _width="30%" _height="60%" class="edit-button"
                               href="<?php echo Url::toRoute(['/aftersales/complaint/getexamine', 'complaint_order' => $complain['complaint_order']]); ?>">审核</a>
                        </div>
                        <div class="button">
                            <a _width="100%" _height="100%" class="edit-button"
                               href="<?php echo Url::toRoute(['/aftersales/complaint/getedit', 'complaint_order' => $complain['complaint_order'], 'order_id' => $complain['order_id'], 'platform' => $complain['platform_code']]); ?>">修改</a>
                        </div>
                        <div class="button" onclick="del('<?php echo $complain['complaint_order']; ?>')">
                            <a href="javascript:void(0)">删除</a>
                        </div>
                    <?php } ?>
                    <?php //if ($complain['status'] == '3') { ?>
<!--                        <div class="button">
                            <a _width="30%" _height="60%" class="edit-button"
                               href="<?php //echo Url::toRoute(['/aftersales/complaint/getconfirm', 'complaint_order' => $complain['complaint_order']]); ?>">确认</a>
                        </div>-->
                    <?php //} ?>
                    <?php if ($complain['status'] == '1' && $complain['is_expedited'] == '0') { ?>
                        <div class="button" onclick="urgent('<?php echo $complain['complaint_order']; ?>')">
                            <a  href="javascript:void(0)">加急</a>
                        </div>
                    <?php } ?>
                </div>
                <div class="col-sm-7">
                    <div class="panel panel-default">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">客诉信息</h3>
                            </div>
                            <table class="table">    
                                <tr>
                                    <th style="width: 105px;">客诉单号</th>
                                    <td><?php echo $complain['complaint_order']; ?>&nbsp;&nbsp;<?php if ($complain['is_expedited'] == 1) { ?><span style="color: red;">【加急】</span><?php } ?>&nbsp;&nbsp;<?php if ($complain['is_overtime'] == 1) { ?><span style="color: red;">【超时】</span><?php } ?></td>
                                </tr>
                                <tr>
                                    <th style="width: 105px;">状态</th> 
                                    <td>
                                        <?php echo ComplaintModel::getstatus($complain['status']) ?>  
                                    </td>
                                </tr>
                                <tr>
                                    <th style="width: 105px;">是否加急</th> 
                                    <td>
                                        <?php
                                        if ($complain['is_expedited'] == 0) {
                                            echo "不加急";
                                        } elseif ($complain['is_expedited'] == 1) {
                                            echo "加急";
                                        }
                                        ?>  
                                    </td>
                                </tr>
                                <tr>
                                    <th>客诉类型</th>
                                    <td><?php echo $complain['type']; ?></td>
                                </tr>
                                <tr>
                                    <th>详细描述</th>
                                    <td><?php echo $complain['description']; ?></td>
                                </tr>
                            </table>

                            <table class="table table-striped table-bordered">
                                <tr>
                                    <th style="width: 212px">产品信息</th>
                                    <th style="width: 54px">数量</th>
                                    <th>图片</th>    
                                </tr>
                     
                                <?php foreach ($complaindetail as $key=>$vv) { ?>
                                    <tr>
                                        <td><span style="color:#b90b0b">名称:</span><?php echo $vv['title']; ?><br/><span style="color:#b90b0b">SKU:</span><?php echo $vv['sku']; ?><br/><span style="color:#b90b0b">产品线:</span><?php echo $vv['product_line']; ?></td>
                                        <td><?php echo $vv['qty']; ?></td>
                                        <td>
                                            <div class="img-list" id="images_<?php echo $key; ?>">
                                                <?php
                                                foreach ($vv['img_url'] as $v) {
                                                    ?>   
                                  <img src="/<?php echo $v; ?>"   data-original="/<?php echo $v; ?>" width="70" height="70" style="margin-left: 13px;cursor: pointer" />
                                                <?php } ?>  
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>

                        </div>
                    </div>
                </div>
                <?php if (count($processing) > 0) { ?>
                    <div class="col-sm-7">
                        <div class="panel panel-default">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h3 class="panel-title">仓库处理信息</h3>
                                </div>
                                <table class="table">    

                                    <tr>
                                        <th style="width: 105px;color: red">处理次数:</th> 
                                        <td style="color:red">
                                            <?php echo $complain['processing_times']; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="color:red">总耗时:</th>
                                        <td style="color:red"><?php
                                            //第一次推送时间
                                            $starttime = strtotime($complain['audit_time']);

                                            //$starttime = strtotime("2018-12-05 18:32:30");
                                            //已完结更新时间
                                            $endtime = strtotime($complain['confirm_time']);
                                            // $endtime = strtotime("2018-12-07 15:45:10");
                                            echo ComplaintModel::getRemainderTime($starttime, $endtime);
                                            ?></td>
                                    </tr>

                                </table>

                                <table class="table table-striped table-bordered">
                                    <tr>
                                        <th style="">推送</th>
                                        <th style="">仓库处理人/时间</th>
                                        <th>仓库处理时效</th> 
                                        <th>处理类型/描述</th>
                                        <th>图片</th>
                                        <th>确认结果/时间</th>
                                    </tr>                                
                                      <?php foreach($processing as $key=>$v){ ?>  
                                    <tr>
                                        <td><?php echo $v['create_user']; ?><br/><?php echo $v['add_time']; ?></td>
                                        <td><?php echo $v['processing_user']; ?><br><?php echo $v['processing_time']; ?></td>
                                        <td>
                                            <?php
                                            //第一次推送时间
                                            $starttime = strtotime($v['add_time']);

                                            //$starttime = strtotime("2018-12-05 18:32:30");
                                            //仓库处理时间
                                            $endtime = strtotime($v['processing_time']);
                                            // $endtime = strtotime("2018-12-07 15:45:10");
                                            echo ComplaintModel::getRemainderTime($starttime, $endtime);
                                            ?>
                                        </td>
                                        <td><?php echo $v['processing_type']; ?><br><?php echo $v['description']; ?></td>
                                        <td>
                                            <div class="img-list" id="imag_<?php echo $key; ?>">           
                                            <?php foreach (json_decode($v['img_info'], true) as $key => $value) { ?>
                                               <br/> <span><?php echo $key; ?></span><br/>
                                                <?php for($i=0;$i<count($value);$i++) { ?>
                                                    <img src="<?php echo $value[$i]; ?>" data-original="<?php echo $value[$i]; ?>" width="50" height="50" style="margin-left: 13px;cursor: pointer"/>
                                                <?php } ?>
                                            <?php } ?> 
                                            </div>        
                                        </td>
                                        <td><?php echo WarehouseprocessingModel::getstatus($v['status'],$complain['complaint_order']); ?><br>
                                            <?php 
                                            if($v['status']==3){
                                                 echo $complain['confirm_time']; 
                                            }else{
                                                  echo $v['confirm_time'];    
                                            }                                       
                                            ?>
                                        </td>
                                    </tr>
                                     <?php } ?>
                                </table>

                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

    </form>
</div>
<?php $this->registerJsFile(Url::base() . '/js/viewer-jquery.min.js');  ?>
<!--<script src="http://www.dowebok.com/demo/192/js/viewer-jquery.min.js"></script>-->
<script>
        $(function () {
            <?php foreach ($complaindetail as $key => $value) { ?>                         
            $('#images_<?php echo $key; ?>').viewer({
                url: 'data-original',
            });
           <?php  }  ?>
        });
</script>
<script>
        $(function () {
            <?php foreach ($processing as $key => $value) { ?>                         
            $('#imag_<?php echo $key; ?>').viewer({
                url: 'data-original',
            });
           <?php  }  ?>
        });
</script>
<script>
    function del(id) {
        var url = "<?php echo Url::toRoute(['/aftersales/complaint/getdelete']) ?>";
        layer.confirm('是否将数据删除？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: url,
                type: "post",
                data: {'id': id},
                dataType: "json",
                success: function (data) {
                    if (data.state == 1) {
                        layer.msg(data.msg, {icon: 1});
                        window.location.href = "/aftersales/warehousecustomercomplaint/index";
                    } else {
                        layer.msg(data.msg, {icon: 2});
                    }
                },
                error: function (e) {
                    layer.msg('系统错误', {icon: 2});
                }
            });
        }, function () {

        });
    }
    function urgent(id) {
        var url = "<?php echo Url::toRoute(['/aftersales/complaint/geturgent']) ?>";
        layer.confirm('是否将数据加急，让仓库紧急处理？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: url,
                type: "post",
                data: {'id': id},
                dataType: "json",
                success: function (data) {
                    if (data.state == 1) {
                        layer.msg(data.msg, {icon: 1});
                        window.location.reload();
                    } else {
                        layer.msg(data.msg, {icon: 2});
                    }
                },
                error: function (e) {
                    layer.msg('系统错误', {icon: 2});
                }


            });



        }, function () {

        });



    }
//    function see(e) {
//        var imgSrc = e.src;
//        layer.open({
//            type: 1
//            , title: false
//            , closeBtn: 0
//            , skin: 'layui-layer-nobg'
//            , shadeClose: true
//            , content: '<img src="' + imgSrc + '" style="height:600px;width:600px;">'
//            , scrollbar: false,
//            area: ['600px', '600px'], //宽高
//
//        })
//    }

</script>

