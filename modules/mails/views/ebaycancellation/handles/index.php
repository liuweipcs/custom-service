
<style>
    #wrapper{font-size: 13px;}
    .center{text-align: center;}
    .table tbody tr td{line-height:1}
    .type_map_params{display: none;}
    .panel-heading{padding: 2px 15px;}
    .language {width:720px;float: left; height: auto; max-height:250px;overflow-y:scroll;}
    .language li{width:16%;float:left;}
    .language li a{font-size: 10px; text-align: left;cursor: pointer;}
    .table{margin-bottom: -10px;}
</style> 
<div class="col-xs-12" style="margin-top: 10px;">
    <div class="row">
        <div class="col-md-7">
            <div class="col-md-12">
                <div class="panel-group" id="accordion">
                    <?php 
                        echo @$this->render('/ebayinquiry/handles/step',['info'=>$info['info'],'model'=>$model,'orderNodelist'=>$info['orderNodelist'],'ondeList'=>$info['ondeList']]);
                        echo $this->render('/ebayinquiry/handles/order_info',['info'=>$info,'accountName' => $accountName,'isAuthority'=>$isAuthority]);//订单信息
                        echo $this->render('/ebayinquiry/handles/package_info',['info'=>$info]);//包裹信息
                        echo $this->render('/ebayinquiry/handles/product_detail',['info'=>$info]);//产品详情
                        echo $this->render('/ebayinquiry/handles/transaction_record',['info'=>$info]);//交易记录
//                        echo $this->render('aftersales',['afterSalesOrders'=>$afterSalesOrders]);//售后问题
                        echo $this->render('/ebayinquiry/handles/logistics',['info'=>$info]);//仓储物流
                        echo $this->render('/ebayinquiry/handles/profit',['info'=>$info]);//利润
                        echo $this->render('/ebayinquiry/handles/log',['info'=>$info]);//操作日志
                    ?>
                </div>
            </div>
        </div>
        
        <!--处理纠纷-->
        <div class="col-md-5">
            <?php echo $this->render('customer_processing', [
                'order_id' => $order_id,
                'info'=>$info,
                'model'=>$model,
                'detailModel' => $detailModel,
                'accountName' => $accountName
                ]); ?>
        </div>
    </div>
</div>