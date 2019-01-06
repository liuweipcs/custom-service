<?php

namespace app\modules\mails\models;

use Yii;

class AmazonResponseReportTask extends \app\modules\mails\models\MailsModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_response_report_task}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['report_type', 'old_account_id', 'processing', 'request_id'], 'required'],
            [['old_account_id', 'scheduled', 'status'], 'integer'],
            [['create_date'], 'safe'],
            [['report_type'], 'string', 'max' => 80],
            [['request_id', 'report_id'], 'string', 'max' => 30],
            [['processing'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'report_type' => '请求类型',
            'old_account_id' => '平台账号ID',
            'start_date' => '开始时间',
            'end_date' => '结束时间',
            'submit_date' => '提交时间',
            'request_id' => '请求ID',
            'report_id' => '响应ID',
            'processing' => '处理状态',
            'scheduled' => '是否是计划生成的',
            'status' => '下一步请求状态',
            'create_date' => 'Create Date',
        ];
    }

    
}
