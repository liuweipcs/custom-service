<?php

use yii\helpers\Url;
use app\modules\aftersales\models\AfterSalesOrder;
use \app\modules\aftersales\models\OrderRedirectDetail;
use \app\modules\aftersales\models\OrderReturnDetail;
use \yii\helpers\Json;
use \app\modules\aftersales\models\AfterSalesProduct;
use kartik\select2\Select2;

?>
<div class="popup-wrapper">
    <?php
    // 获取问题产品信息
    $product_infos = AfterSalesProduct::find()->where(['order_id' => $afterSaleOrderModel->order_id])->all();
    $product_infos = AfterSalesProduct::find()->where(['after_sale_id' => $afterSaleOrderModel->after_sale_id])->all();
    if ($product_infos) {
        $product_infos = Json::decode(Json::encode($product_infos), true);
        $is_exist      = true;
    } else {
        if (!isset($info['product']) || empty($info['product'])) {
            echo '未查询到产品信息!';
            exit;
        }
        $product_infos = $info['product'];
        $is_exist      = false;
    }
    ?>
    <form action="<?php echo Url::toRoute([Yii::$app->request->getUrl(),
        'platform' => $info['info']['platform_code'],
        'order_id' => $info['info']['order_id'],

    ]); ?>" method="post" role="form" class="form-horizontal">
        <div class="popup-body">
            <div class="row">
                <div class="col-sm-5">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">问题产品</h3>
                        </div>
                        <div class="panel-body">
                            <table id="issue-product" class="table">
                                <thead>
                                <tr>
                                    <th width="50%">标题</th>
                                    <th width="20%">SKU</th>
                                    <th width="10%">数量</th>
                                    <th width="10%">产品线</th>
                                    <th width="10%">问题产品</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (isset($product_infos) && !empty($product_infos))
                                    foreach ($product_infos as $product) {
                                        ?>
                                        <tr>
                                            <td><?php echo isset($product['picking_name']) ? $product['picking_name'] : $product['product_title']; ?></td>
                                            <td><?php echo $product['sku']; ?></td>
                                            <td><?php echo $product['quantity']; ?></td>
                                            <td><?php echo $product['linelist_cn_name']; ?></td>
                                            <td><input class="form-control col-lg-4" type="text" size="4"
                                                       name="issue_product[<?php echo $product['sku']; ?>]"
                                                       value="<?php echo $is_exist ? $product['issue_quantity'] : '0'; ?>"/>
                                            </td>
                                            <input type="hidden" name="issue_product_id[<?php echo $product['sku']; ?>]"
                                                   value="<?php echo $is_exist ? $product['id'] : null; ?>">
                                        </tr>
                                        <?php
                                    }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="panel panel-default">
                        <?php
                        echo $this->render('order_info', ['info' => $info, 'isAuthority' => $isAuthority]);
                        echo $this->render('../order/transaction_record', ['info' => $info, 'paypallist' => $paypallist]);//交易记录
                        echo $this->render('../order/package_info', ['info' => $info]);//包裹信息
                        echo $this->render('../order/logistics', ['info' => $info, 'warehouseList' => $warehouseList]);//仓储物流
                        //echo $this->render('../order/aftersales',['afterSalesOrders'=>$afterSalesOrders]);//售后问题
                        echo $this->render('../order/log', ['info' => $info]);//操作日志
                        ?>
                    </div>
                </div>
                <div class="col-sm-7">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">原因</h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <div class="col-sm-3">
                                            <label for="ship_name" class=" control-label required">责任所属部门：<span
                                                        class="text-danger">*</span></label>
                                            <select name="department_id" id="department_id" class="form-control"
                                                    size="12" multiple="multiple">
                                            </select>
                                        </div>
                                        <div class="col-sm-9">
                                            <label for="ship_name" class="control-label required">原因类型：<span
                                                        class="text-danger">*</span></label>
                                            <select name="reason_id" id="reason_id" class="form-control" size="12"
                                                    multiple="multiple">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="after_sale_order_id"
                                   value="<?php echo $afterSaleOrderModel->after_sale_id; ?>">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="ship_street1" class="col-sm-1 control-label">备注：</label>
                                        <div class="col-sm-11">
                                            <textarea rows="4" cols="12" name="remark"
                                                      class="form-control"><?php echo $afterSaleOrderModel->remark; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">售后单类型</h3>
                        </div>
                        <div class="panel-body">

                            <?php
                            switch ($type) {
                                case AfterSalesOrder::ORDER_TYPE_REFUND:
                                    echo '<label class="checkbox-inline">
                                    <input name="after_sales_type" type="hidden" id="refund-input" value="' . AfterSalesOrder::ORDER_TYPE_REFUND . '">退款
                                  </label>';
                                    break;
                                case AfterSalesOrder::ORDER_TYPE_RETURN:
                                    echo '<label class="checkbox-inline">
                                    <input name="after_sales_type" type="hidden" id="return-input" value="' . AfterSalesOrder::ORDER_TYPE_RETURN . '">退货
                                  </label>';
                                    break;
                                case AfterSalesOrder::ORDER_TYPE_REDIRECT:
                                    echo '<label class="checkbox-inline">
                                    <input name="after_sales_type" type="hidden" id="redirect-input" value="' . AfterSalesOrder::ORDER_TYPE_REDIRECT . '">重寄
                                  </label>';
                                    break;
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                    switch ($type) {
                        case AfterSalesOrder::ORDER_TYPE_REFUND:
                            ?>
                            <div class="panel panel-default" id="refund-box">
                                <div class="panel-heading">
                                    <h3 class="panel-title">退款信息</h3>
                                </div>
                                <?php
                                if (isset($refundOrderInfo) && !empty($refundOrderInfo)) { ?>
                                    <div class="alert alert-danger" style="margin-top: 5px;">
                                        注意,当前订单已有退款单&nbsp;&nbsp;
                                        <?php foreach ($refundOrderInfo as $value) {
                                            ?>
                                            <a _width="80%" class="edit-button"
                                               href="/aftersales/sales/detailrefund?after_sale_id=<?php echo $value->after_sale_id; ?>&amp;platform_code=<?php echo $value->platform_code; ?>&amp;status=<?php echo $value->status; ?>"><?php echo $value->after_sale_id; ?></a> &nbsp;&nbsp;
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <div class="panel-body">
                                    <div class="col-sm-12">
                                        <div class="panel-body">
                                            <table class="table" id="tab" class="col-sm-6">
                                                <tbody>
                                                <tr>
                                                    <td>SKU</td>
                                                    <td>平台退款原因</td>
                                                    <td>退款类型</td>
                                                    <td>
                                                        退款金额(<?php echo !empty($info['product'][0]['currency']) ? $info['product'][0]['currency'] : ''; ?>
                                                        )
                                                    </td>
                                                    <td>
                                                        税费(<?php echo !empty($info['product'][0]['currency']) ? $info['product'][0]['currency'] : ''; ?>
                                                        )
                                                    </td>
                                                    <td>
                                                        订单原金额(<?php echo !empty($info['product'][0]['currency']) ? $info['product'][0]['currency'] : ''; ?>
                                                        )
                                                    </td>
                                                    <td>操作</td>
                                                </tr>
                                                <?php foreach ($info['product'] as $value) { ?>
                                                    <?php if ($value['ship_price'] != 0.00) { ?>
                                                        <?php if (!empty($refund_detail)) { ?>
                                                            <?php foreach ($refund_detail as $v) { ?>
                                                                <!--如退款 取退款详情信息-->
                                                                <?php if ($v['item_id'] == intval($value['item_id'])) { ?>
                                                                    <tr style="color: green">
                                                                        <td><?php echo $value['sku']; ?></td>
                                                                        <td class="cov-content">
                                                                            <select class="form-control"
                                                                                    name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][refundReason]">
                                                                                <option value="CustomerChangedMind">
                                                                                    Customer
                                                                                    Changed Mind
                                                                                </option>
                                                                            </select>
                                                                        </td>
                                                                        <td class="cov-content">
                                                                            <select class="form-control"
                                                                                    name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][chargeType]">
                                                                                <option value="PRODUCT" selected>产品退款
                                                                                </option>
                                                                            </select>
                                                                        </td>

                                                                        <td class="cov-content">
                                                                            <input type="hidden"
                                                                                   name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][currency]"
                                                                                   value="<?php echo $value['currency']; ?>"/>
                                                                            <input type="text" style="width:80px;"
                                                                                   class="form-control"
                                                                                   name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][amount]"
                                                                                   value="<?php echo $v['amount']; ?>"/>
                                                                        </td>
                                                                        <td class="cov-content">
                                                                            <input type="text" style="width:50px;"
                                                                                   class="form-control"
                                                                                   name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][tax]"
                                                                                   value="0"/>
                                                                        </td>
                                                                        <td>
                                                                            <?php echo $value['total_price']; ?>
                                                                        </td>
                                                                        <td class="cov-content">
                                                                            <a onclick="delTr(this)">删除</a>&nbsp;&nbsp;&nbsp;&nbsp;<a
                                                                                    onclick="addTr(this,<?php echo $value['item_id']; ?>,<?php echo $value['total_price']; ?>)">添加</a>
                                                                        </td>
                                                                    </tr>
                                                                    <tr style="color: green">
                                                                        <td></td>
                                                                        <td class="cov-content">
                                                                            <select class="form-control"
                                                                                    name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][refundReason]">
                                                                                <option value="CustomerChangedMind">
                                                                                    Customer
                                                                                    Changed Mind
                                                                                </option>
                                                                            </select>
                                                                        </td>
                                                                        <td class="cov-content">
                                                                            <select class="form-control"
                                                                                    name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][chargeType]">
                                                                                <option value="SHIPPING" selected>运费退款
                                                                                </option>
                                                                            </select>
                                                                        </td>

                                                                        <td class="cov-content">
                                                                            <input type="hidden"
                                                                                   name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][currency]"
                                                                                   value="<?php echo $value['currency']; ?>"/>
                                                                            <input type="text" style="width:80px;"
                                                                                   class="form-control"
                                                                                   name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][amount]"
                                                                                   value="<?php echo $v['tax']; ?>"/>
                                                                        </td>
                                                                        <td class="cov-content" style="width: 60px">
                                                                            <input type="text" style="width:50px;"
                                                                                   class="form-control"
                                                                                   name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][tax]"
                                                                                   value="0"/>
                                                                        </td>
                                                                        <td>
                                                                            <?php echo $value['total_price']; ?>
                                                                        </td>
                                                                        <td class="cov-content">
                                                                            <a onclick="delTr(this)">删除</a>&nbsp;&nbsp;&nbsp;&nbsp;<a
                                                                                    onclick="addTr(this,<?php echo $value['item_id']; ?>,<?php echo $value['total_price']; ?>)">添加</a>
                                                                        </td>
                                                                    </tr>
                                                                <?php } ?>

                                                            <?php } ?>
                                                        <?php } else { ?>
                                                            <tr>
                                                                <td><?php echo $value['sku']; ?></td>
                                                                <td class="cov-content">
                                                                    <select class="form-control"
                                                                            name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][refundReason]">
                                                                        <option value="CustomerChangedMind">
                                                                            Customer
                                                                            Changed Mind
                                                                        </option>
                                                                    </select>
                                                                </td>
                                                                <td class="cov-content">
                                                                    <select class="form-control"
                                                                            name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][chargeType]">
                                                                        <option value="PRODUCT" selected>产品退款
                                                                        </option>
                                                                    </select>
                                                                </td>

                                                                <td class="cov-content">
                                                                    <input type="hidden"
                                                                           name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][currency]"
                                                                           value="<?php echo $value['currency']; ?>"/>
                                                                    <input type="text" style="width:80px;"
                                                                           class="form-control"
                                                                           name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][amount]"
                                                                           value="<?php echo $value['sale_price']; ?>"/>
                                                                </td>
                                                                <td class="cov-content">
                                                                    <input type="text" style="width:50px;"
                                                                           class="form-control"
                                                                           name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][tax]"
                                                                           value="0"/>
                                                                </td>
                                                                <td>
                                                                    <?php echo $value['total_price']; ?>
                                                                </td>
                                                                <td class="cov-content">
                                                                    <a onclick="delTr(this)">删除</a>&nbsp;&nbsp;&nbsp;&nbsp;<a
                                                                            onclick="addTr(this,<?php echo $value['item_id']; ?>,<?php echo $value['total_price']; ?>)">添加</a>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td></td>
                                                                <td class="cov-content">
                                                                    <select class="form-control"
                                                                            name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][refundReason]">
                                                                        <option value="CustomerChangedMind">
                                                                            Customer
                                                                            Changed Mind
                                                                        </option>
                                                                    </select>
                                                                </td>
                                                                <td class="cov-content">
                                                                    <select class="form-control"
                                                                            name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][chargeType]">
                                                                        <option value="SHIPPING" selected>运费退款
                                                                        </option>
                                                                    </select>
                                                                </td>

                                                                <td class="cov-content">
                                                                    <input type="hidden"
                                                                           name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][currency]"
                                                                           value="<?php echo $value['currency']; ?>"/>
                                                                    <input type="text" style="width:80px;"
                                                                           class="form-control"
                                                                           name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][amount]"
                                                                           value="<?php echo $value['ship_price']; ?>"/>
                                                                </td>
                                                                <td class="cov-content" style="width: 60px">
                                                                    <input type="text" style="width:50px;"
                                                                           class="form-control"
                                                                           name="refundsku[<?php echo $value['item_id']; ?>][SHIPPING][tax]"
                                                                           value="0"/>
                                                                </td>
                                                                <td>
                                                                    <?php echo $value['total_price']; ?>
                                                                </td>
                                                                <td class="cov-content">
                                                                    <a onclick="delTr(this)">删除</a>&nbsp;&nbsp;&nbsp;&nbsp;<a
                                                                            onclick="addTr(this,<?php echo $value['item_id']; ?>,<?php echo $value['total_price']; ?>)">添加</a>
                                                                </td>
                                                            </tr>

                                                        <?php } ?>
                                                    <?php } else { ?>
                                                        <?php if (!empty($refund_detail)) { ?>
                                                            <?php foreach ($refund_detail as $v) { ?>
                                                                <?php if ($v['item_id'] == intval($value['item_id'])) { ?>
                                                                    <tr style="color: green">
                                                                        <td><?php echo $value['sku']; ?></td>
                                                                        <td class="cov-content">
                                                                            <select class="form-control"
                                                                                    name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][refundReason]">
                                                                                <option value="CustomerChangedMind">
                                                                                    Customer
                                                                                    Changed Mind
                                                                                </option>
                                                                            </select>
                                                                        </td>
                                                                        <td class="cov-content">
                                                                            <select class="form-control"
                                                                                    name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][chargeType]">
                                                                                <option value="PRODUCT">产品退款</option>
                                                                                <option value="SHIPPING">运费退款</option>
                                                                            </select>
                                                                        </td>

                                                                        <td class="cov-content">
                                                                            <input type="hidden"
                                                                                   name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][currency]"
                                                                                   value="<?php echo $value['currency']; ?>"/>
                                                                            <input type="text" style="width:80px;"
                                                                                   class="form-control"
                                                                                   name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][amount]"
                                                                                   value="<?php echo $v['amount']; ?>"/>
                                                                        </td>
                                                                        <td class="cov-content">
                                                                            <input type="text" style="width:50px;"
                                                                                   class="form-control"
                                                                                   name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][tax]"
                                                                                   value="0"/>
                                                                        </td>

                                                                        <td>
                                                                            <?php echo $value['total_price']; ?>
                                                                        </td>
                                                                        <td class="cov-content">
                                                                            <a onclick="delTr(this)">删除</a>&nbsp;&nbsp;&nbsp;&nbsp;<a
                                                                                    onclick="addTr(this,<?php echo $value['item_id']; ?>,<?php echo $value['total_price']; ?>)">添加</a>
                                                                        </td>
                                                                    </tr>
                                                                <?php } ?>
                                                            <?php } ?>

                                                        <?php } else { ?>
                                                            <tr>
                                                                <td><?php echo $value['sku']; ?></td>
                                                                <td class="cov-content">
                                                                    <select class="form-control"
                                                                            name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][refundReason]">
                                                                        <option value="CustomerChangedMind">Customer
                                                                            Changed Mind
                                                                        </option>
                                                                    </select>
                                                                </td>
                                                                <td class="cov-content">
                                                                    <select class="form-control"
                                                                            name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][chargeType]">
                                                                        <option value="PRODUCT">产品退款</option>
                                                                        <option value="SHIPPING">运费退款</option>
                                                                    </select>
                                                                </td>

                                                                <td class="cov-content">
                                                                    <input type="hidden"
                                                                           name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][currency]"
                                                                           value="<?php echo $value['currency']; ?>"/>
                                                                    <input type="text" style="width:80px;"
                                                                           class="form-control"
                                                                           name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][amount]"
                                                                           value="<?php echo $value['total_price']; ?>"/>
                                                                </td>
                                                                <td class="cov-content">
                                                                    <input type="text" style="width:50px;"
                                                                           class="form-control"
                                                                           name="refundsku[<?php echo $value['item_id']; ?>][PRODUCT][tax]"
                                                                           value="0"/>
                                                                </td>
                                                                <td>
                                                                    <?php echo $value['total_price']; ?>
                                                                </td>
                                                                <td class="cov-content">
                                                                    <a onclick="delTr(this)">删除</a>&nbsp;&nbsp;&nbsp;&nbsp;<a
                                                                            onclick="addTr(this,<?php echo $value['item_id']; ?>,<?php echo $value['total_price']; ?>)">添加</a>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>

                                                    <?php } ?>
                                                <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-10">
                                                <div class="form-group">
                                                    <label for="ship_street1" class="col-sm-3 control-label">订单留言：<span
                                                                class="text-danger">&nbsp;</span></label>
                                                    <div class="col-sm-9">
                                                <textarea rows="6" cols="12" name="refundComments"
                                                          class="form-control"><?php echo $refund_comments; ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            break;
                        case AfterSalesOrder::ORDER_TYPE_RETURN:
                            ?>
                            <?php
                            // 查询退货(产品)详情表
                            $product_details = OrderReturnDetail::find()->where(['after_sale_id' => $afterSaleOrderModel->after_sale_id])->all();
                            $product_details = Json::decode(Json::encode($product_details), true);
                            ?>
                            <div class="panel panel-default" id="return-box">
                                <div class="panel-heading">
                                    <h3 class="panel-title">退货信息</h3>
                                </div>
                                <div class="panel-body">
                                    <table id="return-product" class="table">
                                        <thead>
                                        <tr>
                                            <th width="50%">标题</th>
                                            <th width="20%">SKU</th>
                                            <th width="10%">数量</th>
                                            <th width="10%">产品线</th>
                                            <th width="10%">操作</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (isset($product_details) && !empty($product_details))
                                            foreach ($product_details as $product) {
                                                ?>
                                                <tr>
                                                    <input type="hidden" name="return_product_id[]"
                                                           value="<?php echo $product['id']; ?>">
                                                    <td><input class="form-control" type="text" name="return_title[]"
                                                               value="<?php echo $product['product_title']; ?>"/></td>
                                                    <td><input class="form-control" type="text" name="return_sku[]"
                                                               value="<?php echo $product['sku']; ?>"/></td>
                                                    <td><input class="form-control" type="text" name="return_quantity[]"
                                                               value="<?php echo $product['quantity']; ?>"/></td>
                                                    <td><input class="form-control" type="text"
                                                               name="return_linelist_cn_name[]"
                                                               value="<?php echo $product['linelist_cn_name']; ?>"/>
                                                    </td>
                                                    <td><a href="javascript:void(0)" id="delete-row-button">删除</a></td>
                                                </tr>
                                                <?php
                                            }
                                        ?>
                                        </tbody>
                                    </table>
                                    <div><a href="javascript:void(0)" id="add-return-product-row">添加产品</a></div>
                                    <br/>
                                    <div class="row">
                                        <div class="col-sm-10">
                                            <div class="form-group">
                                                <label for="ship_street1" class="col-sm-3 control-label">退回仓库：<span
                                                            class="text-danger">*</span></label>
                                                <div class="col-sm-6">
                                                    <?php
                                                    echo Select2::widget([
                                                        'id'      => 'return_warehouse_id',
                                                        'name'    => 'return_warehouse_id',
                                                        'data'    => $warehouseList,
                                                        'value'   => $afterSaleDetail->warehouse_id,
                                                        'options' => [
                                                            'placeholder' => '--请输入--',
                                                            'onchange'    => 'getLogistics(this)'
                                                        ],
                                                    ]);
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-10">
                                            <div class="form-group">
                                                <label for="ship_street1"
                                                       class="col-sm-3 control-label">退回的运输方式：</label>
                                                <div class="col-sm-6">
                                                    <input class="form-control" type="text" name="return_carrier"
                                                           value="<?php echo $afterSaleDetail->carrier ?>"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-10">
                                            <div class="form-group">
                                                <label for="ship_street1" class="col-sm-3 control-label">退回的跟踪号：</label>
                                                <div class="col-sm-6">
                                                    <input class="form-control" type="text" name="return_tracking_no"
                                                           value="<?php echo $afterSaleDetail->tracking_no ?>"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            break;
                        case AfterSalesOrder::ORDER_TYPE_REDIRECT:
                            ?>
                            <?php
                            // 查询重寄(产品)详情表
                            $product_details = OrderRedirectDetail::find()->where(['after_sale_id' => $afterSaleOrderModel->after_sale_id])->all();
                            $product_details = Json::decode(Json::encode($product_details), true);
                            ?>
                            <div class="panel panel-default" id="redirect-box">
                                <div class="panel-heading">
                                    <h3 class="panel-title">重寄信息</h3>
                                </div>
                                <?php if (isset($redirectOrderInfo) && !empty($redirectOrderInfo)) { ?>
                                    <div class="alert alert-danger" style="margin-top: 5px;">
                                        注意,当前订单已有重寄单&nbsp;&nbsp;
                                        <?php foreach ($redirectOrderInfo as $value) { ?>
                                            <a _width="80%" class="edit-button"
                                               href="/aftersales/sales/detailredirect?after_sale_id=<?php echo $value->after_sale_id; ?>&amp;platform_code=<?php echo $value->platform_code; ?>&amp;status=<?php echo $value->status; ?>"><?php echo $value->after_sale_id; ?></a> &nbsp;&nbsp;
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                                <div class="panel-body">
                                    <div class="panel-group" id="accordion">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h4 class="panel-title">
                                                    <a data-toggle="collapse" data-parent="#accordion"
                                                       href="#collapseOne">订单产品信息</a>
                                                    <?php if ($info['info']['platform_code'] != 'EB'): ?>
                                                        <a style="color:blue" id="figure_redirect_lost">计算亏损</a>&nbsp;<b
                                                                style="color:red" id="redirect_lost"></b>
                                                    <?php endif; ?>
                                                </h4>
                                            </div>
                                            <div id="collapseOne" class="panel-collapse collapse in">
                                                <div class="panel-body">
                                                    <table id="product-table" class="table">
                                                        <thead>
                                                        <tr>
                                                            <th>标题</th>
                                                            <th>SKU</th>
                                                            <th>数量</th>
                                                            <th>产品线</th>
                                                            <th>操作</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <?php foreach ($product_details as $row) { ?>
                                                            <tr>
                                                                <input class="form-control" type="hidden"
                                                                       name="redirect_product_id[]"
                                                                       value="<?= $row['id']; ?>"/>
                                                                <td class="picking_name"><input class="form-control"
                                                                                                type="text"
                                                                                                name="product_title[]"
                                                                                                value="<?= $row['product_title']; ?>"/>
                                                                </td>
                                                                <td><input class="form-control" type="text" name="sku[]"
                                                                           size="12" value="<?= $row['sku']; ?>"
                                                                           onblur="get_sku(this)"/></td>
                                                                <td><input class="form-control" type="text"
                                                                           name="quantity[]" size="6"
                                                                           value="<?= $row['quantity']; ?>"/></td>
                                                                <td class="linelist_cn_name"><input class="form-control"
                                                                                                    type="text"
                                                                                                    name="redirect_linelist_cn_name[]"
                                                                                                    size="6"
                                                                                                    value="<?= $row['linelist_cn_name']; ?>"/>
                                                                </td>
                                                                <input type="hidden" name="item_id[]"
                                                                       value="<?= $row['item_id'] ?>">
                                                                <input type="hidden" name="transaction_id[]"
                                                                       value="<?= $row['transaction_id'] ?>">
                                                                <td><a href="javascript:void(0)" id="delete-row-button">删除</a>
                                                                </td>
                                                                <td>
                                                            </tr>
                                                        <?php } ?>
                                                        </tbody>
                                                    </table>
                                                    <div><a href="javascript:void(0)" id="add-product-row">添加产品</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h4 class="panel-title">
                                                    <a data-toggle="collapse" data-parent="#accordion"
                                                       href="#collapseTwo">发货地址信息</a>
                                                </h4>
                                            </div>
                                            <div id="collapseTwo" class="panel-collapse collapse">
                                                <input type="hidden" name="after_sale_detail_redirect"
                                                       value="<?php echo $afterSaleDetail->after_sale_id ?>">
                                                <div class="panel-body">
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="ship_name"
                                                                       class="col-sm-3 control-label required">收件人<span
                                                                            class="text-danger">*</span></label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" name="ship_name"
                                                                           value="<?php echo $afterSaleDetail->ship_name; ?>"
                                                                           class="form-control" id="ship_name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="ship_street1"
                                                                       class="col-sm-3 control-label">地址1<span
                                                                            class="text-danger">*</span></label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" name="ship_street1"
                                                                           value="<?php echo $afterSaleDetail->ship_street1; ?>"
                                                                           class="form-control" id="ship_street1">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="ship_street2"
                                                                       class="col-sm-3 control-label required">地址2</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text"
                                                                           value="<?php echo $afterSaleDetail->ship_street2; ?>"
                                                                           name="ship_street2" class="form-control"
                                                                           id="ship_street2">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="ship_city_name"
                                                                       class="col-sm-3 control-label">城市<span
                                                                            class="text-danger">*</span></label>
                                                                <div class="col-sm-9">
                                                                    <input type="text"
                                                                           value="<?php echo $afterSaleDetail->ship_city_name; ?>"
                                                                           name="ship_city_name" class="form-control"
                                                                           id="ship_city_name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="ship_stateorprovince"
                                                                       class="col-sm-3 control-label">省/州</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text"
                                                                           value="<?php echo $afterSaleDetail->ship_stateorprovince; ?>"
                                                                           name="ship_stateorprovince"
                                                                           class="form-control"
                                                                           id="ship_stateorprovince">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="ship_country"
                                                                       class="col-sm-3 control-label">国家<span
                                                                            class="text-danger">*</span></label>
                                                                <div class="col-sm-9">
                                                                    <select name="ship_country" id="ship_country"
                                                                            class="form-control">
                                                                        <option value="">选择国家</option>
                                                                        <?php foreach ($countries as $code => $name) { ?>
                                                                            <option<?php echo $afterSaleDetail->ship_country == $code ? ' selected="selected"' : ''; ?>
                                                                                    value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                                                        <?php } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="ship_zip"
                                                                       class="col-sm-3 control-label">邮编<span
                                                                            class="text-danger">*</span></label>
                                                                <div class="col-sm-9">
                                                                    <input type="text"
                                                                           value="<?php echo $afterSaleDetail->ship_zip; ?>"
                                                                           name="ship_zip" class="form-control"
                                                                           id="ship_zip">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="ship_phone" class="col-sm-3 control-label">电话</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text"
                                                                           value="<?php echo $afterSaleDetail->ship_phone; ?>"
                                                                           name="ship_phone" class="form-control"
                                                                           id="ship_phone">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h4 class="panel-title">
                                                    <a data-toggle="collapse" data-parent="#accordion"
                                                       href="#collapseThr">仓库物流信息</a>
                                                </h4>
                                            </div>
                                            <div id="collapseThr" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="warehouse_id"
                                                                       class="col-sm-3 control-label required">发货仓库<span
                                                                            class="text-danger">*</span></label>
                                                                <div class="col-sm-9">
                                                                    <?php
                                                                    echo Select2::widget([
                                                                        'id'      => 'warehouse_id',
                                                                        'name'    => 'warehouse_id',
                                                                        'data'    => $warehouseList,
                                                                        'value'   => $afterSaleDetail->warehouse_id,
                                                                        'options' => [
                                                                            'placeholder' => '--请输入--',
                                                                            'onchange'    => 'getLogistics(this)'
                                                                        ],
                                                                    ]);
                                                                    ?>
                                                                    <input type="hidden" name="warehouse_name"
                                                                           id="warehouse_name"
                                                                           value="<?php echo $afterSaleDetail->warehouse_name; ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="ship_code" class="col-sm-3 control-label">邮寄方式<span
                                                                            class="text-danger">*</span></label>
                                                                <div class="col-sm-9">
                                                                    <?php
                                                                    echo Select2::widget([
                                                                        'id'      => 'ship_code',
                                                                        'name'    => 'ship_code',
                                                                        'data'    => $logistics_arr,
                                                                        'value'   => $afterSaleDetail->ship_code,
                                                                        'options' => [
                                                                            'placeholder' => '--请输入--',
                                                                            'onchange'    => 'getShipName(this)'
                                                                        ],
                                                                    ]);
                                                                    ?>
                                                                    <input type="hidden" name="ship_code_name"
                                                                           id="ship_code_name"
                                                                           value="<?php echo $afterSaleDetail->ship_code_name; ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($info['info']['platform_code'] == 'EB'): ?>
                                            <div class="panel panel-default">
                                                <div class="panel-heading">
                                                    <h4 class="panel-title">
                                                        <a data-toggle="collapse" data-parent="#accordion"
                                                           href="#collapseFour">计算亏损</a>
                                                    </h4>
                                                </div>
                                                <div id="collapseFour" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <div class="row">
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label for="warehouse_id"
                                                                           class="col-sm-3 control-label required"><a
                                                                                style="color:blue"
                                                                                id="figure_redirect_lost">计算亏损</a>&nbsp;<span
                                                                                class="text-danger"></span></label>
                                                                    <div class="col-sm-9">
                                                                        <b style="color:red" id="redirect_lost"></b>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="panel-body">
                                                        <div class="row">
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label for="warehouse_id"
                                                                           class="col-sm-3 control-label required">paypal交易号<span
                                                                                class="text-danger"></span></label>
                                                                    <div class="col-sm-9">
                                                                        <input type="text" id="paypal_record">
                                                                        <button id="search_payapl_record"
                                                                                class="btn btn-default" type="button">搜索
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div id="paypal_table">

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h4 class="panel-title">
                                                    <a data-toggle="collapse" data-parent="#accordion"
                                                       href="#collapseFive">订单/发货备注</a>
                                                </h4>
                                            </div>
                                            <div id="collapseFive" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="order_remark"
                                                                       class="col-sm-3 control-label">订单备注</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" name="order_remark"
                                                                           class="form-control" id="order_remark"
                                                                           value="<?php echo $afterSaleDetail->order_remark; ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="print_remark"
                                                                       class="col-sm-3 control-label">发货备注</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" name="print_remark"
                                                                           class="form-control" id="print_remark"
                                                                           value="<?php echo $afterSaleDetail->print_remark; ?>">(发货备注字节长度不能超过200,1个汉字3字节长度)
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h4 class="panel-title">
                                                    <a data-toggle="collapse" data-parent="#accordion"
                                                       href="#collapseSix">加钱重寄金额</a>
                                                </h4>
                                            </div>
                                            <div id="collapseSix" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="order_remark"
                                                                       class="col-sm-3 control-label">加钱重寄金额</label>
                                                                <div class="col-sm-2">
                                                                    <input type="text" name="order_amount"
                                                                           class="form-control"
                                                                           value="<?php echo $afterSaleDetail->order_amount; ?>"
                                                                           id="order_amount" style="width: 90px;">
                                                                </div>
                                                                <div class="col-sm-2">
                                                                    <div class="form-control"
                                                                         disabled="disabled"><?php echo $currencyCode; ?></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php
                            break;
                    } ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <?php echo $this->render('../order/profit', ['info' => $info]);//利润?>
                </div>
            </div>

        </div>
        <div class="popup-footer">
            <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
            <button class="btn btn-default close-button" type="button"><?php echo Yii::t('system', 'Close'); ?></button>
        </div>
    </form>
</div>
<script type="text/javascript">
    //初始化加载
    $(document).ready(function ($) {
        departmentList = <?php echo $departmentList?>;
        var rightHtml = "";
        for (var i in departmentList) {
            if (<?php echo $afterSaleOrderModel->department_id; ?>== departmentList[i].depart_id
        )
            {
                rightHtml += '<option  selected="selected" value="' + departmentList[i].depart_id + '">' + departmentList[i].depart_name + '</option>' + "\n";

            }
        else
            {
                rightHtml += '<option value="' + departmentList[i].depart_id + '">' + departmentList[i].depart_name + '</option>' + "\n";
            }
        }

        $('#department_id').empty().html(rightHtml);
        var selected_depart_id = $("#department_id").val();
        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: '<?php echo Url::toRoute(['/aftersales/refundreason/getnetleveldata']); ?>',
            data: {'id': selected_depart_id},
            success: function (data) {
                var html = "";
                if (data) {
                    $.each(data, function (n, value) {
                        if (<?php echo $afterSaleOrderModel->reason_id;?>==n
                    )
                        {
                            html += '<option  selected="selected" value=' + n + '>' + value + '</option>';
                        }
                    else
                        {
                            html += '<option value=' + n + '>' + value + '</option>';
                        }
                    });
                } else {
                    html = '<option value="">---请选择---</option>';
                }
                $("#reason_id").empty();
                $("#reason_id").append(html);
            }
        });
    });
    $('a#add-return-product-row').click(function () {
        var html = '<tr>' + "\n" +
            '<input class="form-control" type="hidden" name="return_product_id[]" value="" />' + "\n" +
            '<td><input class="form-control" type="text" name="return_title[]" value="" /></td>' + "\n" +
            '<td><input class="form-control" type="text" name="return_sku[]" size="12" value="" /></td>' + "\n" +
            '<td><input class="form-control" type="text" name="return_quantity[]" size="6" value="1" /></td>' + "\n" +
            '<td><input class="form-control" type="text" name="return_linelist_cn_name[]" /></td>' + "\n" +
            '<td><a href="javascript:void(0)" id="delete-row-button">删除</a></td>' + "\n" +
            '<tr>' + "\n";
        $('table#return-product tbody').append(html);
        $('a#delete-row-button').click(function () {
            $(this).parents('tr').remove();
        });
    });
    $('a#add-product-row').click(function () {
        var html = '<tr>' + "\n" +
            '<input class="form-control" type="hidden" name="redirect_product_id[]" value="" />' + "\n" +
            '<td class="picking_name"><input class="form-control" type="text" name="product_title[]" value="" /></td>' + "\n" +
            '<td><input class="form-control" type="text" name="sku[]" size="12" value="" onblur="get_sku(this)" /></td>' + "\n" +
            '<td><input class="form-control" type="text" name="quantity[]" size="6" value="1" /></td>' + "\n" +
            '<td class="linelist_cn_name"><input class="form-control" type="text" name="redirect_linelist_cn_name[]" value="" /></td>' +
            '<td><a href="javascript:void(0)" id="delete-row-button">删除</a></td>' + "\n" +
            '<tr>' + "\n";
        $('table#product-table tbody').append(html);
        $('a#delete-row-button').click(function () {
            $(this).parents('tr').remove();
        });
    });
    $('a#delete-row-button').click(function () {
        $(this).parents('tr').remove();
    });

    //根据仓库获取物流
    function getLogistics(obj) {
        var warehouseId = $(obj).val();
        var warehouse_name = $(obj).find("option:selected").text();
        $("#warehouse_name").val(warehouse_name);
        var url = '<?php echo Url::toRoute(['/orders/order/getlogistics']);?>';
        $.get(url, 'warehouse_id=' + warehouseId, function (data) {
            var html = '';
            if (data.code != '200')
                if (data.code != '200') {
                    layer.alert(data.message, {
                        icon: 5
                    });
                    return;
                }
            if (typeof(data.data) != 'undefined') {
                var logistics = data.data;
                for (var i in logistics) {
                    $("#ship_code_name").val(logistics[i]);
                    break;
                }
                for (var i in logistics) {
                    html += '<option value="' + i + '">' + logistics[i] + '</option>' + "\n";
                }
            }
            $('select[name=ship_code]').empty().html(html);
        }, 'json');
    }

    function getShipName(obj) {
        var ship_name = $(obj).find("option:selected").text();
        $("#ship_code_name").val(ship_name);

    }

    $("#figure_refund_lost").on('click', function () {
        var order_id = '<?php echo $info['info']['order_id'];?>'
        var platform_code = '<?php echo $info['info']['platform_code'];?>'
        var refund_amount = $("input[name=refund_amount]").val();
        var url = '<?php echo Url::toRoute(['/aftersales/order/getrefundlost']);?>';
        $.get(url, {
            "platform_code": platform_code,
            "order_id": order_id,
            "refund_amount": refund_amount
        }, function (data) {
            var html = '';
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }
            else {
                $('#refund_lost').html(data.data);
            }

        }, 'json');
    })

    $("#figure_redirect_lost").on('click', function () {
        var order_id = '<?php echo $info['info']['order_id'];?>'
        var platform_code = '<?php echo $info['info']['platform_code'];?>'

        var ship_code = $("select[name=ship_code]").val()
        if (ship_code == null || ship_code.length == 0) {
            alert('请选择邮寄方式');
            return;
        }
        var sku = $("input[name^=sku")
        sku_arr = []
        for (var i = 0; i < sku.length; i++) {
            sku_arr[i] = sku[i].value
        }
        var quantity = $("input[name^=quantity]")
        quantity_arr = [];
        for (var i = 0; i < quantity.length; i++) {
            quantity_arr[i] = quantity[i].value
        }
        if (sku_arr.length != quantity_arr.length) {
            alert('产品sku和数量填写不完整')
            return;
        }

        var url = '<?php echo Url::toRoute(['/aftersales/order/getredirectlost']);?>';
        $.get(url, {
            "platform_code": platform_code,
            "order_id": order_id,
            "sku_arr": sku_arr,
            "quantity_arr": quantity_arr,
            "ship_code": ship_code
        }, function (data) {
            var html = '';
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }
            else {
                $('#redirect_lost').html(data.data);
            }

        }, 'json');
    })

    $("#search_payapl_record").on('click', function () {
        var paypal_id = $("#paypal_record").val();
        var account_id = '<?php echo $info['info']['account_id']?>' // 订单account_id(erp系统account_id)
        var url = '<?php echo Url::toRoute(['/aftersales/transactions/getinfobyid']);?>';
        $.get(url, {"transaction_id": paypal_id, "account_id": account_id}, function (data) {
            var html = '';
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }
            else {
                var paypal_info = data.data;
                var html = '';
                html += '<div class="panel-body">\n' +
                    '                    <table id="return-product" class="table">\n' +
                    '                    <thead>\n' +
                    '                    <tr>\n' +
                    '                    <th style="height:74px;line-height:37px;">paypal交易号/交易时间</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">金额/状态</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">佣金</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">货币类型</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">付款帐号</th>\n' +
                    '                    <th style="height:74px;line-height:37px;">收款帐号</th>\n' +
                    '                    </tr>\n' +
                    '                    </thead>\n' +
                    '                    <tbody>';
                html += '<td ><p>' + paypal_info.transaction_id + '</p><p>' + paypal_info.order_time + '</p></td>';
                html += '<td ><p>' + paypal_info.amt + '</p><p>' + paypal_info.payment_status + '</p></td>';
                html += '<td >' + paypal_info.fee_amt + '</td>';
                html += '<td >' + paypal_info.currency + '</td>';
                html += '<td >' + paypal_info.payer_email + '</td>';
                html += '<td >' + paypal_info.receiver_email + '</td>';

                html += '</tbody>\n' +
                    '                </table>' +
                    '                </div>\n';
                $("#paypal_table").html(html);

            }

        }, 'json');
    })

    function get_sku(obj) {
        var url = '<?php echo Url::toRoute(['/products/product/getproduct']);?>';
        obj = $(obj);
        $.get(url, {"sku": obj.val()}, function (data) {
            var returns = data.data;
            if (data.code != '200') {
                layer.alert(data.message, {
                    icon: 5
                });
                return;
            }
            else {
                console.log(obj.parent().siblings(".picking_name").children("input").val());
                obj.parent().siblings(".picking_name").children("input").val(returns.title);
                obj.parent().siblings(".linelist_cn_name").children("input").val(returns.linelist_cn_name);
            }

        }, 'json');
    }

    //填充退款金额
    function fillAmount(obj, itemId) {
        if (obj.checked == true)
            $('input.refund_item').each(function () {
                $(this).val($(this).attr('remain'));
            })
        else
            $('input.refund_item').val('0.00');
    }

    //切换责任归属部门获取对应原因
    $(document).on("change", "#department_id", function () {
        var id = $(this).val();
        if (id) {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/aftersales/refundreason/getnetleveldata']); ?>',
                data: {'id': id},
                success: function (data) {
                    var html = "";
                    if (data) {
                        $.each(data, function (n, value) {
                            html += '<option value=' + n + '>' + value + '</option>';
                        });
                    } else {
                        html = '<option value="">---请选择---</option>';
                    }
                    $("#reason_id").empty();
                    $("#reason_id").append(html);
                }
            });
        } else {
            $("#reason_id").empty();
            $("#reason_id").append(html);
        }
    });

    //在当前行后添加行
    function addTr(CurrentLine, itemId, total_price) {
        var trHtml = '';
        trHtml += '<tr>';
        trHtml += '<td></td>';
        trHtml += '<td class="cov-content"><select class="form-control" name="refundsku[' + itemId + '][SHIPPING][refundReason]"><option value="CustomerChangedMind">Customer Changed Mind</option></select></td>';
        trHtml += '<td class="cov-content"><select class="form-control" name="refundsku[' + itemId + '][SHIPPING][chargeType]"><option value="PRODUCT">产品退款</option><option value="SHIPPING">运费退款</option></select></td>';
        trHtml += '<td class="cov-content"><input type="text" style="width:80px;" class="form-control" name="refundsku[' + itemId + '][SHIPPING][amount]" value="0"></td>';
        trHtml += '<td class="cov-content"><input type="text" style="width:50px;" class="form-control" name="refundsku[' + itemId + '][SHIPPING][tax]" value="0"></td>';
        trHtml += '<td class="cov-content">' + total_price + '</td>';
        trHtml += '<td class="cov-content"><a onclick="delTr(this)">删除</a>&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="addTr(this,' + itemId + ',' + total_price + ')">添加</a></td>';
        trHtml += '</tr>';
        $(CurrentLine).parent().parent().after(trHtml);
    }

    //删除当前行
    function delTr(CurrentLine) {
        $(CurrentLine).parent().parent().remove();
    }
</script>