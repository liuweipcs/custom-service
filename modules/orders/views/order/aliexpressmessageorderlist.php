<?php

use app\components\GridView;
use yii\helpers\Url;
use yii\data\ArrayDataProvider;
use app\modules\orders\models\Order;
use app\modules\accounts\models\Platform;

?>
<style>
    #updateShopOrderStatusOverlay {
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

    #updateShopOrderStatusSpeed {
        position: absolute;
        width: 480px;
        height: 360px;
        top: 50%;
        left: 50%;
        margin-left: -240px;
        margin-top: -180px;
        z-index: 10000;
        overflow-y: auto;
    }

    #updateShopOrderStatusSpeed p.success {
        line-height: 30px;
        color: #5cb85c;
        font-size: 20px;
        font-weight: bold;
    }

    #updateShopOrderStatusSpeed p.error {
        line-height: 30px;
        color: #d9534f;
        font-size: 20px;
        font-weight: bold;
    }

    span.accept_goods_last_time {
        color:#f17838;
        font-weight:bold;
        font-size: 13px;
    }

    #menu1 .well {
        padding: 5px 0 0 0;
        margin-top: 10px;
        margin-bottom 0;
    }

    #search-form button[type='submit'] {
        float: left;
        margin-left: 5px;
    }

    #search-form .list-inline {
        float: left;
        display: inline-block;
        margin-left: 0;
        margin-bottom: 0;
    }

    #search-form .list-inline .form-group {
        margin-bottom: 0;
        margin-left: 0;
        margin-right: 0;
        display: inline-block;
    }

    #search-form .list-inline .form-group .select2-container--krajee {
        min-width: 120px;
    }

    #search-form .list-inline .form-group input[type='text'] {
        width: 160px;
    }

    #search-form .list-inline .form-group .col-lg-5 {
        width: auto;
    }

    #search-form .list-inline .form-group .col-lg-7 {
        width: auto;
    }
</style>
<?php

