<?php

use yii\helpers\Url;
?>
<style>
    .wish_reply_upload_image_display{
        float: left;
    }


</style>
<div>
    <p>请下载指定的excel模板导入</p>
    <button>
        <a href = "/uploads/customer.xls">
            下载模板
        </a>
    </button>
</div>
<div class="form-group" style="padding-top: 20px;">
    <label class="control-label col-sm-3">附件</label>
    <button class="upload_file" type="button">上传文件</button> &nbsp;&nbsp;
</div>
<div class="upload_file_ares">

</div>
<script src="<?php echo yii\helpers\Url::base(true); ?>/js/jquery.form.js"></script>
<script>
    $(function() {
        //上传图片
        $('.upload_file').click(function () {
            layer.open({
                area: ['500px', '200px'],
                type: 1,
                title: '上传文件',
                content: '<form style="padding:10px 0px 0px 20px" action="<?php echo Url::toRoute('/customer/customer/uploadimage')?>" method="post" id="upload_pop_file" enctype="multipart/form-data"><input type="file" name="upload_file"/><p style="color:red">支持文件格式：xls</p></form>',
                btn: '上传',
                yes: function (index, layero) {
                    layero.find('#upload_pop_file').ajaxSubmit({
                        dataType: 'json',
                        beforeSubmit: function (options) {
                            if (!/(xls)/ig.test(options[0].value.name)) {
                                layer.msg('文件格式错误！', {
                                    icon: 2,
                                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                });
                                return false;
                            }
                        },
                        success: function (response) {
                            switch (response.status) {
                                case 'error':
                                    layer.msg(response.info, {
                                        icon: 2,
                                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                    });
                                    break;
                                case 'success':
                                    $('.upload_file_ares').append('<div class="upload_file_display"><p>' + response.url +'</p> <a class="excel_file" name="' + response.url +'">导入&nbsp&nbsp</a><a class="upload_file_delete">删除</a></div>');
                                    layer.close(index);
                            }
                        },
                    });
                }
            });
        });
        //删除图片
        $('.upload_file_ares').delegate('.upload_file_delete', 'click', function () {
            if (window.confirm('确定要删除？')) {
                var $this = $(this);
                var delteImageUrl = $this.siblings('p').text();
                $.post('<?php echo Url::toRoute('/customer/customer/deleteimage')?>', {'url': delteImageUrl}, function (response) {
                    switch (response.status) {
                        case 'error':
                            layer.msg(response.info, {icon: 2, time: 2000});
                            break;
                        case 'success':
                            layer.msg('删除成功', {icon: 1, time: 2000});
                            $this.parent().remove();
                    }
                }, 'json');
            }
        });


        $('.upload_file_ares').delegate('.excel_file','click',function () {
            var name = $(this).attr('name');
            $.post('<?php echo Url::toRoute('/customer/customer/comeexcel')?>', {'url': name}, function (response) {
                switch (response.status) {
                    case 'error':
                        layer.msg(response.info, {icon: 2, time: 2000});
                        break;
                    case 'success':
                        layer.msg(response.info, {icon: 1, time: 2000},function () {
                            top.layer.closeAll("iframe");
                            top.location.href = "/customer/customer/list";
                    });
                }
            }, 'json');
        });

    });
</script>