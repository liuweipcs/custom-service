<?php

namespace app\modules\systems\models;

use Yii;

/**
 * This is the model class for table "{{%amazon_refund_log}}".
 *
 * @property integer $id
 * @property string $request_id
 * @property string $feed_type
 * @property string $submitted_date
 * @property string $feed_submission_id
 * @property string $feed_processing_status
 */
class AmazonRefundLog extends SystemsModel
{   
    const STATUS__AWAITING_ASYNCHRONOUS_REPLY_ = '_AWAITING_ASYNCHRONOUS_REPLY_';//正在处理该请求，但需要等待外部信息才能完成
    const STATUS__CANCELLED_ = '_CANCELLED_';//请求因严重错误而中止
    const STATUS__DONE_ = '_DONE_'; //请求已处理
    const STATUS__IN_PROGRESS_ = '_IN_PROGRESS_';//请求正在处理
    const STATUS__IN_SAFETY_NET_ = '_IN_SAFETY_NET_';//请求正在处理，但系统发现上传数据可能包含潜在错误
    const STATUS__SUBMITTED_ = '_SUBMITTED_';//已收到请求，但尚未开始处理
    const STATUS__UNCONFIRMED_ = '_UNCONFIRMED_';//请求等待中
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_refund_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['xml', 'account_name', 'request_id', 'feed_type', 'submitted_date', 'feed_submission_id', 'feed_processing_status'], 'safe'],
            [[ 'request_id', 'feed_type', 'feed_submission_id', 'account_name'], 'string', 'max' => 50],
            [['feed_processing_status'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'xml' => 'XML',
            'request_id' => 'Request ID',
            'feed_type' => 'Feed Type',
            'submitted_date' => 'Submitted Date',
            'feed_submission_id' => 'Feed Submission ID',
            'feed_processing_status' => 'Feed Processing Status',
            'account_name' => 'Account Name',
        ];
    }

    public static function getFeedProcessingStatus()
    {
        return [
            self::STATUS__AWAITING_ASYNCHRONOUS_REPLY_,
            self::STATUS__CANCELLED_,
            self::STATUS__DONE_,
            self::STATUS__IN_PROGRESS_,
            self::STATUS__IN_SAFETY_NET_,
            self::STATUS__SUBMITTED_,
            self::STATUS__UNCONFIRMED_
        ];
    }

    public static function getList($limit)
    {   
        //请求因严重错误而中止,请求已处理,请求正在处理，但系统发现上传数据可能包含潜在错误状态的请求不用开启计划任务获取结果
        $need_get_result_status = [
            self::STATUS__AWAITING_ASYNCHRONOUS_REPLY_,
            self::STATUS__IN_PROGRESS_,
            self::STATUS__SUBMITTED_,
            self::STATUS__UNCONFIRMED_,
        ];

        $query = self::find();
        //退款完成和退款中的状态将不再进行计划任务退款
        $query->where(['in', 'feed_processing_status', $need_get_result_status]);

        $query->limit($limit);

        //返回需要进行计划任务获取请求结果的列表
        return $query->all();
    }
}