echo GridView::widget([
    'id' => 'grid-view',
    'dataProvider' => isset($dataProvider) ? $dataProvider : new ArrayDataProvider([]),
    'model' => $model,
    'url' => Url::toRoute(['/orders/order/aliexpressmessageorderlist',
        'platform_code' => $platformCode,
        'buyer_id' => $buyerId,
        'current_order_id' => $currentOrderId
    ]),
    'layout' => '{filters}{toolBar}{jsScript}{items}',
    'enableTools' => false,
    //'headSummary' => $headSummary,
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
            'field' => 'order_link',
            'headerTitle' => '订单号',
            'type' => 'text',
            'htmlOptions' => [
                'style' => [
                    'word-break' => 'normal'
                ]
            ],
        ],
        [
            'field' => 'account_country_buyer',
            'headerTitle' => '账号<br>国家<br>买家ID',
            'type' => 'text',
            'htmlOptions' => [
            ],
        ],
        [
            'field' => 'order_status_time',
            'headerTitle' => '店铺订单状态',
            'type' => 'text',
            'htmlOptions' => [
            ],
        ],
        [
            'field' => 'pay_time_status',
            'headerTitle' => '付款时间<br>订单状态<br>评价',
            'type' => 'text',
            'htmlOptions' => [
                'align' => 'center',
            ],
        ],
        [
            'field' => 'order_refund_monery',
            'headerTitle' => '订单金额<br>退款金额<br>利润',
            'type' => 'text',
            'htmlOptions' => [
            ],
        ],
        [
            'field' => 'issue_feedback_sale',
            'headerTitle' => '纠纷状态<br>退货编码<br>售后<br/>仓库客诉',
            'type' => 'text',
            'htmlOptions' => [
                'align' => 'center',
            ],
        ],

        [
            'field' => 'package_info',
            'headerTitle' => '包裹信息',
            'type' => 'text',
            'htmlOptions' => [
            ],
        ],
        [
            'field' => 'operation',
            'headerTitle' => Yii::t('system', 'Operation'),
            'type' => 'operateButton',
            'buttons' => [
                [
                    'text' => '永久作废',
                    'condition' => 'row.complete_status < ' .
                        Order::COMPLETE_STATUS_PARTIAL_SHIP . ' || row.complete_status == 99',
                    'href' => Url::toRoute(['/orders/order/cancelorder',
                        'platform' => 'ALI'
                    ]),
                    'queryParams' => [
                        'order_id' => 'order_id',
                    ],
                    'htmlOptions' => [
                        'class' => 'edit-record',
                        '_width' => "30%",
                        '_height' => "60%"
                    ],
                ],
                [
                    'text' => '暂时作废',
                    'condition' => 'row.complete_status < ' .
                        Order::COMPLETE_STATUS_PARTIAL_SHIP . ' || row.complete_status == 99'.' || row.complete_status == 25',
                    'href' => Url::toRoute(['/orders/order/holdorder',
                        'platform' => 'ALI'
                    ]),
                    'queryParams' => [
                        'order_id' => 'order_id',
                    ],
                    'htmlOptions' => [
                        'class' => 'edit-record',
                        '_width' => "30%",
                        '_height' => "60%"
                    ],
                ],
                [
                    'text' => '取消暂时作废',
                    'condition' => 'row.complete_status == ' . Order::COMPLETE_STATUS_HOLD,
                    'href' => Url::toRoute(['/orders/order/cancelholdorder',
                        'platform' => 'ALI'
                    ]),
                    'queryParams' => [
                        'order_id' => 'order_id',
                    ],
                    'htmlOptions' => [
                        'confirm' => '确定取消暂时作废该订单？',
                        'class' => 'ajax-button'
                    ],
                ],
                [
                    'text' => '新建售后单',
                    'condition' => 'row.order_type !="" ' ,
                    'href' => Url::toRoute(['/aftersales/order/add',
                        'platform' => 'ALI',
                        'from' => 'inbox',
                    ]),
                    'queryParams' => [
                        'order_id' => 'order_id',
                    ],
                    'htmlOptions' => [
                        '_width' => '100%',
                        '_height' => '100%',
                        'class' => 'edit-button'
                    ],
                ],
              
                [
                    'text' => '登记退款单',
                    'condition' => 'row.order_type == '.Order::ORDER_TYPE_REDIRECT_ORDER ,
                    'href' => Url::toRoute(['/aftersales/sales/register',
                        'platform' => 'ALI',
                        'from' => 'inbox',
                    ]),
                    'queryParams' => [
                        'order_id' => 'order_id',
                        'platform'=>'platform_code'
                    ],
                    'htmlOptions' => [
                        '_width' => '100%',
                        '_height' => '100%',
                        'class' => 'edit-button'
                    ],
                ],
                
                [
                    'text' => '发票',
                    'href' =>  Url::toRoute(['/orders/order/invoice',
                        'platform' => 'ALI'
                    ]),
                    'queryParams' => [
                        'order_id' => 'order_id',
                    ],
                    'htmlOptions' =>[
                        '_width' => "50%",
                        '_height' => "80%",
                        'class' => "edit-button"
                    ]

                ],
                [
                    'text' => '登记客诉单',
                    'condition' => 'row.order_type !="" ' ,
                    'href' => Url::toRoute(['/aftersales/complaint/register',
                        'platform' => 'ALI',
                        'from' => 'inbox',
                    ]),
                    'queryParams' => [
                        'order_id' => 'order_id',
                    ],
                    'htmlOptions' => [
                        '_width' => '100%',
                        '_height' => '100%',
                        'class' => 'edit-button'
                    ],
                ]
            ],
            'htmlOptions' => [
                'align' => 'center',
                'style' => ['min-width' => '90px']
            ]
        ]
    ],
    'toolBars' => [
        [
            'href' => '#',
            'text' => '更新店铺订单状态',
            'htmlOptions' => [
                'id' => 'batchUpdateShopOrderStatus',
                'class' => 'btn btn-primary',
                'data-src' => 'id',
            ]
        ],
    ]
]);
?>

