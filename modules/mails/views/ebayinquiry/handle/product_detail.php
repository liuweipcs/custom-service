<?php
    use app\modules\orders\models\Order;
    use app\common\VHelper;
?>
<div class="row" id="section-2">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-body">
                <h4 class="m-b-30 m-t-0">产品详情</h4>
                <div class="row">
                    <div class="col-xs-12">
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
                                <?php if(!empty($info['product'])){?>
                                <?php foreach ($info['product'] as $value){?>
                                    <tr>
                                        <td style="width: 50%">
                                            <a href="<?php echo 'http://www.ebay.com/itm/'.$value['item_id'];?>" target="_blank"><?php echo $value['title'];?>&nbsp;(item_number:<?php echo $value['item_id'];?>)</a></td>
                                        <td rowspan="2"><?php echo $value['sku_old'];?></td>
                                        <td rowspan="2"><?php echo $value['quantity_old'];?></td>
                                        <td rowspan="2"><?php echo $value['sku'];?></td>
                                        <td rowspan="2"><?php echo $value['quantity'];?></td>
                                        <td rowspan="2"><?php echo $value['sale_price'];?></td>
                                        <td rowspan="2"><?php echo $value['ship_price'];?></td>
                                        <td rowspan="2"><?php echo $value['qs'];?></td>
                                        <td rowspan="2"><?php echo $value['stock'];?></td>
                                        <td rowspan="2"><?php echo $value['on_way_stock'];?></td>
                                        <td rowspan="2" >
                                            <?php
                                                $img = Order::getProductImageThub($value['sku']);
                                                $imgUrl = VHelper::getOriginalImgUrl($img);
                                                $thumbnailsImgSrc = isset($imgUrl['thumbnailsImgSrc']) ? $imgUrl['thumbnailsImgSrc'] : "";
                                                $OriginalImgUrl = isset($imgUrl['originalImgSrc']) ? $imgUrl['originalImgSrc'] : "";
                                            ?>
                                            <a target="_blank" href="<?php echo $OriginalImgUrl;?>"><img style="border:1px solid #ccc;padding:2px;width:60px;height:60px;" src="<?php echo $thumbnailsImgSrc;?>" alt="<?php echo $value['sku']?>" /></a></td>
                                        <td rowspan="2"><?php echo $value['total_price'];?></td>
                                    </tr>
                                    <tr>
                                        <td bgcolor="#F8F8F8" valign="<?php echo $value['picking_name'];?>" class="p-picking-name"><?php echo $value['picking_name']?>&nbsp;(sku:<?php echo $value['sku'];?>)</td>
                                    </tr>
                                <?php }?>
                            <?php }else{?>
                                <tr><td colspan="12" align="center">没有找到信息！</td></tr>
                            <?php }?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>