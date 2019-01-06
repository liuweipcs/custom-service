<?php

use yii\helpers\Url;
use kartik\select2\Select2;
use app\modules\accounts\models\UserAccount;
use app\modules\systems\models\BasicConfig;
?>
<script src="https://img.hcharts.cn/highcharts/highcharts.js"></script>
<script src="https://img.hcharts.cn/highcharts/modules/exporting.js"></script>
<script src="https://img.hcharts.cn/highcharts/modules/series-label.js"></script>
<script src="https://img.hcharts.cn/highcharts/modules/oldie.js"></script>
<script src="https://img.hcharts.cn/highcharts-plugins/highcharts-zh_CN.js"></script>
<script src="https://img.hcharts.cn/highcharts/themes/sand-signika.js"></script>
<div id="page-wrapper" class="page-content-wrapper ">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <form name="statistisform" action="<?php echo Url::toRoute(['index']); ?>" method="post">
                    <div class="panel-body">
                        <div class="col-lg-10">
                            <div class="col-lg-2">
                                <?php
                                echo Select2::widget([
                                    'id' => 'platform_code',
                                    'name' => 'platform_code',
                                    'data' => UserAccount::getLoginUserPlatformAccounts(),
                                    'value' => $platform_code,
                                    'options' => [
                                        'placeholder' => '平台'
                                    ],
                                ]);
                                ?>
                            </div>
                            <div class="col-lg-2">
                                <?php
                                echo Select2::widget([
                                    'id' => 'type',
                                    'name' => 'type',
                                    'data' => $typeArr,
                                    'value' => $typeVal,
                                    'options' => [
                                        'placeholder' => '统计类型/账号',
                                        'multiple' => true,
                                    ],
                                ]);
                                ?>
                            </div>
                            <div id="siteAccount" class="col-lg-3" style="display: <?php echo ($platform_code == 'AMAZON') ? 'block' : 'none'; ?>">
                                <?php
                                echo Select2::widget([
                                    'id' => 'account_site',
                                    'name' => 'account_site',
                                    'data' => $accountSiteArr,
                                    'value' => $accountSiteVal,
                                    'options' => [
                                        'placeholder' => '账号/站点',
                                        'multiple' => true,
                                        'display' => 'none'
                                    ],
                                ]);
                                ?>
                            </div>

                            <div class="col-lg-2">
                                <?php
                                echo Select2::widget([
                                    'id' => 'department_id',
                                    'name' => 'department_id',
                                    'data' => BasicConfig::getParentList(52),
                                    'value' => $department_id,
                                    'options' => [
                                        'placeholder' => '责任部门',
                                    ],
                                ]);
                                ?>
                            </div>

                            <div class="col-lg-2">
                                <?php
                                echo Select2::widget([
                                    'id' => 'reason_id',
                                    'name' => 'reason_id',
                                    'data' => $reasonList,
                                    'value' => $reason_id,
                                    'options' => [
                                        'placeholder' => '原因',
                                        'multiple' => true,
                                    ],
                                ]);
                                ?>
                            </div>

                            <div class="col-lg-2" id="cuscomer">
                                <?php
                                echo Select2::widget([
                                    'id' => 'user_id',
                                    'name' => 'user_id',
                                    'data' => $userList,
                                    'value' => $users,
                                    'options' => [
                                        'placeholder' => '客服人员',
                                        'multiple' => true,
                                    ],
                                ]);
                                ?>
                            </div>
                            
                            
                        </div>
                        <div class="col-lg-2">
                            <button type="submit" class="btn search btn-info m-b-5">搜 索</button>
                            <input type="hidden" name="year_judge">
                            <input type="hidden" name="now_year" value="<?= $data['year'] ?>">