<div id='updateShopOrderStatusOverlay'>
    <div id='updateShopOrderStatusSpeed'></div>
</div>

<script type="text/javascript">
    $(function () {
        <?php if(empty($isNotReminder)) { ?>
        (function() {
            $("#toolbar").append("<a href='javascript:void(0);' id='notReminderBtn' data-buyerid='<?php echo $buyerId; ?>' class='btn btn-info'>不催付</a>");
        })();
        <?php } else { ?>
        (function() {
            $("#toolbar").append("<a href='javascript:void(0);' id='cancelNotReminderBtn' data-buyerid='<?php echo $buyerId; ?>' class='btn btn-info'>取消不催付</a>");
        })();
        <?php } ?>

        //不催付和取消不催付
        $("#toolbar").on("click", "a#notReminderBtn,a#cancelNotReminderBtn", function () {
            var _this = $(this);
            var id = $(this).attr("id");
            var buyer_id = $(this).attr("data-buyerid");

            if (id == "notReminderBtn") {
                $.post("<?php echo Url::toRoute(['/systems/remindermsgrule/notreminder']) ?>", {
                    "platform_code" : "<?php echo Platform::PLATFORM_CODE_ALI ?>",
                    "buyer_id" : buyer_id
                }, function (data) {
                    if (data["code"] == 1) {
                        layer.msg("设置成功", {icon: 1});
                        _this.attr("id", "cancelNotReminderBtn").text("取消不催付");
                    } else {
                        layer.msg("设置失败," + data["message"], {icon: 5});
                    }
                }, "json");
            } else if (id == "cancelNotReminderBtn") {
                $.post("<?php echo Url::toRoute(['/systems/remindermsgrule/cancelnotreminder']) ?>", {
                    "platform_code" : "<?php echo Platform::PLATFORM_CODE_ALI ?>",
                    "buyer_id" : buyer_id
                }, function (data) {
                    if (data["code"] == 1) {
                        layer.msg("设置成功", {icon: 1});
                        _this.attr("id", "notReminderBtn").text("不催付");
                    } else {
                        layer.msg("设置失败," + data["message"], {icon: 5});
                    }
                }, "json");
            }
            return false;
        });

        $("#batchUpdateShopOrderStatus").on("click", function () {
            var dataSrc = $(this).attr('data-src');
            var checkBox = $('input[name=' + dataSrc + ']:checked');
            if (checkBox.length == 0) {
                layer.alert("请选择更新项");
                return false;
            }

            var defer = $.Deferred();
            defer.resolve($("#updateShopOrderStatusSpeed").html("<p class='success'>店铺订单状态更新开始</p>"));
            $("#updateShopOrderStatusOverlay").css("display", "block");
            $("body").css("overflow", "hidden");

            checkBox.each(function () {
                //获取当前行的平台订单ID
                var orderId = $(this).parents("tr").find("a.platform_order_id").attr("data-orderid");
                defer = defer.then(function () {
                    return $.ajax({
                        type: "POST",
                        url: "<?php echo Url::toRoute(['/orders/order/updatealishoporderstatus']); ?>",
                        data: {"order_id": orderId},
                        dataType: "json",
                        global: false,
                        success: function (data) {
                            if (data["code"] == 1) {
                                $("#updateShopOrderStatusSpeed").append("<p class='success'>订单ID：" + data["data"]["order_id"] + ",更新成功</p>");
                            } else {
                                $("#updateShopOrderStatusSpeed").append("<p class='error'>订单ID：" + data["data"]["order_id"] + "," + data["message"] + "</p>");
                            }
                        }
                    });
                });
            });

            defer.done(function () {
                $("#updateShopOrderStatusSpeed").append("<p class='success'>店铺订单状态更新完毕</p>");
                setTimeout(function() {
                    $("#updateShopOrderStatusOverlay").css("display", "none");
                    window.location.href = window.location.href;
                }, 500);
            });
            return false;
        });

    });
</script>
