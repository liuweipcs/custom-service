<style>
    #wrapper{font-size: 12px;}
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

    li{list-style: none;}
    .hear-title,.search-box ul{overflow: hidden;}
    .hear-title p:nth-child(1) span:nth-child(1),.hear-title p:nth-child(2) span:nth-child(1){display: inline-block;width: 30%}
    .item-list li{border-bottom: 1px solid #ddd;padding: 5px 10px}
    .item-list li span{display: inline-block;width: 25%}
    .search-box ul li{float: left;padding:0 10px 10px 0}
    .search-box textarea{display: block;margin-top: 10px;width: 100%}
    .info-box .det-info{width: 100%;height: 200px;border: 2px solid #ddd;}
    .well span{padding: 6%}
    .well p{text-align:left}
    #remarkTable tr td{width: 250px;}
    .table{margin-bottom: 0px;}

</style>
<div class="col-xs-12" style="margin-top: 10px;">
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-12">
                
                <div class="panel-group" id="accordion">
                    <?php
                        echo @$this->render('step',['info'=>$info['info'],'orderNodelist'=>$info['orderNodelist'],'ondeList'=>$info['ondeList']]);
                        echo $this->render('button',['order_id'=>$order_id,'platform'=>$platform,'info'=>$info,'transaction_id'=>$transaction_id]);
                        echo $this->render('order_info',['info'=>$info,'is_return'=>$is_return,'track_number'=>$track_number,'countries' => $countries,'returnid'=>$returnid,'isAuthority' => $isAuthority]);//订单信息
                        echo $this->render('product_detail',['info'=>$info,'is_return'=>$is_return,'returnid'=>$returnid,'track_number'=>$track_number,'platform' => $platform]);//产品详情
                        echo $this->render('transaction_record',['info'=>$info,'paypallist' => $paypallist]);//交易记录
                        echo $this->render('package_info',['info'=>$info]);//包裹信息
                        echo $this->render('profit',['info'=>$info]);//利润
                        echo $this->render('logistics',['info'=>$info,'returnid'=>$returnid,'is_return'=>$is_return,'track_number'=>$track_number,'warehouseList'=>$warehouseList]);//仓储物流
                        echo $this->render('aftersales',['afterSalesOrders'=>$afterSalesOrders]);//售后问题
                        echo $this->render('log',['info'=>$info]);//操作日志
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>