<!--                            <button type="submit" id="up" class="btn search btn-info m-b-5">上一年</button>
                            <button type="submit" id="down"  class="btn search btn-info m-b-5">下一年</button>-->
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?php echo $this->render('platform_statistics', ['data' => $data, 'platform_code' => $platform_code, 'typeArr' => $typeArr, 'typeVal' => $typeVal, 'accountSiteArr' => $accountSiteArr, 'accountSiteVal' => $accountSiteVal]); ?>
            </div>
            <div class="col-lg-6">
                <?php echo $this->render('account_statistics', ['data' => $data, 'platform_code' => $platform_code, 'typeArr' => $typeArr, 'typeVal' => $typeVal, 'accountSiteArr' => $accountSiteArr, 'accountSiteVal' => $accountSiteVal]); ?>
            </div>
        </div>
        <div class="row" style="margin-top:10px;">
            <div class="col-lg-6">
                <?php echo $this->render('department_statistics', ['data' => $data, 'platform_code' => $platform_code, 'typeArr' => $typeArr, 'typeVal' => $typeVal, 'accountSiteArr' => $accountSiteArr, 'accountSiteVal' => $accountSiteVal]); ?>
            </div>
            <div class="col-lg-6">
                <?php echo $this->render('service_statistics', ['data' => $data, 'platform_code' => $platform_code, 'typeArr' => $typeArr, 'typeVal' => $typeVal, 'accountSiteArr' => $accountSiteArr, 'accountSiteVal' => $accountSiteVal]); ?>
            </div>
        </div>
        <div class="row" style="margin-top:10px;">

            <div class="col-lg-12">
                <?php echo $this->render('reason_statistics', ['data' => $data, 'platform_code' => $platform_code, 'typeArr' => $typeArr, 'typeVal' => $typeVal, 'accountSiteArr' => $accountSiteArr, 'accountSiteVal' => $accountSiteVal]); ?>
            </div>
            <!--            <div class="col-lg-6">
            <?php //echo $this->render('sku_statistics',['data' => $data,'platform_code' => $platform_code,'typeArr' => $typeArr,'typeVal' => $typeVal,'accountSiteArr' => $accountSiteArr,'accountSiteVal' => $accountSiteVal]); ?>
                        </div>-->
        </div>
    </div>
</div>

<script>
    //搜索动作  基础数据验证
    $(document).on("click", '.search', function () {
        var platform_code = $("#platform_code").val();
        var type = $('#type').val();
        if(platform_code == 'AMAZON' && type === null){
            layer.alert('请选择站点/账号');
            return false;
        }
        var platform_site = $("#account_site").val();
        var url = '';
        if (platform_code != "") {
            url = '?platform_code=' + platform_code;
        }

        if (type != "" || type != null) {
            url += '&type=' + type;
        }
    });

    //按年统计 上一年
    $("#up").click(function () {
        $("input[name=year_judge]").val('up');
    });

    //
    $("#down").click(function () {
        $("input[name=year_judge]").val('down');
    });



//切换搜索平台获取对应的账号/站点信息
    $(document).on("change", "#platform_code", function () {
        var platform_code = $(this).val();
        var html = "";
        if (platform_code == 'AMAZON') {
            $("#type").attr('multiple', false);
            $("#siteAccount").css('display', 'block');
            $("#cuscomer").css('margin-top','10px');
        } else {
            $("#type").attr('multiple', true);
            $("#siteAccount").css('display', 'none');
            $("#cuscomer").css('margin-top','0px');
        }
        if (platform_code) {
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/accounts/account/getaccoutorsite']) ?>',
                data: {'platform_code': platform_code},
                success: function (data) {
                    if (data) {
                        $.each(data, function (n, value) {
                            html += '<option value=' + n + '>' + value + '</option>';
                        });
                    } else {
                        html = '<option value="">---请选择---</option>';
                    }
                    $("#type").empty();
                    $("#type").append(html);
                }
            });
        } else {
            $("#type").empty();
            $("#type").append(html);
        }
    });

    //切换类型 获取对应的账号 或者站点数据
    $(document).on("change", "#type", function () {
        var platform_code = $("#platform_code").val();//平台
        var account = $(this).val();
        var type;
        var html = "";
        if (account) {
            if (account == 'account') {
                type = 1;
            }
            if (account == 'site') {
                type = 2;
            }
//           alert(type);
            //ajax请求数据
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '<?php echo Url::toRoute(['/accounts/account/getaccoutorsite']) ?>',
                data: {'platform_code': platform_code, 'type': type},
                success: function (data) {

                    if (data) {
                        $.each(data, function (n, value) {
                            html += '<option value=' + n + '>' + value + '</option>';
                        });
                    } else {
                        html = '<option value="">---请选择---</option>';
                    }
                    $("#account_site").empty();
                    $("#account_site").append(html);
                }
            });

        } else {
            $("#account_site").empty();
            $("#account_site").append(html);
        }
    });

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
                    var html = '<option value="">--请选择原因--</option>';
                    if (data) {
                        $.each(data, function (n, value) {
                            if (n != " ") {
                                html += '<option value=' + n + '>' + value + '</option>';
                            }
                        });
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
