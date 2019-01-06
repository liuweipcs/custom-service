<?php

use app\components\GridView;
use yii\helpers\Url;

$this->title = '主动联系买家';
?>
<style>
    .select2-container--krajee {
        width: 160px !important;
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
                        'field' => 'platform_code',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'account_id',
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
                        'field' => 'title',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'content',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'left',
                        ],
                    ],
                    [
                        'field' => 'sender_email',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'receive_email',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'create_by',
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
                ],
            ]);
            ?>
        </div>
    </div>
</div>
<script>
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
</script>