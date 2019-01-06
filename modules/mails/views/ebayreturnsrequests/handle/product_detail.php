<?php

use app\modules\orders\models\Order;
use app\common\VHelper;
use yii\helpers\Url;
use app\modules\products\models\Product;

?>
<div class="panel panel-default" style="margin-top: 5px;">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo"><h4 class="panel-title">产品信息</h4></a>
    </div>
    <div id="collapseTwo" class="panel-collapse collapse in">
        <div class="panel-body">
            <table class="table table-hover">
                <tbody>
                <tr>
                    <th>标题</th>
                    <th>绑定产品sku</th>
                    <th>数量</th>
                    <th>产品sku</th>
                    <th>数量</th>
                    <th>平台卖价</th>
                    <th>总运费</th>
                    <th>欠货数量</th>
                    <th>库存</th>
                    <th>在途数</th>
                    <th>缩略图</th>
                    <th>总计</th>
                </tr>
                <?php if (!empty($info['product'])) { ?>
                    <?php foreach ($info['product'] as $value) { ?>
                        <tr>
                            <td style="width: 50%">
                                <a href="<?php echo 'http://www.ebay.com/itm/' . $value['item_id']; ?>"
                                   target="_blank"><?php echo $value['title']; ?>
                                    &nbsp;(item_number:<?php echo $value['item_id']; ?>)</a> <br/>
                                <span style='color:green;'><?= $value['seller_user']; ?></span><br/>
                                <span style='color:green;'><?= $value['serviceer']; ?></span></td>
                            <td rowspan="2"><?php echo $value['sku_old']; ?></td>
                            <td rowspan="2"><?php echo $value['quantity_old']; ?></td>
                            <td rowspan="2"><?php echo $value['sku'];?>
                            </td>
                            <td rowspan="2"><?php echo $value['quantity']; ?></td>
                            <td rowspan="2"><?php echo $value['sale_price']; ?></td>
                            <td rowspan="2"><?php echo $value['ship_price']; ?></td>
                            <td rowspan="2"><?php echo $value['qs']; ?></td>
                            <td rowspan="2" class="p-stock">
                                <?php if (!empty($value['stock'])) { ?>
                                    <?php echo $value['stock']; ?>
                                <?php } else { ?>
                                    <a class="btn btn-xs btn-warning" id="load-stock"
                                       data-sku="<?php echo $value['sku']; ?>"
                                       data-warehousecode="<?php echo !empty($info['wareh_logistics']['warehouse']['warehouse_code']) ? $info['wareh_logistics']['warehouse']['warehouse_code'] : ''; ?>">加载库存</a>
                                <?php } ?>
                            </td>
                            <td rowspan="2" class="p-on-way-stock">
                                <?php if (!empty($value['on_way_stock'])) { ?>
                                    <?php echo $value['on_way_stock']; ?>
                                <?php } else { ?>
                                    <a class="btn btn-xs btn-warning" id="load-way-stock"
                                       data-sku="<?php echo $value['sku']; ?>"
                                       data-warehousecode="<?php echo !empty($info['wareh_logistics']['warehouse']['warehouse_code']) ? $info['wareh_logistics']['warehouse']['warehouse_code'] : ''; ?>">加载在途</a>
                                <?php } ?>
                            </td>
                            <td rowspan="2">
                                <?php
                                $img              = Order::getProductImageThub($value['sku']);
                                $imgUrl           = VHelper::getOriginalImgUrl($img);
                                $thumbnailsImgSrc = isset($imgUrl['thumbnailsImgSrc']) ? $imgUrl['thumbnailsImgSrc'] : "";
                                $OriginalImgUrl   = isset($imgUrl['originalImgSrc']) ? $imgUrl['originalImgSrc'] : "";
                                ?>
                                <a target="_blank" href="<?php echo $OriginalImgUrl; ?>"><img
                                            style="border:1px solid #ccc;padding:2px;width:60px;height:60px;"
                                            src="<?php echo $thumbnailsImgSrc; ?>"
                                            alt="<?php echo $value['sku'] ?>"/></a></td>
                            <td rowspan="2"><?php echo $value['total_price']; ?></td>
                        </tr>
                        <tr>
                            <td bgcolor="#F8F8F8" valign="<?php echo $value['picking_name']; ?>"
                                class="p-picking-name"><?php echo $value['picking_name'] ?>
                                &nbsp;(<a href="http://120.24.249.36/product/index/sku/<?php echo $value['sku']; ?>"
                                           style="color:blue" target='_blank'>sku:<?php echo $value['sku']; ?></a>)<br><br>
                                <span style="color:green;">Item Location:<?= $value['location'] ?></span>
                                &nbsp;&nbsp;
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
                        <td colspan="12" align="center">没有找到信息！</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        //获取库存和在途数
        $("#load-stock,#load-way-stock").on("click", function () {
            var tr = $(this).parents("tr");
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
    });
</script>