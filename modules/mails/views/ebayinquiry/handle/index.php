<style type="text/css">
    /* Custom Styles */
    ul.nav-tabs{
        width: 140px;
        margin-top: 20px;
        border-radius: 4px;
        border: 1px solid #ddd;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.067);
    }
    ul.nav-tabs li{
        margin: 0;
        border-top: 1px solid #ddd;
    }
    ul.nav-tabs li:first-child{
        border-top: none;
    }
    ul.nav-tabs li a{
        margin: 0;
        padding: 8px 16px;
        border-radius: 0;
    }
    ul.nav-tabs li.active a, ul.nav-tabs li.active a:hover{
        color: #fff;
        background: #0088cc;
        border: 1px solid #0088cc;
    }
    ul.nav-tabs li:first-child a{
        border-radius: 4px 4px 0 0;
    }
    ul.nav-tabs li:last-child a{
        border-radius: 0 0 4px 4px;
    }
    ul.nav-tabs.affix{
        top: 20%; /* Set the top position of pinned element */
    }
    
    .type_map_params{
        display: none;
    }
    .ebay_dispute_message_board
    {
        background: #F1F6FC;
    }
    #remarkTable tr td{width: 250px;}
    
    .language {width:750px;float: left;}
    .language li{width:16%;float:left;}
    .language li a{font-size: 10px; text-align: left;cursor: pointer;}
</style>
</head>
<body data-spy="scroll" data-target="#myScrollspy">
    <div class="col-xs-12">
        <div class="row">
            <div class="col-md-11">
                
                  
                <?php echo $this->render('transaction_record',['info'=>$info]);?>
                <?php echo $this->render('product_detail',['info'=>$info]);?>
                <div class="row" id="section-3">
                <?php echo $this->render('order_detail',[
                    'info'=>$info,
                    'accountName' => $accountName,
                    ]);?>
                <?php echo $this->render('warehouse_tracking',['info'=>$info]);?>
                </div>
                <div class="row" id="section-4">
                <?php echo $this->render('inquiry_detail',[
                    'order_id' => $order_id,
                    'info'=>$info,
                    'model'=>$model,
                    'detailModel' => $detailModel,
                    'accountName' => $accountName
                ]);?>
                <?php echo $this->render('customer_processing',[
                    'info'=>$info,
                    'model'=>$model,
                    'reasonCode' =>$reasonCode,
                    'googleLangCode'=>$googleLangCode]);?>    
                </div>
                <?php echo $this->render('profit_detail',['info'=>$info]);?>
            </div>
            
            <div class="col-md-1" id="myScrollspy">
                <ul class="nav nav-tabs nav-stacked" data-spy="affix" data-offset-top="60%">
                    <li  class="active"><a href="#section-1">交易记录</a></li>
                    <li><a href="#section-2">产品详情</a></li>
                    <li><a href="#section-3">订单&包裹&仓储</a></li>
                    <li><a href="#section-4">纠纷&处理</a></li>
                    <li><a href="#section-5">利润</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>