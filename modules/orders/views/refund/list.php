<?php

use app\components\GridView;
use yii\helpers\Url;

$this->title = '平台退款订单';
?>
<style>
    .select2-container--krajee {
        width: 170px !important;
    }

    #search-form .input-group.date {
        width: 320px;
    }

    #autoCreateAftersaleOverlay {
        display: none;
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0px;
        left: 0px;
        z-index: 9999;
        filter: alpha(opacity=60);
        background-color: #333;
        opacity: 0.6;
        -moz-opacity: 0.6;
    }

    #autoCreateAftersaleSpeed {
        position: absolute;
        width: 640px;
        height: 360px;
        top: 50%;
        left: 50%;
        margin-left: -320px;
        margin-top: -180px;
        z-index: 10000;
        overflow-y: auto;
    }

    #autoCreateAftersaleSpeed p.success {
        line-height: 30px;
        color: #5cb85c;
        font-size: 20px;
        font-weight: bold;
    }

    #autoCreateAftersaleSpeed p.error {
        line-height: 30px;
        color: #d9534f;
        font-size: 20px;
        font-weight: bold;
    }
</style>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php
            echo GridView::widget([
                'id' => 'grid-view',
                'dataProvider' => $dataProvider,
                'model' => $model,
                'pager' => [],
                'columns' => [
                    [
                        'field' => 'state',
                        'type' => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field' => 'system_order_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'platform_order_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'account_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'platform_code',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'buyer_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'amount',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'order_status',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'reason',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'refund_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'create_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'is_aftersale',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                ],
                'toolBars' => [
                    [
                        'href' => '#',
                        'text' => '匹配售后规则',
                        'htmlOptions' => [
                            'id' => 'autoCreateAftersale',
                            'class' => 'btn btn-primary',
                            'data-src' => 'id',
                        ]
                    ],
                    [
                        'href' => Url::toRoute('/orders/refund/getrefund'),
                        'text' => Yii::t('system', '根据订单号拉退款单'),
                        'htmlOptions' => [
                            'class' => 'add-button',
                            '_width' => '30%',
                            '_height' => '30%',
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>

<div id='autoCreateAftersaleOverlay'>
    <div id='autoCreateAftersaleSpeed'></div>
</div>

<script type="text/javascript">
    $(function () {
        function addToolbarTips() {
            $("div.well").after("<span style='color:red;'>只包含wish, lazada, joom, my mall, jumia, cd, shopee平台的退款单</span>");
        }

        addToolbarTips();

        $("#autoCreateAftersale").on("click", function () {
            var dataSrc = $(this).attr('data-src');
            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length == 0) {
                layer.alert("请选择更新项");
                return false;
            }

            var defer = $.Deferred();
            defer.resolve($("#autoCreateAftersaleSpeed").html("<p class='success'>匹配售后规则开始</p>"));
            $("#autoCreateAftersaleOverlay").css("display", "block");
            $("body").css("overflow", "hidden");

            checkBox.each(function () {
                var id = $(this).val();
                defer = defer.then(function () {
                    return $.ajax({
                        type: "POST",
                        url: "<?php echo Url::toRoute(['/orders/refund/autocreateaftersale']); ?>",
                        data: {"id": id},
                        dataType: "json",
                        global: false,
                        success: function (data) {
                            if (data["code"] == 1) {
                                $("#autoCreateAftersaleSpeed").append("<p class='success'>平台订单ID：" + data["data"]["platform_order_id"] + ",创建成功</p>");
                            } else {
                                $("#autoCreateAftersaleSpeed").append("<p class='error'>平台订单ID：" + data["data"]["platform_order_id"] + "," + data["message"] + "</p>");
                            }
                        }
                    });
                });
            });

            defer.done(function () {
                $("#autoCreateAftersaleSpeed").append("<p class='success'>匹配售后规则完毕</p>");
                setTimeout(function () {
                    $("#autoCreateAftersaleOverlay").css("display", "none");
                    window.location.href = "<?php echo Url::toRoute(['/orders/refund/list']); ?>";
                }, 1000);
            });
            return false;
        });

        $("select[name='platform_code']").on("change", function () {
            var platform_code = $(this).val();

            $.post("<?php echo Url::toRoute(['/orders/refund/reason']) ?>", {
                "platform_code": platform_code
            }, function (data) {
                 // console.log(data);
                var html = "<option value=' '>全部</option>";
                var htmla = "<option value=' '>全部</option>";
                if (data["code"] == 1) {
                     var account = data["account"];  
                    var data = data["data"];
                  
                  // console.log(account);
                    for (var ix in data) {
                        html += "<option value='" + ix + "'>" + data[ix] + "</option>"
                    }
                   for (var id in account) {
                       htmla += "<option value='" + id + "'>" + account[id] + "</option>"
                    }
                }
               
                $("select[name='account_id']").html(htmla);
                $("select[name='reason']").html(html);
            }, "json");
        });
    });
</script>