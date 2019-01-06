<?php

use app\components\GridView;
use yii\helpers\Url;

switch ($platform_code) {
    case "AMAZON":
        $this->title = 'Amazon';
        break;
    case "EB":
        $this->title = 'Ebay';
        break;
    case "WISH":
        $this->title = 'Wish';
        break;
    case "ALI":
        $this->title = 'Aliexpress';
        break;
    case "WALMART":
        $this->title = 'Walmart';
        break;
    case "CDISCOUNT":
        $this->title = 'Cdiscount';
        break;
}
if (!empty($this->title))
    $this->title .= '发送消息列表';
?>
<style>
    .select2-container--krajee {
        width: 170px !important;
    }

    #search-form .input-group.date {
        width: 320px;
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
                        'field' => 'platform_code',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'subject',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
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
                        'field' => 'send_status_text',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'send_failure_reason',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'send_time',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'create_by',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'create_time',
                        'type' => 'text',
                        'sortAble' => true,
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'modify_by',
                        'type' => 'text',
                        'htmlOptions' => [
                        ],
                    ],
                    [
                        'field' => 'modify_time',
                        'type' => 'text',
                        'sortAble' => true,
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
                        'field' => 'account_short_name',
                        'type' => 'text',
                        'htmlOptions' => [
                            'align' => 'center',
                        ],
                    ],
                    [
                        'field' => 'operation',
                        'headerTitle' => Yii::t('system', 'Operation'),
                        'type' => 'operateButton',
                        'buttons' => [
                            [
                                'text' => \Yii::t('mail_outbox', 'Re-Send'),
                                'href' => Url::toRoute(['/mails/mailoutbox/resend', 'platform_code' => $platform_code]),
                                'htmlOptions' => [
                                    'class' => 'delete-record'
                                ],
                            ],
                            [
                                'text' => '修改主题',
                                'href' => Url::toRoute(['/mails/mailoutbox/update', 'platform_code' => $platform_code]),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'edit-record',
                                    '_width' => '70%',
                                    '_height' => '75%',
                                ],
                            ],
                            [
                                'text' => '删除',
                                'href' => Url::toRoute(['/mails/mailoutbox/delete', 'platform_code' => $platform_code]),
                                'queryParams' => '{id}',
                                'htmlOptions' => [
                                    'class' => 'delete-record',
                                    'confirm' => Yii::t('system', 'Confirm Delete The Record')
                                ],
                            ],
                        ],
                        'htmlOptions' => [
                            'align' => 'center',
                            'style' => ['min-width' => '90px']
                        ]
                    ]
                ],
                'toolBars' => [
                    [
                        'href' => Url::toRoute('/mails/mailoutbox/batchupdate'),
                        'text' => '批量修改主题',
                        'htmlOptions' => [
                            'class' => 'add-tags-button',
                            'data-src' => 'id',
                            '_width' => '70%',
                            '_height' => '75%',
                        ]
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>