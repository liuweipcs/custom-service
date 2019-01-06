<?php

use yii\helpers\Url;
?>
<div class="popup-wrapper">
    <form action="<?php echo Url::toRoute(Yii::$app->request->getUrl()); ?>" method="post" role="form"
          class="form-horizontal">
        <div class="popup-body">
            <div class="row">
                <div class="col-sm-12">
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
                                                <?php
                                                $departmentLists = GuzzleHttp\json_decode($departmentList, true);
                                                if (!empty($departmentLists)) {
                                                    foreach ($departmentLists as $value) { ?>
                                                        <option <?php echo ($afterSaleOrderModel->department_id == $value['depart_id']) ? 'selected="selected"' : ""; ?>
                                                                value="<?php echo $value['depart_id']; ?>"><?php echo $value['depart_name']; ?></option>
                                                    <?php }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-9">
                                            <label for="ship_name" class="control-label required">原因类型：<span
                                                        class="text-danger">*</span></label>
                                            <select name="reason_id" id="reason_id" class="form-control" size="12"
                                                    multiple="multiple">
                                                <?php if (!empty($reasonList)) {
                                                    foreach ($reasonList as $k => $val) { ?>
                                                        <option <?php echo ($afterSaleOrderModel->reason_id == $k) ? 'selected="selected"' : ""; ?>
                                                                value="<?php echo $k; ?>"><?php echo $val; ?></option>
                                                    <?php }
                                                } ?>
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
</script>