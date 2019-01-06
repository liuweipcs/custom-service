<?php 
use app\components\GridView;
use yii\helpers\Url;

$this->title = '系统日志管理';
?>
<div id="page-wrapper">
<!--     <div class="row">
        <div class="col-lg-12">
            <div class="page-header bold">规则标签列表</div>
        </div>
    </div> -->
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
                    'field' => 'table_name',
                    'type' => 'text',
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                ],
                [
                    'field' => 'change_type_text',
                    'type' => 'text',
                    'htmlOptions' => [ 
                        'align' => 'center',
                    ],  
                ],
                [
                    'field' => 'change_content',
                    'type' => 'text',
                    'htmlOptions' => [ 
                        'align' => 'center',
                    ],  
                ],
                [
                    'field' => 'create_ip',
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