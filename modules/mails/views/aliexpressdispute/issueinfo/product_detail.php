<?php

use yii\helpers\Url;
use app\modules\orders\models\Order;
use app\modules\products\models\Product;
?>
<div class="panel panel-default" style="margin-top: 5px;">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo"><h4 class="panel-title">产品信息</h4></a>
    </div>
    <div id="collapseTwo" class="panel-collapse collapse in">
        <div class="panel-body">
            <div id="menu1" class="tab-pane">
                <form action="<?php
                echo Url::toRoute(['/orders/order/editorderproduct',
                    'platform' => !empty($info['info']['platform_code']) ? $info['info']['platform_code'] : '',
                    'order_id' => !empty($info['info']['order_id']) ? $info['info']['order_id'] : '',
                ]);
                ?>" method="post" role="form">
                    <div id="product-box">
                        <table id="product-table" class="table table-striped">
                            <thead>
                            <tr>
                                <th>标题</th>
                                <!--<th>产品中文</th>-->
                                <th>ASIN</th>
                                <th>绑定产品sku</th>
                                <th>数量</th>
                                <th>产品sku</th>
                                <th>数量</th>
                                <th>平台卖价</th>
                                <th>总运费</th>
                                <th>欠货数量</th>
                                <th>库存</th>
                                <th>在途数</th>
                                <td>缩略图</td>
                                <th>总计</th>
                                <th>产品状态</th>
                            </tr>
                            </thead>
                            <tbody id="product">
                            <?php if (!empty($info['product'])) { ?>
                                <?php foreach ($info['product'] as $value) { ?>
                                    <tr class="mail_info">
                                        <td value="<?php echo $value['title']; ?>" class="p-title" style="width: 30%">
                                            <?php
                                            switch ($platform) {
                                                case 'EB':
                                                    $mallLink = 'http://www.ebay.com/itm/' . $value['item_id'];
                                                    $endTag = '';
                                                    break;
                                                case 'AMAZON':
                                                    $mallLink = $value['detail_link_href'];
                                                    $endTag = '';
                                                    break;
                                                default :
                                                    $mallLink = 'https://www.aliexpress.com/item//' . $value['item_id'];
                                                    $endTag = '.html';
                                            }
                                            ?>
                                            <a href="<?php echo $mallLink, $endTag; ?>" target="_blank"><?php echo $value['title']; ?>&nbsp;(item_number:<?php echo $value['item_id']; ?>)</a>
                                            <?php if (isset($value['asinval'])) { ?>
                                                <br>
                                                <!--                                      <a target="_blank" href="<?php /* echo $value['detail_link_href']; */ ?>" title="<?php /* echo $value['detail_link_title']; */ ?>"><?php /* echo $value['asinval']; */ ?></a>-->
                                            <?php } ?>
                                            <br/>
                                            <span style='color:green;'><?= $value['seller_user']; ?></span><br/>
                                            <span style='color:green;'><?= $value['serviceer']; ?></span>
                                        </td>
                                        <td rowspan="2">
                                            <?php if (isset($value['asinval'])) echo $value['asinval'];
                                            else echo '-'; ?>
                                        </td>
                                        <td rowspan="2" value="<?php echo $value['sku_old']; ?>" class="p-sku_old"><?php echo $value['sku_old']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['quantity_old']; ?>" class="p-quantity_old"><?php echo $value['quantity_old']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['sku']; ?>" class="p-sku"><?php echo $value['sku']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['quantity']; ?>" class="p-quantity"><?php echo $value['quantity']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['sale_price']; ?>" class="p-sale-price"><?php echo $value['sale_price']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['ship_price']; ?>" class="p-ship-price"><?php echo $value['ship_price']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['qs']; ?>" class="p-qs"><?php echo $value['qs']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['stock']; ?>" class="p-stock">
                                            <?php if (!empty($value['stock'])) { ?>
                                                <?php echo $value['stock']; ?>
                                            <?php } else { ?>
                                                <a class="btn btn-xs btn-warning" id="load-stock" data-sku="<?php echo $value['sku']; ?>" data-warehousecode="<?php echo !empty($info['wareh_logistics']['warehouse']['warehouse_code']) ? $info['wareh_logistics']['warehouse']['warehouse_code'] : ''; ?>">加载库存</a>
                                            <?php } ?>
                                        </td>
                                        <td rowspan="2" value="<?php echo $value['on_way_stock']; ?>" class="p-on-way-stock">
                                            <?php if (!empty($value['on_way_stock'])) { ?>
                                                <?php echo $value['on_way_stock']; ?>
                                            <?php } else { ?>
                                                <a class="btn btn-xs btn-warning" id="load-way-stock" data-sku="<?php echo $value['sku']; ?>" data-warehousecode="<?php echo !empty($info['wareh_logistics']['warehouse']['warehouse_code']) ? $info['wareh_logistics']['warehouse']['warehouse_code'] : ''; ?>">加载在途</a>
                                            <?php } ?>
                                        </td>
                                        <td rowspan="2"><img style="border:1px solid #ccc;padding:2px;width:60px;height:60px;" src="<?php echo Order::getProductImageThub($value['sku']); ?>" alt="<?php echo $value['sku'] ?>"/></td>
                                        <td rowspan="2" value="<?php echo $value['total_price']; ?>" class="p-total-price"><?php echo $value['total_price']; ?></td>
                                        <td rowspan="2" class="p-total-price" style="color: red;"><?php if ($value['ignore_shipment'] == 1) echo '该SKU不发货'; ?></td>
                                        <td rowspan="2" disabled="none" value="<?php echo $value['is_erp'] ?>" class="p-is-erp"></td>
                                    </tr>
                                    <tr>
                                        <td bgcolor="#F8F8F8" valign="<?php echo $value['picking_name']; ?>" class="p-picking-name"><?php echo $value['picking_name'] ?>&nbsp;(sku:<?php echo $value['sku']; ?>)<br><br>
                                            <?php
                                            if ($value['sku']){
                                                $product_status_value = Product::getStatusValueBySku($value['sku']);
                                                $product_status = Product::getProductStatus($product_status_value);
                                                ?>
                                                <span style="color:green;"> 产品状态: <?php echo $product_status;?> </span>
                                            <?php };?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="6" align="center">没有找到信息！</td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                        <?php if (isset($info['info']['complete_status']) && ($info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP)) { ?>
                            <!--<div><a id="edit-product-button" href="javascript:void(0);">编辑产品</a></div>-->
                        <?php } ?>
                    </div>
                    <div id="product-edit-box" style="display: none;">
                        <table class="table table-striped">
                            <tbody>
                            <tr>
                                <th>标题</th>
                                <th>产品sku</th>
                                <th>数量</th>
                                <th>平台卖价</th>
                                <th>总运费</th>
                                <th>欠货数量</th>
                                <th>库存</th>
                                <th>在途数</th>
                                <th>总计</th>
                                <th>操作</th>
                            </tr>
                            </tbody>
                        </table>
                        <div><a href="javascript:void(0);" id="add-row-button">添加产品</a></div>
                        <div class="popup-footer">
                            <button class="btn btn-primary ajax-submit" type="button">保存</button>
                            <button class="btn btn-default" id="product-cancel-button" type="button">取消</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $('a#edit-product-button').click(function () {
        var html = '<tr><th>标题</th>' + "\n" +
            '<th>产品sku</th>' + "\n" +
            '<th>数量</th>' + "\n" +
            '<th>平台卖价</th>' + "\n" +
            '<th>总运费</th>' + "\n" +
            '<th>欠货数量</th>' + "\n" +
            '<th>库存</th>' + "\n" +
            '<th>在途数</th>' + "\n" +
            '<th>总计</th>' + "\n" +
            '<th>操作</th></tr>' + "\n";
        $('tbody#product').find('tr.mail_info').each(function () {
            html += '<tr>' + "\n" +
                '<td class="picking_name"><input class="form-control" type="text" name="product_title[]" value="' + $(this).find('td.p-title').attr('value') + '" /></td>' + "\n" +
                '<td><input class="form-control" type="text" name="sku[]" value="' + $(this).find('td.p-sku').attr('value') + '" onblur="get_sku(this)" /></td>' + "\n" +
                '<td><input class="form-control" type="text" name="quantity[]" value="' + $(this).find('td.p-quantity').attr('value') + '" /></td>' + "\n" +
                '<td><input class="form-control" type="text" name="sale_price[]" value="' + $(this).find('td.p-sale-price').attr('value') + '" /></td>' + "\n" +
                '<td><input class="form-control" type="text" name="ship_price[]" value="' + $(this).find('td.p-ship-price').attr('value') + '" /></td>' + "\n" +
                '<input class="form-control" type="hidden" name="is_erp[]" value="' + $(this).find('td.p-is-erp').attr('value') + '" />' + "\n" +
                '<td>' + $(this).find('td.p-qs').attr('value') + '</td>' + "\n" +
                '<td>' + $(this).find('td.p-stock').attr('value') + '</td>' + "\n" +
                '<td>' + $(this).find('td.p-on-way-stock').attr('value') + '</td>' + "\n" +
                '<td>' + $(this).find('td.p-total-price').attr('value') + '</td>' + "\n" +
                '<td><a href="javascript:void(0)" id="delete-row-button" is_erp="' + $(this).find('td.p-is-erp').attr('value') + '">删除</a></td>' + "\n" +
                '</tr>';
        });
        $('div#product-edit-box').show();
        $('div#product-edit-box tbody').empty().append(html);
        $('div#product-box').hide();
        $('button#product-cancel-button').click(function () {
            $('div#product-box').show();
            $('div#product-edit-box').hide();
        });
        $('a#delete-row-button').click(function () {
            var is_erp = $(this).attr('is_erp')
            if (is_erp == '0') {
                alert('订单自带产品信息，无法删除');
                return;
            }
            $(this).parents('tr').remove();
        });
        $('a#add-row-button').unbind('click').click(function () {
            var html = '<tr>' +
                '<td class="picking_name"><input class="form-control" type="text" name="product_title[]" value="" /></td>' + "\n" +
                '<td><input class="form-control" type="text" name="sku[]" value="" onblur="get_sku(this)" /></td>' + "\n" +
                '<td><input class="form-control" type="text" name="quantity[]" value="1" /></td>' + "\n" +
                '<td><input class="form-control" type="text" name="sale_price[]" value="0" /></td>' + "\n" +
                '<td><input class="form-control" type="text" name="ship_price[]" value="0" /></td>' + "\n" +
                '<input class="form-control" type="hidden" name="is_erp[]" value="1" />' + "\n" +
                '<td></td>' + "\n" +
                '<td></td>' + "\n" +
                '<td></td>' + "\n" +
                '<td></td>' + "\n" +
                '<td><a href="javascript:void(0)" class="delete-row-button" is_erp="1">删除</a></td>' + "\n" +
                '</tr>';
            $('div#product-edit-box table tbody').append(html);
            $('a.delete-row-button').click(function () {
                $(this).parents('tr').remove();
            });
        });
    });

    // 根据sku匹配产品信息
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
            }

        }, 'json');
    }

    //获取库存和在途数
    $("#load-stock,#load-way-stock").on("click", function () {
        var tr = $(this).parents("tr.mail_info");
        var sku = $.trim($(this).attr("data-sku"));
        var warehouseCode = $.trim($(this).attr("data-warehousecode"));

        $.get("<?php echo Url::toRoute(['/products/product/getproductstockandoncount']) ?>", {
            "sku": sku,
            "warehouseCode": warehouseCode
        }, function (data) {
            if (data["code"] == 1) {
                var info = data["data"];
                var stock = info["available_stock"] ? info["available_stock"] : 0;
                var wayStock = info["on_way_stock"] ? info["on_way_stock"] : 0;
                tr.find("td.p-stock").attr("value", stock).html(stock);
                tr.find("td.p-on-way-stock").attr("value", wayStock).html(wayStock);
            } else {
                layer.alert("没有找到该sku的库存和在途数");
            }
        }, "json");
        return false;
    });
</script>