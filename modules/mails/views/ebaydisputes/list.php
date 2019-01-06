<?php
use app\components\GridView;
use yii\helpers\Url;
use yii\helpers\Html;
?>
<div id="page-wrapper">
    <!--     <div class="row">
            <div class="col-lg-12">
                <div class="page-header bold">平台列表</div>
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
                        'field' => 'id',
                        'type' => 'checkbox',
                        'htmlOptions' => [
                            'style' => [
                                'vertical-align' => 'middle'
                            ],
                        ],
                    ],
                    [
                        'field' => 'case_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],                
                    
                    [
                    'field' => 'case_type',
                    'type' => 'text',
                    'sortAble' => true,
                    'htmlOptions' => [
                                        'align' => 'center',
                                     ],
                    ],
                                       
                   
                    [
                        'field' => 'case_amount',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],

                        
                    ],
                        
                    
                    [
                        'field' => 'case_quantity',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'creation_date',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'item_id',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'item_title',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                    'field' => 'transaction_id',
                    'type' => 'text',
                    'sortAble' => true,
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                    ],

                    [
                    'field' => 'last_modified_date',
                    'type' => 'text',
                    'sortAble' => true,
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                    ],
   
                    [
                    'field' => 'make_side_role',
                    'type' => 'text',
                    'sortAble' => true,
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                    ],
                    
                    [
                    'field' => 'other_side_role',
                    'type' => 'text',
                    'sortAble' => true,
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                    ],
                    
                    [
                    'field' => 'respond_by_date',
                    'type' => 'text',
                    'sortAble' => true,
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                    ],                    
                    
                    [
                    'field' => 'case_status',
                    'type' => 'text',
                    'sortAble' => true,
                    'htmlOptions' => [
                        'align' => 'center',
                    ],
                    ],                    
                    
                    [
                    'field' => 'account_id',
                    'type' => 'text',
                    'sortAble' => true,
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
