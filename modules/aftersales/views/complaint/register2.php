<?php

use yii\helpers\Url;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\accounts\models\Account;
use kartik\select2\Select2;
use app\modules\aftersales\models\ComplaintModel;

$this->registerJsFile(Url::base() . '/js/multiselect.js');
//$this->registerCssFile(Url::base() . '/css/webuploader.css');
//$this->registerJsFile(Url::base() . '/js/webuploader.js');
?>

<link rel="stylesheet" type="text/css" href="/webuploader/webuploader.css">
<script type="text/javascript" src="/webuploader/webuploader.js"></script>
<div class="popup-wrapper">
    <form action="<?php
    echo Url::toRoute(['/aftersales/complaint/register',
        'platform' => $info['info']['platform_code'],
        'order_id' => $info['info']['order_id'],
    ]);
    ?>" method="post" role="form" class="form-horizontal" >
        <div class="popup-body">
            <div class="row">
                <div class="col-sm-5">
                    <div class="panel panel-default">
                        <?php
                        echo $this->render('order_info', ['info' => $info, 'isAuthority' => $isAuthority, 'accountName' => $accountName]);
                        echo $this->render('../order/transaction_record', ['info' => $info, 'paypallist' => $paypallist]); //交易记录
                        echo $this->render('../order/package_info', ['info' => $info]); //包裹信息
                        echo $this->render('../order/logistics', ['info' => $info, 'warehouseList' => $warehouseList]); //仓储物流
                        echo $this->render('../order/aftersales', ['afterSalesOrders' => $afterSalesOrders]); //售后问题
                        echo $this->render('../order/log', ['info' => $info]); //操作日志
                        ?>
                    </div>
                </div>  
                <?php $complain = ComplaintModel::find()->where(['order_id' => $info['info']['order_id']])->andWhere(['platform_order_id' => $info['info']['platform_order_id']])->all();
                ?>
                <div class="col-sm-7">
                    <div class="panel panel-default">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">仓库客诉信息</h3>
                            </div>
                            <input type="hidden" name="platform_order_id" value="<?php echo $info['info']['platform_order_id']; ?>">
                            <input type="hidden" name="buyer_id" value="<?php echo $info['info']['buyer_id']; ?>">
                            <input type="hidden" name="shipped_date" value="<?php echo $info['info']['shipped_date']; ?>">
                            <table class="table">    
                                <tbody>
                                    <?php if (!empty($complain)) { ?>
                                        <tr>
                                            <th style="width: 100px;text-align: -webkit-center">已登记客诉</th>
                                            <td>
                                                <?php foreach ($complain as $key => $vo) { ?>   
                                                    <a _width="100%" _height="100%" class="edit-button" href="<?php echo Url::toRoute(['getcompain', 'complaint_order' => $vo->complaint_order]); ?>" ><?php echo $vo->complaint_order ?></a><?php
                                                    if ($key != (count($complain) - 1)) {
                                                        echo ",";
                                                    }
                                                    ?>
                                                <?php } ?>  
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <tr>
                                        <th style="width: 100px;text-align: -webkit-center">状态</th>
                                        <td>待审核</td>
                                    </tr>
                                    <tr>
                                        <th style="width: 100px;text-align: -webkit-center">加急</th>
                                        <td>
                                            <input name="is_expedited" type="radio" id="redirect-input" value="0" checked>不加急 &nbsp;&nbsp;&nbsp;<input name="is_expedited" type="radio" id="redirect-input" value="1">加急
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="width: 100px;text-align: -webkit-center;line-height: 41px;">客诉类型</th>
                                        <td><select class="form-control select2-hidden-accessible" name="type" data-s2-options="s2options_d6851687" data-krajee-select2="select2_3cdd328e"  tabindex="-1" aria-hidden="true" style="width: 200px;text-align: -webkit-center;line-height: 41px;">
                                                <option value="">请选择...</option>
                                                <?php foreach ($basic as $vo) { ?>
                                                    <option value="<?php echo $vo->name; ?>"><?php echo $vo->name; ?></option>
                                                <?php } ?>
                                            </select></td>
                                    </tr>
                                    <tr>
                                        <th style="width: 100px;text-align: -webkit-center">详情描述</th>
                                        <td>    
                                            <textarea rows="4" cols="12" name="description" class="form-control"></textarea>    
                                        </td>
                                    </tr> 
                                </tbody>
                            </table>

                            <table class="table table-striped table-bordered">
                                <tr>
                                    <th style="width: 80px">是否登记</th>
                                    <th style="width: 240px">产品信息</th>
                                    <th style="width: 50px">数量</th>
                                    <th>图片</th>    
                                </tr>

                                <?php foreach ($info['product'] as $vo) { ?>
                                    <tr>
                                        <td><input name="id[]"  value="<?php echo $vo['id']; ?>" type="checkbox" class="sel"></td>
                                        <td>
                                            <span style="color:#a96a6a">名称:</span><?php echo $vo['picking_name']; ?><input type="hidden" name="title[<?php echo $vo['id']; ?>][]" value="<?php echo $vo['picking_name']; ?>"/><br/>
                                            <span style="color:#a96a6a">SKU:</span><?php echo $vo['sku']; ?><input type="hidden" name="sku[<?php echo $vo['id']; ?>][]" value="<?php echo $vo['sku']; ?>"/><br/>
                                            <span style="color:#a96a6a">产品线:</span><?php echo $vo['linelist_cn_name']; ?><input type="hidden" name="product_line[<?php echo $vo['id']; ?>][]" value="<?php echo $vo['linelist_cn_name']; ?>"/>
                                        </td>
                                        <td><?php echo $vo['quantity']; ?><input type="hidden" name="qty[<?php echo $vo['id']; ?>][]" value="<?php echo $vo['quantity']; ?>"/></td>
                                        <td>
                                            <div id="uploader-demo">
                                                <!--用来存放item-->
                                                <div id="fileList" class="uploader-list"></div>
                                                <div id="filePicker">选择图片</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="popup-footer">
                <!--<input class="form-control" type="hidden" id="_token_" name="_token_" value="1" />-->
                <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
                <button class="btn btn-default close-button" type="button"><?php echo Yii::t('system', 'Close'); ?></button>
            </div>
        </div>

    </form>
</div>
<script type="text/javascript">
    //图片上传demo
    $(function () {
        var $ = jQuery, $list = $('#fileList'),
                // 优化retina, 在retina下这个值是2
                ratio = window.devicePixelRatio || 1,
                // 缩略图大小
                thumbnailWidth = 100 * ratio, thumbnailHeight = 100 * ratio,
                // 初始化Web Uploader
                uploader = WebUploader.create({
                    // 自动上传。
                    auto: true,
                    // swf文件路径
                    swf: '/webuploader/Uploader.swf',
                    // 文件接收服务端。
                    server: 'WebUpLoaderPicture',
                    // 选择文件的按钮。可选。
                    // 内部根据当前运行是创建，可能是input元素，也可能是flash.
                    pick: '#filePicker',
                    // 只允许选择文件，可选。
                    accept: {
                        title: 'Images',
                        extensions: 'gif,jpg,jpeg,bmp,png',
                        mimeTypes: 'image/*'
                    },
                    fileNumLimit: 5, //限制上传个数
                    fileSingleSizeLimit: 2048000
                            //限制单个上传图片的大小
                });
        // 当有文件添加进来的时候
        uploader.on(
                'fileQueued',
                function (file) {
                    var $li = $('<div id="' + file.id + '" class="file-item thumbnail" style="width: 100px;" >'
                            + '<img>' + '</div>'),
                            $img = $li.find('img');
                    $list.append($li);
                    // 创建缩略图
                    uploader.makeThumb(file, function (error, src) {
                        if (error) {
                            $img.replaceWith('<span>不能预览</span>');
                            return;
                        }

                        $img.attr('src', src);
                    }, thumbnailWidth, thumbnailHeight);
                });
        Uploader.register({
            'make-thumb': 'makeThumb'
        }, {
            init: function (options) {},
            makeThumb: function () {}
        });
       
    });
</script>