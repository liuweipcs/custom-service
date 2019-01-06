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
    .complaint_mun{
        width: 260px;
        height: 111px;
        border: 1px solid #eadcdc;
        display: -webkit-inline-box;
        font-size: 19px;  
        margin-left: -4px;
    }
    .tables_ke{
        margin-left: 48px;
        margin-top: 28px;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div class="well">
                <form id="search-form" class="form-horizontal" action="<?php echo \Yii::$app->request->getUrl(); ?>"
                      method="get" role="form">
                    <input type="hidden" name="sortBy" value="">
                    <input type="hidden" name="sortOrder" value="">
                    <ul class="list-inline">
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">所属平台</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="platform_code">
                                        <option value="">全部</option>
                                        <?php foreach ($platformList as $code => $value) { ?>
                                            <option value="<?php echo $code; ?>" <?php if ($code == $platformCode) echo 'selected="selected"'; ?>><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="form-group"><label class="control-label col-lg-5" for="">SKU</label>
                                <div class="col-lg-7">
                                    <textarea rows="4" cols="12" name="sku" style="width: 181px;
                                              height: 80px;" placeholder="请输入sku，不同SKU以英文逗号隔开"><?php echo $sku; ?></textarea>    
                                </div>
                            </div>
                        </li>
                        <li style="margin-left: 70px;">
                            <div class="form-group"><label class="control-label col-lg-5" for="" style="width: 100px;">截止日期</label>
                                <div class="form-group" style="width:359px">
                                    <?php
                                    echo DateTimePicker::widget([
                                        'name' => 'end_date',
                                        'options' => ['placeholder' => ''],
                                        'value' => $end_time,
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
                    </ul>
                    <button type="submit" class="btn btn-primary">搜索</button>

                </form>
            </div>
        </div>
        <div>
            <div class="complaint_mun">
                <table class="tables_ke">
                    <tr>
                        <td style="text-align: center;">过去7天发货单量</td> 
                    </tr>
                    <tr>
                        <td style="text-align: center;color: #2697ab;"><?php echo $skuamunall7; ?></td>
                    </tr>
                </table>        
            </div>
            <div class="complaint_mun">
                <table class="tables_ke">
                    <tr>
                        <td style="text-align: center;">过去7天客诉量/占比</td> 
                    </tr>
                    <tr>
                          <?php if(!empty($skuamunall7)) {?>
                        <td style="text-align: center;color: #2697ab;"><?php echo $complaintskumunall7; ?>(<?php echo sprintf("%.2f",($complaintskumunall7 / $skuamunall7) * 100); ?>%)</td>
                          <?php }else{ ?>
                        <td style="text-align: center;color: #2697ab;">0</td>
                          <?php }?>
                    </tr>
                </table>        
            </div>
            <div class="complaint_mun">
                <table class="tables_ke">
                    <tr>
                        <td style="text-align: center;">过去15天发货单量</td> 
                    </tr>
                    <tr>
                        <td style="text-align: center;color: #2697ab;"><?php echo $skuamunall15; ?></td>
                    </tr>
                </table>        
            </div>
            <div class="complaint_mun">
                <table class="tables_ke">
                    <tr>
                        <td style="text-align: center;">过去15天客诉量/占比</td> 
                    </tr>
                    <tr>
                         <?php if(!empty($skuamunall15)) {?>
                         <td style="text-align: center;color: #2697ab;"><?php echo $complaintskumunall15; ?>(<?php echo sprintf("%.2f",($complaintskumunall15 / $skuamunall15) * 100); ?>%)</td>  <?php }else{ ?>
                         <td style="text-align: center;color: #2697ab;">0</td>
                         <?php }?>
                    </tr>
                </table>        
            </div>
            <div class="complaint_mun">
                <table class="tables_ke">
                    <tr>
                        <td style="text-align: center;">过去30天发货单量</td> 
                    </tr>
                    <tr>
                        <td style="text-align: center;color: #2697ab;"><?php echo $skuamunall30; ?></td>
                    </tr>
                </table>        
            </div>
            <div class="complaint_mun">
                <table class="tables_ke">
                    <tr>
                        <td style="text-align: center;">过去30天客诉量/占比</td> 
                    </tr>
                    <tr>
                        <?php if(!empty($skuamunall30)) {?>
                        <td style="text-align: center;color: #2697ab;"><?php echo $complaintskumunall30; ?>(<?php echo sprintf("%.2f",($complaintskumunall30 / $skuamunall30) * 100); ?>%)</td><?php }else{ ?>
                        <td style="text-align: center;color: #2697ab;">0</td>
                        <?php } ?>
                    </tr>
                </table>        
            </div>
        </div>
        <table class="table table-striped table-bordered">
            <tr>
                <th style=" text-align: center;">sku信息</th>
                <th style=" text-align: center;">过去7天发货单量</th>
                <th style="text-align: center;">过去7天客诉量/占比</th>
                <th style=" text-align: center;">过去15天发货单量</th>
                <th style=" text-align: center;">过去15天客诉量/占比</th>
                <th style=" text-align: center;">过去30天发货单量</th>
                <th style=" text-align: center;">过去30天客诉量/占比</th>
            </tr>
            <?php if ($count == 0 ||empty($skuamunall15) || empty($skuamunall7) || empty($skuamunall30)) { ?>
               <tr>
                 <td colspan="7">暂无数据</td>
                </tr>
            <?php } else { ?>
                    <?php foreach ($data as $key => $v) { ?>           
                    <tr>
                        <td>
                            <a href="http://120.24.249.36/product/index/sku/<?php echo $v['sku']; ?>" style="color:blue" target="_blank">  <?php echo $v['sku']; ?></a><br/>
                            <?php echo $v['title']; ?>
                        </td>
                        <td style=" text-align: center;"><?php echo $v['skuamun7']; ?></td>
                     
                        <?php if(empty($v['skuamun7'])){ ?>
                         <td style=" text-align: center;">0</td>
                        <?php }else{ ?>
                       <td style=" text-align: center;"><?php echo $v['complaintskumun7']; ?>(<?php echo sprintf("%.2f",($v['complaintskumun7'] / $v['skuamun7']) * 100)."%"; ?>)</td>
                        <?php } ?>
                        <td style=" text-align: center;"><?php echo $v['skuamun15']; ?></td>
                         <?php if(empty($v['skuamun15'])){ ?>
                        <td style=" text-align: center;">0</td>
                         <?php }else{?>
                         <td style=" text-align: center;"><?php echo $v['complaintskumun15']; ?>(<?php echo sprintf("%.2f",($v['complaintskumun15'] / $v['skuamun15'])* 100)."%"; ?>)</td>
                         <?php } ?>
                        <td style=" text-align: center;"><?php echo $v['skuamun30']; ?></td>
                         <?php if(empty($v['skuamun30'])){ ?>
                        <td style=" text-align: center;">0</td>
                         <?php }else{?>
                        <td style=" text-align: center;"><?php echo $v['complaintskumun30']; ?>(<?php echo sprintf("%.2f",($v['complaintskumun30'] / $v['skuamun30']) * 100)."%"; ?>%)</td>
                         <?php } ?>
                    </tr>
                <?php } ?>
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
</div>
