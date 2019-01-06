<style>

    .table tbody tr td {
        line-height: 1
    }

    .language li {
        width: 16%;
        float: left;
    }

    .language li a {
        font-size: 10px;
        text-align: left;
        cursor: pointer;
    }


    #wrapper .popup-body {
        padding-top: 15px;
    }

    dl {
        margin-bottom: 5px;
    }

    #wrapper .popup-footer {
        padding: 5px;
    }
</style>
<div class="col-xs-12" style="margin-top: 10px;">
    <div class="row">
        <div class="col-md-7">
            <div class="col-md-12">
                <div class="panel-group" id="accordion">
                    <?php
                    echo @$this->render('../../aliexpressdispute/issueinfo/step', ['info' => $info['info'], 'orderNodelist' => $info['orderNodelist'], 'ondeList' => $info['ondeList']]);
                    echo $this->render('../../aliexpressdispute/issueinfo/order_info', ['info' => $info, 'countries' => $countries, 'isAuthority' => $isAuthority]);//订单信息
                    echo $this->render('../../aliexpressdispute/issueinfo/product_detail', ['info' => $info, 'platform' => $platform]);//产品详情
                    echo $this->render('../../aliexpressdispute/issueinfo/transaction_record', ['info' => $info, 'paypallist' => $paypallist]);//交易记录
                    echo $this->render('../../aliexpressdispute/issueinfo/package_info', ['info' => $info]);//包裹信息
                    echo $this->render('../../aliexpressdispute/issueinfo/profit', ['info' => $info]);//利润
                    echo $this->render('../../aliexpressdispute/issueinfo/logistics', ['info' => $info, 'warehouseList' => $warehouseList]);//仓储物流
                    echo $this->render('../../aliexpressdispute/issueinfo/aftersales', ['afterSalesOrders' => $afterSalesOrders]);//售后问题
                    echo $this->render('../../aliexpressdispute/issueinfo/log', ['info' => $info]);//操作日志
                    ?>
                </div>
            </div>
        </div>

        <!--处理纠纷-->
        <div class="col-md-5">
            <div class="panel-group" id="issue_accordion">
                <?php
                echo $this->render('cancellation_handle', [
                    'id'               => !empty($id) ? $id : 0,
                    'order_id'         => $order_id,
                    'cancellationList' => $cancellationList,
                    'account_id'       => $account_id,
                    'afterSalesOrders' => $afterSalesOrders,
                    'info'=>$info['info']
                ]);
                ?>
            </div>
        </div>
    </div>
</div>