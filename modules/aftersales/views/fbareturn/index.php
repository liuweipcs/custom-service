<?php

use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use app\components\LinkPager;

$this->title = 'FBA退货列表';
?>
<style>
    .select2-container--krajee {
        min-width: 155px !important;
    }
</style>
<style>
    #addReplyFeedback {
        margin: 20px auto 0 auto;
        width: 90%;
        height: auto;
        border-collapse: collapse;
    }

    #addReplyFeedback td {
        border: 1px solid #ccc;
        padding: 10px;
    }

    #addReplyFeedback td.col1 {
        width: 120px;
        text-align: right;
        font-weight: bold;
    }

    #addReplyFeedback .glyphicon.glyphicon-star {
        color: #ff9900;
        font-size: 20px;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-24">
            <div class="well">
                <form id="search-form" class="form-horizontal" action="<?php echo \Yii::$app->request->getUrl(); ?>"
                      method="get" role="form">
                    <input type="hidden" name="sortBy" value="">
                    <input type="hidden" name="sortOrder" value="">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <div class="form-group"><label class="control-label col-lg-5" for="">账号</label>
                                <div class="col-lg-7">
                                    <?php echo Select2::widget([
                                        'name'    => 'account_id',
                                        'id'      => 'account_id',
                                        'data'    => $accountList,
                                        'value'   => $datas['accountId'],
                                        'options' => [
                                            'placeholder' => '--请输入--',
                                        ],
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group"><label class="control-label col-lg-6" for="">平台订单号</label>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control" name="platform_order_id" style="width:150px" value="<?php echo $datas['platformOrderId']; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group"><label class="control-label col-lg-5" for="">关键词</label>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="asin" style="width:150px" value="<?php echo $datas['asin']; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group"><label class="control-label col-lg-4" for="">退货原因</label>
                                <div class="col-lg-8">
                                    <?php
                                        echo Select2::widget([
                                                'id'      => 'return_reason',
                                                'name'    => 'return_reason',
                                                'data'    => $refundReason,
                                                'value'   => $datas['returnReason'],
                                                'options' => [
                                                    'placeholder' => '--请输入--'
                                                ],
                                        ]);
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group"><label class="control-label col-lg-7" for="">退货是否可售</label>
                                <div class="col-lg-5">
                                    <select class="form-control" name="is_available_sale">
                                        <option value="">全部</option>
                                        <option value="1" <?php if ($datas['isAvailableSale'] == 1) echo 'selected="selected"'; ?>>可售</option>
                                        <option value="2" <?php if ($datas['isAvailableSale'] == 2) echo 'selected="selected"'; ?>>不可售</option>
                                    </select>
                                </div>
                            </div>
                        </div>
<!--                        <div class="col-lg-2">
                            <div class="form-group"><label class="control-label col-lg-6" for="">公司SKU状态</label>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control" name="company_sku_status" style="width:150px" value="<?php //echo $datas['companySkuStatus']; ?>">
                                </div>
                            </div>
                        </div>-->
                    </div>
                    <div class="col-lg-12">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label col-lg-6" for="">退货产品状态</label>
                                <div class="col-lg-6">
                                    <?php
                                        echo Select2::widget([
                                                'id'      => 'pro_status',
                                                'name'    => 'pro_status',
                                                'data'    => $returnProstatusList,
                                                'value'   => $datas['proStatus'],
                                                'options' => [
                                                    'placeholder' => '--请输入--'
                                                ],
                                        ]);
                                    ?>
                                </div>
                            </div>
                        </div>
                       <div class="col-lg-3">
                            <div class="col-lg-3">
                                <div class="" style="width:320px">
                                    <label class="control-label col-lg-3" for=""  style="width:80px" >退货率</label>
                                        <select id="return_rate" class="form-control" name="return_rate" style="width:80px;float:left;">
                                            <option value="0"></option>
                                            <option value="3" <?php echo $datas['refundRate'] == 3 ? 'selected' : ""; ?>>3天</option>
                                            <option value="7" <?php echo $datas['refundRate'] == 7 ? 'selected' : ""; ?>>7天</option>
                                            <option value="15" <?php echo $datas['refundRate'] == 15 ? 'selected' : ""; ?>>15天</option>
                                            <option value="30" <?php echo $datas['refundRate'] == 30 ? 'selected' : ""; ?>>30天</option>
                                            <option value="60" <?php echo $datas['refundRate'] == 60 ? 'selected' : ""; ?>>60天</option>
                                            <option value="90" <?php echo $datas['refundRate'] == 90 ? 'selected' : ""; ?>>90天</option>
                                        </select>
                                    <!--<div>-->
                                        <input class="form-control return_rate_str" name="return_rate_str" style="width:70px;float: left;display: inline;"  type="text" value="<?php echo $datas['refundRateStr'];?>"/>
                                        <label style="width:20px;float: left;text-align: center;line-height: 35px;" >～</label>
                                        <input class="form-control return_rate_end" name="return_rate_end" style="width:70px;float: left;display: inline;"  type="text" value="<?php echo $datas['refundRateEnd'];?>"/>
                                    <!--</div>-->
                                </div>
                            </div>
                        </div> 
                       <div class="col-lg-3">
                            <div class="col-lg-3">
                                <div class="" style="width:320px">
                                    <label class="control-label col-lg-3" for=""  style="width:80px" >销量</label>
                                        <select id="sales" class="form-control" name="sales" style="width:80px;float:left;">
                                            <option value="0"></option>
                                            <option value="3" <?php echo $datas['sales'] == 3 ? 'selected' : ""; ?>>3天</option>
                                            <option value="7" <?php echo $datas['sales'] == 7 ? 'selected' : ""; ?>>7天</option>
                                            <option value="15" <?php echo $datas['sales'] == 15 ? 'selected' : ""; ?>>15天</option>
                                            <option value="30" <?php echo $datas['sales'] == 30 ? 'selected' : ""; ?>>30天</option>
                                            <option value="60" <?php echo $datas['sales'] == 60 ? 'selected' : ""; ?>>60天</option>
                                            <option value="90" <?php echo $datas['sales'] == 90 ? 'selected' : ""; ?>>90天</option>
                                        </select>
                                    <!--<div>-->
                                        <input class="form-control sales_str" name="sales_str" style="width:70px;float: left;display: inline;"  type="text" value="<?php echo $datas['salesStr'];?>"/>
                                        <label style="width:20px;float: left;text-align: center;line-height: 35px;" >～</label>
                                        <input class="form-control sales_end" name="sales_end" style="width:70px;float: left;display: inline;"  type="text" value="<?php echo $datas['salesEnd'];?>"/>
                                    <!--</div>-->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="col-lg-3">
                            <div class="form-group" style="width:380px">
                                <label style="width:120px" class="control-label col-lg-5" for="">退货申请时间</label>
                                <?php
                                echo DateTimePicker::widget([
                                    'name'          => 'refund_start_date',
                                    'options'       => ['placeholder' => ''],
                                    'value'         => $datas['refundStartDate'],
                                    'pluginOptions' => [
                                        'autoclose'      => true,
                                        'format'         => 'yyyy-mm-dd hh:ii:ss',
                                        'todayHighlight' => true,
                                        'todayBtn'       => 'linked',
                                    ],

                                ]); ?>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group" style="width:250px">
                                <?php
                                echo DateTimePicker::widget([
                                    'name'          => 'refund_end_date',
                                    'options'       => ['placeholder' => ''],
                                    'value'         => $datas['refundEndDate'],
                                    'pluginOptions' => [
                                        'autoclose'      => true,
                                        'format'         => 'yyyy-mm-dd hh:ii:ss',
                                        'todayHighlight' => true,
                                    ]
                                ]); ?>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-left:20px;">搜索</button>
                    
                </form>
            </div>
        </div>
        <div class="bs-bars pull-left" style="padding-top: 7px;">
            共<?php if ($count) {
                echo $count;
            } else {
                echo 0;
            }; ?>条数据&nbsp;
        </div>
<!--        <div class="bs-bars pull-left">
            <div id="" class="btn-group">
                <a class="btn btn-success" id="download" target="_blank"
                   href="/aftersales/domesticreturngoods/downloadreceipt">下载数据</a>
            </div>
        </div>-->
        <table id="table" class="table table-bordered table-hover"
               data-striped="true"
               data-detail-view = "true"
        >

            <tr>
                <td>账号</td>
                <td>平台订单号/订单类型</td>
                <td>产品信息</td>
                <td>申请退款时间</td>
                <td>退货数量</td>
                <td>退货原因</td>
                <td>退货是否可售</td>
                <td>退货产品状态</td>
                <td>退货率</td>
                <td>销量统计</td>
            </tr>

            <?php if (!empty($receipts)) { ?>
                <?php foreach ($receipts as $item) {?>
                    <tr>
                        <td><?php echo $item['account_id']; ?></td>
                        <td><?php echo $item['platform_order_id'].'<br/>'.$item['order_type']; ?></td>
                        <td><?php echo $item['asin'];?></td>
                        <td><?php echo $item['return_date'];?></td>
                        <td><?php echo $item['qty'];?></td>
                        <td><?php echo $item['return_reason'];?></td>
                        <td><?php echo $item['is_available_sale'];?></td>
                        <td><?php echo $item['pro_status'];?></td>
                        <td><?php echo $item['refund_rate'];?></td>
                        <td><?php echo $item['sales'];?></td>
                    </tr>
                <?php }
                } else { ?>
                <tr><td colspan="10" style="text-align: center;">暂无数据</td></tr>
            <?php } ?>
        </table>
        <?php echo LinkPager::widget([
            'pagination' => $page,
        ]); ?>
    </div>
</div>


<script>
    $(document).ready(function(){  
　　　　$('#sales').change(function(){
            if($(this).val() == 0){
                $(".sales_str").val("");
                $(".sales_end").val("");
            }
        });
        
        $('#return_rate').change(function(){
            if($(this).val() == 0){
                $(".return_rate_str").val("");
                $(".return_rate_end").val("");
            }
        });
    });

</script>