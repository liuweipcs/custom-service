<?php

use yii\helpers\Url;
use app\modules\orders\models\Order;
use app\modules\products\models\WalmartListing;
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
                    'platform'     => $info['info']['platform_code'],
                    'order_id'     => $info['info']['order_id'],
                    'is_return'    => $is_return,
                    'returnid'     => $returnid,
                    'track_number' => $track_number
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
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody id="product">
                            <?php if (!empty($info['product'])) { ?>
                                <input type="hidden" class="first_item_id"
                                       value="<?= $info['product'][0]['item_id'] ?>">
                                <?php foreach ($info['product'] as $value) {
                                    ?>
                                    <tr class="mail_info">
                                        <td
                                                data-item_id="<?= $value['item_id'] ?>"
                                                data-sku="<?= $value['sku'] ?>"
                                                data-product_id="<?= $value['id'] ?>"
                                                value="<?php echo $value['title']; ?>" class="p-title"
                                                style="width: 30%">
                                            <?php
                                            switch ($platform) {
                                                case 'EB':
                                                    $mallLink = 'http://www.ebay.com/itm/' . $value['item_id'];
                                                    $endTag   = '';
                                                    break;
                                                case 'AMAZON':
                                                    $mallLink = $value['detail_link_href'];
                                                    $endTag   = '';
                                                    break;
                                                case 'WISH':
                                                    $mallLink = 'https://www.wish.com/c/' . $value['item_id'];
                                                    $endTag   = '';
                                                    break;
                                                case 'WALMART':
                                                    $item_id  = WalmartListing::find()->select('item_id')->where(['account_id'=>$info['info']['account_id'],'seller_sku' => $value['sku_old']])->asArray()->scalar();
                                                    $mallLink = $item_id ? 'https://www.walmart.com/ip/' . $item_id : '';

                                                    $endTag = '';
                                                    break;
                                                default :
                                                    $mallLink = 'https://www.aliexpress.com/item//' . $value['item_id'];
                                                    $endTag   = '.html';
                                            }
                                            ?>
                                            <a href="<?php echo $mallLink, $endTag; ?>"
                                               target="_blank"><?php echo $value['title']; ?>
                                                &nbsp;(item_number:<?php echo $item_id ? $item_id : '暂无item_id及链接'; ?>
                                                )</a>
                                            <?php if (isset($value['asinval'])) { ?>
                                                <br>
                                            <?php } ?>
                                            <br/>
                                            <span style='color:green;'><?= $value['seller_user']; ?></span><br/>
                                            <span style='color:green;'><?= $value['serviceer']; ?></span>
                                        </td>
                                        <td rowspan="2">
                                            <?php if (isset($value['asinval'])) echo $value['asinval'];
                                            else echo '-'; ?>
                                        </td>
                                        <td rowspan="2" value="<?php echo $value['sku_old']; ?>"
                                            class="p-sku_old"><?php echo $value['sku_old']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['quantity_old']; ?>"
                                            class="p-quantity_old"><?php echo $value['quantity_old']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['sku']; ?>"
                                            class="p-sku"><?php echo $value['sku'];
                                            echo !empty($value['buyer']) ? '<br>采购' . ':' . $value['buyer'] : '';
                                            echo !empty($value['editor']) ? '<br>文案' . ':' . $value['editor'] : '';
                                            echo !empty($value['create_user']) ? '<br>开发' . ':' . $value['create_user'] : ''; ?></td>
                                        <td rowspan="2" data-id="<?= $value['id'] ?>"
                                            value="<?php echo $value['quantity']; ?>"
                                            class="p-quantity"><?php echo $value['quantity']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['sale_price']; ?>"
                                            class="p-sale-price"><?php echo $value['sale_price']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['ship_price']; ?>"
                                            class="p-ship-price"><?php echo $value['ship_price']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['qs']; ?>"
                                            class="p-qs"><?php echo $value['qs']; ?></td>
                                        <td rowspan="2" value="<?php echo $value['stock']; ?>" class="p-stock">
                                            <?php if (!empty($value['stock'])) { ?>
                                                <?php echo $value['stock']; ?>
                                            <?php } else { ?>
                                                <a class="btn btn-xs btn-warning" id="load-stock"
                                                   data-sku="<?php echo $value['sku']; ?>"
                                                   data-warehousecode="<?php echo !empty($info['wareh_logistics']['warehouse']['warehouse_code']) ? $info['wareh_logistics']['warehouse']['warehouse_code'] : ''; ?>">加载库存</a>
                                            <?php } ?>
                                        </td>
                                        <td rowspan="2" value="<?php echo $value['on_way_stock']; ?>"
                                            class="p-on-way-stock">
                                            <?php if (!empty($value['on_way_stock'])) { ?>
                                                <?php echo $value['on_way_stock']; ?>
                                            <?php } else { ?>
                                                <a class="btn btn-xs btn-warning" id="load-way-stock"
                                                   data-sku="<?php echo $value['sku']; ?>"
                                                   data-warehousecode="<?php echo !empty($info['wareh_logistics']['warehouse']['warehouse_code']) ? $info['wareh_logistics']['warehouse']['warehouse_code'] : ''; ?>">加载在途</a>
                                            <?php } ?>
                                        </td>
                                        <td rowspan="2"><img
                                                    style="border:1px solid #ccc;padding:2px;width:60px;height:60px;"
                                                    src="<?php echo Order::getProductImageThub($value['sku']); ?>"
                                                    alt="<?php echo $value['sku'] ?>"/></td>
                                        <td rowspan="2" value="<?php echo $value['total_price']; ?>"
                                            class="p-total-price"><?php echo $value['total_price']; ?></td>
                                        <td rowspan="2" class="p-total-price"
                                            style="color: red;"><?php if ($value['ignore_shipment'] == 1) echo '该SKU不发货'; ?></td>
                                        <td rowspan="2" disabled="none" value="<?php echo $value['is_erp'] ?>"
                                            class="p-is-erp"></td>
                                        <td rowspan="2">
                                            <?php if ($value['is_erp'] == 0) { ?>
                                                <?php if ($value['ignore_shipment'] == 0) { ?>
                                                    <a class="ignore_button" target="ajaxTodo"
                                                       style="text-decoration: underline" title="确定忽略发货？"
                                                       onclick=ignoreitem(<?= $value['id'] ?>,'<?= $value['platform_code'] ?>','<?= $value['order_id'] ?>')><span>忽略发货</span></a>
                                                <?php } else { ?>
                                                    <a class="recover_button" target="ajaxTodo"
                                                       style="text-decoration: underline" title="确定恢复发货？"
                                                       onclick=recoveritem(<?= $value['id'] ?>,'<?= $value['platform_code'] ?>','<?= $value['order_id'] ?>')><span>恢复发货</span></a>
                                                <?php } ?>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td bgcolor="#F8F8F8" valign="<?php echo $value['picking_name']; ?>"
                                            class="p-picking-name"><?php echo $value['picking_name'] ?>
                                            &nbsp;( <a href="http://120.24.249.36/product/index/sku/<?php echo $value['sku']; ?>"
                                           style="color:blue" target='_blank'>sku:<?php echo $value['sku']; ?></a>)<br><br>
                                            <?php if ($platform == 'EB') { ?>
                                                <span style="top: 5px;color:green;">Item Location:<?= $value['location'] ?></span>
                                                &nbsp;
                                            <?php } ?>
                                            <?php
                                            if ($value['sku']) {
                                                $product_status_value = Product::getStatusValueBySku($value['sku']);
                                                $product_status       = Product::getProductStatus($product_status_value);
                                                ?>
                                                <span style="color:green;"> 产品状态: <?php echo $product_status; ?> </span>
                                            <?php }; ?>

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
                        <?php if ($is_return == 1) { ?>
                            <br/>
                            <div><a id="edit-product-button" href="javascript:void(0);">编辑产品</a></div>
                        <?php } else { ?>

                            <?php if ($info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) { ?>
                                <div><a id="edit-product-button" href="javascript:void(0);">编辑产品</a></div>
                            <?php } ?>
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
                '<td class="picking_name">' +
                '<input class="form-control" type="text" name="product_title[]" value="' + $(this).find('td.p-title').attr('value') + '" />' +
                '<input type="hidden" name="editsku[]" value="' + $(this).find('td.p-title').attr('data-sku') + '" />' + "\n" +
                '<input type="hidden" name="product_id[]" value="' + $(this).find('td.p-title').attr('data-product_id') + '" />' + "\n" +
                '<input type="hidden" name="item_id[]" value="' + $(this).find('td.p-title').attr('data-item_id') + '" />' + "\n" +
                '<input type="hidden" name="is_delete[]" value="0" />' + "\n" +
                '</td>' + "\n" +
                '<td><input class="form-control" type="text" name="sku[]" value="' + $(this).find('td.p-sku').attr('value') + '" onblur="get_sku(this)" /></td>' + "\n" +
                '<td><input class="form-control" type="text" name="quantity[]" oninput="totalPrice($(this))" value="' + $(this).find('td.p-quantity').attr('value') + '" /></td>' + "\n" +
                '<td><input class="form-control sale_price" type="text" name="sale_price[]" oninput="totalPrice($(this))" value="' + $(this).find('td.p-sale-price').attr('value') + '" /></td>' + "\n" +
                '<td><input class="form-control ship_price" type="text" name="ship_price[]" oninput="totalPrice($(this))" value="' + $(this).find('td.p-ship-price').attr('value') + '" /></td>' + "\n" +
                '<input class="form-control" type="hidden" name="is_erp[]" value="' + $(this).find('td.p-is-erp').attr('value') + '" />' + "\n" +
                '<td>' + $(this).find('td.p-qs').attr('value') + '</td>' + "\n" +
                '<td>' + $(this).find('td.p-stock').attr('value') + '</td>' + "\n" +
                '<td>' + $(this).find('td.p-on-way-stock').attr('value') + '</td>' + "\n" +
                '<td><input readonly class="form-control total_price" type="text" name="total_price[]" value="' + $(this).find('td.p-total-price').attr('value') + '"  /></td>' + "\n" +
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
            //var is_erp = $(this).attr('is_erp');
            //if (is_erp == '0') {
                //layer.msg('订单自带产品信息，无法删除', {icon: 2});
                //return;
            //}
            $(this).parent().siblings(".picking_name").children("input").eq(4).val(1)
            $(this).parents('tr').css('display', 'none');
        });
        $('a#add-row-button').unbind('click').click(function () {
            var html = '<tr>' +
                '<td class="picking_name">' +
                '<input class="form-control" type="text" name="product_title[]" value="" />' +
                '<input  type="hidden" name="editsku[]"  />' +
                '<input class="add_product_id" type="hidden" name="product_id[]" />' +
                '<input type="hidden" name="item_id[]" />' +
                '<input type="hidden" name="is_delete[]" value="0" />' +
                '</td>' + "\n" +
                '<td><input class="form-control" type="text" name="sku[]" value="" onblur="get_sku(this)" /></td>' + "\n" +
                '<td><input class="form-control" type="text" oninput="totalPrice($(this))" name="quantity[]" value="1" /></td>' + "\n" +
                '<td><input class="form-control" type="text" oninput="totalPrice($(this))" name="sale_price[]" value="0.00" /></td>' + "\n" +
                '<td><input class="form-control" type="text" oninput="totalPrice($(this))" name="ship_price[]" value="0.00" /></td>' + "\n" +
                '<input class="form-control" type="hidden" name="is_erp[]" value="1" />' + "\n" +
                '<td></td>' + "\n" +
                '<td></td>' + "\n" +
                '<td></td>' + "\n" +
                '<td><input readonly class="form-control total_price" type="text" name="total_price[]" value="0.00"  /></td>' + "\n" +
                '<td class="delete_product"><a href="javascript:void(0)" class="delete-row-button" is_erp="1">删除</a></td>' + "\n" +
                '</tr>';
            $('div#product-edit-box table tbody').append(html);

            $('a.delete-row-button').click(function () {
                //var is_erp = $(this).attr('is_erp');
                //if (is_erp == '0') {
                //    layer.msg('订单自带产品信息，无法删除', {icon: 2});
                //    return;
                //}
                $(this).parent().siblings(".picking_name").children("input").eq(4).val(1);
                $(this).parents('tr').css('display', 'none');
            });
        });
    });

    // 根据sku匹配产品信息
    function get_sku(obj) {
        var item_id = $(".first_item_id").val();
        var url = '<?php echo Url::toRoute(['/products/product/getproduct']);?>';
        obj = $(obj);
        var numArr = []; // 定义一个空数组
        var product = $("#product-edit-box").find("input[name='sku[]']");
        for (var i = 0; i < product.length - 1; i++) {
            numArr.push(product.eq(i).val()); // 将文本框的值添加到数组中
        }
        if ($.inArray(obj.val(), numArr) > -1 || $.inArray(obj.val(), numArr) != -1) {
            layer.msg('不能添加相同sku', {icon: 2});
            return false;
        }
        $.get(url, {"sku": obj.val()}, function (data) {
            var returns = data.data;
            if (data.code != '200') {
                layer.alert(data.message, {icon: 5});
                return false;
            } else {
                var input_val = obj.parent().siblings(".picking_name").children("input");
                input_val.eq(0).val(returns.title);
                input_val.eq(1).val(returns.sku);
                // input_val.eq(2).val(returns.id);//添加新sku
                input_val.eq(3).val(item_id);
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

    //id {{%product}}表主键id order_id 平台订单号
    function ignoreitem(id, platform_code, order_id) {
        layer.confirm('确定忽略发货吗？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: "/orders/order/ignoreitem",
                type: "POST",
                data: {"id": id, 'platform_code': platform_code, 'order_id': order_id},
                dataType: "json",
                success: function (data) {
                    if (data.code == 200) {
                        location.href = location.href;
                        layer.msg(data.message, {time: 3000}, {icon: 6});
                    } else {
                        layer.msg(data.msg, {icon: 5});
                        return false;
                    }
                }
            });
        })
    }

    function recoveritem(id, platform_code, order_id) {
        layer.confirm('确定恢复发货吗？', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            $.ajax({
                url: "/orders/order/recoveritem",
                type: "POST",
                data: {"id": id, 'platform_code': platform_code, 'order_id': order_id},
                dataType: "json",
                success: function (data) {
                    if (data.code == 200) {
                        location.href = location.href;
                        layer.msg(data.message, {time: 3000}, {icon: 6});
                    } else {
                        layer.msg(data.msg, {icon: 5});
                        return false;
                    }
                }
            });
        })
    }

    /**
     * 计算总价
     * @param obj
     */
    function totalPrice(obj) {
        var td = obj.parents('tr').children('td');
        var number = td.eq(2).find('input').val();
        var sale_price = td.eq(3).find('input').val();
        var ship_price = td.eq(4).find('input').val();
        var totalPrice = toDecimal2((parseFloat(number) * parseFloat(sale_price)) + parseFloat(ship_price));
        td.eq(8).find('input').val(totalPrice);
    }

    function toDecimal2(x) {
        var f = parseFloat(x);
        if (isNaN(f)) {
            return false;
        }
        var f = Math.round(x * 100) / 100;
        var s = f.toString();
        var rs = s.indexOf('.');
        if (rs < 0) {
            rs = s.length;
            s += '.';
        }
        while (s.length <= rs + 2) {
            s += '0';
        }
        return s;
    }
</script>