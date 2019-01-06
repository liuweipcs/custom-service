<style>
    #wrapper{font-size: 13px;}
    .center{text-align: center;}
    .table tbody tr td{line-height:1}
    .type_map_params{display: none;}
    .panel-heading{padding: 2px 15px;}
    .language {width:720px;float: left; height: auto; max-height:180px;overflow-y:scroll;}
    .language li{width:16%;float:left;}
    .language li a{font-size: 10px; text-align: left;cursor: pointer;}
    .tr_q .dropdown-menu{left:-135px;}
    .tr_h .dropdown-menu {left:-391px;}
    #wrapper .popup-body{padding-top: 15px;}
    dl{margin-bottom: 5px;}
    #wrapper .popup-footer{padding: 5px;}
</style> 
<div class="col-xs-12" style="margin-top: 10px;">
    <div class="row">
        <div class="col-md-7">
            <div class="col-md-12">
                <div class="panel-group" id="accordion">
                    <?php 
                        echo @$this->render('step',['info'=>$info['info'],'orderNodelist'=>$info['orderNodelist'],'ondeList'=>$info['ondeList']]);
                        echo $this->render('order_info',['info'=>$info,'accountName' => $accountName,'isAuthority' => $isAuthority]);//订单信息
                        echo $this->render('product_detail',['info'=>$info]);//产品详情
                        echo $this->render('package_info',['info'=>$info]);//包裹信息
                        echo $this->render('transaction_record',['info'=>$info]);//交易记录
                        echo $this->render('aftersales',['afterSalesOrders'=>$afterSalesOrders]);//售后问题
                        echo $this->render('logistics',['info'=>$info]);//仓储物流
                        echo $this->render('profit',['info'=>$info]);//利润
                        echo $this->render('log',['info'=>$info]);//操作日志
                    ?>
                </div>
            </div>
        </div>
        
        <!--处理纠纷-->
        <div class="col-md-5">
            <?php 
            echo $this->render('customer_processing', [
                    'order_id' => $order_id,
                    'info'=>$info,
                    'model'=>$model,
                    'reasonCode' =>$reasonCode,
                    'googleLangCode'=>$googleLangCode,
                    'detailModel' => $detailModel,
                    'accountName' => $accountName,
                    'departmentList' => $departmentList,
                    'inbox_info' => $inbox_info,
                    'author' => $author,
                    'refund_reason_arr' => $refund_reason_arr,
                ]); ?>
        </div>
    </div>
</div>